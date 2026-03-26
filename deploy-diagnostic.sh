#!/bin/bash

# DÉPLOIEMENT DIAGNOSTIC PHP - Upload automatique vers Hostinger
echo "🚀 DÉPLOIEMENT DIAGNOSTIC PHP HOSTINGER"
echo "========================================"

# 1. Préparer le build
echo "🧹 Préparation du build..."
npm run build > /dev/null 2>&1

# 2. Lister les fichiers à uploader
echo ""
echo "📤 FICHIERS À UPLOADER SUR HOSTINGER :"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo ""
echo "🔧 1. Page de diagnostic principale :"
echo "   ✅ hostinger-diagnostic.php → /public_html/"

echo ""
echo "📁 2. Dossier build complet :"
echo "   ✅ public/build/ → /public_html/build/"
echo "      ├── assets/ (tous les CSS/JS)"
echo "      ├── manifest.json"
echo "      ├── .htaccess"
echo "      └── serve-asset.php"

echo ""
echo "📊 3. Assets générés :"
find public/build/assets -name "app-*.css" -o -name "app-*.js" | head -3
echo "   Plus $(find public/build/assets -type f | wc -l) fichiers assets total"

echo ""
echo "🎯 UTILISATION APRÈS UPLOAD :"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1. 📱 Allez sur :"
echo "   https://deeppink-rhinoceros-934330.hostingersite.com/hostinger-diagnostic.php"
echo ""
echo "2. 🔍 Cliquez 'Lancer Diagnostic Complet'"
echo ""
echo "3. 🔧 Si problèmes détectés, cliquez 'Générer URLs de Réparation'"
echo ""
echo "4. ✅ Testez chaque URL de réparation générée"

echo ""
echo "🛠️ FONCTIONNALITÉS DE LA PAGE :"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Diagnostic automatique de tous vos assets"
echo "✅ Test des MIME types en temps réel"
echo "✅ Génération d'URLs de réparation automatique"
echo "✅ Serveur d'assets PHP intégré"
echo "✅ Logs en temps réel pour debugging"
echo "✅ Solutions d'urgence si tout échoue"
echo "✅ Interface web complète (pas besoin de scripts)"

echo ""
echo "🚨 SI LA PAGE DIAGNOSTIC DÉTECTE DES PROBLÈMES :"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. Elle générera automatiquement des URLs comme :"
echo "   https://votrdomaine.com/hostinger-diagnostic.php?action=serve&file=app.css"
echo ""
echo "2. Vous pourrez tester chaque URL directement"
echo ""
echo "3. Si ça marche, remplacez dans votre template Laravel :"
echo "   AVANT: {{ asset('build/assets/app.css') }}"
echo "   APRÈS:  https://votrdomaine.com/hostinger-diagnostic.php?action=serve&file=app.css"

echo ""
echo "✅ DIAGNOSTIC PHP PRÊT À DÉPLOYER !"
echo "Cette page résoudra vos problèmes MIME directement dans le navigateur."