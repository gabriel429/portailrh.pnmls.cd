<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activite_plans')) {
            return;
        }

        Schema::table('activite_plans', function (Blueprint $table) {
            $table->text('titre')->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('activite_plans')) {
            return;
        }

        Schema::table('activite_plans', function (Blueprint $table) {
            $table->string('titre', 255)->change();
        });
    }
};