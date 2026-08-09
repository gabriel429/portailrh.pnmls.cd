<?php

namespace Tests\Feature\Controllers;

use App\Models\Agent;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use App\Models\Localite;
use App\Models\NotificationPortail;
use App\Models\Province;
use App\Models\Role;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HolidayPlanningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_user_cannot_see_planning_or_calendar_outside_department(): void
    {
        $department = $this->department('VISIBLE');
        $otherDepartment = $this->department('HIDDEN');
        $assistant = $this->userWithRole('Assistant de Département', [
            'departement_id' => $department->id,
            'fonction' => 'Assistant de Département',
        ]);
        $insideAgent = $this->userWithRole('Agent visible', ['departement_id' => $department->id]);
        $outsideAgent = $this->userWithRole('Agent hidden', ['departement_id' => $otherDepartment->id]);
        $insidePlanning = $this->planning($department, HolidayPlanning::STATUT_VALIDE);
        $outsidePlanning = $this->planning($otherDepartment, HolidayPlanning::STATUT_VALIDE);
        $insideHoliday = Holiday::factory()->pending()->create([
            'agent_id' => $insideAgent->agent->id,
            'holiday_planning_id' => $insidePlanning->id,
            'date_debut' => today(),
            'date_fin' => today()->addDay(),
        ]);
        Holiday::factory()->pending()->create([
            'agent_id' => $outsideAgent->agent->id,
            'holiday_planning_id' => $outsidePlanning->id,
            'date_debut' => today(),
            'date_fin' => today()->addDay(),
        ]);
        Sanctum::actingAs($assistant);

        $index = $this->getJson('/api/holiday-plannings?year=' . now()->year)->assertOk();
        $this->assertSame([$insidePlanning->id], collect($index->json('plannings.data'))->pluck('id')->all());
        $this->assertSame(1, $index->json('stats.total_plannings'));

        $show = $this->getJson("/api/holiday-plannings/{$insidePlanning->id}")
            ->assertOk()
            ->assertJsonCount(1, 'planning.holidays');
        $this->assertSame($insideHoliday->id, $show->json('planning.holidays.0.id'));

        $this->getJson("/api/holiday-plannings/{$outsidePlanning->id}")->assertForbidden();

        $calendar = $this->getJson('/api/holiday-plannings/calendar?year=' . now()->year)->assertOk();
        $this->assertSame([$insideHoliday->id], collect($calendar->json('events'))->pluck('id')->all());
    }

    public function test_sen_sees_all_planned_holidays(): void
    {
        $department = $this->department('EXEC-VISIBLE');
        $otherDepartment = $this->department('EXEC-OTHER');
        $insideAgent = $this->userWithRole('Agent visible', ['departement_id' => $department->id]);
        $outsideAgent = $this->userWithRole('Agent outside', ['departement_id' => $otherDepartment->id]);
        $insidePlanning = $this->planning($department, HolidayPlanning::STATUT_VALIDE);
        $outsidePlanning = $this->planning($otherDepartment, HolidayPlanning::STATUT_VALIDE);
        $insideHoliday = Holiday::factory()->pending()->create([
            'agent_id' => $insideAgent->agent->id,
            'holiday_planning_id' => $insidePlanning->id,
        ]);
        $outsideHoliday = Holiday::factory()->pending()->create([
            'agent_id' => $outsideAgent->agent->id,
            'holiday_planning_id' => $outsidePlanning->id,
        ]);

        foreach (['SEN'] as $role) {
            Sanctum::actingAs($this->userWithRole($role, [
                'organe' => 'Secrétariat Exécutif National',
            ]));

            $response = $this->getJson('/api/holiday-plannings?year=' . now()->year)->assertOk();
            $this->assertEqualsCanonicalizing(
                [$insidePlanning->id, $outsidePlanning->id],
                collect($response->json('plannings.data'))->pluck('id')->all(),
            );
            $this->assertEqualsCanonicalizing(
                [$insideHoliday->id, $outsideHoliday->id],
                collect($response->json('holidays.data'))->pluck('id')->all(),
            );
        }
    }

    public function test_national_rh_filter_options_only_include_supported_departments_and_real_localities(): void
    {
        $supportedDepartment = $this->department('SUPPORTED');
        $supportedDepartment->update(['pris_en_charge' => true]);
        $unsupportedDepartment = $this->department('ARCHIVE');
        $province = $this->province('OPTIONS');
        $locality = Localite::create([
            'code' => 'LOC-OPTIONS',
            'nom' => 'Structure locale test',
            'type' => 'ville',
            'province_id' => $province->id,
        ]);
        Sanctum::actingAs($this->userWithRole('Section ressources humaines'));

        $response = $this->getJson('/api/holiday-plannings?year=' . now()->year)->assertOk();

        $this->assertSame([$supportedDepartment->id], collect($response->json('departments'))->pluck('id')->all());
        $this->assertNotContains($unsupportedDepartment->id, collect($response->json('departments'))->pluck('id')->all());
        $this->assertContains($province->id, collect($response->json('provinces'))->pluck('id')->all());
        $this->assertSame([$locality->id], collect($response->json('localities'))->pluck('id')->all());
    }

    public function test_sep_filter_limits_plannings_statistics_and_holidays_to_selected_province(): void
    {
        $visibleProvince = $this->province('FILTER-A');
        $otherProvince = $this->province('FILTER-B');
        $visibleAgent = $this->userWithRole('Agent A', ['province_id' => $visibleProvince->id]);
        $otherAgent = $this->userWithRole('Agent B', ['province_id' => $otherProvince->id]);
        $creator = Agent::factory()->create();
        $visiblePlanning = HolidayPlanning::create([
            'annee' => now()->year,
            'type_structure' => 'sep',
            'structure_id' => $visibleProvince->id,
            'nom_structure' => "SEP {$visibleProvince->nom}",
            'niveau_administratif' => 'provincial',
            'jours_conge_totaux' => 30,
            'jours_utilises' => 5,
            'statut' => HolidayPlanning::STATUT_VALIDE,
            'valide' => true,
            'created_by' => $creator->id,
        ]);
        $otherPlanning = HolidayPlanning::create([
            'annee' => now()->year,
            'type_structure' => 'sep',
            'structure_id' => $otherProvince->id,
            'nom_structure' => "SEP {$otherProvince->nom}",
            'niveau_administratif' => 'provincial',
            'jours_conge_totaux' => 30,
            'jours_utilises' => 7,
            'statut' => HolidayPlanning::STATUT_VALIDE,
            'valide' => true,
            'created_by' => $creator->id,
        ]);
        $visibleHoliday = Holiday::factory()->pending()->create([
            'agent_id' => $visibleAgent->agent->id,
            'holiday_planning_id' => $visiblePlanning->id,
        ]);
        Holiday::factory()->pending()->create([
            'agent_id' => $otherAgent->agent->id,
            'holiday_planning_id' => $otherPlanning->id,
        ]);
        Sanctum::actingAs($this->userWithRole('SEN'));

        $response = $this->getJson('/api/holiday-plannings?year=' . now()->year
            . '&structure_type=sep&structure_id=' . $visibleProvince->id)->assertOk();

        $this->assertSame([$visiblePlanning->id], collect($response->json('plannings.data'))->pluck('id')->all());
        $this->assertSame([$visibleHoliday->id], collect($response->json('holidays.data'))->pluck('id')->all());
        $this->assertSame(1, $response->json('stats.total_plannings'));
        $this->assertEquals(5, $response->json('stats.total_jours_utilises'));
    }

    public function test_missing_sep_planning_reminder_notifies_only_caf_and_sep_of_selected_province(): void
    {
        $province = $this->province('REMIND-A');
        $otherProvince = $this->province('REMIND-B');
        $caf = $this->userWithRole('CAF', [
            'province_id' => $province->id,
            'fonction' => 'Chef de Cellule Administration et Finances',
        ]);
        $sep = $this->userWithRole('SEP', ['province_id' => $province->id]);
        $outsideSep = $this->userWithRole('SEP', ['province_id' => $otherProvince->id]);
        Sanctum::actingAs($this->userWithRole('SEN'));

        $this->postJson('/api/holiday-plannings/notify-missing', [
            'year' => now()->year,
            'structure_type' => 'sep',
            'structure_id' => $province->id,
        ])->assertOk()->assertJsonPath('recipients_count', 2);

        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $caf->id,
            'type' => 'holiday_planning_required_actor',
        ]);
        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $sep->id,
            'type' => 'holiday_planning_required_actor',
        ]);
        $this->assertDatabaseMissing('notifications_portail', [
            'user_id' => $outsideSep->id,
            'type' => 'holiday_planning_required_actor',
        ]);
    }

    public function test_initiator_receives_creation_capability_and_scoped_agents(): void
    {
        $department = $this->department('SCOPE');
        $otherDepartment = $this->department('OTHER');
        $assistant = $this->userWithRole('Assistant de Département', [
            'departement_id' => $department->id,
            'fonction' => 'Assistant de Département',
        ]);
        $employee = $this->userWithRole('Agent scope', ['departement_id' => $department->id]);
        $outsideEmployee = $this->userWithRole('Agent outside', ['departement_id' => $otherDepartment->id]);

        Sanctum::actingAs($assistant);

        $response = $this->getJson('/api/holiday-plannings?year=' . now()->year)
            ->assertOk()
            ->assertJsonPath('workflow.can_create', true)
            ->assertJsonPath('workflow.responsibility.type', 'department');

        $agentIds = collect($response->json('agents'))->pluck('id');
        $this->assertTrue($agentIds->contains($employee->agent->id));
        $this->assertFalse($agentIds->contains($outsideEmployee->agent->id));
    }

    public function test_planning_creation_rejects_an_agent_outside_the_structure_without_writing(): void
    {
        $department = $this->department('IN');
        $otherDepartment = $this->department('OUT');
        $assistant = $this->userWithRole('Assistant de Département', [
            'departement_id' => $department->id,
            'fonction' => 'Assistant de Département',
        ]);
        $outsideEmployee = $this->userWithRole('Agent outside planning', [
            'departement_id' => $otherDepartment->id,
        ]);
        [$start, $end] = $this->annualPlanningPeriod();
        $entries = $this->entriesForStructure('department', $department->id, $start, $end);
        $outsideEntryIndex = count($entries);
        $entries[] = [
            'agent_id' => $outsideEmployee->agent->id,
            'date_debut' => $start->toDateString(),
            'date_fin' => $end->toDateString(),
        ];

        Sanctum::actingAs($assistant);

        $this->postJson('/api/holiday-plannings', [
            'annee' => now()->year,
            'type_structure' => 'department',
            'structure_id' => $department->id,
            'nom_structure' => $department->nom,
            'jours_conge_totaux' => 30,
            'entries' => $entries,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors("entries.{$outsideEntryIndex}.agent_id");

        $this->assertDatabaseCount('holiday_plannings', 0);
        $this->assertDatabaseCount('holidays', 0);
    }

    public function test_planning_creation_requires_dates_for_every_active_agent_in_the_structure(): void
    {
        $department = $this->department('COMPLETE');
        $assistant = $this->userWithRole('Assistant de Département', [
            'departement_id' => $department->id,
            'fonction' => 'Assistant de Département',
        ]);
        $this->userWithRole('Agent without dates', ['departement_id' => $department->id]);
        [$start, $end] = $this->annualPlanningPeriod();

        Sanctum::actingAs($assistant);

        $this->postJson('/api/holiday-plannings', [
            'annee' => now()->year,
            'type_structure' => 'department',
            'structure_id' => $department->id,
            'nom_structure' => $department->nom,
            'jours_conge_totaux' => 30,
            'entries' => [[
                'agent_id' => $assistant->agent->id,
                'date_debut' => $start->toDateString(),
                'date_fin' => $end->toDateString(),
            ]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('entries');

        $this->assertDatabaseCount('holiday_plannings', 0);
    }

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
        $employee = $this->userWithRole('Agent', [
            'departement_id' => $department->id,
        ]);
        [$start, $end] = $this->annualPlanningPeriod();
        $entries = $this->entriesForStructure('department', $department->id, $start, $end);

        Sanctum::actingAs($assistant);
        $planningId = $this->postJson('/api/holiday-plannings', [
            'annee' => now()->year,
            'type_structure' => 'department',
            'structure_id' => $department->id,
            'nom_structure' => $department->nom,
            'jours_conge_totaux' => 30,
            'entries' => $entries,
        ])->assertCreated()
            ->assertJsonPath('planning.statut', 'brouillon')
            ->json('planning.id');

        $this->assertDatabaseHas('holidays', [
            'holiday_planning_id' => $planningId,
            'agent_id' => $employee->agent->id,
            'date_debut' => $start->toDateString(),
            'date_fin' => $end->toDateString(),
            'statut_demande' => 'en_attente',
        ]);

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
        $locality = Localite::create([
            'code' => 'LOC-KCL',
            'nom' => 'Structure locale KCL',
            'type' => 'ville',
            'province_id' => $province->id,
        ]);
        $support = $this->userWithRole('Agent', [
            'province_id' => $province->id,
            'localite_id' => $locality->id,
            'organe' => 'Secrétariat Exécutif Local',
            'fonction' => 'Assistant administratif et financier',
        ]);
        $sel = $this->userWithRole('SEL', [
            'province_id' => $province->id,
            'localite_id' => $locality->id,
            'organe' => 'Secrétariat Exécutif Local',
        ]);

        Sanctum::actingAs($support);
        $planningId = $this->createAndSubmitPlanning('local', $locality->id, $locality->nom);

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

    public function test_t1_requirement_is_idempotent_and_closes_after_validation(): void
    {
        $department = $this->department('DIR');
        $operationalDepartment = $this->department('OPS');
        $province = $this->province('T1P');
        $localProvince = $this->province('T1L');
        $locality = Localite::create([
            'code' => 'LOC-T1L',
            'nom' => 'Structure locale T1L',
            'type' => 'ville',
            'province_id' => $localProvince->id,
        ]);
        $assistant = $this->userWithRole('Assistant de Direction', [
            'departement_id' => $department->id,
            'fonction' => 'Assistant de Direction',
        ]);
        $employee = $this->userWithRole('Agent', ['departement_id' => $department->id]);
        $director = $this->userWithRole('Directeur', ['departement_id' => $department->id]);
        $departmentAssistant = $this->userWithRole('Assistant de Département', [
            'departement_id' => $operationalDepartment->id,
            'fonction' => 'Assistant de Département',
        ]);
        $caf = $this->userWithRole('CAF', ['province_id' => $province->id]);
        $localSupport = $this->userWithRole('Agent', [
            'province_id' => $localProvince->id,
            'localite_id' => $locality->id,
            'organe' => 'Secrétariat Exécutif Local',
            'fonction' => 'Assistant administratif et financier',
        ]);
        $scopeKey = 'holiday-planning:' . now()->year . ":department:{$department->id}";

        $this->artisan('holidays:generate-planning-requirements', ['--force' => true])->assertSuccessful();
        $this->artisan('holidays:generate-planning-requirements', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, Tache::where('system_key', "{$scopeKey}:task:{$assistant->agent->id}")->count());
        $this->assertDatabaseHas('taches', [
            'system_key' => 'holiday-planning:' . now()->year . ":department:{$operationalDepartment->id}:task:{$departmentAssistant->agent->id}",
            'niveau_gestion' => 'departement',
        ]);
        $this->assertDatabaseHas('taches', [
            'system_key' => 'holiday-planning:' . now()->year . ":sep:{$province->id}:task:{$caf->agent->id}",
            'niveau_gestion' => 'province',
        ]);
        $this->assertDatabaseHas('taches', [
            'system_key' => 'holiday-planning:' . now()->year . ":local:{$locality->id}:task:{$localSupport->agent->id}",
            'niveau_gestion' => 'local',
        ]);
        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $assistant->id,
            'type' => 'holiday_planning_required_actor',
            'context_key' => "{$scopeKey}:actor:{$assistant->agent->id}",
        ]);
        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $employee->id,
            'type' => 'holiday_planning_unavailable',
            'context_key' => "{$scopeKey}:agents",
        ]);

        Sanctum::actingAs($assistant);
        $planningId = $this->createAndSubmitPlanning('department', $department->id, $department->nom);
        Sanctum::actingAs($director);
        $this->postJson("/api/holiday-plannings/{$planningId}/validate")->assertOk();

        $this->assertDatabaseHas('taches', [
            'system_key' => "{$scopeKey}:task:{$assistant->agent->id}",
            'statut' => 'terminee',
            'pourcentage' => 100,
            'validation_statut' => 'validee',
        ]);
        $this->assertSame(0, NotificationPortail::where('context_key', 'like', "{$scopeKey}:%")->count());
    }

    private function createAndSubmitPlanning(string $type, int $structureId, string $name): int
    {
        $agentAttributes = in_array($type, ['sep', 'local'], true)
            ? ['province_id' => $structureId]
            : ['departement_id' => $structureId];
        $employee = $this->userWithRole('Agent planning', $agentAttributes);
        [$start, $end] = $this->annualPlanningPeriod();
        $planningId = $this->postJson('/api/holiday-plannings', [
            'annee' => now()->year,
            'type_structure' => $type,
            'structure_id' => $structureId,
            'nom_structure' => $name,
            'jours_conge_totaux' => 30,
            'entries' => $this->entriesForStructure($type, $structureId, $start, $end),
        ])->assertCreated()->json('planning.id');

        $this->postJson("/api/holiday-plannings/{$planningId}/submit")->assertOk();

        return $planningId;
    }

    private function annualPlanningPeriod(): array
    {
        $start = now()->setDate(now()->year, 6, 2)->startOfDay();

        return [$start, $start->copy()->addWeekdays(4)];
    }

    private function entriesForStructure(string $type, int $structureId, $start, $end): array
    {
        $query = Agent::query()->where('statut', 'actif');
        match ($type) {
            'sep' => $query->where('province_id', $structureId),
            'local' => $query->where('localite_id', $structureId),
            default => $query->where('departement_id', $structureId),
        };

        return $query->pluck('id')->map(fn ($agentId) => [
            'agent_id' => $agentId,
            'date_debut' => $start->toDateString(),
            'date_fin' => $end->toDateString(),
        ])->all();
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