<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

$u = App\Models\User::whereHas('roles', function($q){ $q->where('name','SEP'); })->with('agent')->first();
if (!$u) { echo "NO SEP USER FOUND\n"; exit; }
echo "user_id: " . $u->id . "\n";
echo "agent_id: " . ($u->agent->id ?? 'NULL') . "\n";
echo "province_id: " . ($u->agent->province_id ?? 'NULL') . "\n";
echo "roles: " . implode(',', $u->roles->pluck('name')->toArray()) . "\n";

// Aussi verif colonne province_id sur agents
$cols = DB::select("SHOW COLUMNS FROM agents LIKE 'province_id'");
echo "province_id col exists: " . (count($cols) ? 'YES' : 'NO') . "\n";
