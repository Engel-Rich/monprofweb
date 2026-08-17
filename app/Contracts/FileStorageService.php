<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface FileStorageService
{
    public function store(UploadedFile $file, string $folder): ?string;

    public function delete(string $filename, string $folder): bool;

    public function get(string $filename, string $folder): ?string;

    public function getDecrypted(string $filename, string $folder): ?string;
}
