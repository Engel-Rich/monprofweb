<?php

namespace App\Jobs;

use App\DTO\WebhookHandlingDTO;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
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

        $transaction = $query->first();

        if (! $transaction) {
            Log::error('Transaction du webhook introuvable.', [
                'transaction_id' => $this->dto->id,
                'external_reference' => $this->dto->externalReference,
            ]);

            return;
        }

        $status = match (strtoupper($this->dto->status)) {
            'SUCCESS', 'SUCCESSFUL', 'PAID' => TransactionStatus::SUCCESS,
            'FAILED', 'UNPAID', 'CANCELLED', 'CANCELED' => TransactionStatus::FAILED,
            'PENDING', 'PROCESSING' => TransactionStatus::PENDING,
            default => null,
        };

        if (! $status) {
            Log::warning('Statut de webhook non reconnu.', [
                'transaction_id' => $transaction->id,
                'status' => $this->dto->status,
            ]);

            return;
        }

        $finalizer->applyStatus(
            transaction: $transaction,
            status: $status,
            context: ['webhook_data' => $this->dto->toArray()],
            reason: $this->dto->raisonReject,
            source: 'webhook',
        );
    }
}
