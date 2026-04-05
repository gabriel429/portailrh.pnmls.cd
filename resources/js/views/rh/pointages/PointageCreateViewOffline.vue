<template>
  <div class="rh-modern">
    <!-- Header avec indicateur offline -->
    <div class="rh-list-shell">
      <section class="rh-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title">
              <i class="fas fa-clipboard-check me-2"></i>
              Saisie des pointages
              <!-- Indicateur offline -->
              <span v-if="isOffline" class="badge bg-warning ms-2">
                <i class="fas fa-wifi-slash me-1"></i> Mode offline
              </span>
              <span v-else-if="syncStats.total > 0" class="badge bg-info ms-2">
                <i class="fas fa-cloud-upload-alt me-1"></i> {{ syncStats.total }} en attente
              </span>
            </h1>
            <p class="rh-sub">
              Saisie groupée par département/service.
              {{ isOffline ? 'Les pointages seront synchronisés au retour en ligne.' : 'Sélectionnez un département pour afficher ses agents.' }}
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <!-- Bouton sync offline -->
              <button v-if="syncStats.total > 0" @click="forceSyncNow" class="btn-rh"
                      :disabled="isOffline || saving" title="Synchroniser maintenant">
                <i class="fas fa-sync-alt me-1"></i> Sync ({{ syncStats.total }})
              </button>

              <button v-if="isOffline" @click="showOfflinePanel = !showOfflinePanel"
                      class="btn-rh alt me-2" title="Panneau offline">
                <i class="fas fa-database me-1"></i> Cache
              </button>

              <router-link :to="{ name: 'rh.pointages.index' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour liste
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <!-- Panneau offline stats -->
      <div v-if="showOfflinePanel" class="alert alert-info mb-4">
        <div class="row g-3">
          <div class="col-md-3">
            <h6><i class="fas fa-database me-1"></i> Cache</h6>
            <small>{{ syncStats.departments || 0 }} départements<br>{{ syncStats.agents || 0 }} agents</small>
          </div>
          <div class="col-md-3">
            <h6><i class="fas fa-clock me-1"></i> En attente</h6>
            <small>{{ syncStats.total || 0 }} pointages<br>{{ syncStats.errors || 0 }} erreurs</small>
          </div>
          <div class="col-md-3">
            <h6><i class="fas fa-wifi me-1"></i> Connexion</h6>
            <small>
              <span :class="isOffline ? 'text-warning' : 'text-success'">
                {{ isOffline ? 'Hors ligne' : 'En ligne' }}
              </span>
            </small>
          </div>
          <div class="col-md-3">
            <button @click="preloadData" class="btn btn-sm btn-outline-primary" :disabled="isOffline">
              <i class="fas fa-download me-1"></i> Précharger
            </button>
          </div>
        </div>
      </div>

      <div class="rh-list-card p-3 p-lg-4">
        <!-- Sélection département et date -->
        <div class="row g-3 mb-4">
          <div class="col-md-5">
            <label for="department_id" class="form-label fw-bold">
              Département / Service
              <span v-if="isOffline && departments.length === 0" class="text-warning">
                (cache vide)
              </span>
            </label>
            <select class="form-select" id="department_id" v-model="selectedDepartment">
              <option value="">-- Sélectionner un département --</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.nom }}
                <span v-if="isOffline">*</span>
              </option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="date_pointage" class="form-label fw-bold">Date du pointage</label>
            <input type="date" class="form-control" id="date_pointage" v-model="datePointage">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-primary w-100" @click="loadAgents" :disabled="loadingAgents">
              <span v-if="loadingAgents" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-search me-1"></i>
              Charger{{ isOffline ? ' (cache)' : '' }}
            </button>
          </div>
        </div>

        <!-- Message offline si pas de données -->
        <div v-if="isOffline && !agentsLoaded && selectedDepartment" class="alert alert-warning">
          <i class="fas fa-wifi-slash me-2"></i>
          <strong>Mode offline :</strong>
          Seules les données en cache sont disponibles.
          {{ agents.length === 0 ? 'Aucun agent trouvé pour ce département.' : '' }}
        </div>

        <!-- Loading state -->
        <div v-if="loadingAgents" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
          <p class="mt-2 text-muted">
            Chargement des agents{{ isOffline ? ' (depuis le cache local)' : '' }}...
          </p>
        </div>

        <!-- Table des agents -->
        <div v-if="agentsLoaded && agents.length > 0" class="agents-section">
          <!-- Actions groupées -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
              <i class="fas fa-users me-2"></i>
              Agents ({{ agents.length }})
              <small class="text-muted ms-2">{{ selectedAgents.length }} sélectionnés</small>
            </h5>

            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-success" @click="markAllPresent">
                <i class="fas fa-check-double me-1"></i> Tous présents
              </button>
              <button type="button" class="btn btn-sm btn-secondary" @click="resetForm">
                <i class="fas fa-redo me-1"></i> Reset
              </button>
            </div>
          </div>

          <!-- Table responsive -->
          <div class="table-responsive">
            <table class="table table-hover pointage-table">
              <thead class="table-light">
                <tr>
                  <th>Agent</th>
                  <th width="80">Présent</th>
                  <th width="120">Arrivée</th>
                  <th width="120">Départ</th>
                  <th width="200">Commentaire</th>
                  <th width="100">Retard justifié</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="agent in agents" :key="agent.id"
                    :class="{ 'table-success': pointageData[agent.id]?.present }">
                  <td>
                    <div class="agent-info">
                      <div class="agent-name">{{ agent.nom }} {{ agent.prenom }}</div>
                      <small class="agent-poste text-muted">{{ agent.poste_display || 'Non défini' }}</small>
                    </div>
                  </td>
                  <td>
                    <div class="form-check">
                      <input
                        type="checkbox"
                        class="form-check-input"
                        :id="`present_${agent.id}`"
                        :checked="pointageData[agent.id]?.present"
                        @change="togglePresence(agent.id)"
                      >
                    </div>
                  </td>
                  <td>
                    <input
                      type="time"
                      class="form-control form-control-sm"
                      v-model="pointageData[agent.id].heure_arrivee"
                      :disabled="!pointageData[agent.id]?.present"
                    >
                  </td>
                  <td>
                    <input
                      type="time"
                      class="form-control form-control-sm"
                      v-model="pointageData[agent.id].heure_depart"
                      :disabled="!pointageData[agent.id]?.present"
                    >
                  </td>
                  <td>
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Commentaire..."
                      v-model="pointageData[agent.id].commentaire"
                      :disabled="!pointageData[agent.id]?.present"
                    >
                  </td>
                  <td>
                    <div class="form-check">
                      <input
                        type="checkbox"
                        class="form-check-input"
                        :id="`retard_${agent.id}`"
                        v-model="pointageData[agent.id].retard_justifie"
                        :disabled="!pointageData[agent.id]?.present"
                      >
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Actions de sauvegarde -->
          <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
              <i class="fas fa-info-circle me-1"></i>
              {{ selectedAgents.length }} pointage(s) à sauvegarder
              {{ isOffline ? '(sauvegarde locale)' : '' }}
            </div>

            <div>
              <button
                type="button"
                class="btn btn-success btn-lg"
                @click="savePointages"
                :disabled="saving || selectedAgents.length === 0"
              >
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else-if="isOffline" class="fas fa-save me-2"></i>
                <i v-else class="fas fa-cloud-upload-alt me-2"></i>
                {{ saving ? 'Sauvegarde...' : (isOffline ? 'Sauvegarder localement' : 'Sauvegarder') }}
              </button>
            </div>
          </div>

          <!-- Indication offline -->
          <div v-if="isOffline && hasUnsavedChanges" class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Mode offline :</strong>
            Les pointages seront sauvegardés localement et synchronisés automatiquement
            dès que la connexion sera rétablie.
          </div>
        </div>

        <!-- État vide -->
        <div v-if="agentsLoaded && agents.length === 0" class="text-center py-5">
          <div class="mb-3">
            <i class="fas fa-users fa-3x text-muted"></i>
          </div>
          <h5 class="text-muted">Aucun agent trouvé</h5>
          <p class="text-muted">
            {{ isOffline ?
              'Aucun agent en cache pour ce département. Connectez-vous pour charger les données.' :
              'Ce département ne contient aucun agent ou les agents ne sont pas encore chargés.'
            }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'

// Services offline
import cacheService from '@/services/cacheService'
import syncService from '@/services/syncService'

const router = useRouter()
const ui = useUiStore()
const auth = useAuthStore()

// État de base
const departments = ref([])
const selectedDepartment = ref('')
const datePointage = ref(new Date().toISOString().split('T')[0])
const agents = ref([])
const agentsLoaded = ref(false)
const loadingAgents = ref(false)
const saving = ref(false)

// État offline
const isOffline = ref(!navigator.onLine)
const syncStats = ref({})
const showOfflinePanel = ref(false)

// Données de pointage
const pointageData = ref({})

// Computed
const selectedAgents = computed(() =>
  agents.value.filter(agent => pointageData.value[agent.id]?.present)
)

const hasUnsavedChanges = computed(() => selectedAgents.value.length > 0)

// Network listener
function initNetworkListener() {
  const updateOnlineStatus = () => {
    isOffline.value = !navigator.onLine
    if (!isOffline.value) {
      ui.addToast('🌐 Connexion rétablie - Synchronisation...', 'success', 3000)
    }
  }

  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
}

// Récupération départements
async function fetchDepartments() {
  try {
    const result = await client.getDepartments()
    departments.value = result || []

    if (result.length === 0 && isOffline.value) {
      ui.addToast('📱 Aucun département en cache - Connexion requise', 'warning')
    }
  } catch (error) {
    console.error('Erreur départements:', error)
    ui.addToast('Impossible de récupérer les départements', 'danger')
  }
}

// Chargement agents
async function loadAgents() {
  if (!selectedDepartment.value) {
    ui.addToast('Veuillez sélectionner un département', 'warning')
    return
  }

  loadingAgents.value = true

  try {
    const result = await client.getAgentsByDepartment(selectedDepartment.value)
    agents.value = result || []
    agentsLoaded.value = true

    // Initialiser pointageData
    pointageData.value = {}
    agents.value.forEach(agent => {
      pointageData.value[agent.id] = {
        present: false,
        heure_arrivee: '',
        heure_depart: '',
        commentaire: '',
        retard_justifie: false
      }
    })

    const cacheIndicator = isOffline.value ? ' (cache local)' : ''
    if (result.length > 0) {
      ui.addToast(`✅ ${result.length} agent(s) chargé(s)${cacheIndicator}`, 'success')
    }

  } catch (error) {
    console.error('Erreur chargement agents:', error)
    ui.addToast('Impossible de charger les agents', 'danger')
  } finally {
    loadingAgents.value = false
  }
}

// Actions pointage
function togglePresence(agentId) {
  const agent = pointageData.value[agentId]
  agent.present = !agent.present

  if (agent.present) {
    if (!agent.heure_arrivee) agent.heure_arrivee = '08:00'
    if (!agent.heure_depart) agent.heure_depart = '17:00'
  } else {
    agent.heure_arrivee = ''
    agent.heure_depart = ''
    agent.commentaire = ''
    agent.retard_justifie = false
  }
}

function markAllPresent() {
  agents.value.forEach(agent => {
    pointageData.value[agent.id] = {
      present: true,
      heure_arrivee: '08:00',
      heure_depart: '17:00',
      commentaire: '',
      retard_justifie: false
    }
  })
  ui.addToast(`✅ ${agents.value.length} agents marqués présents`, 'success')
}

function resetForm() {
  agents.value.forEach(agent => {
    pointageData.value[agent.id] = {
      present: false,
      heure_arrivee: '',
      heure_depart: '',
      commentaire: '',
      retard_justifie: false
    }
  })
}

// Sauvegarde avec support offline
async function savePointages() {
  if (selectedAgents.value.length === 0) {
    ui.addToast('Aucun pointage à sauvegarder', 'warning')
    return
  }

  saving.value = true

  try {
    const pointagesToSave = selectedAgents.value.map(agent => ({
      agent_id: agent.id,
      date_pointage: datePointage.value,
      present: pointageData.value[agent.id].present,
      heure_arrivee: pointageData.value[agent.id].heure_arrivee || null,
      heure_depart: pointageData.value[agent.id].heure_depart || null,
      commentaire: pointageData.value[agent.id].commentaire || null,
      retard_justifie: pointageData.value[agent.id].retard_justifie,
      department_id: selectedDepartment.value,
      created_by: auth.user?.id
    }))

    const results = await Promise.allSettled(
      pointagesToSave.map(pointage => client.createPointage(pointage))
    )

    const successful = results.filter(r => r.status === 'fulfilled')
    const failed = results.filter(r => r.status === 'rejected')
    const offline = successful.filter(r => r.value.offline).length
    const online = successful.filter(r => !r.value.offline).length

    // Feedback message
    let message = ''
    let type = 'success'

    if (failed.length === 0) {
      if (offline > 0 && online === 0) {
        message = `📱 ${offline} pointage(s) sauvegardé(s) localement`
        type = 'warning'
      } else if (online > 0 && offline === 0) {
        message = `✅ ${online} pointage(s) synchronisé(s)`
      } else {
        message = `✅ ${online} synchronisé(s), ${offline} en attente`
        type = 'info'
      }
    } else {
      message = successful.length > 0 ?
        `⚠️ ${successful.length} réussis, ${failed.length} échecs` :
        `❌ Échec de ${failed.length} pointages`
      type = successful.length > 0 ? 'warning' : 'danger'
    }

    ui.addToast(message, type, 5000)

    if (failed.length === 0) {
      resetForm()
    }

    updateSyncStats()

  } catch (error) {
    console.error('Erreur sauvegarde:', error)
    ui.addToast('Erreur lors de la sauvegarde', 'danger')
  } finally {
    saving.value = false
  }
}

// Stats synchronisation
async function updateSyncStats() {
  try {
    syncStats.value = await syncService.getSyncStats()
  } catch (error) {
    console.error('Erreur stats:', error)
  }
}

// Sync forcée
async function forceSyncNow() {
  if (isOffline.value) {
    ui.addToast('Connexion requise pour synchroniser', 'warning')
    return
  }

  try {
    ui.addToast('🔄 Synchronisation...', 'info')
    const success = await syncService.forceSyncAll()
    updateSyncStats()
  } catch (error) {
    ui.addToast('❌ Erreur synchronisation', 'danger')
  }
}

// Préchargement
async function preloadData() {
  if (isOffline.value) {
    ui.addToast('Connexion requise pour précharger', 'warning')
    return
  }

  try {
    ui.addToast('🚀 Préchargement...', 'info')
    await client.preloadOfflineData()
    ui.addToast('✅ Données préchargées', 'success')
  } catch (error) {
    ui.addToast('⚠️ Échec préchargement', 'warning')
  }
}

// Watchers
watch(selectedDepartment, () => {
  agents.value = []
  agentsLoaded.value = false
  pointageData.value = {}
})

// Initialisation
onMounted(async () => {
  initNetworkListener()
  await fetchDepartments()
  updateSyncStats()

  // Stats périodiques
  const statsInterval = setInterval(updateSyncStats, 10000)

  // Cleanup
  const cleanup = () => clearInterval(statsInterval)
  router.beforeEach(cleanup)
  window.addEventListener('beforeunload', cleanup)
})
</script>

<style scoped>
.pointage-table th, .pointage-table td { vertical-align: middle; }
.pointage-table input[type="time"] { min-width: 120px; }
.pointage-table input[type="text"] { min-width: 120px; }
.agent-name { font-weight: 600; white-space: nowrap; }
.agent-poste { font-size: 0.85em; color: #6c757d; }
.table-success { background-color: #f0fdf4; }
.btn-rh {
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
}
.btn-rh.alt {
  background: #6c757d;
}
.btn-rh:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* ── Mobile responsive ── */
@media (max-width: 767.98px) {
  .pointage-table th, .pointage-table td {
    padding: 0.4rem 0.35rem;
    font-size: 0.82rem;
  }
  .pointage-table input[type="time"] {
    min-width: 90px;
    font-size: 0.85rem;
    padding: 0.25rem 0.4rem;
  }
  .pointage-table input[type="text"] {
    min-width: 90px;
    font-size: 0.85rem;
    padding: 0.25rem 0.4rem;
  }
  .agent-name {
    white-space: normal;
    font-size: 0.85rem;
  }
  .agent-poste { font-size: 0.75em; }
  /* Masquer Heures et Observation */
  .pointage-table th:nth-child(5), .pointage-table td:nth-child(5),
  .pointage-table th:nth-child(6), .pointage-table td:nth-child(6) {
    display: none;
  }
}
</style>