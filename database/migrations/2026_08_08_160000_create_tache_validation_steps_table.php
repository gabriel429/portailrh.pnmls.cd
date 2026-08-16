<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tache_validation_steps', function (Blueprint $table) {
            $table->id();
            // taches/agents sont en MyISAM: ->constrained() n'y crée pas de
            // contrainte réelle et échoue même en ALTER TABLE séparée (err. 1824).
            $table->unsignedBigInteger('tache_id');
            $table->unsignedSmallInteger('step_order');
            $table->string('step_code', 60);
            $table->unsignedBigInteger('validator_agent_id');
            $table->string('structure_type', 30);
            $table->unsignedBigInteger('structure_id')->nullable();
            $table->enum('statut', ['pending', 'active', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->text('commentaire')->nullable();
            $table->json('resolution_metadata')->nullable();
            $table->timestamps();

            $table->unique(['tache_id', 'step_order']);
            $table->index(['validator_agent_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tache_validation_steps');
    }
};