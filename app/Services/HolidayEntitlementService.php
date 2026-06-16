<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentHolidayEntitlement;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use Illuminate\Support\Collection;

class HolidayEntitlementService
{
    private const DEFAULT_ANNUAL_DAYS = 30;

    public function resolvePlanning(Agent $agent, int $year): ?HolidayPlanning
    {
        if ($agent->province_id) {
            return HolidayPlanning::where('annee', $year)
                ->where('type_structure', 'sep')
                ->where('structure_id', $agent->province_id)
                ->first();
        }

        if ($agent->departement_id) {
            return HolidayPlanning::where('annee', $year)
                ->where('type_structure', 'department')
                ->where('structure_id', $agent->departement_id)
                ->first();
        }

        return HolidayPlanning::where('annee', $year)
            ->where('type_structure', 'sen')
            ->where('structure_id', 1)
            ->first();
    }

    public function quotaForAgent(Agent $agent, int $year): array
    {
        $entitlement = AgentHolidayEntitlement::where('agent_id', $agent->id)
            ->where('annee', $year)
            ->first();

        $planning = $this->resolvePlanning($agent, $year);
        $joursUtilises = $this->annualDaysByStatus($agent->id, $year, 'approuve');
        $joursEnAttente = $this->annualDaysByStatus($agent->id, $year, 'en_attente');

        return $this->formatQuota($agent, $year, $entitlement, $planning, $joursUtilises, $joursEnAttente);
    }

    public function enrichAgents(Collection $agents, int $year): Collection
    {
        $agentIds = $agents->pluck('id')->filter()->values();

        if ($agentIds->isEmpty()) {
            return $agents;
        }

        $entitlements = AgentHolidayEntitlement::whereIn('agent_id', $agentIds)
            ->where('annee', $year)
            ->get()
            ->keyBy('agent_id');

        $usedDays = $this->annualDaysForAgents($agentIds, $year, 'approuve');
        $pendingDays = $this->annualDaysForAgents($agentIds, $year, 'en_attente');
        $planningMaps = $this->planningMaps($year);

        return $agents->map(function (Agent $agent) use ($year, $entitlements, $usedDays, $pendingDays, $planningMaps) {
            $planning = $this->planningForAgentFromMaps($agent, $planningMaps);

            return [
                'id' => $agent->id,
                'nom_complet' => trim(($agent->nom ?? '') . ' ' . ($agent->postnom ?? '') . ' ' . ($agent->prenom ?? '')),
                'fonction' => $agent->fonction,
                'province_id' => $agent->province_id,
                'departement_id' => $agent->departement_id,
                'holiday_entitlement' => $this->formatQuota(
                    $agent,
                    $year,
                    $entitlements->get($agent->id),
                    $planning,
                    (int) ($usedDays[$agent->id] ?? 0),
                    (int) ($pendingDays[$agent->id] ?? 0),
                ),
            ];
        });
    }

    private function annualDaysByStatus(int $agentId, int $year, string $status): int
    {
        return (int) Holiday::where('agent_id', $agentId)
            ->whereYear('date_debut', $year)
            ->where('type_conge', 'annuel')
            ->where('statut_demande', $status)
            ->sum('nombre_jours');
    }

    private function annualDaysForAgents(Collection $agentIds, int $year, string $status): Collection
    {
        return Holiday::whereIn('agent_id', $agentIds)
            ->whereYear('date_debut', $year)
            ->where('type_conge', 'annuel')
            ->where('statut_demande', $status)
            ->selectRaw('agent_id, COALESCE(SUM(nombre_jours), 0) as total_jours')
            ->groupBy('agent_id')
            ->pluck('total_jours', 'agent_id');
    }

    private function planningMaps(int $year): array
    {
        $plannings = HolidayPlanning::where('annee', $year)->get();

        return [
            'sep' => $plannings->where('type_structure', 'sep')->keyBy('structure_id'),
            'department' => $plannings->where('type_structure', 'department')->keyBy('structure_id'),
            'sen' => $plannings
                ->where('type_structure', 'sen')
                ->where('structure_id', 1)
                ->first(),
        ];
    }

    private function planningForAgentFromMaps(Agent $agent, array $planningMaps): ?HolidayPlanning
    {
        if ($agent->province_id) {
            return $planningMaps['sep']->get($agent->province_id);
        }

        if ($agent->departement_id) {
            return $planningMaps['department']->get($agent->departement_id);
        }

        return $planningMaps['sen'];
    }

    private function formatQuota(
        Agent $agent,
        int $year,
        ?AgentHolidayEntitlement $entitlement,
        ?HolidayPlanning $planning,
        int $joursUtilises,
        int $joursEnAttente
    ): array {
        $joursAutorises = $entitlement?->jours_autorises
            ?? $planning?->jours_conge_totaux
            ?? self::DEFAULT_ANNUAL_DAYS;

        $source = $entitlement ? 'individual' : ($planning ? 'planning' : 'default');

        return [
            'agent_id' => $agent->id,
            'annee' => $year,
            'entitlement_id' => $entitlement?->id,
            'jours_autorises' => (int) $joursAutorises,
            'jours_utilises' => $joursUtilises,
            'jours_en_attente' => $joursEnAttente,
            'jours_restants' => (int) $joursAutorises - $joursUtilises,
            'source' => $source,
            'source_label' => match ($source) {
                'individual' => 'Spécifique',
                'planning' => 'Planning',
                default => 'Défaut',
            },
            'planning_id' => $planning?->id,
            'planning_nom' => $planning?->nom_structure,
            'notes' => $entitlement?->notes,
        ];
    }
}
