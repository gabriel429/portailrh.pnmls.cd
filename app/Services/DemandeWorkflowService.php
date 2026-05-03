<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Request as RequestModel;
use App\Models\RequestValidationHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DemandeWorkflowService
{
    private const WORKFLOWS = [
        'national_with_department' => ['director', 'rh', 'sen'],
        'national_sen_direct' => ['sen'],
        'provincial' => ['caf', 'sep'],
        'local' => ['aaf', 'sep'],
    ];

    private const STEP_ROLE_LABELS = [
        'director' => 'Directeur de departement',
        'rh' => 'Section ressources humaines',
        'caf' => 'CAF',
        'aaf' => 'Assistant Administratif et Financier',
        'sep' => 'SEP',
        'sen' => 'SEN',
    ];

    private const STEP_PERMISSIONS = [
        'director' => 'demande.validate_director',
        'rh' => 'demande.validate_rh',
        'caf' => 'demande.validate_sep',
        'aaf' => 'demande.validate_sep',
        'sep' => 'demande.validate_sep',
        'sen' => 'demande.validate_sen',
    ];

    public function getCurrentStep(RequestModel $request): ?string
    {
        return $request->current_step;
    }

    public function getWorkflowStatus(RequestModel $request): array
    {
        $steps = [];

        foreach ($this->getWorkflowSteps($request) as $step) {
            $validatedBy = $request->{"validated_by_{$step}"};
            $validatedAt = $request->{"validated_at_{$step}"};

            $validator = null;
            if ($validatedBy) {
                $agent = Agent::select('id', 'nom', 'prenom', 'poste_actuel')->find($validatedBy);
                $validator = $agent ? [
                    'id' => $agent->id,
                    'nom_complet' => trim(($agent->prenom ?? '') . ' ' . ($agent->nom ?? '')),
                    'poste' => $agent->poste_actuel,
                ] : null;
            }

            $status = 'pending';
            if ($validatedBy) {
                $status = 'validated';
            } elseif ($request->statut === 'rejeté') {
                $status = $request->current_step === $step ? 'rejected' : 'skipped';
            } elseif ($request->current_step === $step) {
                $status = 'current';
            } elseif ($this->isStepAfter($request, $step, $request->current_step)) {
                $status = 'waiting';
            }

            $steps[] = [
                'step' => $step,
                'label' => $this->getStepLabel($step, $request),
                'status' => $status,
                'validator' => $validator,
                'validated_at' => $validatedAt?->toISOString(),
            ];
        }

        return $steps;
    }

    public function canValidate(User $user, RequestModel $request): bool
    {
        if ($request->statut !== 'en_attente') {
            return false;
        }

        $currentStep = $request->current_step;
        if (!$currentStep || !in_array($currentStep, $this->getWorkflowSteps($request), true)) {
            return false;
        }

        if ($user->agent?->id && $user->agent->id === (int) $request->agent_id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($permissionCode = (self::STEP_PERMISSIONS[$currentStep] ?? null)) {
            if ($user->hasPermission($permissionCode) && $this->matchesStepScope($user, $request, $currentStep)) {
                return true;
            }
        }

        return $this->matchesStepScope($user, $request, $currentStep);
    }

    public function canValidateAtStep(User $user, string $step): bool
    {
        if (!isset(self::STEP_ROLE_LABELS[$step])) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissionCode = self::STEP_PERMISSIONS[$step] ?? null;
        if ($permissionCode && $user->hasPermission($permissionCode)) {
            return true;
        }

        return $this->hasStepRole($user, $step);
    }

    public function getValidatableSteps(User $user): array
    {
        return array_values(array_filter(array_keys(self::STEP_ROLE_LABELS), fn($step) => $this->canValidateAtStep($user, $step)));
    }

    public function applyValidationInboxScope(Builder $query, User $user, array $steps): Builder
    {
        $agentId = $user->agent?->id;

        return $query->where(function (Builder $outer) use ($user, $steps, $agentId) {
            if ($agentId) {
                $outer->where('agent_id', $agentId);
            }

            foreach ($steps as $step) {
                $outer->orWhere(function (Builder $stepQuery) use ($user, $step) {
                    $stepQuery->where('current_step', $step);

                    if (in_array($step, ['caf', 'sep', 'aaf'], true)) {
                        $provinceId = $user->agent?->province_id;
                        $stepQuery->whereHas('agent', fn(Builder $agentQuery) => $agentQuery->where('province_id', $provinceId));
                    } elseif ($step === 'director') {
                        $deptId = $user->agent?->departement_id;
                        $stepQuery->whereHas('agent', fn(Builder $agentQuery) => $agentQuery->where('departement_id', $deptId));
                    } elseif ($step === 'sen') {
                        $stepQuery->whereHas('agent', function (Builder $agentQuery) {
                            $agentQuery->where(function (Builder $scope) {
                                $scope->whereNull('departement_id')
                                    ->orWhere('organe', 'Secrétariat Exécutif National');
                            });
                        });
                    }
                });
            }
        });
    }

    public function approve(User $user, RequestModel $request): array
    {
        if (!$this->canValidate($user, $request)) {
            return ['success' => false, 'message' => 'Vous n etes pas autorise a valider cette etape.'];
        }

        $currentStep = $request->current_step;
        $agent = $user->agent;

        if (!$agent) {
            return ['success' => false, 'message' => 'Votre compte n est pas associe a un agent.'];
        }

        $request->{"validated_by_{$currentStep}"} = $agent->id;
        $request->{"validated_at_{$currentStep}"} = now();

        $nextStep = $this->getNextStep($request, $currentStep);
        if ($nextStep) {
            $request->current_step = $nextStep;
        } else {
            $request->statut = 'approuvé';
            $request->current_step = null;
        }

        $request->save();

        $this->recordHistory($request, $user, $currentStep, 'approved');
        $this->notifyStepCompleted($request, $currentStep, $user, 'approved');

        return [
            'success' => true,
            'message' => $nextStep
                ? 'Etape validee. La demande passe a l etape suivante.'
                : 'Demande approuvee definitivement.',
            'next_step' => $nextStep,
            'statut' => $request->statut,
        ];
    }

    public function reject(User $user, RequestModel $request, ?string $remarques = null): array
    {
        if (!$this->canValidate($user, $request)) {
            return ['success' => false, 'message' => 'Vous n etes pas autorise a rejeter cette demande.'];
        }

        $agent = $user->agent;
        if (!$agent) {
            return ['success' => false, 'message' => 'Votre compte n est pas associe a un agent.'];
        }

        $currentStep = $request->current_step;
        $request->statut = 'rejeté';
        $request->{"validated_by_{$currentStep}"} = $agent->id;
        $request->{"validated_at_{$currentStep}"} = now();

        if ($remarques) {
            $request->remarques = $remarques;
        }

        $request->save();

        $this->recordHistory($request, $user, $currentStep, 'rejected', $remarques);
        $this->notifyStepCompleted($request, $currentStep, $user, 'rejected');

        return [
            'success' => true,
            'message' => 'Demande rejetee a l etape ' . $this->getStepLabel($currentStep, $request) . '.',
        ];
    }

    public function initializeWorkflow(RequestModel $request): void
    {
        $workflowLevel = $this->resolveWorkflowLevel($request);
        $steps = self::WORKFLOWS[$workflowLevel] ?? self::WORKFLOWS['national_with_department'];

        $request->workflow_level = $workflowLevel;
        $request->current_step = $steps[0] ?? null;
        $request->save();
    }

    private function getWorkflowSteps(RequestModel $request): array
    {
        $level = $request->workflow_level ?: $this->resolveWorkflowLevel($request);
        return self::WORKFLOWS[$level] ?? self::WORKFLOWS['national_with_department'];
    }

    private function getNextStep(RequestModel $request, string $currentStep): ?string
    {
        $steps = $this->getWorkflowSteps($request);
        $index = array_search($currentStep, $steps, true);

        if ($index === false || $index >= count($steps) - 1) {
            return null;
        }

        return $steps[$index + 1];
    }

    private function isStepAfter(RequestModel $request, string $step, ?string $referenceStep): bool
    {
        if (!$referenceStep) {
            return false;
        }

        $steps = $this->getWorkflowSteps($request);
        $stepIndex = array_search($step, $steps, true);
        $refIndex = array_search($referenceStep, $steps, true);

        return $stepIndex !== false && $refIndex !== false && $stepIndex > $refIndex;
    }

    private function getStepLabel(string $step, ?RequestModel $request = null): string
    {
        if ($step === 'sen' && $request && $request->workflow_level === 'national_sen_direct') {
            return 'Assistant de direction / SEN';
        }

        if ($step === 'sep' && $request && in_array($request->workflow_level, ['provincial', 'local'], true)) {
            return 'SEP';
        }

        return self::STEP_ROLE_LABELS[$step] ?? ucfirst($step);
    }

    private function notifyStepCompleted(RequestModel $request, string $step, User $actionBy, string $action): void
    {
        $request->loadMissing('agent.user');
        $agent = $request->agent;
        $agentUser = $agent?->user;
        $stepLabel = $this->getStepLabel($step, $request);

        if ($agentUser) {
            if ($action === 'approved') {
                $nextStep = $this->getNextStep($request, $step);
                if ($nextStep) {
                    NotificationService::envoyer(
                        $agentUser->id,
                        'demande_modifiee',
                        'Demande validee - ' . $stepLabel,
                        'Votre demande de ' . $request->type . ' a ete validee par ' . $stepLabel . '.',
                        '/requests/' . $request->id,
                        $actionBy->id
                    );
                } else {
                    NotificationService::envoyer(
                        $agentUser->id,
                        'demande_approuvee',
                        'Demande approuvee',
                        'Votre demande de ' . $request->type . ' a ete approuvee par toutes les instances.',
                        '/requests/' . $request->id,
                        $actionBy->id
                    );
                }
            } else {
                NotificationService::envoyer(
                    $agentUser->id,
                    'demande_rejetee',
                    'Demande rejetee - ' . $stepLabel,
                    'Votre demande de ' . $request->type . ' a ete rejetee a l etape ' . $stepLabel . '.',
                    '/requests/' . $request->id,
                    $actionBy->id
                );
            }
        }

        if ($action === 'approved') {
            $nextStep = $this->getNextStep($request, $step);
            if ($nextStep) {
                $this->notifyNextStepValidators($request, $nextStep, $actionBy);
            }
        }
    }

    public function notifyNextStepValidators(RequestModel $request, string $step, User $actionBy): void
    {
        $request->loadMissing('agent.departement', 'agent.province');
        $agent = $request->agent;
        $nomAgent = $agent ? trim(($agent->prenom ?? '') . ' ' . ($agent->nom ?? '')) : 'Un agent';

        $validators = $this->resolveStepValidators($request, $step);
        $userIds = $validators->pluck('id')->unique()->values()->all();

        if (empty($userIds)) {
            return;
        }

        NotificationService::envoyerMultiple(
            $userIds,
            'demande',
            'Validation requise - ' . $this->getStepLabel($step, $request),
            'La demande de ' . $request->type . ' de ' . $nomAgent . ' necessite votre validation.',
            '/requests/' . $request->id,
            $actionBy->id
        );
    }

    private function resolveWorkflowLevel(RequestModel $request): string
    {
        $request->loadMissing('agent.departement', 'agent.province');
        $agent = $request->agent;

        $organe = strtolower((string) ($agent?->organe ?? ''));
        $hasDepartment = !empty($agent?->departement_id);

        if (str_contains($organe, 'local')) {
            return 'local';
        }

        if (str_contains($organe, 'provincial')) {
            return 'provincial';
        }

        if (!$hasDepartment) {
            return 'national_sen_direct';
        }

        return 'national_with_department';
    }

    private function matchesStepScope(User $user, RequestModel $request, string $step): bool
    {
        if (!$this->hasStepRole($user, $step)) {
            return false;
        }

        $request->loadMissing('agent.departement', 'agent.province');
        $agent = $request->agent;
        $validatorAgent = $user->agent;
        $role = $this->normalizeValue($user->role?->nom_role);

        return match ($step) {
            'director' => (int) ($validatorAgent?->departement_id ?? 0) === (int) ($agent?->departement_id ?? 0),
            'rh' => true,
            'caf' => (int) ($validatorAgent?->province_id ?? 0) === (int) ($agent?->province_id ?? 0),
            'aaf' => str_contains((string) ($validatorAgent?->organe ?? ''), 'Local')
                && (int) ($validatorAgent?->province_id ?? 0) === (int) ($agent?->province_id ?? 0),
            'sep' => (int) ($validatorAgent?->province_id ?? 0) === (int) ($agent?->province_id ?? 0),
            'sen' => in_array($role, ['sen', 'sena'], true),
            default => false,
        };
    }

    private function hasStepRole(User $user, string $step): bool
    {
        $role = $this->normalizeValue($user->role?->nom_role);
        $deptCode = $this->normalizeValue($user->agent?->departement?->code);
        $deptName = $this->normalizeValue($user->agent?->departement?->nom);
        $fonction = $this->normalizeValue($user->agent?->fonction);
        $poste = $this->normalizeValue($user->agent?->poste_actuel);

        return match ($step) {
            'director' => in_array($role, [
                'directeur',
                'directrice',
                'directeur de departement',
                'directrice de departement',
                'assistant',
                'assistant de departement',
                'secretaire de departement',
            ], true) || str_starts_with($role, 'assistant'),
            'rh' => in_array($role, [
                'section ressources humaines',
                'chef section rh',
                'rh national',
                'rh provincial',
            ], true),
            'caf' => in_array($role, ['caf', 'chef caf', 'responsable caf'], true)
                || $deptCode === 'caf'
                || str_contains($deptName, 'cellule administrative et financ'),
            'aaf' => str_contains($fonction, 'assistant administratif et financier')
                || str_contains($poste, 'assistant administratif et financier'),
            'sep' => in_array($role, ['sep', 'secom'], true),
            'sen' => in_array($role, ['sen', 'sena'], true),
            default => false,
        };
    }

    private function resolveStepValidators(RequestModel $request, string $step)
    {
        $request->loadMissing('agent.departement', 'agent.province');
        $agent = $request->agent;

        $query = User::query()->with('agent.departement');

        return match ($step) {
            'director' => $query
                ->whereHas('agent', fn($q) => $q->where('departement_id', $agent?->departement_id))
                ->get()
                ->filter(fn(User $user) => $this->hasStepRole($user, 'director')),
            'rh' => $query
                ->whereHas('role', fn($q) => $q->whereIn('nom_role', [
                    'Section ressources humaines',
                    'Chef Section RH',
                    'RH National',
                    'RH Provincial',
                ]))
                ->get(),
            'caf' => $query
                ->whereHas('agent', fn($q) => $q->where('province_id', $agent?->province_id))
                ->get()
                ->filter(fn(User $user) => $this->hasStepRole($user, 'caf')),
            'aaf' => $query
                ->whereHas('agent', fn($q) => $q->where('province_id', $agent?->province_id)->where('organe', 'Secrétariat Exécutif Local'))
                ->get()
                ->filter(fn(User $user) => $this->hasStepRole($user, 'aaf')),
            'sep' => $query
                ->whereHas('agent', fn($q) => $q->where('province_id', $agent?->province_id))
                ->get()
                ->filter(fn(User $user) => $this->hasStepRole($user, 'sep')),
            'sen' => $query
                ->whereHas('role', fn($q) => $q->whereIn('nom_role', ['SEN', 'SENA']))
                ->get(),
            default => collect(),
        };
    }

    private function recordHistory(RequestModel $request, User $user, string $step, string $action, ?string $commentaire = null): void
    {
        RequestValidationHistory::create([
            'request_id' => $request->id,
            'agent_id' => $user->agent?->id,
            'user_id' => $user->id,
            'step' => $step,
            'action' => $action,
            'role_label' => $this->getStepLabel($step, $request),
            'commentaire' => $commentaire,
            'acted_at' => now(),
        ]);
    }

    private function normalizeValue(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }
}
