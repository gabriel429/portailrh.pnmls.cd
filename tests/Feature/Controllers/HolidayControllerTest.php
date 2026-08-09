<?php

namespace Tests\Feature\Controllers;

use App\Events\CongeApproved;
use App\Events\CongeRequested;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HolidayControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $rhUser;
    private Agent $rhAgent;
    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([CongeApproved::class, CongeRequested::class]);

        $rhRole = Role::firstOrCreate(['nom_role' => 'RH National']);
        $agentRole = Role::firstOrCreate(['nom_role' => 'Agent']);

        $this->rhAgent = Agent::factory()->create([
            'organe' => 'Secrétariat Exécutif National',
            'role_id' => $rhRole->id,
            'statut' => 'actif',
        ]);
        $this->rhUser = User::factory()->create([
            'agent_id' => $this->rhAgent->id,
            'role_id' => $rhRole->id,
        ]);
        $this->agent = Agent::factory()->create([
            'organe' => 'Secrétariat Exécutif National',
            'role_id' => $agentRole->id,
            'statut' => 'actif',
        ]);

        Sanctum::actingAs($this->rhUser);
    }

    public function test_can_list_holidays(): void
    {
        Holiday::factory()->count(5)->create();

        $this->getJson('/api/holidays')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'agent_id', 'date_debut', 'date_fin', 'statut_demande'],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_can_create_holiday_request(): void
    {
        [$dateDebut, $dateFin] = $this->futureWorkingWeek();

        $response = $this->postJson('/api/holidays', [
            'agent_id' => $this->agent->id,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'type_conge' => 'annuel',
            'motif' => 'Congé annuel réglementaire',
        ]);

        $response->assertCreated()
            ->assertJsonPath('holiday.agent_id', $this->agent->id)
            ->assertJsonPath('holiday.type_conge', 'annuel')
            ->assertJsonPath('holiday.statut_demande', 'en_attente');

        $this->assertDatabaseHas('holidays', [
            'agent_id' => $this->agent->id,
            'type_conge' => 'annuel',
            'statut_demande' => 'en_attente',
            'demande_par' => $this->rhAgent->id,
        ]);
    }

    public function test_standard_agent_can_create_own_holiday_request(): void
    {
        $agentUser = User::factory()->create([
            'agent_id' => $this->agent->id,
            'role_id' => $this->agent->role_id,
        ]);
        Sanctum::actingAs($agentUser);
        [$dateDebut, $dateFin] = $this->futureWorkingWeek();
        $planning = HolidayPlanning::create([
            'annee' => Carbon::parse($dateDebut)->year,
            'type_structure' => 'sen',
            'structure_id' => 1,
            'nom_structure' => 'SEN',
            'jours_conge_totaux' => 30,
            'statut' => HolidayPlanning::STATUT_VALIDE,
            'valide' => true,
            'created_by' => $this->rhAgent->id,
        ]);
        $plannedHoliday = Holiday::factory()->pending()->create([
            'agent_id' => $this->agent->id,
            'holiday_planning_id' => $planning->id,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'nombre_jours' => 5,
            'type_conge' => 'annuel',
            'motif' => 'Période proposée',
        ]);

        $this->postJson('/api/my-holiday', [
            'date_debut' => Carbon::parse($dateDebut)->addWeek()->toDateString(),
            'date_fin' => Carbon::parse($dateFin)->addWeek()->toDateString(),
            'type_conge' => 'annuel',
            'motif' => 'Autre période souhaitée',
        ])->assertUnprocessable()
            ->assertJsonPath('suggested_holiday.id', $plannedHoliday->id)
            ->assertJsonPath('suggested_holiday.date_debut', $dateDebut)
            ->assertJsonValidationErrors(['date_debut', 'date_fin']);

        $this->postJson('/api/my-holiday', [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'type_conge' => 'annuel',
            'motif' => 'Congé annuel réglementaire',
        ])->assertOk()
            ->assertJsonPath('holiday.agent_id', $this->agent->id)
            ->assertJsonPath('holiday.id', $plannedHoliday->id)
            ->assertJsonPath('holiday.statut_demande', 'en_attente');

        $this->assertDatabaseHas('holidays', [
            'agent_id' => $this->agent->id,
            'type_conge' => 'annuel',
            'statut_demande' => 'en_attente',
            'demande_par' => $this->agent->id,
            'motif' => 'Congé annuel réglementaire',
        ]);
        $this->assertDatabaseCount('holidays', 1);
        Event::assertDispatched(
            CongeRequested::class,
            fn (CongeRequested $event) => $event->holiday->agent_id === $this->agent->id,
        );
    }

    public function test_standard_agent_cannot_request_annual_leave_without_validated_planning(): void
    {
        $agentUser = User::factory()->create([
            'agent_id' => $this->agent->id,
            'role_id' => $this->agent->role_id,
        ]);
        Sanctum::actingAs($agentUser);
        [$dateDebut, $dateFin] = $this->futureWorkingWeek();

        $this->postJson('/api/my-holiday', [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'type_conge' => 'annuel',
            'motif' => 'Congé sans planning',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('date_debut')
            ->assertJsonFragment([
                'message' => 'Aucun planning de congés validé n’est disponible pour votre structure. Consultez le planning de référence défini en amont ou contactez la Section RH.',
            ]);

        $this->assertDatabaseCount('holidays', 0);
    }

    public function test_agent_receives_upstream_reference_planning_when_department_planning_is_unavailable(): void
    {
        $department = Department::create([
            'code' => 'REF',
            'nom' => 'Département sans planning',
            'description' => 'Département utilisé pour tester le planning de référence.',
        ]);
        $this->agent->update(['departement_id' => $department->id]);
        $agentUser = User::factory()->create([
            'agent_id' => $this->agent->id,
            'role_id' => $this->agent->role_id,
        ]);
        $referencePlanning = HolidayPlanning::create([
            'annee' => now()->year,
            'type_structure' => 'sen',
            'structure_id' => 1,
            'nom_structure' => 'Planning SEN de référence',
            'jours_conge_totaux' => 30,
            'statut' => HolidayPlanning::STATUT_VALIDE,
            'valide' => true,
            'created_by' => $this->rhAgent->id,
        ]);
        Sanctum::actingAs($agentUser);

        $this->getJson('/api/mon-planning-conges?year=' . now()->year)
            ->assertOk()
            ->assertJsonPath('planning', null)
            ->assertJsonPath('reference_planning.id', $referencePlanning->id)
            ->assertJsonPath('reference_planning.nom_structure', 'Planning SEN de référence');
    }

    public function test_can_approve_holiday(): void
    {
        $holiday = $this->pendingHoliday();

        $this->postJson("/api/holidays/{$holiday->id}/approve")
            ->assertOk()
            ->assertJsonPath('holiday.statut_demande', 'approuve');

        $this->assertDatabaseHas('holidays', [
            'id' => $holiday->id,
            'statut_demande' => 'approuve',
            'approuve_par' => $this->rhAgent->id,
        ]);
    }

    public function test_can_refuse_holiday(): void
    {
        $holiday = $this->pendingHoliday();

        $this->postJson("/api/holidays/{$holiday->id}/refuse", [
            'motif_refus' => 'Effectif insuffisant',
        ])->assertOk()
            ->assertJsonPath('holiday.statut_demande', 'refuse');

        $this->assertDatabaseHas('holidays', [
            'id' => $holiday->id,
            'statut_demande' => 'refuse',
            'refuse_par' => $this->rhAgent->id,
            'commentaire_refus' => 'Effectif insuffisant',
        ]);
    }

    public function test_holiday_requires_valid_dates(): void
    {
        $this->postJson('/api/holidays', [
            'agent_id' => $this->agent->id,
            'date_debut' => now()->addDays(10)->toDateString(),
            'date_fin' => now()->addDays(5)->toDateString(),
            'type_conge' => 'annuel',
            'motif' => 'Dates invalides',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['date_fin']);
    }

    public function test_detects_overlapping_approved_holidays(): void
    {
        [$dateDebut, $dateFin] = $this->futureWorkingWeek();

        Holiday::factory()->approved()->create([
            'agent_id' => $this->agent->id,
            'demande_par' => $this->agent->id,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ]);

        $this->postJson('/api/holidays', [
            'agent_id' => $this->agent->id,
            'date_debut' => $dateDebut,
            'date_fin' => Carbon::parse($dateFin)->addDays(3)->toDateString(),
            'type_conge' => 'annuel',
            'motif' => 'Demande en conflit',
        ])->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Conflit de dates : l\'agent a déjà un congé approuvé sur cette période',
            ]);
    }

    public function test_can_get_agent_holiday_statistics(): void
    {
        [$dateDebut, $dateFin] = $this->futureWorkingWeek();

        Holiday::factory()->approved()->create([
            'agent_id' => $this->agent->id,
            'demande_par' => $this->agent->id,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'nombre_jours' => 5,
            'type_conge' => 'annuel',
        ]);
        Holiday::factory()->pending()->create([
            'agent_id' => $this->agent->id,
            'demande_par' => $this->agent->id,
            'date_debut' => Carbon::parse($dateDebut)->addMonth(),
            'date_fin' => Carbon::parse($dateFin)->addMonth(),
            'nombre_jours' => 5,
            'type_conge' => 'annuel',
        ]);

        $this->getJson("/api/agents/{$this->agent->id}/holidays/stats")
            ->assertOk()
            ->assertJsonPath('total_conges', 2)
            ->assertJsonPath('conges_approuves', 1)
            ->assertJsonPath('conges_en_attente', 1)
            ->assertJsonPath('jours_annuels_utilises', 5)
            ->assertJsonPath('jours_annuels_en_attente', 5)
            ->assertJsonPath('jours_restants', 25);
    }

    private function pendingHoliday(): Holiday
    {
        [$dateDebut, $dateFin] = $this->futureWorkingWeek();

        return Holiday::factory()->pending()->create([
            'agent_id' => $this->agent->id,
            'demande_par' => $this->agent->id,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'nombre_jours' => 5,
            'type_conge' => 'annuel',
        ]);
    }

    private function futureWorkingWeek(): array
    {
        $dateDebut = now()->addMonth()->next(Carbon::MONDAY)->startOfDay();
        $dateFin = $dateDebut->copy()->addDays(4);

        return [$dateDebut->toDateString(), $dateFin->toDateString()];
    }
}