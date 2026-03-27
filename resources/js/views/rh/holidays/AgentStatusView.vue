<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="ast-hero">
      <div class="ast-hero-inner">
        <div class="ast-hero-text">
          <h2><i class="fas fa-user-clock me-2"></i>Statuts des agents</h2>
          <p>Vue d'ensemble des agents en congé, mission, formation ou suspension.</p>
        </div>
        <div class="ast-hero-kpis">
          <div v-for="st in statusCards" :key="st.key" class="ast-kpi" :style="{ borderColor: st.color }">
            <div class="ast-kpi-icon" :style="{ background: st.bg, color: st.color }">
              <i class="fas" :class="st.icon"></i>
            </div>
            <div>
              <div class="ast-kpi-val" :style="{ color: st.color }">{{ statusCount(st.key) }}</div>
              <div class="ast-kpi-lbl">{{ st.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="ast-filters">
      <button
        v-for="st in [{ key: '', label: 'Tous', icon: 'fa-th-large', color: '#0077B5' }, ...statusCards]"
        :key="st.key"
        class="ast-filter-btn"
        :class="{ active: currentFilter === st.key }"
        :style="currentFilter === st.key ? { background: st.color, borderColor: st.color, color: '#fff' } : {}"
        @click="currentFilter = st.key"
      >
        <i class="fas me-1" :class="st.icon"></i>
        {{ st.label }}
        <span v-if="st.key" class="ast-filter-count">{{ statusCount(st.key) }}</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-2">Chargement des statuts...</p>
    </div>

    <template v-else>
      <!-- Status sections -->
      <div v-for="st in filteredStatusCards" :key="st.key" class="ast-section">
        <div class="ast-section-head">
          <div class="ast-section-icon" :style="{ background: st.bg, color: st.color }">
            <i class="fas" :class="st.icon"></i>
          </div>
          <div>
            <h3 class="ast-section-title">{{ st.label }}</h3>
            <p class="ast-section-sub">{{ statusCount(st.key) }} agent{{ statusCount(st.key) > 1 ? 's' : '' }}</p>
          </div>
        </div>

        <div v-if="statusAgents(st.key).length" class="ast-table-wrap">
          <table class="ast-table">
            <thead>
              <tr>
                <th>Agent</th>
                <th>Matricule</th>
                <th>Organe</th>
                <th>Poste</th>
                <th>Période</th>
                <th>Motif</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="agent in statusAgents(st.key)" :key="agent.id">
                <td>
                  <div class="ast-agent-cell">
                    <div class="ast-agent-avatar" :style="{ background: st.color }">
                      {{ initials(agent) }}
                    </div>
                    <div>
                      <div class="ast-agent-name">{{ agent.prenom }} {{ agent.nom }}</div>
                      <div v-if="agent.sexe" class="ast-agent-sexe">
                        <i class="fas" :class="agent.sexe === 'M' ? 'fa-mars' : 'fa-venus'"></i>
                        {{ agent.sexe === 'M' ? 'Homme' : 'Femme' }}
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="ast-badge-id">{{ agent.id_agent || '-' }}</span>
                </td>
                <td>
                  <span v-if="agent.organe" class="ast-badge-organe">
                    <i class="fas fa-building me-1"></i>{{ agent.organe }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>{{ agent.poste || '-' }}</td>
                <td>
                  <div class="ast-dates">
                    <span>{{ formatDate(agent.date_debut) }}</span>
                    <template v-if="agent.date_fin">
                      <i class="fas fa-arrow-right mx-1" style="font-size:.55rem;opacity:.4;"></i>
                      <span>{{ formatDate(agent.date_fin) }}</span>
                    </template>
                    <span v-else class="ast-badge-ongoing">En cours</span>
                  </div>
                </td>
                <td>
                  <span v-if="agent.motif" class="ast-motif" :title="agent.motif">{{ agent.motif }}</span>
                  <span v-else class="text-muted">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="ast-empty">
          <i class="fas fa-check-circle"></i>
          <span>Aucun agent {{ st.label.toLowerCase() }} actuellement</span>
        </div>
      </div>

      <!-- Global empty -->
      <div v-if="!filteredStatusCards.length" class="ast-empty mt-4">
        <i class="fas fa-users"></i>
        <span>Aucun agent avec un statut particulier</span>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import holidaysApi from '@/api/holidays'

const ui = useUiStore()
const loading = ref(true)
const statuses = ref({})
const currentFilter = ref('')

const statusCards = [
  { key: 'en_conge', label: 'En congé', icon: 'fa-umbrella-beach', color: '#0ea5e9', bg: '#e0f2fe' },
  { key: 'en_mission', label: 'En mission', icon: 'fa-plane-departure', color: '#8b5cf6', bg: '#ede9fe' },
  { key: 'en_formation', label: 'En formation', icon: 'fa-graduation-cap', color: '#f59e0b', bg: '#fef3c7' },
  { key: 'suspendu', label: 'Suspendu', icon: 'fa-user-slash', color: '#ef4444', bg: '#fee2e2' },
]

const filteredStatusCards = computed(() => {
  if (!currentFilter.value) {
    return statusCards.filter(st => statusAgents(st.key).length > 0)
  }
  return statusCards.filter(st => st.key === currentFilter.value)
})

function statusCount(key) {
  return statuses.value[key]?.length ?? 0
}

function statusAgents(key) {
  return statuses.value[key] ?? []
}

function initials(agent) {
  return ((agent.prenom || '').charAt(0) + (agent.nom || '').charAt(0)).toUpperCase()
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  try {
    const { data } = await holidaysApi.getCurrentAgentStatuses()
    // Group by statut
    const grouped = {}
    const list = data.data ?? data
    for (const s of list) {
      const key = s.statut
      if (!grouped[key]) grouped[key] = []
      grouped[key].push({
        id: s.id,
        agent_id: s.agent_id,
        nom: s.agent?.nom,
        prenom: s.agent?.prenom,
        id_agent: s.agent?.id_agent,
        organe: s.agent?.organe,
        poste: s.agent?.poste_actuel,
        sexe: s.agent?.sexe,
        date_debut: s.date_debut,
        date_fin: s.date_fin,
        motif: s.motif,
      })
    }
    statuses.value = grouped
  } catch (err) {
    ui.addToast('Erreur lors du chargement des statuts.', 'danger')
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* Hero */
.ast-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 30%, #0c4a6e 60%, #0077B5 100%);
  border-radius: 18px;
  padding: 2rem 2.2rem;
  margin-bottom: 1.5rem;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.ast-hero::before {
  content: '';
  position: absolute;
  top: -40%;
  right: -8%;
  width: 240px;
  height: 240px;
  border-radius: 50%;
  background: rgba(255, 255, 255, .07);
}
.ast-hero-inner {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  flex-wrap: wrap;
}
.ast-hero-text h2 {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0 0 .3rem;
}
.ast-hero-text p {
  font-size: .85rem;
  opacity: .7;
  margin: 0;
}
.ast-hero-kpis {
  display: flex;
  gap: .6rem;
  flex-wrap: wrap;
}
.ast-kpi {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: rgba(255,255,255,.08);
  border-radius: 12px;
  padding: .6rem .85rem;
  border-left: 3px solid;
}
.ast-kpi-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .85rem;
}
.ast-kpi-val {
  font-size: 1.3rem;
  font-weight: 800;
  line-height: 1;
}
.ast-kpi-lbl {
  font-size: .6rem;
  opacity: .6;
  text-transform: uppercase;
  letter-spacing: .3px;
  font-weight: 600;
}

/* Filters */
.ast-filters {
  display: flex;
  gap: .5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.ast-filter-btn {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  padding: .45rem .9rem;
  border-radius: 10px;
  font-size: .8rem;
  font-weight: 600;
  border: 2px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  transition: all .2s;
}
.ast-filter-btn:hover {
  border-color: #94a3b8;
  transform: translateY(-1px);
}
.ast-filter-btn.active {
  color: #fff;
  border-color: transparent;
}
.ast-filter-count {
  font-size: .7rem;
  background: rgba(255,255,255,.2);
  padding: .05rem .35rem;
  border-radius: 5px;
}
.ast-filter-btn:not(.active) .ast-filter-count {
  background: #f1f5f9;
  color: #475569;
}

/* Sections */
.ast-section {
  margin-bottom: 1.5rem;
}
.ast-section-head {
  display: flex;
  align-items: center;
  gap: .75rem;
  margin-bottom: .75rem;
}
.ast-section-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .95rem;
  flex-shrink: 0;
}
.ast-section-title {
  font-size: 1.05rem;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
  line-height: 1.2;
}
.ast-section-sub {
  font-size: .72rem;
  color: #94a3b8;
  margin: 0;
  font-weight: 500;
}

/* Table */
.ast-table-wrap {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
  overflow-x: auto;
}
.ast-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .82rem;
}
.ast-table thead th {
  background: #f8fafc;
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .3px;
  color: #64748b;
  padding: .7rem 1rem;
  border-bottom: 2px solid #e5e7eb;
  white-space: nowrap;
}
.ast-table tbody td {
  padding: .7rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  vertical-align: middle;
}
.ast-table tbody tr:hover {
  background: #f8fafc;
}
.ast-table tbody tr:last-child td {
  border-bottom: none;
}

/* Agent cell */
.ast-agent-cell {
  display: flex;
  align-items: center;
  gap: .6rem;
}
.ast-agent-avatar {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  flex-shrink: 0;
}
.ast-agent-name {
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}
.ast-agent-sexe {
  font-size: .68rem;
  color: #94a3b8;
}

/* Badges */
.ast-badge-id {
  display: inline-block;
  font-size: .72rem;
  font-weight: 600;
  padding: .15rem .5rem;
  border-radius: 6px;
  background: #f1f5f9;
  color: #475569;
  font-family: monospace;
}
.ast-badge-organe {
  display: inline-flex;
  align-items: center;
  font-size: .72rem;
  font-weight: 600;
  padding: .15rem .5rem;
  border-radius: 6px;
  background: #e0f2fe;
  color: #0369a1;
}
.ast-badge-ongoing {
  display: inline-block;
  font-size: .65rem;
  font-weight: 700;
  padding: .1rem .4rem;
  border-radius: 4px;
  background: #fef3c7;
  color: #d97706;
  margin-left: .35rem;
}

/* Dates */
.ast-dates {
  display: flex;
  align-items: center;
  gap: .2rem;
  font-size: .78rem;
}

/* Motif */
.ast-motif {
  font-size: .78rem;
  color: #64748b;
  max-width: 180px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Empty */
.ast-empty {
  text-align: center;
  padding: 2rem;
  color: #cbd5e1;
  font-size: .85rem;
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
}
.ast-empty i {
  display: block;
  font-size: 1.5rem;
  margin-bottom: .5rem;
  color: #d1fae5;
}

/* Responsive */
@media (max-width: 991.98px) {
  .ast-hero-inner {
    flex-direction: column;
    align-items: flex-start;
  }
  .ast-hero-kpis {
    width: 100%;
  }
  .ast-kpi {
    flex: 1;
    min-width: 120px;
  }
}
@media (max-width: 767.98px) {
  .ast-hero {
    padding: 1.4rem 1.2rem;
    border-radius: 14px;
  }
  .ast-hero-text h2 {
    font-size: 1.2rem;
  }
  .ast-hero-kpis {
    flex-wrap: wrap;
    gap: .4rem;
  }
  .ast-kpi {
    padding: .5rem .6rem;
  }
  .ast-kpi-val {
    font-size: 1.1rem;
  }
  .ast-filters {
    gap: .35rem;
  }
  .ast-filter-btn {
    font-size: .72rem;
    padding: .35rem .6rem;
  }
  .ast-table thead th,
  .ast-table tbody td {
    padding: .5rem .6rem;
    font-size: .75rem;
  }
  /* Hide less important columns on mobile */
  .ast-table thead th:nth-child(4),
  .ast-table tbody td:nth-child(4),
  .ast-table thead th:nth-child(6),
  .ast-table tbody td:nth-child(6) {
    display: none;
  }
}
</style>