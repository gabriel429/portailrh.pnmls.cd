<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Agent;
use App\Models\Department;
use App\Models\ActivitePlan;

echo "=== Agents dept_id=1 ===\n";
Agent::where("departement_id", 1)->select("id","nom","prenom","section","organe")
  ->get()->each(function($a) { echo "$a->id | $a->nom $a->prenom | section=$a->section | organe=$a->organe\n"; });
echo "Total: " . Agent::where("departement_id", 1)->count() . "\n";

echo "\n=== Dept parent_id ===\n";
if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'parent_id')) {
    Department::whereNotNull('parent_id')->select('id','code','nom','parent_id')
      ->get()->each(function($d) { echo "$d->id | $d->code | $d->nom | parent=$d->parent_id\n"; });
} else {
    echo "Pas de colonne parent_id\n";
}

echo "\n=== Activites 2026 dept NULL (top 5) ===\n";
ActivitePlan::where("annee", 2026)->whereNull("departement_id")
  ->select("id","libelle_activite")
  ->limit(5)->get()->each(function($a) { echo "$a->id | $a->libelle_activite\n"; });

echo "\n=== Colonnes activite_plans ===\n";
$cols = \Illuminate\Support\Facades\Schema::getColumnListing('activite_plans');
echo implode(', ', $cols) . "\n";
