<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Pointage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PointageSequentialLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistant_records_arrival_then_departure_without_editing_locked_times(): void
    {
        $assistant = $this->userWithRole('Assistant RH');
        $agent = $this->targetAgent();
        $date = '2026-08-14';

        Sanctum::actingAs($assistant);

        $this->postJson('/api/pointages', [
            'date_pointage' => $date,
            'pointages' => [[
                'agent_id' => $agent->id,
                'heure_entree' => '08:00',
                'heure_sortie' => '16:00',
            ]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['heure_sortie']);

        $this->postJson('/api/pointages', [
            'date_pointage' => $date,
            'pointages' => [[
                'agent_id' => $agent->id,
                'heure_entree' => '08:00',
            ]],
        ])->assertCreated();

        $pointage = Pointage::where('agent_id', $agent->id)->whereDate('date_pointage', $date)->firstOrFail();
        $this->assertSame('08:00', $pointage->heure_entree->format('H:i'));
        $this->assertNull($pointage->heure_sortie);

        $this->postJson('/api/pointages', [
            'date_pointage' => $date,
            'pointages' => [[
                'agent_id' => $agent->id,
                'heure_entree' => '08:30',
            ]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['heure_entree']);

        $this->postJson('/api/pointages', [
            'date_pointage' => $date,
            'pointages' => [[
                'agent_id' => $agent->id,
                'heure_sortie' => '16:00',
            ]],
        ])->assertOk();

        $pointage->refresh();
        $this->assertSame('08:00', $pointage->heure_entree->format('H:i'));
        $this->assertSame('16:00', $pointage->heure_sortie->format('H:i'));

        $this->postJson('/api/pointages', [
            'date_pointage' => $date,
            'pointages' => [[
                'agent_id' => $agent->id,
                'heure_sortie' => '17:00',
            ]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['heure_sortie']);

        $this->deleteJson("/api/pointages/{$pointage->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('pointages', ['id' => $pointage->id]);
    }

    public function test_superior_correction_requires_reason_and_creates_audit_log(): void
    {
        $rhNational = $this->userWithRole('RH National');
        $agent = $this->targetAgent();
        $pointage = Pointage::create([
            'agent_id' => $agent->id,
            'date_pointage' => '2026-08-14',
            'heure_entree' => '08:00',
            'heure_sortie' => '16:00',
            'heures_travaillees' => 8,
        ]);

        Sanctum::actingAs($rhNational);

        $this->putJson("/api/pointages/{$pointage->id}", [
            'heure_entree' => '08:15',
            'heure_sortie' => '16:00',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['motif_correction']);

        $this->putJson("/api/pointages/{$pointage->id}", [
            'heure_entree' => '08:15',
            'heure_sortie' => '16:00',
            'motif_correction' => 'Correction apres verification de la feuille de presence.',
        ])->assertOk();

        $pointage->refresh();
        $this->assertSame('08:15', $pointage->heure_entree->format('H:i'));

        $log = AuditLog::where('table_name', 'pointages')
            ->where('record_id', $pointage->id)
            ->where('action', 'correction_pointage_heure')
            ->firstOrFail();

        $this->assertSame('heure_entree', $log->donnees_avant['champ']);
        $this->assertSame('08:00', $log->donnees_avant['ancienne_valeur']);
        $this->assertSame('08:15', $log->donnees_apres['nouvelle_valeur']);
        $this->assertSame('Correction apres verification de la feuille de presence.', $log->donnees_apres['motif']);
        $this->assertSame($rhNational->id, $log->donnees_apres['corrige_par_id']);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['nom_role' => $roleName]);
        $agent = Agent::factory()->active()->create([
            'role_id' => $role->id,
            'organe' => 'Secrétariat Exécutif National',
        ]);

        return User::factory()->create([
            'agent_id' => $agent->id,
            'role_id' => $role->id,
        ]);
    }

    private function targetAgent(): Agent
    {
        $role = Role::firstOrCreate(['nom_role' => 'Agent']);

        return Agent::factory()->active()->create([
            'role_id' => $role->id,
            'organe' => 'Secrétariat Exécutif National',
        ]);
    }
}
