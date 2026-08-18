<?php

namespace App\Console\Commands;

use App\Enums\TransactionStatus;
use App\Jobs\VerifyPendingTransaction;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyPendingTransactions extends Command
{
    protected $signature = 'payments:verify-pending
                            {--duration= : Durée totale de la boucle en secondes}
                            {--interval= : Intervalle entre deux passages en secondes}
                            {--chunk= : Nombre de transactions chargées par lot}
                            {--once : Effectuer un seul passage sans attendre}';

    protected $description = 'Vérifie les transactions en attente auprès de leur fournisseur de paiement';

    public function handle(): int
    {
        $duration = max(1, (int) ($this->option('duration') ?: config('payments.polling.duration', 55)));
        $interval = max(1, (int) ($this->option('interval') ?: config('payments.polling.interval', 5)));
        $chunk = max(1, (int) ($this->option('chunk') ?: config('payments.polling.chunk', 100)));
        $once = (bool) $this->option('once');
        $lock = Cache::lock('payments:verify-pending-command', $duration + 10);

        if (! $lock->get()) {
            $this->components->warn('Une vérification des transactions est déjà en cours.');

            return self::SUCCESS;
        }

        $deadline = microtime(true) + ($once ? 1 : $duration);
        $passes = 0;
        $processed = 0;

        try {
            do {
                $passStartedAt = microtime(true);
                $passes++;
                $processed += $this->verifyPass($chunk, $deadline);

                if ($once || microtime(true) >= $deadline) {
                    break;
                }

                $sleepFor = min(
                    max(0, $interval - (microtime(true) - $passStartedAt)),
                    max(0, $deadline - microtime(true)),
                );

                if ($sleepFor > 0) {
                    usleep((int) ($sleepFor * 1_000_000));
                }
            } while (microtime(true) < $deadline);
        } finally {
            $lock->release();
        }

        $this->components->info("{$processed} vérification(s) exécutée(s) en {$passes} passage(s).");

        return self::SUCCESS;
    }

    private function verifyPass(int $chunk, float $deadline): int
    {
        $processed = 0;

        Transaction::query()
            ->whereIn('status', [
                TransactionStatus::PENDING->value,
                TransactionStatus::PROCESSING->value,
            ])
            ->whereNotNull('transaction_id')
            ->orderBy('id')
            ->chunkById($chunk, function ($transactions) use (&$processed, $deadline): bool {
                foreach ($transactions as $transaction) {
                    if (microtime(true) >= $deadline) {
                        return false;
                    }

                    try {
                        VerifyPendingTransaction::dispatchSync($transaction->id);
                    } catch (Throwable $exception) {
                        Log::error('Échec inattendu de la vérification d’une transaction.', [
                            'transaction_id' => $transaction->id,
                            'exception' => $exception,
                        ]);
                    }
                    $processed++;
                }

                return true;
            });

        return $processed;
    }
}
