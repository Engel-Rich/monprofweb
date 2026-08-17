<?php

namespace App\Services;

use App\Contracts\FileStorageService;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MinioFileService implements FileStorageService
{
    private S3Client $client;

    private string $bucket;

    private string $publicUrl;

    private bool $bucketReady = false;

    public function __construct(array $config)
    {
        foreach (['endpoint', 'key', 'secret', 'bucket', 'public_url'] as $required) {
            if (blank($config[$required] ?? null)) {
                throw new RuntimeException("Configuration MinIO manquante : {$required}.");
            }
        }

        $this->bucket = $config['bucket'];
        $this->publicUrl = rtrim($config['public_url'], '/');
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $config['region'] ?? 'us-east-1',
            'endpoint' => rtrim($config['endpoint'], '/'),
            'use_path_style_endpoint' => (bool) ($config['use_path_style_endpoint'] ?? true),
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);
    }

    public function store(UploadedFile $file, string $folder): ?string
    {
        $filename = Str::uuid().'-'.$this->safeFilename($file);

        return $this->storeAs($file, $folder, $filename);
    }

    public function storeAs(UploadedFile $file, string $folder, string $filename): ?string
    {
        try {
            $this->ensureBucketPublic();
            $filename = basename($filename);

            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->objectName($folder, $filename),
                'Body' => fopen($file->getPathname(), 'rb'),
                'ContentLength' => $file->getSize(),
                'ContentType' => $file->getClientMimeType() ?: 'application/octet-stream',
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);

            return $filename;
        } catch (Throwable $exception) {
            Log::error('Erreur d’upload MinIO.', ['exception' => $exception]);

            return null;
        }
    }

    public function delete(string $filename, string $folder): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->objectName($folder, $filename),
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::error('Erreur de suppression MinIO.', ['exception' => $exception]);

            return false;
        }
    }

    public function get(string $filename, string $folder): ?string
    {
        if ($filename === '' || filter_var($filename, FILTER_VALIDATE_URL)) {
            return $filename ?: null;
        }

        try {
            $this->ensureBucketPublic();
            $objectName = $this->objectName($folder, $filename);
            $encodedObjectName = implode('/', array_map('rawurlencode', explode('/', $objectName)));

            return "{$this->publicUrl}/{$this->bucket}/{$encodedObjectName}";
        } catch (Throwable $exception) {
            Log::error('Erreur de génération de l’URL MinIO.', ['exception' => $exception]);

            return null;
        }
    }

    public function getDecrypted(string $filename, string $folder): ?string
    {
        $temporaryPath = tempnam(storage_path('app'), 'minio-');

        if ($temporaryPath === false) {
            return null;
        }

        try {
            $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $this->objectName($folder, $filename),
                'SaveAs' => $temporaryPath,
            ]);

            return $temporaryPath;
        } catch (Throwable $exception) {
            @unlink($temporaryPath);
            Log::error('Erreur de téléchargement MinIO.', ['exception' => $exception]);

            return null;
        }
    }

    public function exists(string $filename, string $folder): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $this->objectName($folder, $filename),
            ]);

            return true;
        } catch (AwsException $exception) {
            if ($exception->getStatusCode() === 404) {
                return false;
            }

            throw $exception;
        }
    }

    private function ensureBucketPublic(): void
    {
        if ($this->bucketReady) {
            return;
        }

        try {
            $this->client->headBucket(['Bucket' => $this->bucket]);
        } catch (AwsException $exception) {
            if ($exception->getStatusCode() !== 404) {
                throw $exception;
            }

            $this->client->createBucket(['Bucket' => $this->bucket]);
        }

        $this->client->putBucketPolicy([
            'Bucket' => $this->bucket,
            'Policy' => json_encode([
                'Version' => '2012-10-17',
                'Statement' => [[
                    'Effect' => 'Allow',
                    'Principal' => ['AWS' => ['*']],
                    'Action' => ['s3:GetObject'],
                    'Resource' => ["arn:aws:s3:::{$this->bucket}/*"],
                ]],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->bucketReady = true;
    }

    private function objectName(string $folder, string $filename): string
    {
        $segments = array_filter(explode('/', trim($folder, '/')));
        $safeFolder = implode('/', array_map(
            static fn (string $segment): string => Str::slug($segment) ?: 'uploads',
            $segments,
        ));

        return ($safeFolder ?: 'uploads').'/'.ltrim($filename, '/');
    }

    private function safeFilename(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return (Str::slug($name) ?: 'fichier').'.'.strtolower($extension);
    }
}
