<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('requests', 'workflow_level')) {
            DB::statement('ALTER TABLE requests MODIFY workflow_level VARCHAR(80) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('requests', 'workflow_level')) {
            DB::statement('ALTER TABLE requests MODIFY workflow_level VARCHAR(30) NULL');
        }
    }
};
