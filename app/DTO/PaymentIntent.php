<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class PaymentIntent extends DataTransferObject
{
    public string $paymentToken;

    public string $providerReference;

    /** @deprecated Utiliser providerReference. */
    public string $transactionId;

    public static function fromArray(array $data): self
    {
        $transaction = data_get($data, 'result.transaction', data_get($data, 'result', []));

        return new self(
            paymentToken: (string) $transaction['pay_token'],
            providerReference: (string) $transaction['id'],
            transactionId: (string) $transaction['id'],
        );
    }
}
