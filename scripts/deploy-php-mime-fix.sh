#!/bin/bash

# ULTIMATE HOSTINGER MIME FIX - PHP Solution
# This script deploys PHP-based MIME type fixes for Hostinger

echo "🛠️ SOLUTION PHP ULTIME - MIME TYPES HOSTINGER"
echo "=============================================="

# 1. Rebuild application (clean)
echo "🧹 Rebuild propre de l'application..."
rm -rf public/build/*
npm run build

# 2. Copy PHP fix files to build directory
echo "📁 Installation des fixes PHP..."
cp public/build/serve-asset.php public/build/serve-asset.php.backup 2>/dev/null || true
cp public/build/emergency-css.php public/build/emergency-css.php.backup 2>/dev/null || true
cp public/build/emergency-js.php public/build/emergency-js.php.backup 2>/dev/null || true

# 3. Verify PHP files are in place
echo "✅ Vérification des fichiers PHP..."
if [[ -f "public/build/serve-asset.php" ]]; then
    echo "✅ serve-asset.php ready"
else
    echo "❌ serve-asset.php missing!"
    exit 1
fi

if [[ -f "public/build/emergency-css.php" ]]; then
    echo "✅ emergency-css.php ready"
else
    echo "❌ emergency-css.php missing!"
fi

if [[ -f "public/build/emergency-js.php" ]]; then
    echo "✅ emergency-js.php ready"
else
    echo "❌ emergency-js.php missing!"
fi

# 4. Show file sizes and counts
echo ""
echo "📊 ASSETS GÉNÉRÉS :"
echo "CSS files: $(find public/build/assets -name "*.css" | wc -l)"
echo "JS files: $(find public/build/assets -name "*.js" | wc -l)"
echo "Total assets: $(find public/build/assets -type f | wc -l)"

# 5. List critical files to upload
echo ""
echo "📤 FICHIERS CRITIQUES À UPLOADER SUR HOSTINGER :"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. public/build/ (DOSSIER COMPLET)"
echo "   ├── assets/ (tous les fichiers CSS/JS)"
echo "   ├── manifest.json"
echo "   ├── .htaccess"
echo "   ├── serve-asset.php ⭐"
echo "   ├── emergency-css.php ⭐"
echo "   └── emergency-js.php ⭐"
echo ""
echo "2. public/cleanup-sw.html"
echo "3. public/diagnostic-mime.html"
echo "4. public/test-php-mime.html"

echo ""
echo "🎯 TESTS À FAIRE APRÈS UPLOAD :"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. https://deeppink-rhinoceros-934330.hostingersite.com/test-php-mime.html"
echo "   → Clic 'Tester serve-asset.php'"
echo ""
echo "2. Si ça marche : https://deeppink-rhinoceros-934330.hostingersite.com/"
echo "   → L'app devrait se charger sans erreur MIME"
echo ""
echo "3. Si ça ne marche pas : Utiliser les URLs d'urgence générées"

echo ""
echo "🔧 SOLUTIONS DISPONIBLES (par ordre de préférence) :"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. .htaccess normal (ForceType) - peut marcher"
echo "2. serve-asset.php - solution universelle PHP"
echo "3. emergency-*.php - solutions spécifiques"
echo "4. Intervention support Hostinger si tout échoue"

echo ""
echo "✅ SOLUTION PHP PRÊTE !"
echo "Les fichiers PHP vont forcer les bons MIME types même si .htaccess échoue."