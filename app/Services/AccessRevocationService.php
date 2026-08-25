<?php

namespace App\Services;

use App\Models\Codes;
use App\Models\Paiements;
use Illuminate\Support\Facades\DB;

class AccessRevocationService
{
    public function revokeCode(Codes $code, int $administratorId, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($code, $administratorId, $reason): bool {
            $lockedCode = Codes::query()->lockForUpdate()->findOrFail($code->id);

            if ($lockedCode->revoked_at !== null) {
                return false;
            }

            $lockedCode->forceFill($this->revocationData($administratorId, $reason))->save();

            return true;
        }, 3);
    }

    public function revokeSubscription(Paiements $payment, int $administratorId, ?string $reason = null): int
    {
        return DB::transaction(function () use ($payment, $administratorId, $reason): int {
            $lockedPayment = Paiements::query()->lockForUpdate()->findOrFail($payment->id);
            $data = $this->revocationData($administratorId, $reason);

            if ($lockedPayment->revoked_at === null) {
                $lockedPayment->forceFill($data)->save();
            }

            Codes::query()
                ->where('paiements_id', $lockedPayment->id)
                ->whereNull('revoked_at')
                ->update([...$data, 'updated_at' => now()]);

            return Codes::query()->where('paiements_id', $lockedPayment->id)->count();
        }, 3);
    }

    /** @return array{revoked_at: \Illuminate\Support\Carbon, revoked_by: int, revocation_reason: string} */
    private function revocationData(int $administratorId, ?string $reason): array
    {
        return [
            'revoked_at' => now(),
            'revoked_by' => $administratorId,
            'revocation_reason' => filled($reason)
                ? trim((string) $reason)
                : 'Révocation manuelle depuis la console administrateur.',
        ];
    }
}
