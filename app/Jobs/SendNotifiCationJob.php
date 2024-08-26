<?php

namespace App\Jobs;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\AppMessage;
use App\Services\PushNotifictaionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotifiCationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected  $messageID)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $mmessage = AppMessage::find($this->messageID);        
            $notification = new PushNotifictaionService($mmessage);
            $userLis  = User::all();
            foreach ($userLis as $user) {
                $token = $user->fcm_token;
                if($token!=null) $notification->sendNotificationToToken($token);
            }             
        } catch (\Throwable $th) {
            Log::info("Erreur de notification dans le job".$th->getMessage());
        }
    }
}
