#!/bin/bash

# Script d'urgence pour nettoyer les service workers sur Hostinger
# Usage: ./emergency-cleanup-hostinger.sh

echo "🚨 NETTOYAGE D'URGENCE SERVICE WORKER HOSTINGER"
echo "==============================================="

# 1. Supprimer TOUS les anciens service workers
echo "🧹 Suppression anciens service workers..."
rm -f public/sw.js
rm -f public/build/sw.js
rm -f public/build/workbox-*.js
rm -f public/workbox-*.js

# 2. Supprimer ancien manifest PWA (conflits)
echo "🗑️  Suppression anciens manifests..."
rm -f public/build/manifest.webmanifest

# 3. Supprimer tout le dossier build pour rebuild complet
echo "🔥 Suppression ancien build..."
rm -rf public/build

# 4. Rebuild sans service worker
echo "🏗️  Rebuild sans service worker..."
npm run build

# 5. Créer page de nettoyage service worker côté client
echo "📝 Création page nettoyage client..."
cat > public/cleanup-sw.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Nettoyage Service Worker - Portail RH PNMLS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        button {
            background: #0077B5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 0;
        }
        button:hover {
            background: #005a8b;
        }
        .log {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Nettoyage Service Worker</h1>
        <p>Cette page nettoie automatiquement les anciens service workers qui causent des problèmes de chargement.</p>

        <div id="status"></div>

        <button onclick="forceCleanup()">🔄 Forcer le nettoyage</button>
        <button onclick="goToApp()">🏠 Aller à l'application</button>

        <div id="log" class="log"></div>
    </div>

    <script>
        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            const className = type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : '';
            logDiv.innerHTML += `<div class="${className}">[${timestamp}] ${message}</div>`;
            logDiv.scrollTop = logDiv.scrollHeight;
            console.log(`[SW Cleanup] ${message}`);
        }

        async function cleanupServiceWorkers() {
            log('Début du nettoyage des service workers...', 'info');

            if ('serviceWorker' in navigator) {
                try {
                    // 1. Désenregistrer tous les service workers
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    log(`Trouvé ${registrations.length} service worker(s) à supprimer`, 'info');

                    for (let registration of registrations) {
                        log(`Suppression SW: ${registration.scope}`, 'info');
                        await registration.unregister();
                        log(`✅ SW supprimé: ${registration.scope}`, 'success');
                    }

                    // 2. Nettoyer tous les caches
                    if ('caches' in window) {
                        const cacheNames = await caches.keys();
                        log(`Trouvé ${cacheNames.length} cache(s) à supprimer`, 'info');

                        for (let cacheName of cacheNames) {
                            log(`Suppression cache: ${cacheName}`, 'info');
                            await caches.delete(cacheName);
                            log(`✅ Cache supprimé: ${cacheName}`, 'success');
                        }
                    }

                    log('🎉 Nettoyage terminé avec succès !', 'success');
                    document.getElementById('status').innerHTML =
                        '<p class="success">✅ Nettoyage terminé ! Les service workers ont été supprimés.</p>';

                } catch (error) {
                    log(`❌ Erreur: ${error.message}`, 'error');
                    document.getElementById('status').innerHTML =
                        `<p class="error">❌ Erreur pendant le nettoyage: ${error.message}</p>`;
                }
            } else {
                log('ℹ️ Service Workers non supportés par ce navigateur', 'warning');
                document.getElementById('status').innerHTML =
                    '<p class="warning">⚠️ Service Workers non supportés par ce navigateur</p>';
            }
        }

        function forceCleanup() {
            document.getElementById('log').innerHTML = '';
            cleanupServiceWorkers();
        }

        function goToApp() {
            log('Redirection vers l\'application...', 'info');
            window.location.href = '/';
        }

        // Auto-cleanup au chargement de la page
        document.addEventListener('DOMContentLoaded', () => {
            log('Page de nettoyage chargée', 'info');
            cleanupServiceWorkers();
        });
    </script>
</body>
</html>
EOF

# 6. Instructions finales
echo ""
echo "✅ NETTOYAGE TERMINÉ !"
echo ""
echo "📋 ÉTAPES SUIVANTES :"
echo "1. Uploadez TOUT le dossier public/build/ sur Hostinger"
echo "2. Uploadez public/cleanup-sw.html"
echo "3. Demandez aux utilisateurs d'aller sur:"
echo "   https://deeppink-rhinoceros-934330.hostingersite.com/cleanup-sw.html"
echo "4. Puis rediriger vers l'app normale"
echo ""
echo "📊 NOUVEAUX ASSETS GÉNÉRÉS :"
find public/build/assets -name "*.js" | head -5
find public/build/assets -name "*.css" | head -3

echo ""
echo "🚨 IMPORTANT : Plus de service worker généré = Chargement direct !"
echo "Les erreurs de cache devraient disparaître."