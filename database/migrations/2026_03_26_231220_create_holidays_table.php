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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('holiday_planning_id')->nullable(); // Lien vers le planning
            $table->date('date_debut');
            $table->date('date_fin');
            $table->integer('nombre_jours'); // Nombre de jours calculé
            $table->enum('type_conge', ['annuel', 'maladie', 'maternite', 'paternite', 'urgence', 'special']);
            $table->enum('statut_demande', ['en_attente', 'approuve', 'refuse', 'annule']);
            $table->text('motif')->nullable(); // Motif de la demande
            $table->text('commentaire_refus')->nullable(); // Raison du refus le cas échéant
            $table->string('document_medical')->nullable(); // Pour congé maladie
            $table->boolean('report_possible')->default(false); // Si le congé peut être reporté
            $table->date('date_retour_prevu');
            $table->date('date_retour_effectif')->nullable(); // Date réelle de retour
            $table->unsignedBigInteger('demande_par'); // Qui a fait la demande
            $table->unsignedBigInteger('approuve_par')->nullable();
            $table->timestamp('approuve_le')->nullable();
            $table->unsignedBigInteger('refuse_par')->nullable();
            $table->timestamp('refuse_le')->nullable();
            $table->timestamps();

            // Index
            $table->index(['agent_id', 'date_debut', 'date_fin']);
            $table->index(['date_debut', 'date_fin']); // Pour vérifier les conflits
            $table->index(['statut_demande', 'type_conge']);
            $table->index('holiday_planning_id');

            // Foreign keys
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('holiday_planning_id')->references('id')->on('holiday_plannings')->onDelete('set null');
            $table->foreign('demande_par')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('approuve_par')->references('id')->on('agents')->onDelete('set null');
            $table->foreign('refuse_par')->references('id')->on('agents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
