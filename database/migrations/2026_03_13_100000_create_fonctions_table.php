<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fonctions')) {
            return;
        }

        Schema::create('fonctions', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique()->comment('Intitulé du poste / de la fonction');
            $table->enum('niveau_administratif', ['SEN', 'SEP', 'SEL', 'TOUS'])
                  ->default('SEN')
                  ->comment('SEN=National, SEP=Provincial, SEL=Local, TOUS=Tous niveaux');
            $table->enum('type_poste', [
                'direction',       // SEN/SENA
                'service_rattache',// services directement rattachés SEN
                'département',     // poste de département (SEN)
                'section',         // poste de section (SEN)
                'cellule',         // poste de cellule (SEN)
                'appui',           // poste d'appui/support (chauffeur, commis…)
                'province',        // poste SEP
                'local',           // poste SEL
            ])->default('département')->comment('Catégorie structurelle du poste');
            $table->text('description')->nullable();
            $table->boolean('est_chef')->default(false)->comment('Poste de responsable unique par entité');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonctions');
    }
};
