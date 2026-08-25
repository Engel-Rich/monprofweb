<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_services', function (Blueprint $table): void {
            $table->decimal('provider_fee_percentage', 8, 4)
                ->default(2.5)
                ->after('sens');
            $table->decimal('user_fee_percentage', 5, 2)
                ->default(100)
                ->after('provider_fee_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('payment_services', function (Blueprint $table): void {
            $table->dropColumn([
                'provider_fee_percentage',
                'user_fee_percentage',
            ]);
        });
    }
};
