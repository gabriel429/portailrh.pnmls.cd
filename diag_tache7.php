<?php
// Diagnostic: organe des agents assignés aux tâches 6 et 7
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

use App\Models\Tache;
use App\Models\Agent;
use App\Models\User;

$tacheIds = [6, 7];
foreach ($tacheIds as $id) {
    $tache = Tache::find($id);
    if (!$tache) { echo "Tache $id: introuvable\n"; continue; }
    echo "Tache $id: agent_id={$tache->agent_id}, createur_id={$tache->createur_id}\n";
    if ($tache->agent_id) {
        $a = Agent::find($tache->agent_id);
        if ($a) {
            echo "  agent: nom={$a->nom}, organe=" . var_export($a->organe, true) . "\n";
            echo "  organe hex: " . bin2hex($a->organe) . "\n";
        } else {
            echo "  agent: introuvable (agent_id={$tache->agent_id})\n";
        }
    } else {
        echo "  agent_id: NULL\n";
    }
    if ($tache->createur_id) {
        $c = Agent::find($tache->createur_id);
        if ($c) {
            echo "  createur: nom={$c->nom}, organe=" . var_export($c->organe, true) . "\n";
        }
    }
}

// Aussi afficher les users SEN/SENA et leurs agents
echo "\n--- Users SEN/SENA ---\n";
$users = User::with(['role', 'agent'])->whereHas('role', function($q){
    $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(nom_role)'), ['sen', 'sena']);
})->get();
foreach ($users as $u) {
    echo "User {$u->id} ({$u->name}): role={$u->role?->nom_role}, agent_id=" . ($u->agent?->id ?? 'NULL') . "\n";
    if ($u->agent) {
        echo "  agent organe=" . var_export($u->agent->organe, true) . "\n";
    }
}
