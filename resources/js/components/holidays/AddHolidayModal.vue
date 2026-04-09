<template>
  <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">
            <i class="fas fa-user-clock me-2"></i>
            Planifier les congés
            <template v-if="isProvincial">— {{ scopeInfo.province_nom }}</template>
            <template v-else>par département / service</template>
          </h5>
          <button type="button" class="btn-close btn-close-white" @click="$emit('close')"></button>
        </div>

        <div class="modal-body">
          <!-- Sélecteurs : National = Département obligatoire, Provincial = auto-chargement -->
          <div class="row g-3 mb-4" v-if="!isProvincial">
            <div class="col-md-5">
              <label class="form-label fw-bold">Département / Service <span class="text-danger">*</span></label>
              <select class="form-select" v-model="selectedDepartment" :disabled="loadingAgents">
                <option value="">-- Sélectionner un département --</option>
                <option v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                  {{ dept.nom }}
                </option>
              </select>
            </div>
            <div class="col-md-4" v-if="plannings.length > 0">
              <label class="form-label fw-bold">Planning lié</label>
              <select class="form-select" v-model="selectedPlanningId">
                <option value="">-- Aucun --</option>
                <option v-for="p in plannings" :key="p.id" :value="p.id">
                  {{ p.nom_structure || p.type_structure }} — {{ p.annee }}
                </option>
              </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button
                type="button"
                class="btn btn-primary w-100"
                @click="loadAgents"
                :disabled="!selectedDepartment || loadingAgents"
              >
                <span v-if="loadingAgents" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-search me-1"></i>
                Charger les agents
              </button>
            </div>
          </div>
          <!-- Provincial : planning lié uniquement -->
          <div class="row g-3 mb-4" v-if="isProvincial && plannings.length > 0">
            <div class="col-md-6">
              <label class="form-label fw-bold">Planning lié</label>
              <select class="form-select" v-model="selectedPlanningId">
                <option value="">-- Aucun --</option>
                <option v-for="p in plannings" :key="p.id" :value="p.id">
                  {{ p.nom_structure || p.type_structure }} — {{ p.annee }}
                </option>
              </select>
            </div>
          </div>

          <!-- Tableau des agents -->
          <div v-if="agentsLoaded">
            <div v-if="agents.length === 0" class="text-center text-muted py-4">
              <i class="fas fa-users-slash fa-2x mb-2 d-block"></i>
              Aucun agent actif {{ isProvincial ? 'dans cette province' : 'dans ce département' }}
            </div>

            <div v-else>
              <!-- Sélection globale + compteur -->
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="selectAll" v-model="selectAll" @change="toggleSelectAll">
                  <label class="form-check-label fw-medium" for="selectAll">
                    Tout sélectionner ({{ selectedCount }}/{{ agents.length }})
                  </label>
                </div>
                <div class="d-flex gap-2 align-items-center">
                  <label class="form-label mb-0 small fw-medium">Type par défaut :</label>
                  <select class="form-select form-select-sm" style="width: 160px" v-model="defaultType" @change="applyDefaultType">
                    <option value="annuel">Congé annuel</option>
                    <option value="maladie">Congé maladie</option>
                    <option value="maternite">Congé maternité</option>
                    <option value="paternite">Congé paternité</option>
                    <option value="urgence">Congé d'urgence</option>
                    <option value="special">Congé spécial</option>
                  </select>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                  <thead class="table-light">
                    <tr>
                      <th style="width:35px"></th>
                      <th>Agent</th>
                      <th>Fonction</th>
                      <th style="width:140px">Date début</th>
                      <th style="width:140px">Date fin</th>
                      <th style="width:60px">Jours</th>
                      <th style="width:130px">Type</th>
                      <th>Observation</th>
                      <th style="width:180px">Intérim par</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="agent in agents"
                      :key="agent.id"
                      :class="{
                        'table-success': agent.selected && agent.date_debut && agent.date_fin,
                        'table-warning': agent.conge_existant?.length > 0
                      }"
                    >
                      <td>
                        <input type="checkbox" class="form-check-input" v-model="agent.selected">
                      </td>
                      <td class="fw-medium">{{ agent.nom_complet }}</td>
                      <td class="small text-muted">{{ agent.fonction || '-' }}</td>
                      <td>
                        <input type="date" class="form-control form-control-sm" v-model="agent.date_debut" :disabled="!agent.selected">
                      </td>
                      <td>
                        <input type="date" class="form-control form-control-sm" v-model="agent.date_fin" :min="agent.date_debut" :disabled="!agent.selected">
                      </td>
                      <td class="text-center">
                        <span v-if="agentDuree(agent) > 0" class="badge bg-info">{{ agentDuree(agent) }}</span>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td>
                        <select class="form-select form-select-sm" v-model="agent.type_conge" :disabled="!agent.selected">
                          <option value="annuel">Annuel</option>
                          <option value="maladie">Maladie</option>
                          <option value="maternite">Maternité</option>
                          <option value="paternite">Paternité</option>
                          <option value="urgence">Urgence</option>
                          <option value="special">Spécial</option>
                        </select>
                      </td>
                      <td>
                        <input type="text" class="form-control form-control-sm" v-model="agent.observation" placeholder="..." :disabled="!agent.selected">
                      </td>
                      <td>
                        <select class="form-select form-select-sm" v-model="agent.interim_par" :disabled="!agent.selected">
                          <option value="">--</option>
                          <option v-for="a in interimOptions(agent.id)" :key="a.id" :value="a.id">
                            {{ a.nom_complet }}
                          </option>
                        </select>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Alerte congés existants -->
              <div v-if="agentsWithExisting.length > 0" class="alert alert-warning mt-2 py-2 small">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>{{ agentsWithExisting.length }} agent(s)</strong> ont déjà un congé planifié cette année
                <span class="text-muted">(lignes en jaune)</span>
              </div>
            </div>
          </div>

          <!-- Erreurs -->
          <div v-if="globalError" class="alert alert-danger mt-3 mb-0">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ globalError }}
          </div>
          <div v-if="serverErrors.length > 0" class="alert alert-warning mt-3 mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <ul class="mb-0 ps-3">
              <li v-for="(err, i) in serverErrors" :key="i">{{ err }}</li>
            </ul>
          </div>
        </div>

        <div class="modal-footer">
          <div class="me-auto small text-muted" v-if="readyCount > 0">
            <i class="fas fa-check-circle text-success me-1"></i>
            {{ readyCount }} congé(s) prêt(s) à planifier
          </div>
          <button type="button" class="btn btn-secondary" @click="$emit('close')" :disabled="submitting">
            Annuler
          </button>
          <button type="button" class="btn btn-primary" @click="submitBatch" :disabled="submitting || readyCount === 0">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-save me-1"></i>
            Planifier {{ readyCount }} congé(s)
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const props = defineProps({
  show: { type: Boolean, required: true },
  departments: { type: Array, default: () => [] },
  plannings: { type: Array, default: () => [] },
  year: { type: [Number, String], default: () => new Date().getFullYear() },
  scopeInfo: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['close', 'created'])

const ui = useUiStore()
const submitting = ref(false)
const loadingAgents = ref(false)
const agentsLoaded = ref(false)
const globalError = ref('')
const serverErrors = ref([])
const agents = ref([])
const selectAll = ref(true)
const defaultType = ref('annuel')
const selectedDepartment = ref('')
const selectedPlanningId = ref('')

const isProvincial = computed(() => !!props.scopeInfo?.is_provincial)

// RH National : uniquement départements nationaux (province_id null)
const filteredDepartments = computed(() => {
  if (isProvincial.value) return props.departments
  return props.departments.filter(d => !d.province_id)
})

// Provincial : charger tous les agents de la province à l'ouverture
onMounted(() => {
  if (isProvincial.value) {
    loadAgents()
  }
})

// Charger les agents
async function loadAgents() {
  if (!isProvincial.value && !selectedDepartment.value) return
  loadingAgents.value = true
  globalError.value = ''
  agents.value = []

  try {
    const params = { year: props.year }
    if (selectedDepartment.value) params.department_id = selectedDepartment.value
    const { data } = await client.get('/holidays/agents-by-structure', { params })

    agents.value = (data.agents || []).map(a => ({
      ...a,
      selected: true,
      date_debut: '',
      date_fin: '',
      type_conge: defaultType.value,
      observation: '',
      interim_par: '',
    }))
    agentsLoaded.value = true
  } catch (error) {
    globalError.value = 'Erreur lors du chargement des agents'
  } finally {
    loadingAgents.value = false
  }
}

// Calculer la durée pour un agent
function agentDuree(agent) {
  if (!agent.date_debut || !agent.date_fin) return 0
  const s = new Date(agent.date_debut)
  const e = new Date(agent.date_fin)
  if (e < s) return 0
  return Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + 1
}

// Options intérim : tous les agents du même département sauf l'agent lui-même
function interimOptions(agentId) {
  return agents.value.filter(a => a.id !== agentId)
}

// Sélectionner / désélectionner tout
function toggleSelectAll() {
  agents.value.forEach(a => { a.selected = selectAll.value })
}

// Appliquer le type par défaut à tous les agents sélectionnés
function applyDefaultType() {
  agents.value.forEach(a => {
    if (a.selected) a.type_conge = defaultType.value
  })
}

const selectedCount = computed(() => agents.value.filter(a => a.selected).length)

const readyCount = computed(() =>
  agents.value.filter(a => a.selected && a.date_debut && a.date_fin).length
)

const agentsWithExisting = computed(() =>
  agents.value.filter(a => a.conge_existant?.length > 0)
)

// Soumission en lot
async function submitBatch() {
  const entries = agents.value
    .filter(a => a.selected && a.date_debut && a.date_fin)
    .map(a => ({
      agent_id: a.id,
      date_debut: a.date_debut,
      date_fin: a.date_fin,
      type_conge: a.type_conge,
      observation: a.observation || null,
      interim_assure_par: a.interim_par || null,
    }))

  if (entries.length === 0) return

  submitting.value = true
  globalError.value = ''
  serverErrors.value = []

  try {
    const { data } = await client.post('/holidays/batch', {
      entries,
      holiday_planning_id: selectedPlanningId.value || null,
    })

    if (data.errors?.length > 0) {
      serverErrors.value = data.errors
    }

    if (data.created_count > 0) {
      ui.addToast(data.message, data.error_count > 0 ? 'warning' : 'success')
      emit('created')
    } else {
      globalError.value = data.message
    }
  } catch (error) {
    if (error.response?.status === 422) {
      globalError.value = error.response.data.message || 'Erreur de validation'
    } else {
      globalError.value = 'Erreur lors de la planification des congés'
    }
  } finally {
    submitting.value = false
  }
}
</script>
