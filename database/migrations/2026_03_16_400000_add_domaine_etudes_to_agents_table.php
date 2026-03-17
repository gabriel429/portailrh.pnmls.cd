<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'domaine_etudes')) {
                $table->string('domaine_etudes')->nullable()->after('niveau_etudes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('domaine_etudes');
        });
    }
};
