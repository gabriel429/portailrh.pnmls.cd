<?php
// S�CURIT� : � SUPPRIMER IMM�DIATEMENT APR�S USAGE
if (!isset($_GET['token']) || $_GET['token'] !== 'MON_TOKEN_SECRET_2026') {
    http_response_code(403);
    die('Acc�s refus�');
}

// Sur Hostinger, tous les fichiers Laravel sont dans public_html/ lui-meme
$root = __DIR__;
$php  = '/opt/alt/php83/usr/bin/php';

putenv('COMPOSER_HOME=/tmp/composer_home');
putenv('HOME=/tmp');

echo '<pre>';

// -- MODE CLONE : ?token=...&clone&git=VOTRE_TOKEN_GITHUB -------------------
if (isset($_GET['clone'])) {
    $gitToken = $_GET['git'] ?? '';
    if (empty($gitToken)) {
        echo "ERREUR : Param�tre &git=VOTRE_TOKEN_GITHUB manquant.\n";
        echo "Cr�ez un token sur https://github.com/settings/tokens (scope: repo)\n";
        echo "Puis acc�dez � : ?token=MON_TOKEN_SECRET_2026&clone&git=VOTRE_TOKEN\n";
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

    echo "\n=== Sync public/ -> public_html/ ===\n";
    $pubHtml = "{$root}/public_html";
    echo shell_exec("cp -r {$root}/public/. {$pubHtml}/ 2>&1");
    echo "Sync terminee.\n";

    echo "\n=== storage:link ===\n";
    echo shell_exec("{$php} {$root}/artisan storage:link 2>&1");

    echo "\n=== Verification ===\n";
    echo "artisan           : " . (file_exists("{$root}/artisan")      ? 'OK' : 'MISSING') . "\n";
    echo "vendor            : " . (is_dir("{$root}/vendor")            ? 'OK' : 'MISSING') . "\n";
    echo "public_html/index : " . (file_exists("{$pubHtml}/index.php") ? 'OK' : 'MISSING') . "\n";
    echo "\nEtape suivante : creez le .env puis accedez a ?token=MON_TOKEN_SECRET_2026\n";
    echo '</pre>';
    exit;
}

// -- MODE SETUP : ?token=...&setup -----------------------------------------
if (isset($_GET['setup'])) {
    echo "=== storage:link (manuel, sans exec) ===\n";
    $link   = "{$root}/public/storage";
    $target = "{$root}/storage/app/public";
    if (is_link($link)) {
        echo "Lien deja existant : {$link}\n";
    } elseif (symlink($target, $link)) {
        echo "Lien cree : {$link} -> {$target}\n";
    } else {
        echo "ECHEC symlink - tentative shell_exec\n";
        echo shell_exec("ln -s {$target} {$link} 2>&1");
    }

    echo "\n=== Permissions storage/ ===\n";
    echo shell_exec("chmod -R 775 {$root}/storage {$root}/bootstrap/cache 2>&1");
    echo "Permissions appliquees.\n";

    echo "\n=== config:cache ===\n";
    echo shell_exec("{$php} {$root}/artisan config:clear 2>&1");
    echo shell_exec("{$php} {$root}/artisan route:clear 2>&1");
    echo shell_exec("{$php} {$root}/artisan view:clear 2>&1");

    echo "\n=== Verification finale ===\n";
    echo "artisan  : " . (file_exists("{$root}/artisan")            ? 'OK' : 'MISSING') . "\n";
    echo "vendor   : " . (is_dir("{$root}/vendor")                  ? 'OK' : 'MISSING') . "\n";
    echo ".env     : " . (file_exists("{$root}/.env")               ? 'OK' : 'MISSING') . "\n";
    echo "storage/ : " . (is_dir("{$root}/storage/app/public")      ? 'OK' : 'MISSING') . "\n";
    echo "pub/stor : " . (is_link("{$root}/public/storage")         ? 'OK (lien sym)' : (is_dir("{$root}/public/storage") ? 'OK (dossier)' : 'MISSING')) . "\n";
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
    echo '</pre>'; exit;
}

// -- MODE DIAG2 : ?token=...&diag2 -----------------------------------------
// Affiche les vrais messages d'erreur (sans les stack traces)
if (isset($_GET['diag2'])) {
    $pubHtml = "{$root}/public_html";

    echo "=== Racine domaine ({$root}) ===\n";
    echo "artisan  : " . (file_exists("{$root}/artisan")      ? 'OK' : 'MISSING') . "\n";
    echo ".env     : " . (file_exists("{$root}/.env")         ? 'OK' : 'MISSING') . "\n";
    echo "vendor   : " . (is_dir("{$root}/vendor")            ? 'OK' : 'MISSING') . "\n";

    echo "\n=== public_html/ ({$pubHtml}) ===\n";
    echo "public_html/artisan : " . (file_exists("{$pubHtml}/artisan") ? 'FOUND (webapp dans public_html !)' : 'absent') . "\n";
    echo "public_html/.env    : " . (file_exists("{$pubHtml}/.env")    ? 'FOUND' : 'absent') . "\n";
    echo "public_html/vendor/ : " . (is_dir("{$pubHtml}/vendor")       ? 'FOUND (vendor dans public_html !)' : 'absent') . "\n";

    echo "\n=== Contenu de public_html/index.php (15 premieres lignes) ===\n";
    $lines = file("{$pubHtml}/index.php") ?: [];
    echo htmlspecialchars(implode('', array_slice($lines, 0, 15)));

    echo "\n=== 3 derniers blocs d'erreur du log ===\n";
    $logFile2 = "{$root}/storage/logs/laravel.log";
    if (file_exists($logFile2)) {
        $raw = file_get_contents($logFile2);
        preg_match_all('/\[\d{4}-\d{2}-\d{2}[^\]]+\] \w+\.ERROR:.*?(?=\[\d{4}-\d{2}-\d{2}|\z)/s', $raw, $matches);
        $errors = array_filter($matches[0] ?? []);
        foreach (array_slice($errors, -3) as $err) {
            // Afficher seulement le message (1ere ligne) sans le JSON
            $firstLine = strtok($err, "\n");
            echo htmlspecialchars(substr($firstLine, 0, 400)) . "\n";
        }
    } else {
        echo "Log introuvable.\n";
    }
    echo '</pre>'; exit;
}

// -- MODE DBTEST : ?token=...&dbtest ---------------------------------------
if (isset($_GET['dbtest'])) {
    $envFile = "{$root}/.env";
    if (!file_exists($envFile)) { echo ".env MANQUANT\n"; echo '</pre>'; exit; }
    $env = parse_ini_file($envFile);
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $db   = $env['DB_DATABASE'] ?? '';
    $user = $env['DB_USERNAME'] ?? '';
    $pass = $env['DB_PASSWORD'] ?? '';
    echo "Host     : {$host}\n";
    echo "Database : {$db}\n";
    echo "User     : {$user}\n";
    echo "Password : " . (empty($pass) ? '(vide)' : str_repeat('*', strlen($pass))) . "\n\n";
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass, [PDO::ATTR_TIMEOUT => 5]);
        echo "CONNEXION DB : OK ✅\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables existantes : " . count($tables) . "\n";
        foreach ($tables as $t) echo "  - {$t}\n";
    } catch (PDOException $e) {
        echo "CONNEXION DB : ECHEC ❌\n";
        echo "Erreur : " . $e->getMessage() . "\n";
    }
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
