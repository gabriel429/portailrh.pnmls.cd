<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use App\Models\Signalement;
use App\Models\Tache;
use App\Models\ActivitePlan;
use App\Models\Communique;
use App\Models\Document;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use App\Models\AgentStatus;
use App\Models\Affectation;
use App\Models\AuditLog;
use App\Models\NotificationPortail;
use App\Models\Message;
use App\Models\Grade;
use App\Models\Fonction;
use App\Models\Section;
use App\Models\Department;
use App\Models\Province;
use App\Models\Localite;
use App\Models\Institution;
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExecutiveDashboardController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('SEN')) {
            return response()->json(['message' => 'Acces reserve au SEN.'], 403);
        }

        $now = Carbon::now();
        $currentYear = $now->year;
        $startOfMonth = $now->copy()->startOfMonth();

        // ─── ORGANES mapping ───
        $organes = [
            'sen' => 'Secrétariat Exécutif National',
            'sep' => 'Secrétariat Exécutif Provincial',
            'sel' => 'Secrétariat Exécutif Local',
        ];

        // ─── AGENTS ───
        $agentsTotal = Agent::count();
        $agentsActifs = Agent::actifs()->count();
        $agentsSuspendus = Agent::suspendu()->count();
        $agentsAnciens = Agent::anciens()->count();

        $agentsByOrgane = [];
        foreach ($organes as $code => $nom) {
            $agentsByOrgane[$code] = [
                'total' => Agent::where('organe', $nom)->count(),
                'actifs' => Agent::actifs()->where('organe', $nom)->count(),
                'suspendus' => Agent::suspendu()->where('organe', $nom)->count(),
                'anciens' => Agent::anciens()->where('organe', $nom)->count(),
            ];
        }

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

        // Presence par organe
        $attendanceByOrgane = [];
        foreach ($organes as $code => $nom) {
            $orgActifs = $agentsByOrgane[$code]['actifs'] ?: 1;
            $orgTodayPresent = Pointage::byDate($now->toDateString())
                ->whereNotNull('heure_entree')
                ->whereHas('agent', fn($q) => $q->where('organe', $nom))
                ->distinct('agent_id')
                ->count('agent_id');

            $orgMonthly = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
                ->whereNotNull('heure_entree')
                ->whereHas('agent', fn($q) => $q->where('organe', $nom))
                ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
                ->groupBy('date_pointage')
                ->get();

            $orgMonthlyRate = 0;
            if ($orgMonthly->count() > 0) {
                $orgMonthlyRate = round(($orgMonthly->avg('present') / $orgActifs) * 100, 1);
            }

            $attendanceByOrgane[$code] = [
                'today_present' => $orgTodayPresent,
                'today_rate' => round(($orgTodayPresent / $orgActifs) * 100, 1),
                'monthly_avg_rate' => $orgMonthlyRate,
                'total_active_agents' => $agentsByOrgane[$code]['actifs'],
            ];
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

        // Plan par organe (niveau_administratif = SEN/SEP/SEL)
        $planByOrgane = [];
        foreach (['SEN', 'SEP', 'SEL'] as $niveau) {
            $nq = ActivitePlan::parAnnee($currentYear)->parNiveau($niveau);
            $nTotal = (clone $nq)->count();
            $nTerminee = (clone $nq)->terminee()->count();
            $nAvg = (clone $nq)->avg('pourcentage') ?? 0;

            $nTrimestres = [];
            for ($t = 1; $t <= 4; $t++) {
                $ntq = ActivitePlan::parAnnee($currentYear)->parNiveau($niveau)->parTrimestre("T{$t}");
                $ntTotal = (clone $ntq)->count();
                $ntTerminee = (clone $ntq)->terminee()->count();
                $ntAvg = (clone $ntq)->avg('pourcentage') ?? 0;
                $nTrimestres[] = [
                    'trimestre' => "T{$t}",
                    'total' => $ntTotal,
                    'terminee' => $ntTerminee,
                    'avg_pourcentage' => round($ntAvg, 0),
                ];
            }

            $planByOrgane[strtolower($niveau)] = [
                'total' => $nTotal,
                'terminee' => $nTerminee,
                'avg_completion' => round($nAvg, 0),
                'by_trimestre' => $nTrimestres,
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
        $documentsValides = Document::where('statut', 'valide')->count();
        $documentsExpires = Document::where('statut', 'expiré')->count();
        $documentsRejetes = Document::where('statut', 'rejeté')->count();

        // Documents par type (utilise le champ 'type' de la table documents)
        $documentsByType = Document::select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($doc) => [
                'type' => $doc->type,
                'count' => $doc->count,
            ]);

        // ─── CONGÉS (HOLIDAYS) ─── [PRIORITÉ 1]
        $holidaysTotal = Holiday::count();
        $holidaysPending = Holiday::pending()->count();
        $holidaysApproved = Holiday::approved()->count();

        // Congés actifs aujourd'hui
        $holidaysActiveToday = Holiday::active($now)->count();
        $agentsEnCongeToday = Holiday::active($now)
            ->with('agent:id,nom,prenom,organe,fonction')
            ->get()
            ->map(fn($h) => [
                'id' => $h->id,
                'agent' => $h->agent ? $h->agent->prenom . ' ' . $h->agent->nom : 'N/A',
                'organe' => $h->agent->organe ?? 'N/A',
                'type' => $h->getTypeCongeLabel(),
                'date_debut' => $h->date_debut->format('Y-m-d'),
                'date_fin' => $h->date_fin->format('Y-m-d'),
                'date_retour_prevu' => $h->date_retour_prevu?->format('Y-m-d'),
            ]);

        // Prochains congés (30 jours)
        $next30Days = $now->copy()->addDays(30);
        $upcomingHolidays = Holiday::approved()
            ->whereBetween('date_debut', [$now->toDateString(), $next30Days->toDateString()])
            ->with('agent:id,nom,prenom,organe')
            ->orderBy('date_debut')
            ->limit(10)
            ->get(['id', 'agent_id', 'date_debut', 'date_fin', 'type_conge', 'nombre_jours']);

        // Congés par organe
        $holidaysByOrgane = [];
        foreach ($organes as $code => $nom) {
            $holidaysByOrgane[$code] = [
                'active_today' => Holiday::active($now)
                    ->whereHas('agent', fn($q) => $q->where('organe', $nom))
                    ->count(),
                'pending' => Holiday::pending()
                    ->whereHas('agent', fn($q) => $q->where('organe', $nom))
                    ->count(),
                'approved_this_year' => Holiday::approved()
                    ->forYear($currentYear)
                    ->whereHas('agent', fn($q) => $q->where('organe', $nom))
                    ->count(),
            ];
        }

        // Planning congés annuel (taux utilisation)
        $holidayPlanningStats = HolidayPlanning::where('annee', $currentYear)
            ->selectRaw('
                COUNT(*) as total_plannings,
                SUM(jours_utilises) as total_utilises,
                SUM(jours_conge_totaux) as total_conge_totaux,
                AVG(jours_utilises) as avg_utilises
            ')
            ->first();

        $tauxUtilisationConges = 0;
        if ($holidayPlanningStats && $holidayPlanningStats->total_conge_totaux > 0) {
            $tauxUtilisationConges = round(
                ($holidayPlanningStats->total_utilises / $holidayPlanningStats->total_conge_totaux) * 100,
                1
            );
        }

        // ─── AFFECTATIONS ─── [PRIORITÉ 1]
        $affectationsTotal = Affectation::count();
        $affectationsActives = Affectation::where('actif', true)->count();

        // Postes vacants (fonctions sans affectation active)
        $fonctionsTotal = Fonction::count();
        $fonctionsAvecAffectation = Affectation::where('actif', true)
            ->distinct('fonction_id')
            ->count('fonction_id');
        $postesVacants = $fonctionsTotal - $fonctionsAvecAffectation;

        // Agents sans affectation active
        $agentsSansAffectation = Agent::actifs()
            ->whereDoesntHave('affectations', fn($q) => $q->where('actif', true))
            ->count();

        // Mobilité récente (changements affectation 30 derniers jours)
        $mobiliteRecente = Affectation::where('date_debut', '>=', $now->copy()->subDays(30))
            ->with('agent:id,nom,prenom', 'fonction:id,nom')
            ->orderByDesc('date_debut')
            ->limit(10)
            ->get(['id', 'agent_id', 'fonction_id', 'niveau_administratif', 'date_debut']);

        // Affectations par niveau administratif
        $affectationsByNiveau = [];
        foreach (['SEN', 'SEP', 'SEL'] as $niveau) {
            $affectationsByNiveau[strtolower($niveau)] = Affectation::where('actif', true)
                ->where('niveau_administratif', $niveau)
                ->count();
        }

        // ─── AUDIT & SÉCURITÉ ─── [PRIORITÉ 1]
        $auditTotal = AuditLog::count();
        $auditLast24h = AuditLog::where('created_at', '>=', $now->copy()->subHours(24))->count();

        // Actions sensibles récentes (7 derniers jours)
        $actionsSensibles = AuditLog::whereIn('action', [
                'DELETE', 'UPDATE', 'CREATE'
            ])
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'user_id', 'action', 'table_name', 'record_id', 'created_at']);

        // Comptes gelés récents
        $comptesGeles = DB::table('users')
            ->where('is_frozen', true)
            ->count();

        // Connexions échouées (dernières 24h) - basé sur les logs d'audit
        $connexionsEchouees = AuditLog::where('action', 'LOGIN_FAILED')
            ->where('created_at', '>=', $now->copy()->subHours(24))
            ->count();

        // ─── MESSAGES & NOTIFICATIONS ───
        $messagesNonLus = Message::where('lu', false)->count();
        $notificationsNonLues = NotificationPortail::where('lu', false)->count();

        // ─── GRADES & STRUCTURE RH ───
        $gradesDistribution = Grade::withCount('agents')
            ->orderBy('ordre')
            ->get(['id', 'nom', 'code'])
            ->map(fn($g) => [
                'nom' => $g->nom,
                'count' => $g->agents_count,
            ]);

        // ─── FORMATIONS & COMPÉTENCES ───
        // Domaines d'études distribution
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

        // ─── INSTITUTIONS & PARTENARIATS ───
        $institutionsTotal = Institution::count();
        $agentsByInstitution = Institution::withCount('agents')
            ->orderByDesc('agents_count')
            ->limit(10)
            ->get(['id', 'nom', 'code'])
            ->map(fn($i) => [
                'nom' => $i->nom,
                'count' => $i->agents_count,
            ]);

        // ─── GÉOGRAPHIE ───
        $provincesTotal = Province::count();
        $provincesAvecAgents = Agent::actifs()
            ->distinct('province_id')
            ->whereNotNull('province_id')
            ->count('province_id');

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

        // ─── STATUTS AGENTS (AgentStatus) ─── [NOUVEAU - CRITIQUE POUR SEN]
        $agentStatusCounts = AgentStatus::actuel()
            ->select('statut', DB::raw('COUNT(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        // Détails par statut (limité à 10 par catégorie pour performance)
        $agentStatusDetails = [];
        $statusTypes = ['en_conge', 'en_mission', 'suspendu', 'en_formation', 'disponible'];
        foreach ($statusTypes as $statut) {
            $agents = AgentStatus::actuel()
                ->byStatut($statut)
                ->with(['agent:id,nom,prenom,id_agent,organe,sexe,poste_actuel'])
                ->orderBy('date_debut', 'desc')
                ->limit(10)
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

        // ─── SECTIONS & DÉPARTEMENTS ───
        $sectionsTotal = Section::count();
        $departementsTotal = Department::count();

        $agentsBySection = Section::withCount('agents')
            ->orderByDesc('agents_count')
            ->limit(10)
            ->get(['id', 'nom', 'code'])
            ->map(fn($s) => [
                'nom' => $s->nom,
                'count' => $s->agents_count,
            ]);

        // ─── INDICATEURS PERFORMANCE AVANCÉS ───
        // Taux d'approbation demandes
        $totalRequestsTraitees = RequestModel::whereIn('statut', ['approuve', 'rejete'])->count();
        $tauxApprobation = 0;
        if ($totalRequestsTraitees > 0) {
            $tauxApprobation = round(($requestsApproved / $totalRequestsTraitees) * 100, 1);
        }

        // Évolution mensuelle effectifs (3 derniers mois)
        $evolutionEffectifs = [];
        for ($i = 2; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            // Approximation: agents créés jusqu'à la fin du mois
            $effectif = Agent::where('created_at', '<=', $monthEnd)->count();

            $evolutionEffectifs[] = [
                'mois' => $monthStart->format('M Y'),
                'effectif' => $effectif,
            ];
        }

        $payload = [
            'agents' => [
                'total' => $agentsTotal,
                'actifs' => $agentsActifs,
                'suspendus' => $agentsSuspendus,
                'anciens' => $agentsAnciens,
                'sans_affectation' => $agentsSansAffectation,
                'by_organe' => $agentsByOrgane,
                'by_grade' => $gradesDistribution,
                'by_province' => $agentsByProvince,
                'by_section' => $agentsBySection,
                'by_institution' => $agentsByInstitution,
                'evolution_mensuelle' => $evolutionEffectifs,
            ],
            'requests' => [
                'total' => $requestsTotal,
                'en_attente' => $requestsPending,
                'approuve' => $requestsApproved,
                'rejete' => $requestsRejected,
                'recent_pending' => $recentPendingRequests,
                'taux_approbation' => $tauxApprobation,
            ],
            'attendance' => [
                'today_present' => $todayPresent,
                'today_rate' => $todayRate,
                'monthly_avg_rate' => $avgMonthlyRate,
                'total_active_agents' => $totalActiveAgents,
                'by_organe' => $attendanceByOrgane,
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
                'by_organe' => $planByOrgane,
            ],
            'communiques' => [
                'actifs' => $communiquesActifs,
                'urgents' => $communiquesUrgents,
                'recent' => $recentCommuniques,
            ],
            'documents' => [
                'total' => $documentsTotal,
                'valides' => $documentsValides,
                'expires' => $documentsExpires,
                'rejetes' => $documentsRejetes,
                'by_type' => $documentsByType,
            ],
            // ──────────────────────────────────────────
            // ✨ NOUVELLES DONNÉES - VISION 360° SEN ✨
            // ──────────────────────────────────────────
            'holidays' => [
                'total' => $holidaysTotal,
                'pending' => $holidaysPending,
                'approved' => $holidaysApproved,
                'active_today' => $holidaysActiveToday,
                'agents_en_conge_today' => $agentsEnCongeToday,
                'upcoming_30_days' => $upcomingHolidays,
                'by_organe' => $holidaysByOrgane,
                'taux_utilisation_pct' => $tauxUtilisationConges,
                'planning_stats' => [
                    'total_plannings' => $holidayPlanningStats->total_plannings ?? 0,
                    'total_jours_utilises' => $holidayPlanningStats->total_utilises ?? 0,
                    'total_jours_conge_totaux' => $holidayPlanningStats->total_conge_totaux ?? 0,
                    'avg_jours_utilises' => round($holidayPlanningStats->avg_utilises ?? 0, 1),
                ],
            ],
            'affectations' => [
                'total' => $affectationsTotal,
                'actives' => $affectationsActives,
                'postes_vacants' => $postesVacants,
                'agents_sans_affectation' => $agentsSansAffectation,
                'mobilite_30_jours' => $mobiliteRecente,
                'by_niveau' => $affectationsByNiveau,
            ],
            'audit' => [
                'total' => $auditTotal,
                'last_24h' => $auditLast24h,
                'actions_sensibles' => $actionsSensibles,
                'comptes_geles' => $comptesGeles,
                'connexions_echouees_24h' => $connexionsEchouees,
            ],
            'notifications' => [
                'messages_non_lus' => $messagesNonLus,
                'notifications_prioritaires' => $notificationsNonLues,
            ],
            'formations' => [
                'domaines_etudes' => $domainesEtudes,
            ],
            'geographie' => [
                'provinces_total' => $provincesTotal,
                'provinces_avec_agents' => $provincesAvecAgents,
                'agents_by_province' => $agentsByProvince,
            ],
            'structure' => [
                'sections_total' => $sectionsTotal,
                'departements_total' => $departementsTotal,
                'institutions_total' => $institutionsTotal,
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
        ];

        return $this->success($payload, [], $payload);
    }

    /**
     * Drill-down par organe : détails provinces (SEP) ou départements (SEN/SEL)
     */
    public function organeDetail(Request $request, string $code)
    {
        $code = strtoupper($code);
        $organes = [
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariat Exécutif Local',
        ];

        if (!isset($organes[$code])) {
            return $this->error('Organe invalide', 404);
        }

        $nom = $organes[$code];
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $currentYear = $now->year;

        // Provincial scoping
        $scope = $this->scopeService();
        $user = $request->user();
        $isProvincial = $scope->isProvincialRh($user);
        $userProvinceId = $isProvincial ? $scope->provinceId($user) : null;

        // Agents de cet organe (scoped)
        $agents = Agent::where('organe', $nom);
        if ($userProvinceId) {
            $agents->where('province_id', $userProvinceId);
        }
        $total = (clone $agents)->count();
        $actifs = (clone $agents)->actifs()->count();
        $suspendus = (clone $agents)->suspendu()->count();
        $anciens = (clone $agents)->anciens()->count();

        $items = [];

        if ($code === 'SEP') {
            // Breakdown par province (scoped pour RH Provincial)
            $provQuery = Province::query();
            if ($userProvinceId) {
                $provQuery->where('id', $userProvinceId);
            }
            $provinces = $provQuery->withCount([
                'agents as total' => fn($q) => $q->where('organe', $nom),
                'agents as actifs' => fn($q) => $q->where('organe', $nom)->actifs(),
                'agents as suspendus' => fn($q) => $q->where('organe', $nom)->suspendu(),
            ])->get();

            foreach ($provinces as $prov) {
                $provActifs = $prov->actifs ?: 1;

                // Présence aujourd'hui
                $todayPresent = Pointage::byDate($now->toDateString())
                    ->whereNotNull('heure_entree')
                    ->whereHas('agent', fn($q) => $q->where('organe', $nom)->where('province_id', $prov->id))
                    ->distinct('agent_id')->count('agent_id');

                // Moyenne mensuelle
                $monthly = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
                    ->whereNotNull('heure_entree')
                    ->whereHas('agent', fn($q) => $q->where('organe', $nom)->where('province_id', $prov->id))
                    ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
                    ->groupBy('date_pointage')->get();
                $monthlyRate = $monthly->count() > 0 ? round(($monthly->avg('present') / $provActifs) * 100, 1) : 0;

                // PTA
                $ptaQuery = ActivitePlan::parAnnee($currentYear)->where('province_id', $prov->id);
                $ptaTotal = (clone $ptaQuery)->count();
                $ptaTerminee = (clone $ptaQuery)->terminee()->count();
                $ptaAvg = (clone $ptaQuery)->avg('pourcentage') ?? 0;

                $items[] = [
                    'id' => $prov->id,
                    'nom' => $prov->nom,
                    'code' => $prov->code,
                    'ville_secretariat' => $prov->ville_secretariat,
                    'nom_secretariat_executif' => $prov->nom_secretariat_executif,
                    'effectifs' => ['total' => $prov->total, 'actifs' => $prov->actifs, 'suspendus' => $prov->suspendus],
                    'presence' => [
                        'today_present' => $todayPresent,
                        'today_rate' => round(($todayPresent / $provActifs) * 100, 1),
                        'monthly_rate' => $monthlyRate,
                        'total_active' => $prov->actifs,
                    ],
                    'pta' => ['total' => $ptaTotal, 'terminee' => $ptaTerminee, 'avg' => round($ptaAvg, 0)],
                ];
            }

            // Trier par ordre alphabétique
            usort($items, fn($a, $b) => strcasecmp($a['nom'], $b['nom']));
        } else {
            // SEN / SEL : breakdown par département (scoped pour RH Provincial)
            $deptQuery = Department::query();
            if ($userProvinceId) {
                $deptQuery->where('province_id', $userProvinceId);
            }
            $departments = $deptQuery->withCount([
                'agents as total' => fn($q) => $q->where('organe', $nom),
                'agents as actifs' => fn($q) => $q->where('organe', $nom)->actifs(),
                'agents as suspendus' => fn($q) => $q->where('organe', $nom)->suspendu(),
            ])->get();

            foreach ($departments as $dept) {
                if ($dept->total === 0) continue;

                $deptActifs = $dept->actifs ?: 1;

                $todayPresent = Pointage::byDate($now->toDateString())
                    ->whereNotNull('heure_entree')
                    ->whereHas('agent', fn($q) => $q->where('organe', $nom)->where('departement_id', $dept->id))
                    ->distinct('agent_id')->count('agent_id');

                $monthly = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
                    ->whereNotNull('heure_entree')
                    ->whereHas('agent', fn($q) => $q->where('organe', $nom)->where('departement_id', $dept->id))
                    ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
                    ->groupBy('date_pointage')->get();
                $monthlyRate = $monthly->count() > 0 ? round(($monthly->avg('present') / $deptActifs) * 100, 1) : 0;

                // PTA du département
                $ptaDeptQuery = ActivitePlan::parAnnee($currentYear)->where('departement_id', $dept->id);
                $ptaDeptTotal = (clone $ptaDeptQuery)->count();
                $ptaDeptTerminee = (clone $ptaDeptQuery)->terminee()->count();
                $ptaDeptAvg = (clone $ptaDeptQuery)->avg('pourcentage') ?? 0;

                $items[] = [
                    'id' => $dept->id,
                    'nom' => $dept->nom,
                    'code' => $dept->code,
                    'effectifs' => ['total' => $dept->total, 'actifs' => $dept->actifs, 'suspendus' => $dept->suspendus],
                    'presence' => [
                        'today_present' => $todayPresent,
                        'today_rate' => round(($todayPresent / $deptActifs) * 100, 1),
                        'monthly_rate' => $monthlyRate,
                        'total_active' => $dept->actifs,
                    ],
                    'pta' => ['total' => $ptaDeptTotal, 'terminee' => $ptaDeptTerminee, 'avg' => round($ptaDeptAvg, 0)],
                ];
            }

            usort($items, fn($a, $b) => $b['effectifs']['total'] - $a['effectifs']['total']);
        }

        // PTA global de cet organe (scoped)
        $ptaOrganeQuery = ActivitePlan::parAnnee($currentYear)
            ->where('niveau_administratif', $code === 'SEN' ? 'national' : ($code === 'SEP' ? 'provincial' : 'local'));
        if ($userProvinceId) {
            $ptaOrganeQuery->where('province_id', $userProvinceId);
        }
        $ptaOrganeTotal = (clone $ptaOrganeQuery)->count();
        $ptaOrganeTerminee = (clone $ptaOrganeQuery)->terminee()->count();
        $ptaOrganeEnCours = (clone $ptaOrganeQuery)->enCours()->count();
        $ptaOrganeAvg = (clone $ptaOrganeQuery)->avg('pourcentage') ?? 0;

        // Top activités PTA de l'organe
        $ptaActivites = (clone $ptaOrganeQuery)
            ->orderByDesc('pourcentage')
            ->limit(50)
            ->get(['id', 'titre', 'categorie', 'statut', 'pourcentage', 'trimestre', 'date_debut', 'date_fin', 'departement_id'])
            ->map(fn($a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'categorie' => $a->categorie,
                'statut' => $a->statut,
                'pourcentage' => $a->pourcentage ?? 0,
                'trimestre' => $a->trimestre,
                'date_debut' => $a->date_debut?->format('d/m/Y'),
                'date_fin' => $a->date_fin?->format('d/m/Y'),
                'departement' => $a->departement_id ? optional($a->departement)->nom : null,
            ]);

        return $this->success([
            'organe' => $code,
            'nom' => $nom,
            'type_items' => $code === 'SEP' ? 'provinces' : 'departements',
            'summary' => compact('total', 'actifs', 'suspendus', 'anciens'),
            'items' => $items,
            'pta' => [
                'total' => $ptaOrganeTotal,
                'terminee' => $ptaOrganeTerminee,
                'en_cours' => $ptaOrganeEnCours,
                'avg' => round($ptaOrganeAvg, 0),
            ],
            'activites' => $ptaActivites,
        ]);
    }

    /**
     * Drill-down province : détail complet d'une province
     */
    public function provinceDetail(Request $request, int $id)
    {
        // Provincial scoping: RH Provincial and SEP can only see their own province
        $scope = $this->scopeService();
        $user = $request->user();
        $isProvincial = $scope->isProvinciallyScopedUser($user);
        $userProvinceId = $isProvincial ? $scope->provinceId($user) : null;

        if ($userProvinceId && $userProvinceId !== $id) {
            return $this->error('Acces refuse pour cette province.', 403);
        }

        $province = Province::find($id);
        if (!$province) {
            return $this->error('Province introuvable', 404);
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $currentYear = $now->year;

        $agents = Agent::where('province_id', $id);
        $total = (clone $agents)->count();
        $actifs = (clone $agents)->actifs()->count();
        $suspendus = (clone $agents)->suspendu()->count();
        $anciens = (clone $agents)->anciens()->count();

        // Par organe dans cette province
        $organes = ['sen' => 'Secrétariat Exécutif National', 'sep' => 'Secrétariat Exécutif Provincial', 'sel' => 'Secrétariat Exécutif Local'];
        $byOrgane = [];
        foreach ($organes as $oCode => $oNom) {
            $byOrgane[$oCode] = Agent::where('province_id', $id)->where('organe', $oNom)->actifs()->count();
        }

        // Présence
        $provActifs = $actifs ?: 1;
        $todayPresent = Pointage::byDate($now->toDateString())
            ->whereNotNull('heure_entree')
            ->whereHas('agent', fn($q) => $q->where('province_id', $id))
            ->distinct('agent_id')->count('agent_id');

        $monthly = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
            ->whereNotNull('heure_entree')
            ->whereHas('agent', fn($q) => $q->where('province_id', $id))
            ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
            ->groupBy('date_pointage')->get();
        $monthlyRate = $monthly->count() > 0 ? round(($monthly->avg('present') / $provActifs) * 100, 1) : 0;

        // PTA dans cette province
        $ptaQuery = ActivitePlan::parAnnee($currentYear)->where('province_id', $id);
        $ptaTotal = (clone $ptaQuery)->count();
        $ptaTerminee = (clone $ptaQuery)->terminee()->count();
        $ptaEnCours = (clone $ptaQuery)->enCours()->count();
        $ptaAvg = (clone $ptaQuery)->avg('pourcentage') ?? 0;

        // Départements de la province
        $departments = Department::where('province_id', $id)
            ->withCount([
                'agents as total_agents' => fn($q) => $q,
                'agents as actifs_agents' => fn($q) => $q->actifs(),
            ])
            ->get(['id', 'nom', 'code'])
            ->map(fn($d) => [
                'id' => $d->id,
                'nom' => $d->nom,
                'code' => $d->code,
                'total' => $d->total_agents,
                'actifs' => $d->actifs_agents,
            ]);

        // Top agents (noms/prénoms/fonctions + contact)
        $topAgents = Agent::where('province_id', $id)->actifs()
            ->orderBy('nom')
            ->limit(20)
            ->get(['id', 'nom', 'postnom', 'prenom', 'organe', 'fonction', 'poste_actuel', 'sexe', 'email', 'email_professionnel', 'telephone', 'matricule_etat', 'grade_etat'])
            ->map(fn($a) => [
                'id' => $a->id,
                'nom' => $a->prenom . ' ' . $a->nom,
                'organe' => $a->organe,
                'fonction' => $a->fonction ?? $a->poste_actuel ?? '-',
                'sexe' => $a->sexe,
                'email' => $a->email_professionnel ?: $a->email ?: null,
                'telephone' => $a->telephone,
                'matricule' => $a->matricule_etat,
                'grade' => $a->grade_etat,
            ]);

        // Activités PTA de cette province
        $activites = ActivitePlan::parAnnee($currentYear)
            ->where('province_id', $id)
            ->orderByDesc('pourcentage')
            ->limit(50)
            ->get(['id', 'titre', 'categorie', 'statut', 'pourcentage', 'trimestre', 'date_debut', 'date_fin'])
            ->map(fn($a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'categorie' => $a->categorie,
                'statut' => $a->statut,
                'pourcentage' => $a->pourcentage ?? 0,
                'trimestre' => $a->trimestre,
                'date_debut' => $a->date_debut?->format('d/m/Y'),
                'date_fin' => $a->date_fin?->format('d/m/Y'),
            ]);

        return $this->success([
            'province' => [
                'id' => $province->id,
                'nom' => $province->nom,
                'code' => $province->code,
                'ville_secretariat' => $province->ville_secretariat,
                'nom_gouverneur' => $province->nom_gouverneur,
                'nom_secretariat_executif' => $province->nom_secretariat_executif,
                'email' => $province->email_officiel,
                'telephone' => $province->telephone_officiel,
            ],
            'effectifs' => compact('total', 'actifs', 'suspendus', 'anciens'),
            'by_organe' => $byOrgane,
            'presence' => [
                'today_present' => $todayPresent,
                'today_rate' => round(($todayPresent / $provActifs) * 100, 1),
                'monthly_rate' => $monthlyRate,
                'total_active' => $actifs,
            ],
            'pta' => [
                'total' => $ptaTotal,
                'terminee' => $ptaTerminee,
                'en_cours' => $ptaEnCours,
                'avg' => round($ptaAvg, 0),
            ],
            'departments' => $departments,
            'agents' => $topAgents,
            'activites' => $activites,
        ]);
    }

    /**
     * Drill-down département : détail complet d'un département
     */
    public function departmentDetail(Request $request, int $id)
    {
        $department = Department::find($id);
        if (!$department) {
            return $this->error('Département introuvable', 404);
        }

        // Provincial scoping: RH Provincial and SEP can only see departments in their province
        $scope = $this->scopeService();
        $user = $request->user();
        $isProvincial = $scope->isProvinciallyScopedUser($user);
        $userProvinceId = $isProvincial ? $scope->provinceId($user) : null;

        if ($userProvinceId && (int) $department->province_id !== $userProvinceId) {
            return $this->error('Acces refuse pour ce departement.', 403);
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        $agents = Agent::where('departement_id', $id);
        $total = (clone $agents)->count();
        $actifs = (clone $agents)->actifs()->count();
        $suspendus = (clone $agents)->suspendu()->count();
        $anciens = (clone $agents)->anciens()->count();

        // Présence
        $deptActifs = $actifs ?: 1;
        $todayPresent = Pointage::byDate($now->toDateString())
            ->whereNotNull('heure_entree')
            ->whereHas('agent', fn($q) => $q->where('departement_id', $id))
            ->distinct('agent_id')->count('agent_id');

        $monthly = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
            ->whereNotNull('heure_entree')
            ->whereHas('agent', fn($q) => $q->where('departement_id', $id))
            ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
            ->groupBy('date_pointage')->get();
        $monthlyRate = $monthly->count() > 0 ? round(($monthly->avg('present') / $deptActifs) * 100, 1) : 0;

        // Agents du département
        $topAgents = Agent::where('departement_id', $id)->actifs()
            ->orderBy('nom')
            ->limit(50)
            ->get(['id', 'nom', 'postnom', 'prenom', 'organe', 'fonction', 'poste_actuel', 'sexe', 'email', 'email_professionnel', 'telephone', 'matricule_etat', 'grade_etat'])
            ->map(fn($a) => [
                'id' => $a->id,
                'nom' => $a->prenom . ' ' . $a->nom,
                'organe' => $a->organe,
                'fonction' => $a->fonction ?? $a->poste_actuel ?? '-',
                'sexe' => $a->sexe,
                'email' => $a->email_professionnel ?: $a->email ?: null,
                'telephone' => $a->telephone,
                'matricule' => $a->matricule_etat,
                'grade' => $a->grade_etat,
            ]);

        // PTA du département
        $currentYear = $now->year;
        $ptaQuery = ActivitePlan::parAnnee($currentYear)->where('departement_id', $id);
        $ptaTotal = (clone $ptaQuery)->count();
        $ptaTerminee = (clone $ptaQuery)->terminee()->count();
        $ptaEnCours = (clone $ptaQuery)->enCours()->count();
        $ptaAvg = $ptaTotal > 0 ? round((clone $ptaQuery)->avg('pourcentage'), 1) : 0;

        $activites = ActivitePlan::parAnnee($currentYear)
            ->where('departement_id', $id)
            ->orderByDesc('pourcentage')
            ->limit(50)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'titre' => $a->titre ?? $a->activite ?? '-',
                'pourcentage' => $a->pourcentage ?? 0,
                'statut' => $a->statut ?? '-',
                'categorie' => $a->categorie ?? '-',
                'trimestre' => $a->trimestre ?? '-',
                'date_debut' => $a->date_debut?->format('d/m/Y') ?? '-',
                'date_fin' => $a->date_fin?->format('d/m/Y') ?? '-',
            ]);

        return $this->success([
            'department' => [
                'id' => $department->id,
                'nom' => $department->nom,
                'code' => $department->code,
            ],
            'effectifs' => compact('total', 'actifs', 'suspendus', 'anciens'),
            'presence' => [
                'today_present' => $todayPresent,
                'today_rate' => round(($todayPresent / $deptActifs) * 100, 1),
                'monthly_rate' => $monthlyRate,
                'total_active' => $actifs,
            ],
            'pta' => [
                'total' => $ptaTotal,
                'terminee' => $ptaTerminee,
                'en_cours' => $ptaEnCours,
                'avg' => $ptaAvg,
            ],
            'activites' => $activites,
            'agents' => $topAgents,
        ]);
    }

    /**
     * Dashboard SEP : tableau de bord provincial pour le Secrétaire Exécutif Provincial
     */
    public function sepIndex(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('SEP')) {
            return response()->json(['message' => 'Accès réservé au SEP.'], 403);
        }

        $scope = $this->scopeService();
        $provinceId = $scope->provinceId($user);

        if (!$provinceId) {
            return response()->json(['message' => 'Aucune province associée à cet utilisateur SEP.'], 422);
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $currentYear = $now->year;

        $province = Province::find($provinceId);
        if (!$province) {
            return response()->json(['message' => 'Province introuvable.'], 404);
        }

        // ─── AGENTS ───
        $agents = Agent::where('province_id', $provinceId);
        $agentsTotal = (clone $agents)->count();
        $agentsActifs = (clone $agents)->actifs()->count();
        $agentsSuspendus = (clone $agents)->suspendu()->count();
        $agentsAnciens = (clone $agents)->anciens()->count();

        $organes = [
            'sen' => 'Secrétariat Exécutif National',
            'sep' => 'Secrétariat Exécutif Provincial',
            'sel' => 'Secrétariat Exécutif Local',
        ];
        $byOrgane = [];
        foreach ($organes as $oCode => $oNom) {
            $byOrgane[$oCode] = Agent::where('province_id', $provinceId)->where('organe', $oNom)->actifs()->count();
        }

        // ─── PRÉSENCE ───
        $totalActive = $agentsActifs ?: 1;
        $todayPresent = Pointage::byDate($now->toDateString())
            ->whereNotNull('heure_entree')
            ->whereHas('agent', fn($q) => $q->where('province_id', $provinceId))
            ->distinct('agent_id')->count('agent_id');
        $todayRate = round(($todayPresent / $totalActive) * 100, 1);

        $monthly = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
            ->whereNotNull('heure_entree')
            ->whereHas('agent', fn($q) => $q->where('province_id', $provinceId))
            ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
            ->groupBy('date_pointage')->get();
        $monthlyRate = $monthly->count() > 0 ? round(($monthly->avg('present') / $totalActive) * 100, 1) : 0;

        // ─── DEMANDES ───
        $requestsPending = RequestModel::enAttente()
            ->whereHas('agent', fn($q) => $q->where('province_id', $provinceId))
            ->count();

        // ─── SIGNALEMENTS ───
        $signalementsOuverts = Signalement::ouvert()
            ->whereHas('agent', fn($q) => $q->where('province_id', $provinceId))
            ->count();

        // ─── PLAN DE TRAVAIL ───
        $ptaQuery = ActivitePlan::parAnnee($currentYear)->where('province_id', $provinceId);
        $ptaTotal = (clone $ptaQuery)->count();
        $ptaTerminee = (clone $ptaQuery)->terminee()->count();
        $ptaEnCours = (clone $ptaQuery)->enCours()->count();
        $ptaAvg = $ptaTotal > 0 ? round((clone $ptaQuery)->avg('pourcentage'), 1) : 0;

        // PTA par trimestre
        $ptaByTrimestre = (clone $ptaQuery)
            ->select('trimestre', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN statut = "terminee" THEN 1 ELSE 0 END) as terminee'), DB::raw('AVG(pourcentage) as avg_pourcentage'))
            ->groupBy('trimestre')
            ->orderBy('trimestre')
            ->get()
            ->map(fn($t) => [
                'trimestre' => $t->trimestre,
                'total' => $t->total,
                'terminee' => (int) $t->terminee,
                'avg_pourcentage' => round($t->avg_pourcentage ?? 0, 0),
            ]);

        // ─── DÉPARTEMENTS ───
        $departments = Department::where('province_id', $provinceId)
            ->withCount([
                'agents as total_agents' => fn($q) => $q,
                'agents as actifs_agents' => fn($q) => $q->actifs(),
            ])
            ->get(['id', 'nom', 'code'])
            ->map(fn($d) => [
                'id' => $d->id,
                'nom' => $d->nom,
                'code' => $d->code,
                'total' => $d->total_agents,
                'actifs' => $d->actifs_agents,
            ]);

        // ─── COMMUNIQUÉS ───
        $communiquesActifs = Communique::visibles()->count();

        return $this->success([
            'province' => [
                'id' => $province->id,
                'nom' => $province->nom,
                'code' => $province->code,
                'ville_secretariat' => $province->ville_secretariat,
                'nom_gouverneur' => $province->nom_gouverneur,
                'nom_secretariat_executif' => $province->nom_secretariat_executif,
                'email' => $province->email_officiel,
                'telephone' => $province->telephone_officiel,
            ],
            'agents' => [
                'total' => $agentsTotal,
                'actifs' => $agentsActifs,
                'suspendus' => $agentsSuspendus,
                'anciens' => $agentsAnciens,
                'by_organe' => $byOrgane,
            ],
            'attendance' => [
                'today_present' => $todayPresent,
                'today_rate' => $todayRate,
                'monthly_rate' => $monthlyRate,
                'total_active_agents' => $agentsActifs,
            ],
            'requests' => [
                'en_attente' => $requestsPending,
            ],
            'signalements' => [
                'ouvert' => $signalementsOuverts,
            ],
            'plan_travail' => [
                'total' => $ptaTotal,
                'terminee' => $ptaTerminee,
                'en_cours' => $ptaEnCours,
                'avg_completion' => $ptaAvg,
                'by_trimestre' => $ptaByTrimestre,
            ],
            'departments' => $departments,
            'communiques' => [
                'actifs' => $communiquesActifs,
            ],
        ]);
    }
}
