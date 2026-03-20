<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-calendar-alt me-2"></i>Plan de Travail Annuel {{ filters.annee }}</h1>
            <p class="rh-sub">Activites planifiees, en cours et terminees de votre unite organisationnelle.</p>
          </div>
          <div v-if="canEdit" class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'plan-travail.create' }" class="btn-rh main">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle activite
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
              <select v-model="filters.annee" class="form-select form-select-sm" @change="loadPlan">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="form-label mb-1 small fw-bold">Trimestre</label>
              <select v-model="filters.trimestre" class="form-select form-select-sm" @change="loadPlan">
                <option value="">Tous</option>
                <option value="T1">T1 (Jan-Mar)</option>
                <option value="T2">T2 (Avr-Jun)</option>
                <option value="T3">T3 (Jul-Sep)</option>
                <option value="T4">T4 (Oct-Dec)</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="form-label mb-1 small fw-bold">Statut</label>
              <select v-model="filters.statut" class="form-select form-select-sm" @change="loadPlan">
                <option value="">Tous</option>
                <option value="planifiee">Planifiee</option>
                <option value="en_cours">En cours</option>
                <option value="terminee">Terminee</option>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">Reinitialiser</button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement du plan de travail..." />
      </div>

      <template v-else>
        <!-- KPI resume -->
        <div class="row g-3 mt-2">
          <div class="col-6 col-md-3">
            <div class="p-3 rounded text-center" style="background: #f0f4f8;">
              <h3 class="mb-0 fw-bold">{{ stats.total }}</h3>
              <small class="text-muted">Total</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded text-center" style="background: #fff3e0;">
              <h3 class="mb-0 fw-bold text-secondary">{{ stats.planifiee }}</h3>
              <small class="text-muted">Planifiees</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded text-center" style="background: #e3f2fd;">
              <h3 class="mb-0 fw-bold text-primary">{{ stats.en_cours }}</h3>
              <small class="text-muted">En cours</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded text-center" style="background: #e8f5e9;">
              <h3 class="mb-0 fw-bold text-success">{{ stats.terminee }}</h3>
              <small class="text-muted">Terminees</small>
            </div>
          </div>
        </div>

        <!-- Barre de progression globale -->
        <div v-if="stats.total > 0" class="mt-3 p-3 bg-white rounded shadow-sm">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="fw-bold">Progression globale</small>
            <small class="text-muted">{{ stats.avg_pourcentage }}%</small>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" :style="{ width: stats.avg_pourcentage + '%' }"></div>
          </div>
        </div>

        <!-- Activites groupees par trimestre -->
        <template v-if="Object.keys(groupees).length">
          <div v-for="(activites, tri) in groupees" :key="tri" class="dash-panel mt-3">
            <header class="panel-head">
              <div>
                <h3 class="panel-title">
                  <i class="fas fa-layer-group me-2 text-info"></i>{{ triLabel(tri) }}
                  <span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">{{ activites.length }} activite{{ activites.length > 1 ? 's' : '' }}</span>
                </h3>
              </div>
            </header>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr class="text-muted small">
                    <th>Activite</th>
                    <th style="width: 100px;">Statut</th>
                    <th style="width: 150px;">Periode</th>
                    <th style="width: 120px;">Progression</th>
                    <th style="width: 80px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="a in activites" :key="a.id">
                    <td>
                      <router-link :to="{ name: 'plan-travail.show', params: { id: a.id } }" class="text-decoration-none">
                        <strong class="text-dark">{{ a.titre }}</strong>
                      </router-link>
                      <br v-if="a.description"><small v-if="a.description" class="text-muted">{{ truncate(a.description, 80) }}</small>
                      <br><small class="text-muted">
                        <i class="fas fa-building me-1"></i>{{ a.niveau_administratif }}
                        <template v-if="a.departement"> - {{ a.departement.nom }}</template>
                        <template v-if="a.province"> - {{ a.province.nom }}</template>
                      </small>
                    </td>
                    <td><span :class="statutBadge(a.statut)">{{ statutLabel(a.statut) }}</span></td>
                    <td>
                      <small>
                        <template v-if="a.date_debut">{{ formatShortDate(a.date_debut) }}</template>
                        <template v-if="a.date_fin"> &rarr; {{ formatShortDate(a.date_fin) }}</template>
                      </small>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 6px;">
                          <div class="progress-bar" :class="a.pourcentage >= 100 ? 'bg-success' : 'bg-primary'" :style="{ width: a.pourcentage + '%' }"></div>
                        </div>
                        <small class="text-muted">{{ a.pourcentage }}%</small>
                      </div>
                    </td>
                    <td>
                      <router-link :to="{ name: 'plan-travail.show', params: { id: a.id } }" class="btn btn-sm btn-outline-primary" title="Voir">
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link v-if="canEdit" :to="{ name: 'plan-travail.edit', params: { id: a.id } }" class="btn btn-sm btn-outline-secondary" title="Modifier">
                        <i class="fas fa-edit"></i>
                      </router-link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="dash-panel mt-3">
          <div class="text-center py-5 text-muted">
            <i class="fas fa-calendar-alt fa-3x mb-3 d-block"></i>
            <p class="mb-0">Aucune activite pour l'annee {{ filters.annee }}.</p>
            <router-link v-if="canEdit" :to="{ name: 'plan-travail.create' }" class="btn btn-sm btn-outline-primary mt-3">
              <i class="fas fa-plus-circle me-1"></i> Creer la premiere activite
            </router-link>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { list } from '@/api/planTravail'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()
const loading = ref(true)
const groupees = ref({})
const stats = ref({ total: 0, planifiee: 0, en_cours: 0, terminee: 0, avg_pourcentage: 0 })
const canEdit = ref(false)
const filters = ref({ annee: new Date().getFullYear(), trimestre: '', statut: '' })

const years = computed(() => {
  const arr = []
  for (let y = new Date().getFullYear() + 1; y >= 2023; y--) arr.push(y)
  return arr
})

async function loadPlan() {
  loading.value = true
  try {
    const params = { annee: filters.value.annee }
    if (filters.value.trimestre) params.trimestre = filters.value.trimestre
    if (filters.value.statut) params.statut = filters.value.statut
    const { data } = await list(params)
    groupees.value = data.groupees
    stats.value = data.stats
    canEdit.value = data.canEdit
  } catch {
    ui.addToast('Erreur lors du chargement du plan de travail.', 'danger')
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.value = { annee: new Date().getFullYear(), trimestre: '', statut: '' }
  loadPlan()
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

function triLabel(tri) {
  const map = {
    T1: '1er Trimestre (Janvier - Mars)',
    T2: '2e Trimestre (Avril - Juin)',
    T3: '3e Trimestre (Juillet - Septembre)',
    T4: '4e Trimestre (Octobre - Decembre)',
    Annuel: 'Activites annuelles',
  }
  return map[tri] || tri
}

function statutBadge(statut) {
  const map = { terminee: 'badge bg-success', en_cours: 'badge bg-primary', planifiee: 'badge bg-secondary' }
  return map[statut] || 'badge bg-secondary'
}

function statutLabel(statut) {
  const map = { terminee: 'Terminee', en_cours: 'En cours', planifiee: 'Planifiee' }
  return map[statut] || statut
}

function formatShortDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
}

onMounted(() => loadPlan())
</script>
