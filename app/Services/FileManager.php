<?php

namespace App\Services;

use App\Contracts\FileStorageService;
use Illuminate\Http\UploadedFile;

class FileManager
{
    public function __construct(
        private string $filefolder = 'uploads',
        private ?FileStorageService $storage = null,
    ) {
        $this->filefolder = trim($this->filefolder, '/') ?: 'uploads';
        $this->storage ??= app(FileStorageService::class);
    }

    public function store(UploadedFile $file): ?string
    {
        return $this->storage->store($file, $this->filefolder);
    }

    public function delete(string $filename): bool
    {
        return $this->storage->delete($filename, $this->filefolder);
    }

    public function get(string $filename): ?string
    {
        return $this->storage->get($filename, $this->filefolder);
    }

    public function getDecrypted(string $filename): ?string
    {
        return $this->storage->getDecrypted($filename, $this->filefolder);
    }
}
