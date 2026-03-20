<template>
  <div class="user-dashboard">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;"></div>
      <p class="mt-3 text-muted fw-semibold">Chargement du tableau de bord...</p>
    </div>

    <template v-else>
      <!-- Hero Welcome Banner -->
      <div class="hero-banner mb-4">
        <div class="hero-pattern"></div>
        <div class="hero-content">
          <div class="hero-left">
            <div class="hero-avatar">
              <i class="fas fa-user"></i>
            </div>
            <div>
              <h3 class="hero-title mb-1">
                Bienvenue, {{ auth.agent?.prenom || auth.user?.name || 'Utilisateur' }}
              </h3>
              <p class="hero-subtitle mb-0">
                Votre espace personnel du Portail RH PNMLS
              </p>
            </div>
          </div>
          <div class="hero-right">
            <div class="hero-date">
              <i class="fas fa-calendar-alt me-2"></i>{{ today }}
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="section-header mb-3">
        <i class="fas fa-bolt me-2 text-warning"></i>
        <span>Actions rapides</span>
      </div>
      <div class="row g-3 mb-4">
        <div v-for="action in quickActions" :key="action.to" class="col-6 col-md-3">
          <router-link :to="action.to" class="action-card">
            <div class="action-icon" :style="{ background: action.color + '14', color: action.color }">
              <i :class="['fas', action.icon]"></i>
            </div>
            <div class="action-label">{{ action.label }}</div>
            <div class="action-desc">{{ action.desc }}</div>
            <i class="fas fa-arrow-right action-arrow"></i>
          </router-link>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="section-header mb-3">
        <i class="fas fa-chart-bar me-2 text-primary"></i>
        <span>Mes statistiques</span>
      </div>
      <div class="row g-3 mb-4">
        <div v-for="card in statCards" :key="card.label" class="col-6 col-lg-3">
          <div class="stat-card" :style="{ '--accent': card.color }">
            <div class="stat-card-top">
              <div class="stat-icon-circle" :style="{ background: card.bg, color: card.color }">
                <i :class="['fas', card.icon]"></i>
              </div>
              <div class="stat-trend" :style="{ color: card.color }" v-if="card.trend">
                <i :class="['fas', card.trendIcon]"></i>
                {{ card.trend }}
              </div>
            </div>
            <div class="stat-value">{{ card.value }}</div>
            <div class="stat-label">{{ card.label }}</div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="section-header mb-3">
        <i class="fas fa-history me-2 text-info"></i>
        <span>Activite recente</span>
      </div>
      <div class="activity-container">
        <!-- If activities are available from API -->
        <template v-if="activities.length">
          <div v-for="(activity, index) in activities" :key="index" class="activity-item">
            <div class="activity-dot-wrapper">
              <div class="activity-dot" :style="{ background: getActivityColor(activity.type) }"></div>
              <div v-if="index < activities.length - 1" class="activity-line"></div>
            </div>
            <div class="activity-content">
              <div class="activity-text">{{ activity.description }}</div>
              <div class="activity-time">
                <i class="fas fa-clock me-1"></i>{{ formatTime(activity.created_at) }}
              </div>
            </div>
          </div>
        </template>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <div class="empty-state-icon">
            <i class="fas fa-inbox"></i>
          </div>
          <h6 class="empty-state-title">Aucune activite recente</h6>
          <p class="empty-state-text">
            Vos dernieres actions et notifications apparaitront ici.
            Commencez par soumettre une demande ou telecharger un document.
          </p>
          <router-link to="/requests/create" class="btn btn-sm btn-primary-soft">
            <i class="fas fa-plus me-1"></i>Nouvelle demande
          </router-link>
        </div>
      </div>

      <!-- Info Cards Row -->
      <div class="row g-3 mt-3">
        <div class="col-md-6">
          <div class="info-card">
            <div class="info-card-header">
              <div class="info-card-icon" style="background: #eff6ff; color: #2563eb;">
                <i class="fas fa-info-circle"></i>
              </div>
              <h6 class="mb-0 fw-bold">Besoin d'aide ?</h6>
            </div>
            <p class="info-card-text">
              Pour toute question relative a votre dossier RH, contactez la Section
              Ressources Humaines ou consultez les documents de travail disponibles.
            </p>
            <router-link to="/documents-travail" class="info-card-link">
              <span>Voir les documents de travail</span>
              <i class="fas fa-chevron-right ms-1"></i>
            </router-link>
          </div>
        </div>
        <div class="col-md-6">
          <div class="info-card">
            <div class="info-card-header">
              <div class="info-card-icon" style="background: #fef3c7; color: #d97706;">
                <i class="fas fa-bell"></i>
              </div>
              <h6 class="mb-0 fw-bold">Notifications</h6>
            </div>
            <p class="info-card-text">
              Restez informe des mises a jour sur vos demandes, documents
              et communiques importants de l'organisation.
            </p>
            <router-link to="/notifications" class="info-card-link">
              <span>Voir mes notifications</span>
              <i class="fas fa-chevron-right ms-1"></i>
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
  {
    to: '/requests/create',
    label: 'Nouvelle demande',
    desc: 'Conge, attestation...',
    icon: 'fa-plus-circle',
    color: '#0077B5',
  },
  {
    to: '/documents/create',
    label: 'Uploader document',
    desc: 'Ajouter un fichier',
    icon: 'fa-cloud-upload-alt',
    color: '#059669',
  },
  {
    to: '/plan-travail',
    label: 'Plan de travail',
    desc: 'Consulter ou creer',
    icon: 'fa-tasks',
    color: '#d97706',
  },
  {
    to: '/profile',
    label: 'Mon profil',
    desc: 'Voir mes infos',
    icon: 'fa-user-circle',
    color: '#7c3aed',
  },
]

const statCards = computed(() => [
  {
    label: 'Documents',
    value: stats.value.documents ?? 0,
    icon: 'fa-folder-open',
    color: '#0077B5',
    bg: '#e0f2fe',
  },
  {
    label: 'Demandes en attente',
    value: stats.value.requests_pending ?? 0,
    icon: 'fa-clock',
    color: '#d97706',
    bg: '#fef3c7',
  },
  {
    label: 'Demandes approuvees',
    value: stats.value.requests_approved ?? 0,
    icon: 'fa-check-circle',
    color: '#059669',
    bg: '#d1fae5',
  },
  {
    label: 'Absences',
    value: stats.value.absences ?? 0,
    icon: 'fa-calendar-times',
    color: '#dc2626',
    bg: '#fee2e2',
  },
])

function getActivityColor(type) {
  const colors = {
    request: '#0077B5',
    document: '#059669',
    absence: '#dc2626',
    approval: '#d97706',
  }
  return colors[type] || '#94a3b8'
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
    // API not yet available, show empty
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.user-dashboard {
  padding-top: 1.5rem;
}

/* ═══════════════════════════════════════════
   Hero Welcome Banner
   ═══════════════════════════════════════════ */
.hero-banner {
  background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #004165 100%);
  border-radius: 18px;
  padding: 2rem 2.25rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 119, 181, 0.3);
}

.hero-banner::after {
    content: '';
    position: absolute;
    right: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 200px;
    height: 200px;
    background: url('/images/pnmls.jpeg') center/contain no-repeat;
    opacity: 0.10;
    pointer-events: none;
}

.hero-pattern {
  position: absolute;
  inset: 0;
  opacity: 0.07;
  background-image:
    radial-gradient(circle at 20% 50%, #fff 1px, transparent 1px),
    radial-gradient(circle at 80% 20%, #fff 1.5px, transparent 1.5px),
    radial-gradient(circle at 60% 80%, #fff 1px, transparent 1px),
    radial-gradient(circle at 40% 30%, #fff 2px, transparent 2px),
    radial-gradient(circle at 90% 60%, #fff 1px, transparent 1px);
  background-size: 100px 100px, 150px 150px, 120px 120px, 80px 80px, 200px 200px;
  pointer-events: none;
}

.hero-pattern::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.04);
}

.hero-pattern::after {
  content: '';
  position: absolute;
  bottom: -60%;
  left: -10%;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.03);
}

.hero-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.hero-left {
  display: flex;
  align-items: center;
  gap: 1.15rem;
}

.hero-avatar {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #fff;
  flex-shrink: 0;
  border: 2px solid rgba(255, 255, 255, 0.15);
}

.hero-title {
  color: #fff;
  font-weight: 700;
  font-size: 1.35rem;
  letter-spacing: -0.3px;
}

.hero-subtitle {
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.88rem;
  font-weight: 400;
}

.hero-right {
  flex-shrink: 0;
}

.hero-date {
  color: rgba(255, 255, 255, 0.55);
  font-size: 0.82rem;
  font-weight: 500;
  background: rgba(255, 255, 255, 0.1);
  padding: 0.45rem 1rem;
  border-radius: 10px;
  backdrop-filter: blur(4px);
}

/* ═══════════════════════════════════════════
   Section Headers
   ═══════════════════════════════════════════ */
.section-header {
  display: flex;
  align-items: center;
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
  gap: 0.25rem;
}

/* ═══════════════════════════════════════════
   Quick Action Cards
   ═══════════════════════════════════════════ */
.action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem 1rem 1.25rem;
  text-decoration: none !important;
  border: 1px solid #f1f5f9;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  height: 100%;
}

.action-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: currentColor;
  opacity: 0;
  transition: opacity 0.3s;
}

.action-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
  border-color: #e2e8f0;
}

.action-card:hover::before {
  opacity: 0.6;
}

.action-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  margin-bottom: 0.85rem;
  transition: transform 0.3s;
}

.action-card:hover .action-icon {
  transform: scale(1.1);
}

.action-label {
  font-weight: 700;
  color: #1e293b;
  font-size: 0.88rem;
  margin-bottom: 0.2rem;
}

.action-desc {
  font-size: 0.73rem;
  color: #94a3b8;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.action-arrow {
  color: #cbd5e1;
  font-size: 0.65rem;
  transition: all 0.3s;
}

.action-card:hover .action-arrow {
  color: #0077B5;
  transform: translateX(3px);
}

/* ═══════════════════════════════════════════
   Stat Cards
   ═══════════════════════════════════════════ */
.stat-card {
  background: #fff;
  border-radius: 16px;
  padding: 1.35rem 1.25rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  border-left: 4px solid var(--accent);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.stat-card::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: var(--accent);
  opacity: 0.04;
  transform: translate(30%, -30%);
  transition: opacity 0.3s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
}

.stat-card:hover::after {
  opacity: 0.08;
}

.stat-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.stat-icon-circle {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
}

.stat-trend {
  font-size: 0.72rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 3px;
}

.stat-value {
  font-size: 2rem;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.15;
  letter-spacing: -0.5px;
}

.stat-label {
  font-size: 0.76rem;
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-top: 0.25rem;
}

/* ═══════════════════════════════════════════
   Recent Activity
   ═══════════════════════════════════════════ */
.activity-container {
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem;
  border: 1px solid #f1f5f9;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 0.75rem 0;
}

.activity-item:first-child {
  padding-top: 0;
}

.activity-item:last-child {
  padding-bottom: 0;
}

.activity-dot-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  padding-top: 4px;
}

.activity-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.activity-line {
  width: 2px;
  flex: 1;
  background: #e2e8f0;
  margin-top: 6px;
  border-radius: 1px;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-text {
  font-size: 0.88rem;
  color: #334155;
  font-weight: 500;
  line-height: 1.45;
}

.activity-time {
  font-size: 0.73rem;
  color: #94a3b8;
  margin-top: 0.2rem;
  font-weight: 500;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 2.5rem 1.5rem;
}

.empty-state-icon {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  font-size: 1.5rem;
  color: #cbd5e1;
}

.empty-state-title {
  color: #475569;
  font-weight: 700;
  margin-bottom: 0.4rem;
}

.empty-state-text {
  color: #94a3b8;
  font-size: 0.85rem;
  max-width: 380px;
  margin: 0 auto 1.25rem;
  line-height: 1.55;
}

.btn-primary-soft {
  background: #e0f2fe;
  color: #0077B5;
  border: none;
  font-weight: 600;
  font-size: 0.82rem;
  padding: 0.5rem 1.25rem;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-primary-soft:hover {
  background: #bae6fd;
  color: #005a87;
}

/* ═══════════════════════════════════════════
   Info Cards
   ═══════════════════════════════════════════ */
.info-card {
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem;
  border: 1px solid #f1f5f9;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
  height: 100%;
  transition: all 0.25s ease;
}

.info-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}

.info-card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.85rem;
}

.info-card-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
}

.info-card-text {
  font-size: 0.84rem;
  color: #64748b;
  line-height: 1.55;
  margin-bottom: 0.85rem;
}

.info-card-link {
  display: inline-flex;
  align-items: center;
  font-size: 0.82rem;
  font-weight: 600;
  color: #0077B5;
  text-decoration: none;
  transition: all 0.2s;
}

.info-card-link:hover {
  color: #005a87;
  gap: 2px;
}

.info-card-link i {
  font-size: 0.65rem;
  transition: transform 0.2s;
}

.info-card-link:hover i {
  transform: translateX(3px);
}

/* ═══════════════════════════════════════════
   Responsive
   ═══════════════════════════════════════════ */
@media (max-width: 767.98px) {
  .hero-banner {
    padding: 1.5rem 1.25rem;
    border-radius: 14px;
  }

  .hero-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    font-size: 1.1rem;
  }

  .hero-title {
    font-size: 1.1rem;
  }

  .hero-subtitle {
    font-size: 0.8rem;
  }

  .hero-right {
    display: none;
  }

  .action-card {
    padding: 1.15rem 0.75rem 1rem;
    border-radius: 14px;
  }

  .action-icon {
    width: 42px;
    height: 42px;
    font-size: 1.05rem;
    margin-bottom: 0.6rem;
  }

  .action-label {
    font-size: 0.8rem;
  }

  .action-desc {
    display: none;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .stat-label {
    font-size: 0.68rem;
  }

  .stat-icon-circle {
    width: 36px;
    height: 36px;
    font-size: 0.9rem;
  }
}

@media (max-width: 575.98px) {
  .hero-left {
    gap: 0.85rem;
  }

  .action-arrow {
    display: none;
  }
}
</style>
