<?php
/**
 * Hostinger Asset Servor - PHP MIME Type Fix
 * Serve assets with correct MIME types when .htaccess fails
 */

// Security: Only allow access from same domain
if (!isset($_SERVER['HTTP_HOST']) || !isset($_GET['file'])) {
    http_response_code(403);
    exit('Access denied');
}

// Get requested file path
$requestedFile = $_GET['file'] ?? '';

// Security: Prevent directory traversal
if (strpos($requestedFile, '..') !== false || strpos($requestedFile, '/') === 0) {
    http_response_code(403);
    exit('Invalid file path');
}

// Map to physical file in assets directory
$assetPath = __DIR__ . '/assets/' . $requestedFile;

// Check if file exists
if (!file_exists($assetPath) || !is_file($assetPath)) {
    http_response_code(404);
    exit('File not found: ' . htmlspecialchars($requestedFile));
}

// Get file extension
$extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));

// Define MIME types - EXACT mapping
$mimeTypes = [
    'js' => 'application/javascript; charset=utf-8',
    'css' => 'text/css; charset=utf-8',
    'json' => 'application/json; charset=utf-8',
    'webmanifest' => 'application/manifest+json; charset=utf-8',
    'woff2' => 'font/woff2',
    'woff' => 'font/woff',
    'ttf' => 'font/ttf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'svg' => 'image/svg+xml; charset=utf-8',
    'ico' => 'image/x-icon',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf'
];

// Get correct MIME type
$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Set correct MIME type header - FORCE it!
header('Content-Type: ' . $mimeType);

// Set cache headers for better performance
if (in_array($extension, ['js', 'css', 'woff2', 'woff', 'png', 'jpg', 'svg', 'ico'])) {
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
} else {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Set content length
$fileSize = filesize($assetPath);
if ($fileSize !== false) {
    header('Content-Length: ' . $fileSize);
}

// Output file content
readfile($assetPath);

// Log successful serve (for debugging)
error_log("MIME Fix: Served {$requestedFile} as {$mimeType}");
exit;
?>