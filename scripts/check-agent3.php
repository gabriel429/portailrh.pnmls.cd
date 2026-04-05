<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$agent = DB::table('agents')->where('id', 3)->first();
if (!$agent) { echo "Agent id=3 introuvable\n"; exit; }
echo "AGENT: $agent->id | $agent->nom $agent->prenom | dept=$agent->departement_id | user_id=" . ($agent->user_id ?? 'NULL') . "\n";

// Check related tables
$tables = [
    'users' => "SELECT id,name,email FROM users WHERE id = " . ($agent->user_id ?? 0),
    'affectations' => "SELECT id FROM affectations WHERE agent_id = 3",
    'conges' => "SELECT id FROM conges WHERE agent_id = 3",
    'pointages' => "SELECT id FROM pointages WHERE agent_id = 3",
    'taches' => "SELECT id FROM taches WHERE agent_id = 3",
    'notifications' => "SELECT id FROM notifications WHERE agent_id = 3",
    'activite_plans_createur' => "SELECT id FROM activite_plans WHERE createur_id = 3",
    'demandes' => "SELECT id FROM demandes WHERE agent_id = 3",
    'signalements' => "SELECT id FROM signalements WHERE agent_id = 3",
    'formations_agent' => "SELECT agent_id FROM agent_formation WHERE agent_id = 3",
];

foreach ($tables as $label => $sql) {
    try {
        $count = count(DB::select($sql));
        if ($count > 0) echo "  $label: $count enregistrement(s)\n";
    } catch (\Exception $e) {
        // table doesn't exist, skip
    }
}
echo "DONE\n";
