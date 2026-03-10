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
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->date('date_pointage');
            $table->time('heure_entree')->nullable();
            $table->time('heure_sortie')->nullable();
            $table->integer('heures_travaillees')->nullable()->comment('Heures travaillées en décimal');
            $table->text('observations')->nullable();
            $table->unique(['agent_id', 'date_pointage']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pointages');
    }
};
