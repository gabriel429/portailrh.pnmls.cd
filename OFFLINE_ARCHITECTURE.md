# Architecture Offline-First

## Objectif

L'application reste utilisable sans reseau pour les parcours qui ont ete visites et precharges. Le Service Worker sert l'application Vue pour les navigations profondes, tandis qu'IndexedDB conserve les donnees locales necessaires au travail hors ligne.

## Application shell et routes profondes

- Vite genere `public/build/offline-shell.html` a partir du manifeste de production.
- Le Service Worker est publie a la racine sous `/sw.js`, avec le scope `/`.
- Workbox precache le shell, les assets Vite et les chunks de routes.
- Toute navigation non-API qui echoue hors ligne revient au shell. Vue Router restitue ensuite la route demandee, par exemple `/rh/pointages/create`.
- Le build est copie de `public/build` vers `build` par `npm run build`.

## Session locale

L'authentification serveur reste une session Laravel/Sanctum geree par cookie HTTP-only. Aucun mot de passe, token Sanctum ou cookie n'est copie dans IndexedDB ou localStorage.

Apres une authentification reussie, le navigateur conserve uniquement un profil utilisateur minimalement necessaire a l'interface :

- `localStorage.epnmls_auth_user` et `localStorage.epnmls_auth_hint` permettent un rendu immediat de l'interface hors ligne.
- Le store IndexedDB `offline_sessions` conserve la meme information par `user_id`, avec le dernier statut de session verifie.
- Une reponse serveur explicite `401` ou `419` invalide ce cache local. Une indisponibilite reseau ou une erreur transitoire le preserve.
- La deconnexion efface le profil local et l'entree IndexedDB du compte.

## IndexedDB

La base `PortailRH_OfflineDB` contient :

| Store | Usage |
| --- | --- |
| `departments` | Donnees de reference pour la selection de departement |
| `agents` | Ancien cache de compatibilite par departement |
| `offline_sessions` | Profil local non sensible par utilisateur |
| `pointage_agents` | Agents du formulaire de pointage, isoles par `user_id` et perimetre |
| `sync_queue` | Operations d'ecriture offline generiques |
| `pointage_queue` | Store historique conserve pendant la migration |
| `sync_metadata` | Horodatages de cache |

Les agents de pointage sont indexes par une cle `user_id:scope_key`, par exemple `42:department:7`. Un compte ne peut donc pas reutiliser le cache d'un autre compte.

## Pointage offline

1. En ligne, la vue de saisie enregistre les agents recus de l'API dans `pointage_agents`.
2. Hors ligne, ou si une requete echoue, la vue lit ce cache. Les requetes de reseau ne bloquent pas le repli local au-dela de cinq secondes.
3. Une ecriture locale devient une entree `sync_queue` avec `client_operation_id` UUID stable.
4. Au retour en ligne, les pointages sont envoyes un par un. Une operation reste en queue tant que le serveur ne l'a pas confirmee.
5. Les erreurs d'authentification ou de permission deviennent `blocked_auth`; les erreurs transitoires deviennent `retryable_error`, puis `error` apres le nombre maximal de tentatives.

## Idempotence serveur

La migration `2026_08_08_000000_create_offline_sync_operations_table.php` cree la table `offline_sync_operations` avec une contrainte unique sur `(user_id, client_operation_id)`.

`POST /api/pointages` accepte `pointages.*.client_operation_id`. Le controleur :

- retourne le recu deja enregistre pour une operation repetee ;
- execute la modification du pointage et le recu dans une meme transaction ;
- gere une collision de contrainte unique en relisant le recu existant.

Les validations de perimetre, d'absence justifiee et de jour ferie restent appliquees avant l'ecriture.

## Limites actuelles

La synchronisation generique est en place dans IndexedDB, mais seul le pointage est connecte a un handler serveur idempotent. Les taches, demandes de conge, absences et approbations ne doivent pas etre ajoutes a la queue tant qu'un endpoint dedie ne reutilise pas leurs validations et politiques metier cote Laravel.

Avant le deploiement, executer :

```powershell
php artisan migrate --force
npm run build
```

## Verification manuelle

1. Ouvrir la page de pointage en ligne et charger un departement.
2. Fermer la page, couper le reseau, puis rouvrir directement `/rh/pointages/create`.
3. Verifier que l'interface, la session locale, les departements et les agents precedemment charges sont affiches.
4. Saisir un pointage et verifier qu'il apparait dans la queue locale.
5. Retablir le reseau et verifier que l'operation est retiree uniquement apres confirmation du serveur.
