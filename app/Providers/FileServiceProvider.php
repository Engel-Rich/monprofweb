<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FileManager;

class FileServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
     $this->app->singleton(FileManager::class, function ($app) {
            return new FileManager(config('filesystems.default'));
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
