<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Affectations : lie un agent à une fonction dans un département, section ou cellule.
     * Règles :
     *  - Chef de département  → 1 seul par département  (est_chef=true, niveau=département)
     *  - Assistant/Secrétaire → 1 seul par département  (est_chef=false, niveau=département)
     *  - Chef de section      → 1 seul par section      (est_chef=true, niveau=section)
     *  - Assistants           → plusieurs par section   (est_chef=false, niveau=section)
     *  - Chef de cellule      → 1 seul par cellule      (est_chef=true, niveau=cellule)
     */
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->foreignId('fonction_id')->constrained('fonctions')->onDelete('restrict');
            $table->enum('niveau', ['département', 'section', 'cellule']);
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade');
            $table->foreignId('cellule_id')->nullable()->constrained('cellules')->onDelete('cascade');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);
            $table->text('remarque')->nullable();
            $table->timestamps();

            // Un agent ne peut occuper la même fonction dans la même entité qu'une fois
            $table->unique(['agent_id', 'fonction_id', 'department_id', 'section_id', 'cellule_id'], 'affectation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
