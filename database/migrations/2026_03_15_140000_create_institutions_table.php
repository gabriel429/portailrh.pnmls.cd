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
        // Table: Institution Categories (Parent)
        Schema::create('institution_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->tinyInteger('ordre')->default(1); // 1-11 for ordering
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('ordre');
        });

        // Table: Institutions (Child)
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->foreignId('institution_categorie_id')
                ->constrained('institution_categories')
                ->onDelete('cascade');
            $table->tinyInteger('ordre')->default(1);
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index('institution_categorie_id');
            $table->index('actif');
            $table->index('ordre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('institution_categories');
    }
};
