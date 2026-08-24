<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete();
            $table->string('local_reference')->nullable()->index();
            $table->string('provider_reference')->nullable()->index();
            $table->string('provider_code', 50)->nullable()->index();
            $table->string('event', 100)->index();
            $table->string('source', 50)->default('application')->index();
            $table->string('status_from', 30)->nullable();
            $table->string('status_to', 30)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->json('changes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
