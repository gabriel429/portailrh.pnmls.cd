<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        $rhRole = Role::firstOrCreate(['name' => 'RH National']);
        $this->rhUser->roles()->attach($rhRole->id);

        // Create normal user
        $this->normalUser = User::factory()->create();
        $agentRole = Role::firstOrCreate(['name' => 'Agent']);
        $this->normalUser->roles()->attach($agentRole->id);
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
                             'id',
                             'matricule',
                             'nom',
                             'prenom',
                             'email',
                             'telephone'
                         ]
                     ],
                     'links',
                     'meta'
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
        Storage::fake('public');

        $agentData = [
            'matricule' => 'PNM-TEST001',
            'nom' => 'Test',
            'prenom' => 'Agent',
            'postnom' => 'Creation',
            'sexe' => 'M',
            'date_naissance' => '1990-01-15',
            'lieu_naissance' => 'Kinshasa',
            'nationalite' => 'Congolaise',
            'email' => 'test.agent@pnmls.cd',
            'telephone' => '+243815555555',
            'adresse' => '123 Avenue de la Paix',
            'photo' => UploadedFile::fake()->image('photo.jpg')
        ];

        $response = $this->postJson('/api/agents', $agentData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'matricule',
                         'nom',
                         'prenom'
                     ]
                 ]);

        $this->assertDatabaseHas('agents', [
            'matricule' => 'PNM-TEST001',
            'email' => 'test.agent@pnmls.cd'
        ]);

        Storage::disk('public')->assertExists('agents/photos');
    }

    /**
     * Test agent update
     */
    public function test_rh_can_update_agent()
    {
        Sanctum::actingAs($this->rhUser);

        $agent = Agent::factory()->create([
            'nom' => 'OldName'
        ]);

        $updateData = [
            'nom' => 'NewName',
            'prenom' => 'UpdatedPrenom',
            'telephone' => '+243825555555'
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

        $agent = Agent::factory()->create();

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

        Agent::factory()->create(['nom' => 'Kabamba', 'prenom' => 'Jean']);
        Agent::factory()->create(['nom' => 'Mutua', 'prenom' => 'Marie']);
        Agent::factory()->create(['nom' => 'Mbuyi', 'prenom' => 'Pierre']);

        $response = $this->getJson('/api/agents?search=Kabamba');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.nom', 'Kabamba');
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
                 ->assertJsonValidationErrors([matricule', 'nom', 'prenom']);
    }

    /**
     * Test unique matricule constraint
     */
    public function test_cannot_create_agent_with_duplicate_matricule()
    {
        Sanctum::actingAs($this->rhUser);

        Agent::factory()->create(['matricule' => 'PNM-DUP001']);

        $response = $this->postJson('/api/agents', [
            'matricule' => 'PNM-DUP001',
            'nom' => 'Test',
            'prenom' => 'Agent',
            'sexe' => 'M'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['matricule']);
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