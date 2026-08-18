<?php

namespace App\Services;

use App\Jobs\SendMailJob;
use App\Models\Codes;
use App\Models\Paiements;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class PaiementService
{
    /**
     * Finalise un paiement une seule fois, même si le webhook et le polling
     * reçoivent le succès simultanément.
     */
    public function validePayment(array $request): bool
    {
        $id = (int) ($request['paiement'] ?? 0);

        if ($id <= 0) {
            throw new InvalidArgumentException('Identifiant de paiement manquant.');
        }

        $lock = Cache::lock("paiement:finalize:{$id}", 60);

        if (! $lock->get()) {
            Log::info('Finalisation du paiement déjà en cours.', ['paiement_id' => $id]);

            return false;
        }

        try {
            $paiement = Paiements::findOrFail($id);

            if ($paiement->paiement_date !== null) {
                Log::info('Paiement déjà finalisé.', ['paiement_id' => $id]);

                return false;
            }

            $user = User::findOrFail($paiement->user_id);
            $quantity = max(1, (int) $paiement->nombre_de_code);
            $messageService = new SendMessageService($paiement, $user);

            if ($quantity === 1) {
                $code = $this->saveOneCode($id);

                if (! $code) {
                    throw new RuntimeException('La génération du code d’activation a échoué.');
                }

                $this->markAsPaid($paiement);
                $messageService->sendSMS($code);

                if ($user->fcm_token) {
                    $notification = new PushNotifictaionService(
                        "Votre paiement a été validé et votre code activé.\nVous recevrez le code par SMS.\nMonProf vous remercie.",
                        'Validation de compte MonProf',
                    );
                    $notification->sendNotificationToToken($user->fcm_token);
                }

                Log::info('Code activé avec succès.', ['paiement_id' => $id]);

                return true;
            }

            $codes = $this->saveManyCod($id, $quantity);

            if (count($codes) !== $quantity) {
                throw new RuntimeException('La génération de la liste des codes a échoué.');
            }

            $this->markAsPaid($paiement);
            SendMailJob::dispatch($messageService, $codes, $user->fcm_token)->delay(now());

            if ($user->fcm_token) {
                $notification = new PushNotifictaionService(
                    "Votre paiement de {$quantity} codes a été validé.\nVous recevrez la liste des codes par e-mail.\nMonProf vous remercie.",
                    'Validation de compte MonProf',
                );
                $notification->sendNotificationToToken($user->fcm_token, even_type: 'PAYMENT');
            }

            Log::info('Liste des codes générée avec succès.', [
                'paiement_id' => $id,
                'quantity' => $quantity,
            ]);

            return true;
        } finally {
            $lock->release();
        }
    }

    public function genererCodeActivation(int $paiementId): string
    {
        $seed = now()->format('mdY').$paiementId;
        $code = '';

        for ($index = 0; $index < 10; $index++) {
            $code .= $seed[random_int(0, strlen($seed) - 1)];
        }

        return 'C'.$code;
    }

    public function saveOneCode(int $paiementId): ?string
    {
        try {
            $code = $this->genererCodeActivation($paiementId);

            while (Codes::where('code', $code)->exists()) {
                $code = $this->genererCodeActivation($paiementId);
            }

            Codes::create([
                'paiements_id' => $paiementId,
                'code' => $code,
            ]);

            return $code;
        } catch (\Throwable $exception) {
            Codes::where('paiements_id', $paiementId)->delete();
            Log::error('Génération du code de paiement impossible.', [
                'paiement_id' => $paiementId,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    public function saveManyCod(int $paiementId, int $quantity): array
    {
        $codes = [];

        for ($index = 0; $index < $quantity; $index++) {
            $code = $this->saveOneCode($paiementId);

            if (! $code) {
                Codes::where('paiements_id', $paiementId)->delete();

                return [];
            }

            $codes[] = $code;
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
