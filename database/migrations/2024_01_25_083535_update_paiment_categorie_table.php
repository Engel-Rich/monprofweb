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
        Schema::table("paiements",function(Blueprint $table){            
            $table->dropForeignIdFor(Classe::class);
            $table->dropColumn(['classe_id']);

        });
        Schema::table("categories",function(Blueprint $table){                       
            $table->double('prix');
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
