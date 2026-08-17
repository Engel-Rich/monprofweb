<?php

namespace Tests\Unit;

use App\Contracts\FileStorageService;
use App\Services\FileManager;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class FileManagerTest extends TestCase
{
    public function test_it_delegates_operations_with_the_selected_folder(): void
    {
        $storage = new class implements FileStorageService
        {
            public array $calls = [];

            public function store(UploadedFile $file, string $folder): ?string
            {
                $this->calls[] = ['store', $folder];

                return 'video.mp4';
            }

            public function delete(string $filename, string $folder): bool
            {
                $this->calls[] = ['delete', $filename, $folder];

                return true;
            }

            public function get(string $filename, string $folder): ?string
            {
                $this->calls[] = ['get', $filename, $folder];

                return 'https://files.monprof.test/monprof/videos/video.mp4';
            }

            public function getDecrypted(string $filename, string $folder): ?string
            {
                $this->calls[] = ['download', $filename, $folder];

                return '/tmp/video.mp4';
            }
        };

        $manager = new FileManager('/Videos/Terminale/Maths/', $storage);
        $file = UploadedFile::fake()->create('cours.mp4', 10, 'video/mp4');

        self::assertSame('video.mp4', $manager->store($file));
        self::assertSame(
            'https://files.monprof.test/monprof/videos/video.mp4',
            $manager->get('video.mp4'),
        );
        self::assertTrue($manager->delete('video.mp4'));
        self::assertSame('/tmp/video.mp4', $manager->getDecrypted('video.mp4'));
        self::assertSame([
            ['store', 'Videos/Terminale/Maths'],
            ['get', 'video.mp4', 'Videos/Terminale/Maths'],
            ['delete', 'video.mp4', 'Videos/Terminale/Maths'],
            ['download', 'video.mp4', 'Videos/Terminale/Maths'],
        ], $storage->calls);
    }
}
