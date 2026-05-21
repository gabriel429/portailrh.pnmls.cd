<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('requests', 'statut')) {
            DB::statement("ALTER TABLE requests MODIFY statut ENUM('en_attente', 'approuvé', 'rejeté', 'annulé', 'expiré') NOT NULL DEFAULT 'en_attente'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('requests', 'statut')) {
            DB::statement("ALTER TABLE requests MODIFY statut ENUM('en_attente', 'approuvé', 'rejeté', 'annulé') NOT NULL DEFAULT 'en_attente'");
        }
    }
};
