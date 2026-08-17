<?php

namespace App\Services;

use App\Contracts\FileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Storage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use RuntimeException;
use Throwable;

class FirebaseFileService implements FileStorageService
{
    private Storage $storage;

    private mixed $bucket;

    private string $encryptionKey;

    public function __construct()
    {
        $key = base64_decode((string) config('file-storage.encryption_key'), true);

        if ($key === false || $key === '') {
            throw new RuntimeException('ENCRYPTION_KEY doit contenir une clé encodée en base64.');
        }

        $this->encryptionKey = $key;
        $this->storage = Firebase::storage();
        $this->bucket = $this->storage->getBucket();
    }

    public function store(UploadedFile $file, string $folder): ?string
    {
        $filename = Str::uuid().'.'.$this->extension($file);
        $temporaryPath = tempnam(storage_path('app'), 'firebase-encrypted-');

        if ($temporaryPath === false) {
            Log::error('Impossible de créer le fichier temporaire Firebase.');

            return null;
        }

        try {
            $this->encryptFile($file->getPathname(), $temporaryPath);
            $this->bucket->upload(fopen($temporaryPath, 'rb'), [
                'name' => $this->objectName($folder, $filename),
                'metadata' => ['contentType' => $file->getClientMimeType()],
            ]);

            return $filename;
        } catch (Throwable $exception) {
            Log::error('Erreur de stockage Firebase.', ['exception' => $exception]);

            return null;
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    public function delete(string $filename, string $folder): bool
    {
        try {
            $this->bucket->object($this->objectName($folder, $filename))->delete();

            return true;
        } catch (Throwable $exception) {
            Log::error('Erreur de suppression Firebase.', ['exception' => $exception]);

            return false;
        }
    }

    public function get(string $filename, string $folder): ?string
    {
        if ($filename === '' || filter_var($filename, FILTER_VALIDATE_URL)) {
            return $filename ?: null;
        }

        try {
            return $this->bucket
                ->object($this->objectName($folder, $filename))
                ->signedUrl(now()->addMinutes((int) config('file-storage.firebase.url_ttl', 30)));
        } catch (Throwable $exception) {
            Log::error('Erreur de génération de l’URL Firebase.', ['exception' => $exception]);

            return null;
        }
    }

    public function getDecrypted(string $filename, string $folder): ?string
    {
        $encryptedPath = tempnam(storage_path('app'), 'firebase-encrypted-');
        $decryptedPath = tempnam(storage_path('app'), 'firebase-decrypted-');

        if ($encryptedPath === false || $decryptedPath === false) {
            return null;
        }

        try {
            $object = $this->bucket->object($this->objectName($folder, $filename));

            if (! $object->exists()) {
                return null;
            }

            $object->downloadToFile($encryptedPath);
            $this->decryptFile($encryptedPath, $decryptedPath);

            return $decryptedPath;
        } catch (Throwable $exception) {
            @unlink($decryptedPath);
            Log::error('Erreur de déchiffrement Firebase.', ['exception' => $exception]);

            return null;
        } finally {
            @unlink($encryptedPath);
        }
    }

    private function encryptFile(string $inputPath, string $outputPath): void
    {
        $iv = random_bytes(16);
        $contents = file_get_contents($inputPath);

        if ($contents === false) {
            throw new RuntimeException('Impossible de lire le fichier à chiffrer.');
        }

        $encrypted = openssl_encrypt($contents, 'aes-256-cbc', $this->encryptionKey, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false || file_put_contents($outputPath, $iv.$encrypted) === false) {
            throw new RuntimeException('Impossible de chiffrer le fichier.');
        }
    }

    private function decryptFile(string $inputPath, string $outputPath): void
    {
        $contents = file_get_contents($inputPath);

        if ($contents === false || strlen($contents) < 17) {
            throw new RuntimeException('Le fichier chiffré est invalide.');
        }

        $decrypted = openssl_decrypt(
            substr($contents, 16),
            'aes-256-cbc',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            substr($contents, 0, 16),
        );

        if ($decrypted === false || file_put_contents($outputPath, $decrypted) === false) {
            throw new RuntimeException('Impossible de déchiffrer le fichier.');
        }
    }

    private function objectName(string $folder, string $filename): string
    {
        return trim($folder, '/').'/'.ltrim($filename, '/');
    }

    private function extension(UploadedFile $file): string
    {
        return $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
    }
}
