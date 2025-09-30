<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class PaymentIntent extends DataTransferObject
{
    public string $paymentToken;
    public string $transactionId;

    static function fromArray(array $data): self
    {
        return new self(
            paymentToken: $data['result']['pay_token'],
            transactionId: $data['result']['id']
        );
    }
}
