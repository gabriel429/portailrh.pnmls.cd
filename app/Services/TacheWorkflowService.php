<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Tache;
use App\Models\TacheHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TacheWorkflowService
{
    public function determineManagementLevel(User $user): string
    {
        $agent = $user->agent;
        $organe = $this->normalize($agent?->organe);

        if ($user->hasRole('SEN') || $user->hasRole('SENA') || str_contains($organe, 'national')) {
            return 'sen';
        }

        if (app(RoleService::class)->isSepManager($user) || str_contains($organe, 'provincial')) {
            return 'province';
        }

        if ($this->isSelManager($user) || $this->isLocalSupport($user) || str_contains($organe, 'local')) {
            return 'local';
        }

        return 'departement';
    }

    public function determineValidationRole(User $user): string
    {
        return match ($this->determineManagementLevel($user)) {
            'sen' => 'sen',
            'province' => 'sep',
            'local' => 'sel',
            default => 'directeur',
        };
    }

    public function canPrepareOrManage(User $user): bool
    {
        $roles = app(RoleService::class);

        return $roles->hasDirecteurOrDafRole($user)
            || $roles->isDepartmentManager($user)
            || $user->hasRole('SEN')
            || $user->hasRole('SENA')
            || $roles->isSepManager($user)
            || $this->isLocalSupport($user)
            || $this->isSelManager($user);
    }

    public function canFinalValidate(User $user, Tache $tache): bool
    {
        $agent = $user->agent;
        if (!$agent || $tache->validation_statut !== 'a_valider') {
            return false;
        }

        $taskAgent = $tache->agent ?: Agent::find($tache->agent_id);
        if (!$taskAgent) {
            return false;
        }

        return match ($tache->validation_responsable_role) {
            'directeur' => $this->isDepartmentPrincipal($user)
                && (int) $agent->departement_id === (int) $taskAgent->departement_id,
            'sep' => $this->isSepPrincipal($user)
                && (int) $agent->province_id === (int) $taskAgent->province_id,
            'sel' => $this->isSelManager($user)
                && (int) $agent->province_id === (int) $taskAgent->province_id
                && $this->normalize($agent->organe) === $this->normalize($taskAgent->organe),
            'sen' => $this->isSenPrincipal($user),
            default => false,
        };
    }

    public function submitForValidation(Tache $tache, Agent $agent, ?string $commentaire = null): void
    {
        $oldStatus = $tache->statut;
        $oldValidation = $tache->validation_statut;

        $tache->statut = 'terminee';
        $tache->pourcentage = 100;
        $tache->validation_statut = 'a_valider';
        $tache->validation_commentaire = $commentaire;
        $tache->soumise_validation_at = now();
        $tache->blocked_by = null;
        $tache->blocked_at = null;
        $tache->blocking_reason = null;
        $tache->save();

        $this->recordHistory(
            $tache,
            $agent,
            'soumission_validation',
            'Soumission pour validation',
            $oldStatus,
            $tache->statut,
            $oldValidation,
            $tache->validation_statut,
            $commentaire
        );
    }

    public function validateTask(Tache $tache, Agent $agent, ?string $commentaire = null): void
    {
        $oldValidation = $tache->validation_statut;

        $tache->validation_statut = 'validee';
        $tache->validation_commentaire = $commentaire;
        $tache->validated_by = $agent->id;
        $tache->validated_at = now();
        $tache->rejected_by = null;
        $tache->rejected_at = null;
        $tache->save();

        $this->recordHistory(
            $tache,
            $agent,
            'validation_finale',
            'Validation finale',
            $tache->statut,
            $tache->statut,
            $oldValidation,
            $tache->validation_statut,
            $commentaire
        );
    }

    public function rejectTask(Tache $tache, Agent $agent, ?string $commentaire = null): void
    {
        $oldStatus = $tache->statut;
        $oldValidation = $tache->validation_statut;

        $tache->statut = 'en_cours';
        $tache->validation_statut = 'rejetee';
        $tache->validation_commentaire = $commentaire;
        $tache->rejected_by = $agent->id;
        $tache->rejected_at = now();
        $tache->save();

        $this->recordHistory(
            $tache,
            $agent,
            'rejet_validation',
            'Rejet de validation',
            $oldStatus,
            $tache->statut,
            $oldValidation,
            $tache->validation_statut,
            $commentaire
        );
    }

    public function markBlocked(Tache $tache, Agent $agent, ?string $commentaire = null): void
    {
        $oldStatus = $tache->statut;
        $oldValidation = $tache->validation_statut;

        $tache->statut = 'bloquee';
        $tache->validation_statut = 'non_requise';
        $tache->validation_commentaire = $commentaire;
        $tache->soumise_validation_at = null;
        $tache->blocked_by = $agent->id;
        $tache->blocked_at = now();
        $tache->blocking_reason = $commentaire;
        $tache->save();

        $this->recordHistory(
            $tache,
            $agent,
            'blocage',
            'Blocage signale',
            $oldStatus,
            $tache->statut,
            $oldValidation,
            $tache->validation_statut,
            $commentaire
        );
    }

    public function reopenAfterBlocked(Tache $tache, Agent $agent, ?string $commentaire = null): void
    {
        $oldStatus = $tache->statut;

        $tache->statut = 'en_cours';
        $tache->blocked_by = null;
        $tache->blocked_at = null;
        $tache->blocking_reason = null;
        $tache->save();

        $this->recordHistory(
            $tache,
            $agent,
            'reprise',
            'Reprise de la tache',
            $oldStatus,
            $tache->statut,
            $tache->validation_statut,
            $tache->validation_statut,
            $commentaire
        );
    }

    public function finalValidators(Tache $tache): Collection
    {
        $taskAgent = $tache->agent ?: Agent::find($tache->agent_id);
        if (!$taskAgent) {
            return collect();
        }

        $query = User::query()->with(['role', 'agent.departement']);

        return match ($tache->validation_responsable_role) {
            'directeur' => $query
                ->whereHas('agent', fn($q) => $q->where('departement_id', $taskAgent->departement_id))
                ->get()
                ->filter(fn(User $user) => $this->isDepartmentPrincipal($user)),
            'sep' => $query
                ->whereHas('agent', fn($q) => $q->where('province_id', $taskAgent->province_id))
                ->get()
                ->filter(fn(User $user) => $this->isSepPrincipal($user)),
            'sel' => $query
                ->whereHas('agent', fn($q) => $q->where('province_id', $taskAgent->province_id))
                ->get()
                ->filter(fn(User $user) => $this->isSelManager($user) && $this->normalize($user->agent?->organe) === $this->normalize($taskAgent->organe)),
            'sen' => $query
                ->whereHas('role', fn($q) => $q->where('nom_role', 'SEN'))
                ->get(),
            default => collect(),
        };
    }

    public function recordHistory(
        Tache $tache,
        ?Agent $agent,
        string $action,
        string $label,
        ?string $ancienStatut,
        ?string $nouveauStatut,
        ?string $ancienValidationStatut,
        ?string $nouveauValidationStatut,
        ?string $commentaire = null,
        array $meta = []
    ): void {
        TacheHistory::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent?->id,
            'action' => $action,
            'action_label' => $label,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'ancien_validation_statut' => $ancienValidationStatut,
            'nouveau_validation_statut' => $nouveauValidationStatut,
            'commentaire' => $commentaire,
            'meta' => empty($meta) ? null : $meta,
        ]);
    }

    public function validationRoleLabel(?string $role): string
    {
        return [
            'sen' => 'SEN',
            'directeur' => 'Directeur',
            'sep' => 'SEP',
            'sel' => 'SEL',
        ][$role] ?? 'Responsable';
    }

    public function isDepartmentPrincipal(?User $user): bool
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
        ], true);
    }

    public function isSepPrincipal(?User $user): bool
    {
        return (bool) $user && $this->normalize($user->role?->nom_role) === 'sep';
    }

    public function isSenPrincipal(?User $user): bool
    {
        return (bool) $user && $this->normalize($user->role?->nom_role) === 'sen';
    }

    public function isSelManager(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = $this->normalize($user->role?->nom_role);
        $fonction = $this->normalize($user->agent?->fonction);
        $poste = $this->normalize($user->agent?->poste_actuel);
        $organe = $this->normalize($user->agent?->organe);

        if ($role === 'sel') {
            return true;
        }

        return str_contains($organe, 'local')
            && (
                str_contains($fonction, 'secretaire executif local')
                || str_contains($poste, 'secretaire executif local')
                || str_contains($fonction, '(sel)')
                || str_contains($poste, '(sel)')
            );
    }

    public function isLocalSupport(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $fonction = $this->normalize($user->agent?->fonction);
        $poste = $this->normalize($user->agent?->poste_actuel);
        $organe = $this->normalize($user->agent?->organe);

        return str_contains($organe, 'local')
            && (
                str_contains($fonction, 'assistant administratif et financier')
                || str_contains($poste, 'assistant administratif et financier')
                || str_contains($fonction, 'rh local')
                || str_contains($poste, 'rh local')
            );
    }

    private function normalize(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }
}
