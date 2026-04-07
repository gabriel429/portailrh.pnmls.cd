<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 5 dernières dates de pointage ===\n";
$dates = DB::table('pointages')
    ->selectRaw('date_pointage, count(*) as cnt')
    ->groupBy('date_pointage')
    ->orderByDesc('date_pointage')
    ->limit(5)
    ->get();
foreach ($dates as $d) {
    echo "  {$d->date_pointage} => {$d->cnt} pointages\n";
}

echo "\n=== Pointages aujourd'hui ===\n";
$today = now()->toDateString();
echo "Date: $today\n";
$cnt = DB::table('pointages')->where('date_pointage', $today)->count();
echo "Total pointages aujourd'hui: $cnt\n";

echo "\n=== Présence par organe (aujourd'hui) ===\n";
$presence = DB::table('pointages as p')
    ->join('agents as a', 'a.id', '=', 'p.agent_id')
    ->where('p.date_pointage', $today)
    ->whereNotNull('p.heure_entree')
    ->selectRaw('a.organe, count(DISTINCT p.agent_id) as presents')
    ->groupBy('a.organe')
    ->get();
if ($presence->isEmpty()) {
    echo "  Aucun pointage aujourd'hui.\n";
} else {
    foreach ($presence as $p) {
        echo "  {$p->organe} => {$p->presents}\n";
    }
}
