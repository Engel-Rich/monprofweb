<?php

namespace Tests\Feature;

use App\Mail\ActivationCodeMail;
use App\Models\Codes;
use App\Models\Eleve;
use App\Models\Paiements;
use App\Models\User;
use App\Services\CourseAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActivationCodeGrantTest extends TestCase
{
    protected User $admin;

    protected User $studentUser;

    protected Eleve $student;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'services.sms.url' => 'https://sms.test/send',
            'services.sms.sender_id' => 'MONPROF',
            'services.sms.key' => 'test-key',
            'services.sms.secret' => 'test-secret',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        $this->withoutVite();

        $this->createSchema();
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password', 'rule_id' => 1]);
        $this->studentUser = User::create(['name' => 'Awa', 'last_name' => 'Ngono', 'email' => 'awa@example.test', 'phone' => '690000001', 'password' => 'password', 'rule_id' => 2]);
        $this->student = Eleve::create(['user_id' => $this->studentUser->id, 'classe_id' => 1]);
        DB::table('categories')->insert(['id' => 5, 'libelle' => 'Premium', 'prix' => 5000, 'status' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_an_admin_grants_a_free_code_that_the_student_can_activate(): void
    {
        $response = $this->actingAs($this->admin)->post(route('codes.grant.store'), [
            'eleve_id' => $this->student->id,
            'categorie_id' => 5,
            'reason' => 'Partenariat école',
        ]);

        $code = Codes::with('paiement')->sole();
        $response->assertRedirect(route('codes.grant.show', $code));
        $response->assertSessionHas('success');

        $this->assertSame(Paiements::ORIGIN_ADMIN_GRANT, $code->paiement->origin);
        $this->assertSame(0.0, (float) $code->paiement->montant);
        $this->assertTrue((bool) $code->paiement->status);
        $this->assertNull($code->paiement->transaction_id);
        $this->assertSame($this->admin->id, (int) $code->paiement->granted_by);
        $this->assertSame('Partenariat école', $code->paiement->grant_reason);
        $this->assertFalse((bool) $code->actif);

        app(CourseAccessService::class)->activateCodeForUser($this->studentUser, $code->code);
        $this->assertTrue(app(CourseAccessService::class)->studentHasCategoryAccess($this->student, 5));
    }

    public function test_the_code_can_be_sent_by_sms_and_email_at_creation(): void
    {
        Http::fake(['https://sms.test/*' => Http::response(['status' => 'sent'])]);
        Mail::fake();

        $this->actingAs($this->admin)->post(route('codes.grant.store'), [
            'eleve_id' => $this->student->id,
            'categorie_id' => 5,
            'send_sms' => '1',
            'send_email' => '1',
        ])->assertSessionHas('success', fn (string $message) => str_contains($message, 'SMS et e-mail'));

        $code = Codes::sole();
        Http::assertSent(fn ($request) => str_contains($request['message'], $code->code)
            && $request['msisdn'] === ['237690000001']);
        Mail::assertSent(ActivationCodeMail::class, fn (ActivationCodeMail $mail) => $mail->hasTo('awa@example.test')
            && $mail->code->is($code));

        $html = (new ActivationCodeMail($code->load('paiement.user', 'paiement.categorie')))->render();
        $this->assertStringContainsString($code->code, $html);
        $this->assertStringContainsString('Premium', $html);
    }

    public function test_a_failed_delivery_keeps_the_code_and_reports_the_error(): void
    {
        Http::fake(['https://sms.test/*' => Http::response(['error' => 'quota'], 500)]);

        $this->actingAs($this->admin)->post(route('codes.grant.store'), [
            'eleve_id' => $this->student->id,
            'categorie_id' => 5,
            'send_sms' => '1',
        ])->assertSessionHas('error', fn (string $message) => str_contains($message, 'SMS'));

        $this->assertDatabaseCount('codes', 1);
    }

    public function test_a_student_with_an_active_subscription_cannot_receive_another_code(): void
    {
        $paymentId = DB::table('paiements')->insertGetId([
            'user_id' => $this->studentUser->id, 'categorie_id' => 5, 'nombre_de_code' => 1, 'montant' => 5000,
            'numero_payeur' => '690000001', 'status' => true, 'paiement_date' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('codes')->insert([
            'paiements_id' => $paymentId, 'eleve_id' => $this->student->id, 'code' => 'C-ACTIVE', 'actif' => true,
            'active_date' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)->post(route('codes.grant.store'), [
            'eleve_id' => $this->student->id,
            'categorie_id' => 5,
        ])->assertSessionHas('error', fn (string $message) => str_contains($message, 'déjà un abonnement actif'));

        $this->assertDatabaseCount('codes', 1);
    }

    public function test_the_grant_pages_render_and_find_students(): void
    {
        $this->actingAs($this->admin)
            ->get(route('codes.grant.create', ['recherche' => 'Awa Ngono']))
            ->assertOk()
            ->assertSee('Awa Ngono')
            ->assertSee(route('codes.grant.create', ['eleve' => $this->student->id]), false);

        $this->actingAs($this->admin)->post(route('codes.grant.store'), ['eleve_id' => $this->student->id, 'categorie_id' => 5]);
        $code = Codes::sole();

        $this->actingAs($this->admin)
            ->get(route('codes.grant.show', $code))
            ->assertOk()
            ->assertSee($code->code)
            ->assertSee('Copier le code');
    }

    public function test_purchased_codes_are_not_handled_by_the_grant_screens(): void
    {
        Http::fake();
        $paymentId = DB::table('paiements')->insertGetId([
            'user_id' => $this->studentUser->id, 'categorie_id' => 5, 'nombre_de_code' => 1, 'montant' => 5000,
            'numero_payeur' => '690000001', 'numero_client' => '690000001', 'status' => true, 'paiement_date' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $code = Codes::create(['paiements_id' => $paymentId, 'code' => 'C-PURCHASED']);

        $this->actingAs($this->admin)->get(route('codes.grant.show', $code))->assertNotFound();
        $this->actingAs($this->admin)->post(route('codes.grant.sms', $code))->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_grants_are_excluded_from_purchases(): void
    {
        $this->actingAs($this->admin)->post(route('codes.grant.store'), ['eleve_id' => $this->student->id, 'categorie_id' => 5]);

        $this->assertSame(1, Paiements::count());
        $this->assertSame(0, Paiements::purchases()->count());
    }

    public function test_only_admins_can_grant_codes(): void
    {
        $this->actingAs($this->studentUser)->get(route('codes.grant.create'))->assertForbidden();
        $this->actingAs($this->studentUser)->post(route('codes.grant.store'), [
            'eleve_id' => $this->student->id,
            'categorie_id' => 5,
        ])->assertForbidden();

        $this->assertDatabaseCount('codes', 0);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('fcm_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->timestamps();
        });
        Schema::create('eleves', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->string('etablissement')->nullable();
            $table->string('sexe')->nullable();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->string('description')->nullable();
            $table->decimal('prix', 12, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->dateTime('paiement_date')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('categorie_id');
            $table->integer('nombre_de_code')->default(1);
            $table->decimal('montant', 12, 2)->default(0);
            $table->string('numero_payeur');
            $table->string('numero_client')->nullable();
            $table->boolean('status')->default(false);
            $table->string('origin', 20)->default('PURCHASE');
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->string('grant_reason', 500)->nullable();
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
}
