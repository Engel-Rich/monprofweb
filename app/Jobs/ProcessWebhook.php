<?php

namespace App\Jobs;

use App\DTO\WebhookHandlingDTO;
use App\Enums\TransactionStatus;
use App\Models\PaymentProvider;
use App\Models\Transaction;
use App\Services\Payments\PaymentFactory;
use App\Services\Payments\TransactionAuditService;
use App\Services\Payments\TransactionFinalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly WebhookHandlingDTO $dto) {}

    public function handle(
        TransactionFinalizationService $finalizer,
        TransactionAuditService $audit,
    ): void {
        if (blank($this->dto->providerReference)
            && blank($this->dto->localReference)
            && blank($this->dto->paymentToken)) {
            Log::error('Webhook reçu sans identifiant de transaction.');

            return;
        }

        $provider = PaymentProvider::query()
            ->where('code', strtoupper($this->dto->providerCode))
            ->first();

        if (! $provider) {
            Log::error('Fournisseur du webhook introuvable.', ['provider' => $this->dto->providerCode]);

            return;
        }

        $query = Transaction::query()
            ->where('payment_provider_id', $provider->id)
            ->where(function ($query): void {
                $hasCondition = false;

                if (filled($this->dto->providerReference)) {
                    $query->where('provider_reference', $this->dto->providerReference);
                    $hasCondition = true;
                }

                if (filled($this->dto->localReference)) {
                    $method = $hasCondition ? 'orWhere' : 'where';
                    $query->{$method}('reference', $this->dto->localReference);
                    $hasCondition = true;
                }

                if (filled($this->dto->paymentToken)) {
                    $method = $hasCondition ? 'orWhere' : 'where';
                    $query->{$method}('payment_token', $this->dto->paymentToken);
                }
            });

        $transaction = $query->with('provider')->first();

        if (! $transaction) {
            Log::error('Transaction du webhook introuvable.', [
                'provider' => $this->dto->providerCode,
                'provider_reference' => $this->dto->providerReference,
                'local_reference' => $this->dto->localReference,
                'payment_token' => $this->dto->paymentToken,
            ]);

            return;
        }

        // La notification peut arriver avant que l'initiation n'ait eu le temps
        // d'écrire la référence fournisseur : on la récupère ici, sinon le
        // poller n'aurait plus rien pour interroger le fournisseur.
        $audit->record(
            transaction: $transaction,
            event: 'provider.webhook_received',
            source: 'webhook',
            payload: $this->dto->payload,
            providerCode: $provider->code,
        );

        if (blank($transaction->provider_reference) && filled($this->dto->providerReference)) {
            $transaction->update(['provider_reference' => $this->dto->providerReference]);
            $transaction->refresh();
        }

        if (blank($transaction->payment_token) && filled($this->dto->paymentToken)) {
            $transaction->update(['payment_token' => $this->dto->paymentToken]);
            $transaction->refresh();
        }

        $providerReference = $this->dto->providerReference ?: $transaction->provider_reference;

        if (! $provider || blank($providerReference)) {
            Log::warning('Webhook non vérifiable : fournisseur ou référence manquants.', [
                'local_transaction_id' => $transaction->id,
                'provider' => $provider?->code,
                'reference' => $providerReference,
            ]);

            return;
        }

        // L'endpoint de callback est public : le statut du corps de la requête
        // n'est qu'un déclencheur. Le statut faisant foi est celui que le
        // fournisseur renvoie sur son API.
        try {
            $result = PaymentFactory::make($provider)->verifyPayment(
                (string) $providerReference,
                $this->dto->paymentToken ?: $transaction->payment_token,
            );
        } catch (\Throwable $exception) {
            Log::error('Vérification du webhook auprès du fournisseur impossible.', [
                'local_transaction_id' => $transaction->id,
                'provider' => $provider->code,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if (! $result->status || $result->status === TransactionStatus::ERROR) {
            $finalizer->applyProviderResult(
                transaction: $transaction,
                result: $result,
                provider: $provider->code,
                source: 'webhook',
            );

            // Rien n'est appliqué : la transaction reste en attente et le
            // scheduler la reprendra au passage suivant.
            Log::warning('Webhook reçu mais statut fournisseur indisponible.', [
                'local_transaction_id' => $transaction->id,
                'provider' => $provider->code,
                'error' => $result->error,
                'webhook_status' => $this->dto->status,
            ]);

            return;
        }

        $finalizer->applyProviderResult(
            transaction: $transaction,
            result: $result,
            provider: $provider->code,
            source: 'webhook',
        );
    }
}
