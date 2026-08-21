<?php

namespace App\Services\Payments;

use App\DTO\PaymentResult;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\PaiementService;
use App\Services\PushNotifictaionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TransactionFinalizationService
{
    public function __construct(private readonly PaiementService $paiementService) {}

    public function applyProviderResult(
        Transaction $transaction,
        PaymentResult $result,
        string $provider,
        string $source = 'polling',
    ): bool {
        if (! $result->status || $result->status === TransactionStatus::ERROR) {
            Log::warning('Vérification fournisseur temporairement indisponible.', [
                'transaction_id' => $transaction->id,
                'provider' => $provider,
                'error' => $result->error,
            ]);

            return false;
        }

        $providerReason = $result->error
            ?? data_get($result->paymentResponseModel?->data, 'reason')
            ?? data_get($result->paymentResponseModel?->data, 'message');
        $reason = is_scalar($providerReason) ? (string) $providerReason : null;

        return $this->applyStatus(
            transaction: $transaction,
            status: $result->status,
            context: [
                'provider' => $provider,
                'provider_response' => $result->paymentResponseModel?->data,
            ],
            reason: $reason,
            source: $source,
        );
    }

    public function applyStatus(
        Transaction $transaction,
        TransactionStatus $status,
        array $context = [],
        ?string $reason = null,
        string $source = 'webhook',
    ): bool {
        $lock = Cache::lock("transaction:finalize:{$transaction->id}", 30);

        if (! $lock->get()) {
            Log::info('Finalisation déjà en cours pour la transaction.', ['transaction_id' => $transaction->id]);

            return false;
        }

        try {
            $transaction = Transaction::query()
                ->with(['paiement', 'provider'])
                ->find($transaction->id);

            if (! $transaction) {
                return false;
            }

            $targetStatus = $this->databaseStatus($status);
            $metadata = $this->appendCheckMetadata($transaction, $targetStatus, $source, $context);

            if ($transaction->status === TransactionStatus::SUCCESS->value
                && $targetStatus !== TransactionStatus::SUCCESS->value) {
                $transaction->update(['metadatas' => $metadata]);
                Log::warning('Statut fournisseur tardif ignoré après un succès définitif.', [
                    'transaction_id' => $transaction->id,
                    'received_status' => $targetStatus,
                ]);

                return false;
            }

            if ($transaction->status === TransactionStatus::FAILED->value
                && $targetStatus === TransactionStatus::PENDING->value) {
                $transaction->update(['metadatas' => $metadata]);

                return false;
            }

            if ($targetStatus === TransactionStatus::PENDING->value) {
                $transaction->update(['metadatas' => $metadata]);

                return false;
            }

            if ($targetStatus === TransactionStatus::SUCCESS->value
                && strtoupper((string) $transaction->internal_service) === 'MONPROF_PURCHASE') {
                $paiement = $transaction->paiement;

                if (! $paiement) {
                    Log::warning('Paiement associé encore introuvable, la transaction reste en attente.', [
                        'transaction_id' => $transaction->id,
                    ]);

                    $transaction->update(['metadatas' => $metadata]);

                    return false;
                }

                $this->paiementService->validePayment([
                    ...$context,
                    'paiement' => $paiement->id,
                ]);

                $paiement->refresh();
                $expectedCodes = max(1, (int) $paiement->nombre_de_code);

                if (! $paiement->status
                    || blank($paiement->paiement_date)
                    || $paiement->codes()->count() < $expectedCodes) {
                    Log::warning('Le paiement n’est pas complètement finalisé, la transaction reste en attente.', [
                        'transaction_id' => $transaction->id,
                        'paiement_id' => $paiement->id,
                    ]);
                    $transaction->update(['metadatas' => $metadata]);

                    return false;
                }
            }

            $previousStatus = $transaction->status;
            $updates = [
                'status' => $targetStatus,
                'metadatas' => $metadata,
            ];

            if ($targetStatus === TransactionStatus::FAILED->value && filled($reason)) {
                $updates['raison_reject'] = $reason;
            }

            $transaction->update($updates);

            if ($previousStatus !== $targetStatus) {
                $this->notifyUser($transaction->fresh(), $reason);
            }

            Log::info('Transaction réconciliée avec le fournisseur.', [
                'transaction_id' => $transaction->id,
                'source' => $source,
                'previous_status' => $previousStatus,
                'status' => $targetStatus,
            ]);

            return true;
        } finally {
            $lock->release();
        }
    }

    private function databaseStatus(TransactionStatus $status): string
    {
        return match ($status) {
            TransactionStatus::SUCCESS => TransactionStatus::SUCCESS->value,
            TransactionStatus::FAILED,
            TransactionStatus::CANCELLED => TransactionStatus::FAILED->value,
            default => TransactionStatus::PENDING->value,
        };
    }

    private function appendCheckMetadata(
        Transaction $transaction,
        string $status,
        string $source,
        array $context,
    ): array {
        $metadata = is_array($transaction->metadatas) ? $transaction->metadatas : [];
        $checks = is_array($metadata['status_checks'] ?? null) ? $metadata['status_checks'] : [];
        $checks[] = [
            'source' => $source,
            'status' => $status,
            'checked_at' => now()->toIso8601String(),
            ...$context,
        ];

        $metadata['status_checks'] = array_slice($checks, -10);
        $metadata['last_status_check_at'] = now()->toIso8601String();

        return $metadata;
    }

    private function notifyUser(Transaction $transaction, ?string $reason): void
    {
        $user = $transaction->user_id ? \App\Models\User::find($transaction->user_id) : null;

        if (! $user?->fcm_token) {
            return;
        }

        $success = $transaction->status === TransactionStatus::SUCCESS->value;
        $notification = new PushNotifictaionService(
            $success
                ? "Votre paiement de {$transaction->amount} a été validé avec succès.\nMonProf vous remercie."
                : "Votre paiement de {$transaction->amount} a échoué.\nMotif : ".($reason ?: 'Indéterminé'),
            $success ? 'Paiement validé MonProf' : 'Paiement échoué MonProf',
        );

        $notification->sendNotificationToToken(
            $user->fcm_token,
            even_type: 'PAYMENT_STATUS',
            data: [
                'amount' => (string) $transaction->amount,
                'transaction_id' => (string) $transaction->id,
                'status' => (string) $transaction->status,
                'raison_reject' => (string) ($reason ?? ''),
            ],
        );
    }
}
