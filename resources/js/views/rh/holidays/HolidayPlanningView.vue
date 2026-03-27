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
            </h1>
            <p class="rh-sub">
              Planning annuel des congés par département et structure organisationnelle
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
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
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Année</label>
            <select class="form-select" v-model="filters.year" @change="loadPlannings">
              <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
          <div class="col-md-3">
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

          <div v-else-if="plannings.data?.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-calendar-times fa-3x"></i>
            </div>
            <h5>Aucun planning trouvé</h5>
            <p class="text-muted">Créez un nouveau planning pour commencer</p>
          </div>

          <div v-else>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Structure</th>
                    <th>Type</th>
                    <th>Année</th>
                    <th>Jours Totaux</th>
                    <th>Utilisés</th>
                    <th>Taux</th>
                    <th>Statut</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="planning in plannings.data" :key="planning.id">
                    <td>
                      <div class="fw-medium">{{ planning.nom_structure }}</div>
                    </td>
                    <td>
                      <span class="badge bg-secondary">{{ planning.type_structure_label }}</span>
                    </td>
                    <td>{{ planning.annee }}</td>
                    <td>
                      <span class="fw-medium">{{ planning.jours_conge_totaux }} j</span>
                    </td>
                    <td>
                      <span class="fw-medium text-warning">{{ planning.jours_utilises }} j</span>
                    </td>
                    <td>
                      <div class="progress" style="width: 80px; height: 8px;">
                        <div
                          class="progress-bar"
                          :class="{
                            'bg-success': planning.pourcentage_utilisation < 70,
                            'bg-warning': planning.pourcentage_utilisation >= 70 && planning.pourcentage_utilisation < 90,
                            'bg-danger': planning.pourcentage_utilisation >= 90
                          }"
                          :style="{ width: planning.pourcentage_utilisation + '%' }"
                        ></div>
                      </div>
                      <small class="text-muted">{{ planning.pourcentage_utilisation }}%</small>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="planning.valide ? 'bg-success' : 'bg-warning'"
                      >
                        {{ planning.valide ? 'Validé' : 'En attente' }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button
                          class="btn btn-outline-primary"
                          @click="viewPlanning(planning)"
                          title="Voir détails"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button
                          class="btn btn-outline-success"
                          @click="editPlanning(planning)"
                          v-if="!planning.valide || canValidate"
                          title="Modifier"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          class="btn btn-outline-warning"
                          @click="validatePlanning(planning)"
                          v-if="!planning.valide && canValidate"
                          title="Valider"
                        >
                          <i class="fas fa-check"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <Pagination
              v-if="plannings.last_page > 1"
              :pagination="plannings"
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

const router = useRouter()
const ui = useUiStore()
const auth = useAuthStore()

// État
const loading = ref(false)
const plannings = ref({ data: [] })
const departments = ref([])
const stats = ref(null)
const viewMode = ref('list')
const calendarKey = ref(0)
const statsKey = ref(0)

// Modales
const showCreateModal = ref(false)
const showDetailsModal = ref(false)
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
    const response = await client.get('/api/departments')
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

    const response = await client.get('/api/holiday-plannings', { params })
    plannings.value = response.data.plannings
    stats.value = response.data.stats

    if (departments.value.length === 0 && response.data.departments?.length) {
      departments.value = response.data.departments
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
    await client.post(`/api/holiday-plannings/${planning.id}/validate`)
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

    const response = await client.get('/api/holiday-plannings/export', {
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
</style>