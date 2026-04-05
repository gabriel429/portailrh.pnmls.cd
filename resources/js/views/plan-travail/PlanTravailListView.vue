<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="pt-hero">
      <div class="pt-hero-body">
        <div class="pt-hero-text">
          <h2><i class="fas fa-calendar-alt me-2"></i>Plan de Travail Annuel {{ filters.annee }}</h2>
          <p>Activites planifiees, en cours et terminees de votre unite organisationnelle.</p>
          <div class="pt-hero-stats">
            <div>
              <div class="pt-hero-stat-val">{{ stats.total }}</div>
              <div class="pt-hero-stat-lbl">Total</div>
            </div>
            <div>
              <div class="pt-hero-stat-val">{{ stats.avg_pourcentage }}%</div>
              <div class="pt-hero-stat-lbl">Progression</div>
            </div>
          </div>
        </div>
        <div class="pt-hero-actions">
          <button v-if="canEdit" class="pt-hero-btn" @click="openCreateModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle activite
          </button>
          <div class="pt-hero-filters">
            <select v-model="filters.annee" class="pt-filter-select" @change="loadPlan">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="accessDenied && !loading" class="pt-alert pt-alert-danger">
      <div class="pt-alert-icon"><i class="fas fa-lock"></i></div>
      <div>
        <h5>Acces restreint</h5>
        <p>{{ accessDeniedMessage }}</p>
      </div>
    </div>

    <!-- Status filter cards (always visible) -->
    <div class="pt-filter-grid">
      <button class="pt-filter-card pt-filter-all" :class="{ active: !filters.statut && !filters.trimestre }" @click="setFilter('', '')">
        <div class="pt-filter-icon"><i class="fas fa-th-large"></i></div>
        <div class="pt-filter-info">
          <div class="pt-filter-name">Toutes</div>
          <div class="pt-filter-count">{{ stats.total }} activite{{ stats.total > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button class="pt-filter-card pt-filter-planned" :class="{ active: filters.statut === 'planifiee' }" @click="setFilter('planifiee', '')">
        <div class="pt-filter-icon"><i class="fas fa-clock"></i></div>
        <div class="pt-filter-info">
          <div class="pt-filter-name">Planifiees</div>
          <div class="pt-filter-count">{{ stats.planifiee }} activite{{ stats.planifiee > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button class="pt-filter-card pt-filter-progress" :class="{ active: filters.statut === 'en_cours' }" @click="setFilter('en_cours', '')">
        <div class="pt-filter-icon"><i class="fas fa-spinner"></i></div>
        <div class="pt-filter-info">
          <div class="pt-filter-name">En cours</div>
          <div class="pt-filter-count">{{ stats.en_cours }} activite{{ stats.en_cours > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button class="pt-filter-card pt-filter-done" :class="{ active: filters.statut === 'terminee' }" @click="setFilter('terminee', '')">
        <div class="pt-filter-icon"><i class="fas fa-check-circle"></i></div>
        <div class="pt-filter-info">
          <div class="pt-filter-name">Terminees</div>
          <div class="pt-filter-count">{{ stats.terminee }} activite{{ stats.terminee > 1 ? 's' : '' }}</div>
        </div>
      </button>
    </div>

    <!-- Trimester filter (always visible) -->
    <div class="pt-trim-bar">
      <button
        v-for="t in trimestres" :key="t.value"
        class="pt-trim-btn"
        :class="{ active: filters.trimestre === t.value }"
        @click="setFilter(filters.statut, t.value)"
      >
        {{ t.label }}
      </button>
    </div>

    <!-- Global progress bar -->
    <div v-if="stats.total > 0" class="pt-progress-bar">
      <div class="pt-progress-header">
        <span class="pt-progress-label">Progression globale</span>
        <span class="pt-progress-val">{{ stats.avg_pourcentage }}%</span>
      </div>
      <div class="pt-progress-track">
        <div class="pt-progress-fill" :style="{ width: stats.avg_pourcentage + '%' }"></div>
      </div>
    </div>

    <!-- Section header when filtered -->
    <div v-if="filters.statut || filters.trimestre" class="pt-section-header">
      <div class="pt-section-title">
        <i class="fas fa-filter" style="color:#7c3aed;"></i>
        <span v-if="filters.statut">{{ statutLabel(filters.statut) }}</span>
        <span v-if="filters.statut && filters.trimestre"> &middot; </span>
        <span v-if="filters.trimestre">{{ triLabel(filters.trimestre) }}</span>
        <span class="pt-section-badge">{{ flatActivites.length }} activite{{ flatActivites.length > 1 ? 's' : '' }}</span>
      </div>
      <button class="pt-back-btn" @click="setFilter('', '')">
        <i class="fas fa-arrow-left"></i> Tout afficher
      </button>
    </div>

    <!-- Loading spinner (initial load only) -->
    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement du plan de travail..." />
    </div>

    <template v-else>
      <!-- Activity cards -->
      <div v-if="flatActivites.length" class="pt-grid" :class="{ 'pt-filtering': filtering }">
        <div v-for="a in flatActivites" :key="a.id" class="pt-card">
          <div class="pt-card-top">
            <div class="pt-card-status-icon" :class="statutIconClass(a.statut)">
              <i :class="statutIconName(a.statut)"></i>
            </div>
            <div class="pt-card-info">
              <router-link :to="{ name: 'plan-travail.show', params: { id: a.id } }" class="pt-card-title">
                {{ a.titre }}
              </router-link>
              <div v-if="a.description" class="pt-card-desc">{{ truncate(a.description, 100) }}</div>
              <div class="pt-card-tags">
                  <span v-if="a.categorie" class="pt-meta-badge">{{ a.categorie }}</span>
                <span :class="statutBadgeClass(a.statut)">{{ statutLabel(a.statut) }}</span>
              </div>
              <div class="pt-quarter-strip" :aria-label="`Chronogramme ${a.titre}`">
                <span
                  v-for="slot in trimestreSlots(a)"
                  :key="`${a.id}-${slot.label}`"
                  class="pt-quarter-chip"
                  :class="{ active: slot.active }"
                >
                  {{ slot.label }}
                </span>
              </div>
            </div>
          </div>
          <div class="pt-card-meta">
            <span class="pt-meta-item">
              <i class="fas fa-building me-1"></i>{{ a.niveau_administratif }}
              <template v-if="a.departement"> - {{ a.departement.nom }}</template>
            </span>
              <span v-if="a.responsable_code" class="pt-meta-item">
                <i class="fas fa-user-tie me-1"></i>{{ a.responsable_code }}
              </span>
              <span v-if="provinceSummary(a)" class="pt-meta-item">
                <i class="fas fa-map-marker-alt me-1"></i>{{ provinceSummary(a) }}
              </span>
              <span v-if="a.cout_cdf !== null && a.cout_cdf !== undefined" class="pt-meta-item">
                <i class="fas fa-coins me-1"></i>{{ formatCurrency(a.cout_cdf) }} CDF
              </span>
            <span v-if="a.date_debut" class="pt-meta-item">
              <i class="fas fa-calendar me-1"></i>{{ formatShortDate(a.date_debut) }}
              <template v-if="a.date_fin"> &rarr; {{ formatShortDate(a.date_fin) }}</template>
            </span>
          </div>
          <div class="pt-card-progress">
            <div class="pt-card-progress-track">
              <div
                class="pt-card-progress-fill"
                :class="a.pourcentage >= 100 ? 'done' : ''"
                :style="{ width: a.pourcentage + '%' }"
              ></div>
            </div>
            <span class="pt-card-progress-val">{{ a.pourcentage }}%</span>
          </div>
          <div class="pt-card-footer">
            <span class="pt-card-date">
              <i class="fas fa-clock me-1"></i>{{ formatDate(a.created_at) }}
            </span>
            <div class="pt-card-actions">
              <router-link :to="{ name: 'plan-travail.show', params: { id: a.id } }" class="pt-act-btn pt-act-view">
                <i class="fas fa-eye"></i> Voir
              </router-link>
              <button v-if="canEdit" class="pt-act-btn pt-act-edit" @click="openEditModal(a.id)">
                <i class="fas fa-edit"></i> Modifier
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="pt-empty">
        <div class="pt-empty-icon"><i class="fas fa-calendar-alt"></i></div>
        <template v-if="filters.statut || filters.trimestre">
          <h5>Aucune activite trouvee</h5>
          <p>Il n'y a pas d'activites correspondant a ces filtres.</p>
          <button class="pt-back-btn mt-3" style="display:inline-flex;" @click="setFilter('', '')">
            <i class="fas fa-arrow-left"></i> Tout afficher
          </button>
        </template>
        <template v-else>
          <h5>Aucune activite pour l'annee {{ filters.annee }}</h5>
          <p>Le plan de travail n'a pas encore d'activites enregistrees.</p>
          <button v-if="canEdit" class="pt-hero-btn mt-3" style="display:inline-flex;" @click="openCreateModal">
            <i class="fas fa-plus-circle me-1"></i> Creer la premiere activite
          </button>
        </template>
      </div>
    </template>

    <!-- Create Modal -->
    <teleport to="body">
      <div v-if="showCreateModal" class="ptm-overlay" @click.self="closeCreateModal">
        <div class="ptm-dialog">
          <div class="ptm-header">
            <div class="ptm-header-icon"><i class="fas fa-plus-circle"></i></div>
            <div>
              <h4 class="ptm-title">Nouvelle activite</h4>
              <p class="ptm-subtitle">Ajouter une activite au plan de travail</p>
            </div>
            <button class="ptm-close" @click="closeCreateModal"><i class="fas fa-times"></i></button>
          </div>

          <form @submit.prevent="handleCreateSubmit" class="ptm-body">
            <!-- Titre -->
            <div class="ptm-field">
              <label class="ptm-label">Titre <span class="ptm-req">*</span></label>
              <input v-model="createForm.titre" type="text" class="ptm-input" :class="{ 'ptm-err': createErrors.titre }" placeholder="Titre de l'activite" maxlength="255">
              <span v-if="createErrors.titre" class="ptm-err-msg">{{ createErrors.titre[0] }}</span>
            </div>

              <div class="ptm-row">
                <div class="ptm-field ptm-half">
                  <label class="ptm-label">Rubrique / categorie</label>
                  <input v-model="createForm.categorie" list="pta-categories-list" type="text" class="ptm-input" placeholder="Ex. Leadership">
                  <datalist id="pta-categories-list">
                    <option v-for="item in createCategories" :key="item" :value="item"></option>
                  </datalist>
                </div>
                <div class="ptm-field ptm-half">
                  <label class="ptm-label">Responsable</label>
                  <input v-model="createForm.responsable_code" list="pta-responsables-list" type="text" class="ptm-input" placeholder="Ex. DPSE">
                  <datalist id="pta-responsables-list">
                    <option v-for="item in createResponsables" :key="item" :value="item"></option>
                  </datalist>
                </div>
              </div>

              <div class="ptm-field">
                <label class="ptm-label">Cout en CDF</label>
                <input v-model.number="createForm.cout_cdf" type="number" class="ptm-input" min="0" step="0.01" placeholder="0">
              </div>

            <div class="ptm-field">
              <label class="ptm-label">Objectif strategique</label>
              <textarea v-model="createForm.objectif" class="ptm-input ptm-textarea" rows="2" placeholder="Objectif strategique de l'activite"></textarea>
            </div>

            <div class="ptm-field">
              <label class="ptm-label">Resultat attendu</label>
              <textarea v-model="createForm.resultat_attendu" class="ptm-input ptm-textarea" rows="2" placeholder="Resultat attendu"></textarea>
            </div>

            <!-- Niveau administratif -->
            <div class="ptm-field">
              <label class="ptm-label">Niveau administratif <span class="ptm-req">*</span></label>
              <div class="ptm-card-row">
                <button v-for="n in niveauOptions" :key="n.value" type="button" class="ptm-opt-card" :class="{ active: createForm.niveau_administratif === n.value }" @click="createForm.niveau_administratif = n.value; onNiveauChange()">
                  {{ n.label }}
                </button>
              </div>
              <span v-if="createErrors.niveau_administratif" class="ptm-err-msg">{{ createErrors.niveau_administratif[0] }}</span>
            </div>

            <!-- Departement (SEN) -->
            <div v-if="createForm.niveau_administratif === 'SEN'" class="ptm-field">
              <label class="ptm-label">Departement</label>
              <select v-model="createForm.departement_id" class="ptm-input">
                <option value="">-- Choisir --</option>
                <option v-for="d in createDepartments" :key="d.id" :value="d.id">{{ d.nom }}</option>
              </select>
            </div>

            <!-- Province (SEP / SEL) -->
            <div v-if="createForm.niveau_administratif === 'SEL'" class="ptm-field">
              <label class="ptm-label">Province</label>
              <select v-model="createForm.province_id" class="ptm-input">
                <option value="">-- Choisir --</option>
                <option v-for="p in createProvinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
              </select>
            </div>

            <div v-if="createForm.niveau_administratif === 'SEP'" class="ptm-field">
              <label class="ptm-label">Provinces concernees</label>
              <select v-model="createForm.province_ids" class="ptm-input" multiple size="6">
                <option v-for="p in createProvinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
              </select>
            </div>

            <!-- Localite (SEL) -->
            <div v-if="createForm.niveau_administratif === 'SEL'" class="ptm-field">
              <label class="ptm-label">Localite</label>
              <select v-model="createForm.localite_id" class="ptm-input">
                <option value="">-- Choisir --</option>
                <option v-for="l in createLocalites" :key="l.id" :value="l.id">{{ l.nom }}</option>
              </select>
            </div>

            <!-- Row: Annee + Trimestre -->
            <div class="ptm-row">
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Annee <span class="ptm-req">*</span></label>
                <input v-model.number="createForm.annee" type="number" class="ptm-input" min="2020" max="2040">
                <span v-if="createErrors.annee" class="ptm-err-msg">{{ createErrors.annee[0] }}</span>
              </div>
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Trimestre</label>
                <select v-model="createForm.trimestre" class="ptm-input">
                  <option v-for="t in trimestreOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
              </div>
            </div>

            <div class="ptm-field">
              <label class="ptm-label">Chronogramme</label>
              <div class="ptm-card-row">
                <label class="ptm-check"><input v-model="createForm.trimestre_1" type="checkbox"> T1</label>
                <label class="ptm-check"><input v-model="createForm.trimestre_2" type="checkbox"> T2</label>
                <label class="ptm-check"><input v-model="createForm.trimestre_3" type="checkbox"> T3</label>
                <label class="ptm-check"><input v-model="createForm.trimestre_4" type="checkbox"> T4</label>
              </div>
            </div>

            <div class="ptm-field">
              <label class="ptm-label">Validation</label>
              <select v-model="createForm.validation_niveau" class="ptm-input">
                <option value="">-- Choisir --</option>
                <option value="direction">Direction</option>
                <option value="coordination_nationale">Coordination nationale</option>
                <option value="coordination_provinciale">Coordination provinciale</option>
              </select>
            </div>

            <!-- Statut + Pourcentage -->
            <div class="ptm-row">
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Statut <span class="ptm-req">*</span></label>
                <select v-model="createForm.statut" class="ptm-input">
                  <option v-for="s in statutOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <span v-if="createErrors.statut" class="ptm-err-msg">{{ createErrors.statut[0] }}</span>
              </div>
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Pourcentage</label>
                <input v-model.number="createForm.pourcentage" type="number" class="ptm-input" min="0" max="100">
              </div>
            </div>

            <!-- Dates -->
            <div class="ptm-row">
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Date debut</label>
                <input v-model="createForm.date_debut" type="date" class="ptm-input">
              </div>
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Date fin</label>
                <input v-model="createForm.date_fin" type="date" class="ptm-input">
              </div>
            </div>

            <!-- Description -->
            <div class="ptm-field">
              <label class="ptm-label">Description</label>
              <textarea v-model="createForm.description" class="ptm-input ptm-textarea" rows="3" placeholder="Description de l'activite..."></textarea>
            </div>

            <!-- Observations -->
            <div class="ptm-field">
              <label class="ptm-label">Observations</label>
              <textarea v-model="createForm.observations" class="ptm-input ptm-textarea" rows="2" placeholder="Observations (facultatif)..."></textarea>
            </div>

            <!-- Footer -->
            <div class="ptm-footer">
              <button type="button" class="ptm-btn-cancel" @click="closeCreateModal">Annuler</button>
              <button type="submit" class="ptm-btn-submit" :disabled="createSubmitting">
                <i v-if="createSubmitting" class="fas fa-spinner fa-spin me-1"></i>
                <i v-else class="fas fa-plus-circle me-1"></i>
                {{ createSubmitting ? 'Creation...' : 'Creer l\'activite' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

    <!-- Edit modal -->
    <PlanTravailEditModal
      :show="showEditModal"
      :plan-travail-id="editingPlanTravailId"
      @close="closeEditModal"
      @updated="handlePlanTravailUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { list, create, getCreateData } from '@/api/planTravail'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import PlanTravailEditModal from '@/components/plan-travail/PlanTravailEditModal.vue'

const ui = useUiStore()
const loading = ref(true)
const filtering = ref(false)
const initialLoadDone = ref(false)
const accessDenied = ref(false)
const accessDeniedMessage = ref("Vous ne pouvez consulter que le PTA de votre departement.")
const groupees = ref({})
const stats = ref({ total: 0, planifiee: 0, en_cours: 0, terminee: 0, avg_pourcentage: 0 })
const canEdit = ref(false)
const filters = ref({ annee: new Date().getFullYear(), trimestre: '', statut: '' })

const trimestres = [
  { value: '', label: 'Tous' },
  { value: 'T1', label: 'T1' },
  { value: 'T2', label: 'T2' },
  { value: 'T3', label: 'T3' },
  { value: 'T4', label: 'T4' },
]

const years = computed(() => {
  const arr = []
  for (let y = new Date().getFullYear() + 1; y >= 2023; y--) arr.push(y)
  return arr
})

const flatActivites = computed(() => {
  const all = []
  for (const tri of Object.keys(groupees.value)) {
    for (const a of groupees.value[tri]) {
      all.push({ ...a, trimestre: tri })
    }
  }
  return all
})

async function loadPlan() {
  if (!initialLoadDone.value) {
    loading.value = true
  }
  filtering.value = true
  try {
    accessDenied.value = false
    const params = { annee: filters.value.annee }
    if (filters.value.trimestre) params.trimestre = filters.value.trimestre
    if (filters.value.statut) params.statut = filters.value.statut
    const { data } = await list(params)
    groupees.value = data.groupees
    stats.value = data.stats
    canEdit.value = data.canEdit
  } catch (err) {
    if (err.response?.status === 403) {
      accessDenied.value = true
      accessDeniedMessage.value = err.response?.data?.message || 'Vous ne pouvez consulter que le PTA de votre departement.'
      groupees.value = {}
      stats.value = { total: 0, planifiee: 0, en_cours: 0, terminee: 0, avg_pourcentage: 0 }
      canEdit.value = false
      ui.addToast(accessDeniedMessage.value, 'warning')
    } else {
      ui.addToast('Erreur lors du chargement du plan de travail.', 'danger')
    }
  } finally {
    loading.value = false
    filtering.value = false
    initialLoadDone.value = true
  }
}

function setFilter(statut, trimestre) {
  filters.value.statut = statut
  filters.value.trimestre = trimestre
  loadPlan()
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

function triLabel(tri) {
  const map = {
    T1: '1er Trimestre (Jan-Mar)',
    T2: '2e Trimestre (Avr-Jun)',
    T3: '3e Trimestre (Jul-Sep)',
    T4: '4e Trimestre (Oct-Dec)',
    Annuel: 'Activites annuelles',
  }
  return map[tri] || tri
}

function statutLabel(statut) {
  const map = { terminee: 'Terminee', en_cours: 'En cours', planifiee: 'Planifiee' }
  return map[statut] || statut
}

function statutBadgeClass(statut) {
  const map = { terminee: 'pt-badge done', en_cours: 'pt-badge progress', planifiee: 'pt-badge planned' }
  return map[statut] || 'pt-badge planned'
}

function statutIconClass(statut) {
  const map = { terminee: 'pt-si-done', en_cours: 'pt-si-progress', planifiee: 'pt-si-planned' }
  return map[statut] || 'pt-si-planned'
}

function statutIconName(statut) {
  const map = { terminee: 'fas fa-check-circle', en_cours: 'fas fa-spinner', planifiee: 'fas fa-clock' }
  return map[statut] || 'fas fa-clock'
}

function formatShortDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

/* ── Create modal ── */
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createErrors = ref({})
const createDepartments = ref([])
const createProvinces = ref([])
const createLocalites = ref([])
const createCategories = ref([])
const createResponsables = ref([])

/* ── Edit modal ── */
const showEditModal = ref(false)
const editingPlanTravailId = ref(null)

const niveauOptions = [
  { value: 'SEN', label: 'SEN (National)' },
  { value: 'SEP', label: 'SEP (Provincial)' },
  { value: 'SEL', label: 'SEL (Local)' },
]
const statutOptions = [
  { value: 'planifiee', label: 'Planifiee' },
  { value: 'en_cours', label: 'En cours' },
  { value: 'terminee', label: 'Terminee' },
]
const trimestreOptions = [
  { value: '', label: 'Annuel' },
  { value: 'T1', label: 'T1 (Jan-Mar)' },
  { value: 'T2', label: 'T2 (Avr-Jun)' },
  { value: 'T3', label: 'T3 (Jul-Sep)' },
  { value: 'T4', label: 'T4 (Oct-Dec)' },
]

function defaultCreateForm() {
  return {
    titre: '',
    categorie: '',
    objectif: '',
    responsable_code: '',
    cout_cdf: '',
    niveau_administratif: '',
    validation_niveau: '',
    departement_id: '',
    province_id: '',
    province_ids: [],
    localite_id: '',
    annee: new Date().getFullYear(),
    trimestre: '',
    trimestre_1: false,
    trimestre_2: false,
    trimestre_3: false,
    trimestre_4: false,
    statut: 'planifiee',
    pourcentage: 0,
    date_debut: '',
    date_fin: '',
    description: '',
    resultat_attendu: '',
    observations: '',
  }
}
const createForm = ref(defaultCreateForm())

async function openCreateModal() {
  createForm.value = defaultCreateForm()
  createErrors.value = {}
  showCreateModal.value = true
  try {
    const { data } = await getCreateData()
    createDepartments.value = data.departments || []
    createProvinces.value = data.provinces || []
    createLocalites.value = data.localites || []
    createCategories.value = data.categories || []
    createResponsables.value = data.responsables || []
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast(err.response?.data?.message || 'Vous ne pouvez creer des activites PTA que pour votre departement.', 'warning')
      showCreateModal.value = false
    }
  }
}

function closeCreateModal() {
  showCreateModal.value = false
}

function onNiveauChange() {
  if (createForm.value.niveau_administratif !== 'SEN') createForm.value.departement_id = ''
  if (createForm.value.niveau_administratif !== 'SEL') createForm.value.province_id = ''
  if (createForm.value.niveau_administratif !== 'SEP') createForm.value.province_ids = []
  if (createForm.value.niveau_administratif !== 'SEL') createForm.value.localite_id = ''
}

async function handleCreateSubmit() {
  createSubmitting.value = true
  createErrors.value = {}
  try {
    const payload = { ...createForm.value }
    if (!payload.trimestre) delete payload.trimestre
    if (!payload.departement_id) delete payload.departement_id
    if (!payload.province_id) delete payload.province_id
    if (!payload.province_ids?.length) delete payload.province_ids
    if (!payload.localite_id) delete payload.localite_id
    if (!payload.date_debut) delete payload.date_debut
    if (!payload.date_fin) delete payload.date_fin
    if (!payload.description) delete payload.description
    if (!payload.objectif) delete payload.objectif
    if (!payload.resultat_attendu) delete payload.resultat_attendu
    if (!payload.observations) delete payload.observations
    if (!payload.categorie) delete payload.categorie
    if (!payload.responsable_code) delete payload.responsable_code
    if (payload.cout_cdf === '' || payload.cout_cdf === null) delete payload.cout_cdf
    await create(payload)
    ui.addToast('Activite creee avec succes !', 'success')
    showCreateModal.value = false
    loadPlan()
  } catch (err) {
    if (err.response?.status === 422) {
      createErrors.value = err.response.data.errors || {}
    } else if (err.response?.status === 403) {
      ui.addToast(err.response?.data?.message || 'Vous ne pouvez creer des activites PTA que pour votre departement.', 'warning')
    } else {
      ui.addToast('Erreur lors de la creation.', 'danger')
    }
  } finally {
    createSubmitting.value = false
  }
}

// Edit modal functions
function openEditModal(planTravailId) {
  editingPlanTravailId.value = planTravailId
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  editingPlanTravailId.value = null
}

function handlePlanTravailUpdated() {
  loadPlan()
}

function activeTrimestres(activite) {
  const values = []
  if (activite.trimestre_1) values.push('T1')
  if (activite.trimestre_2) values.push('T2')
  if (activite.trimestre_3) values.push('T3')
  if (activite.trimestre_4) values.push('T4')
  if (!values.length && activite.trimestre) values.push(activite.trimestre)
  return values
}

function trimestreSlots(activite) {
  const active = new Set(activeTrimestres(activite))
  return ['T1', 'T2', 'T3', 'T4'].map((label) => ({ label, active: active.has(label) }))
}

function provinceSummary(activite) {
  const names = (activite.provinces || []).map((province) => province.nom).filter(Boolean)
  if (names.length) return names.join(', ')
  return activite.province?.nom || ''
}

function formatCurrency(value) {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount)
}

onMounted(() => loadPlan())
</script>

<style scoped>
/* ── Hero ── */
.pt-hero {
  background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
  border-radius: 18px; padding: 2rem 2.2rem; margin-bottom: 1.5rem; color: #fff;
  position: relative; overflow: hidden;
}
.pt-hero::before {
  content: ''; position: absolute; top: -40%; right: -8%;
  width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.07);
}
.pt-hero-body {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 1.5rem; position: relative; z-index: 1;
}
.pt-hero-text h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .3rem; }
.pt-hero-text p { font-size: .85rem; opacity: .8; margin: 0; }
.pt-hero-stats { display: flex; gap: 1.5rem; margin-top: 1rem; }
.pt-hero-stat-val { font-size: 1.5rem; font-weight: 800; }
.pt-hero-stat-lbl { font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .5px; }
.pt-hero-actions { display: flex; flex-direction: column; align-items: flex-end; gap: .75rem; flex-shrink: 0; }
.pt-hero-btn {
  display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.2rem;
  border-radius: 10px; font-size: .85rem; font-weight: 700;
  background: rgba(255,255,255,.18); color: #fff; text-decoration: none;
  border: 1px solid rgba(255,255,255,.25); transition: all .2s; cursor: pointer;
}
.pt-hero-btn:hover { background: rgba(255,255,255,.3); color: #fff; }
.pt-hero-filters { display: flex; gap: .5rem; }
.pt-alert {
  display: flex; align-items: flex-start; gap: .9rem;
  padding: 1rem 1.1rem; margin-bottom: 1rem; border-radius: 14px;
  border: 1px solid transparent;
}
.pt-alert-danger {
  background: #fff7ed;
  border-color: #fdba74;
  color: #9a3412;
}
.pt-alert-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(234, 88, 12, .12); color: #c2410c; flex-shrink: 0;
}
.pt-alert h5 { margin: 0 0 .2rem; font-size: .95rem; font-weight: 700; }
.pt-alert p { margin: 0; font-size: .82rem; }
.pt-filter-select {
  background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
  color: #fff; border-radius: 8px; padding: .35rem .7rem; font-size: .8rem;
}
.pt-filter-select option { color: #1e293b; background: #fff; }
.pt-filter-select:focus { outline: none; border-color: rgba(255,255,255,.5); }

/* ── Status filter cards ── */
.pt-filter-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: .8rem; margin-bottom: 1rem;
}
.pt-filter-card {
  display: flex; align-items: center; gap: .7rem; padding: .9rem 1rem;
  background: #fff; border: 2px solid #e5e7eb; border-radius: 14px;
  color: #374151; transition: all .25s; cursor: pointer;
}
.pt-filter-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }

.pt-quarter-strip {
  display: inline-flex;
  gap: .35rem;
  margin-top: .65rem;
  flex-wrap: wrap;
}

.pt-quarter-chip {
  min-width: 42px;
  padding: .22rem .5rem;
  border-radius: 999px;
  border: 1px solid #d1d5db;
  background: #f8fafc;
  color: #64748b;
  font-size: .72rem;
  font-weight: 700;
  text-align: center;
}

.pt-quarter-chip.active {
  border-color: #8b5cf6;
  background: linear-gradient(135deg, #8b5cf6, #6d28d9);
  color: #fff;
  box-shadow: 0 4px 10px rgba(109, 40, 217, .18);
}

.pt-filter-all .pt-filter-icon { background: #ede9fe; color: #7c3aed; }
.pt-filter-all:hover { border-color: #7c3aed; color: #7c3aed; }
.pt-filter-all.active { background: linear-gradient(135deg, #7c3aed, #6d28d9); border-color: #7c3aed; color: #fff; box-shadow: 0 4px 16px rgba(124,58,237,.25); }
.pt-filter-all.active .pt-filter-icon { background: rgba(255,255,255,.2); color: #fff; }

.pt-filter-planned .pt-filter-icon { background: #f1f5f9; color: #64748b; }
.pt-filter-planned:hover { border-color: #64748b; }
.pt-filter-planned.active { background: linear-gradient(135deg, #64748b, #475569); border-color: #64748b; color: #fff; box-shadow: 0 4px 16px rgba(100,116,139,.25); }
.pt-filter-planned.active .pt-filter-icon { background: rgba(255,255,255,.2); color: #fff; }

.pt-filter-progress .pt-filter-icon { background: #dbeafe; color: #2563eb; }
.pt-filter-progress:hover { border-color: #2563eb; color: #1e40af; }
.pt-filter-progress.active { background: linear-gradient(135deg, #2563eb, #1d4ed8); border-color: #2563eb; color: #fff; box-shadow: 0 4px 16px rgba(37,99,235,.25); }
.pt-filter-progress.active .pt-filter-icon { background: rgba(255,255,255,.2); color: #fff; }

.pt-filter-done .pt-filter-icon { background: #dcfce7; color: #16a34a; }
.pt-filter-done:hover { border-color: #16a34a; color: #166534; }
.pt-filter-done.active { background: linear-gradient(135deg, #16a34a, #15803d); border-color: #16a34a; color: #fff; box-shadow: 0 4px 16px rgba(22,163,74,.25); }
.pt-filter-done.active .pt-filter-icon { background: rgba(255,255,255,.2); color: #fff; }

.pt-filter-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.pt-filter-info { flex: 1; min-width: 0; text-align: left; }
.pt-filter-name { font-size: .82rem; font-weight: 700; line-height: 1.2; }
.pt-filter-count { font-size: .7rem; opacity: .6; }
.pt-filter-card.active .pt-filter-count { opacity: .8; }

/* ── Trimester pills ── */
.pt-trim-bar { display: flex; gap: .4rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.pt-trim-btn {
  padding: .35rem .85rem; border-radius: 20px; font-size: .78rem; font-weight: 600;
  border: 1px solid #e2e8f0; background: #fff; color: #64748b;
  cursor: pointer; transition: all .2s;
}
.pt-trim-btn:hover { border-color: #7c3aed; color: #7c3aed; }
.pt-trim-btn.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

/* ── Progress bar ── */
.pt-progress-bar {
  background: #fff; border-radius: 14px; padding: 1rem 1.25rem;
  border: 1px solid #e5e7eb; box-shadow: 0 2px 12px rgba(0,0,0,.04); margin-bottom: 1.25rem;
}
.pt-progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
.pt-progress-label { font-size: .82rem; font-weight: 700; color: #1e293b; }
.pt-progress-val { font-size: .82rem; font-weight: 700; color: #7c3aed; }
.pt-progress-track { height: 8px; border-radius: 6px; background: #f1f5f9; overflow: hidden; }
.pt-progress-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #7c3aed, #a78bfa); transition: width .4s; }

/* ── Section header ── */
.pt-section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1rem; padding-bottom: .6rem; border-bottom: 2px solid #f3f4f6;
}
.pt-section-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: .5rem; }
.pt-section-badge { font-size: .72rem; font-weight: 700; padding: .2rem .6rem; border-radius: 20px; background: #ede9fe; color: #7c3aed; }
.pt-back-btn {
  display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .8rem;
  border-radius: 8px; font-size: .78rem; font-weight: 600;
  background: #f3f4f6; color: #6b7280; text-decoration: none; border: none; cursor: pointer; transition: all .2s;
}
.pt-back-btn:hover { background: #e5e7eb; color: #374151; }

/* ── Activity cards grid ── */
.pt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; }
.pt-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); overflow: hidden; transition: all .2s;
  display: flex; flex-direction: column;
}
.pt-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.pt-card-top { display: flex; align-items: flex-start; gap: .8rem; padding: 1.2rem 1.2rem .6rem; }
.pt-card-status-icon {
  width: 44px; height: 44px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.pt-si-planned { background: #f1f5f9; color: #64748b; }
.pt-si-progress { background: #dbeafe; color: #2563eb; }
.pt-si-done { background: #dcfce7; color: #16a34a; }

.pt-card-info { flex: 1; min-width: 0; }
.pt-card-title { font-weight: 700; font-size: .9rem; color: #1e293b; text-decoration: none; display: block; line-height: 1.3; margin-bottom: .25rem; }
.pt-card-title:hover { color: #7c3aed; }
.pt-card-desc { font-size: .78rem; color: #9ca3af; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: .4rem; }
.pt-card-tags { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }

.pt-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 6px; font-size: .72rem; font-weight: 600; }
.pt-badge.planned { background: #f1f5f9; color: #475569; }
.pt-badge.progress { background: #dbeafe; color: #1e40af; }
.pt-badge.done { background: #dcfce7; color: #166534; }
.pt-meta-badge { font-size: .68rem; font-weight: 600; padding: .2rem .55rem; border-radius: 6px; background: #f3f4f6; color: #6b7280; }

.pt-card-meta { padding: .4rem 1.2rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.pt-meta-item { font-size: .75rem; color: #9ca3af; display: flex; align-items: center; }

.pt-card-progress { padding: .4rem 1.2rem; display: flex; align-items: center; gap: .6rem; }
.pt-card-progress-track { flex: 1; height: 6px; border-radius: 6px; background: #f1f5f9; overflow: hidden; }
.pt-card-progress-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #7c3aed, #a78bfa); transition: width .4s; }
.pt-card-progress-fill.done { background: linear-gradient(90deg, #16a34a, #4ade80); }
.pt-card-progress-val { font-size: .75rem; font-weight: 700; color: #7c3aed; flex-shrink: 0; min-width: 35px; text-align: right; }

.pt-card-footer {
  border-top: 1px solid #f3f4f6; padding: .7rem 1.2rem;
  display: flex; align-items: center; justify-content: space-between; margin-top: auto; gap: .5rem;
}
.pt-card-date { font-size: .72rem; color: #9ca3af; }
.pt-card-actions { display: flex; gap: .4rem; }
.pt-act-btn {
  display: inline-flex; align-items: center; gap: .25rem; padding: .3rem .65rem;
  border-radius: 8px; font-size: .72rem; font-weight: 600; text-decoration: none;
  border: 1px solid #e2e8f0; background: #fff; cursor: pointer; transition: all .2s;
}
.pt-act-view { color: #7c3aed; }
.pt-act-view:hover { background: #f5f3ff; border-color: #7c3aed; }
.pt-act-edit { color: #d97706; }
.pt-act-edit:hover { background: #fffbeb; border-color: #d97706; }

/* ── Empty state ── */
.pt-empty { text-align: center; padding: 3rem 1rem; color: #9ca3af; }

/* ── Filtering overlay ── */
.pt-filtering { opacity: 0.4; pointer-events: none; transition: opacity .2s; }
.pt-empty-icon {
  width: 64px; height: 64px; border-radius: 50%; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; color: #d1d5db;
}

/* ── Mobile ── */
@media (max-width: 576px) {
  .pt-hero { padding: 1.4rem 1.2rem; border-radius: 14px; }
  .pt-hero-body { flex-direction: column; gap: 1rem; }
  .pt-hero-actions { align-items: stretch; width: 100%; }
  .pt-hero-btn { justify-content: center; }
  .pt-filter-grid { grid-template-columns: repeat(2, 1fr); }
  .pt-grid { grid-template-columns: 1fr; }
  .pt-section-header { flex-direction: column; align-items: flex-start; gap: .5rem; }
  .pt-card-footer { flex-direction: column; align-items: flex-start; gap: .5rem; }
  .pt-card-actions { width: 100%; }
  .pt-act-btn { flex: 1; justify-content: center; }
}

/* ── Create Modal (ptm-*) ── */
.ptm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: ptmFadeIn .2s ease;
}
@keyframes ptmFadeIn { from { opacity: 0; } to { opacity: 1; } }

.ptm-dialog {
  background: #fff; border-radius: 20px; width: 100%; max-width: 620px;
  max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.25);
  animation: ptmSlideUp .25s ease;
}
@keyframes ptmSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.ptm-header {
  display: flex; align-items: center; gap: .8rem;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f3f4f6;
  background: linear-gradient(135deg, #faf5ff 0%, #f5f3ff 100%);
  border-radius: 20px 20px 0 0;
}
.ptm-header-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.ptm-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.ptm-subtitle { font-size: .78rem; color: #64748b; margin: 0; }
.ptm-close {
  margin-left: auto; background: none; border: none; cursor: pointer;
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; color: #94a3b8;
  transition: all .2s; font-size: 1rem;
}
.ptm-close:hover { background: #fee2e2; color: #ef4444; }

.ptm-body { padding: 1.25rem 1.5rem; }

.ptm-field { margin-bottom: 1rem; }
.ptm-label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
.ptm-req { color: #ef4444; }

.ptm-input {
  width: 100%; padding: .55rem .8rem; border: 1.5px solid #e2e8f0;
  border-radius: 10px; font-size: .85rem; color: #1e293b;
  background: #f8fafc; transition: all .2s;
}
.ptm-input:focus { outline: none; border-color: #7c3aed; background: #fff; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.ptm-input.ptm-err { border-color: #ef4444; }
.ptm-textarea { resize: vertical; min-height: 60px; }

.ptm-err-msg { display: block; font-size: .72rem; color: #ef4444; margin-top: .25rem; }

.ptm-row { display: flex; gap: .75rem; }
.ptm-half { flex: 1; min-width: 0; }

.ptm-card-row { display: flex; gap: .5rem; flex-wrap: wrap; }
.ptm-opt-card {
  padding: .45rem .85rem; border-radius: 10px; font-size: .8rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b;
  cursor: pointer; transition: all .2s;
}
.ptm-opt-card:hover { border-color: #7c3aed; color: #7c3aed; }
.ptm-opt-card.active { background: linear-gradient(135deg, #7c3aed, #6d28d9); border-color: #7c3aed; color: #fff; }

.ptm-footer {
  display: flex; gap: .75rem; justify-content: flex-end;
  padding-top: 1rem; border-top: 1px solid #f3f4f6; margin-top: .5rem;
}
.ptm-btn-cancel {
  padding: .55rem 1.2rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .2s;
}
.ptm-btn-cancel:hover { background: #f3f4f6; }
.ptm-btn-submit {
  padding: .55rem 1.5rem; border-radius: 10px; font-size: .82rem; font-weight: 700;
  border: none; background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff;
  cursor: pointer; transition: all .2s;
}
.ptm-btn-submit:hover { box-shadow: 0 4px 16px rgba(124,58,237,.3); }
.ptm-btn-submit:disabled { opacity: .6; cursor: not-allowed; }

@media (max-width: 576px) {
  .ptm-dialog { max-width: 100%; border-radius: 16px; }
  .ptm-header { padding: 1rem 1.1rem; border-radius: 16px 16px 0 0; }
  .ptm-body { padding: 1rem 1.1rem; }
  .ptm-row { flex-direction: column; gap: 0; }
  .ptm-card-row { flex-direction: column; }
  .ptm-opt-card { text-align: center; }
  .ptm-footer { flex-direction: column; }
  .ptm-btn-cancel, .ptm-btn-submit { width: 100%; text-align: center; }
}
</style>
