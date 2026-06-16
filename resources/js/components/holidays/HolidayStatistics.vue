<template>
  <div class="holiday-statistics">
    <div class="stats-top">
      <div>
        <div class="stats-eyebrow">Statistiques RH</div>
        <h5>Congés {{ currentYear }}</h5>
        <p>{{ totalHolidays }} congé(s), {{ formatNumber(globalStats.total_jours_utilises) }} jour(s) utilisés</p>
      </div>
      <div class="stats-tabs" role="tablist">
        <button type="button" :class="{ active: viewType === 'summary' }" @click="viewType = 'summary'">
          Synthèse
        </button>
        <button type="button" :class="{ active: viewType === 'structures' }" @click="viewType = 'structures'">
          Structures
        </button>
        <button type="button" :class="{ active: viewType === 'trends' }" @click="viewType = 'trends'">
          Tendances
        </button>
      </div>
    </div>

    <div v-if="loading" class="stats-loading">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <template v-else>
      <div class="kpi-grid">
        <article v-for="item in kpis" :key="item.label" class="kpi-tile" :class="item.tone">
          <i :class="item.icon"></i>
          <div>
            <strong>{{ item.value }}</strong>
            <span>{{ item.label }}</span>
          </div>
        </article>
      </div>

      <section v-if="viewType === 'summary'" class="stats-layout">
        <article class="stats-panel utilization-panel">
          <div>
            <h6>Utilisation globale</h6>
            <p>{{ formatNumber(globalStats.total_jours_utilises) }} / {{ formatNumber(globalStats.total_jours_prevus) }} jours</p>
          </div>
          <div class="usage-ring" :style="usageRingStyle">
            <span>{{ formatPercentage(globalStats.taux_utilisation_global) }}%</span>
          </div>
        </article>

        <article class="stats-panel">
          <div class="panel-heading">
            <h6>Statuts des demandes</h6>
            <span>{{ statusRows.length }}</span>
          </div>
          <div v-if="statusRows.length" class="status-list">
            <div v-for="status in statusRows" :key="status.statut" class="status-row">
              <span class="status-pill" :class="statusClass(status.statut)">{{ status.label }}</span>
              <strong>{{ status.total }}</strong>
              <small>{{ status.jours }}j</small>
              <div class="mini-track">
                <span :style="{ width: percentageWidth(status.total, statusTotal) }"></span>
              </div>
            </div>
          </div>
          <div v-else class="empty-inline">Aucune demande sur cette période</div>
        </article>

        <article class="stats-panel">
          <div class="panel-heading">
            <h6>Types de congés</h6>
            <span>{{ typeRows.length }}</span>
          </div>
          <div v-if="typeRows.length" class="type-list">
            <div v-for="type in typeRows" :key="type.type" class="type-row">
              <div>
                <strong>{{ type.label }}</strong>
                <small>{{ type.total }} demande(s)</small>
              </div>
              <div class="type-meter">
                <span :class="typeClass(type.type)" :style="{ width: percentageWidth(type.jours, typeDayMax) }"></span>
              </div>
              <b>{{ type.jours }}j</b>
            </div>
          </div>
          <div v-else class="empty-inline">Aucun type à afficher</div>
        </article>

        <article class="stats-panel">
          <div class="panel-heading">
            <h6>Agents les plus concernés</h6>
            <span>{{ topAgents.length }}</span>
          </div>
          <div v-if="topAgents.length" class="agent-list">
            <div v-for="agent in topAgents" :key="agent.agent_id" class="agent-row">
              <div>
                <strong>{{ agent.agent }}</strong>
                <small>{{ agent.total }} congé(s)</small>
              </div>
              <span>{{ agent.jours }}j</span>
            </div>
          </div>
          <div v-else class="empty-inline">Aucun congé approuvé</div>
        </article>
      </section>

      <section v-if="viewType === 'structures'" class="stats-panel structures-panel">
        <div class="structures-toolbar">
          <div>
            <h6>Comparaison par structure</h6>
            <p>{{ filteredStructureRows.length }} structure(s)</p>
          </div>
          <div class="structure-search">
            <i class="fas fa-search"></i>
            <input v-model="structureSearch" type="search" class="form-control" placeholder="Rechercher une structure">
          </div>
        </div>

        <div class="table-responsive">
          <table class="table align-middle stats-table">
            <thead>
              <tr>
                <th>Structure</th>
                <th>Type</th>
                <th>Jours prévus</th>
                <th>Jours utilisés</th>
                <th>Restants</th>
                <th>Taux</th>
                <th>Congés</th>
                <th>En attente</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in filteredStructureRows" :key="`${row.structureType}-${row.nom_structure}`">
                <td>
                  <strong>{{ row.nom_structure }}</strong>
                  <small>{{ row.agents_concernes || 0 }} agent(s)</small>
                </td>
                <td><span class="structure-type">{{ row.typeLabel }}</span></td>
                <td>{{ formatNumber(row.jours_conge_totaux) }}</td>
                <td>{{ formatNumber(row.jours_utilises) }}</td>
                <td>{{ formatNumber(row.jours_restants) }}</td>
                <td>
                  <div class="rate-cell">
                    <div class="rate-track">
                      <span :class="rateClass(row.taux_utilisation)" :style="{ width: percentageWidth(row.taux_utilisation, 100) }"></span>
                    </div>
                    <b>{{ formatPercentage(row.taux_utilisation) }}%</b>
                  </div>
                </td>
                <td><span class="count-badge">{{ row.total_conges || 0 }}</span></td>
                <td>
                  <span v-if="row.conges_en_attente" class="pending-badge">{{ row.conges_en_attente }}</span>
                  <span v-else class="text-muted">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="viewType === 'trends'" class="trends-grid">
        <article class="stats-panel chart-panel">
          <div class="panel-heading">
            <h6>Évolution mensuelle</h6>
            <span>{{ currentYear }}</span>
          </div>
          <div class="chart-box">
            <canvas ref="monthlyChart"></canvas>
          </div>
        </article>

        <article class="stats-panel chart-panel">
          <div class="panel-heading">
            <h6>Répartition par type</h6>
            <span>{{ formatNumber(typeDayMax) }}j max</span>
          </div>
          <div class="chart-box">
            <canvas ref="typeChart"></canvas>
          </div>
        </article>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import {
  ArcElement,
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  DoughnutController,
  Legend,
  LineController,
  LineElement,
  LinearScale,
  PointElement,
  Tooltip
} from 'chart.js'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

Chart.register(
  ArcElement,
  BarController,
  BarElement,
  CategoryScale,
  DoughnutController,
  Legend,
  LineController,
  LineElement,
  LinearScale,
  PointElement,
  Tooltip
)

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const ui = useUiStore()
const loading = ref(false)
const statistiques = ref({})
const globalStats = ref({})
const holidaysSummary = ref({
  by_type: [],
  by_status: [],
  monthly: [],
  top_agents: []
})
const viewType = ref('summary')
const structureSearch = ref('')
const monthlyChart = ref(null)
const typeChart = ref(null)

let monthlyChartInstance = null
let typeChartInstance = null

const monthLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc']
const chartColors = ['#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b']

const currentYear = computed(() => props.filters.year || new Date().getFullYear())
const statusRows = computed(() => holidaysSummary.value.by_status || [])
const typeRows = computed(() => holidaysSummary.value.by_type || [])
const topAgents = computed(() => holidaysSummary.value.top_agents || [])
const statusTotal = computed(() => Math.max(1, statusRows.value.reduce((total, item) => total + numberValue(item.total), 0)))
const typeDayMax = computed(() => Math.max(1, ...typeRows.value.map(item => numberValue(item.jours))))
const totalHolidays = computed(() => numberValue(globalStats.value.total_conges))

const structureRows = computed(() => {
  return Object.entries(statistiques.value).flatMap(([structureType, data]) => {
    return (data.structures || []).map(structure => ({
      ...structure,
      structureType,
      typeLabel: getStructureLabel(structureType),
      jours_conge_totaux: numberValue(structure.jours_conge_totaux),
      jours_utilises: numberValue(structure.jours_utilises),
      jours_restants: numberValue(structure.jours_restants),
      taux_utilisation: numberValue(structure.taux_utilisation)
    }))
  })
})

const filteredStructureRows = computed(() => {
  const query = structureSearch.value.trim().toLowerCase()
  if (!query) return structureRows.value

  return structureRows.value.filter(row => [
    row.nom_structure,
    row.typeLabel
  ].some(value => String(value || '').toLowerCase().includes(query)))
})

const kpis = computed(() => [
  {
    label: 'Structures',
    value: formatNumber(globalStats.value.total_structures),
    icon: 'fas fa-building',
    tone: 'tone-blue'
  },
  {
    label: 'Jours prévus',
    value: formatNumber(globalStats.value.total_jours_prevus),
    icon: 'fas fa-calendar-day',
    tone: 'tone-teal'
  },
  {
    label: 'Jours utilisés',
    value: formatNumber(globalStats.value.total_jours_utilises),
    icon: 'fas fa-calendar-minus',
    tone: 'tone-amber'
  },
  {
    label: 'Demandes',
    value: formatNumber(globalStats.value.total_conges),
    icon: 'fas fa-file-signature',
    tone: 'tone-slate'
  }
])

const monthlyRows = computed(() => {
  const map = new Map((holidaysSummary.value.monthly || []).map(item => [numberValue(item.month), item]))
  return monthLabels.map((label, index) => {
    const item = map.get(index + 1) || {}
    return {
      label,
      total: numberValue(item.total),
      jours: numberValue(item.jours)
    }
  })
})

const usageRingStyle = computed(() => {
  const percent = clamp(numberValue(globalStats.value.taux_utilisation_global), 0, 100)
  return {
    background: `conic-gradient(#0ea5e9 ${percent * 3.6}deg, #e2e8f0 0deg)`
  }
})

async function loadStatistics() {
  loading.value = true
  try {
    const response = await client.get('/holiday-plannings/statistiques', {
      params: {
        year: currentYear.value,
        ...props.filters
      }
    })

    statistiques.value = response.data.statistiques || {}
    globalStats.value = response.data.global || {}
    holidaysSummary.value = response.data.holidays_summary || {
      by_type: [],
      by_status: [],
      monthly: [],
      top_agents: []
    }

    if (viewType.value === 'trends') {
      nextTick(drawTrendCharts)
    }
  } catch (error) {
    console.error('Erreur chargement statistiques:', error)
    ui.addToast('Erreur lors du chargement des statistiques', 'danger')
  } finally {
    loading.value = false
  }
}

function drawTrendCharts() {
  drawMonthlyChart()
  drawTypeChart()
}

function drawMonthlyChart() {
  if (!monthlyChart.value) return
  if (monthlyChartInstance) monthlyChartInstance.destroy()

  monthlyChartInstance = new Chart(monthlyChart.value, {
    type: 'bar',
    data: {
      labels: monthlyRows.value.map(item => item.label),
      datasets: [
        {
          label: 'Demandes',
          data: monthlyRows.value.map(item => item.total),
          backgroundColor: '#0ea5e9',
          borderRadius: 6
        },
        {
          label: 'Jours',
          type: 'line',
          data: monthlyRows.value.map(item => item.jours),
          borderColor: '#f59e0b',
          backgroundColor: '#f59e0b',
          borderWidth: 3,
          pointRadius: 3,
          tension: .35,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } },
        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } }
      },
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  })
}

function drawTypeChart() {
  if (!typeChart.value) return
  if (typeChartInstance) typeChartInstance.destroy()

  typeChartInstance = new Chart(typeChart.value, {
    type: 'doughnut',
    data: {
      labels: typeRows.value.map(item => item.label),
      datasets: [{
        data: typeRows.value.map(item => item.jours),
        backgroundColor: chartColors.slice(0, Math.max(1, typeRows.value.length)),
        borderColor: '#fff',
        borderWidth: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '64%',
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  })
}

function destroyCharts() {
  if (monthlyChartInstance) {
    monthlyChartInstance.destroy()
    monthlyChartInstance = null
  }
  if (typeChartInstance) {
    typeChartInstance.destroy()
    typeChartInstance = null
  }
}

function getStructureLabel(type) {
  return {
    department: 'Département',
    sen: 'SEN',
    sena: 'SENA',
    sep: 'SEP Provincial',
    local: 'Structure locale'
  }[type] || type
}

function formatNumber(value) {
  return numberValue(value).toLocaleString('fr-FR')
}

function formatPercentage(value) {
  return Math.round(numberValue(value) * 10) / 10
}

function percentageWidth(value, max) {
  return `${clamp((numberValue(value) / Math.max(1, numberValue(max))) * 100, 0, 100)}%`
}

function numberValue(value) {
  const number = Number(value)
  return Number.isFinite(number) ? number : 0
}

function clamp(value, min, max) {
  return Math.min(max, Math.max(min, value))
}

function statusClass(status) {
  return {
    approuve: 'status-approved',
    en_attente: 'status-pending',
    refuse: 'status-refused',
    annule: 'status-cancelled',
    retourne: 'status-returned'
  }[status] || 'status-cancelled'
}

function typeClass(type) {
  return {
    annuel: 'type-annuel',
    maladie: 'type-maladie',
    maternite: 'type-maternite',
    paternite: 'type-paternite',
    urgence: 'type-urgence',
    special: 'type-special'
  }[type] || 'type-special'
}

function rateClass(value) {
  if (numberValue(value) >= 90) return 'rate-danger'
  if (numberValue(value) >= 70) return 'rate-warning'
  return 'rate-good'
}

watch(() => props.filters, () => loadStatistics(), { deep: true })

watch(() => viewType.value, value => {
  if (value === 'trends') {
    nextTick(drawTrendCharts)
  } else {
    destroyCharts()
  }
})

onMounted(() => loadStatistics())
onUnmounted(() => destroyCharts())
</script>

<style scoped>
.holiday-statistics {
  background: #fff;
  border: 1px solid #d9e9f7;
  border-radius: 14px;
  padding: 1.25rem;
}

.stats-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.stats-eyebrow {
  color: #0284c7;
  font-size: .75rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
}

.stats-top h5 {
  color: #0f172a;
  font-weight: 800;
  margin: .15rem 0;
}

.stats-top p,
.structures-toolbar p {
  color: #64748b;
  margin: 0;
}

.stats-tabs {
  display: inline-flex;
  gap: .35rem;
  border: 1px solid #cfe6f7;
  border-radius: 10px;
  background: #f8fbfd;
  padding: .25rem;
}

.stats-tabs button {
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #31516f;
  font-weight: 700;
  min-height: 34px;
  padding: 0 .85rem;
}

.stats-tabs button.active {
  background: #0284c7;
  color: #fff;
}

.stats-loading {
  display: grid;
  min-height: 280px;
  place-items: center;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: .85rem;
  margin-bottom: 1rem;
}

.kpi-tile {
  display: flex;
  align-items: center;
  gap: .85rem;
  border: 1px solid #e2edf6;
  border-radius: 12px;
  background: #f8fbfd;
  padding: 1rem;
}

.kpi-tile i {
  display: grid;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  place-items: center;
  border-radius: 10px;
  color: #fff;
}

.kpi-tile strong {
  display: block;
  color: #0f172a;
  font-size: 1.35rem;
  font-weight: 800;
  line-height: 1;
}

.kpi-tile span {
  color: #64748b;
  font-size: .84rem;
}

.tone-blue i { background: #0ea5e9; }
.tone-teal i { background: #10b981; }
.tone-amber i { background: #f59e0b; }
.tone-slate i { background: #475569; }

.stats-layout,
.trends-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.stats-panel {
  border: 1px solid #d9e9f7;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
}

.panel-heading,
.utilization-panel,
.structures-toolbar,
.agent-row,
.type-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.stats-panel h6 {
  color: #0f172a;
  font-weight: 800;
  margin: 0;
}

.panel-heading span {
  border-radius: 999px;
  background: #e0f2fe;
  color: #0369a1;
  font-size: .75rem;
  font-weight: 800;
  padding: .2rem .55rem;
}

.usage-ring {
  display: grid;
  width: 108px;
  height: 108px;
  flex: 0 0 108px;
  place-items: center;
  border-radius: 50%;
}

.usage-ring span {
  display: grid;
  width: 76px;
  height: 76px;
  place-items: center;
  border-radius: 50%;
  background: #fff;
  color: #0f172a;
  font-weight: 900;
}

.status-list,
.type-list,
.agent-list {
  display: grid;
  gap: .75rem;
  margin-top: 1rem;
}

.status-row {
  display: grid;
  grid-template-columns: minmax(120px, 1fr) 40px 48px minmax(80px, 1fr);
  align-items: center;
  gap: .6rem;
}

.status-pill,
.structure-type,
.count-badge,
.pending-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 800;
  padding: .25rem .65rem;
}

.status-approved { background: #dcfce7; color: #166534; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-refused { background: #fee2e2; color: #991b1b; }
.status-cancelled { background: #e2e8f0; color: #334155; }
.status-returned { background: #dbeafe; color: #1d4ed8; }

.mini-track,
.type-meter,
.rate-track {
  overflow: hidden;
  border-radius: 999px;
  background: #e2e8f0;
}

.mini-track,
.type-meter {
  height: 8px;
}

.mini-track span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: #0ea5e9;
}

.type-row {
  display: grid;
  grid-template-columns: minmax(140px, 1fr) minmax(90px, 1fr) 48px;
}

.type-row strong,
.agent-row strong,
.stats-table strong {
  display: block;
  color: #0f172a;
}

.type-row small,
.agent-row small,
.stats-table small {
  display: block;
  color: #64748b;
  font-size: .78rem;
}

.type-meter span,
.rate-track span {
  display: block;
  height: 100%;
  border-radius: inherit;
}

.type-annuel { background: #3b82f6; }
.type-maladie { background: #ef4444; }
.type-maternite { background: #22c55e; }
.type-paternite { background: #06b6d4; }
.type-urgence { background: #f59e0b; }
.type-special { background: #8b5cf6; }

.agent-row {
  border-bottom: 1px solid #eef5fb;
  padding-bottom: .65rem;
}

.agent-row:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.agent-row span {
  color: #0f172a;
  font-weight: 900;
}

.structures-panel {
  min-width: 0;
}

.structures-toolbar {
  margin-bottom: 1rem;
}

.structure-search {
  position: relative;
  width: min(100%, 340px);
}

.structure-search i {
  position: absolute;
  left: .75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.structure-search .form-control {
  padding-left: 2rem;
}

.stats-table {
  min-width: 980px;
  margin-bottom: 0;
}

.stats-table th {
  color: #31516f;
  font-size: .76rem;
  text-transform: uppercase;
  letter-spacing: 0;
  white-space: nowrap;
}

.rate-cell {
  display: flex;
  align-items: center;
  gap: .65rem;
}

.rate-track {
  width: 96px;
  height: 8px;
}

.rate-good { background: #10b981; }
.rate-warning { background: #f59e0b; }
.rate-danger { background: #ef4444; }

.structure-type {
  background: #e0f2fe;
  color: #0369a1;
}

.count-badge {
  background: #dbeafe;
  color: #1d4ed8;
}

.pending-badge {
  background: #fef3c7;
  color: #92400e;
}

.chart-panel {
  min-width: 0;
}

.chart-box {
  height: 320px;
  margin-top: 1rem;
  position: relative;
}

.empty-inline {
  display: grid;
  min-height: 120px;
  place-items: center;
  color: #64748b;
}

@media (max-width: 991.98px) {
  .stats-top,
  .structures-toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .kpi-grid,
  .stats-layout,
  .trends-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 575.98px) {
  .holiday-statistics {
    padding: 1rem;
  }

  .stats-tabs {
    display: grid;
    grid-template-columns: 1fr;
    width: 100%;
  }

  .kpi-grid,
  .stats-layout,
  .trends-grid {
    grid-template-columns: 1fr;
  }

  .utilization-panel {
    align-items: flex-start;
    flex-direction: column;
  }

  .structure-search {
    width: 100%;
  }
}
</style>
