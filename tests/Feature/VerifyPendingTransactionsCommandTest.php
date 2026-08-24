<?php

namespace Tests\Feature;

use App\DTO\CreateTransactionDto;
use App\DTO\PaymentResult;
use App\Enums\TransactionStatus;
use App\Jobs\VerifyPendingTransaction;
use App\Models\PaymentProvider;
use App\Models\Transaction;
use App\Services\PaiementService;
use App\Services\Payments\PaymentFactory;
use App\Services\Payments\PaymentResponseModel;
use App\Services\Payments\PaymentStrategy;
use App\Services\Payments\TransactionFinalizationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class VerifyPendingTransactionsCommandTest extends TestCase
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

        Schema::create('payment_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_provider_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('payment_token')->nullable();
            $table->string('reference')->nullable();
            $table->string('amount');
            $table->string('phone_number');
            $table->string('status')->default('PENDING');
            $table->string('sens')->default('IN');
            $table->string('service_id')->nullable();
            $table->string('internal_service')->nullable();
            $table->string('subscription_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('raison_reject')->nullable();
            $table->text('metadatas')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });

        Schema::create('paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->dateTime('paiement_date')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('categorie_id')->nullable();
            $table->integer('nombre_de_code')->default(1);
            $table->decimal('montant', 12, 2)->default(0);
            $table->string('numero_payeur')->nullable();
            $table->string('numero_client')->nullable();
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

    public function test_the_command_verifies_pending_transactions_once(): void
    {
        PollingPaymentStrategy::$verificationCount = 0;
        PollingPaymentStrategy::$verificationStatus = TransactionStatus::PENDING->value;
        PaymentFactory::extend('TEST_POLLING', PollingPaymentStrategy::class);

        $provider = PaymentProvider::create([
            'name' => 'Test polling',
            'code' => 'TEST_POLLING',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => 'provider-123',
            'reference' => 'MPP-test',
            'amount' => '1000',
            'phone_number' => '690000000',
            'status' => TransactionStatus::PENDING->value,
            'sens' => 'IN',
            'internal_service' => 'TEST',
        ]);

        $finalizer = Mockery::mock(TransactionFinalizationService::class);
        $finalizer->shouldReceive('applyProviderResult')
            ->once()
            ->withArgs(fn (Transaction $checked, PaymentResult $result, string $providerCode) => $checked->is($transaction)
                && $result->status === TransactionStatus::PENDING
                && $providerCode === 'TEST_POLLING')
            ->andReturn(false);
        $this->app->instance(TransactionFinalizationService::class, $finalizer);

        $exitCode = Artisan::call('payments:verify-pending', ['--once' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, PollingPaymentStrategy::$verificationCount);
        $this->assertStringContainsString('1 vérification(s)', Artisan::output());
    }

    public function test_a_successful_provider_response_finalizes_the_transaction(): void
    {
        PollingPaymentStrategy::$verificationCount = 0;
        PollingPaymentStrategy::$verificationStatus = TransactionStatus::SUCCESS->value;
        PaymentFactory::extend('TEST_SUCCESS', PollingPaymentStrategy::class);
        $provider = PaymentProvider::create([
            'name' => 'Test success',
            'code' => 'TEST_SUCCESS',
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => 'provider-success-123',
            'reference' => 'MPP-provider-success',
            'amount' => '1000',
            'phone_number' => '690000002',
            'status' => TransactionStatus::PENDING->value,
            'sens' => 'IN',
            'internal_service' => 'TEST',
        ]);

        $exitCode = Artisan::call('payments:verify-pending', ['--once' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(TransactionStatus::SUCCESS->value, $transaction->fresh()->status);
        $this->assertSame('TEST_SUCCESS', data_get($transaction->fresh()->metadatas, 'status_checks.0.provider'));
    }

    public function test_an_already_finalized_payment_is_not_processed_twice(): void
    {
        $paymentId = DB::table('paiements')->insertGetId([
            'paiement_date' => now(),
            'user_id' => 999,
            'nombre_de_code' => 1,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('codes')->insert([
            'paiements_id' => $paymentId,
            'code' => 'C-ALREADY-CREATED',
            'actif' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $finalized = app(PaiementService::class)->validePayment(['paiement' => $paymentId]);

        $this->assertFalse($finalized);
        $this->assertDatabaseCount('paiements', 1);
        $this->assertDatabaseCount('codes', 1);
    }

    public function test_success_creates_the_activation_code_before_marking_the_transaction_successful(): void
    {
        Http::fake();
        PollingPaymentStrategy::$verificationStatus = TransactionStatus::SUCCESS->value;
        PaymentFactory::extend('TEST_ACTIVATION', PollingPaymentStrategy::class);
        $provider = PaymentProvider::create([
            'name' => 'Test activation',
            'code' => 'TEST_ACTIVATION',
            'is_active' => true,
        ]);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Élève',
            'email' => 'student@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => 'provider-course-access',
            'reference' => 'MPP-course-access',
            'amount' => '1000',
            'phone_number' => '690000003',
            'status' => TransactionStatus::PENDING->value,
            'sens' => 'IN',
            'internal_service' => 'MONPROF_PURCHASE',
            'user_id' => $userId,
        ]);
        $paymentId = DB::table('paiements')->insertGetId([
            'transaction_id' => $transaction->id,
            'user_id' => $userId,
            'categorie_id' => 10,
            'nombre_de_code' => 1,
            'montant' => 1000,
            'numero_payeur' => '690000003',
            'numero_client' => '690000003',
            'status' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('payments:verify-pending', [
            '--once' => true,
            '--transaction' => $transaction->id,
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertSame(TransactionStatus::SUCCESS->value, $transaction->fresh()->status);
        $this->assertDatabaseHas('paiements', ['id' => $paymentId, 'status' => true]);
        $this->assertNotNull(DB::table('paiements')->where('id', $paymentId)->value('paiement_date'));
        $this->assertDatabaseCount('codes', 1);
        $this->assertFalse((bool) DB::table('codes')->where('paiements_id', $paymentId)->value('actif'));
    }

    public function test_provider_success_without_a_linked_payment_is_reported_as_an_error(): void
    {
        PollingPaymentStrategy::$verificationStatus = TransactionStatus::SUCCESS->value;
        PaymentFactory::extend('TEST_INCOMPLETE', PollingPaymentStrategy::class);
        $provider = PaymentProvider::create([
            'name' => 'Test incomplete',
            'code' => 'TEST_INCOMPLETE',
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => 'provider-without-payment',
            'reference' => 'MPP-without-payment',
            'amount' => '1000',
            'phone_number' => '690000004',
            'status' => TransactionStatus::PENDING->value,
            'sens' => 'IN',
            'internal_service' => 'MONPROF_PURCHASE',
        ]);

        $exitCode = Artisan::call('payments:verify-pending', [
            '--once' => true,
            '--transaction' => $transaction->id,
            '-v' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(TransactionStatus::PENDING->value, $transaction->fresh()->status);
        $this->assertStringContainsString('finalisation locale', Artisan::output());
    }

    public function test_dry_run_checks_the_provider_without_updating_the_transaction(): void
    {
        PollingPaymentStrategy::$verificationStatus = TransactionStatus::SUCCESS->value;
        PaymentFactory::extend('TEST_DRY_RUN', PollingPaymentStrategy::class);
        $provider = PaymentProvider::create([
            'name' => 'Test dry run',
            'code' => 'TEST_DRY_RUN',
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => 'provider-dry-run',
            'reference' => 'MPP-dry-run',
            'amount' => '1000',
            'phone_number' => '690000005',
            'status' => TransactionStatus::PENDING->value,
            'sens' => 'IN',
            'internal_service' => 'TEST',
        ]);

        $exitCode = Artisan::call('payments:verify-pending', [
            '--once' => true,
            '--transaction' => $transaction->id,
            '--dry-run' => true,
            '-v' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(TransactionStatus::PENDING->value, $transaction->fresh()->status);
        $this->assertStringContainsString('aucune donnée locale modifiée', Artisan::output());
    }

    public function test_a_missing_target_transaction_returns_a_failure(): void
    {
        $exitCode = Artisan::call('payments:verify-pending', [
            '--once' => true,
            '--transaction' => 999,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('introuvable', Artisan::output());
    }

    public function test_a_late_pending_status_cannot_downgrade_a_successful_transaction(): void
    {
        $provider = PaymentProvider::create([
            'name' => 'Terminal provider',
            'code' => 'TERMINAL_PROVIDER',
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => 'provider-success',
            'reference' => 'MPP-success',
            'amount' => '1500',
            'phone_number' => '690000001',
            'status' => TransactionStatus::SUCCESS->value,
            'sens' => 'IN',
            'internal_service' => 'TEST',
        ]);
        $paymentService = Mockery::mock(PaiementService::class);
        $paymentService->shouldNotReceive('validePayment');
        $finalizer = new TransactionFinalizationService($paymentService);

        $applied = $finalizer->applyStatus($transaction, TransactionStatus::PENDING);

        $this->assertFalse($applied);
        $this->assertSame(TransactionStatus::SUCCESS->value, $transaction->fresh()->status);
    }

    public function test_an_admin_retry_can_reconcile_a_failed_transaction(): void
    {
        PollingPaymentStrategy::$verificationCount = 0;
        PollingPaymentStrategy::$verificationStatus = TransactionStatus::SUCCESS->value;
        PaymentFactory::extend('TEST_ADMIN_RETRY', PollingPaymentStrategy::class);
        $provider = PaymentProvider::create([
            'name' => 'Retry provider',
            'code' => 'TEST_ADMIN_RETRY',
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => 'provider-late-success',
            'payment_token' => 'provider-pay-token',
            'reference' => 'MPP-late-success',
            'amount' => '1500',
            'phone_number' => '690000008',
            'status' => TransactionStatus::FAILED->value,
            'sens' => 'IN',
            'internal_service' => 'TEST',
        ]);

        $result = (new VerifyPendingTransaction($transaction->id, force: true))
            ->handle(app(TransactionFinalizationService::class));

        $this->assertFalse($result->isError());
        $this->assertSame(TransactionStatus::SUCCESS->value, $result->providerStatus);
        $this->assertSame(TransactionStatus::SUCCESS->value, $transaction->fresh()->status);
        $this->assertSame(1, PollingPaymentStrategy::$verificationCount);
        $this->assertSame('provider-pay-token', PollingPaymentStrategy::$lastPaymentToken);
    }

    public function test_a_transaction_without_a_provider_reference_is_never_sent_to_the_provider(): void
    {
        PollingPaymentStrategy::$verificationCount = 0;
        PollingPaymentStrategy::$verificationStatus = TransactionStatus::PENDING->value;
        PaymentFactory::extend('TEST_NO_REFERENCE', PollingPaymentStrategy::class);
        $provider = PaymentProvider::create([
            'name' => 'Test sans référence',
            'code' => 'TEST_NO_REFERENCE',
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => null,
            'reference' => 'MPP-no-reference',
            'amount' => '1000',
            'phone_number' => '690000006',
            'status' => TransactionStatus::PENDING->value,
            'sens' => 'IN',
            'internal_service' => 'TEST',
        ]);

        // Ciblée : la garde du job doit s'appliquer même sans le filtre SQL.
        $exitCode = Artisan::call('payments:verify-pending', [
            '--once' => true,
            '--transaction' => $transaction->id,
            '-v' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, PollingPaymentStrategy::$verificationCount);
        $this->assertSame(TransactionStatus::PENDING->value, $transaction->fresh()->status);
        $this->assertStringContainsString('Référence fournisseur pas encore disponible', Artisan::output());

        // Balayage global : le filtre SQL doit l'écarter aussi.
        Artisan::call('payments:verify-pending', ['--once' => true, '--no-expire' => true]);

        $this->assertSame(0, PollingPaymentStrategy::$verificationCount);
    }

    public function test_a_stale_transaction_is_expired_instead_of_being_polled_forever(): void
    {
        PollingPaymentStrategy::$verificationCount = 0;
        PollingPaymentStrategy::$verificationStatus = TransactionStatus::PENDING->value;
        PaymentFactory::extend('TEST_STALE', PollingPaymentStrategy::class);
        config(['payments.polling.max_age' => 180]);

        $provider = PaymentProvider::create([
            'name' => 'Test expiration',
            'code' => 'TEST_STALE',
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'provider_reference' => 'provider-stale',
            'reference' => 'MPP-stale',
            'amount' => '1000',
            'phone_number' => '690000007',
            'status' => TransactionStatus::PENDING->value,
            'sens' => 'IN',
            'internal_service' => 'TEST',
        ]);
        $transaction->forceFill(['created_at' => now()->subHours(4)])->save();

        $exitCode = Artisan::call('payments:verify-pending', ['--once' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, PollingPaymentStrategy::$verificationCount);
        $this->assertSame(TransactionStatus::FAILED->value, $transaction->fresh()->status);
        $this->assertStringContainsString('Délai de paiement dépassé', (string) $transaction->fresh()->raison_reject);
    }
}

class PollingPaymentStrategy implements PaymentStrategy
{
    public static int $verificationCount = 0;

    public static string $verificationStatus = 'PENDING';

    public static ?string $lastPaymentToken = null;

    public function startPayment(CreateTransactionDto $dto): PaymentResponseModel
    {
        throw new \LogicException('Not used by this test.');
    }

    public function checkStatus(string $reference): PaymentResponseModel
    {
        throw new \LogicException('Not used by this test.');
    }

    public function processPayment(CreateTransactionDto $dto): PaymentResult
    {
        throw new \LogicException('Not used by this test.');
    }

    public function cancelPayment(string $providerReference): PaymentResult
    {
        throw new \LogicException('Not used by this test.');
    }

    public function verifyPayment(string $providerReference, ?string $paymentToken = null): PaymentResult
    {
        self::$verificationCount++;
        self::$lastPaymentToken = $paymentToken;

        return new PaymentResult(
            status: TransactionStatus::from(self::$verificationStatus),
            providerReference: $providerReference,
        );
    }

    public function getProviderName(): string
    {
        return 'TEST_POLLING';
    }
}
