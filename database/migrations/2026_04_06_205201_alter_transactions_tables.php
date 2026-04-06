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
        Schema::table("transactions", function (Blueprint $table)  {
            $table->string("transaction_id")->nullable()->change();            
            
            // La reference externe 
            $table->string("payment_token")->nullable()->change();
            $table->jsonb("metadatas")->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
