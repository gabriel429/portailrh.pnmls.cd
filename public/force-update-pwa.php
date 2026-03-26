<?php
/**
 * HOSTINGER PWA FORCE UPDATER
 * Script PHP pour forcer la mise à jour des assets PWA automatiquement
 * Upload ce fichier sur Hostinger et exécutez-le dans votre navigateur
 */

// Configuration
$FORCE_UPDATE = true;
$DELETE_OLD_FILES = true;
$DEBUG_MODE = isset($_GET['debug']);

// Structure des fichiers PWA
$PWA_FILES = [
    [
        'source' => '/build/assets/app--hCqS3r6.js',
        'target' => '/public/build/assets/app--hCqS3r6.js',
        'type' => 'js',
        'critical' => true
    ],
    [
        'source' => '/build/sw.js',
        'target' => '/public/build/sw.js',
        'type' => 'js',
        'critical' => true
    ],
    [
        'source' => '/build/workbox-8c29f6e4.js',
        'target' => '/public/build/workbox-8c29f6e4.js',
        'type' => 'js',
        'critical' => true
    ],
    [
        'source' => '/build/manifest.webmanifest',
        'target' => '/public/build/manifest.webmanifest',
        'type' => 'json',
        'critical' => true
    ],
    [
        'source' => '/build/manifest.json',
        'target' => '/public/build/manifest.json',
        'type' => 'json',
        'critical' => true
    ]
];

// Anciens fichiers à supprimer
$OLD_FILES = [
    '/public/build/assets/app-Bi-CZn0p.js',
    '/public/build/assets/app-CwcRsQhl.css.old',
    '/public/build/sw-old.js',
    '/public/build/workbox-legacy.js'
];

$results = [];
$errors = [];

/**
 * Logger avec horodatage
 */
function logMessage($message, $type = 'info') {
    global $results;
    $timestamp = date('H:i:s');
    $results[] = [
        'time' => $timestamp,
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Créer un répertoire s'il n'existe pas
 */
function ensureDirectory($path) {
    $dir = dirname($_SERVER['DOCUMENT_ROOT'] . $path);
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            logMessage("✅ Répertoire créé: $dir", 'success');
            return true;
        } else {
            logMessage("❌ Échec création répertoire: $dir", 'error');
            return false;
        }
    }
    return true;
}

/**
 * Copier un fichier avec vérification
 */
function copyPWAFile($source, $target, $type, $critical = false) {
    global $DEBUG_MODE;

    $sourcePath = $_SERVER['DOCUMENT_ROOT'] . $source;
    $targetPath = $_SERVER['DOCUMENT_ROOT'] . $target;

    if ($DEBUG_MODE) {
        logMessage("🔍 SOURCE: $sourcePath", 'debug');
        logMessage("🔍 TARGET: $targetPath", 'debug');
    }

    // Vérifier que le fichier source existe
    if (!file_exists($sourcePath)) {
        logMessage("❌ CRITIQUE: Fichier source manquant: $source", 'error');
        return false;
    }

    // Créer le répertoire cible si nécessaire
    if (!ensureDirectory($target)) {
        return false;
    }

    // Sauvegarder l'ancien fichier si il existe
    if (file_exists($targetPath)) {
        $backupPath = $targetPath . '.backup-' . date('YmdHis');
        if (copy($targetPath, $backupPath)) {
            logMessage("💾 Sauvegarde créée: " . basename($backupPath), 'info');
        }
    }

    // Copier le nouveau fichier
    if (copy($sourcePath, $targetPath)) {
        $fileSize = number_format(filesize($targetPath) / 1024, 1);
        logMessage("✅ COPIÉ: " . basename($target) . " ({$fileSize} KB)", 'success');

        // Vérifier l'intégrité pour les fichiers critiques
        if ($critical) {
            $sourceHash = md5_file($sourcePath);
            $targetHash = md5_file($targetPath);
            if ($sourceHash === $targetHash) {
                logMessage("🔒 INTÉGRITÉ OK: " . basename($target), 'success');
            } else {
                logMessage("⚠️ ATTENTION: Checksums différents pour " . basename($target), 'warning');
            }
        }

        return true;
    } else {
        logMessage("❌ ÉCHEC COPIE: $source → $target", 'error');
        return false;
    }
}

/**
 * Supprimer les anciens fichiers
 */
function cleanupOldFiles($files) {
    $cleaned = 0;
    foreach ($files as $file) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $file;
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                logMessage("🗑️ SUPPRIMÉ: " . basename($file), 'success');
                $cleaned++;
            } else {
                logMessage("❌ Échec suppression: " . basename($file), 'error');
            }
        }
    }
    return $cleaned;
}

/**
 * Tester l'accessibilité des fichiers PWA
 */
function testPWAFiles() {
    global $PWA_FILES;
    $success = 0;
    $total = count($PWA_FILES);

    foreach ($PWA_FILES as $file) {
        $targetPath = $_SERVER['DOCUMENT_ROOT'] . $file['target'];
        if (file_exists($targetPath) && is_readable($targetPath)) {
            $success++;
        }
    }

    logMessage("🧪 TEST: $success/$total fichiers PWA accessibles", $success === $total ? 'success' : 'warning');
    return $success === $total;
}

// EXÉCUTION PRINCIPALE
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['force'])) {
    logMessage("🚀 DÉBUT MISE À JOUR PWA FORCÉE", 'info');
    logMessage("📍 Document Root: " . $_SERVER['DOCUMENT_ROOT'], 'info');

    $successCount = 0;
    $totalFiles = count($PWA_FILES);

    // 1. Copier les nouveaux fichiers PWA
    logMessage("📁 Phase 1: Copie des nouveaux fichiers PWA", 'info');
    foreach ($PWA_FILES as $file) {
        if (copyPWAFile($file['source'], $file['target'], $file['type'], $file['critical'])) {
            $successCount++;
        }
    }

    // 2. Nettoyer les anciens fichiers
    if ($DELETE_OLD_FILES) {
        logMessage("🧹 Phase 2: Nettoyage des anciens fichiers", 'info');
        $cleanedCount = cleanupOldFiles($OLD_FILES);
        logMessage("✅ Nettoyage terminé: $cleanedCount fichiers supprimés", 'success');
    }

    // 3. Test final
    logMessage("🧪 Phase 3: Test de fonctionnement", 'info');
    $allWorking = testPWAFiles();

    // 4. Résultat final
    $successRate = round(($successCount / $totalFiles) * 100);
    if ($successCount === $totalFiles && $allWorking) {
        logMessage("🎉 MISE À JOUR PWA: 100% RÉUSSIE", 'success');
        logMessage("🔄 Action requise: Rechargez votre site web (Ctrl+F5)", 'info');
    } else {
        logMessage("⚠️ MISE À JOUR PARTIELLE: $successRate% ($successCount/$totalFiles)", 'warning');
    }

    logMessage("✅ Process terminé à " . date('H:i:s'), 'info');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🚀 PWA Force Updater - Hostinger</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 800px; margin: 0 auto;
            background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white; padding: 30px; text-align: center;
        }
        .content { padding: 30px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid; }
        .success { background: #d4edda; color: #155724; border-color: #28a745; }
        .error { background: #f8d7da; color: #721c24; border-color: #dc3545; }
        .warning { background: #fff3cd; color: #856404; border-color: #ffc107; }
        .info { background: #d1ecf1; color: #0c5460; border-color: #17a2b8; }
        .debug { background: #e2e3e5; color: #495057; border-color: #6c757d; }

        .btn {
            display: inline-block; padding: 15px 30px; border: none; border-radius: 8px;
            font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none;
            transition: all 0.2s; margin: 10px;
        }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); }
        .btn-info { background: #3498db; color: white; }
        .btn-info:hover { background: #2980b9; transform: translateY(-2px); }

        .log-container {
            background: #1a202c; color: #e2e8f0; padding: 20px; border-radius: 8px;
            max-height: 400px; overflow-y: auto; margin: 20px 0;
        }
        .log-entry {
            margin: 8px 0; padding: 8px 12px; border-radius: 4px;
            display: flex; align-items: center; gap: 10px;
        }
        .log-time { color: #a0aec0; font-size: 0.85em; min-width: 60px; }
        .log-success { background: rgba(72, 187, 120, 0.2); }
        .log-error { background: rgba(245, 101, 101, 0.2); }
        .log-warning { background: rgba(246, 173, 85, 0.2); }
        .log-info { background: rgba(66, 153, 225, 0.2); }
        .log-debug { background: rgba(113, 128, 150, 0.2); }

        .file-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px; margin: 20px 0;
        }
        .file-card {
            background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;
        }
        .file-path { font-family: monospace; font-size: 0.9em; color: #6c757d; }

        .warning-box {
            background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;
            padding: 20px; margin: 20px 0; color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 PWA Force Updater</h1>
            <p>Mise à jour automatique des assets PWA sur Hostinger</p>
            <p><strong>Site:</strong> <?= $_SERVER['HTTP_HOST'] ?></p>
        </div>

        <div class="content">
            <?php if (empty($results)): ?>
                <!-- INTERFACE INITIALE -->
                <div class="warning-box">
                    <h3>⚠️ ATTENTION: Opération de force</h3>
                    <p><strong>Cette action va :</strong></p>
                    <ul>
                        <li>Remplacer tous les assets PWA par les nouvelles versions</li>
                        <li>Supprimer les anciens fichiers JavaScript (app-Bi-CZn0p.js)</li>
                        <li>Activer le Service Worker PWA complet</li>
                        <li>Créer des sauvegardes automatiques</li>
                    </ul>
                </div>

                <h3>📁 Fichiers qui seront mis à jour:</h3>
                <div class="file-grid">
                    <?php foreach ($PWA_FILES as $file): ?>
                        <div class="file-card">
                            <h4><?= basename($file['target']) ?></h4>
                            <div class="file-path">
                                <strong>Source:</strong> <?= $file['source'] ?><br>
                                <strong>Destination:</strong> <?= $file['target'] ?><br>
                                <strong>Type:</strong> <?= strtoupper($file['type']) ?>
                                <?= $file['critical'] ? ' <span style="color:#e74c3c;">[CRITIQUE]</span>' : '' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <form method="POST" style="display: inline;">
                        <button type="submit" class="btn btn-danger">
                            🚀 FORCER LA MISE À JOUR PWA
                        </button>
                    </form>
                    <a href="?debug=1&force=1" class="btn btn-info">
                        🔍 Mode Debug
                    </a>
                </div>

            <?php else: ?>
                <!-- RÉSULTATS DE L'EXÉCUTION -->
                <h3>📋 Journal d'exécution:</h3>
                <div class="log-container">
                    <?php foreach ($results as $log): ?>
                        <div class="log-entry log-<?= $log['type'] ?>">
                            <span class="log-time"><?= $log['time'] ?></span>
                            <span><?= htmlspecialchars($log['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="?" class="btn btn-info">🔄 Nouvelle mise à jour</a>
                    <a href="/" class="btn btn-success" style="background:#28a745;">🌐 Tester le site</a>
                    <a href="hostinger-diagnostic.php" class="btn btn-info">🔍 Diagnostic PWA</a>
                </div>
            <?php endif; ?>

            <!-- INFO SYSTÈME -->
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                <h4>🖥️ Informations Système:</h4>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 0.9em;">
                    <strong>PHP Version:</strong> <?= phpversion() ?><br>
                    <strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?><br>
                    <strong>Timestamp:</strong> <?= date('Y-m-d H:i:s T') ?><br>
                    <strong>User Agent:</strong> <?= $_SERVER['HTTP_USER_AGENT'] ?? 'N/A' ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh après 30 secondes si on est sur les résultats
        <?php if (!empty($results)): ?>
        setTimeout(() => {
            document.body.style.opacity = '0.7';
            const notice = document.createElement('div');
            notice.innerHTML = '🔄 Redirection automatique dans 5 secondes...';
            notice.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:15px;border-radius:8px;z-index:1000;';
            document.body.appendChild(notice);
            setTimeout(() => window.location.href = '/', 5000);
        }, 30000);
        <?php endif; ?>

        console.log('🚀 PWA Force Updater Ready');
        console.log('📍 Document Root: <?= $_SERVER['DOCUMENT_ROOT'] ?>');
    </script>
</body>
</html>