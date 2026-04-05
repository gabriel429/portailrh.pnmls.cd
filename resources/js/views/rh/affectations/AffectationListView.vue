<template>
  <div class="py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="fas fa-project-diagram me-2"></i>Affectations</h4>
        <p class="text-muted mb-0 small">Gestion des affectations des agents aux fonctions et structures.</p>
      </div>
      <button class="btn btn-primary" @click="openCreateModal">
        <i class="fas fa-plus me-1"></i> Nouvelle affectation
      </button>
    </div>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body py-3">
        <form @submit.prevent="applySearch" class="row g-2 align-items-center">
          <div class="col-md-8">
            <div class="input-group">
              <input
                type="text"
                class="form-control"
                v-model="searchInput"
                placeholder="Rechercher par nom ou prenom de l'agent..."
              >
              <button class="btn btn-primary" type="submit">
                <i class="fas fa-search"></i>
              </button>
              <button v-if="search" class="btn btn-outline-secondary" type="button" @click="clearSearch">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          <div class="col-md-4 text-end">
            <small class="text-muted" v-if="total > 0">{{ total }} affectation{{ total > 1 ? 's' : '' }} trouvee{{ total > 1 ? 's' : '' }}</small>
          </div>
        </form>
      </div>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="loading" message="Chargement des affectations..." />

    <!-- Table -->
    <template v-else>
      <div v-if="affectations.length > 0" class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Agent</th>
                  <th>Fonction</th>
                  <th>Niveau Admin.</th>
                  <th>Departement</th>
                  <th>Section</th>
                  <th>Province</th>
                  <th>Actif</th>
                  <th style="width: 100px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="aff in affectations" :key="aff.id">
                  <td>
                    <span v-if="aff.agent">{{ aff.agent.prenom }} {{ aff.agent.nom }}</span>
                    <span v-else class="text-muted">N/A</span>
                  </td>
                  <td>
                    <span v-if="aff.fonction">{{ aff.fonction.nom }}</span>
                    <span v-else class="text-muted">N/A</span>
                  </td>
                  <td>
                    <span class="badge" :class="niveauBadge(aff.niveau_administratif)">
                      {{ aff.niveau_administratif }}
                    </span>
                  </td>
                  <td>
                    <span v-if="aff.department">{{ aff.department.nom }}</span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <span v-if="aff.section">{{ aff.section.nom }}</span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <span v-if="aff.province">{{ aff.province.nom }}</span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <span v-if="aff.actif" class="badge bg-success">Actif</span>
                    <span v-else class="badge bg-secondary">Inactif</span>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <router-link
                        :to="{ name: 'rh.affectations.edit', params: { id: aff.id } }"
                        class="btn btn-outline-warning btn-sm"
                        title="Modifier"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button
                        class="btn btn-outline-danger btn-sm"
                        title="Supprimer"
                        @click="confirmDelete(aff)"
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
        <div v-if="lastPage > 1" class="card-footer bg-white border-top">
          <Pagination
            :current-page="currentPage"
            :last-page="lastPage"
            :pages="paginationPages"
            :has-pages="lastPage > 1"
            @page-change="goToPage"
          />
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-project-diagram fa-3x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Aucune affectation</h5>
          <p class="text-muted">Il n'y a aucune affectation enregistree.</p>
          <button class="btn btn-primary" @click="openCreateModal">
            <i class="fas fa-plus me-1"></i> Creer une affectation
          </button>
        </div>
      </div>
    </template>

    <!-- Delete modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer l'affectation"
      :message="deleteMessage"
      :loading="deleting"
      @confirm="doDelete"
      @cancel="showDeleteModal = false"
    />

    <!-- Create Modal -->
    <teleport to="body">
      <div v-if="showCreateModal" class="afm-overlay" @click.self="closeCreateModal">
        <div class="afm-dialog">
          <div class="afm-header">
            <div class="afm-header-icon"><i class="fas fa-project-diagram"></i></div>
            <div>
              <h4 class="afm-title">Nouvelle affectation</h4>
              <p class="afm-subtitle">Affecter un agent a une fonction et structure</p>
            </div>
            <button class="afm-close" @click="closeCreateModal"><i class="fas fa-times"></i></button>
          </div>

          <div v-if="createLoadingData" class="afm-body" style="text-align:center;padding:2rem;">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2 text-muted">Chargement des donnees...</p>
          </div>

          <form v-else @submit.prevent="handleCreateSubmit" class="afm-body">
            <!-- Agent -->
            <div class="afm-field">
              <label class="afm-label">Agent <span class="afm-req">*</span></label>
              <select v-model="createForm.agent_id" class="afm-input" :class="{ 'afm-err': createErrors.agent_id }">
                <option value="">-- Choisir un agent --</option>
                <option v-for="a in options.agents" :key="a.id" :value="a.id">{{ a.prenom }} {{ a.nom }}</option>
              </select>
              <span v-if="createErrors.agent_id" class="afm-err-msg">{{ createErrors.agent_id[0] }}</span>
            </div>

            <!-- Niveau administratif -->
            <div class="afm-field">
              <label class="afm-label">Niveau administratif <span class="afm-req">*</span></label>
              <div class="afm-card-row">
                <button v-for="n in niveauAdminOptions" :key="n.value" type="button" class="afm-opt-card" :class="{ active: createForm.niveau_administratif === n.value }" @click="createForm.niveau_administratif = n.value; onNiveauAdminChange()">
                  {{ n.label }}
                </button>
              </div>
              <span v-if="createErrors.niveau_administratif" class="afm-err-msg">{{ createErrors.niveau_administratif[0] }}</span>
            </div>

            <!-- Type de poste -->
            <div class="afm-field">
              <label class="afm-label">Type de poste <span class="afm-req">*</span></label>
              <select v-model="createForm.niveau" class="afm-input" :class="{ 'afm-err': createErrors.niveau }" @change="createForm.fonction_id = ''">
                <option value="">-- Choisir --</option>
                <option v-for="np in niveauPosteOptions" :key="np.value" :value="np.value">{{ np.label }}</option>
              </select>
              <span v-if="createErrors.niveau" class="afm-err-msg">{{ createErrors.niveau[0] }}</span>
            </div>

            <!-- Fonction -->
            <div class="afm-field">
              <label class="afm-label">Fonction <span class="afm-req">*</span></label>
              <select v-model="createForm.fonction_id" class="afm-input" :class="{ 'afm-err': createErrors.fonction_id }">
                <option value="">-- Choisir une fonction --</option>
                <option v-for="f in filteredFonctions" :key="f.id" :value="f.id">{{ f.nom }}</option>
              </select>
              <span v-if="createErrors.fonction_id" class="afm-err-msg">{{ createErrors.fonction_id[0] }}</span>
            </div>

            <!-- SEN: Department → Section → Cellule -->
            <template v-if="createForm.niveau_administratif === 'SEN'">
              <div class="afm-field">
                <label class="afm-label">Departement</label>
                <select v-model="createForm.department_id" class="afm-input" @change="onDepartmentChange">
                  <option value="">-- Choisir --</option>
                  <option v-for="d in options.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                </select>
              </div>
              <div v-if="filteredSections.length" class="afm-field">
                <label class="afm-label">Section</label>
                <select v-model="createForm.section_id" class="afm-input" @change="onSectionChange">
                  <option value="">-- Choisir --</option>
                  <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.nom }}</option>
                </select>
              </div>
              <div v-if="filteredCellules.length" class="afm-field">
                <label class="afm-label">Cellule</label>
                <select v-model="createForm.cellule_id" class="afm-input">
                  <option value="">-- Choisir --</option>
                  <option v-for="c in filteredCellules" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
            </template>

            <!-- SEP/SEL: Province → Localite -->
            <template v-if="createForm.niveau_administratif === 'SEP' || createForm.niveau_administratif === 'SEL'">
              <div class="afm-field">
                <label class="afm-label">Province</label>
                <select v-model="createForm.province_id" class="afm-input" @change="onProvinceChange">
                  <option value="">-- Choisir --</option>
                  <option v-for="p in options.provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
                </select>
              </div>
              <div v-if="createForm.niveau_administratif === 'SEL' && filteredLocalites.length" class="afm-field">
                <label class="afm-label">Localite</label>
                <select v-model="createForm.localite_id" class="afm-input">
                  <option value="">-- Choisir --</option>
                  <option v-for="l in filteredLocalites" :key="l.id" :value="l.id">{{ l.nom }}</option>
                </select>
              </div>
            </template>

            <!-- Dates -->
            <div class="afm-row">
              <div class="afm-field afm-half">
                <label class="afm-label">Date debut</label>
                <input v-model="createForm.date_debut" type="date" class="afm-input">
              </div>
              <div class="afm-field afm-half">
                <label class="afm-label">Date fin</label>
                <input v-model="createForm.date_fin" type="date" class="afm-input">
                <span v-if="createErrors.date_fin" class="afm-err-msg">{{ createErrors.date_fin[0] }}</span>
              </div>
            </div>

            <!-- Actif -->
            <div class="afm-field">
              <label class="afm-label">Statut</label>
              <div class="afm-card-row">
                <button type="button" class="afm-opt-card" :class="{ active: createForm.actif === true }" @click="createForm.actif = true">
                  <i class="fas fa-check-circle me-1"></i> Actif
                </button>
                <button type="button" class="afm-opt-card" :class="{ active: createForm.actif === false }" @click="createForm.actif = false">
                  <i class="fas fa-times-circle me-1"></i> Inactif
                </button>
              </div>
            </div>

            <!-- Remarque -->
            <div class="afm-field">
              <label class="afm-label">Remarque</label>
              <textarea v-model="createForm.remarque" class="afm-input afm-textarea" rows="2" placeholder="Remarque (facultatif)..."></textarea>
            </div>

            <!-- Footer -->
            <div class="afm-footer">
              <button type="button" class="afm-btn-cancel" @click="closeCreateModal">Annuler</button>
              <button type="submit" class="afm-btn-submit" :disabled="createSubmitting">
                <i v-if="createSubmitting" class="fas fa-spinner fa-spin me-1"></i>
                <i v-else class="fas fa-plus me-1"></i>
                {{ createSubmitting ? 'Creation...' : 'Creer l\'affectation' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import Pagination from '@/components/common/Pagination.vue'

const ui = useUiStore()

// State
const loading = ref(true)
const affectations = ref([])
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const searchInput = ref('')
const search = ref('')

// Delete
const showDeleteModal = ref(false)
const affToDelete = ref(null)
const deleting = ref(false)

const deleteMessage = computed(() => {
  if (!affToDelete.value) return ''
  const agent = affToDelete.value.agent
  const name = agent ? `${agent.prenom} ${agent.nom}` : 'cet agent'
  return `Etes-vous sur de vouloir supprimer l'affectation de ${name} ? Cette action est irreversible.`
})

// Pagination pages
const paginationPages = computed(() => {
  const pages = []
  const total = lastPage.value
  const current = currentPage.value
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    if (current < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

function niveauBadge(niveau) {
  switch (niveau) {
    case 'SEN': return 'bg-primary'
    case 'SEP': return 'bg-info text-dark'
    case 'SEL': return 'bg-warning text-dark'
    default: return 'bg-secondary'
  }
}

async function fetchAffectations() {
  loading.value = true
  try {
    const params = { page: currentPage.value }
    if (search.value) params.search = search.value
    const { data } = await client.get('/affectations', { params })
    affectations.value = data.data || []
    total.value = data.total || 0
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
  } catch (err) {
    console.error('Error fetching affectations:', err)
    ui.addToast('Erreur lors du chargement des affectations', 'danger')
  } finally {
    loading.value = false
  }
}

function applySearch() {
  search.value = searchInput.value
  currentPage.value = 1
  fetchAffectations()
}

function clearSearch() {
  searchInput.value = ''
  search.value = ''
  currentPage.value = 1
  fetchAffectations()
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  currentPage.value = page
  fetchAffectations()
}

function confirmDelete(aff) {
  affToDelete.value = aff
  showDeleteModal.value = true
}

async function doDelete() {
  if (!affToDelete.value) return
  deleting.value = true
  try {
    await client.delete(`/affectations/${affToDelete.value.id}`)
    ui.addToast('Affectation supprimee avec succes', 'success')
    showDeleteModal.value = false
    affToDelete.value = null
    await fetchAffectations()
  } catch (err) {
    console.error('Error deleting affectation:', err)
    ui.addToast('Erreur lors de la suppression', 'danger')
  } finally {
    deleting.value = false
  }
}

/* ── Create Modal ── */
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createErrors = ref({})
const createLoadingData = ref(false)
const options = reactive({
  agents: [], fonctions: [], departments: [], sections: [], cellules: [], provinces: [], localites: []
})

const niveauAdminOptions = [
  { value: 'SEN', label: 'SEN (National)' },
  { value: 'SEP', label: 'SEP (Provincial)' },
  { value: 'SEL', label: 'SEL (Local)' },
]
const niveauPosteOptions = [
  { value: 'direction', label: 'Direction' },
  { value: 'service_rattache', label: 'Service rattache' },
  { value: 'departement', label: 'Departement' },
  { value: 'section', label: 'Section' },
  { value: 'cellule', label: 'Cellule' },
  { value: 'province', label: 'Province' },
  { value: 'local', label: 'Local' },
]

function defaultCreateForm() {
  return {
    agent_id: '',
    fonction_id: '',
    niveau_administratif: '',
    niveau: '',
    department_id: '',
    section_id: '',
    cellule_id: '',
    province_id: '',
    localite_id: '',
    date_debut: '',
    date_fin: '',
    actif: true,
    remarque: '',
  }
}
const createForm = ref(defaultCreateForm())

const typePosteMap = {
  direction: 'direction',
  service_rattache: 'service_rattache',
  departement: 'departement',
  section: 'section',
  cellule: 'cellule',
  province: 'province',
  local: 'local',
}

const filteredFonctions = computed(() => {
  if (!createForm.value.niveau_administratif) return options.fonctions
  return options.fonctions.filter(f => {
    const matchNiveau = f.niveau_administratif === createForm.value.niveau_administratif || f.niveau_administratif === 'TOUS'
    const posteType = typePosteMap[createForm.value.niveau] || null
    const matchType = !posteType || f.type_poste === posteType || f.type_poste === 'appui'
    return matchNiveau && matchType
  })
})
const filteredSections = computed(() => {
  if (!createForm.value.department_id) return []
  return options.sections.filter(s => s.department_id == createForm.value.department_id)
})
const filteredCellules = computed(() => {
  if (!createForm.value.section_id) return []
  return options.cellules.filter(c => c.section_id == createForm.value.section_id)
})
const filteredLocalites = computed(() => {
  if (!createForm.value.province_id) return []
  return options.localites.filter(l => l.province_id == createForm.value.province_id)
})

async function openCreateModal() {
  createForm.value = defaultCreateForm()
  createErrors.value = {}
  showCreateModal.value = true
  if (options.agents.length === 0) {
    createLoadingData.value = true
    try {
      const { data } = await client.get('/affectations/form-data')
      options.agents = data.agents || []
      options.fonctions = data.fonctions || []
      options.departments = data.departments || []
      options.sections = data.sections || []
      options.cellules = data.cellules || []
      options.provinces = data.provinces || []
      options.localites = data.localites || []
    } catch (err) {
      console.error('Error loading form data:', err)
      ui.addToast('Erreur lors du chargement des donnees du formulaire.', 'danger')
    }
    finally { createLoadingData.value = false }
  }
}

function closeCreateModal() { showCreateModal.value = false }

function onNiveauAdminChange() {
  createForm.value.fonction_id = ''
  if (createForm.value.niveau_administratif === 'SEN') {
    createForm.value.province_id = ''
    createForm.value.localite_id = ''
  } else {
    createForm.value.department_id = ''
    createForm.value.section_id = ''
    createForm.value.cellule_id = ''
  }
}
function onDepartmentChange() {
  createForm.value.section_id = ''
  createForm.value.cellule_id = ''
}
function onSectionChange() {
  createForm.value.cellule_id = ''
}
function onProvinceChange() {
  createForm.value.localite_id = ''
}

async function handleCreateSubmit() {
  createSubmitting.value = true
  createErrors.value = {}
  try {
    const payload = {}
    for (const [k, v] of Object.entries(createForm.value)) {
      if (v !== '' && v !== null) payload[k] = v
    }
    await client.post('/affectations', payload)
    ui.addToast('Affectation creee avec succes !', 'success')
    showCreateModal.value = false
    fetchAffectations()
  } catch (err) {
    if (err.response?.status === 422) {
      createErrors.value = err.response.data.errors || {}
    } else {
      ui.addToast('Erreur lors de la creation.', 'danger')
    }
  } finally {
    createSubmitting.value = false
  }
}

onMounted(() => {
  fetchAffectations()
})
</script>

<style scoped>
/* ── Mobile responsive styles ── */
@media (max-width: 767.98px) {
  /* Header stack */
  .d-flex.justify-content-between.align-items-center.mb-4 {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }

  /* Search bar */
  .card-body .row {
    flex-direction: column;
  }
  .col-md-4.text-end {
    text-align: center !important;
  }

  /* Table compact */
  .table {
    font-size: 0.82rem;
  }
  .table th,
  .table td {
    padding: 0.5rem 0.4rem;
    white-space: nowrap;
  }

  /* Hide Niveau Admin (3rd), Departement (4th), Section (5th), Province (6th) */
  .table th:nth-child(3),
  .table td:nth-child(3),
  .table th:nth-child(4),
  .table td:nth-child(4),
  .table th:nth-child(5),
  .table td:nth-child(5),
  .table th:nth-child(6),
  .table td:nth-child(6) {
    display: none;
  }

  /* Compact action buttons */
  .btn-group .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
  }
}

@media (max-width: 576px) {
  /* Keep only Agent (1st), Fonction (2nd), Actif (7th), Actions (8th) */
  /* Already hiding 3-6 above; no additional hides needed */
  .table {
    font-size: 0.78rem;
  }
  .table th,
  .table td {
    padding: 0.35rem 0.3rem;
  }
}

/* ── Create Modal (afm-*) ── */
.afm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: afmFadeIn .2s ease;
}
@keyframes afmFadeIn { from { opacity: 0; } to { opacity: 1; } }

.afm-dialog {
  background: #fff; border-radius: 20px; width: 100%; max-width: 620px;
  max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.25);
  animation: afmSlideUp .25s ease;
}
@keyframes afmSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.afm-header {
  display: flex; align-items: center; gap: .8rem;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f3f4f6;
  background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
  border-radius: 20px 20px 0 0;
}
.afm-header-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #0077B5, #005a8c);
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.afm-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.afm-subtitle { font-size: .78rem; color: #64748b; margin: 0; }
.afm-close {
  margin-left: auto; background: none; border: none; cursor: pointer;
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; color: #94a3b8;
  transition: all .2s; font-size: 1rem;
}
.afm-close:hover { background: #fee2e2; color: #ef4444; }

.afm-body { padding: 1.25rem 1.5rem; }

.afm-field { margin-bottom: 1rem; }
.afm-label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
.afm-req { color: #ef4444; }

.afm-input {
  width: 100%; padding: .55rem .8rem; border: 1.5px solid #e2e8f0;
  border-radius: 10px; font-size: .85rem; color: #1e293b;
  background: #f8fafc; transition: all .2s;
}
.afm-input:focus { outline: none; border-color: #0077B5; background: #fff; box-shadow: 0 0 0 3px rgba(0,119,181,.1); }
.afm-input.afm-err { border-color: #ef4444; }
.afm-textarea { resize: vertical; min-height: 60px; }

.afm-err-msg { display: block; font-size: .72rem; color: #ef4444; margin-top: .25rem; }

.afm-row { display: flex; gap: .75rem; }
.afm-half { flex: 1; min-width: 0; }

.afm-card-row { display: flex; gap: .5rem; flex-wrap: wrap; }
.afm-opt-card {
  padding: .45rem .85rem; border-radius: 10px; font-size: .8rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b;
  cursor: pointer; transition: all .2s;
}
.afm-opt-card:hover { border-color: #0077B5; color: #0077B5; }
.afm-opt-card.active { background: linear-gradient(135deg, #0077B5, #005a8c); border-color: #0077B5; color: #fff; }

.afm-footer {
  display: flex; gap: .75rem; justify-content: flex-end;
  padding-top: 1rem; border-top: 1px solid #f3f4f6; margin-top: .5rem;
}
.afm-btn-cancel {
  padding: .55rem 1.2rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .2s;
}
.afm-btn-cancel:hover { background: #f3f4f6; }
.afm-btn-submit {
  padding: .55rem 1.5rem; border-radius: 10px; font-size: .82rem; font-weight: 700;
  border: none; background: linear-gradient(135deg, #0077B5, #005a8c); color: #fff;
  cursor: pointer; transition: all .2s;
}
.afm-btn-submit:hover { box-shadow: 0 4px 16px rgba(0,119,181,.3); }
.afm-btn-submit:disabled { opacity: .6; cursor: not-allowed; }

@media (max-width: 576px) {
  .afm-dialog { max-width: 100%; border-radius: 16px; }
  .afm-header { padding: 1rem 1.1rem; border-radius: 16px 16px 0 0; }
  .afm-body { padding: 1rem 1.1rem; }
  .afm-row { flex-direction: column; gap: 0; }
  .afm-card-row { flex-direction: column; }
  .afm-opt-card { text-align: center; }
  .afm-footer { flex-direction: column; }
  .afm-btn-cancel, .afm-btn-submit { width: 100%; text-align: center; }
}
</style>
