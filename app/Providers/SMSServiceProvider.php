<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SendSMSService;

class SMSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SendSMSService::class, function ($app) {
            return new SendSMSService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
