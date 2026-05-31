<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Localite;
use App\Models\Pointage;
use App\Models\Request as RequestModel;
use App\Models\Signalement;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Support\Str;

class UserDataScope
{
    private array $globalAdminRoles = [
        'Section ressources humaines',
        'Chef Section RH',
        'RH National',
        'Section Nouvelle Technologie',
        'Chef Section Nouvelle Technologie',
        'Chef de Section Nouvelle Technologie',
        'SEN',
    ];

    public function hasGlobalAdminAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roleService = app(RoleService::class);

        if ($roleService->isSuperAdmin($user)) {
            return true;
        }

        if ($user->hasRole($this->globalAdminRoles)) {
            return true;
        }

        // Le DAF (Directeur Administratif et Financier) a accès global RH
        if ($roleService->hasDirecteurOrDafRole($user)) {
            return true;
        }

        return false;
    }

    /**
     * Contacts and interim selections are intentionally stricter than the
     * administrative RH scope: only institutional authorities see everyone.
     */
    public function hasInstitutionAuthorityAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roleService = app(RoleService::class);

        if ($roleService->isSuperAdmin($user)) {
            return true;
        }

        if ($user->hasRole([
            'SEN',
            'SENA',
            'RH National',
            'Section ressources humaines',
            'Chef Section RH',
            'DAF',
        ])) {
            return true;
        }

        return $roleService->hasDirecteurOrDafRole($user);
    }

    public function isProvincialRh(?User $user): bool
    {
        return (bool) $user?->hasRole('RH Provincial');
    }

    public function isAssistantRh(?User $user): bool
    {
        return app(RoleService::class)->isAssistantRh($user);
    }

    public function isProvincialSep(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('SEP')) {
            return true;
        }

        if (!$user->hasRole('SECOM')) {
            return false;
        }

        $agent = $user->agent;
        $organe = strtolower((string) ($agent?->organe ?? ''));
        $deptCode = strtolower((string) ($agent?->departement?->code ?? ''));
        $deptName = strtolower((string) ($agent?->departement?->nom ?? ''));

        if ($deptCode === 'caf' || str_contains($deptName, 'cellule administrative et financ')) {
            return false;
        }

        return str_contains($organe, 'provincial');
    }

    private function normalizeText(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function profileText(?User $user): string
    {
        $agent = $user?->agent;

        return trim(implode(' ', array_filter([
            $this->normalizeText($user?->role?->nom_role),
            $this->normalizeText($agent?->fonction),
            $this->normalizeText($agent?->poste_actuel),
            $this->normalizeText($agent?->departement?->code),
            $this->normalizeText($agent?->departement?->nom),
        ])));
    }

    public function isLocalUser(?User $user): bool
    {
        if (!$user || !$user->agent) {
            return false;
        }

        $role = $this->normalizeText($user->role?->nom_role);
        if (in_array($role, ['sel', 'rh local', 'aaf local', 'assistant administratif et financier'], true)) {
            return true;
        }

        $organe = $this->normalizeText($user->agent?->organe);
        if (!str_contains($organe, 'local')) {
            return false;
        }

        $profile = $this->profileText($user);

        return str_contains($profile, 'secretaire executif local')
            || str_contains($profile, 'rh local')
            || str_contains($profile, 'assistant administratif et financier')
            || str_contains($profile, 'aaf local')
            || str_contains($profile, '(sel)');
    }

    /** Vrai si l'utilisateur doit être scopé à sa propre province (RH Provincial OU SEP). */
    public function isProvincialUser(?User $user): bool
    {
        return $this->isProvincialRh($user) || $this->isProvincialSep($user);
    }

    public function provinceId(?User $user): ?int
    {
        $provinceId = $user?->agent?->province_id;

        return $provinceId ? (int) $provinceId : null;
    }

    public function localiteId(?User $user): ?int
    {
        $agent = $user?->agent;
        if (!$agent) {
            return null;
        }

        $localiteId = $agent->localite_id;
        if (!$localiteId) {
            $localiteId = $agent->affectations()
                ->where('actif', true)
                ->whereNotNull('localite_id')
                ->orderByDesc('date_debut')
                ->orderByDesc('id')
                ->value('localite_id');
        }

        return $localiteId ? (int) $localiteId : null;
    }

    public function applyAgentScope($query, ?User $user, string $table = 'agents')
    {
        $this->excludeAncienAgents($query, $table);

        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if ($this->isLocalUser($user)) {
            $localiteId = $this->localiteId($user);
            if (!$localiteId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function ($localScope) use ($table, $localiteId) {
                $localScope->where($table . '.localite_id', $localiteId)
                    ->orWhereHas('affectations', function ($affectationScope) use ($localiteId) {
                        $affectationScope
                            ->where('actif', true)
                            ->where('localite_id', $localiteId);
                    });
            });
        }

        if ($this->isProvincialUser($user)) {
            $provinceId = $this->provinceId($user);
            if (!$provinceId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where($table . '.province_id', $provinceId);
        }

        // Les assistants RH doivent pouvoir consulter les dossiers agents pour
        // les opérations déléguées: pointage, documents, suivi administratif.
        if ($this->isAssistantRh($user)) {
            return $query;
        }

        return $query->whereHas('departement', function ($dq) {
            $dq->operational();
        });
    }

    public function applyContactScope($query, ?User $user, string $table = 'agents')
    {
        $this->excludeAncienAgents($query, $table);

        if ($this->hasInstitutionAuthorityAccess($user)) {
            return $query;
        }

        return $this->applyAgentLocalScope($query, $user?->agent, $table);
    }

    public function canUseAgentAsInterim(?User $user, ?Agent $interimAgent, ?Agent $requestAgent = null): bool
    {
        if (!$interimAgent || $this->isAncienAgent($interimAgent)) {
            return false;
        }

        if (strtolower((string) $interimAgent->statut) !== 'actif') {
            return false;
        }

        $ownerAgent = $requestAgent ?: $user?->agent;

        if (!$ownerAgent) {
            return false;
        }

        if ((int) $interimAgent->id === (int) $ownerAgent->id) {
            return false;
        }

        if ($this->hasInstitutionAuthorityAccess($user)) {
            return true;
        }

        return $this->agentsShareLocalScope($ownerAgent, $interimAgent);
    }

    public function isDepartmentManager(?User $user): bool
    {
        return app(RoleService::class)->isDepartmentManager($user);
    }

    public function applyRequestScope($query, ?User $user)
    {
        $query->whereHas('agent', function ($agentQuery) {
            $this->excludeAncienAgents($agentQuery);
        });

        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if ($this->isProvincialUser($user)) {
            $provinceId = $this->provinceId($user);

            if (!$provinceId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('agent', function ($agentQuery) use ($provinceId) {
                $agentQuery->where('province_id', $provinceId);
            });
        }

        // Directeurs and assistants can see all requests from agents in their department
        if ($this->isDepartmentManager($user)) {
            $deptId = $user?->agent?->departement_id;
            if ($deptId) {
                $deptAgentIds = Agent::where('departement_id', $deptId)
                    ->where(function ($agentQuery) {
                        $this->excludeAncienAgents($agentQuery);
                    })
                    ->pluck('id');
                return $query->whereIn('agent_id', $deptAgentIds);
            }
        }

        $agentId = $user?->agent?->id;

        return $agentId
            ? $query->where('agent_id', $agentId)
            : $query->whereRaw('1 = 0');
    }

    public function applySignalementScope($query, ?User $user)
    {
        $query->whereHas('agent', function ($agentQuery) {
            $this->excludeAncienAgents($agentQuery);
        });

        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if (!$this->isProvincialUser($user)) {
            $agentId = $user?->agent?->id;

            return $agentId
                ? $query->where('agent_id', $agentId)
                : $query->whereRaw('1 = 0');
        }

        $provinceId = $this->provinceId($user);
        if (!$provinceId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('agent', function ($agentQuery) use ($provinceId) {
            $agentQuery->where('province_id', $provinceId);
        });
    }

    public function applyPointageScope($query, ?User $user)
    {
        $query->whereHas('agent', function ($agentQuery) {
            $this->excludeAncienAgents($agentQuery);
        });

        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if (!$this->isProvincialUser($user)) {
            return $query;
        }

        $provinceId = $this->provinceId($user);
        if (!$provinceId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('agent', function ($agentQuery) use ($provinceId) {
            $agentQuery->where('province_id', $provinceId);
        });
    }

    public function applyAffectationScope($query, ?User $user)
    {
        $query->where(function ($scopeQuery) {
            $scopeQuery
                ->whereDoesntHave('agent')
                ->orWhereHas('agent', function ($agentQuery) {
                    $this->excludeAncienAgents($agentQuery);
                });
        });

        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if (!$this->isProvincialUser($user)) {
            return $query;
        }

        $provinceId = $this->provinceId($user);
        if (!$provinceId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($scopeQuery) use ($provinceId) {
            $scopeQuery
                ->where('province_id', $provinceId)
                ->orWhereHas('agent', function ($agentQuery) use ($provinceId) {
                    $agentQuery->where('province_id', $provinceId);
                })
                ->orWhereHas('department', function ($departmentQuery) use ($provinceId) {
                    $departmentQuery->where('province_id', $provinceId);
                });
        });
    }

    public function canAccessAgent(?User $user, ?Agent $agent, bool $allowOwn = false): bool
    {
        if (!$agent) {
            return false;
        }

        if ($this->isAncienAgent($agent)) {
            return false;
        }

        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if ($this->isAssistantRh($user)) {
            return true;
        }

        if ($this->isProvincialUser($user)) {
            $provinceId = $this->provinceId($user);

            return $provinceId !== null && (int) $agent->province_id === $provinceId;
        }

        return $allowOwn && (int) ($user?->agent?->id ?? 0) === (int) $agent->id;
    }

    public function canAccessRequest(?User $user, RequestModel $demande, bool $allowOwn = true): bool
    {
        $agent = $demande->relationLoaded('agent') ? $demande->agent : $demande->agent()->first();

        if ($this->isAncienAgent($agent)) {
            return false;
        }

        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if ($this->isProvincialUser($user)) {
            return $this->canAccessAgent($user, $agent, false);
        }

        if ($this->isDepartmentManager($user)) {
            $deptId = $user?->agent?->departement_id;
            if ($deptId && $agent && (int) $agent->departement_id === (int) $deptId) {
                return true;
            }
        }

        return $allowOwn && (int) ($user?->agent?->id ?? 0) === (int) $demande->agent_id;
    }

    public function canAccessSignalement(?User $user, Signalement $signalement, bool $allowOwn = false): bool
    {
        $agent = $signalement->relationLoaded('agent') ? $signalement->agent : $signalement->agent()->first();

        if ($this->isAncienAgent($agent)) {
            return false;
        }

        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if (!$this->isProvincialUser($user)) {
            return $allowOwn && (int) ($user?->agent?->id ?? 0) === (int) $signalement->agent_id;
        }

        return $this->canAccessAgent($user, $agent, false);
    }

    public function canAccessPointage(?User $user, Pointage $pointage): bool
    {
        $agent = $pointage->relationLoaded('agent') ? $pointage->agent : $pointage->agent()->first();

        if ($this->isAncienAgent($agent)) {
            return false;
        }

        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if (!$this->isProvincialUser($user)) {
            return false;
        }

        return $this->canAccessAgent($user, $agent, false);
    }

    public function canAccessAffectation(?User $user, Affectation $affectation): bool
    {
        $agent = $affectation->relationLoaded('agent') ? $affectation->agent : $affectation->agent()->first();

        if ($this->isAncienAgent($agent)) {
            return false;
        }

        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if (!$this->isProvincialUser($user)) {
            return false;
        }

        $provinceId = $this->provinceId($user);
        if (!$provinceId) {
            return false;
        }

        if ((int) ($affectation->province_id ?? 0) === $provinceId) {
            return true;
        }

        if ($this->canAccessAgent($user, $agent, false)) {
            return true;
        }

        $department = $affectation->relationLoaded('department') ? $affectation->department : $affectation->department()->first();

        return (int) ($department?->province_id ?? 0) === $provinceId;
    }

    public function enforceAgentPayloadScope(array $validated, ?User $user): array
    {
        if (!$this->isProvincialUser($user)) {
            return $validated;
        }

        $provinceId = $this->provinceId($user);
        if (!$provinceId) {
            abort(403, 'Aucune province associee a cet utilisateur provincial.');
        }

        if (!empty($validated['province_id']) && (int) $validated['province_id'] !== $provinceId) {
            abort(403, 'Acces refuse pour cette province.');
        }

        if (!empty($validated['departement_id'])) {
            $department = Department::find($validated['departement_id']);

            if (!$department || (int) $department->province_id !== $provinceId) {
                abort(403, 'Acces refuse pour ce departement.');
            }
        }

        if (!empty($validated['localite_id'])) {
            $localite = Localite::find($validated['localite_id']);

            if (!$localite || (int) $localite->province_id !== $provinceId) {
                abort(403, 'Acces refuse pour cette localite.');
            }
        }

        $validated['province_id'] = $provinceId;

        return $validated;
    }

    public function filterDepartments($query, ?User $user)
    {
        if (!$this->isProvincialUser($user)) {
            return $query->operational();
        }

        $provinceId = $this->provinceId($user);

        return $provinceId
            ? $query->where('province_id', $provinceId)
            : $query->whereRaw('1 = 0');
    }

    public function filterProvinces($query, ?User $user)
    {
        if (!$this->isProvincialUser($user)) {
            return $query;
        }

        $provinceId = $this->provinceId($user);

        return $provinceId
            ? $query->where('id', $provinceId)
            : $query->whereRaw('1 = 0');
    }

    private function excludeAncienAgents($query, string $table = 'agents')
    {
        return $query->where(function ($agentQuery) use ($table) {
            $agentQuery
                ->whereNull($table . '.statut')
                ->orWhere($table . '.statut', '!=', 'ancien');
        });
    }

    private function isAncienAgent(?Agent $agent): bool
    {
        return strtolower((string) ($agent?->statut ?? '')) === 'ancien';
    }

    private function applyAgentLocalScope($query, ?Agent $agent, string $table = 'agents')
    {
        if (!$agent) {
            return $query->whereRaw('1 = 0');
        }

        $organe = trim((string) $agent->organe);
        $normalizedOrgane = $this->normalizeText($organe);
        if (str_contains($normalizedOrgane, 'local')) {
            $localiteId = $agent->localite_id ?: $agent->affectations()
                ->where('actif', true)
                ->whereNotNull('localite_id')
                ->orderByDesc('date_debut')
                ->orderByDesc('id')
                ->value('localite_id');

            if ($localiteId) {
                return $query->where(function ($localScope) use ($table, $localiteId) {
                    $localScope->where($table . '.localite_id', $localiteId)
                        ->orWhereHas('affectations', function ($affectationScope) use ($localiteId) {
                            $affectationScope
                                ->where('actif', true)
                                ->where('localite_id', $localiteId);
                        });
                });
            }
        }

        if ($agent->province_id) {
            return $query->where($table . '.province_id', $agent->province_id);
        }

        if ($agent->departement_id) {
            return $query->where($table . '.departement_id', $agent->departement_id);
        }

        if ($organe !== '') {
            return $query->where($table . '.organe', $organe);
        }

        return $query->where($table . '.id', $agent->id);
    }

    private function agentsShareLocalScope(Agent $ownerAgent, Agent $candidateAgent): bool
    {
        $ownerOrgane = trim((string) $ownerAgent->organe);
        $ownerNormalizedOrgane = $this->normalizeText($ownerOrgane);
        if (str_contains($ownerNormalizedOrgane, 'local')) {
            $ownerLocaliteId = $ownerAgent->localite_id ?: $ownerAgent->affectations()
                ->where('actif', true)
                ->whereNotNull('localite_id')
                ->orderByDesc('date_debut')
                ->orderByDesc('id')
                ->value('localite_id');

            if (!$ownerLocaliteId) {
                return false;
            }

            $candidateLocaliteId = $candidateAgent->localite_id ?: $candidateAgent->affectations()
                ->where('actif', true)
                ->whereNotNull('localite_id')
                ->orderByDesc('date_debut')
                ->orderByDesc('id')
                ->value('localite_id');

            return (int) $candidateLocaliteId === (int) $ownerLocaliteId;
        }

        if ($ownerAgent->province_id) {
            return (int) $candidateAgent->province_id === (int) $ownerAgent->province_id;
        }

        if ($ownerAgent->departement_id) {
            return (int) $candidateAgent->departement_id === (int) $ownerAgent->departement_id;
        }

        if ($ownerOrgane !== '') {
            return trim((string) $candidateAgent->organe) === $ownerOrgane;
        }

        return false;
    }
}
