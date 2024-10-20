<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSMSService
{
    public static function sendSMS(string $message, string $phone): void
    {
        $body = [
            'message' => $message,
            "senderId" => env("SMS_SENDER_ID"),
            "msisdn" => ['237' . $phone]
        ];
        $url = "https://sms.lmtgroup.com/api/v1/pushes";
        $headers = [
            "X-Api-Key" => env('SMS_API_KEY'),
            "Content-Type" => "application/json",
            "X-Secret" => env('SMS_API_SECRET')
        ];

        /**
         * @\Illuminate\Http\Client\Response
         */
        try {
            Log::info("started send Message");
            $data = Http::withHeaders($headers)->post($url, $body);
            Log::notice($data->json());
            Log::info("Message has been sent successfully");
        } catch (\Throwable $th) {
            Log::info("Message has not been sent successfully");
            Log::info($th->getMessage());
        }
    }
}
