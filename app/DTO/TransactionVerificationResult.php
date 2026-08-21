<?php

namespace App\DTO;

class TransactionVerificationResult
{
    public function __construct(
        public readonly int $transactionId,
        public readonly string $outcome,
        public readonly ?string $providerStatus = null,
        public readonly ?string $message = null,
    ) {}

    public function isError(): bool
    {
        return $this->outcome === 'ERROR';
    }

    public function toArray(): array
    {
        return [
            'transaction' => $this->transactionId,
            'resultat' => $this->outcome,
            'statut_provider' => $this->providerStatus ?? '-',
            'message' => $this->message ?? '-',
        ];
    }
}
