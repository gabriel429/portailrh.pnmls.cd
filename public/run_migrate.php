<?php
// SÉCURITÉ : À SUPPRIMER IMMÉDIATEMENT APRÈS USAGE
if (!isset($_GET['token']) || $_GET['token'] !== 'MON_TOKEN_SECRET_2026') {
    http_response_code(403);
    die('Accès refusé');
}

// Aller à la racine du projet Laravel (un niveau au-dessus de /public)
$root = dirname(__DIR__);
chdir($root);

$php = PHP_BINARY; // chemin PHP courant du serveur

echo '<pre>';
echo "=== migrate --force ===\n";
echo shell_exec($php . ' artisan migrate --force 2>&1');

echo "\n=== db:seed AdminNTSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=AdminNTSeeder --force 2>&1');

echo "\n=== db:seed GradeSeeder --force ===\n";
echo shell_exec($php . ' artisan db:seed --class=GradeSeeder --force 2>&1');
echo '</pre>';
