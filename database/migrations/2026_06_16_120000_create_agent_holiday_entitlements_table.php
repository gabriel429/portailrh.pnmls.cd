<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_holiday_entitlements', function (Blueprint $table) {
            $table->id();
            // agents est en MyISAM: ->constrained() ne crée pas de contrainte
            // réelle et une ALTER TABLE ADD CONSTRAINT séparée y échoue avec
            // l'erreur 1824. Colonnes simples + index, comme le reste du projet.
            $table->unsignedBigInteger('agent_id');
            $table->year('annee');
            $table->unsignedSmallInteger('jours_autorises')->default(30);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'annee'], 'unique_agent_holiday_entitlement');
            $table->index(['annee', 'jours_autorises']);
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_holiday_entitlements');
    }
};
