<?php

namespace App\DTO;

class WebhookHandlingDTO
{
    public function __construct(
        public readonly string $providerCode,
        public readonly ?string $providerReference,
        public readonly ?string $localReference,
        public readonly string $status,
        public readonly ?string $paymentToken = null,
        public readonly ?string $reason = null,
        public readonly array $payload = [],
    ) {}

    /** @deprecated Utiliser CampayWebhookPayload::normalize(). */
    public static function fromArray(array $data): self
    {
        return CampayWebhookPayload::normalize($data);
    }

    /**
     * Useful for logs / debugging
     */
    public function toString(): string
    {
        return json_encode([
            'provider' => $this->providerCode,
            'provider_reference' => $this->providerReference,
            'local_reference' => $this->localReference,
            'status' => $this->status,
            'pay_token' => $this->paymentToken,
            'raison_reject' => $this->reason,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->providerCode,
            'provider_reference' => $this->providerReference,
            'local_reference' => $this->localReference,
            'status' => $this->status,
            'pay_token' => $this->paymentToken,
            'raison_reject' => $this->reason,
        ];
    }
}
