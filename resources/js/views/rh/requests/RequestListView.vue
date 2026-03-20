<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <!-- Hero -->
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title">
              <i class="fas fa-tasks me-2" v-if="isRH"></i>
              <i class="fas fa-paper-plane me-2" v-else></i>
              {{ isRH ? 'Gestion des demandes' : 'Mes Demandes' }}
            </h1>
            <p class="rh-sub" v-if="isRH">Suivi, validation et historisation des demandes des agents.</p>
            <p class="rh-sub" v-else>Suivez l'etat de vos demandes de conge, absence et permission.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'requests.create' }" class="btn-rh main">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle demande
              </router-link>
            </div>
          </div>
        </div>

        <!-- Filters (RH only) -->
        <div v-if="isRH" class="hero-filters mt-3">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="filter-label">Statut</label>
              <select v-model="filters.statut" class="filter-select" @change="loadRequests(1)">
                <option value="">Tous</option>
                <option value="en_attente">En attente</option>
                <option value="approuvé">Approuve</option>
                <option value="rejeté">Rejete</option>
                <option value="annulé">Annule</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="filter-label">Type</label>
              <select v-model="filters.type" class="filter-select" @change="loadRequests(1)">
                <option value="">Tous</option>
                <option value="conge">Conge</option>
                <option value="absence">Absence</option>
                <option value="permission">Permission</option>
                <option value="formation">Formation</option>
                <option value="renforcement_capacites">Renforcement</option>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn-rh outline-light" @click="resetFilters">
                <i class="fas fa-redo me-1"></i> Reinitialiser
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Chargement...</span>
        </div>
        <p class="text-muted mt-2">Chargement des demandes...</p>
      </div>

      <!-- Table -->
      <div v-else-if="requests.length" class="dash-panel mt-3">
        <div class="rh-table-wrap">
          <table class="rh-table">
            <thead>
              <tr>
                <th v-if="isRH">Agent</th>
                <th>Type</th>
                <th>Periode</th>
                <th>Statut</th>
                <th>Date creation</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="req in requests" :key="req.id">
                <td v-if="isRH" data-label="Agent">
                  <div class="agent-cell">
                    <div class="agent-avatar">{{ agentInitials(req.agent) }}</div>
                    <div>
                      <div class="agent-name">{{ req.agent?.prenom }} {{ req.agent?.nom }}</div>
                      <div class="agent-id">{{ req.agent?.id_agent }}</div>
                    </div>
                  </div>
                </td>
                <td data-label="Type">
                  <span class="req-badge type">{{ formatType(req.type) }}</span>
                </td>
                <td data-label="Periode">
                  <span class="date-text">
                    {{ formatDate(req.date_debut) }}
                    <template v-if="req.date_fin"> - {{ formatDate(req.date_fin) }}</template>
                  </span>
                </td>
                <td data-label="Statut">
                  <span :class="statusBadgeClass(req.statut)">
                    <i :class="statusIcon(req.statut)" class="me-1"></i>
                    {{ statusLabel(req.statut) }}
                  </span>
                </td>
                <td data-label="Date">
                  <span class="date-text">{{ formatDateTime(req.created_at) }}</span>
                </td>
                <td data-label="Actions">
                  <div class="action-btns">
                    <router-link
                      :to="{ name: 'requests.show', params: { id: req.id } }"
                      class="act-btn blue" title="Details"
                    >
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <router-link
                      v-if="isRH"
                      :to="{ name: 'requests.edit', params: { id: req.id } }"
                      class="act-btn orange" title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      v-if="canDelete(req)"
                      class="act-btn red" title="Supprimer"
                      @click="confirmDelete(req)"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="rh-pagination">
          <small class="text-muted">
            {{ meta.from ?? 0 }} - {{ meta.to ?? 0 }} sur {{ meta.total }}
          </small>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <button class="page-link" @click="loadRequests(meta.current_page - 1)">&laquo;</button>
              </li>
              <li
                v-for="page in paginationPages" :key="page"
                class="page-item" :class="{ active: page === meta.current_page }"
              >
                <button class="page-link" @click="loadRequests(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <button class="page-link" @click="loadRequests(meta.current_page + 1)">&raquo;</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="dash-panel mt-3">
        <div class="p-5 text-center">
          <i class="fas fa-inbox fa-4x mb-3 d-block" style="color:#cbd5e1;"></i>
          <h5 class="text-muted">Aucune demande</h5>
          <p class="text-muted">
            {{ isRH ? "Il n'y a aucune demande a afficher." : "Vous n'avez pas encore soumis de demande." }}
          </p>
          <router-link :to="{ name: 'requests.create' }" class="btn-rh main mt-2">
            <i class="fas fa-plus me-1"></i> Creer une demande
          </router-link>
        </div>
      </div>
    </div>

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
  loading.value = true
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
  }
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
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
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
/* Hero filters */
.hero-filters { border-top: 1px solid rgba(255,255,255,.15); padding-top: .75rem; }
.filter-label { display: block; font-size: .7rem; font-weight: 600; color: rgba(255,255,255,.7); margin-bottom: .25rem; text-transform: uppercase; letter-spacing: .5px; }
.filter-select { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); color: #fff; border-radius: 8px; padding: .4rem .75rem; font-size: .82rem; min-width: 130px; }
.filter-select option { color: #1e293b; background: #fff; }
.filter-select:focus { outline: none; border-color: rgba(255,255,255,.5); }
.btn-rh.outline-light { background: transparent; color: rgba(255,255,255,.8); border: 1px solid rgba(255,255,255,.25); padding: .4rem 1rem; border-radius: 8px; font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .2s; }
.btn-rh.outline-light:hover { background: rgba(255,255,255,.12); color: #fff; }

/* Agent cell */
.agent-cell { display: flex; align-items: center; gap: .6rem; }
.agent-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #0077B5, #005a87); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; flex-shrink: 0; }
.agent-name { font-weight: 600; font-size: .85rem; color: #1e293b; }
.agent-id { font-size: .72rem; color: #94a3b8; }

/* Badges */
.req-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 6px; font-size: .75rem; font-weight: 600; }
.req-badge.type { background: #e0f2fe; color: #075985; }
.req-badge.warning { background: #fef3c7; color: #92400e; }
.req-badge.success { background: #dcfce7; color: #166534; }
.req-badge.danger { background: #fee2e2; color: #991b1b; }
.req-badge.secondary { background: #f1f5f9; color: #475569; }

.date-text { font-size: .82rem; color: #64748b; }

/* Action buttons */
.action-btns { display: flex; gap: 4px; }
.act-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; cursor: pointer; transition: all .2s; text-decoration: none; }
.act-btn.blue { color: #0077B5; }
.act-btn.blue:hover { background: #f0f9ff; border-color: #0077B5; }
.act-btn.orange { color: #d97706; }
.act-btn.orange:hover { background: #fffbeb; border-color: #d97706; }
.act-btn.red { color: #ef4444; }
.act-btn.red:hover { background: #fef2f2; border-color: #ef4444; }

/* Pagination */
.rh-pagination { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-top: 1px solid #f1f5f9; }
.rh-pagination .page-link { border-radius: 8px; margin: 0 2px; border: 1px solid #e2e8f0; color: #64748b; font-size: .82rem; font-weight: 600; padding: .35rem .7rem; }
.rh-pagination .page-item.active .page-link { background: #0077B5; border-color: #0077B5; color: #fff; }

@media (max-width: 767.98px) {
  .hero-filters .row { flex-direction: column; }
  .hero-filters .col-auto { width: 100%; }
  .filter-select { width: 100%; }

  .rh-table thead { display: none; }
  .rh-table tbody tr { display: block; padding: .75rem; border-bottom: 1px solid #f1f5f9; }
  .rh-table tbody td { display: flex; justify-content: space-between; align-items: center; padding: .35rem 0; border: none; }
  .rh-table tbody td::before { content: attr(data-label); font-weight: 600; font-size: .75rem; color: #64748b; text-transform: uppercase; }

  .agent-cell { gap: .4rem; }
  .agent-avatar { width: 28px; height: 28px; font-size: .6rem; }

  .rh-pagination { flex-direction: column; gap: .75rem; text-align: center; }
}
</style>
