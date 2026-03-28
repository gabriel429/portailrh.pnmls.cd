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

        // ─── ORGANES mapping ───
        $organes = [
            'sen' => 'Secretariat Executif National',
            'sep' => 'Secretariat Executif Provincial',
            'sel' => 'Secretariat Executif Local',
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
            ->with('agent:id,nom,prenom,organe,fonction_id')
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
        $notificationsNonLues = NotificationPortail::where('lu', false)
            ->where('priorite', 'haute')
            ->count();

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
        // Délai moyen de traitement des demandes
        $avgDelaiTraitement = RequestModel::whereNotNull('date_traitement')
            ->selectRaw('AVG(DATEDIFF(date_traitement, created_at)) as avg_days')
            ->value('avg_days');
        $avgDelaiTraitement = round($avgDelaiTraitement ?? 0, 1);

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

        return response()->json([
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
                'avg_delai_traitement_jours' => $avgDelaiTraitement,
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
        ]);
    }
}
