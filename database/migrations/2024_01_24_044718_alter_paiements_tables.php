<?php

use App\Models\Categorie;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Paiements;
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
        Schema::table("paiements",function(Blueprint $table){            
            $table->dateTimeTz('paiement_date')->nullable();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Categorie::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Classe::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('nombre_de_code')->default(1);
            $table->double('montant');
            $table->string('numero_payeur');
            $table->string('numero_client')->nullable();
            $table->boolean('status')->default(0);
        });

        Schema::table("codes",function(Blueprint $table){
           $table->foreignIdFor(Paiements::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
           $table->foreignIdFor(Eleve::class)->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
           $table->string('code')->unique();
           $table->dateTimeTz('active_date');
           $table->boolean('actif')->default(0);
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
