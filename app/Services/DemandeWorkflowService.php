<?php

namespace App\Services;

use App\Models\Request as RequestModel;
use App\Models\Agent;
use App\Models\User;

class DemandeWorkflowService
{
    /**
     * Workflow steps in order.
     * Each step maps to: validated_by_{step}, validated_at_{step}
     */
    private const STEPS = ['director', 'rh', 'sep', 'sen'];

    /**
     * Roles allowed to validate each step.
     */
    private const STEP_ROLES = [
        'director' => [
            'Directeur',
            'Chef Section RH',
            'Chef Section Renforcement',
            'Chef Cellule Renforcement',
            'Chef Section Planification',
            'Chef Section Juridique',
        ],
        'rh' => [
            'Section ressources humaines',
            'Chef Section RH',
            'RH National',
            'RH Provincial',
        ],
        'sep' => [
            'SEP',
        ],
        'sen' => [
            'SEN',
            'SENA',
        ],
    ];

    /**
     * Permission codes for each validation step.
     */
    private const STEP_PERMISSIONS = [
        'director' => 'demande.validate_director',
        'rh'       => 'demande.validate_rh',
        'sep'      => 'demande.validate_sep',
        'sen'      => 'demande.validate_sen',
    ];

    /**
     * Get the current pending step for a request.
     */
    public function getCurrentStep(RequestModel $request): ?string
    {
        return $request->current_step;
    }

    /**
     * Get the full workflow status for display.
     */
    public function getWorkflowStatus(RequestModel $request): array
    {
        $steps = [];

        foreach (self::STEPS as $step) {
            $validatedBy = $request->{"validated_by_{$step}"};
            $validatedAt = $request->{"validated_at_{$step}"};

            $validator = null;
            if ($validatedBy) {
                $agent = Agent::select('id', 'nom', 'prenom', 'poste_actuel')->find($validatedBy);
                $validator = $agent ? [
                    'id' => $agent->id,
                    'nom_complet' => $agent->prenom . ' ' . $agent->nom,
                    'poste' => $agent->poste_actuel,
                ] : null;
            }

            $status = 'pending';
            if ($validatedBy) {
                $status = 'validated';
            } elseif ($request->statut === 'rejeté') {
                // If rejected and this step has no validator, it's the rejection point
                if ($request->current_step === $step) {
                    $status = 'rejected';
                } else {
                    $status = 'skipped';
                }
            } elseif ($request->current_step === $step) {
                $status = 'current';
            } elseif ($this->isStepAfter($step, $request->current_step)) {
                $status = 'waiting';
            }

            $steps[] = [
                'step' => $step,
                'label' => $this->getStepLabel($step),
                'status' => $status,
                'validator' => $validator,
                'validated_at' => $validatedAt?->toISOString(),
            ];
        }

        return $steps;
    }

    /**
     * Check if the user can validate the current step of a request.
     */
    public function canValidate(User $user, RequestModel $request): bool
    {
        if ($request->statut !== 'en_attente') {
            return false;
        }

        $currentStep = $request->current_step;
        if (!$currentStep || !in_array($currentStep, self::STEPS)) {
            return false;
        }

        // Interdire l'auto-approbation : un agent ne peut pas valider sa propre demande
        if ($user->agent?->id && $user->agent->id === (int) $request->agent_id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check permission first
        $permissionCode = self::STEP_PERMISSIONS[$currentStep] ?? null;
        if ($permissionCode && $user->hasPermission($permissionCode)) {
            return true;
        }

        // Fallback: check role
        $allowedRoles = self::STEP_ROLES[$currentStep] ?? [];
        return $user->hasRole($allowedRoles);
    }

    /**
     * Check if the user can validate a specific workflow step (regardless of request state).
     * Useful for filtering requests by validatable steps.
     */
    public function canValidateAtStep(User $user, string $step): bool
    {
        if (!in_array($step, self::STEPS)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissionCode = self::STEP_PERMISSIONS[$step] ?? null;
        if ($permissionCode && $user->hasPermission($permissionCode)) {
            return true;
        }

        $allowedRoles = self::STEP_ROLES[$step] ?? [];
        return $user->hasRole($allowedRoles);
    }

    /**
     * Return the list of workflow steps this user is authorized to validate.
     */
    public function getValidatableSteps(User $user): array
    {
        return array_values(array_filter(self::STEPS, fn($step) => $this->canValidateAtStep($user, $step)));
    }

    /**
     * Validate (approve) the current step.
     */
    public function approve(User $user, RequestModel $request): array
    {
        if (!$this->canValidate($user, $request)) {
            return ['success' => false, 'message' => 'Vous n\'êtes pas autorisé à valider cette étape.'];
        }

        $currentStep = $request->current_step;
        $agent = $user->agent;

        if (!$agent) {
            return ['success' => false, 'message' => 'Votre compte n\'est pas associé à un agent.'];
        }

        $request->{"validated_by_{$currentStep}"} = $agent->id;
        $request->{"validated_at_{$currentStep}"} = now();

        // Move to next step or approve
        $nextStep = $this->getNextStep($currentStep);
        if ($nextStep) {
            $request->current_step = $nextStep;
        } else {
            // All steps completed → approve
            $request->statut = 'approuvé';
            $request->current_step = null;
        }

        $request->save();

        // Notify
        $this->notifyStepCompleted($request, $currentStep, $user, 'approved');

        return [
            'success' => true,
            'message' => $nextStep
                ? 'Étape validée. La demande passe à l\'étape suivante.'
                : 'Demande approuvée définitivement.',
            'next_step' => $nextStep,
            'statut' => $request->statut,
        ];
    }

    /**
     * Reject the request at the current step.
     */
    public function reject(User $user, RequestModel $request, ?string $remarques = null): array
    {
        if (!$this->canValidate($user, $request)) {
            return ['success' => false, 'message' => 'Vous n\'êtes pas autorisé à rejeter cette demande.'];
        }

        $agent = $user->agent;
        if (!$agent) {
            return ['success' => false, 'message' => 'Votre compte n\'est pas associé à un agent.'];
        }

        $currentStep = $request->current_step;

        $request->statut = 'rejeté';
        $request->{"validated_by_{$currentStep}"} = $agent->id;
        $request->{"validated_at_{$currentStep}"} = now();

        if ($remarques) {
            $request->remarques = $remarques;
        }

        $request->save();

        // Notify
        $this->notifyStepCompleted($request, $currentStep, $user, 'rejected');

        return [
            'success' => true,
            'message' => 'Demande rejetée à l\'étape ' . $this->getStepLabel($currentStep) . '.',
        ];
    }

    /**
     * Initialize workflow for a new request.
     */
    public function initializeWorkflow(RequestModel $request): void
    {
        $request->current_step = self::STEPS[0]; // Start at 'director'
        $request->save();
    }

    /**
     * Get the next step after the given step.
     */
    private function getNextStep(string $currentStep): ?string
    {
        $index = array_search($currentStep, self::STEPS);
        if ($index === false || $index >= count(self::STEPS) - 1) {
            return null;
        }
        return self::STEPS[$index + 1];
    }

    /**
     * Check if $step comes after $referenceStep in the workflow.
     */
    private function isStepAfter(string $step, ?string $referenceStep): bool
    {
        if (!$referenceStep) return false;
        $stepIndex = array_search($step, self::STEPS);
        $refIndex = array_search($referenceStep, self::STEPS);
        return $stepIndex !== false && $refIndex !== false && $stepIndex > $refIndex;
    }

    /**
     * Get human label for a workflow step.
     */
    private function getStepLabel(string $step): string
    {
        return match ($step) {
            'director' => 'Chef de Section',
            'rh'       => 'Ressources Humaines',
            'sep'      => 'SEP',
            'sen'      => 'SEN',
            default    => ucfirst($step),
        };
    }

    /**
     * Send notifications after a step is completed.
     */
    private function notifyStepCompleted(RequestModel $request, string $step, User $actionBy, string $action): void
    {
        $agent = $request->agent;
        if (!$agent) return;

        $agentUser = User::where('agent_id', $agent->id)->first();
        if (!$agentUser) return;

        $stepLabel = $this->getStepLabel($step);

        if ($action === 'approved') {
            $nextStep = $this->getNextStep($step);
            if ($nextStep) {
                // Notify agent: step validated, moving to next
                NotificationService::envoyer(
                    $agentUser->id,
                    'demande_validee_etape',
                    'Demande validée - ' . $stepLabel,
                    'Votre demande de ' . $request->type . ' a été validée par ' . $stepLabel . '. Elle est maintenant en attente de validation par ' . $this->getStepLabel($nextStep) . '.',
                    '/requests/' . $request->id,
                    $actionBy->id
                );

                // Notify next step validators
                $this->notifyNextStepValidators($request, $nextStep, $actionBy);
            } else {
                // Final approval
                NotificationService::envoyer(
                    $agentUser->id,
                    'demande_approuvee',
                    'Demande approuvée',
                    'Votre demande de ' . $request->type . ' a été approuvée par toutes les instances.',
                    '/requests/' . $request->id,
                    $actionBy->id
                );
            }
        } else {
            // Rejected
            NotificationService::envoyer(
                $agentUser->id,
                'demande_rejetee',
                'Demande rejetée - ' . $stepLabel,
                'Votre demande de ' . $request->type . ' a été rejetée à l\'étape ' . $stepLabel . '.',
                '/requests/' . $request->id,
                $actionBy->id
            );
        }
    }

    /**
     * Notify users who can validate the next step.
     */
    private function notifyNextStepValidators(RequestModel $request, string $step, User $actionBy): void
    {
        $agent = $request->agent;
        $nomAgent = $agent ? $agent->prenom . ' ' . $agent->nom : 'Un agent';

        // Notify based on role
        $roles = self::STEP_ROLES[$step] ?? [];
        if (empty($roles)) return;

        NotificationService::notifierRH(
            'demande_validation_requise',
            'Validation requise - ' . $this->getStepLabel($step),
            'La demande de ' . $request->type . ' de ' . $nomAgent . ' nécessite votre validation.',
            '/requests/' . $request->id,
            $actionBy->id
        );
    }
}
