<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerformanceEvaluationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_director_only_sees_own_department_evaluations(): void
    {
        $ownDepartment = $this->department('DIR-A');
        $otherDepartment = $this->department('DIR-B');

        [$directorUser, $directorAgent] = $this->userWithAgentRole('Directeur', [
            'departement_id' => $ownDepartment->id,
        ]);

        $ownAgent = $this->agentWithRole('Agent', [
            'departement_id' => $ownDepartment->id,
        ]);
        $otherAgent = $this->agentWithRole('Agent', [
            'departement_id' => $otherDepartment->id,
        ]);
        $otherEvaluator = $this->agentWithRole('Directeur', [
            'departement_id' => $otherDepartment->id,
        ]);

        $ownEvaluation = $this->evaluation($ownAgent, $directorAgent);
        $otherEvaluation = $this->evaluation($otherAgent, $otherEvaluator);

        Sanctum::actingAs($directorUser);

        $response = $this->getJson('/api/evaluations');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($ownEvaluation->id, $ids);
        $this->assertNotContains($otherEvaluation->id, $ids);

        $this->getJson("/api/evaluations/{$ownEvaluation->id}")
            ->assertOk();

        $this->getJson("/api/evaluations/{$otherEvaluation->id}")
            ->assertForbidden();
    }

    public function test_sen_keeps_global_performance_evaluation_visibility(): void
    {
        $firstDepartment = $this->department('SEN-A');
        $secondDepartment = $this->department('SEN-B');

        [$senUser, $senAgent] = $this->userWithAgentRole('SEN', [
            'organe' => Agent::ORGANES[0],
        ]);

        $firstAgent = $this->agentWithRole('Agent', [
            'departement_id' => $firstDepartment->id,
        ]);
        $secondAgent = $this->agentWithRole('Agent', [
            'departement_id' => $secondDepartment->id,
        ]);

        $firstEvaluation = $this->evaluation($firstAgent, $senAgent);
        $secondEvaluation = $this->evaluation($secondAgent, $senAgent);

        Sanctum::actingAs($senUser);

        $response = $this->getJson('/api/evaluations');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($firstEvaluation->id, $ids);
        $this->assertContains($secondEvaluation->id, $ids);
    }

    private function department(string $code): Department
    {
        return Department::create([
            'code' => $code,
            'nom' => "Departement {$code}",
            'description' => 'Department used by performance scope tests.',
        ]);
    }

    private function userWithAgentRole(string $roleName, array $agentAttributes = []): array
    {
        $agent = $this->agentWithRole($roleName, $agentAttributes);

        $user = User::factory()->create([
            'agent_id' => $agent->id,
            'role_id' => $agent->role_id,
        ])->load('agent.departement', 'role');

        return [$user, $agent];
    }

    private function agentWithRole(string $roleName, array $attributes = []): Agent
    {
        $role = Role::firstOrCreate(['nom_role' => $roleName]);

        return Agent::factory()->active()->create([
            'role_id' => $role->id,
            'organe' => $attributes['organe'] ?? Agent::ORGANES[0],
            ...$attributes,
        ])->load('role');
    }

    private function evaluation(Agent $agent, Agent $evaluateur): Evaluation
    {
        return Evaluation::create([
            'agent_id' => $agent->id,
            'evaluateur_id' => $evaluateur->id,
            'organe' => $agent->organe,
            'province_id' => $agent->province_id,
            'departement_id' => $agent->departement_id,
            'periode_type' => 'annuel',
            'periode_annee' => 2026,
            'periode_trimestre' => 0,
            'statut' => 'brouillon',
            'score_manuel' => 80,
        ]);
    }
}
