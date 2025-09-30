<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string("transaction_id")->unique();
            $table->string("payment_token")->unique();
            $table->string("reference")->unique()->nullable();
            $table->string("amount");
            $table->string("phone_number");
            $table->enum("status", ["PENDING", "SUCCESS", "FAILED"])->default("PENDING");
            $table->enum("sens", ["IN", "OUT"])->default("IN");
            $table->string("service_id")->nullable();
            $table->string("internal_service")->nullable()->default("MONPROF_PURCHASE");
            $table->string("subscription_id")->nullable();
            $table->string("user_id")->nullable();
            $table->string("raison_reject")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
