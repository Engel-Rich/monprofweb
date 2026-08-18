<?php

namespace App\Jobs;

use App\Enums\TransactionStatus;
use App\Models\PayementServices;
use App\Models\PaymentProvider;
use App\Models\Transaction;
use App\Services\Payments\PaymentFactory;
use App\Services\Payments\TransactionFinalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyPendingTransaction implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 45;

    public function __construct(public readonly int $transactionId) {}

    public function uniqueId(): string
    {
        return (string) $this->transactionId;
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(TransactionFinalizationService $finalizer): void
    {
        $transaction = Transaction::query()
            ->with(['provider', 'paymentService.provider'])
            ->find($this->transactionId);

        if (! $transaction || ! in_array($transaction->status, [
            TransactionStatus::PENDING->value,
            TransactionStatus::PROCESSING->value,
        ], true)) {
            return;
        }

        if (blank($transaction->transaction_id)) {
            Log::warning('Transaction en attente sans identifiant fournisseur.', [
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        $provider = $this->resolveProvider($transaction);

        if (! $provider) {
            Log::error('Aucun fournisseur ne permet de vérifier la transaction.', [
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        if (! $transaction->payment_provider_id) {
            $transaction->update(['payment_provider_id' => $provider->id]);
        }

        $strategy = PaymentFactory::make($provider);
        $result = $strategy->verifyPayment($transaction->transaction_id);

        $finalizer->applyProviderResult(
            transaction: $transaction,
            result: $result,
            provider: $provider->code,
        );
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
