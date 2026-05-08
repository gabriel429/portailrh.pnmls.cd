<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['agents', 'affectations', 'fonctions'] as $table) {
            if (!Schema::hasTable($table)) {
                return;
            }
        }

        $defaultOrganes = [
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariat Exécutif Local',
        ];

        $organeNames = Schema::hasTable('organes')
            ? DB::table('organes')->pluck('nom', 'code')->all()
            : [];

        $agentIds = DB::table('affectations')
            ->where('actif', true)
            ->distinct()
            ->pluck('agent_id');

        foreach ($agentIds as $agentId) {
            $active = DB::table('affectations')
                ->leftJoin('fonctions', 'fonctions.id', '=', 'affectations.fonction_id')
                ->where('affectations.agent_id', $agentId)
                ->where('affectations.actif', true)
                ->orderByDesc('affectations.date_debut')
                ->orderByDesc('affectations.id')
                ->select([
                    'affectations.niveau_administratif',
                    'affectations.department_id',
                    'affectations.province_id',
                    'fonctions.nom as fonction_nom',
                ])
                ->first();

            if (!$active) {
                continue;
            }

            DB::table('agents')
                ->where('id', $agentId)
                ->update([
                    'organe' => $organeNames[$active->niveau_administratif]
                        ?? ($defaultOrganes[$active->niveau_administratif] ?? null),
                    'fonction' => $active->fonction_nom,
                    'poste_actuel' => $active->fonction_nom,
                    'departement_id' => $active->department_id,
                    'province_id' => $active->province_id,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Data harmonization only; no rollback.
    }
};
