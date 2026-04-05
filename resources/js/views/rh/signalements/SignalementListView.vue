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
              <button class="btn-rh main" @click="openCreateModal">
                <i class="fas fa-plus me-1"></i> Nouveau signalement
              </button>
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
                  <template v-if="s.is_anonymous">
                    <span class="anon-badge"><i class="fas fa-user-secret me-1"></i> Anonyme</span>
                  </template>
                  <template v-else>
                    <strong>{{ s.agent?.prenom }} {{ s.agent?.nom }}</strong><br>
                    <small class="text-muted">{{ s.agent?.id_agent }}</small>
                  </template>
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
                    <button
                      class="btn btn-outline-warning"
                      title="Modifier"
                      @click="openEditModal(s.id)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
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
          <button class="btn btn-primary mt-2" @click="openCreateModal">
            <i class="fas fa-plus me-2"></i>Creer un signalement
          </button>
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

  <!-- Create signalement modal -->
  <teleport to="body">
    <div v-if="showCreateModal" class="scm-overlay" @click.self="closeCreateModal">
      <div class="scm-dialog">
        <div class="scm-header">
          <div>
            <h4 class="scm-title"><i class="fas fa-plus-circle me-2"></i>Nouveau signalement</h4>
            <p class="scm-sub">Declarer un incident et fixer sa severite initiale.</p>
          </div>
          <button class="scm-close" @click="closeCreateModal"><i class="fas fa-times"></i></button>
        </div>

        <div class="scm-body">
          <div v-if="Object.keys(createErrors).length" class="scm-errors">
            <strong>Erreurs de validation</strong>
            <ul>
              <li v-for="(msgs, field) in createErrors" :key="field">{{ Array.isArray(msgs) ? msgs[0] : msgs }}</li>
            </ul>
          </div>

          <form @submit.prevent="handleCreate" id="createSignalementForm">
            <div class="scm-form-grid">
              <div class="scm-field">
                <label class="scm-label">Agent</label>
                <div class="scm-agent-info">
                  <i class="fas fa-user-circle me-1"></i>
                  {{ auth.agent?.prenom }} {{ auth.agent?.nom }}
                  <small class="text-muted ms-1">({{ auth.agent?.id_agent }})</small>
                </div>
              </div>
              <div class="scm-field">
                <label class="scm-label">Type <span class="text-danger">*</span></label>
                <input v-model="createForm.type" type="text" class="scm-input" placeholder="Ex: Retard, Absence..." required>
              </div>
              <div class="scm-field">
                <label class="scm-label">Severite <span class="text-danger">*</span></label>
                <div class="scm-severity-cards">
                  <label v-for="sev in severiteOptions" :key="sev.value"
                    class="scm-sev-card" :class="{ active: createForm.severite === sev.value, [sev.cls]: true }">
                    <input type="radio" v-model="createForm.severite" :value="sev.value" class="d-none">
                    <i :class="sev.icon" class="scm-sev-icon"></i>
                    <span>{{ sev.label }}</span>
                  </label>
                </div>
              </div>
            </div>

            <div class="scm-field mt-3">
              <label class="scm-label">Description <span class="text-danger">*</span></label>
              <textarea v-model="createForm.description" class="scm-textarea" rows="4" placeholder="Decrivez l'incident en detail..." required></textarea>
            </div>

            <div class="scm-field mt-3">
              <label class="scm-label">Observations</label>
              <textarea v-model="createForm.observations" class="scm-textarea" rows="3" placeholder="Remarques supplementaires (optionnel)"></textarea>
            </div>
          </form>
        </div>

        <div class="scm-footer">
          <button type="submit" form="createSignalementForm" class="scm-btn-save" :disabled="createSubmitting">
            <span v-if="createSubmitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-save me-1"></i> Creer le signalement
          </button>
          <button class="scm-btn-cancel" @click="closeCreateModal">Annuler</button>
        </div>
      </div>
    </div>
  </teleport>

  <!-- Edit modal -->
  <SignalementEditModal
    :show="showEditModal"
    :signalement-id="editingSignalementId"
    @close="closeEditModal"
    @updated="handleSignalementUpdated"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { list, remove, create } from '@/api/signalements'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import SignalementEditModal from '@/components/signalements/SignalementEditModal.vue'

const ui = useUiStore()
const auth = useAuthStore()
const loading = ref(true)
const signalements = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })
const filters = ref({ severite: '', statut: '' })

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

// Edit modal
const showEditModal = ref(false)
const editingSignalementId = ref(null)

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

// Edit modal functions
function openEditModal(signalementId) {
  editingSignalementId.value = signalementId
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  editingSignalementId.value = null
}

function handleSignalementUpdated(updatedSignalement) {
  // Refresh the signalements list to show updated data
  loadSignalements(meta.value.current_page)
}

onMounted(() => loadSignalements())

// ── Create modal ──
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createErrors = ref({})
const severiteOptions = [
    { value: 'basse', label: 'Basse', icon: 'fas fa-arrow-down', cls: 'sev-basse' },
    { value: 'moyenne', label: 'Moyenne', icon: 'fas fa-minus', cls: 'sev-moyenne' },
    { value: 'haute', label: 'Haute', icon: 'fas fa-arrow-up', cls: 'sev-haute' },
]

function defaultCreateForm() {
    return { type: '', description: '', observations: '', severite: '' }
}
const createForm = ref(defaultCreateForm())

async function openCreateModal() {
    createForm.value = defaultCreateForm()
    createErrors.value = {}
    showCreateModal.value = true
}

function closeCreateModal() { showCreateModal.value = false }

async function handleCreate() {
    createErrors.value = {}
    createSubmitting.value = true
    try {
        await create({ ...createForm.value, agent_id: auth.agent?.id })
        ui.addToast('Signalement cree avec succes.', 'success')
        closeCreateModal()
        await loadSignalements(meta.value.current_page)
    } catch (err) {
        if (err.response?.status === 422) {
            createErrors.value = err.response.data.errors || {}
        } else {
            ui.addToast(err.response?.data?.message || 'Erreur lors de la creation.', 'danger')
        }
    } finally {
        createSubmitting.value = false
    }
}
</script>

<style scoped>
/* ── Create Modal (scm-*) ── */
.scm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(15,23,42,.55); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: scmFadeIn .2s ease;
}
@keyframes scmFadeIn { from { opacity: 0 } to { opacity: 1 } }
@keyframes scmSlideUp { from { opacity: 0; transform: translateY(24px) } to { opacity: 1; transform: translateY(0) } }

.scm-dialog {
  background: #fff; border-radius: 18px; width: 100%; max-width: 580px;
  box-shadow: 0 24px 48px rgba(0,0,0,.18);
  animation: scmSlideUp .25s ease;
  display: flex; flex-direction: column; max-height: 90vh;
}

.scm-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  border-radius: 18px 18px 0 0; color: #fff;
}
.scm-title { font-size: 1.1rem; font-weight: 700; margin: 0; }
.scm-sub { font-size: .78rem; opacity: .85; margin: .2rem 0 0; }
.scm-close {
  background: rgba(255,255,255,.15); border: none; color: #fff;
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .2s; flex-shrink: 0;
}
.scm-close:hover { background: rgba(255,255,255,.3); }

.scm-body { padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; }

.scm-errors {
  background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
  padding: .75rem 1rem; margin-bottom: 1rem; font-size: .82rem; color: #991b1b;
}
.scm-errors ul { margin: .3rem 0 0; padding-left: 1.2rem; }
.scm-errors li { margin-bottom: .15rem; }

.scm-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }
.scm-form-grid > .scm-field:last-child { grid-column: 1 / -1; }

.scm-field { display: flex; flex-direction: column; }
.scm-label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }

.scm-input, .scm-select, .scm-textarea {
  border: 1.5px solid #e2e8f0; border-radius: 10px; padding: .55rem .75rem;
  font-size: .85rem; color: #1e293b; background: #fff; transition: border-color .2s;
  width: 100%;
}
.scm-input:focus, .scm-select:focus, .scm-textarea:focus {
  outline: none; border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.1);
}
.scm-textarea { resize: vertical; min-height: 60px; }

.scm-agent-info {
  padding: .55rem .75rem; border-radius: 10px; font-size: .85rem;
  background: #f8fafc; border: 1.5px solid #e2e8f0; color: #1e293b; font-weight: 600;
}

/* Severity cards */
.scm-severity-cards { display: flex; gap: .5rem; }
.scm-sev-card {
  flex: 1; display: flex; flex-direction: column; align-items: center; gap: .25rem;
  padding: .6rem .4rem; border-radius: 10px; border: 2px solid #e2e8f0;
  cursor: pointer; transition: all .2s; font-size: .75rem; font-weight: 600;
  background: #fff;
}
.scm-sev-icon { font-size: 1rem; }
.scm-sev-card.sev-basse { color: #22c55e; }
.scm-sev-card.sev-moyenne { color: #f59e0b; }
.scm-sev-card.sev-haute { color: #ef4444; }
.scm-sev-card:hover { border-color: #94a3b8; }
.scm-sev-card.active.sev-basse { border-color: #22c55e; background: #f0fdf4; }
.scm-sev-card.active.sev-moyenne { border-color: #f59e0b; background: #fffbeb; }
.scm-sev-card.active.sev-haute { border-color: #ef4444; background: #fef2f2; }

.scm-footer {
  display: flex; gap: .75rem; justify-content: flex-end;
  padding: .85rem 1.5rem; border-top: 1px solid #f3f4f6;
}
.scm-btn-save {
  padding: .5rem 1.2rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: none; background: #dc2626; color: #fff; cursor: pointer; transition: all .2s;
}
.scm-btn-save:hover:not(:disabled) { background: #b91c1c; }
.scm-btn-save:disabled { opacity: .6; cursor: not-allowed; }
.scm-btn-cancel {
  padding: .5rem 1.2rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .2s;
}
.scm-btn-cancel:hover { background: #f3f4f6; }

/* ── Mobile responsive styles ── */
@media (max-width: 768px) {
  .rh-hero .row { text-align: center; }
  .rh-hero .col-lg-4 { text-align: center; }
  .hero-tools { justify-content: center; display: flex; }
  .rh-title { font-size: 1.3rem; }
  .rh-sub { font-size: 0.85rem; }
  .dash-panel .row .col-auto { flex: 1 1 100%; }
  .rh-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .rh-table { font-size: 0.82rem; }
  .rh-table th, .rh-table td { padding: 0.5rem 0.4rem; white-space: nowrap; }
  .rh-table th:nth-child(2), .rh-table td:nth-child(2),
  .rh-table th:nth-child(5), .rh-table td:nth-child(5) { display: none; }
  .btn-group .btn { padding: 0.25rem 0.4rem; font-size: 0.75rem; }
  .pagination { flex-wrap: wrap; gap: 2px; }
  .page-link { padding: 0.25rem 0.5rem; font-size: 0.78rem; }

  .scm-dialog { max-width: 100%; border-radius: 14px; }
  .scm-header { padding: 1rem; border-radius: 14px 14px 0 0; }
  .scm-body { padding: 1rem; }
  .scm-form-grid { grid-template-columns: 1fr; }
  .scm-severity-cards { gap: .35rem; }
  .scm-footer { padding: .75rem 1rem; }
}
</style>
