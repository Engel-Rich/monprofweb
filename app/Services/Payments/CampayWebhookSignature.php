<?php

namespace App\Services\Payments;

/**
 * Vérifie la signature JWT (HS256) que CamPay joint à chaque notification.
 *
 * La clé est celle affichée sous « WEBHOOK KEY » dans le tableau de bord CamPay.
 * Sans cette vérification, n'importe qui peut appeler l'endpoint public de
 * callback et faire passer une transaction en SUCCESS.
 */
class CampayWebhookSignature
{
    public static function isValid(?string $token, ?string $key): bool
    {
        if (blank($token) || blank($key)) {
            return false;
        }

        $segments = explode('.', (string) $token);

        if (count($segments) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $segments;

        $decodedHeader = json_decode(self::base64UrlDecode($header) ?: '', true);

        if (! is_array($decodedHeader) || strtoupper((string) ($decodedHeader['alg'] ?? '')) !== 'HS256') {
            return false;
        }

        $expected = hash_hmac('sha256', "{$header}.{$payload}", (string) $key, true);

        return hash_equals(self::base64UrlEncode($expected), $signature);
    }

    private static function base64UrlDecode(string $value): string|false
    {
        $remainder = strlen($value) % 4;

        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
