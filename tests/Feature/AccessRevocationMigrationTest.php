<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccessRevocationMigrationTest extends TestCase
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

        Schema::create('codes', function (Blueprint $table): void {
            $table->id();
            $table->boolean('actif')->default(false);
        });
        Schema::create('paiements', function (Blueprint $table): void {
            $table->id();
            $table->boolean('status')->default(false);
        });
    }

    public function test_it_adds_revocation_audit_fields(): void
    {
        $migration = require database_path('migrations/2026_08_25_000002_add_revocation_fields_to_codes_and_paiements_tables.php');

        $migration->up();

        foreach (['revoked_at', 'revoked_by', 'revocation_reason'] as $column) {
            $this->assertTrue(Schema::hasColumn('codes', $column));
            $this->assertTrue(Schema::hasColumn('paiements', $column));
        }
    }
}
