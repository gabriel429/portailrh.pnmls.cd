<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\TacheController;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use App\Services\TacheWorkflowService;
use Tests\TestCase;

class TacheControllerAssignmentScopeTest extends TestCase
{
    public function test_sep_can_assign_only_to_same_province_agents_and_same_province_sel(): void
    {
        $controller = $this->controller();
        $creator = $this->agent(['province_id' => 1], 'SEP');
        $user = $this->userWithAgent($creator, 'SEP');

        $provincialAgent = $this->agent([
            'province_id' => 1,
            'organe' => 'Secrétariat Exécutif Provincial',
        ], 'RH Provincial');
        $sameProvinceSel = $this->agent([
            'province_id' => 1,
            'localite_id' => 10,
            'organe' => 'Secrétariat Exécutif Local',
            'fonction' => 'Secrétaire Exécutif Local',
        ], 'SEL');
        $sameProvinceLocalSupport = $this->agent([
            'province_id' => 1,
            'localite_id' => 10,
            'organe' => 'Secrétariat Exécutif Local',
            'fonction' => 'Assistant administratif et financier',
        ], 'AAF Local');
        $sameProvinceNationalAgent = $this->agent([
            'province_id' => 1,
            'organe' => 'Secrétariat Exécutif National',
        ], 'Assistant');
        $otherProvinceAgent = $this->agent([
            'province_id' => 2,
            'organe' => 'Secrétariat Exécutif Provincial',
        ], 'RH Provincial');

        $this->assertTrue($controller->canAssignForTest($user, $creator, $provincialAgent));
        $this->assertTrue($controller->canAssignForTest($user, $creator, $sameProvinceSel));
        $this->assertFalse($controller->canAssignForTest($user, $creator, $sameProvinceLocalSupport));
        $this->assertFalse($controller->canAssignForTest($user, $creator, $sameProvinceNationalAgent));
        $this->assertFalse($controller->canAssignForTest($user, $creator, $otherProvinceAgent));
    }

    public function test_provincial_caf_uses_the_same_assignment_scope_as_sep(): void
    {
        $controller = $this->controller();
        $creator = $this->agent(
            [
                'province_id' => 1,
                'fonction' => 'Chef de Cellule Administration et Finances',
                'organe' => 'Secrétariat Exécutif Provincial',
            ],
            'SECOM',
            ['code' => 'CAF', 'nom' => 'Cellule Administrative et Financière']
        );
        $user = $this->userWithAgent($creator, 'SECOM');

        $provincialAgent = $this->agent([
            'province_id' => 1,
            'organe' => 'Secrétariat Exécutif Provincial',
        ], 'RH Provincial');
        $otherProvinceSel = $this->agent([
            'province_id' => 2,
            'localite_id' => 20,
            'organe' => 'Secrétariat Exécutif Local',
            'fonction' => 'Secrétaire Exécutif Local',
        ], 'SEL');

        $this->assertTrue($controller->canAssignForTest($user, $creator, $provincialAgent));
        $this->assertFalse($controller->canAssignForTest($user, $creator, $otherProvinceSel));
    }

    private function controller(): object
    {
        return new class extends TacheController {
            public function canAssignForTest(User $user, Agent $creator, Agent $target): bool
            {
                return $this->canAssignTacheToAgent(
                    $user,
                    $creator,
                    $target,
                    new RoleService(),
                    new TacheWorkflowService()
                );
            }
        };
    }

    private function agent(array $attributes = [], ?string $roleName = null, ?array $departmentAttributes = null): Agent
    {
        $agent = new Agent();
        $agent->forceFill(array_merge([
            'id' => random_int(1, 100000),
            'nom' => 'Agent',
            'prenom' => 'Test',
            'province_id' => 1,
            'departement_id' => null,
            'organe' => 'Secrétariat Exécutif Provincial',
            'fonction' => null,
            'poste_actuel' => null,
        ], $attributes));

        if ($roleName !== null) {
            $agent->setRelation('role', new Role(['nom_role' => $roleName]));
        }

        if ($departmentAttributes !== null) {
            $department = new Department($departmentAttributes);
            $agent->setRelation('departement', $department);
            $agent->departement_id = $departmentAttributes['id'] ?? 1;
        }

        return $agent;
    }

    private function userWithAgent(Agent $agent, ?string $roleName = null): User
    {
        $user = new User();
        $user->forceFill([
            'id' => random_int(1, 100000),
            'name' => 'User Test',
            'email' => 'assignment' . random_int(1, 100000) . '@example.test',
            'agent_id' => $agent->id,
        ]);
        $user->setRelation('agent', $agent);

        if ($roleName !== null) {
            $role = new Role(['nom_role' => $roleName]);
            $user->setRelation('role', $role);
            $agent->setRelation('role', $role);
        }

        return $user;
    }
}
