<?php 
namespace App\Services;
use App\Models\AppMessage;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;

class PushNotifictaionService{

    
    protected Messaging $messaging; 
    public function __construct(protected AppMessage $appMessage){
        $this->messaging = Firebase::messaging();
    }

    public function sendNotificationToToken(string $token)  {
     try {
        $notification = Notification::fromArray(
            [
                'title'=> $this->appMessage->title,
                'body'=> $this->appMessage->body,
            ]
        );
        $message = CloudMessage::withTarget('token', $token)
        ->withNotification($notification)
        ->withData(['EVENT_TYPE'=>'APP_MESSAGE']);
        
        Log::info("Voicie le message Jusque là je suis Bon avec le token" .$token);
        
        $this->messaging->send($message);
     } catch (\Throwable $th) {
        Log::info("Erreur de notification dans le service ".$th->getMessage());
     }
    }

}