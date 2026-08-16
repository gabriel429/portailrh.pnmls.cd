<template>
  <div class="performance-dashboard-page">
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-chart-pie"></i>
        </div>
        <div>
          <h1 class="hero-title">{{ auth.isSEN || auth.isRHNational ? 'Performance des agents' : 'Performance de mon équipe' }}</h1>
          <p class="hero-subtitle">
            {{ auth.isSEN || auth.isRHNational ? "Vue globale d'évaluation, par organe" : "Évaluation des agents de votre périmètre" }}
          </p>
        </div>
      </div>
      <router-link v-if="auth.isSEN || auth.isRHNational" :to="{ name: 'performance.validations' }" class="hero-btn">
        <i class="fas fa-clipboard-check"></i> File de validation
      </router-link>
    </div>

    <!-- Onglets par organe -->
    <div class="stats-tabs" role="tablist">
      <button
        v-for="tab in organeTabs"
        :key="tab.code"
        type="button"
        :class="{ active: filters.organe === tab.code }"
        @click="filters.organe = tab.code"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Filtres -->
    <div class="data-card filters-card">
      <div class="filters-grid">
        <div class="filter-field">
          <label>Recherche</label>
          <input v-model="filters.search" type="search" class="form-control" placeholder="Nom, matricule...">
        </div>
        <div class="filter-field">
          <label>Province</label>
          <select v-model="filters.province_id" class="form-select">
            <option value="">Toutes</option>
            <option v-for="p in provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
          </select>
        </div>
        <div class="filter-field">
          <label>Département</label>
          <select v-model="filters.departement_id" class="form-select">
            <option value="">Tous</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
          </select>
        </div>
        <div class="filter-field">
          <label>Année</label>
          <select v-model.number="filters.annee" class="form-select">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="filter-field">
          <label>Période</label>
          <select v-model="filters.trimestre" class="form-select">
            <option value="annuel">Année entière</option>
            <option value="1">T1</option>
            <option value="2">T2</option>
            <option value="3">T3</option>
            <option value="4">T4</option>
          </select>
        </div>
        <div class="filter-field">
          <label>Statut d'évaluation</label>
          <select v-model="filters.statut_evaluation" class="form-select">
            <option value="tous">Tous</option>
            <option value="non_evalue">Non évalué</option>
            <option value="brouillon">Brouillon</option>
            <option value="soumise">Soumise</option>
            <option value="validee">Validée</option>
            <option value="rejetee">Rejetée</option>
          </select>
        </div>
      </div>
    </div>

    <!-- KPI -->
    <div class="kpi-grid">
      <article class="kpi-tile tone-blue">
        <i class="fas fa-users"></i>
        <div><strong>{{ kpis.nb_agents ?? 0 }}</strong><span>Agents dans le périmètre</span></div>
      </article>
      <article class="kpi-tile tone-teal">
        <i class="fas fa-clipboard-check"></i>
        <div><strong>{{ kpis.nb_evalues ?? 0 }}</strong><span>Évalués sur la période</span></div>
      </article>
      <article class="kpi-tile tone-amber">
        <i class="fas fa-hourglass-half"></i>
        <div><strong>{{ kpis.nb_en_attente_validation ?? 0 }}</strong><span>En attente de validation</span></div>
      </article>
      <article class="kpi-tile tone-slate">
        <i class="fas fa-star"></i>
        <div><strong>{{ formatScore(kpis.score_global_moyen) }}</strong><span>Score global moyen</span></div>
      </article>
      <article class="kpi-tile tone-blue">
        <i class="fas fa-tasks"></i>
        <div><strong>{{ formatScore(kpis.taux_completion_moyen) }}%</strong><span>Complétion tâches (moy.)</span></div>
      </article>
      <article class="kpi-tile tone-teal">
        <i class="fas fa-calendar-check"></i>
        <div><strong>{{ formatScore(kpis.taux_assiduite_moyen) }}%</strong><span>Assiduité (moy.)</span></div>
      </article>
    </div>

    <!-- Comparaison par organe -->
    <div class="data-card chart-card">
      <div class="panel-heading">
        <h6>Score global moyen par organe</h6>
      </div>
      <div class="chart-box">
        <canvas ref="organeChart"></canvas>
      </div>
    </div>

    <!-- Tableau des agents -->
    <div class="data-card">
      <div class="table-responsive">
        <table class="table data-table">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Organe</th>
              <th>Localisation</th>
              <th class="text-end">Complétion tâches</th>
              <th class="text-end">Assiduité</th>
              <th class="text-end">Score global</th>
              <th>Statut évaluation</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody v-if="!loading">
            <tr v-for="row in rows" :key="row.id">
              <td>
                <span class="fw-semibold" style="color:#1e293b;">{{ row.prenom }} {{ row.nom }}</span>
                <small class="d-block text-muted">{{ row.matricule_etat || '-' }} · {{ row.poste_actuel || '-' }}</small>
              </td>
              <td><span class="code-badge">{{ row.structure_code }}</span></td>
              <td>{{ row.departement || row.province || '-' }}</td>
              <td class="text-end">{{ formatScore(row.score_auto ? row.score_auto : null, true) }}</td>
              <td class="text-end">{{ formatPercent(row.taux_assiduite) }}</td>
              <td class="text-end fw-semibold">{{ formatScore(row.score_global) }}</td>
              <td>
                <span class="status-indicator">
                  <span class="status-dot" :class="statutDotClass(row.statut_evaluation)"></span>
                  {{ row.statut_evaluation_label }}
                </span>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <router-link :to="{ name: 'performance.agents.show', params: { id: row.id } }" class="action-btn" title="Voir le détail">
                    <i class="fas fa-eye"></i>
                  </router-link>
                  <router-link
                    v-if="row.statut_evaluation === 'non_evalue' || row.statut_evaluation === 'rejetee'"
                    :to="{ name: 'performance.evaluations.create', query: { agent_id: row.id, annee: filters.annee, trimestre: filters.trimestre } }"
                    class="action-btn"
                    title="Évaluer"
                  >
                    <i class="fas fa-pen"></i>
                  </router-link>
                </div>
              </td>
            </tr>
            <tr v-if="rows.length === 0">
              <td colspan="8" class="text-center text-muted py-4">Aucun agent pour ces filtres.</td>
            </tr>
          </tbody>
        </table>
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border" style="color:#0077B5;" role="status"></div>
        </div>
      </div>

      <div v-if="meta.last_page > 1" class="pagination-bar">
        <button class="btn btn-sm btn-outline-secondary" :disabled="meta.current_page <= 1" @click="page--">Précédent</button>
        <span>Page {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button class="btn btn-sm btn-outline-secondary" :disabled="meta.current_page >= meta.last_page" @click="page++">Suivant</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import {
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  Legend,
  LinearScale,
  Tooltip
} from 'chart.js'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'

Chart.register(BarController, BarElement, CategoryScale, Legend, LinearScale, Tooltip)

const ui = useUiStore()
const auth = useAuthStore()

const organeTabs = [
  { code: 'tous', label: 'Tous' },
  { code: 'SEN', label: 'SEN' },
  { code: 'SEP', label: 'SEP' },
  { code: 'SEL', label: 'SEL' },
]

const currentYear = new Date().getFullYear()
const yearOptions = [currentYear, currentYear - 1, currentYear - 2]

const filters = reactive({
  organe: 'tous',
  province_id: '',
  departement_id: '',
  annee: currentYear,
  trimestre: 'annuel',
  statut_evaluation: 'tous',
  search: '',
})

const page = ref(1)
const loading = ref(false)
const rows = ref([])
const kpis = ref({})
const parOrgane = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const provinces = ref([])
const departments = ref([])

const organeChart = ref(null)
let organeChartInstance = null

async function loadFormOptions() {
  try {
    const response = await client.get('/agents/form-options')
    provinces.value = response.data.provinces || []
    departments.value = response.data.departments || []
  } catch (e) {
    // silencieux — les filtres restent utilisables sans les listes
  }
}

async function loadDashboard() {
  loading.value = true
  try {
    const response = await client.get('/performance/dashboard', {
      params: {
        ...filters,
        trimestre: filters.trimestre === 'annuel' ? '' : filters.trimestre,
        page: page.value,
      },
    })

    rows.value = response.data.data || []
    kpis.value = response.data.kpis || {}
    parOrgane.value = response.data.par_organe || []
    meta.value = response.data.meta || { current_page: 1, last_page: 1 }

    nextTick(drawOrganeChart)
  } catch (e) {
    ui.addToast(e.response?.data?.message || 'Erreur lors du chargement du tableau de performance.', 'danger')
  } finally {
    loading.value = false
  }
}

function drawOrganeChart() {
  if (!organeChart.value) return
  if (organeChartInstance) organeChartInstance.destroy()

  organeChartInstance = new Chart(organeChart.value, {
    type: 'bar',
    data: {
      labels: parOrgane.value.map(o => o.code),
      datasets: [{
        label: 'Score global moyen',
        data: parOrgane.value.map(o => o.score_global_moyen),
        backgroundColor: ['#0ea5e9', '#10b981', '#f59e0b'],
        borderRadius: 6,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true, max: 100 } },
      plugins: { legend: { display: false } },
    },
  })
}

function statutDotClass(statut) {
  return {
    non_evalue: 'dot-inactive',
    brouillon: 'dot-pending',
    soumise: 'dot-pending',
    validee: 'dot-active',
    rejetee: 'dot-inactive',
  }[statut] || 'dot-inactive'
}

function formatScore(value, allowNull = false) {
  if (value === null || value === undefined) return allowNull ? '-' : '0.0'
  return Number(value).toFixed(1)
}

function formatPercent(value) {
  if (value === null || value === undefined) return '-'
  return `${Number(value).toFixed(1)}%`
}

watch(filters, () => {
  page.value = 1
  loadDashboard()
}, { deep: true })

watch(page, loadDashboard)

onMounted(() => {
  loadFormOptions()
  loadDashboard()
})

onUnmounted(() => {
  if (organeChartInstance) organeChartInstance.destroy()
})
</script>

<style scoped>
.performance-dashboard-page {
  padding: 1.5rem 0;
}

.page-hero {
  background: linear-gradient(135deg, #0f172a 0%, #0c4a6e 55%, #0077B5 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(0, 119, 181, .25);
  margin-bottom: 1.25rem;
}
.page-hero-content { display: flex; align-items: center; gap: 1rem; }
.page-hero-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; color: #fff;
}
.hero-title { margin: 0; font-size: 1.35rem; font-weight: 700; color: #fff; }
.hero-subtitle { margin: 0; font-size: .85rem; color: rgba(255,255,255,.8); }
.hero-btn {
  background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3);
  color: #fff; padding: .5rem 1.25rem; border-radius: 10px;
  font-weight: 600; font-size: .85rem; text-decoration: none;
  display: inline-flex; align-items: center; gap: .5rem;
}
.hero-btn:hover { background: rgba(255,255,255,.35); color: #fff; }

.stats-tabs {
  display: inline-flex; gap: .35rem; border: 1px solid #cfe6f7;
  border-radius: 10px; background: #f8fbfd; padding: .25rem; margin-bottom: 1rem;
}
.stats-tabs button {
  border: 0; border-radius: 8px; background: transparent; color: #31516f;
  font-weight: 700; min-height: 34px; padding: 0 .85rem;
}
.stats-tabs button.active { background: #0284c7; color: #fff; }

.data-card {
  background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #f1f5f9; overflow: hidden; margin-bottom: 1.25rem;
}
.filters-card { padding: 1rem 1.25rem; }
.filters-grid {
  display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .85rem;
}
.filter-field label {
  display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase;
  color: #64748b; margin-bottom: .3rem;
}

.kpi-grid {
  display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .85rem; margin-bottom: 1.25rem;
}
.kpi-tile {
  display: flex; align-items: center; gap: .85rem; border: 1px solid #e2edf6;
  border-radius: 12px; background: #f8fbfd; padding: 1rem;
}
.kpi-tile i {
  display: grid; width: 42px; height: 42px; flex: 0 0 42px; place-items: center;
  border-radius: 10px; color: #fff;
}
.kpi-tile strong { display: block; color: #0f172a; font-size: 1.2rem; font-weight: 800; line-height: 1; }
.kpi-tile span { color: #64748b; font-size: .78rem; }
.tone-blue i { background: #0ea5e9; }
.tone-teal i { background: #10b981; }
.tone-amber i { background: #f59e0b; }
.tone-slate i { background: #475569; }

.chart-card { padding: 1rem 1.25rem; }
.panel-heading h6 { color: #0f172a; font-weight: 800; margin: 0 0 .75rem; }
.chart-box { height: 260px; position: relative; }

.data-table thead th {
  background: #f8fafc; border: none; font-size: .78rem; font-weight: 600;
  text-transform: uppercase; letter-spacing: .5px; color: #64748b; padding: .85rem 1rem;
}
.data-table tbody td { padding: .75rem 1rem; border-color: #f1f5f9; vertical-align: middle; font-size: .88rem; }
.data-table tbody tr:hover { background: #f8fafc; }

.code-badge {
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  background: rgba(0, 119, 181, .1); color: #005a87; font-size: .78rem;
  font-weight: 600; padding: 3px 10px; border-radius: 6px; border: 1px solid #bae6fd;
}

.status-indicator { display: inline-flex; align-items: center; font-size: .85rem; color: #475569; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.dot-active { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.2); }
.dot-inactive { background: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,.2); }
.dot-pending { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.2); }

.action-btn {
  width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center;
  justify-content: center; border: 1px solid #e2e8f0; background: #fff; color: #64748b;
  font-size: .8rem; text-decoration: none;
}
.action-btn:hover { border-color: #0077B5; color: #0077B5; background: #e0f2fe; }

.pagination-bar {
  display: flex; align-items: center; justify-content: center; gap: 1rem;
  padding: .85rem; border-top: 1px solid #f1f5f9; font-size: .85rem; color: #475569;
}

@media (max-width: 991.98px) {
  .filters-grid, .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
