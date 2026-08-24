<?php

namespace App\Console\Commands;

use App\DTO\TransactionVerificationResult;
use App\Enums\TransactionStatus;
use App\Jobs\VerifyPendingTransaction;
use App\Models\Transaction;
use App\Services\Payments\TransactionFinalizationService;
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
                            {--no-expire : Ne pas expirer les transactions trop anciennes}
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
            $results = $this->expireStaleTransactions($chunk);

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
            ->when(
                $this->option('transaction'),
                // Vérification manuelle ciblée : on court-circuite les filtres.
                fn ($query, $id) => $query->whereKey((int) $id),
                // Sans référence fournisseur il n'y a rien à interroger, et au-delà
                // de max_age la transaction est expirée : la repoller indéfiniment
                // saturait le quota du fournisseur.
                fn ($query) => $query
                    ->whereNotNull('provider_reference')
                    ->where('provider_reference', '!=', '')
                    ->where('created_at', '>=', now()->subMinutes($this->maxAge())),
            )
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
                            'local_transaction_id' => $transaction->id,
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

    private function maxAge(): int
    {
        return max(1, (int) config('payments.polling.max_age', 180));
    }

    /**
     * Ferme les transactions restées en attente au-delà de la fenêtre de polling.
     * Après plusieurs heures d'interrogations infructueuses, le paiement mobile
     * n'aboutira plus : on le marque échoué une bonne fois et on cesse d'appeler
     * le fournisseur pour cette transaction.
     *
     * @return array<int, TransactionVerificationResult>
     */
    private function expireStaleTransactions(int $chunk): array
    {
        if (
            $this->option('dry-run')
            || $this->option('no-expire')
            || $this->option('transaction')
            || ! config('payments.polling.expire_stale', true)
        ) {
            return [];
        }

        $finalizer = app(TransactionFinalizationService::class);
        $cutoff = now()->subMinutes($this->maxAge());
        $results = [];

        Transaction::query()
            ->whereRaw('UPPER(status) IN (?, ?)', [
                TransactionStatus::PENDING->value,
                TransactionStatus::PROCESSING->value,
            ])
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById($chunk, function ($transactions) use (&$results, $finalizer): void {
                foreach ($transactions as $transaction) {
                    try {
                        $finalizer->applyStatus(
                            transaction: $transaction,
                            status: TransactionStatus::FAILED,
                            context: ['expired_after_minutes' => $this->maxAge()],
                            reason: 'Délai de paiement dépassé, aucune confirmation du fournisseur.',
                            source: 'expiration',
                        );

                        $results[] = new TransactionVerificationResult(
                            $transaction->id,
                            TransactionStatus::FAILED->value,
                            null,
                            'Transaction expirée après '.$this->maxAge().' minutes sans confirmation.',
                        );
                    } catch (Throwable $exception) {
                        Log::error('Échec de l’expiration d’une transaction en attente.', [
                            'local_transaction_id' => $transaction->id,
                            'exception' => $exception,
                        ]);
                    }
                }
            });

        if ($results !== []) {
            $this->components->info(count($results).' transaction(s) expirée(s).');
        }

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
