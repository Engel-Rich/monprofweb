<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class TransactionPostDto extends DataTransferObject
{
    public ?string $provider_reference;

    public ?string $payment_token;

    public ?string $reference;

    public string $amount;

    public ?string $base_amount;

    public ?string $service_fee;

    public ?string $provider_fee_percentage;

    public ?string $user_fee_percentage;

    public string $phone_number;

    public ?string $status; // "PENDING", "SUCCESS", "FAILED"

    public ?string $sens; // "IN", "OUT"

    public ?string $service_id;

    public ?string $internal_service; // default "MONPROF_PURCHASE"

    public ?string $subscription_id;

    public ?string $user_id;

    public function __construct(array $data)
    {
        $this->provider_reference = $data['provider_reference'] ?? $data['transaction_id'] ?? null;
        $this->payment_token = $data['payment_token'] ?? null;
        $this->reference = $data['reference'] ?? null;
        $this->amount = $data['amount'];
        $this->base_amount = isset($data['base_amount']) ? (string) $data['base_amount'] : null;
        $this->service_fee = isset($data['service_fee']) ? (string) $data['service_fee'] : null;
        $this->provider_fee_percentage = isset($data['provider_fee_percentage']) ? (string) $data['provider_fee_percentage'] : null;
        $this->user_fee_percentage = isset($data['user_fee_percentage']) ? (string) $data['user_fee_percentage'] : null;
        $this->phone_number = $data['phone_number'];
        $this->status = $data['status'] ?? null;
        $this->sens = $data['sens'] ?? null;
        $this->service_id = $data['service_id'] ?? null;
        $this->internal_service = $data['internal_service'] ?? null;
        $this->subscription_id = $data['subscription_id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'provider_reference' => $this->provider_reference,
            'payment_token' => $this->payment_token,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'base_amount' => $this->base_amount,
            'service_fee' => $this->service_fee,
            'provider_fee_percentage' => $this->provider_fee_percentage,
            'user_fee_percentage' => $this->user_fee_percentage,
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

    public ?string $provider_reference;

    public ?string $payment_token;

    public ?string $raison_reject;

    public function __construct(array $data)
    {
        $this->status = $data['status'] ?? null;
        $this->provider_reference = $data['provider_reference'] ?? $data['transaction_id'] ?? null;
        $this->payment_token = $data['payment_token'] ?? null;
        $this->raison_reject = $data['raison_reject'] ?? null;
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->provider_reference !== null) {
            $data['provider_reference'] = $this->provider_reference;
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
