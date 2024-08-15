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
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->require();
            $table->string('description')->nullable()->default('Description pour les accès du role');
            $table->timestamps();
        });
        DB::table('rules')->insert(
            [
                [
                    'name' => 'Admin',
                    'description' => 'Administrateur avec tous les droits',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Eleve',
                    'description' => 'Utilisateur standard qui sont des élèves',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Parent',
                    'description' => 'Utilisateur standard qui sont des parents',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
