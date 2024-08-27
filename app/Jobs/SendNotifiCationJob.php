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
    protected $messageID;
    public function __construct(  $messageID)
    {
        $this->messageID = $messageID;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $message = AppMessage::find($this->messageID);        
            $notification = new PushNotifictaionService($message);
            $userLis  = User::where('fcm_token', '!=', null)->get();  
            foreach ($userLis as $user) {
                $token = $user->fcm_token;
                Log::info($token);
                if($token!=null) $notification->sendNotificationToToken($token);
            }             
        } catch (\Throwable $th) {
            Log::info("Erreur de notification dans le job".$th->getMessage());
        }
    }
}
