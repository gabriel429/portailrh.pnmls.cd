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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('matricule_pnmls')->unique()->comment('Matricule PNMLS (PNM-XXXXXX)');
            $table->string('matricule_etat')->nullable()->unique()->comment('Matricule État');
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable()->comment('Mot de passe si utilisateur');
            $table->string('photo')->nullable()->comment('Chemin de la photo de profil');
            $table->date('date_naissance');
            $table->string('lieu_naissance');
            $table->enum('situation_familiale', ['célibataire', 'marié', 'divorcé', 'veuf'])->default('célibataire');
            $table->integer('nombre_enfants')->default(0);
            $table->string('telephone')->nullable();
            $table->text('adresse')->nullable();
            $table->string('poste_actuel')->nullable();
            $table->foreignId('departement_id')->nullable()->constrained();
            $table->foreignId('province_id')->nullable()->constrained();
            $table->foreignId('role_id')->nullable()->constrained();
            $table->date('date_embauche');
            $table->enum('statut', ['actif', 'suspendu', 'ancien'])->default('actif');
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
