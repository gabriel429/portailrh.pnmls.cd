<?php
/**
 * HOSTINGER DIAGNOSTIC & FIX CENTER
 * Page web complète pour diagnostiquer et réparer les problèmes MIME
 * Upload cette page sur Hostinger et utilisez-la directement dans votre navigateur
 */

// Configuration
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = "$protocol://$domain";

// Actions disponibles
$action = $_GET['action'] ?? 'dashboard';

// Fonction pour tester un asset
function testAsset($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (MIME Diagnostic Tool)'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'mime' => $contentType ?: 'unknown',
        'success' => $httpCode === 200,
        'headers' => $response
    ];
}

// Fonction pour servir un asset avec le bon MIME
function serveAsset($file) {
    $assetsDir = $_SERVER['DOCUMENT_ROOT'] . '/build/assets/';
    $filePath = $assetsDir . basename($file);

    // Debug info pour diagnostic
    if (isset($_GET['debug'])) {
        header('Content-Type: text/plain');
        echo "DEBUG INFO:\n";
        echo "Requested file: " . $file . "\n";
        echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
        echo "Assets dir: " . $assetsDir . "\n";
        echo "Full path: " . $filePath . "\n";
        echo "File exists: " . (file_exists($filePath) ? 'YES' : 'NO') . "\n";
        echo "Realpath assets: " . realpath($assetsDir) . "\n";
        echo "Realpath file: " . realpath($filePath) . "\n";

        if (file_exists($assetsDir)) {
            echo "Files in assets dir:\n";
            $files = scandir($assetsDir);
            foreach($files as $f) {
                if ($f !== '.' && $f !== '..') {
                    echo "  - " . $f . "\n";
                }
            }
        } else {
            echo "Assets directory does not exist!\n";
        }
        exit;
    }

    // Sécurité : vérifier que le fichier existe et est dans le bon dossier
    if (!file_exists($filePath)) {
        // Essayer avec le fichier directement dans /build/
        $altPath = $_SERVER['DOCUMENT_ROOT'] . '/build/' . basename($file);
        if (file_exists($altPath)) {
            $filePath = $altPath;
        } else {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo "File not found: " . $filePath . "\nAlternate path tried: " . $altPath;
            return false;
        }
    }

    // Vérification sécurité
    $assetsRealPath = realpath($assetsDir);
    $fileRealPath = realpath($filePath);

    if ($assetsRealPath && $fileRealPath && strpos($fileRealPath, $assetsRealPath) !== 0) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo "Security violation: file outside assets directory";
        return false;
    }

    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'woff2' => 'font/woff2',
        'png' => 'image/png',
        'svg' => 'image/svg+xml'
    ];

    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=31536000');
    header('X-Served-By: PHP-MIME-Fix');
    header('X-Original-File: ' . basename($file));

    readfile($filePath);
    return true;
}

// Traitement des actions
if ($action === 'serve' && !empty($_GET['file'])) {
    if (serveAsset($_GET['file'])) {
        exit;
    }
}

// Si c'est une requête AJAX pour les tests
if ($action === 'test' && !empty($_GET['url'])) {
    header('Content-Type: application/json');
    echo json_encode(testAsset($_GET['url']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🛠️ Hostinger Diagnostic & Fix Center</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f5f7fa;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .card {
            background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0; padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; text-align: center; padding: 30px;
            border-radius: 8px; margin-bottom: 20px;
        }
        .status { padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid; }
        .success { background: #d4edda; color: #155724; border-color: #28a745; }
        .error { background: #f8d7da; color: #721c24; border-color: #dc3545; }
        .warning { background: #fff3cd; color: #856404; border-color: #ffc107; }
        .info { background: #d1ecf1; color: #0c5460; border-color: #17a2b8; }

        .btn {
            display: inline-block; padding: 10px 20px; border: none; border-radius: 4px;
            cursor: pointer; text-decoration: none; font-weight: 500; margin: 5px;
            transition: all 0.2s;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .test-result { font-family: monospace; background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .code-block { background: #2d3748; color: #e2e8f0; padding: 20px; border-radius: 6px; overflow-x: auto; margin: 15px 0; }
        .highlight { background: #ffeaa7; padding: 2px 4px; border-radius: 3px; }

        #live-log { max-height: 400px; overflow-y: auto; background: #1a202c; color: #e2e8f0; padding: 15px; border-radius: 6px; font-family: monospace; }
        .log-entry { margin: 5px 0; padding: 5px; border-radius: 3px; }
        .log-success { background: rgba(72, 187, 120, 0.2); }
        .log-error { background: rgba(245, 101, 101, 0.2); }
        .log-info { background: rgba(66, 153, 225, 0.2); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛠️ Hostinger Diagnostic & Fix Center</h1>
            <p>Diagnostic et réparation en temps réel des problèmes MIME types</p>
            <p><strong>Domaine:</strong> <?= htmlspecialchars($baseUrl) ?></p>
        </div>

        <div class="grid">
            <!-- Panel de diagnostic -->
            <div class="card">
                <h3>🔍 Diagnostic Automatique</h3>
                <p>Teste tous les assets critiques et identifie les problèmes</p>
                <button class="btn btn-primary" onclick="runFullDiagnostic()">
                    <span id="diag-spinner" style="display: none;" class="spinner"></span>
                    Lancer Diagnostic Complet
                </button>
                <div id="diagnostic-results"></div>
            </div>

            <!-- Panel de réparation -->
            <div class="card">
                <h3>🔧 Réparation Automatique</h3>
                <p>Génère les URLs de réparation PHP pour vos assets</p>
                <button class="btn btn-success" onclick="generateFixUrls()">
                    Générer URLs de Réparation
                </button>
                <div id="fix-urls" style="display: none;"></div>
            </div>

            <!-- Panel de test manuel -->
            <div class="card">
                <h3>🎯 Test Manuel</h3>
                <p>Testez une URL spécifique</p>
                <input type="text" id="manual-url" placeholder="URL à tester (ex: /build/assets/app.css)" style="width: 100%; padding: 8px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <button class="btn btn-primary" onclick="testManualUrl()">Tester URL</button>
                <div id="manual-results"></div>
            </div>

            <!-- Panel info serveur -->
            <div class="card">
                <h3>📊 Info Serveur</h3>
                <div class="test-result">
                    <strong>PHP Version:</strong> <?= phpversion() ?><br>
                    <strong>OS:</strong> <?= PHP_OS ?><br>
                    <strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?><br>
                    <strong>cURL disponible:</strong> <?= extension_loaded('curl') ? '✅ Oui' : '❌ Non' ?><br>
                    <strong>Fichier .htaccess:</strong> <?= file_exists($_SERVER['DOCUMENT_ROOT'] . '/build/.htaccess') ? '✅ Présent' : '❌ Manquant' ?><br>
                </div>
            </div>
        </div>

        <!-- Log en temps réel -->
        <div class="card">
            <h3>📝 Log en Temps Réel</h3>
            <div id="live-log"></div>
            <button class="btn btn-danger" onclick="clearLog()">Vider Log</button>
        </div>

        <!-- Solutions d'urgence -->
        <div class="card" id="emergency-section" style="display: none;">
            <h3>🚨 Solutions d'Urgence</h3>
            <div id="emergency-content"></div>
        </div>
    </div>

    <script>
        const baseUrl = '<?= $baseUrl ?>';

        // Logging système
        function log(message, type = 'info') {
            const logDiv = document.getElementById('live-log');
            const time = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.innerHTML = `[${time}] ${message}`;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        // Test d'un asset via AJAX
        async function testAsset(url) {
            try {
                const response = await fetch(`?action=test&url=${encodeURIComponent(url)}`);
                return await response.json();
            } catch (error) {
                return { success: false, error: error.message };
            }
        }

        // Diagnostic complet
        async function runFullDiagnostic() {
            const spinner = document.getElementById('diag-spinner');
            const results = document.getElementById('diagnostic-results');

            spinner.style.display = 'inline-block';
            results.innerHTML = '';
            log('🔍 Début du diagnostic complet...', 'info');

            const assets = [
                '/build/assets/app-CwcRsQhl.css',
                '/build/assets/app-Bi-CZn0p.js',
                '/build/assets/ui-BHVFKrWF.js',
                '/build/assets/runtime-core.esm-bundler-DnwlI2lq.js',
                '/build/manifest.json'
            ];

            let successCount = 0;
            const testResults = [];

            for (const asset of assets) {
                const fullUrl = baseUrl + asset;
                log(`Test: ${asset}`, 'info');

                const result = await testAsset(fullUrl);
                testResults.push({ asset, result });

                if (result.success) {
                    const expectedMime = asset.endsWith('.css') ? 'text/css' :
                                       asset.endsWith('.js') ? 'application/javascript' : 'application/json';

                    if (result.mime.includes(expectedMime) || result.mime.includes('text/css') || result.mime.includes('javascript')) {
                        successCount++;
                        log(`✅ ${asset} - MIME OK: ${result.mime}`, 'success');
                    } else {
                        log(`❌ ${asset} - MIME INCORRECT: ${result.mime}`, 'error');
                    }
                } else {
                    log(`❌ ${asset} - ERREUR: ${result.error || 'Status ' + result.status}`, 'error');
                }
            }

            // Afficher les résultats
            const successRate = Math.round((successCount / assets.length) * 100);
            let statusClass, statusText;

            if (successRate === 100) {
                statusClass = 'success';
                statusText = '🎉 Tous les assets fonctionnent parfaitement !';
                log('🎉 DIAGNOSTIC: Aucun problème détecté', 'success');
            } else if (successRate > 50) {
                statusClass = 'warning';
                statusText = `⚠️ ${successCount}/${assets.length} assets fonctionnent. Problèmes partiels détectés.`;
                log(`⚠️ DIAGNOSTIC: Problèmes partiels (${successRate}% OK)`, 'warning');
                showEmergencySection();
            } else {
                statusClass = 'error';
                statusText = `❌ ${successCount}/${assets.length} assets fonctionnent. Problèmes majeurs !`;
                log(`❌ DIAGNOSTIC: Problèmes majeurs (${successRate}% OK)`, 'error');
                showEmergencySection();
            }

            results.innerHTML = `
                <div class="status ${statusClass}">${statusText}</div>
                <h4>Détails des tests:</h4>
                ${testResults.map(({asset, result}) => `
                    <div class="test-result">
                        <strong>${asset}</strong><br>
                        Status: ${result.status || 'Error'}<br>
                        MIME: ${result.mime}<br>
                        ${result.success ? '✅' : '❌'} ${result.success ? 'OK' : (result.error || 'Failed')}
                    </div>
                `).join('')}
            `;

            spinner.style.display = 'none';
            log('✅ Diagnostic complet terminé', 'info');
        }

        // Générer les URLs de réparation
        function generateFixUrls() {
            const fixDiv = document.getElementById('fix-urls');
            const assets = [
                'app-CwcRsQhl.css',
                'app-Bi-CZn0p.js',
                'ui-BHVFKrWF.js',
                'runtime-core.esm-bundler-DnwlI2lq.js'
            ];

            const fixUrls = assets.map(asset =>
                `${baseUrl}/<?= basename(__FILE__) ?>?action=serve&file=${asset}`
            );

            fixDiv.innerHTML = `
                <h4>🔧 URLs de Réparation Générées:</h4>
                <div class="code-block">
                    ${assets.map((asset, i) => `
                        <div style="margin: 10px 0;">
                            <strong>${asset}:</strong><br>
                            <span class="highlight">${fixUrls[i]}</span>
                            <button onclick="testRepairUrl('${fixUrls[i]}')" style="margin-left: 10px; padding: 5px 10px;">Tester</button>
                            <button onclick="window.open('${fixUrls[i]}&debug=1', '_blank')" style="margin-left: 5px; padding: 5px 10px; background: #6c757d;">Debug</button>
                        </div>
                    `).join('')}
                </div>
                <p><strong>Usage:</strong> Remplacez vos URLs assets par ces URLs dans votre template Laravel.</p>
            `;

            fixDiv.style.display = 'block';
            log('🔧 URLs de réparation générées', 'success');
        }

        // Test d'une URL de réparation
        async function testRepairUrl(url) {
            log(`🔧 Test URL réparation: ${url}`, 'info');
            const result = await testAsset(url);

            if (result.success) {
                log(`✅ URL de réparation fonctionne: ${result.mime}`, 'success');
                alert(`✅ URL de réparation OK!\nMIME Type: ${result.mime}`);
            } else {
                log(`❌ URL de réparation échoue: ${result.error}`, 'error');
                alert(`❌ Échec URL de réparation: ${result.error || result.status}`);
            }
        }

        // Test manuel
        async function testManualUrl() {
            const url = document.getElementById('manual-url').value.trim();
            const results = document.getElementById('manual-results');

            if (!url) {
                alert('Entrez une URL à tester');
                return;
            }

            const fullUrl = url.startsWith('http') ? url : baseUrl + (url.startsWith('/') ? '' : '/') + url;
            log(`🎯 Test manuel: ${fullUrl}`, 'info');

            const result = await testAsset(fullUrl);

            results.innerHTML = `
                <div class="status ${result.success ? 'success' : 'error'}">
                    <strong>Test de:</strong> ${fullUrl}<br>
                    <strong>Status:</strong> ${result.status}<br>
                    <strong>MIME:</strong> ${result.mime}<br>
                    <strong>Résultat:</strong> ${result.success ? '✅ OK' : '❌ ' + (result.error || 'Failed')}
                </div>
            `;
        }

        // Afficher la section d'urgence
        function showEmergencySection() {
            const section = document.getElementById('emergency-section');
            const content = document.getElementById('emergency-content');

            content.innerHTML = `
                <div class="status error">
                    <h4>🚨 Problèmes détectés ! Solutions d'urgence:</h4>
                    <ol>
                        <li><strong>1. Contactez le support Hostinger:</strong><br>
                            "Mes fichiers .htaccess ne forcent pas les bons MIME types. Pouvez-vous activer mod_headers et mod_rewrite ?"</li>
                        <li><strong>2. Utilisez les URLs de réparation PHP:</strong><br>
                            Cliquez sur "Générer URLs de Réparation" ci-dessus</li>
                        <li><strong>3. Upload manuel:</strong><br>
                            Vérifiez que tous vos fichiers sont bien uploadés dans le bon dossier</li>
                    </ol>
                </div>
            `;

            section.style.display = 'block';
        }

        // Vider le log
        function clearLog() {
            document.getElementById('live-log').innerHTML = '';
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            log('🛠️ Hostinger Diagnostic & Fix Center chargé', 'success');
            log(`📍 Base URL: ${baseUrl}`, 'info');

            // Auto-test si paramètre présent
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autotest') === '1') {
                setTimeout(runFullDiagnostic, 1000);
            }
        });
    </script>
</body>
</html>