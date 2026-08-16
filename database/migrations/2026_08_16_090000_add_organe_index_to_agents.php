<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // organe is the primary filter in nearly every dashboard query
        // (ExecutiveDashboardController, RhDashboardController, etc.) but
        // had no dedicated index.
        if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'organe')
            && !$this->indexExists('agents', 'agents_organe_index')
        ) {
            Schema::table('agents', function (Blueprint $table) {
                $table->index('organe');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agents') && $this->indexExists('agents', 'agents_organe_index')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropIndex('agents_organe_index');
            });
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['name'] === $name);
    }
};
