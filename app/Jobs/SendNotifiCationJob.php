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
    protected $message;
    protected $title;
    protected $event_type = "APP_MESSAGE";
    public function __construct($message, $title, $event_type = "APP_MESSAGE")
    {
        $this->title = $title;
        $this->message = $message;
        $this->event_type = $event_type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $notification = new PushNotifictaionService($this->message, $this->title);
            $userLis  = User::where('fcm_token', '!=', null)->get();
            $arrayTokens = $userLis->pluck('fcm_token')->toArray();
            Log::info($arrayTokens);
            $notification->sendMultiCastFCM($arrayTokens);
        } catch (\Throwable $th) {
            Log::info("Erreur de notification dans le job" . $th->getMessage());
        }
    }
}
