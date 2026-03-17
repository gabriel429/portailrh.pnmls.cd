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
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'postnom')) {
                $table->string('postnom')->nullable()->after('nom');
            }
            if (!Schema::hasColumn('agents', 'organe')) {
                $table->string('organe')->nullable()->after('postnom');
            }
            if (!Schema::hasColumn('agents', 'fonction')) {
                $table->string('fonction')->nullable()->after('poste_actuel');
            }
            if (!Schema::hasColumn('agents', 'grade_etat')) {
                $table->string('grade_etat')->nullable()->after('fonction');
            }
            if (!Schema::hasColumn('agents', 'sexe')) {
                $table->string('sexe', 20)->nullable()->after('grade_etat');
            }
            if (!Schema::hasColumn('agents', 'provenance_matricule')) {
                $table->string('provenance_matricule')->nullable()->after('matricule_etat');
            }
            if (!Schema::hasColumn('agents', 'niveau_etudes')) {
                $table->string('niveau_etudes')->nullable()->after('provenance_matricule');
            }
            if (!Schema::hasColumn('agents', 'annee_engagement_programme')) {
                $table->unsignedSmallInteger('annee_engagement_programme')->nullable()->after('niveau_etudes');
            }
            if (!Schema::hasColumn('agents', 'annee_naissance')) {
                $table->unsignedSmallInteger('annee_naissance')->nullable()->after('annee_engagement_programme');
            }
            if (!Schema::hasColumn('agents', 'email_prive')) {
                $table->string('email_prive')->nullable()->after('email');
            }
            if (!Schema::hasColumn('agents', 'email_professionnel')) {
                $table->string('email_professionnel')->nullable()->after('email_prive');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'postnom',
                'organe',
                'fonction',
                'grade_etat',
                'sexe',
                'provenance_matricule',
                'niveau_etudes',
                'annee_engagement_programme',
                'annee_naissance',
                'email_prive',
                'email_professionnel',
            ]);
        });
    }
};
