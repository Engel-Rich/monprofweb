<?php

use App\Models\Classe;
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
        Schema::table("codes",function(Blueprint $table){            
            $table->dropColumn('active_date');
            // $table->dateTimeTz('active_date')->nullable();
        });
        Schema::table("codes",function(Blueprint $table){            
            // $table->dropColumn('active_date');
            $table->dateTimeTz('active_date')->nullable();
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
