<?php

namespace App\Jobs;

use App\DTO\TransactionVerificationResult;
use App\Enums\TransactionStatus;
use App\Models\PayementServices;
use App\Models\PaymentProvider;
use App\Models\Transaction;
use App\Services\Payments\PaymentFactory;
use App\Services\Payments\TransactionFinalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyPendingTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly int $transactionId,
        public readonly bool $dryRun = false,
    ) {}

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(TransactionFinalizationService $finalizer): TransactionVerificationResult
    {
        $lock = Cache::lock("transaction:verify:{$this->transactionId}", 45);

        if (! $lock->get()) {
            return new TransactionVerificationResult(
                $this->transactionId,
                'SKIPPED',
                message: 'Une vérification de cette transaction est déjà en cours.',
            );
        }

        try {
            $transaction = Transaction::query()
                ->with(['provider', 'paymentService.provider'])
                ->find($this->transactionId);

            if (! $transaction) {
                return new TransactionVerificationResult($this->transactionId, 'ERROR', message: 'Transaction introuvable.');
            }
            if (! in_array(strtoupper((string) $transaction->status), [
                TransactionStatus::PENDING->value,
                TransactionStatus::PROCESSING->value,
            ], true)) {
                return new TransactionVerificationResult(
                    $transaction->id,
                    'SKIPPED',
                    strtoupper((string) $transaction->status),
                    'La transaction n’est plus en attente.',
                );
            }

            // La référence fournisseur n'est écrite qu'après l'appel d'initiation :
            // tant qu'elle est vide, interroger le provider produit une URL
            // «/transaction//» à laquelle CamPay répond 403. On repasse plus tard.
            if (blank($transaction->transaction_id)) {
                return new TransactionVerificationResult(
                    $transaction->id,
                    'SKIPPED',
                    strtoupper((string) $transaction->status),
                    'Référence fournisseur pas encore disponible, vérification reportée.',
                );
            }

            $provider = $this->resolveProvider($transaction);

            if (! $provider) {
                throw new \RuntimeException('Aucun fournisseur ne permet de vérifier la transaction.');
            }

            if (! $transaction->payment_provider_id) {
                $transaction->update(['payment_provider_id' => $provider->id]);
            }

            $strategy = PaymentFactory::make($provider);
            $result = $strategy->verifyPayment((string) $transaction->transaction_id);

            if (! $result->status || $result->status === TransactionStatus::ERROR) {
                throw new \RuntimeException($result->error ?: 'Le fournisseur n’a retourné aucun statut exploitable.');
            }

            if ($this->dryRun) {
                return new TransactionVerificationResult(
                    $transaction->id,
                    $result->status->value,
                    $result->status->value,
                    "Simulation via {$provider->code} : aucune donnée locale modifiée.",
                );
            }

            $applied = $finalizer->applyProviderResult(
                transaction: $transaction,
                result: $result,
                provider: $provider->code,
            );
            $localStatus = strtoupper((string) $transaction->fresh()->status);

            if (
                $result->status === TransactionStatus::SUCCESS
                && ! $applied
                && $localStatus !== TransactionStatus::SUCCESS->value
            ) {
                return new TransactionVerificationResult(
                    $transaction->id,
                    'ERROR',
                    $result->status->value,
                    'Le provider confirme le succès, mais la finalisation locale du paiement ou des codes est encore incomplète.',
                );
            }

            return new TransactionVerificationResult(
                $transaction->id,
                $localStatus,
                $result->status->value,
                "Vérification effectuée via {$provider->code}.",
            );
        } catch (Throwable $exception) {
            Log::error('Échec de la vérification d’une transaction en attente.', [
                'transaction_id' => $this->transactionId,
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return new TransactionVerificationResult(
                $this->transactionId,
                'ERROR',
                message: $exception->getMessage(),
            );
        } finally {
            $lock->release();
        }
    }

    private function resolveProvider(Transaction $transaction): ?PaymentProvider
    {
        if ($transaction->provider) {
            return $transaction->provider;
        }

        if ($transaction->paymentService?->provider) {
            return $transaction->paymentService->provider;
        }

        if (filled($transaction->subscription_id)) {
            $service = PayementServices::query()
                ->with('provider')
                ->where('subscription_id', $transaction->subscription_id)
                ->when($transaction->sens, fn ($query, $sens) => $query->where('sens', $sens))
                ->first();

            if ($service?->provider) {
                return $service->provider;
            }
        }

        return PaymentProvider::active()->first();
    }
}
