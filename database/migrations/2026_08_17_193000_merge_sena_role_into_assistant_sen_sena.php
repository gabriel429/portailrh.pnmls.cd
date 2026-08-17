<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_ROLE = 'SENA';
    private const NEW_ROLE = 'Assistant SEN/SENA';

    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $oldRole = DB::table('roles')->where('nom_role', self::OLD_ROLE)->first();
        $newRole = DB::table('roles')->where('nom_role', self::NEW_ROLE)->first();
        $description = 'Assistant du Secrétariat Exécutif National / SENA';

        if (!$newRole && $oldRole) {
            DB::table('roles')
                ->where('id', $oldRole->id)
                ->update([
                    'nom_role' => self::NEW_ROLE,
                    'description' => $description,
                    'updated_at' => now(),
                ]);

            return;
        }

        if (!$newRole) {
            $newRoleId = DB::table('roles')->insertGetId([
                'nom_role' => self::NEW_ROLE,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $newRoleId = (int) $newRole->id;
            DB::table('roles')
                ->where('id', $newRoleId)
                ->update([
                    'description' => $description,
                    'updated_at' => now(),
                ]);
        }

        if (!$oldRole || (int) $oldRole->id === $newRoleId) {
            return;
        }

        $this->moveRoleReferences((int) $oldRole->id, $newRoleId);

        DB::table('roles')->where('id', $oldRole->id)->delete();
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $newRole = DB::table('roles')->where('nom_role', self::NEW_ROLE)->first();
        if (!$newRole) {
            return;
        }

        $oldRole = DB::table('roles')->where('nom_role', self::OLD_ROLE)->first();
        $description = 'Secrétaire Exécutif National Adjoint';

        if (!$oldRole) {
            DB::table('roles')
                ->where('id', $newRole->id)
                ->update([
                    'nom_role' => self::OLD_ROLE,
                    'description' => $description,
                    'updated_at' => now(),
                ]);

            return;
        }

        $this->moveRoleReferences((int) $newRole->id, (int) $oldRole->id);

        DB::table('roles')->where('id', $newRole->id)->delete();
    }

    private function moveRoleReferences(int $fromRoleId, int $toRoleId): void
    {
        foreach (['users', 'agents'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'role_id')) {
                DB::table($table)
                    ->where('role_id', $fromRoleId)
                    ->update(['role_id' => $toRoleId]);
            }
        }

        if (!Schema::hasTable('role_permission')) {
            return;
        }

        $permissionIds = DB::table('role_permission')
            ->where('role_id', $fromRoleId)
            ->pluck('permission_id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permission')->insertOrIgnore([
                'role_id' => $toRoleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('role_permission')->where('role_id', $fromRoleId)->delete();
    }
};
