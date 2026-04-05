<template>
  <div class="admin-dashboard">
    <!-- Hero Header -->
    <div class="dash-hero mb-4">
      <div class="dash-hero-content">
        <div class="dash-hero-icon">
          <i class="fas fa-cogs"></i>
        </div>
        <div>
          <h4 class="mb-1 fw-bold text-white">Tableau de bord Administration</h4>
          <p class="mb-0 text-white-50">Vue d'ensemble de la configuration du systeme</p>
        </div>
      </div>
      <div class="dash-hero-date text-white-50">
        <i class="fas fa-calendar-alt me-1"></i>{{ today }}
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-grow text-primary" style="width:3rem;height:3rem;"></div>
      <p class="mt-3 text-muted fw-semibold">Chargement du tableau de bord...</p>
    </div>

    <template v-else>
      <!-- Stats Cards -->
      <div class="row g-3 mb-4">
        <div v-for="item in statCards" :key="item.label" class="col-6 col-md-4 col-lg-3 col-xl-2">
          <div class="stat-card" :style="{ '--accent': item.color }">
            <div class="stat-card-icon" :style="{ background: item.color + '15', color: item.color }">
              <i :class="['fas', item.icon]"></i>
            </div>
            <div class="stat-card-value">{{ item.value }}</div>
            <div class="stat-card-label">{{ item.label }}</div>
          </div>
        </div>
      </div>

      <!-- Stats par Organe -->
      <div v-if="statsByOrgane.length" class="mb-4">
        <div class="section-header mb-3">
          <i class="fas fa-sitemap me-2 text-primary"></i>
          <span>Repartition par Organe</span>
        </div>
        <div class="row g-3">
          <div v-for="organe in statsByOrgane" :key="organe.code" class="col-md-4">
            <div class="organe-card" :style="{ '--organe-color': organe.color, '--organe-bg': organe.bgColor }">
              <div class="organe-card-header">
                <div class="organe-icon" :style="{ background: organe.color + '20', color: organe.color }">
                  <i :class="['fas', organe.icon]"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0 fw-bold">{{ organe.nom }}</h6>
                </div>
                <span class="organe-badge" :style="{ background: organe.color }">{{ organe.sigle }}</span>
              </div>
              <div class="organe-stats">
                <div class="organe-stat-item">
                  <div class="organe-stat-value" :style="{ color: organe.color }">{{ organe.agents }}</div>
                  <div class="organe-stat-label">Agents</div>
                </div>
                <div class="organe-stat-divider"></div>
                <div class="organe-stat-item">
                  <div class="organe-stat-value" :style="{ color: organe.color }">{{ organe.fonctions }}</div>
                  <div class="organe-stat-label">Fonctions</div>
                </div>
                <div class="organe-stat-divider"></div>
                <div class="organe-stat-item">
                  <div class="organe-stat-value" :style="{ color: organe.color }">{{ organe.affectations }}</div>
                  <div class="organe-stat-label">Affectations</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Utilisateurs connectes -->
      <div class="mb-4">
        <div class="section-header mb-3">
          <i class="fas fa-users me-2 text-success"></i>
          <span>Utilisateurs connectes</span>
          <span class="connected-badge">{{ connectedUsers.length }} en ligne</span>
        </div>
        <div v-if="connectedUsers.length" class="card border-0 shadow-sm overflow-hidden" style="border-radius:14px;">
          <div class="table-responsive admin-users-table-wrap">
            <table class="table table-hover mb-0 align-middle admin-users-table">
              <thead>
                <tr style="background:#f8fafc;">
                  <th class="border-0 text-muted small fw-semibold py-3 ps-4">Nom</th>
                  <th class="border-0 text-muted small fw-semibold py-3">Role</th>
                  <th class="border-0 text-muted small fw-semibold py-3">Province</th>
                  <th class="border-0 text-muted small fw-semibold py-3">Derniere activite</th>
                  <th v-if="auth.isSuperAdmin" class="border-0 text-muted small fw-semibold py-3">Appareil</th>
                  <th class="border-0 text-muted small fw-semibold py-3 pe-4">IP</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="u in connectedUsers" :key="u.id">
                  <td class="ps-4" data-label="Nom">
                    <div class="d-flex align-items-center gap-2">
                      <div class="user-avatar-sm">{{ getInitials(u.nom_complet) }}</div>
                      <span class="fw-semibold">{{ u.nom_complet }}</span>
                    </div>
                  </td>
                  <td data-label="Role"><span class="role-pill">{{ u.role }}</span></td>
                  <td class="text-muted" data-label="Province">{{ u.province || '-' }}</td>
                  <td data-label="Derniere activite">
                    <span class="activity-indicator">
                      <span class="activity-dot"></span>
                      {{ formatTime(u.last_activity) }}
                    </span>
                  </td>
                  <td v-if="auth.isSuperAdmin" data-label="Appareil">
                    <span class="device-badge" :class="u.device_type === 'Telephone' ? 'device-mobile' : u.device_type === 'Tablette' ? 'device-tablet' : 'device-desktop'">
                      <i class="fas me-1" :class="u.device_type === 'Telephone' ? 'fa-mobile-alt' : u.device_type === 'Tablette' ? 'fa-tablet-alt' : 'fa-desktop'"></i>
                      {{ u.device_model || u.device_type }}
                    </span>
                  </td>
                  <td class="pe-4" data-label="IP"><code class="ip-badge">{{ u.ip_address }}</code></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-else class="empty-state">
          <i class="fas fa-user-clock"></i>
          <p>Aucun utilisateur connecte actuellement</p>
        </div>
      </div>

      <!-- Acces rapide -->
      <div class="section-header mb-3">
        <i class="fas fa-bolt me-2 text-warning"></i>
        <span>Acces rapide</span>
      </div>
      <div class="row g-3">
        <div v-for="link in quickLinks" :key="link.to" class="col-12 col-sm-6 col-md-4 col-lg-3">
          <router-link :to="link.to" class="quick-link-card">
            <div class="quick-link-icon" :style="{ background: link.color + '12', color: link.color }">
              <i :class="['fas', link.icon]"></i>
            </div>
            <div class="quick-link-label">{{ link.label }}</div>
            <i class="fas fa-chevron-right quick-link-arrow"></i>
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const loading = ref(true)
const stats = ref({})
const statsByOrgane = ref([])
const connectedUsers = ref([])

const today = new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
})

const statCards = computed(() => [
  { label: 'Agents', value: stats.value.agents ?? 0, icon: 'fa-users', color: '#0077B5' },
  { label: 'Utilisateurs', value: stats.value.users ?? 0, icon: 'fa-user-shield', color: '#059669' },
  { label: 'Provinces', value: stats.value.provinces ?? 0, icon: 'fa-map', color: '#d97706' },
  { label: 'Departements', value: stats.value.departments ?? 0, icon: 'fa-building', color: '#7c3aed' },
  { label: 'Sections', value: stats.value.sections ?? 0, icon: 'fa-layer-group', color: '#0ea5e9' },
  { label: 'Cellules', value: stats.value.cellules ?? 0, icon: 'fa-th', color: '#6366f1' },
  { label: 'Fonctions', value: stats.value.fonctions ?? 0, icon: 'fa-briefcase', color: '#f43f5e' },
  { label: 'Grades', value: stats.value.grades ?? 0, icon: 'fa-medal', color: '#eab308' },
  { label: 'Roles', value: stats.value.roles ?? 0, icon: 'fa-user-tag', color: '#14b8a6' },
  { label: 'Organes', value: stats.value.organes ?? 0, icon: 'fa-sitemap', color: '#8b5cf6' },
  { label: 'Localites', value: stats.value.localites ?? 0, icon: 'fa-map-pin', color: '#0d9488' },
  { label: 'Permissions', value: stats.value.permissions ?? 0, icon: 'fa-key', color: '#64748b' },
])

const quickLinks = [
  { to: '/plan-travail', label: 'PTA', icon: 'fa-calendar-check', color: '#0077B5' },
  { to: '/admin/utilisateurs', label: 'Utilisateurs', icon: 'fa-user-shield', color: '#059669' },
  { to: '/admin/agents/import', label: 'Import agents', icon: 'fa-file-import', color: '#2563eb' },
  { to: '/admin/organes', label: 'Organes', icon: 'fa-sitemap', color: '#8b5cf6' },
  { to: '/admin/provinces', label: 'Provinces', icon: 'fa-map', color: '#d97706' },
  { to: '/admin/departments', label: 'Departements', icon: 'fa-building', color: '#7c3aed' },
  { to: '/admin/fonctions', label: 'Fonctions', icon: 'fa-briefcase', color: '#f43f5e' },
  { to: '/admin/grades', label: 'Grades', icon: 'fa-medal', color: '#eab308' },
  { to: '/admin/roles', label: 'Roles', icon: 'fa-user-tag', color: '#14b8a6' },
  { to: '/admin/sections', label: 'Sections', icon: 'fa-layer-group', color: '#0ea5e9' },
  { to: '/admin/cellules', label: 'Cellules', icon: 'fa-th', color: '#6366f1' },
  { to: '/admin/localites', label: 'Localites', icon: 'fa-map-pin', color: '#0d9488' },
  { to: '/admin/categories-documents', label: 'Categories Docs', icon: 'fa-tags', color: '#f59e0b' },
  { to: '/admin/deployment', label: 'Deploiement', icon: 'fa-rocket', color: '#0077B5' },
  { to: '/admin/logs', label: 'Logs systeme', icon: 'fa-file-alt', color: '#64748b' },
]

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
}

function formatTime(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1) return "A l'instant"
  if (diff < 60) return `Il y a ${diff} min`
  return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  try {
    const { data } = await client.get('/admin/dashboard')
    stats.value = data.stats || {}
    statsByOrgane.value = data.statsByOrgane || []
    connectedUsers.value = data.connectedUsers || []
  } catch (e) {
    console.error('Erreur chargement dashboard admin:', e)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* Hero */
.dash-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #003f5f 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(0, 119, 181, .25);
  position: relative;
  overflow: hidden;
}
.dash-hero::after {
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
.dash-hero-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.dash-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255,255,255,.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #fff;
  backdrop-filter: blur(4px);
}
.dash-hero-date {
  font-size: .85rem;
}

/* Section Headers */
.section-header {
  display: flex;
  align-items: center;
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
  gap: .25rem;
}
.connected-badge {
  margin-left: auto;
  background: #dcfce7;
  color: #16a34a;
  font-size: .72rem;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  letter-spacing: .3px;
}

/* Stat Cards */
.stat-card {
  background: #fff;
  border-radius: 14px;
  padding: 1.1rem .8rem;
  text-align: center;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #f1f5f9;
  transition: all .25s ease;
  position: relative;
  overflow: hidden;
}
.stat-card::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--accent);
  border-radius: 0 0 14px 14px;
  opacity: 0;
  transition: opacity .25s;
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.stat-card:hover::after {
  opacity: 1;
}
.stat-card-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto .6rem;
  font-size: .95rem;
}
.stat-card-value {
  font-size: 1.6rem;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.2;
}
.stat-card-label {
  font-size: .75rem;
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-top: .15rem;
}

/* Organe Cards */
.organe-card {
  background: var(--organe-bg, #f8fafc);
  border-radius: 14px;
  padding: 1.25rem;
  border: 1px solid #f1f5f9;
  border-left: 4px solid var(--organe-color);
  box-shadow: 0 2px 12px rgba(0,0,0,.05);
  transition: transform .2s, box-shadow .2s;
  height: 100%;
}
.organe-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,.08);
}
.organe-card-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  margin-bottom: 1rem;
}
.organe-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .9rem;
  flex-shrink: 0;
}
.organe-badge {
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 6px;
  letter-spacing: .5px;
}
.organe-stats {
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding-top: .75rem;
  border-top: 1px solid rgba(0,0,0,.06);
}
.organe-stat-item {
  text-align: center;
  flex: 1;
}
.organe-stat-value {
  font-size: 1.3rem;
  font-weight: 800;
  line-height: 1.2;
}
.organe-stat-label {
  font-size: .7rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .3px;
}
.organe-stat-divider {
  width: 1px;
  height: 28px;
  background: rgba(0,0,0,.08);
}

/* Connected Users Table */
.user-avatar-sm {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.role-pill {
  background: #eff6ff;
  color: #2563eb;
  font-size: .72rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 6px;
}
.activity-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: .82rem;
  color: #64748b;
}
.activity-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  flex-shrink: 0;
  animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50% { opacity: .4; }
}
.ip-badge {
  background: #f1f5f9;
  color: #475569;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: .75rem;
}
.device-badge {
  font-size: .73rem;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  white-space: nowrap;
}
.device-mobile {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}
.device-tablet {
  background: #ede9fe;
  color: #5b21b6;
  border: 1px solid #c4b5fd;
}
.device-desktop {
  background: #e0f2fe;
  color: #075985;
  border: 1px solid #bae6fd;
}
.empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  background: #fff;
  border-radius: 14px;
  border: 2px dashed #e2e8f0;
}
.empty-state i {
  font-size: 2rem;
  color: #cbd5e1;
  margin-bottom: .75rem;
  display: block;
}
.empty-state p {
  color: #94a3b8;
  margin: 0;
  font-weight: 500;
}

/* Quick Links */
.quick-link-card {
  display: flex;
  align-items: center;
  gap: .75rem;
  background: #fff;
  border-radius: 12px;
  padding: .85rem 1rem;
  text-decoration: none !important;
  border: 1px solid #f1f5f9;
  box-shadow: 0 2px 8px rgba(0,0,0,.04);
  transition: all .25s ease;
  height: 100%;
}
.quick-link-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,.08);
  border-color: #e2e8f0;
}
.quick-link-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .9rem;
  flex-shrink: 0;
}
.quick-link-label {
  font-weight: 600;
  color: #1e293b;
  font-size: .85rem;
  flex: 1;
}
.quick-link-arrow {
  color: #cbd5e1;
  font-size: .7rem;
  transition: transform .2s;
}
.quick-link-card:hover .quick-link-arrow {
  transform: translateX(3px);
  color: #0077B5;
}

/* Responsive */
@media (max-width: 767.98px) {
  .dash-hero {
    padding: 1.2rem;
    border-radius: 12px;
    align-items: flex-start;
  }
  .dash-hero::after {
    display: none;
  }
  .dash-hero-content {
    width: 100%;
    align-items: flex-start;
  }
  .dash-hero-icon {
    width: 42px;
    height: 42px;
    font-size: 1.1rem;
  }
  .dash-hero h4 {
    font-size: 1rem;
  }
  .dash-hero-date {
    display: none;
  }
  .section-header {
    flex-wrap: wrap;
    align-items: center;
    row-gap: .35rem;
  }
  .connected-badge {
    margin-left: 0;
  }
  .stat-card-value {
    font-size: 1.3rem;
  }
  .organe-card {
    padding: 1rem;
  }
  .organe-card-header {
    align-items: flex-start;
  }
  .organe-stats {
    gap: .5rem;
  }
  .organe-stat-value {
    font-size: 1.1rem;
  }
  .admin-users-table-wrap {
    overflow: visible;
  }
  .admin-users-table,
  .admin-users-table tbody,
  .admin-users-table tr,
  .admin-users-table td {
    display: block;
    width: 100%;
  }
  .admin-users-table thead {
    display: none;
  }
  .admin-users-table tbody {
    padding: .75rem;
    background: #f8fafc;
  }
  .admin-users-table tr {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .05);
    padding: .25rem 0;
  }
  .admin-users-table tr + tr {
    margin-top: .75rem;
  }
  .admin-users-table td {
    position: relative;
    padding: .7rem .9rem .7rem 7rem !important;
    min-height: 44px;
    border: 0;
    white-space: normal;
  }
  .admin-users-table td::before {
    content: attr(data-label);
    position: absolute;
    left: .9rem;
    top: .7rem;
    width: 5.2rem;
    color: #64748b;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .3px;
  }
  .admin-users-table td:first-child {
    padding-top: .9rem !important;
  }
  .admin-users-table td:last-child {
    padding-bottom: .9rem !important;
  }
  .admin-users-table .d-flex.align-items-center.gap-2 {
    align-items: center !important;
  }
  .device-badge {
    white-space: normal;
    display: inline-flex;
    align-items: center;
    line-height: 1.3;
  }
  .ip-badge {
    display: inline-block;
    word-break: break-all;
  }
  .quick-link-card {
    padding: .8rem .9rem;
  }
  .quick-link-label {
    font-size: .82rem;
  }
}
</style>
