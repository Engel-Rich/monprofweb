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

    public function store(UploadedFile $file): string|null
    {
        try {

            $fileName =  time() . '_' . $file->getExtension();
            $filePath = $this->filefolder . '/' . $fileName;
            $this->bucket->upload(
                fopen($file->getPathname(), 'r'),
                [
                    'name' => $filePath,
                    'metadata' => [
                        'contentType' => $file->getClientMimeType(),
                    ],
                ]
            );
            return $fileName;
        } catch (\Throwable $th) {
            Log::error("Erreur de stockage de fichier : " . $th->getMessage());
        }
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

    public function get(string $filename,): string|null
    {
        try {
            $object = $this->bucket->object($this->filefolder . '/' . $filename);
            return $object->signedUrl(now()->addMinutes(30));
        } catch (\Throwable $th) {
            Log::error("Erreur de récupération de fichier : " . $th->getMessage());
        }
    }
}
