<?php

namespace App\Services\Payments;

use App\Models\Transaction;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TransactionAuditService
{
    public function record(
        Transaction $transaction,
        string $event,
        string $source = 'application',
        ?array $payload = null,
        ?array $changes = null,
        ?string $statusFrom = null,
        ?string $statusTo = null,
        ?string $providerCode = null,
    ): ?TransactionLog {
        if (! Schema::hasTable('transaction_logs')) {
            return null;
        }

        try {
            return TransactionLog::query()->create([
                'transaction_id' => $transaction->getKey(),
                'local_reference' => $transaction->reference,
                'provider_reference' => $transaction->provider_reference,
                'provider_code' => $providerCode ?? $transaction->provider?->code,
                'event' => $event,
                'source' => $source,
                'status_from' => $statusFrom,
                'status_to' => $statusTo ?? $transaction->status,
                'actor_id' => Auth::id(),
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'changes' => $this->sanitize($changes),
                'payload' => $this->sanitize($payload),
            ]);
        } catch (Throwable $exception) {
            // L'audit ne doit jamais interrompre un paiement déjà engagé.
            Log::warning('Impossible d’enregistrer le journal de transaction.', [
                'local_transaction_id' => $transaction->getKey(),
                'event' => $event,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as $key => $value) {
            if (preg_match('/secret|password|authorization|api[-_]?key|signature/i', (string) $key)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
