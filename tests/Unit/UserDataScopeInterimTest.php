<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Department;
use App\Models\Localite;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use App\Services\UserDataScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDataScopeInterimTest extends TestCase
{
    use RefreshDatabase;

    private UserDataScope $scope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scope = app(UserDataScope::class);
    }

    public function test_director_can_only_choose_section_head_from_same_department(): void
    {
        $department = $this->department('DIR');
        $otherDepartment = $this->department('OTHER');
        $director = $this->userWithProfile('Directeur', 'Directeur / Chef de Département', [
            'departement_id' => $department->id,
        ]);
        $allowed = $this->agentWithProfile('Agent', 'Chef de Section', [
            'departement_id' => $department->id,
        ]);
        $wrongFunction = $this->agentWithProfile('Agent', 'Chargé de programme', [
            'departement_id' => $department->id,
        ]);
        $wrongDepartment = $this->agentWithProfile('Agent', 'Chef de Section', [
            'departement_id' => $otherDepartment->id,
        ]);

        $this->assertTrue($this->scope->canUseAgentAsInterim($director, $allowed));
        $this->assertFalse($this->scope->canUseAgentAsInterim($director, $wrongFunction));
        $this->assertFalse($this->scope->canUseAgentAsInterim($director, $wrongDepartment));
    }

    public function test_sen_and_sena_can_only_choose_directors(): void
    {
        $director = $this->agentWithProfile('Directeur', 'Directeur / Chef de Département');
        $sectionHead = $this->agentWithProfile('Agent', 'Chef de Section');

        foreach ([
            $this->userWithProfile('SEN', 'Secrétaire Exécutif National (SEN)'),
            $this->userWithProfile('SENA', 'Secrétaire Exécutif National Adjoint (SENA)'),
        ] as $authority) {
            $this->assertTrue($this->scope->canUseAgentAsInterim($authority, $director));
            $this->assertFalse($this->scope->canUseAgentAsInterim($authority, $sectionHead));
        }
    }

    public function test_sep_can_only_choose_cell_head_from_same_province(): void
    {
        $province = $this->province('PROV-A');
        $otherProvince = $this->province('PROV-B');
        $sep = $this->userWithProfile('SEP', 'Secrétaire Exécutif Provincial (SEP)', [
            'province_id' => $province->id,
            'organe' => 'Secrétariat Exécutif Provincial',
        ]);
        $allowed = $this->agentWithProfile('Agent', 'Chef de Cellule', [
            'province_id' => $province->id,
        ]);
        $wrongFunction = $this->agentWithProfile('Agent', 'Assistant Provincial', [
            'province_id' => $province->id,
        ]);
        $wrongProvince = $this->agentWithProfile('Agent', 'Chef de Cellule', [
            'province_id' => $otherProvince->id,
        ]);

        $this->assertTrue($this->scope->canUseAgentAsInterim($sep, $allowed));
        $this->assertFalse($this->scope->canUseAgentAsInterim($sep, $wrongFunction));
        $this->assertFalse($this->scope->canUseAgentAsInterim($sep, $wrongProvince));
    }

    public function test_sel_can_only_choose_assistant_from_same_locality(): void
    {
        $province = $this->province('PROV-SEL');
        $locality = $this->locality('SEL-A', $province->id);
        $otherLocality = $this->locality('SEL-B', $province->id);
        $sel = $this->userWithProfile('SEL', 'Secrétaire Exécutif Local (SEL)', [
            'province_id' => $province->id,
            'localite_id' => $locality->id,
            'organe' => 'Secrétariat Exécutif Local',
        ]);
        $allowed = $this->agentWithProfile('Agent', 'Assistant Administratif et Financier (SEL)', [
            'province_id' => $province->id,
            'localite_id' => $locality->id,
        ]);
        $wrongFunction = $this->agentWithProfile('Agent', 'Chef de Cellule', [
            'province_id' => $province->id,
            'localite_id' => $locality->id,
        ]);
        $wrongLocality = $this->agentWithProfile('Agent', 'Assistant Administratif et Financier (SEL)', [
            'province_id' => $province->id,
            'localite_id' => $otherLocality->id,
        ]);

        $this->assertTrue($this->scope->canUseAgentAsInterim($sel, $allowed));
        $this->assertFalse($this->scope->canUseAgentAsInterim($sel, $wrongFunction));
        $this->assertFalse($this->scope->canUseAgentAsInterim($sel, $wrongLocality));
    }

    public function test_other_agents_are_limited_to_their_own_entity(): void
    {
        $department = $this->department('AGENT');
        $otherDepartment = $this->department('OUTSIDE');
        $owner = $this->userWithProfile('Agent', 'Chargé de programme', [
            'departement_id' => $department->id,
        ]);
        $sameDepartment = $this->agentWithProfile('Agent', 'Assistant de Section', [
            'departement_id' => $department->id,
        ]);
        $outsideDepartment = $this->agentWithProfile('Agent', 'Assistant de Section', [
            'departement_id' => $otherDepartment->id,
        ]);

        $this->assertTrue($this->scope->canUseAgentAsInterim($owner, $sameDepartment));
        $this->assertFalse($this->scope->canUseAgentAsInterim($owner, $outsideDepartment));
        $this->assertFalse($this->scope->canUseAgentAsInterim($owner, $owner->agent));
    }

    private function userWithProfile(string $roleName, string $function, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['nom_role' => $roleName]);
        $agent = Agent::factory()->create([
            'role_id' => $role->id,
            'fonction' => $function,
            'statut' => 'actif',
            ...$attributes,
        ]);

        return User::factory()->create([
            'agent_id' => $agent->id,
            'role_id' => $role->id,
        ])->load('agent.role', 'role');
    }

    private function agentWithProfile(string $roleName, string $function, array $attributes = []): Agent
    {
        $role = Role::firstOrCreate(['nom_role' => $roleName]);

        return Agent::factory()->create([
            'role_id' => $role->id,
            'fonction' => $function,
            'statut' => 'actif',
            ...$attributes,
        ])->load('role');
    }

    private function department(string $code): Department
    {
        return Department::create([
            'code' => $code,
            'nom' => "Département {$code}",
            'description' => 'Département utilisé pour les tests des intérimaires.',
        ]);
    }

    private function province(string $code): Province
    {
        return Province::create([
            'code' => $code,
            'nom' => "Province {$code}",
            'description' => 'Province utilisée pour les tests des intérimaires.',
        ]);
    }

    private function locality(string $code, int $provinceId): Localite
    {
        return Localite::create([
            'code' => $code,
            'nom' => "Localité {$code}",
            'type' => 'territoire',
            'province_id' => $provinceId,
        ]);
    }
}