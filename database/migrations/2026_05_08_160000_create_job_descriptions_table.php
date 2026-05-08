<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_descriptions')) {
            return;
        }

        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fonction_id')->constrained('fonctions')->cascadeOnDelete();
            $table->string('titre');
            $table->text('mission_principale')->nullable();
            $table->longText('responsabilites_principales')->nullable();
            $table->longText('taches_specifiques')->nullable();
            $table->longText('competences_attendues')->nullable();
            $table->string('service_section_departement')->nullable();
            $table->boolean('actif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fonction_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_descriptions');
    }
};
