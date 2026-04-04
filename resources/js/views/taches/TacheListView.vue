<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>Mes Taches</h1>
            <p class="rh-sub">Suivi des taches assignees et creees.</p>
          </div>
          <div v-if="isDirecteur" class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'taches.create' }" class="btn-rh main">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle tache
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement des taches..." />
      </div>

      <template v-else>
        <!-- Taches assignees a moi -->
        <div class="dash-panel mt-3">
          <header class="panel-head">
            <div>
              <h3 class="panel-title"><i class="fas fa-clipboard-check me-2 text-primary"></i>Taches qui me sont assignees</h3>
              <p class="panel-sub">Taches attribuees par votre directeur.</p>
            </div>
          </header>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Origine</th>
                  <th>De</th>
                  <th>Priorite</th>
                  <th>Statut</th>
                  <th>Echeance</th>
                  <th>Date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in mesTaches" :key="t.id">
                  <td>
                    <strong>{{ t.titre }}</strong>
                    <br v-if="t.description"><small v-if="t.description" class="text-muted">{{ truncate(t.description, 60) }}</small>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">{{ sourceTypeLabel(t.source_type) }}</span>
                    <br v-if="t.activite_plan"><small class="text-muted">{{ t.activite_plan.titre }}</small>
                  </td>
                  <td>{{ t.createur?.nom_complet ?? '-' }}</td>
                  <td><span :class="prioriteBadge(t.priorite)">{{ capitalize(t.priorite) }}</span></td>
                  <td><span :class="statutBadge(t.statut)">{{ statutLabel(t.statut) }}</span></td>
                  <td>
                    <template v-if="t.date_echeance">
                      {{ formatDate(t.date_echeance) }}
                      <br v-if="isOverdue(t)"><small v-if="isOverdue(t)" class="text-danger">En retard</small>
                    </template>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>{{ formatDate(t.created_at) }}</td>
                  <td>
                    <router-link :to="{ name: 'taches.show', params: { id: t.id } }" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i>
                    </router-link>
                  </td>
                </tr>
                <tr v-if="!mesTaches.length">
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Aucune tache assignee.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Taches creees par moi (Directeur) -->
        <div v-if="isDirecteur" class="dash-panel mt-3">
          <header class="panel-head">
            <div>
              <h3 class="panel-title"><i class="fas fa-clipboard-list me-2 text-success"></i>Taches que j'ai assignees</h3>
              <p class="panel-sub">Suivi des taches attribuees aux agents de votre departement.</p>
            </div>
          </header>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Origine</th>
                  <th>Assigne a</th>
                  <th>Priorite</th>
                  <th>Statut</th>
                  <th>Echeance</th>
                  <th>Date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in tachesCreees" :key="t.id">
                  <td>
                    <strong>{{ t.titre }}</strong>
                    <br v-if="t.description"><small v-if="t.description" class="text-muted">{{ truncate(t.description, 60) }}</small>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">{{ sourceTypeLabel(t.source_type) }}</span>
                    <br v-if="t.activite_plan"><small class="text-muted">{{ t.activite_plan.titre }}</small>
                  </td>
                  <td>{{ t.agent?.nom_complet ?? '-' }}</td>
                  <td><span :class="prioriteBadge(t.priorite)">{{ capitalize(t.priorite) }}</span></td>
                  <td><span :class="statutBadge(t.statut)">{{ statutLabel(t.statut) }}</span></td>
                  <td>
                    <template v-if="t.date_echeance">
                      {{ formatDate(t.date_echeance) }}
                      <br v-if="isOverdue(t)"><small v-if="isOverdue(t)" class="text-danger">En retard</small>
                    </template>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>{{ formatDate(t.created_at) }}</td>
                  <td>
                    <router-link :to="{ name: 'taches.show', params: { id: t.id } }" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i>
                    </router-link>
                  </td>
                </tr>
                <tr v-if="!tachesCreees.length">
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Aucune tache creee.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { list } from '@/api/taches'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()
const loading = ref(true)
const mesTaches = ref([])
const tachesCreees = ref([])
const isDirecteur = ref(false)

async function loadTaches() {
  try {
    const { data } = await list()
    mesTaches.value = data.mesTaches
    tachesCreees.value = data.tachesCreees
    isDirecteur.value = data.isDirecteur
  } catch {
    ui.addToast('Erreur lors du chargement des taches.', 'danger')
  } finally {
    loading.value = false
  }
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

function prioriteBadge(priorite) {
  const map = { urgente: 'badge bg-danger', haute: 'badge bg-warning text-dark', normale: 'badge bg-secondary' }
  return map[priorite] || 'badge bg-secondary'
}

function statutBadge(statut) {
  const map = { terminee: 'badge bg-success', en_cours: 'badge bg-primary', nouvelle: 'badge bg-secondary' }
  return map[statut] || 'badge bg-secondary'
}

function statutLabel(statut) {
  const map = { terminee: 'Terminee', en_cours: 'En cours', nouvelle: 'Nouvelle' }
  return map[statut] || capitalize(statut)
}

function sourceTypeLabel(sourceType) {
  return sourceType === 'pta' ? 'PTA' : 'Hors PTA'
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function isOverdue(tache) {
  if (!tache.date_echeance || tache.statut === 'terminee') return false
  return new Date(tache.date_echeance) < new Date()
}

onMounted(() => loadTaches())
</script>

<style scoped>
/* ── Mobile responsive styles ── */
@media (max-width: 768px) {
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

  /* Panel headers */
  .panel-title {
    font-size: 1rem;
  }
  .panel-sub {
    font-size: 0.8rem;
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

  /* Hide De/Assigne (2nd), Echeance (5th), Date (6th) */
  .table th:nth-child(2),
  .table td:nth-child(2),
  .table th:nth-child(6),
  .table td:nth-child(6),
  .table th:nth-child(7),
  .table td:nth-child(7) {
    display: none;
  }

  /* Compact action buttons */
  .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
  }

  /* Dash panels spacing */
  .dash-panel {
    margin-top: 0.75rem !important;
  }
}
</style>
