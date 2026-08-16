<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ip_allowlist')) {
            return;
        }

        Schema::create('ip_allowlist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->string('label')->nullable();
            // No real FK: users/agents are MyISAM tables on this database
            // and don't support foreign key constraints.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_allowlist');
    }
};
