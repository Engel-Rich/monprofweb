<?php

namespace App\Services;

use App\DTO\MundiPayRequestDTO;
use App\DTO\PaymentIntent;
use Illuminate\Support\Facades\Http;

class MundiPayService
{
    public static function requestPaymentIntent(MundiPayRequestDTO $data): PaymentIntent
    {
        $ApiKey = env('MUNDY_PAY_API_KEY');
        $ApiSecret = env('MUNDY_PAY_API_SECRET');
        $baseUrl = env('MUNDY_PAY_API_URL', 'https://gateway.mundipay.pro/api/smobilpay/');

        $header = [
            "Content-Type" => "application/json",
            "api_key" => $ApiKey,
            "api_secret" => $ApiSecret
        ];
        $response = Http::withHeaders($header)->post("{$baseUrl}payment/transaction", $data);

        return PaymentIntent::fromArray($response->json());
    }
}
