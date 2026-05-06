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

$candidatePaths = [
    dirname(__DIR__) . '/public/build/assets/' . $file,
    dirname(__DIR__) . '/storage/app/build/assets/' . $file,
];

$path = null;
$servedFallbackAsset = false;

foreach ($candidatePaths as $candidatePath) {
    if (is_file($candidatePath)) {
        $path = $candidatePath;
        break;
    }
}

if ($path === null) {
    $manifestPath = dirname(__DIR__) . '/public/build/manifest.json';
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $stem = pathinfo($file, PATHINFO_FILENAME);
    $prefix = explode('-', $stem)[0] ?? '';

    if ($prefix !== '' && in_array($extension, ['js', 'css'], true) && is_file($manifestPath)) {
        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (is_array($manifest)) {
            foreach ($manifest as $entry) {
                $manifestFiles = [];

                if (is_array($entry)) {
                    $manifestFiles[] = $entry['file'] ?? '';
                    $manifestFiles = array_merge($manifestFiles, $entry['css'] ?? []);
                }

                foreach ($manifestFiles as $manifestFile) {
                    $manifestName = basename((string) $manifestFile);

                    if (
                        $manifestName !== ''
                        && str_ends_with($manifestName, '.' . $extension)
                        && str_starts_with($manifestName, $prefix . '-')
                    ) {
                        $fallbackPath = dirname(__DIR__) . '/public/build/' . $manifestFile;

                        if (is_file($fallbackPath)) {
                            $path = $fallbackPath;
                            $servedFallbackAsset = true;
                            break 2;
                        }
                    }
                }
            }
        }
    }
}

if ($path === null) {
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
if ($servedFallbackAsset) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
} else {
    header('Cache-Control: public, max-age=31536000, immutable');
}
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));

readfile($path);
