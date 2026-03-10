# Installation et Utilisation - Portail RH PNMLS

## 🚀 Quick Start (5 minutes)

### 1. Démarrer MySQL via WAMP
- Ouvrir WampServer
- Double-cliquer pour démarrer
- Vérifier que MySQL est vert

### 2. Créer la base de données

Ouvrir phpMyAdmin (http://localhost/phpmyadmin) et exécuter:

```sql
CREATE DATABASE IF NOT EXISTS portailrh_pnmls
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;
```

### 3. Installer les dépendances

```bash
cd c:/wamp64/www/portailrh.pnmls.cd
composer install
```

### 4. Configurer l'environnement

Ouvrir `.env` et vérifier:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portailrh_pnmls
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Exécuter les migrations

```bash
php artisan migrate
php artisan db:seed
```

### 6. Lancer l'app

**Option A : Artisan built-in server**
```bash
php artisan serve
# Accès: http://localhost:8000
```

**Option B : Via WAMP (configuré pour www/portailrh.pnmls.cd)**
- Accès: http://portailrh.pnmls.cd
- (Nécessite configuration VirtualHost - voir plus bas)

---

## 🔑 Comptes de Test

Après migration + seeding, 2 comptes disponibles:

### Agent (Test basique)
- **Matricule**: PNM-000001
- **Mot de passe**: password
- **Rôle**: Agent

### Chef RH (Admin)
- **Matricule**: PNM-000002
- **Mot de passe**: password
- **Rôle**: Chef Section RH

---

## 📖 Structure de l'Applic

### Dossiers clés:
```
app/Models/              → Modèles de données
app/Http/Controllers/    → Logique des pages
app/Http/Controllers/RH/ → Contrôleurs admin RH
database/migrations/     → Tables SQL
resources/views/         → Fichiers HTML/Blade
routes/                  → Définition des routes
```

### Fichiers de config:
- `.env` - Variables d'environnement (BDD, APP, MAIL)
- `config/app.php` - Configuration app
- `config/database.php` - Configuration BDD

---

## 🛠️ Commandes Utiles

### Setup initial
```bash
php artisan migrate              # Créer les tables
php artisan db:seed             # Remplir données de test
php artisan storage:link        # Lier dossier uploads
```

### Développement
```bash
php artisan serve                         # Lancer serveur
php artisan tinker                        # Console interactive
php artisan make:migration table_name     # Nouvelle migration
php artisan make:model ModelName -m       # Nouveau modèle + migration
php artisan make:controller ControllerName # Nouveau contrôleur
```

### Reset complet
```bash
php artisan migrate:fresh --seed  # ⚠️ Supprime tout et reconstruit
```

### Cache (prod)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🌐 Configuration VirtualHost (pour WAMP)

Pour accéder via `http://portailrh.pnmls.cd`:

### 1. Modifier `C:\Windows\System32\drivers\etc\hosts`

Ajouter:
```
127.0.0.1 portailrh.pnmls.cd
```

### 2. Configurer Apache (WAMP)

Ouvrir `C:\wamp64\bin\apache\apache2.4.xx\conf\extra\httpd-vhosts.conf`

Ajouter à la fin:
```apache
<VirtualHost *:80>
    ServerName portailrh.pnmls.cd
    ServerAlias www.portailrh.pnmls.cd
    DocumentRoot "c:/wamp64/www/portailrh.pnmls.cd/public"

    <Directory "c:/wamp64/www/portailrh.pnmls.cd/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 3. Redémarrer Apache
- WampServer → Redémarrer

---

## 📊 Modules et Leurs Utilisations

### 1️⃣ **Profil Agent**
- **Accès**: Cliquer photo → "Mon Profil"
- **Actions**:
  - Voir infos personnelles
  - Consulter carrière
  - Télécharger documents
  - Modifier infos (photo, contact, etc.)

### 2️⃣ **Gestion Documentaire (GED)**
- **Accès**: Menu → "Documents"
- **Actions**:
  - Upload documents (PDF, Images)
  - Organiser par catégories
  - Télécharger
  - Partager avec RH

### 3️⃣ **Demandes**
- **Accès**: Menu → "Mes Demandes" ou "Nouvelle Demande"
- **Types**:
  - Congé (annuel, maladie, etc.)
  - Promotion
  - Disponibilité
  - Formation
- **Workflow**:
  - Vous envoyer → Chef approuve (+ visa) → Décision finale

### 4️⃣ **Signalements**
- **Accès**: Dashboard → "Signaler un abus"
- **Anonyme** possible
- **Catégories**: harcèlement, favoritisme, abus, etc.

### 5️⃣ **Admin RH**
- **Accès**: Menu Admin (si rôle RH)
- **Fonctions**:
  - Créer/modifier agents
  - Générer matricules
  - Gérer pointages
  - Voir tableaux de bord

---

## 🆘 Troubleshooting

### ❌ "SQLSTATE[HY000]: General error & Connexion refusée"
**Cause**: MySQL n'est pas démarré
**Solution**:
```bash
# Démarrer MySQL via WAMP, ou:
"C:\wamp64\bin\mysql\mysql9.1.0\bin\mysqld.exe"
```

### ❌ "No such file or directory" (storage)
**Cause**: Dossier `storage` n'a pas les bonnes permissions
**Solution**:
```bash
chmod -R 775 storage bootstrap/cache
```

### ❌ "Route not found" ou page blanche
**Cause**: Cache vieux
**Solution**:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### ❌ "Base de données n'existe pas"
**Cause**: Migration n'a pas roulé
**Solution**:
```bash
# Vérifier .env DB_DATABASE=portailrh_pnmls
# Créer manuellement via phpMyAdmin
php artisan migrate
```

---

## 🔐 Sécurité de Base

✅ **Déjà implémenté:**
- Hashage bcrypt des mots de passe
- Protection CSRF tokens
- Validation inputs
- Requêtes préparées (injection SQL prévenue)
- Roles & permissions (RBAC)

⚠️ **Pour production:**
- Changer `APP_DEBUG=false` dans `.env`
- Utiliser vrai serveur (Apache/Nginx)
- HTTPS activé
- Rate limiting
- Backup régulière

---

## 📝 Prochaines Étapes de Développement

1. **Vues complètes** pour les demandes, GED, admin
2. **Emails** - Notifications des demandes
3. **Rapports PDF** - Génération automatique
4. **API REST** - Pour mobile app future
5. **Tests** - PHPUnit tests
6. **Déploiement** - Sever de production

---

## 📞 Support

**Tous les fichiers sont dans:**
- `c:/wamp64/www/portailrh.pnmls.cd/`

**Ressources utiles:**
- [Laravel Docs](https://laravel.com/docs)
- [Bootstrap Docs](https://getbootstrap.com/docs)
- [MySQL Docs](https://dev.mysql.com/doc)

Good luck! 🚀
