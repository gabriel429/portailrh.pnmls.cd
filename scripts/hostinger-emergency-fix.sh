#!/bin/bash

# Script d'urgence HOSTINGER - Fix définitif MIME types + service worker
# Usage: ./hostinger-emergency-fix.sh

echo "🚨 FIX URGENCE HOSTINGER - MIME TYPES"
echo "===================================="

# 1. Clean rebuild sans service worker
echo "🧹 Clean rebuild..."
rm -rf public/build
npm run build

# 2. S'assurer que le .htaccess est dans build/
echo "📁 Configuration .htaccess build/..."
cp public/build/.htaccess public/build/.htaccess.backup 2>/dev/null || true

# 3. Vérification assets générés
echo "📊 ASSETS PRÊTS À UPLOADER :"
echo ""
echo "📂 UPLOADEZ CES DOSSIERS/FICHIERS SUR HOSTINGER :"
echo "1. public/build/ → /public_html/build/"
echo "2. public/cleanup-sw.html → /public_html/cleanup-sw.html"
echo ""

# 4. Afficher les assets principaux
echo "🎯 ASSETS CRITIQUES :"
find public/build/assets -name "app-*.css" -o -name "app-*.js" | head -5
echo ""
find public/build/assets -name "*.js" | wc -l | sed 's/^/   Fichiers JS: /'
find public/build/assets -name "*.css" | wc -l | sed 's/^/   Fichiers CSS: /'

echo ""
echo "🔧 INSTRUCTIONS HOSTINGER DÉTAILLÉES :"
echo "-------------------------------------"
echo "1. Connectez-vous au File Manager Hostinger"
echo "2. Allez dans /public_html/"
echo "3. SUPPRIMEZ complètement l'ancien dossier /build/"
echo "4. UPLOADEZ le nouveau dossier public/build/ complet"
echo "5. UPLOADEZ public/cleanup-sw.html"
echo "6. Testez https://deeppink-rhinoceros-934330.hostingersite.com/"
echo ""
echo "⚠️  IMPORTANT: Le .htaccess dans build/ force les bons MIME types"
echo "    Si les erreurs persistent, contactez le support Hostinger"
echo ""
echo "🧹 NETTOYAGE UTILISATEURS :"
echo "https://deeppink-rhinoceros-934330.hostingersite.com/cleanup-sw.html"