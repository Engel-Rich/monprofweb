<?php

namespace App\Services\Admin;

use App\Mail\ActivationCodeMail;
use App\Models\Categorie;
use App\Models\Codes;
use App\Models\Eleve;
use App\Models\Paiements;
use App\Models\User;
use App\Services\CourseAccessService;
use App\Services\PaiementService;
use App\Services\SendMessageService;
use App\Support\PhoneNumber;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Codes d'activation offerts par l'administration, sans paiement.
 *
 * Le code repose sur un paiement à 0 marqué ADMIN_GRANT : l'activation, la
 * révocation et le contrôle d'accès restent ceux d'un code acheté.
 */
class ActivationCodeGrantService
{
    public function __construct(
        private readonly PaiementService $paiementService,
        private readonly CourseAccessService $courseAccess,
    ) {}

    public function grant(Eleve $student, Categorie $category, User $admin, ?string $phone, ?string $reason): Codes
    {
        return DB::transaction(function () use ($student, $category, $admin, $phone, $reason): Codes {
            // Sérialise les attributions pour un même élève.
            Eleve::query()->whereKey($student->id)->lockForUpdate()->first();

            if ($this->courseAccess->studentHasCategoryAccess($student, $category->id)) {
                throw new DomainException("Cet élève a déjà un abonnement actif pour la catégorie {$category->libelle}.");
            }

            $phone = filled($phone) ? PhoneNumber::local($phone) : PhoneNumber::local($student->user?->phone);

            $paiement = Paiements::create([
                'user_id' => $student->user_id,
                'categorie_id' => $category->id,
                'nombre_de_code' => 1,
                'montant' => 0,
                'numero_payeur' => $phone,
                'numero_client' => $phone ?: null,
                'status' => true,
                'paiement_date' => now(),
                'origin' => Paiements::ORIGIN_ADMIN_GRANT,
                'granted_by' => $admin->id,
                'grant_reason' => filled($reason) ? trim($reason) : null,
            ]);

            $this->paiementService->saveOneCode($paiement->id);

            Log::info('Code d’activation attribué depuis la console.', [
                'paiement_id' => $paiement->id,
                'eleve_id' => $student->id,
                'categorie_id' => $category->id,
                'admin_id' => $admin->id,
            ]);

            return $paiement->codes()->with('paiement.categorie', 'paiement.user')->firstOrFail();
        });
    }

    public function sendSms(Codes $code): void
    {
        $paiement = $this->deliverablePayment($code);

        if (! PhoneNumber::isValidCameroonMobile($paiement->numero_client)) {
            throw new DomainException('Aucun numéro mobile valide n’est associé à ce code.');
        }

        $sent = (new SendMessageService($paiement, $paiement->user))
            ->sendSMS(message: $this->smsMessage($code));

        if (! $sent) {
            throw new DomainException('Le fournisseur SMS a refusé ou interrompu l’envoi.');
        }
    }

    public function sendEmail(Codes $code): void
    {
        $paiement = $this->deliverablePayment($code);
        $email = $paiement->user->email;

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Aucune adresse e-mail valide n’est associée à cet élève.');
        }

        try {
            Mail::to($email)->send(new ActivationCodeMail($code));
        } catch (Throwable $exception) {
            Log::error('Envoi du code d’activation par e-mail impossible.', [
                'code_id' => $code->id,
                'exception' => $exception,
            ]);

            throw new DomainException('Le serveur e-mail a refusé ou interrompu l’envoi.');
        }
    }

    public function smsMessage(Codes $code): string
    {
        $category = $code->paiement?->categorie?->libelle;

        return "MonProf : votre code d'activation est {$code->code}"
            .($category ? " (catégorie {$category})" : '')
            .". Saisissez-le dans l'application pour débloquer vos cours.";
    }

    private function deliverablePayment(Codes $code): Paiements
    {
        $code->loadMissing('paiement.categorie', 'paiement.user');
        $paiement = $code->paiement;

        if (! $paiement?->isAdminGrant()) {
            throw new DomainException('Seuls les codes attribués depuis la console peuvent être envoyés ici.');
        }

        if ($code->revoked_at !== null || $paiement->revoked_at !== null) {
            throw new DomainException('Ce code est révoqué : il ne peut plus être envoyé.');
        }

        if ($code->actif) {
            throw new DomainException('Ce code a déjà été activé.');
        }

        if (! $paiement->user) {
            throw new DomainException('Le compte de l’élève est introuvable.');
        }

        return $paiement;
    }
}
