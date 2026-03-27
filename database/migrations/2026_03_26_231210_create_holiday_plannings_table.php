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
        Schema::create('holiday_plannings', function (Blueprint $table) {
            $table->id();
            $table->year('annee');
            $table->string('type_structure'); // 'department', 'sen', 'sena', 'sep', 'local'
            $table->unsignedBigInteger('structure_id'); // ID de la structure (department_id, etc.)
            $table->string('nom_structure'); // Nom de la structure pour faciliter les requêtes
            $table->integer('jours_conge_totaux')->default(30); // Nombre de jours de congés autorisés
            $table->integer('jours_utilises')->default(0); // Jours déjà utilisés
            $table->json('periods_fermeture')->nullable(); // Périodes de fermeture obligatoire
            $table->text('notes')->nullable(); // Notes spécifiques au planning
            $table->boolean('valide')->default(false); // Planning validé par la hiérarchie
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['annee', 'type_structure']);
            $table->index(['structure_id', 'type_structure']);
            $table->unique(['annee', 'type_structure', 'structure_id'], 'unique_planning_structure');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('validated_by')->references('id')->on('agents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_plannings');
    }
};
