<?php

namespace App\Services;

use App\Models\ActivitePlan;
use App\Models\Agent;
use App\Models\CongeConflit;
use App\Models\CongeRegleDepartement;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CongeConflictService
{
    /**
     * Check if agent has PTA activities overlapping with the given leave period.
     */
    public static function checkPtaConflict(int $agentId, Carbon $dateDebut, Carbon $dateFin): Collection
    {
        $agent = Agent::find($agentId);
        if (!$agent) {
            return collect();
        }

        $query = ActivitePlan::query()
            ->where(function ($q) use ($dateDebut, $dateFin) {
                $q->where(function ($sub) use ($dateDebut, $dateFin) {
                    $sub->where('date_debut', '<=', $dateFin)
                        ->where('date_fin', '>=', $dateDebut);
                });
            })
            ->whereIn('statut', ['planifiee', 'en_cours']);

        // Scope to agent's department or tasks assigned to agent
        if ($agent->departement_id) {
            $query->where(function ($q) use ($agent) {
                $q->where('departement_id', $agent->departement_id)
                    ->orWhereHas('taches', fn ($t) => $t->where('agent_id', $agent->id));
            });
        } else {
            $query->whereHas('taches', fn ($t) => $t->where('agent_id', $agent->id));
        }

        return $query->get();
    }

    /**
     * Check if department absence quota would be exceeded.
     * Returns warning message or null.
     */
    public static function checkDepartmentQuota(int $departementId, Carbon $dateDebut, Carbon $dateFin): ?string
    {
        $regle = CongeRegleDepartement::where('departement_id', $departementId)->first();

        if (!$regle || !$regle->taux_absent_max) {
            return null;
        }

        $totalAgents = Agent::where('departement_id', $departementId)
            ->where('statut', 'actif')
            ->count();

        if ($totalAgents === 0) {
            return null;
        }

        // Count agents on approved leave during this period
        $agentsEnConge = Holiday::where('statut_demande', 'approuve')
            ->whereHas('agent', fn ($q) => $q->where('departement_id', $departementId))
            ->where(function ($q) use ($dateDebut, $dateFin) {
                $q->where('date_debut', '<=', $dateFin)
                    ->where('date_fin', '>=', $dateDebut);
            })
            ->distinct('agent_id')
            ->count('agent_id');

        // +1 for the new request
        $projected = $agentsEnConge + 1;
        $tauxProjecte = ($projected / $totalAgents) * 100;

        if ($tauxProjecte > (float) $regle->taux_absent_max) {
            return "Le département dépasserait son quota d'absence ({$tauxProjecte}% > {$regle->taux_absent_max}%). "
                . "$projected/$totalAgents agents absents.";
        }

        return null;
    }

    /**
     * Create a conflict record.
     */
    public static function createConflitRecord(int $holidayId, int $activitePlanId, string $type, string $description): CongeConflit
    {
        return CongeConflit::create([
            'holiday_id' => $holidayId,
            'activite_plan_id' => $activitePlanId,
            'type_conflit' => $type,
            'description' => $description,
            'resolue' => false,
        ]);
    }
}
