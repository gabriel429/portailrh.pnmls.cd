<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communique_attachments')) {
            return;
        }

        Schema::create('communique_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('communique_id')->constrained('communiques')->onDelete('cascade');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communique_attachments');
    }
};
