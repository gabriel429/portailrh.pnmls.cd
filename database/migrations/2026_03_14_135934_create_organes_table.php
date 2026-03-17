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
        if (Schema::hasTable('organes')) {
            return;
        }
        Schema::create('organes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();           // SEN, SEP, SEL
            $table->string('nom');                           // Secrétariat Exécutif National
            $table->string('sigle', 30)->nullable();
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organes');
    }
};
