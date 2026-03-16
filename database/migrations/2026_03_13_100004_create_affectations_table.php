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
        if (Schema::hasTable('affectations')) {
            return;
        }

        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->foreignId('fonction_id')->constrained('fonctions')->onDelete('restrict');

            // Niveau administratif : dans quel secrétariat l'agent est affecté
            $table->enum('niveau_administratif', ['SEN', 'SEP', 'SEL'])
                  ->default('SEN')
                  ->comment('SEN=National, SEP=Provincial, SEL=Local');

            // Rattachement structurel précis
            $table->enum('niveau', [
                'direction',        // SEN/SENA (hors département)
                'service_rattache', // Service directement sous SEN/SENA
                'département',      // Rattaché au département (sans section)
                'section',          // Rattaché à une section
                'cellule',          // Rattaché à une cellule
                'province',         // SEP (rattaché à une province)
                'local',            // SEL (rattaché à une localité)
            ])->default('département');

            // FKs structurelles (SEN)
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade');
            $table->foreignId('cellule_id')->nullable()->constrained('cellules')->onDelete('cascade');

            // FKs SEP / SEL
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('cascade');
            $table->foreignId('localite_id')->nullable()->constrained('localites')->onDelete('cascade');

            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);
            $table->text('remarque')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
