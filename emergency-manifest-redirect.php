<?php
/**
 * EMERGENCY HOSTINGER LARAVEL VITE FIX
 * Upload this file as: /public_html/public/build/manifest.json
 * It will redirect Laravel to the correct manifest location
 */

// Get the real manifest from /build/manifest.json
$realManifestPath = $_SERVER['DOCUMENT_ROOT'] . '/build/manifest.json';

// Check if real manifest exists
if (!file_exists($realManifestPath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Manifest not found',
        'searched_path' => $realManifestPath,
        'note' => 'Upload build folder to /public_html/build/'
    ]);
    exit;
}

// Read and return the real manifest with correct Content-Type
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Redirected-From: /public/build/manifest.json');
header('X-Real-Path: /build/manifest.json');

// Read and output the real manifest
readfile($realManifestPath);
?>