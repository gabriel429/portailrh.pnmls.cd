<?php

namespace Tests\Feature\Controllers;

use App\Models\Agent;
use App\Models\Role;
use App\Models\TechnicalSupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TechnicalSupportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_creates_ticket_and_only_technical_section_is_notified(): void
    {
        Mail::fake();
        Storage::fake('public');
        $agent = $this->userWithRole('Agent');
        $technician = $this->userWithRole('Section Nouvelle Technologie');
        $other = $this->userWithRole('RH Provincial');
        Sanctum::actingAs($agent);

        $response = $this->post('/api/technical-support', [
            'subject' => 'Impossible de pointer',
            'description' => 'Le bouton de pointage reste bloqué.',
            'module' => 'Pointage',
            'priority' => 'urgent',
            'attachment' => UploadedFile::fake()->image('capture.png'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subject', 'Impossible de pointer')
            ->assertJsonPath('data.status', 'nouveau');

        $ticketId = $response->json('data.id');
        $this->assertDatabaseHas('technical_support_tickets', [
            'id' => $ticketId,
            'requester_user_id' => $agent->id,
            'priority' => 'urgent',
        ]);
        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $technician->id,
            'type' => 'technical_support_new',
            'lien' => "/support-technique/{$ticketId}",
        ]);
        $this->assertDatabaseMissing('notifications_portail', [
            'user_id' => $other->id,
            'type' => 'technical_support_new',
        ]);
    }

    public function test_unrelated_agent_cannot_view_ticket(): void
    {
        $owner = $this->userWithRole('Agent');
        $other = $this->userWithRole('Agent');
        $ticket = TechnicalSupportTicket::create([
            'requester_user_id' => $owner->id,
            'subject' => 'Problème profil',
            'description' => 'Photo impossible à charger.',
            'module' => 'Profil',
            'priority' => 'normal',
        ]);
        Sanctum::actingAs($other);

        $this->getJson("/api/technical-support/{$ticket->id}")->assertForbidden();
    }

    public function test_technician_replies_and_changes_status_with_history(): void
    {
        Mail::fake();
        $owner = $this->userWithRole('Agent');
        $technician = $this->userWithRole('Section Nouvelle Technologie');
        $ticket = TechnicalSupportTicket::create([
            'requester_user_id' => $owner->id,
            'subject' => 'Congés indisponibles',
            'description' => 'La page ne charge pas.',
            'module' => 'Congés',
            'priority' => 'normal',
        ]);
        Sanctum::actingAs($technician);

        $this->postJson("/api/technical-support/{$ticket->id}/messages", [
            'body' => 'Pouvez-vous actualiser puis réessayer ?',
        ])->assertCreated();

        $this->putJson("/api/technical-support/{$ticket->id}/status", [
            'status' => 'en_cours',
        ])->assertOk();

        $this->assertDatabaseHas('technical_support_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $technician->id,
            'type' => 'status_change',
            'status_from' => 'nouveau',
            'status_to' => 'en_cours',
        ]);
        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $owner->id,
            'type' => 'technical_support_reply',
        ]);
        $this->assertDatabaseHas('notifications_portail', [
            'user_id' => $owner->id,
            'type' => 'technical_support_status',
        ]);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['nom_role' => $roleName]);
        $agent = Agent::factory()->create(['role_id' => $role->id, 'statut' => 'actif']);

        return User::factory()->create([
            'agent_id' => $agent->id,
            'role_id' => $role->id,
        ]);
    }
}