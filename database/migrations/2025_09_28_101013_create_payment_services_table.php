<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_services', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('img')->nullable();
            $table->text('description')->nullable();
            $table->integer('status')->default(1);
            $table->text('subtitle');
            $table->integer('is_active');
            $table->string('reg_exp')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->enum('sens', ['IN', 'OUT'])->nullable();
            $table->timestamps();
        });

        DB::table('payment_services')->insert([
            [
                'id' => 17,
                'title' => 'MTN MOBILE MONEY',
                'img' => '',
                'description' => 'MTN MOMO DEPOSIT',
                'status' => 1,
                'subtitle' => 'Deposit',
                'is_active' => 1,
                'reg_exp' => '',
                'subscription_id' => 2,
                'sens' => 'IN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 18,
                'title' => 'MTN MOBILE MONEY',
                'img' => 'images/payment/1751363863.png',
                'description' => 'MTN MOMO WITHDRAWAL',
                'status' => 1,
                'subtitle' => 'WITHDRAWAL',
                'is_active' => 1,
                'reg_exp' => '',
                'subscription_id' => 3,
                'sens' => 'OUT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 19,
                'title' => 'ORANGE MONEY',
                'img' => 'images/payment/1751363547.png',
                'description' => 'ORANGA MONEY DEPOSIT',
                'status' => 1,
                'subtitle' => 'Deposit',
                'is_active' => 1,
                'subscription_id' => 1,
                'reg_exp' => '',
                'sens' => 'IN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'title' => 'ORANGE MONEY',
                'img' => 'images/payment/1751363812.png',
                'description' => 'ORANGA MONEY WITHDRAWAL',
                'status' => 1,
                'subtitle' => 'WITHDRAWAL',
                'is_active' => 1,
                'subscription_id' => 4,
                'reg_exp' => '',
                'sens' => 'OUT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_services');
    }
};
