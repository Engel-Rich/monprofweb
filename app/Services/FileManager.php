<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Storage;
use Kreait\Laravel\Firebase\Facades\Firebase;

// use Kreait\Laravel\Firebase\Facades\Firebase;

class FileManager
{

    protected string $filefolder;

    protected Storage $storage;
    protected $bucket;
    protected $encryptionKey = base64_decode(env("ENCRYPTION_KEY"));

    public function __construct(string $filefolder)
    {
        try {
            $this->filefolder = $filefolder;
            $this->storage = Firebase::storage();
            $this->bucket = $this->storage->getBucket();
        } catch (\Throwable $th) {
            Log::error("Erreur d'initialisation Firebase : " . $th->getMessage());
            throw $th;
        }
    }

    /**
     * @param  UploadedFile  $file
     * @param  string  $path
     * @return string
     */

    public function store(UploadedFile $file): string | null
    {
        try {
            // Crypt file 

            $fileName =  time() . '_' . $file->getExtension();
            $filePath = $this->filefolder . '/' . $fileName;

            // 🔐 Chiffrer le fichier
            $encryptedPath = storage_path("app/temp_encrypted_" . $fileName);
            $this->encryptFile($file->getPathname(), $encryptedPath, $this->encryptionKey);

            $this->bucket->upload(
                fopen($encryptedPath, 'r'),
                [
                    'name' => $filePath,
                    'metadata' => [
                        'contentType' => $file->getClientMimeType(),
                    ],
                ]
            );
            unlink($encryptedPath); // ❌ Nettoye

            return $fileName;
        } catch (\Throwable $th) {
            Log::error("Erreur de stockage de fichier : " . $th->getMessage());
        }
    }

    private function encryptFile(string $inputPath, string $outputPath, string $key): void
    {
        $iv = random_bytes(16);
        $data = file_get_contents($inputPath);
        $encrypted = openssl_encrypt($data, "aes-256-cbc", $key, OPENSSL_RAW_DATA, $iv);
        file_put_contents($outputPath, $iv . $encrypted); // Préfixer le IV
    }


    public function delete(string $filename): bool
    {
        try {
            $this->bucket->object($this->filefolder . '/' . $filename)->delete();
            return true;
        } catch (\Throwable $th) {
            Log::error("Erreur de suppression de fichier : " . $th->getMessage());
            return false;
        }
    }

    public function get(string $filename,): string | null
    {
        try {
            $object = $this->bucket->object($this->filefolder . '/' . $filename);
            return $object->signedUrl(now()->addMinutes(30));
        } catch (\Throwable $th) {
            Log::error("Erreur de récupération de fichier : " . $th->getMessage());
        }
    }
}
