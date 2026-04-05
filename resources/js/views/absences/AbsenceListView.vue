<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-calendar-times me-2"></i>Mes Absences</h1>
            <p class="rh-sub">Liste des jours ou votre presence n'a pas ete enregistree.</p>
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

      <!-- Filtres -->
      <div class="dash-panel mt-3">
        <div class="p-3">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="form-label mb-1 small fw-bold">Annee</label>
              <select v-model="filters.annee" class="form-select form-select-sm" @change="loadAbsences">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="form-label mb-1 small fw-bold">Mois</label>
              <select v-model="filters.mois" class="form-select form-select-sm" @change="loadAbsences">
                <option value="">Tous</option>
                <option v-for="(label, num) in moisLabels" :key="num" :value="num">{{ label }}</option>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">Reinitialiser</button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement des absences..." />
      </div>

      <template v-else>
        <!-- Resume -->
        <div class="row g-3 mt-2">
          <div class="col-6 col-md-4">
            <div class="p-3 rounded text-center" style="background: #fce4ec;">
              <h3 class="mb-0 fw-bold text-danger">{{ totalAbsences }}</h3>
              <small class="text-muted">Absences en {{ filters.annee }}</small>
            </div>
          </div>
          <div class="col-6 col-md-4">
            <div class="p-3 rounded text-center" style="background: #f0f4f8;">
              <h3 class="mb-0 fw-bold">{{ agentName }}</h3>
              <small class="text-muted">Agent</small>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="p-3 rounded text-center" style="background: #fff3e0;">
              <h3 class="mb-0 fw-bold text-warning">
                <i v-if="totalAbsences > 10" class="fas fa-exclamation-triangle"></i>
                <i v-else-if="totalAbsences > 5" class="fas fa-exclamation-circle"></i>
                <i v-else class="fas fa-check-circle text-success"></i>
              </h3>
              <small class="text-muted">
                {{ totalAbsences > 10 ? 'Taux eleve' : totalAbsences > 5 ? 'A surveiller' : 'Normal' }}
              </small>
            </div>
          </div>
        </div>

        <!-- Liste des absences -->
        <div class="dash-panel mt-3">
          <header class="panel-head">
            <div>
              <h3 class="panel-title">
                <i class="fas fa-list me-2 text-danger"></i>Jours d'absence
                <span class="badge bg-danger ms-2" style="font-size: 0.7rem;">{{ totalAbsences }}</span>
              </h3>
            </div>
          </header>

          <div v-if="absences.length" class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr class="text-muted small">
                  <th style="width: 50px;">#</th>
                  <th>Date</th>
                  <th>Jour</th>
                  <th>Observations</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(pointage, index) in absences" :key="pointage.id">
                  <td class="text-muted">{{ (meta.from || 1) + index }}</td>
                  <td>
                    <i class="fas fa-calendar-day text-danger me-1"></i>
                    <strong>{{ formatDate(pointage.date_pointage) }}</strong>
                  </td>
                  <td>{{ dayName(pointage.date_pointage) }}</td>
                  <td>
                    <span v-if="pointage.observations" class="text-muted">{{ pointage.observations }}</span>
                    <span v-else class="text-muted fst-italic">Aucune observation</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="text-center py-5 text-muted">
            <i class="fas fa-check-circle fa-3x mb-3 d-block text-success"></i>
            <p class="mb-0">Aucune absence detectee pour cette periode.</p>
          </div>

          <!-- Pagination -->
          <div v-if="meta.last_page > 1" class="p-3 d-flex justify-content-center">
            <nav>
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                  <button class="page-link" @click="loadAbsences(meta.current_page - 1)">&laquo;</button>
                </li>
                <li
                  v-for="page in paginationPages"
                  :key="page"
                  class="page-item"
                  :class="{ active: page === meta.current_page }"
                >
                  <button class="page-link" @click="loadAbsences(page)">{{ page }}</button>
                </li>
                <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                  <button class="page-link" @click="loadAbsences(meta.current_page + 1)">&raquo;</button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const auth = useAuthStore()
const ui = useUiStore()

const loading = ref(true)
const absences = ref([])
const totalAbsences = ref(0)
const meta = ref({ current_page: 1, last_page: 1, from: null, to: null })
const filters = ref({ annee: new Date().getFullYear(), mois: '' })

const agentName = computed(() => {
  const a = auth.agent
  return a ? `${a.prenom} ${a.nom}` : 'N/A'
})

const years = computed(() => {
  const arr = []
  for (let y = new Date().getFullYear(); y >= 2023; y--) arr.push(y)
  return arr
})

const moisLabels = {
  1: 'Janvier', 2: 'Fevrier', 3: 'Mars', 4: 'Avril', 5: 'Mai', 6: 'Juin',
  7: 'Juillet', 8: 'Aout', 9: 'Septembre', 10: 'Octobre', 11: 'Novembre', 12: 'Decembre',
}

const paginationPages = computed(() => {
  const pages = []
  const total = meta.value.last_page
  const current = meta.value.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function loadAbsences(page = 1) {
  loading.value = true
  try {
    const params = { annee: filters.value.annee, page }
    if (filters.value.mois) params.mois = filters.value.mois
    const { data } = await client.get('/mes-absences', { params })
    absences.value = data.data
    totalAbsences.value = data.totalAbsences
    meta.value = data.meta || { current_page: 1, last_page: 1, from: null, to: null }
  } catch {
    ui.addToast('Erreur lors du chargement des absences.', 'danger')
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.value = { annee: new Date().getFullYear(), mois: '' }
  loadAbsences()
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function dayName(dateStr) {
  if (!dateStr) return ''
  const jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return jours[new Date(dateStr).getDay()]
}

onMounted(() => loadAbsences())
</script>

<style scoped>
/* ── Mobile responsive styles ── */
@media (max-width: 767.98px) {
  .rh-hero .row {
    text-align: center;
  }
  .rh-hero .col-lg-4 {
    text-align: center;
  }
  .hero-tools {
    justify-content: center;
    display: flex;
  }
  .rh-title {
    font-size: 1.3rem;
  }
  .rh-sub {
    font-size: 0.85rem;
  }

  /* Filters: stack vertically */
  .dash-panel .row .col-auto {
    flex: 1 1 100%;
  }

  /* Summary cards */
  .col-6.col-md-4 .p-3 h3 {
    font-size: 1.3rem;
  }
  .col-6.col-md-4 .p-3 small {
    font-size: 0.75rem;
  }
  .col-12.col-md-4 .p-3 h3 {
    font-size: 1.3rem;
  }

  /* Panel headers */
  .panel-title {
    font-size: 1rem;
  }

  /* Table compact */
  .table {
    font-size: 0.82rem;
  }
  .table th,
  .table td {
    padding: 0.5rem 0.4rem;
    white-space: nowrap;
  }

  /* Hide # column (1st) */
  .table th:nth-child(1),
  .table td:nth-child(1) {
    display: none;
  }

  /* Pagination */
  .pagination {
    flex-wrap: wrap;
    gap: 2px;
  }
  .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.78rem;
  }
}
</style>
