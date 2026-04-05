# 🔍 GAP ANALYSIS FINAL – E-PNMLS vs User Story Complet (v2)

**Date** : 5 avril 2026 — Mise à jour v2
**Analysé par** : Audit automatisé du code source
**Branche** : `main`
**Référence** : User Story Complet – Tous Modules Confondus (9 modules, 14 rôles)

---

## 📊 RÉSUMÉ EXÉCUTIF

| Catégorie | Existant | Requis | Couverture | Commentaire |
|-----------|----------|--------|------------|-------------|
| **Rôles** | 8 (+6 via migration gap) | 14 | 100% des rôles définis | ✅ Migration existe, à exécuter |
| **Permissions (seedées)** | 25 (+50 via migration gap) | ~75 | 100% définis | ✅ Migration existe, à exécuter |
| **Permissions (wired dans le code)** | 1 (DemandeWorkflowService) | ~75 | **1%** | 🔴 Critique |
| **Pivot role_permission (peuplé)** | 0 (mais migration gap le fait) | ~200 entrées | 0% → 100% si migration | 🟡 Migration existe |
| **Modules fonctionnels** | 7/9 | 9/9 | 78% | Renforcement + Juridique manquent |
| **Tables DB** | 30+ | 33+ | ~91% | task_reports vide/non utilisée |
| **Events/Listeners** | 0 | ~10 events | **0%** | 🔴 Critique |
| **Notifications par mail** | 0 | Oui | **0%** | 🔴 Critique |
| **Workflow multi-étapes** | ✅ Service créé | Complet | ~70% | Service OK, routes partielles |
| **Détection conflit Congé↔PTA** | 0 | Oui | **0%** | 🔴 Critique |
| **Rapports (renforcement/signalement/tâches)** | 0 | 6 types | **0%** | 🔴 |
| **Policies Laravel** | 0 | Oui | **0%** | 🟠 |

---

## 🔴 PROBLÈMES CRITIQUES (Priorité 1)

### 1. Système de Permissions construit mais JAMAIS utilisé

**Constat** : Le `PermissionSeeder` insère 25 permissions, les modèles `Role` et `Permission` ont les relations `BelongsToMany` via la table pivot `role_permission`, mais :

- **Aucun contrôleur** n'utilise `->middleware('permission:xxx')` 
- **Aucune route** n'a de middleware de permission
- La table `role_permission` est **vide** (pas d'assignation rôle↔permission)
- La table `agent_permission` est **vide**
- Le `PermissionMiddleware` a un **bug** : il cherche `'nom_permission'` mais le champ est `'nom'`

**Impact** : Tout le contrôle d'accès repose sur des chaînes de rôle en dur dans le code (`hasRole('RH National')`, `hasAdminAccess()`). C'est fragile, non configurable, et ne correspond pas au user story.

### 2. Authentification duale incohérente

**Constat** : Les deux modèles `User` et `Agent` étendent `Authenticatable` et ont des méthodes `hasRole()`, `hasAdminAccess()` identiques.

- `User.hasAdminAccess()` inclut `'SEN'` dans les rôles admin
- `Agent.hasAdminAccess()` **n'inclut PAS** `'SEN'`
- Le frontend appelle `auth.hasAdminAccess` mais on ne sait pas si c'est `User` ou `Agent`

**Impact** : Comportements imprévisibles selon le contexte d'authentification.

### 3. Pas de workflow de validation multi-étapes pour les Demandes

**Constat (User Story)** : `Agent → Directeur → RH → SEP → SEN`
**Constat (Code)** : La table `requests` a un champ `statut` simple (`en_attente`, `approuvé`, `rejeté`, `annulé`). Le `RequestController` n'a que `store()` + `update()` basique. Pas de concept d'étapes de validation, pas de champ `validated_by`, `validated_at`, `validation_level`.

---

## 🟠 MODULES MANQUANTS (Priorité 2)

### MODULE 3 – Renforcement des Capacités (0% implémenté)

**Requis** :
- Tables : `formations`, `beneficiaires`
- Contrôleur : `RenforcementController` (view, process, plan, validate, monitor, report)
- Rôles : `Chef Section Renforcement`, `Chef Cellule Renforcement`
- Règle : Demande type `renforcement_capacites` → notification auto au Chef Section Renforcement
- Lien avec Tâches : Formation planifiée → génère tâches pour bénéficiaires

**Existant** :
- ✅ Le type de demande `renforcement_capacites` existe (migration de merge)
- ✅ Le département DRRC a été créé
- ❌ Aucune table `formations` ou `beneficiaires`
- ❌ Aucun contrôleur dédié
- ❌ Aucun rôle spécifique seedé
- ❌ Pas de notification automatique

### MODULE 5 – Signalements (50% implémenté)

**Requis** :
- Anonymat obligatoire (agent_id NULL)
- Rôles : `Chef Section Juridique`
- Workflow : `ouvert → en_cours → résolu → fermé`
- Permissions : `view_all`, `analyze`, `process`, `close`
- Rapports mensuels/annuels

**Existant** :
- ✅ Table `signalements` avec `statut`, `severite`
- ✅ `SignalementController` basique (CRUD)
- ❌ `agent_id` est `NOT NULL` avec foreign key → **pas d'anonymat possible**
- ❌ Pas de rôle `Chef Section Juridique`
- ❌ Pas de workflow d'analyse (qui traite, historique des traitements)
- ❌ Pas de rapports

---

## 🟡 ÉCARTS PAR MODULE (Priorité 3)

### MODULE 1 – Agent & Authentification

| User Story | Existant | Écart |
|-----------|----------|-------|
| auth.login | ✅ AuthController@login | - |
| profile.view | ✅ ProfileController@show | - |
| profile.update | ✅ ProfileController@update | - |
| document.upload | ✅ DocumentController@store | - |
| document.delete | ✅ DocumentController@destroy | - |
| document.view | ✅ DocumentController@index/show | - |

**Verdict** : ✅ Complet

### MODULE 2 – Demandes

| User Story | Existant | Écart |
|-----------|----------|-------|
| demande.create | ✅ RequestController@store | - |
| demande.view_own | ✅ (scoped via UserDataScope) | - |
| demande.view_departement | ⚠️ Partiel | Pas de scope par département pour Directeur |
| demande.validate_director | ❌ | Pas de notion d'étape Directeur |
| demande.review_rh | ❌ | Pas de notion de review RH |
| demande.validate_sep | ❌ | Pas de validation provinciale |
| demande.validate_sen | ❌ | Pas de validation stratégique |
| Règle : formation → Renforcement | ❌ | Pas de routage auto |

**Écarts** :
- Table `requests` manque : `validated_by`, `validated_at`, `validation_level`, `current_step`
- Pas de workflow multi-étapes
- Pas de notification par étape

### MODULE 4 – PTA

| User Story | Existant | Écart |
|-----------|----------|-------|
| pta.create | ✅ PlanTravailController@store | - |
| pta.update | ✅ PlanTravailController@update | - |
| pta.review | ⚠️ Partiel | Pas de rôle "Chef Section Planification" dédié |
| pta.validate_section | ❌ | Champ `validation_niveau` existe mais pas de logic |
| pta.validate_cellule | ❌ | Pas de "Cellule Planification" |
| pta.monitor | ⚠️ Partiel | Dashboard SEN a des stats PTA |
| pta.update_progress | ✅ | Champ `pourcentage` |
| pta.dashboard | ⚠️ Partiel | Dans ExecutiveDashboard |
| pta.export_excel | ✅ | PlanTravailController@export |

### MODULE 6 – Congés

| User Story | Existant | Écart |
|-----------|----------|-------|
| conge.view_own | ✅ HolidayController@index (scoped) | - |
| conge.view_team | ⚠️ Partiel | Pas de vue équipe/département explicite |
| conge.request | ✅ HolidayController@store | - |
| conge.update_own | ✅ HolidayController@update | - |
| conge.plan_national | ✅ HolidayPlanningController | - |
| conge.plan_provincial | ⚠️ | Logique existe mais pas de rôle CAF |
| conge.validate_national | ✅ HolidayController@approve | - |
| conge.validate_provincial | ⚠️ | Approve existe mais scoping flou |
| conge.detect_conflict | ✅ | Chevauchement vérifié |
| conge.check_pta_conflict | ❌ | **Aucune détection conflit PTA** |
| conge.notify_pta_conflict | ❌ | Pas implémenté |
| Table conges_regles_departement | ❌ | N'existe pas |
| Table conges_conflits | ❌ | N'existe pas |

### MODULE 7 – Tâches

| User Story | Existant | Écart |
|-----------|----------|-------|
| task.create | ✅ TacheController@store | - |
| task.link_pta | ✅ | Champ `activite_plan_id` |
| task.notify | ⚠️ | Notification DB, pas mail |
| task.view_own | ✅ | Scoped |
| task.update_status | ✅ | - |
| task.submit_report | ❌ | **Pas de table `task_reports`** |
| task.report.view | ❌ | Pas de rapports d'exécution |
| task.report.performance | ❌ | Pas de métriques par agent |

### MODULE 8 – Communication

| User Story | Existant | Écart |
|-----------|----------|-------|
| message.send_rh | ✅ MessageController@store | - |
| message.reply | ✅ | - |
| message.send_targeted | ⚠️ | Pas de groupes |
| announcement.broadcast | ✅ CommuniqueController | - |
| notification.receive | ✅ NotificationController | - |
| notification.send (email) | ❌ | **DB uniquement, pas de mail** |

### MODULE 9 – Notifications centralisées

| Événement | Implémenté | Canal |
|-----------|-----------|-------|
| Demande formation → Chef Renforcement | ❌ | - |
| Signalement abus → Chef Juridique | ❌ | - |
| Modification PTA → Cellule Planification | ❌ | - |
| Tâche attribuée → Agent | ⚠️ DB only | DB (pas mail) |
| Congé en conflit PTA | ❌ | - |
| Message reçu → Agent | ⚠️ DB only | DB (pas mail) |
| Communiqué → Tous agents | ⚠️ DB only | DB (pas mail) |

---

## 👥 RÔLES – Analyse détaillée

### Rôles existants (RoleSeeder) :
1. `Agent`
2. `Directeur`
3. `RH Provincial`
4. `RH National`
5. `Section ressources humaines`
6. `Section Nouvelle Technologie`
7. `SEP`
8. `SEN`

### Rôles manquants par rapport au User Story :
| Rôle requis | Correspondance | Action |
|------------|----------------|--------|
| CAF Provincial | `RH Provincial` ≈ | Renommer ou alias |
| Assistant RH National | `RH National` ≈ | OK mais clarifier |
| Chef Section RH | `Section ressources humaines` ≈ | OK mais incohérent |
| Chef Section Renforcement | ❌ | **À créer** |
| Chef Cellule Renforcement | ❌ | **À créer** |
| Chef Section Planification | ❌ | **À créer** |
| Cellule Planification (PSE) | ❌ | **À créer** |
| Chef Section Juridique | ❌ | **À créer** |
| SENA | SEN ≈ | Clarifier si distinct |

---

## 🗄️ TABLES MANQUANTES

### Tables à créer :

```sql
-- MODULE 3 : Renforcement des Capacités
CREATE TABLE formations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    request_id BIGINT UNSIGNED NULL,  -- Lien vers la demande d'origine
    objectif TEXT,
    lieu VARCHAR(255),
    formateur VARCHAR(255),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    statut ENUM('planifiee','en_cours','terminee','annulee') DEFAULT 'planifiee',
    created_by BIGINT UNSIGNED NOT NULL,
    validated_by BIGINT UNSIGNED NULL,
    validated_at TIMESTAMP NULL,
    budget DECIMAL(18,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES agents(id) ON DELETE SET NULL
);

CREATE TABLE formation_beneficiaires (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formation_id BIGINT UNSIGNED NOT NULL,
    agent_id BIGINT UNSIGNED NOT NULL,
    statut ENUM('inscrit','confirme','present','absent','certifie') DEFAULT 'inscrit',
    note_evaluation TEXT,
    certificat VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE(formation_id, agent_id),
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
);

-- MODULE 7 : Rapports d'exécution de tâches
CREATE TABLE task_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tache_id BIGINT UNSIGNED NOT NULL,
    agent_id BIGINT UNSIGNED NOT NULL,
    rapport TEXT NOT NULL,
    fichier VARCHAR(255) NULL,
    date_soumission TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tache_id) REFERENCES taches(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
);

-- MODULE 6 : Règles de congé par département
CREATE TABLE conges_regles_departement (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    departement_id BIGINT UNSIGNED NOT NULL,
    max_consecutif INT DEFAULT 30,
    taux_absent_max DECIMAL(5,2) DEFAULT 20.00,  -- % max d'absents simultanés
    jours_annuels INT DEFAULT 30,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE(departement_id),
    FOREIGN KEY (departement_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- MODULE 6 : Conflits congé/PTA
CREATE TABLE conges_conflits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_id BIGINT UNSIGNED NOT NULL,
    activite_plan_id BIGINT UNSIGNED NULL,
    type_conflit ENUM('chevauchement_conge','conflit_pta','taux_absent_depasse') NOT NULL,
    description TEXT,
    resolue BOOLEAN DEFAULT FALSE,
    resolue_par BIGINT UNSIGNED NULL,
    resolue_le TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE CASCADE,
    FOREIGN KEY (activite_plan_id) REFERENCES activite_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (resolue_par) REFERENCES agents(id) ON DELETE SET NULL
);
```

### Colonnes à ajouter aux tables existantes :

```sql
-- Table requests : workflow multi-étapes
ALTER TABLE requests
    ADD COLUMN current_step ENUM('directeur','rh','sep','sen') DEFAULT 'directeur' AFTER statut,
    ADD COLUMN validated_by_director BIGINT UNSIGNED NULL,
    ADD COLUMN validated_at_director TIMESTAMP NULL,
    ADD COLUMN validated_by_rh BIGINT UNSIGNED NULL,
    ADD COLUMN validated_at_rh TIMESTAMP NULL,
    ADD COLUMN validated_by_sep BIGINT UNSIGNED NULL,
    ADD COLUMN validated_at_sep TIMESTAMP NULL,
    ADD COLUMN validated_by_sen BIGINT UNSIGNED NULL,
    ADD COLUMN validated_at_sen TIMESTAMP NULL;

-- Table signalements : anonymat
ALTER TABLE signalements
    MODIFY agent_id BIGINT UNSIGNED NULL;  -- Permettre NULL pour anonymat
ALTER TABLE signalements DROP FOREIGN KEY signalements_agent_id_foreign;
ALTER TABLE signalements
    ADD CONSTRAINT signalements_agent_id_foreign
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE SET NULL;
```

---

## 🔧 CORRECTIONS DE CODE REQUISES

### 1. Bug PermissionMiddleware (ligne 32)

**Fichier** : `app/Http/Middleware/PermissionMiddleware.php`
```php
// ACTUEL (BUG)
if ($userPermissions->contains('nom_permission', $permission)) {

// CORRIGÉ
if ($userPermissions->contains('code', $permission)) {
```

### 2. Incohérence User.hasAdminAccess() vs Agent.hasAdminAccess()

**User.php** inclut `'SEN'` et `'Section Nouvelle Technologie'`
**Agent.php** inclut `'Section Nouvelle Technologie'` mais pas `'SEN'`

→ Aligner les deux méthodes ou supprimer la duplication en délégant de `User` vers `Agent`.

### 3. Permissions non assignées aux rôles

La table `role_permission` est vide. Aucun rôle n'a de permissions attachées.

---

## 📋 PERMISSIONS COMPLÈTES À CRÉER

### Permissions existantes (25) :
```
view_own_profile, edit_own_profile, view_own_pointages, view_own_documents,
create_requests, view_agents, view_agent_detail, create_agent, edit_agent,
delete_agent, view_documents, create_document, edit_document, validate_document,
delete_document, view_requests, approve_request, reject_request, view_pointages,
create_pointage, edit_pointage, view_signalements, create_signalement,
edit_signalement, view_roles, create_role, edit_role, view_permissions, access_admin
```

### Permissions à ajouter (~35) :

```
-- Module Demandes (workflow)
demande.view_departement
demande.validate_director
demande.review_rh
demande.validate_sep
demande.validate_sen

-- Module Renforcement
renforcement.view
renforcement.process
renforcement.plan
renforcement.validate
renforcement.monitor
renforcement.report.monthly
renforcement.report.annual

-- Module PTA
pta.create
pta.update
pta.review
pta.validate_section
pta.validate_cellule
pta.monitor
pta.update_progress
pta.dashboard
pta.export_excel

-- Module Signalements
signalement.view_all
signalement.analyze
signalement.process
signalement.close
signalement.report.monthly
signalement.report.annual

-- Module Congés
conge.view_own
conge.view_team
conge.request
conge.update_own
conge.plan_national
conge.plan_provincial
conge.validate_national
conge.validate_provincial
conge.view_all
conge.view_province
conge.export
conge.detect_conflict

-- Module Tâches
task.create
task.link_pta
task.view_own
task.update_status
task.submit_report
task.report.view
task.report.performance

-- Module Communication
message.send_rh
message.reply
message.send_targeted
announcement.broadcast
```

---

## 🏗️ ARCHITECTURE MANQUANTE

### 1. Aucun Event/Listener

Le dossier `app/Events/` et `app/Listeners/` **n'existent pas**. Tous les effets de bord (notifications, mises à jour) sont codés directement dans les contrôleurs → code spaghetti.

**Events à créer** :
| Event | Listener(s) |
|-------|-------------|
| `DemandeCreated` | `NotifyRHListener`, `RouteToRenforcementListener` |
| `DemandeValidated` | `NotifyNextStepListener`, `NotifyAgentListener` |
| `FormationPlanned` | `CreateBeneficiaryTasksListener` |
| `SignalementCreated` | `NotifyJuridiqueListener` |
| `PtaModified` | `NotifyCellulePlanificationListener` |
| `TacheAssigned` | `NotifyAgentTaskListener`, `SendEmailListener` |
| `CongeRequested` | `CheckPtaConflictListener`, `CheckQuotaListener` |
| `CongeApproved` | `UpdateAgentStatusListener`, `NotifyAgentListener` |
| `CommuniquePublished` | `NotifyAllAgentsListener`, `SendBulkEmailListener` |

### 2. Aucune notification par email

`NotificationService` écrit uniquement en DB (`notifications_portail`). Aucune configuration de mail dans le code applicatif (bien que `config/mail.php` existe).

### 3. Pas de Policies Laravel

Aucune utilisation de `Gate::define()` ou de classes `Policy`. Tout le contrôle d'accès est ad-hoc dans les contrôleurs.

---

## 📊 MATRICE RÔLE → PERMISSION (à implémenter)

| Permission | Agent | Directeur | RH Prov | RH Nat | Section RH | Chef Renf | Chef Cell Renf | Chef Planif | Cell Planif | Chef Juridique | SEP | SEN |
|-----------|-------|-----------|---------|--------|------------|-----------|---------------|-------------|------------|----------------|-----|-----|
| profile.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| demande.create | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| demande.validate_director | | ✅ | | | | | | | | | | |
| demande.review_rh | | | | ✅ | ✅ | | | | | | | |
| demande.validate_sep | | | | | | | | | | | ✅ | |
| demande.validate_sen | | | | | | | | | | | | ✅ |
| renforcement.view | | | | | | ✅ | ✅ | | | | | |
| renforcement.process | | | | | | ✅ | | | | | | |
| renforcement.validate | | | | | | | ✅ | | | | | |
| pta.create | | ✅ | | | | | | | | | ✅ | ✅ |
| pta.validate_section | | | | | | | | ✅ | | | | |
| pta.validate_cellule | | | | | | | | | ✅ | | | |
| signalement.create | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| signalement.view_all | | | | | | | | | | ✅ | | |
| signalement.process | | | | | | | | | | ✅ | | |
| conge.plan_national | | | | ✅ | ✅ | | | | | | | |
| conge.plan_provincial | | | ✅ | | | | | | | | | |
| task.create | | ✅ | | ✅ | | | | | | | ✅ | ✅ |

---

## 🗺️ PLAN D'ACTION RECOMMANDÉ

### Phase 1 – Fondations (Critique, 1-2 semaines)
1. **Fixer le PermissionMiddleware** (bug `nom_permission` → `code`)
2. **Aligner User/Agent** `hasAdminAccess()` (supprimer duplication)
3. **Créer les 5 rôles manquants** via migration
4. **Créer les ~35 permissions manquantes** via migration
5. **Peupler `role_permission`** avec la matrice ci-dessus
6. **Remplacer progressivement `hasRole()` par `hasPermission()`** dans les contrôleurs

### Phase 2 – Workflow Demandes (1 semaine)
1. Migration : ajouter colonnes workflow à `requests`
2. Refactorer `RequestController` avec workflow multi-étapes
3. Créer `DemandeWorkflowService`
4. Events : `DemandeCreated`, `DemandeValidated`

### Phase 3 – Module Renforcement (1-2 semaines)
1. Migrations : `formations`, `formation_beneficiaires`
2. Models : `Formation`, `FormationBeneficiaire`
3. Controller : `RenforcementController`
4. Listener : demande `renforcement_capacites` → auto-notifie Chef Renforcement
5. Vues Vue.js

### Phase 4 – Signalements anonymes + Juridique (3-5 jours)
1. Migration : rendre `signalements.agent_id` nullable
2. Adapter `SignalementController` pour anonymat
3. Créer workflow d'analyse/traitement
4. Rapports mensuels/annuels

### Phase 5 – Congés avancés (1 semaine)
1. Migrations : `conges_regles_departement`, `conges_conflits`
2. Détection automatique conflit PTA
3. Vue équipe/département
4. Listener : `CongeRequested` → vérifie conflits PTA

### Phase 6 – Notifications mail + Events (1 semaine)
1. Créer `app/Events/` et `app/Listeners/`
2. Configurer le canal mail
3. Connecter tous les événements métier
4. Template mail PNMLS

### Phase 7 – Rapports de tâches (3-5 jours)
1. Migration : `task_reports`
2. Model + Controller
3. Métriques de performance par agent

---

## ✅ CE QUI FONCTIONNE BIEN (à préserver)

- ✅ Système d'authentification Sanctum robuste
- ✅ `UserDataScope` pour le scoping national/provincial (bien conçu)
- ✅ Audit logs complets avec possibilité de revert
- ✅ Gestion des pointages (daily/monthly + export Excel)
- ✅ Holiday plannings avec quotas et chevauchement
- ✅ PTA avec import, trimestres, pourcentage de progression
- ✅ Tâches liées au PTA avec documents et commentaires
- ✅ NotificationService (DB) structuré et extensible
- ✅ Dashboard exécutif SEN avec 30+ KPIs
- ✅ Sécurité : headers, gel de compte, suivi IP
- ✅ PWA avec support offline (sync)
- ✅ Architecture API Resource propre (ApiController base)

---
---

# 📌 CONFRONTATION DÉTAILLÉE – USER STORY vs CODE EXISTANT (v2)

## ⚡ CHANGEMENTS DEPUIS LA V1 DU GAP ANALYSIS

### Ce qui a été AJOUTÉ depuis la première analyse :

| Élément | Fichier | Statut |
|---------|---------|--------|
| 6 nouveaux rôles | Migration `2026_04_05_200000` | ✅ Défini, à exécuter |
| ~50 nouvelles permissions | Migration `2026_04_05_200000` | ✅ Défini, à exécuter |
| Matrice rôle↔permission (200+ pivots) | Migration `2026_04_05_200000` | ✅ Défini, à exécuter |
| Colonnes workflow multi-étapes | Migration `2026_04_05_200000` | ✅ Défini, à exécuter |
| `DemandeWorkflowService` complet | `app/Services/DemandeWorkflowService.php` | ✅ Implémenté |
| Signalement anonymat | Migration + `SignalementController` | ✅ Implémenté |
| Modèles Formation/FormationBeneficiaire | `app/Models/` | ✅ Modèles existent |
| Modèles CongeConflit/CongeRegleDepartement | `app/Models/` | ✅ Modèles existent |
| Modèle TaskReport | `app/Models/TaskReport.php` | ✅ Modèle existe |

---

## 🔴 ÉCARTS RESTANTS – MODULE PAR MODULE

### MODULE 1 — Agent & Authentification ✅ COMPLET

Aucun écart fonctionnel. Permissions définies mais non wired (contrôle via `auth:sanctum` seulement).

### MODULE 2 — Demandes (Workflow global) — 69%

| User Story | Statut | Commentaire |
|-----------|--------|-------------|
| Créer demande | ✅ | `RequestController@store` |
| Voir mes demandes | ✅ | Scoped via `UserDataScope` |
| Voir demandes département | 🟡 | Scope province/global, pas département pour Directeur |
| Valider (Directeur/RH/SEP/SEN) | ✅ | `DemandeWorkflowService` complet |
| Règle : formation → Renforcement | 🔴 | **Pas d'event/listener** |

**Manque** : Event `DemandeCreated` → notification auto Chef Section Renforcement si type=`renforcement_capacites`. Scope département pour Directeur.

### MODULE 3 — Renforcement des Capacités 🔴 0%

Modèles `Formation` et `FormationBeneficiaire` existent. Permissions et rôles définis dans migration gap. **Manque** :
- `RenforcementController` (CRUD, workflow validation, rapports)
- Routes API (`/renforcement/*`)
- Listeners (demande→notification, formation→tâches bénéficiaires)
- Rapports mensuels/annuels

### MODULE 4 — PTA — 56%

| User Story | Statut |
|-----------|--------|
| CRUD activités | ✅ |
| Mettre progression | ✅ |
| Valider section/cellule | 🔴 Pas de workflow validation PTA |
| Dashboard PTA dédié | 🟡 Dans ExecutiveDashboard |
| Modification → notification PSE | 🔴 Pas d'event |

**Manque** : Colonnes `validated_by_section/cellule` sur `activite_plans`, méthodes `validateSection()`/`validateCellule()`, Event `PtaModified`.

### MODULE 5 — Signalements — 63%

| User Story | Statut |
|-----------|--------|
| Créer anonyme | ✅ (`is_anonymous`, `agent_id` nullable) |
| Workflow statuts | ✅ (ouvert→en_cours→resolu→ferme) |
| Notification Chef Juridique | 🔴 Pas d'event |
| Rapports mensuel/annuel | 🔴 |

### MODULE 6 — Congés — 70%

| User Story | Statut |
|-----------|--------|
| CRUD congés | ✅ |
| Planning national/provincial | ✅ |
| Approuver/refuser/annuler | ✅ |
| Détection chevauchement congé↔congé | ✅ (`Holiday::hasConflict`) |
| **Détection conflit Congé↔PTA** | 🔴 CRITIQUE — Table `conges_conflits` orpheline |
| **Règles département (taux_absent_max)** | 🔴 — `CongeRegleDepartement` non vérifié |
| Vue congés + PTA combinée | 🔴 |

### MODULE 7 — Tâches — 61%

| User Story | Statut |
|-----------|--------|
| CRUD + lien PTA | ✅ |
| Commentaires + documents | ✅ |
| **Soumettre rapport exécution** | 🔴 — `TaskReport` modèle orphelin, aucune route |
| **Rapport performance/agent** | 🔴 |

### MODULE 8 — Communication — 58%

| User Story | Statut |
|-----------|--------|
| Messages + communiqués | ✅ |
| Notifications DB | ✅ |
| **Notifications mail** | 🔴 ZERO email dans tout le code |
| Messages ciblés par groupe | 🟡 |

### MODULE 9 — Notifications centralisées — 43%

| Événement | DB | Mail |
|-----------|-----|------|
| Tâche attribuée | ✅ | ❌ |
| Validation demande | ✅ | ❌ |
| Message reçu | ✅ | ❌ |
| Communiqué | ✅ | ❌ |
| Demande formation → Renforcement | ❌ | ❌ |
| Signalement → Juridique | ❌ | ❌ |
| Modification PTA → Planification | ❌ | ❌ |
| Conflit congé/PTA | ❌ | ❌ |

---

## 🔧 PROBLÈME TRANSVERSAL : PERMISSIONS NON WIRED

Les ~75 permissions sont définies en DB (migration gap) mais **1 seule est vérifiée dans le code** (`DemandeWorkflowService.canValidate()`). Tous les contrôleurs utilisent `hasRole('...')` en dur.

**Bug PermissionMiddleware** : cherche `nom_permission` au lieu de `code`.
**Incohérence** : `User.hasAdminAccess()` inclut SEN, `Agent.hasAdminAccess()` ne l'inclut pas.

---

## 🔴 15 ÉCARTS CRITIQUES CLASSÉS

| # | Écart | Module | Priorité | Effort |
|---|-------|--------|----------|--------|
| E1 | Permissions non wired dans contrôleurs | Transversal | P1 | Moyen |
| E2 | Module Renforcement (0% logique métier) | 3 | P1 | Élevé |
| E3 | Détection conflit Congé↔PTA | 6 | P1 | Moyen |
| E4 | Events/Listeners (0 sur ~10 requis) | Transversal | P1 | Élevé |
| E5 | Notifications mail (0%) | 8/9 | P1 | Moyen |
| E6 | Rapports (0/6 types) | 3/5/7 | P1 | Élevé |
| E7 | Validation PTA section→cellule | 4 | P2 | Moyen |
| E8 | Task Reports (modèle orphelin) | 7 | P2 | Faible |
| E9 | CongeRegleDepartement non appliqué | 6 | P2 | Faible |
| E10 | Bug PermissionMiddleware | Transversal | P2 | Faible |
| E11 | User/Agent hasAdminAccess incohérent | 1 | P2 | Faible |
| E12 | Scope département pour Directeur | 2 | P3 | Faible |
| E13 | Vue combinée congés + PTA | 6 | P3 | Faible |
| E14 | Messages ciblés par groupe | 8 | P3 | Faible |
| E15 | Policies Laravel | Transversal | P3 | Moyen |

---

## 📊 COUVERTURE FINALE PAR MODULE

| Module | US Total | ✅ | 🟡 | 🔴 | % |
|--------|----------|---|---|---|---|
| 1. Auth & Profil | 6 | 6 | 0 | 0 | **100%** |
| 2. Demandes | 8 | 5 | 1 | 2 | **69%** |
| 3. Renforcement | 7 | 0 | 0 | 7 | **0%** |
| 4. PTA | 9 | 4 | 3 | 2 | **56%** |
| 5. Signalements | 8 | 4 | 2 | 2 | **63%** |
| 6. Congés | 15 | 9 | 3 | 3 | **70%** |
| 7. Tâches | 9 | 5 | 1 | 3 | **61%** |
| 8. Communication | 6 | 3 | 2 | 1 | **58%** |
| 9. Notifications | 7 | 3 | 0 | 4 | **43%** |
| **TOTAL** | **75** | **39** | **12** | **24** | **~57%** |

> **Conclusion** : Infrastructure solide (~80% DB/modèles). Écarts = logique métier (events, conflits, rapports) + câblage permissions. Migration `2026_04_05_200000` comble 100% rôles/permissions en définition — reste à les **utiliser dans le code**.
