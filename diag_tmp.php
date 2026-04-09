<?php
// Diagnostic dashboard temporaire
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->boot();

$users = DB::table('users')->select('id','name','email','agent_id','role_id')->get();
echo "=== USERS ===\n";
foreach($users as $u) echo json_encode($u)."\n";

echo "\n=== REQUESTS (5 derniers) ===\n";
$reqs = DB::table('requests')->orderByDesc('id')->limit(5)->select('id','agent_id','statut','type','current_step','created_at')->get();
foreach($reqs as $r) echo json_encode($r)."\n";

echo "\n=== AGENTS (10 premiers) ===\n";
$agents = DB::table('agents')->limit(10)->select('id','nom','prenom','matricule')->get();
foreach($agents as $a) echo json_encode($a)."\n";
