<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="req-hero">
      <div class="req-hero-body">
        <div class="req-hero-text">
          <h2>
            <i class="fas fa-tasks me-2" v-if="isRH"></i>
            <i class="fas fa-paper-plane me-2" v-else></i>
            {{ isRH ? 'Gestion des demandes' : 'Mes Demandes' }}
          </h2>
          <p v-if="isRH">Suivi, validation et historisation des demandes des agents.</p>
          <p v-else>Suivez l'etat de vos demandes de conge, absence et permission.</p>
          <div class="req-hero-stats">
            <div>
              <div class="req-hero-stat-val">{{ meta.total }}</div>
              <div class="req-hero-stat-lbl">Demandes</div>
            </div>
          </div>
        </div>
        <div class="req-hero-actions">
          <router-link :to="{ name: 'requests.create' }" class="req-hero-btn">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle demande
          </router-link>
          <!-- Type filter for RH -->
          <div v-if="isRH" class="req-type-filter">
            <label class="req-type-label">Type</label>
            <select v-model="filters.type" class="req-type-select" @change="loadRequests(1)">
              <option value="">Tous les types</option>
              <option value="conge">Conge</option>
              <option value="absence">Absence</option>
              <option value="permission">Permission</option>
              <option value="formation">Formation</option>
              <option value="renforcement_capacites">Renforcement</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="text-muted mt-2">Chargement des demandes...</p>
    </div>

    <template v-else>
      <!-- Status filter cards -->
      <div class="req-filter-grid">
        <button
          class="req-filter-card req-filter-all"
          :class="{ active: !filters.statut }"
          @click="setStatut('')"
        >
          <div class="req-filter-icon"><i class="fas fa-th-large"></i></div>
          <div class="req-filter-info">
            <div class="req-filter-name">Toutes</div>
            <div class="req-filter-count">{{ meta.total }} demande{{ meta.total > 1 ? 's' : '' }}</div>
          </div>
        </button>
        <button
          class="req-filter-card req-filter-warning"
          :class="{ active: filters.statut === 'en_attente' }"
          @click="setStatut('en_attente')"
        >
          <div class="req-filter-icon"><i class="fas fa-hourglass-half"></i></div>
          <div class="req-filter-info">
            <div class="req-filter-name">En attente</div>
            <div class="req-filter-count">A traiter</div>
          </div>
        </button>
        <button
          class="req-filter-card req-filter-success"
          :class="{ active: filters.statut === 'approuvé' }"
          @click="setStatut('approuvé')"
        >
          <div class="req-filter-icon"><i class="fas fa-check-circle"></i></div>
          <div class="req-filter-info">
            <div class="req-filter-name">Approuvee</div>
            <div class="req-filter-count">Validees</div>
          </div>
        </button>
        <button
          class="req-filter-card req-filter-danger"
          :class="{ active: filters.statut === 'rejeté' }"
          @click="setStatut('rejeté')"
        >
          <div class="req-filter-icon"><i class="fas fa-times-circle"></i></div>
          <div class="req-filter-info">
            <div class="req-filter-name">Rejetee</div>
            <div class="req-filter-count">Refusees</div>
          </div>
        </button>
        <button
          class="req-filter-card req-filter-secondary"
          :class="{ active: filters.statut === 'annulé' }"
          @click="setStatut('annulé')"
        >
          <div class="req-filter-icon"><i class="fas fa-ban"></i></div>
          <div class="req-filter-info">
            <div class="req-filter-name">Annulee</div>
            <div class="req-filter-count">Annulees</div>
          </div>
        </button>
      </div>

      <!-- Section header when filtered -->
      <div v-if="filters.statut" class="req-section-header">
        <div class="req-section-title">
          <i :class="statusIcon(filters.statut)" :style="{ color: statusColor(filters.statut) }"></i>
          {{ statusLabel(filters.statut) }}
          <span class="req-section-badge">{{ meta.total }} demande{{ meta.total > 1 ? 's' : '' }}</span>
        </div>
        <button class="req-back-btn" @click="setStatut('')">
          <i class="fas fa-arrow-left"></i> Tous les statuts
        </button>
      </div>

      <!-- Request cards grid -->
      <div v-if="requests.length" class="req-grid" :class="{ 'req-filtering': filtering }">
        <div v-for="req in requests" :key="req.id" class="req-card">
          <div class="req-card-top">
            <div class="req-card-status-icon" :class="statusIconClass(req.statut)">
              <i :class="statusIcon(req.statut)"></i>
            </div>
            <div class="req-card-info">
              <div v-if="isRH && req.agent" class="req-card-agent">
                <span class="req-card-avatar">{{ agentInitials(req.agent) }}</span>
                <span class="req-card-agent-name">{{ req.agent.prenom }} {{ req.agent.nom }}</span>
              </div>
              <div class="req-card-type-row">
                <span class="req-badge-type">{{ formatType(req.type) }}</span>
                <span :class="statusBadgeClass(req.statut)">
                  <i :class="statusIcon(req.statut)" class="me-1"></i>
                  {{ statusLabel(req.statut) }}
                </span>
              </div>
            </div>
          </div>
          <div class="req-card-dates">
            <i class="fas fa-calendar-alt me-1"></i>
            <span>{{ formatDate(req.date_debut) }}</span>
            <template v-if="req.date_fin">
              <i class="fas fa-arrow-right req-date-arrow"></i>
              <span>{{ formatDate(req.date_fin) }}</span>
            </template>
          </div>
          <div class="req-card-footer">
            <span class="req-card-created">
              <i class="fas fa-clock me-1"></i>{{ formatDateTime(req.created_at) }}
            </span>
            <div class="req-card-actions">
              <router-link
                :to="{ name: 'requests.show', params: { id: req.id } }"
                class="req-act-btn req-act-view"
                title="Details"
              >
                <i class="fas fa-eye"></i> Voir
              </router-link>
              <router-link
                v-if="isRH"
                :to="{ name: 'requests.edit', params: { id: req.id } }"
                class="req-act-btn req-act-edit"
                title="Modifier"
              >
                <i class="fas fa-edit"></i> Modifier
              </router-link>
              <button
                v-if="canDelete(req)"
                class="req-act-btn req-act-delete"
                title="Supprimer"
                @click="confirmDelete(req)"
              >
                <i class="fas fa-trash"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="d-flex justify-content-center mt-4">
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
              <button class="page-link" @click="loadRequests(meta.current_page - 1)">&laquo;</button>
            </li>
            <li
              v-for="page in paginationPages"
              :key="page"
              class="page-item"
              :class="{ active: page === meta.current_page }"
            >
              <button class="page-link" @click="loadRequests(page)">{{ page }}</button>
            </li>
            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
              <button class="page-link" @click="loadRequests(meta.current_page + 1)">&raquo;</button>
            </li>
          </ul>
        </nav>
      </div>

      <!-- Empty state -->
      <div v-if="!requests.length" class="req-empty">
        <div class="req-empty-icon"><i class="fas fa-inbox"></i></div>
        <template v-if="filters.statut">
          <h5>Aucune demande &laquo; {{ statusLabel(filters.statut) }} &raquo;</h5>
          <p>Il n'y a pas de demandes avec ce statut pour le moment.</p>
          <button class="req-back-btn mt-3" style="display:inline-flex;" @click="setStatut('')">
            <i class="fas fa-arrow-left"></i> Voir toutes les demandes
          </button>
        </template>
        <template v-else>
          <h5>Aucune demande</h5>
          <p>{{ isRH ? "Il n'y a aucune demande a afficher." : "Vous n'avez pas encore soumis de demande." }}</p>
          <router-link :to="{ name: 'requests.create' }" class="req-hero-btn mt-3" style="display:inline-flex;">
            <i class="fas fa-plus me-1"></i> Creer une demande
          </router-link>
        </template>
      </div>
    </template>

    <!-- Confirm delete modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer la demande"
      :message="`Etes-vous sur de vouloir supprimer cette demande de ${deleteTarget?.type ?? ''} ?`"
      :loading="deleting"
      @confirm="handleDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { list, remove } from '@/api/requests'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const auth = useAuthStore()
const ui = useUiStore()

const isRH = computed(() => auth.hasAdminAccess)
const loading = ref(true)
const filtering = ref(false)
const requests = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })
const filters = ref({ statut: '', type: '' })

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

const paginationPages = computed(() => {
  const pages = []
  const total = meta.value.last_page
  const current = meta.value.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function loadRequests(page = 1) {
  // Only show full-page spinner on initial load, not on filter changes
  if (requests.value.length === 0 && !filtering.value) {
    loading.value = true
  }
  filtering.value = true
  try {
    const params = { page }
    if (filters.value.statut) params.statut = filters.value.statut
    if (filters.value.type) params.type = filters.value.type
    const { data } = await list(params)
    requests.value = data.data
    meta.value = data.meta
  } catch (err) {
    ui.addToast('Erreur lors du chargement des demandes.', 'danger')
  } finally {
    loading.value = false
    filtering.value = false
  }
}

function setStatut(statut) {
  filters.value.statut = statut
  loadRequests(1)
}

function resetFilters() {
  filters.value = { statut: '', type: '' }
  loadRequests(1)
}

function agentInitials(agent) {
  if (!agent) return ''
  return ((agent.prenom || '').charAt(0) + (agent.nom || '').charAt(0)).toUpperCase()
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function formatType(type) {
  if (!type) return ''
  return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
}

function statusLabel(statut) {
  const labels = {
    en_attente: 'En attente',
    'approuvé': 'Approuve',
    'rejeté': 'Rejete',
    'annulé': 'Annule',
  }
  return labels[statut] || statut
}

function statusIcon(statut) {
  const icons = {
    en_attente: 'fas fa-hourglass-half',
    'approuvé': 'fas fa-check-circle',
    'rejeté': 'fas fa-times-circle',
    'annulé': 'fas fa-ban',
  }
  return icons[statut] || 'fas fa-circle'
}

function statusBadgeClass(statut) {
  const classes = {
    en_attente: 'req-badge warning',
    'approuvé': 'req-badge success',
    'rejeté': 'req-badge danger',
    'annulé': 'req-badge secondary',
  }
  return classes[statut] || 'req-badge secondary'
}

function statusIconClass(statut) {
  const classes = {
    en_attente: 'req-si-warning',
    'approuvé': 'req-si-success',
    'rejeté': 'req-si-danger',
    'annulé': 'req-si-secondary',
  }
  return classes[statut] || 'req-si-secondary'
}

function statusColor(statut) {
  const colors = {
    en_attente: '#d97706',
    'approuvé': '#059669',
    'rejeté': '#dc2626',
    'annulé': '#6b7280',
  }
  return colors[statut] || '#6b7280'
}

function canDelete(req) {
  if (isRH.value) return true
  return req.statut === 'en_attente' && auth.agent?.id === req.agent_id
}

function confirmDelete(req) {
  deleteTarget.value = req
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await remove(deleteTarget.value.id)
    ui.addToast('Demande supprimee avec succes.', 'success')
    showDeleteModal.value = false
    deleteTarget.value = null
    await loadRequests(meta.value.current_page)
  } catch (err) {
    const msg = err.response?.data?.message || 'Erreur lors de la suppression.'
    ui.addToast(msg, 'danger')
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  loadRequests()
})
</script>

<style scoped>
/* ── Hero ── */
.req-hero {
  background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
  border-radius: 18px;
  padding: 2rem 2.2rem;
  margin-bottom: 1.5rem;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.req-hero::before {
  content: '';
  position: absolute;
  top: -40%;
  right: -8%;
  width: 240px;
  height: 240px;
  border-radius: 50%;
  background: rgba(255, 255, 255, .07);
}
.req-hero-body {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.5rem;
  position: relative;
  z-index: 1;
}
.req-hero-text h2 {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0 0 .3rem;
}
.req-hero-text p {
  font-size: .85rem;
  opacity: .8;
  margin: 0;
}
.req-hero-stats {
  display: flex;
  gap: 1.5rem;
  margin-top: 1rem;
}
.req-hero-stat-val {
  font-size: 1.5rem;
  font-weight: 800;
}
.req-hero-stat-lbl {
  font-size: .7rem;
  opacity: .7;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.req-hero-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: .75rem;
  flex-shrink: 0;
}
.req-hero-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .55rem 1.2rem;
  border-radius: 10px;
  font-size: .85rem;
  font-weight: 700;
  background: rgba(255, 255, 255, .18);
  color: #fff;
  text-decoration: none;
  border: 1px solid rgba(255, 255, 255, .25);
  transition: all .2s;
  cursor: pointer;
}
.req-hero-btn:hover {
  background: rgba(255, 255, 255, .3);
  color: #fff;
}
.req-type-filter {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}
.req-type-label {
  font-size: .68rem;
  font-weight: 600;
  opacity: .7;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: .2rem;
}
.req-type-select {
  background: rgba(255, 255, 255, .12);
  border: 1px solid rgba(255, 255, 255, .2);
  color: #fff;
  border-radius: 8px;
  padding: .35rem .7rem;
  font-size: .8rem;
  min-width: 140px;
}
.req-type-select option {
  color: #1e293b;
  background: #fff;
}
.req-type-select:focus {
  outline: none;
  border-color: rgba(255, 255, 255, .5);
}

/* ── Status filter cards ── */
.req-filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: .8rem;
  margin-bottom: 1.5rem;
}
.req-filter-card {
  display: flex;
  align-items: center;
  gap: .7rem;
  padding: .9rem 1rem;
  background: #fff;
  border: 2px solid #e5e7eb;
  border-radius: 14px;
  text-decoration: none;
  color: #374151;
  transition: all .25s;
  cursor: pointer;
}
.req-filter-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
}

/* All card */
.req-filter-all .req-filter-icon { background: #e0f2fe; color: #0077B5; }
.req-filter-all:hover { border-color: #0077B5; color: #0077B5; }
.req-filter-all.active {
  background: linear-gradient(135deg, #0077B5, #005a87);
  border-color: #0077B5;
  color: #fff;
  box-shadow: 0 4px 16px rgba(0, 119, 181, .25);
}
.req-filter-all.active .req-filter-icon { background: rgba(255, 255, 255, .2); color: #fff; }

/* Warning (En attente) */
.req-filter-warning .req-filter-icon { background: #fef3c7; color: #d97706; }
.req-filter-warning:hover { border-color: #d97706; color: #92400e; }
.req-filter-warning.active {
  background: linear-gradient(135deg, #d97706, #b45309);
  border-color: #d97706;
  color: #fff;
  box-shadow: 0 4px 16px rgba(217, 119, 6, .25);
}
.req-filter-warning.active .req-filter-icon { background: rgba(255, 255, 255, .2); color: #fff; }

/* Success (Approuve) */
.req-filter-success .req-filter-icon { background: #dcfce7; color: #059669; }
.req-filter-success:hover { border-color: #059669; color: #166534; }
.req-filter-success.active {
  background: linear-gradient(135deg, #059669, #047857);
  border-color: #059669;
  color: #fff;
  box-shadow: 0 4px 16px rgba(5, 150, 105, .25);
}
.req-filter-success.active .req-filter-icon { background: rgba(255, 255, 255, .2); color: #fff; }

/* Danger (Rejete) */
.req-filter-danger .req-filter-icon { background: #fee2e2; color: #dc2626; }
.req-filter-danger:hover { border-color: #dc2626; color: #991b1b; }
.req-filter-danger.active {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  border-color: #dc2626;
  color: #fff;
  box-shadow: 0 4px 16px rgba(220, 38, 38, .25);
}
.req-filter-danger.active .req-filter-icon { background: rgba(255, 255, 255, .2); color: #fff; }

/* Secondary (Annule) */
.req-filter-secondary .req-filter-icon { background: #f1f5f9; color: #6b7280; }
.req-filter-secondary:hover { border-color: #6b7280; color: #374151; }
.req-filter-secondary.active {
  background: linear-gradient(135deg, #6b7280, #4b5563);
  border-color: #6b7280;
  color: #fff;
  box-shadow: 0 4px 16px rgba(107, 114, 128, .25);
}
.req-filter-secondary.active .req-filter-icon { background: rgba(255, 255, 255, .2); color: #fff; }

.req-filter-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}
.req-filter-info {
  flex: 1;
  min-width: 0;
  text-align: left;
}
.req-filter-name {
  font-size: .82rem;
  font-weight: 700;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.req-filter-count {
  font-size: .7rem;
  opacity: .6;
}
.req-filter-card.active .req-filter-count { opacity: .8; }

/* ── Section header ── */
.req-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  padding-bottom: .6rem;
  border-bottom: 2px solid #f3f4f6;
}
.req-section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.req-section-badge {
  font-size: .72rem;
  font-weight: 700;
  padding: .2rem .6rem;
  border-radius: 20px;
  background: #ecfdf5;
  color: #059669;
}
.req-back-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .35rem .8rem;
  border-radius: 8px;
  font-size: .78rem;
  font-weight: 600;
  background: #f3f4f6;
  color: #6b7280;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all .2s;
}
.req-back-btn:hover {
  background: #e5e7eb;
  color: #374151;
}

/* ── Request cards grid ── */
.req-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
}
.req-card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
  overflow: hidden;
  transition: all .2s;
  display: flex;
  flex-direction: column;
}
.req-card:hover {
  box-shadow: 0 6px 24px rgba(0, 0, 0, .08);
  transform: translateY(-2px);
}
.req-card-top {
  display: flex;
  align-items: flex-start;
  gap: .8rem;
  padding: 1.2rem 1.2rem .6rem;
}
.req-card-status-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.req-si-warning { background: #fef3c7; color: #d97706; }
.req-si-success { background: #dcfce7; color: #059669; }
.req-si-danger { background: #fee2e2; color: #dc2626; }
.req-si-secondary { background: #f1f5f9; color: #6b7280; }

.req-card-info {
  flex: 1;
  min-width: 0;
}
.req-card-agent {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-bottom: .4rem;
}
.req-card-avatar {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: linear-gradient(135deg, #059669, #047857);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .6rem;
  font-weight: 700;
  flex-shrink: 0;
}
.req-card-agent-name {
  font-weight: 600;
  font-size: .85rem;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.req-card-type-row {
  display: flex;
  align-items: center;
  gap: .5rem;
  flex-wrap: wrap;
}

/* ── Badges ── */
.req-badge-type {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 6px;
  font-size: .75rem;
  font-weight: 600;
  background: #e0f2fe;
  color: #075985;
}
.req-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 6px;
  font-size: .72rem;
  font-weight: 600;
}
.req-badge.warning { background: #fef3c7; color: #92400e; }
.req-badge.success { background: #dcfce7; color: #166534; }
.req-badge.danger { background: #fee2e2; color: #991b1b; }
.req-badge.secondary { background: #f1f5f9; color: #475569; }

/* ── Dates row ── */
.req-card-dates {
  padding: .5rem 1.2rem;
  display: flex;
  align-items: center;
  gap: .4rem;
  font-size: .8rem;
  color: #64748b;
}
.req-date-arrow {
  font-size: .6rem;
  margin: 0 .15rem;
  opacity: .5;
}

/* ── Card footer ── */
.req-card-footer {
  border-top: 1px solid #f3f4f6;
  padding: .7rem 1.2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  gap: .5rem;
}
.req-card-created {
  font-size: .72rem;
  color: #9ca3af;
  flex-shrink: 0;
}
.req-card-actions {
  display: flex;
  gap: .4rem;
  flex-wrap: wrap;
}
.req-act-btn {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  padding: .3rem .65rem;
  border-radius: 8px;
  font-size: .72rem;
  font-weight: 600;
  text-decoration: none;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  transition: all .2s;
}
.req-act-view {
  color: #0077B5;
}
.req-act-view:hover {
  background: #f0f9ff;
  border-color: #0077B5;
  color: #0077B5;
}
.req-act-edit {
  color: #d97706;
}
.req-act-edit:hover {
  background: #fffbeb;
  border-color: #d97706;
  color: #d97706;
}
.req-act-delete {
  color: #ef4444;
}
.req-act-delete:hover {
  background: #fef2f2;
  border-color: #ef4444;
}

/* ── Pagination ── */
.pagination .page-link {
  border-radius: 8px;
  margin: 0 2px;
  border: 1px solid #e2e8f0;
  color: #64748b;
  font-size: .82rem;
  font-weight: 600;
  padding: .35rem .7rem;
}
.page-item.active .page-link {
  background: #059669;
  border-color: #059669;
  color: #fff;
}

/* ── Empty state ── */
.req-empty {
  text-align: center;
  padding: 3rem 1rem;
  color: #9ca3af;
}
.req-empty-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin: 0 auto 1rem;
  color: #d1d5db;
}

/* ── Filtering overlay ── */
.req-filtering {
  opacity: 0.4;
  pointer-events: none;
  transition: opacity .2s;
}

/* ── Mobile responsive ── */
@media (max-width: 576px) {
  .req-hero {
    padding: 1.4rem 1.2rem;
    border-radius: 14px;
  }
  .req-hero-body {
    flex-direction: column;
    gap: 1rem;
  }
  .req-hero-actions {
    align-items: stretch;
    width: 100%;
  }
  .req-hero-btn {
    justify-content: center;
  }
  .req-type-filter {
    align-items: stretch;
  }
  .req-type-select {
    width: 100%;
  }
  .req-filter-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .req-grid {
    grid-template-columns: 1fr;
  }
  .req-section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: .5rem;
  }
  .req-card-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: .5rem;
  }
  .req-card-actions {
    width: 100%;
  }
  .req-act-btn {
    flex: 1;
    justify-content: center;
  }
}
</style>
