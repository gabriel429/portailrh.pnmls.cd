<template>
  <div class="rh-modern holiday-planning-page">
    <!-- Header -->
    <div class="rh-list-shell">
      <section class="rh-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title">
              <i class="fas fa-calendar-alt me-2"></i>
              Gestion des Congés
              <span v-if="scopeInfo.province_nom" class="rh-title-badge">{{ scopeInfo.province_nom }}</span>
            </h1>
            <p class="rh-sub">
              <template v-if="scopeInfo.is_provincial">
                Planning des congés {{ filters.year }} — Province {{ scopeInfo.province_nom }}
              </template>
              <template v-else>
                Planning annuel des congés par département et structure organisationnelle
              </template>
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <button @click="showAddHolidayModal = true" class="btn-rh me-2">
                <i class="fas fa-user-clock me-1"></i> Planifier un congé
              </button>
              <button @click="showCreateModal = true" class="btn-rh me-2">
                <i class="fas fa-plus me-1"></i> Nouveau Planning
              </button>
              <button @click="exportData" class="btn-rh alt" :disabled="loading">
                <i class="fas fa-download me-1"></i> Export
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Filtres -->
      <div class="rh-filters-card">
        <!-- Bandeau province pour RH Provincial -->
        <div v-if="scopeInfo.is_provincial" class="province-scope-banner mb-3">
          <i class="fas fa-map-marker-alt me-2"></i>
          <strong>Province : {{ scopeInfo.province_nom }}</strong>
          <span class="ms-3">|</span>
          <span class="ms-3"><i class="fas fa-calendar me-1"></i> Année : {{ filters.year }}</span>
        </div>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Année</label>
            <select class="form-select" v-model="filters.year" @change="loadPlannings">
              <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
          <div class="col-md-3" v-if="!scopeInfo.is_provincial">
            <label class="form-label">Type de Structure</label>
            <select class="form-select" v-model="filters.structure_type" @change="loadPlannings">
              <option value="">Toutes les structures</option>
              <option value="department">Départements</option>
              <option value="sen">SEN</option>
              <option value="sena">SENA</option>
              <option value="sep">SEP Provincial</option>
              <option value="local">Structures Locales</option>
            </select>
          </div>

          <div class="col-md-3" v-if="filters.structure_type === 'department'">
            <label class="form-label">Département</label>
            <select class="form-select" v-model="filters.structure_id" @change="loadPlannings">
              <option value="">Tous les départements</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.nom }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Vue</label>
            <div class="btn-group w-100">
              <button
                type="button"
                class="btn btn-sm"
                :class="viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary'"
                @click="viewMode = 'list'"
              >
                <i class="fas fa-list"></i> Liste
              </button>
              <button
                type="button"
                class="btn btn-sm"
                :class="viewMode === 'calendar' ? 'btn-primary' : 'btn-outline-primary'"
                @click="viewMode = 'calendar'"
              >
                <i class="fas fa-calendar"></i> Calendrier
              </button>
              <button
                type="button"
                class="btn btn-sm"
                :class="viewMode === 'stats' ? 'btn-primary' : 'btn-outline-primary'"
                @click="viewMode = 'stats'"
              >
                <i class="fas fa-chart-bar"></i> Stats
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistiques -->
      <div v-if="stats" class="holiday-stat-grid">
        <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-primary">
              <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.total_plannings }}</h3>
              <p>Plannings créés</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-success">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.plannings_valides }}</h3>
              <p>Plannings validés</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-info">
              <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.total_jours_prevus }}</h3>
              <p>Jours prévus</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-warning">
              <i class="fas fa-calendar-minus"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.total_jours_utilises }}</h3>
              <p>Jours utilisés</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="rh-list-card">

        <!-- Vue Liste -->
        <div v-if="viewMode === 'list'">
          <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
          </div>

          <!-- Tableau des Plannings de Structures -->
          <div v-else-if="plannings.data?.length > 0" class="mb-5">
            <div class="section-header mb-3">
              <h4 class="mb-0">
                <i class="fas fa-calendar-check me-2 text-primary"></i>
                Plannings de Structures
              </h4>
              <small class="text-muted d-block mt-1">{{ plannings.data?.length }} planning(s) créé(s)</small>
            </div>
            <div class="table-responsive holiday-table-wrap">
              <table class="table table-hover">
                <thead>
                  <tr class="text-muted small">
                    <th>Structure</th>
                    <th>Type</th>
                    <th>Année</th>
                    <th>Jours autorisés</th>
                    <th>Jours utilisés</th>
                    <th>Jours restants</th>
                    <th>Taux</th>
                    <th>Statut</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="planning in plannings.data" :key="planning.id">
                    <td>
                      <div class="fw-medium">{{ planning.nom_structure }}</div>
                      <small class="text-muted">ID: {{ planning.id }}</small>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark">{{ planning.type_structure_label }}</span>
                    </td>
                    <td class="text-center fw-medium">{{ planning.annee }}</td>
                    <td class="text-center">
                      <span class="badge bg-info">{{ planning.jours_conge_totaux }}j</span>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-warning">{{ planning.jours_utilises }}j</span>
                    </td>
                    <td class="text-center">
                      <span class="badge" :class="planning.jours_restants > 0 ? 'bg-success' : 'bg-danger'">
                        {{ planning.jours_restants }}j
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="progress" style="height: 20px; width: 80px; margin: 0 auto;">
                        <div
                          class="progress-bar"
                          :class="planning.pourcentage_utilisation > 80 ? 'bg-danger' : planning.pourcentage_utilisation > 50 ? 'bg-warning' : 'bg-success'"
                          :style="{ width: Math.min(planning.pourcentage_utilisation, 100) + '%' }"
                        >
                          <small class="fw-bold">{{ planning.pourcentage_utilisation }}%</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge" :class="planning.valide ? 'bg-success' : 'bg-warning'">
                        {{ planning.valide ? 'Validé' : 'En attente' }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                        <button
                          type="button"
                          class="btn btn-outline-primary"
                          title="Consulter et modifier"
                          @click="viewPlanning(planning)"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button
                          v-if="!planning.valide && canValidate"
                          type="button"
                          class="btn btn-outline-success"
                          title="Valider le planning"
                          @click="validatePlanning(planning)"
                        >
                          <i class="fas fa-check"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-outline-danger"
                          title="Supprimer"
                          @click="deletePlanning(planning)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Pagination Plannings -->
            <Pagination
              v-if="plannings.last_page > 1"
              :pagination="plannings"
              @page-changed="changePage"
            />
          </div>

          <!-- Tableau des Congés Individuels -->
          <div v-if="holidays.data?.length > 0">
            <div class="section-header mb-3">
              <h4 class="mb-0">
                <i class="fas fa-users me-2 text-primary"></i>
                Congés Individuels Planifiés
              </h4>
              <small class="text-muted d-block mt-1">{{ holidays.data?.length }} congé(s) planifié(s)</small>
            </div>
            <div class="table-responsive holiday-table-wrap">
              <table class="table table-hover">
                <thead>
                  <tr class="text-muted small">
                    <th>Agent</th>
                    <th>Fonction</th>
                    <th>Type</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Durée</th>
                    <th>Statut</th>
                    <th>Intérim assuré par</th>
                    <th>Observation</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="holiday in holidays.data" :key="holiday.id">
                    <td>
                      <div class="fw-medium">{{ holiday.agent?.nom_complet || holiday.agent?.nom || '-' }}</div>
                    </td>
                    <td class="small text-muted">{{ holiday.agent?.fonction || '-' }}</td>
                    <td>
                      <span class="badge" :class="typeCongeClass(holiday.type_conge)">
                        {{ typeCongeLabel(holiday.type_conge) }}
                      </span>
                    </td>
                    <td>{{ formatDate(holiday.date_debut) }}</td>
                    <td>{{ formatDate(holiday.date_fin) }}</td>
                    <td>
                      <span class="badge bg-info">{{ holiday.nombre_jours }}j</span>
                    </td>
                    <td>
                      <span class="badge" :class="statutClass(holiday.statut_demande)">
                        {{ statutLabel(holiday.statut_demande) }}
                      </span>
                    </td>
                    <td>{{ holiday.interim_par?.nom_complet || holiday.interim_par?.nom || '-' }}</td>
                    <td class="small text-muted">{{ holiday.observation || holiday.motif || '-' }}</td>
                    <td>
                      <div class="btn-group btn-group-sm holiday-action-group">
                        <button
                          type="button"
                          class="btn btn-outline-primary"
                          title="Modifier"
                          :disabled="!canManageHoliday(holiday)"
                          @click="openHolidayEditor(holiday, 'edit')"
                        >
                          <i class="fas fa-pen"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-outline-info"
                          title="Reporter"
                          :disabled="!canManageHoliday(holiday)"
                          @click="openHolidayEditor(holiday, 'report')"
                        >
                          <i class="fas fa-calendar-plus"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-outline-success"
                          title="Retour effectif"
                          :disabled="holiday.statut_demande !== 'approuve' || !canManageHoliday(holiday)"
                          @click="openReturnModal(holiday)"
                        >
                          <i class="fas fa-sign-out-alt"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-outline-danger"
                          title="Annuler"
                          :disabled="holiday.statut_demande === 'annule' || !canManageHoliday(holiday)"
                          @click="cancelHoliday(holiday)"
                        >
                          <i class="fas fa-ban"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Congés -->
            <Pagination
              v-if="holidays.last_page > 1"
              :pagination="holidays"
              @page-changed="changePage"
            />
          </div>

          <!-- État vide -->
          <div v-else-if="plannings.data?.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-calendar-times fa-3x"></i>
            </div>
            <h5>Aucun planning créé</h5>
            <p class="text-muted mb-3">Créez un planning pour commencer à gérer les congés</p>
            <button @click="showCreateModal = true" class="btn btn-primary">
              <i class="fas fa-plus me-1"></i> Créer un planning
            </button>
          </div>
        </div>

        <!-- Vue Calendrier -->
        <HolidayCalendar
          v-else-if="viewMode === 'calendar'"
          :filters="filters"
          :key="calendarKey"
          @holiday-selected="openHolidayFromCalendar"
        />

        <!-- Vue Statistiques -->
        <HolidayStatistics
          v-else-if="viewMode === 'stats'"
          :filters="filters"
          :key="statsKey"
        />
      </div>
    </div>

    <!-- Modal création planning -->
    <HolidayPlanningModal
      v-if="showCreateModal"
      :show="showCreateModal"
      :departments="departments"
      :provinces="provinces"
      :scope-info="scopeInfo"
      @close="showCreateModal = false"
      @created="onPlanningCreated"
    />

    <!-- Modal détails planning -->
    <HolidayPlanningDetailsModal
      v-if="showDetailsModal"
      :show="showDetailsModal"
      :planning="selectedPlanning"
      @close="showDetailsModal = false"
      @updated="onPlanningUpdated"
    />

    <!-- Modal planifier un congé -->
    <AddHolidayModal
      v-if="showAddHolidayModal"
      :show="showAddHolidayModal"
      :departments="departments"
      :plannings="plannings.data || []"
      :year="filters.year"
      :scope-info="scopeInfo"
      @close="showAddHolidayModal = false"
      @created="onHolidayCreated"
    />

    <!-- Modal modification/report congé individuel -->
    <div
      v-if="holidayEdit.show"
      class="modal fade show d-block holiday-edit-modal"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-calendar-alt me-2"></i>
              {{ holidayEdit.mode === 'report' ? 'Reporter le congé' : 'Modifier le congé' }}
            </h5>
            <button type="button" class="btn-close" @click="closeHolidayEditor"></button>
          </div>
          <form @submit.prevent="saveHolidayEdit">
            <div class="modal-body">
              <div class="holiday-edit-agent">
                {{ holidayEdit.holiday?.agent?.nom_complet || holidayEdit.holiday?.agent?.nom || '-' }}
              </div>
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label">Début</label>
                  <input v-model="holidayEdit.form.date_debut" type="date" class="form-control" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Fin</label>
                  <input v-model="holidayEdit.form.date_fin" type="date" class="form-control" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Type</label>
                  <select v-model="holidayEdit.form.type_conge" class="form-select" required>
                    <option value="annuel">Annuel</option>
                    <option value="maladie">Maladie</option>
                    <option value="maternite">Maternité</option>
                    <option value="paternite">Paternité</option>
                    <option value="urgence">Urgence</option>
                    <option value="special">Spécial</option>
                  </select>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Intérim</label>
                  <select v-model="holidayEdit.form.interim_assure_par" class="form-select">
                    <option value="">Aucun</option>
                    <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                      {{ agent.nom_complet }}
                    </option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Observation</label>
                  <textarea v-model="holidayEdit.form.observation" class="form-control" rows="3" maxlength="1000"></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="closeHolidayEditor">
                Fermer
              </button>
              <button type="submit" class="btn btn-primary" :disabled="holidayEdit.saving">
                <span v-if="holidayEdit.saving" class="spinner-border spinner-border-sm me-2"></span>
                Enregistrer
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal retour congé -->
    <div
      v-if="returnEdit.show"
      class="modal fade show d-block holiday-edit-modal"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-sign-out-alt me-2"></i>
              Retour effectif
            </h5>
            <button type="button" class="btn-close" @click="closeReturnModal"></button>
          </div>
          <form @submit.prevent="saveHolidayReturn">
            <div class="modal-body">
              <div class="holiday-edit-agent">{{ returnEdit.holiday?.agent?.nom_complet || '-' }}</div>
              <label class="form-label">Date de retour</label>
              <input v-model="returnEdit.date_retour" type="date" class="form-control" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="closeReturnModal">
                Annuler
              </button>
              <button type="submit" class="btn btn-success" :disabled="returnEdit.saving">
                Valider
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import Pagination from '@/components/common/Pagination.vue'
import HolidayCalendar from '@/components/holidays/HolidayCalendar.vue'
import HolidayStatistics from '@/components/holidays/HolidayStatistics.vue'
import HolidayPlanningModal from '@/components/holidays/HolidayPlanningModal.vue'
import HolidayPlanningDetailsModal from '@/components/holidays/HolidayPlanningDetailsModal.vue'
import AddHolidayModal from '@/components/holidays/AddHolidayModal.vue'

const router = useRouter()
const ui = useUiStore()
const auth = useAuthStore()

// État
const loading = ref(false)
const plannings = ref({ data: [] })
const holidays = ref({ data: [] })
const departments = ref([])
const provinces = ref([])
const agents = ref([])
const stats = ref(null)
const scopeInfo = ref({ is_provincial: false, province_id: null, province_nom: null })
const viewMode = ref('list')
const calendarKey = ref(0)
const statsKey = ref(0)
const holidayEdit = ref({
  show: false,
  mode: 'edit',
  saving: false,
  holiday: null,
  form: {
    date_debut: '',
    date_fin: '',
    type_conge: 'annuel',
    observation: '',
    interim_assure_par: ''
  }
})
const returnEdit = ref({
  show: false,
  saving: false,
  holiday: null,
  date_retour: ''
})

// Modales
const showCreateModal = ref(false)
const showDetailsModal = ref(false)
const showAddHolidayModal = ref(false)
const selectedPlanning = ref(null)

// Filtres
const filters = ref({
  year: new Date().getFullYear(),
  structure_type: '',
  structure_id: ''
})

const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => currentYear - 2 + i)
})

const canValidate = computed(() => {
  return auth.hasRole(['RH National', 'RH Provincial', 'SEN'])
})

// Méthodes
async function loadDepartments() {
  try {
    const response = await client.get('/departments')
    departments.value = response.data.departments || response.data || []
  } catch (error) {
    console.error('Erreur chargement départements:', error)
  }
}

async function loadPlannings(page = 1) {
  loading.value = true
  try {
    const params = {
      ...filters.value,
      page
    }

    const response = await client.get('/holiday-plannings', { params })
    plannings.value = response.data.plannings
    holidays.value = response.data.holidays || { data: [] }
    stats.value = response.data.stats

    if (response.data.scope) {
      scopeInfo.value = response.data.scope
    }

    if (departments.value.length === 0 && response.data.departments?.length) {
      departments.value = response.data.departments
    }

    if (response.data.agents?.length) {
      agents.value = response.data.agents
    }

    if (response.data.provinces?.length) {
      provinces.value = response.data.provinces
    }
  } catch (error) {
    console.error('Erreur chargement plannings:', error)
    ui.addToast('Erreur lors du chargement des plannings', 'danger')
  } finally {
    loading.value = false
  }
}

function changePage(page) {
  loadPlannings(page)
}

function viewPlanning(planning) {
  selectedPlanning.value = planning
  showDetailsModal.value = true
}

function canManageHoliday(holiday) {
  const canManage = auth.canManageRhAdminModules || auth.hasRole(['RH National', 'RH Provincial'])
  return canManage && ['en_attente', 'approuve'].includes(holiday?.statut_demande)
}

async function openHolidayFromCalendar(event) {
  try {
    const response = await client.get(`/holidays/${event.id}`)
    openHolidayEditor(response.data, 'edit')
  } catch (error) {
    console.error('Erreur chargement congé:', error)
    ui.addToast('Impossible de charger ce congé', 'danger')
  }
}

function openHolidayEditor(holiday, mode = 'edit') {
  if (!canManageHoliday(holiday)) {
    ui.addToast('Vous ne pouvez pas modifier ce congé', 'warning')
    return
  }

  holidayEdit.value = {
    show: true,
    mode,
    saving: false,
    holiday,
    form: {
      date_debut: toDateInput(holiday.date_debut),
      date_fin: toDateInput(holiday.date_fin),
      type_conge: holiday.type_conge || 'annuel',
      observation: holiday.observation || holiday.motif || '',
      interim_assure_par: holiday.interim_assure_par || holiday.interim_par?.id || ''
    }
  }
}

function closeHolidayEditor() {
  if (holidayEdit.value.saving) return
  holidayEdit.value = {
    show: false,
    mode: 'edit',
    saving: false,
    holiday: null,
    form: {
      date_debut: '',
      date_fin: '',
      type_conge: 'annuel',
      observation: '',
      interim_assure_par: ''
    }
  }
}

async function saveHolidayEdit() {
  const holiday = holidayEdit.value.holiday
  if (!holiday) return

  holidayEdit.value.saving = true
  try {
    const payload = {
      ...holidayEdit.value.form,
      motif: holidayEdit.value.form.observation || 'Congé modifié par RH'
    }

    if (!payload.interim_assure_par) {
      payload.interim_assure_par = null
    }

    await client.put(`/holidays/${holiday.id}`, payload)
    ui.addToast(holidayEdit.value.mode === 'report' ? 'Congé reporté avec succès' : 'Congé modifié avec succès', 'success')
    holidayEdit.value.saving = false
    closeHolidayEditor()
    refreshHolidayViews()
  } catch (error) {
    console.error('Erreur modification congé:', error)
    ui.addToast(error.response?.data?.message || 'Erreur lors de la modification du congé', 'danger')
  } finally {
    holidayEdit.value.saving = false
  }
}

function openReturnModal(holiday) {
  if (holiday?.statut_demande !== 'approuve' || !canManageHoliday(holiday)) return
  returnEdit.value = {
    show: true,
    saving: false,
    holiday,
    date_retour: toDateInput(new Date())
  }
}

function closeReturnModal() {
  if (returnEdit.value.saving) return
  returnEdit.value = {
    show: false,
    saving: false,
    holiday: null,
    date_retour: ''
  }
}

async function saveHolidayReturn() {
  const holiday = returnEdit.value.holiday
  if (!holiday) return

  returnEdit.value.saving = true
  try {
    await client.post(`/holidays/${holiday.id}/mark-returned`, {
      date_retour: returnEdit.value.date_retour
    })
    ui.addToast('Retour enregistré', 'success')
    returnEdit.value.saving = false
    closeReturnModal()
    refreshHolidayViews()
  } catch (error) {
    console.error('Erreur retour congé:', error)
    ui.addToast(error.response?.data?.message || 'Erreur lors du retour', 'danger')
  } finally {
    returnEdit.value.saving = false
  }
}

async function cancelHoliday(holiday) {
  if (!canManageHoliday(holiday)) {
    ui.addToast('Vous ne pouvez pas annuler ce congé', 'warning')
    return
  }

  if (!holiday || !confirm(`Annuler le congé de ${holiday.agent?.nom_complet || holiday.agent?.nom || 'cet agent'} ?`)) return

  try {
    await client.post(`/holidays/${holiday.id}/cancel`)
    ui.addToast('Congé annulé', 'success')
    refreshHolidayViews()
  } catch (error) {
    console.error('Erreur annulation congé:', error)
    ui.addToast(error.response?.data?.message || 'Erreur lors de l\'annulation', 'danger')
  }
}

function refreshHolidayViews() {
  loadPlannings()
  calendarKey.value++
  statsKey.value++
}

function editPlanning(planning) {
  router.push({
    name: 'rh.holidays.planning-edit',
    params: { id: planning.id }
  })
}

async function validatePlanning(planning) {
  if (!confirm('Êtes-vous sûr de vouloir valider ce planning ?')) return

  try {
    await client.post(`/holiday-plannings/${planning.id}/validate`)
    ui.addToast('Planning validé avec succès', 'success')
    loadPlannings()
  } catch (error) {
    ui.addToast('Erreur lors de la validation', 'danger')
  }
}

async function exportData() {
  try {
    const params = {
      ...filters.value,
      format: 'csv'
    }

    const response = await client.get('/holiday-plannings/export', {
      params,
      responseType: 'blob'
    })

    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `planning-conges-${filters.value.year}.csv`
    link.click()

    ui.addToast('Export réussi', 'success')
  } catch (error) {
    ui.addToast('Erreur lors de l\'export', 'danger')
  }
}

function onPlanningCreated() {
  showCreateModal.value = false
  loadPlannings()
}

function onPlanningUpdated() {
  showDetailsModal.value = false
  loadPlannings()
}

function onHolidayCreated() {
  showAddHolidayModal.value = false
  loadPlannings()
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const value = toDateInput(dateStr)
  const [year, month, day] = value.split('-')
  return `${day}/${month}/${year}`
}

function toDateInput(value) {
  if (!value) return ''
  if (value instanceof Date) {
    const year = value.getFullYear()
    const month = String(value.getMonth() + 1).padStart(2, '0')
    const day = String(value.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }
  return String(value).slice(0, 10)
}

function typeCongeLabel(type) {
  const labels = {
    'annuel': 'Annuel',
    'maladie': 'Maladie',
    'maternite': 'Maternité',
    'paternite': 'Paternité',
    'urgence': 'Urgence',
    'special': 'Spécial'
  }
  return labels[type] || type
}

function typeCongeClass(type) {
  const classes = {
    'annuel': 'bg-primary',
    'maladie': 'bg-danger',
    'maternite': 'bg-success',
    'paternite': 'bg-info',
    'urgence': 'bg-warning text-dark',
    'special': 'bg-secondary'
  }
  return classes[type] || 'bg-secondary'
}

function statutLabel(statut) {
  const labels = {
    'en_attente': 'En attente',
    'approuve': 'Approuvé',
    'refuse': 'Refusé',
    'annule': 'Annulé',
    'retour': 'Retour'
  }
  return labels[statut] || statut
}

function statutClass(statut) {
  const classes = {
    'en_attente': 'bg-warning text-dark',
    'approuve': 'bg-success',
    'refuse': 'bg-danger',
    'annule': 'bg-secondary',
    'retour': 'bg-info'
  }
  return classes[statut] || 'bg-secondary'
}

async function deletePlanning(planning) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer le planning de ${planning.nom_structure} ?`)) return

  try {
    await client.delete(`/holiday-plannings/${planning.id}`)
    ui.addToast('Planning supprimé avec succès', 'success')
    loadPlannings()
  } catch (error) {
    console.error('Erreur suppression:', error)
    ui.addToast('Erreur lors de la suppression', 'danger')
  }
}

// Watchers
watch(() => viewMode.value, (newMode) => {
  if (newMode === 'calendar') {
    calendarKey.value++
  } else if (newMode === 'stats') {
    statsKey.value++
  }
})

// Initialisation
onMounted(() => {
  loadDepartments()
  loadPlannings()
})
</script>

<style scoped>
.rh-modern {
  margin-top: 1rem;
}

.holiday-planning-page {
  --holiday-line: #d9e9f7;
  --holiday-ink: #102235;
  --holiday-muted: #64748b;
}

.holiday-planning-page .rh-list-shell {
  width: min(100%, 1360px);
}

.holiday-planning-page .rh-hero {
  border-radius: 18px;
}

.holiday-planning-page .rh-hero .row {
  row-gap: 1rem;
}

.holiday-planning-page .hero-tools {
  justify-content: flex-end;
  gap: 0.65rem;
}

.holiday-planning-page .btn-rh {
  border: 1px solid rgba(125, 211, 252, 0.45);
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.94), rgba(15, 118, 110, 0.92));
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 38px;
  line-height: 1.1;
  white-space: nowrap;
}

.holiday-planning-page .btn-rh.alt {
  background: rgba(255, 255, 255, 0.16);
}

.rh-filters-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  border: 1px solid #e9ecef;
}

.province-scope-banner {
  background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
  border: 1px solid #c8e6c9;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  color: #2e7d32;
}

.rh-title-badge {
  display: inline-block;
  font-size: 0.65em;
  background: #e3f2fd;
  color: #1565c0;
  padding: 0.15em 0.6em;
  border-radius: 6px;
  vertical-align: middle;
  margin-left: 0.5rem;
}

.holiday-stat-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.holiday-stat-grid > [class*="col-"] {
  width: auto;
  max-width: none;
  padding: 0;
  margin: 0;
  flex: none;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  border: 1px solid #e9ecef;
  min-height: 112px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  margin-right: 1rem;
  font-size: 1.5rem;
}

.stat-content h3 {
  margin: 0;
  font-size: 1.75rem;
}

.stat-content p {
  margin: 0.25rem 0 0;
  font-size: 0.85rem;
  opacity: 0.8;
}

.rh-list-card {
  overflow: hidden;
  padding: 1.25rem;
}

.holiday-table-wrap {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  border: 1px solid var(--holiday-line);
  border-radius: 12px;
  background: #fff;
}

.holiday-table-wrap > .table {
  width: 100%;
  min-width: 960px;
  margin-bottom: 0;
}

.holiday-table-wrap th,
.holiday-table-wrap td {
  vertical-align: middle;
  white-space: nowrap;
}

.holiday-table-wrap td:first-child,
.holiday-table-wrap th:first-child {
  white-space: normal;
  min-width: 220px;
}

.holiday-table-wrap td:last-child,
.holiday-table-wrap th:last-child {
  text-align: center;
}

.holiday-table-wrap .btn-group {
  display: inline-flex;
  flex-wrap: nowrap;
}

.holiday-action-group .btn {
  width: 34px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.section-header {
  padding-bottom: 1rem;
  border-bottom: 2px solid #e9ecef;
}

.section-header h4 {
  font-weight: 600;
  color: #2c3e50;
}

.holiday-edit-modal {
  background: rgba(15, 23, 42, .58);
}

.holiday-edit-modal .modal-dialog {
  width: min(760px, calc(100vw - 2rem));
  max-width: min(760px, calc(100vw - 2rem));
  margin: 1.25rem auto;
}

.holiday-edit-modal .modal-sm {
  width: min(420px, calc(100vw - 2rem));
  max-width: min(420px, calc(100vw - 2rem));
}

.holiday-edit-modal .modal-content {
  border: 0;
  border-radius: 16px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, .26);
}

.holiday-edit-agent {
  color: #0f172a;
  font-weight: 700;
  margin-bottom: 1rem;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 2px dashed #dee2e6;
}

.empty-icon {
  color: #dee2e6;
  margin-bottom: 1rem;
}

.empty-state h5 {
  color: #6c757d;
  margin-bottom: 0.5rem;
}

@media (max-width: 991.98px) {
  .holiday-stat-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .holiday-planning-page .hero-tools {
    justify-content: flex-start;
  }

}

@media (max-width: 575.98px) {
  .rh-filters-card,
  .rh-list-card {
    padding: 1rem;
  }

  .holiday-stat-grid {
    grid-template-columns: 1fr;
  }

  .holiday-planning-page .hero-tools {
    display: grid;
    grid-template-columns: 1fr;
    width: 100%;
  }

  .holiday-planning-page .btn-rh {
    width: 100%;
    white-space: normal;
  }
}
</style>
