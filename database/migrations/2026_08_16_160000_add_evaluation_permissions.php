<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $newPermissions = [
            ['nom' => 'Voir tableau de bord performance', 'code' => 'performance.dashboard', 'description' => 'Voir le tableau de bord de performance globale des agents'],
            ['nom' => 'Créer évaluation', 'code' => 'evaluation.create', 'description' => "Créer une évaluation de performance pour un agent"],
            ['nom' => 'Modifier évaluation', 'code' => 'evaluation.update', 'description' => 'Modifier une évaluation en brouillon'],
            ['nom' => 'Soumettre évaluation', 'code' => 'evaluation.submit', 'description' => 'Soumettre une évaluation pour validation'],
            ['nom' => 'Valider évaluation', 'code' => 'evaluation.validate', 'description' => 'Valider une évaluation soumise'],
            ['nom' => 'Rejeter évaluation', 'code' => 'evaluation.reject', 'description' => 'Rejeter une évaluation soumise'],
        ];

        foreach ($newPermissions as $perm) {
            DB::table('permissions')->insertOrIgnore([
                'nom' => $perm['nom'],
                'code' => $perm['code'],
                'description' => $perm['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $matrix = [
            'SEN' => ['performance.dashboard', 'evaluation.create', 'evaluation.update', 'evaluation.submit', 'evaluation.validate', 'evaluation.reject'],
            'RH National' => ['performance.dashboard', 'evaluation.create', 'evaluation.update', 'evaluation.submit', 'evaluation.validate', 'evaluation.reject'],
            'Directeur' => ['evaluation.create', 'evaluation.update', 'evaluation.submit'],
            'SEP' => ['evaluation.create', 'evaluation.update', 'evaluation.submit'],
            'RH Provincial' => ['evaluation.create', 'evaluation.update', 'evaluation.submit'],
            'Section ressources humaines' => ['evaluation.create', 'evaluation.update', 'evaluation.submit'],
        ];

        $roleIds = DB::table('roles')->pluck('id', 'nom_role');
        $permIds = DB::table('permissions')->pluck('id', 'code');

        $pivotRows = [];
        foreach ($matrix as $roleName => $permCodes) {
            $roleId = $roleIds[$roleName] ?? null;
            if (!$roleId) {
                continue;
            }

            foreach ($permCodes as $code) {
                $permId = $permIds[$code] ?? null;
                if (!$permId) {
                    continue;
                }

                $pivotRows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($pivotRows, 100) as $chunk) {
            DB::table('role_permission')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        $codes = [
            'performance.dashboard',
            'evaluation.create',
            'evaluation.update',
            'evaluation.submit',
            'evaluation.validate',
            'evaluation.reject',
        ];

        $permIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};
