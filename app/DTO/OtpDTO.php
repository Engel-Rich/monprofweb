<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class OTPDTO extends DataTransferObject
{
    public string $phone;
    public string $otp;
    public bool $is_used;
    public  $signature;
    public string $verification_id;
    public string $expired_at;


    public function toArrayWithoutOtp(): array
    {
        return [
            'phone' => $this->phone,
            'is_used' => $this->is_used,
            'signature' => $this->signature,
            'verification_id' => $this->verification_id,
            'expired_at' => $this->expired_at,
        ];
    }
}
