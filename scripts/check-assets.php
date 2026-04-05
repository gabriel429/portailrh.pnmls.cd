<?php
// Diagnostic: check asset serving on production
chdir(dirname(__DIR__));

echo "=== DOCUMENT ROOT ===\n";
echo "SERVER DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "getcwd: " . getcwd() . "\n\n";

echo "=== Vite manifest (public/build/manifest.json) ===\n";
$manifest = json_decode(file_get_contents('public/build/manifest.json'), true);
$appEntry = $manifest['resources/js/app.js'] ?? null;
if ($appEntry) {
    echo "app.js entry: " . $appEntry['file'] . "\n";
    echo "css: " . json_encode($appEntry['css'] ?? []) . "\n";
    $imports = $appEntry['imports'] ?? [];
    foreach ($imports as $imp) {
        $impEntry = $manifest[$imp] ?? null;
        if ($impEntry) {
            echo "  import $imp => " . $impEntry['file'] . "\n";
        }
    }
}

echo "\n=== Build manifest (build/manifest.json) ===\n";
$manifest2 = json_decode(file_get_contents('build/manifest.json'), true);
$appEntry2 = $manifest2['resources/js/app.js'] ?? null;
if ($appEntry2) {
    echo "app.js entry: " . $appEntry2['file'] . "\n";
}

echo "\n=== Check if files exist ===\n";
$filesToCheck = [
    'app-Dh7CNlrz.js',
    'router-Dm_Up0r-.js',
    'syncService-BqQOvddk.js',
];
foreach ($filesToCheck as $f) {
    $inPublic = is_file("public/build/assets/$f");
    $inRoot = is_file("build/assets/$f");
    echo "$f: public/build=" . ($inPublic ? 'YES' : 'NO') . " | build=" . ($inRoot ? 'YES' : 'NO') . "\n";
}

echo "\n=== SPA view references ===\n";
$spaView = file_get_contents('resources/views/spa.blade.php');
// Extract Vite directive or script tags
preg_match_all('/(src|href)=["\']([^"\']+build[^"\']*)["\']/', $spaView, $matches);
if (empty($matches[0])) {
    echo "No build references found in HTML (likely uses @vite directive)\n";
    echo "First 500 chars: " . substr($spaView, 0, 500) . "\n";
} else {
    foreach ($matches[2] as $ref) echo "  $ref\n";
}

echo "\n=== LiteSpeed check ===\n";
echo "Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? php_sapi_name()) . "\n";
