<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Str;

class RoleService
{
    public function hasRole(User|Agent|null $entity, string|array $roles): bool
    {
        if (!$entity) {
            return false;
        }

        if ($entity instanceof User) {
            return $entity->hasRole($roles);
        }

        return $entity->hasRole($roles);
    }

    public function hasDirecteurOrDafRole(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->isDepartmentPrincipalRole($user)
            || $user->hasRole('DAF');
    }

    public function hasSENARole(?User $user): bool
    {
        return (bool) $user && $user->hasRole('SENA');
    }

    public function isSepManager(?User $user): bool
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

    public function hasTacheManagerRole(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $workflow = app(TacheWorkflowService::class);

        return $this->hasDirecteurOrDafRole($user)
            || $this->isDepartmentManager($user)
            || $user->hasRole('SEN')
            || $user->hasRole('SENA')
            || $this->isSepManager($user)
            || $workflow->isSelManager($user)
            || $workflow->isLocalSupport($user);
    }

    public function isDafByDepartment(?User $user): bool
    {
        if (!$user || !$user->agent || !$user->agent->departement) {
            return false;
        }

        $deptCode = strtoupper($user->agent->departement->code ?? '');

        return $deptCode === 'DAF';
    }

    public function isDepartmentManager(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = $this->normalize($user->role?->nom_role);

        return in_array($role, [
            'directeur',
            'directrice',
            'directeur de departement',
            'directrice de departement',
            'daf',
            'assistant',
            'assistant de departement',
            'secretaire de departement',
        ], true) || str_starts_with($role, 'assistant');
    }

    public function isSuperAdmin(?User $user): bool
    {
        return (bool) $user && $user->isSuperAdmin();
    }

    public function hasGlobalAdminAccess(?User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user && $user->hasRole([
            'Section ressources humaines',
            'Chef Section RH',
            'RH National',
            'Section Nouvelle Technologie',
            'Chef Section Nouvelle Technologie',
            'Chef de Section Nouvelle Technologie',
            'SEN',
            'DAF',
        ]);
    }

    private function isDepartmentPrincipalRole(User $user): bool
    {
        $role = $this->normalize($user->role?->nom_role);

        return in_array($role, [
            'directeur',
            'directrice',
            'directeur de departement',
            'directrice de departement',
        ], true);
    }

    private function normalize(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }
}
