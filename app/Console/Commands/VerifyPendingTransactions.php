<?php

namespace App\Console\Commands;

use App\DTO\TransactionVerificationResult;
use App\Enums\TransactionStatus;
use App\Jobs\VerifyPendingTransaction;
use App\Models\Transaction;
use Illuminate\Bus\Dispatcher;
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
                            {--transaction= : ID local d’une transaction précise à vérifier}
                            {--dry-run : Interroger le provider sans modifier les paiements ni les transactions}
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

        $deadline = $once ? null : microtime(true) + $duration;
        $passes = 0;
        $results = [];

        try {
            do {
                $passStartedAt = microtime(true);
                $passes++;
                array_push($results, ...$this->verifyPass($chunk, $deadline));

                if ($once || ($deadline !== null && microtime(true) >= $deadline)) {
                    break;
                }

                $sleepFor = min(
                    max(0, $interval - (microtime(true) - $passStartedAt)),
                    max(0, $deadline - microtime(true)),
                );

                if ($sleepFor > 0) {
                    usleep((int) ($sleepFor * 1_000_000));
                }
            } while ($deadline !== null && microtime(true) < $deadline);
        } catch (Throwable $exception) {
            Log::error('Impossible d’exécuter la vérification des transactions.', [
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }

        $this->renderResults($results, $passes);

        if ($this->option('transaction') && $results === []) {
            $this->components->error('La transaction indiquée est introuvable ou n’est plus en attente.');

            return self::FAILURE;
        }

        return collect($results)->contains(fn (TransactionVerificationResult $result) => $result->isError())
            ? self::FAILURE
            : self::SUCCESS;
    }

    /** @return array<int, TransactionVerificationResult> */
    private function verifyPass(int $chunk, ?float $deadline): array
    {
        $results = [];

        Transaction::query()
            ->whereRaw('UPPER(status) IN (?, ?)', [
                TransactionStatus::PENDING->value,
                TransactionStatus::PROCESSING->value,
            ])
            ->when($this->option('transaction'), fn ($query, $id) => $query->whereKey((int) $id))
            ->orderBy('id')
            ->chunkById($chunk, function ($transactions) use (&$results, $deadline): bool {
                foreach ($transactions as $transaction) {
                    if ($deadline !== null && microtime(true) >= $deadline) {
                        return false;
                    }

                    try {
                        $result = app(Dispatcher::class)->dispatchNow(
                            new VerifyPendingTransaction(
                                $transaction->id,
                                (bool) $this->option('dry-run'),
                            )
                        );
                        $results[] = $result instanceof TransactionVerificationResult
                            ? $result
                            : new TransactionVerificationResult($transaction->id, 'ERROR', message: 'Résultat de vérification invalide.');
                    } catch (Throwable $exception) {
                        Log::error('Échec inattendu de la vérification d’une transaction.', [
                            'transaction_id' => $transaction->id,
                            'exception' => $exception,
                        ]);
                        $results[] = new TransactionVerificationResult(
                            $transaction->id,
                            'ERROR',
                            message: $exception->getMessage(),
                        );
                    }
                }

                return true;
            });

        return $results;
    }

    /** @param array<int, TransactionVerificationResult> $results */
    private function renderResults(array $results, int $passes): void
    {
        if ($this->output->isVerbose() && $results !== []) {
            $this->table(
                ['Transaction', 'Résultat', 'Statut provider', 'Message'],
                array_map(fn (TransactionVerificationResult $result) => array_values($result->toArray()), $results),
            );
        }

        $counts = collect($results)->countBy(fn (TransactionVerificationResult $result) => $result->outcome);
        $this->components->info(sprintf(
            '%d vérification(s), %d succès, %d en attente, %d échec(s), %d ignorée(s), %d erreur(s), en %d passage(s).',
            count($results),
            (int) $counts->get(TransactionStatus::SUCCESS->value, 0),
            (int) $counts->get(TransactionStatus::PENDING->value, 0),
            (int) $counts->get(TransactionStatus::FAILED->value, 0),
            (int) $counts->get('SKIPPED', 0),
            (int) $counts->get('ERROR', 0),
            $passes,
        ));
    }
}
