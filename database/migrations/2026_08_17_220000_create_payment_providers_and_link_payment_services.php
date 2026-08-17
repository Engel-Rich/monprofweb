<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('image')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        $now = now();
        $campayId = DB::table('payment_providers')->insertGetId([
            'name' => 'CamPay',
            'code' => 'CAMPAY',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('payment_providers')->insert([
            'name' => 'MundiPay',
            'code' => 'MUNDIPAY',
            'is_active' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::table('payment_services', function (Blueprint $table) {
            $table->foreignId('payment_provider_id')
                ->nullable()
                ->after('id')
                ->constrained('payment_providers')
                ->nullOnDelete();
        });

        DB::table('payment_services')
            ->whereNull('payment_provider_id')
            ->update(['payment_provider_id' => $campayId]);
    }

    public function down(): void
    {
        Schema::table('payment_services', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropColumn('payment_provider_id');
            } else {
                $table->dropConstrainedForeignId('payment_provider_id');
            }
        });

        Schema::dropIfExists('payment_providers');
    }
};
