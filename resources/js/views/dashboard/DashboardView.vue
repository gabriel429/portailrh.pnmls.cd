<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="dash-hero">
      <div class="dash-hero-avatar">
        <i class="fas fa-user"></i>
      </div>
      <div class="dash-hero-text">
        <h2>Bienvenue, {{ auth.agent?.prenom || auth.user?.name || 'Utilisateur' }}</h2>
        <p>{{ today }}</p>
      </div>
      <div class="dash-hero-stats">
        <div>
          <div class="dash-hero-stat-val">{{ stats.documents ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">Documents</div>
        </div>
        <div>
          <div class="dash-hero-stat-val">{{ stats.requests_pending ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">En attente</div>
        </div>
        <div>
          <div class="dash-hero-stat-val">{{ stats.requests_approved ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">Approuvees</div>
        </div>
        <div>
          <div class="dash-hero-stat-val">{{ stats.absences ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">Absences</div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement du tableau de bord..." />
    </div>

    <template v-else>
      <!-- Quick Actions -->
      <div class="dash-section-header">
        <div class="dash-section-title">
          <i class="fas fa-bolt" style="color:#0077B5;"></i>
          Actions rapides
        </div>
      </div>
      <div class="dash-action-grid">
        <router-link
          v-for="action in quickActions"
          :key="action.to"
          :to="action.to"
          class="dash-action-card"
        >
          <div class="dash-action-icon" :style="{ background: action.bg, color: action.color }">
            <i class="fas" :class="action.icon"></i>
          </div>
          <div class="dash-action-info">
            <div class="dash-action-name">{{ action.label }}</div>
            <div class="dash-action-desc">{{ action.desc }}</div>
          </div>
        </router-link>
      </div>

      <!-- Stat Cards -->
      <div class="dash-section-header">
        <div class="dash-section-title">
          <i class="fas fa-chart-bar" style="color:#0077B5;"></i>
          Mes statistiques
        </div>
      </div>
      <div class="dash-stat-grid">
        <div v-for="card in statCards" :key="card.label" class="dash-stat-card">
          <div class="dash-stat-top">
            <div class="dash-stat-icon" :style="{ background: card.bg, color: card.color }">
              <i class="fas" :class="card.icon"></i>
            </div>
            <div class="dash-stat-info">
              <div class="dash-stat-val">{{ card.value }}</div>
              <div class="dash-stat-lbl">{{ card.label }}</div>
            </div>
          </div>
          <div class="dash-stat-bar">
            <div class="dash-stat-bar-fill" :style="{ background: card.color, width: card.pct + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="dash-section-header">
        <div class="dash-section-title">
          <i class="fas fa-history" style="color:#0077B5;"></i>
          Activite recente
        </div>
      </div>
      <div v-if="activities.length" class="dash-activity-grid">
        <div v-for="(activity, index) in activities" :key="index" class="dash-activity-card">
          <div class="dash-activity-top">
            <div class="dash-activity-icon" :class="'dash-act-' + (activity.type || 'default')">
              <i class="fas" :class="activityIcon(activity.type)"></i>
            </div>
            <div class="dash-activity-info">
              <div class="dash-activity-text">{{ activity.description }}</div>
              <div class="dash-activity-time"><i class="fas fa-clock me-1"></i>{{ formatTime(activity.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="dash-empty">
        <div class="dash-empty-icon"><i class="fas fa-inbox"></i></div>
        <h5>Aucune activite recente</h5>
        <p>Vos dernieres actions et notifications apparaitront ici.</p>
        <router-link to="/requests/create" class="dash-action-btn">
          <i class="fas fa-plus me-1"></i>Nouvelle demande
        </router-link>
      </div>

      <!-- Info Cards -->
      <div class="dash-info-grid">
        <div class="dash-info-card">
          <div class="dash-info-top">
            <div class="dash-info-icon" style="background:#e0f2fe;color:#0077B5;">
              <i class="fas fa-info-circle"></i>
            </div>
            <div class="dash-info-content">
              <div class="dash-info-title">Besoin d'aide ?</div>
              <div class="dash-info-text">Pour toute question relative a votre dossier RH, contactez la Section Ressources Humaines ou consultez les documents de travail.</div>
            </div>
          </div>
          <div class="dash-info-footer">
            <router-link to="/documents-travail" class="dash-info-link">
              <i class="fas fa-file-invoice me-1"></i> Documents de travail
            </router-link>
          </div>
        </div>
        <div class="dash-info-card">
          <div class="dash-info-top">
            <div class="dash-info-icon" style="background:#fef3c7;color:#d97706;">
              <i class="fas fa-bell"></i>
            </div>
            <div class="dash-info-content">
              <div class="dash-info-title">Notifications</div>
              <div class="dash-info-text">Restez informe des mises a jour sur vos demandes, documents et communiques importants de l'organisation.</div>
            </div>
          </div>
          <div class="dash-info-footer">
            <router-link to="/notifications" class="dash-info-link">
              <i class="fas fa-bell me-1"></i> Voir mes notifications
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const auth = useAuthStore()
const loading = ref(true)
const stats = ref({})
const activities = ref([])

const today = computed(() => {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

const quickActions = [
  { to: '/requests/create', label: 'Nouvelle demande', desc: 'Conge, attestation...', icon: 'fa-plus-circle', color: '#0077B5', bg: '#e0f2fe' },
  { to: '/documents/create', label: 'Uploader document', desc: 'Ajouter un fichier', icon: 'fa-cloud-upload-alt', color: '#059669', bg: '#d1fae5' },
  { to: '/plan-travail', label: 'Plan de travail', desc: 'Consulter ou creer', icon: 'fa-tasks', color: '#d97706', bg: '#fef3c7' },
  { to: '/profile', label: 'Mon profil', desc: 'Voir mes infos', icon: 'fa-user-circle', color: '#7c3aed', bg: '#ede9fe' },
]

const maxStat = computed(() => {
  const vals = [
    stats.value.documents ?? 0,
    stats.value.requests_pending ?? 0,
    stats.value.requests_approved ?? 0,
    stats.value.absences ?? 0,
  ]
  return Math.max(...vals, 1)
})

const statCards = computed(() => [
  { label: 'Documents', value: stats.value.documents ?? 0, icon: 'fa-folder-open', color: '#0077B5', bg: '#e0f2fe', pct: ((stats.value.documents ?? 0) / maxStat.value) * 100 },
  { label: 'Demandes en attente', value: stats.value.requests_pending ?? 0, icon: 'fa-clock', color: '#d97706', bg: '#fef3c7', pct: ((stats.value.requests_pending ?? 0) / maxStat.value) * 100 },
  { label: 'Demandes approuvees', value: stats.value.requests_approved ?? 0, icon: 'fa-check-circle', color: '#059669', bg: '#d1fae5', pct: ((stats.value.requests_approved ?? 0) / maxStat.value) * 100 },
  { label: 'Absences', value: stats.value.absences ?? 0, icon: 'fa-calendar-times', color: '#dc2626', bg: '#fee2e2', pct: ((stats.value.absences ?? 0) / maxStat.value) * 100 },
])

function activityIcon(type) {
  const map = { request: 'fa-paper-plane', document: 'fa-file-alt', absence: 'fa-calendar-times', approval: 'fa-check-circle' }
  return map[type] || 'fa-bell'
}

function formatTime(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1) return "A l'instant"
  if (diff < 60) return `Il y a ${diff} min`
  if (diff < 1440) return `Il y a ${Math.floor(diff / 60)}h`
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

onMounted(async () => {
  try {
    const { data } = await client.get('/dashboard')
    stats.value = data.stats || data
    activities.value = data.activities || []
  } catch {
    // API not yet available
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* Hero */
.dash-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #004165 100%);
  border-radius: 18px; padding: 2rem 2.2rem; margin-bottom: 1.5rem; color: #fff;
  position: relative; overflow: hidden;
  display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}
.dash-hero::before {
  content: ''; position: absolute; top: -40%; right: -8%;
  width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.07);
}
.dash-hero-avatar {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(255,255,255,.18); display: flex; align-items: center;
  justify-content: center; font-size: 1.3rem; flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.15);
}
.dash-hero-text { flex: 1; min-width: 150px; }
.dash-hero-text h2 { font-size: 1.3rem; font-weight: 700; margin: 0 0 .2rem; }
.dash-hero-text p { font-size: .82rem; opacity: .7; margin: 0; text-transform: capitalize; }
.dash-hero-stats { display: flex; gap: 1.5rem; margin-left: auto; }
.dash-hero-stat-val { font-size: 1.4rem; font-weight: 800; text-align: center; }
.dash-hero-stat-lbl { font-size: .68rem; opacity: .7; text-transform: uppercase; letter-spacing: .5px; text-align: center; }

/* Section headers */
.dash-section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: .8rem; padding-bottom: .5rem; border-bottom: 2px solid #f3f4f6;
}
.dash-section-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: .5rem; }

/* Quick Actions */
.dash-action-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .8rem; margin-bottom: 1.5rem; }
.dash-action-card {
  display: flex; align-items: center; gap: .7rem; padding: .9rem 1rem;
  background: #fff; border: 2px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; cursor: pointer;
}
.dash-action-card:hover { border-color: #0077B5; color: #0077B5; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,119,181,.1); }
.dash-action-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
  justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.dash-action-info { flex: 1; min-width: 0; }
.dash-action-name { font-size: .82rem; font-weight: 700; line-height: 1.2; }
.dash-action-desc { font-size: .7rem; opacity: .6; }

/* Stat Cards */
.dash-stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.dash-stat-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); padding: 1.2rem; transition: all .2s;
}
.dash-stat-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dash-stat-top { display: flex; align-items: center; gap: .8rem; margin-bottom: .8rem; }
.dash-stat-icon {
  width: 44px; height: 44px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.dash-stat-info { flex: 1; min-width: 0; }
.dash-stat-val { font-size: 1.6rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
.dash-stat-lbl { font-size: .72rem; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.dash-stat-bar { height: 4px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
.dash-stat-bar-fill { height: 100%; border-radius: 4px; transition: width .6s ease; min-width: 4px; }

/* Activity */
.dash-activity-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.dash-activity-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); padding: 1rem 1.2rem; transition: all .2s;
}
.dash-activity-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dash-activity-top { display: flex; align-items: flex-start; gap: .8rem; }
.dash-activity-icon {
  width: 38px; height: 38px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0;
}
.dash-act-request { background: #e0f2fe; color: #0077B5; }
.dash-act-document { background: #d1fae5; color: #059669; }
.dash-act-absence { background: #fee2e2; color: #dc2626; }
.dash-act-approval { background: #fef3c7; color: #d97706; }
.dash-act-default { background: #f1f5f9; color: #64748b; }
.dash-activity-info { flex: 1; min-width: 0; }
.dash-activity-text { font-size: .85rem; font-weight: 600; color: #1e293b; line-height: 1.3; margin-bottom: .3rem; }
.dash-activity-time { font-size: .72rem; color: #9ca3af; }

/* Empty */
.dash-empty { text-align: center; padding: 3rem 1rem; color: #9ca3af; margin-bottom: 1.5rem; }
.dash-empty-icon {
  width: 64px; height: 64px; border-radius: 50%; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; color: #d1d5db;
}
.dash-action-btn {
  display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .9rem;
  border-radius: 8px; font-size: .8rem; font-weight: 600; background: #e0f2fe;
  color: #0077B5; text-decoration: none; border: 1px solid #bae6fd; transition: all .2s;
}
.dash-action-btn:hover { background: #0077B5; color: #fff; border-color: #0077B5; }

/* Info Cards */
.dash-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
.dash-info-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); overflow: hidden; transition: all .2s;
  display: flex; flex-direction: column;
}
.dash-info-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dash-info-top { display: flex; align-items: flex-start; gap: .8rem; padding: 1.2rem 1.2rem .6rem; }
.dash-info-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.dash-info-content { flex: 1; min-width: 0; }
.dash-info-title { font-weight: 700; font-size: .9rem; color: #1e293b; margin-bottom: .3rem; }
.dash-info-text { font-size: .78rem; color: #9ca3af; line-height: 1.5; }
.dash-info-footer {
  border-top: 1px solid #f3f4f6; padding: .7rem 1.2rem;
  margin-top: auto;
}
.dash-info-link {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .78rem; font-weight: 600; color: #0077B5; text-decoration: none; transition: all .2s;
}
.dash-info-link:hover { color: #005a87; }

/* Responsive */
@media (max-width: 576px) {
  .dash-hero { flex-direction: column; align-items: flex-start; padding: 1.5rem 1.2rem; }
  .dash-hero-stats { margin-left: 0; margin-top: .5rem; gap: 1rem; flex-wrap: wrap; }
  .dash-hero-stat-val { font-size: 1.1rem; }
  .dash-action-grid { grid-template-columns: repeat(2, 1fr); }
  .dash-stat-grid { grid-template-columns: repeat(2, 1fr); }
  .dash-activity-grid { grid-template-columns: 1fr; }
  .dash-info-grid { grid-template-columns: 1fr; }
}
</style>
