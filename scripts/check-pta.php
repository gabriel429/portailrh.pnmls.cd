<?php
// Diagnostic PTA pour Planification, Suivi et Evaluation
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Department;
use App\Models\ActivitePlan;
use App\Models\Agent;

echo "=== DEPARTEMENTS ===\n";
Department::select('id','code','nom','description')->get()->each(function($d) {
    echo "$d->id | $d->code | $d->nom | $d->description\n";
});

echo "\n=== ACTIVITES PTA 2026 (count par département) ===\n";
$counts = ActivitePlan::where('annee', 2026)
    ->selectRaw('departement_id, count(*) as total')
    ->groupBy('departement_id')
    ->get();
foreach ($counts as $c) {
    $dept = $c->departement_id ? Department::find($c->departement_id)?->nom : 'NULL';
    echo "Dept $c->departement_id ($dept): $c->total activités\n";
}
if ($counts->isEmpty()) echo "(aucune activité pour 2026)\n";

echo "\n=== ACTIVITES PTA TOUTES ANNEES (count par année) ===\n";
ActivitePlan::selectRaw('annee, count(*) as total')
    ->groupBy('annee')
    ->orderBy('annee')
    ->get()
    ->each(function($r) { echo "$r->annee: $r->total activités\n"; });

echo "\n=== AGENTS avec département contenant 'planif' ou 'suivi' ===\n";
Agent::where(function($q) {
    $q->whereHas('departement', fn($dq) => $dq->where('nom', 'like', '%planif%'))
      ->orWhereHas('departement', fn($dq) => $dq->where('nom', 'like', '%suivi%'));
})->select('id','nom','prenom','departement_id')
  ->limit(10)->get()->each(function($a) {
    echo "$a->id | $a->nom $a->prenom | dept_id=$a->departement_id\n";
});

echo "\n=== AGENTS sans département (departement_id NULL) - top 5 ===\n";
Agent::whereNull('departement_id')
    ->select('id','nom','prenom')
    ->limit(5)->get()->each(function($a) {
    echo "$a->id | $a->nom $a->prenom\n";
});
echo "Total agents sans département: " . Agent::whereNull('departement_id')->count() . "\n";
