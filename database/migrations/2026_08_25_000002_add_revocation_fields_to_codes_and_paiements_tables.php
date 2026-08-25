<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('codes', function (Blueprint $table): void {
            $table->timestamp('revoked_at')->nullable()->index()->after('actif');
            $table->unsignedBigInteger('revoked_by')->nullable()->after('revoked_at');
            $table->string('revocation_reason', 500)->nullable()->after('revoked_by');
        });

        Schema::table('paiements', function (Blueprint $table): void {
            $table->timestamp('revoked_at')->nullable()->index()->after('status');
            $table->unsignedBigInteger('revoked_by')->nullable()->after('revoked_at');
            $table->string('revocation_reason', 500)->nullable()->after('revoked_by');
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table): void {
            $table->dropColumn(['revoked_at', 'revoked_by', 'revocation_reason']);
        });

        Schema::table('codes', function (Blueprint $table): void {
            $table->dropColumn(['revoked_at', 'revoked_by', 'revocation_reason']);
        });
    }
};
