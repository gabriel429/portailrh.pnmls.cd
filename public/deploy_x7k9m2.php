<?php
// SÉCURITÉ : À SUPPRIMER IMMÉDIATEMENT APRÈS USAGE
if (!isset($_GET['token']) || $_GET['token'] !== 'MON_TOKEN_SECRET_2026') {
    http_response_code(403);
    die('Accès refusé');
}

$root = dirname(__DIR__);
$php  = '/opt/alt/php83/usr/bin/php';

echo '<pre>';

// ── MODE CLONE : ?token=...&clone ──────────────────────────────────────────
if (isset($_GET['clone'])) {
    echo "=== git clone ===\n";
    $repoUrl = 'https://github.com/gabriel429/portailrh.pnmls.cd.git';
    // Clone dans un dossier temporaire puis déplace
    echo shell_exec("git clone {$repoUrl} /tmp/portailrh_clone 2>&1");

    echo "\n=== Copie des fichiers vers la racine ===\n";
    echo shell_exec("cp -r /tmp/portailrh_clone/. {$root}/ 2>&1");
    echo shell_exec("rm -rf /tmp/portailrh_clone 2>&1");
    echo "Copie terminée.\n";

    echo "\n=== composer install ===\n";
    echo shell_exec("cd {$root} && /opt/alt/php83/usr/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader 2>&1");

    echo "\n=== Vérification ===\n";
    echo "artisan : " . (file_exists("{$root}/artisan") ? 'OK' : 'MISSING') . "\n";
    echo "vendor  : " . (is_dir("{$root}/vendor") ? 'OK' : 'MISSING') . "\n";
    echo '</pre>';
    exit;
}

// ── MODE MIGRATE : ?token=... ──────────────────────────────────────────────
chdir($root);

echo "=== DIAGNOSTIC ===\n";
echo "PHP binary  : {$php}\n";
echo "Root path   : {$root}\n";
echo "artisan     : " . (file_exists("{$root}/artisan") ? 'FOUND' : 'MISSING - lancez ?token=...&clone d\'abord') . "\n";
echo ".env        : " . (file_exists("{$root}/.env")    ? 'FOUND' : 'MISSING') . "\n";
echo "vendor      : " . (is_dir("{$root}/vendor")       ? 'FOUND' : 'MISSING') . "\n";
echo "shell_exec  : " . (function_exists('shell_exec')  ? 'ENABLED' : 'DISABLED') . "\n";

$logFile = "{$root}/storage/logs/laravel.log";
echo "log file    : " . (file_exists($logFile) ? 'EXISTS (' . round(filesize($logFile)/1024) . ' KB)' : 'MISSING') . "\n";

if (isset($_GET['log'])) {
    echo "\n=== Dernières 80 lignes du log ===\n";
    echo file_exists($logFile)
        ? htmlspecialchars(implode('', array_slice(file($logFile), -80)))
        : 'Log introuvable.';
    echo '</pre>';
    exit;
}

if (!file_exists("{$root}/artisan")) {
    echo "\nARTISAN MANQUANT — Lancez d'abord :\n";
    echo "https://deeppink-rhinoceros-934330.hostingersite.com/deploy_x7k9m2.php?token=MON_TOKEN_SECRET_2026&clone\n";
    echo '</pre>';
    exit;
}

echo "\n=== config:clear + cache ===\n";
echo shell_exec("{$php} artisan config:clear 2>&1");
echo shell_exec("{$php} artisan cache:clear 2>&1");
echo shell_exec("{$php} artisan view:clear 2>&1");

echo "\n=== migrate --force ===\n";
echo shell_exec("{$php} artisan migrate --force 2>&1");

echo "\n=== db:seed GradeSeeder ===\n";
echo shell_exec("{$php} artisan db:seed --class=GradeSeeder --force 2>&1");

echo "\n=== db:seed AdminNTSeeder ===\n";
echo shell_exec("{$php} artisan db:seed --class=AdminNTSeeder --force 2>&1");

echo "\n=== db:seed ProvinceSeeder ===\n";
echo shell_exec("{$php} artisan db:seed --class=ProvinceSeeder --force 2>&1");

echo "\n=== db:seed RoleSeeder ===\n";
echo shell_exec("{$php} artisan db:seed --class=RoleSeeder --force 2>&1");

echo "\n=== db:seed DepartmentSeeder ===\n";
echo shell_exec("{$php} artisan db:seed --class=DepartmentSeeder --force 2>&1");

echo "\n=== db:seed PermissionSeeder ===\n";
echo shell_exec("{$php} artisan db:seed --class=PermissionSeeder --force 2>&1");

echo "\n=== db:seed AgentSeeder ===\n";
echo shell_exec("{$php} artisan db:seed --class=AgentSeeder --force 2>&1");

echo "\n=== Dernières erreurs log ===\n";
echo file_exists($logFile)
    ? htmlspecialchars(implode('', array_slice(file($logFile), -40)))
    : 'Log introuvable.';

echo '</pre>';

$root = dirname(__DIR__);
chdir($root);

// lsphp est le handler LiteSpeed web, pas le CLI — on force le vrai PHP CLI
$php = PHP_BINARY;
foreach ([
    '/opt/alt/php83/usr/bin/php',
    '/opt/alt/php82/usr/bin/php',
    '/opt/alt/php81/usr/bin/php',
    '/usr/bin/php83',
    '/usr/bin/php82',
    '/usr/bin/php81',
    '/usr/bin/php',
] as $c) {
    if (file_exists($c) && is_executable($c)) { $php = $c; break; }
}

echo '<pre>';

// ── Diagnostic ─────────────────────────────────────────────────────────────
echo "=== DIAGNOSTIC ===\n";
echo "PHP binary  : " . $php . "\n";
echo "Root path   : " . $root . "\n";
echo "Current dir : " . getcwd() . "\n";
echo "artisan     : " . (file_exists($root . '/artisan') ? 'FOUND' : 'MISSING') . "\n";
echo ".env        : " . (file_exists($root . '/.env') ? 'FOUND' : 'MISSING') . "\n";
echo "storage/logs: " . (is_dir($root . '/storage/logs') ? 'EXISTS' : 'MISSING') . "\n";
echo "shell_exec  : " . (function_exists('shell_exec') ? 'ENABLED' : 'DISABLED') . "\n";

$logFile = $root . '/storage/logs/laravel.log';
echo "log file    : " . (file_exists($logFile) ? 'EXISTS (' . round(filesize($logFile)/1024) . ' KB)' : 'MISSING') . "\n";

// ── Mode log uniquement ─────────────────────────────────────────────────────
if (isset($_GET['log'])) {
    echo "\n=== Dernières 80 lignes du log ===\n";
    if (file_exists($logFile)) {
        $lines = file($logFile);
        echo htmlspecialchars(implode('', array_slice($lines, -80)));
    } else {
        echo 'Fichier log introuvable : ' . $logFile;
    }
    echo '</pre>';
    exit;
}

// ── Migrations ─────────────────────────────────────────────────────────────
if (!function_exists('shell_exec')) {
    echo "\nERREUR : shell_exec est désactivé sur ce serveur.\n";
    echo "Solution : activer shell_exec dans les paramètres PHP de Hostinger (PHP Configuration).\n";
    echo '</pre>';
    exit;
}

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
if (file_exists($logFile)) {
    $lines = file($logFile);
    echo htmlspecialchars(implode('', array_slice($lines, -40)));
} else {
    echo 'Log introuvable : ' . $logFile;
}

echo '</pre>';
