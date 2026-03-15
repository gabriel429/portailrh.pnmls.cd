<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('createur_id')->constrained('agents')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('priorite', ['normale', 'haute', 'urgente'])->default('normale');
            $table->enum('statut', ['nouvelle', 'en_cours', 'terminee'])->default('nouvelle');
            $table->date('date_echeance')->nullable();
            $table->timestamps();

            $table->index('createur_id');
            $table->index('agent_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
