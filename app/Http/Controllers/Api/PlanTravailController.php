<?php

namespace App\Http\Controllers\Api;

use App\Events\PtaModified;
use App\Http\Resources\ActivitePlanResource;
use App\Models\ActivitePlan;
use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Department;
use App\Models\Province;
use App\Models\Localite;
use App\Services\NotificationService;
use App\Services\UserDataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PlanTravailController extends ApiController
{
    private array $departmentFamilyCache = [];

    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    /**
     * Returns true if the authenticated user has a Section Planification role.
     */
    private function isPlanificationRole(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        $role = $user->role?->nom_role ?? '';
        return in_array($role, ['Chef Section Planification', 'Cellule Planification']);
    }

    private function isPtaAdminContext(): bool
    {
        return request()->boolean('admin_pta');
    }

    private function canUsePtaAdminContext(): bool
    {
        $user = auth()->user();

        return $user
            && ($this->scopeService()->hasGlobalAdminAccess($user) || $this->isPlanificationRole())
            && $this->isPtaAdminContext();
    }

    private function getScopedAgent(): ?Agent
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        if ($this->canUsePtaAdminContext()) {
            return null;
        }

        $agent = $user->agent;

        if ($this->isPlanificationNormalPtaUser($agent)) {
            return $agent;
        }

        if ($this->scopeService()->hasGlobalAdminAccess($user)) {
            return null;
        }

        return $agent;
    }

    private function isPlanificationNormalPtaUser(?Agent $agent): bool
    {
        if ($this->canUsePtaAdminContext()) {
            return false;
        }

        if ($this->isPlanificationRole()) {
            return true;
        }

        if (!$agent) {
            return false;
        }

        return str_contains($this->getNomFonctionAgent($agent), 'planification');
    }

    private function applyOwnDepartmentOnlyScope(Builder $query, Agent $agent): Builder
    {
        if (!$agent->departement_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('departement_id', (int) $agent->departement_id);
    }

    private function normalizeScopeText(?string $value): string
    {
        $value = trim((string) $value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return mb_strtolower($ascii !== false ? $ascii : $value);
    }

    private function isProvincialAgent(?Agent $agent): bool
    {
        return str_contains($this->normalizeScopeText($agent?->organe), 'provincial');
    }

    private function applyProvinceScope(Builder $query, Agent $agent): Builder
    {
        if (!$agent->province_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('niveau_administratif', 'SEP')
            ->where(function (Builder $provinceQuery) use ($agent) {
                $provinceQuery
                    ->where('province_id', $agent->province_id)
                    ->orWhereHas('provinces', function (Builder $relationQuery) use ($agent) {
                        $relationQuery->where('provinces.id', $agent->province_id);
                    });
            });
    }

    private function applyScopedAccess(Builder $query, ?Agent $agent): Builder
    {
        if (!$agent) {
            return $query;
        }

        if ($this->isPlanificationNormalPtaUser($agent)) {
            return $this->applyOwnDepartmentOnlyScope($query, $agent);
        }

        return $query->where(function (Builder $accessQuery) use ($agent) {
            $accessQuery->whereHas('agents', fn (Builder $assignedQuery) => $assignedQuery->where('agents.id', $agent->id));

            if ($this->isProvincialAgent($agent)) {
                if (!$agent->province_id) {
                    return;
                }

                $accessQuery->orWhere(function (Builder $provinceAccess) use ($agent) {
                    $provinceAccess
                        ->where('niveau_administratif', 'SEP')
                        ->where(function (Builder $provinceQuery) use ($agent) {
                            $provinceQuery
                                ->where('province_id', $agent->province_id)
                                ->orWhereHas('provinces', function (Builder $relationQuery) use ($agent) {
                                    $relationQuery->where('provinces.id', $agent->province_id);
                                });
                        });
                });

                return;
            }

            if (!$agent->departement_id) {
                return;
            }

            $familyIds = $this->getActiveDepartmentFamily($agent->departement_id);

            $accessQuery->orWhere(function (Builder $departmentAccess) use ($familyIds) {
                count($familyIds) === 1
                    ? $departmentAccess->where('departement_id', $familyIds[0])
                    : $departmentAccess->whereIn('departement_id', $familyIds);
            });
        });
    }

    private function applyDepartmentScope(Builder $query, ?Agent $agent): Builder
    {
        if (!$agent) {
            return $query;
        }

        if (!$agent->departement_id) {
            return $query->whereRaw('1 = 0');
        }

        $familyIds = $this->getActiveDepartmentFamily($agent->departement_id);

        return count($familyIds) === 1
            ? $query->where('departement_id', $familyIds[0])
            : $query->whereIn('departement_id', $familyIds);
    }

    /**
     * Get all department IDs belonging to the same active-department family.
     *
     * The 5 active PNMLS departments are identified by
     * Department::ACTIVE_NATIONAL_DEPARTMENT_KEYWORDS.  Tracking
     * sub-departments (e.g. SE, PRC) share keywords with their parent
     * active department.  This method groups them together so that PTA
     * scoping includes the whole family.
     */
    private function getActiveDepartmentFamily(int $departmentId): array
    {
        if (isset($this->departmentFamilyCache[$departmentId])) {
            return $this->departmentFamilyCache[$departmentId];
        }

        $department = Department::find($departmentId);
        if (!$department || $department->province_id) {
            return $this->departmentFamilyCache[$departmentId] = [$departmentId];
        }

        $nomNormalized = $this->normalizeScopeText($department->nom);

        $matchedGroup = null;
        $bestScore = 0;

        foreach (Department::ACTIVE_NATIONAL_DEPARTMENT_KEYWORDS as $keywordGroup) {
            $score = 0;
            foreach ($keywordGroup as $keyword) {
                if (str_contains($nomNormalized, $keyword)) {
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

        $familyIds = Department::whereNull('province_id')
            ->where(function ($query) use ($matchedGroup) {
                foreach ($matchedGroup as $keyword) {
                    $query->orWhere('nom', 'like', '%' . $keyword . '%');
                }
            })
            ->pluck('id')
            ->toArray();

        if (!in_array($departmentId, $familyIds)) {
            $familyIds[] = $departmentId;
        }

        return $this->departmentFamilyCache[$departmentId] = $familyIds;
    }

    private function canAccessActivity(ActivitePlan $activite): bool
    {
        $agent = $this->getScopedAgent();

        if (!$agent) {
            return true;
        }

        if ($this->isPlanificationNormalPtaUser($agent)) {
            return $agent->departement_id
                && (int) $activite->departement_id === (int) $agent->departement_id;
        }

        if ($activite->relationLoaded('agents')) {
            if ($activite->agents->contains('id', $agent->id)) {
                return true;
            }
        } elseif ($activite->agents()->where('agents.id', $agent->id)->exists()) {
            return true;
        }

        if ($this->isProvincialAgent($agent)) {
            return (bool) $agent->province_id
                && $activite->niveau_administratif === 'SEP'
                && $this->activityTargetsProvince($activite, (int) $agent->province_id);
        }

        $familyIds = $this->getActiveDepartmentFamily((int) $agent->departement_id);

        return in_array((int) $activite->departement_id, $familyIds);
    }

    private function enforceManagedScope(array $validated, ?Agent $agent): array
    {
        if (!$agent) {
            return $validated;
        }

        if ($this->isProvincialAgent($agent)) {
            if (!$agent->province_id) {
                abort(403, 'Aucune province associee a cet agent.');
            }

            if (($validated['niveau_administratif'] ?? null) !== 'SEP') {
                abort(403, 'Acces refuse pour ce niveau administratif.');
            }

            $validated['departement_id'] = null;
            $validated['localite_id'] = null;
            $validated['province_id'] = (int) $agent->province_id;
            $validated['province_ids'] = [(int) $agent->province_id];

            return $validated;
        }

        if (!$agent->departement_id) {
            abort(403, 'Aucun departement associe a cet agent.');
        }

        $familyIds = $this->getActiveDepartmentFamily((int) $agent->departement_id);

        if (!empty($validated['departement_id']) && !in_array((int) $validated['departement_id'], $familyIds)) {
            abort(403, 'Acces refuse pour ce departement.');
        }

        $validated['departement_id'] = (int) $agent->departement_id;

        return $validated;
    }

    /**
     * Check if user can manage PTA (create, edit, delete).
     */
    private function canManage(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($this->scopeService()->hasGlobalAdminAccess($user)) {
            return true;
        }

        if ($this->canUsePtaAdminContext()) {
            return true;
        }

        $agent = $user->agent;
        if (!$agent) {
            return false;
        }

        $nomFonction = $this->getNomFonctionAgent($agent);
        $organe = $agent->organe ?? '';

        if (str_contains($organe, 'National') && str_contains($nomFonction, 'planification')) {
            return true;
        }
        if (str_contains($organe, 'Provincial') && str_contains($nomFonction, 'planification')) {
            return true;
        }
        if (str_contains($organe, 'Local') && str_contains($nomFonction, 'assistant technique')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can update the status of a PTA activity.
     */
    private function canUpdateStatut(ActivitePlan $activite): bool
    {
        if (!$this->canAccessActivity($activite)) {
            return false;
        }

        $user = auth()->user();
        if ($this->scopeService()->hasGlobalAdminAccess($user)) {
            return true;
        }
        if ($this->canManage()) {
            return true;
        }

        $agent = $user->agent;
        if (!$agent) {
            return false;
        }

        $nomFonction = $this->getNomFonctionAgent($agent);
        $organe = $agent->organe ?? '';

        // SEN level
        if (str_contains($organe, 'National') && $activite->niveau_administratif === 'SEN') {
            if (str_contains($nomFonction, 'directeur') || str_contains($nomFonction, 'chef de département')) {
                if ($agent->departement_id && in_array((int) $activite->departement_id, $this->getActiveDepartmentFamily((int) $agent->departement_id))) {
                    return true;
                }
            }
            if (str_contains($nomFonction, 'assistant de département') || str_contains($nomFonction, 'secrétaire de département')) {
                if ($agent->departement_id && in_array((int) $activite->departement_id, $this->getActiveDepartmentFamily((int) $agent->departement_id))) {
                    return true;
                }
            }
            if ((str_contains($nomFonction, 'assistant') && str_contains($nomFonction, 'direction'))
                || str_contains($nomFonction, 'secrétaire de direction')) {
                if (!$activite->departement_id) {
                    return true;
                }
            }
        }

        // SEP level
        if (str_contains($organe, 'Provincial') && $activite->niveau_administratif === 'SEP') {
            if ($agent->province_id && $this->activityTargetsProvince($activite, (int) $agent->province_id)) {
                if (str_contains($nomFonction, 'secrétaire exécutif provincial') || str_contains($nomFonction, 'sep')) {
                    return true;
                }
                if (str_contains($nomFonction, 'planification')) {
                    return true;
                }
            }
        }

        // SEL level
        if (str_contains($organe, 'Local') && $activite->niveau_administratif === 'SEL') {
            if (str_contains($nomFonction, 'assistant technique')) {
                return true;
            }
        }

        return false;
    }

    private function getNomFonctionAgent(Agent $agent): string
    {
        if (Schema::hasTable('affectations') && Schema::hasTable('fonctions')) {
            $affectationActive = Affectation::where('agent_id', $agent->id)
                ->where('actif', true)
                ->with('fonction')
                ->first();

            if ($affectationActive && $affectationActive->fonction) {
                return mb_strtolower($affectationActive->fonction->nom);
            }
        }

        return mb_strtolower($agent->fonction ?? '');
    }

    private function formatAssignableAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'nom_complet' => trim($agent->prenom . ' ' . $agent->nom),
            'organe' => $agent->organe,
            'fonction' => $agent->fonction,
            'departement_id' => $agent->departement_id,
        ];
    }

    private function ptaSenAttacheAgents()
    {
        return Agent::actifs()
            ->whereNull('departement_id')
            ->where(function (Builder $query) {
                $query
                    ->where('organe', 'like', '%National%')
                    ->orWhere('organe', 'like', '%SEN%');
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'organe', 'fonction', 'departement_id'])
            ->map(fn (Agent $agent) => $this->formatAssignableAgent($agent))
            ->values();
    }

    private function ptaDepartmentAgents(array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        return Agent::actifs()
            ->whereIn('departement_id', $departmentIds)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'organe', 'fonction', 'departement_id'])
            ->groupBy('departement_id')
            ->map(fn ($agents) => $agents->map(fn (Agent $agent) => $this->formatAssignableAgent($agent))->values()->all())
            ->all();
    }

    private function assertAssignedAgentsMatchTarget(array $validated, array $assignedAgentIds): array
    {
        $assignmentTarget = $validated['assignment_target'] ?? null;
        unset($validated['assignment_target']);

        if (!$this->canUsePtaAdminContext() || ($assignedAgentIds === [] && !$assignmentTarget)) {
            return $validated;
        }

        $allowedAgentIds = [];

        if ($assignmentTarget === 'sen_attaches') {
            $validated['niveau_administratif'] = 'SEN';
            $validated['departement_id'] = null;
            $validated['responsable_code'] = $validated['responsable_code'] ?? 'Attaches SEN';
            $allowedAgentIds = $this->ptaSenAttacheAgents()->pluck('id')->map(fn ($id) => (int) $id)->all();
        } elseif (str_starts_with((string) $assignmentTarget, 'department:')) {
            $departmentId = (int) substr((string) $assignmentTarget, strlen('department:'));
            $validated['niveau_administratif'] = 'SEN';
            $validated['departement_id'] = $departmentId;
            $allowedAgentIds = Agent::actifs()
                ->where('departement_id', $departmentId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } elseif (!empty($validated['departement_id'])) {
            $departmentId = (int) $validated['departement_id'];
            $allowedAgentIds = Agent::actifs()
                ->where('departement_id', $departmentId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            throw ValidationException::withMessages([
                'assignment_target' => ['Choisissez d abord un departement ou les attaches du SEN.'],
            ]);
        }

        $invalidAgentIds = array_diff(array_map('intval', $assignedAgentIds), $allowedAgentIds);

        if ($invalidAgentIds !== []) {
            throw ValidationException::withMessages([
                'assigned_agent_ids' => ['Un agent selectionne ne correspond pas a la cible choisie.'],
            ]);
        }

        return $validated;
    }

    /**
     * Display listing of PTA activities.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $agent = $this->getScopedAgent();
        $annee = $request->input('annee', now()->year);
        $trimestre = $request->input('trimestre');
        $statut = $request->input('statut');
        $retard = $request->boolean('retard');

        $departementId = $request->input('departement_id');
        $provinceId    = $request->input('province_id');
        $niveauFilter  = $request->input('niveau_administratif');

        $query = ActivitePlan::with('createur', 'departement', 'province', 'localite')
            ->with('provinces')
            ->with('agents')
            ->parAnnee($annee);

        $this->applyScopedAccess($query, $agent);

        // Extra filters available for planification / global users.
        if ($departementId && !$agent) {
            $query->where('departement_id', (int) $departementId);
        }
        if ($provinceId && !$agent) {
            $query->where(function (Builder $q) use ($provinceId) {
                $q->where('province_id', (int) $provinceId)
                  ->orWhereHas('provinces', fn (Builder $r) => $r->where('provinces.id', (int) $provinceId));
            });
        }
        if ($niveauFilter && !$agent) {
            $query->where('niveau_administratif', $niveauFilter);
        }

        if ($trimestre) {
            $query->parTrimestre($trimestre);
        }

        if ($retard) {
            $query->whereNotNull('date_fin')
                ->whereDate('date_fin', '<', now()->toDateString())
                ->whereNotIn('statut', ['terminee', 'annulee']);
        } elseif ($statut) {
            $query->where('statut', $statut);
        }

        $activites = $query->orderByRaw("FIELD(trimestre, 'T1','T2','T3','T4')")->latest()->get();

        $totalCount = $activites->count();
        $planifieeCount = $activites->where('statut', 'planifiee')->count();
        $enCoursCount = $activites->where('statut', 'en_cours')->count();
        $termineeCount = $activites->where('statut', 'terminee')->count();
        $annuleeCount = $activites->where('statut', 'annulee')->count();
        $enRetardCount = $activites
            ->filter(fn (ActivitePlan $activity) => $this->isPtaActivityOverdue($activity, now()->startOfDay()))
            ->count();
        $avgPourcentage = $totalCount > 0 ? round($activites->avg('pourcentage')) : 0;

        $resources = ActivitePlanResource::collection($activites)->resolve();
        $activitesGroupees = collect($resources)->groupBy(fn($a) => $a['trimestre'] ?? 'Annuel')->toArray();

        $isGlobalPta = !$agent;
        $filterOptions = $isGlobalPta ? [
            'departments' => Department::operational()->orderBy('nom')->get(['id', 'nom']),
            'provinces'   => Province::orderBy('nom')->get(['id', 'nom']),
        ] : null;

        return $this->success($resources, [
            'stats' => [
                'total' => $totalCount,
                'planifiee' => $planifieeCount,
                'en_cours' => $enCoursCount,
                'terminee' => $termineeCount,
                'annulee' => $annuleeCount,
                'en_retard' => $enRetardCount,
                'avg_pourcentage' => $avgPourcentage,
            ],
            'filters' => [
                'annee' => (int) $annee,
                'trimestre' => $trimestre,
                'statut' => $statut,
                'retard' => $retard,
            ],
            'canEdit' => $this->canManage(),
            'isGlobalPta' => $isGlobalPta,
            'isPtaAdmin' => $this->canUsePtaAdminContext(),
            'filterOptions' => $filterOptions,
        ], [
            'groupees' => $activitesGroupees,
            'stats' => [
                'total' => $totalCount,
                'planifiee' => $planifieeCount,
                'en_cours' => $enCoursCount,
                'terminee' => $termineeCount,
                'annulee' => $annuleeCount,
                'en_retard' => $enRetardCount,
                'avg_pourcentage' => $avgPourcentage,
            ],
            'filters' => [
                'annee' => (int) $annee,
                'trimestre' => $trimestre,
                'statut' => $statut,
                'retard' => $retard,
            ],
            'canEdit' => $this->canManage(),
            'isGlobalPta' => $isGlobalPta,
            'isPtaAdmin' => $this->canUsePtaAdminContext(),
            'filterOptions' => $filterOptions,
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        if (!$this->canUsePtaAdminContext()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $annee = (int) $request->input('annee', now()->year);
        $base = ActivitePlan::with('departement', 'agents', 'province', 'provinces')->parAnnee($annee);
        $activities = $base->get();
        $today = now()->startOfDay();

        $total = $activities->count();
        $byStatus = $activities->groupBy('statut')->map->count();
        $overdue = $activities
            ->filter(fn (ActivitePlan $activity) => $this->isPtaActivityOverdue($activity, $today))
            ->count();

        $byDepartment = $activities
            ->mapToGroups(function (ActivitePlan $activity) {
                $group = $this->ptaDashboardDepartmentGroup($activity);

                return [$group['key'] => array_merge($group, ['activity' => $activity])];
            })
            ->map(function ($rows) use ($today) {
                $first = $rows->first();

                return $this->formatPtaDashboardGroup(
                    $rows->pluck('activity'),
                    $first['label'],
                    $first['type'],
                    $first['id'],
                    $today
                );
            })
            ->sortByDesc('total')
            ->values();

        $byProvince = $activities
            ->flatMap(function (ActivitePlan $activity) {
                return $this->ptaDashboardProvinceRows($activity);
            })
            ->groupBy('key')
            ->map(function ($rows) use ($today) {
                $first = $rows->first();

                return $this->formatPtaDashboardGroup(
                    $rows->pluck('activity'),
                    $first['label'],
                    'province',
                    $first['id'],
                    $today
                );
            })
            ->sortByDesc('total')
            ->values();

        $byAgent = $activities
            ->flatMap(fn (ActivitePlan $activity) => $activity->agents->map(fn (Agent $agent) => [
                'id' => $agent->id,
                'label' => trim($agent->prenom . ' ' . $agent->nom),
                'statut' => $activity->statut,
                'pourcentage' => $activity->pourcentage,
            ]))
            ->groupBy('id')
            ->map(fn ($items) => [
                'label' => $items->first()['label'] ?: 'Agent non renseigne',
                'total' => $items->count(),
                'terminee' => $items->where('statut', 'terminee')->count(),
                'avg_pourcentage' => $items->count() ? round($items->avg('pourcentage')) : 0,
            ])
            ->sortByDesc('total')
            ->take(12)
            ->values();

        $byTrimestre = collect(['T1', 'T2', 'T3', 'T4'])->map(fn ($trimestre) => [
            'label' => $trimestre,
            'total' => (clone $base)->parTrimestre($trimestre)->count(),
        ]);

        $payload = [
            'annee' => $annee,
            'summary' => [
                'total' => $total,
                'planifiee' => (int) ($byStatus['planifiee'] ?? 0),
                'en_cours' => (int) ($byStatus['en_cours'] ?? 0),
                'terminee' => (int) ($byStatus['terminee'] ?? 0),
                'annulee' => (int) ($byStatus['annulee'] ?? 0),
                'en_retard' => $overdue,
                'avg_pourcentage' => $total ? round($activities->avg('pourcentage')) : 0,
            ],
            'by_department' => $byDepartment,
            'by_province' => $byProvince,
            'by_agent' => $byAgent,
            'by_trimestre' => $byTrimestre,
        ];

        return $this->success($payload, [], $payload);
    }

    private function ptaDashboardDepartmentGroup(ActivitePlan $activity): array
    {
        if ($activity->departement) {
            return [
                'key' => 'department:' . $activity->departement->id,
                'id' => $activity->departement->id,
                'type' => $activity->departement->province_id ? 'department' : 'sen_service',
                'label' => $activity->departement->nom,
            ];
        }

        if (str_contains(strtolower((string) $activity->responsable_code), 'attache')) {
            return [
                'key' => 'sen_attaches',
                'id' => null,
                'type' => 'sen_attaches',
                'label' => 'Attaches du SEN',
            ];
        }

        return [
            'key' => 'sen_unassigned',
            'id' => null,
            'type' => 'sen_service',
            'label' => 'Direction / Non renseigne',
        ];
    }

    private function ptaDashboardProvinceRows(ActivitePlan $activity): array
    {
        $provinces = $activity->provinces->isNotEmpty()
            ? $activity->provinces
            : collect($activity->province ? [$activity->province] : []);

        if ($provinces->isEmpty()) {
            return $activity->niveau_administratif === 'SEP'
                ? [[
                    'key' => 'province:0',
                    'id' => 0,
                    'label' => 'Province non renseignee',
                    'activity' => $activity,
                ]]
                : [];
        }

        return $provinces->map(fn (Province $province) => [
            'key' => 'province:' . $province->id,
            'id' => $province->id,
            'label' => $province->nom,
            'activity' => $activity,
        ])->all();
    }

    private function formatPtaDashboardGroup($activities, string $label, string $type, ?int $id, $today): array
    {
        $items = collect($activities)->values();
        $total = $items->count();
        $agents = $items
            ->flatMap(fn (ActivitePlan $activity) => $activity->agents)
            ->filter()
            ->unique('id')
            ->sortBy(fn (Agent $agent) => trim($agent->nom . ' ' . $agent->prenom))
            ->values();

        return [
            'id' => $id,
            'type' => $type,
            'label' => $label ?: 'Non renseigne',
            'total' => $total,
            'planifiee' => $items->where('statut', 'planifiee')->count(),
            'en_cours' => $items->where('statut', 'en_cours')->count(),
            'terminee' => $items->where('statut', 'terminee')->count(),
            'annulee' => $items->where('statut', 'annulee')->count(),
            'en_retard' => $items->filter(fn (ActivitePlan $activity) => $this->isPtaActivityOverdue($activity, $today))->count(),
            'avg_pourcentage' => $total ? round($items->avg('pourcentage')) : 0,
            'agent_count' => $agents->count(),
            'agents' => $agents->take(10)->map(fn (Agent $agent) => [
                'id' => $agent->id,
                'nom_complet' => trim($agent->prenom . ' ' . $agent->nom),
                'fonction' => $agent->fonction,
                'organe' => $agent->organe,
            ])->values(),
            'latest_updates' => $items
                ->sortByDesc(fn (ActivitePlan $activity) => $activity->updated_at)
                ->take(5)
                ->map(fn (ActivitePlan $activity) => [
                    'id' => $activity->id,
                    'titre' => $activity->titre,
                    'statut' => $activity->statut,
                    'pourcentage' => $activity->pourcentage,
                    'updated_at' => optional($activity->updated_at)?->toIso8601String(),
                ])
                ->values(),
        ];
    }

    private function isPtaActivityOverdue(ActivitePlan $activity, $today): bool
    {
        return (bool) $activity->date_fin
            && $activity->date_fin->lt($today)
            && !in_array($activity->statut, ['terminee', 'annulee'], true);
    }

    /**
     * Return data for the create form.
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent = $this->getScopedAgent();

        $departments = Department::query()
            ->when($agent?->departement_id, fn (Builder $query) => $query->whereIn('id', $this->getActiveDepartmentFamily($agent->departement_id)))
            ->when(!$agent?->departement_id, fn (Builder $query) => $query->operational())
            ->orderBy('nom')
            ->get(['id', 'nom']);
        $provinces = Province::query()
            ->when($this->isProvincialAgent($agent) && $agent?->province_id, fn (Builder $query) => $query->where('id', $agent->province_id))
            ->orderBy('nom')
            ->get(['id', 'nom']);
        $localites = class_exists(Localite::class) ? Localite::orderBy('nom')->get(['id', 'nom']) : collect();

        $agentsSen = $this->isPlanificationRole()
            ? Agent::where(function ($q) {
                    $q->where('organe', 'like', '%National%')
                      ->orWhere('organe', 'like', '%Secrétariat Exécutif National%');
                })
                ->actifs()
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom', 'organe', 'fonction'])
                ->map(fn ($a) => [
                    'id'         => $a->id,
                    'nom_complet' => trim($a->prenom . ' ' . $a->nom),
                    'organe'     => $a->organe,
                    'fonction'   => $a->fonction,
                ])
            : [];

        $isPlanification = $this->canUsePtaAdminContext();
        $departmentIds = $departments->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $departmentAgents = $isPlanification ? $this->ptaDepartmentAgents($departmentIds) : [];
        $agentsSen = $isPlanification ? $this->ptaSenAttacheAgents() : collect();
        $assignmentTargets = $isPlanification
            ? collect([
                [
                    'value' => 'sen_attaches',
                    'type' => 'sen_attaches',
                    'department_id' => null,
                    'label' => 'Attaches du SEN',
                    'agent_count' => $agentsSen->count(),
                ],
            ])->merge($departments->map(fn ($department) => [
                'value' => 'department:' . $department->id,
                'type' => 'department',
                'department_id' => $department->id,
                'label' => $department->nom,
                'agent_count' => count($departmentAgents[$department->id] ?? []),
            ]))->values()
            : collect();

        $payload = [
            'departments' => $departments,
            'provinces' => $provinces,
            'localites' => $localites,
            'categories' => $this->ptaCategories(),
            'responsables' => $this->ptaResponsables(),
            'annee' => now()->year,
            'validation_options' => [
                ['value' => 'direction', 'label' => 'Direction'],
                ['value' => 'coordination_nationale', 'label' => 'Coordination nationale'],
                ['value' => 'coordination_provinciale', 'label' => 'Coordination provinciale'],
            ],
            'agents_sen' => $agentsSen->values(),
            'sen_attache_agents' => $agentsSen->values(),
            'department_agents' => $departmentAgents,
            'assignment_targets' => $assignmentTargets,
            'is_planification_role' => $isPlanification,
        ];

        return $this->success($payload, [], $payload);
    }

    /**
     * Store a new PTA activity.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent = $this->getScopedAgent();

        $validated = $request->validate([
            'titre'                => 'required|string|max:1000',
            'categorie'            => 'nullable|string|max:120',
            'objectif'             => 'nullable|string',
            'description'          => 'nullable|string',
            'resultat_attendu'     => 'nullable|string',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'validation_niveau'    => 'nullable|in:direction,coordination_nationale,coordination_provinciale',
            'assignment_target'    => 'nullable|string|max:60',
            'responsable_code'     => 'nullable|string|max:30',
            'cout_cdf'             => 'nullable|numeric|min:0',
            'departement_id'       => 'nullable|exists:departments,id',
            'province_id'          => 'nullable|exists:provinces,id',
            'province_ids'         => 'nullable|array',
            'province_ids.*'       => 'integer|exists:provinces,id',
            'localite_id'          => 'nullable|exists:localites,id',
            'annee'                => 'required|integer|min:2020|max:2040',
            'trimestre'            => 'nullable|in:T1,T2,T3,T4',
            'trimestre_1'          => 'nullable|boolean',
            'trimestre_2'          => 'nullable|boolean',
            'trimestre_3'          => 'nullable|boolean',
            'trimestre_4'          => 'nullable|boolean',
            'statut'               => 'required|in:planifiee,en_cours,terminee',
            'date_debut'           => 'nullable|date',
            'date_fin'             => 'nullable|date',
            'pourcentage'          => 'integer|min:0|max:100',
            'observations'         => 'nullable|string',
            'assigned_agent_ids'   => 'nullable|array',
            'assigned_agent_ids.*' => 'integer|exists:agents,id',
        ]);

        $hasAssignedAgents = array_key_exists('assigned_agent_ids', $validated);
        $assignedAgentIds = $validated['assigned_agent_ids'] ?? [];
        unset($validated['assigned_agent_ids']);

        $validated = $this->assertAssignedAgentsMatchTarget($validated, $assignedAgentIds);
        $validated = $this->normalizeTrimestreFlags($validated);
        $validated = $this->enforceManagedScope($validated, $agent);
        $provinceIds = $this->normalizeProvinceIds($validated);
        $validated['province_id'] = $provinceIds[0] ?? ($validated['province_id'] ?? null);

        $validated['createur_id'] = auth()->user()->agent->id;

        $activite = ActivitePlan::create($validated);
        $this->syncActiviteProvinces($activite, $provinceIds);
        if ($hasAssignedAgents) {
            $activite->agents()->sync($assignedAgentIds);
        }

        PtaModified::dispatch($activite, 'created');

        NotificationService::notifierTous(
            'plan_travail',
            'Nouvelle activite PTA : ' . $activite->titre,
            'Une nouvelle activite a ete ajoutee au Plan de Travail Annuel (' . $activite->annee . ' ' . ($activite->trimestre ?? '') . ').',
            '/plan-travail/' . $activite->id,
            auth()->id()
        );

        $resource = ActivitePlanResource::make($activite->load('createur', 'departement', 'province', 'provinces', 'localite', 'agents'));

        return $this->resource($resource, [], [
            'message' => 'Activite creee avec succes.',
        ], 201);
    }

    /**
     * Display a single PTA activity.
     */
    public function show(ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canAccessActivity($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $activitePlan->load(['createur', 'departement', 'province', 'provinces', 'localite', 'taches.agent', 'agents']);

        return $this->resource(ActivitePlanResource::make($activitePlan), [
            'canEdit' => $this->canManage(),
            'canUpdateStatut' => $this->canUpdateStatut($activitePlan),
        ], [
            'canEdit' => $this->canManage(),
            'canUpdateStatut' => $this->canUpdateStatut($activitePlan),
        ]);
    }

    /**
     * Update a PTA activity.
     */
    public function update(Request $request, ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!$this->canAccessActivity($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent = $this->getScopedAgent();

        $validated = $request->validate([
            'titre'                => 'required|string|max:1000',
            'categorie'            => 'nullable|string|max:120',
            'objectif'             => 'nullable|string',
            'description'          => 'nullable|string',
            'resultat_attendu'     => 'nullable|string',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'validation_niveau'    => 'nullable|in:direction,coordination_nationale,coordination_provinciale',
            'assignment_target'    => 'nullable|string|max:60',
            'responsable_code'     => 'nullable|string|max:30',
            'cout_cdf'             => 'nullable|numeric|min:0',
            'departement_id'       => 'nullable|exists:departments,id',
            'province_id'          => 'nullable|exists:provinces,id',
            'province_ids'         => 'nullable|array',
            'province_ids.*'       => 'integer|exists:provinces,id',
            'localite_id'          => 'nullable|exists:localites,id',
            'annee'                => 'required|integer|min:2020|max:2040',
            'trimestre'            => 'nullable|in:T1,T2,T3,T4',
            'trimestre_1'          => 'nullable|boolean',
            'trimestre_2'          => 'nullable|boolean',
            'trimestre_3'          => 'nullable|boolean',
            'trimestre_4'          => 'nullable|boolean',
            'statut'               => 'required|in:planifiee,en_cours,terminee',
            'date_debut'           => 'nullable|date',
            'date_fin'             => 'nullable|date',
            'pourcentage'          => 'integer|min:0|max:100',
            'observations'         => 'nullable|string',
            'assigned_agent_ids'   => 'nullable|array',
            'assigned_agent_ids.*' => 'integer|exists:agents,id',
        ]);

        $hasAssignedAgents = array_key_exists('assigned_agent_ids', $validated);
        $assignedAgentIds = $validated['assigned_agent_ids'] ?? [];
        unset($validated['assigned_agent_ids']);

        $validated = $this->assertAssignedAgentsMatchTarget($validated, $assignedAgentIds);
        $validated = $this->normalizeTrimestreFlags($validated);
        $validated = $this->enforceManagedScope($validated, $agent);
        $provinceIds = $this->normalizeProvinceIds($validated);
        $validated['province_id'] = $provinceIds[0] ?? ($validated['province_id'] ?? null);

        $activitePlan->update($validated);
        $this->syncActiviteProvinces($activitePlan, $provinceIds);
        if ($hasAssignedAgents) {
            $activitePlan->agents()->sync($assignedAgentIds);
        }

        PtaModified::dispatch($activitePlan, 'updated');

        NotificationService::notifierTous(
            'plan_travail',
            'PTA mis a jour : ' . $activitePlan->titre,
            'L\'activite "' . $activitePlan->titre . '" a ete mise a jour (' . $activitePlan->statut . ', ' . $activitePlan->pourcentage . '%).',
            '/plan-travail/' . $activitePlan->id,
            auth()->id()
        );

        $resource = ActivitePlanResource::make($activitePlan->fresh()->load('createur', 'departement', 'province', 'provinces', 'localite', 'agents'));

        return $this->resource($resource, [], [
            'message' => 'Activite mise a jour.',
        ]);
    }

    /**
     * Remove a PTA activity.
     */
    public function destroy(ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!$this->canAccessActivity($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $activitePlan->delete();

        return $this->success(null, [], [
            'message' => 'Activite supprimee.',
        ]);
    }

    /**
     * Quick status update for a PTA activity.
     */
    public function updateStatut(Request $request, ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canUpdateStatut($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'statut'       => 'required|in:planifiee,en_cours,terminee',
            'pourcentage'  => 'integer|min:0|max:100',
            'observations' => 'nullable|string',
        ]);

        $activitePlan->update($validated);

        PtaModified::dispatch($activitePlan, 'status_changed');

        $resource = ActivitePlanResource::make($activitePlan->fresh()->load('createur', 'departement', 'province', 'provinces', 'localite', 'taches.agent', 'agents'));

        return $this->resource($resource, [], [
            'message' => 'Statut mis a jour.',
        ]);
    }

    public function importParsed(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$this->scopeService()->hasGlobalAdminAccess($user)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'createur_id' => 'nullable|integer|exists:agents,id',
            'records' => 'required|array|min:1',
            'records.*.titre' => 'required|string|max:1000',
            'records.*.categorie' => 'nullable|string|max:120',
            'records.*.resultat_attendu' => 'nullable|string',
            'records.*.niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'records.*.validation_niveau' => 'nullable|in:direction,coordination_nationale,coordination_provinciale',
            'records.*.responsable_code' => 'nullable|string|max:30',
            'records.*.cout_cdf' => 'nullable|numeric|min:0',
            'records.*.province_ids' => 'nullable|array',
            'records.*.province_ids.*' => 'integer|exists:provinces,id',
            'records.*.province_names' => 'nullable|array',
            'records.*.province_names.*' => 'string|max:255',
            'records.*.annee' => 'required|integer|min:2020|max:2040',
            'records.*.trimestre' => 'nullable|in:T1,T2,T3,T4',
            'records.*.trimestre_1' => 'nullable|boolean',
            'records.*.trimestre_2' => 'nullable|boolean',
            'records.*.trimestre_3' => 'nullable|boolean',
            'records.*.trimestre_4' => 'nullable|boolean',
        ]);

        $created = 0;
        $updated = 0;
        $createurId = (int) ($validated['createur_id'] ?? $user->agent?->id ?? 0);

        if ($createurId <= 0) {
            return response()->json(['message' => 'createur_id est obligatoire pour cet utilisateur.'], 422);
        }

        \DB::transaction(function () use ($validated, $createurId, &$created, &$updated) {
            foreach ($validated['records'] as $record) {
                $match = [
                    'annee' => $record['annee'],
                    'niveau_administratif' => $record['niveau_administratif'],
                    'titre' => $record['titre'],
                    'categorie' => $record['categorie'] ?? null,
                ];

                $activite = ActivitePlan::query()->firstOrNew($match);
                $isNew = !$activite->exists;
                $provinceIds = $this->resolveImportedProvinceIds($record);

                $payload = [
                    'titre' => $record['titre'],
                    'categorie' => $record['categorie'] ?? null,
                    'resultat_attendu' => $record['resultat_attendu'] ?? null,
                    'niveau_administratif' => $record['niveau_administratif'],
                    'validation_niveau' => $record['validation_niveau'] ?? null,
                    'responsable_code' => $record['responsable_code'] ?? null,
                    'cout_cdf' => $record['cout_cdf'] ?? null,
                    'province_id' => $provinceIds[0] ?? null,
                    'annee' => $record['annee'],
                    'trimestre' => $record['trimestre'] ?? null,
                    'trimestre_1' => (bool) ($record['trimestre_1'] ?? false),
                    'trimestre_2' => (bool) ($record['trimestre_2'] ?? false),
                    'trimestre_3' => (bool) ($record['trimestre_3'] ?? false),
                    'trimestre_4' => (bool) ($record['trimestre_4'] ?? false),
                ];

                if ($isNew) {
                    $payload['createur_id'] = $createurId;
                    $payload['statut'] = 'planifiee';
                    $payload['pourcentage'] = 0;
                }

                $activite->fill($payload);
                $activite->save();
                $activite->provinces()->sync($provinceIds);

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        return $this->success([
            'created' => $created,
            'updated' => $updated,
            'total' => $created + $updated,
        ], [], [
            'message' => sprintf('Import PTA termine : %d creee(s), %d mise(s) a jour.', $created, $updated),
            'created' => $created,
            'updated' => $updated,
            'total' => $created + $updated,
        ]);
    }

    private function normalizeProvinceIds(array &$validated): array
    {
        $provinceIds = collect($validated['province_ids'] ?? []);

        if (!empty($validated['province_id'])) {
            $provinceIds->prepend((int) $validated['province_id']);
        }

        unset($validated['province_ids']);

        return $provinceIds
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Validate PTA at Section level (Chef Section Planification).
     */
    public function validateSection(Request $request, ActivitePlan $activitePlan): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasPermission('pta.validate.section')) {
            return response()->json(['message' => 'Permission requise : pta.validate.section'], 403);
        }

        $validated = $request->validate([
            'observations' => 'nullable|string',
        ]);

        $activitePlan->update([
            'validated_by_section' => $user->agent?->id,
            'validated_at_section' => now(),
        ]);

        if (!empty($validated['observations'])) {
            $activitePlan->update(['observations' => $validated['observations']]);
        }

        $resource = ActivitePlanResource::make($activitePlan->fresh()->load('createur', 'departement', 'province', 'provinces', 'localite', 'taches.agent'));

        return $this->resource($resource, [], [
            'message' => 'Activité validée au niveau Section.',
        ]);
    }

    /**
     * Validate PTA at Cellule level (Chef Cellule Planification).
     */
    public function validateCellule(Request $request, ActivitePlan $activitePlan): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasPermission('pta.validate.cellule')) {
            return response()->json(['message' => 'Permission requise : pta.validate.cellule'], 403);
        }

        // Section validation should precede Cellule
        if (!$activitePlan->validated_by_section) {
            return response()->json(['message' => 'Cette activité doit d\'abord être validée au niveau Section.'], 422);
        }

        $validated = $request->validate([
            'observations' => 'nullable|string',
        ]);

        $activitePlan->update([
            'validated_by_cellule' => $user->agent?->id,
            'validated_at_cellule' => now(),
        ]);

        if (!empty($validated['observations'])) {
            $activitePlan->update(['observations' => $validated['observations']]);
        }

        $resource = ActivitePlanResource::make($activitePlan->fresh()->load('createur', 'departement', 'province', 'provinces', 'localite', 'taches.agent'));

        return $this->resource($resource, [], [
            'message' => 'Activité validée au niveau Cellule.',
        ]);
    }

    private function normalizeTrimestreFlags(array $validated): array
    {
        $mapping = [
            'T1' => 'trimestre_1',
            'T2' => 'trimestre_2',
            'T3' => 'trimestre_3',
            'T4' => 'trimestre_4',
        ];

        foreach (['trimestre_1', 'trimestre_2', 'trimestre_3', 'trimestre_4'] as $field) {
            $validated[$field] = (bool) ($validated[$field] ?? false);
        }

        if (!empty($validated['trimestre'])) {
            $validated[$mapping[$validated['trimestre']]] = true;
            return $validated;
        }

        foreach ($mapping as $trimestre => $field) {
            if ($validated[$field]) {
                $validated['trimestre'] = $trimestre;
                break;
            }
        }

        return $validated;
    }

    private function syncActiviteProvinces(ActivitePlan $activite, array $provinceIds): void
    {
        $activite->provinces()->sync($provinceIds);
    }

    private function resolveImportedProvinceIds(array $record): array
    {
        $provinceIds = collect($record['province_ids'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id);

        if ($provinceIds->isNotEmpty()) {
            return $provinceIds->unique()->values()->all();
        }

        $provinceNames = $record['province_names'] ?? [];
        if ($provinceNames === []) {
            return [];
        }

        $catalog = Province::query()->get(['id', 'nom']);
        $joined = $this->normalizeProvinceImportKey(implode(' ', $provinceNames));
        $ids = [];

        foreach ($catalog as $province) {
            $key = $this->normalizeProvinceImportKey($province->nom ?? '');
            if ($key !== '' && str_contains($joined, $key)) {
                $ids[] = (int) $province->id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function normalizeProvinceImportKey(string $value): string
    {
        $value = mb_strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]/u', '', $value) ?? $value;

        return str_replace([
            'KASAIORIENT',
            'SUDUBANGI',
            'BASUELE',
            'HAUTUELE',
        ], [
            'KASAIORIENTAL',
            'SUDUBANGI',
            'BASUELE',
            'HAUTUELE',
        ], $value);
    }

    private function activityTargetsProvince(ActivitePlan $activite, int $provinceId): bool
    {
        if ((int) $activite->province_id === $provinceId) {
            return true;
        }

        if (!$activite->relationLoaded('provinces')) {
            $activite->load('provinces:id');
        }

        return $activite->provinces->contains('id', $provinceId);
    }

    private function ptaCategories(): array
    {
        return [
            'Leadership',
            'Planification & Suivi Evaluation',
            'Renforcement des capacités et Recherche',
            'Coordination des secteurs et multisectorialité',
            'Partenariat et Coopération Régionale, Bi et Multilatérale',
            'Administration et Finances',
            'Informations stratégiques (Suivi Evaluation)',
            'Planification et Renforcement des capacités',
            'Coordination des secteurs et partenariat',
            'Administration logistique et finances',
        ];
    }

    private function ptaResponsables(): array
    {
        return [
            'SEN',
            'DPSE',
            'DRRC',
            'DCS',
            'DPCMB',
            'DAF',
            'SEP',
            'SEL',
            'Tous les départements',
        ];
    }
}
