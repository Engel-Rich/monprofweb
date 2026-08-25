<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentFeesMigrationTest extends TestCase
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

        Schema::create('payment_services', function (Blueprint $table): void {
            $table->id();
            $table->string('sens')->nullable();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->decimal('amount', 12, 2);
            $table->string('raison_reject')->nullable();
        });
        Schema::create('paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->decimal('montant', 12, 2);
        });
    }

    public function test_it_adds_defaults_and_backfills_existing_transaction_fees(): void
    {
        $serviceId = DB::table('payment_services')->insertGetId(['sens' => 'IN']);
        $transactionId = DB::table('transactions')->insertGetId([
            'amount' => 1025,
        ]);
        DB::table('paiements')->insert([
            'transaction_id' => $transactionId,
            'montant' => 1000,
        ]);

        $serviceMigration = require database_path('migrations/2026_08_25_000000_add_fee_configuration_to_payment_services_table.php');
        $transactionMigration = require database_path('migrations/2026_08_25_000001_add_fees_and_conclusion_method_to_transactions_table.php');
        $serviceMigration->up();
        $transactionMigration->up();

        $service = DB::table('payment_services')->find($serviceId);
        $transaction = DB::table('transactions')->find($transactionId);

        $this->assertSame(2.5, (float) $service->provider_fee_percentage);
        $this->assertSame(100.0, (float) $service->user_fee_percentage);
        $this->assertSame(1000.0, (float) $transaction->base_amount);
        $this->assertSame(25.0, (float) $transaction->service_fee);
        $this->assertTrue(Schema::hasColumn('transactions', 'conclusion_method'));
    }
}
