<?php
/**
 * Emergency JS Server for Hostinger
 * Forces application/javascript MIME type for critical JS files
 */

// Map of emergency JS files
$jsFiles = [
    'app-Bi-CZn0p.js' => 'resources/js/app.js entry point',
    'ui-BHVFKrWF.js' => 'UI components',
    'runtime-core.esm-bundler-DnwlI2lq.js' => 'Vue runtime core'
];

$file = $_GET['file'] ?? 'app-Bi-CZn0p.js';

// Security check
if (!isset($jsFiles[$file]) || strpos($file, '..') !== false) {
    http_response_code(403);
    exit('Invalid JS file requested');
}

$jsPath = __DIR__ . '/assets/' . $file;

if (!file_exists($jsPath)) {
    http_response_code(404);
    exit('JS file not found: ' . htmlspecialchars($file));
}

// Force correct MIME type
header('Content-Type: application/javascript; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=31536000, immutable');

// Output JS
readfile($jsPath);
?>