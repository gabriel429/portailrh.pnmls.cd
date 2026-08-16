<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentStatus;
use App\Models\Pointage;
use App\Models\Tache;
use Carbon\Carbon;

class PerformanceScoreService
{
    // Poids de la moitié "auto" dans le score global — le jugement du
    // responsable (score manuel) pèse plus lourd que les métriques brutes.
    private const AUTO_WEIGHT = 0.4;
    private const MANUAL_WEIGHT = 0.6;

    // À l'intérieur de la moitié auto, complétion des tâches et assiduité
    // comptent à parts égales.
    private const TASK_COMPLETION_WEIGHT = 0.5;
    private const ATTENDANCE_WEIGHT = 0.5;

    /**
     * @return array{taux_completion_taches: float, taux_assiduite: float, score_auto: float}
     */
    public function computeAutoScore(Agent $agent, int $annee, int $trimestre = 0): array
    {
        [$start, $end] = $this->periodBounds($annee, $trimestre);

        $tauxCompletion = $this->taskCompletionRate($agent, $start, $end);
        $tauxAssiduite = $this->attendanceRate($agent, $start, $end);

        $scoreAuto = round(
            $tauxCompletion * self::TASK_COMPLETION_WEIGHT + $tauxAssiduite * self::ATTENDANCE_WEIGHT,
            2
        );

        return [
            'taux_completion_taches' => $tauxCompletion,
            'taux_assiduite' => $tauxAssiduite,
            'score_auto' => $scoreAuto,
        ];
    }

    public function combine(?float $scoreAuto, ?float $scoreManuel): ?float
    {
        if ($scoreAuto === null && $scoreManuel === null) {
            return null;
        }

        return round(
            ($scoreAuto ?? 0) * self::AUTO_WEIGHT + ($scoreManuel ?? 0) * self::MANUAL_WEIGHT,
            2
        );
    }

    private function taskCompletionRate(Agent $agent, Carbon $start, Carbon $end): float
    {
        $query = Tache::where('agent_id', $agent->id)
            ->whereBetween('created_at', [$start, $end]);

        $total = (clone $query)->count();
        if ($total === 0) {
            return 0.0;
        }

        $done = (clone $query)->where('statut', 'terminee')->count();

        return round(($done / $total) * 100, 2);
    }

    private function attendanceRate(Agent $agent, Carbon $start, Carbon $end): float
    {
        $expectedDays = $this->expectedWorkingDays($agent, $start, $end);
        if ($expectedDays === 0) {
            return 0.0;
        }

        $presentDays = Pointage::where('agent_id', $agent->id)
            ->whereBetween('date_pointage', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('heure_entree')
            ->distinct('date_pointage')
            ->count('date_pointage');

        return round(min(1, $presentDays / $expectedDays) * 100, 2);
    }

    /**
     * Jours ouvrés (lun-ven) de la période, moins ceux couverts par un
     * AgentStatus non-"disponible" (congé, mission, suspension, formation) —
     * l'agent était excusé de pointer ces jours-là.
     */
    private function expectedWorkingDays(Agent $agent, Carbon $start, Carbon $end): int
    {
        $excusedRanges = AgentStatus::where('agent_id', $agent->id)
            ->where('statut', '!=', 'disponible')
            ->where(function ($q) use ($start, $end) {
                $q->where('date_debut', '<=', $end)
                    ->where(function ($sub) use ($start) {
                        $sub->whereNull('date_fin')->orWhere('date_fin', '>=', $start);
                    });
            })
            ->get(['date_debut', 'date_fin']);

        $days = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday() && !$this->isExcused($cursor, $excusedRanges)) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function isExcused(Carbon $date, $excusedRanges): bool
    {
        foreach ($excusedRanges as $range) {
            $debut = Carbon::parse($range->date_debut);
            $fin = $range->date_fin ? Carbon::parse($range->date_fin) : null;

            if ($date->gte($debut) && (!$fin || $date->lte($fin))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodBounds(int $annee, int $trimestre): array
    {
        if ($trimestre >= 1 && $trimestre <= 4) {
            $start = Carbon::create($annee, ($trimestre - 1) * 3 + 1, 1)->startOfMonth();
            $end = $start->copy()->addMonths(2)->endOfMonth();
        } else {
            $start = Carbon::create($annee, 1, 1)->startOfYear();
            $end = Carbon::create($annee, 12, 31)->endOfYear();
        }

        $now = Carbon::now();
        if ($end->gt($now)) {
            $end = $now->copy()->endOfDay();
        }

        return [$start, $end];
    }
}
