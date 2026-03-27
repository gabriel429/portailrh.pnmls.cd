<?php
/**
 * Sync build files from public/ to public_html/ (Hostinger)
 * Access: /sync-build.php?key=pnmls2026
 */
if (!isset($_GET['key']) || $_GET['key'] !== 'pnmls2026') {
    http_response_code(403);
    die('Forbidden');
}

echo "<pre style='background:#1e293b;color:#e2e8f0;padding:20px;border-radius:8px;font-size:13px;'>\n";

$publicHtml = __DIR__;
$root = realpath($publicHtml . '/..');
$gitBuild = $root . '/public/build';

echo "📁 public_html: $publicHtml\n";
echo "📁 git public/build: $gitBuild\n";
echo "─────────────────────────────\n\n";

// 1. Git pull first
echo "=== Git Pull ===\n";
echo shell_exec("cd " . escapeshellarg($root) . " && git pull origin main 2>&1");
echo "\n";

// 2. Sync build/ directory
if (is_dir($gitBuild)) {
    echo "=== Sync build ===\n";
    echo shell_exec("rm -rf " . escapeshellarg($publicHtml . '/build'));
    echo shell_exec("cp -r " . escapeshellarg($gitBuild) . " " . escapeshellarg($publicHtml . '/build') . " 2>&1");

    $count = count(glob($publicHtml . '/build/assets/*'));
    echo "✅ Build synced! ($count assets)\n\n";
} else {
    echo "❌ $gitBuild not found!\n\n";
}

// 3. Update deploy-pull.php too
$gitDeploy = $root . '/public/deploy-pull.php';
if (file_exists($gitDeploy)) {
    copy($gitDeploy, $publicHtml . '/deploy-pull.php');
    echo "✅ deploy-pull.php updated\n";
}

// 4. Update this script too
$gitSync = $root . '/public/sync-build.php';
if (file_exists($gitSync)) {
    copy($gitSync, $publicHtml . '/sync-build.php');
    echo "✅ sync-build.php updated\n";
}

echo "\n✅ Terminé!\n";
echo "</pre>\n";
