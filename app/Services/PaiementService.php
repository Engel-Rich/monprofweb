<?php

namespace App\Services;

use App\Jobs\SendMailJob;
use App\Models\Codes;
use App\Models\Paiements;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            $finalization = DB::transaction(function () use ($id): ?array {
                $paiement = Paiements::query()->lockForUpdate()->findOrFail($id);
                $quantity = max(1, (int) $paiement->nombre_de_code);
                $existingCodes = Codes::query()
                    ->where('paiements_id', $id)
                    ->pluck('code')
                    ->all();
                $missingCodes = max(0, $quantity - count($existingCodes));

                if ($paiement->paiement_date !== null && $missingCodes === 0) {
                    Log::info('Paiement déjà finalisé.', ['paiement_id' => $id]);

                    return null;
                }

                $user = User::findOrFail($paiement->user_id);

                if ($missingCodes > 0) {
                    $generatedCodes = $this->saveManyCod($id, $missingCodes);

                    if (count($generatedCodes) !== $missingCodes) {
                        throw new RuntimeException('La génération des codes d’activation a échoué.');
                    }
                }

                $codes = Codes::query()
                    ->where('paiements_id', $id)
                    ->orderBy('id')
                    ->pluck('code')
                    ->all();

                if (count($codes) < $quantity) {
                    throw new RuntimeException('Le paiement ne possède pas tous ses codes d’activation.');
                }

                $this->markAsPaid($paiement);

                return [$paiement->fresh(), $user, $codes];
            }, 3);

            if ($finalization === null) {
                return false;
            }

            [$paiement, $user, $codes] = $finalization;
            $quantity = max(1, (int) $paiement->nombre_de_code);
            $codes = array_slice($codes, 0, $quantity);
            $messageService = new SendMessageService($paiement, $user);

            if ($quantity === 1) {
                $code = $codes[0];
                $messageService->sendSMS($code);

                if ($user->fcm_token) {
                    $notification = new PushNotifictaionService(
                        "Votre paiement a été validé et votre code généré.\nVous recevrez le code par SMS.\nMonProf vous remercie.",
                        'Validation de compte MonProf',
                    );
                    $notification->sendNotificationToToken($user->fcm_token);
                }

                Log::info('Code généré et envoyé, en attente d’utilisation par un élève.', [
                    'paiement_id' => $id,
                    'code_status' => 'AVAILABLE',
                ]);

                return true;
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
