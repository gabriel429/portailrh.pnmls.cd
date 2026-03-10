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
        Schema::create('historique_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->foreignId('modifie_par')->comment('ID de l\'utilisateur qui a fait la modification')->constrained('agents')->onDelete('restrict');
            $table->string('table_name')->comment('Nom de la table modifiée');
            $table->unsignedBigInteger('record_id')->comment('ID du record modifié');
            $table->string('action')->comment('Action effectuée (CREATE, UPDATE, DELETE)');
            $table->json('donnees_avant')->nullable()->comment('Données avant la modification');
            $table->json('donnees_apres')->nullable()->comment('Données après la modification');
            $table->text('raison')->nullable()->comment('Raison de la modification');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historique_modifications');
    }
};
