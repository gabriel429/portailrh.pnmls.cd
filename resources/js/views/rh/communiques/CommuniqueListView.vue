<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <!-- Hero -->
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-bullhorn me-2"></i>Communiqués officiels</h1>
            <p class="rh-sub">Gestion des annonces diffusees a tous les agents.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <button class="btn-rh main" @click="openCreateModal">
                <i class="fas fa-plus-circle me-1"></i> Nouveau communiqué
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement des communiqués..." />
      </div>

      <!-- Table -->
      <div v-else-if="communiques.length" class="dash-panel mt-3">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Titre</th>
                <th>Urgence</th>
                <th>Signataire</th>
                <th>Date</th>
                <th>Expiration</th>
                <th>Statut</th>
                <th>Lectures</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in communiques" :key="c.id">
                <td>
                  <strong>{{ c.titre }}</strong>
                  <br><small class="text-muted">{{ truncate(c.contenu, 60) }}</small>
                </td>
                <td>
                  <span :class="urgenceBadge(c.urgence)">{{ capitalize(c.urgence) }}</span>
                </td>
                <td>{{ c.signataire || '-' }}</td>
                <td>{{ formatDateTime(c.created_at) }}</td>
                <td>
                  <template v-if="c.date_expiration">
                    {{ formatDate(c.date_expiration) }}
                    <br v-if="isExpired(c.date_expiration)">
                    <small v-if="isExpired(c.date_expiration)" class="text-danger">Expire</small>
                  </template>
                  <span v-else class="text-muted">Illimité</span>
                </td>
                <td>
                  <span :class="c.actif ? 'badge bg-success' : 'badge bg-secondary'">
                    {{ c.actif ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td>
                  <button
                    v-if="canViewReaders(c)"
                    class="btn btn-sm btn-outline-secondary readers-pill"
                    type="button"
                    title="Voir qui a lu"
                    @click="openReadersModal(c)"
                  >
                    <i class="fas fa-users me-1"></i>{{ c.read_count ?? 0 }}
                  </button>
                  <span v-else class="badge bg-light text-dark">{{ c.read_count ?? 0 }}</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <router-link
                      :to="{ name: 'communiques.show-public', params: { id: c.id } }"
                      class="btn btn-sm btn-outline-info"
                      title="Voir"
                    >
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <button
                      class="btn btn-sm btn-outline-primary"
                      title="Modifier"
                      @click="openEditModal(c)"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-sm btn-outline-danger"
                      title="Supprimer"
                      @click="confirmDelete(c)"
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
        <div v-if="meta.last_page > 1" class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <small class="text-muted">
            Affichage {{ meta.from ?? 0 }} a {{ meta.to ?? 0 }} sur {{ meta.total }} communiques
          </small>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <button class="page-link" @click="loadCommuniqués(meta.current_page - 1)">&laquo;</button>
              </li>
              <li
                v-for="page in paginationPages"
                :key="page"
                class="page-item"
                :class="{ active: page === meta.current_page }"
              >
                <button class="page-link" @click="loadCommuniqués(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <button class="page-link" @click="loadCommuniqués(meta.current_page + 1)">&raquo;</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="dash-panel mt-3">
        <div class="text-center py-5 text-muted">
          <i class="fas fa-inbox fa-4x mb-3 d-block"></i>
          <h5>Aucun communiqué publié.</h5>
          <button class="btn btn-primary mt-2" @click="openCreateModal">
            <i class="fas fa-plus me-2"></i>Publier un communiqué
          </button>
        </div>
      </div>

      <ConfirmModal
        :show="showDeleteModal"
        title="Supprimer le communiqué"
        :message="`Supprimer le communiqué '${deleteTarget?.titre ?? ''}' ?`"
        :loading="deleting"
        @confirm="handleDelete"
        @cancel="showDeleteModal = false"
      />
    </div>
  </div>

  <!-- ═══ Create / Edit Modal ═══ -->
  <teleport to="body">
    <div v-if="showFormModal" class="ccm-overlay" @click.self="closeFormModal">
      <div class="ccm-dialog">
        <!-- Header -->
        <div class="ccm-header">
          <div class="ccm-header-icon">
            <i class="fas" :class="editTarget ? 'fa-edit' : 'fa-plus-circle'"></i>
          </div>
          <div>
            <h5 class="ccm-title">{{ editTarget ? 'Modifier le communiqué' : 'Nouveau communiqué' }}</h5>
            <span class="ccm-subtitle">{{ editTarget ? 'Mise à jour du communiqué' : 'Publier un nouveau communiqué officiel' }}</span>
          </div>
          <button class="ccm-close" @click="closeFormModal"><i class="fas fa-times"></i></button>
        </div>

        <!-- Loading -->
        <div v-if="formLoading" class="ccm-loading">
          <div class="spinner-border text-primary"></div>
          <p>Chargement...</p>
        </div>

        <!-- Form -->
        <form v-else @submit.prevent="handleFormSubmit">
          <div class="ccm-body">
            <!-- Errors -->
            <div v-if="formErrors.length" class="ccm-errors">
              <i class="fas fa-exclamation-triangle me-1"></i>
              <ul>
                <li v-for="(err, i) in formErrors" :key="i">{{ err }}</li>
              </ul>
            </div>

            <!-- Titre -->
            <div class="ccm-field">
              <label class="ccm-label">Titre <span class="ccm-req">*</span></label>
              <input v-model="form.titre" type="text" class="ccm-input" required
                placeholder="Ex. : Communiqué relatif aux congés annuels">
            </div>

            <!-- Contenu -->
            <div class="ccm-field">
              <label class="ccm-label">Contenu <span class="ccm-req">*</span></label>
              <textarea v-model="form.contenu" class="ccm-textarea" rows="6" required
                placeholder="Rédigez le contenu du communiqué..."></textarea>
            </div>

            <!-- Row: Urgence + Signataire -->
            <div class="ccm-row">
              <div class="ccm-field">
                <label class="ccm-label">Urgence <span class="ccm-req">*</span></label>
                <select v-model="form.urgence" class="ccm-input" required>
                  <option value="normal">Normal</option>
                  <option value="important">Important</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div class="ccm-field">
                <label class="ccm-label">Signataire</label>
                <input v-model="form.signataire" type="text" class="ccm-input"
                  placeholder="Ex: Le Secretaire Executif National">
              </div>
            </div>

            <!-- Row: Date expiration + Actif -->
            <div class="ccm-row">
              <div class="ccm-field">
                <label class="ccm-label">Date d'expiration</label>
                <input v-model="form.date_expiration" type="date" class="ccm-input">
                <small class="ccm-help">Laisser vide pour un communique sans expiration.</small>
              </div>
              <div class="ccm-field ccm-check-wrap">
                <label class="ccm-check-label">
                  <input v-model="form.actif" type="checkbox" class="ccm-checkbox">
                  <span>Publier immédiatement (actif)</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="ccm-footer">
            <button type="button" class="ccm-btn-cancel" @click="closeFormModal">Annuler</button>
            <button type="submit" class="ccm-btn-submit" :disabled="submitting">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas me-1" :class="editTarget ? 'fa-save' : 'fa-paper-plane'"></i>
              {{ editTarget ? 'Mettre à jour' : 'Publier' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </teleport>

  <teleport to="body">
    <div v-if="showReadersModal" class="readers-overlay" @click.self="closeReadersModal">
      <div class="readers-dialog">
        <div class="readers-header">
          <div class="readers-icon"><i class="fas fa-users"></i></div>
          <div>
            <h5>Agents ayant lu</h5>
            <p>{{ readersTarget?.titre }}</p>
          </div>
          <button class="readers-close" type="button" @click="closeReadersModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div v-if="readersLoading" class="readers-loading">
          <div class="spinner-border text-primary"></div>
          <span>Chargement des lectures...</span>
        </div>

        <div v-else-if="readersList.length" class="readers-list">
          <article v-for="reader in readersList" :key="reader.id" class="reader-row">
            <div class="reader-avatar">{{ readerInitials(reader.user?.name) }}</div>
            <div class="reader-info">
              <strong>{{ reader.user?.name || 'Agent' }}</strong>
              <span>{{ reader.user?.role || 'Rôle non précisé' }}</span>
              <small>
                {{ [reader.user?.departement, reader.user?.province].filter(Boolean).join(' - ') || reader.user?.email || 'Structure non précisée' }}
              </small>
            </div>
            <time>{{ formatDateTime(reader.read_at) }}</time>
          </article>
        </div>

        <div v-else class="readers-empty">
          <i class="fas fa-envelope-open-text"></i>
          <strong>Aucune lecture enregistrée</strong>
          <span>Les agents apparaîtront ici après avoir cliqué sur "J'ai lu".</span>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { list, get, create, update, remove, readers } from '@/api/communiques'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()
const auth = useAuthStore()
const loading = ref(true)
const communiques = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

/* ─── Create/Edit Modal ─── */
const showFormModal = ref(false)
const formLoading = ref(false)
const submitting = ref(false)
const editTarget = ref(null)
const formErrors = ref([])
const showReadersModal = ref(false)
const readersTarget = ref(null)
const readersLoading = ref(false)
const readersList = ref([])

const defaultForm = () => ({
  titre: '',
  contenu: '',
  urgence: 'normal',
  signataire: '',
  date_expiration: '',
  actif: true,
})
const form = ref(defaultForm())

function openCreateModal() {
  editTarget.value = null
  form.value = defaultForm()
  formErrors.value = []
  showFormModal.value = true
}

async function openEditModal(c) {
  editTarget.value = c
  form.value = defaultForm()
  formErrors.value = []
  showFormModal.value = true
  formLoading.value = true
  try {
    const { data } = await get(c.id)
    const d = data.data || data.communique || data
    form.value = {
      titre: d.titre || '',
      contenu: d.contenu || '',
      urgence: d.urgence || 'normal',
      signataire: d.signataire || '',
      date_expiration: d.date_expiration ? d.date_expiration.substring(0, 10) : '',
      actif: d.actif ?? true,
    }
  } catch {
    ui.addToast('Erreur lors du chargement du communiqué.', 'danger')
    showFormModal.value = false
  } finally {
    formLoading.value = false
  }
}

function closeFormModal() {
  showFormModal.value = false
  editTarget.value = null
  formErrors.value = []
}

async function handleFormSubmit() {
  submitting.value = true
  formErrors.value = []
  try {
    if (editTarget.value) {
      await update(editTarget.value.id, form.value)
      ui.addToast('Communiqué mis à jour avec succès.', 'success')
    } else {
      await create(form.value)
      ui.addToast('Communiqué publié avec succès.', 'success')
    }
    closeFormModal()
    await loadCommuniqués(meta.value.current_page)
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      formErrors.value = Object.values(err.response.data.errors).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de la soumission.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

/* ─── List ─── */
const paginationPages = computed(() => {
  const pages = []
  const total = meta.value.last_page
  const current = meta.value.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function loadCommuniqués(page = 1) {
  loading.value = true
  try {
    const { data } = await list({ page })
    communiques.value = data.data
    meta.value = data.meta
  } catch {
    ui.addToast('Erreur lors du chargement des communiques.', 'danger')
  } finally {
    loading.value = false
  }
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

function urgenceBadge(urgence) {
  const map = { urgent: 'badge bg-danger', important: 'badge bg-warning text-dark', normal: 'badge bg-info text-white' }
  return map[urgence] || 'badge bg-secondary'
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

function isExpired(dateStr) {
  if (!dateStr) return false
  return new Date(dateStr) < new Date()
}

function canViewReaders(c) {
  return auth.isSEN || auth.isSuperAdmin || Number(c.auteur?.id) === Number(auth.user?.id)
}

async function openReadersModal(c) {
  readersTarget.value = c
  readersList.value = []
  showReadersModal.value = true
  readersLoading.value = true

  try {
    const { data } = await readers(c.id)
    readersList.value = data.data || []
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors du chargement des lectures.', 'danger')
    showReadersModal.value = false
  } finally {
    readersLoading.value = false
  }
}

function closeReadersModal() {
  showReadersModal.value = false
  readersTarget.value = null
  readersList.value = []
}

function readerInitials(name) {
  if (!name) return 'A'

  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(part => part[0])
    .join('')
    .toUpperCase()
}

function confirmDelete(c) {
  deleteTarget.value = c
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await remove(deleteTarget.value.id)
    ui.addToast('Communiqué supprimé.', 'success')
    showDeleteModal.value = false
    deleteTarget.value = null
    await loadCommuniqués(meta.value.current_page)
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  } finally {
    deleting.value = false
  }
}

onMounted(() => loadCommuniqués())
</script>

<style scoped>
/* ═══ Create/Edit Modal ═══ */
.ccm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(15,23,42,.55); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: ccmFadeIn .2s ease;
}
@keyframes ccmFadeIn { from { opacity: 0; } to { opacity: 1; } }

.ccm-dialog {
  background: #fff; border-radius: 18px;
  width: 100%; max-width: 620px; max-height: 90vh;
  display: flex; flex-direction: column;
  box-shadow: 0 24px 64px rgba(0,0,0,.18);
  animation: ccmSlideUp .25s cubic-bezier(.4,0,.2,1);
  overflow: hidden;
}
@keyframes ccmSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.ccm-header {
  display: flex; align-items: center; gap: .75rem;
  padding: 1.1rem 1.3rem; border-bottom: 1px solid #f1f5f9;
  background: linear-gradient(135deg, #f8fafc, #f0f9ff);
}
.ccm-header-icon {
  width: 42px; height: 42px; border-radius: 12px;
  background: #0077B5; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; flex-shrink: 0;
}
.ccm-title { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.2; }
.ccm-subtitle { font-size: .7rem; color: #94a3b8; font-weight: 500; }
.ccm-close {
  margin-left: auto; background: none; border: none;
  color: #94a3b8; font-size: 1.1rem; cursor: pointer;
  width: 34px; height: 34px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s;
}
.ccm-close:hover { background: #fee2e2; color: #dc2626; }

.ccm-loading {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 3rem 1rem; gap: .75rem;
  color: #64748b; font-size: .85rem;
}

.ccm-body {
  padding: 1.2rem 1.3rem; overflow-y: auto;
  display: flex; flex-direction: column; gap: .85rem;
}

.ccm-errors {
  background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
  padding: .65rem .85rem; color: #dc2626; font-size: .78rem;
}
.ccm-errors ul { margin: .3rem 0 0 1rem; padding: 0; }
.ccm-errors li { margin-bottom: .15rem; }

.ccm-field { display: flex; flex-direction: column; gap: .25rem; }
.ccm-label { font-size: .78rem; font-weight: 600; color: #475569; }
.ccm-req { color: #dc2626; }
.ccm-input {
  border: 1px solid #e2e8f0; border-radius: 8px;
  padding: .45rem .7rem; font-size: .85rem; color: #1e293b;
  outline: none; transition: all .2s;
}
.ccm-input:focus { border-color: #0077B5; box-shadow: 0 0 0 3px rgba(0,119,181,.1); }
.ccm-textarea {
  border: 1px solid #e2e8f0; border-radius: 8px;
  padding: .5rem .7rem; font-size: .85rem; color: #1e293b;
  outline: none; transition: all .2s; resize: vertical;
  font-family: inherit;
}
.ccm-textarea:focus { border-color: #0077B5; box-shadow: 0 0 0 3px rgba(0,119,181,.1); }
.ccm-help { font-size: .68rem; color: #94a3b8; }

.ccm-row { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }

.ccm-check-wrap {
  display: flex; align-items: flex-end; padding-bottom: .3rem;
}
.ccm-check-label {
  display: flex; align-items: center; gap: .5rem;
  font-size: .82rem; color: #475569; cursor: pointer;
}
.ccm-checkbox {
  width: 18px; height: 18px; border-radius: 4px;
  accent-color: #0077B5; cursor: pointer;
}

.ccm-footer {
  display: flex; justify-content: flex-end; gap: .6rem;
  padding: .85rem 1.3rem; border-top: 1px solid #f1f5f9;
  background: #fafbfc;
}
.ccm-btn-cancel {
  background: #f1f5f9; border: 1px solid #e2e8f0;
  color: #64748b; border-radius: 8px;
  padding: .45rem 1rem; font-size: .82rem; font-weight: 600;
  cursor: pointer; transition: all .2s;
}
.ccm-btn-cancel:hover { background: #e2e8f0; color: #475569; }
.ccm-btn-submit {
  background: #0077B5; border: none; color: #fff;
  border-radius: 8px; padding: .45rem 1.2rem;
  font-size: .82rem; font-weight: 700; cursor: pointer;
  transition: all .2s; display: flex; align-items: center;
}
.ccm-btn-submit:hover { background: #005f8f; }
.ccm-btn-submit:disabled { opacity: .6; cursor: not-allowed; }

.readers-pill {
  border-radius: 999px;
  font-weight: 700;
}

.readers-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, .48);
  backdrop-filter: blur(14px) saturate(145%);
}

.readers-dialog {
  width: min(100%, 680px);
  max-height: 88vh;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, .55);
  border-radius: 22px;
  background: linear-gradient(145deg, rgba(255, 255, 255, .9), rgba(240, 249, 255, .7));
  box-shadow: 0 28px 80px rgba(15, 23, 42, .28);
  backdrop-filter: blur(22px) saturate(160%);
  display: flex;
  flex-direction: column;
}

.readers-header {
  display: flex;
  align-items: center;
  gap: .8rem;
  padding: 1rem 1.15rem;
  border-bottom: 1px solid rgba(148, 163, 184, .22);
}

.readers-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: linear-gradient(135deg, #0077B5, #0f766e);
  color: #fff;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.readers-header h5 {
  margin: 0;
  font-size: 1rem;
  font-weight: 850;
  color: #0f172a;
}

.readers-header p {
  margin: .1rem 0 0;
  color: #64748b;
  font-size: .78rem;
}

.readers-close {
  margin-left: auto;
  width: 36px;
  height: 36px;
  border: 0;
  border-radius: 12px;
  color: #64748b;
  background: rgba(255, 255, 255, .66);
}

.readers-close:hover {
  color: #dc2626;
  background: rgba(254, 226, 226, .9);
}

.readers-loading,
.readers-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .7rem;
  padding: 3rem 1rem;
  color: #64748b;
  text-align: center;
}

.readers-empty i {
  font-size: 2rem;
  color: #94a3b8;
}

.readers-empty strong {
  color: #0f172a;
}

.readers-list {
  overflow: auto;
  padding: .75rem;
}

.reader-row {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr) auto;
  gap: .75rem;
  align-items: center;
  padding: .75rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, .58);
  border: 1px solid rgba(148, 163, 184, .18);
}

.reader-row + .reader-row {
  margin-top: .55rem;
}

.reader-avatar {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: #e0f2fe;
  color: #0369a1;
  display: grid;
  place-items: center;
  font-weight: 850;
}

.reader-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: .1rem;
}

.reader-info strong {
  color: #0f172a;
  font-size: .9rem;
}

.reader-info span,
.reader-info small,
.reader-row time {
  color: #64748b;
  font-size: .75rem;
}

.reader-row time {
  white-space: nowrap;
  font-weight: 700;
}

/* ═══ Mobile responsive ═══ */
@media (max-width: 768px) {
  .rh-hero .row { text-align: center; }
  .rh-hero .col-lg-4 { text-align: center; }
  .hero-tools { justify-content: center; display: flex; }
  .rh-title { font-size: 1.3rem; }
  .rh-sub { font-size: 0.85rem; }
  .table { font-size: 0.82rem; }
  .table th, .table td { padding: 0.5rem 0.4rem; white-space: nowrap; }
  .table th:nth-child(3), .table td:nth-child(3),
  .table th:nth-child(4), .table td:nth-child(4),
  .table th:nth-child(5), .table td:nth-child(5) { display: none; }
  .d-flex.gap-1 .btn { padding: 0.25rem 0.4rem; font-size: 0.75rem; }
  .pagination { flex-wrap: wrap; gap: 2px; }
  .page-link { padding: 0.25rem 0.5rem; font-size: 0.78rem; }

  .ccm-dialog { max-width: 100%; border-radius: 14px; }
  .ccm-row { grid-template-columns: 1fr; }
  .ccm-header { padding: .85rem 1rem; }
  .ccm-body { padding: 1rem; }
  .ccm-footer { padding: .7rem 1rem; }
  .reader-row { grid-template-columns: 40px minmax(0, 1fr); }
  .reader-row time { grid-column: 2; }
}

@media (max-width: 576px) {
  .table th:nth-child(2), .table td:nth-child(2) { display: none; }
}
</style>
