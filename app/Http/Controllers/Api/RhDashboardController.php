<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentStatus;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use App\Models\Signalement;
use App\Models\Document;
use App\Models\Communique;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use App\Models\Affectation;
use App\Models\ActivitePlan;
use App\Models\Tache;
use App\Models\Grade;
use App\Models\Province;
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

        $recentRequests = RequestModel::with('agent:id,nom,prenom')
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

        // ─── STATUTS AGENTS ───
        $agentStatusCounts = AgentStatus::actuel()
            ->select('statut', DB::raw('COUNT(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $agentStatusDetails = [];
        $statusTypes = ['en_conge', 'en_mission', 'suspendu', 'en_formation'];
        foreach ($statusTypes as $statut) {
            $agents = AgentStatus::actuel()
                ->byStatut($statut)
                ->with(['agent:id,nom,prenom,id_agent,organe,sexe,poste_actuel'])
                ->orderBy('date_debut', 'desc')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'agent_id' => $s->agent_id,
                    'nom' => $s->agent?->nom,
                    'prenom' => $s->agent?->prenom,
                    'id_agent' => $s->agent?->id_agent,
                    'organe' => $s->agent?->organe,
                    'poste' => $s->agent?->poste_actuel,
                    'sexe' => $s->agent?->sexe,
                    'date_debut' => $s->date_debut,
                    'date_fin' => $s->date_fin,
                    'motif' => $s->motif,
                ]);
            $agentStatusDetails[$statut] = $agents;
        }

        // ─── CONGÉS (HOLIDAY) ─── [NOUVEAU - CRITIQUE POUR RH]
        $currentYear = $now->year;

        $holidaysTotal = Holiday::count();
        $holidaysPending = Holiday::pending()->count();
        $holidaysApproved = Holiday::approved()->count();
        $holidaysActiveToday = Holiday::active($now)->count();

        // Prochains retours de congé (7 prochains jours)
        $next7Days = $now->copy()->addDays(7);
        $upcomingReturns = Holiday::approved()
            ->whereBetween('date_retour_prevu', [$now->toDateString(), $next7Days->toDateString()])
            ->with('agent:id,nom,prenom,organe')
            ->orderBy('date_retour_prevu')
            ->limit(10)
            ->get(['id', 'agent_id', 'date_retour_prevu', 'type_conge', 'nombre_jours']);

        // Taux d'utilisation congés
        $holidayPlanningStats = HolidayPlanning::where('annee', $currentYear)
            ->selectRaw('
                COUNT(*) as total_plannings,
                SUM(jours_utilises) as total_utilises,
                SUM(jours_conge_totaux) as total_conge_totaux
            ')
            ->first();

        $tauxUtilisationConges = 0;
        if ($holidayPlanningStats && $holidayPlanningStats->total_conge_totaux > 0) {
            $tauxUtilisationConges = round(
                ($holidayPlanningStats->total_utilises / $holidayPlanningStats->total_conge_totaux) * 100,
                1
            );
        }

        // ─── AFFECTATIONS ─── [NOUVEAU - GESTION MOBILITÉ]
        $affectationsActives = Affectation::where('actif', true)->count();

        $agentsSansAffectation = Agent::actifs()
            ->whereDoesntHave('affectations', fn($q) => $q->where('actif', true))
            ->count();

        // Mobilité récente (30 derniers jours)
        $mobiliteRecente = Affectation::where('date_debut', '>=', $now->copy()->subDays(30))
            ->with('agent:id,nom,prenom', 'fonction:id,nom')
            ->orderByDesc('date_debut')
            ->limit(10)
            ->get(['id', 'agent_id', 'fonction_id', 'niveau_administratif', 'date_debut']);

        // ─── PLAN DE TRAVAIL ─── [NOUVEAU]
        $planTotal = ActivitePlan::parAnnee($currentYear)->count();
        $planTerminee = ActivitePlan::parAnnee($currentYear)->terminee()->count();
        $planEnCours = ActivitePlan::parAnnee($currentYear)->enCours()->count();
        $avgCompletionPlan = ActivitePlan::parAnnee($currentYear)->avg('pourcentage') ?? 0;

        // ─── TÂCHES RH ─── [NOUVEAU]
        $tachesTotal = Tache::count();
        $tachesEnCours = Tache::enCours()->count();
        $tachesTerminee = Tache::terminee()->count();
        $tachesOverdue = Tache::where('statut', '!=', 'terminee')
            ->whereNotNull('date_echeance')
            ->where('date_echeance', '<', $now->toDateString())
            ->count();

        // Tâches récentes
        $recentTaches = Tache::orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'titre', 'statut', 'priorite', 'date_echeance', 'created_at']);

        // ─── GRADES & COMPÉTENCES ─── [NOUVEAU]
        $gradesDistribution = Grade::withCount('agents')
            ->orderBy('ordre')
            ->get(['id', 'nom', 'code'])
            ->map(fn($g) => [
                'nom' => $g->nom,
                'count' => $g->agents_count,
            ]);

        $domainesEtudes = Agent::actifs()
            ->select('domaine_etudes', DB::raw('COUNT(*) as count'))
            ->whereNotNull('domaine_etudes')
            ->groupBy('domaine_etudes')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($d) => [
                'domaine' => $d->domaine_etudes,
                'count' => $d->count,
            ]);

        // ─── GÉOGRAPHIE ─── [NOUVEAU]
        $agentsByProvince = Province::withCount([
                'agents' => fn($q) => $q->actifs()
            ])
            ->orderByDesc('agents_count')
            ->limit(10)
            ->get(['id', 'nom', 'code'])
            ->map(fn($p) => [
                'nom' => $p->nom,
                'code' => $p->code,
                'count' => $p->agents_count,
            ]);

        // ─── KPIs AVANCÉS ─── [NOUVEAU]
        $totalRequestsTraitees = RequestModel::whereIn('statut', ['approuve', 'rejete'])->count();
        $tauxApprobation = 0;
        if ($totalRequestsTraitees > 0) {
            $tauxApprobation = round(($requestsApproved / $totalRequestsTraitees) * 100, 1);
        }

        // Évolution effectifs (3 derniers mois)
        $evolutionEffectifs = [];
        for ($i = 2; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $effectif = Agent::where('created_at', '<=', $monthEnd)->count();

            $evolutionEffectifs[] = [
                'mois' => $monthStart->format('M Y'),
                'effectif' => $effectif,
            ];
        }

        return response()->json([
            'agents' => [
                'total' => $agentsTotal,
                'actifs' => $agentsActifs,
                'suspendus' => $agentsSuspendus,
                'anciens' => $agentsAnciens,
                'new_this_month' => $newAgentsMonth,
                'sans_affectation' => $agentsSansAffectation,
                'by_sexe' => $agentsBySexe,
                'by_organe' => $agentsByOrgane,
                'by_grade' => $gradesDistribution,
                'by_province' => $agentsByProvince,
                'evolution_mensuelle' => $evolutionEffectifs,
            ],
            'requests' => [
                'total' => $requestsTotal,
                'en_attente' => $requestsPending,
                'approuve' => $requestsApproved,
                'rejete' => $requestsRejected,
                'taux_approbation' => $tauxApprobation,
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
            'agent_statuses' => [
                'counts' => [
                    'en_conge' => $agentStatusCounts['en_conge'] ?? 0,
                    'en_mission' => $agentStatusCounts['en_mission'] ?? 0,
                    'suspendu' => $agentStatusCounts['suspendu'] ?? 0,
                    'en_formation' => $agentStatusCounts['en_formation'] ?? 0,
                    'disponible' => $agentStatusCounts['disponible'] ?? 0,
                ],
                'details' => $agentStatusDetails,
            ],
            // ──────────────────────────────────────────
            // ✨ NOUVELLES DONNÉES - VISION RH COMPLÈTE ✨
            // ──────────────────────────────────────────
            'holidays' => [
                'total' => $holidaysTotal,
                'pending' => $holidaysPending,
                'approved' => $holidaysApproved,
                'active_today' => $holidaysActiveToday,
                'upcoming_returns' => $upcomingReturns,
                'taux_utilisation_pct' => $tauxUtilisationConges,
            ],
            'affectations' => [
                'actives' => $affectationsActives,
                'agents_sans_affectation' => $agentsSansAffectation,
                'mobilite_30_jours' => $mobiliteRecente,
            ],
            'plan_travail' => [
                'total' => $planTotal,
                'en_cours' => $planEnCours,
                'terminee' => $planTerminee,
                'avg_completion' => round($avgCompletionPlan, 0),
            ],
            'taches' => [
                'total' => $tachesTotal,
                'en_cours' => $tachesEnCours,
                'terminee' => $tachesTerminee,
                'overdue' => $tachesOverdue,
                'recent' => $recentTaches,
            ],
            'formations' => [
                'domaines_etudes' => $domainesEtudes,
            ],
        ]);
    }
}
