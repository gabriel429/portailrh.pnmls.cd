<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Department;
use App\Models\Request as RequestModel;
use App\Models\Role;
use App\Models\User;
use App\Services\DemandeWorkflowService;
use Tests\TestCase;

class DemandeWorkflowServiceScopeTest extends TestCase
{
    public function test_national_request_rh_validation_excludes_provincial_and_local_rh(): void
    {
        $workflow = new DemandeWorkflowService();
        $demande = $this->requestForAgent(
            $this->agent([
                'province_id' => null,
                'localite_id' => null,
                'departement_id' => 10,
                'organe' => 'Secrétariat Exécutif National',
            ], 'Agent', ['id' => 10, 'nom' => 'Direction Administrative', 'province_id' => null])
        );

        $nationalRh = $this->userWithAgent($this->agent([
            'province_id' => null,
            'localite_id' => null,
            'organe' => 'Secrétariat Exécutif National',
        ], 'RH National'), 'RH National');

        $provincialRh = $this->userWithAgent($this->agent([
            'province_id' => 2,
            'localite_id' => null,
            'organe' => 'Secrétariat Exécutif Provincial',
        ], 'RH Provincial'), 'RH Provincial');

        $localAssistantRh = $this->userWithAgent($this->agent([
            'province_id' => 2,
            'localite_id' => 20,
            'organe' => 'Secrétariat Exécutif Local',
            'fonction' => 'Assistant RH local',
        ], 'Assistant RH'), 'Assistant RH');

        $this->assertTrue($workflow->canValidate($nationalRh, $demande));
        $this->assertFalse($workflow->canValidate($provincialRh, $demande));
        $this->assertFalse($workflow->canValidate($localAssistantRh, $demande));
    }

    private function requestForAgent(Agent $agent): RequestModel
    {
        $demande = new RequestModel();
        $demande->forceFill([
            'id' => random_int(1, 100000),
            'agent_id' => $agent->id,
            'type' => 'permission',
            'statut' => 'en_attente',
            'workflow_level' => 'national_with_department',
            'current_step' => 'rh',
        ]);
        $demande->setRelation('agent', $agent);

        return $demande;
    }

    private function agent(array $attributes = [], ?string $roleName = null, ?array $departmentAttributes = null): Agent
    {
        $agent = new Agent();
        $agent->forceFill(array_merge([
            'id' => random_int(1, 100000),
            'nom' => 'Agent',
            'prenom' => 'Test',
            'province_id' => null,
            'localite_id' => null,
            'departement_id' => null,
            'organe' => 'Secrétariat Exécutif National',
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
        $user = new class extends User {
            public function hasPermission(string $code): bool
            {
                return false;
            }
        };
        $user->forceFill([
            'id' => random_int(1, 100000),
            'name' => 'User Test',
            'email' => 'demande-scope' . random_int(1, 100000) . '@example.test',
            'agent_id' => $agent->id,
            'is_super_admin' => false,
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
