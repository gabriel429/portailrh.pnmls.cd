<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_operations', function (Blueprint $table) {
            $table->id();
            // users est en MyISAM: ->constrained() n'y crée pas de contrainte réelle.
            $table->unsignedBigInteger('user_id');
            $table->uuid('client_operation_id');
            $table->string('entity', 100);
            $table->string('operation', 30);
            $table->string('status', 30)->default('accepted');
            $table->json('result')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'client_operation_id']);
            $table->index(['user_id', 'entity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_operations');
    }
};
