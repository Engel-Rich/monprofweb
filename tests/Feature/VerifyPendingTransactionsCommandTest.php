<?php

namespace Tests\Feature;

use App\DTO\CreateTransactionDto;
use App\DTO\PaymentResult;
use App\Enums\TransactionStatus;
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
            $table->string('transaction_id')->nullable();
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

        Schema::create('paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->dateTime('paiement_date')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('nombre_de_code')->default(1);
            $table->boolean('status')->default(false);
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
            'transaction_id' => 'provider-123',
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
            'transaction_id' => 'provider-success-123',
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

        $finalized = app(PaiementService::class)->validePayment(['paiement' => $paymentId]);

        $this->assertFalse($finalized);
        $this->assertDatabaseCount('paiements', 1);
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
            'transaction_id' => 'provider-success',
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
}

class PollingPaymentStrategy implements PaymentStrategy
{
    public static int $verificationCount = 0;

    public static string $verificationStatus = 'PENDING';

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

    public function cancelPayment(string $transactionId): PaymentResult
    {
        throw new \LogicException('Not used by this test.');
    }

    public function verifyPayment(string $transactionId): PaymentResult
    {
        self::$verificationCount++;

        return new PaymentResult(
            status: TransactionStatus::from(self::$verificationStatus),
            transactionId: $transactionId,
        );
    }

    public function getProviderName(): string
    {
        return 'TEST_POLLING';
    }
}
