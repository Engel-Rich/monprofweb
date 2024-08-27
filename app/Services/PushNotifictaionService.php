<?php
namespace App\Services;
use App\Models\AppMessage;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;

class PushNotifictaionService
{

    protected AppMessage $appMessage;
    public function __construct(AppMessage $appMessage)
    {
        $this->appMessage = $appMessage;
    }

    public function sendNotificationToToken(string $token)
    {
        try {
            $messaging = Firebase::messaging();
            $notification = Notification::fromArray(
                [
                    'title' => $this->appMessage->title,
                    'body' => $this->appMessage->body,
                ]
            );
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData(['EVENT_TYPE' => 'APP_MESSAGE']);
            $messaging->send($message);
        } catch (\Throwable $th) {
            Log::info("Erreur de notification dans le service " . $th->getMessage());
        }
    }

}