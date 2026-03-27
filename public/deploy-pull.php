<?php
/**
 * Emergency Git Pull Script
 * Access via: https://your-domain.com/deploy-pull.php?key=pnmls2026
 * DELETE THIS FILE after use for security
 */

$secret = 'pnmls2026';

if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    die('Forbidden');
}

$root = realpath(__DIR__ . '/..');

echo "<pre>\n";
echo "=== Git Pull ===\n";
echo shell_exec("cd " . escapeshellarg($root) . " && git pull origin main 2>&1");
echo "\n=== Done ===\n";
echo "</pre>\n";
echo "<p><strong>N'oubliez pas de supprimer ce fichier !</strong></p>";
