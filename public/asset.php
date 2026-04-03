<?php
/**
 * Standalone asset server — forces correct MIME types for build assets.
 * Called by public/.htaccess for all /build/assets/* requests.
 * Does NOT require Laravel, route cache, or mod_headers.
 */

$file = $_GET['f'] ?? '';

// Security: block path traversal and absolute paths
if ($file === '' || strpos($file, '..') !== false || $file[0] === '/') {
    http_response_code(403);
    exit();
}

$path = __DIR__ . '/build/assets/' . $file;

if (!is_file($path)) {
    http_response_code(404);
    exit();
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

$mime = match ($ext) {
    'js'          => 'application/javascript; charset=utf-8',
    'css'         => 'text/css; charset=utf-8',
    'json'        => 'application/json; charset=utf-8',
    'webmanifest' => 'application/manifest+json; charset=utf-8',
    'woff2'       => 'font/woff2',
    'woff'        => 'font/woff',
    'ttf'         => 'font/ttf',
    'png'         => 'image/png',
    'jpg', 'jpeg' => 'image/jpeg',
    'svg'         => 'image/svg+xml',
    'ico'         => 'image/x-icon',
    default       => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));

readfile($path);
