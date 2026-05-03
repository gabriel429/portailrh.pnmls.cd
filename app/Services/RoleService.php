<?php

namespace App\Services;

use App\Models\User;
use App\Models\Agent;

/**
 * Service centralisé pour la vérification des rôles.
 * Évite la duplication de la logique de vérification dans les contrôleurs.
 */
class RoleService
{
    /**
     * Vérifie si l'utilisateur (ou son agent) a un ou plusieurs rôles.
     */
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

    /**
     * Vérifie si l'utilisateur a un rôle de gestionnaire de département (Directeur ou DAF).
     */
    public function hasDirecteurOrDafRole(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole('Directeur') || $user->hasRole('DAF');
    }

    /**
     * Vérifie si l'utilisateur est SENA (assistante de direction du SEN).
     */
    public function hasSENARole(?User $user): bool
    {
        if (!$user) return false;
        return $user->hasRole('SENA');
    }

    /**
     * Vérifie si l'utilisateur pilote le périmètre provincial SEP.
     */
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

    /**
     * Vérifie si l'utilisateur peut créer/gérer des tâches (Directeur, DAF, SEN, SENA).
     */
    public function hasTacheManagerRole(?User $user): bool
    {
        if (!$user) return false;
        return $this->hasDirecteurOrDafRole($user)
            || $this->isDepartmentManager($user)
            || $user->hasRole('SEN')
            || $user->hasRole('SENA')
            || $this->isSepManager($user);
    }

    /**
     * Vérifie si le département de l'agent est le DAF (via le code du département).
     * Utile pour les directeurs qui n'ont pas explicitement le rôle DAF.
     */
    public function isDafByDepartment(?User $user): bool
    {
        if (!$user || !$user->agent || !$user->agent->departement) {
            return false;
        }

        $deptCode = strtoupper($user->agent->departement->code ?? '');

        return $deptCode === 'DAF';
    }

    /**
     * Vérifie si l'utilisateur est un gestionnaire départemental
     * (peut voir/gérer les agents et tâches de son département).
     */
    public function isDepartmentManager(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = strtolower($user->role?->nom_role ?? '');

        return in_array($role, ['directeur', 'directeur de département', 'daf', 'assistant', 'assistant de département'])
            || str_starts_with($role, 'assistant');
    }

    /**
     * Vérifie si l'utilisateur est SuperAdmin.
     */
    public function isSuperAdmin(?User $user): bool
    {
        return $user && $user->isSuperAdmin();
    }

    /**
     * Vérifie si l'utilisateur a un accès global admin (RH, NT, SEN, DAF).
     */
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
}
