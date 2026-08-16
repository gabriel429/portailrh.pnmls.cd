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
3. Une creation locale (POST) devient une entree `sync_queue` (`operation: 'create'`) avec `client_operation_id` UUID stable.
4. Une modification (PUT) ou suppression (DELETE) d'un pointage existant hors ligne devient une entree distincte (`operation: 'update'` ou `'delete'`, avec `entity_id` = l'identifiant du pointage vise) — elle n'est plus confondue avec une creation lors de la synchronisation.
5. Au retour en ligne, les operations sont envoyees une par une dans l'ordre de la queue. Une operation reste en queue tant que le serveur ne l'a pas confirmee.
6. Les erreurs d'authentification ou de permission deviennent `blocked_auth`; les erreurs transitoires deviennent `retryable_error`, puis `error` apres le nombre maximal de tentatives (5).
7. Les entrees `synced`, `error` et `blocked_auth` vieilles de plus de 7 jours sont purgees automatiquement au demarrage de l'application (`SyncService.cleanupQueue()`), de meme que les reponses API en cache de plus de 7 jours.

## Idempotence serveur

La migration `2026_08_08_000000_create_offline_sync_operations_table.php` cree la table `offline_sync_operations` avec une contrainte unique sur `(user_id, client_operation_id)`.

Deux mecanismes coexistent :

- **Pointage (creation uniquement)** : `POST /api/pointages` accepte `pointages.*.client_operation_id` et gere lui-meme le recu idempotent dans sa propre transaction (retourne le recu existant pour une operation repetee, gere une collision de contrainte unique en relisant le recu). Les corrections (`PUT /pointages/{id}`) et suppressions (`DELETE /pointages/{id}`) ne passent pas par ce mecanisme — elles ciblent un enregistrement deja identifie, donc rejouer la meme requete est deja naturellement sans effet de bord supplementaire (une modification identique appliquee deux fois donne le meme resultat).
- **Autres entites (creation uniquement)** : `App\Services\OfflineIdempotencyService::once()` fournit le meme comportement generique (verrouille sur `(user_id, client_operation_id)`, rejoue le resultat existant, transaction) pour les endpoints de creation qui l'utilisent explicitement. A ce jour : demandes (`RequestController`), taches (`TacheController`), conges individuels et plannings (`HolidayController`, `HolidayPlanningController`), signalements (`SignalementController`), activites PTA (`PlanTravailController`).

Les validations de perimetre, d'absence justifiee et de jour ferie restent appliquees avant l'ecriture.

## Limites actuelles

- La file d'attente offline (`OFFLINE_QUEUEABLE_CREATIONS` dans `resources/js/api/client.js`) ne couvre que les creations en JSON pur, sans fichier joint : taches, demandes, conges (individuels et planning), signalements, activites PTA, plus le pointage (creation/modification/suppression). Toute action impliquant un upload de fichier (documents, piece jointe de demande) reste bloquee hors ligne avec un message explicite plutot que d'echouer silencieusement.
- Il n'existe pas de resolution de conflit au sens strict : si l'etat serveur a change entre la creation locale et la synchronisation (ex. l'agent cible a ete desactive entretemps), la synchronisation echoue et l'operation reste visible en `error` dans le panneau de synchronisation (icone de la barre superieure) jusqu'a intervention manuelle (reessayer ou supprimer).
- Le panneau de synchronisation liste les operations individuelles et permet de les relancer ou de les supprimer, mais n'affiche pas de detail structure de l'erreur serveur au-dela du message brut renvoye.

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
