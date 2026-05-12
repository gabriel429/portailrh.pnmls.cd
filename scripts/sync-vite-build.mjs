import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');
const sourceDir = path.join(projectRoot, 'public', 'build');
const targetDir = path.join(projectRoot, 'build');

function patchHtaccess(buildDir) {
    const file = path.join(buildDir, '.htaccess');

    if (!existsSync(file)) {
        return;
    }

    const fallbackRule = [
        '# Missing hashed assets are usually stale browser/PWA references.',
        "# Serve the current matching asset instead of returning Hostinger's HTML 404.",
        'RewriteCond %{REQUEST_FILENAME} !-f',
        'RewriteRule ^assets/(.+\\.(js|css))$ serve-asset.php?file=$1 [L,QSA]',
    ].join('\n');

    const content = readFileSync(file, 'utf8');
    let patched = content;

    if (!patched.includes('stale browser/PWA references')) {
        patched = patched.replace('RewriteEngine On', `RewriteEngine On\n\n${fallbackRule}`);
    }

    const fallbackRewriteRules = [
        'RewriteRule ^assets/(.+\\.css)$ serve-asset.php?file=$1 [L,QSA]',
        'RewriteRule ^assets/(.+\\.js)$ serve-asset.php?file=$1 [L,QSA]',
        'RewriteRule ^assets/(.+\\.json)$ serve-asset.php?file=$1 [L,QSA]',
        'RewriteRule ^assets/(.+\\.(woff2|woff|ttf))$ serve-asset.php?file=$1 [L,QSA]',
    ];

    for (const rule of fallbackRewriteRules) {
        const ruleIndex = patched.indexOf(rule);
        if (ruleIndex === -1) {
            continue;
        }

        const blockStart = Math.max(
            patched.lastIndexOf('\n# For ', ruleIndex),
            patched.lastIndexOf('\n\n', ruleIndex),
        );
        const block = patched.slice(blockStart, ruleIndex);

        if (block.includes('RewriteCond %{REQUEST_FILENAME} !-f')) {
            continue;
        }

        const uriCondIndex = patched.indexOf('RewriteCond %{REQUEST_URI}', blockStart);
        if (uriCondIndex !== -1 && uriCondIndex < ruleIndex) {
            patched = `${patched.slice(0, uriCondIndex)}RewriteCond %{REQUEST_FILENAME} !-f\n${patched.slice(uriCondIndex)}`;
        }
    }

    if (patched !== content) {
        writeFileSync(file, patched);
    }
}

function patchServeAsset(buildDir) {
    const file = path.join(buildDir, 'serve-asset.php');

    if (!existsSync(file)) {
        return;
    }

    const fallbackBlock = String.raw`
if (!file_exists($assetPath) || !is_file($assetPath)) {
    $extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
    $stem = pathinfo($requestedFile, PATHINFO_FILENAME);
    $prefix = explode('-', $stem)[0] ?? '';
    $manifestPath = __DIR__ . '/manifest.json';

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
                        $fallbackPath = __DIR__ . '/' . $manifestFile;

                        if (is_file($fallbackPath)) {
                            $assetPath = $fallbackPath;
                            $servedFallbackAsset = true;
                            break 2;
                        }
                    }
                }
            }
        }
    }
}
`.trim();

    const content = readFileSync(file, 'utf8');
    let patched = content;

    const marker = "$assetPath = __DIR__ . '/assets/' . $requestedFile;";

    if (!patched.includes('$manifestPath = __DIR__ . \'/manifest.json\';')) {
        patched = patched.replace(marker, `${marker}\n$servedFallbackAsset = false;\n\n${fallbackBlock}`);
    }

    const staleAssetBlock = String.raw`
    // Stale Vite asset reload shim: avoid returning Hostinger HTML for old chunks.
    $missingExtension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));

    if ($missingExtension === 'js') {
        $payload = "console.warn('E-PNMLS: stale build asset, forcing a clean reload.');\n"
            . "window.location.reload();\n"
            . "export default {};\n";
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Content-Length: ' . strlen($payload));
        echo $payload;
        exit;
    }

    if ($missingExtension === 'css') {
        $payload = "/* E-PNMLS: stale CSS asset ignored. */\n";
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: text/css; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Content-Length: ' . strlen($payload));
        echo $payload;
        exit;
    }
`.trimEnd();

    const missingFileMarker = "// Check if file exists\nif (!file_exists($assetPath) || !is_file($assetPath)) {\n";

    if (!patched.includes('Stale Vite asset reload shim')) {
        patched = patched.replace(missingFileMarker, `${missingFileMarker}${staleAssetBlock}\n\n`);
    }

    const cacheMarker = "if (in_array($extension, ['js', 'css', 'woff2', 'woff', 'png', 'jpg', 'svg', 'ico'])) {";
    const cacheReplacement = String.raw`if ($servedFallbackAsset) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
} elseif (in_array($extension, ['js', 'css', 'woff2', 'woff', 'png', 'jpg', 'svg', 'ico'])) {`;

    if (!patched.includes('if ($servedFallbackAsset) {')) {
        patched = patched.replace(cacheMarker, cacheReplacement);
    }

    if (patched !== content) {
        writeFileSync(file, patched);
    }
}

function patchBuildFallbacks(buildDir) {
    patchHtaccess(buildDir);
    patchServeAsset(buildDir);
}

if (!existsSync(sourceDir)) {
    console.error('Source build not found:', sourceDir);
    process.exit(1);
}

patchBuildFallbacks(sourceDir);
rmSync(targetDir, { recursive: true, force: true });
mkdirSync(targetDir, { recursive: true });
cpSync(sourceDir, targetDir, { recursive: true });
patchBuildFallbacks(targetDir);

console.log('Mirrored Vite build from public/build to build');
