<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Tache;
use App\Models\User;
use App\Services\TacheWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TacheValidationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private TacheWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = app(TacheWorkflowService::class);
    }

    public function test_submission_activates_first_step_and_only_its_validator_can_act(): void
    {
        [$task, $target, $firstValidator, $secondValidator] = $this->taskWithTwoSteps();
        $firstUser = User::factory()->create(['agent_id' => $firstValidator->id]);
        $secondUser = User::factory()->create(['agent_id' => $secondValidator->id]);

        $this->workflow->submitForValidation($task, $target, 'Travail terminé');
        $task->refresh();

        $this->assertSame('a_valider', $task->validation_statut);
        $this->assertSame($firstValidator->id, $task->validation_responsable_id);
        $this->assertSame(['active', 'pending'], $task->validationSteps()->pluck('statut')->all());
        $this->assertTrue($this->workflow->canFinalValidate($firstUser, $task));
        $this->assertFalse($this->workflow->canFinalValidate($secondUser, $task));
    }

    public function test_approval_progresses_to_next_step_then_finalizes_task(): void
    {
        [$task, $target, $firstValidator, $secondValidator] = $this->taskWithTwoSteps();
        $this->workflow->submitForValidation($task, $target);

        $isFinal = $this->workflow->validateTask($task, $firstValidator, 'Première étape validée');
        $task->refresh();

        $this->assertFalse($isFinal);
        $this->assertSame('a_valider', $task->validation_statut);
        $this->assertSame($secondValidator->id, $task->validation_responsable_id);
        $this->assertSame(['approved', 'active'], $task->validationSteps()->pluck('statut')->all());

        $isFinal = $this->workflow->validateTask($task, $secondValidator, 'Validation finale');
        $task->refresh();

        $this->assertTrue($isFinal);
        $this->assertSame('validee', $task->validation_statut);
        $this->assertSame($secondValidator->id, $task->validated_by);
        $this->assertSame(['approved', 'approved'], $task->validationSteps()->pluck('statut')->all());
    }

    public function test_rejection_rejects_active_step_and_cancels_following_steps(): void
    {
        [$task, $target, $firstValidator] = $this->taskWithTwoSteps();
        $this->workflow->submitForValidation($task, $target);

        $this->workflow->rejectTask($task, $firstValidator, 'Correction demandée');
        $task->refresh();

        $this->assertSame('en_cours', $task->statut);
        $this->assertSame('rejetee', $task->validation_statut);
        $this->assertSame($firstValidator->id, $task->rejected_by);
        $this->assertSame(['rejected', 'cancelled'], $task->validationSteps()->pluck('statut')->all());
    }

    public function test_legacy_task_without_steps_keeps_designated_validator_fallback(): void
    {
        $creator = Agent::factory()->active()->create();
        $target = Agent::factory()->active()->create();
        $validator = Agent::factory()->active()->create();
        $otherValidator = Agent::factory()->active()->create();
        $task = $this->task($creator, $target, $validator);
        $validatorUser = User::factory()->create(['agent_id' => $validator->id]);
        $otherUser = User::factory()->create(['agent_id' => $otherValidator->id]);

        $this->workflow->submitForValidation($task, $target);
        $task->refresh();

        $this->assertTrue($this->workflow->canFinalValidate($validatorUser, $task));
        $this->assertFalse($this->workflow->canFinalValidate($otherUser, $task));
        $this->assertTrue($this->workflow->validateTask($task, $validator));
        $this->assertSame('validee', $task->refresh()->validation_statut);
    }

    private function taskWithTwoSteps(): array
    {
        $creator = Agent::factory()->active()->create();
        $target = Agent::factory()->active()->create();
        $firstValidator = Agent::factory()->active()->create();
        $secondValidator = Agent::factory()->active()->create();
        $task = $this->task($creator, $target, $firstValidator);

        $task->validationSteps()->createMany([
            [
                'step_order' => 1,
                'step_code' => 'section_manager',
                'validator_agent_id' => $firstValidator->id,
                'structure_type' => 'section',
                'structure_id' => 10,
            ],
            [
                'step_order' => 2,
                'step_code' => 'department_director',
                'validator_agent_id' => $secondValidator->id,
                'structure_type' => 'department',
                'structure_id' => 20,
            ],
        ]);

        return [$task, $target, $firstValidator, $secondValidator];
    }

    private function task(Agent $creator, Agent $target, Agent $validator): Tache
    {
        return Tache::create([
            'createur_id' => $creator->id,
            'agent_id' => $target->id,
            'titre' => 'Tâche de validation',
            'priorite' => 'normale',
            'statut' => 'en_cours',
            'pourcentage' => 50,
            'validation_statut' => 'non_requise',
            'validation_responsable_id' => $validator->id,
        ]);
    }
}