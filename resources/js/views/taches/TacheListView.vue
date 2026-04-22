<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>{{ pageTitle }}</h1>
            <p class="rh-sub">{{ pageSubtitle }}</p>
          </div>
          <div v-if="isDirecteur" class="col-lg-4">
            <div class="hero-tools">
              <router-link v-if="!showAssignedByMe" :to="{ name: 'taches.assigned-by-me' }" class="btn-rh alt">
                <i class="fas fa-clipboard-list me-1"></i> Tâches assignées par moi
              </router-link>
              <router-link v-else :to="{ name: 'taches.index' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour à mes tâches
              </router-link>
              <router-link :to="{ name: 'taches.create' }" class="btn-rh main">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle tâche
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement des tâches..." />
      </div>

      <template v-else>
        <!-- ── Status Tabs ── -->
        <div class="tache-status-tabs mt-3">
          <button
            v-for="tab in statusTabs"
            :key="tab.key"
            class="tache-tab"
            :class="{ active: statusFilter === tab.key, [tab.color]: true }"
            @click="statusFilter = tab.key"
          >
            <i :class="tab.icon + ' me-1'"></i>
            <span class="tache-tab-label">{{ tab.label }}</span>
            <span class="tache-tab-count">{{ tab.count }}</span>
          </button>
        </div>

        <div class="dash-panel mt-2">
          <header class="panel-head">
            <div>
              <h3 class="panel-title">
                <i :class="showAssignedByMe ? 'fas fa-clipboard-list me-2 text-success' : 'fas fa-clipboard-check me-2 text-primary'"></i>
                {{ panelTitle }}
              </h3>
              <p class="panel-sub">{{ panelSubtitle }}</p>
            </div>
            <div class="task-filters">
              <label for="source-filter" class="task-filter-label">Source</label>
              <select id="source-filter" v-model="sourceFilter" class="form-select form-select-sm task-filter-select">
                <option value="all">Toutes</option>
                <option value="direction">Direction</option>
                <option value="sen">SEN</option>
                <option value="sep">SEP</option>
                <option value="sel">SEL</option>
                <option value="autre">Autre</option>
              </select>
            </div>
          </header>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Origine</th>
                  <th>{{ showAssignedByMe ? 'Assigné à' : 'De' }}</th>
                  <th>Priorité</th>
                  <th>Statut</th>
                  <th>Échéance</th>
                  <th>Date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in filteredTaches" :key="t.id">
                  <td>
                    <strong>{{ t.titre }}</strong>
                    <br v-if="t.description"><small v-if="t.description" class="text-muted">{{ truncate(t.description, 60) }}</small>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">{{ sourceTypeLabel(t.source_type) }}</span>
                    <br><small class="text-muted">{{ sourceEmetteurLabel(t.source_emetteur) }}</small>
                    <br v-if="t.activite_plan"><small class="text-muted">{{ t.activite_plan.titre }}</small>
                  </td>
                  <td>{{ showAssignedByMe ? (t.agent?.nom_complet ?? '-') : (t.createur?.nom_complet ?? '-') }}</td>
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
                <tr v-if="!filteredTaches.length">
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    {{ emptyStateText }}
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
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { list } from '@/api/taches'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()
const loading = ref(true)
const mesTaches = ref([])
const tachesCreees = ref([])
const isDirecteur = ref(false)
const sourceFilter = ref('all')
const statusFilter = ref(route.query.statut ?? 'all')

const showAssignedByMe = computed(() => route.name === 'taches.assigned-by-me')

/* ── helper: overdue check ── */
function isOverdue(tache) {
  if (!tache.date_echeance || tache.statut === 'terminee') return false
  return new Date(tache.date_echeance) < new Date()
}

/* ── Source-filtered base list ── */
const sourceFilteredTaches = computed(() => {
  const items = showAssignedByMe.value ? tachesCreees.value : mesTaches.value
  return items.filter((t) => matchesSourceFilter(t.source_emetteur))
})

/* ── Status tabs with live counts ── */
const statusTabs = computed(() => {
  const items = sourceFilteredTaches.value
  return [
    { key: 'all',       label: 'Toutes',    icon: 'fas fa-list',           color: 'tab-all',       count: items.length },
    { key: 'nouvelle',  label: 'Nouvelles',  icon: 'fas fa-star',           color: 'tab-nouvelle',  count: items.filter(t => t.statut === 'nouvelle').length },
    { key: 'en_cours',  label: 'En cours',   icon: 'fas fa-spinner',        color: 'tab-en-cours',  count: items.filter(t => t.statut === 'en_cours').length },
    { key: 'terminee',  label: 'Terminées',  icon: 'fas fa-check-circle',   color: 'tab-terminee',  count: items.filter(t => t.statut === 'terminee').length },
    { key: 'en_retard', label: 'En retard',  icon: 'fas fa-exclamation-triangle', color: 'tab-en-retard', count: items.filter(t => isOverdue(t)).length },
  ]
})

/* ── Final filtered list (source + status) ── */
const filteredTaches = computed(() => {
  let items = sourceFilteredTaches.value
  if (statusFilter.value === 'en_retard') {
    items = items.filter(t => isOverdue(t))
  } else if (statusFilter.value !== 'all') {
    items = items.filter(t => t.statut === statusFilter.value)
  }
  return items
})

const pageTitle = computed(() => showAssignedByMe.value ? 'Tâches assignées par moi' : 'Mes Tâches')
const pageSubtitle = computed(() => showAssignedByMe.value
  ? 'Suivi des tâches que vous attribuez aux agents de votre structure.'
  : 'Espace des tâches qui vous sont attribuées par votre direction, le SEN, le SEP ou le SEL.')
const panelTitle = computed(() => showAssignedByMe.value ? 'Tâches que j\'ai assignées' : 'Tâches qui me sont assignées')
const panelSubtitle = computed(() => showAssignedByMe.value
  ? 'Liste des tâches que vous avez affectées aux agents.'
  : 'Tâches attribuées par votre direction, le SEN, le SEP ou le SEL.')
const emptyStateText = computed(() => showAssignedByMe.value ? 'Aucune tâche assignée par vous pour ce filtre.' : 'Aucune tâche assignée pour ce filtre.')

async function loadTaches() {
  try {
    const { data } = await list()
    mesTaches.value = data.mesTaches
    tachesCreees.value = data.tachesCreees
    isDirecteur.value = data.isDirecteur
  } catch {
    ui.addToast('Erreur lors du chargement des tâches.', 'danger')
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
  const map = { terminee: 'Terminée', en_cours: 'En cours', nouvelle: 'Nouvelle' }
  return map[statut] || capitalize(statut)
}

function sourceTypeLabel(sourceType) {
  return sourceType === 'pta' ? 'PTA' : 'Hors PTA'
}

function sourceEmetteurLabel(source) {
  const map = {
    directeur: 'Direction',
    assistant_departement: 'Direction',
    sen: 'SEN',
    sep: 'SEP',
    sel: 'SEL',
    autre: 'Autre',
  }
  return map[source] || source
}

function matchesSourceFilter(source) {
  if (sourceFilter.value === 'all') return true
  if (sourceFilter.value === 'direction') {
    return ['directeur', 'assistant_departement'].includes(source)
  }
  return source === sourceFilter.value
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => {
  if (route.query.statut) {
    const valid = ['nouvelle', 'en_cours', 'terminee', 'en_retard']
    if (valid.includes(route.query.statut)) {
      statusFilter.value = route.query.statut
    }
  }
  loadTaches()
})

/* ── Sync tab → URL query param ── */
watch(statusFilter, (val) => {
  const query = { ...route.query }
  if (val === 'all') {
    delete query.statut
  } else {
    query.statut = val
  }
  router.replace({ query })
})
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
    flex-wrap: wrap;
    gap: .5rem;
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
  .task-filters {
    width: 100%;
    justify-content: center;
  }

  /* Table compact */
  .table {
    font-size: 0.82rem;
  }
  .table th,
  .table td {
    padding: 0.5rem 0.4rem;
  }
  .table td:last-child {
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

.task-filters {
  display: flex;
  align-items: center;
  gap: .6rem;
}

.task-filter-label {
  font-size: .82rem;
  font-weight: 700;
  color: #64748b;
}

.task-filter-select {
  min-width: 150px;
}

/* ── Status Tabs ── */
.tache-status-tabs {
  display: flex;
  gap: .5rem;
  flex-wrap: wrap;
}

.tache-tab {
  display: flex;
  align-items: center;
  gap: .35rem;
  padding: .5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: .6rem;
  background: #fff;
  font-size: .85rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all .2s;
}
.tache-tab:hover { border-color: #94a3b8; color: #334155; }

.tache-tab-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.4rem;
  height: 1.4rem;
  padding: 0 .35rem;
  border-radius: 999px;
  font-size: .72rem;
  font-weight: 700;
  background: #e2e8f0;
  color: #475569;
}

/* ── Active states ── */
.tache-tab.active.tab-all          { border-color: #475569; color: #1e293b; background: #f1f5f9; }
.tache-tab.active.tab-all .tache-tab-count { background: #475569; color: #fff; }

.tache-tab.active.tab-nouvelle     { border-color: #6366f1; color: #4338ca; background: #eef2ff; }
.tache-tab.active.tab-nouvelle .tache-tab-count { background: #6366f1; color: #fff; }

.tache-tab.active.tab-en-cours     { border-color: #3b82f6; color: #1d4ed8; background: #eff6ff; }
.tache-tab.active.tab-en-cours .tache-tab-count { background: #3b82f6; color: #fff; }

.tache-tab.active.tab-terminee     { border-color: #22c55e; color: #15803d; background: #f0fdf4; }
.tache-tab.active.tab-terminee .tache-tab-count { background: #22c55e; color: #fff; }

.tache-tab.active.tab-en-retard    { border-color: #ef4444; color: #b91c1c; background: #fef2f2; }
.tache-tab.active.tab-en-retard .tache-tab-count { background: #ef4444; color: #fff; }

@media (max-width: 767.98px) {
  .tache-status-tabs {
    gap: .3rem;
  }
  .tache-tab {
    padding: .35rem .6rem;
    font-size: .75rem;
    border-radius: .45rem;
  }
  .tache-tab-label {
    display: none;
  }
  .tache-tab-count {
    font-size: .68rem;
    min-width: 1.2rem;
    height: 1.2rem;
  }
}
</style>
