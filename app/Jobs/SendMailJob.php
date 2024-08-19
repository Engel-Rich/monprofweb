<?php

namespace App\Jobs;

use App\Services\SendMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected SendMessageService $sendMessageService;
    protected array  $codeList;
    /**
     * Create a new job instance.
     */
    public function __construct(SendMessageService $sendMessageService, array $codeList)
    {
        //
        $this->sendMessageService = $sendMessageService;
        $this->codeList = $codeList;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
      $this->sendMessageService->sendEmail($this->codeList);
    }
}
