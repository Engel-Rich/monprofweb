<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('payment_provider_id')
                ->nullable()
                ->after('id')
                ->constrained('payment_providers')
                ->nullOnDelete();
        });

        $activeProviderId = DB::table('payment_providers')
            ->where('is_active', true)
            ->value('id');

        if ($activeProviderId) {
            DB::table('transactions')
                ->whereNull('payment_provider_id')
                ->update(['payment_provider_id' => $activeProviderId]);
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropColumn('payment_provider_id');
            } else {
                $table->dropConstrainedForeignId('payment_provider_id');
            }
        });
    }
};
