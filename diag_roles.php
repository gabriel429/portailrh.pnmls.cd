<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roles = DB::table('roles')->select('id', 'nom_role')->orderBy('id')->get();
foreach ($roles as $r) {
    echo $r->id . ' | ' . $r->nom_role . PHP_EOL;
}
