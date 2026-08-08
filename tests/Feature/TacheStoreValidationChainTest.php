<?php

namespace Tests\Feature;

use App\Events\TacheAssigned;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Cellule;
use App\Models\Department;
use App\Models\Fonction;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TacheStoreValidationChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_resolves_validator_without_client_supplied_validator(): void
    {
        Event::fake([TacheAssigned::class]);
        [$target, $manager] = $this->cellAgentAndManagers(1);
        $user = User::factory()->create(['agent_id' => $target->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/taches', $this->validPayload($target));

        $response->assertCreated();
        $this->assertDatabaseHas('taches', [
            'agent_id' => $target->id,
            'validation_responsable_id' => $manager->id,
        ]);
        $this->assertDatabaseHas('tache_validation_steps', [
            'step_order' => 1,
            'step_code' => 'cell_manager',
            'validator_agent_id' => $manager->id,
            'statut' => 'pending',
        ]);
    }

    public function test_store_rejects_ambiguous_hierarchy_without_creating_task(): void
    {
        Event::fake([TacheAssigned::class]);
        [$target] = $this->cellAgentAndManagers(2);
        $user = User::factory()->create(['agent_id' => $target->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/taches', $this->validPayload($target));

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'TASK_HIERARCHY_AMBIGUOUS')
            ->assertJsonValidationErrors('agent_id');
        $this->assertDatabaseCount('taches', 0);
        $this->assertDatabaseCount('tache_validation_steps', 0);
    }

    private function cellAgentAndManagers(int $managerCount): array
    {
        $suffix = bin2hex(random_bytes(4));
        $department = Department::create(['code' => "DEP-{$suffix}", 'nom' => "Département {$suffix}"]);
        $section = Section::create([
            'code' => "SEC-{$suffix}",
            'nom' => "Section {$suffix}",
            'department_id' => $department->id,
        ]);
        $cell = Cellule::create([
            'code' => "CEL-{$suffix}",
            'nom' => "Cellule {$suffix}",
            'section_id' => $section->id,
        ]);
        $agentFunction = $this->function("Agent {$suffix}", false);
        $target = Agent::factory()->active()->create(['departement_id' => $department->id]);
        $this->assignment($target, $agentFunction, $department, $section, $cell);
        $managers = [];

        for ($index = 0; $index < $managerCount; $index++) {
            $manager = Agent::factory()->active()->create(['departement_id' => $department->id]);
            $this->assignment($manager, $this->function("Chef {$suffix}-{$index}", true), $department, $section, $cell);
            $managers[] = $manager;
        }

        return [$target, ...$managers];
    }

    private function function(string $name, bool $isManager): Fonction
    {
        return Fonction::create([
            'nom' => $name,
            'niveau_administratif' => 'SEN',
            'type_poste' => 'cellule',
            'est_chef' => $isManager,
        ]);
    }

    private function assignment(
        Agent $agent,
        Fonction $function,
        Department $department,
        Section $section,
        Cellule $cell,
    ): void {
        Affectation::create([
            'agent_id' => $agent->id,
            'fonction_id' => $function->id,
            'niveau_administratif' => 'SEN',
            'niveau' => 'cellule',
            'department_id' => $department->id,
            'section_id' => $section->id,
            'cellule_id' => $cell->id,
            'date_debut' => now()->subMonth(),
            'actif' => true,
        ]);
    }

    private function validPayload(Agent $target): array
    {
        return [
            'agent_id' => $target->id,
            'titre' => 'Tâche hiérarchique',
            'description' => 'Validation automatique du responsable.',
            'source_type' => 'hors_pta',
            'source_emetteur' => 'autre',
            'priorite' => 'normale',
        ];
    }
}