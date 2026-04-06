<?php

namespace App\Services\Payments;

class PaymentResponseModel
{
    public array $data;

    public function __construct(array $data)
    {
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