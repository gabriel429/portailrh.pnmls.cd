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

          <div v-else-if="holidays.data?.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-calendar-times fa-3x"></i>
            </div>
            <h5>Aucun congé trouvé</h5>
            <p class="text-muted">Aucun congé planifié pour cette période</p>
          </div>

          <div v-else>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Noms</th>
                    <th>Fonction</th>
                    <th>Date de début</th>
                    <th>Date de fin</th>
                    <th>Date de reprise</th>
                    <th>Durée</th>
                    <th>Observation</th>
                    <th>Intérim assuré par</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="holiday in holidays.data" :key="holiday.id">
                    <td>
                      <div class="fw-medium">{{ holiday.agent?.nom_complet || holiday.agent?.nom || '-' }}</div>
                    </td>
                    <td>{{ holiday.agent?.fonction || '-' }}</td>
                    <td>{{ formatDate(holiday.date_debut) }}</td>
                    <td>{{ formatDate(holiday.date_fin) }}</td>
                    <td>{{ formatDate(holiday.date_retour_prevu) }}</td>
                    <td>
                      <span class="badge bg-info">{{ holiday.nombre_jours }} j</span>
                    </td>
                    <td>{{ holiday.observation || holiday.motif || '-' }}</td>
                    <td>{{ holiday.interim_par?.nom_complet || holiday.interim_par?.nom || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <Pagination
              v-if="holidays.last_page > 1"
              :pagination="holidays"
              @page-changed="changePage"
            />
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
  return auth.user?.agent?.hasRole(['RH National', 'SEN'])
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
  margin-right: 1rem;
  color: white;
  font-size: 1.5rem;
}

.stat-content h3 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: #333;
}

.stat-content p {
  margin: 0;
  color: #666;
  font-size: 0.875rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon {
  color: #dee2e6;
  margin-bottom: 1rem;
}

.btn-rh {
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-rh:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-rh.alt {
  background: #6c757d;
}

.progress {
  margin-bottom: 4px;
}

/* ── Mobile responsive ── */
@media (max-width: 767.98px) {
  .stat-card { padding: 1rem; }
  .stat-icon { width: 40px; height: 40px; font-size: 1.1rem; margin-right: .75rem; }
  .stat-content h3 { font-size: 1.3rem; }
  .stat-content p { font-size: 0.8rem; }
  .rh-filters-card { padding: 1rem; }
  .table { font-size: 0.82rem; }
  .table th, .table td { padding: 0.5rem 0.4rem; white-space: nowrap; }
  /* Masquer Année, Jours Totaux, Taux sur mobile */
  .table th:nth-child(3), .table td:nth-child(3),
  .table th:nth-child(4), .table td:nth-child(4),
  .table th:nth-child(6), .table td:nth-child(6) { display: none; }
  .btn-rh { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
}

@media (max-width: 575.98px) {
  .table { font-size: 0.78rem; }
  .table th, .table td { padding: 0.35rem 0.3rem; }
  .stat-card { padding: 0.75rem; }
  .stat-icon { width: 34px; height: 34px; font-size: 0.95rem; }
  .stat-content h3 { font-size: 1.1rem; }
}
</style>