<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activite_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('createur_id')->constrained('agents')->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('niveau_administratif', ['SEN', 'SEP', 'SEL']);
            $table->foreignId('departement_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('set null');
            $table->foreignId('localite_id')->nullable()->constrained('localites')->onDelete('set null');
            $table->unsignedSmallInteger('annee');
            $table->enum('trimestre', ['T1', 'T2', 'T3', 'T4'])->nullable();
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee'])->default('planifiee');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->unsignedTinyInteger('pourcentage')->default(0);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index('niveau_administratif');
            $table->index('annee');
            $table->index('statut');
            $table->index('departement_id');
            $table->index('province_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activite_plans');
    }
};
