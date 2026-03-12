<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->char('categorie', 1)->comment('Catégorie : A, B ou C');
            $table->string('nom_categorie')->comment('Libellé de la catégorie (ex: Haut cadre)');
            $table->unsignedTinyInteger('ordre')->comment('Ordre hiérarchique global (1 à 11)');
            $table->string('libelle')->comment('Intitulé du grade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
