<?php

namespace App\Services\Payments;

class PaymentResponseModel
{
    public array $data;

    public function __construct(array $data)
    {
        $providerReference = $data['provider_reference'] ?? $data['transaction_id'] ?? null;
        $data['provider_reference'] = $providerReference;

        // Compatibilité avec les consommateurs mobiles déjà déployés.
        $data['transaction_id'] ??= $providerReference;
        $this->data = $data;
    }

    public function isPending(): bool
    {
        return in_array($this->data['status'] ?? '', ['PENDING', 'PROCESSING']);
    }

    public function isSuccess(): bool
    {
        return in_array($this->data['status'] ?? '', ['SUCCESS', 'SUCCESSFUL']);
    }
}
