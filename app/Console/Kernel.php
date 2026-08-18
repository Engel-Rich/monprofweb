<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $duration = max(1, (int) config('payments.polling.duration', 55));
        $interval = max(1, (int) config('payments.polling.interval', 5));

        $schedule
            ->command("payments:verify-pending --duration={$duration} --interval={$interval}")
            ->everyMinute()
            ->withoutOverlapping(2)
            ->name('payments:verify-pending');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
