<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\Fonction;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    protected $rhUser;
    protected $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create RH user
        $this->rhUser = User::factory()->create();
        $rhRole = Role::firstOrCreate(['nom_role' => 'RH National']);
        $this->rhUser->update(['role_id' => $rhRole->id]);
        $rhAgent = Agent::factory()->create([
            'organe' => 'Secrétariat Exécutif National',
            'role_id' => $rhRole->id,
            'statut' => 'actif',
        ]);
        $this->rhUser->update(['agent_id' => $rhAgent->id]);

        // Create normal user
        $this->normalUser = User::factory()->create();
        $agentRole = Role::firstOrCreate(['nom_role' => 'Agent']);
        $this->normalUser->update(['role_id' => $agentRole->id]);
    }

    /**
     * Test that RH users can list agents
     */
    public function test_rh_users_can_list_agents()
    {
        Sanctum::actingAs($this->rhUser);

        Agent::factory()->count(15)->create();

        $response = $this->getJson('/api/agents');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'label',
                             'agents' => [
                                 '*' => [
                                     'id',
                                     'matricule_etat',
                                     'nom',
                                     'prenom',
                                     'email_prive',
                                     'email_professionnel',
                                     'telephone',
                                 ],
                             ],
                             'pagination',
                         ]
                     ],
                     'meta' => ['stats', 'pagination'],
                 ]);
    }

    /**
     * Test that normal users cannot list agents
     */
    public function test_normal_users_cannot_list_agents()
    {
        Sanctum::actingAs($this->normalUser);

        $response = $this->getJson('/api/agents');

        $response->assertStatus(403);
    }

    /**
     * Test agent creation with valid data
     */
    public function test_rh_can_create_agent()
    {
        Sanctum::actingAs($this->rhUser);
        Fonction::create(['nom' => 'Chargé de test']);

        $agentData = [
            'matricule_etat' => 'PNM-TEST001',
            'nom' => 'Test',
            'prenom' => 'Agent',
            'postnom' => 'Creation',
            'sexe' => 'M',
            'annee_naissance' => 1990,
            'date_naissance' => '1990-01-15',
            'lieu_naissance' => 'Kinshasa',
            'email_professionnel' => 'test.agent@pnmls.cd',
            'telephone' => '+243815555555',
            'adresse' => '123 Avenue de la Paix',
            'organe' => 'Secrétariat Exécutif National',
            'fonction' => 'Chargé de test',
            'niveau_etudes' => 'Licence',
            'annee_engagement_programme' => 2020,
        ];

        $response = $this->postJson('/api/agents', $agentData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'id',
                         'matricule_etat',
                         'nom',
                         'prenom'
                     ],
                     'agent',
                 ]);

        $this->assertDatabaseHas('agents', [
            'matricule_etat' => 'PNM-TEST001',
            'email_professionnel' => 'test.agent@pnmls.cd'
        ]);
    }

    /**
     * Test agent update
     */
    public function test_rh_can_update_agent()
    {
        Sanctum::actingAs($this->rhUser);
        Fonction::create(['nom' => 'Fonction mise à jour']);

        $agent = Agent::factory()->create([
            'nom' => 'OldName',
            'organe' => 'Secrétariat Exécutif National',
            'fonction' => 'Fonction mise à jour',
            'niveau_etudes' => 'Licence',
            'annee_naissance' => 1990,
            'annee_engagement_programme' => 2020,
            'statut' => 'actif',
        ]);

        $updateData = [
            'nom' => 'NewName',
            'prenom' => 'UpdatedPrenom',
            'telephone' => '+243825555555',
            'annee_naissance' => 1990,
            'lieu_naissance' => $agent->lieu_naissance,
            'sexe' => $agent->sexe,
            'organe' => $agent->organe,
            'fonction' => $agent->fonction,
            'niveau_etudes' => $agent->niveau_etudes,
            'annee_engagement_programme' => 2020,
            'statut' => $agent->statut,
        ];

        $response = $this->putJson("/api/agents/{$agent->id}", $updateData);

        $response->assertStatus(200);

        $agent->refresh();
        $this->assertEquals('NewName', $agent->nom);
        $this->assertEquals('UpdatedPrenom', $agent->prenom);
    }

    /**
     * Test agent deletion
     */
    public function test_rh_can_delete_agent()
    {
        Sanctum::actingAs($this->rhUser);

        $agent = Agent::factory()->create([
            'organe' => 'Secrétariat Exécutif National',
            'statut' => 'actif',
        ]);

        $response = $this->deleteJson("/api/agents/{$agent->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('agents', ['id' => $agent->id]);
    }

    /**
     * Test agent search functionality
     */
    public function test_can_search_agents_by_name()
    {
        Sanctum::actingAs($this->rhUser);

        $scope = ['organe' => 'Secrétariat Exécutif National', 'statut' => 'actif'];
        Agent::factory()->create([...$scope, 'nom' => 'Kabamba', 'prenom' => 'Jean']);
        Agent::factory()->create([...$scope, 'nom' => 'Mutua', 'prenom' => 'Marie']);
        Agent::factory()->create([...$scope, 'nom' => 'Mbuyi', 'prenom' => 'Pierre']);

        $response = $this->getJson('/api/agents?search=Kabamba');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonCount(1, 'data.0.agents')
                 ->assertJsonPath('data.0.agents.0.nom', 'Kabamba');
    }

    /**
     * Test agent export
     */
    public function test_rh_can_export_agents()
    {
        Sanctum::actingAs($this->rhUser);

        Agent::factory()->count(5)->create();

        $response = $this->get('/api/agents/export');

        $response->assertStatus(200);
        $response->assertDownload();
    }

    /**
     * Test agent validation rules
     */
    public function test_agent_creation_requires_required_fields()
    {
        Sanctum::actingAs($this->rhUser);

        $response = $this->postJson('/api/agents', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'nom',
                     'prenom',
                     'annee_naissance',
                     'lieu_naissance',
                     'sexe',
                     'organe',
                     'fonction',
                     'niveau_etudes',
                     'annee_engagement_programme',
                 ]);
    }

    /**
     * Test unique matricule constraint
     */
    public function test_cannot_create_agent_with_duplicate_matricule()
    {
        Sanctum::actingAs($this->rhUser);

        Fonction::create(['nom' => 'Fonction doublon']);
        Agent::factory()->create(['matricule_etat' => 'PNM-DUP001']);

        $response = $this->postJson('/api/agents', [
            'matricule_etat' => 'PNM-DUP001',
            'nom' => 'Test',
            'prenom' => 'Agent',
            'sexe' => 'M',
            'annee_naissance' => 1990,
            'lieu_naissance' => 'Kinshasa',
            'organe' => 'Secrétariat Exécutif National',
            'fonction' => 'Fonction doublon',
            'niveau_etudes' => 'Licence',
            'annee_engagement_programme' => 2020,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['matricule_etat']);
    }

    /**
     * Test agent form options endpoint
     */
    public function test_can_get_agent_form_options()
    {
        Sanctum::actingAs($this->rhUser);

        $response = $this->getJson('/api/agents/form-options');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'departments',
                     'grades',
                     'fonctions',
                     'provinces'
                 ]);
    }
}