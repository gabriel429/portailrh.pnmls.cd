<template>
  <div class="py-4">
    <div class="d-flex align-items-center mb-4">
      <router-link :to="{ name: 'admin.utilisateurs.index' }" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
      </router-link>
      <h4 class="mb-0">{{ isEdit ? 'Modifier l\'Utilisateur' : 'Nouvel Utilisateur' }}</h4>
    </div>

    <div v-if="loadingData" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>

    <div v-else class="card shadow-sm">
      <div class="card-body">
        <div v-if="error" class="alert alert-danger alert-dismissible fade show">
          {{ error }}
          <button type="button" class="btn-close" @click="error = null"></button>
        </div>

        <div v-if="Object.keys(validationErrors).length" class="alert alert-danger">
          <ul class="mb-0">
            <li v-for="(messages, field) in validationErrors" :key="field">
              {{ messages.join(', ') }}
            </li>
          </ul>
        </div>

        <form @submit.prevent="submit">
          <!-- Agent (create only) -->
          <div v-if="!isEdit" class="mb-3">
            <label for="agent_search" class="form-label">Agent <span class="text-danger">*</span></label>
            <div class="agent-picker" :class="{ 'is-invalid': validationErrors.agent_id }">
              <div class="agent-search-control">
                <i class="fas fa-search"></i>
                <input
                  id="agent_search"
                  v-model="agentSearch"
                  type="search"
                  class="form-control"
                  placeholder="Rechercher par nom, matricule, fonction, organe..."
                  autocomplete="off"
                  @input="onAgentSearchInput"
                >
                <button v-if="agentSearch" type="button" class="agent-clear-btn" title="Effacer" @click="clearAgentSearch">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <div v-if="selectedAgent" class="selected-agent-card">
                <div class="agent-avatar">{{ agentInitials(selectedAgent) }}</div>
                <div class="selected-agent-info">
                  <strong>{{ agentName(selectedAgent) }}</strong>
                  <span>{{ agentMeta(selectedAgent) }}</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearSelectedAgent">
                  Changer
                </button>
              </div>

              <div class="agent-results">
                <div v-if="loadingAgents" class="agent-results-state">
                  <span class="spinner-border spinner-border-sm me-2"></span>Recherche des agents...
                </div>
                <template v-else>
                  <button
                    v-for="agent in agents"
                    :key="agent.id"
                    type="button"
                    class="agent-option"
                    :class="{ active: String(form.agent_id) === String(agent.id) }"
                    @click="selectAgent(agent)"
                  >
                    <div class="agent-avatar">{{ agentInitials(agent) }}</div>
                    <div class="agent-option-info">
                      <strong>{{ agentName(agent) }}</strong>
                      <span>{{ agentMeta(agent) }}</span>
                    </div>
                    <i v-if="String(form.agent_id) === String(agent.id)" class="fas fa-check-circle"></i>
                  </button>
                </template>
                <div v-if="!loadingAgents && agents.length === 0" class="agent-results-state">
                  Aucun agent sans compte utilisateur ne correspond à cette recherche.
                </div>
              </div>
            </div>
            <div v-if="validationErrors.agent_id" class="invalid-feedback d-block">
              {{ validationErrors.agent_id.join(', ') }}
            </div>
            <small class="form-text text-muted">
              Tapez au moins quelques lettres pour retrouver rapidement l'agent à associer au compte.
            </small>
          </div>

          <!-- Agent info (edit mode - read only) -->
          <div v-if="isEdit && editUser" class="mb-3">
            <label class="form-label">Agent</label>
            <input type="text" class="form-control" :value="editUser.agent ? `${editUser.agent.nom} ${editUser.agent.postnom || ''} ${editUser.agent.prenom || ''}`.trim() : editUser.name" disabled>
          </div>

          <!-- Role -->
          <div class="mb-3">
            <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
            <select id="role_id" v-model="form.role_id" class="form-select" :class="{ 'is-invalid': validationErrors.role_id }" required>
              <option value="">-- Sélectionner un rôle --</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">
                {{ role.nom_role }}
              </option>
            </select>
          </div>

          <!-- Password -->
          <div class="mb-3">
            <label for="password" class="form-label">
              Mot de passe
              <span v-if="!isEdit" class="text-danger">*</span>
              <small v-if="isEdit" class="text-muted">(laisser vide pour ne pas modifier)</small>
            </label>
            <input type="password" id="password" v-model="form.password" class="form-control" :class="{ 'is-invalid': validationErrors.password }" :required="!isEdit">
          </div>

          <!-- Password confirmation -->
          <div class="mb-3">
            <label for="password_confirmation" class="form-label">
              Confirmer le mot de passe
              <span v-if="!isEdit" class="text-danger">*</span>
            </label>
            <input type="password" id="password_confirmation" v-model="form.password_confirmation" class="form-control" :required="!isEdit">
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
              {{ isEdit ? 'Mettre à jour' : 'Créer' }}
            </button>
            <router-link :to="{ name: 'admin.utilisateurs.index' }" class="btn btn-secondary">Annuler</router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import client from '@/api/client'

const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const loadingData = ref(false)
const submitting = ref(false)
const error = ref(null)
const validationErrors = ref({})

const agents = ref([])
const roles = ref([])
const editUser = ref(null)
const agentSearch = ref('')
const selectedAgent = ref(null)
const loadingAgents = ref(false)
let agentSearchTimeout = null

const form = ref({
  agent_id: '',
  role_id: '',
  password: '',
  password_confirmation: '',
})

async function fetchFormData(searchTerm = '', refreshAgentsOnly = false) {
  const isSearchRefresh = refreshAgentsOnly
  if (isSearchRefresh) {
    loadingAgents.value = true
  } else {
    loadingData.value = true
  }
  error.value = null
  try {
    const response = await client.get('/admin/utilisateurs/form-data', {
      params: {
        agent_search: searchTerm || undefined,
        agent_limit: 50,
      },
    })
    agents.value = response.data.agents || []
    roles.value = response.data.roles || []
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement des donnees.'
  } finally {
    loadingData.value = false
    loadingAgents.value = false
  }
}

async function fetchUtilisateur() {
  loadingData.value = true
  error.value = null
  try {
    const [userResponse, formDataResponse] = await Promise.all([
      client.get(`/admin/utilisateurs/${route.params.id}`),
      client.get('/admin/utilisateurs/form-data'),
    ])
    const user = userResponse.data.data || userResponse.data
    editUser.value = user
    roles.value = formDataResponse.data.roles || []
    form.value = {
      agent_id: user.agent_id || '',
      role_id: user.role_id || '',
      password: '',
      password_confirmation: '',
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement de l\'utilisateur.'
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  submitting.value = true
  error.value = null
  validationErrors.value = {}

  const payload = { ...form.value }
  if (!isEdit.value && !payload.agent_id) {
    validationErrors.value = { agent_id: ['Veuillez sélectionner un agent.'] }
    submitting.value = false
    return
  }

  if (isEdit.value && !payload.password) {
    delete payload.password
    delete payload.password_confirmation
  }

  try {
    if (isEdit.value) {
      await client.put(`/admin/utilisateurs/${route.params.id}`, payload)
    } else {
      await client.post('/admin/utilisateurs', payload)
    }
    router.push({ name: 'admin.utilisateurs.index' })
  } catch (e) {
    if (e.response?.status === 422) {
      validationErrors.value = e.response.data.errors || {}
    } else {
      error.value = e.response?.data?.message || 'Erreur lors de l\'enregistrement.'
    }
  } finally {
    submitting.value = false
  }
}

function onAgentSearchInput() {
  clearTimeout(agentSearchTimeout)
  agentSearchTimeout = setTimeout(() => {
    fetchFormData(agentSearch.value.trim(), true)
  }, 350)
}

function clearAgentSearch() {
  clearTimeout(agentSearchTimeout)
  agentSearch.value = ''
  fetchFormData('', true)
}

function selectAgent(agent) {
  selectedAgent.value = agent
  form.value.agent_id = agent.id
  const nextErrors = { ...validationErrors.value }
  delete nextErrors.agent_id
  validationErrors.value = nextErrors
}

function clearSelectedAgent() {
  selectedAgent.value = null
  form.value.agent_id = ''
}

function agentName(agent) {
  return [agent.nom, agent.postnom, agent.prenom].filter(Boolean).join(' ').trim() || `Agent #${agent.id}`
}

function agentInitials(agent) {
  const first = (agent.prenom || agent.nom || 'A').trim().charAt(0)
  const second = (agent.nom || agent.postnom || '').trim().charAt(0)
  return `${first}${second}`.toUpperCase()
}

function agentMeta(agent) {
  const structure = agent.localite?.nom || agent.province?.nom || agent.departement?.nom || agent.organe
  return [agent.matricule_etat, agent.fonction || agent.poste_actuel, structure].filter(Boolean).join(' · ')
}

onMounted(() => {
  if (isEdit.value) {
    fetchUtilisateur()
  } else {
    fetchFormData()
  }
})
</script>

<style scoped>
.agent-picker {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: .75rem;
  background: #f8fafc;
}

.agent-picker.is-invalid {
  border-color: #dc3545;
}

.agent-search-control {
  position: relative;
}

.agent-search-control > i {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: .85rem;
  z-index: 1;
}

.agent-search-control input {
  padding-left: 2.35rem;
  padding-right: 2.35rem;
}

.agent-clear-btn {
  position: absolute;
  right: .45rem;
  top: 50%;
  transform: translateY(-50%);
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 8px;
  background: #eef2f7;
  color: #64748b;
}

.selected-agent-card,
.agent-option {
  display: flex;
  align-items: center;
  gap: .75rem;
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  padding: .65rem .75rem;
}

.selected-agent-card {
  margin-top: .7rem;
  border-color: #059669;
  background: #ecfdf5;
}

.selected-agent-info,
.agent-option-info {
  min-width: 0;
  flex: 1;
  text-align: left;
}

.selected-agent-info strong,
.agent-option-info strong {
  display: block;
  color: #1e293b;
  font-size: .9rem;
}

.selected-agent-info span,
.agent-option-info span {
  display: block;
  color: #64748b;
  font-size: .76rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.agent-avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #dbeafe;
  color: #1d4ed8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .75rem;
  font-weight: 800;
  flex-shrink: 0;
}

.agent-results {
  display: flex;
  flex-direction: column;
  gap: .45rem;
  max-height: 320px;
  overflow-y: auto;
  margin-top: .7rem;
}

.agent-option {
  cursor: pointer;
  transition: border-color .15s, background .15s, transform .15s;
}

.agent-option:hover,
.agent-option.active {
  border-color: #059669;
  background: #f0fdf4;
}

.agent-option:hover {
  transform: translateY(-1px);
}

.agent-option > i {
  color: #059669;
  flex-shrink: 0;
}

.agent-results-state {
  color: #64748b;
  background: #fff;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  padding: .9rem;
  text-align: center;
  font-size: .85rem;
}

@media (max-width: 575.98px) {
  .selected-agent-card,
  .agent-option {
    align-items: flex-start;
  }

  .selected-agent-card {
    flex-wrap: wrap;
  }
}
</style>
