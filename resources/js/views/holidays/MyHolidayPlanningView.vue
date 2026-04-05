<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-calendar-alt me-2"></i>Planning Conges</h1>
            <p class="rh-sub">
              <template v-if="structure">{{ structure.nom }}</template>
              <template v-else>Conges de votre structure</template>
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'dashboard' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <!-- Filtre annee -->
      <div class="dash-panel mt-3">
        <div class="p-3">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="form-label mb-1 small fw-bold">Annee</label>
              <select v-model="selectedYear" class="form-select form-select-sm" @change="loadPlanning">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Chargement du planning...</p>
      </div>

      <template v-else>
        <!-- Structure non identifiee -->
        <div v-if="!structure" class="dash-panel mt-3">
          <div class="text-center py-5 text-muted">
            <i class="fas fa-building fa-3x mb-3 d-block text-warning"></i>
            <h5>Structure non identifiee</h5>
            <p class="mb-0">Votre departement ou organe n'a pas pu etre determine.<br>Veuillez contacter la Section RH.</p>
          </div>
        </div>

        <!-- Pas de planning -->
        <div v-else-if="!planning" class="dash-panel mt-3">
          <div class="text-center py-5 text-muted">
            <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
            <h5>Aucun planning pour {{ selectedYear }}</h5>
            <p class="mb-0">Le planning de conges de <strong>{{ structure.nom }}</strong> n'a pas encore ete cree pour cette annee.</p>
          </div>
        </div>

        <!-- Planning trouve -->
        <template v-else>
          <!-- Statistiques -->
          <div class="row g-3 mt-2">
            <div class="col-6 col-md-3">
              <div class="stat-card-mini" style="background: #e3f2fd;">
                <h3 class="mb-0 fw-bold text-primary">{{ stats.jours_totaux }}</h3>
                <small class="text-muted">Jours totaux</small>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card-mini" style="background: #fff3e0;">
                <h3 class="mb-0 fw-bold text-warning">{{ stats.jours_utilises }}</h3>
                <small class="text-muted">Jours utilises</small>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card-mini" style="background: #e8f5e9;">
                <h3 class="mb-0 fw-bold text-success">{{ stats.jours_restants }}</h3>
                <small class="text-muted">Jours restants</small>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card-mini" style="background: #f3e5f5;">
                <div class="d-flex align-items-center justify-content-center gap-2">
                  <h3 class="mb-0 fw-bold" :class="tauxColor">{{ stats.taux }}%</h3>
                </div>
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
          <div class="mt-3">
            <span class="badge" :class="planning.valide ? 'bg-success' : 'bg-warning'">
              <i class="fas me-1" :class="planning.valide ? 'fa-check-circle' : 'fa-clock'"></i>
              {{ planning.valide ? 'Planning valide' : 'Planning en attente de validation' }}
            </span>
          </div>

          <!-- Periodes de fermeture -->
          <div v-if="fermetures.length" class="dash-panel mt-3">
            <header class="panel-head">
              <div>
                <h3 class="panel-title">
                  <i class="fas fa-door-closed me-2 text-danger"></i>Periodes de fermeture
                </h3>
              </div>
            </header>
            <div class="p-3">
              <div class="row g-2">
                <div v-for="(p, i) in fermetures" :key="i" class="col-md-4">
                  <div class="border rounded p-2 text-center" style="background: #fce4ec;">
                    <strong>{{ p.nom || 'Fermeture' }}</strong>
                    <div class="small text-muted">
                      {{ formatDate(p.start) }} — {{ formatDate(p.end) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Collegues en conge -->
          <div class="dash-panel mt-3">
            <header class="panel-head">
              <div>
                <h3 class="panel-title">
                  <i class="fas fa-users me-2 text-info"></i>Collegues en conge
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
                    <th>Debut</th>
                    <th>Fin</th>
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
                    <td>{{ formatDate(h.date_debut) }}</td>
                    <td>{{ formatDate(h.date_fin) }}</td>
                    <td><strong>{{ h.nombre_jours }}j</strong></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="text-center py-4 text-muted">
              <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
              <p class="mb-0">Aucun collegue en conge approuve pour cette annee.</p>
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

function typeCongeLabel(type) {
  const labels = {
    annuel: 'Annuel',
    maladie: 'Maladie',
    maternite: 'Maternite',
    paternite: 'Paternite',
    urgence: 'Urgence',
    special: 'Special',
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

onMounted(() => loadPlanning())
</script>

<style scoped>
.stat-card-mini {
  padding: 1rem;
  border-radius: 10px;
  text-align: center;
}

.stat-card-mini h3 {
  font-size: 1.5rem;
}

/* ── Mobile responsive ── */
@media (max-width: 767.98px) {
  .rh-hero .row { text-align: center; }
  .rh-hero .col-lg-4 { text-align: center; }
  .hero-tools { justify-content: center; display: flex; }
  .rh-title { font-size: 1.3rem; }
  .rh-sub { font-size: 0.85rem; }

  .dash-panel .row .col-auto { flex: 1 1 100%; }

  .stat-card-mini h3 { font-size: 1.2rem; }
  .stat-card-mini { padding: 0.75rem; }

  .panel-title { font-size: 1rem; }

  .table { font-size: 0.82rem; }
  .table th, .table td { padding: 0.5rem 0.4rem; white-space: nowrap; }

  .pagination { flex-wrap: wrap; gap: 2px; }
  .page-link { padding: 0.25rem 0.5rem; font-size: 0.78rem; }
}
</style>
