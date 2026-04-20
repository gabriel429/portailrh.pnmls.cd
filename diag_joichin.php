<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// FIX: Assigner le rôle Directeur (id=2) à JOICHIN TAILA (agent id=18)
$updated = DB::table('agents')->where('id', 18)->update(['role_id' => 2]);
echo "=== FIX ROLE ===\n";
echo "Lignes mises à jour: $updated\n\n";

// Tous les rôles
$roles = DB::table('roles')->select('id','nom_role')->orderBy('id')->get();
echo "=== ROLES ===\n";
foreach ($roles as $r) echo "  id={$r->id} nom={$r->nom_role}\n";

// Recherche JOICHIN ou TAILA
$agents = DB::table('agents')
    ->leftJoin('roles','agents.role_id','=','roles.id')
    ->leftJoin('departments','agents.departement_id','=','departments.id')
    ->where(function($q){
        $q->where(DB::raw("UPPER(agents.nom)"),'LIKE','%TAILA%')
          ->orWhere(DB::raw("UPPER(agents.prenom)"),'LIKE','%JOICHIN%')
          ->orWhere(DB::raw("UPPER(agents.prenom)"),'LIKE','%JOACHIM%');
    })
    ->select('agents.id','agents.nom','agents.prenom','agents.email','agents.role_id','roles.nom_role','agents.departement_id','departments.nom as dept_nom')
    ->get();
echo "\n=== AGENT(S) JOICHIN/TAILA ===\n";
foreach ($agents as $a) echo json_encode($a)."\n";

// Agents avec role Directeur
$dirs = DB::table('agents')
    ->join('roles','agents.role_id','=','roles.id')
    ->leftJoin('departments','agents.departement_id','=','departments.id')
    ->where(DB::raw("UPPER(roles.nom_role)"),'LIKE','%DIRECTEUR%')
    ->select('agents.id','agents.nom','agents.prenom','agents.role_id','roles.nom_role','agents.departement_id','departments.nom as dept_nom')
    ->get();
echo "\n=== AGENTS ROLE DIRECTEUR ===\n";
foreach ($dirs as $d) echo json_encode($d)."\n";
echo "(total: ".count($dirs).")\n";
