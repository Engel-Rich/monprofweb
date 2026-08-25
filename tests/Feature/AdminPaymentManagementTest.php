<?php

namespace Tests\Feature;

use App\Models\Codes;
use App\Models\Paiements;
use App\Models\User;
use App\Services\Admin\PaymentAdminPresenter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPaymentManagementTest extends TestCase
{
    protected User $admin;

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

        $this->createSchema();
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'rule_id' => 1,
        ]);
    }

    public function test_the_payment_drawer_contains_the_associated_transaction_details(): void
    {
        [$payment] = $this->createPayment(status: false, transactionStatus: 'PENDING');

        $response = $this->actingAs($this->admin)->get(route('paiement.index'));

        $response->assertOk();
        $response->assertSee('provider-reference-100');

        $item = app(PaymentAdminPresenter::class)->item(
            $payment->fresh()->load(PaymentAdminPresenter::RELATIONS)
        );

        $this->assertSame(route('paiement.active', $payment), $item['actionUrl']);
        $this->assertSame('Référence MonProf', data_get($item, 'details.1.fields.1.label'));
        $this->assertSame('provider-reference-100', data_get($item, 'details.1.fields.2.value'));
        $this->assertSame('Revérifier', data_get($item, 'actions.0.label'));

        $this->actingAs($this->admin)
            ->get(route('paiement.active', $payment))
            ->assertOk()
            ->assertSee('Détails du paiement')
            ->assertSee('provider-reference-100');
    }

    public function test_an_admin_can_manually_finalize_a_pending_payment(): void
    {
        Http::fake(['https://sms.test/*' => Http::response(['status' => 'sent'])]);
        [$payment] = $this->createPayment(status: false, transactionStatus: null);

        $response = $this->actingAs($this->admin)->post(route('paiement.activate', $payment));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue((bool) $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paiement_date);
        $this->assertDatabaseCount('codes', 1);
    }

    public function test_a_generated_code_can_be_resent_by_sms(): void
    {
        Http::fake(['https://sms.test/*' => Http::response(['status' => 'sent'])]);
        [$payment] = $this->createPayment(status: true, transactionStatus: 'SUCCESS');
        Codes::create([
            'paiements_id' => $payment->id,
            'code' => 'C-RESEND-123',
            'actif' => true,
            'active_date' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('paiement.resend-notification', $payment));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Http::assertSent(fn ($request) => $request->url() === 'https://sms.test/send'
            && str_contains($request['message'], 'C-RESEND-123'));
    }

    public function test_a_pending_payment_cannot_resend_a_code(): void
    {
        Http::fake();
        [$payment] = $this->createPayment(status: false, transactionStatus: 'PENDING');
        Codes::create([
            'paiements_id' => $payment->id,
            'code' => 'C-NOT-PAID',
        ]);

        $response = $this->actingAs($this->admin)->post(route('paiement.resend-notification', $payment));

        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_the_presenter_warns_before_reactivating_an_already_used_code(): void
    {
        [$payment] = $this->createPayment(status: true, transactionStatus: 'SUCCESS');
        Codes::create([
            'paiements_id' => $payment->id,
            'code' => 'C-USED',
            'actif' => true,
            'active_date' => now(),
            'eleve_id' => 99,
        ]);
        $payment->load(PaymentAdminPresenter::RELATIONS);

        $activation = collect(app(PaymentAdminPresenter::class)->actions($payment))
            ->firstWhere('url', route('paiement.activate', $payment));

        $this->assertSame('danger', $activation['style']);
        $this->assertStringContainsString('déjà été utilisé', $activation['confirm']['warning']);
    }

    public function test_an_admin_can_revoke_one_code_without_making_it_reusable(): void
    {
        [$payment] = $this->createPayment(status: true, transactionStatus: 'SUCCESS');
        $code = Codes::create([
            'paiements_id' => $payment->id,
            'code' => 'C-REVOKE-ONE',
            'actif' => true,
            'active_date' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('codes.revoke', $code), [
            'reason' => 'Accès accordé au mauvais élève',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $code->refresh();
        $this->assertTrue($code->actif);
        $this->assertNotNull($code->revoked_at);
        $this->assertSame($this->admin->id, (int) $code->revoked_by);
        $this->assertSame('Accès accordé au mauvais élève', $code->revocation_reason);
    }

    public function test_an_admin_can_revoke_an_entire_subscription_and_all_its_codes(): void
    {
        [$payment] = $this->createPayment(status: true, transactionStatus: 'SUCCESS');
        Codes::create(['paiements_id' => $payment->id, 'code' => 'C-SUB-1']);
        Codes::create(['paiements_id' => $payment->id, 'code' => 'C-SUB-2', 'actif' => true]);

        $response = $this->actingAs($this->admin)->post(route('paiement.revoke', $payment), [
            'reason' => 'Abonnement annulé pour fraude',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertNotNull($payment->fresh()->revoked_at);
        $this->assertSame(2, Codes::where('paiements_id', $payment->id)->whereNotNull('revoked_at')->count());
        $this->assertSame(
            'Abonnement annulé pour fraude',
            $payment->fresh()->revocation_reason,
        );
    }

    public function test_a_revocation_requires_a_reason(): void
    {
        [$payment] = $this->createPayment(status: true, transactionStatus: 'SUCCESS');
        $code = Codes::create(['paiements_id' => $payment->id, 'code' => 'C-REASON']);

        $this->actingAs($this->admin)
            ->from(route('codes.index', 'all'))
            ->post(route('codes.revoke', $code))
            ->assertRedirect(route('codes.index', 'all'))
            ->assertSessionHasErrors('reason');

        $this->assertNull($code->fresh()->revoked_at);
    }

    public function test_a_non_administrator_cannot_revoke_a_code(): void
    {
        [$payment] = $this->createPayment(status: true, transactionStatus: 'SUCCESS');
        $code = Codes::create(['paiements_id' => $payment->id, 'code' => 'C-FORBIDDEN']);
        $user = User::create([
            'name' => 'Utilisateur',
            'email' => 'user@example.test',
            'password' => 'password',
            'rule_id' => 2,
        ]);

        $this->actingAs($user)->post(route('codes.revoke', $code), [
            'reason' => 'Tentative non autorisée',
        ])->assertForbidden();

        $this->assertNull($code->fresh()->revoked_at);
    }

    private function createPayment(bool $status, ?string $transactionStatus): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Client',
            'last_name' => 'MonProf',
            'email' => 'client'.uniqid().'@example.test',
            'password' => 'password',
            'rule_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'libelle' => 'Premium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $transactionId = null;

        if ($transactionStatus !== null) {
            $providerId = DB::table('payment_providers')->insertGetId([
                'name' => 'Provider test',
                'code' => 'TEST_PROVIDER_'.uniqid(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $serviceId = DB::table('payment_services')->insertGetId([
                'payment_provider_id' => $providerId,
                'title' => 'Mobile Money',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $transactionId = DB::table('transactions')->insertGetId([
                'payment_provider_id' => $providerId,
                'service_id' => $serviceId,
                'user_id' => $userId,
                'provider_reference' => 'provider-reference-100',
                'reference' => 'MPP-reference-100',
                'amount' => 1025,
                'phone_number' => '690000000',
                'status' => $transactionStatus,
                'sens' => 'IN',
                'internal_service' => 'MONPROF_PURCHASE',
                'subscription_id' => 'SUB-1',
                'metadatas' => json_encode(['status_checks' => []]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $payment = Paiements::create([
            'transaction_id' => $transactionId,
            'user_id' => $userId,
            'categorie_id' => $categoryId,
            'nombre_de_code' => 1,
            'montant' => 1000,
            'numero_payeur' => '690000000',
            'numero_client' => '691000000',
            'status' => $status,
            'paiement_date' => $status ? now() : null,
        ]);

        return [$payment];
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rule_id')->default(2);
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('fcm_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->timestamps();
        });
        Schema::create('payment_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        Schema::create('payment_services', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_provider_id')->nullable();
            $table->string('title');
            $table->string('img')->nullable();
            $table->string('description')->nullable();
            $table->string('subtitle')->nullable();
            $table->integer('status')->default(1);
            $table->boolean('is_active')->default(true);
            $table->string('reg_exp')->nullable();
            $table->string('subscription_id')->nullable();
            $table->string('sens')->nullable();
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_provider_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('payment_token')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('phone_number');
            $table->string('status');
            $table->string('sens');
            $table->string('internal_service')->nullable();
            $table->string('subscription_id')->nullable();
            $table->string('raison_reject')->nullable();
            $table->text('metadatas')->nullable();
            $table->timestamps();
        });
        Schema::create('paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('categorie_id');
            $table->integer('nombre_de_code')->default(1);
            $table->decimal('montant', 12, 2);
            $table->string('numero_payeur');
            $table->string('numero_client')->nullable();
            $table->boolean('status')->default(false);
            $table->dateTime('paiement_date')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('eleves', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
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
