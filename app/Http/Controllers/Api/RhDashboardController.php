<?php

namespace App\Http\Controllers\Api;

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
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RhDashboardController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    /**
     * Helper: returns a base Agent query scoped to the user's province (if RH Provincial).
     */
    private function agentQuery(Request $request)
    {
        $q = Agent::query();
        $this->scopeService()->applyAgentScope($q, $request->user());
        return $q;
    }

    /**
     * Helper: scope any model that has an agent_id FK by province.
     */
    private function scopeByAgent($query, ?int $provinceId)
    {
        if ($provinceId) {
            $query->whereHas('agent', fn($q) => $q->where('province_id', $provinceId));
        }
        return $query;
    }

    public function index(Request $request)
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        $scope = $this->scopeService();
        $user = $request->user();
        $isProvincial = $scope->isProvincialRh($user);
        $provinceId = $isProvincial ? $scope->provinceId($user) : null;

        // ─── AGENTS ───
        $agentsTotal = $this->agentQuery($request)->count();
        $agentsActifs = $this->agentQuery($request)->actifs()->count();
        $agentsSuspendus = $this->agentQuery($request)->suspendu()->count();
        $agentsAnciens = $this->agentQuery($request)->anciens()->count();

        $agentsBySexe = $this->agentQuery($request)->actifs()
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
                'total' => $this->agentQuery($request)->where('organe', $nom)->count(),
                'actifs' => $this->agentQuery($request)->actifs()->where('organe', $nom)->count(),
            ];
        }

        // Nouveaux agents ce mois
        $newAgentsMonth = $this->agentQuery($request)->where('created_at', '>=', $startOfMonth)->count();

        // ─── DEMANDES ───
        $reqBase = fn() => $this->scopeByAgent(RequestModel::query(), $provinceId);
        $requestsTotal = $reqBase()->count();
        $requestsPending = $reqBase()->enAttente()->count();
        $requestsApproved = $reqBase()->approuve()->count();
        $requestsRejected = $reqBase()->rejete()->count();

        $requestsByType = $reqBase()->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'type')
            ->toArray();

        $recentRequests = $reqBase()->with('agent:id,nom,prenom')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'agent_id', 'type', 'statut', 'created_at']);

        // ─── PRESENCE ───
        $totalActiveAgents = $agentsActifs ?: 1;

        $todayPresent = $this->scopeByAgent(
            Pointage::byDate($now->toDateString())->whereNotNull('heure_entree'),
            $provinceId
        )->distinct('agent_id')->count('agent_id');
        $todayRate = round(($todayPresent / $totalActiveAgents) * 100, 1);

        // Presence des 7 derniers jours
        $weekAgo = $now->copy()->subDays(6);
        $weeklyPresence = $this->scopeByAgent(
            Pointage::betweenDates($weekAgo->toDateString(), $now->toDateString())->whereNotNull('heure_entree'),
            $provinceId
        )->select('date_pointage', DB::raw('COUNT(DISTINCT agent_id) as present'))
            ->groupBy('date_pointage')
            ->orderBy('date_pointage')
            ->get()
            ->map(fn($p) => [
                'date' => $p->date_pointage,
                'present' => $p->present,
                'rate' => round(($p->present / $totalActiveAgents) * 100, 1),
            ]);

        $avgMonthlyRate = 0;
        $monthlyPointages = $this->scopeByAgent(
            Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())->whereNotNull('heure_entree'),
            $provinceId
        )->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
            ->groupBy('date_pointage')
            ->get();
        if ($monthlyPointages->count() > 0) {
            $avgMonthlyRate = round(($monthlyPointages->avg('present') / $totalActiveAgents) * 100, 1);
        }

        // ─── DOCUMENTS ───
        $docBase = fn() => $this->scopeByAgent(Document::query(), $provinceId);
        $documentsTotal = $docBase()->count();
        $documentsValides = $docBase()->valides()->count();
        $documentsExpires = $docBase()->expires()->count();

        $documentsByType = $docBase()->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'type')
            ->toArray();

        // ─── SIGNALEMENTS ───
        $sigBase = fn() => $this->scopeByAgent(Signalement::query(), $provinceId);
        $signalementTotal = $sigBase()->count();
        $signalementOuvert = $sigBase()->ouvert()->count();
        $signalementEnCours = $sigBase()->enCours()->count();
        $signalementResolu = $sigBase()->resolu()->count();
        $signalementHaute = $sigBase()->hauteSeverite()
            ->whereIn('statut', ['ouvert', 'en_cours'])
            ->count();

        $recentSignalements = $sigBase()->with('agent:id,nom,prenom')
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
        $statusBase = fn() => $this->scopeByAgent(AgentStatus::actuel(), $provinceId);
        $agentStatusCounts = $statusBase()
            ->select('statut', DB::raw('COUNT(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $agentStatusDetails = [];
        $statusTypes = ['en_conge', 'en_mission', 'suspendu', 'en_formation'];
        foreach ($statusTypes as $statut) {
            $agents = $this->scopeByAgent(AgentStatus::actuel(), $provinceId)
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

        // ─── CONGÉS (HOLIDAY) ─── [CRITIQUE POUR RH]
        $currentYear = $now->year;

        $holBase = fn() => $this->scopeByAgent(Holiday::query(), $provinceId);
        $holidaysTotal = $holBase()->count();
        $holidaysPending = $holBase()->pending()->count();
        $holidaysApproved = $holBase()->approved()->count();
        $holidaysActiveToday = $holBase()->active($now)->count();

        // Prochains retours de congé (7 prochains jours)
        $next7Days = $now->copy()->addDays(7);
        $upcomingReturns = $holBase()->approved()
            ->whereBetween('date_retour_prevu', [$now->toDateString(), $next7Days->toDateString()])
            ->with('agent:id,nom,prenom,organe')
            ->orderBy('date_retour_prevu')
            ->limit(10)
            ->get(['id', 'agent_id', 'date_retour_prevu', 'type_conge', 'nombre_jours']);

        // Taux d'utilisation congés
        $holidayPlanningQuery = HolidayPlanning::where('annee', $currentYear);
        if ($provinceId) {
            $holidayPlanningQuery->where(function ($q) use ($provinceId) {
                $q->where('type_structure', 'sep')
                  ->where('structure_id', $provinceId);
            });
        }
        $holidayPlanningStats = $holidayPlanningQuery
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

        // ─── AFFECTATIONS ─── [GESTION MOBILITÉ]
        $affBase = fn() => $provinceId
            ? Affectation::where('actif', true)->where(function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId)
                  ->orWhereHas('agent', fn($aq) => $aq->where('province_id', $provinceId));
            })
            : Affectation::where('actif', true);

        $affectationsActives = $affBase()->count();

        $agentsSansAffectation = $this->agentQuery($request)->actifs()
            ->whereDoesntHave('affectations', fn($q) => $q->where('actif', true))
            ->count();

        // Mobilité récente (30 derniers jours)
        $mobiliteQuery = Affectation::where('date_debut', '>=', $now->copy()->subDays(30));
        if ($provinceId) {
            $mobiliteQuery->where(function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId)
                  ->orWhereHas('agent', fn($aq) => $aq->where('province_id', $provinceId));
            });
        }
        $mobiliteRecente = $mobiliteQuery
            ->with('agent:id,nom,prenom', 'fonction:id,nom')
            ->orderByDesc('date_debut')
            ->limit(10)
            ->get(['id', 'agent_id', 'fonction_id', 'niveau_administratif', 'date_debut']);

        // ─── PLAN DE TRAVAIL ───
        $ptaBase = fn() => $provinceId
            ? ActivitePlan::parAnnee($currentYear)->where('province_id', $provinceId)
            : ActivitePlan::parAnnee($currentYear);

        $planTotal = $ptaBase()->count();
        $planTerminee = $ptaBase()->terminee()->count();
        $planEnCours = $ptaBase()->enCours()->count();
        $avgCompletionPlan = $ptaBase()->avg('pourcentage') ?? 0;

        // ─── TÂCHES RH ───
        $tacheBase = fn() => $this->scopeByAgent(Tache::query(), $provinceId);
        $tachesTotal = $tacheBase()->count();
        $tachesEnCours = $tacheBase()->enCours()->count();
        $tachesTerminee = $tacheBase()->terminee()->count();
        $tachesOverdue = $tacheBase()->where('statut', '!=', 'terminee')
            ->whereNotNull('date_echeance')
            ->where('date_echeance', '<', $now->toDateString())
            ->count();

        // Tâches récentes
        $recentTaches = $tacheBase()->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'titre', 'statut', 'priorite', 'date_echeance', 'created_at']);

        // ─── GRADES & COMPÉTENCES ───
        $gradesDistribution = Grade::query()
            ->withCount(['agents' => function ($q) use ($provinceId) {
                if ($provinceId) {
                    $q->where('province_id', $provinceId);
                }
            }])
            ->orderBy('ordre')
            ->get(['id', 'nom', 'code'])
            ->map(fn($g) => [
                'nom' => $g->nom,
                'count' => $g->agents_count,
            ]);

        $domainesEtudes = $this->agentQuery($request)->actifs()
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

        // ─── GÉOGRAPHIE ───
        if ($provinceId) {
            // RH Provincial: only their province
            $agentsByProvince = Province::where('id', $provinceId)
                ->withCount(['agents' => fn($q) => $q->actifs()])
                ->get(['id', 'nom', 'code'])
                ->map(fn($p) => [
                    'nom' => $p->nom,
                    'code' => $p->code,
                    'count' => $p->agents_count,
                ]);
        } else {
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
        }

        // ─── KPIs AVANCÉS ───
        $totalRequestsTraitees = $reqBase()->whereIn('statut', ['approuve', 'rejete'])->count();
        $tauxApprobation = 0;
        if ($totalRequestsTraitees > 0) {
            $tauxApprobation = round(($requestsApproved / $totalRequestsTraitees) * 100, 1);
        }

        // Évolution effectifs (3 derniers mois)
        $evolutionEffectifs = [];
        for ($i = 2; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $effectifQuery = Agent::where('created_at', '<=', $monthEnd);
            if ($provinceId) {
                $effectifQuery->where('province_id', $provinceId);
            }
            $effectif = $effectifQuery->count();

            $evolutionEffectifs[] = [
                'mois' => $monthStart->format('M Y'),
                'effectif' => $effectif,
            ];
        }

        $payload = [
            'scope' => [
                'is_provincial' => $isProvincial,
                'province_id' => $provinceId,
                'province_nom' => $isProvincial && $provinceId
                    ? Province::find($provinceId)?->nom
                    : null,
            ],
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
        ];

        return $this->success($payload, [], $payload);
    }
}
