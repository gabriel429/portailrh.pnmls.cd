<?php

namespace Tests\Feature\Controllers;

use App\Models\Agent;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HolidayPlanningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_planning_follows_submission_and_validation_workflow(): void
    {
        $department = $this->department('PLAN');
        $assistant = $this->userWithRole('Assistant de Département', [
            'departement_id' => $department->id,
            'fonction' => 'Assistant de Département',
        ]);
        $director = $this->userWithRole('Directeur de département', [
            'departement_id' => $department->id,
        ]);

        Sanctum::actingAs($assistant);
        $planningId = $this->postJson('/api/holiday-plannings', [
            'annee' => now()->year,
            'type_structure' => 'department',
            'structure_id' => $department->id,
            'nom_structure' => $department->nom,
            'jours_conge_totaux' => 30,
        ])->assertCreated()
            ->assertJsonPath('planning.statut', 'brouillon')
            ->json('planning.id');

        $this->postJson("/api/holiday-plannings/{$planningId}/submit")
            ->assertOk()
            ->assertJsonPath('planning.statut', 'soumis');

        Sanctum::actingAs($director);
        $this->postJson("/api/holiday-plannings/{$planningId}/validate")
            ->assertOk()
            ->assertJsonPath('planning.statut', 'valide')
            ->assertJsonPath('planning.valide', true);

        $this->assertDatabaseHas('holiday_plannings', [
            'id' => $planningId,
            'statut' => 'valide',
            'validated_by' => $director->agent->id,
        ]);
    }

    public function test_wrong_department_director_cannot_validate_planning(): void
    {
        $department = $this->department('JUR');
        $otherDepartment = $this->department('COM');
        $director = $this->userWithRole('Directeur de département', [
            'departement_id' => $otherDepartment->id,
        ]);
        $planning = $this->planning($department, HolidayPlanning::STATUT_SOUMIS);

        Sanctum::actingAs($director);

        $this->postJson("/api/holiday-plannings/{$planning->id}/validate")
            ->assertForbidden();
    }

    public function test_provincial_planning_follows_caf_to_sep_workflow(): void
    {
        $province = $this->province('KSH');
        $caf = $this->userWithRole('CAF', ['province_id' => $province->id]);
        $sep = $this->userWithRole('SEP', ['province_id' => $province->id]);

        Sanctum::actingAs($caf);
        $planningId = $this->createAndSubmitPlanning('sep', $province->id, $province->nom);

        Sanctum::actingAs($sep);
        $this->postJson("/api/holiday-plannings/{$planningId}/validate")
            ->assertOk()
            ->assertJsonPath('planning.statut', HolidayPlanning::STATUT_VALIDE);
    }

    public function test_local_planning_follows_support_to_sel_workflow(): void
    {
        $province = $this->province('KCL');
        $support = $this->userWithRole('Agent', [
            'province_id' => $province->id,
            'organe' => 'Secrétariat Exécutif Local',
            'fonction' => 'Assistant administratif et financier',
        ]);
        $sel = $this->userWithRole('SEL', [
            'province_id' => $province->id,
            'organe' => 'Secrétariat Exécutif Local',
        ]);

        Sanctum::actingAs($support);
        $planningId = $this->createAndSubmitPlanning('local', $province->id, "Structure locale {$province->nom}");

        Sanctum::actingAs($sel);
        $this->postJson("/api/holiday-plannings/{$planningId}/validate")
            ->assertOk()
            ->assertJsonPath('planning.statut', HolidayPlanning::STATUT_VALIDE);
    }

    public function test_approved_holiday_cannot_be_modified_directly(): void
    {
        $user = $this->userWithRole('RH National');
        $holiday = Holiday::factory()->approved()->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/holidays/{$holiday->id}", [
            'date_debut' => now()->addMonth()->toDateString(),
            'date_fin' => now()->addMonth()->addDays(10)->toDateString(),
        ])->assertStatus(409)
            ->assertJsonPath('requires_modification_request', true);
    }

    public function test_validated_modification_request_applies_new_period(): void
    {
        $department = $this->department('DAF');
        $director = $this->userWithRole('Directeur de département', [
            'departement_id' => $department->id,
        ]);
        $employee = $this->userWithRole('Agent', ['departement_id' => $department->id]);
        $planning = $this->planning($department, HolidayPlanning::STATUT_VALIDE);
        $holiday = Holiday::factory()->approved()->create([
            'agent_id' => $employee->agent->id,
            'demande_par' => $employee->agent->id,
            'holiday_planning_id' => $planning->id,
            'nombre_jours' => 5,
        ]);
        $start = now()->addMonths(3)->nextWeekday();
        $end = $start->copy()->addWeekdays(5);

        Sanctum::actingAs($employee);
        $requestId = $this->postJson("/api/holidays/{$holiday->id}/modification-requests", [
            'date_debut_proposee' => $start->toDateString(),
            'date_fin_proposee' => $end->toDateString(),
            'motif' => 'Contraintes familiales documentées',
        ])->assertCreated()->json('modification_request.id');

        Sanctum::actingAs($director);
        $this->postJson("/api/holiday-modification-requests/{$requestId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('holidays', [
            'id' => $holiday->id,
            'date_debut' => $start->toDateString(),
            'date_fin' => $end->toDateString(),
        ]);
        $this->assertDatabaseHas('holiday_modification_requests', [
            'id' => $requestId,
            'statut' => 'approuvee',
            'reviewed_by' => $director->agent->id,
        ]);
    }

    public function test_departure_alert_is_sent_once_at_ten_working_days(): void
    {
        $department = $this->department('ALR');
        $employee = $this->userWithRole('Agent', ['departement_id' => $department->id]);
        $director = $this->userWithRole('Directeur de département', ['departement_id' => $department->id]);
        $rh = $this->userWithRole('RH National');
        $planning = $this->planning($department, HolidayPlanning::STATUT_VALIDE);
        $planning->update(['validated_by' => $director->agent->id]);
        $departure = today()->addWeekdays(10);

        $holiday = Holiday::factory()->approved()->create([
            'agent_id' => $employee->agent->id,
            'demande_par' => $employee->agent->id,
            'holiday_planning_id' => $planning->id,
            'date_debut' => $departure,
            'date_fin' => $departure->copy()->addWeekdays(4),
        ]);

        $this->artisan('holidays:send-departure-alerts')->assertSuccessful();

        foreach ([$employee, $director, $rh] as $recipient) {
            $this->assertDatabaseHas('notifications_portail', [
                'user_id' => $recipient->id,
                'type' => 'holiday_departure_reminder',
            ]);
        }
        $this->assertNotNull($holiday->fresh()->departure_alert_sent_at);
        $notificationCount = \App\Models\NotificationPortail::where('type', 'holiday_departure_reminder')->count();

        $this->artisan('holidays:send-departure-alerts')->assertSuccessful();

        $this->assertSame(
            $notificationCount,
            \App\Models\NotificationPortail::where('type', 'holiday_departure_reminder')->count(),
        );
    }

    private function createAndSubmitPlanning(string $type, int $structureId, string $name): int
    {
        $planningId = $this->postJson('/api/holiday-plannings', [
            'annee' => now()->year,
            'type_structure' => $type,
            'structure_id' => $structureId,
            'nom_structure' => $name,
            'jours_conge_totaux' => 30,
        ])->assertCreated()->json('planning.id');

        $this->postJson("/api/holiday-plannings/{$planningId}/submit")->assertOk();

        return $planningId;
    }

    private function userWithRole(string $roleName, array $agentAttributes = []): User
    {
        $role = Role::firstOrCreate(['nom_role' => $roleName]);
        $agent = Agent::factory()->create([
            'role_id' => $role->id,
            'statut' => 'actif',
            ...$agentAttributes,
        ]);

        return User::factory()->create([
            'agent_id' => $agent->id,
            'role_id' => $role->id,
        ])->load('agent', 'role');
    }

    private function planning(Department $department, string $status): HolidayPlanning
    {
        $creator = Agent::factory()->create(['departement_id' => $department->id]);

        return HolidayPlanning::create([
            'annee' => now()->year,
            'type_structure' => 'department',
            'structure_id' => $department->id,
            'nom_structure' => $department->nom,
            'niveau_administratif' => 'national',
            'jours_conge_totaux' => 30,
            'statut' => $status,
            'valide' => $status === HolidayPlanning::STATUT_VALIDE,
            'created_by' => $creator->id,
        ]);
    }

    private function department(string $code): Department
    {
        return Department::create([
            'code' => $code,
            'nom' => "Département {$code}",
            'description' => 'Département utilisé pour le test du workflow des congés.',
        ]);
    }

    private function province(string $code): Province
    {
        return Province::create([
            'code' => $code,
            'nom' => "Province {$code}",
            'chef_lieu' => "Chef-lieu {$code}",
        ]);
    }
}