<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class FileManager{

    protected string $filefolder;


    public function __construct(string $filefolder){
        $this->filefolder = $filefolder;
    }

     /**
     * Store the uploaded file.
     *
     * @param  UploadedFile  $file
     * @param  string  $path
     * @return string
     */

    public function store(UploadedFile $file, $storedisk = "public",): string|null {

        try {
            
            $fileExtention = $file->extension();
            $timestam = Carbon::now()->getTimestamp();
            $filename = "$this->filefolder/$timestam.$fileExtention";
            $storage = Storage::disk($storedisk)->put($filename, file_get_contents($file));
            if ($storage) {
                return "$timestam.$fileExtention";
            }
            return null;
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return null;
        }

       
    }



    public function delete(string $filename, $storedisk = "public"): bool {
        try {
            return Storage::disk($storedisk)->delete("$this->filefolder/$filename");
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return false;
        }



    }

    public function get(string $filename, $storedisk = "public"): string|null {
        try {
            return Storage::disk($storedisk)->url("$this->filefolder/$filename");
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return null;
        }
    }
}