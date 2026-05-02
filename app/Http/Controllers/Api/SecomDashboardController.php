<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use App\Models\Tache;
use App\Models\Communique;
use App\Models\Document;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use App\Models\Signalement;
use App\Models\ActivitePlan;
use App\Models\Province;
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dashboard SECOM Provincial (SEP/CAF Assistant)
 * 
 * Provides provincial-scoped statistics and tasks for:
 * - Secrétaire Exécutif Provincial (SEP)
 * - Cellule Administration et Finance (CAF) - RH Provincial
 */
class SecomDashboardController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    /**
     * Get the province ID for the current user.
     * Returns null if user is not provincial or doesn't have a province.
     */
    private function getProvinceId(Request $request): ?int
    {
        $user = $request->user();
        $scopeService = $this->scopeService();

        if (!$scopeService->isProvincialUser($user)) {
            return null;
        }

        return $scopeService->provinceId($user);
    }

    /**
     * Main SECOM dashboard endpoint
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $provinceId = $this->getProvinceId($request);

        if (!$provinceId) {
            return $this->error('Utilisateur non provincial ou sans province associée', [], 403);
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $province = Province::find($provinceId);

        // ─── AGENTS STATISTICS ───
        $agentsData = $this->getAgentsStats($provinceId, $startOfMonth);

        // ─── AFFECTATIONS SECOM ───
        $affectations = $this->getSecomAffectations($provinceId);

        // ─── REQUESTS/DEMANDES ───
        $requestsData = $this->getRequestsStats($provinceId, $startOfMonth);

        // ─── ATTENDANCE/PRESENCE ───
        $attendanceData = $this->getAttendanceStats($provinceId, $now, $startOfMonth);

        // ─── MY TASKS (utilisateur connecté) ───
        $myTasks = $this->getMyTasks($user);

        // ─── UPCOMING DEADLINES ───
        $upcomingDeadlines = $this->getUpcomingDeadlines($provinceId, $now);

        // ─── COMMUNIQUES ───
        $communiques = Communique::visibles()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'titre', 'urgence', 'signataire', 'created_at']);

        // ─── HOLIDAY PLANNING ───
        $holidayPlanning = $this->getHolidayPlanning($provinceId, $now, $endOfMonth);

        // ─── SIGNALEMENTS ───
        $signalements = $this->getSignalements($provinceId);

        // ─── RECENT ACTIVITIES ───
        $activities = $this->getRecentActivities($provinceId);

        return response()->json([
            'province' => [
                'id' => $province->id,
                'nom' => $province->nom,
                'code' => $province->code,
                'ville_secretariat' => $province->ville_secretariat,
            ],
            'agents' => $agentsData,
            'affectations' => $affectations,
            'requests' => $requestsData,
            'attendance' => $attendanceData,
            'my_tasks' => $myTasks,
            'upcoming_deadlines' => $upcomingDeadlines,
            'communiques' => $communiques,
            'holiday_planning' => $holidayPlanning,
            'signalements' => $signalements,
            'recent_activities' => $activities,
        ]);
    }

    /**
     * Get agent statistics for the province
     */
    private function getAgentsStats(int $provinceId, Carbon $startOfMonth): array
    {
        $agentQuery = Agent::where('province_id', $provinceId);

        $total = $agentQuery->count();
        $actifs = $agentQuery->actifs()->count();
        $suspendu = $agentQuery->suspendu()->count();
        $anciens = $agentQuery->anciens()->count();

        $bySexe = $agentQuery->actifs()
            ->select('sexe', DB::raw('COUNT(*) as total'))
            ->groupBy('sexe')
            ->pluck('total', 'sexe')
            ->toArray();

        $newThisMonth = $agentQuery->where('created_at', '>=', $startOfMonth)->count();

        // Agent breakdown by organe
        $byOrgane = [];
        foreach (['Secrétariat Exécutif Provincial', 'Secrétariat Exécutif Local'] as $organe) {
            $byOrgane[$organe] = [
                'total' => $agentQuery->where('organe', $organe)->count(),
                'actifs' => $agentQuery->actifs()->where('organe', $organe)->count(),
            ];
        }

        return [
            'total' => $total,
            'actifs' => $actifs,
            'suspendu' => $suspendu,
            'anciens' => $anciens,
            'by_sexe' => $bySexe,
            'by_organe' => $byOrgane,
            'new_this_month' => $newThisMonth,
        ];
    }

    /**
     * Get SECOM (SEP + CAF) affectations for the province
     */
    private function getSecomAffectations(int $provinceId): array
    {
        $affectations = Affectation::where('province_id', $provinceId)
            ->whereIn('niveau_administratif', ['SEP', 'SEL'])
            ->where('actif', true)
            ->with([
                'agent:id,nom,postnom,prenom,email_professionnel,telephone',
                'fonction:id,titre',
                'department:id,nom',
            ])
            ->orderBy('niveau_administratif')
            ->orderBy('date_debut')
            ->get();

        return [
            'total' => $affectations->count(),
            'by_level' => $affectations->groupBy('niveau_administratif_label')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'list' => $affectations->map(fn($a) => [
                'id' => $a->id,
                'agent_name' => trim($a->agent->prenom . ' ' . $a->agent->nom),
                'agent_email' => $a->agent->email_professionnel ?: $a->agent->email,
                'fonction' => $a->fonction->titre ?? 'N/A',
                'niveau' => $a->niveau_administratif_label,
                'department' => $a->department?->nom ?? 'N/A',
                'date_debut' => $a->date_debut?->format('Y-m-d'),
                'date_fin' => $a->date_fin?->format('Y-m-d'),
            ])->values(),
        ];
    }

    /**
     * Get requests/demandes statistics
     */
    private function getRequestsStats(int $provinceId, Carbon $startOfMonth): array
    {
        $requestQuery = RequestModel::whereHas('agent', fn($q) =>
            $q->where('province_id', $provinceId)
        );

        $total = $requestQuery->count();
        $pending = $requestQuery->enAttente()->count();
        $approved = $requestQuery->approuve()->count();
        $rejected = $requestQuery->rejete()->count();

        $byType = $requestQuery->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'type')
            ->toArray();

        $thisMonth = $requestQuery->where('created_at', '>=', $startOfMonth)->count();

        $recent = $requestQuery->with('agent:id,nom,prenom')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'agent_id', 'type', 'statut', 'created_at', 'validated_at_rh', 'validated_at_sep']);

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'by_type' => $byType,
            'this_month' => $thisMonth,
            'recent' => $recent->map(fn($r) => [
                'id' => $r->id,
                'agent' => trim($r->agent->prenom . ' ' . $r->agent->nom),
                'type' => $r->type,
                'statut' => $r->statut,
                'validated_at_rh' => $r->validated_at_rh ? 'Oui' : 'Non',
                'validated_at_sep' => $r->validated_at_sep ? 'Oui' : 'Non',
                'created_at' => $r->created_at->format('Y-m-d H:i'),
            ])->values(),
        ];
    }

    /**
     * Get attendance/presence statistics
     */
    private function getAttendanceStats(int $provinceId, Carbon $now, Carbon $startOfMonth): array
    {
        $activeAgents = Agent::where('province_id', $provinceId)->actifs()->count() ?: 1;

        // Today's presence
        $todayPresent = Pointage::byDate($now->toDateString())
            ->whereHas('agent', fn($q) => $q->where('province_id', $provinceId))
            ->whereNotNull('heure_entree')
            ->distinct('agent_id')
            ->count('agent_id');
        $todayRate = round(($todayPresent / $activeAgents) * 100, 1);

        // Weekly presence
        $weekAgo = $now->copy()->subDays(6);
        $weeklyPresence = Pointage::betweenDates($weekAgo->toDateString(), $now->toDateString())
            ->whereHas('agent', fn($q) => $q->where('province_id', $provinceId))
            ->whereNotNull('heure_entree')
            ->select('date_pointage', DB::raw('COUNT(DISTINCT agent_id) as present'))
            ->groupBy('date_pointage')
            ->orderBy('date_pointage')
            ->get()
            ->map(fn($p) => [
                'date' => $p->date_pointage,
                'present' => $p->present,
                'rate' => round(($p->present / $activeAgents) * 100, 1),
            ]);

        // Monthly average
        $monthlyPointages = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
            ->whereHas('agent', fn($q) => $q->where('province_id', $provinceId))
            ->whereNotNull('heure_entree')
            ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
            ->groupBy('date_pointage')
            ->get();

        $monthlyRate = $monthlyPointages->isNotEmpty()
            ? round($monthlyPointages->avg('present') / $activeAgents * 100, 1)
            : 0;

        return [
            'total_active_agents' => $activeAgents,
            'today_present' => $todayPresent,
            'today_rate' => $todayRate,
            'weekly_presence' => $weeklyPresence->values(),
            'monthly_rate' => $monthlyRate,
        ];
    }

    /**
     * Get user's own tasks
     */
    private function getMyTasks($user): array
    {
        $agentId = $user->agent?->id;

        if (!$agentId) {
            return [];
        }

        $tasks = Tache::where('agent_id', $agentId)
            ->orderByDesc('priorite')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'titre', 'statut', 'pourcentage', 'date_echeance', 'priorite', 'updated_at']);

        return $tasks->map(fn($t) => [
            'id' => $t->id,
            'titre' => $t->titre,
            'statut' => $t->statut,
            'pourcentage' => $t->pourcentage,
            'date_echeance' => $t->date_echeance?->format('Y-m-d'),
            'priorite' => $t->priorite,
            'is_overdue' => $t->date_echeance && $t->date_echeance->isPast() && $t->statut !== 'terminee',
        ])->values()->toArray();
    }

    /**
     * Get upcoming deadlines for provincial tasks
     */
    private function getUpcomingDeadlines(int $provinceId, Carbon $now): array
    {
        $nextWeek = $now->copy()->addDays(7);

        $deadlines = Tache::whereHas('agent', fn($q) =>
            $q->where('province_id', $provinceId)
        )
            ->whereNotIn('statut', ['terminee', 'annulee'])
            ->whereNotNull('date_echeance')
            ->whereBetween('date_echeance', [$now->toDateString(), $nextWeek->toDateString()])
            ->with('agent:id,nom,prenom')
            ->orderBy('date_echeance')
            ->orderBy('priorite')
            ->limit(10)
            ->get(['id', 'agent_id', 'titre', 'statut', 'date_echeance', 'priorite']);

        return $deadlines->map(fn($t) => [
            'id' => $t->id,
            'titre' => $t->titre,
            'agent' => trim($t->agent->prenom . ' ' . $t->agent->nom),
            'date_echeance' => $t->date_echeance->format('Y-m-d'),
            'priorite' => $t->priorite,
            'statut' => $t->statut,
        ])->values()->toArray();
    }

    /**
     * Get holiday planning for the current and next months
     */
    private function getHolidayPlanning(int $provinceId, Carbon $now, Carbon $endOfMonth): array
    {
        $holidays = Holiday::whereHas('agent', fn($q) =>
            $q->where('province_id', $provinceId)
        )
            ->whereBetween('date_debut', [$now->startOfMonth()->toDateString(), $endOfMonth->toDateString()])
            ->orderBy('date_debut')
            ->limit(10)
            ->with('agent:id,nom,prenom')
            ->get(['id', 'agent_id', 'type', 'date_debut', 'date_fin', 'statut']);

        return [
            'total' => $holidays->count(),
            'list' => $holidays->map(fn($h) => [
                'id' => $h->id,
                'agent' => trim($h->agent->prenom . ' ' . $h->agent->nom),
                'type' => $h->type,
                'date_debut' => $h->date_debut->format('Y-m-d'),
                'date_fin' => $h->date_fin->format('Y-m-d'),
                'statut' => $h->statut,
            ])->values(),
        ];
    }

    /**
     * Get signalements (abuse reports)
     */
    private function getSignalements(int $provinceId): array
    {
        $signalements = Signalement::whereHas('agent', fn($q) =>
            $q->where('province_id', $provinceId)
        )
            ->whereNotIn('statut', ['ferme', 'clos', 'archive'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'type', 'statut', 'severite', 'is_anonymous', 'created_at']);

        return [
            'total' => $signalements->count(),
            'by_severity' => $signalements->groupBy('severite')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'list' => $signalements->map(fn($s) => [
                'id' => $s->id,
                'type' => $s->type,
                'statut' => $s->statut,
                'severite' => $s->severite,
                'is_anonymous' => $s->is_anonymous,
                'created_at' => $s->created_at->format('Y-m-d H:i'),
            ])->values(),
        ];
    }

    /**
     * Get recent activities (requests + documents)
     */
    private function getRecentActivities(int $provinceId): array
    {
        $recentRequests = RequestModel::whereHas('agent', fn($q) =>
            $q->where('province_id', $provinceId)
        )
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'type', 'statut', 'updated_at'])
            ->map(fn($r) => [
                'type' => 'demande',
                'description' => 'Demande de ' . $r->type . ' — ' . $r->statut,
                'link' => '/requests/' . $r->id,
                'created_at' => $r->updated_at,
            ]);

        $recentDocuments = Document::whereHas('agent', fn($q) =>
            $q->where('province_id', $provinceId)
        )
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id', 'name', 'type', 'created_at'])
            ->map(fn($d) => [
                'type' => 'document',
                'description' => 'Document ajouté : ' . $d->name,
                'link' => '/documents',
                'created_at' => $d->created_at,
            ]);

        $activities = $recentRequests->concat($recentDocuments)
            ->sortByDesc('created_at')
            ->values()
            ->take(6);

        return $activities->map(fn($a) => [
            'type' => $a['type'],
            'description' => $a['description'],
            'link' => $a['link'],
            'created_at' => $a['created_at']->format('Y-m-d H:i'),
        ])->toArray();
    }
}
