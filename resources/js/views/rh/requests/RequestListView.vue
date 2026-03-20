<template>
  <div class="py-4">
    <!-- Hero header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="fas fa-tasks me-2" v-if="isRH"></i>
          <i class="fas fa-paper-plane me-2" v-else></i>
          {{ isRH ? 'Gestion des demandes' : 'Mes Demandes' }}
        </h4>
        <p class="text-muted mb-0" v-if="isRH">
          Suivi, validation et historisation des demandes des agents.
        </p>
        <p class="text-muted mb-0" v-else>
          Suivez l'etat de vos demandes de conge, absence et permission.
        </p>
      </div>
      <router-link :to="{ name: 'requests.create' }" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nouvelle demande
      </router-link>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4" v-if="isRH">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Statut</label>
            <select v-model="filters.statut" class="form-select" @change="loadRequests(1)">
              <option value="">Tous</option>
              <option value="en_attente">En attente</option>
              <option value="approuve">Approuve</option>
              <option value="rejete">Rejete</option>
              <option value="annule">Annule</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Type</label>
            <select v-model="filters.type" class="form-select" @change="loadRequests(1)">
              <option value="">Tous</option>
              <option value="conge">Conge</option>
              <option value="absence">Absence</option>
              <option value="permission">Permission</option>
              <option value="formation">Formation</option>
              <option value="renforcement_capacites">Renforcement</option>
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end gap-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters">
              <i class="fas fa-redo me-1"></i> Reinitialiser
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="text-muted mt-2">Chargement des demandes...</p>
    </div>

    <!-- Table -->
    <div v-else-if="requests.length" class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
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
                <td v-if="isRH">
                  <strong>{{ req.agent?.prenom }} {{ req.agent?.nom }}</strong>
                  <br>
                  <small class="text-muted">{{ req.agent?.id_agent }}</small>
                </td>
                <td>
                  <span class="badge bg-info text-dark">{{ formatType(req.type) }}</span>
                </td>
                <td>
                  <small>
                    {{ formatDate(req.date_debut) }}
                    <template v-if="req.date_fin">
                      a {{ formatDate(req.date_fin) }}
                    </template>
                  </small>
                </td>
                <td>
                  <span :class="statusBadgeClass(req.statut)">
                    {{ statusLabel(req.statut) }}
                  </span>
                </td>
                <td>
                  <small>{{ formatDateTime(req.created_at) }}</small>
                </td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <router-link
                      :to="{ name: 'requests.show', params: { id: req.id } }"
                      class="btn btn-outline-primary"
                      title="Details"
                    >
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <router-link
                      v-if="isRH"
                      :to="{ name: 'requests.edit', params: { id: req.id } }"
                      class="btn btn-outline-warning"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      v-if="canDelete(req)"
                      class="btn btn-outline-danger"
                      title="Supprimer"
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
      </div>

      <!-- Pagination -->
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
          Affichage {{ meta.from ?? 0 }} a {{ meta.to ?? 0 }} sur {{ meta.total }} demandes
        </small>
        <nav v-if="meta.last_page > 1">
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
    </div>

    <!-- Empty state -->
    <div v-else class="card border-0 shadow-sm">
      <div class="card-body text-center py-5">
        <i class="fas fa-inbox fa-4x text-muted mb-3 d-block"></i>
        <h5 class="text-muted">Aucune demande</h5>
        <p class="text-muted">
          {{ isRH ? "Il n'y a aucune demande a afficher." : "Vous n'avez pas encore soumis de demande." }}
        </p>
        <router-link :to="{ name: 'requests.create' }" class="btn btn-primary mt-2">
          <i class="fas fa-plus me-2"></i> Creer une demande
        </router-link>
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

// Delete state
const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

// Pagination
const paginationPages = computed(() => {
  const pages = []
  const total = meta.value.last_page
  const current = meta.value.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
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

// Formatting helpers
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

function statusBadgeClass(statut) {
  const classes = {
    en_attente: 'badge bg-warning text-dark',
    'approuvé': 'badge bg-success',
    'rejeté': 'badge bg-danger',
    'annulé': 'badge bg-secondary',
  }
  return classes[statut] || 'badge bg-secondary'
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
.py-4 h4 { font-weight: 700; color: #1e293b; }
.py-4 h4 i { color: #0077B5; }

/* Cards */
.card { border-radius: 14px !important; border: 1px solid #f1f5f9 !important; box-shadow: 0 2px 12px rgba(0,0,0,.06) !important; overflow: hidden; }
.card-body { padding: 1.25rem; }

/* Filter section */
.form-select, .form-control { border-radius: 10px; border: 1px solid #e2e8f0; padding: .5rem 1rem; font-size: .88rem; }
.form-select:focus, .form-control:focus { border-color: #0077B5; box-shadow: 0 0 0 3px rgba(0,119,181,.1); }
.form-label { font-size: .82rem; font-weight: 600; color: #64748b; }

/* Table */
.table { margin-bottom: 0; }
.table thead th, .table-light th { background: #f8fafc !important; border: none !important; font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #64748b; padding: .85rem 1rem; }
.table tbody td { padding: .75rem 1rem; border-color: #f1f5f9; vertical-align: middle; font-size: .88rem; }
.table tbody tr { transition: background .15s; }
.table tbody tr:hover { background: #f8fafc !important; }

/* Badges */
.badge { font-weight: 600; font-size: .75rem; padding: 4px 10px; border-radius: 6px; }
.badge.bg-warning { background: #fef3c7 !important; color: #92400e !important; }
.badge.bg-success { background: #dcfce7 !important; color: #166534 !important; }
.badge.bg-danger { background: #fee2e2 !important; color: #991b1b !important; }
.badge.bg-secondary { background: #f1f5f9 !important; color: #475569 !important; }
.badge.bg-info { background: #e0f2fe !important; color: #075985 !important; }

/* Action buttons */
.btn-group-sm .btn { border-radius: 8px !important; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border: 1px solid #e2e8f0; margin: 0 2px; font-size: .8rem; }
.btn-group-sm .btn-outline-primary { color: #0077B5; border-color: #e2e8f0; background: #fff; }
.btn-group-sm .btn-outline-primary:hover { background: #f0f9ff; border-color: #0077B5; }
.btn-group-sm .btn-outline-warning { color: #d97706; border-color: #e2e8f0; background: #fff; }
.btn-group-sm .btn-outline-warning:hover { background: #fffbeb; border-color: #d97706; }
.btn-group-sm .btn-outline-danger { color: #ef4444; border-color: #e2e8f0; background: #fff; }
.btn-group-sm .btn-outline-danger:hover { background: #fef2f2; border-color: #ef4444; }

/* Create button */
.btn-primary { background: linear-gradient(135deg, #0077B5, #005a87); border: none; border-radius: 10px; font-weight: 600; font-size: .85rem; padding: .5rem 1.25rem; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,119,181,.3); }

/* Pagination */
.card-footer { background: #fff !important; border-top: 1px solid #f1f5f9 !important; padding: .85rem 1.25rem !important; }
.pagination .page-link { border-radius: 8px; margin: 0 2px; border: 1px solid #e2e8f0; color: #64748b; font-size: .82rem; font-weight: 600; padding: .35rem .7rem; }
.pagination .page-item.active .page-link { background: #0077B5; border-color: #0077B5; color: #fff; }

/* Empty state */
.card-body.text-center i.fa-inbox { color: #cbd5e1 !important; }

/* Reset button */
.btn-outline-secondary { border-radius: 10px; border: 1px solid #e2e8f0; font-size: .85rem; font-weight: 600; }
.btn-outline-secondary:hover { background: #f8fafc; }

@media (max-width: 767.98px) {
  .btn-primary { width: 100%; margin-top: .75rem; }
}
</style>
