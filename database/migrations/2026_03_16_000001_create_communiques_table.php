<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communiques')) {
            return;
        }

        Schema::create('communiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auteur_id')->constrained('users')->onDelete('cascade');
            $table->string('titre');
            $table->text('contenu');
            $table->enum('urgence', ['normal', 'important', 'urgent'])->default('normal');
            $table->string('signataire')->nullable();
            $table->date('date_expiration')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communiques');
    }
};
