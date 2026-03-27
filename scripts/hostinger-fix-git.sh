#!/bin/bash
# Script de réinitialisation Git sur Hostinger
# À exécuter APRÈS connexion SSH

set -e  # Arrêt si erreur

echo "═══════════════════════════════════════════"
echo "  🔧 Réinitialisation Git - Hostinger"
echo "═══════════════════════════════════════════"
echo ""

# Variables
DOMAIN_ROOT="$HOME/domains/deeppink-rhinoceros-934330.hostingersite.com"
WEB_ROOT="$DOMAIN_ROOT/public_html"
BACKUP_DIR="$HOME/backup_$(date +%Y%m%d_%H%M%S)"

echo "📁 Domain root: $DOMAIN_ROOT"
echo "📁 Web root: $WEB_ROOT"
echo "📁 Backup: $BACKUP_DIR"
echo ""

# Étape 1: Backup (sécurité)
echo "━━━ Étape 1/5: Backup fichiers actuels ━━━"
mkdir -p "$BACKUP_DIR"
if [ -f "$WEB_ROOT/.env" ]; then
    cp "$WEB_ROOT/.env" "$BACKUP_DIR/.env"
    echo "✅ .env sauvegardé"
fi
if [ -d "$WEB_ROOT/storage" ]; then
    cp -r "$WEB_ROOT/storage" "$BACKUP_DIR/storage"
    echo "✅ storage/ sauvegardé"
fi
echo ""

# Étape 2: Nettoyer ancien git mal placé
echo "━━━ Étape 2/5: Nettoyage ancien git ━━━"
cd "$DOMAIN_ROOT"
if [ -d ".git" ]; then
    echo "⚠️  Git trouvé dans domain root (mauvais endroit)"
    rm -rf .git
    echo "✅ .git supprimé de domain root"
fi
# Supprimer dossiers dupliqués au mauvais endroit
for dir in public app resources routes database; do
    if [ -d "$dir" ]; then
        echo "🗑️  Suppression $dir/ (duplicata)"
        rm -rf "$dir"
    fi
done
echo ""

# Étape 3: Initialiser git dans public_html
echo "━━━ Étape 3/5: Init git dans public_html ━━━"
cd "$WEB_ROOT"

if [ -d ".git" ]; then
    echo "⚠️  Git existe déjà dans public_html"
    read -p "Réinitialiser quand même? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf .git
    else
        echo "❌ Annulé par l'utilisateur"
        exit 1
    fi
fi

git init
echo "✅ Git initialisé"

git remote add origin https://github.com/gabriel429/portailrh.pnmls.cd.git
echo "✅ Remote GitHub ajouté"

git fetch origin main
echo "✅ Fetch origin/main"

git checkout -f main
echo "✅ Checkout main (force)"
echo ""

# Étape 4: Vérifier fichiers critiques
echo "━━━ Étape 4/5: Vérification fichiers ━━━"

# Manifest
if [ -f "public/build/manifest.json" ]; then
    echo "✅ public/build/manifest.json"
else
    echo "❌ ERREUR: manifest.json manquant!"
    exit 1
fi

# Assets
ASSET_COUNT=$(ls -1 public/build/assets/ 2>/dev/null | wc -l)
echo "✅ Assets: $ASSET_COUNT fichiers"

# .env
if [ -f ".env" ]; then
    echo "✅ .env existe"
else
    echo "⚠️  .env manquant - copie depuis backup"
    if [ -f "$BACKUP_DIR/.env" ]; then
        cp "$BACKUP_DIR/.env" .env
        echo "✅ .env restauré depuis backup"
    else
        echo "⚠️  Pas de backup .env - copie depuis .env.example"
        cp .env.example .env
        echo "❗ IMPORTANT: Éditer .env avec vos credentials!"
    fi
fi
echo ""

# Étape 5: Clear cache Laravel
echo "━━━ Étape 5/5: Optimisation Laravel ━━━"

# Clear cache
php artisan config:clear 2>/dev/null || echo "⚠️  config:clear failed"
php artisan route:clear 2>/dev/null || echo "⚠️  route:clear failed"
php artisan view:clear 2>/dev/null || echo "⚠️  view:clear failed"
php artisan cache:clear 2>/dev/null || echo "⚠️  cache:clear failed"

# Rebuild cache
php artisan config:cache
echo "✅ Config cached"

# Permissions
chmod -R 755 storage bootstrap/cache
echo "✅ Permissions fixées"
echo ""

# Résumé final
echo "═══════════════════════════════════════════"
echo "  ✅ DÉPLOIEMENT TERMINÉ"
echo "═══════════════════════════════════════════"
echo ""
echo "📊 Résumé:"
echo "  - Git: $(git log --oneline -1)"
echo "  - Assets: $ASSET_COUNT fichiers"
echo "  - Backup: $BACKUP_DIR"
echo ""
echo "🌐 Testez maintenant:"
echo "  https://deeppink-rhinoceros-934330.hostingersite.com/"
echo ""
echo "⚠️  Si .env a été recréé, configurez:"
echo "  1. APP_KEY (php artisan key:generate)"
echo "  2. DB_* (credentials MySQL)"
echo "  3. APP_ENV=production, APP_DEBUG=false"
echo ""
