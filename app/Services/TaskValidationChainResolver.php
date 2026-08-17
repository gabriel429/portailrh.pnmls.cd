<?php

namespace App\Services;

use App\Exceptions\TaskHierarchyException;
use App\Models\Affectation;
use App\Models\Agent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaskValidationChainResolver
{
    public function resolve(Agent $target): array
    {
        $assignment = Affectation::with(['fonction', 'section.department', 'cellule.section.department'])
            ->where('agent_id', $target->id)
            ->where('actif', true)
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get();

        if ($assignment->isEmpty()) {
            throw new TaskHierarchyException(
                "Aucune affectation active n'est configurée pour {$target->nom_complet}.",
            );
        }

        if ($assignment->count() > 1) {
            throw new TaskHierarchyException(
                "Plusieurs affectations actives sont configurées pour {$target->nom_complet}.",
                'TASK_HIERARCHY_AMBIGUOUS',
            );
        }

        $assignment = $assignment->first();

        return match ($assignment->niveau_administratif) {
            'SEP' => [$this->resolveProvincialStep($assignment, $target)],
            'SEL' => [$this->resolveLocalStep($assignment, $target)],
            default => [$this->resolveNationalStep($assignment, $target)],
        };
    }

    private function resolveNationalStep(Affectation $assignment, Agent $target): array
    {
        $isManager = (bool) $assignment->fonction?->est_chef;

        if ($assignment->niveau === 'cellule') {
            if (!$assignment->cellule_id || !$assignment->cellule?->section_id) {
                throw new TaskHierarchyException('La cellule ou sa section parente n’est pas configurée.');
            }

            return $isManager
                ? $this->managerStep('section_manager', 'section', $assignment->cellule->section_id, $target)
                : $this->managerStep('cell_manager', 'cellule', $assignment->cellule_id, $target);
        }

        if ($assignment->niveau === 'section') {
            if (!$assignment->section_id || !$assignment->section?->department_id) {
                throw new TaskHierarchyException('La section ou son département parent n’est pas configuré.');
            }

            return $isManager
                ? $this->managerStep('department_director', 'department', $assignment->section->department_id, $target)
                : $this->managerStep('section_manager', 'section', $assignment->section_id, $target);
        }

        $departmentId = $assignment->department_id
            ?? $assignment->section?->department_id
            ?? $assignment->cellule?->section?->department_id;

        if ($departmentId) {
            if ($isManager || $this->isDepartmentDirector($target)) {
                return $this->senStep($target);
            }

            return $this->managerStep('department_director', 'department', $departmentId, $target);
        }

        return $this->senStep($target);
    }

    private function resolveProvincialStep(Affectation $assignment, Agent $target): array
    {
        if (!$assignment->province_id) {
            throw new TaskHierarchyException('La province de l’affectation SEP n’est pas configurée.');
        }

        $candidates = $this->activeAssignmentsFor('province_id', $assignment->province_id)
            ->filter(fn (Affectation $candidate) => $this->profileContains($candidate, [
                'secretaire executif provincial',
                '(sep)',
            ]) || $this->roleIs($candidate, 'sep'));

        return $this->singleCandidateStep($candidates, 'sep', 'province', $assignment->province_id, $target);
    }

    private function resolveLocalStep(Affectation $assignment, Agent $target): array
    {
        if (!$assignment->localite_id) {
            throw new TaskHierarchyException('La localité de l’affectation SEL n’est pas configurée.');
        }

        $candidates = $this->activeAssignmentsFor('localite_id', $assignment->localite_id)
            ->filter(fn (Affectation $candidate) => $this->profileContains($candidate, [
                'secretaire executif local',
                '(sel)',
            ]) || $this->roleIs($candidate, 'sel'));

        return $this->singleCandidateStep($candidates, 'sel', 'localite', $assignment->localite_id, $target);
    }

    private function managerStep(string $code, string $structureType, int $structureId, Agent $target): array
    {
        $foreignKey = match ($structureType) {
            'cellule' => 'cellule_id',
            'section' => 'section_id',
            default => 'department_id',
        };

        $candidates = $this->activeAssignmentsFor($foreignKey, $structureId)
            ->filter(fn (Affectation $candidate) => (bool) $candidate->fonction?->est_chef);

        return $this->singleCandidateStep($candidates, $code, $structureType, $structureId, $target);
    }

    private function senStep(Agent $target): array
    {
        $candidates = Affectation::with(['agent.role', 'fonction'])
            ->where('actif', true)
            ->where('niveau_administratif', 'SEN')
            ->get()
            ->filter(fn (Affectation $candidate) => $this->isPrincipalSenAssignment($candidate));

        return $this->singleCandidateStep($candidates, 'sen', 'sen', null, $target);
    }

    private function activeAssignmentsFor(string $foreignKey, int $structureId): Collection
    {
        return Affectation::with(['agent.role', 'fonction'])
            ->where('actif', true)
            ->where($foreignKey, $structureId)
            ->get();
    }

    private function singleCandidateStep(
        Collection $candidates,
        string $code,
        string $structureType,
        ?int $structureId,
        Agent $target,
    ): array {
        $candidates = $candidates
            ->filter(fn (Affectation $candidate) => (int) $candidate->agent_id !== (int) $target->id)
            ->unique('agent_id')
            ->values();

        if ($candidates->isEmpty()) {
            throw new TaskHierarchyException(
                "Aucun validateur {$code} actif n'est configuré pour le périmètre de {$target->nom_complet}.",
            );
        }

        if ($candidates->count() > 1) {
            throw new TaskHierarchyException(
                "Plusieurs validateurs {$code} actifs sont configurés pour le même périmètre.",
                'TASK_HIERARCHY_AMBIGUOUS',
            );
        }

        $assignment = $candidates->first();

        return [
            'step_order' => 1,
            'step_code' => $code,
            'validator_agent_id' => (int) $assignment->agent_id,
            'structure_type' => $structureType,
            'structure_id' => $structureId,
            'resolution_metadata' => [
                'source' => 'active_assignment',
                'assignment_id' => $assignment->id,
            ],
        ];
    }

    private function roleIs(Affectation $assignment, string $role): bool
    {
        return $this->normalize($assignment->agent?->role?->nom_role) === $role;
    }

    private function isPrincipalSenAssignment(Affectation $assignment): bool
    {
        $profile = $this->normalize(implode(' ', [
            $assignment->agent?->role?->nom_role,
            $assignment->fonction?->nom,
            $assignment->agent?->fonction,
            $assignment->agent?->poste_actuel,
        ]));

        if (
            str_contains($profile, 'adjoint')
            || str_contains($profile, 'assistant sen/sena')
            || str_contains($profile, 'assistant sena')
        ) {
            return false;
        }

        return $this->roleIs($assignment, 'sen')
            || str_contains($profile, 'secretaire executif national');
    }

    private function profileContains(Affectation $assignment, array $needles): bool
    {
        $profile = $this->normalize(implode(' ', [
            $assignment->fonction?->nom,
            $assignment->agent?->fonction,
            $assignment->agent?->poste_actuel,
        ]));

        return collect($needles)->contains(fn (string $needle) => str_contains($profile, $needle));
    }

    private function isDepartmentDirector(Agent $agent): bool
    {
        $profile = $this->normalize(implode(' ', [
            $agent->role?->nom_role,
            $agent->fonction,
            $agent->poste_actuel,
        ]));

        return str_contains($profile, 'directeur');
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim(Str::ascii((string) $value)));
    }
}
