<?php

namespace App\Enums;

/**
 * Codes d'erreur applicatifs renvoyés aux clients mobiles.
 *
 * Le mobile doit s'appuyer sur ces codes — jamais sur le texte du message ni sur
 * le seul statut HTTP — pour décider quoi afficher ou vers quel écran router.
 * Toute valeur ajoutée ici doit l'être aussi dans
 * monprofapp/lib/corps/utils/api_error_code.dart.
 */
enum ApiErrorCode: string
{
    /** L'en-tête « phone-emei » est absent de la requête. */
    case DEVICE_ID_MISSING = 'DEVICE_ID_MISSING';

    /** Le compte est rattaché à un autre téléphone : réinitialisation par OTP requise. */
    case DEVICE_NOT_AUTHORIZED = 'DEVICE_NOT_AUTHORIZED';

    public function message(): string
    {
        return match ($this) {
            self::DEVICE_ID_MISSING => 'Veuillez renseigner un identifiant de téléphone',
            self::DEVICE_NOT_AUTHORIZED => 'Vous devez vous connecter avec votre ancien téléphone',
        };
    }

    /**
     * Corps de réponse normalisé.
     *
     * « error » et « message » portent le même texte : les anciennes versions du
     * mobile ne lisent que l'un ou l'autre selon l'écran.
     */
    public function response(array $data = [], ?string $message = null): array
    {
        $message ??= $this->message();

        return [
            'status' => false,
            'code' => $this->value,
            'error' => $message,
            'message' => $message,
            'data' => $data === [] ? null : $data,
        ];
    }
}
