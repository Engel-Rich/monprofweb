<?php

namespace App\DTO;

final class CampayWebhookPayload
{
    public static function normalize(array $payload): WebhookHandlingDTO
    {
        $providerReference = $payload['provider_reference'] ?? $payload['reference'] ?? $payload['transaction_id'] ?? null;
        $localReference = $payload['local_reference'] ?? $payload['external_reference'] ?? null;

        if (filled($localReference) && ! str_starts_with((string) $localReference, 'MPP-')) {
            $localReference = 'MPP-'.$localReference;
        }

        return new WebhookHandlingDTO(
            providerCode: 'CAMPAY',
            providerReference: self::stringOrNull($providerReference),
            localReference: self::stringOrNull($localReference),
            status: strtoupper((string) ($payload['status'] ?? 'PENDING')),
            paymentToken: self::stringOrNull($payload['pay_token'] ?? null),
            reason: self::stringOrNull($payload['raison_reject'] ?? null),
            payload: $payload,
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) && filled((string) $value) ? (string) $value : null;
    }
}
