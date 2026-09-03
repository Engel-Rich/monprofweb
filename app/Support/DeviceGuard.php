<?php

namespace App\Support;

use App\Models\User;

/**
 * Règles de rattachement d'un compte à un téléphone.
 *
 * Centralise ce qui était dupliqué (et divergent) entre le middleware
 * PhoneEmei et le contrôleur de connexion.
 */
class DeviceGuard
{
    /** Compte exempté du contrôle (comptes de test/recette). */
    public static function bypasses(?string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        $allowed = array_map('strtolower', (array) config('devices.bypass_emails', []));

        return in_array(strtolower($email), $allowed, true);
    }

    /** Le rôle de cet utilisateur est-il soumis au verrouillage ? */
    public static function appliesTo(User $user): bool
    {
        return (int) $user->rule_id === (int) config('devices.locked_rule_id', 2);
    }

    public static function isAuthorized(User $user, ?string $deviceId): bool
    {
        if (self::bypasses($user->email) || ! self::appliesTo($user)) {
            return true;
        }

        return filled($deviceId)
            && hash_equals((string) $user->user_phone_emei, (string) $deviceId);
    }

    /**
     * Numéro partiellement masqué, affiché par le mobile pour rappeler sur quel
     * numéro le code de réinitialisation sera envoyé — sans divulguer le numéro
     * complet dans une réponse d'erreur.
     */
    public static function maskPhone(?string $phone): ?string
    {
        // Normalisé d'abord : le masque ne doit pas dépendre de la façon dont le
        // numéro a été saisi (« 690… », « +237 690… », « 00237690… »).
        $digits = PhoneNumber::local($phone);

        if (strlen($digits) < 4) {
            return null;
        }

        return str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
