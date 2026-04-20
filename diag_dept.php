<?php
// Diagnostic: departement_id des directeurs
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Tous les rôles
echo "=== Rôles en DB ===\n";
$roles = DB::table('roles')->select('id','nom_role')->get();
foreach ($roles as $r) echo "  ID:{$r->id} [{$r->nom_role}]\n";

// Agents avec departement_id non-null
echo "\n=== Agents avec departement_id renseigné ===\n";
$rows = DB::table('agents')
    ->join('roles','agents.role_id','=','roles.id')
    ->whereNotNull('agents.departement_id')
    ->select('agents.id','agents.nom','agents.prenom','agents.departement_id','roles.nom_role')
    ->limit(20)->get();
foreach ($rows as $r) {
    echo "ID:{$r->id} {$r->prenom} {$r->nom} | role:[{$r->nom_role}] | dept_id:{$r->departement_id}\n";
}
if ($rows->isEmpty()) echo "  (aucun)\n";

// Tous les agents (limite 30)
echo "\n=== Tous les agents (30 premiers) ===\n";
$all = DB::table('agents')
    ->join('roles','agents.role_id','=','roles.id')
    ->select('agents.id','agents.nom','agents.prenom','agents.email','agents.departement_id','roles.id as role_id','roles.nom_role')
    ->orderBy('agents.id')->limit(30)->get();
foreach ($all as $a) {
    echo "ID:{$a->id} {$a->prenom} {$a->nom} | email:{$a->email} | role:[{$a->nom_role}(#{$a->role_id})] | dept_id:".($a->departement_id ?? 'NULL')."\n";
}
