<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Un code attribué depuis la console repose sur un paiement à 0 : l'origine
    // le distingue des achats pour les listes de paiements et les statistiques.
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table): void {
            $table->string('origin', 20)->default('PURCHASE')->index()->after('status');
            $table->foreignId('granted_by')->nullable()->after('origin')->constrained('users')->nullOnDelete();
            $table->string('grant_reason', 500)->nullable()->after('granted_by');
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('granted_by');
            $table->dropIndex(['origin']);
            $table->dropColumn(['origin', 'grant_reason']);
        });
    }
};
