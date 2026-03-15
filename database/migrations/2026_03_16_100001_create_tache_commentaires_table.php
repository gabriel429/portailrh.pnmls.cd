<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tache_commentaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained('taches')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->text('contenu');
            $table->string('ancien_statut')->nullable();
            $table->string('nouveau_statut')->nullable();
            $table->timestamps();

            $table->index('tache_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tache_commentaires');
    }
};
