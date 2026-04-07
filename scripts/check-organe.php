<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ORGANE VALUES ===\n";
$rows = Illuminate\Support\Facades\DB::select("SELECT organe, count(id) as cnt FROM agents GROUP BY organe");
foreach ($rows as $r) {
    echo $r->organe . ' => ' . $r->cnt . "\n";
}

echo "\n=== POINTAGE TODAY SEN ===\n";
$today = date('Y-m-d');
$sen = Illuminate\Support\Facades\DB::select("SELECT count(DISTINCT p.agent_id) as cnt FROM pointages p JOIN agents a ON a.id = p.agent_id WHERE p.date_pointage = ? AND p.heure_entree IS NOT NULL AND a.organe = 'Secretariat Executif National'", [$today]);
echo "SEN today present: " . $sen[0]->cnt . "\n";

$senLike = Illuminate\Support\Facades\DB::select("SELECT count(DISTINCT p.agent_id) as cnt FROM pointages p JOIN agents a ON a.id = p.agent_id WHERE p.date_pointage = ? AND p.heure_entree IS NOT NULL AND a.organe LIKE '%National%'", [$today]);
echo "LIKE National today present: " . $senLike[0]->cnt . "\n";

$senActifs = Illuminate\Support\Facades\DB::select("SELECT count(id) as cnt FROM agents WHERE organe = 'Secretariat Executif National' AND statut = 'actif'");
echo "SEN actifs: " . $senActifs[0]->cnt . "\n";

$senActifsLike = Illuminate\Support\Facades\DB::select("SELECT count(id) as cnt FROM agents WHERE organe LIKE '%National%' AND statut = 'actif'");
echo "LIKE National actifs: " . $senActifsLike[0]->cnt . "\n";
