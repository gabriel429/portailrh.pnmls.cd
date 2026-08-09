<?php

namespace App\Services;

use App\Models\HolidayPlanning;
use App\Models\User;
use Illuminate\Support\Str;

class HolidayPlanningWorkflowService
{
    public function levelFor(string $structureType): string
    {
        return match ($structureType) {
            'sep' => 'provincial',
            'local' => 'local',
            default => 'national',
        };
    }

    public function canInitiate(User $user, string $level, int $structureId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($level) {
            'national' => $this->isDepartmentAssistant($user)
                && (int) $user->agent?->departement_id === $structureId,
            'provincial' => app(RoleService::class)->isProvincialCafManager($user)
                && (int) $user->agent?->province_id === $structureId,
            'local' => app(TacheWorkflowService::class)->isLocalSupport($user)
                && $this->belongsToLocalScope($user, $structureId),
            default => false,
        };
    }

    public function responsibilityFor(User $user): ?array
    {
        $agent = $user->agent;
        if (!$agent || $agent->statut !== 'actif') {
            return null;
        }

        if ($this->isDepartmentAssistant($user) && $agent->departement_id) {
            $profile = $this->profile($user);

            return [
                'level' => 'national',
                'type' => 'department',
                'structure_id' => (int) $agent->departement_id,
                'label' => $agent->departement?->nom
                    ?? (str_contains($profile, 'direction') ? 'Direction' : 'Département'),
            ];
        }

        if (app(RoleService::class)->isProvincialCafManager($user) && $agent->province_id) {
            return [
                'level' => 'provincial',
                'type' => 'sep',
                'structure_id' => (int) $agent->province_id,
                'label' => $agent->province?->nom ?? 'Province',
            ];
        }

        if (app(TacheWorkflowService::class)->isLocalSupport($user) && $agent->province_id) {
            return [
                'level' => 'local',
                'type' => 'local',
                'structure_id' => (int) $agent->province_id,
                'label' => $agent->province?->nom ?? 'SEL',
            ];
        }

        return null;
    }

    public function canAccessModule(User $user): bool
    {
        return app(UserDataScope::class)->hasGlobalHolidayAccess($user)
            || $this->isRh($user)
            || $this->isDepartmentAssistant($user)
            || $this->isDepartmentDirector($user)
            || app(RoleService::class)->isProvincialCafManager($user)
            || app(RoleService::class)->isSepManager($user)
            || app(TacheWorkflowService::class)->isLocalSupport($user)
            || app(TacheWorkflowService::class)->isSelManager($user);
    }

    public function isRh(User $user): bool
    {
        return $user->hasRole([
            'RH National',
            'RH Provincial',
            'Section ressources humaines',
            'Chef Section RH',
        ]);
    }

    public function canValidate(User $user, HolidayPlanning $planning): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($planning->niveau_administratif) {
            'national' => $this->isDepartmentDirector($user)
                && (int) $user->agent?->departement_id === (int) $planning->structure_id,
            'provincial' => app(RoleService::class)->isSepManager($user)
                && (int) $user->agent?->province_id === (int) $planning->structure_id,
            'local' => app(TacheWorkflowService::class)->isSelManager($user)
                && $this->belongsToLocalScope($user, (int) $planning->structure_id),
            default => false,
        };
    }

    public function canEdit(User $user, HolidayPlanning $planning): bool
    {
        return $planning->statut === HolidayPlanning::STATUT_BROUILLON
            && $this->canInitiate($user, $planning->niveau_administratif, (int) $planning->structure_id);
    }

    public function canSubmit(User $user, HolidayPlanning $planning): bool
    {
        return $this->canEdit($user, $planning);
    }

    public function capabilities(User $user, HolidayPlanning $planning): array
    {
        return [
            'can_edit' => $this->canEdit($user, $planning),
            'can_submit' => $this->canSubmit($user, $planning),
            'can_validate' => $planning->statut === HolidayPlanning::STATUT_SOUMIS
                && $this->canValidate($user, $planning),
            'can_delete' => $planning->statut === HolidayPlanning::STATUT_BROUILLON
                && $this->canEdit($user, $planning),
            'read_only' => $this->isRh($user),
        ];
    }

    private function isDepartmentAssistant(User $user): bool
    {
        $profile = $this->profile($user);

        return str_contains($profile, 'assistant de departement')
            || str_contains($profile, 'assistant du departement')
            || str_contains($profile, 'assistant de direction')
            || str_contains($profile, 'assistante de direction')
            || str_contains($profile, 'secretaire de direction')
            || str_contains($profile, 'secretaire de departement');
    }

    private function isDepartmentDirector(User $user): bool
    {
        $role = $this->normalize($user->role?->nom_role);

        return in_array($role, [
            'directeur',
            'directrice',
            'directeur de departement',
            'directrice de departement',
            'daf',
        ], true);
    }

    private function belongsToLocalScope(User $user, int $structureId): bool
    {
        $agent = $user->agent;

        return (int) ($agent?->localite_id ?? 0) === $structureId
            || (int) ($agent?->province_id ?? 0) === $structureId;
    }

    private function profile(User $user): string
    {
        return $this->normalize(implode(' ', [
            $user->role?->nom_role,
            $user->agent?->fonction,
            $user->agent?->poste_actuel,
        ]));
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim(Str::ascii((string) $value)));
    }
}