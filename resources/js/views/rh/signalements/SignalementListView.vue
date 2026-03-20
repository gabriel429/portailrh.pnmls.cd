<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <!-- Hero -->
      <section class="rh-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-triangle-exclamation me-2"></i>Gestion des signalements</h1>
            <p class="rh-sub">Suivi des incidents, severite et statut de resolution.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'signalements.create' }" class="btn-rh main">
                <i class="fas fa-plus me-1"></i> Nouveau signalement
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <!-- Filters -->
      <div class="dash-panel mt-3">
        <div class="p-3">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="form-label mb-1 small fw-bold">Severite</label>
              <select v-model="filters.severite" class="form-select form-select-sm" @change="loadSignalements(1)">
                <option value="">Toutes</option>
                <option value="basse">Basse</option>
                <option value="moyenne">Moyenne</option>
                <option value="haute">Haute</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="form-label mb-1 small fw-bold">Statut</label>
              <select v-model="filters.statut" class="form-select form-select-sm" @change="loadSignalements(1)">
                <option value="">Tous</option>
                <option value="ouvert">Ouvert</option>
                <option value="en_cours">En cours</option>
                <option value="résolu">Resolu</option>
                <option value="fermé">Ferme</option>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">Reinitialiser</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement des signalements..." />
      </div>

      <!-- Table -->
      <div v-else-if="signalements.length" class="rh-list-card p-3 p-lg-4 mt-3">
        <div class="rh-table-wrap">
          <table class="rh-table">
            <thead>
              <tr>
                <th>Agent</th>
                <th>Type</th>
                <th>Severite</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in signalements" :key="s.id">
                <td>
                  <strong>{{ s.agent?.prenom }} {{ s.agent?.nom }}</strong><br>
                  <small class="text-muted">{{ s.agent?.id_agent }}</small>
                </td>
                <td>{{ capitalize(s.type) }}</td>
                <td>
                  <span :class="severiteChip(s.severite)">{{ capitalize(s.severite) }}</span>
                </td>
                <td>
                  <span :class="statutChip(s.statut)">{{ statutLabel(s.statut) }}</span>
                </td>
                <td>{{ formatDateTime(s.created_at) }}</td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <router-link
                      :to="{ name: 'signalements.show', params: { id: s.id } }"
                      class="btn btn-outline-primary"
                      title="Details"
                    >
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <router-link
                      :to="{ name: 'signalements.edit', params: { id: s.id } }"
                      class="btn btn-outline-warning"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      class="btn btn-outline-danger"
                      title="Supprimer"
                      @click="confirmDelete(s)"
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
        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
          <div class="text-muted small">
            Affichage {{ meta.from ?? 0 }} a {{ meta.to ?? 0 }} sur {{ meta.total }} signalements
          </div>
          <nav v-if="meta.last_page > 1">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <button class="page-link" @click="loadSignalements(meta.current_page - 1)">&laquo;</button>
              </li>
              <li
                v-for="page in paginationPages"
                :key="page"
                class="page-item"
                :class="{ active: page === meta.current_page }"
              >
                <button class="page-link" @click="loadSignalements(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <button class="page-link" @click="loadSignalements(meta.current_page + 1)">&raquo;</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="rh-list-card mt-3">
        <div class="text-center py-5">
          <i class="fas fa-triangle-exclamation fa-4x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Aucun signalement</h5>
          <p class="text-muted">Aucun incident n'a encore ete signale.</p>
          <router-link :to="{ name: 'signalements.create' }" class="btn btn-primary mt-2">
            <i class="fas fa-plus me-2"></i>Creer un signalement
          </router-link>
        </div>
      </div>

      <!-- Confirm delete modal -->
      <ConfirmModal
        :show="showDeleteModal"
        title="Supprimer le signalement"
        :message="`Etes-vous sur de vouloir supprimer ce signalement de type '${deleteTarget?.type ?? ''}' ?`"
        :loading="deleting"
        @confirm="handleDelete"
        @cancel="showDeleteModal = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { list, remove } from '@/api/signalements'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()
const loading = ref(true)
const signalements = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })
const filters = ref({ severite: '', statut: '' })

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

async function loadSignalements(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filters.value.severite) params.severite = filters.value.severite
    if (filters.value.statut) params.statut = filters.value.statut
    const { data } = await list(params)
    signalements.value = data.data
    meta.value = data.meta
  } catch {
    ui.addToast('Erreur lors du chargement des signalements.', 'danger')
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.value = { severite: '', statut: '' }
  loadSignalements(1)
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function severiteChip(sev) {
  const map = { haute: 'status-chip st-bad', moyenne: 'status-chip st-mid', basse: 'status-chip st-ok' }
  return map[sev] || 'status-chip st-neutral'
}

function statutChip(statut) {
  const map = {
    ouvert: 'status-chip st-bad',
    en_cours: 'status-chip st-mid',
    'résolu': 'status-chip st-ok',
    'fermé': 'status-chip st-neutral',
  }
  return map[statut] || 'status-chip st-neutral'
}

function statutLabel(statut) {
  const map = {
    ouvert: 'Ouvert',
    en_cours: 'En cours',
    'résolu': 'Resolu',
    'fermé': 'Ferme',
  }
  return map[statut] || capitalize(statut)
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function confirmDelete(s) {
  deleteTarget.value = s
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await remove(deleteTarget.value.id)
    ui.addToast('Signalement supprime avec succes.', 'success')
    showDeleteModal.value = false
    deleteTarget.value = null
    await loadSignalements(meta.value.current_page)
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  } finally {
    deleting.value = false
  }
}

onMounted(() => loadSignalements())
</script>

<style scoped>
/* ── Mobile responsive styles ── */
@media (max-width: 768px) {
  .rh-hero .row {
    text-align: center;
  }
  .rh-hero .col-lg-4 {
    text-align: center;
  }
  .hero-tools {
    justify-content: center;
    display: flex;
  }
  .rh-title {
    font-size: 1.3rem;
  }
  .rh-sub {
    font-size: 0.85rem;
  }

  /* Filters: stack vertically */
  .dash-panel .row .col-auto {
    flex: 1 1 100%;
  }

  /* Table compact */
  .rh-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .rh-table {
    font-size: 0.82rem;
  }
  .rh-table th,
  .rh-table td {
    padding: 0.5rem 0.4rem;
    white-space: nowrap;
  }

  /* Hide Type (2nd col) and Date (5th col) */
  .rh-table th:nth-child(2),
  .rh-table td:nth-child(2),
  .rh-table th:nth-child(5),
  .rh-table td:nth-child(5) {
    display: none;
  }

  /* Compact action buttons */
  .btn-group .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
  }

  /* Pagination */
  .pagination {
    flex-wrap: wrap;
    gap: 2px;
  }
  .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.78rem;
  }
}
</style>
