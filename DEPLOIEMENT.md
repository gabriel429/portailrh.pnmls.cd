# 🚀 Guide de Déploiement Rapide - Hostinger

## ⚠️ IMPORTANT: Configuration Initiale (Une seule fois)

Sur le serveur Hostinger, configure un **alias git personnalisé** pour automatiser le déploiement:

```bash
# Se connecter au serveur
ssh u605154961@nl-srv-web712.hstgr.io
cd domains/deeppink-rhinoceros-934330.hostingersite.com/public_html

# Créer l'alias git deploy
git config alias.deploy '!git pull origin main && bash deploy.sh'

# Rendre deploy.sh exécutable
chmod +x deploy.sh
```

## ✅ Déploiement (Après configuration)

### Commande Simple (Recommandée)
```bash
ssh u605154961@nl-srv-web712.hstgr.io
cd domains/deeppink-rhinoceros-934330.hostingersite.com/public_html
git deploy
```

Cette commande fait AUTOMATIQUEMENT:
1. ✅ `git pull origin main`
2. ✅ Création symlinks (`build`, `images`)
3. ✅ Clear caches Laravel
4. ✅ Fix permissions

### Commande Manuelle (Si alias non configuré)
```bash
ssh u605154961@nl-srv-web712.hstgr.io
cd domains/deeppink-rhinoceros-934330.hostingersite.com/public_html
git pull origin main
bash deploy.sh
```

## 🔧 Dépannage

### Erreur 404 sur assets (/build/assets/app-xxx.js)
```bash
# Vérifier que le symlink existe
ls -la | grep build
# Devrait afficher: lrwxrwxrwx ... build -> public/build

# Si absent, recréer manuellement
rm -f build && ln -sf public/build build
```

### Cache Laravel bloque
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Permissions incorrectes
```bash
chmod -R 755 public/build
chmod -R 755 storage bootstrap/cache
```

## 📝 Checklist Déploiement

- [ ] Pull code: `git pull origin main` ✅
- [ ] Execute deploy: `bash deploy.sh` ✅
- [ ] Vérifier symlinks: `ls -la | grep build` ✅
- [ ] Tester l'app: https://deeppink-rhinoceros-934330.hostingersite.com ✅
- [ ] Vérifier console (F12): 0 erreur 404 ✅

## 🎯 Workflow Complet

### Sur ta machine locale:
```bash
# 1. Build assets
npm run build

# 2. Commit changements
git add -A
git commit -m "Description des changements"
git push origin main
```

### Sur le serveur Hostinger:
```bash
# 3. Déployer (une seule commande!)
git deploy
```

## 🚨 Pourquoi les erreurs 404 se répètent?

**Problème**: Hostinger a un document root `public_html/` au lieu de `public_html/public/`

**Solution**: Le symlink `build -> public/build` permet d'accéder à `/build/assets/` depuis la racine web.

**Pourquoi il disparaît**: Git ne versionne PAS les symlinks sur certains systèmes. Donc à chaque `git pull`, le symlink peut disparaître.

**Solution permanente**: Le script `deploy.sh` recrée TOUJOURS le symlink après chaque pull.

## 💡 Astuce Pro

Ajoute ceci à ton `~/.bashrc` sur le serveur pour un alias global:

```bash
alias deploy='cd /home/u605154961/domains/deeppink-rhinoceros-934330.hostingersite.com/public_html && git deploy'
```

Ensuite tu pourras juste taper `deploy` depuis n'importe où! 🎉
