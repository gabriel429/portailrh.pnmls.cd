<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('taches', 'source_type')) {
            DB::statement("ALTER TABLE taches MODIFY source_type ENUM('pta', 'hors_pta', 'agenda', 'formation') DEFAULT 'hors_pta'");
        }

        if (Schema::hasColumn('taches', 'source_emetteur')) {
            DB::statement("ALTER TABLE taches MODIFY source_emetteur ENUM('directeur', 'assistant_departement', 'sen', 'sena', 'sep', 'secom', 'sel', 'aaf_local', 'autre') DEFAULT 'autre'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('taches', 'source_type')) {
            DB::table('taches')->where('source_type', 'agenda')->update(['source_type' => 'hors_pta']);
            DB::statement("ALTER TABLE taches MODIFY source_type ENUM('pta', 'hors_pta', 'formation') DEFAULT 'hors_pta'");
        }

        if (Schema::hasColumn('taches', 'source_emetteur')) {
            DB::table('taches')->where('source_emetteur', 'sena')->update(['source_emetteur' => 'sen']);
            DB::statement("ALTER TABLE taches MODIFY source_emetteur ENUM('directeur', 'assistant_departement', 'sen', 'sep', 'secom', 'sel', 'aaf_local', 'autre') DEFAULT 'autre'");
        }
    }
};
