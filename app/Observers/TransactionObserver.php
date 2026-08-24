<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\Payments\TransactionAuditService;

class TransactionObserver
{
    public function __construct(private readonly TransactionAuditService $audit) {}

    public function created(Transaction $transaction): void
    {
        $this->audit->record(
            transaction: $transaction,
            event: 'transaction.created',
            source: 'model',
            changes: ['after' => $transaction->getAttributes()],
            statusTo: $transaction->status,
        );
    }

    public function updated(Transaction $transaction): void
    {
        $changes = collect($transaction->getChanges())->except('updated_at')->all();

        if ($changes === []) {
            return;
        }

        $before = collect(array_keys($changes))
            ->mapWithKeys(fn (string $key): array => [$key => $transaction->getOriginal($key)])
            ->all();

        $this->audit->record(
            transaction: $transaction,
            event: array_key_exists('status', $changes) ? 'transaction.status_changed' : 'transaction.updated',
            source: 'model',
            changes: ['before' => $before, 'after' => $changes],
            statusFrom: isset($before['status']) ? (string) $before['status'] : null,
            statusTo: isset($changes['status']) ? (string) $changes['status'] : $transaction->status,
        );
    }

    public function deleting(Transaction $transaction): void
    {
        $this->audit->record(
            transaction: $transaction,
            event: 'transaction.deleting',
            source: 'model',
            statusFrom: $transaction->status,
            statusTo: $transaction->status,
        );
    }
}
