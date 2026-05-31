<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use App\Models\Tache;
use App\Models\Holiday;
use App\Models\Department;
use App\Models\AgentStatus;
use App\Services\AgentPresenceService;
use App\Services\DemandeWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentDashboardController extends ApiController
{
    private array $departmentFamilyCache = [];

    public function index(Request $request)
    {
        $user     = $request->user();
        // Le département est sur le profil agent (agents.departement_id), pas sur users
        $deptId   = $user->agent?->departement_id ?? $user->departement_id;
        $validatableSteps = app(DemandeWorkflowService::class)->getValidatableSteps($user);
        $isDepartmentWorkflowValidator = in_array('director', $validatableSteps, true);

        if (!$deptId) {
            return response()->json(['message' => 'Aucun département associé à votre compte.'], 403);
        }

        $now          = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // ─── Département info ───────────────────────────────────
        $department = Department::with('province:id,nom')->find($deptId);

        // Seuls les départements fonctionnels (pris en charge) ont un dashboard.
        // Les autres sont conservés pour l'affectation et l'historisation uniquement.
        if (!$department || !$department->pris_en_charge) {
            return response()->json([
                'message' => 'Ce département n\'est pas encore pris en charge par le système.',
                'code'    => 'DEPT_NOT_FUNCTIONAL',
            ], 403);
        }

        $departmentIds = $this->departmentScopeIds((int) $deptId);

        // ─── Agents ────────────────────────────────────────────
        $departmentAgentsQuery = Agent::whereIn('departement_id', $departmentIds)->actifs();
        $agentsActifs = (clone $departmentAgentsQuery)->count();
        $agentsTotal  = $agentsActifs;
        $agentIds     = (clone $departmentAgentsQuery)->pluck('id');
        $presence = app(AgentPresenceService::class);
        $onlineAgentMap = $presence->onlineMap($agentIds->all());
        $recentOnlineAgentMap = $presence->recent($onlineAgentMap);

        // ─── Tâches ────────────────────────────────────────────
        $tachesNouvelle = Tache::whereIn('agent_id', $agentIds)
            ->where('statut', 'nouvelle')->count();
        $tachesEnCours  = Tache::whereIn('agent_id', $agentIds)
            ->where('statut', 'en_cours')->count();
        $tachesTermine  = Tache::whereIn('agent_id', $agentIds)
            ->where('statut', 'terminee')->count();
        $tachesOverdue  = Tache::whereIn('agent_id', $agentIds)
            ->whereNotIn('statut', ['terminee'])
            ->whereNotNull('date_echeance')
            ->where('date_echeance', '<', $now->toDateString())
            ->count();

        // ─── Demandes ──────────────────────────────────────────
        $this->expireOutdatedPendingRequests($agentIds);
        $this->repairPendingRequestWorkflows($agentIds);

        $requestsPendingQuery = RequestModel::whereIn('agent_id', $agentIds)
            ->enAttente();

        if ($isDepartmentWorkflowValidator) {
            $requestsPendingQuery->where('current_step', 'director');
        }

        $requestsPending = (clone $requestsPendingQuery)->count();

        // ─── Présence ──────────────────────────────────────────
        $totalActive   = max($agentsActifs, 1);
        $todayPresent  = Pointage::byDate($now->toDateString())
            ->whereIn('agent_id', $agentIds)
            ->whereNotNull('heure_entree')
            ->distinct('agent_id')->count('agent_id');
        $todayRate     = round(($todayPresent / $totalActive) * 100, 1);

        // Taux mensuel moyen
        $monthlyPointages = Pointage::betweenDates(
            $startOfMonth->toDateString(),
            $now->toDateString()
        )->whereIn('agent_id', $agentIds)
         ->whereNotNull('heure_entree')
         ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
         ->groupBy('date_pointage')->get();

        $monthlyRate = 0;
        if ($monthlyPointages->count() > 0) {
            $monthlyRate = round(($monthlyPointages->avg('present') / $totalActive) * 100, 1);
        }

        // ─── Congés actifs ─────────────────────────────────────
        $activeLeaves = Holiday::whereIn('agent_id', $agentIds)
            ->where('statut_demande', 'approuve')
            ->where('date_debut', '<=', $now->toDateString())
            ->where('date_fin',   '>=', $now->toDateString())
            ->count();

        // ─── Performance équipe ────────────────────────────────
        $teamPerf = Agent::whereIn('departement_id', $departmentIds)
            ->actifs()
            ->with([
                'tachesAssignees' => fn($q) => $q->select('id', 'agent_id', 'statut', 'pourcentage', 'date_echeance'),
            ])
            ->get(['id', 'nom', 'prenom', 'photo', 'fonction'])
            ->map(function ($agent) use ($onlineAgentMap, $now) {
                $taches    = $agent->tachesAssignees;
                $total     = $taches->count();
                $done      = $taches->where('statut', 'terminee')->count();
                $inProgress = $taches->whereIn('statut', ['nouvelle', 'en_cours'])->count();
                $overdue   = $taches->whereNotIn('statut', ['terminee'])
                    ->filter(fn($t) => $t->date_echeance && $t->date_echeance->lt($now))
                    ->count();
                $avgPct    = $total > 0 ? round($taches->avg('pourcentage'), 0) : 0;

                return [
                    'id'            => $agent->id,
                    'nom'           => $agent->nom,
                    'prenom'        => $agent->prenom,
                    'photo'         => $agent->photo,
                    'fonction'      => $agent->fonction,
                    'is_online'     => isset($onlineAgentMap[$agent->id]),
                    'online_label'   => $onlineAgentMap[$agent->id]['label'] ?? null,
                    'taches_total'  => $total,
                    'taches_done'   => $done,
                    'taches_en_cours' => $inProgress,
                    'taches_overdue'  => $overdue,
                    'avg_completion'  => $avgPct,
                ];
            });

        // ─── Tâches récentes ───────────────────────────────────
        $recentTaches = Tache::whereIn('agent_id', $agentIds)
            ->with('agent:id,nom,prenom')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'agent_id', 'titre', 'statut', 'pourcentage', 'date_echeance', 'priorite', 'updated_at']);

        // ─── Demandes en attente ───────────────────────────────
        $pendingRequests = $requestsPendingQuery
            ->with('agent:id,nom,prenom')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'agent_id', 'type', 'description', 'statut', 'current_step', 'created_at']);

        // ─── Mes tâches (tâches de l'agent connecté) ──────────
        $myAgentId = $user->agent?->id;
        $myTasks = $myAgentId
            ? Tache::where('agent_id', $myAgentId)
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get(['id', 'agent_id', 'titre', 'statut', 'pourcentage', 'date_echeance', 'priorite', 'updated_at'])
            : collect();

        // ─── Agenda : tâches du département avec échéance dans 7 jours ──
        $nextWeek = $now->copy()->addDays(7);
        $upcomingDeadlines = Tache::whereIn('agent_id', $agentIds)
            ->whereNotIn('statut', ['terminee'])
            ->whereNotNull('date_echeance')
            ->whereBetween('date_echeance', [$now->toDateString(), $nextWeek->toDateString()])
            ->with('agent:id,nom,prenom')
            ->orderBy('date_echeance')
            ->limit(10)
            ->get(['id', 'agent_id', 'titre', 'statut', 'pourcentage', 'date_echeance', 'priorite']);

        // ─── Liste agents du département ──────────────────────
        $agentList = Agent::whereIn('departement_id', $departmentIds)
            ->actifs()
            ->orderInstitutionally()
            ->get(['id', 'nom', 'prenom', 'fonction', 'photo'])
            ->map(fn($a) => [
                'id'       => $a->id,
                'nom'      => $a->nom,
                'prenom'   => $a->prenom,
                'fonction' => $a->fonction,
                'photo'    => $a->photo,
                'is_online' => isset($onlineAgentMap[$a->id]),
                'online_label' => $onlineAgentMap[$a->id]['label'] ?? null,
            ]);

        return $this->success([
            'department' => [
                'id'       => $department?->id,
                'nom'      => $department?->nom,
                'code'     => $department?->code,
                'province' => $department?->province?->nom,
            ],
            'agents' => [
                'total'  => $agentsTotal,
                'actifs' => $agentsActifs,
                'online' => count($onlineAgentMap),
            ],
            'taches' => [
                'nouvelle'  => $tachesNouvelle,
                'en_cours'  => $tachesEnCours,
                'terminees' => $tachesTermine,
                'overdue'   => $tachesOverdue,
            ],
            'requests' => [
                'en_attente' => $requestsPending,
                'validation_en_attente' => $requestsPending,
            ],
            'attendance' => [
                'today_present'  => $todayPresent,
                'today_rate'     => $todayRate,
                'monthly_rate'   => $monthlyRate,
                'total_actifs'   => $agentsActifs,
            ],
            'conges' => [
                'actifs' => $activeLeaves,
            ],
            'team_performance'   => $teamPerf,
            'recent_taches'      => $recentTaches,
            'pending_requests'   => $pendingRequests,
            'my_tasks'           => $myTasks,
            'upcoming_deadlines' => $upcomingDeadlines,
            'agent_list'         => $agentList,
            'online_agents'      => array_values($onlineAgentMap),
            'recent_online_agents' => array_values($recentOnlineAgentMap),
        ]);
    }

    /**
     * Drill-down agents du département pour le directeur
     * Retourne la liste complète des agents avec statut, présence, tâches
     */
    public function agentsDrill(Request $request)
    {
        $user   = $request->user();
        $deptId = $user->agent?->departement_id ?? $user->departement_id;

        if (!$deptId) {
            return response()->json(['message' => 'Aucun département associé à votre compte.'], 403);
        }

        $now          = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $departmentIds = $this->departmentScopeIds((int) $deptId);
        $agentIds     = Agent::whereIn('departement_id', $departmentIds)->actifs()->pluck('id');

        // Présences du mois par agent
        $monthlyPresence = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
            ->whereIn('agent_id', $agentIds)
            ->whereNotNull('heure_entree')
            ->select('agent_id', DB::raw('COUNT(DISTINCT date_pointage) as jours_presents'))
            ->groupBy('agent_id')
            ->pluck('jours_presents', 'agent_id');

        $todayPointages = Pointage::byDate($now->toDateString())
            ->whereIn('agent_id', $agentIds)
            ->get(['agent_id', 'heure_entree', 'heure_sortie', 'observations'])
            ->keyBy('agent_id');

        $joursOuvrables = max(
            Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
                ->select(DB::raw('COUNT(DISTINCT date_pointage) as jours'))
                ->value('jours'),
            1
        );

        // Statuts actuels
        $statuts = AgentStatus::actuel()
            ->whereIn('agent_id', $agentIds)
            ->get(['agent_id', 'statut'])
            ->keyBy('agent_id');

        // Congés actifs
        $congesActifs = Holiday::whereIn('agent_id', $agentIds)
            ->where('statut_demande', 'approuve')
            ->where('date_debut', '<=', $now->toDateString())
            ->where('date_fin', '>=', $now->toDateString())
            ->pluck('agent_id')
            ->flip();
        $onlineAgentMap = app(AgentPresenceService::class)->onlineMap($agentIds->all());

        $agents = Agent::whereIn('departement_id', $departmentIds)
            ->actifs()
            ->with([
                'tachesAssignees' => fn($q) => $q->select('id', 'agent_id', 'statut', 'pourcentage', 'date_echeance'),
            ])
            ->orderInstitutionally()
            ->get(['id', 'nom', 'prenom', 'photo', 'fonction', 'poste_actuel', 'statut'])
            ->map(function ($agent) use ($monthlyPresence, $todayPointages, $joursOuvrables, $statuts, $congesActifs, $onlineAgentMap, $now) {
                $taches     = $agent->tachesAssignees;
                $total      = $taches->count();
                $done       = $taches->where('statut', 'terminee')->count();
                $inProgress = $taches->whereIn('statut', ['nouvelle', 'en_cours'])->count();
                $overdue    = $taches->whereNotIn('statut', ['terminee'])
                    ->filter(fn($t) => $t->date_echeance && $t->date_echeance->lt($now))
                    ->count();
                $avgPct     = $total > 0 ? round($taches->avg('pourcentage'), 0) : 0;

                $joursPresents = $monthlyPresence[$agent->id] ?? 0;
                $tauxPresence  = round(($joursPresents / $joursOuvrables) * 100, 1);
                $pointage = $todayPointages->get($agent->id);

                $statutActuel = $statuts[$agent->id]?->statut ?? null;
                if (!$statutActuel) {
                    $statutActuel = isset($congesActifs[$agent->id]) ? 'en_conge' : $agent->statut;
                }

                return [
                    'id'              => $agent->id,
                    'nom'             => $agent->nom,
                    'prenom'          => $agent->prenom,
                    'photo'           => $agent->photo,
                    'fonction'        => $agent->poste_actuel ?: $agent->fonction,
                    'statut'          => $statutActuel,
                    'is_online'       => isset($onlineAgentMap[$agent->id]),
                    'online_label'     => $onlineAgentMap[$agent->id]['label'] ?? null,
                    'online_since'     => $onlineAgentMap[$agent->id]['last_activity'] ?? null,
                    'presence_status' => $pointage?->heure_entree ? 'present' : 'absent',
                    'presence_label'  => $pointage?->heure_entree ? 'Présent' : 'Absent',
                    'heure_entree'    => optional($pointage?->heure_entree)->format('H:i'),
                    'heure_sortie'    => optional($pointage?->heure_sortie)->format('H:i'),
                    'pointage_observation' => $pointage?->observations,
                    'taches_total'    => $total,
                    'taches_done'     => $done,
                    'taches_en_cours' => $inProgress,
                    'taches_overdue'  => $overdue,
                    'avg_completion'  => $avgPct,
                    'jours_presents'  => $joursPresents,
                    'taux_presence'   => $tauxPresence,
                ];
            });

        return $this->success([
            'agents'          => $agents,
            'jours_ouvrables' => $joursOuvrables,
            'online_count'    => count($onlineAgentMap),
        ]);
    }

    private function departmentScopeIds(int $departmentId): array
    {
        if (isset($this->departmentFamilyCache[$departmentId])) {
            return $this->departmentFamilyCache[$departmentId];
        }

        $department = Department::find($departmentId);
        if (!$department || $department->province_id) {
            return $this->departmentFamilyCache[$departmentId] = [$departmentId];
        }

        $name = $this->normalizeScopeText($department->nom);
        $matchedGroup = null;
        $bestScore = 0;

        foreach (Department::ACTIVE_NATIONAL_DEPARTMENT_KEYWORDS as $keywordGroup) {
            $score = 0;
            foreach ($keywordGroup as $keyword) {
                if (str_contains($name, $keyword)) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $matchedGroup = $keywordGroup;
            }
        }

        if (!$matchedGroup) {
            return $this->departmentFamilyCache[$departmentId] = [$departmentId];
        }

        $ids = Department::whereNull('province_id')
            ->where(function ($query) use ($matchedGroup) {
                foreach ($matchedGroup as $keyword) {
                    $query->orWhere('nom', 'like', '%' . $keyword . '%');
                }
            })
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (!in_array($departmentId, $ids, true)) {
            $ids[] = $departmentId;
        }

        return $this->departmentFamilyCache[$departmentId] = array_values(array_unique($ids));
    }

    private function repairPendingRequestWorkflows($agentIds): void
    {
        RequestModel::whereIn('agent_id', $agentIds)
            ->enAttente()
            ->whereNull('current_step')
            ->with('agent')
            ->get()
            ->each(fn(RequestModel $request) => app(DemandeWorkflowService::class)->initializeWorkflow($request));
    }

    private function expireOutdatedPendingRequests($agentIds): void
    {
        $today = Carbon::today()->toDateString();

        RequestModel::whereIn('agent_id', $agentIds)
            ->where('statut', 'en_attente')
            ->where(function ($dateQuery) use ($today) {
                $dateQuery
                    ->where(function ($withEndDate) use ($today) {
                        $withEndDate
                            ->whereNotNull('date_fin')
                            ->whereDate('date_fin', '<', $today);
                    })
                    ->orWhere(function ($withoutEndDate) use ($today) {
                        $withoutEndDate
                            ->whereNull('date_fin')
                            ->whereDate('date_debut', '<', $today);
                    });
            })
            ->update([
                'statut' => 'expiré',
                'current_step' => null,
            ]);
    }

    private function normalizeScopeText(?string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim((string) $value));

        return mb_strtolower($ascii !== false ? $ascii : (string) $value);
    }

}
