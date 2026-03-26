<?php
/**
 * HOSTINGER VITE CONFIG FIX
 * Place this file as public_html/public/hostinger-vite-fix.php
 * Redirects Laravel Vite requests to correct paths
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Si c'est une demande pour build/assets ou manifest
if (preg_match('#^/build/(.+)$#', $requestUri, $matches)) {
    $file = $matches[1];

    // Rediriger vers le vrai emplacement
    $realPath = $_SERVER['DOCUMENT_ROOT'] . '/build/' . $file;

    if (file_exists($realPath)) {
        // Déterminer le MIME type
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'woff2' => 'font/woff2',
            'png' => 'image/png',
            'svg' => 'image/svg+xml'
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($realPath));
        header('Cache-Control: public, max-age=31536000');

        readfile($realPath);
        exit;
    }
}

// Si pas trouvé, retourner 404
http_response_code(404);
echo "File not found: " . htmlspecialchars($requestUri);
?>