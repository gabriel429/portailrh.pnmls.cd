<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Holiday;
use App\Models\Agent;
use App\Models\User;
use\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelumSanctum;

class HolidayControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $agent;

    protected function setUp(): void
    {
        parent::setUp();
        // Create user with RH role
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'RH National']);
        $this->user->roles()->attach($role->
        
        $this->agent = Agent::factory()->create([
            'user_id' => $this->user->id
        ]);
    }

    /**
     * Test listing holidays
     */
    public function test_can_list_holidays()
    {
        Sanctum::actingAs($this->user);

        Holiday::factory()->count(5)->create();

        $response = $this->getJson('/api/holidays');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'agent_id', 'date_debut', 'date_fin', 'statut']
                     ]
                 ]);
    }

    holiday request creation
    */
    public function test_can_create_holiday_request()
    {
        Sanctum::actingAs($this->user);
        
        $holidayData = [
            'agent_id' => $this->agent->id,
            'date_debut' => now()->addDays(7)->format('Y-m-d'),
            'date_fin' => now()->addDays(14)->format('Y-m-d'),
            'type' => 'Congé annuel',
            'motif' => 'Congé annuel réglementaire'
        ];

        $response = $this->postJson('/api/holidays', $holidayData);
        
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => ['id', 'agent_id', 'date_debut', 'date_fin', 'type']
                 ]);

        $this->assertDatabaseHas('holidays', [
            'agent_id' => $this->agent->id,
            'type' => 'Congé annuel'
        ]);
    }

    /**
     * Test approve holiday approval
     */
    public function test_can_approve_holiday()
    {
        Sanctum::actingAs($this->user);
        
        $holiday = Holiday::factory()->create([
            'statut' => 'En attente'
        ]);

        $response = $this->postJson("/api/holidays/{$holiday->id}/approve");
        
        $response->assertStatus(200);

        $holiday->refresh();
        $this->assertEquals('Approuvé', $holiday->statut);
    }

    /**
     * Test refuse holiday
     */
    public function test_can_refuse_holiday()
    {
        Sanctum::actingAs($this->user);
        
        $holiday = Holiday::factory()->create([
            'statut' => 'En attente'
        ]);

        $response = $this->postJson("/api/holidays/{$holiday->id}/refuse", [
            'raison' => 'Effectif insuffisant'
        ]);
        
        $response->assertStatus(200);

        $holiday->refresh();
        $this->assertEquals('Refusé', $holiday->statut);
    }

    /**
     * Test holiday validation rules
     */
    public function test_holiday_requires_valid_dates()
    {
        Sanctum::actingAs($this->user);
        
        // date range
        $holidDataData = [
holidayData = [
            'agent_id' => $this->agent->id,
            'date_debut' => now()->formatDays(5)->format('Y-m-d'),
            'date_fin' => now()->format('Y-m-d'), // Date fin avant date début
            'type' => 'Congé annuel'
        ];

        $response = $this->postJson('/api/holidays', $holidayData);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date_fin']);
    }
Test conflict detection
     */
    public function test_detects_holidayapping_holidays()
    {
        Sanctum::actingAs($this->user);
        
        // Create existing holiday
        Holiday::factory()->create([
            'agent_id' => $this->agent->id,
            'date_debut' => now()->addDays(7)->format('Y-m-d'),
            'date_fin' => now()->addDays(14)->format('Y-m-d'),
            'statut' => 'Approuvé'
        ]);

        // Try to create overlapping holiday
        $holidData = [
            'agent_id' => $this->agent->id,
            'date_debut' => now()->addDays(10)->format('Y-m-d'),
            'date_fin' => now()->addDays(20)->format('Y-m-d'),
            'type' => 'Congé annuel'
        ];

        $response = $this->postJson('/api/holidays', $holidayData);
        
        $response->assertStatus(422); // Should detect conflict
    }

    /**
     * Test agent holiday statistics
     */
    public function test_can_get_agent_holiday_statistics()
    {
        Sanctum::actingAs($this->user);

        Holiday::factory()->create([
            'agent_id' => $this->agent->id,
            'statut' => 'Approuvé',
            'nombre_jours' => 14
        ]);

        Holiday::factory()->create([
            'agent_id' => $this->agent->id,
            'statut' => 'Refusé',
            'nombre_jours' => 7
        ]);

        $response = $this->getJson("/api/agents/{$this->agent->id}/holidays/stats");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total_pris',
                     'total_restant',
                     'total_demande',
                     'annee'
                 ]);
    }
}