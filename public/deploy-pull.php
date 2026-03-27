<?php
/**
 * Emergency Deploy Script - Init Git + Pull
 * Access: /deploy-pull.php?key=pnmls2026
 * DELETE THIS FILE after use!
 */

$secret = 'pnmls2026';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    die('Forbidden');
}

echo "<pre style='background:#1e293b;color:#e2e8f0;padding:20px;border-radius:8px;font-size:13px;'>\n";

// Detect project root
$publicDir = __DIR__;
$root = realpath($publicDir . '/..');

echo "📁 Public dir: $publicDir\n";
echo "📁 Project root: $root\n";
echo "─────────────────────────────\n\n";

// Step 1: Check if git exists
$gitVersion = shell_exec("git --version 2>&1");
echo "🔧 Git: $gitVersion\n";

// Step 2: Check if .git exists
$gitDir = $root . '/.git';
if (is_dir($gitDir)) {
    echo "✅ Git repo found at $root\n\n";
    echo "=== Git Pull ===\n";
    echo shell_exec("cd " . escapeshellarg($root) . " && git pull origin main 2>&1");
    echo "\n=== Nettoyage anciens fichiers build ===\n";
    echo shell_exec("cd " . escapeshellarg($root) . " && git clean -fd public/build/ 2>&1");
} else {
    echo "❌ No .git directory found at $root\n";
    echo "🔄 Initializing git repo and connecting to GitHub...\n\n";

    $commands = [
        "cd " . escapeshellarg($root) . " && git init 2>&1",
        "cd " . escapeshellarg($root) . " && git remote add origin https://github.com/gabriel429/portailrh.pnmls.cd.git 2>&1",
        "cd " . escapeshellarg($root) . " && git fetch origin main 2>&1",
        "cd " . escapeshellarg($root) . " && git checkout -f main 2>&1",
    ];

    foreach ($commands as $cmd) {
        echo "$ " . preg_replace('/cd .+ && /', '', $cmd) . "\n";
        echo shell_exec($cmd);
        echo "\n";
    }

    echo "=== Nettoyage anciens fichiers build ===\n";
    echo shell_exec("cd " . escapeshellarg($root) . " && git clean -fd public/build/ 2>&1");
}

echo "\n─────────────────────────────\n";
echo "=== Directory listing (public/build/) ===\n";
$buildDir = $publicDir . '/build';
if (is_dir($buildDir)) {
    $files = scandir($buildDir);
    echo "Files: " . count($files) . "\n";
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') echo "  $f\n";
    }
    if (is_dir($buildDir . '/assets')) {
        $assets = scandir($buildDir . '/assets');
        echo "\nAssets: " . (count($assets) - 2) . " files\n";
        // Show first 10
        $i = 0;
        foreach ($assets as $f) {
            if ($f !== '.' && $f !== '..') {
                echo "  $f\n";
                if (++$i >= 10) { echo "  ... and more\n"; break; }
            }
        }
    }
} else {
    echo "❌ public/build/ directory NOT FOUND\n";
}

echo "\n✅ Done!\n";
echo "</pre>\n";
echo "<p style='color:red;font-weight:bold;'>⚠️ SUPPRIMEZ CE FICHIER APRES USAGE !</p>\n";
