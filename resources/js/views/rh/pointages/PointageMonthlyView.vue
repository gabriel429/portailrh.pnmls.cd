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

      <!-- Month picker + organe filter -->
      <div class="rh-filters mb-3">
        <form @submit.prevent="fetchMonthly" class="row g-3 align-items-end">
          <div class="col-md-5">
            <label for="month" class="form-label">Mois</label>
            <input type="month" name="month" id="month" class="form-control" v-model="selectedMonth">
          </div>
          <div class="col-md-4">
            <label for="organe" class="form-label">Organe</label>
            <select id="organe" v-model="selectedOrgane" class="form-select">
              <option value="">Tous les organes</option>
              <option v-for="o in organeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100" :disabled="loading">
              <i class="fas fa-search me-2"></i>Afficher
            </button>
          </div>
        </form>
      </div>

      <LoadingSpinner v-if="loading" message="Chargement du rapport mensuel..." />

      <template v-else>
        <!-- Organe overview cards -->
        <div class="pm-organe-overview" v-if="statsByOrgane && !selectedOrgane">
          <div class="pm-organe-card" v-for="o in organeOverview" :key="o.code"
            :class="{ active: selectedOrgane === o.value }"
            @click="filterByOrgane(o.value)">
            <div class="pm-organe-top">
              <div class="pm-organe-badge" :style="{ background: o.color }">{{ o.code }}</div>
              <div class="pm-organe-name">{{ o.label }}</div>
            </div>
            <div class="pm-organe-stats">
              <div class="pm-organe-stat">
                <div class="pm-organe-val">{{ o.total_agents }}</div>
                <div class="pm-organe-lbl">Agents</div>
              </div>
              <div class="pm-organe-stat">
                <div class="pm-organe-val" style="color:#059669;">{{ o.total_present }}</div>
                <div class="pm-organe-lbl">Presents</div>
              </div>
              <div class="pm-organe-stat">
                <div class="pm-organe-val" style="color:#dc2626;">{{ o.total_absent }}</div>
                <div class="pm-organe-lbl">Absents</div>
              </div>
              <div class="pm-organe-stat">
                <div class="pm-organe-val" :style="{ color: rateColor(o.average_attendance) }">{{ o.average_attendance }}%</div>
                <div class="pm-organe-lbl">Taux</div>
              </div>
            </div>
            <div class="pm-organe-bar">
              <div class="pm-organe-bar-fill" :style="{ width: o.average_attendance + '%', background: o.color }"></div>
            </div>
          </div>
        </div>

        <!-- KPI cards -->
        <div class="pm-kpi-grid" v-if="globalStats">
          <div class="pm-kpi">
            <div class="pm-kpi-icon" style="background:#e0f2fe;color:#0077B5;">
              <i class="fas fa-users"></i>
            </div>
            <div class="pm-kpi-body">
              <div class="pm-kpi-val">{{ globalStats.total_agents }}</div>
              <div class="pm-kpi-lbl">{{ selectedOrgane ? 'Agents (organe)' : 'Total agents' }}</div>
            </div>
          </div>
          <div class="pm-kpi">
            <div class="pm-kpi-icon" style="background:#d1fae5;color:#059669;">
              <i class="fas fa-user-check"></i>
            </div>
            <div class="pm-kpi-body">
              <div class="pm-kpi-val">{{ globalStats.total_present }}</div>
              <div class="pm-kpi-lbl">Presences cumulees</div>
            </div>
          </div>
          <div class="pm-kpi">
            <div class="pm-kpi-icon" style="background:#fee2e2;color:#dc2626;">
              <i class="fas fa-user-times"></i>
            </div>
            <div class="pm-kpi-body">
              <div class="pm-kpi-val">{{ globalStats.total_absent }}</div>
              <div class="pm-kpi-lbl">Absences cumulees</div>
            </div>
          </div>
          <div class="pm-kpi">
            <div class="pm-kpi-icon" :style="{ background: rateColor(globalStats.average_attendance) + '20', color: rateColor(globalStats.average_attendance) }">
              <i class="fas fa-chart-line"></i>
            </div>
            <div class="pm-kpi-body">
              <div class="pm-kpi-val">{{ globalStats.average_attendance }}%</div>
              <div class="pm-kpi-lbl">Taux moyen</div>
            </div>
          </div>
        </div>

        <!-- Active organe filter chip -->
        <div v-if="selectedOrgane" class="pm-active-filter">
          <span class="pm-filter-chip">
            <i class="fas fa-filter me-1"></i>
            {{ organeOptions.find(o => o.value === selectedOrgane)?.label || selectedOrgane }}
            <button @click="clearOrgane" class="pm-filter-clear"><i class="fas fa-times"></i></button>
          </span>
        </div>

        <!-- Agent stats table -->
        <div class="pm-table-card">
          <div class="pm-table-header">
            <h5><i class="fas fa-table me-2"></i>Detail par agent — {{ monthLabel }}</h5>
            <span class="pm-table-count">{{ agentStats.length }} agent(s)</span>
          </div>
          <template v-if="agentStats.length > 0">
            <div class="rh-table-wrap">
              <table class="rh-table">
                <thead>
                  <tr>
                    <th>Agent</th>
                    <th>Matricule</th>
                    <th>Organe</th>
                    <th>Jours travail</th>
                    <th>Presents</th>
                    <th>Absents</th>
                    <th>Heures</th>
                    <th>Taux</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="stat in agentStats" :key="stat.agent?.id">
                    <td><strong>{{ stat.agent?.prenom }} {{ stat.agent?.nom }}</strong></td>
                    <td><span class="pm-matricule">{{ stat.agent?.id_agent }}</span></td>
                    <td><span class="pm-organe-chip" :style="{ background: organeColor(stat.agent?.organe) + '18', color: organeColor(stat.agent?.organe) }">{{ organeShort(stat.agent?.organe) }}</span></td>
                    <td>{{ stat.working_days }}</td>
                    <td><span class="status-chip st-ok">{{ stat.present }}</span></td>
                    <td><span class="status-chip st-bad">{{ stat.absent }}</span></td>
                    <td>{{ stat.total_hours }}h</td>
                    <td>
                      <span class="pm-rate-badge" :style="{ background: rateColor(stat.attendance_rate) + '18', color: rateColor(stat.attendance_rate) }">
                        {{ stat.attendance_rate }}%
                      </span>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4"><strong>Total</strong></td>
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
            <i class="fas fa-chart-bar fa-3x mb-3 d-block" style="opacity:.3;"></i>
            <h5>Aucune donnee</h5>
            <p class="mb-0">Aucun pointage sur ce mois{{ selectedOrgane ? ' pour cet organe' : '' }}.</p>
          </div>
        </div>

        <!-- Period / averages info -->
        <div class="pm-info-grid" v-if="globalStats && agentStats.length > 0">
          <div class="pm-info-card">
            <div class="pm-info-title"><i class="fas fa-calendar me-2" style="color:#0077B5;"></i>Periode</div>
            <div class="pm-info-row"><span>Debut</span><strong>{{ formatDate(dateDebut) }}</strong></div>
            <div class="pm-info-row"><span>Fin</span><strong>{{ formatDate(dateFin) }}</strong></div>
            <div class="pm-info-row"><span>Duree</span><strong>{{ periodDays }} jours</strong></div>
          </div>
          <div class="pm-info-card">
            <div class="pm-info-title"><i class="fas fa-calculator me-2" style="color:#7c3aed;"></i>Moyennes</div>
            <div class="pm-info-row"><span>Heures/agent</span><strong>{{ globalStats.average_hours }}h</strong></div>
            <div class="pm-info-row"><span>Taux assiduite</span><strong>{{ globalStats.average_attendance }}%</strong></div>
            <div class="pm-info-row"><span>Agents</span><strong>{{ globalStats.total_agents }}</strong></div>
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
const selectedOrgane = ref('')

const agentStats = ref([])
const globalStats = ref(null)
const statsByOrgane = ref(null)
const dateDebut = ref('')
const dateFin = ref('')

const organeOptions = [
  { value: 'Secretariat Executif National', label: 'National (SEN)', code: 'SEN', color: '#0077B5' },
  { value: 'Secretariat Executif Provincial', label: 'Provincial (SEP)', code: 'SEP', color: '#0ea5e9' },
  { value: 'Secretariat Executif Local', label: 'Local (SEL)', code: 'SEL', color: '#0d9488' },
]

const organeOverview = computed(() => {
  if (!statsByOrgane.value) return []
  return organeOptions.map(o => ({
    ...o,
    ...(statsByOrgane.value[o.code.toLowerCase()] || { total_agents: 0, total_present: 0, total_absent: 0, total_hours: 0, average_attendance: 0 }),
  }))
})

const monthLabel = computed(() => {
  if (!selectedMonth.value) return ''
  const [year, month] = selectedMonth.value.split('-')
  const d = new Date(year, month - 1, 1)
  return d.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

const totalHours = computed(() => agentStats.value.reduce((sum, s) => sum + s.total_hours, 0))

const periodDays = computed(() => {
  if (!dateDebut.value || !dateFin.value) return 0
  const start = new Date(dateDebut.value)
  const end = new Date(dateFin.value)
  return Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1
})

function rateColor(rate) {
  if (rate >= 90) return '#059669'
  if (rate >= 80) return '#d97706'
  return '#dc2626'
}

function organeColor(organe) {
  if (!organe) return '#94a3b8'
  if (organe.includes('National')) return '#0077B5'
  if (organe.includes('Provincial')) return '#0ea5e9'
  if (organe.includes('Local')) return '#0d9488'
  return '#94a3b8'
}

function organeShort(organe) {
  if (!organe) return '-'
  if (organe.includes('National')) return 'SEN'
  if (organe.includes('Provincial')) return 'SEP'
  if (organe.includes('Local')) return 'SEL'
  return '-'
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function filterByOrgane(organe) {
  selectedOrgane.value = organe
  fetchMonthly()
}

function clearOrgane() {
  selectedOrgane.value = ''
  fetchMonthly()
}

async function fetchMonthly() {
  loading.value = true
  try {
    const params = { month: selectedMonth.value }
    if (selectedOrgane.value) params.organe = selectedOrgane.value

    const { data } = await pointagesApi.monthly(params)
    agentStats.value = data.agent_stats || []
    globalStats.value = data.global_stats || null
    statsByOrgane.value = data.stats_by_organe || null
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
    const params = { month: selectedMonth.value }
    if (selectedOrgane.value) params.organe = selectedOrgane.value
    const response = await pointagesApi.exportMonthly(params)

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
/* ═══ Organe overview ═══ */
.pm-organe-overview { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin-bottom: 1rem; }
.pm-organe-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 1rem;
  cursor: pointer; transition: all .25s;
}
.pm-organe-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.pm-organe-card.active { border-color: #0077B5; box-shadow: 0 0 0 2px rgba(0,119,181,.15); }
.pm-organe-top { display: flex; align-items: center; gap: .6rem; margin-bottom: .75rem; }
.pm-organe-badge {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center;
  font-size: .65rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.pm-organe-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.pm-organe-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .4rem; margin-bottom: .6rem; }
.pm-organe-stat { text-align: center; }
.pm-organe-val { font-size: 1.1rem; font-weight: 800; color: #1e293b; line-height: 1; }
.pm-organe-lbl { font-size: .58rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .1rem; }
.pm-organe-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.pm-organe-bar-fill { height: 100%; border-radius: 4px; transition: width .8s ease; min-width: 2px; }

/* ═══ KPI ═══ */
.pm-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-bottom: 1rem; }
.pm-kpi {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 1rem;
  display: flex; align-items: center; gap: .8rem; transition: all .25s;
}
.pm-kpi:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.06); }
.pm-kpi-icon {
  width: 44px; height: 44px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.pm-kpi-body {}
.pm-kpi-val { font-size: 1.5rem; font-weight: 800; color: #1e293b; line-height: 1; }
.pm-kpi-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-top: .15rem; }

/* ═══ Active filter ═══ */
.pm-active-filter { margin-bottom: .75rem; }
.pm-filter-chip {
  display: inline-flex; align-items: center; gap: .3rem;
  background: #e0f2fe; color: #0077B5; font-size: .78rem; font-weight: 600;
  padding: .3rem .7rem; border-radius: 8px;
}
.pm-filter-clear {
  background: none; border: none; color: #0077B5; cursor: pointer;
  padding: 0 0 0 .3rem; font-size: .7rem; opacity: .7; transition: opacity .2s;
}
.pm-filter-clear:hover { opacity: 1; }

/* ═══ Table card ═══ */
.pm-table-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem 1.3rem; margin-bottom: 1rem;
}
.pm-table-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 1rem;
}
.pm-table-header h5 { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; }
.pm-table-count { font-size: .72rem; color: #94a3b8; font-weight: 600; }
.pm-matricule { font-family: monospace; font-size: .78rem; color: #64748b; }
.pm-organe-chip {
  display: inline-block; font-size: .65rem; font-weight: 700; padding: .15rem .45rem;
  border-radius: 6px; letter-spacing: .3px;
}
.pm-rate-badge {
  display: inline-block; font-size: .78rem; font-weight: 700; padding: .15rem .5rem;
  border-radius: 6px;
}

/* ═══ Info grid ═══ */
.pm-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.pm-info-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 1.1rem;
}
.pm-info-title { font-size: .88rem; font-weight: 700; color: #1e293b; margin-bottom: .75rem; display: flex; align-items: center; }
.pm-info-row {
  display: flex; justify-content: space-between; align-items: center;
  font-size: .82rem; color: #475569; padding: .3rem 0;
  border-bottom: 1px solid #f8fafc;
}
.pm-info-row:last-child { border-bottom: none; }

/* ═══ Responsive ═══ */
@media (max-width: 991.98px) {
  .pm-organe-overview { grid-template-columns: 1fr; }
  .pm-kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .pm-info-grid { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
  .rh-table th:nth-child(2), .rh-table td:nth-child(2),
  .rh-table th:nth-child(3), .rh-table td:nth-child(3),
  .rh-table th:nth-child(4), .rh-table td:nth-child(4),
  .rh-table th:nth-child(7), .rh-table td:nth-child(7) { display: none; }
  .rh-table th, .rh-table td { padding: .4rem .3rem; font-size: .76rem; }
  .rh-table th { font-size: .65rem; }
  .pm-kpi-grid { grid-template-columns: repeat(2, 1fr); gap: .5rem; }
  .pm-kpi { padding: .7rem; }
  .pm-kpi-val { font-size: 1.2rem; }
  .pm-kpi-icon { width: 38px; height: 38px; font-size: .85rem; }
  .rh-filters .row.g-3 > [class*="col-md"] { flex: 0 0 100%; max-width: 100%; }
  .d-flex.gap-2.mb-3 .btn { font-size: .78rem; padding: .3rem .55rem; }
  .rh-table tfoot td { font-size: .7rem; }
  .pm-organe-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 575.98px) {
  .rh-table th:nth-child(6), .rh-table td:nth-child(6) { display: none; }
  .rh-table th, .rh-table td { padding: .35rem .25rem; font-size: .72rem; }
  .rh-table th { font-size: .62rem; }
  .pm-kpi-val { font-size: 1.05rem; }
  .pm-kpi-lbl { font-size: .6rem; }
}
</style>
