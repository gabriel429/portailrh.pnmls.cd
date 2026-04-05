<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Pointage;
use App\Models\Request as RequestModel;
use App\Models\Signalement;
use App\Models\User;
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

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $user->hasRole($this->globalAdminRoles);
    }

    public function isProvincialRh(?User $user): bool
    {
        return (bool) $user?->hasRole('RH Provincial');
    }

    public function provinceId(?User $user): ?int
    {
        $provinceId = $user?->agent?->province_id;

        return $provinceId ? (int) $provinceId : null;
    }

    public function applyAgentScope($query, ?User $user, string $table = 'agents')
    {
        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if (!$this->isProvincialRh($user)) {
            return $query;
        }

        $provinceId = $this->provinceId($user);
        if (!$provinceId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($table . '.province_id', $provinceId);
    }

    public function applyRequestScope($query, ?User $user)
    {
        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if ($this->isProvincialRh($user)) {
            $provinceId = $this->provinceId($user);

            if (!$provinceId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('agent', function ($agentQuery) use ($provinceId) {
                $agentQuery->where('province_id', $provinceId);
            });
        }

        $agentId = $user?->agent?->id;

        return $agentId
            ? $query->where('agent_id', $agentId)
            : $query->whereRaw('1 = 0');
    }

    public function applySignalementScope($query, ?User $user)
    {
        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if (!$this->isProvincialRh($user)) {
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

    public function applyPointageScope($query, ?User $user)
    {
        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if (!$this->isProvincialRh($user)) {
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
        if ($this->hasGlobalAdminAccess($user)) {
            return $query;
        }

        if (!$this->isProvincialRh($user)) {
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

        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if ($this->isProvincialRh($user)) {
            $provinceId = $this->provinceId($user);

            return $provinceId !== null && (int) $agent->province_id === $provinceId;
        }

        return $allowOwn && (int) ($user?->agent?->id ?? 0) === (int) $agent->id;
    }

    public function canAccessRequest(?User $user, RequestModel $demande, bool $allowOwn = true): bool
    {
        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        $agent = $demande->relationLoaded('agent') ? $demande->agent : $demande->agent()->first();

        if ($this->isProvincialRh($user)) {
            return $this->canAccessAgent($user, $agent, false);
        }

        return $allowOwn && (int) ($user?->agent?->id ?? 0) === (int) $demande->agent_id;
    }

    public function canAccessSignalement(?User $user, Signalement $signalement): bool
    {
        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if (!$this->isProvincialRh($user)) {
            return false;
        }

        $agent = $signalement->relationLoaded('agent') ? $signalement->agent : $signalement->agent()->first();

        return $this->canAccessAgent($user, $agent, false);
    }

    public function canAccessPointage(?User $user, Pointage $pointage): bool
    {
        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if (!$this->isProvincialRh($user)) {
            return false;
        }

        $agent = $pointage->relationLoaded('agent') ? $pointage->agent : $pointage->agent()->first();

        return $this->canAccessAgent($user, $agent, false);
    }

    public function canAccessAffectation(?User $user, Affectation $affectation): bool
    {
        if ($this->hasGlobalAdminAccess($user)) {
            return true;
        }

        if (!$this->isProvincialRh($user)) {
            return false;
        }

        $provinceId = $this->provinceId($user);
        if (!$provinceId) {
            return false;
        }

        if ((int) ($affectation->province_id ?? 0) === $provinceId) {
            return true;
        }

        $agent = $affectation->relationLoaded('agent') ? $affectation->agent : $affectation->agent()->first();
        if ($this->canAccessAgent($user, $agent, false)) {
            return true;
        }

        $department = $affectation->relationLoaded('department') ? $affectation->department : $affectation->department()->first();

        return (int) ($department?->province_id ?? 0) === $provinceId;
    }

    public function enforceAgentPayloadScope(array $validated, ?User $user): array
    {
        if (!$this->isProvincialRh($user)) {
            return $validated;
        }

        $provinceId = $this->provinceId($user);
        if (!$provinceId) {
            abort(403, 'Aucune province associee a cet utilisateur RH provincial.');
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

        $validated['province_id'] = $provinceId;

        return $validated;
    }

    public function filterDepartments($query, ?User $user)
    {
        if (!$this->isProvincialRh($user)) {
            return $query;
        }

        $provinceId = $this->provinceId($user);

        return $provinceId
            ? $query->where('province_id', $provinceId)
            : $query->whereRaw('1 = 0');
    }

    public function filterProvinces($query, ?User $user)
    {
        if (!$this->isProvincialRh($user)) {
            return $query;
        }

        $provinceId = $this->provinceId($user);

        return $provinceId
            ? $query->where('id', $provinceId)
            : $query->whereRaw('1 = 0');
    }
}