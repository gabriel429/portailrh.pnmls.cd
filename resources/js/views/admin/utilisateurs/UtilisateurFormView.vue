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
            <label for="agent_id" class="form-label">Agent <span class="text-danger">*</span></label>
            <select id="agent_id" v-model="form.agent_id" class="form-select" :class="{ 'is-invalid': validationErrors.agent_id }" required>
              <option value="">-- Selectionner un agent --</option>
              <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                {{ agent.nom }} {{ agent.postnom || '' }} {{ agent.prenom || '' }}
              </option>
            </select>
          </div>

          <!-- Agent info (edit mode - read only) -->
          <div v-if="isEdit && editUser" class="mb-3">
            <label class="form-label">Agent</label>
            <input type="text" class="form-control" :value="editUser.agent ? `${editUser.agent.nom} ${editUser.agent.postnom || ''} ${editUser.agent.prenom || ''}`.trim() : editUser.name" disabled>
          </div>

          <!-- Role -->
          <div class="mb-3">
            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
            <select id="role_id" v-model="form.role_id" class="form-select" :class="{ 'is-invalid': validationErrors.role_id }" required>
              <option value="">-- Selectionner un role --</option>
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
              {{ isEdit ? 'Mettre a jour' : 'Creer' }}
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

const form = ref({
  agent_id: '',
  role_id: '',
  password: '',
  password_confirmation: '',
})

async function fetchFormData() {
  loadingData.value = true
  error.value = null
  try {
    const response = await client.get('/admin/utilisateurs/form-data')
    agents.value = response.data.agents || []
    roles.value = response.data.roles || []
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement des donnees.'
  } finally {
    loadingData.value = false
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

onMounted(() => {
  if (isEdit.value) {
    fetchUtilisateur()
  } else {
    fetchFormData()
  }
})
</script>
