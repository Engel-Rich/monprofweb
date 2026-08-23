<?php

namespace App\Jobs;

use App\DTO\WebhookHandlingDTO;
use App\Enums\TransactionStatus;
use App\Models\PaymentProvider;
use App\Models\Transaction;
use App\Services\Payments\PaymentFactory;
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

    public function __construct(protected WebhookHandlingDTO $dto) {}

    public function handle(TransactionFinalizationService $finalizer): void
    {
        if (blank($this->dto->id) && blank($this->dto->externalReference)) {
            Log::error('Webhook reçu sans identifiant de transaction.');

            return;
        }

        $query = Transaction::query();

        if (filled($this->dto->id)) {
            $query->where('transaction_id', $this->dto->id);
        }

        if (filled($this->dto->externalReference)) {
            $query->when(
                filled($this->dto->id),
                fn ($query) => $query->orWhere('reference', $this->dto->externalReference),
                fn ($query) => $query->where('reference', $this->dto->externalReference),
            );
        }

        $transaction = $query->with('provider')->first();

        if (! $transaction) {
            Log::error('Transaction du webhook introuvable.', [
                'transaction_id' => $this->dto->id,
                'external_reference' => $this->dto->externalReference,
            ]);

            return;
        }

        // La notification peut arriver avant que l'initiation n'ait eu le temps
        // d'écrire la référence fournisseur : on la récupère ici, sinon le
        // poller n'aurait plus rien pour interroger le fournisseur.
        if (blank($transaction->transaction_id) && filled($this->dto->reference)) {
            $transaction->update(['transaction_id' => $this->dto->reference]);
            $transaction->refresh();
        }

        $providerReference = $this->dto->reference ?: $transaction->transaction_id;
        $provider = $transaction->provider ?? PaymentProvider::active()->first();

        if (! $provider || blank($providerReference)) {
            Log::warning('Webhook non vérifiable : fournisseur ou référence manquants.', [
                'transaction_id' => $transaction->id,
                'provider' => $provider?->code,
                'reference' => $providerReference,
            ]);

            return;
        }

        // L'endpoint de callback est public : le statut du corps de la requête
        // n'est qu'un déclencheur. Le statut faisant foi est celui que le
        // fournisseur renvoie sur son API.
        try {
            $result = PaymentFactory::make($provider)->verifyPayment((string) $providerReference);
        } catch (\Throwable $exception) {
            Log::error('Vérification du webhook auprès du fournisseur impossible.', [
                'transaction_id' => $transaction->id,
                'provider' => $provider->code,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if (! $result->status || $result->status === TransactionStatus::ERROR) {
            // Rien n'est appliqué : la transaction reste en attente et le
            // scheduler la reprendra au passage suivant.
            Log::warning('Webhook reçu mais statut fournisseur indisponible.', [
                'transaction_id' => $transaction->id,
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
