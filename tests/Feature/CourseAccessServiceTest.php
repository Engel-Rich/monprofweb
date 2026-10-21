<?php

namespace Tests\Feature;

use App\Models\Eleve;
use App\Models\Paiements;
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
            $table->integer('nombre_de_code')->default(1);
            $table->dateTime('paiement_date')->nullable();
            $table->boolean('status')->default(false);
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('codes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('paiements_id');
            $table->unsignedBigInteger('eleve_id')->nullable();
            $table->string('code')->unique();
            $table->dateTime('active_date')->nullable();
            $table->boolean('actif')->default(false);
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->string('revocation_reason')->nullable();
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

    public function test_a_revoked_code_cannot_be_activated_or_keep_course_access(): void
    {
        [$user, $student, $paymentId] = $this->createStudentAndPayment(true);
        DB::table('codes')->insert([
            'paiements_id' => $paymentId,
            'eleve_id' => $student->id,
            'code' => 'C-REVOKED',
            'actif' => true,
            'active_date' => now(),
            'revoked_at' => now(),
            'revoked_by' => 99,
            'revocation_reason' => 'Compte compromis',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(CourseAccessService::class)->activateCodeForUser($user, 'C-REVOKED');
            $this->fail('Le code révoqué aurait dû être refusé.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('révoqué', $exception->getMessage());
        }

        $this->assertFalse(app(CourseAccessService::class)->studentHasCategoryAccess($student, 5));
    }

    public function test_revoking_the_payment_removes_access_from_all_its_codes(): void
    {
        [$user, $student, $paymentId] = $this->createStudentAndPayment(true);
        DB::table('codes')->insert([
            'paiements_id' => $paymentId,
            'eleve_id' => $student->id,
            'code' => 'C-REVOKED-SUBSCRIPTION',
            'actif' => true,
            'active_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('paiements')->where('id', $paymentId)->update([
            'revoked_at' => now(),
            'revoked_by' => 99,
            'revocation_reason' => 'Abonnement révoqué',
        ]);

        $service = app(CourseAccessService::class);
        $this->assertFalse($service->studentHasCategoryAccess($student, 5));

        $this->expectException(DomainException::class);
        $service->activateCodeForUser($user, 'C-REVOKED-SUBSCRIPTION');
    }

    public function test_a_student_with_an_active_subscription_cannot_activate_another_code_for_the_category(): void
    {
        [$user, $student, $paymentId] = $this->createStudentAndPayment(true);
        DB::table('codes')->insert([
            ['paiements_id' => $paymentId, 'eleve_id' => $student->id, 'code' => 'C-ALREADY-ACTIVE', 'actif' => true, 'active_date' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['paiements_id' => $this->createPayment($user->id), 'eleve_id' => null, 'code' => 'C-SECOND-CODE', 'actif' => false, 'active_date' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        try {
            app(CourseAccessService::class)->activateCodeForUser($user, 'C-SECOND-CODE');
            $this->fail('Le second code de la même catégorie aurait dû être refusé.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('déjà un abonnement actif pour la catégorie Premium', $exception->getMessage());
        }

        $this->assertFalse((bool) DB::table('codes')->where('code', 'C-SECOND-CODE')->value('actif'));
    }

    public function test_the_single_code_of_a_student_payer_is_activated_automatically(): void
    {
        [, $student, $paymentId] = $this->createStudentAndPayment(true);
        $this->insertCode($paymentId, 'C-AUTO');

        $code = app(CourseAccessService::class)->autoActivateForPayer(Paiements::find($paymentId));

        $this->assertNotNull($code);
        $this->assertTrue((bool) $code->actif);
        $this->assertSame($student->id, (int) $code->eleve_id);
    }

    public function test_a_batch_of_codes_is_never_activated_automatically(): void
    {
        [, , $paymentId] = $this->createStudentAndPayment(true);
        DB::table('paiements')->where('id', $paymentId)->update(['nombre_de_code' => 2]);
        $this->insertCode($paymentId, 'C-BATCH-1');
        $this->insertCode($paymentId, 'C-BATCH-2');

        $this->assertNull(app(CourseAccessService::class)->autoActivateForPayer(Paiements::find($paymentId)));
        $this->assertSame(0, DB::table('codes')->where('actif', true)->count());
    }

    public function test_a_code_paid_by_a_non_student_is_never_activated_automatically(): void
    {
        DB::table('categories')->insert(['id' => 5, 'libelle' => 'Premium', 'created_at' => now(), 'updated_at' => now()]);
        $parent = User::create(['name' => 'Parent']);
        $paymentId = $this->createPayment($parent->id);
        $this->insertCode($paymentId, 'C-PARENT');

        $this->assertNull(app(CourseAccessService::class)->autoActivateForPayer(Paiements::find($paymentId)));
        $this->assertFalse((bool) DB::table('codes')->where('code', 'C-PARENT')->value('actif'));
    }

    public function test_a_student_who_already_has_the_category_keeps_the_new_code_available(): void
    {
        [$user, $student, $paymentId] = $this->createStudentAndPayment(true);
        DB::table('codes')->insert([
            'paiements_id' => $paymentId,
            'eleve_id' => $student->id,
            'code' => 'C-CURRENT',
            'actif' => true,
            'active_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondPaymentId = $this->createPayment($user->id);
        $this->insertCode($secondPaymentId, 'C-SPARE');

        $this->assertNull(app(CourseAccessService::class)->autoActivateForPayer(Paiements::find($secondPaymentId)));
        $this->assertFalse((bool) DB::table('codes')->where('code', 'C-SPARE')->value('actif'));
    }

    private function createPayment(int $userId): int
    {
        return DB::table('paiements')->insertGetId([
            'user_id' => $userId,
            'categorie_id' => 5,
            'paiement_date' => now(),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertCode(int $paymentId, string $value): void
    {
        DB::table('codes')->insert([
            'paiements_id' => $paymentId,
            'code' => $value,
            'actif' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
