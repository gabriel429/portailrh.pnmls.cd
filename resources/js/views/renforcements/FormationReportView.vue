<template>
  <div class="container py-4">

    <!-- Header -->
    <div class="rp-header">
      <div class="rp-header-left">
        <div class="rp-header-icon"><i class="fas fa-chart-bar"></i></div>
        <div>
          <h2 class="rp-title">Rapports Renforcement des Capacités</h2>
          <p class="rp-sub">Statistiques et rapports des formations organisées par la section RC.</p>
        </div>
      </div>
      <div class="rp-header-tabs">
        <button class="rp-tab" :class="{ active: mode === 'monthly' }" @click="mode = 'monthly'; load()">
          <i class="fas fa-calendar-alt me-1"></i> Mensuel
        </button>
        <button class="rp-tab" :class="{ active: mode === 'annual' }" @click="mode = 'annual'; load()">
          <i class="fas fa-chart-line me-1"></i> Annuel
        </button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="rp-filters">
      <div v-if="mode === 'monthly'" class="rp-filter-row">
        <div class="rp-filter-group">
          <label class="rp-filter-label">Mois</label>
          <select v-model="filters.mois" class="rp-filter-select" @change="load()">
            <option v-for="m in moisOptions" :key="m.val" :value="m.val">{{ m.label }}</option>
          </select>
        </div>
        <div class="rp-filter-group">
          <label class="rp-filter-label">Année</label>
          <select v-model="filters.annee" class="rp-filter-select" @change="load()">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
      </div>
      <div v-else class="rp-filter-row">
        <div class="rp-filter-group">
          <label class="rp-filter-label">Année</label>
          <select v-model="filters.annee" class="rp-filter-select" @change="load()">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-success" role="status"></div>
      <p class="text-muted mt-2">Chargement du rapport...</p>
    </div>

    <template v-else-if="report">

      <!-- Titre période -->
      <div class="rp-period-title">
        <i class="fas fa-calendar-check me-2"></i>
        {{ mode === 'monthly'
          ? `Rapport mensuel — ${moisLabel(filters.mois)} ${filters.annee}`
          : `Rapport annuel — ${filters.annee}` }}
      </div>

      <!-- KPIs -->
      <div class="rp-kpi-grid">
        <div class="rp-kpi">
          <div class="rp-kpi-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-chalkboard-teacher"></i></div>
          <div class="rp-kpi-val">{{ report.stats?.total_formations ?? 0 }}</div>
          <div class="rp-kpi-lbl">Formations</div>
        </div>
        <div class="rp-kpi">
          <div class="rp-kpi-icon" style="background:#e0f2fe;color:#0ea5e9;"><i class="fas fa-calendar-alt"></i></div>
          <div class="rp-kpi-val">{{ report.stats?.planifiees ?? 0 }}</div>
          <div class="rp-kpi-lbl">Planifiées</div>
        </div>
        <div class="rp-kpi">
          <div class="rp-kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-spinner"></i></div>
          <div class="rp-kpi-val">{{ report.stats?.en_cours ?? 0 }}</div>
          <div class="rp-kpi-lbl">En cours</div>
        </div>
        <div class="rp-kpi">
          <div class="rp-kpi-icon" style="background:#d1fae5;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
          <div class="rp-kpi-val">{{ report.stats?.terminees ?? 0 }}</div>
          <div class="rp-kpi-lbl">Terminées</div>
        </div>
        <div class="rp-kpi">
          <div class="rp-kpi-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-users"></i></div>
          <div class="rp-kpi-val">{{ report.stats?.total_beneficiaires ?? 0 }}</div>
          <div class="rp-kpi-lbl">Bénéficiaires</div>
        </div>
        <div class="rp-kpi">
          <div class="rp-kpi-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-certificate"></i></div>
          <div class="rp-kpi-val">{{ report.stats?.certifies ?? 0 }}</div>
          <div class="rp-kpi-lbl">Certifiés</div>
        </div>
        <div class="rp-kpi">
          <div class="rp-kpi-icon" style="background:#fef9c3;color:#a16207;"><i class="fas fa-dollar-sign"></i></div>
          <div class="rp-kpi-val">{{ formatBudgetShort(report.stats?.budget_total) }}</div>
          <div class="rp-kpi-lbl">Budget total</div>
        </div>
        <div class="rp-kpi">
          <div class="rp-kpi-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-percent"></i></div>
          <div class="rp-kpi-val">{{ tauxCertification }}%</div>
          <div class="rp-kpi-lbl">Taux certification</div>
        </div>
      </div>

      <!-- Table formations -->
      <div class="rp-card" v-if="report.formations?.length">
        <div class="rp-card-head">
          <i class="fas fa-table me-2 text-success"></i>
          Détail des formations
          <span class="rp-badge-count">{{ report.formations.length }}</span>
        </div>
        <div class="rp-table-wrap">
          <table class="rp-table">
            <thead>
              <tr>
                <th>Formation</th>
                <th>Statut</th>
                <th>Dates</th>
                <th>Lieu / Formateur</th>
                <th class="text-right">Bénéficiaires</th>
                <th class="text-right">Certifiés</th>
                <th class="text-right">Budget</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in report.formations" :key="f.id">
                <td>
                  <router-link :to="{ name: 'renforcements.show', params: { id: f.id } }" class="rp-table-link">
                    {{ f.titre }}
                  </router-link>
                </td>
                <td><span class="rp-fstat" :class="'rps-' + f.statut">{{ statutLabel(f.statut) }}</span></td>
                <td class="rp-dates">
                  <div>{{ formatDate(f.date_debut) }}</div>
                  <div v-if="f.date_fin" class="rp-date-fin">→ {{ formatDate(f.date_fin) }}</div>
                </td>
                <td>
                  <div v-if="f.lieu" class="rp-meta-line"><i class="fas fa-map-marker-alt me-1 text-muted"></i>{{ f.lieu }}</div>
                  <div v-if="f.formateur" class="rp-meta-line"><i class="fas fa-user-tie me-1 text-muted"></i>{{ f.formateur }}</div>
                </td>
                <td class="text-right font-bold">{{ f.beneficiaires?.length ?? 0 }}</td>
                <td class="text-right">
                  <span class="rp-certif-count">{{ (f.beneficiaires || []).filter(b => b.statut === 'certifie').length }}</span>
                </td>
                <td class="text-right">
                  <span v-if="f.budget" class="rp-budget">{{ formatBudget(f.budget) }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="rp-tfoot">
                <td colspan="4"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ report.stats?.total_beneficiaires ?? 0 }}</strong></td>
                <td class="text-right"><strong>{{ report.stats?.certifies ?? 0 }}</strong></td>
                <td class="text-right"><strong>{{ formatBudget(report.stats?.budget_total) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div v-else class="rp-empty">
        <i class="fas fa-chart-bar"></i>
        <p>Aucune formation pour cette période.</p>
      </div>

    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import client from '@/api/client'

const route = useRoute()
const now = new Date()
const mode    = ref(route.query.mode || 'monthly')
const loading = ref(false)
const report  = ref(null)

const filters = ref({
  mois:  now.getMonth() + 1,
  annee: now.getFullYear(),
})

const yearOptions = Array.from({ length: 6 }, (_, i) => now.getFullYear() - i)

const moisOptions = [
  { val: 1, label: 'Janvier' }, { val: 2, label: 'Février' }, { val: 3, label: 'Mars' },
  { val: 4, label: 'Avril' },   { val: 5, label: 'Mai' },     { val: 6, label: 'Juin' },
  { val: 7, label: 'Juillet' }, { val: 8, label: 'Août' },    { val: 9, label: 'Septembre' },
  { val: 10, label: 'Octobre' },{ val: 11, label: 'Novembre' },{ val: 12, label: 'Décembre' },
]

const tauxCertification = computed(() => {
  const s = report.value?.stats
  if (!s || !s.total_beneficiaires) return 0
  return Math.round((s.certifies / s.total_beneficiaires) * 100)
})

async function load() {
  loading.value = true
  report.value = null
  try {
    const endpoint = mode.value === 'monthly'
      ? '/renforcements/report/monthly'
      : '/renforcements/report/annual'
    const params = { annee: filters.value.annee }
    if (mode.value === 'monthly') params.mois = filters.value.mois
    const { data } = await client.get(endpoint, { params })
    report.value = data
  } finally {
    loading.value = false
  }
}

function moisLabel(m) {
  return moisOptions.find(o => o.val === m)?.label ?? m
}

function formatDate(v) {
  if (!v) return ''
  return new Date(v).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatBudget(v) {
  if (!v) return '—'
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v)
}

function formatBudgetShort(v) {
  if (!v) return '0'
  if (v >= 1000000) return (v / 1000000).toFixed(1) + 'M'
  if (v >= 1000) return (v / 1000).toFixed(0) + 'K'
  return v.toString()
}

function statutLabel(s) {
  return { planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' }[s] ?? s
}

onMounted(load)
</script>

<style scoped>
/* Header */
.rp-header { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; margin-bottom: 1.3rem; flex-wrap: wrap; }
.rp-header-left { display: flex; align-items: center; gap: 1rem; }
.rp-header-icon { width: 48px; height: 48px; border-radius: 14px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.rp-title { font-size: 1.3rem; font-weight: 800; color: #1e293b; margin: 0; }
.rp-sub { font-size: .78rem; color: #94a3b8; margin: 0; }
.rp-header-tabs { display: flex; background: #f1f5f9; border-radius: 12px; padding: .2rem; }
.rp-tab { padding: .5rem 1.1rem; border: none; background: none; border-radius: 10px; font-size: .82rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all .2s; }
.rp-tab.active { background: #fff; color: #059669; box-shadow: 0 2px 8px rgba(0,0,0,.08); }

/* Filtres */
.rp-filters { margin-bottom: 1.2rem; }
.rp-filter-row { display: flex; gap: 1rem; flex-wrap: wrap; }
.rp-filter-group { display: flex; flex-direction: column; gap: .3rem; }
.rp-filter-label { font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; }
.rp-filter-select { padding: .5rem .8rem; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: .85rem; background: #fff; min-width: 130px; }

/* Period title */
.rp-period-title { font-size: 1rem; font-weight: 700; color: #1e293b; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: .7rem 1rem; margin-bottom: 1.2rem; display: flex; align-items: center; }

/* KPIs */
.rp-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-bottom: 1.5rem; }
.rp-kpi { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px; padding: 1.1rem; display: flex; flex-direction: column; align-items: flex-start; }
.rp-kpi-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: .9rem; margin-bottom: .6rem; }
.rp-kpi-val { font-size: 1.6rem; font-weight: 800; line-height: 1; color: #1e293b; }
.rp-kpi-lbl { font-size: .67rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-top: .2rem; }

/* Card */
.rp-card { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px; padding: 1.3rem; }
.rp-card-head { font-size: .9rem; font-weight: 800; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; gap: .3rem; }
.rp-badge-count { margin-left: auto; background: #d1fae5; color: #065f46; font-size: .7rem; font-weight: 700; padding: .15rem .55rem; border-radius: 20px; }

/* Table */
.rp-table-wrap { overflow-x: auto; }
.rp-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.rp-table th { font-size: .68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; padding: .55rem .75rem; border-bottom: 2px solid #f1f5f9; text-align: left; white-space: nowrap; }
.rp-table td { padding: .65rem .75rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.rp-table tbody tr:last-child td { border-bottom: none; }
.rp-table tbody tr:hover td { background: #f9fafb; }
.text-right { text-align: right; }
.font-bold { font-weight: 700; }
.rp-table-link { color: #059669; font-weight: 700; text-decoration: none; }
.rp-table-link:hover { text-decoration: underline; }
.rp-fstat { display: inline-flex; padding: .12rem .5rem; border-radius: 6px; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
.rps-planifiee { background: #e0f2fe; color: #0369a1; }
.rps-en_cours  { background: #fef3c7; color: #92400e; }
.rps-terminee  { background: #d1fae5; color: #065f46; }
.rps-annulee   { background: #f1f5f9; color: #64748b; }
.rp-dates { font-size: .78rem; color: #64748b; }
.rp-date-fin { color: #94a3b8; }
.rp-meta-line { font-size: .75rem; color: #64748b; }
.rp-certif-count { font-weight: 700; color: #059669; }
.rp-budget { font-weight: 700; color: #059669; }
.rp-tfoot td { padding: .75rem; font-size: .85rem; border-top: 2px solid #e5e7eb; background: #f8fafc; }

/* Empty */
.rp-empty { text-align: center; padding: 3rem; color: #94a3b8; font-size: .85rem; }
.rp-empty i { font-size: 2.5rem; color: #d1fae5; display: block; margin-bottom: .8rem; }

@media (max-width: 900px) { .rp-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .rp-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
