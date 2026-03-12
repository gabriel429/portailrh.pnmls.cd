<?php
// SÉCURITÉ : À SUPPRIMER IMMÉDIATEMENT APRÈS USAGE
if (!isset($_GET['token']) || $_GET['token'] !== 'MON_TOKEN_SECRET_2026') {
    http_response_code(403);
    die('Accès refusé');
}

$root = dirname(__DIR__);
chdir($root);
$php = PHP_BINARY;

// ── Mode log uniquement ─────────────────────────────────────────────────────
if (isset($_GET['log'])) {
    $logFile = $root . '/storage/logs/laravel.log';
    echo '<pre>';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        echo htmlspecialchars(implode('', array_slice($lines, -80)));
    } else {
        echo 'Fichier log introuvable.';
    }
    echo '</pre>';
    exit;
}

// ── Mode migrate + seed ─────────────────────────────────────────────────────
echo '<pre>';

echo "=== PHP binary ===\n";
echo $php . "\n";

echo "\n=== config:clear ===\n";
echo shell_exec($php . ' artisan config:clear 2>&1');

echo "\n=== migrate --force ===\n";
echo shell_exec($php . ' artisan migrate --force 2>&1');

echo "\n=== db:seed GradeSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=GradeSeeder --force 2>&1');

echo "\n=== db:seed AdminNTSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=AdminNTSeeder --force 2>&1');

echo "\n=== db:seed ProvinceSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=ProvinceSeeder --force 2>&1');

echo "\n=== db:seed RoleSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=RoleSeeder --force 2>&1');

echo "\n=== db:seed DepartmentSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=DepartmentSeeder --force 2>&1');

echo "\n=== db:seed PermissionSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=PermissionSeeder --force 2>&1');

echo "\n=== db:seed AgentSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=AgentSeeder --force 2>&1');

echo "\n=== Dernières erreurs log ===\n";
$logFile = $root . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    echo htmlspecialchars(implode('', array_slice($lines, -40)));
}

echo '</pre>';
