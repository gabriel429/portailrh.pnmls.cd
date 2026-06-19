<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use App\Services\TacheWorkflowService;
use Tests\TestCase;

class TacheWorkflowServiceTest extends TestCase
{
    public function test_province_final_validator_only_accepts_sep_or_caf_profiles(): void
    {
        $workflow = new TacheWorkflowService();
        $target = $this->agent(['province_id' => 1, 'organe' => 'Secrétariat Exécutif Provincial']);

        $sep = $this->agent(['province_id' => 1], 'SEP');
        $caf = $this->agent(
            [
                'province_id' => 1,
                'fonction' => 'Chef de Cellule Administration et Finances',
            ],
            'SECOM',
            ['code' => 'CAF', 'nom' => 'Cellule Administrative et Financière']
        );
        $rhProvincial = $this->agent(
            ['province_id' => 1, 'fonction' => 'Responsable RH Provincial'],
            'RH Provincial'
        );
        $daf = $this->agent(['province_id' => 1, 'fonction' => 'Directeur Administratif et Financier'], 'DAF');
        $sel = $this->agent(
            [
                'province_id' => 1,
                'organe' => 'Secrétariat Exécutif Local',
                'fonction' => 'Secrétaire Exécutif Local',
            ],
            'SEL'
        );

        $this->assertTrue($workflow->isEligibleValidatorAgent($sep, 'province', $target));
        $this->assertTrue($workflow->isEligibleValidatorAgent($caf, 'province', $target));
        $this->assertFalse($workflow->isEligibleValidatorAgent($rhProvincial, 'province', $target));
        $this->assertFalse($workflow->isEligibleValidatorAgent($daf, 'province', $target));
        $this->assertFalse($workflow->isEligibleValidatorAgent($sel, 'province', $target));
    }

    public function test_province_final_validator_rejects_sep_or_caf_from_another_province(): void
    {
        $workflow = new TacheWorkflowService();
        $target = $this->agent(['province_id' => 1]);

        $otherProvinceSep = $this->agent(['province_id' => 2], 'SEP');
        $otherProvinceCaf = $this->agent(
            ['province_id' => 2],
            'SECOM',
            ['code' => 'CAF', 'nom' => 'Cellule Administrative et Financière']
        );

        $this->assertFalse($workflow->isEligibleValidatorAgent($otherProvinceSep, 'province', $target));
        $this->assertFalse($workflow->isEligibleValidatorAgent($otherProvinceCaf, 'province', $target));
    }

    public function test_role_service_detects_provincial_caf_without_granting_generic_province_profiles(): void
    {
        $roles = new RoleService();
        $cafUser = $this->userWithAgent(
            $this->agent(
                [
                    'province_id' => 1,
                    'fonction' => 'Chef de Cellule Administration et Finances',
                    'organe' => 'Secrétariat Exécutif Provincial',
                ],
                'SECOM',
                ['code' => 'CAF', 'nom' => 'Cellule Administrative et Financière']
            ),
            'SECOM'
        );
        $provinceRhUser = $this->userWithAgent(
            $this->agent(
                [
                    'province_id' => 1,
                    'fonction' => 'Responsable RH Provincial',
                    'organe' => 'Secrétariat Exécutif Provincial',
                ],
                'RH Provincial'
            ),
            'RH Provincial'
        );
        $dafUser = $this->userWithAgent(
            $this->agent(
                [
                    'province_id' => 1,
                    'fonction' => 'Directeur Administratif et Financier',
                    'organe' => 'Secrétariat Exécutif Provincial',
                ],
                'DAF'
            ),
            'DAF'
        );
        $nationalCafUser = $this->userWithAgent(
            $this->agent(
                [
                    'province_id' => null,
                    'fonction' => 'Chef de Cellule Administration et Finances',
                    'organe' => 'Secrétariat Exécutif National',
                ],
                'SECOM',
                ['code' => 'CAF', 'nom' => 'Cellule Administrative et Financière']
            ),
            'SECOM'
        );

        $this->assertTrue($roles->isProvincialCafManager($cafUser));
        $this->assertFalse($roles->isProvincialCafManager($provinceRhUser));
        $this->assertFalse($roles->isProvincialCafManager($dafUser));
        $this->assertFalse($roles->isProvincialCafManager($nationalCafUser));
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
            'email' => 'user' . random_int(1, 100000) . '@example.test',
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
