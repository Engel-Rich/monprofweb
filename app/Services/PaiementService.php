<?php

namespace App\Services;

use App\DTO\PaymentFinalization;
use App\Jobs\SendMailJob;
use App\Models\Codes;
use App\Models\Paiements;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class PaiementService
{
    // Sans 0/O, 1/I/L : le code est recopié à la main depuis un SMS.
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 10;

    private const CODE_ATTEMPTS = 5;

    public function __construct(private readonly CourseAccessService $courseAccess) {}

    /**
     * Finalise un paiement une seule fois, même si le webhook et le polling
     * reçoivent le succès simultanément, puis envoie les notifications.
     */
    public function validePayment(array $request): bool
    {
        $id = (int) ($request['paiement'] ?? 0);

        if ($id <= 0) {
            throw new InvalidArgumentException('Identifiant de paiement manquant.');
        }

        $finalization = $this->finalizePayment($id);

        if ($finalization === null) {
            return false;
        }

        $this->notifyFinalization($finalization);

        return true;
    }

    /**
     * Génère les codes, marque le paiement payé et active le code si le
     * payeur est l'élève. N'envoie rien : l'appelant appelle
     * notifyFinalization() une fois ses propres verrous libérés.
     */
    public function finalizePayment(int $id): ?PaymentFinalization
    {
        $lock = Cache::lock("paiement:finalize:{$id}", 60);

        if (! $lock->get()) {
            Log::info('Finalisation du paiement déjà en cours.', ['paiement_id' => $id]);

            return null;
        }

        try {
            return DB::transaction(function () use ($id): ?PaymentFinalization {
                $paiement = Paiements::query()->lockForUpdate()->findOrFail($id);

                if ($paiement->revoked_at !== null) {
                    throw new RuntimeException('Cet abonnement est révoqué et ne peut pas être réactivé.');
                }

                $quantity = max(1, (int) $paiement->nombre_de_code);
                $missingCodes = max(0, $quantity - Codes::query()->where('paiements_id', $id)->count());

                if ($paiement->paiement_date !== null && $missingCodes === 0) {
                    Log::info('Paiement déjà finalisé.', ['paiement_id' => $id]);

                    return null;
                }

                $user = User::findOrFail($paiement->user_id);
                $this->saveManyCod($id, $missingCodes);
                $this->markAsPaid($paiement);

                $codes = Codes::query()
                    ->where('paiements_id', $id)
                    ->orderBy('id')
                    ->limit($quantity)
                    ->pluck('code')
                    ->all();

                return new PaymentFinalization(
                    paiement: $paiement,
                    user: $user,
                    codes: $codes,
                    activatedCode: $this->courseAccess->autoActivateForPayer($paiement),
                );
            }, 3);
        } finally {
            $lock->release();
        }
    }

    public function notifyFinalization(PaymentFinalization $finalization): void
    {
        $paiement = $finalization->paiement;
        $user = $finalization->user;
        $codes = $finalization->codes;
        $quantity = count($codes);
        $messageService = new SendMessageService($paiement, $user);

        if ($finalization->activatedCode) {
            $category = $paiement->categorie?->libelle;
            $unlocked = $category
                ? "les cours de la catégorie {$category} sont débloqués"
                : 'vos cours sont débloqués';

            $messageService->sendSMS(message: "MonProf : votre paiement est validé et votre code {$finalization->activatedCode->code} a été activé automatiquement, {$unlocked}.");

            if ($user->fcm_token) {
                $notification = new PushNotifictaionService(
                    "Votre paiement a été validé et votre code activé automatiquement : {$unlocked}.\nMonProf vous remercie.",
                    'Validation des cours dans Monprof',
                );
                $notification->sendNotificationToToken($user->fcm_token, even_type: 'ACTIVATION');
            }

            Log::info('Code généré et activé automatiquement pour l’élève payeur.', [
                'paiement_id' => $paiement->id,
                'code_id' => $finalization->activatedCode->id,
                'eleve_id' => $finalization->activatedCode->eleve_id,
            ]);

            return;
        }

        if ($quantity === 1) {
            $messageService->sendSMS($codes[0]);

            if ($user->fcm_token) {
                $notification = new PushNotifictaionService(
                    "Votre paiement a été validé et votre code généré.\nVous recevrez le code par SMS.\nMonProf vous remercie.",
                    'Validation de compte MonProf',
                );
                $notification->sendNotificationToToken($user->fcm_token);
            }

            Log::info('Code généré et envoyé, en attente d’utilisation par un élève.', [
                'paiement_id' => $paiement->id,
                'code_status' => 'AVAILABLE',
            ]);

            return;
        }

        SendMailJob::dispatch($messageService, $codes)->delay(now());

        if ($user->fcm_token) {
            $notification = new PushNotifictaionService(
                "Votre paiement de {$quantity} codes a été validé.\nVous recevrez la liste des codes par e-mail.\nMonProf vous remercie.",
                'Validation de compte MonProf',
            );
            $notification->sendNotificationToToken($user->fcm_token, even_type: 'PAYMENT');
        }

        Log::info('Liste des codes générée avec succès.', [
            'paiement_id' => $paiement->id,
            'quantity' => $quantity,
        ]);
    }

    public function genererCodeActivation(): string
    {
        $max = strlen(self::CODE_ALPHABET) - 1;
        $code = '';

        for ($index = 0; $index < self::CODE_LENGTH; $index++) {
            $code .= self::CODE_ALPHABET[random_int(0, $max)];
        }

        return 'C'.$code;
    }

    /**
     * L'index unique sur codes.code fait foi : une collision, très
     * improbable, provoque simplement un nouveau tirage.
     */
    public function saveOneCode(int $paiementId): string
    {
        for ($attempt = 1; ; $attempt++) {
            $code = $this->genererCodeActivation();

            try {
                Codes::create([
                    'paiements_id' => $paiementId,
                    'code' => $code,
                ]);

                return $code;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= self::CODE_ATTEMPTS) {
                    throw new RuntimeException('La génération des codes d’activation a échoué.', previous: $exception);
                }
            }
        }
    }

    /** @return array<int, string> */
    public function saveManyCod(int $paiementId, int $quantity): array
    {
        $codes = [];

        for ($index = 0; $index < $quantity; $index++) {
            $codes[] = $this->saveOneCode($paiementId);
        }

        return $codes;
    }

    private function markAsPaid(Paiements $paiement): void
    {
        $paiement->forceFill([
            'paiement_date' => now(),
            'status' => true,
        ])->save();
    }
}
