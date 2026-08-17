<?php

namespace App\Console\Commands;

use App\Models\Cours;
use App\Models\Questions;
use App\Models\Reponses;
use App\Models\User;
use App\Services\FirebaseFileService;
use App\Services\MinioFileService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

class MigrateFilesToMinio extends Command
{
    protected $signature = 'files:migrate-to-minio
                            {--dry-run : Compter les fichiers sans les déplacer}';

    protected $description = 'Migre les photos, vidéos et images existantes de Firebase vers MinIO';

    private int $migrated = 0;

    private int $failed = 0;

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->components->info($this->countFiles().' fichier(s) Firebase référencé(s) en base.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Migrer les fichiers Firebase vers MinIO ?', true)) {
            return self::SUCCESS;
        }

        $firebase = new FirebaseFileService;
        $minio = new MinioFileService(config('file-storage.minio'));

        $this->migrateUsers($firebase, $minio);
        $this->migrateCourses($firebase, $minio);
        $this->migrateQuestions($firebase, $minio);
        $this->migrateResponses($firebase, $minio);

        $this->newLine();
        $this->components->info("{$this->migrated} fichier(s) migré(s).");

        if ($this->failed > 0) {
            $this->components->error("{$this->failed} fichier(s) en erreur ; leurs références Firebase ont été conservées.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function migrateUsers(FirebaseFileService $firebase, MinioFileService $minio): void
    {
        User::query()->whereNotNull('profile_image')->where('profile_image', '!=', '')
            ->chunkById(100, function ($users) use ($firebase, $minio): void {
                foreach ($users as $user) {
                    $this->migrate($user, 'profile_image', "profile/users/{$user->phone}", $firebase, $minio);
                }
            });
    }

    private function migrateCourses(FirebaseFileService $firebase, MinioFileService $minio): void
    {
        Cours::query()->with(['categorie', 'classe', 'matiere'])
            ->whereNotNull('video_url')->where('video_url', '!=', '')
            ->chunkById(50, function ($courses) use ($firebase, $minio): void {
                foreach ($courses as $course) {
                    if (! $course->categorie || ! $course->classe || ! $course->matiere) {
                        $this->recordFailure($course, 'video_url', 'relations du cours incomplètes');

                        continue;
                    }

                    $folder = "Videos/{$course->categorie->libelle}/{$course->classe->libelle}/{$course->matiere->libelle}";
                    $this->migrate($course, 'video_url', $folder, $firebase, $minio);
                }
            });
    }

    private function migrateQuestions(FirebaseFileService $firebase, MinioFileService $minio): void
    {
        Questions::query()->whereNotNull('image_url')->where('image_url', '!=', '')
            ->chunkById(100, function ($questions) use ($firebase, $minio): void {
                foreach ($questions as $question) {
                    $this->migrate($question, 'image_url', 'questions/eleves', $firebase, $minio);
                }
            });
    }

    private function migrateResponses(FirebaseFileService $firebase, MinioFileService $minio): void
    {
        Reponses::query()->whereNotNull('image_url')->where('image_url', '!=', '')
            ->chunkById(100, function ($responses) use ($firebase, $minio): void {
                foreach ($responses as $response) {
                    $this->migrate($response, 'image_url', "Reponses/{$response->questions_id}", $firebase, $minio);
                }
            });
    }

    private function migrate(
        Model $model,
        string $column,
        string $folder,
        FirebaseFileService $firebase,
        MinioFileService $minio,
    ): void {
        $sourceFilename = (string) $model->getAttribute($column);

        if ($this->alreadyOnMinio($sourceFilename, $folder, $minio)) {
            return;
        }

        $temporaryPath = null;

        try {
            if (filter_var($sourceFilename, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('la base contient une URL externe au lieu d’un nom de fichier');
            }

            $temporaryPath = $firebase->getDecrypted($sourceFilename, $folder);

            if (! $temporaryPath) {
                throw new RuntimeException('fichier Firebase introuvable ou illisible');
            }

            $mimeType = mime_content_type($temporaryPath) ?: 'application/octet-stream';
            $uploadedFile = new UploadedFile($temporaryPath, basename($sourceFilename), $mimeType, null, true);
            $minioFilename = $minio->storeAs($uploadedFile, $folder, $sourceFilename);

            if (! $minioFilename) {
                throw new RuntimeException('upload MinIO impossible');
            }

            $this->migrated++;
            $this->line("Migré : {$model->getTable()}#{$model->getKey()} ({$column})");
        } catch (Throwable $exception) {
            $this->recordFailure($model, $column, $exception->getMessage());
        } finally {
            if ($temporaryPath && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    private function alreadyOnMinio(string $value, string $folder, MinioFileService $minio): bool
    {
        $publicUrl = rtrim((string) config('file-storage.minio.public_url'), '/');

        return ($publicUrl !== '' && str_starts_with($value, $publicUrl.'/'))
            || (! filter_var($value, FILTER_VALIDATE_URL) && $minio->exists($value, $folder));
    }

    private function recordFailure(Model $model, string $column, string $reason): void
    {
        $this->failed++;
        $this->components->error("Échec : {$model->getTable()}#{$model->getKey()} ({$column}) — {$reason}");
    }

    private function countFiles(): int
    {
        return User::query()->whereNotNull('profile_image')->where('profile_image', '!=', '')->count()
            + Cours::query()->whereNotNull('video_url')->where('video_url', '!=', '')->count()
            + Questions::query()->whereNotNull('image_url')->where('image_url', '!=', '')->count()
            + Reponses::query()->whereNotNull('image_url')->where('image_url', '!=', '')->count();
    }
}
