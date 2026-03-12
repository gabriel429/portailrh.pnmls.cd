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
            $table->string('postnom')->nullable()->after('nom');
            $table->string('organe')->nullable()->after('postnom');
            $table->string('fonction')->nullable()->after('poste_actuel');
            $table->string('grade_etat')->nullable()->after('fonction');
            $table->string('sexe', 20)->nullable()->after('grade_etat');
            $table->string('provenance_matricule')->nullable()->after('matricule_etat');
            $table->string('niveau_etudes')->nullable()->after('provenance_matricule');
            $table->unsignedSmallInteger('annee_engagement_programme')->nullable()->after('niveau_etudes');
            $table->unsignedSmallInteger('annee_naissance')->nullable()->after('annee_engagement_programme');
            $table->string('email_prive')->nullable()->after('email');
            $table->string('email_professionnel')->nullable()->after('email_prive');
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
