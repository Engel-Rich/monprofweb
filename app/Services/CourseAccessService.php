<?php

namespace App\Services;

use App\Models\Codes;
use App\Models\Eleve;
use App\Models\Paiements;
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

            return $this->activate($code, $student);
        }, 3);
    }

    /**
     * Active automatiquement le code d'un paiement d'un seul code lorsque le
     * payeur est lui-même l'élève et n'a pas encore accès à la catégorie.
     * Un parent ne choisit pas l'élève, et un lot de codes peut servir à
     * plusieurs élèves : dans ces cas le code reste disponible.
     *
     * Doit être appelée dans la transaction qui a validé le paiement.
     */
    public function autoActivateForPayer(Paiements $paiement): ?Codes
    {
        if ((int) $paiement->nombre_de_code !== 1) {
            return null;
        }

        $student = Eleve::query()->where('user_id', $paiement->user_id)->first();

        if (! $student || $this->studentHasCategoryAccess($student, (int) $paiement->categorie_id)) {
            return null;
        }

        $code = Codes::query()
            ->where('paiements_id', $paiement->id)
            ->lockForUpdate()
            ->first();

        if (! $code || $code->actif || $code->revoked_at !== null) {
            return null;
        }

        $code->setRelation('paiement', $paiement);

        return $this->activate($code, $student);
    }

    /**
     * Règle d'activation commune à la saisie manuelle et à l'activation
     * automatique. Le code doit avoir été chargé avec lockForUpdate.
     */
    private function activate(Codes $code, Eleve $student): Codes
    {
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

        // Verrouiller l'élève sérialise ses activations : deux codes de la
        // même catégorie ne peuvent pas passer le contrôle en même temps.
        Eleve::query()->whereKey($student->id)->lockForUpdate()->first();

        if ($this->studentHasCategoryAccess($student, (int) $code->paiement->categorie_id)) {
            $category = $code->paiement->categorie?->libelle;

            throw new DomainException($category
                ? "Vous avez déjà un abonnement actif pour la catégorie {$category}."
                : 'Vous avez déjà un abonnement actif pour cette catégorie.');
        }

        $code->forceFill([
            'active_date' => now(),
            'actif' => true,
            'eleve_id' => $student->id,
        ])->save();

        return $code->fresh(['paiement.categorie']);
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
