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
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->string('type')->comment('Type de signalement');
            $table->text('description');
            $table->text('observations')->nullable();
            $table->enum('severite', ['basse', 'moyenne', 'haute'])->default('moyenne');
            $table->enum('statut', ['ouvert', 'en_cours', 'résolu', 'fermé'])->default('ouvert');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
