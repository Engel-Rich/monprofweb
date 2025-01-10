<?php

use App\Models\Categorie;
use App\Models\Classe;
use App\Models\Professeur;
use App\Models\User;
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
            // $table->dropForeignIdFor(Classe::class);
            // $table->dropForeignIdFor(Categorie::class);
            // $table->dropColumn('classe_id');            
            // $table->dropColumn('categorie_id');            

        });

        Schema::table("reponses",function(Blueprint $table){            
            $table->string('image_url')->change()->nullable();
            $table->string('titre')->change()->nullable();
            $table->dropForeignIdFor(Professeur::class);
            $table->dropColumn('professeur_id');                    
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();    
            // $table->dateTimeTz('active_date')->nullable();
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
