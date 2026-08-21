<?php

namespace Tests\Feature;

use App\Models\Eleve;
use App\Models\User;
use App\Services\CourseAccessService;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseAccessServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('eleves', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->timestamps();
        });
        Schema::create('paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('categorie_id');
            $table->dateTime('paiement_date')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
        Schema::create('codes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('paiements_id');
            $table->unsignedBigInteger('eleve_id')->nullable();
            $table->string('code')->unique();
            $table->dateTime('active_date')->nullable();
            $table->boolean('actif')->default(false);
            $table->timestamps();
        });
    }

    public function test_a_paid_code_unlocks_its_category_for_the_student(): void
    {
        [$user, $student, $paymentId] = $this->createStudentAndPayment(true);
        DB::table('codes')->insert([
            'paiements_id' => $paymentId,
            'code' => 'C-VALID-COURSE',
            'actif' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $service = app(CourseAccessService::class);

        $code = $service->activateCodeForUser($user, 'C-VALID-COURSE');

        $this->assertTrue((bool) $code->actif);
        $this->assertSame($student->id, (int) $code->eleve_id);
        $this->assertTrue($service->studentHasCategoryAccess($student, 5));
    }

    public function test_an_unpaid_code_cannot_unlock_courses(): void
    {
        [$user, $student, $paymentId] = $this->createStudentAndPayment(false);
        DB::table('codes')->insert([
            'paiements_id' => $paymentId,
            'code' => 'C-UNPAID',
            'actif' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(CourseAccessService::class)->activateCodeForUser($user, 'C-UNPAID');
            $this->fail('Le code non payé aurait dû être refusé.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('pas encore validé', $exception->getMessage());
        }

        $this->assertFalse(app(CourseAccessService::class)->studentHasCategoryAccess($student, 5));
    }

    private function createStudentAndPayment(bool $paid): array
    {
        $user = User::create(['name' => 'Test student']);
        $student = Eleve::create([
            'user_id' => $user->id,
            'classe_id' => 2,
        ]);
        DB::table('categories')->insert([
            'id' => 5,
            'libelle' => 'Premium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentId = DB::table('paiements')->insertGetId([
            'user_id' => $user->id,
            'categorie_id' => 5,
            'paiement_date' => $paid ? now() : null,
            'status' => $paid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $student, $paymentId];
    }
}
