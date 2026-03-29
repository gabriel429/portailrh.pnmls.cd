   # Guide de Déploiement - Hostinger

## 🏗️ Structure Hostinger

Sur Hostinger, la structure des dossiers est :

```
/home/u605154961/domains/deeppink-rhinoceros-934330.hostingersite.com/
├── public_html/          ← WEB ROOT (Laravel root doit être ICI)
│   ├── index.php
│   ├── build/
│   ├── images/
│   └── ...
├── logs/
└── ...
```

**IMPORTANT** : Le projet Laravel complet doit être dans `public_html/`, PAS au niveau parent.

## 📋 Déploiement Initial (première fois)

### Étape 1 : Accès SSH

1. Se connecter à hPanel Hostinger
2. Aller dans **Advanced** → **SSH Access**
3. Activer SSH et noter les credentials
4. Se connecter via terminal :

```bash
ssh u605154961@deeppink-rhinoceros-934330.hostingersite.com
```

### Étape 2 : Nettoyer et Réinitialiser Git

```bash
# Aller dans le web root
cd ~/domains/deeppink-rhinoceros-934330.hostingersite.com/public_html

# Supprimer ancien git s'il existe au mauvais endroit
cd ..
rm -rf .git public/ app/ resources/ routes/ 2>/dev/null

# Retourner dans public_html
cd public_html

# Backup des fichiers actuels (si nécessaire)
mkdir -p ~/backup_$(date +%Y%m%d)
cp -r * ~/backup_$(date +%Y%m%d)/ 2>/dev/null

# Initialiser git ICI (dans public_html)
git init
git remote add origin https://github.com/gabriel429/portailrh.pnmls.cd.git
git fetch origin main
git checkout -f main
```

### Étape 3 : Configuration .env

```bash
# Copier .env.example si .env n'existe pas
cp .env.example .env

# Éditer .env avec nano
nano .env
```

**Valeurs OBLIGATOIRES pour production** :

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u605154961_portailrh
DB_USERNAME=u605154961_portailrh_user
DB_PASSWORD=[MOT_DE_PASSE_FORT]

SESSION_ENCRYPT=true
```

Générer APP_KEY :

```bash
php artisan key:generate
```

### Étape 4 : Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R u605154961:u605154961 storage bootstrap/cache
```

### Étape 5 : Base de Données

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=ProvinceSeeder --force
```

### Étape 6 : Cache Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔄 Déploiement des Mises à Jour

### Via SSH (Méthode Sécurisée)

```bash
# Se connecter
ssh u605154961@deeppink-rhinoceros-934330.hostingersite.com

# Aller dans le projet
cd ~/domains/deeppink-rhinoceros-934330.hostingersite.com/public_html

# Pull derniers changements
git pull origin main

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations si nécessaire
php artisan migrate --force
```

### Via Gestionnaire de Fichiers (NON RECOMMANDÉ)

Si SSH indisponible, utiliser FTP/SFTP avec FileZilla :

1. Télécharger uniquement les fichiers changés
2. **NE JAMAIS** uploader :
   - `.env` (déjà configuré sur serveur)
   - `vendor/` (trop lourd, utiliser `composer install` via SSH)
   - `node_modules/` (inutile en production)
   - Scripts `*.sh` du dossier `/scripts/`

3. Uploader :
   - `app/`, `resources/`, `routes/`, `database/`
   - `public/build/` (assets compilés)
   - Fichiers racine (`composer.json`, `package.json`, etc.)

## 🚨 Checklist Avant Déploiement

### Sécurité

- [ ] `.env` production avec `APP_DEBUG=false`
- [ ] Aucun fichier `*diagnostic*.php` dans `public/`
- [ ] Aucun fichier `*fix*.php` ou `*test*.php`
- [ ] Scripts shell dans `/scripts/` (jamais dans racine)
- [ ] Logs nettoyés (`storage/logs/`)

### Build

- [ ] `npm run build` exécuté en local
- [ ] Fichiers dans `public/build/` committés
- [ ] Manifest PWA valide

### Base de Données

- [ ] Migrations testées en local
- [ ] Backup DB production avant migration
- [ ] Seeds préparés (si nouveaux rôles/permissions)

## 🔍 Diagnostic Production

### Vérifier Git

```bash
cd ~/domains/deeppink-rhinoceros-934330.hostingersite.com/public_html
git status
git log --oneline -5
```

### Vérifier Assets

```bash
ls -lah public/build/
ls -lah public/build/assets/ | head -20
cat public/build/manifest.json | head
```

### Vérifier .env

```bash
# Afficher config sans credentials
php artisan config:show | grep -E '(APP_ENV|APP_DEBUG|LOG_LEVEL)'
```

### Logs Erreurs

```bash
# Dernières 50 lignes du log Laravel
tail -50 storage/logs/laravel.log

# Logs serveur Hostinger
tail -50 ~/logs/deeppink-rhinoceros-934330.hostingersite.com_error.log
```

## 🐛 Résolution Problèmes Courants

### Erreur 404 sur Assets JS/CSS

**Symptôme** : `app-*.js`, `app-*.css` retournent 404 ou HTML au lieu du fichier

**Cause 1** : Fichiers build au mauvais endroit (à la racine au lieu de `public/build/`)

**Solution** :

```bash
cd ~/domains/deeppink-rhinoceros-934330.hostingersite.com/public_html

# Vérifier la structure
ls -la | grep build

# Si `build/` existe à la racine (MAUVAIS), le déplacer
if [ -d build ] && [ -d public ]; then
    mv build public/build
fi

# Vérifier que les fichiers sont maintenant dans le bon dossier
ls -la public/build/assets/ | head -5
```

**Cause 2** : Problème de cache PWA/navigateur

**Solution** :

```bash
# Sur le navigateur :
# 1. DevTools (F12) → Application → Service Workers → Unregister
# 2. DevTools → Application → Cache Storage → Supprimer tous les caches
# 3. Hard refresh : Ctrl+Shift+R
```

**Cause 3** : Fichiers build pas synchronisés avec git

**Solution** :

```bash
# Vérifier localisation git
cd ~/domains/deeppink-rhinoceros-934330.hostingersite.com
ls -la | grep .git

# Si .git existe ICI (mauvais), le supprimer
rm -rf .git public/ app/

# Réinitialiser dans public_html (voir Étape 2)
cd public_html
git init
# ... (suite Étape 2)
```

### Erreur MIME Type 'text/html'

**Symptôme** : CSS retourne HTML au lieu de CSS

**Cause** : Laravel route catch-all retourne SPA pour tous fichiers

**Solution** : Vérifier `public/.htaccess` ligne 18-21 :

```apache
# Si fichier existe, le servir directement
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]
```

### Erreur "Vite manifest not found"

**Symptôme** : `ViteManifestNotFoundException`

**Cause** : `public/build/manifest.json` manquant

**Solution** :

```bash
# Vérifier git a bien pull le manifest
ls -la public/build/manifest.json

# Si manquant, rebuild en local et commit
npm run build
git add public/build/
git commit -m "Add build manifest"
git push
```

### Erreur 500 Internal Server Error

**Symptômes** : Page blanche avec erreur 500

**Causes possibles** :
1. `.env` mal configuré
2. Permissions incorrectes sur `storage/`
3. Cache corrompu

**Solutions** :

```bash
# 1. Vérifier .env existe
ls -la .env

# 2. Fix permissions
chmod -R 755 storage bootstrap/cache

# 3. Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## 📞 Support

En cas de problème non résolu :

1. Vérifier logs : `storage/logs/laravel.log`
2. Vérifier logs serveur Hostinger dans hPanel
3. Contacter support Hostinger si problème infrastructure

---

**Dernière mise à jour** : 27 mars 2026
**Version Laravel** : 12.53.0
**Version PHP** : 8.3.16
