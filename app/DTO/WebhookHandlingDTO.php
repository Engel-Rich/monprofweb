<?php

namespace App\DTO;

class WebhookHandlingDTO
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $reference,
        public readonly ?string $externalReference,
        public readonly string $status,
        public readonly ?string $payToken = null,
        public readonly ?string $raisonReject = null,
    ) {}

    /**
     * Build DTO from request array
     */
    public static function fromArray(array $data): self
    {
        $reference = $data['reference'] ?? null;

        $externalReference = isset($data['external_reference'])
            ? 'MPP-' . $data['external_reference']
            : null;

        $id = $reference === null
            ? ($data['transaction_id'] ?? null)
            : $reference;

        return new self(
            id: $id,
            reference: $reference,
            externalReference: $externalReference,
            status: $data['status'],
            payToken: $data['pay_token'] ?? null,
            raisonReject: $data['raison_reject'] ?? null,
        );
    }

    /**
     * Useful for logs / debugging
     */
    public function toString(): string
    {
        return json_encode([
            'id' => $this->id,
            'reference' => $this->reference,
            'external_reference' => $this->externalReference,
            'status' => $this->status,
            'pay_token' => $this->payToken,
            'raison_reject' => $this->raisonReject,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->id,
            'reference' => $this->reference,
            'external_reference' => $this->externalReference,
            'status' => $this->status,
            'pay_token' => $this->payToken,
            'raison_reject' => $this->raisonReject,
        ];
    }
}