<?php

namespace App\Services;

use App\DTO\MundiPayRequestDTO;
use App\DTO\PaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MundiPayService
{
    public static function requestPaymentIntent(MundiPayRequestDTO $data): PaymentIntent
    {
        try {
            $ApiKey = env('MUNDY_PAY_API_KEY');
            $ApiSecret = env('MUNDY_PAY_API_SECRET');
            $baseUrl = env('MUNDY_PAY_API_URL', 'https://gateway.mundipay.pro/api/smobilpay/');

            $header = [
                "Content-Type" => "application/json",
                "X-API-KEY" => $ApiKey,
                "X-API-SECRET" => $ApiSecret
            ];
            $response = Http::withHeaders($header)->post("{$baseUrl}payment/transaction", $data->toArrayApi());
            // Log::info("Reponse Payment API" . $response);
            return PaymentIntent::fromArray($response->json());
        } catch (\Throwable $th) {
            Log::error("Error When requested payment  " . $th->getMessage());
            throw $th;
        }
    }
}
