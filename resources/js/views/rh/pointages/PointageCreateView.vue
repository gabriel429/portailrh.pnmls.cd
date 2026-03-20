<template>
  <div class="rh-modern">
    <div class="rh-list-shell">
      <section class="rh-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-clipboard-check me-2"></i>Saisie des pointages</h1>
            <p class="rh-sub">Saisie groupee par departement/service. Selectionnez un departement pour afficher ses agents.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'rh.pointages.index' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour liste
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div class="rh-list-card p-3 p-lg-4">
        <!-- Step 1: Select date and department -->
        <div class="row g-3 mb-4">
          <div class="col-md-5">
            <label for="department_id" class="form-label fw-bold">Departement / Service</label>
            <select class="form-select" id="department_id" v-model="selectedDepartment">
              <option value="">-- Selectionner un departement --</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.nom }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="date_pointage" class="form-label fw-bold">Date du pointage</label>
            <input type="date" class="form-control" id="date_pointage" v-model="datePointage">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-primary w-100" @click="loadAgents" :disabled="loadingAgents">
              <span v-if="loadingAgents" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-search me-1"></i> Charger
            </button>
          </div>
        </div>

        <!-- Loading state -->
        <div v-if="loadingAgents" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
          <p class="mt-2 text-muted">Chargement des agents...</p>
        </div>

        <!-- Step 2: Agents table (shown after loading) -->
        <div v-else-if="agentsLoaded && agents.length > 0">
          <!-- Alert/info bar -->
          <div class="alert alert-info py-2 mb-3">
            <i class="fas fa-info-circle me-1"></i>
            <strong>{{ selectedDepartmentName }}</strong> &mdash;
            {{ agents.length }} agent(s).
            <span v-if="recordedCount > 0" class="badge bg-success ms-2">{{ recordedCount }} deja pointe(s)</span>
            <br><small class="text-muted">Les heures existantes sont pre-remplies. Vous pouvez completer les heures de sortie et re-enregistrer.</small>
          </div>

          <!-- Validation errors -->
          <div v-if="errors.length > 0" class="alert alert-danger">
            <strong>Erreurs de validation</strong>
            <ul class="mb-0 mt-2">
              <li v-for="(error, i) in errors" :key="i">{{ error }}</li>
            </ul>
          </div>

          <div class="table-responsive">
            <table class="table table-hover pointage-table">
              <thead class="table-light">
                <tr>
                  <th style="width: 5%">
                    <input type="checkbox" class="form-check-input" v-model="selectAll" @change="toggleSelectAll">
                  </th>
                  <th style="width: 25%">Agent</th>
                  <th style="width: 20%">Arrivee (Avant-midi)</th>
                  <th style="width: 20%">Sortie (Apres-midi)</th>
                  <th style="width: 15%">Heures</th>
                  <th style="width: 15%">Observation</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(agent, index) in agents" :key="agent.id" :class="{ 'row-recorded': agent.pointage_existant }">
                  <td>
                    <input type="checkbox" class="form-check-input" v-model="agent.selected">
                  </td>
                  <td>
                    <div class="agent-name">
                      {{ agent.prenom }} {{ agent.nom }}
                      <span v-if="agent.pointage_existant" class="badge bg-success ms-1" title="Pointage deja enregistre">
                        <i class="fas fa-check"></i>
                      </span>
                    </div>
                    <div class="agent-poste">{{ agent.poste_actuel || '' }}</div>
                  </td>
                  <td>
                    <input type="time" class="form-control" v-model="agent.heure_entree">
                  </td>
                  <td>
                    <input type="time" class="form-control" v-model="agent.heure_sortie">
                  </td>
                  <td>
                    <span class="text-muted">{{ calculateHours(agent) }}</span>
                  </td>
                  <td>
                    <input type="text" class="form-control" v-model="agent.observations" placeholder="Observation...">
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Action buttons -->
          <div class="d-flex gap-2 mt-3 flex-wrap">
            <button type="button" class="btn btn-primary btn-lg" @click="submitPointages" :disabled="submitting">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-save me-2"></i>Enregistrer les pointages
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="fillAll">
              <i class="fas fa-clock me-1"></i> Remplir tout (08:00 - 16:00)
            </button>
            <button type="button" class="btn btn-outline-danger" @click="clearAll">
              <i class="fas fa-eraser me-1"></i> Tout effacer
            </button>
          </div>
        </div>

        <!-- No agents found -->
        <div v-else-if="agentsLoaded && agents.length === 0" class="text-center py-5">
          <i class="fas fa-users-slash fa-3x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Aucun agent actif dans ce departement</h5>
        </div>

        <!-- Initial state -->
        <div v-else class="text-center py-5">
          <i class="fas fa-building fa-3x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Selectionnez un departement</h5>
          <p class="text-muted">Choisissez un departement et une date, puis cliquez sur "Charger" pour afficher les agents.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import * as pointagesApi from '@/api/pointages'
import client from '@/api/client'

const router = useRouter()
const ui = useUiStore()

const departments = ref([])
const selectedDepartment = ref('')
const datePointage = ref(new Date().toISOString().split('T')[0])
const agents = ref([])
const agentsLoaded = ref(false)
const loadingAgents = ref(false)
const submitting = ref(false)
const selectAll = ref(false)
const errors = ref([])

const selectedDepartmentName = computed(() => {
    const dept = departments.value.find(d => d.id == selectedDepartment.value)
    return dept ? dept.nom : ''
})

const recordedCount = computed(() => {
    return agents.value.filter(a => a.pointage_existant).length
})

function calculateHours(agent) {
    if (!agent.heure_entree || !agent.heure_sortie) return '-'
    const entree = new Date(`2000-01-01T${agent.heure_entree}`)
    const sortie = new Date(`2000-01-01T${agent.heure_sortie}`)
    const diff = (sortie - entree) / (1000 * 60 * 60)
    if (diff > 0) return diff.toFixed(1) + 'h'
    return '-'
}

function toggleSelectAll() {
    agents.value.forEach(a => { a.selected = selectAll.value })
}

function fillAll() {
    agents.value.forEach(agent => {
        if (!agent.heure_entree) agent.heure_entree = '08:00'
        if (!agent.heure_sortie) agent.heure_sortie = '16:00'
    })
}

function clearAll() {
    agents.value.forEach(agent => {
        agent.heure_entree = ''
        agent.heure_sortie = ''
        agent.observations = ''
    })
}

async function loadAgents() {
    if (!selectedDepartment.value) {
        ui.addToast('Veuillez selectionner un departement.', 'warning')
        return
    }
    if (!datePointage.value) {
        ui.addToast('Veuillez selectionner une date.', 'warning')
        return
    }

    loadingAgents.value = true
    agentsLoaded.value = false
    errors.value = []

    try {
        const { data } = await pointagesApi.agentsByDepartment({
            department_id: selectedDepartment.value,
            date: datePointage.value,
        })

        agents.value = data.map(agent => ({
            ...agent,
            selected: true,
            heure_entree: agent.pointage_existant?.heure_entree || '',
            heure_sortie: agent.pointage_existant?.heure_sortie || '',
            observations: agent.pointage_existant?.observations || '',
        }))

        agentsLoaded.value = true
    } catch {
        ui.addToast('Erreur lors du chargement des agents.', 'danger')
    } finally {
        loadingAgents.value = false
    }
}

async function submitPointages() {
    errors.value = []

    // Build pointages array from agents that have times entered
    const pointagesData = agents.value
        .filter(a => a.heure_entree || a.heure_sortie)
        .map(a => ({
            agent_id: a.id,
            heure_entree: a.heure_entree || null,
            heure_sortie: a.heure_sortie || null,
            observations: a.observations || null,
        }))

    if (pointagesData.length === 0) {
        ui.addToast('Aucun pointage a enregistrer. Veuillez saisir au moins une heure.', 'warning')
        return
    }

    submitting.value = true
    try {
        const { data } = await pointagesApi.storeBulk({
            date_pointage: datePointage.value,
            pointages: pointagesData,
        })
        ui.addToast(data.message, 'success')
        router.push({ name: 'rh.pointages.index' })
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            const validationErrors = err.response.data.errors
            errors.value = Object.values(validationErrors).flat()
        } else {
            ui.addToast('Erreur lors de l\'enregistrement des pointages.', 'danger')
        }
    } finally {
        submitting.value = false
    }
}

async function fetchDepartments() {
    try {
        const { data } = await client.get('/agents/form-options')
        departments.value = data.departments || []
    } catch {
        // Fallback if form-options not available
    }
}

onMounted(() => {
    fetchDepartments()
})
</script>

<style scoped>
.pointage-table th, .pointage-table td { vertical-align: middle; }
.pointage-table input[type="time"] { min-width: 120px; }
.pointage-table input[type="text"] { min-width: 120px; }
.agent-name { font-weight: 600; white-space: nowrap; }
.agent-poste { font-size: 0.85em; color: #6c757d; }
.row-recorded { background-color: #f0fdf4; }
</style>
