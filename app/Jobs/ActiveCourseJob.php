<?php

namespace App\Jobs;

use App\Services\PushNotifictaionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ActiveCourseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected PushNotifictaionService $pushNotifictaionService;
    protected string $token;

    public function __construct(PushNotifictaionService $pushNotifictaionService, string $token)
    {
        $this->pushNotifictaionService = $pushNotifictaionService;
        $this->token = $token;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->pushNotifictaionService->sendNotificationToToken($this->token, even_type: "ACTIVATION");
    }
}
