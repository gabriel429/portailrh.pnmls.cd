<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="fas fa-th me-2"></i>
          {{ isEdit ? 'Modifier la cellule' : 'Nouvelle cellule' }}
        </h4>
        <p class="text-muted mb-0">
          {{ isEdit ? 'Modifiez les informations de cette cellule.' : 'Remplissez le formulaire pour creer une nouvelle cellule.' }}
        </p>
      </div>
      <router-link to="/admin/cellules" class="btn btn-outline-secondary">
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
                placeholder="Ex: CEL-001"
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
                placeholder="Ex: Cellule Suivi-Evaluation"
              >
            </div>

            <div class="mb-3">
              <label for="description" class="form-label fw-bold">Description</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control"
                rows="3"
                placeholder="Description de la cellule..."
              ></textarea>
            </div>

            <div class="mb-4">
              <label for="section_id" class="form-label fw-bold">Section <span class="text-danger">*</span></label>
              <select id="section_id" v-model="form.section_id" class="form-select" required>
                <option value="">-- Selectionnez une section --</option>
                <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.nom }}</option>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-save me-1"></i>
                {{ isEdit ? 'Mettre a jour' : 'Enregistrer' }}
              </button>
              <router-link to="/admin/cellules" class="btn btn-outline-secondary">Annuler</router-link>
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
const sections = ref([])

const form = reactive({
  code: '',
  nom: '',
  description: '',
  section_id: '',
})

async function fetchSections() {
  try {
    const { data } = await client.get('/admin/sections')
    sections.value = data.data || data || []
  } catch (e) {
    console.error('Erreur chargement sections:', e)
  }
}

async function loadCellule() {
  loadingData.value = true
  loadError.value = ''
  try {
    const { data } = await client.get('/admin/cellules/' + route.params.id)
    const c = data.data || data
    form.code = c.code || ''
    form.nom = c.nom || ''
    form.description = c.description || ''
    form.section_id = c.section_id || ''
  } catch (e) {
    console.error('Erreur chargement cellule:', e)
    loadError.value = 'Impossible de charger cette cellule.'
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = []
  try {
    if (isEdit.value) {
      await client.put('/admin/cellules/' + route.params.id, form)
    } else {
      await client.post('/admin/cellules', form)
    }
    router.push('/admin/cellules')
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
  await fetchSections()
  if (isEdit.value) {
    await loadCellule()
  }
})
</script>
