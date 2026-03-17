<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications_portail')) {
            return;
        }

        Schema::table('notifications_portail', function (Blueprint $table) {
            // Compound index for fast unread count queries
            if (!Schema::hasIndex('notifications_portail', 'notif_user_lu_index')) {
                $table->index(['user_id', 'lu', 'created_at'], 'notif_user_lu_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications_portail', function (Blueprint $table) {
            $table->dropIndex('notif_user_lu_index');
        });
    }
};
