<template>
  <div class="rh-modern">
    <div class="rh-list-shell">
      <section class="rh-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-chart-bar me-2"></i>Rapport mensuel pointages</h1>
            <p class="rh-sub">Synthese d'assiduite, de presence et d'heures travaillees.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <button class="btn-rh main" @click="exportExcel" :disabled="exporting">
                <span v-if="exporting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-download me-1"></i> Export Excel
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Navigation tabs -->
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <router-link :to="{ name: 'rh.pointages.index' }" class="btn btn-outline-secondary">
          <i class="fas fa-list me-2"></i>Liste
        </router-link>
        <router-link :to="{ name: 'rh.pointages.daily' }" class="btn btn-outline-secondary">
          <i class="fas fa-calendar-alt me-2"></i>Par jour
        </router-link>
        <router-link :to="{ name: 'rh.pointages.monthly' }" class="btn btn-primary">
          <i class="fas fa-chart-bar me-2"></i>Rapport mensuel
        </router-link>
      </div>

      <!-- Month picker -->
      <div class="rh-filters mb-3">
        <form @submit.prevent="fetchMonthly" class="row g-3 align-items-end">
          <div class="col-md-8">
            <label for="month" class="form-label">Mois</label>
            <input type="month" name="month" id="month" class="form-control" v-model="selectedMonth">
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100" :disabled="loading">
              <i class="fas fa-search me-2"></i>Afficher
            </button>
          </div>
        </form>
      </div>

      <!-- Loading -->
      <LoadingSpinner v-if="loading" message="Chargement du rapport mensuel..." />

      <template v-else>
        <!-- KPI cards -->
        <section class="kpi-grid" v-if="globalStats">
          <article class="kpi">
            <p class="label">Total agents</p>
            <h2 class="value">{{ globalStats.total_agents }}</h2>
            <span class="trend trend-info">Population suivie</span>
          </article>
          <article class="kpi">
            <p class="label">Total presents</p>
            <h2 class="value">{{ globalStats.total_present }}</h2>
            <span class="trend trend-ok">Presences cumulees</span>
          </article>
          <article class="kpi">
            <p class="label">Total absents</p>
            <h2 class="value">{{ globalStats.total_absent }}</h2>
            <span class="trend trend-bad">Absences cumulees</span>
          </article>
          <article class="kpi">
            <p class="label">Taux moyen</p>
            <h2 class="value">{{ globalStats.average_attendance }}%</h2>
            <span class="trend trend-mid">Assiduite moyenne</span>
          </article>
        </section>

        <!-- Agent stats table -->
        <div class="rh-list-card p-3 p-lg-4 mb-3">
          <h5 class="mb-3"><i class="fas fa-users me-2"></i>Detail par agent - {{ monthLabel }}</h5>
          <template v-if="agentStats.length > 0">
            <div class="rh-table-wrap">
              <table class="rh-table">
                <thead>
                  <tr>
                    <th>Agent</th>
                    <th>Matricule</th>
                    <th>Jours travail</th>
                    <th>Enregistres</th>
                    <th>Presents</th>
                    <th>Absents</th>
                    <th>Heures</th>
                    <th>Taux</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="stat in agentStats" :key="stat.agent?.id">
                    <td><strong>{{ stat.agent?.prenom }} {{ stat.agent?.nom }}</strong></td>
                    <td>{{ stat.agent?.id_agent }}</td>
                    <td>{{ stat.working_days }}</td>
                    <td>{{ stat.recorded }}</td>
                    <td><span class="status-chip st-ok">{{ stat.present }}</span></td>
                    <td><span class="status-chip st-bad">{{ stat.absent }}</span></td>
                    <td>{{ stat.total_hours }}h</td>
                    <td>
                      <span v-if="stat.attendance_rate >= 90" class="status-chip st-ok">{{ stat.attendance_rate }}%</span>
                      <span v-else-if="stat.attendance_rate >= 80" class="status-chip st-mid">{{ stat.attendance_rate }}%</span>
                      <span v-else class="status-chip st-bad">{{ stat.attendance_rate }}%</span>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong>{{ totalRecorded }}</strong></td>
                    <td><strong>{{ globalStats.total_present }}</strong></td>
                    <td><strong>{{ globalStats.total_absent }}</strong></td>
                    <td><strong>{{ totalHours }}h</strong></td>
                    <td><strong>{{ globalStats.average_attendance }}%</strong></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </template>
          <div v-else class="text-center py-5 text-muted">
            <i class="fas fa-chart-bar fa-4x mb-3 d-block"></i>
            <h5>Aucune donnee</h5>
            <p class="mb-0">Aucun pointage sur ce mois.</p>
          </div>
        </div>

        <!-- Period / averages info -->
        <div class="row g-3" v-if="globalStats && agentStats.length > 0">
          <div class="col-md-6">
            <div class="rh-list-card p-3">
              <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Periode</h6>
              <p class="mb-1"><strong>Debut:</strong> {{ formatDate(dateDebut) }}</p>
              <p class="mb-1"><strong>Fin:</strong> {{ formatDate(dateFin) }}</p>
              <p class="mb-0"><strong>Duree:</strong> {{ periodDays }} jours</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="rh-list-card p-3">
              <h6 class="mb-2"><i class="fas fa-calculator me-2"></i>Moyennes</h6>
              <p class="mb-1"><strong>Heures moyennes/agent:</strong> {{ globalStats.average_hours }}h</p>
              <p class="mb-1"><strong>Taux d'assiduite moyen:</strong> {{ globalStats.average_attendance }}%</p>
              <p class="mb-0"><strong>Agents:</strong> {{ globalStats.total_agents }}</p>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import * as pointagesApi from '@/api/pointages'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()

const loading = ref(true)
const exporting = ref(false)

const now = new Date()
const selectedMonth = ref(`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`)

const agentStats = ref([])
const globalStats = ref(null)
const dateDebut = ref('')
const dateFin = ref('')

const monthLabel = computed(() => {
    if (!selectedMonth.value) return ''
    const [year, month] = selectedMonth.value.split('-')
    const d = new Date(year, month - 1, 1)
    return d.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

const totalRecorded = computed(() => {
    return agentStats.value.reduce((sum, s) => sum + s.recorded, 0)
})

const totalHours = computed(() => {
    return agentStats.value.reduce((sum, s) => sum + s.total_hours, 0)
})

const periodDays = computed(() => {
    if (!dateDebut.value || !dateFin.value) return 0
    const start = new Date(dateDebut.value)
    const end = new Date(dateFin.value)
    return Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1
})

function formatDate(dateStr) {
    if (!dateStr) return 'N/A'
    const d = new Date(dateStr)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

async function fetchMonthly() {
    loading.value = true
    try {
        const { data } = await pointagesApi.monthly({ month: selectedMonth.value })
        agentStats.value = data.agent_stats || []
        globalStats.value = data.global_stats || null
        dateDebut.value = data.date_debut || ''
        dateFin.value = data.date_fin || ''
    } catch {
        ui.addToast('Erreur lors du chargement du rapport mensuel.', 'danger')
    } finally {
        loading.value = false
    }
}

async function exportExcel() {
    exporting.value = true
    try {
        const response = await pointagesApi.exportMonthly({ month: selectedMonth.value })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `rapport_mensuel_${selectedMonth.value}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        ui.addToast('Export Excel telecharge avec succes.', 'success')
    } catch {
        ui.addToast('Erreur lors de l\'export Excel.', 'danger')
    } finally {
        exporting.value = false
    }
}

onMounted(() => {
    fetchMonthly()
})
</script>

<style scoped>
@media (max-width: 767.98px) {
    /* Hide Matricule(2), Jours travail(3), Enregistres(4), Heures(7) */
    .rh-table th:nth-child(2),
    .rh-table td:nth-child(2),
    .rh-table th:nth-child(3),
    .rh-table td:nth-child(3),
    .rh-table th:nth-child(4),
    .rh-table td:nth-child(4),
    .rh-table th:nth-child(7),
    .rh-table td:nth-child(7) { display: none; }

    /* Compact table */
    .rh-table th, .rh-table td { padding: .4rem .3rem; font-size: .76rem; }
    .rh-table th { font-size: .65rem; }

    /* KPI grid: 2 columns on mobile */
    .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; gap: .6rem; }
    .kpi-grid .kpi { padding: .6rem; }
    .kpi-grid .kpi .value { font-size: 1.3rem; }
    .kpi-grid .kpi .label { font-size: .7rem; }
    .kpi-grid .kpi .trend { font-size: .6rem; }

    /* Period info cards stack */
    .row.g-3 > .col-md-6 { flex: 0 0 100%; max-width: 100%; }

    /* Filters */
    .rh-filters { padding: .6rem; }
    .rh-filters .row.g-3 > [class*="col-md"] { flex: 0 0 100%; max-width: 100%; }

    /* Nav tabs wrap */
    .d-flex.gap-2.mb-3 .btn { font-size: .78rem; padding: .3rem .55rem; }

    /* Footer row in tfoot - adjust colspan */
    .rh-table tfoot td { font-size: .7rem; }
}

@media (max-width: 575.98px) {
    /* Also hide Absents(6) */
    .rh-table th:nth-child(6),
    .rh-table td:nth-child(6) { display: none; }

    .rh-table th, .rh-table td { padding: .35rem .25rem; font-size: .72rem; }
    .rh-table th { font-size: .62rem; }

    .kpi-grid .kpi .value { font-size: 1.1rem; }
    .kpi-grid .kpi .label { font-size: .65rem; }
}
</style>
