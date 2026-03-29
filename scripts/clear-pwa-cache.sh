#!/bin/bash

# Script de nettoyage cache PWA pour Hostinger
# Usage: chmod +x clear-pwa-cache.sh && ./clear-pwa-cache.sh

echo "🧹 Nettoyage cache PWA E-PNMLS..."

# 1. Supprimer anciens builds
echo "📦 Suppression anciens builds..."
rm -rf public/build/assets/*
rm -f public/build/manifest.json
rm -f public/build/sw.js
rm -f public/build/workbox-*.js

# 2. Supprimer caches Laravel
echo "🔧 Nettoyage cache Laravel..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Rebuild PWA
echo "🏗️  Rebuild PWA..."
npm run build

# 4. Vérifier que les nouveaux assets existent
echo "✅ Vérification des assets..."
if [ -f "public/build/sw.js" ]; then
    echo "✅ Service Worker généré"
else
    echo "❌ Erreur: Service Worker non généré"
fi

if [ -f "public/build/manifest.json" ]; then
    echo "✅ Manifest généré"
else
    echo "❌ Erreur: Manifest non généré"
fi

# 5. Afficher les nouveaux assets
echo "📄 Nouveaux assets générés:"
ls -la public/build/assets/ | head -10

echo ""
echo "🎉 Nettoyage terminé !"
echo "💡 Demander aux utilisateurs de:"
echo "   1. Vider leur cache navigateur (Ctrl+Shift+Delete)"
echo "   2. Ou forcer reload (Ctrl+F5)"