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
        Schema::table("questions",function(Blueprint $table){
            $table->string('image_url')->nullable();
            $table->foreignIdFor(\App\Models\Questions::class)->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
       
       
        Schema::table("users",function(Blueprint $table){    
            $table->string('profile_image')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("questions",function(Blueprint $table){            
            // $table->dropColumn('active_date');
            $table->dropColumn('image_url')->nullable();
            $table->dropForeignIdFor(\App\Models\Questions::class);
            $table->dropColumn('questions_id')->nullable();
        });
        
        Schema::table("users",function(Blueprint $table){    
            $table->dropColumn('profile_image');
        });
    }
};
