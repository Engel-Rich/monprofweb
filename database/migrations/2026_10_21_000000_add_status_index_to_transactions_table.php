<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Le polling filtre toutes les 5 secondes sur status puis created_at.
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
