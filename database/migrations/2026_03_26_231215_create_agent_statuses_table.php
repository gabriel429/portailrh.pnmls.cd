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
        Schema::create('agent_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->enum('statut', ['disponible', 'en_conge', 'en_mission', 'suspendu', 'en_formation']);
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('motif')->nullable(); // Motif du changement de statut
            $table->text('commentaire')->nullable(); // Détails supplémentaires
            $table->string('document_joint')->nullable(); // Fichier justificatif éventuel
            $table->boolean('actuel')->default(true); // Indique si c'est le statut actuel
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable(); // Qui a approuvé le changement
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Index
            $table->index(['agent_id', 'actuel']);
            $table->index(['agent_id', 'date_debut', 'date_fin']);
            $table->index('statut');

            // Foreign keys
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('agents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_statuses');
    }
};
