<?php

namespace Tests\Feature\Api;

use App\Models\ActivitePlan;
use App\Models\Agent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlanTravailScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sen_pta_menu_is_limited_to_sen_scoped_activities(): void
    {
        [$senUser, $senAgent] = $this->createSenUser();

        $senActivity = $this->createPtaActivity($senAgent, 'Activite SEN', 'SEN');
        $assignedToSenActivity = $this->createPtaActivity($senAgent, 'Activite attachee SEN', 'SEP');
        $assignedToSenActivity->agents()->attach($senAgent->id);
        $sepActivity = $this->createPtaActivity($senAgent, 'Activite SEP', 'SEP');
        $selActivity = $this->createPtaActivity($senAgent, 'Activite SEL', 'SEL');

        Sanctum::actingAs($senUser);

        $response = $this->getJson('/api/plan-travail?annee=2026');

        $response->assertOk()
            ->assertJsonPath('meta.stats.total', 2)
            ->assertJsonPath('meta.isGlobalPta', false);

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertContains($senActivity->id, $ids);
        $this->assertContains($assignedToSenActivity->id, $ids);
        $this->assertNotContains($sepActivity->id, $ids);
        $this->assertNotContains($selActivity->id, $ids);

        $this->getJson("/api/plan-travail/{$senActivity->id}")
            ->assertOk();

        $this->getJson("/api/plan-travail/{$sepActivity->id}")
            ->assertForbidden();
    }

    public function test_sen_admin_pta_context_keeps_global_visibility(): void
    {
        [$senUser, $senAgent] = $this->createSenUser();

        $senActivity = $this->createPtaActivity($senAgent, 'Activite SEN', 'SEN');
        $assignedToSenActivity = $this->createPtaActivity($senAgent, 'Activite attachee SEN', 'SEP');
        $assignedToSenActivity->agents()->attach($senAgent->id);
        $sepActivity = $this->createPtaActivity($senAgent, 'Activite SEP', 'SEP');
        $selActivity = $this->createPtaActivity($senAgent, 'Activite SEL', 'SEL');

        Sanctum::actingAs($senUser);

        $response = $this->getJson('/api/plan-travail?admin_pta=1&annee=2026');

        $response->assertOk()
            ->assertJsonPath('meta.stats.total', 4)
            ->assertJsonPath('meta.isGlobalPta', true);

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertContains($senActivity->id, $ids);
        $this->assertContains($assignedToSenActivity->id, $ids);
        $this->assertContains($sepActivity->id, $ids);
        $this->assertContains($selActivity->id, $ids);

        $this->getJson('/api/plan-travail/dashboard?admin_pta=1&annee=2026')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 4);

        $this->getJson("/api/plan-travail/{$sepActivity->id}?admin_pta=1")
            ->assertOk();
    }

    private function createSenUser(): array
    {
        $senRole = Role::firstOrCreate(['nom_role' => 'SEN']);
        $senAgent = Agent::factory()->active()->create([
            'organe' => Agent::ORGANES[0],
            'role_id' => $senRole->id,
            'departement_id' => null,
        ]);
        $senUser = User::factory()->create([
            'agent_id' => $senAgent->id,
            'role_id' => $senRole->id,
        ]);

        return [$senUser, $senAgent];
    }

    private function createPtaActivity(Agent $creator, string $title, string $niveau): ActivitePlan
    {
        return ActivitePlan::create([
            'createur_id' => $creator->id,
            'titre' => $title,
            'description' => $title,
            'niveau_administratif' => $niveau,
            'annee' => 2026,
            'trimestre' => 'T1',
            'statut' => 'planifiee',
            'pourcentage' => 0,
        ]);
    }
}
