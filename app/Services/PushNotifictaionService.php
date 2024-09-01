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

    protected string $appMessage;
    protected string $title;
    public function __construct(string $appMessage, string $title)
    {
        $this->appMessage = $appMessage;
        $this->title = $title;
    }

    public function sendNotificationToToken(string $token)
    {
        try {
            $messaging = Firebase::messaging();
            $notification = Notification::fromArray(
                [
                    'title' => $this->title,
                    'body' => $this->appMessage,
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