#!/bin/bash

# Script de déploiement PWA pour Hostinger
# Usage: chmod +x deploy-hostinger.sh && ./deploy-hostinger.sh

set -e  # Exit on any error

echo "🚀 Déploiement PWA E-PNMLS sur Hostinger"
echo "=================================================="

# Configuration
DOMAIN="deeppink-rhinoceros-934330.hostingersite.com"
BUILD_DIR="public/build"

# 1. Vérification pré-déploiement
echo "🔍 Vérification environnement..."

if [ ! -f "package.json" ]; then
    echo "❌ Erreur: package.json non trouvé"
    exit 1
fi

if [ ! -f "vite.config.js" ]; then
    echo "❌ Erreur: vite.config.js non trouvé"
    exit 1
fi

# 2. Installation des dépendances
echo "📦 Installation des dépendances..."
npm ci --production=false

# 3. Nettoyage des anciens builds
echo "🧹 Nettoyage anciens builds..."
rm -rf "$BUILD_DIR"
rm -rf "public/manifest.json"
rm -rf "public/sw.js"
rm -rf "public/workbox-*.js"

# 4. Build PWA production
echo "🏗️  Build PWA production..."
npm run build

# 5. Vérification des assets générés
echo "✅ Vérification assets..."

if [ ! -d "$BUILD_DIR" ]; then
    echo "❌ Erreur: Répertoire build non créé"
    exit 1
fi

if [ ! -f "$BUILD_DIR/sw.js" ]; then
    echo "❌ Erreur: Service Worker non généré"
    exit 1
fi

# Compter les assets JS/CSS
JS_COUNT=$(find "$BUILD_DIR/assets" -name "*.js" | wc -l)
CSS_COUNT=$(find "$BUILD_DIR/assets" -name "*.css" | wc -l)

echo "📊 Assets générés:"
echo "   - JavaScript: $JS_COUNT fichiers"
echo "   - CSS: $CSS_COUNT fichiers"
echo "   - Service Worker: ✅"

# 6. Création du fichier .htaccess pour assets
echo "⚙️  Configuration serveur..."

cat > "$BUILD_DIR/.htaccess" << EOF
# Configuration MIME types pour assets PWA
AddType application/javascript .js
AddType text/css .css
AddType application/json .json
AddType image/png .png
AddType image/svg+xml .svg
AddType font/woff2 .woff2

# Cache pour assets buildés (avec hash)
<FilesMatch "\.(js|css|png|svg|woff2)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
</FilesMatch>

# Pas de cache pour SW et manifest
<FilesMatch "(sw\.js|manifest\.json|workbox-.+\.js)$">
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires "0"
</FilesMatch>

# Sécurité
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>
EOF

# 7. Vérification finale des fichiers cruciaux
echo "🔍 Vérification finale..."

CRITICAL_FILES=(
    "public/manifest.json"
    "public/pwa-192x192.png"
    "public/pwa-512x512.png"
    "public/apple-touch-icon.png"
    "$BUILD_DIR/sw.js"
    "$BUILD_DIR/manifest.json"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ MANQUANT: $file"
    fi
done

# 8. Information de déploiement
echo ""
echo "🎉 Build PWA terminé avec succès !"
echo ""
echo "📋 Instructions pour Hostinger:"
echo "1. Uploader TOUT le répertoire public/build/ via cPanel File Manager"
echo "2. Vérifier que les permissions sont 644 pour les fichiers"
echo "3. Tester sur: https://$DOMAIN"
echo ""
echo "🔧 Si problème MIME type:"
echo "1. Vérifier que .htaccess est uploadé dans public/build/"
echo "2. Contacter support Hostinger si AddType ne fonctionne pas"
echo ""
echo "📱 Test PWA:"
echo "1. Ouvrir: https://$DOMAIN"
echo "2. F12 > Application > Service Workers (doit apparaître)"
echo "3. Test installation: Icône + dans barre d'adresse"

# 9. Afficher structure finale
echo ""
echo "📁 Structure déployée:"
tree "$BUILD_DIR" -I "*.map" 2>/dev/null || find "$BUILD_DIR" -type f | head -20

echo ""
echo "✨ Déploiement prêt pour Hostinger !"