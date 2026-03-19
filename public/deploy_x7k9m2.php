<?php
// SECURITE : Token fort, rate limit, sanitize inputs
$DEPLOY_TOKEN = 'PNMLS_D3PL0Y_S3CR3T_X9K2M7_2026!';

if (!isset($_GET['token']) || !hash_equals($DEPLOY_TOKEN, $_GET['token'])) {
    http_response_code(403);
    die('Acces refuse');
}

// Rate limit: max 10 requests per minute via file lock
$lockFile = sys_get_temp_dir() . '/pnmls_deploy_' . date('YmdHi') . '.lock';
$count = file_exists($lockFile) ? (int)file_get_contents($lockFile) : 0;
if ($count >= 10) { http_response_code(429); die('Rate limit exceeded'); }
file_put_contents($lockFile, $count + 1);

// Le script est dans public/ ; Laravel root est le dossier parent
$root = dirname(__DIR__);
$php  = '/opt/alt/php83/usr/bin/php';

putenv('COMPOSER_HOME=/tmp/composer_home');
putenv('HOME=/tmp');

echo '<pre>';

// -- MODE CLEARCACHE : ?token=...&clearcache --------------------------------
if (isset($_GET['clearcache'])) {
    echo "=== optimize:clear ===\n";
    echo shell_exec("{$php} {$root}/artisan optimize:clear 2>&1");
    echo "\nCache nettoye avec succes.\n";
    echo '</pre>'; exit;
}

// -- MODE FIXEMAIL : ?token=...&fixemail --------------------------------------
if (isset($_GET['fixemail'])) {
    echo "=== Fix: rendre la colonne email nullable ===\n";
    // Load Laravel to use DB
    require $root . '/vendor/autoload.php';
    $app = require_once $root . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    try {
        if (\Illuminate\Support\Facades\Schema::hasColumn('agents', 'email')) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE agents MODIFY email VARCHAR(255) NULL DEFAULT NULL');
            echo "Colonne 'email' rendue nullable avec succes.\n";
        } else {
            echo "Colonne 'email' n'existe pas dans la table agents.\n";
        }
    } catch (\Exception $e) {
        echo "ERREUR: " . $e->getMessage() . "\n";
    }
    echo '</pre>'; exit;
}

// -- MODE GITPULL : ?token=...&gitpull&git=TOKEN ---------------------------
if (isset($_GET['gitpull'])) {
    $gitToken = $_GET['git'] ?? '';
    if (empty($gitToken)) {
        echo "ERREUR : &git=TOKEN manquant.\n"; echo '</pre>'; exit;
    }
    echo "=== git remote set-url + pull ===\n";
    $repoUrl = "https://{$gitToken}@github.com/gabriel429/portailrh.pnmls.cd.git";
    echo shell_exec("cd {$root} && git remote set-url origin {$repoUrl} 2>&1");
    echo shell_exec("cd {$root} && git pull origin main 2>&1");

    echo "\n=== config:clear + route:clear ===\n";
    echo shell_exec("{$php} {$root}/artisan config:clear 2>&1");
    echo shell_exec("{$php} {$root}/artisan route:clear 2>&1");
    echo shell_exec("{$php} {$root}/artisan view:clear 2>&1");
    echo shell_exec("{$php} {$root}/artisan migrate --force 2>&1");
    echo "\n=== db:seed FonctionSeeder ===\n";
    echo shell_exec("{$php} {$root}/artisan db:seed --class=FonctionSeeder --force 2>&1");
    echo '</pre>'; exit;
}

// -- MODE LOG : ?token=...&log ----------------------------------------------
$logFile = "{$root}/storage/logs/laravel.log";
if (isset($_GET['log'])) {
    echo file_exists($logFile)
        ? htmlspecialchars(implode('', array_slice(file($logFile), -80)))
        : 'Log introuvable.';
    echo '</pre>';
    exit;
}

// -- MODE MIGRATE : ?token=... ----------------------------------------------
chdir($root);

echo "=== DIAGNOSTIC ===\n";
echo "artisan  : " . (file_exists("{$root}/artisan") ? 'FOUND' : 'MISSING') . "\n";
echo ".env     : " . (file_exists("{$root}/.env")    ? 'FOUND' : 'MISSING') . "\n";
echo "vendor   : " . (is_dir("{$root}/vendor")       ? 'FOUND' : 'MISSING') . "\n";
echo "log file : " . (file_exists($logFile) ? 'EXISTS (' . round(filesize($logFile)/1024) . ' KB)' : 'MISSING') . "\n";

if (!file_exists("{$root}/artisan")) {
    echo "\nARTISAN MANQUANT - Verifiez l'installation.\n";
    echo '</pre>'; exit;
}
if (!file_exists("{$root}/.env")) {
    echo "\n.env MANQUANT - Creez le fichier .env dans File Manager Hostinger.\n";
    echo '</pre>'; exit;
}

echo "\n=== config:clear + cache ===\n";
echo shell_exec("{$php} artisan config:clear 2>&1");
echo shell_exec("{$php} artisan cache:clear 2>&1");
echo shell_exec("{$php} artisan view:clear 2>&1");

echo "\n=== migrate --force ===\n";
echo shell_exec("{$php} artisan migrate --force 2>&1");

foreach (['GradeSeeder','AdminNTSeeder','ProvinceSeeder','RoleSeeder','DepartmentSeeder','PermissionSeeder','AgentSeeder','FonctionSeeder'] as $seeder) {
    echo "\n=== db:seed {$seeder} ===\n";
    echo shell_exec("{$php} artisan db:seed --class={$seeder} --force 2>&1");
}

echo "\n=== Dernieres erreurs log ===\n";
echo file_exists($logFile)
    ? htmlspecialchars(implode('', array_slice(file($logFile), -40)))
    : 'Log introuvable.';

echo '</pre>';
