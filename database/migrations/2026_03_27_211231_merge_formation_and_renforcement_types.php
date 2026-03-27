<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrer toutes les demandes "formation" vers "renforcement_capacites"
        DB::table('requests')
            ->where('type', 'formation')
            ->update(['type' => 'renforcement_capacites']);

        // Ajouter le département Documentation, Recherche et Renforcement des Capacités s'il n'existe pas
        DB::table('departments')->updateOrInsert(
            ['code' => 'DRRC'],
            [
                'nom' => 'Département Documentation, Recherche et Renforcement des Capacités',
                'description' => 'Documentation scientifique, recherche et renforcement des capacités du personnel',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer les types "renforcement_capacites" en "formation" (approximation)
        // Note: impossible de savoir quelles demandes étaient à l'origine "formation"
        // vs. "renforcement_capacites" donc on ne fait rien

        // Supprimer le département DRRC
        DB::table('departments')->where('code', 'DRRC')->delete();
    }
};
