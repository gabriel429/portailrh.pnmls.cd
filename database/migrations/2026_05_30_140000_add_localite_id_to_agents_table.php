<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agents') || Schema::hasColumn('agents', 'localite_id')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table) {
            $table->foreignId('localite_id')
                ->nullable()
                ->after('province_id')
                ->constrained('localites')
                ->nullOnDelete();
        });

        if (Schema::hasTable('affectations') && Schema::hasColumn('affectations', 'localite_id')) {
            DB::table('agents')
                ->join('affectations', function ($join) {
                    $join->on('agents.id', '=', 'affectations.agent_id')
                        ->where('affectations.actif', true)
                        ->whereNotNull('affectations.localite_id');
                })
                ->whereNull('agents.localite_id')
                ->update(['agents.localite_id' => DB::raw('affectations.localite_id')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('agents') || !Schema::hasColumn('agents', 'localite_id')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('localite_id');
        });
    }
};
