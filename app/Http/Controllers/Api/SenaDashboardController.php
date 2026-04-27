<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\ActivitePlan;
use App\Models\Communique;
use App\Models\Pointage;
use App\Models\Request as RequestModel;
use App\Models\Signalement;
use App\Models\Tache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SenaDashboardController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $now          = Carbon::now();
        $nextWeek     = $now->copy()->addDays(7);
        $currentYear  = $now->year;

        $senOrgane    = 'Secrétariat Exécutif National';

        // ─── Agents SEN (organe national) ─────────────────────
        $senAgentIds = Agent::where('organe', $senOrgane)->pluck('id');
        $senActifs   = Agent::where('organe', $senOrgane)->actifs()->count();

        // ─── Mes tâches (agent connecté) ──────────────────────
        $myAgentId = $user->agent?->id;
        $myTasks = $myAgentId
            ? Tache::where('agent_id', $myAgentId)
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get(['id', 'agent_id', 'titre', 'statut', 'pourcentage', 'date_echeance', 'priorite', 'updated_at'])
            : collect();

        // ─── Agenda : tâches SEN avec échéance dans 7 jours ──
        $upcomingDeadlines = Tache::whereIn('agent_id', $senAgentIds)
            ->whereNotIn('statut', ['terminee'])
            ->whereNotNull('date_echeance')
            ->whereBetween('date_echeance', [$now->toDateString(), $nextWeek->toDateString()])
            ->with('agent:id,nom,prenom')
            ->orderBy('date_echeance')
            ->limit(10)
            ->get(['id', 'agent_id', 'titre', 'statut', 'pourcentage', 'date_echeance', 'priorite']);

        // ─── Demandes en attente de validation SEN ────────────
        // Demandes dont le statut indique qu'elles attendent la validation SEN
        $pendingRequests = RequestModel::with('agent:id,nom,prenom,photo')
            ->where(function ($q) {
                $q->where('current_step', 'sen')
                  ->orWhere('statut', 'en_attente_sen')
                  ->orWhere(function ($q2) {
                      // Validé par RH et SEP mais pas encore par SEN → en attente SEN
                      $q2->whereNotNull('validated_at_rh')
                         ->whereNull('validated_at_sen')
                         ->where('statut', 'en_attente');
                  });
            })
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['id', 'agent_id', 'type', 'statut', 'current_step', 'created_at',
                   'validated_at_director', 'validated_at_rh', 'validated_at_sep', 'validated_at_sen']);

        // ─── Communiqués récents ───────────────────────────────
        $recentCommuniques = Communique::visibles()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'titre', 'urgence', 'signataire', 'created_at']);

        // ─── Présence du jour (agents SEN) ────────────────────
        $totalActifs   = max($senActifs, 1);
        $todayPresent  = Pointage::byDate($now->toDateString())
            ->whereIn('agent_id', $senAgentIds)
            ->whereNotNull('heure_entree')
            ->distinct('agent_id')
            ->count('agent_id');
        $todayRate = round(($todayPresent / $totalActifs) * 100, 1);

        // Taux mensuel moyen
        $startOfMonth = $now->copy()->startOfMonth();
        $monthlyPointages = Pointage::betweenDates(
            $startOfMonth->toDateString(),
            $now->toDateString()
        )->whereIn('agent_id', $senAgentIds)
         ->whereNotNull('heure_entree')
         ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
         ->groupBy('date_pointage')->get();

        $monthlyRate = $monthlyPointages->isNotEmpty()
            ? round($monthlyPointages->avg(fn ($p) => ($p->present / $totalActifs) * 100), 1)
            : 0;

        // ─── PTA global SEN (année en cours) ──────────────────
        $ptaSen = ActivitePlan::where('niveau_administratif', 'SEN')
            ->where('annee', $currentYear)
            ->select(DB::raw('COUNT(*) as total'), DB::raw('AVG(pourcentage) as avg_completion'),
                     DB::raw('SUM(CASE WHEN statut="terminee" THEN 1 ELSE 0 END) as terminee'),
                     DB::raw('SUM(CASE WHEN statut="en_cours" THEN 1 ELSE 0 END) as en_cours'),
                     DB::raw('SUM(CASE WHEN statut="nouvelle" THEN 1 ELSE 0 END) as nouvelle'))
            ->first();

        // ─── Signalements en cours ─────────────────────────────
        $signalementsEnCours = Signalement::whereNotIn('statut', ['ferme', 'clos', 'archive'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'type', 'statut', 'severite', 'is_anonymous', 'created_at']);

        return response()->json([
            'my_tasks'           => $myTasks,
            'upcoming_deadlines' => $upcomingDeadlines,
            'pending_requests'   => $pendingRequests,
            'communiques'        => $recentCommuniques,
            'attendance'         => [
                'total_actifs'   => $senActifs,
                'today_present'  => $todayPresent,
                'today_rate'     => $todayRate,
                'monthly_rate'   => $monthlyRate,
            ],
            'pta'                => [
                'total'          => (int) ($ptaSen->total ?? 0),
                'avg_completion' => round((float) ($ptaSen->avg_completion ?? 0), 1),
                'terminee'       => (int) ($ptaSen->terminee ?? 0),
                'en_cours'       => (int) ($ptaSen->en_cours ?? 0),
                'nouvelle'       => (int) ($ptaSen->nouvelle ?? 0),
            ],
            'signalements'       => $signalementsEnCours,
        ]);
    }
}
