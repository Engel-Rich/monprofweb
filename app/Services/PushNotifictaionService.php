<?php

namespace App\Services;

// use App\Models\AppMessage;
// use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;

class PushNotifictaionService
{

    protected string $appMessage;
    protected string $title;
    public function __construct(string $appMessage, string $title)
    {
        $this->appMessage = $appMessage;
        $this->title = $title;
    }

    public function sendNotificationToToken(string $token, string $even_type = "APP_MESSAGE", array $data = []): void
    {
        // Log::info("Token reçu : $token");
        try {
            $messaging = Firebase::messaging();
            $notification = Notification::fromArray(
                [
                    'title' => $this->title,
                    'body' => $this->appMessage,
                ]
            );

            $dataNotification = array_merge(['EVENT_TYPE' => $even_type], $data);
            $message = CloudMessage::new()->toToken($token)
                ->withNotification($notification)
                ->withData($dataNotification);
            $messaging->send($message);
        } catch (\Throwable $th) {
            Log::error("Erreur de notification : " . $th->getMessage(), [
                'token' => $token,
                // 'trace' => $th->getTraceAsString()
            ]);
        }
    }


    public function sendMultiCastFCM(array $tokens, string $even_type = "APP_MESSAGE"): void
    {
        try {
            $splitTokens = array_chunk($tokens, 500);
            $messaging = Firebase::messaging();
            $notification = Notification::fromArray(
                [
                    'title' => $this->title,
                    'body' => $this->appMessage,
                ]
            );
            $message = CloudMessage::new()->withNotification($notification)
                ->withData(['EVENT_TYPE' => $even_type]);
            foreach ($splitTokens as $token) {
                $messaging->sendMulticast($message, $token);
            }
        } catch (\Throwable $th) {
            Log::error("Erreur de notification : " . $th->getMessage());
        }
    }
}
