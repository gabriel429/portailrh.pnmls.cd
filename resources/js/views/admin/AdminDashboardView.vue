<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="fas fa-cogs me-2"></i>Tableau de bord Parametres</h4>
        <p class="text-muted mb-0">Vue d'ensemble de la configuration du systeme</p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement...</p>
    </div>

    <template v-else>
      <!-- Stats générales -->
      <div class="row g-3 mb-4">
        <div v-for="item in statCards" :key="item.label" class="col-6 col-md-4 col-lg-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
              <div class="mb-2">
                <i :class="['fas', item.icon, 'fa-lg']" :style="{ color: item.color }"></i>
              </div>
              <h3 class="mb-0 fw-bold">{{ item.value }}</h3>
              <small class="text-muted">{{ item.label }}</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats par Organe -->
      <div v-if="statsByOrgane.length" class="mb-4">
        <h5 class="mb-3"><i class="fas fa-sitemap me-2"></i>Repartition par Organe</h5>
        <div class="row g-3">
          <div v-for="organe in statsByOrgane" :key="organe.code" class="col-md-4">
            <div class="card border-0 shadow-sm h-100" :style="{ borderLeft: '4px solid ' + organe.color + ' !important' }">
              <div class="card-body" :style="{ background: organe.bgColor }">
                <div class="d-flex align-items-center mb-3">
                  <i :class="['fas', organe.icon, 'me-2']" :style="{ color: organe.color }"></i>
                  <h6 class="mb-0 fw-bold">{{ organe.nom }}</h6>
                  <span class="badge ms-auto" :style="{ background: organe.color, color: '#fff' }">{{ organe.sigle }}</span>
                </div>
                <div class="row text-center g-2">
                  <div class="col-4">
                    <div class="fw-bold">{{ organe.agents }}</div>
                    <small class="text-muted">Agents</small>
                  </div>
                  <div class="col-4">
                    <div class="fw-bold">{{ organe.fonctions }}</div>
                    <small class="text-muted">Fonctions</small>
                  </div>
                  <div class="col-4">
                    <div class="fw-bold">{{ organe.affectations }}</div>
                    <small class="text-muted">Affect.</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Utilisateurs connectés -->
      <div class="mb-4">
        <h5 class="mb-3">
          <i class="fas fa-users me-2"></i>Utilisateurs connectes
          <span class="badge bg-success ms-2">{{ connectedUsers.length }}</span>
        </h5>
        <div v-if="connectedUsers.length" class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="text-muted small">
                <tr>
                  <th>Nom</th>
                  <th>Role</th>
                  <th>Province</th>
                  <th>Derniere activite</th>
                  <th>IP</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="u in connectedUsers" :key="u.id">
                  <td class="fw-semibold">{{ u.nom_complet }}</td>
                  <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ u.role }}</span></td>
                  <td>{{ u.province }}</td>
                  <td><small>{{ formatTime(u.last_activity) }}</small></td>
                  <td><code class="small">{{ u.ip_address }}</code></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-else class="text-muted">
          <i class="fas fa-info-circle me-1"></i>Aucun utilisateur connecte actuellement.
        </div>
      </div>

      <!-- Liens rapides -->
      <h5 class="mb-3"><i class="fas fa-link me-2"></i>Acces rapide</h5>
      <div class="row g-3">
        <div v-for="link in quickLinks" :key="link.to" class="col-6 col-md-4 col-lg-3">
          <router-link :to="link.to" class="card border-0 shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-3">
              <i :class="['fas', link.icon, 'fa-2x mb-2']" :style="{ color: link.color }"></i>
              <div class="fw-semibold text-dark">{{ link.label }}</div>
            </div>
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'

const loading = ref(true)
const stats = ref({})
const statsByOrgane = ref([])
const connectedUsers = ref([])

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
  { to: '/admin/utilisateurs', label: 'Utilisateurs', icon: 'fa-user-shield', color: '#059669' },
  { to: '/admin/organes', label: 'Organes', icon: 'fa-sitemap', color: '#8b5cf6' },
  { to: '/admin/provinces', label: 'Provinces', icon: 'fa-map', color: '#d97706' },
  { to: '/admin/departments', label: 'Departements', icon: 'fa-building', color: '#7c3aed' },
  { to: '/admin/fonctions', label: 'Fonctions', icon: 'fa-briefcase', color: '#f43f5e' },
  { to: '/admin/grades', label: 'Grades', icon: 'fa-medal', color: '#eab308' },
  { to: '/admin/roles', label: 'Roles', icon: 'fa-user-tag', color: '#14b8a6' },
  { to: '/admin/deployment', label: 'Deploiement', icon: 'fa-rocket', color: '#0077B5' },
  { to: '/admin/logs', label: 'Logs', icon: 'fa-file-alt', color: '#64748b' },
  { to: '/admin/documents-travail', label: 'Docs Travail', icon: 'fa-folder-open', color: '#0ea5e9' },
  { to: '/admin/categories-documents', label: 'Categories Docs', icon: 'fa-tags', color: '#f59e0b' },
  { to: '/admin/localites', label: 'Localites', icon: 'fa-map-pin', color: '#0d9488' },
]

function formatTime(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1) return 'A l\'instant'
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
