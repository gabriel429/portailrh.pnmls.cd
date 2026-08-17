<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Department;
use App\Models\Localite;
use App\Models\Province;
use App\Models\Request as RequestModel;
use App\Models\Role;
use App\Models\Tache;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserDataScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SenaAssistantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistant_sen_sena_tracks_high_hierarchy_and_sen_created_tasks(): void
    {
        $roles = app(RoleService::class);
        [$assistantUser, $senAgent, $director, $sep, $sel, $outsideAgent] = $this->fixture();

        $scopedAgentIds = $roles->senaScopedAgentIds($assistantUser);

        $this->assertContains($director->id, $scopedAgentIds);
        $this->assertContains($sep->id, $scopedAgentIds);
        $this->assertContains($sel->id, $scopedAgentIds);
        $this->assertNotContains($outsideAgent->id, $scopedAgentIds);

        $scopedTask = $this->tache($senAgent, $sel);
        $senCreatedOutsideTask = $this->tache($senAgent, $outsideAgent);
        $outsideTask = $this->tache($outsideAgent, $outsideAgent);

        $watchedTaskIds = $roles->senaWatchedTachesQuery($assistantUser)->pluck('id')->all();

        $this->assertContains($scopedTask->id, $watchedTaskIds);
        $this->assertContains($senCreatedOutsideTask->id, $watchedTaskIds);
        $this->assertNotContains($outsideTask->id, $watchedTaskIds);
        $this->assertTrue($roles->canFollowSenaTask($assistantUser, $senCreatedOutsideTask, $outsideAgent));
        $this->assertFalse($roles->canManageSenaScopedAgent($assistantUser, $outsideAgent));
    }

    public function test_assistant_sen_sena_can_read_requests_in_high_hierarchy_scope_only(): void
    {
        $scope = app(UserDataScope::class);
        [$assistantUser, , , $sep, $sel, $outsideAgent] = $this->fixture();

        $sepRequest = $this->demande($sep);
        $selRequest = $this->demande($sel);
        $outsideRequest = $this->demande($outsideAgent);

        $visibleRequestIds = $scope->applyRequestScope(RequestModel::query(), $assistantUser)
            ->pluck('id')
            ->all();

        $this->assertContains($sepRequest->id, $visibleRequestIds);
        $this->assertContains($selRequest->id, $visibleRequestIds);
        $this->assertNotContains($outsideRequest->id, $visibleRequestIds);
        $this->assertTrue($scope->canAccessRequest($assistantUser, $selRequest));
        $this->assertFalse($scope->canAccessRequest($assistantUser, $outsideRequest));
    }

    private function fixture(): array
    {
        $province = Province::create([
            'code' => 'PSA',
            'nom' => 'Province suivie assistant',
            'chef_lieu' => 'Ville suivie',
        ]);
        $otherProvince = Province::create([
            'code' => 'PHA',
            'nom' => 'Province hors assistant',
            'chef_lieu' => 'Ville hors scope',
        ]);
        $department = Department::create([
            'code' => 'DIR-SENA',
            'nom' => 'Direction suivie SEN/SENA',
        ]);
        $localite = Localite::create([
            'code' => 'SEL-SENA',
            'nom' => 'Localité suivie SEN/SENA',
            'province_id' => $province->id,
        ]);

        $assistantAgent = $this->agent('Assistant SEN/SENA', [
            'organe' => 'Secrétariat Exécutif National',
            'departement_id' => null,
            'province_id' => null,
            'fonction' => 'Assistante SEN/SENA',
        ]);
        $assistantUser = $this->user($assistantAgent, 'Assistant SEN/SENA');

        $senAgent = $this->agent('SEN', [
            'organe' => 'Secrétariat Exécutif National',
            'departement_id' => null,
            'province_id' => null,
            'fonction' => 'Secrétaire Exécutif National',
        ]);
        $director = $this->agent('Directeur', [
            'organe' => 'Secrétariat Exécutif National',
            'departement_id' => $department->id,
            'province_id' => null,
            'fonction' => 'Directeur de département',
        ]);
        $sep = $this->agent('SEP', [
            'organe' => 'Secrétariat Exécutif Provincial',
            'province_id' => $province->id,
            'fonction' => 'Secrétaire Exécutif Provincial',
        ]);
        $sel = $this->agent('SEL', [
            'organe' => 'Secrétariat Exécutif Local',
            'province_id' => $province->id,
            'localite_id' => $localite->id,
            'fonction' => 'Secrétaire Exécutif Local',
        ]);
        $outsideAgent = $this->agent('Agent', [
            'organe' => 'Secrétariat Exécutif Provincial',
            'province_id' => $otherProvince->id,
            'fonction' => 'Chargé de suivi provincial',
        ]);

        return [$assistantUser, $senAgent, $director, $sep, $sel, $outsideAgent];
    }

    private function agent(string $roleName, array $attributes = []): Agent
    {
        $role = $this->role($roleName);

        return Agent::factory()->active()->create(array_merge([
            'role_id' => $role->id,
            'organe' => 'Secrétariat Exécutif National',
            'departement_id' => null,
            'province_id' => null,
            'fonction' => null,
            'poste_actuel' => null,
        ], $attributes));
    }

    private function user(Agent $agent, string $roleName): User
    {
        $role = $this->role($roleName);

        return User::factory()->create([
            'agent_id' => $agent->id,
            'role_id' => $role->id,
        ]);
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['nom_role' => $name], ['description' => $name]);
    }

    private function tache(Agent $creator, Agent $target): Tache
    {
        return Tache::create([
            'createur_id' => $creator->id,
            'agent_id' => $target->id,
            'titre' => 'Tâche de suivi SEN/SENA',
            'source_type' => 'hors_pta',
            'source_emetteur' => 'sen',
            'priorite' => 'normale',
            'statut' => 'nouvelle',
            'pourcentage' => 0,
            'validation_statut' => 'non_requise',
        ]);
    }

    private function demande(Agent $agent): RequestModel
    {
        return RequestModel::create([
            'agent_id' => $agent->id,
            'type' => 'permission',
            'description' => 'Demande de suivi SEN/SENA',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDay()->toDateString(),
            'statut' => 'en_attente',
            'current_step' => 'sen',
            'workflow_level' => 'national_sen_direct',
        ]);
    }
}
