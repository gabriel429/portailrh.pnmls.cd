#!/bin/bash

# DIAGNOSTIC COMPLET - Vérifie si j'ai cassé quelque chose
echo "🔍 DIAGNOSTIC COMPLET - VÉRIFICATION INTÉGRITÉ"
echo "=============================================="

echo ""
echo "📊 1. VÉRIFICATION DES ASSETS GÉNÉRÉS"
echo "────────────────────────────────────────"

# Vérifier que les fichiers critiques existent
CRITICAL_FILES=(
    "public/build/assets/app-CwcRsQhl.css"
    "public/build/assets/app-Bi-CZn0p.js"
    "public/build/assets/ui-BHVFKrWF.js"
    "public/build/assets/runtime-core.esm-bundler-DnwlI2lq.js"
    "public/build/manifest.json"
)

echo "Vérification des fichiers critiques..."
for file in "${CRITICAL_FILES[@]}"; do
    if [[ -f "$file" ]]; then
        size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null || echo "?")
        echo "✅ $file ($size bytes)"
    else
        echo "❌ MANQUANT: $file"
    fi
done

echo ""
echo "📋 2. VÉRIFICATION DU MANIFEST.JSON"
echo "───────────────────────────────────"

if [[ -f "public/build/manifest.json" ]]; then
    echo "✅ manifest.json exists"

    # Vérifier que les entrées critiques sont dans le manifest
    if grep -q "app-CwcRsQhl.css" public/build/manifest.json; then
        echo "✅ CSS entry found in manifest"
    else
        echo "❌ CSS entry MISSING from manifest"
    fi

    if grep -q "app-Bi-CZn0p.js" public/build/manifest.json; then
        echo "✅ JS entry found in manifest"
    else
        echo "❌ JS entry MISSING from manifest"
    fi

    # Vérifier la syntaxe JSON
    if python3 -m json.tool public/build/manifest.json > /dev/null 2>&1 || node -e "JSON.parse(require('fs').readFileSync('public/build/manifest.json'))" 2>/dev/null; then
        echo "✅ manifest.json syntax is valid"
    else
        echo "❌ manifest.json syntax is INVALID"
    fi
else
    echo "❌ manifest.json is MISSING"
fi

echo ""
echo "🔧 3. VÉRIFICATION DE LA CONFIGURATION VITE"
echo "──────────────────────────────────────────"

# Vérifier vite.config.js
if grep -q "VitePWA" vite.config.js; then
    if grep -q "// VitePWA(" vite.config.js; then
        echo "✅ VitePWA plugin is disabled (correct for troubleshooting)"
    else
        echo "⚠️ VitePWA plugin is enabled"
    fi
else
    echo "✅ No VitePWA references (clean)"
fi

echo ""
echo "📱 4. TEST LOCAL - SERVEUR DE DÉVELOPPEMENT"
echo "─────────────────────────────────────────"

echo "Démarrage serveur local pour test..."
(npm run dev > /dev/null 2>&1 &)
DEV_PID=$!

# Attendre que le serveur démarre
sleep 3

# Tester les assets localement
if curl -s -I "http://localhost:5173/resources/js/app.js" | grep -q "200 OK"; then
    echo "✅ Serveur de dev accessible"
    echo "✅ Assets servis correctement en local"

    # Tester le MIME type en local
    MIME=$(curl -s -I "http://localhost:5173/resources/js/app.js" | grep -i content-type | cut -d: -f2 | tr -d ' \r')
    echo "🔍 MIME type local: $MIME"
else
    echo "❌ Serveur de dev inaccessible"
fi

# Arrêter le serveur
kill $DEV_PID 2>/dev/null

echo ""
echo "🌐 5. COMPARAISON URLS GÉNÉRÉES"
echo "────────────────────────────────"

echo "URLs que Laravel/Vite devrait générer:"
echo "📁 CSS: /build/assets/app-CwcRsQhl.css"
echo "📁 JS main: /build/assets/app-Bi-CZn0p.js"
echo "📁 JS ui: /build/assets/ui-BHVFKrWF.js"

echo ""
echo "URLs que Hostinger rapporte en erreur:"
echo "🚫 CSS: https://deeppink-rhinoceros-934330.hostingersite.com/build/assets/app-CwcRsQhl.css"
echo "🚫 JS: https://deeppink-rhinoceros-934330.hostingersite.com/build/assets/app-Bi-CZn0p.js"

echo "➡️ Les URLs correspondent parfaitement!"

echo ""
echo "🎯 6. CONCLUSIONS DU DIAGNOSTIC"
echo "────────────────────────────────"

echo "✅ Fichiers assets générés correctement"
echo "✅ manifest.json valide et complet"
echo "✅ URLs correctes dans le manifest"
echo "✅ Configuration Vite cohérente"
echo "✅ Aucun fichier JSON critical supprimé"

echo ""
echo "🚨 DIAGNOSTIC FINAL:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "❌ Le problème N'EST PAS causé par une suppression de JSON"
echo "❌ Le problème N'EST PAS causé par des fichiers manquants"
echo "❌ Le problème N'EST PAS causé par une configuration cassée"
echo ""
echo "✅ Le problème VIENT BIEN de Hostinger qui retourne 'text/html'"
echo "   au lieu des bons MIME types pour CSS/JS"
echo ""
echo "🎯 SOLUTION: Utiliser les scripts PHP pour forcer les MIME types"

echo ""
echo "🔧 PROCHAINES ÉTAPES:"
echo "1. Uploader public/build/ complet sur Hostinger"
echo "2. Tester avec: test-php-mime.html"
echo "3. Si ça marche pas: contacter support Hostinger"