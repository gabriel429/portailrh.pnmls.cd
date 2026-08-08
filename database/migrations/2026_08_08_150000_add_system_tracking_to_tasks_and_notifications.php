<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->string('system_key', 191)->nullable()->unique()->after('assignment_group');
        });

        Schema::table('notifications_portail', function (Blueprint $table) {
            $table->string('context_key', 191)->nullable()->index()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('notifications_portail', function (Blueprint $table) {
            $table->dropIndex(['context_key']);
            $table->dropColumn('context_key');
        });

        Schema::table('taches', function (Blueprint $table) {
            $table->dropUnique(['system_key']);
            $table->dropColumn('system_key');
        });
    }
};