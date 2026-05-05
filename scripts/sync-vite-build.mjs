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

    if (content.includes('stale browser/PWA references')) {
        return;
    }

    writeFileSync(
        file,
        content.replace('RewriteEngine On', `RewriteEngine On\n\n${fallbackRule}`),
    );
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

    if (content.includes('$manifestPath = __DIR__ . \'/manifest.json\';')) {
        return;
    }

    const marker = "$assetPath = __DIR__ . '/assets/' . $requestedFile;";
    writeFileSync(file, content.replace(marker, `${marker}\n\n${fallbackBlock}`));
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
