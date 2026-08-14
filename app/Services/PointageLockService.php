<?php

namespace App\Services;

use App\Models\Pointage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PointageLockService
{
    public function __construct(
        private RoleService $roles,
        private UserDataScope $scope,
    ) {
    }

    public function isRestrictedOperator(?User $user): bool
    {
        if (!$user || $this->roles->isSuperAdmin($user)) {
            return false;
        }

        if ($this->roles->isAssistantRh($user) || $user->hasRole('SECOM')) {
            return true;
        }

        if ($this->scope->isLocalUser($user) && !$user->hasRole('SEL')) {
            return true;
        }

        $profile = $this->profileText($user);

        return str_contains($profile, 'assistant')
            && !$user->hasRole(['SEN', 'SEP', 'SEL', 'RH National', 'RH Provincial']);
    }

    public function canCorrectTimes(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isRestrictedOperator($user)) {
            return false;
        }

        return $this->roles->isSuperAdmin($user)
            || $this->scope->hasGlobalAdminAccess($user)
            || $user->hasRole(['RH Provincial', 'SEP', 'SEL'])
            || $this->roles->hasDirecteurOrDafRole($user);
    }

    public function actualTime(?Pointage $pointage, string $field): ?string
    {
        if (!$pointage || !in_array($field, ['heure_entree', 'heure_sortie'], true)) {
            return null;
        }

        $value = $pointage->{$field};
        if (!$value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            $formatted = Carbon::parse($value)->format('H:i');
        } else {
            $formatted = substr(trim((string) $value), 0, 5);
        }

        return $this->isActualTime($formatted) ? $formatted : null;
    }

    public function fieldLocked(?Pointage $pointage, string $field): bool
    {
        return $this->actualTime($pointage, $field) !== null;
    }

    public function locksPayload(?Pointage $pointage, ?User $user): array
    {
        $entryLocked = $this->fieldLocked($pointage, 'heure_entree');
        $exitLocked = $this->fieldLocked($pointage, 'heure_sortie');

        return [
            'heure_entree_verrouillee' => $entryLocked,
            'heure_sortie_verrouillee' => $exitLocked,
            'can_edit_heure_entree' => !$entryLocked,
            'can_edit_heure_sortie' => $entryLocked && !$exitLocked,
            'can_correct_times' => $this->canCorrectTimes($user),
            'correction_requires_motif' => $entryLocked || $exitLocked,
        ];
    }

    private function isActualTime(string $time): bool
    {
        return $time !== '' && $time !== '-' && $time !== '00:00';
    }

    private function profileText(User $user): string
    {
        $agent = $user->agent;

        $parts = [
            $user->role?->nom_role,
            $agent?->fonction,
            $agent?->poste_actuel,
            $agent?->departement?->code,
            $agent?->departement?->nom,
            $agent?->organe,
        ];

        return trim(implode(' ', array_map(fn ($value) => $this->normalize($value), $parts)));
    }

    private function normalize(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }
}
