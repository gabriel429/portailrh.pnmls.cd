<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Agent;
use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test agent creation
     */
    public function test_can_create_agent(): void
    {
        $agent = Agent::factory()->create([
            'matricule_etat' => 'PNM-TEST001',
            'nom' => 'Test',
            'prenom' => 'Agent',
            'email_professionnel' => 'test.agent@pnmls.cd',
        ]);

        $this->assertDatabaseHas('agents', [
            'matricule_etat' => 'PNM-TEST001',
            'email_professionnel' => 'test.agent@pnmls.cd',
        ]);

        $this->assertEquals('Test', $agent->nom);
        $this->assertEquals('Agent', $agent->prenom);
    }

    /**
     * Test agent full name accessor
     */
    public function test_agent_full_name_accessor(): void
    {
        $agent = Agent::factory()->make([
            'nom' => 'Kabamba',
            'prenom' => 'Jean',
            'postnom' => 'Pierre'
        ]);

        $this->assertEquals('Jean Kabamba', $agent->nom_complet);
    }

    /**
     * Test agent relationships
     */
    public function test_agent_has_one_user(): void
    {
        $agent = Agent::factory()->create();
        $user = User::factory()->create(['agent_id' => $agent->id]);

        $this->assertInstanceOf(User::class, $agent->user);
        $this->assertTrue($user->is($agent->user));
    }

    /**
     * Test agent department relationship
     */
    public function test_agent_belongs_to_department(): void
    {
        $department = Department::create([
            'code' => 'TEST',
            'nom' => 'Département test',
        ]);
        $agent = Agent::factory()->create([
            'departement_id' => $department->id,
        ]);

        $this->assertInstanceOf(Department::class, $agent->departement);
        $this->assertTrue($department->is($agent->departement));
    }

    /**
     * Test agent active scope
     */
    public function test_agent_active_scope(): void
    {
        Agent::factory()->count(3)->create(['statut' => 'actif']);
        Agent::factory()->count(2)->create(['statut' => 'ancien']);

        $this->assertCount(3, Agent::actifs()->get());
    }

    /**
     * Test agent search functionality
     */
    public function test_agent_search_by_matricule(): void
    {
        $agent = Agent::factory()->create([
            'matricule_etat' => 'PNM-SEARCH001',
        ]);

        $found = Agent::where('matricule_etat', 'LIKE', '%SEARCH%')->first();

        $this->assertNotNull($found);
        $this->assertEquals('PNM-SEARCH001', $found->matricule_etat);
    }

    /**
     * Test agent soft deletes
     */
    public function test_agent_soft_deletes(): void
    {
        $agent = Agent::factory()->create();
        $agentId = $agent->id;

        $agent->delete();

        $this->assertSoftDeleted('agents', ['id' => $agentId]);

        $trashedAgent = Agent::withTrashed()->find($agentId);
        $this->assertNotNull($trashedAgent);
    }
}