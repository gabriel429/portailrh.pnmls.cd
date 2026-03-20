<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="fas fa-layer-group me-2"></i>
          {{ isEdit ? 'Modifier la section' : 'Nouvelle section' }}
        </h4>
        <p class="text-muted mb-0">
          {{ isEdit ? 'Modifiez les informations de cette section.' : 'Remplissez le formulaire pour creer une nouvelle section.' }}
        </p>
      </div>
      <router-link to="/admin/sections" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Retour
      </router-link>
    </div>

    <!-- Loading -->
    <div v-if="loadingData" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement...</p>
    </div>

    <!-- Error -->
    <div v-else-if="loadError" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <!-- Form -->
    <template v-else>
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <form @submit.prevent="submit">
            <!-- Validation errors -->
            <div v-if="errors.length" class="alert alert-danger">
              <ul class="mb-0">
                <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
              </ul>
            </div>

            <div class="mb-3">
              <label for="code" class="form-label fw-bold">Code <span class="text-danger">*</span></label>
              <input
                id="code"
                v-model="form.code"
                type="text"
                class="form-control"
                required
                placeholder="Ex: SEC-RH"
              >
            </div>

            <div class="mb-3">
              <label for="nom" class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
              <input
                id="nom"
                v-model="form.nom"
                type="text"
                class="form-control"
                required
                placeholder="Ex: Section Ressources Humaines"
              >
            </div>

            <div class="mb-3">
              <label for="description" class="form-label fw-bold">Description</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control"
                rows="3"
                placeholder="Description de la section..."
              ></textarea>
            </div>

            <div class="mb-3">
              <label for="type" class="form-label fw-bold">Type <span class="text-danger">*</span></label>
              <select id="type" v-model="form.type" class="form-select" required>
                <option value="">-- Selectionnez --</option>
                <option value="section">Section</option>
                <option value="service_rattache">Service rattache</option>
              </select>
            </div>

            <div class="mb-4">
              <label for="department_id" class="form-label fw-bold">Departement</label>
              <select id="department_id" v-model="form.department_id" class="form-select">
                <option value="">-- Aucun departement --</option>
                <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-save me-1"></i>
                {{ isEdit ? 'Mettre a jour' : 'Enregistrer' }}
              </button>
              <router-link to="/admin/sections" class="btn btn-outline-secondary">Annuler</router-link>
            </div>
          </form>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import client from '@/api/client'

const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const loadingData = ref(false)
const loadError = ref('')
const saving = ref(false)
const errors = ref([])
const departments = ref([])

const form = reactive({
  code: '',
  nom: '',
  description: '',
  type: '',
  department_id: '',
})

async function fetchDepartments() {
  try {
    const { data } = await client.get('/admin/departments')
    departments.value = data.data || data || []
  } catch (e) {
    console.error('Erreur chargement departements:', e)
  }
}

async function loadSection() {
  loadingData.value = true
  loadError.value = ''
  try {
    const { data } = await client.get('/admin/sections/' + route.params.id)
    const s = data.data || data
    form.code = s.code || ''
    form.nom = s.nom || ''
    form.description = s.description || ''
    form.type = s.type || ''
    form.department_id = s.department_id || ''
  } catch (e) {
    console.error('Erreur chargement section:', e)
    loadError.value = 'Impossible de charger cette section.'
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = []
  try {
    if (isEdit.value) {
      await client.put('/admin/sections/' + route.params.id, form)
    } else {
      await client.post('/admin/sections', form)
    }
    router.push('/admin/sections')
  } catch (e) {
    console.error('Erreur sauvegarde:', e)
    if (e.response?.status === 422 && e.response.data?.errors) {
      const serverErrors = e.response.data.errors
      errors.value = Object.values(serverErrors).flat()
    } else {
      errors.value = ['Une erreur est survenue lors de la sauvegarde.']
    }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await fetchDepartments()
  if (isEdit.value) {
    await loadSection()
  }
})
</script>
