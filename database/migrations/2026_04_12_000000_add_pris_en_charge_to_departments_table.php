<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le flag pris_en_charge pour distinguer les départements
     * fonctionnels (gérés par le système) des départements conservés
     * uniquement pour l'affectation et l'historisation.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('pris_en_charge')
                  ->default(false)
                  ->after('description')
                  ->comment('Département fonctionnel géré par le système (dashboard, directeur, stats)');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('pris_en_charge');
        });
    }
};
