# E-PNMLS

Gestion Intégrée des Ressources Humaines - Programme National Multisectoriel de Lutte contre le Sida

## 📋 Description

Le E-PNMLS est une application web complète dédiée à la gestion des ressources humaines. Elle offre :

- 🔐 **Authentification** avec rôles et permissions
- 📄 **Gestion Documentaire (GED)** - Upload et archivage des documents
- 📋 **Workflow des Demandes** - Congés, absences, abus
- 👥 **Gestion des Agents** - Profils, carrière, historique
- 📊 **Tableaux de Bord** - Statistiques et rapports RH
- 🕐 **Pointage** - Suivi des présences
- 📱 **Interface Responsive** - Design LinkedIn-inspired

## 🚀 Installation

### Prérequis

- PHP 8.3+
- MySQL 8.0+
- Composer
- Node.js (optionnel)

### Étapes d'installation

1. **Cloner le repository**
   ```bash
   git clone https://github.com/gabriel429/portailrh.pnmls.cd.git
   cd portailrh.pnmls.cd
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurer la base de données** dans `.env` :
   ```env
   DB_DATABASE=portailrh_pnmls
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

5. **Exécuter les migrations**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Créer le lien symbolique pour le storage**
   ```bash
   php artisan storage:link
   ```

7. **Démarrer le serveur**
   ```bash
   php artisan serve
   ```

   L'application est accessible à : `http://localhost:8000`

## 📝 Comptes de Test

Après le seed, utilisez ces comptes pour tester :

| Matricule | Nom | Rôle | Mot de Passe |
|-----------|-----|------|--------------|
| PNM-000001 | Kabamba Jean-Pierre | Agent | password |
| PNM-000002 | Mutua Marie | Chef Section RH | password |
| PNM-000003 | Malu Simon | RH National | password |

## 🏗️ Architecture

### Dossiers Principaux

- `app/Models/` - Modèles Eloquent
- `app/Http/Controllers/` - Contrôleurs
- `app/Http/Middleware/` - Middlewares (Rôles, Permissions)
- `database/migrations/` - Schéma de base de données
- `database/seeders/` - Données d'initialisation
- `resources/views/` - Templates Blade
- `routes/` - Routes de l'application

### Modèles

- **Agent** - Utilisateurs du système
- **Role** - Rôles (Agent, Directeur, RH, etc.)
- **Permission** - Permissions granulaires
- **Document** - Gestion documentaire
- **Request** - Demandes (congés, absences, etc.)
- **Signalement** - Signalements d'abus
- **Pointage** - Suivi des présences
- **Department** - Départements
- **Province** - Provinces

## 🔐 Rôles et Permissions

### Rôles disponibles

1. **Agent** - Accès de base (voir son profil et documents)
2. **Directeur** - Superviseur d'équipe
3. **RH Provincial** - Gestion RH provinciale
4. **RH National** - Gestion RH centrale
5. **Chef Section RH** - Validation des demandes
6. **SEP/SEN** - Administrateur système

## 📚 Routes Principales

### Authentification
- `POST /login` - Se connecter
- `POST /logout` - Se déconnecter

### Dashboard & Profil
- `GET /dashboard` - Tableau de bord
- `GET /profile/{id}` - Voir profil agent
- `GET /profile/edit` - Éditer profil

### Documents
- `GET /documents` - Liste des documents
- `POST /documents` - Upload document
- `GET /documents/{id}/download` - Télécharger document

### Demandes
- `GET /requests` - Liste des demandes
- `POST /requests` - Créer une demande
- `GET /requests/{id}` - Détails demande

### Admin RH
- `GET /rh/agents` - Gestion des agents
- `GET /rh/roles` - Gestion des rôles
- `GET /rh/pointages` - Pointages

## 🛠️ Configuration

### Variables d'environnement importantes

```env
APP_NAME=Portail RH PNMLS
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=portailrh_pnmls
LOG_LEVEL=error
```

## 📦 Dépendances principales

- Laravel 12
- Bootstrap 5
- Font Awesome 6.x
- Eloquent ORM

## ✅ Commandes Artisan Utiles

```bash
# Migrations
php artisan migrate              # Exécuter toutes les migrations
php artisan migrate:refresh     # Réinitialiser la BDD
php artisan db:seed            # Charger les données de test

# Cache
php artisan cache:clear         # Vider le cache
php artisan view:clear          # Vider le cache des vues

# Serveur
php artisan serve               # Lancer le serveur de développement

# Clé d'application
php artisan key:generate        # Générer une nouvelle clé APP_KEY
```

## 📖 Documentation

- Voir `INSTALLATION.md` pour les instructions détaillées
- Voir `STRUCTURE_COMPLETE.md` pour la structure complète du projet

## 🐛 Troubleshooting

### "Clé trop longue" lors de la migration
**Solution** : Vérifier que `AppServiceProvider` contient `Schema::defaultStringLength(191);`

### Sessions en base de données
**Configuration** : `SESSION_DRIVER=database` dans `.env`

### Images et assets
Les images doivent être placées dans `public/images/`

## 🤝 Contribution

1. Fork le repository
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Propriétaire - PNMLS

## 👤 Auteur

Créé par l'équipe technique PNMLS

---

**Dernière mise à jour** : 10 Mars 2026
**Version** : 1.0-MVP
**Statut** : 🟢 En développement actif
