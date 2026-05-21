<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $this->renameOrMergeRole('Chef Section RH', 'Section ressources humaines');
        $this->renameOrMergeRole('Chef Section Nouvelle Technologie', 'Section Nouvelle Technologie');
        $this->renameOrMergeRole('Chef de Section Nouvelle Technologie', 'Section Nouvelle Technologie');
    }

    public function down(): void
    {
        $this->renameOrMergeRole('Section ressources humaines', 'Chef Section RH', 'Chef de Section RH');
        $this->renameOrMergeRole('Section Nouvelle Technologie', 'Chef Section Nouvelle Technologie', 'Chef de Section Nouvelle Technologie');
    }

    private function renameOrMergeRole(string $from, string $to, ?string $description = null): void
    {
        $fromRole = DB::table('roles')->where('nom_role', $from)->first();

        if (!$fromRole) {
            return;
        }

        $toRole = DB::table('roles')->where('nom_role', $to)->first();

        if (!$toRole) {
            DB::table('roles')
                ->where('id', $fromRole->id)
                ->update([
                    'nom_role' => $to,
                    'description' => $description ?? $to,
                ]);

            return;
        }

        DB::transaction(function () use ($fromRole, $toRole) {
            foreach (['agents', 'users'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'role_id')) {
                    DB::table($table)
                        ->where('role_id', $fromRole->id)
                        ->update(['role_id' => $toRole->id]);
                }
            }

            if (Schema::hasTable('role_permission')) {
                $now = now();
                $permissionIds = DB::table('role_permission')
                    ->where('role_id', $fromRole->id)
                    ->pluck('permission_id');

                foreach ($permissionIds as $permissionId) {
                    DB::table('role_permission')->insertOrIgnore([
                        'role_id' => $toRole->id,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('role_permission')->where('role_id', $fromRole->id)->delete();
            }

            DB::table('roles')->where('id', $fromRole->id)->delete();
        });
    }
};
