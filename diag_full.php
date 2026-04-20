<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$agents = DB::table('agents')
    ->leftJoin('roles','agents.role_id','=','roles.id')
    ->leftJoin('departments','agents.departement_id','=','departments.id')
    ->whereRaw("LOWER(agents.nom) LIKE '%nage%' OR LOWER(agents.nom) LIKE '%mayamba%'")
    ->select('agents.id','agents.nom','agents.prenom','agents.role_id','agents.departement_id','roles.nom_role','departments.nom as dept_nom','departments.pris_en_charge','departments.id as dept_id')
    ->get();
echo "AGENTS:\n";
foreach($agents as $a) echo "  {$a->prenom} {$a->nom} | role:{$a->nom_role}(id:{$a->role_id}) | dept:{$a->dept_nom}(id:{$a->departement_id}) | pris_en_charge:" . json_encode($a->pris_en_charge) . "\n";

echo "\nROLES:\n";
foreach(DB::table('roles')->orderBy('id')->get() as $r) echo "  ID:{$r->id} {$r->nom_role}\n";

echo "\nDEPTS (admin/financ):\n";
foreach(DB::table('departments')->where('nom','like','%admin%')->orWhere('nom','like','%financ%')->get() as $d)
    echo "  ID:{$d->id} {$d->nom} | pris_en_charge:".json_encode($d->pris_en_charge)."\n";
