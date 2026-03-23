<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use App\Models\Signalement;
use App\Models\Document;
use App\Models\Communique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RhDashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // ─── AGENTS ───
        $agentsTotal = Agent::count();
        $agentsActifs = Agent::actifs()->count();
        $agentsSuspendus = Agent::suspendu()->count();
        $agentsAnciens = Agent::anciens()->count();

        $agentsBySexe = Agent::actifs()
            ->select('sexe', DB::raw('COUNT(*) as total'))
            ->groupBy('sexe')
            ->pluck('total', 'sexe')
            ->toArray();

        $agentsByOrgane = [];
        $organes = [
            'sen' => 'Secretariat Executif National',
            'sep' => 'Secretariat Executif Provincial',
            'sel' => 'Secretariat Executif Local',
        ];
        foreach ($organes as $code => $nom) {
            $agentsByOrgane[$code] = [
                'total' => Agent::where('organe', $nom)->count(),
                'actifs' => Agent::actifs()->where('organe', $nom)->count(),
            ];
        }

        // Nouveaux agents ce mois
        $newAgentsMonth = Agent::where('created_at', '>=', $startOfMonth)->count();

        // ─── DEMANDES ───
        $requestsTotal = RequestModel::count();
        $requestsPending = RequestModel::enAttente()->count();
        $requestsApproved = RequestModel::approuve()->count();
        $requestsRejected = RequestModel::rejete()->count();

        $requestsByType = RequestModel::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'type')
            ->toArray();

        $recentRequests = RequestModel::with('agent:id,nom,prenom,matricule_pnmls')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'agent_id', 'type', 'statut', 'created_at']);

        // ─── PRESENCE ───
        $totalActiveAgents = $agentsActifs ?: 1;

        $todayPresent = Pointage::byDate($now->toDateString())
            ->whereNotNull('heure_entree')
            ->distinct('agent_id')
            ->count('agent_id');
        $todayRate = round(($todayPresent / $totalActiveAgents) * 100, 1);

        // Presence des 7 derniers jours
        $weekAgo = $now->copy()->subDays(6);
        $weeklyPresence = Pointage::betweenDates($weekAgo->toDateString(), $now->toDateString())
            ->whereNotNull('heure_entree')
            ->select('date_pointage', DB::raw('COUNT(DISTINCT agent_id) as present'))
            ->groupBy('date_pointage')
            ->orderBy('date_pointage')
            ->get()
            ->map(fn($p) => [
                'date' => $p->date_pointage,
                'present' => $p->present,
                'rate' => round(($p->present / $totalActiveAgents) * 100, 1),
            ]);

        $avgMonthlyRate = 0;
        $monthlyPointages = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
            ->whereNotNull('heure_entree')
            ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
            ->groupBy('date_pointage')
            ->get();
        if ($monthlyPointages->count() > 0) {
            $avgMonthlyRate = round(($monthlyPointages->avg('present') / $totalActiveAgents) * 100, 1);
        }

        // ─── DOCUMENTS ───
        $documentsTotal = Document::count();
        $documentsValides = Document::valides()->count();
        $documentsExpires = Document::expires()->count();

        $documentsByType = Document::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'type')
            ->toArray();

        // ─── SIGNALEMENTS ───
        $signalementTotal = Signalement::count();
        $signalementOuvert = Signalement::ouvert()->count();
        $signalementEnCours = Signalement::enCours()->count();
        $signalementResolu = Signalement::resolu()->count();
        $signalementHaute = Signalement::hauteSeverite()
            ->whereIn('statut', ['ouvert', 'en_cours'])
            ->count();

        $recentSignalements = Signalement::with('agent:id,nom,prenom')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'agent_id', 'type', 'severite', 'statut', 'created_at']);

        // ─── COMMUNIQUES ───
        $communiquesActifs = Communique::visibles()->count();
        $communiquesUrgents = Communique::visibles()->urgent()->count();

        $recentCommuniques = Communique::visibles()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'titre', 'urgence', 'created_at']);

        return response()->json([
            'agents' => [
                'total' => $agentsTotal,
                'actifs' => $agentsActifs,
                'suspendus' => $agentsSuspendus,
                'anciens' => $agentsAnciens,
                'new_this_month' => $newAgentsMonth,
                'by_sexe' => $agentsBySexe,
                'by_organe' => $agentsByOrgane,
            ],
            'requests' => [
                'total' => $requestsTotal,
                'en_attente' => $requestsPending,
                'approuve' => $requestsApproved,
                'rejete' => $requestsRejected,
                'by_type' => $requestsByType,
                'recent' => $recentRequests,
            ],
            'attendance' => [
                'today_present' => $todayPresent,
                'today_rate' => $todayRate,
                'monthly_avg_rate' => $avgMonthlyRate,
                'total_active_agents' => $totalActiveAgents,
                'weekly' => $weeklyPresence,
            ],
            'documents' => [
                'total' => $documentsTotal,
                'valides' => $documentsValides,
                'expires' => $documentsExpires,
                'by_type' => $documentsByType,
            ],
            'signalements' => [
                'total' => $signalementTotal,
                'ouvert' => $signalementOuvert,
                'en_cours' => $signalementEnCours,
                'resolu' => $signalementResolu,
                'haute_severite' => $signalementHaute,
                'recent' => $recentSignalements,
            ],
            'communiques' => [
                'actifs' => $communiquesActifs,
                'urgents' => $communiquesUrgents,
                'recent' => $recentCommuniques,
            ],
        ]);
    }
}
