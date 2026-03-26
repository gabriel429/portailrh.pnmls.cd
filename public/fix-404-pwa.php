<?php
/**
 * HOSTINGER PWA FIX FINAL - Créer la structure manquante
 * Laravel cherche dans /build/ mais fichiers sont dans /public/build/
 */

$results = [];
$errors = [];

function logMessage($message, $type = 'info') {
    global $results;
    $results[] = [
        'time' => date('H:i:s'),
        'type' => $type,
        'message' => $message
    ];
}

// Action demandée
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['fix'])) {
    logMessage("🚀 CRÉATION ALIAS PWA - Résolution 404", 'info');

    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $sourceBuildDir = $docRoot . '/public/build';
    $targetBuildDir = $docRoot . '/build';

    logMessage("📂 Source: $sourceBuildDir", 'info');
    logMessage("📂 Target: $targetBuildDir", 'info');

    // Vérifier que la source existe
    if (!is_dir($sourceBuildDir)) {
        logMessage("❌ Source directory missing: /public/build/", 'error');
        $errors[] = "Directory /public/build/ not found";
    } else {
        logMessage("✅ Source directory exists", 'success');

        // Créer le répertoire cible s'il n'existe pas
        if (!is_dir($targetBuildDir)) {
            if (mkdir($targetBuildDir, 0755, true)) {
                logMessage("✅ Created directory: /build/", 'success');
            } else {
                logMessage("❌ Failed to create: /build/", 'error');
                $errors[] = "Cannot create /build/ directory";
            }
        }

        // Si on peut créer des liens symboliques
        if (function_exists('symlink') && empty($errors)) {
            $assetsSource = $sourceBuildDir . '/assets';
            $assetsTarget = $targetBuildDir . '/assets';

            if (is_dir($assetsSource)) {
                // Supprimer le lien existant s'il y en a un
                if (is_link($assetsTarget)) {
                    unlink($assetsTarget);
                }

                if (symlink($assetsSource, $assetsTarget)) {
                    logMessage("🔗 Symbolic link created: /build/assets → /public/build/assets", 'success');
                } else {
                    logMessage("⚠️ Symlink failed, copying files instead...", 'warning');
                    // Copie manuelle si symlink échoue
                    copyDirectoryRecursive($assetsSource, $assetsTarget);
                }
            }

            // Copier les fichiers critiques à la racine
            $criticalFiles = ['sw.js', 'workbox-8c29f6e4.js', 'manifest.json', 'manifest.webmanifest'];
            foreach ($criticalFiles as $file) {
                $sourceFile = $sourceBuildDir . '/' . $file;
                $targetFile = $targetBuildDir . '/' . $file;

                if (file_exists($sourceFile)) {
                    if (copy($sourceFile, $targetFile)) {
                        logMessage("✅ Copied: $file", 'success');
                    } else {
                        logMessage("❌ Copy failed: $file", 'error');
                    }
                }
            }

        } else {
            logMessage("⚠️ Symlink not available, using copy method", 'warning');
            // Méthode de copie complète
            copyDirectoryRecursive($sourceBuildDir, $targetBuildDir);
        }
    }

    // Test final
    $testFile = $docRoot . '/build/assets/app--hCqS3r6.js';
    if (file_exists($testFile)) {
        $size = number_format(filesize($testFile) / 1024, 1);
        logMessage("🎉 SUCCESS: /build/assets/app--hCqS3r6.js accessible ($size KB)", 'success');
        logMessage("🔄 NEXT: Refresh your website (Ctrl+F5)", 'info');
    } else {
        logMessage("❌ FINAL CHECK FAILED: File still not accessible", 'error');
    }

    logMessage("✅ Process completed at " . date('H:i:s'), 'info');
}

function copyDirectoryRecursive($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    $files = scandir($source);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                copyDirectoryRecursive($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }
    logMessage("📁 Directory copied: " . basename($source), 'success');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🔧 PWA Fix Final - Résolution 404</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f7fa; }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white; padding: 20px; border-radius: 8px; text-align: center;
        }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn {
            background: #e74c3c; color: white; border: none; padding: 15px 30px;
            border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold;
        }
        .btn:hover { background: #c0392b; }

        .log-entry { margin: 8px 0; padding: 8px 12px; border-radius: 4px; display: flex; gap: 10px; }
        .log-time { color: #666; min-width: 60px; font-family: monospace; }
        .log-success { background: #d4edda; color: #155724; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-warning { background: #fff3cd; color: #856404; }
        .log-info { background: #d1ecf1; color: #0c5460; }

        .problem-box {
            background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;
            padding: 20px; margin: 20px 0; color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 PWA Fix Final</h1>
            <p>Résolution de l'erreur 404: build/assets/app--hCqS3r6.js</p>
        </div>

        <div class="card">
            <?php if (empty($results)): ?>
                <div class="problem-box">
                    <h3>🚨 Problème identifié:</h3>
                    <p><strong>Laravel cherche:</strong> <code>/build/assets/app--hCqS3r6.js</code></p>
                    <p><strong>Fichier existe dans:</strong> <code>/public/build/assets/app--hCqS3r6.js</code></p>
                    <p><strong>Solution:</strong> Créer l'alias manquant pour que Laravel trouve les fichiers</p>
                </div>

                <h3>🔧 Actions à effectuer:</h3>
                <ul>
                    <li>✅ Créer le répertoire <code>/build/</code></li>
                    <li>🔗 Lier <code>/build/assets</code> → <code>/public/build/assets</code></li>
                    <li>📄 Copier les fichiers critiques (sw.js, manifests)</li>
                    <li>🧪 Test d'accessibilité final</li>
                </ul>

                <div style="text-align: center; margin: 30px 0;">
                    <form method="POST" style="display: inline;">
                        <button type="submit" class="btn">
                            🔧 CRÉER L'ALIAS PWA
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <h3>📋 Journal d'exécution:</h3>
                <div style="background: #1a202c; color: #e2e8f0; padding: 15px; border-radius: 8px; max-height: 400px; overflow-y: auto;">
                    <?php foreach ($results as $log): ?>
                        <div class="log-entry log-<?= $log['type'] ?>">
                            <span class="log-time"><?= $log['time'] ?></span>
                            <span><?= htmlspecialchars($log['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <?php if (empty($errors)): ?>
                        <a href="/" class="btn" style="background:#28a745;">🌐 TESTER LE SITE</a>
                        <a href="?" class="btn">🔄 Nouveau Fix</a>
                    <?php else: ?>
                        <a href="?" class="btn">🔄 Réessayer</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>🔍 Information System:</h3>
            <p><strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?></p>
            <p><strong>Symlink Support:</strong> <?= function_exists('symlink') ? '✅ Available' : '❌ Not available' ?></p>
            <p><strong>Expected File:</strong> <code>/build/assets/app--hCqS3r6.js</code></p>
            <p><strong>Existing File:</strong> <code>/public/build/assets/app--hCqS3r6.js</code></p>
        </div>
    </div>

    <script>
        console.log('🔧 PWA Fix Final Ready');
    </script>
</body>
</html>