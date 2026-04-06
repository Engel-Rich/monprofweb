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
    protected $encryptionKey; // = base64_decode(env("ENCRYPTION_KEY"));

    public function __construct(string $filefolder)
    {
        try {
            $this->encryptionKey = base64_decode(env("ENCRYPTION_KEY"));
            $this->filefolder = $filefolder;
            // dd("File folder: " . $this->filefolder, "Encryption Key: " . env("ENCRYPTION_KEY"), "Decoded Key: " . strlen($this->encryptionKey));
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

            $fileName =  time() . '.' . $file->getExtension();
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
            return null;
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
            return null;
        }
    }
    public function getDecrypted(string $filename): string | null
    {
        try {
            $object = $this->bucket->object($this->filefolder . '/' . $filename);

            if (!$object->exists()) {
                Log::warning("Fichier introuvable : " . $filename);
                return null;
            }

            // 📥 Télécharger le fichier chiffré localement
            $encryptedPath = storage_path("app/temp_encrypted_" . $filename);
            $object->downloadToFile($encryptedPath);

            //  Décrypter le fichier localement
            $decryptedPath = storage_path("app/temp_decrypted_" . $filename);
            $this->decryptFile($encryptedPath, $decryptedPath, $this->encryptionKey);

            // 🧼 Nettoyer le fichier chiffré temporaire
            unlink($encryptedPath);

            // 🔁 Retourner le chemin local du fichier déchiffré
            return $decryptedPath;
        } catch (\Throwable $th) {
            Log::error("Erreur de récupération ou déchiffrement de fichier : " . $th->getMessage());
            return null;
        }
    }


    private function decryptFile(string $inputPath, string $outputPath, string $key): void
    {
        $data = file_get_contents($inputPath);
        $iv = substr($data, 0, 16);
        $cipherText = substr($data, 16);
        $decrypted = openssl_decrypt($cipherText, "aes-256-cbc", $key, OPENSSL_RAW_DATA, $iv);
        file_put_contents($outputPath, $decrypted);
    }
}
