<?php

namespace Tests\Feature;

use App\Http\Middleware\PhoneEmei;
use App\Http\Requests\API\StorePaymentRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePaymentApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        $this->withoutMiddleware(PhoneEmei::class);

        $this->createSchema();
    }

    public function test_mobile_only_receives_active_incoming_payment_services(): void
    {
        $activeProvider = $this->provider('ACTIVE', true);
        $inactiveProvider = $this->provider('INACTIVE', false);

        $incomingId = $this->service($activeProvider, 'Mobile Money entrant', 'IN', true);
        $this->service($activeProvider, 'Retrait', 'OUT', true);
        $this->service($activeProvider, 'Service désactivé', 'IN', false);
        $this->service($inactiveProvider, 'Provider désactivé', 'IN', true);

        $response = $this->getJson('/api/payment_services?sens=OUT');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $incomingId)
            ->assertJsonPath('data.0.sens', 'IN')
            ->assertJsonPath('data.0.provider_fee_percentage', 2.5)
            ->assertJsonPath('data.0.user_fee_percentage', 100)
            ->assertJsonMissingPath('data.0.provider')
            ->assertJsonMissingPath('data.0.payment_provider_id');
    }

    public function test_payment_request_normalizes_phones_and_resolves_the_local_service_id(): void
    {
        $user = $this->user('request@example.test');
        $providerId = $this->provider('NORMALIZATION', true);
        $serviceId = $this->service($providerId, 'Service entrant', 'IN', true);
        DB::table('payment_services')->where('id', $serviceId)->update([
            'subscription_id' => '701',
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'libelle' => 'Premium',
            'prix' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = StorePaymentRequest::create('/api/paiement', 'POST', [
            'categorie_id' => $categoryId,
            'nombre_de_code' => 1,
            'numero_payeur' => '+237 690 00 00 00',
            'numero_client' => '00237 691 00 00 00',
            'subscription_id' => '701',
            'sens' => 'OUT',
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setUserResolver(fn () => $user);
        $request->validateResolved();

        $this->assertSame('690000000', $request->validated('numero_payeur'));
        $this->assertSame('691000000', $request->validated('numero_client'));
        $this->assertSame('IN', $request->validated('sens'));
        $this->assertSame($serviceId, (int) $request->validated('payment_service_id'));
        $this->assertSame($serviceId, $request->paymentService()->id);
    }

    public function test_transaction_status_is_private_and_exposes_a_failure_reason(): void
    {
        $owner = $this->user('owner@example.test');
        $otherUser = $this->user('other@example.test');
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $owner->id,
            'reference' => 'MPP-mobile-status',
            'amount' => 1025,
            'phone_number' => '690000000',
            'status' => 'FAILED',
            'sens' => 'IN',
            'raison_reject' => 'Solde insuffisant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner, 'api');
        $this->getJson("/api/transactions/{$transactionId}/status")
            ->assertOk()
            ->assertJsonPath('data.status', 'FAILED')
            ->assertJsonPath('data.is_final', true)
            ->assertJsonPath('data.is_successful', false)
            ->assertJsonPath('data.failure_reason', 'Solde insuffisant');

        $this->actingAs($otherUser, 'api');
        $this->getJson("/api/transactions/{$transactionId}/status")
            ->assertNotFound();
    }

    public function test_success_is_only_final_after_the_associated_payment_is_activated(): void
    {
        $owner = $this->user('success@example.test');
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $owner->id,
            'reference' => 'MPP-success-status',
            'amount' => 1025,
            'phone_number' => '690000000',
            'status' => 'SUCCESS',
            'sens' => 'IN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'libelle' => 'Premium',
            'prix' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentId = DB::table('paiements')->insertGetId([
            'transaction_id' => $transactionId,
            'user_id' => $owner->id,
            'categorie_id' => $categoryId,
            'nombre_de_code' => 1,
            'montant' => 1000,
            'numero_payeur' => '690000000',
            'numero_client' => '691000000',
            'status' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner, 'api');
        $this->getJson("/api/transactions/{$transactionId}/status")
            ->assertOk()
            ->assertJsonPath('data.is_final', false)
            ->assertJsonPath('data.is_successful', false);

        DB::table('paiements')->where('id', $paymentId)->update([
            'status' => true,
            'paiement_date' => now(),
        ]);

        $this->getJson("/api/transactions/{$transactionId}/status")
            ->assertOk()
            ->assertJsonPath('data.is_final', true)
            ->assertJsonPath('data.is_successful', true)
            ->assertJsonPath('data.payment_id', $paymentId);
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => 'Mobile User',
            'email' => $email,
            'password' => 'password',
            'rule_id' => 2,
        ]);
    }

    private function provider(string $code, bool $active): int
    {
        return DB::table('payment_providers')->insertGetId([
            'name' => $code,
            'code' => $code,
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function service(int $providerId, string $title, string $sens, bool $active): int
    {
        return DB::table('payment_services')->insertGetId([
            'payment_provider_id' => $providerId,
            'title' => $title,
            'status' => 1,
            'is_active' => $active,
            'reg_exp' => '^6[0-9]{8}$',
            'sens' => $sens,
            'provider_fee_percentage' => 2.5,
            'user_fee_percentage' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rule_id')->default(2);
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('user_phone_emei')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->integer('prix')->default(0);
            $table->timestamps();
        });
        Schema::create('payment_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
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
            $table->decimal('provider_fee_percentage', 8, 4)->default(2.5);
            $table->decimal('user_fee_percentage', 5, 2)->default(100);
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('base_amount', 12, 2)->nullable();
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->string('conclusion_method')->nullable();
            $table->string('phone_number');
            $table->string('status');
            $table->string('sens');
            $table->string('raison_reject')->nullable();
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
            $table->timestamps();
        });
    }
}
