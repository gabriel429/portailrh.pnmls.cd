<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonctions', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique()->comment('Nom de la fonction / poste');
            $table->string('niveau')->nullable()->comment('département|section|cellule|transversal');
            $table->text('description')->nullable();
            $table->boolean('est_chef')->default(false)->comment('Poste de responsable unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonctions');
    }
};
