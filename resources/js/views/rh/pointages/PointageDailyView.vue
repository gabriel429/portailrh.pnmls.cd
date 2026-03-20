<template>
  <div class="rh-modern">
    <div class="rh-list-shell">
      <section class="rh-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-calendar-alt me-2"></i>Pointages par jour</h1>
            <p class="rh-sub">Analyse quotidienne des presences sur une periode donnee.</p>
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
        <router-link :to="{ name: 'rh.pointages.daily' }" class="btn btn-primary">
          <i class="fas fa-calendar-alt me-2"></i>Par jour
        </router-link>
        <router-link :to="{ name: 'rh.pointages.monthly' }" class="btn btn-outline-secondary">
          <i class="fas fa-chart-bar me-2"></i>Rapport mensuel
        </router-link>
      </div>

      <!-- Filters -->
      <div class="rh-filters mb-3">
        <form @submit.prevent="fetchDaily" class="row g-3">
          <div class="col-md-3">
            <label for="date_debut" class="form-label">Date debut</label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" v-model="filters.date_debut">
          </div>
          <div class="col-md-3">
            <label for="date_fin" class="form-label">Date fin</label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" v-model="filters.date_fin">
          </div>
          <div class="col-md-4">
            <label for="agent_id" class="form-label">Agent</label>
            <select name="agent_id" id="agent_id" class="form-select" v-model="filters.agent_id">
              <option value="">Tous les agents</option>
              <option v-for="agent in agentsList" :key="agent.id" :value="agent.id">
                {{ agent.prenom }} {{ agent.nom }} ({{ agent.id_agent }})
              </option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100" :disabled="loading">
              <i class="fas fa-filter me-2"></i>Filtrer
            </button>
          </div>
        </form>
      </div>

      <!-- Loading -->
      <LoadingSpinner v-if="loading" message="Chargement des pointages..." />

      <template v-else>
        <!-- Daily cards -->
        <template v-if="days.length > 0">
          <div v-for="day in days" :key="day.date" class="rh-list-card p-3 p-lg-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
              <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>{{ formatDateLong(day.date) }}</h5>
              <div class="d-flex gap-2">
                <span class="rh-pill st-neutral">{{ day.stats.count }} agents</span>
                <span class="rh-pill st-ok">{{ day.stats.present }} presents</span>
                <span class="rh-pill st-bad">{{ day.stats.absent }} absents</span>
              </div>
            </div>
            <div class="rh-table-wrap">
              <table class="rh-table">
                <thead>
                  <tr>
                    <th>Agent</th>
                    <th>Matricule</th>
                    <th>Entree</th>
                    <th>Sortie</th>
                    <th>Heures</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pointage in day.pointages" :key="pointage.id">
                    <td><strong>{{ pointage.agent?.prenom }} {{ pointage.agent?.nom }}</strong></td>
                    <td>{{ pointage.agent?.id_agent }}</td>
                    <td>{{ pointage.heure_entree ? formatTime(pointage.heure_entree) : '-' }}</td>
                    <td>{{ pointage.heure_sortie ? formatTime(pointage.heure_sortie) : '-' }}</td>
                    <td>{{ pointage.heures_travaillees ? pointage.heures_travaillees + 'h' : '-' }}</td>
                    <td>
                      <span v-if="pointage.heure_entree" class="status-chip st-ok">Present</span>
                      <span v-else class="status-chip st-bad">Absent</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="day.stats.total_hours > 0" class="text-end text-muted small mt-2">
              <strong>Total heures:</strong> {{ day.stats.total_hours }}h
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="rh-list-card p-5 text-center">
          <i class="fas fa-calendar-alt fa-4x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Aucun pointage</h5>
          <p class="text-muted">Aucune donnee pour la periode selectionnee.</p>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import * as pointagesApi from '@/api/pointages'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()

const loading = ref(true)
const exporting = ref(false)
const days = ref([])
const agentsList = ref([])

// Default: from start of month to today
const now = new Date()
const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)

const filters = reactive({
    date_debut: startOfMonth.toISOString().split('T')[0],
    date_fin: now.toISOString().split('T')[0],
    agent_id: '',
})

function formatTime(timeStr) {
    if (!timeStr) return '-'
    if (timeStr.length > 5) {
        const d = new Date(timeStr)
        if (!isNaN(d.getTime())) {
            return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
        }
    }
    return timeStr.substring(0, 5)
}

function formatDateLong(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr + 'T00:00:00')
    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
    return d.toLocaleDateString('fr-FR', options)
}

async function fetchDaily() {
    loading.value = true
    try {
        const params = {
            date_debut: filters.date_debut,
            date_fin: filters.date_fin,
        }
        if (filters.agent_id) params.agent_id = filters.agent_id

        const { data } = await pointagesApi.daily(params)
        days.value = data.days || []
        agentsList.value = data.agents || []
    } catch {
        ui.addToast('Erreur lors du chargement des pointages.', 'danger')
    } finally {
        loading.value = false
    }
}

async function exportExcel() {
    exporting.value = true
    try {
        const params = {
            date_debut: filters.date_debut,
            date_fin: filters.date_fin,
        }
        if (filters.agent_id) params.agent_id = filters.agent_id

        const response = await pointagesApi.exportDaily(params)

        // Create download link from blob
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `pointages_${filters.date_debut}_${filters.date_fin}.xlsx`)
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
    fetchDaily()
})
</script>

<style scoped>
@media (max-width: 767.98px) {
    /* Hide Matricule(2), Entree(3), Sortie(4) */
    .rh-table th:nth-child(2),
    .rh-table td:nth-child(2),
    .rh-table th:nth-child(3),
    .rh-table td:nth-child(3),
    .rh-table th:nth-child(4),
    .rh-table td:nth-child(4) { display: none; }

    /* Compact table */
    .rh-table th, .rh-table td { padding: .4rem .3rem; font-size: .76rem; }
    .rh-table th { font-size: .65rem; }

    /* Day card stats should wrap */
    .d-flex.gap-2 .rh-pill { font-size: .68rem; padding: .15rem .4rem; }

    /* Filters */
    .rh-filters { padding: .6rem; }
    .rh-filters .row.g-3 > [class*="col-md"] { flex: 0 0 100%; max-width: 100%; }

    /* Nav tabs wrap */
    .d-flex.gap-2.mb-3 .btn { font-size: .78rem; padding: .3rem .55rem; }
}

@media (max-width: 575.98px) {
    /* Also hide Heures(5) */
    .rh-table th:nth-child(5),
    .rh-table td:nth-child(5) { display: none; }

    .rh-table th, .rh-table td { padding: .35rem .25rem; font-size: .72rem; }
    .rh-table th { font-size: .62rem; }

    .d-flex.gap-2 .rh-pill { font-size: .62rem; padding: .1rem .3rem; }
}
</style>
