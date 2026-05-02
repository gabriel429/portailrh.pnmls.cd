# SECOM Provincial Dashboard

## Overview

Le **SECOM Provincial Dashboard** est un tableau de bord spécialisé pour l'assistant du SEP (Secrétaire Exécutif Provincial) et l'assistant du CAF (Cellule Administration et Finance - RH Provincial) au niveau provincial.

Le dashboard fournit une vue consolidée des données et statistiques provinciales, permettant aux gestionnaires SECOM de suivre :
- Les agents de leur province
- Les affectations SECOM (SEP et SEL)
- Les demandes et workflows
- Les présences et absences
- Les tâches en cours et les échéances
- Les congés planifiés
- Les signalements d'abus

## Accès

### Rôles autorisés
- **SEP** - Secrétaire Exécutif Provincial
- **RH Provincial** - Cellule Administration et Finance (CAF)

### Routes API

#### SPA (Web Application)
```
GET /api/dashboard/secom
```

#### Mobile API
```
GET /api/mobile/secom/dashboard
```

## Réponse API

### Structure globale
```json
{
  "province": {
    "id": 1,
    "nom": "Province Name",
    "code": "PROV_CODE",
    "ville_secretariat": "City",
    "message": "Bienvenue au dashboard SECOM provincial"
  },
  "agents": { ... },
  "affectations": { ... },
  "requests": { ... },
  "attendance": { ... },
  "my_tasks": [ ... ],
  "upcoming_deadlines": [ ... ],
  "communiques": [ ... ],
  "holiday_planning": { ... },
  "signalements": { ... },
  "recent_activities": [ ... ]
}
```

### Détails des sections

#### 1. Province Info
```json
{
  "id": 1,
  "nom": "Kasai",
  "code": "KAS",
  "ville_secretariat": "Kananga",
  "message": "Bienvenue au dashboard SECOM provincial"
}
```

#### 2. Agent Statistics
```json
{
  "agents": {
    "total": 150,
    "actifs": 140,
    "suspendu": 5,
    "anciens": 5,
    "by_sexe": {
      "M": 85,
      "F": 55
    },
    "by_organe": {
      "Secrétariat Exécutif Provincial": {"total": 25, "actifs": 24},
      "Secrétariat Exécutif Local": {"total": 125, "actifs": 116}
    },
    "new_this_month": 3
  }
}
```

#### 3. SECOM Affectations
```json
{
  "affectations": {
    "total": 15,
    "by_level": {
      "Secrétariat Exécutif Provincial": 10,
      "Secrétariat Exécutif Local": 5
    },
    "list": [
      {
        "id": 1,
        "agent_name": "Jean Paul Mutua",
        "agent_email": "jean.paul@example.com",
        "fonction": "Coordinator",
        "niveau": "Secrétariat Exécutif Provincial",
        "department": "RH",
        "date_debut": "2025-01-15",
        "date_fin": null
      }
    ]
  }
}
```

#### 4. Requests/Demandes
```json
{
  "requests": {
    "total": 87,
    "pending": 12,
    "approved": 65,
    "rejected": 10,
    "by_type": {
      "congé": 45,
      "mission": 32,
      "formation": 10
    },
    "this_month": 8,
    "recent": [
      {
        "id": 1,
        "agent": "Marie Kabamba",
        "type": "congé",
        "statut": "en_attente",
        "validated_at_rh": "Non",
        "validated_at_sep": "Non",
        "created_at": "2025-05-01 10:30"
      }
    ]
  }
}
```

#### 5. Attendance/Presence
```json
{
  "attendance": {
    "total_active_agents": 140,
    "today_present": 125,
    "today_rate": 89.3,
    "weekly_presence": [
      {
        "date": "2025-04-28",
        "present": 120,
        "rate": 85.7
      }
    ],
    "monthly_rate": 87.5
  }
}
```

#### 6. My Tasks
```json
{
  "my_tasks": [
    {
      "id": 1,
      "titre": "Validate holiday planning",
      "statut": "en_cours",
      "pourcentage": 75,
      "date_echeance": "2025-05-10",
      "priorite": "haute",
      "is_overdue": false
    }
  ]
}
```

#### 7. Upcoming Deadlines
```json
{
  "upcoming_deadlines": [
    {
      "id": 1,
      "titre": "Review monthly reports",
      "agent": "Jean Paul Mutua",
      "date_echeance": "2025-05-05",
      "priorite": "moyenne",
      "statut": "en_cours"
    }
  ]
}
```

#### 8. Holiday Planning
```json
{
  "holiday_planning": {
    "total": 5,
    "list": [
      {
        "id": 1,
        "agent": "Marie Kabamba",
        "type": "congé_annuel",
        "date_debut": "2025-05-10",
        "date_fin": "2025-05-20",
        "statut": "approuvé"
      }
    ]
  }
}
```

#### 9. Signalements (Abuse Reports)
```json
{
  "signalements": {
    "total": 3,
    "by_severity": {
      "critique": 0,
      "haute": 1,
      "moyenne": 2
    },
    "list": [
      {
        "id": 1,
        "type": "harcèlement",
        "statut": "en_cours",
        "severite": "haute",
        "is_anonymous": false,
        "created_at": "2025-04-28 14:30"
      }
    ]
  }
}
```

#### 10. Recent Activities
```json
{
  "recent_activities": [
    {
      "type": "demande",
      "description": "Demande de congé — approuvé",
      "link": "/requests/1",
      "created_at": "2025-05-01 09:15"
    },
    {
      "type": "document",
      "description": "Document ajouté : monthly_report_may.pdf",
      "link": "/documents",
      "created_at": "2025-05-01 08:00"
    }
  ]
}
```

## Scoping Behavior

### Provincial Scope
Le dashboard applique automatiquement un filtre provincial :
- Seul l'utilisateur voir les données de **sa province**
- Les agents, affectations, demandes, pointages etc. sont filtrés par `province_id`
- Si l'utilisateur n'a pas de province associée, le dashboard retourne une erreur 403

### Organe Filter
- Les agents par organe sont limités à :
  - Secrétariat Exécutif Provincial (SEP)
  - Secrétariat Exécutif Local (SEL)
- Les affectations SECOM incluent SEP et SEL

## Usage Examples

### Vue.js Component
```vue
<template>
  <div class="secom-dashboard">
    <h1>{{ province.nom }} - Dashboard SECOM</h1>
    
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Agents Actifs</h3>
        <p>{{ agents.actifs }} / {{ agents.total }}</p>
      </div>
      <div class="stat-card">
        <h3>Présence Aujourd'hui</h3>
        <p>{{ attendance.today_rate }}%</p>
      </div>
      <div class="stat-card">
        <h3>Demandes en Attente</h3>
        <p>{{ requests.pending }}</p>
      </div>
    </div>
    
    <section>
      <h2>Affectations SECOM</h2>
      <table>
        <tr v-for="aff in affectations.list" :key="aff.id">
          <td>{{ aff.agent_name }}</td>
          <td>{{ aff.fonction }}</td>
          <td>{{ aff.niveau }}</td>
        </tr>
      </table>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const dashboard = ref(null)

onMounted(async () => {
  const response = await axios.get('/api/dashboard/secom')
  dashboard.value = response.data
})

const province = computed(() => dashboard.value?.province || {})
const agents = computed(() => dashboard.value?.agents || {})
const attendance = computed(() => dashboard.value?.attendance || {})
const requests = computed(() => dashboard.value?.requests || {})
const affectations = computed(() => dashboard.value?.affectations || {})
</script>
```

### cURL Example
```bash
# Get SECOM Dashboard
curl -X GET "http://localhost:8000/api/dashboard/secom" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"

# Mobile API endpoint
curl -X GET "http://localhost:8000/api/mobile/secom/dashboard" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

## Security

- **Authentication** : Requires valid Sanctum token
- **Authorization** : Limited to users with SEP or RH Provincial roles
- **Scoping** : Automatically filters to user's province
- **Error Handling** : Returns 403 if user lacks permission or proper province scope

## Performance Considerations

- Dashboard queries use indexes on `province_id`, `agent_id`, `statut`
- Relationships are eager loaded to prevent N+1 queries
- Data is aggregated and returned as lightweight JSON
- Recent activities are limited to prevent large response payloads

## Future Enhancements

- Real-time metrics updates via WebSockets
- Export to PDF/Excel
- Custom date range filtering
- Advanced filtering and search
- Performance analytics
- Trend analysis

---

**Version** : 1.0
**Last Updated** : May 2, 2026
**Author** : PNMLS Technical Team
