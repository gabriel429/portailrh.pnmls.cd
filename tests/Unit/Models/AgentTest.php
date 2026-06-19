<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Agent;
use App\Models\User;
use App\Models\Department;
use App\Models\Grade;
use App\Models\Fonction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test agent creation
     */
    public function test_can_create_agent()
    {
        $agent = Agent::factory()->create([
            'matricule' => 'PNM-TEST001',
            'nom' => 'Test',
            'prenom' => 'Agent',
            'email' => 'test.agent@pnmls.cd'
        ]);

        $this->assertDatabaseHas('agents', [
            'matricule' => 'PNM-TEST001',
            'email' => 'test.agent@pnmls.cd'
        ]);

        $this->assertEquals('Test', $agent->nom);
        $this->assertEquals('Agent', $agent->prenom);
    }

    /**
     * Test agent full name accessor
     */
    public function test_agent_full_name_accessor()
    {
        $agent = Agent::factory()->make([
            'nom' => 'Kabamba',
            'prenom' => 'Jean',
            'postnom' => 'Pierre'
        ]);

        $this->assertEquals('Kabamba Jean Pierre', $agent->nom_complet);
    }

    /**
     * Test agent relationships
     */
    public function test_agent_belongs_to_user()
    {
        $user = User::factory()->create();
        $agent = Agent::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $agent->user);
        $this->assertEquals($user->id, $agent->user->id);
    }

    /**
     * Test agent department relationship
     */
    public function test_agent_belongs_to_department()
    {
        $department = \App\Models\Department::factory()->create();
        $agent = Agent::factory()->create([
            'department_id' => $department->id
        ]);

        $this->assertInstanceOf(\App\Models\Department::class, $agent->department);
        $this->assertEquals($department->id, $agent->department->id);
    }

    /**
     * Test agent active scope
     */
    public function test_agent_active_scope()
    {
        // Create active agents
        Agent::factory()->count(3)->create(['statut' => 'Actif']);
        
        // Create inactive agents
        Agent::factory()->count(2)->create(['statut' => 'Inactif']);

        $activeAgents = Agent::where('statut', 'Actif')->get();
        
        $this->assertCount(3, $activeAgents);
    }

    /**
     * Test agent search functionality
     */
    public function test_agent_search_by_matricule()
    {
        $agent = Agent::factory()->create([
            'matricule' => 'PNM-SEARCH001'
        ]);

        $found = Agent::where('matricule', 'LIKE', '%SEARCH%')->first();
        
        $this->assertNotNull($found);
        $this->assertEquals('PNM-SEARCH001', $found->matricule);
    }

    /**
     * Test agent soft deletes
     */
    public function test_agent_soft_deletes()
    {
        $agent = Agent::factory()->create();
        $agentId = $agent->id;

        $agent->delete();

        $this->assertSoftDeleted('agents', ['id' => $agentId]);
        
        // Agent should still exist in trashed
        $trashedAgent = Agent::withTrashed()->find($agentId);
        $this->assertNotNull($trashedAgent);
    }
}