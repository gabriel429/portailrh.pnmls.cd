<?php
/**
 * DIAGNOSTIC HOSTINGER - Localisation des fichiers PWA
 * Ce script va scanner et trouver où sont VOS fichiers
 */

$documentRoot = $_SERVER['DOCUMENT_ROOT'];

// Fichiers PWA qu'on cherche
$targetFiles = [
    'app--hCqS3r6.js',
    'sw.js',
    'workbox-8c29f6e4.js',
    'manifest.webmanifest',
    'manifest.json'
];

// Dossiers à scanner
$scanDirs = [
    '/build',
    '/public/build',
    '/build/assets',
    '/public/build/assets',
    '/',
    '/public'
];

$results = [];

function scanDirectory($dir, $targetFiles) {
    global $documentRoot;
    $fullPath = $documentRoot . $dir;
    $found = [];

    if (!is_dir($fullPath)) {
        return ['error' => "Dossier n'existe pas: $dir"];
    }

    try {
        $files = scandir($fullPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $fullPath . '/' . $file;

            // Chercher nos fichiers cibles
            if (in_array($file, $targetFiles)) {
                $found[] = [
                    'file' => $file,
                    'path' => $dir . '/' . $file,
                    'full_path' => $filePath,
                    'size' => number_format(filesize($filePath) / 1024, 1) . ' KB',
                    'readable' => is_readable($filePath) ? 'YES' : 'NO'
                ];
            }

            // Scanner les sous-dossiers
            if (is_dir($filePath) && !in_array($file, ['.', '..', 'vendor', 'node_modules'])) {
                $subFiles = scanDirectory($dir . '/' . $file, $targetFiles);
                if (isset($subFiles['found']) && count($subFiles['found']) > 0) {
                    $found = array_merge($found, $subFiles['found']);
                }
            }
        }

        return ['found' => $found, 'total_files' => count($files) - 2];

    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// Scanner tous les dossiers
foreach ($scanDirs as $dir) {
    $results[$dir] = scanDirectory($dir, $targetFiles);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🔍 Diagnostic PWA - Localisation des fichiers</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; text-align: center; padding: 20px; border-radius: 8px; }
        .found { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .not-found { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .file-info { background: #e2e3e5; padding: 10px; margin: 5px 0; border-radius: 4px; font-family: monospace; }
        .summary { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .copy-btn { background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; margin-left: 10px; }
        .directory-header { background: #e9ecef; padding: 10px; border-radius: 4px; margin: 10px 0; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnostic PWA Hostinger</h1>
            <p>Localisation exacte de vos fichiers PWA</p>
            <strong>Document Root:</strong> <?= $documentRoot ?>
        </div>

        <div class="card">
            <h2>📁 Scan des dossiers</h2>

            <?php
            $allFound = [];
            $totalFound = 0;

            foreach ($results as $dir => $result):
            ?>
                <div class="directory-header">📂 Dossier: <?= $dir ?></div>

                <?php if (isset($result['error'])): ?>
                    <div class="not-found">
                        ❌ <strong>Erreur:</strong> <?= htmlspecialchars($result['error']) ?>
                    </div>

                <?php elseif (empty($result['found'])): ?>
                    <div class="not-found">
                        🔍 Aucun fichier PWA trouvé dans ce dossier (<?= $result['total_files'] ?> fichiers scannés)
                    </div>

                <?php else: ?>
                    <div class="found">
                        ✅ <strong><?= count($result['found']) ?> fichier(s) PWA trouvé(s) !</strong>

                        <?php foreach ($result['found'] as $file): ?>
                            <div class="file-info">
                                <strong><?= $file['file'] ?></strong>
                                <span style="color: #666;">(<?= $file['size'] ?>)</span>
                                <span style="color: #28a745;">Lisible: <?= $file['readable'] ?></span>
                                <br>
                                <em>Path complet:</em> <code><?= $file['full_path'] ?></code>
                                <button class="copy-btn" onclick="copyToClipboard('<?= addslashes($file['path']) ?>')">Copier path</button>
                            </div>
                            <?php
                            $allFound[] = $file;
                            $totalFound++;
                            ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="summary">
            <h3>📊 Résumé de la découverte</h3>
            <p><strong>Total fichiers PWA trouvés:</strong> <?= $totalFound ?>/<?= count($targetFiles) ?></p>

            <?php if ($totalFound > 0): ?>
                <h4>🎯 Fichiers découverts:</h4>
                <?php
                $filesByName = [];
                foreach ($allFound as $file) {
                    $filesByName[$file['file']][] = $file;
                }

                foreach ($filesByName as $fileName => $locations):
                ?>
                    <div style="margin: 10px 0;">
                        <strong><?= $fileName ?>:</strong>
                        <?php if (count($locations) === 1): ?>
                            <code><?= $locations[0]['path'] ?></code>
                        <?php else: ?>
                            <em>Trouvé dans <?= count($locations) ?> emplacements:</em>
                            <?php foreach ($locations as $loc): ?>
                                <br>&nbsp;&nbsp;- <code><?= $loc['path'] ?></code>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <h4>🔧 Script de correction suggéré:</h4>
                <pre id="corrected-script"><?php
                    // Générer le script corrigé basé sur les fichiers trouvés
                    $sourceMap = [];
                    foreach ($allFound as $file) {
                        if (!isset($sourceMap[$file['file']])) {
                            $sourceMap[$file['file']] = $file['path'];
                        }
                    }

                    echo "// Chemins corrigés pour votre configuration:\n";
                    echo "/*\n";
                    foreach ($sourceMap as $fileName => $path) {
                        echo "'{$fileName}' => '{$path}',\n";
                    }
                    echo "*/\n\n";
                    echo "// Mettez ceci dans force-update-pwa.php ligne ~15:\n";
                    echo "\$PWA_FILES = [\n";

                    $fileMapping = [
                        'app--hCqS3r6.js' => ['target' => '/public/build/assets/app--hCqS3r6.js', 'type' => 'js'],
                        'sw.js' => ['target' => '/public/build/sw.js', 'type' => 'js'],
                        'workbox-8c29f6e4.js' => ['target' => '/public/build/workbox-8c29f6e4.js', 'type' => 'js'],
                        'manifest.webmanifest' => ['target' => '/public/build/manifest.webmanifest', 'type' => 'json'],
                        'manifest.json' => ['target' => '/public/build/manifest.json', 'type' => 'json']
                    ];

                    foreach ($fileMapping as $fileName => $config) {
                        if (isset($sourceMap[$fileName])) {
                            echo "    [\n";
                            echo "        'source' => '{$sourceMap[$fileName]}',\n";
                            echo "        'target' => '{$config['target']}',\n";
                            echo "        'type' => '{$config['type']}',\n";
                            echo "        'critical' => true\n";
                            echo "    ],\n";
                        }
                    }
                    echo "];\n";
                ?></pre>

                <button onclick="copyToClipboard(document.getElementById('corrected-script').textContent)" class="copy-btn" style="padding: 10px 20px;">
                    📋 Copier le script corrigé
                </button>

            <?php else: ?>
                <div class="not-found">
                    ❌ <strong>Aucun fichier PWA trouvé !</strong><br>
                    Vérifiez que vous avez bien uploadé les fichiers sur Hostinger.
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>📋 Fichiers recherchés:</h3>
            <ul>
                <?php foreach ($targetFiles as $file): ?>
                    <li><code><?= $file ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('📋 Copié dans le presse-papiers !');
            });
        }

        console.log('🔍 Diagnostic PWA chargé');
        console.log('📂 Document Root: <?= $documentRoot ?>');
    </script>
</body>
</html>