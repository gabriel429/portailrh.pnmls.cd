<?php
/**
 * Emergency CSS Server for Hostinger
 * Forces text/css MIME type for app-CwcRsQhl.css
 */

$cssFile = __DIR__ . '/assets/app-CwcRsQhl.css';

if (!file_exists($cssFile)) {
    http_response_code(404);
    exit('CSS file not found');
}

// Force correct MIME type
header('Content-Type: text/css; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=31536000, immutable');

// Output CSS
readfile($cssFile);
?>