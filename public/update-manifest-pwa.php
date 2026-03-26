<?php
/**
 * HOSTINGER PWA MANIFEST UPDATER
 * Force Laravel à utiliser le nouveau manifest avec PWA activé
 */

// Chemins des manifests
$manifestPaths = [
    'Laravel recherche' => $_SERVER['DOCUMENT_ROOT'] . '/public/build/manifest.json',
    'Nouveau build' => $_SERVER['DOCUMENT_ROOT'] . '/build/manifest.json'
];

$results = [];

function logMessage($message, $type = 'info') {
    global $results;
    $results[] = [
        'time' => date('H:i:s'),
        'type' => $type,
        'message' => $message
    ];
}

// Action de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['update'])) {
    logMessage("🔄 MISE À JOUR MANIFEST PWA", 'info');

    $laravelManifest = $manifestPaths['Laravel recherche'];
    $newManifest = $manifestPaths['Nouveau build'];

    // Vérifier que le nouveau manifest existe
    if (!file_exists($newManifest)) {
        logMessage("❌ Nouveau manifest introuvable: $newManifest", 'error');
    } else {
        logMessage("✅ Nouveau manifest trouvé", 'success');

        // Sauvegarder l'ancien manifest
        if (file_exists($laravelManifest)) {
            $backup = $laravelManifest . '.backup-' . date('YmdHis');
            if (copy($laravelManifest, $backup)) {
                logMessage("💾 Sauvegarde ancien manifest: " . basename($backup), 'success');
            }
        }

        // Copier le nouveau manifest
        if (copy($newManifest, $laravelManifest)) {
            logMessage("✅ Nouveau manifest copié vers Laravel", 'success');

            // Vérifier le contenu
            $content = file_get_contents($laravelManifest);
            $manifest = json_decode($content, true);

            if ($manifest && isset($manifest['resources/js/app.js'])) {
                $appFile = $manifest['resources/js/app.js']['file'];
                logMessage("🎯 APP.JS FILE: $appFile", 'info');

                if (strpos($appFile, 'app--hCqS3r6.js') !== false) {
                    logMessage("✅ CORRECT: Pointe vers le NOUVEAU fichier PWA!", 'success');
                } else {
                    logMessage("❌ PROBLÈME: Pointe encore vers l'ancien fichier!", 'error');
                }
            }

            // Supprimer l'ancien fichier JS pour forcer le rechargement
            $oldJsFile = $_SERVER['DOCUMENT_ROOT'] . '/public/build/assets/app-Bi-CZn0p.js';
            if (file_exists($oldJsFile)) {
                if (unlink($oldJsFile)) {
                    logMessage("🗑️ SUPPRIMÉ: app-Bi-CZn0p.js (ancien fichier)", 'success');
                } else {
                    logMessage("⚠️ Échec suppression ancien fichier", 'warning');
                }
            }

            logMessage("🎉 MANIFEST PWA UPDATED SUCCESSFULLY!", 'success');
            logMessage("🔄 Action: Rechargez votre site (Ctrl+F5)", 'info');

        } else {
            logMessage("❌ Échec copie du nouveau manifest", 'error');
        }
    }

    logMessage("✅ Process terminé à " . date('H:i:s'), 'info');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🔄 PWA Manifest Updater</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f7fa; }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: linear-gradient(135deg, #28a745, #20862d);
            color: white; padding: 20px; border-radius: 8px; text-align: center;
        }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn {
            background: #28a745; color: white; border: none; padding: 15px 30px;
            border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold;
        }
        .btn:hover { background: #218838; }

        .log-entry { margin: 8px 0; padding: 8px 12px; border-radius: 4px; display: flex; gap: 10px; }
        .log-time { color: #666; min-width: 60px; font-family: monospace; }
        .log-success { background: #d4edda; color: #155724; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-warning { background: #fff3cd; color: #856404; }
        .log-info { background: #d1ecf1; color: #0c5460; }

        .file-info { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; font-family: monospace; }
        .status-box { border: 2px solid #ffc107; background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 PWA Manifest Updater</h1>
            <p>Force Laravel à utiliser le nouveau manifest avec PWA activé</p>
        </div>

        <div class="card">
            <?php if (empty($results)): ?>
                <div class="status-box">
                    <h3>🚨 Problème détecté:</h3>
                    <p><strong>Console montre:</strong> <code>app-Bi-CZn0p.js</code> (ancien fichier désactivé)</p>
                    <p><strong>Doit charger:</strong> <code>app--hCqS3r6.js</code> (nouveau fichier PWA)</p>
                    <p><strong>Cause:</strong> Laravel utilise un ancien manifest.json</p>
                </div>

                <h3>📋 Analyse des manifests:</h3>
                <?php foreach ($manifestPaths as $label => $path): ?>
                    <div class="file-info">
                        <strong><?= $label ?>:</strong><br>
                        <?= $path ?><br>
                        <?php if (file_exists($path)): ?>
                            <?php
                            $size = number_format(filesize($path) / 1024, 1);
                            $modified = date('Y-m-d H:i:s', filemtime($path));
                            echo "✅ EXISTS ({$size} KB) - Modifié: {$modified}";

                            // Analyser le contenu
                            $content = file_get_contents($path);
                            $manifest = json_decode($content, true);
                            if ($manifest && isset($manifest['resources/js/app.js'])) {
                                $appFile = $manifest['resources/js/app.js']['file'];
                                echo "<br>📄 APP FILE: {$appFile}";
                                if (strpos($appFile, 'app-Bi-CZn0p.js') !== false) {
                                    echo " <span style='color:#dc3545;'>[ANCIEN]</span>";
                                } elseif (strpos($appFile, 'app--hCqS3r6.js') !== false) {
                                    echo " <span style='color:#28a745;'>[NOUVEAU]</span>";
                                }
                            }
                            ?>
                        <?php else: ?>
                            ❌ NOT FOUND
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="text-align: center; margin: 30px 0;">
                    <form method="POST" style="display: inline;">
                        <button type="submit" class="btn">
                            🔄 METTRE À JOUR MANIFEST PWA
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <h3>📋 Journal de mise à jour:</h3>
                <div style="background: #1a202c; color: #e2e8f0; padding: 15px; border-radius: 8px; max-height: 400px; overflow-y: auto;">
                    <?php foreach ($results as $log): ?>
                        <div class="log-entry log-<?= $log['type'] ?>">
                            <span class="log-time"><?= $log['time'] ?></span>
                            <span><?= htmlspecialchars($log['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="/" class="btn" style="background:#007bff;">🌐 TESTER LE SITE</a>
                    <a href="?" class="btn">🔄 Nouveau Update</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>🎯 Résultat attendu après mise à jour:</h3>
            <div style="background: #d4edda; padding: 15px; border-radius: 4px; color: #155724;">
                <strong>Console navigateur :</strong><br>
                ✅ <code>PWA: Service Worker re-enabled after clean deployment</code><br>
                ✅ <code>PWA: Service Worker registered successfully</code><br>
                ✅ <code>PWA: App ready for offline use</code><br>
                ✅ <code>GET /build/assets/app--hCqS3r6.js 200 (OK)</code>
            </div>
        </div>
    </div>

    <script>
        console.log('🔄 PWA Manifest Updater Ready');
    </script>
</body>
</html>