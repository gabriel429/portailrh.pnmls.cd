<template>
  <div class="holiday-statistics">
    <!-- Header -->
    <div class="stats-header mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Statistiques des congés - {{ filters.year }}</h5>
          <p class="text-muted mb-0">Analyse détaillée par structure organisationnelle</p>
        </div>
        <div class="btn-group">
          <button
            class="btn btn-sm btn-outline-primary"
            :class="{ active: viewType === 'overview' }"
            @click="viewType = 'overview'"
          >
            Vue d'ensemble
          </button>
          <button
            class="btn btn-sm btn-outline-primary"
            :class="{ active: viewType === 'details' }"
            @click="viewType = 'details'"
          >
            Détails
          </button>
          <button
            class="btn btn-sm btn-outline-primary"
            :class="{ active: viewType === 'charts' }"
            @click="viewType = 'charts'"
          >
            Graphiques
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">Chargement des statistiques...</p>
    </div>

    <div v-else>
      <!-- Vue d'ensemble -->
      <div v-if="viewType === 'overview'" class="overview-section">
        <!-- Statistiques globales -->
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <div class="stat-card bg-primary text-white">
              <div class="stat-icon">
                <i class="fas fa-building"></i>
              </div>
              <div class="stat-content">
                <h3>{{ globalStats.total_structures }}</h3>
                <p>Structures</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card bg-info text-white">
              <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
              </div>
              <div class="stat-content">
                <h3>{{ formatNumber(globalStats.total_jours_prevus) }}</h3>
                <p>Jours prévus</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card bg-warning text-white">
              <div class="stat-icon">
                <i class="fas fa-calendar-minus"></i>
              </div>
              <div class="stat-content">
                <h3>{{ formatNumber(globalStats.total_jours_utilises) }}</h3>
                <p>Jours utilisés</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card bg-success text-white">
              <div class="stat-icon">
                <i class="fas fa-percentage"></i>
              </div>
              <div class="stat-content">
                <h3>{{ formatPercentage(globalStats.taux_utilisation_global) }}%</h3>
                <p>Taux d'utilisation</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Résumé par type de structure -->
        <div class="row g-4">
          <div
            v-for="(data, structureType) in statistiques"
            :key="structureType"
            class="col-lg-6"
          >
            <div class="structure-summary-card">
              <div class="card-header">
                <h6 class="mb-0">
                  <i class="fas fa-layer-group me-2"></i>
                  {{ getStructureLabel(structureType) }}
                </h6>
                <span class="badge bg-primary">{{ data.structures.length }} structures</span>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-6">
                    <div class="mini-stat">
                      <div class="mini-stat-value">{{ formatNumber(data.totals.jours_totaux) }}</div>
                      <div class="mini-stat-label">Jours totaux</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mini-stat">
                      <div class="mini-stat-value">{{ formatNumber(data.totals.jours_utilises) }}</div>
                      <div class="mini-stat-label">Jours utilisés</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mini-stat">
                      <div class="mini-stat-value">{{ formatNumber(data.totals.total_conges) }}</div>
                      <div class="mini-stat-label">Total congés</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mini-stat">
                      <div class="mini-stat-value">{{ formatPercentage(data.totals.taux_moyen) }}%</div>
                      <div class="mini-stat-label">Taux moyen</div>
                    </div>
                  </div>
                </div>

                <!-- Barre de progression globale -->
                <div class="mt-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Utilisation globale</small>
                    <small class="fw-medium">{{ formatPercentage(data.totals.taux_moyen) }}%</small>
                  </div>
                  <div class="progress" style="height: 8px;">
                    <div
                      class="progress-bar"
                      :class="getProgressBarClass(data.totals.taux_moyen)"
                      :style="{ width: data.totals.taux_moyen + '%' }"
                    ></div>
                  </div>
                </div>

                <button
                  class="btn btn-sm btn-outline-primary mt-3"
                  @click="showStructureDetails(structureType, data)"
                >
                  <i class="fas fa-eye me-1"></i>
                  Voir détails
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue détails -->
      <div v-if="viewType === 'details'" class="details-section">
        <div class="structure-tabs">
          <ul class="nav nav-tabs">
            <li
              v-for="(data, structureType) in statistiques"
              :key="structureType"
              class="nav-item"
            >
              <button
                class="nav-link"
                :class="{ active: activeStructureTab === structureType }"
                @click="activeStructureTab = structureType"
              >
                {{ getStructureLabel(structureType) }}
                <span class="badge bg-secondary ms-2">{{ data.structures.length }}</span>
              </button>
            </li>
          </ul>

          <div class="tab-content mt-3">
            <div
              v-for="(data, structureType) in statistiques"
              :key="structureType"
              class="tab-pane"
              :class="{ 'show active': activeStructureTab === structureType }"
            >
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Structure</th>
                      <th>Jours Totaux</th>
                      <th>Jours Utilisés</th>
                      <th>Taux Utilisation</th>
                      <th>Total Congés</th>
                      <th>Agents Concernés</th>
                      <th>En Attente</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="structure in data.structures" :key="structure.nom_structure">
                      <td>
                        <div class="fw-medium">{{ structure.nom_structure }}</div>
                      </td>
                      <td>
                        <span class="fw-medium">{{ structure.jours_conge_totaux }}</span>
                      </td>
                      <td>
                        <span class="fw-medium text-warning">{{ structure.jours_utilises }}</span>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="progress me-2" style="width: 60px; height: 6px;">
                            <div
                              class="progress-bar"
                              :class="getProgressBarClass(structure.taux_utilisation)"
                              :style="{ width: structure.taux_utilisation + '%' }"
                            ></div>
                          </div>
                          <small class="fw-medium">{{ formatPercentage(structure.taux_utilisation) }}%</small>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-primary">{{ structure.total_conges }}</span>
                      </td>
                      <td>
                        <span class="badge bg-info">{{ structure.agents_concernes }}</span>
                      </td>
                      <td>
                        <span
                          v-if="structure.conges_en_attente > 0"
                          class="badge bg-warning"
                        >
                          {{ structure.conges_en_attente }}
                        </span>
                        <span v-else class="text-muted">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue graphiques -->
      <div v-if="viewType === 'charts'" class="charts-section">
        <div class="row g-4">
          <!-- Graphique répartition par structure -->
          <div class="col-lg-6">
            <div class="chart-card">
              <h6 class="chart-title">
                <i class="fas fa-chart-pie me-2"></i>
                Répartition des jours utilisés
              </h6>
              <div class="chart-placeholder">
                <canvas ref="pieChart" width="300" height="300"></canvas>
              </div>
            </div>
          </div>

          <!-- Graphique évolution -->
          <div class="col-lg-6">
            <div class="chart-card">
              <h6 class="chart-title">
                <i class="fas fa-chart-bar me-2"></i>
                Taux d'utilisation par structure
              </h6>
              <div class="chart-placeholder">
                <canvas ref="barChart" width="300" height="300"></canvas>
              </div>
            </div>
          </div>

          <!-- Graphique tendances -->
          <div class="col-12">
            <div class="chart-card">
              <h6 class="chart-title">
                <i class="fas fa-chart-line me-2"></i>
                Comparaison des structures
              </h6>
              <div class="chart-placeholder">
                <canvas ref="lineChart" width="600" height="200"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const ui = useUiStore()

// État
const loading = ref(false)
const statistiques = ref({})
const globalStats = ref({})
const viewType = ref('overview')
const activeStructureTab = ref('')

// Refs pour les graphiques
const pieChart = ref(null)
const barChart = ref(null)
const lineChart = ref(null)

// Méthodes
async function loadStatistics() {
  loading.value = true
  try {
    const params = {
      year: props.filters.year || new Date().getFullYear(),
      ...props.filters
    }

    const response = await client.get('/holiday-plannings/statistiques', { params })
    statistiques.value = response.data.statistiques || {}
    globalStats.value = response.data.global || {}

    // Définir le premier onglet actif
    if (Object.keys(statistiques.value).length > 0 && !activeStructureTab.value) {
      activeStructureTab.value = Object.keys(statistiques.value)[0]
    }

    // Redessiner les graphiques
    nextTick(() => {
      if (viewType.value === 'charts') {
        drawCharts()
      }
    })

  } catch (error) {
    console.error('Erreur chargement statistiques:', error)
    ui.addToast('Erreur lors du chargement des statistiques', 'danger')
  } finally {
    loading.value = false
  }
}

function formatNumber(value) {
  if (value === null || value === undefined) return '0'
  return value.toLocaleString('fr-FR')
}

function formatPercentage(value) {
  if (value === null || value === undefined) return '0'
  return Math.round(value * 10) / 10
}

function getStructureLabel(type) {
  const labels = {
    'department': 'Départements',
    'sen': 'SEN',
    'sena': 'SENA',
    'sep': 'SEP Provincial',
    'local': 'Structures Locales'
  }
  return labels[type] || type
}

function getProgressBarClass(percentage) {
  if (percentage >= 90) return 'bg-danger'
  if (percentage >= 70) return 'bg-warning'
  return 'bg-success'
}

function showStructureDetails(structureType, data) {
  activeStructureTab.value = structureType
  viewType.value = 'details'
}

// Graphiques (version simplifiée sans Chart.js pour l'instant)
function drawCharts() {
  drawPieChart()
  drawBarChart()
  drawLineChart()
}

function drawPieChart() {
  if (!pieChart.value) return

  const ctx = pieChart.value.getContext('2d')
  const data = Object.values(statistiques.value).map(s => s.totals.jours_utilises)
  const labels = Object.keys(statistiques.value).map(getStructureLabel)

  // Simulation d'un graphique simple
  ctx.clearRect(0, 0, 300, 300)
  ctx.fillStyle = '#e9ecef'
  ctx.fillRect(0, 0, 300, 300)
  ctx.fillStyle = '#333'
  ctx.font = '14px Arial'
  ctx.textAlign = 'center'
  ctx.fillText('Graphique de répartition', 150, 150)
  ctx.fillText('(Chart.js requis)', 150, 170)
}

function drawBarChart() {
  if (!barChart.value) return

  const ctx = barChart.value.getContext('2d')
  ctx.clearRect(0, 0, 300, 300)
  ctx.fillStyle = '#e9ecef'
  ctx.fillRect(0, 0, 300, 300)
  ctx.fillStyle = '#333'
  ctx.font = '14px Arial'
  ctx.textAlign = 'center'
  ctx.fillText('Graphique en barres', 150, 150)
  ctx.fillText('(Chart.js requis)', 150, 170)
}

function drawLineChart() {
  if (!lineChart.value) return

  const ctx = lineChart.value.getContext('2d')
  ctx.clearRect(0, 0, 600, 200)
  ctx.fillStyle = '#e9ecef'
  ctx.fillRect(0, 0, 600, 200)
  ctx.fillStyle = '#333'
  ctx.font = '14px Arial'
  ctx.textAlign = 'center'
  ctx.fillText('Graphique linéaire', 300, 100)
  ctx.fillText('(Chart.js requis)', 300, 120)
}

// Watchers
watch(() => props.filters, () => {
  loadStatistics()
}, { deep: true })

watch(() => viewType.value, (newValue) => {
  if (newValue === 'charts') {
    nextTick(() => {
      drawCharts()
    })
  }
})

// Initialisation
onMounted(() => {
  loadStatistics()
})
</script>

<style scoped>
.holiday-statistics {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
}

.stats-header {
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 1rem;
}

.stat-card {
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  font-size: 1.5rem;
}

.stat-content h3 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
}

.stat-content p {
  margin: 0;
  font-size: 0.875rem;
  opacity: 0.9;
}

.structure-summary-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.card-header {
  background: #f8f9fa;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: between;
  align-items: center;
}

.card-body {
  padding: 1.5rem;
}

.mini-stat {
  text-align: center;
}

.mini-stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
}

.mini-stat-label {
  font-size: 0.75rem;
  color: #666;
  margin-top: 0.25rem;
}

.chart-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chart-title {
  margin-bottom: 1rem;
  color: #333;
  font-weight: 600;
}

.chart-placeholder {
  display: flex;
  justify-content: center;
  align-items: center;
}

.nav-tabs .nav-link {
  border: none;
  border-bottom: 2px solid transparent;
  color: #666;
  font-weight: 500;
  padding: 0.75rem 1rem;
}

.nav-tabs .nav-link.active {
  border-bottom-color: #0077B5;
  color: #0077B5;
  background: none;
}

.tab-content {
  border: none;
}

.progress {
  border-radius: 4px;
}

.btn.active {
  background-color: #0077B5;
  border-color: #0077B5;
  color: white;
}

/* Responsive */
@media (max-width: 768px) {
  .stat-card {
    padding: 1rem;
  }

  .stat-content h3 {
    font-size: 1.5rem;
  }

  .chart-placeholder canvas {
    width: 100% !important;
    max-width: 300px;
  }

  .structure-summary-card .card-body {
    padding: 1rem;
  }
}
</style>