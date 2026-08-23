<?php

namespace App\Support;

/**
 * Normalisation des numéros camerounais.
 *
 * Les numéros arrivent sous des formes très variées selon la saisie mobile :
 * « 690000000 », « +237 690 00 00 00 », « 00237690000000 ». Chaque consommateur
 * (CamPay, passerelle SMS) attend un format précis, d'où ce point unique.
 */
class PhoneNumber
{
    /** Chiffres significatifs, sans indicatif pays (ex : 690000000). */
    public static function local(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '00237')) {
            return substr($digits, 5);
        }

        if (str_starts_with($digits, '237')) {
            return substr($digits, 3);
        }

        return $digits;
    }

    /** Format international sans « + » (ex : 237690000000). */
    public static function msisdn(?string $phone): string
    {
        return '237'.self::local($phone);
    }

    /** Numéro mobile camerounais valide (9 chiffres commençant par 6). */
    public static function isValidCameroonMobile(?string $phone): bool
    {
        return (bool) preg_match('/^6[0-9]{8}$/', self::local($phone));
    }
}
