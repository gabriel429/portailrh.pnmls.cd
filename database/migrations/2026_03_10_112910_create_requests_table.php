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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->string('type')->comment('Type de demande (congé, formation, etc)');
            $table->text('description');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['en_attente', 'approuvé', 'rejeté', 'annulé'])->default('en_attente');
            $table->text('remarques')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
