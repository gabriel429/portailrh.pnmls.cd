<?php
// SÉCURITÉ : À SUPPRIMER IMMÉDIATEMENT APRÈS USAGE
if (!isset($_GET['token']) || $_GET['token'] !== 'MON_TOKEN_SECRET_2026') {
    http_response_code(403);
    die('Accès refusé');
}

$root = dirname(__DIR__);
$php  = '/opt/alt/php83/usr/bin/php';

putenv('COMPOSER_HOME=/tmp/composer_home');
putenv('HOME=/tmp');

echo '<pre>';

// -- MODE CLONE : ?token=...&clone&git=VOTRE_TOKEN_GITHUB -------------------
if (isset($_GET['clone'])) {
    $gitToken = $_GET['git'] ?? '';
    if (empty($gitToken)) {
        echo "ERREUR : Paramètre &git=VOTRE_TOKEN_GITHUB manquant.\n";
        echo "Créez un token sur https://github.com/settings/tokens (scope: repo)\n";
        echo "Puis accédez à : ?token=MON_TOKEN_SECRET_2026&clone&git=VOTRE_TOKEN\n";
        echo '</pre>';
        exit;
    }

    $repoUrl = "https://{$gitToken}@github.com/gabriel429/portailrh.pnmls.cd.git";

    echo "=== git clone ===\n";
    echo shell_exec("git clone {$repoUrl} /tmp/portailrh_clone 2>&1");

    if (!is_dir('/tmp/portailrh_clone')) {
        echo "ECHEC du clone.\n";
        echo '</pre>';
        exit;
    }

    echo "\n=== Copie des fichiers vers la racine ===\n";
    echo shell_exec("cp -r /tmp/portailrh_clone/. {$root}/ 2>&1");
    echo shell_exec("rm -rf /tmp/portailrh_clone 2>&1");
    echo "Copie terminee.\n";

    echo "\n=== composer install ===\n";
    $composerPhar = '/usr/local/bin/composer';
    if (!file_exists($composerPhar)) {
        echo shell_exec("curl -sS https://getcomposer.org/installer | {$php} -- --install-dir=/tmp --filename=composer 2>&1");
        $composerPhar = '/tmp/composer';
    }
    echo shell_exec("HOME=/tmp {$php} {$composerPhar} install --no-dev --optimize-autoloader --no-interaction --working-dir={$root} 2>&1");

    echo "\n=== Verification ===\n";
    echo "artisan : " . (file_exists("{$root}/artisan") ? 'OK' : 'MISSING') . "\n";
    echo "vendor  : " . (is_dir("{$root}/vendor")       ? 'OK' : 'MISSING') . "\n";
    echo "\nEtape suivante : creez le .env puis accedez a ?token=MON_TOKEN_SECRET_2026\n";
    echo '</pre>';
    exit;
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
    echo "\nARTISAN MANQUANT - Lancez d'abord : ?token=MON_TOKEN_SECRET_2026&clone&git=TOKEN_GITHUB\n";
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

foreach (['GradeSeeder','AdminNTSeeder','ProvinceSeeder','RoleSeeder','DepartmentSeeder','PermissionSeeder','AgentSeeder'] as $seeder) {
    echo "\n=== db:seed {$seeder} ===\n";
    echo shell_exec("{$php} artisan db:seed --class={$seeder} --force 2>&1");
}

echo "\n=== Dernieres erreurs log ===\n";
echo file_exists($logFile)
    ? htmlspecialchars(implode('', array_slice(file($logFile), -40)))
    : 'Log introuvable.';

echo '</pre>';
