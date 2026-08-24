<?php

namespace Tests\Feature;

use App\Models\PaymentProvider;
use App\Models\Transaction;
use App\Models\TransactionLog;
use App\Services\Payments\TransactionAuditService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionAuditLogTest extends TestCase
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

        Schema::create('payment_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_provider_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('payment_token')->nullable();
            $table->string('reference')->nullable();
            $table->string('amount');
            $table->string('phone_number');
            $table->string('status')->default('PENDING');
            $table->string('sens')->default('IN');
            $table->text('metadatas')->nullable();
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_08_24_230000_create_transaction_logs_table.php');
        $migration->up();
    }

    public function test_the_observer_records_creation_and_status_changes(): void
    {
        $provider = PaymentProvider::create([
            'name' => 'MundiPay',
            'code' => 'MUNDIPAY',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'payment_provider_id' => $provider->id,
            'reference' => 'MPP-audit-test',
            'amount' => '1000',
            'phone_number' => '690000000',
            'status' => 'PENDING',
            'sens' => 'IN',
        ]);
        $transaction->update(['status' => 'SUCCESS']);

        $this->assertDatabaseHas('transaction_logs', [
            'transaction_id' => $transaction->id,
            'event' => 'transaction.created',
            'status_to' => 'PENDING',
        ]);
        $this->assertDatabaseHas('transaction_logs', [
            'transaction_id' => $transaction->id,
            'event' => 'transaction.status_changed',
            'status_from' => 'PENDING',
            'status_to' => 'SUCCESS',
        ]);
    }

    public function test_provider_payloads_are_stored_without_credentials(): void
    {
        $transaction = Transaction::create([
            'reference' => 'MPP-payload-test',
            'amount' => '500',
            'phone_number' => '690000001',
            'status' => 'PENDING',
            'sens' => 'IN',
        ]);

        app(TransactionAuditService::class)->record(
            transaction: $transaction,
            event: 'provider.webhook_received',
            source: 'webhook',
            payload: [
                'status' => 'PAID',
                'pay_token' => 'TOKEN-TO-TRACE',
                'signature' => 'must-not-be-stored',
                'api_key' => 'must-not-be-stored',
            ],
            providerCode: 'MUNDIPAY',
        );

        $log = TransactionLog::query()->where('event', 'provider.webhook_received')->firstOrFail();

        $this->assertSame('PAID', $log->payload['status']);
        $this->assertSame('TOKEN-TO-TRACE', $log->payload['pay_token']);
        $this->assertSame('[REDACTED]', $log->payload['signature']);
        $this->assertSame('[REDACTED]', $log->payload['api_key']);
    }
}
