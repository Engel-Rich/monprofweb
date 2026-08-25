<?php

namespace App\Services\Payments;

use App\Models\PayementServices;

class PaymentFeeCalculator
{
    private const AMOUNT_INCREMENT = 5;

    /**
     * @return array{
     *     base_amount: int,
     *     provider_fee: int,
     *     service_fee: int,
     *     total_amount: int,
     *     provider_fee_percentage: float,
     *     user_fee_percentage: float
     * }
     */
    public function calculate(int|float $baseAmount, PayementServices $service): array
    {
        $baseAmount = max(0, (int) ceil($baseAmount));
        $providerFeePercentage = max(0, (float) $service->provider_fee_percentage);
        $userFeePercentage = min(100, max(0, (float) $service->user_fee_percentage));

        $rawProviderFee = $baseAmount * $providerFeePercentage / 100;
        $providerFee = (int) ceil($rawProviderFee);
        $rawUserFee = $rawProviderFee * $userFeePercentage / 100;
        $totalAmount = (int) (ceil(
            ($baseAmount + $rawUserFee) / self::AMOUNT_INCREMENT
        ) * self::AMOUNT_INCREMENT);
        $userFee = $totalAmount - $baseAmount;

        return [
            'base_amount' => $baseAmount,
            'provider_fee' => $providerFee,
            'service_fee' => $userFee,
            'total_amount' => $totalAmount,
            'provider_fee_percentage' => $providerFeePercentage,
            'user_fee_percentage' => $userFeePercentage,
        ];
    }
}
