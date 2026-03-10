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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->string('type')->comment('Type de document (identité, diplôme, etc)');
            $table->string('fichier')->comment('Chemin du fichier');
            $table->text('description')->nullable();
            $table->date('date_expiration')->nullable();
            $table->enum('statut', ['valide', 'expiré', 'rejeté'])->default('valide');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
