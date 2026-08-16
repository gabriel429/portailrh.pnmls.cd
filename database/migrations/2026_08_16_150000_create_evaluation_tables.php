<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Toute la base est en MyISAM (agents, users, provinces, departments...) —
    // ->constrained() ne lève pas d'erreur mais ne crée aucune contrainte réelle.
    // On utilise donc des colonnes simples + index explicites, comme le reste
    // du projet (holiday_plannings, agent_statuses).
    public function up(): void
    {
        if (!Schema::hasTable('evaluation_criteres')) {
            Schema::create('evaluation_criteres', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('libelle');
                $table->text('description')->nullable();
                $table->string('categorie', 40)->default('general');
                $table->decimal('poids', 5, 2);
                $table->unsignedInteger('ordre')->default(0);
                $table->boolean('actif')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('evaluations')) {
            Schema::create('evaluations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id');
                $table->unsignedBigInteger('evaluateur_id');

                // Snapshot organisationnel au moment de l'évaluation — un agent peut
                // être réaffecté par la suite (cf. Affectation), l'évaluation doit
                // rester rattachée à où il travaillait réellement durant la période.
                $table->string('organe')->nullable();
                $table->unsignedBigInteger('province_id')->nullable();
                $table->unsignedBigInteger('departement_id')->nullable();

                $table->enum('periode_type', ['trimestriel', 'annuel'])->default('trimestriel');
                $table->unsignedSmallInteger('periode_annee');
                // 0 = annuel, 1-4 = T1..T4. Volontairement 0 et non NULL: un index
                // unique MySQL traite plusieurs NULL comme distincts et laisserait
                // passer des doublons d'évaluation annuelle pour un même agent.
                $table->unsignedTinyInteger('periode_trimestre')->default(0);

                $table->enum('statut', ['brouillon', 'soumise', 'validee', 'rejetee'])->default('brouillon');

                // Moitié auto-calculée — snapshotée au passage brouillon -> soumise
                $table->decimal('taux_completion_taches', 5, 2)->nullable();
                $table->decimal('taux_assiduite', 5, 2)->nullable();
                $table->decimal('score_auto', 5, 2)->nullable();

                // Moitié manuelle — dérivée de evaluation_details, mise en cache ici
                $table->decimal('score_manuel', 5, 2)->nullable();

                // Combiné
                $table->decimal('score_global', 5, 2)->nullable();

                $table->text('commentaire_general')->nullable();

                $table->unsignedBigInteger('soumise_par_id')->nullable();
                $table->timestamp('soumise_le')->nullable();
                $table->unsignedBigInteger('validee_par_id')->nullable();
                $table->timestamp('validee_le')->nullable();
                $table->text('motif_rejet')->nullable();

                $table->timestamps();

                $table->unique(['agent_id', 'periode_annee', 'periode_trimestre'], 'evaluations_agent_periode_unique');
                $table->index('agent_id');
                $table->index('evaluateur_id');
                $table->index(['organe', 'periode_annee']);
                $table->index('statut');
                $table->index('province_id');
                $table->index('departement_id');
            });
        }

        if (!Schema::hasTable('evaluation_details')) {
            Schema::create('evaluation_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('evaluation_id');
                $table->unsignedBigInteger('evaluation_critere_id');
                $table->decimal('note', 3, 2); // 0.00 - 5.00
                $table->decimal('poids_utilise', 5, 2); // snapshot du poids du critère
                $table->text('commentaire')->nullable();
                $table->timestamps();

                $table->unique(['evaluation_id', 'evaluation_critere_id'], 'evaluation_details_unique');
                $table->index('evaluation_id');
            });
        }

        // Critères standards RH — somme des poids = 100%
        $criteres = [
            ['code' => 'qualite_travail', 'libelle' => 'Qualité du travail', 'categorie' => 'qualite', 'poids' => 25, 'ordre' => 1],
            ['code' => 'productivite', 'libelle' => 'Productivité / Atteinte des objectifs', 'categorie' => 'resultats', 'poids' => 20, 'ordre' => 2],
            ['code' => 'respect_delais', 'libelle' => 'Respect des délais', 'categorie' => 'resultats', 'poids' => 15, 'ordre' => 3],
            ['code' => 'assiduite_ponctualite', 'libelle' => 'Assiduité et ponctualité', 'categorie' => 'comportement', 'poids' => 15, 'ordre' => 4],
            ['code' => 'esprit_equipe', 'libelle' => "Esprit d'équipe et collaboration", 'categorie' => 'comportement', 'poids' => 10, 'ordre' => 5],
            ['code' => 'initiative_autonomie', 'libelle' => 'Initiative et autonomie', 'categorie' => 'comportement', 'poids' => 10, 'ordre' => 6],
            ['code' => 'communication', 'libelle' => 'Communication', 'categorie' => 'comportement', 'poids' => 5, 'ordre' => 7],
        ];

        foreach ($criteres as $critere) {
            DB::table('evaluation_criteres')->insertOrIgnore(array_merge($critere, [
                'description' => null,
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_details');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('evaluation_criteres');
    }
};
