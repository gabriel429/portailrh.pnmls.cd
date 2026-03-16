<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Localités : entités de niveau local (SEL — Secrétariat Exécutif Local).
     * Chaque localité appartient à une province et peut accueillir un SEL.
     */
    public function up(): void
    {
        if (Schema::hasTable('localites')) {
            return;
        }

        Schema::create('localites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code de la localité (ex: SEL-KIN-01)');
            $table->string('nom');
            $table->enum('type', [
                'territoire',
                'zone_de_sante',
                'commune',
                'ville',
                'autre',
            ])->default('territoire')->comment('Type administratif local');
            $table->text('description')->nullable();
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localites');
    }
};
