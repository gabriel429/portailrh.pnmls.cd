<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-7 col-md-6">
            <h1 class="rh-title"><i class="fas fa-calendar-alt me-2"></i>Planning Congés</h1>
            <p class="rh-sub">
              <template v-if="structure">{{ structure.nom }}</template>
              <template v-else>Congés de votre structure</template>
            </p>
          </div>
          <div class="col-lg-5 col-md-6">
            <div class="hero-tools">
              <div class="year-filter">
                <label class="year-label">Année</label>
                <select v-model="selectedYear" class="year-select" @change="loadPlanning">
                  <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <router-link :to="{ name: 'dashboard' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Chargement du planning...</p>
      </div>

      <template v-else>
        <!-- Structure non identifiée -->
        <div v-if="!structure" class="dash-panel mt-3">
          <div class="text-center py-5 text-muted">
            <i class="fas fa-building fa-3x mb-3 d-block text-warning"></i>
            <h5>Structure non identifiée</h5>
            <p class="mb-0">Votre département ou organe n'a pas pu être déterminé.<br>Veuillez contacter la Section RH.</p>
          </div>
        </div>

        <!-- Pas de planning -->
        <div v-else-if="!planning" class="dash-panel mt-3">
          <div class="text-center py-5 text-muted">
            <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
            <h5>Aucun planning pour {{ selectedYear }}</h5>
            <p class="mb-0">Le planning de congés de <strong>{{ structure.nom }}</strong> n'a pas encore été créé pour cette année.</p>
          </div>
        </div>

        <!-- Planning trouvé -->
        <template v-else>
          <!-- Statistiques -->
          <div class="row g-3 mt-2">
            <div class="col-6 col-md-3">
              <div class="stat-card-mini stat-blue">
                <h3 class="mb-0 fw-bold text-primary">{{ stats.jours_totaux }}</h3>
                <small class="text-muted">Jours totaux</small>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card-mini stat-orange">
                <h3 class="mb-0 fw-bold text-warning">{{ stats.jours_utilises }}</h3>
                <small class="text-muted">Jours utilisés</small>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card-mini stat-green">
                <h3 class="mb-0 fw-bold text-success">{{ stats.jours_restants }}</h3>
                <small class="text-muted">Jours restants</small>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card-mini stat-purple">
                <h3 class="mb-0 fw-bold" :class="tauxColor">{{ stats.taux }}%</h3>
                <div class="progress mt-1" style="height: 6px;">
                  <div
                    class="progress-bar"
                    :class="tauxBarColor"
                    :style="{ width: Math.min(stats.taux, 100) + '%' }"
                  ></div>
                </div>
                <small class="text-muted">Taux d'utilisation</small>
              </div>
            </div>
          </div>

          <!-- Statut validation -->
          <div class="validation-status mt-3">
            <span class="badge" :class="planning.valide ? 'bg-success' : 'bg-warning'">
              <i class="fas me-1" :class="planning.valide ? 'fa-check-circle' : 'fa-clock'"></i>
              {{ planning.valide ? 'Planning validé' : 'Planning en attente de validation' }}
            </span>
          </div>

          <!-- MON CONGÉ PLANIFIÉ -->
          <div v-if="myHolidays.length > 0" class="dash-panel mt-3">
            <header class="panel-head">
              <div>
                <h3 class="panel-title">
                  <i class="fas fa-umbrella-beach me-2 text-primary"></i>Mon congé planifié
                  <span class="badge bg-primary ms-2" style="font-size: 0.7rem;">{{ myHolidays.length }}</span>
                </h3>
              </div>
            </header>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr class="text-muted small">
                    <th>Type</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Jours</th>
                    <th>Statut</th>
                    <th>Intérimaire</th>
                    <th>Observation</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in myHolidays" :key="h.id">
                    <td>
                      <span class="badge" :class="typeCongeClass(h.type_conge)">
                        {{ typeCongeLabel(h.type_conge) }}
                      </span>
                    </td>
                    <td>{{ formatDate(h.date_debut) }}</td>
                    <td>{{ formatDate(h.date_fin) }}</td>
                    <td><strong>{{ h.nombre_jours }}j</strong></td>
                    <td>
                      <span class="badge" :class="statutClass(h.statut_demande)">
                        {{ statutLabel(h.statut_demande) }}
                      </span>
                    </td>
                    <td>
                      <template v-if="h.interim_par">
                        {{ agentName(h.interim_par) }}
                      </template>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td class="small text-muted">{{ h.observation || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- MES INTÉRIMS -->
          <div v-if="myInterims.length > 0" class="dash-panel mt-3">
            <header class="panel-head">
              <div>
                <h3 class="panel-title">
                  <i class="fas fa-user-shield me-2 text-warning"></i>Mes intérims à assurer
                  <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem;">{{ myInterims.length }}</span>
                </h3>
              </div>
            </header>
            <div class="p-3">
              <div class="alert alert-warning py-2 mb-3">
                <i class="fas fa-info-circle me-1"></i>
                Vous êtes désigné(e) comme intérimaire pour les agents ci-dessous pendant leur absence.
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr class="text-muted small">
                    <th>Agent absent</th>
                    <th>Type</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Jours</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in myInterims" :key="h.id">
                    <td>
                      <strong>{{ agentName(h.agent) }}</strong>
                      <div v-if="h.agent?.fonction" class="small text-muted">{{ h.agent.fonction }}</div>
                    </td>
                    <td>
                      <span class="badge" :class="typeCongeClass(h.type_conge)">
                        {{ typeCongeLabel(h.type_conge) }}
                      </span>
                    </td>
                    <td>{{ formatDate(h.date_debut) }}</td>
                    <td>{{ formatDate(h.date_fin) }}</td>
                    <td><strong>{{ h.nombre_jours }}j</strong></td>
                    <td>
                      <span class="badge" :class="statutClass(h.statut_demande)">
                        {{ statutLabel(h.statut_demande) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Périodes de fermeture -->
          <div v-if="fermetures.length" class="dash-panel mt-3">
            <header class="panel-head">
              <div>
                <h3 class="panel-title">
                  <i class="fas fa-door-closed me-2 text-danger"></i>Périodes de fermeture
                </h3>
              </div>
            </header>
            <div class="p-3">
              <div class="row g-2">
                <div v-for="(p, i) in fermetures" :key="i" class="col-sm-6 col-md-4">
                  <div class="fermeture-card">
                    <strong>{{ p.nom || 'Fermeture' }}</strong>
                    <div class="small text-muted">
                      {{ formatDate(p.start) }} — {{ formatDate(p.end) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Collègues en congé -->
          <div class="dash-panel mt-3">
            <header class="panel-head">
              <div>
                <h3 class="panel-title">
                  <i class="fas fa-users me-2 text-info"></i>Collègues en congé
                  <span v-if="colleagues.length" class="badge bg-info ms-2" style="font-size: 0.7rem;">{{ colleagues.length }}</span>
                </h3>
              </div>
            </header>

            <div v-if="colleagues.length" class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr class="text-muted small">
                    <th>Agent</th>
                    <th>Type</th>
                    <th class="d-none d-sm-table-cell">Début</th>
                    <th class="d-none d-sm-table-cell">Fin</th>
                    <th class="d-sm-none">Période</th>
                    <th>Jours</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in colleagues" :key="h.id">
                    <td>
                      <strong>{{ agentName(h.agent) }}</strong>
                      <div v-if="h.agent?.fonction" class="small text-muted">{{ h.agent.fonction }}</div>
                    </td>
                    <td>
                      <span class="badge" :class="typeCongeClass(h.type_conge)">
                        {{ typeCongeLabel(h.type_conge) }}
                      </span>
                    </td>
                    <td class="d-none d-sm-table-cell">{{ formatDate(h.date_debut) }}</td>
                    <td class="d-none d-sm-table-cell">{{ formatDate(h.date_fin) }}</td>
                    <td class="d-sm-none">
                      <div class="small">{{ formatDateShort(h.date_debut) }}</div>
                      <div class="small text-muted">{{ formatDateShort(h.date_fin) }}</div>
                    </td>
                    <td><strong>{{ h.nombre_jours }}j</strong></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="text-center py-4 text-muted">
              <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
              <p class="mb-0">Aucun collègue en congé approuvé pour cette année.</p>
            </div>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import client from '@/api/client'

const auth = useAuthStore()
const ui = useUiStore()

const loading = ref(true)
const selectedYear = ref(new Date().getFullYear())
const structure = ref(null)
const planning = ref(null)
const colleagues = ref([])
const myHolidays = ref([])
const myInterims = ref([])
const stats = ref({ jours_totaux: 0, jours_utilises: 0, jours_restants: 0, taux: 0 })

const years = computed(() => {
  const arr = []
  for (let y = new Date().getFullYear() + 1; y >= 2024; y--) arr.push(y)
  return arr
})

const fermetures = computed(() => {
  if (!planning.value?.periods_fermeture) return []
  const raw = planning.value.periods_fermeture
  return typeof raw === 'string' ? JSON.parse(raw) : raw
})

const tauxColor = computed(() => {
  const t = stats.value.taux
  if (t >= 90) return 'text-danger'
  if (t >= 70) return 'text-warning'
  return 'text-success'
})

const tauxBarColor = computed(() => {
  const t = stats.value.taux
  if (t >= 90) return 'bg-danger'
  if (t >= 70) return 'bg-warning'
  return 'bg-success'
})

async function loadPlanning() {
  loading.value = true
  try {
    const { data } = await client.get('/mon-planning-conges', {
      params: { year: selectedYear.value }
    })
    structure.value = data.structure
    planning.value = data.planning
    colleagues.value = data.colleagues || []
    myHolidays.value = data.my_holidays || []
    myInterims.value = data.my_interims || []
    if (data.stats) {
      stats.value = data.stats
    }
  } catch (error) {
    console.error('Erreur chargement planning:', error)
    ui.addToast('Erreur lors du chargement du planning.', 'danger')
  } finally {
    loading.value = false
  }
}

function agentName(agent) {
  if (!agent) return 'Agent'
  return [agent.prenom, agent.nom, agent.postnom].filter(Boolean).join(' ')
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateShort(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

function typeCongeLabel(type) {
  const labels = {
    annuel: 'Annuel',
    maladie: 'Maladie',
    maternite: 'Maternité',
    paternite: 'Paternité',
    urgence: 'Urgence',
    special: 'Spécial',
  }
  return labels[type] || type
}

function typeCongeClass(type) {
  const classes = {
    annuel: 'bg-primary',
    maladie: 'bg-danger',
    maternite: 'bg-info',
    paternite: 'bg-info',
    urgence: 'bg-warning text-dark',
    special: 'bg-secondary',
  }
  return classes[type] || 'bg-secondary'
}

function statutLabel(statut) {
  const labels = { en_attente: 'En attente', approuve: 'Approuvé', refuse: 'Refusé', annule: 'Annulé' }
  return labels[statut] || statut
}

function statutClass(statut) {
  const classes = { en_attente: 'bg-warning text-dark', approuve: 'bg-success', refuse: 'bg-danger', annule: 'bg-secondary' }
  return classes[statut] || 'bg-secondary'
}

onMounted(() => loadPlanning())
</script>

<style scoped>
/* ── Year filter in hero ── */
.year-filter {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 999px;
  padding: 0.35rem 0.75rem;
}

.year-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.85);
  white-space: nowrap;
}

.year-select {
  background: transparent;
  border: none;
  color: #fff;
  font-size: 0.85rem;
  font-weight: 800;
  cursor: pointer;
  padding: 0;
  outline: none;
  -webkit-appearance: none;
  appearance: none;
}

.year-select option {
  background: #0d2f52;
  color: #fff;
}

/* ── Stat cards ── */
.stat-card-mini {
  padding: 1rem;
  border-radius: 12px;
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 90px;
}

.stat-blue { background: #e3f2fd; }
.stat-orange { background: #fff3e0; }
.stat-green { background: #e8f5e9; }
.stat-purple { background: #f3e5f5; }

.stat-card-mini h3 {
  font-size: 1.5rem;
}

/* ── Validation badge ── */
.validation-status {
  display: flex;
  align-items: center;
}

/* ── Fermeture cards ── */
.fermeture-card {
  border: 1px solid #f8bbd0;
  border-radius: 10px;
  padding: 0.65rem 0.75rem;
  text-align: center;
  background: #fce4ec;
}

/* ── Mobile responsive ── */
@media (max-width: 767.98px) {
  .rh-hero .row { text-align: center; }
  .rh-hero .col-md-6 { text-align: center; }
  .hero-tools { justify-content: center; display: flex; flex-wrap: wrap; gap: 0.5rem; }
  .rh-title { font-size: 1.3rem; }
  .rh-sub { font-size: 0.85rem; }

  .year-filter { margin: 0 auto; }

  .stat-card-mini h3 { font-size: 1.2rem; }
  .stat-card-mini { padding: 0.75rem; min-height: 75px; }

  .panel-title { font-size: 1rem; }

  .table { font-size: 0.82rem; }
  .table th, .table td { padding: 0.5rem 0.4rem; }

  .fermeture-card { padding: 0.5rem; font-size: 0.85rem; }
}

@media (max-width: 575.98px) {
  .stat-card-mini h3 { font-size: 1rem; }
  .stat-card-mini { padding: 0.6rem; min-height: 65px; }
  .stat-card-mini small { font-size: 0.7rem; }

  .year-filter { padding: 0.3rem 0.6rem; }
  .year-label { font-size: 0.72rem; }
  .year-select { font-size: 0.78rem; }
}
</style>
