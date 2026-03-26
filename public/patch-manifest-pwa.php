<?php
/**
 * HOSTINGER PWA MANIFEST PATCHER
 * Modifie directement le manifest pour pointer vers les bons fichiers PWA
 */

$results = [];

function logMessage($message, $type = 'info') {
    global $results;
    $results[] = [
        'time' => date('H:i:s'),
        'type' => $type,
        'message' => $message
    ];
}

// Action de patch
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['patch'])) {
    logMessage("🔧 PATCH MANIFEST PWA - Correction directe", 'info');

    $manifestPath = $_SERVER['DOCUMENT_ROOT'] . '/public/build/manifest.json';

    if (!file_exists($manifestPath)) {
        logMessage("❌ Manifest non trouvé: $manifestPath", 'error');
    } else {
        // Sauvegarder
        $backup = $manifestPath . '.backup-patch-' . date('YmdHis');
        copy($manifestPath, $backup);
        logMessage("💾 Sauvegarde: " . basename($backup), 'success');

        // Lire le manifest
        $content = file_get_contents($manifestPath);
        $manifest = json_decode($content, true);

        if (!$manifest) {
            logMessage("❌ Manifest JSON invalide", 'error');
        } else {
            logMessage("✅ Manifest JSON valide", 'success');

            $changes = 0;

            // PATCH 1: Corriger resources/js/app.js
            if (isset($manifest['resources/js/app.js'])) {
                $oldFile = $manifest['resources/js/app.js']['file'];
                logMessage("🎯 ANCIEN APP FILE: $oldFile", 'info');

                // Remplacer par le bon nom
                $manifest['resources/js/app.js']['file'] = 'assets/app--hCqS3r6.js';
                $changes++;
                logMessage("✅ NOUVEAU APP FILE: assets/app--hCqS3r6.js", 'success');
            }

            // PATCH 2: Corriger toute référence à l'ancien fichier
            foreach ($manifest as $key => &$value) {
                if (is_array($value) && isset($value['file'])) {
                    if (strpos($value['file'], 'app-Bi-CZn0p.js') !== false) {
                        $value['file'] = str_replace('app-Bi-CZn0p.js', 'app--hCqS3r6.js', $value['file']);
                        logMessage("🔄 Corrigé: $key → " . $value['file'], 'success');
                        $changes++;
                    }
                }

                // Corriger les imports aussi
                if (is_array($value) && isset($value['imports'])) {
                    foreach ($value['imports'] as &$import) {
                        if (strpos($import, 'app-Bi-CZn0p.js') !== false) {
                            $import = str_replace('app-Bi-CZn0p.js', 'app--hCqS3r6.js', $import);
                            $changes++;
                        }
                    }
                }
            }

            // PATCH 3: Ajouter les entrées PWA manquantes
            $pwaDependencies = [
                'sw.js' => 'sw.js',
                'workbox-8c29f6e4.js' => 'workbox-8c29f6e4.js',
                'manifest.webmanifest' => 'manifest.webmanifest'
            ];

            foreach ($pwaDependencies as $key => $file) {
                if (!isset($manifest[$key])) {
                    $manifest[$key] = ['file' => $file];
                    $changes++;
                    logMessage("➕ Ajouté PWA: $key → $file", 'success');
                }
            }

            // Sauvegarder le manifest modifié
            $newContent = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (file_put_contents($manifestPath, $newContent)) {
                logMessage("✅ MANIFEST PATCHÉ: $changes modifications", 'success');

                // Vérification finale
                $verifyManifest = json_decode(file_get_contents($manifestPath), true);
                if (isset($verifyManifest['resources/js/app.js'])) {
                    $finalFile = $verifyManifest['resources/js/app.js']['file'];
                    if (strpos($finalFile, 'app--hCqS3r6.js') !== false) {
                        logMessage("🎉 VÉRIFICATION OK: Pointe vers $finalFile", 'success');
                    } else {
                        logMessage("❌ VÉRIFICATION ÉCHEC: Pointe encore vers $finalFile", 'error');
                    }
                }

                // Supprimer l'ancien fichier JS
                $oldJsFile = $_SERVER['DOCUMENT_ROOT'] . '/public/build/assets/app-Bi-CZn0p.js';
                if (file_exists($oldJsFile)) {
                    if (unlink($oldJsFile)) {
                        logMessage("🗑️ SUPPRIMÉ: app-Bi-CZn0p.js", 'success');
                    }
                }

                logMessage("🚀 PATCH TERMINÉ! Rechargez votre site (Ctrl+F5)", 'info');

            } else {
                logMessage("❌ Échec sauvegarde manifest patché", 'error');
            }
        }
    }

    logMessage("✅ Process patchage terminé à " . date('H:i:s'), 'info');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🔧 PWA Manifest Patcher</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f7fa; }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white; padding: 20px; border-radius: 8px; text-align: center;
        }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn {
            background: #dc3545; color: white; border: none; padding: 15px 30px;
            border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold;
        }
        .btn:hover { background: #c82333; }

        .log-entry { margin: 8px 0; padding: 8px 12px; border-radius: 4px; display: flex; gap: 10px; }
        .log-time { color: #666; min-width: 60px; font-family: monospace; }
        .log-success { background: #d4edda; color: #155724; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-warning { background: #fff3cd; color: #856404; }
        .log-info { background: #d1ecf1; color: #0c5460; }

        .problem-box {
            background: #f8d7da; border: 2px solid #dc3545; border-radius: 8px;
            padding: 20px; margin: 20px 0; color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 PWA Manifest Patcher</h1>
            <p>Modification directe du manifest pour forcer PWA</p>
        </div>

        <div class="card">
            <?php if (empty($results)): ?>
                <div class="problem-box">
                    <h3>🚨 Problème critique identifié:</h3>
                    <p><strong>MÊME NOTRE "NOUVEAU" MANIFEST POINTE VERS L'ANCIEN FICHIER!</strong></p>
                    <p>Manifest dit: <code>assets/app-Bi-CZn0p.js</code> ❌</p>
                    <p>Fichier réel: <code>assets/app--hCqS3r6.js</code> ✅</p>
                </div>

                <h3>🔧 Ce que le patch va faire:</h3>
                <ul>
                    <li>✅ Corriger <code>resources/js/app.js</code> → <code>assets/app--hCqS3r6.js</code></li>
                    <li>🔄 Remplacer toutes les références à <code>app-Bi-CZn0p.js</code></li>
                    <li>➕ Ajouter les entrées PWA manquantes (sw.js, workbox, manifest.webmanifest)</li>
                    <li>🗑️ Supprimer l'ancien fichier pour forcer le rechargement</li>
                    <li>💾 Créer une sauvegarde avant modification</li>
                </ul>

                <div style="text-align: center; margin: 30px 0;">
                    <form method="POST" style="display: inline;">
                        <button type="submit" class="btn">
                            🔧 PATCH MANIFEST MAINTENANT
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <h3>📋 Journal de patch:</h3>
                <div style="background: #1a202c; color: #e2e8f0; padding: 15px; border-radius: 8px; max-height: 400px; overflow-y: auto;">
                    <?php foreach ($results as $log): ?>
                        <div class="log-entry log-<?= $log['type'] ?>">
                            <span class="log-time"><?= $log['time'] ?></span>
                            <span><?= htmlspecialchars($log['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="/" class="btn" style="background:#28a745;">🌐 TESTER LE SITE</a>
                    <a href="?" class="btn">🔧 Nouveau Patch</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>🎯 Après le patch, la console devrait afficher:</h3>
            <div style="background: #d4edda; padding: 15px; border-radius: 4px; color: #155724; font-family: monospace;">
                ✅ GET /build/assets/app--hCqS3r6.js 200 (OK)<br>
                ✅ PWA: Service Worker re-enabled after clean deployment<br>
                ✅ PWA: Service Worker registered successfully<br>
                ✅ PWA: App ready for offline use
            </div>
        </div>
    </div>

    <script>
        console.log('🔧 PWA Manifest Patcher Ready');
    </script>
</body>
</html>