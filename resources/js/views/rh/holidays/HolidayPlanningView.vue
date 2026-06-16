<template>
  <div class="rh-modern">
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
      <div v-if="stats" class="row g-3 mb-4">
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
            <div class="table-responsive">
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
            <div class="table-responsive">
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
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
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
.rh-modern { margin-top: 1rem; }

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

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  border: 1px solid #e9ecef;
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

.section-header {
  padding-bottom: 1rem;
  border-bottom: 2px solid #e9ecef;
}

.section-header h4 {
  font-weight: 600;
  color: #2c3e50;
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
</style>