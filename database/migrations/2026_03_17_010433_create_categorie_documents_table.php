<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categorie_documents')) {
            return;
        }
        Schema::create('categorie_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('icone')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Seed default categories
        DB::table('categorie_documents')->insert([
            ['nom' => 'Règlement', 'icone' => 'fa-gavel', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Procédure', 'icone' => 'fa-list-check', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Formulaire', 'icone' => 'fa-file-lines', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Note de service', 'icone' => 'fa-bullhorn', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Modèle', 'icone' => 'fa-copy', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Rapport', 'icone' => 'fa-chart-bar', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Guide', 'icone' => 'fa-book', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Autre', 'icone' => 'fa-folder', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie_documents');
    }
};
