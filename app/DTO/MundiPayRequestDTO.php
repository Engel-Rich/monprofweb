<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class MundiPayRequestDTO extends DataTransferObject
{
    public float $amount;

    public string $subscription_id;

    public string $countryCode;

    public string $phonenumber;

    public function __construct(array $data)
    {
        $this->amount = (float) $data['amount'];
        $this->subscription_id = (string) $data['subscription_id'];
        $this->countryCode = strtoupper((string) ($data['country_code'] ?? 'CMR'));
        $this->phonenumber = (string) $data['phone_number'];
    }

    public function toArrayApi(): array
    {
        // Snake case keys for API compatibility
        return [
            'amount' => $this->amount,
            'subscription_id' => $this->subscription_id,
            'country_code' => $this->countryCode,
            'phone_number' => $this->phonenumber,
        ];
    }
}
