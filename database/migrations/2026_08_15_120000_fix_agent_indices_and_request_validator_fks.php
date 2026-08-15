<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const REQUEST_VALIDATOR_COLUMNS = [
        'validated_by_director',
        'validated_by_rh',
        'validated_by_caf',
        'validated_by_aaf',
        'validated_by_sep',
        'validated_by_sen',
    ];

    public function up(): void
    {
        // Fix a dead index: 2026_12_11_add_performance_indices only creates it
        // when a column named 'matricule' exists, but that column has never
        // existed (the real column is matricule_etat) — the condition was
        // always false and this index has never actually been created.
        if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'matricule_etat')
            && !$this->indexExists('agents', 'agents_matricule_etat_index')
        ) {
            Schema::table('agents', function (Blueprint $table) {
                $table->index('matricule_etat');
            });
        }

        // localite_id is filtered heavily by the executive dashboards
        // (ExecutiveDashboardController, PointageController) but had no
        // dedicated index.
        if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'localite_id')
            && !$this->indexExists('agents', 'agents_localite_id_index')
        ) {
            Schema::table('agents', function (Blueprint $table) {
                $table->index('localite_id');
            });
        }

        // The workflow validator columns on requests were added as plain
        // unsignedBigInteger with no referential integrity (unlike agent_id,
        // which is constrained). Null out any orphaned references first so
        // this migration is safe to run regardless of existing data, then
        // constrain them the same way.
        if (Schema::hasTable('requests') && Schema::hasTable('agents')) {
            foreach (self::REQUEST_VALIDATOR_COLUMNS as $column) {
                if (!Schema::hasColumn('requests', $column)) {
                    continue;
                }

                DB::table('requests')
                    ->whereNotNull($column)
                    ->whereNotIn($column, function ($query) {
                        $query->select('id')->from('agents');
                    })
                    ->update([$column => null]);
            }

            Schema::table('requests', function (Blueprint $table) {
                foreach (self::REQUEST_VALIDATOR_COLUMNS as $column) {
                    if (Schema::hasColumn('requests', $column) && !$this->foreignKeyExists('requests', $column)) {
                        $table->foreign($column)->references('id')->on('agents')->nullOnDelete();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('requests')) {
            Schema::table('requests', function (Blueprint $table) {
                foreach (self::REQUEST_VALIDATOR_COLUMNS as $column) {
                    if (Schema::hasColumn('requests', $column) && $this->foreignKeyExists('requests', $column)) {
                        $table->dropForeign([$column]);
                    }
                }
            });
        }

        if (Schema::hasTable('agents')) {
            Schema::table('agents', function (Blueprint $table) {
                if ($this->indexExists('agents', 'agents_matricule_etat_index')) {
                    $table->dropIndex('agents_matricule_etat_index');
                }
                if ($this->indexExists('agents', 'agents_localite_id_index')) {
                    $table->dropIndex('agents_localite_id_index');
                }
            });
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['name'] === $name);
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $fk) => in_array($column, $fk['columns'], true));
    }
};
