<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionProviderReferenceMigrationTest extends TestCase
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

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_id')->nullable()->unique();
        });
    }

    public function test_the_provider_reference_is_renamed_without_losing_existing_values(): void
    {
        DB::table('transactions')->insert(['transaction_id' => 'provider-existing-123']);
        $migration = require database_path('migrations/2026_08_24_220000_rename_transaction_id_to_provider_reference.php');

        $migration->up();

        $this->assertTrue(Schema::hasColumn('transactions', 'provider_reference'));
        $this->assertFalse(Schema::hasColumn('transactions', 'transaction_id'));
        $this->assertSame('provider-existing-123', DB::table('transactions')->value('provider_reference'));

        $migration->down();

        $this->assertTrue(Schema::hasColumn('transactions', 'transaction_id'));
        $this->assertSame('provider-existing-123', DB::table('transactions')->value('transaction_id'));
    }
}
