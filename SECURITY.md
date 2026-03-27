# Sécurité - Portail RH PNMLS

## 🔒 Configuration de Production

### Variables d'Environnement Critiques

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
SESSION_ENCRYPT=true
```

### Base de Données

- ✅ Utilisateur MySQL dédié avec mot de passe fort
- ✅ Jamais d'accès root sans mot de passe
- ✅ Connexions limitées depuis localhost uniquement

## 🛡️ Protections Actives

### Authentification

- **Rate Limiting**: Maximum 5 tentatives de connexion par minute
- **Sessions chiffrées**: Toutes les sessions sont chiffrées en base
- **CSRF Protection**: Actif sur toutes les routes web
- **Sanctum SPA Auth**: Cookies HTTPOnly pour API

### Headers de Sécurité

Middleware `SecurityHeaders` appliqué globalement :

- `Content-Security-Policy`: Strict CSP bloquant scripts inline
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`: Restrictions strictes

### Autorisations

- **RoleMiddleware**: Contrôle d'accès basé sur les rôles
- **PermissionMiddleware**: Permissions granulaires
- **AdminNTMiddleware**: Accès administrateur système
- **SuperAdminMiddleware**: Actions critiques uniquement

## 🚫 Fichiers à NE JAMAIS Déployer en Production

Voir `.deployment-ignore` pour la liste complète.

**Types de fichiers interdits** :
- Scripts de diagnostic PHP (`*diagnostic*.php`, `*fix*.php`)
- Scripts shell de déploiement (`*.sh`)
- Backups de configuration (`*.backup`, `*.bak`, `.env.*`)
- Fichiers de test (`test-*.html`, `test-*.php`)
- Documentation technique locale

## 📋 Checklist Avant Déploiement

### Configuration

- [ ] `APP_ENV=production` et `APP_DEBUG=false`
- [ ] `LOG_LEVEL=error`
- [ ] `SESSION_ENCRYPT=true`
- [ ] Mot de passe DB fort et sécurisé
- [ ] APP_KEY généré et unique

### Nettoyage

- [ ] Supprimer tous fichiers `*diagnostic*.php` de `public/`
- [ ] Supprimer tous fichiers `*fix*.php` de `public/`
- [ ] Supprimer backups `.env.*`
- [ ] Nettoyer `storage/logs/` (garder uniquement `.gitignore`)
- [ ] Vérifier absence de scripts shell `*.sh` dans la racine

### Assets

- [ ] Build Vite exécuté (`npm run build`)
- [ ] Fichiers dans `public/build/` committés
- [ ] Manifest PWA valide

### Sécurité

- [ ] `.env` contient credentials production
- [ ] `.gitignore` empêche commit de `.env`
- [ ] Rate limiting actif sur `/api/login`
- [ ] Headers de sécurité configurés
- [ ] HTTPS forcé (vérifier `.htaccess`)

## 🔐 Gestion des Secrets

### APP_KEY

Ne JAMAIS :
- Commiter l'APP_KEY dans git
- Partager l'APP_KEY par email/chat
- Utiliser la même clé en dev et prod

Générer avec :
```bash
php artisan key:generate
```

### Mots de Passe

- Minimum 12 caractères
- Alphanumériques + symboles
- Jamais réutilisés entre environnements
- Rotation tous les 90 jours pour comptes admin

## 🚨 Incident Response

### En Cas de Compromission

1. **Immédiat** (< 5 min)
   - Mettre l'app en mode maintenance: `php artisan down`
   - Changer tous les mots de passe (DB, admin)
   - Révoquer toutes les sessions: `php artisan session:flush`

2. **Court terme** (< 1h)
   - Régénérer APP_KEY: `php artisan key:generate`
   - Analyser logs `storage/logs/laravel.log`
   - Vérifier fichiers modifiés: `git status`, `git diff`
   - Scanner fichiers PHP suspects dans `public/`

3. **Moyen terme** (< 24h)
   - Audit complet des accès base de données
   - Rotation credentials API tierces
   - Backup sécurisé et chiffré
   - Analyse forensique des logs serveur

### Contacts Urgence

- **Développeur Principal**: [À définir]
- **Admin Système**: [À définir]
- **Responsable Sécurité PNMLS**: [À définir]

## 📊 Audit et Monitoring

### Logs à Surveiller

- Tentatives de connexion échouées (`storage/logs/laravel.log`)
- Erreurs 500 fréquentes (possibles attaques)
- Accès SuperAdmin (`audit_logs` table)
- Modifications de données sensibles

### Commandes de Vérification

```bash
# Vérifier config production
php artisan config:show

# Lister routes protégées
php artisan route:list --columns=uri,middleware

# Vérifier permissions fichiers
ls -la public/ | grep '\.php$'

# Scanner fichiers suspects
find public/ -name '*diagnostic*' -o -name '*fix*' -o -name '*test*'
```

## 🔄 Mises à Jour de Sécurité

### Fréquence

- **Laravel**: Vérifier mises à jour hebdomadaires
- **Dépendances Composer**: `composer outdated` mensuellement
- **Dépendances NPM**: `npm outdated` mensuellement

### Processus

1. Lire changelogs pour patches de sécurité
2. Tester en environnement dev local
3. Backup complet avant mise à jour prod
4. Déployer pendant heures creuses
5. Monitoring post-déploiement 24h

## 📚 Ressources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [Vue.js Security](https://vuejs.org/guide/best-practices/security.html)

---

**Dernière révision**: 27 mars 2026
**Version**: 1.0
**Statut**: Production
