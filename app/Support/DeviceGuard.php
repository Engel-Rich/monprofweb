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
    /**
     * Liste normalisée des adresses exemptées du contrôle d'appareil.
     *
     * La normalisation est faite ici plutôt que dans le fichier de
     * configuration : elle s'applique ainsi aux adresses déclarées dans le code
     * comme à celles ajoutées par variable d'environnement, et elle n'est pas
     * figée par « config:cache ».
     *
     * @return array<int, string>
     */
    public static function bypassEmails(): array
    {
        $configured = config('devices.bypass_emails', []);

        // Tolère une liste déjà éclatée comme une chaîne « a@b.com, c@d.com ».
        $values = is_array($configured)
            ? $configured
            : preg_split('/[\s,;]+/', (string) $configured, -1, PREG_SPLIT_NO_EMPTY);

        $emails = [];

        foreach ($values ?: [] as $value) {
            $email = strtolower(trim((string) $value));

            // Une entrée mal saisie ne doit surtout pas ouvrir une brèche : on
            // ignore tout ce qui n'est pas une adresse valide.
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    /** Compte exempté du contrôle (comptes de test/recette). */
    public static function bypasses(?string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        return in_array(strtolower(trim($email)), self::bypassEmails(), true);
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
