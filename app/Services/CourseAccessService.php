<?php

namespace App\Services;

use App\Models\Codes;
use App\Models\Eleve;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CourseAccessService
{
    public function activateCodeForUser(User $user, string $value): Codes
    {
        return DB::transaction(function () use ($user, $value): Codes {
            $student = Eleve::query()->where('user_id', $user->id)->first();

            if (! $student) {
                throw new DomainException('Aucun profil élève trouvé pour cet utilisateur.');
            }

            $code = Codes::query()
                ->with(['paiement.categorie'])
                ->where('code', trim($value))
                ->lockForUpdate()
                ->first();

            if (! $code) {
                throw new DomainException('Ce code d’activation est invalide.');
            }

            if ($code->revoked_at !== null || $code->paiement?->revoked_at !== null) {
                throw new DomainException('Ce code d’activation a été révoqué par un administrateur.');
            }

            if (! $code->paiement
                || ! $code->paiement->status
                || blank($code->paiement->paiement_date)) {
                throw new DomainException('Le paiement associé à ce code n’est pas encore validé.');
            }

            if ($code->actif) {
                if ((int) $code->eleve_id === (int) $student->id) {
                    return $code;
                }

                throw new DomainException('Ce code d’activation a déjà été utilisé.');
            }

            $code->forceFill([
                'active_date' => now(),
                'actif' => true,
                'eleve_id' => $student->id,
            ])->save();

            return $code->fresh(['paiement.categorie']);
        }, 3);
    }

    public function studentHasCategoryAccess(Eleve $student, int $categoryId): bool
    {
        return Codes::query()
            ->where('eleve_id', $student->id)
            ->where('actif', true)
            ->whereNull('revoked_at')
            ->whereHas('paiement', function ($query) use ($categoryId): void {
                $query->where('categorie_id', $categoryId)
                    ->where('status', true)
                    ->whereNull('revoked_at')
                    ->whereNotNull('paiement_date');
            })
            ->exists();
    }
}
