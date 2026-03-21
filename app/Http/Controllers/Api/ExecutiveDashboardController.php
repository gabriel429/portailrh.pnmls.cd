<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use App\Models\Signalement;
use App\Models\Tache;
use App\Models\ActivitePlan;
use App\Models\Communique;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('SEN')) {
            return response()->json(['message' => 'Acces reserve au SEN.'], 403);
        }

        $now = Carbon::now();
        $currentYear = $now->year;
        $startOfMonth = $now->copy()->startOfMonth();

        // ─── AGENTS ───
        $agentsTotal = Agent::count();
        $agentsActifs = Agent::actifs()->count();
        $agentsSuspendus = Agent::suspendu()->count();
        $agentsAnciens = Agent::anciens()->count();

        $agentsByOrgane = [
            'sen' => Agent::where('organe', 'Secrétariat Exécutif National')->count(),
            'sep' => Agent::where('organe', 'Secrétariat Exécutif Provincial')->count(),
            'sel' => Agent::where('organe', 'Secrétariat Exécutif Local')->count(),
        ];

        // ─── DEMANDES ───
        $requestsTotal = RequestModel::count();
        $requestsPending = RequestModel::enAttente()->count();
        $requestsApproved = RequestModel::approuve()->count();
        $requestsRejected = RequestModel::rejete()->count();

        $recentPendingRequests = RequestModel::enAttente()
            ->with('agent:id,nom,prenom,organe')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'agent_id', 'type', 'description', 'created_at']);

        // ─── PRESENCE ───
        $totalActiveAgents = $agentsActifs ?: 1;

        $todayPresent = Pointage::byDate($now->toDateString())
            ->whereNotNull('heure_entree')
            ->distinct('agent_id')
            ->count('agent_id');
        $todayRate = round(($todayPresent / $totalActiveAgents) * 100, 1);

        $monthlyPointages = Pointage::betweenDates(
            $startOfMonth->toDateString(),
            $now->toDateString()
        )->whereNotNull('heure_entree')
         ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
         ->groupBy('date_pointage')
         ->get();

        $avgMonthlyRate = 0;
        if ($monthlyPointages->count() > 0) {
            $avgPresent = $monthlyPointages->avg('present');
            $avgMonthlyRate = round(($avgPresent / $totalActiveAgents) * 100, 1);
        }

        // ─── SIGNALEMENTS ───
        $signalementTotal = Signalement::count();
        $signalementOuvert = Signalement::ouvert()->count();
        $signalementEnCours = Signalement::enCours()->count();
        $signalementHaute = Signalement::hauteSeverite()
            ->whereIn('statut', ['ouvert', 'en_cours'])
            ->count();

        $recentSignalements = Signalement::with('agent:id,nom,prenom')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'agent_id', 'type', 'severite', 'statut', 'created_at']);

        // ─── TACHES ───
        $tachesTotal = Tache::count();
        $tachesNouvelle = Tache::nouvelle()->count();
        $tachesEnCours = Tache::enCours()->count();
        $tachesTerminee = Tache::terminee()->count();
        $tachesOverdue = Tache::where('statut', '!=', 'terminee')
            ->whereNotNull('date_echeance')
            ->where('date_echeance', '<', $now->toDateString())
            ->count();

        // ─── PLAN DE TRAVAIL ───
        $planQuery = ActivitePlan::parAnnee($currentYear);
        $planTotal = (clone $planQuery)->count();
        $planTerminee = (clone $planQuery)->terminee()->count();
        $planEnCours = (clone $planQuery)->enCours()->count();
        $planPlanifiee = (clone $planQuery)->planifiee()->count();
        $avgCompletion = (clone $planQuery)->avg('pourcentage') ?? 0;

        $progressByTrimestre = [];
        for ($t = 1; $t <= 4; $t++) {
            $tq = ActivitePlan::parAnnee($currentYear)->parTrimestre("T{$t}");
            $total = (clone $tq)->count();
            $terminee = (clone $tq)->terminee()->count();
            $avgPct = (clone $tq)->avg('pourcentage') ?? 0;
            $progressByTrimestre[] = [
                'trimestre' => "T{$t}",
                'total' => $total,
                'terminee' => $terminee,
                'avg_pourcentage' => round($avgPct, 0),
            ];
        }

        // ─── COMMUNIQUES ───
        $communiquesActifs = Communique::visibles()->count();
        $communiquesUrgents = Communique::visibles()->urgent()->count();

        $recentCommuniques = Communique::visibles()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'titre', 'urgence', 'created_at']);

        // ─── DOCUMENTS ───
        $documentsTotal = Document::count();

        return response()->json([
            'agents' => [
                'total' => $agentsTotal,
                'actifs' => $agentsActifs,
                'suspendus' => $agentsSuspendus,
                'anciens' => $agentsAnciens,
                'by_organe' => $agentsByOrgane,
            ],
            'requests' => [
                'total' => $requestsTotal,
                'en_attente' => $requestsPending,
                'approuve' => $requestsApproved,
                'rejete' => $requestsRejected,
                'recent_pending' => $recentPendingRequests,
            ],
            'attendance' => [
                'today_present' => $todayPresent,
                'today_rate' => $todayRate,
                'monthly_avg_rate' => $avgMonthlyRate,
                'total_active_agents' => $totalActiveAgents,
            ],
            'signalements' => [
                'total' => $signalementTotal,
                'ouvert' => $signalementOuvert,
                'en_cours' => $signalementEnCours,
                'haute_severite' => $signalementHaute,
                'recent' => $recentSignalements,
            ],
            'taches' => [
                'total' => $tachesTotal,
                'nouvelle' => $tachesNouvelle,
                'en_cours' => $tachesEnCours,
                'terminee' => $tachesTerminee,
                'overdue' => $tachesOverdue,
            ],
            'plan_travail' => [
                'total' => $planTotal,
                'planifiee' => $planPlanifiee,
                'en_cours' => $planEnCours,
                'terminee' => $planTerminee,
                'avg_completion' => round($avgCompletion, 0),
                'by_trimestre' => $progressByTrimestre,
            ],
            'communiques' => [
                'actifs' => $communiquesActifs,
                'urgents' => $communiquesUrgents,
                'recent' => $recentCommuniques,
            ],
            'documents' => [
                'total' => $documentsTotal,
            ],
        ]);
    }
}
