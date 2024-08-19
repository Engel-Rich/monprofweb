<?php

namespace App\Jobs;

use App\Services\SendMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SendMessageService $sendMessageService;
    /**
     * Create a new job instance.
     */
    public function __construct(SendMessageService $sendMessageService, protected string $code)
    {
        //
        $this->sendMessageService = $sendMessageService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendMessageService->sendSMS($this->code);
    }
}
