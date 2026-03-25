<template>
  <div class="rh-modern">
    <div class="rh-list-shell">

      <!-- ═══ Hero banner ═══ -->
      <section class="rh-hero pm-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-7">
            <h1 class="rh-title"><i class="fas fa-chart-bar me-2"></i>Rapport mensuel</h1>
            <p class="rh-sub">Synthese d'assiduite, de presence et d'heures travaillees — <strong>{{ monthLabel }}</strong></p>
          </div>
          <div class="col-lg-5">
            <div class="hero-tools">
              <router-link :to="{ name: 'rh.pointages.index' }" class="btn-rh alt">
                <i class="fas fa-list me-1"></i> Liste
              </router-link>
              <router-link :to="{ name: 'rh.pointages.daily' }" class="btn-rh alt">
                <i class="fas fa-calendar-alt me-1"></i> Par jour
              </router-link>
              <button class="btn-rh main" @click="exportExcel" :disabled="exporting">
                <span v-if="exporting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-file-excel me-1"></i> Export
              </button>
            </div>
          </div>
        </div>

        <!-- Inline month / organe picker inside hero -->
        <div class="pm-hero-filters">
          <div class="pm-month-picker">
            <i class="fas fa-calendar-week"></i>
            <input type="month" v-model="selectedMonth" @change="fetchMonthly" class="pm-month-input">
          </div>
          <div class="pm-organe-pills">
            <button class="pm-pill" :class="{ active: !selectedOrgane }" @click="filterByOrgane('')">Tous</button>
            <button v-for="o in organeOptions" :key="o.code" class="pm-pill"
              :class="{ active: selectedOrgane === o.value }" @click="filterByOrgane(o.value)">
              <span class="pm-pill-dot" :style="{ background: o.color }"></span>
              {{ o.code }}
            </button>
          </div>
        </div>

        <!-- Sub-filter dropdown (structure or province) -->
        <transition name="pm-subfilter-slide">
          <div v-if="selectedOrgane && subFilterType" class="pm-subfilter-bar">
            <div class="pm-subfilter-label">
              <i class="fas" :class="subFilterType === 'structure' ? 'fa-sitemap' : 'fa-map-marked-alt'"></i>
              {{ subFilterLabel }}
            </div>
            <select v-model="selectedSubFilter" @change="onSubFilterChange" class="pm-subfilter-select">
              <option value="">Toutes les {{ subFilterType === 'structure' ? 'structures' : 'provinces' }}</option>
              <!-- SEN: grouped by Direction / Departments / Attaches -->
              <template v-if="subFilterType === 'structure'">
                <optgroup v-for="grp in senGroups" :key="grp.label" :label="grp.label">
                  <option v-for="s in grp.items" :key="s.id" :value="s.id">{{ s.nom }}</option>
                </optgroup>
              </template>
              <!-- SEP/SEL: flat list of provinces -->
              <template v-else>
                <option v-for="p in allProvinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
              </template>
            </select>
            <button v-if="selectedSubFilter" @click="selectedSubFilter = ''; onSubFilterChange()" class="pm-subfilter-clear">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </transition>
      </section>

      <LoadingSpinner v-if="loading" message="Chargement du rapport mensuel..." />

      <template v-else>

        <!-- ═══ Organe overview — 3 premium glass cards ═══ -->
        <div class="pm-organes" v-if="statsByOrgane && !selectedOrgane">
          <div class="pm-org-card" v-for="o in organeOverview" :key="o.code"
            @click="filterByOrgane(o.value)"
            :style="{ '--org-color': o.color }">
            <div class="pm-org-head">
              <div class="pm-org-icon" :style="{ background: o.color }">
                <i class="fas" :class="o.icon"></i>
              </div>
              <div>
                <div class="pm-org-label">{{ o.label }}</div>
                <div class="pm-org-code">{{ o.code }}</div>
              </div>
            </div>

            <div class="pm-org-metrics">
              <div class="pm-org-metric">
                <span class="pm-org-metric-val">{{ o.total_agents }}</span>
                <span class="pm-org-metric-lbl">Agents</span>
              </div>
              <div class="pm-org-divider"></div>
              <div class="pm-org-metric">
                <span class="pm-org-metric-val pm-green">{{ o.total_present }}</span>
                <span class="pm-org-metric-lbl">Presents</span>
              </div>
              <div class="pm-org-divider"></div>
              <div class="pm-org-metric">
                <span class="pm-org-metric-val pm-red">{{ o.total_absent }}</span>
                <span class="pm-org-metric-lbl">Absents</span>
              </div>
            </div>

            <!-- Attendance gauge -->
            <div class="pm-org-gauge">
              <div class="pm-org-gauge-track">
                <div class="pm-org-gauge-fill" :style="{ width: o.average_attendance + '%', background: `linear-gradient(135deg, ${o.color}, ${o.color}cc)` }"></div>
              </div>
              <span class="pm-org-gauge-pct" :style="{ color: rateColor(o.average_attendance) }">
                {{ o.average_attendance }}%
              </span>
            </div>
          </div>
        </div>

        <!-- ═══ KPI strip — 4 metric cards ═══ -->
        <div class="pm-kpis" v-if="globalStats">
          <div class="pm-kpi-card" v-for="kpi in kpiCards" :key="kpi.label">
            <div class="pm-kpi-icon-wrap" :style="{ background: kpi.bg, color: kpi.fg }">
              <i class="fas" :class="kpi.icon"></i>
            </div>
            <div class="pm-kpi-content">
              <div class="pm-kpi-value">{{ kpi.value }}<span v-if="kpi.suffix" class="pm-kpi-suffix">{{ kpi.suffix }}</span></div>
              <div class="pm-kpi-label">{{ kpi.label }}</div>
            </div>
          </div>
        </div>

        <!-- ═══ Active organe filter indicator ═══ -->
        <div v-if="selectedOrgane" class="pm-active-filter-bar">
          <div class="pm-filter-indicator">
            <span class="pm-filter-dot" :style="{ background: organeColor(selectedOrgane) }"></span>
            Filtrage : <strong>{{ organeOptions.find(o => o.value === selectedOrgane)?.label || selectedOrgane }}</strong>
            <template v-if="activeSubFilterName">
              <i class="fas fa-chevron-right mx-1" style="font-size:.6rem;opacity:.5"></i>
              <strong>{{ activeSubFilterName }}</strong>
            </template>
          </div>
          <button @click="clearOrgane" class="pm-filter-clear-btn">
            <i class="fas fa-times me-1"></i> Effacer le filtre
          </button>
        </div>

        <!-- ═══ Data table panel ═══ -->
        <div class="pm-panel" v-if="agentStats.length > 0">
          <div class="pm-panel-head">
            <div class="pm-panel-title">
              <i class="fas fa-table me-2"></i>Detail par agent
            </div>
            <div class="pm-panel-meta">
              <span class="pm-badge-count">{{ agentStats.length }} agent(s)</span>
              <span class="pm-badge-period">{{ monthLabel }}</span>
            </div>
          </div>

          <div class="rh-table-wrap">
            <table class="rh-table pm-tbl">
              <thead>
                <tr>
                  <th class="pm-th-agent">Agent</th>
                  <th class="pm-th-mat">Matricule</th>
                  <th class="pm-th-org">Organe</th>
                  <th class="pm-th-num">Jours</th>
                  <th class="pm-th-num">Presents</th>
                  <th class="pm-th-num">Absents</th>
                  <th class="pm-th-num">Heures</th>
                  <th class="pm-th-rate">Taux</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(stat, idx) in agentStats" :key="stat.agent?.id" :class="{ 'pm-row-alt': idx % 2 === 0 }">
                  <td class="pm-td-agent">
                    <div class="pm-agent-cell">
                      <div class="pm-avatar" :style="{ background: avatarColor(stat.agent?.prenom) }">
                        {{ avatarInitials(stat.agent?.prenom, stat.agent?.nom) }}
                      </div>
                      <div>
                        <div class="pm-agent-name">{{ stat.agent?.prenom }} {{ stat.agent?.nom }}</div>
                        <div class="pm-agent-dept">{{ stat.agent?.departement?.nom || '' }}</div>
                      </div>
                    </div>
                  </td>
                  <td><code class="pm-matricule">{{ stat.agent?.id_agent }}</code></td>
                  <td>
                    <span class="pm-organe-tag" :style="{ '--tag-color': organeColor(stat.agent?.organe) }">
                      {{ organeShort(stat.agent?.organe) }}
                    </span>
                  </td>
                  <td class="pm-td-num">{{ stat.working_days }}</td>
                  <td class="pm-td-num"><span class="pm-num-present">{{ stat.present }}</span></td>
                  <td class="pm-td-num"><span class="pm-num-absent">{{ stat.absent }}</span></td>
                  <td class="pm-td-num">{{ stat.total_hours }}<small>h</small></td>
                  <td class="pm-td-rate">
                    <div class="pm-rate-wrap">
                      <div class="pm-rate-bar">
                        <div class="pm-rate-bar-fill" :style="{ width: stat.attendance_rate + '%', background: rateColor(stat.attendance_rate) }"></div>
                      </div>
                      <span class="pm-rate-pct" :style="{ color: rateColor(stat.attendance_rate) }">{{ stat.attendance_rate }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" class="pm-tfoot-label">
                    <i class="fas fa-sigma me-1"></i> Totaux
                  </td>
                  <td class="pm-td-num"><strong>—</strong></td>
                  <td class="pm-td-num"><strong>{{ globalStats.total_present }}</strong></td>
                  <td class="pm-td-num"><strong>{{ globalStats.total_absent }}</strong></td>
                  <td class="pm-td-num"><strong>{{ totalHours }}h</strong></td>
                  <td class="pm-td-rate"><strong :style="{ color: rateColor(globalStats.average_attendance) }">{{ globalStats.average_attendance }}%</strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- ═══ Empty state ═══ -->
        <div v-else-if="!loading" class="pm-empty">
          <div class="pm-empty-icon"><i class="fas fa-chart-bar"></i></div>
          <h5>Aucune donnee pour ce mois</h5>
          <p>Aucun pointage enregistre{{ selectedOrgane ? ' pour cet organe' : '' }} sur la periode selectionnee.</p>
          <button class="btn-rh main" @click="fetchMonthly"><i class="fas fa-sync me-1"></i> Reactualiser</button>
        </div>

        <!-- ═══ Period + averages summary ═══ -->
        <div class="pm-summary" v-if="globalStats && agentStats.length > 0">
          <div class="pm-summary-card">
            <div class="pm-summary-icon" style="color:#0077B5;">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="pm-summary-body">
              <div class="pm-summary-title">Periode</div>
              <div class="pm-summary-rows">
                <div class="pm-summary-row"><span>Debut</span><strong>{{ formatDate(dateDebut) }}</strong></div>
                <div class="pm-summary-row"><span>Fin</span><strong>{{ formatDate(dateFin) }}</strong></div>
                <div class="pm-summary-row"><span>Duree</span><strong>{{ periodDays }} jours</strong></div>
              </div>
            </div>
          </div>
          <div class="pm-summary-card">
            <div class="pm-summary-icon" style="color:#7c3aed;">
              <i class="fas fa-calculator"></i>
            </div>
            <div class="pm-summary-body">
              <div class="pm-summary-title">Moyennes</div>
              <div class="pm-summary-rows">
                <div class="pm-summary-row"><span>Heures / agent</span><strong>{{ globalStats.average_hours }}h</strong></div>
                <div class="pm-summary-row"><span>Taux assiduite</span><strong :style="{ color: rateColor(globalStats.average_attendance) }">{{ globalStats.average_attendance }}%</strong></div>
                <div class="pm-summary-row"><span>Agents suivis</span><strong>{{ globalStats.total_agents }}</strong></div>
              </div>
            </div>
          </div>
          <div class="pm-summary-card">
            <div class="pm-summary-icon" style="color:#059669;">
              <i class="fas fa-clock"></i>
            </div>
            <div class="pm-summary-body">
              <div class="pm-summary-title">Volume horaire</div>
              <div class="pm-summary-rows">
                <div class="pm-summary-row"><span>Total heures</span><strong>{{ totalHours }}h</strong></div>
                <div class="pm-summary-row"><span>Presences tot.</span><strong>{{ globalStats.total_present }}</strong></div>
                <div class="pm-summary-row"><span>Absences tot.</span><strong class="pm-red">{{ globalStats.total_absent }}</strong></div>
              </div>
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
const selectedOrgane = ref('')

const agentStats = ref([])
const globalStats = ref(null)
const statsByOrgane = ref(null)
const dateDebut = ref('')
const dateFin = ref('')

// Sub-filter state
const selectedDepartment = ref('')
const selectedProvince = ref('')
const senStructures = ref([])
const allProvinces = ref([])

const organeOptions = [
  { value: 'Secretariat Executif National', label: 'National', code: 'SEN', color: '#0077B5', icon: 'fa-landmark' },
  { value: 'Secretariat Executif Provincial', label: 'Provincial', code: 'SEP', color: '#0ea5e9', icon: 'fa-map-marked-alt' },
  { value: 'Secretariat Executif Local', label: 'Local', code: 'SEL', color: '#0d9488', icon: 'fa-map-pin' },
]

/* ─── Computed ─── */

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

const kpiCards = computed(() => {
  if (!globalStats.value) return []
  return [
    { icon: 'fa-users', value: globalStats.value.total_agents, label: selectedOrgane.value ? 'Agents (organe)' : 'Total agents', bg: '#e0f2fe', fg: '#0077B5' },
    { icon: 'fa-user-check', value: globalStats.value.total_present, label: 'Presences cumulees', bg: '#d1fae5', fg: '#059669' },
    { icon: 'fa-user-times', value: globalStats.value.total_absent, label: 'Absences cumulees', bg: '#fee2e2', fg: '#dc2626' },
    { icon: 'fa-chart-line', value: globalStats.value.average_attendance, suffix: '%', label: 'Taux moyen', bg: rateColor(globalStats.value.average_attendance) + '18', fg: rateColor(globalStats.value.average_attendance) },
  ]
})

/* ─── Sub-filter logic ─── */

const subFilterType = computed(() => {
  if (!selectedOrgane.value) return null
  if (selectedOrgane.value.includes('National')) return 'structure'
  return 'province'
})

const subFilterLabel = computed(() => {
  if (subFilterType.value === 'structure') return 'Structure'
  if (subFilterType.value === 'province') return 'Province'
  return ''
})

/** Group SEN structures by their group field for optgroups */
const senGroups = computed(() => {
  const groups = {}
  for (const s of senStructures.value) {
    if (!groups[s.group]) groups[s.group] = []
    groups[s.group].push(s)
  }
  // Fixed order: Direction, Departements, Attaches
  const ordered = []
  if (groups['Direction']) ordered.push({ label: 'Direction', items: groups['Direction'] })
  if (groups['Departements']) ordered.push({ label: 'Departements', items: groups['Departements'] })
  if (groups['Attaches']) ordered.push({ label: 'Attaches / Services rattaches', items: groups['Attaches'] })
  return ordered
})

const selectedSubFilter = computed({
  get() {
    if (subFilterType.value === 'structure') return selectedDepartment.value
    if (subFilterType.value === 'province') return selectedProvince.value
    return ''
  },
  set(val) {
    if (subFilterType.value === 'structure') selectedDepartment.value = val
    else if (subFilterType.value === 'province') selectedProvince.value = val
  }
})

const activeSubFilterName = computed(() => {
  if (!selectedSubFilter.value) return ''
  if (subFilterType.value === 'structure') {
    const s = senStructures.value.find(o => o.id == selectedSubFilter.value)
    return s?.nom || ''
  }
  const p = allProvinces.value.find(o => o.id == selectedSubFilter.value)
  return p?.nom || ''
})

/* ─── Helpers ─── */

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
  if (!organe) return '—'
  if (organe.includes('National')) return 'SEN'
  if (organe.includes('Provincial')) return 'SEP'
  if (organe.includes('Local')) return 'SEL'
  return '—'
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function avatarInitials(prenom, nom) {
  return ((prenom?.[0] || '') + (nom?.[0] || '')).toUpperCase()
}

function avatarColor(name) {
  const colors = ['#0077B5', '#0ea5e9', '#0d9488', '#059669', '#7c3aed', '#d97706', '#dc2626', '#6366f1']
  let hash = 0
  for (const ch of (name || '')) hash = ch.charCodeAt(0) + ((hash << 5) - hash)
  return colors[Math.abs(hash) % colors.length]
}

/* ─── Actions ─── */

function filterByOrgane(organe) {
  selectedOrgane.value = organe
  selectedDepartment.value = ''
  selectedProvince.value = ''
  fetchMonthly()
}

function clearOrgane() {
  selectedOrgane.value = ''
  selectedDepartment.value = ''
  selectedProvince.value = ''
  fetchMonthly()
}

function onSubFilterChange() {
  fetchMonthly()
}

async function fetchMonthly() {
  loading.value = true
  try {
    const params = { month: selectedMonth.value }
    if (selectedOrgane.value) params.organe = selectedOrgane.value
    if (selectedDepartment.value) params.department_id = selectedDepartment.value
    if (selectedProvince.value) params.province_id = selectedProvince.value

    const { data } = await pointagesApi.monthly(params)
    agentStats.value = data.agent_stats || []
    globalStats.value = data.global_stats || null
    statsByOrgane.value = data.stats_by_organe || null
    dateDebut.value = data.date_debut || ''
    dateFin.value = data.date_fin || ''
    senStructures.value = data.sen_structures || []
    allProvinces.value = data.provinces || []
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
    if (selectedDepartment.value) params.department_id = selectedDepartment.value
    if (selectedProvince.value) params.province_id = selectedProvince.value
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
/* ═══════════════════════════════════════════════
   MONTHLY REPORT — PREMIUM LAYOUT
   ═══════════════════════════════════════════════ */

/* ── Hero extension ── */
.pm-hero { position: relative; overflow: visible; }

.pm-hero-filters {
  display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
  margin-top: 1.2rem; padding-top: 1rem;
  border-top: 1px solid rgba(255,255,255,.12);
}

.pm-month-picker {
  display: flex; align-items: center; gap: .5rem;
  background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.18); border-radius: 12px;
  padding: .4rem .7rem; color: #fff;
}
.pm-month-picker i { font-size: .85rem; opacity: .7; }
.pm-month-input {
  background: transparent; border: none; outline: none;
  color: #fff; font-size: .88rem; font-weight: 600;
  width: auto;
}
.pm-month-input::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }

.pm-organe-pills { display: flex; gap: .4rem; flex-wrap: wrap; }
.pm-pill {
  display: inline-flex; align-items: center; gap: .35rem;
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
  color: rgba(255,255,255,.75); border-radius: 999px; padding: .32rem .85rem;
  font-size: .78rem; font-weight: 600; cursor: pointer;
  transition: all .2s ease;
}
.pm-pill:hover { background: rgba(255,255,255,.18); color: #fff; }
.pm-pill.active {
  background: #fff; color: #0b2948;
  border-color: #fff; font-weight: 700;
}
.pm-pill-dot {
  width: 8px; height: 8px; border-radius: 50%;
  display: inline-block; flex-shrink: 0;
}
.pm-pill.active .pm-pill-dot { box-shadow: 0 0 0 2px rgba(0,0,0,.15); }

/* ── Sub-filter dropdown bar ── */
.pm-subfilter-bar {
  display: flex; align-items: center; gap: .6rem;
  margin-top: .8rem; padding: .5rem .8rem;
  background: rgba(255,255,255,.08); backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.15); border-radius: 12px;
}
.pm-subfilter-label {
  color: rgba(255,255,255,.75); font-size: .78rem; font-weight: 600;
  white-space: nowrap; display: flex; align-items: center; gap: .4rem;
}
.pm-subfilter-select {
  flex: 1; max-width: 280px;
  background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
  color: #fff; border-radius: 8px; padding: .35rem .65rem;
  font-size: .82rem; font-weight: 600; outline: none; cursor: pointer;
  transition: all .2s ease; appearance: auto;
}
.pm-subfilter-select:hover { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.3); }
.pm-subfilter-select:focus { background: rgba(255,255,255,.2); border-color: rgba(255,255,255,.4); box-shadow: 0 0 0 2px rgba(255,255,255,.1); }
.pm-subfilter-select option { background: #1e293b; color: #f1f5f9; }
.pm-subfilter-select optgroup { font-weight: 700; color: #94a3b8; font-style: normal; padding-top: .3rem; }
.pm-subfilter-select optgroup option { font-weight: 600; color: #f1f5f9; padding-left: .5rem; }
.pm-subfilter-clear {
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
  color: rgba(255,255,255,.7); border-radius: 6px;
  width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
  font-size: .7rem; cursor: pointer; transition: all .2s;
}
.pm-subfilter-clear:hover { background: rgba(220,38,38,.3); color: #fff; border-color: rgba(220,38,38,.4); }

/* Sub-filter slide transition */
.pm-subfilter-slide-enter-active,
.pm-subfilter-slide-leave-active {
  transition: all .3s cubic-bezier(.4,0,.2,1); overflow: hidden;
}
.pm-subfilter-slide-enter-from,
.pm-subfilter-slide-leave-to {
  opacity: 0; max-height: 0; margin-top: 0; padding-top: 0; padding-bottom: 0;
}
.pm-subfilter-slide-enter-to,
.pm-subfilter-slide-leave-from {
  opacity: 1; max-height: 60px;
}

/* ── Organe overview cards ── */
.pm-organes {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: .85rem; margin-bottom: 1.2rem;
}
.pm-org-card {
  background: #fff; border-radius: 16px;
  border: 1px solid #e5e7eb; padding: 1.2rem;
  cursor: pointer; transition: all .3s cubic-bezier(.4,0,.2,1);
  position: relative; overflow: hidden;
}
.pm-org-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px; background: var(--org-color); opacity: 0;
  transition: opacity .3s ease;
}
.pm-org-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0,0,0,.1);
}
.pm-org-card:hover::before { opacity: 1; }

.pm-org-head {
  display: flex; align-items: center; gap: .75rem;
  margin-bottom: 1.1rem;
}
.pm-org-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: .95rem; flex-shrink: 0;
}
.pm-org-label { font-size: .88rem; font-weight: 700; color: #1e293b; }
.pm-org-code { font-size: .62rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .8px; }

.pm-org-metrics {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1rem;
}
.pm-org-metric { flex: 1; text-align: center; }
.pm-org-metric-val { display: block; font-size: 1.35rem; font-weight: 800; color: #1e293b; line-height: 1; }
.pm-org-metric-lbl { display: block; font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .4px; margin-top: .25rem; }
.pm-green { color: #059669 !important; }
.pm-red { color: #dc2626 !important; }
.pm-org-divider { width: 1px; height: 28px; background: #e5e7eb; flex-shrink: 0; }

.pm-org-gauge { display: flex; align-items: center; gap: .6rem; }
.pm-org-gauge-track {
  flex: 1; height: 6px; background: #f1f5f9;
  border-radius: 6px; overflow: hidden;
}
.pm-org-gauge-fill {
  height: 100%; border-radius: 6px;
  transition: width 1s cubic-bezier(.4,0,.2,1);
  min-width: 3px;
}
.pm-org-gauge-pct { font-size: .82rem; font-weight: 800; min-width: 38px; text-align: right; }

/* ── KPI strip ── */
.pm-kpis {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: .85rem; margin-bottom: 1.2rem;
}
.pm-kpi-card {
  background: #fff; border-radius: 16px;
  border: 1px solid #e5e7eb; padding: 1.1rem;
  display: flex; align-items: center; gap: .85rem;
  transition: all .3s cubic-bezier(.4,0,.2,1);
}
.pm-kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 28px rgba(0,0,0,.07);
}
.pm-kpi-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.05rem; flex-shrink: 0;
}
.pm-kpi-value {
  font-size: 1.65rem; font-weight: 800; color: #1e293b;
  line-height: 1; letter-spacing: -.03em;
}
.pm-kpi-suffix { font-size: .85rem; font-weight: 700; margin-left: 1px; }
.pm-kpi-label {
  font-size: .68rem; color: #94a3b8; font-weight: 600;
  text-transform: uppercase; letter-spacing: .4px; margin-top: .2rem;
}

/* ── Active filter bar ── */
.pm-active-filter-bar {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
  border: 1px solid #bae6fd; border-radius: 12px;
  padding: .65rem 1rem; margin-bottom: 1rem;
}
.pm-filter-indicator {
  font-size: .82rem; color: #0369a1;
  display: flex; align-items: center; gap: .5rem;
}
.pm-filter-dot {
  width: 10px; height: 10px; border-radius: 50%;
  flex-shrink: 0;
}
.pm-filter-clear-btn {
  background: #fff; border: 1px solid #bae6fd; color: #0077B5;
  border-radius: 8px; font-size: .75rem; font-weight: 600;
  padding: .3rem .7rem; cursor: pointer;
  transition: all .2s;
}
.pm-filter-clear-btn:hover { background: #0077B5; color: #fff; border-color: #0077B5; }

/* ── Data table panel ── */
.pm-panel {
  background: #fff; border-radius: 18px;
  border: 1px solid #e5e7eb; overflow: hidden;
  margin-bottom: 1.2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.pm-panel-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 1.3rem; border-bottom: 1px solid #f1f5f9;
  background: #fafbfc;
}
.pm-panel-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
.pm-panel-meta { display: flex; gap: .5rem; align-items: center; }
.pm-badge-count {
  font-size: .7rem; font-weight: 700; color: #0077B5;
  background: #e0f2fe; padding: .2rem .6rem; border-radius: 999px;
}
.pm-badge-period {
  font-size: .7rem; font-weight: 600; color: #64748b;
  background: #f1f5f9; padding: .2rem .6rem; border-radius: 999px;
  text-transform: capitalize;
}

/* Table refinements */
.pm-tbl { font-size: .84rem; }
.pm-tbl thead th {
  font-size: .68rem; text-transform: uppercase; letter-spacing: .5px;
  color: #64748b; font-weight: 700; padding: .7rem .6rem;
  background: #f8fafc; border-bottom: 2px solid #e5e7eb;
  white-space: nowrap;
}
.pm-tbl tbody td { padding: .65rem .6rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.pm-row-alt { background: #fafbfc; }
.pm-tbl tbody tr:hover { background: #f0f9ff; }

.pm-td-agent { min-width: 180px; }
.pm-agent-cell { display: flex; align-items: center; gap: .6rem; }
.pm-avatar {
  width: 34px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: .65rem; font-weight: 800;
  flex-shrink: 0;
}
.pm-agent-name { font-size: .84rem; font-weight: 600; color: #1e293b; line-height: 1.2; }
.pm-agent-dept { font-size: .65rem; color: #94a3b8; font-weight: 500; }

.pm-matricule {
  font-size: .76rem; color: #475569; background: #f1f5f9;
  padding: .15rem .4rem; border-radius: 6px;
}

.pm-organe-tag {
  display: inline-block; font-size: .65rem; font-weight: 700;
  padding: .18rem .5rem; border-radius: 6px;
  background: color-mix(in srgb, var(--tag-color) 12%, transparent);
  color: var(--tag-color);
  letter-spacing: .3px;
}

.pm-td-num { text-align: center; font-weight: 600; color: #475569; white-space: nowrap; }
.pm-num-present { color: #059669; font-weight: 700; }
.pm-num-absent { color: #dc2626; font-weight: 700; }

.pm-td-rate { min-width: 120px; }
.pm-rate-wrap { display: flex; align-items: center; gap: .5rem; }
.pm-rate-bar { flex: 1; height: 5px; background: #f1f5f9; border-radius: 5px; overflow: hidden; }
.pm-rate-bar-fill { height: 100%; border-radius: 5px; transition: width .8s ease; min-width: 2px; }
.pm-rate-pct { font-size: .78rem; font-weight: 800; min-width: 34px; text-align: right; }

/* tfoot */
.pm-tbl tfoot td {
  padding: .75rem .6rem; background: #f8fbff;
  border-top: 2px solid #e5e7eb; font-size: .82rem;
}
.pm-tfoot-label { font-weight: 700; color: #1e293b; white-space: nowrap; }

/* ── Empty state ── */
.pm-empty {
  text-align: center; padding: 3.5rem 2rem;
  background: #fff; border-radius: 18px;
  border: 1px solid #e5e7eb; margin-bottom: 1.2rem;
}
.pm-empty-icon {
  width: 72px; height: 72px; border-radius: 20px;
  background: #f0f9ff; color: #0077B5;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.8rem; margin-bottom: 1rem;
}
.pm-empty h5 { font-weight: 700; color: #1e293b; margin-bottom: .4rem; }
.pm-empty p { font-size: .88rem; color: #94a3b8; margin-bottom: 1.2rem; }

/* ── Summary cards ── */
.pm-summary {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: .85rem;
}
.pm-summary-card {
  background: #fff; border-radius: 16px;
  border: 1px solid #e5e7eb; padding: 1.2rem;
  display: flex; gap: .85rem;
  transition: all .25s;
}
.pm-summary-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.06); }
.pm-summary-icon { font-size: 1.2rem; margin-top: .1rem; }
.pm-summary-body { flex: 1; }
.pm-summary-title { font-size: .88rem; font-weight: 700; color: #1e293b; margin-bottom: .6rem; }
.pm-summary-row {
  display: flex; justify-content: space-between; align-items: center;
  font-size: .82rem; color: #475569; padding: .3rem 0;
  border-bottom: 1px solid #f8fafc;
}
.pm-summary-row:last-child { border-bottom: none; }
.pm-summary-row strong { color: #1e293b; }

/* ═══ Responsive ═══ */
@media (max-width: 1100px) {
  .pm-organes { grid-template-columns: 1fr 1fr; }
  .pm-kpis { grid-template-columns: repeat(2, 1fr); }
  .pm-summary { grid-template-columns: 1fr; }
}
@media (max-width: 991.98px) {
  .pm-organes { grid-template-columns: 1fr; }
  .pm-hero-filters { flex-direction: column; align-items: flex-start; gap: .75rem; }
}
@media (max-width: 767.98px) {
  .pm-kpis { grid-template-columns: repeat(2, 1fr); gap: .5rem; }
  .pm-kpi-card { padding: .8rem; }
  .pm-kpi-value { font-size: 1.3rem; }
  .pm-kpi-icon-wrap { width: 40px; height: 40px; font-size: .9rem; }
  .pm-organe-pills { gap: .3rem; }
  .pm-pill { font-size: .72rem; padding: .28rem .65rem; }
  .pm-subfilter-bar { flex-wrap: wrap; }
  .pm-subfilter-select { max-width: 100%; flex: 1 1 100%; }

  /* Table compact */
  .pm-tbl thead th { font-size: .6rem; padding: .5rem .3rem; }
  .pm-tbl tbody td { padding: .45rem .3rem; font-size: .76rem; }
  .pm-th-mat, .pm-tbl td:nth-child(2),
  .pm-th-org, .pm-tbl td:nth-child(3),
  .pm-th-num:nth-of-type(1), .pm-tbl td:nth-child(4) { display: none; }
  .pm-avatar { width: 28px; height: 28px; font-size: .55rem; border-radius: 8px; }
  .pm-agent-name { font-size: .76rem; }
  .pm-agent-dept { display: none; }
  .pm-rate-bar { display: none; }
  .pm-active-filter-bar { flex-direction: column; gap: .5rem; text-align: center; }
  .pm-panel-head { flex-direction: column; gap: .4rem; padding: .8rem; }
  .pm-summary { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
  .pm-kpis { grid-template-columns: 1fr 1fr; }
  .pm-kpi-value { font-size: 1.1rem; }
  .pm-kpi-label { font-size: .58rem; }
  .hero-tools { flex-wrap: wrap; gap: .4rem; }
  .hero-tools .btn-rh { font-size: .72rem; padding: .3rem .6rem; }
  .pm-tbl tfoot td { font-size: .7rem; padding: .5rem .3rem; }
}
</style>
