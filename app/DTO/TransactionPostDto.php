<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class TransactionPostDto extends DataTransferObject
{
    public ?string $transaction_id;
    public ?string $payment_token;
    public ?string $reference;
    public string $amount;
    public string $phone_number;
    public ?string $status; // "PENDING", "SUCCESS", "FAILED"
    public ?string $sens; // "IN", "OUT"
    public ?string $service_id;
    public ?string $internal_service; // default "MONPROF_PURCHASE"
    public ?string $subscription_id;
    public ?string $user_id;

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'payment_token' => $this->payment_token,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'phone_number' => $this->phone_number,
            'status' => $this->status ?? 'PENDING',
            'sens' => $this->sens ?? 'IN',
            'service_id' => $this->service_id,
            'internal_service' => $this->internal_service ?? 'MONPROF_PURCHASE',
            'subscription_id' => $this->subscription_id,
            'user_id' => $this->user_id,
        ];
    }
}


class TransactionUpdateDto extends DataTransferObject
{
    public ?string $status; // "PENDING", "SUCCESS", "FAILED"
    public ?string $transaction_id;
    public ?string $payment_token;
    public ?string $raison_reject;

    public function toArray(): array
    {
        $data = [];
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->transaction_id !== null) {
            $data['transaction_id'] = $this->transaction_id;
        }
        if ($this->payment_token !== null) {
            $data['payment_token'] = $this->payment_token;
        }
        if ($this->raison_reject !== null) {
            $data['raison_reject'] = $this->raison_reject;
        }
        return $data;
    }
}
