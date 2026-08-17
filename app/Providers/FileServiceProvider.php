<?php

namespace App\Providers;

use App\Contracts\FileStorageService;
use App\Services\FileManager;
use App\Services\FirebaseFileService;
use App\Services\MinioFileService;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class FileServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FileStorageService::class, function () {
            return match (config('file-storage.driver')) {
                'firebase' => new FirebaseFileService,
                'minio' => new MinioFileService(config('file-storage.minio')),
                default => throw new InvalidArgumentException(
                    'FILE_STORAGE_DRIVER doit être "firebase" ou "minio".'
                ),
            };
        });

        $this->app->bind(FileManager::class, function ($app, array $parameters = []) {
            return new FileManager(
                $parameters['filefolder'] ?? 'uploads',
                $app->make(FileStorageService::class),
            );
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
