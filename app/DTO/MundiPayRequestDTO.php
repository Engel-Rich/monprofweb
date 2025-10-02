<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class MundiPayRequestDTO extends DataTransferObject
{
    public float $amount;
    public string $subscription_id;
    public string $countryCode;
    public string $phonenumber;

    public function __construct(array $date)
    {
        $this->amount = $date['amount'];
        $this->subscription_id = $date['subscription_id'];
        $this->countryCode = $date['country_code'] ?? "237";
        $this->phonenumber = $date['phone_number'];
    }

    public function toArrayApi(): array
    {
        // Snake case keys for API compatibility
        return [
            'amount' => $this->amount,
            'subscription_id' => $this->subscription_id,
            'country_code' => $this->countryCode ?? "237",
            'phone_number' => $this->phonenumber,
        ];
    }
}
