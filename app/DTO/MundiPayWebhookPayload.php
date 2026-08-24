<?php

namespace App\DTO;

final class MundiPayWebhookPayload
{
    public static function normalize(array $payload): WebhookHandlingDTO
    {
        $transaction = data_get($payload, 'result.transaction')
            ?? data_get($payload, 'result')
            ?? data_get($payload, 'transaction')
            ?? data_get($payload, 'data')
            ?? [];

        if (! is_array($transaction)) {
            $transaction = [];
        }

        return new WebhookHandlingDTO(
            providerCode: 'MUNDIPAY',
            providerReference: self::stringOrNull(
                $payload['provider_reference']
                    ?? $payload['transaction_id']
                    ?? $transaction['id']
                    ?? $payload['id']
                    ?? null
            ),
            localReference: self::stringOrNull(
                $payload['local_reference']
                    ?? $payload['external_reference']
                    ?? $transaction['local_reference']
                    ?? null
            ),
            status: strtoupper((string) ($transaction['status'] ?? $payload['status'] ?? 'PENDING')),
            paymentToken: self::stringOrNull($transaction['pay_token'] ?? $payload['pay_token'] ?? null),
            reason: self::stringOrNull(
                $transaction['reason']
                    ?? $payload['raison_reject']
                    ?? $payload['message']
                    ?? null
            ),
            payload: $payload,
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) && filled((string) $value) ? (string) $value : null;
    }
}
