<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            if (!Schema::hasColumn('taches', 'validation_responsable_id')) {
                $table->foreignId('validation_responsable_id')
                    ->nullable()
                    ->after('validation_responsable_role')
                    ->constrained('agents')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('taches', 'assignment_group')) {
                $table->uuid('assignment_group')->nullable()->after('agent_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            if (Schema::hasColumn('taches', 'validation_responsable_id')) {
                $table->dropConstrainedForeignId('validation_responsable_id');
            }

            if (Schema::hasColumn('taches', 'assignment_group')) {
                $table->dropColumn('assignment_group');
            }
        });
    }
};
