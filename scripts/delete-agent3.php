<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$agent = App\Models\Agent::find(3);
if (!$agent) {
    echo "Agent id=3 introuvable (deja supprime?)\n";
    exit;
}
echo "Suppression: $agent->id | $agent->nom $agent->prenom\n";
$agent->delete();
echo "OK - Agent supprime\n";
