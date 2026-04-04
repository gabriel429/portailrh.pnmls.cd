<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activite_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('activite_plans', 'objectif')) {
                $table->text('objectif')->nullable()->after('titre');
            }

            if (!Schema::hasColumn('activite_plans', 'resultat_attendu')) {
                $table->text('resultat_attendu')->nullable()->after('description');
            }

            if (!Schema::hasColumn('activite_plans', 'validation_niveau')) {
                $table->enum('validation_niveau', ['direction', 'coordination_nationale', 'coordination_provinciale'])
                    ->nullable()
                    ->after('niveau_administratif');
            }
        });

        Schema::table('taches', function (Blueprint $table) {
            if (!Schema::hasColumn('taches', 'source_type')) {
                $table->enum('source_type', ['pta', 'hors_pta'])
                    ->default('hors_pta')
                    ->after('description');
            }

            if (!Schema::hasColumn('taches', 'source_emetteur')) {
                $table->enum('source_emetteur', ['directeur', 'assistant_departement', 'sen', 'autre'])
                    ->default('autre')
                    ->after('source_type');
            }

            if (!Schema::hasColumn('taches', 'activite_plan_id')) {
                $table->foreignId('activite_plan_id')
                    ->nullable()
                    ->after('source_emetteur')
                    ->constrained('activite_plans')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('taches', 'date_tache')) {
                $table->date('date_tache')->nullable()->after('date_echeance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            if (Schema::hasColumn('taches', 'activite_plan_id')) {
                $table->dropConstrainedForeignId('activite_plan_id');
            }

            if (Schema::hasColumn('taches', 'source_emetteur')) {
                $table->dropColumn('source_emetteur');
            }

            if (Schema::hasColumn('taches', 'source_type')) {
                $table->dropColumn('source_type');
            }

            if (Schema::hasColumn('taches', 'date_tache')) {
                $table->dropColumn('date_tache');
            }
        });

        Schema::table('activite_plans', function (Blueprint $table) {
            if (Schema::hasColumn('activite_plans', 'validation_niveau')) {
                $table->dropColumn('validation_niveau');
            }

            if (Schema::hasColumn('activite_plans', 'resultat_attendu')) {
                $table->dropColumn('resultat_attendu');
            }

            if (Schema::hasColumn('activite_plans', 'objectif')) {
                $table->dropColumn('objectif');
            }
        });
    }
};