<?php

namespace Tests\Feature\Controllers;

use App\Models\Agent;
use App\Models\Request as RequestModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_is_notified_when_sen_cancels_request(): void
    {
        Mail::fake();
        [$sen, $owner, $demande] = $this->requestContext();
        Sanctum::actingAs($sen);

        $this->putJson("/api/requests/{$demande->id}", [
            'statut' => 'annulé',
            'remarques' => 'Annulation décidée par le SEN.',
        ])->assertOk();

        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $owner->id,
            'emetteur_id' => $sen->id,
            'type' => 'demande_annulee',
            'titre' => 'Demande annulée',
            'message' => 'Votre demande de permission a été annulée.',
            'lien' => "/requests/{$demande->id}",
            'lu' => false,
        ]);
    }

    public function test_owner_is_notified_when_sen_deletes_request(): void
    {
        Mail::fake();
        [$sen, $owner, $demande] = $this->requestContext();
        Sanctum::actingAs($sen);

        $this->deleteJson("/api/requests/{$demande->id}")->assertOk();

        $this->assertSoftDeleted('requests', ['id' => $demande->id]);
        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $owner->id,
            'emetteur_id' => $sen->id,
            'type' => 'demande_supprimee',
            'titre' => 'Demande supprimée',
            'message' => 'Votre demande de permission a été supprimée.',
            'lien' => '/requests',
            'lu' => false,
        ]);
    }

    private function requestContext(): array
    {
        $senRole = Role::firstOrCreate(['nom_role' => 'SEN']);
        $agentRole = Role::firstOrCreate(['nom_role' => 'Agent']);
        $senAgent = Agent::factory()->create(['role_id' => $senRole->id, 'statut' => 'actif']);
        $ownerAgent = Agent::factory()->create(['role_id' => $agentRole->id, 'statut' => 'actif']);
        $sen = User::factory()->create(['agent_id' => $senAgent->id, 'role_id' => $senRole->id]);
        $owner = User::factory()->create(['agent_id' => $ownerAgent->id, 'role_id' => $agentRole->id]);
        $demande = RequestModel::create([
            'agent_id' => $ownerAgent->id,
            'type' => 'permission',
            'description' => 'Demande à traiter par le SEN.',
            'date_debut' => now()->addWeek()->toDateString(),
            'date_fin' => now()->addWeek()->toDateString(),
            'statut' => 'en_attente',
        ]);

        return [$sen, $owner, $demande];
    }
}