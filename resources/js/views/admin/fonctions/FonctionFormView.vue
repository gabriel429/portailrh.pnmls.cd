<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="fas fa-briefcase me-2"></i>
          {{ isEdit ? 'Modifier la fonction' : 'Nouvelle fonction' }}
        </h4>
        <p class="text-muted mb-0">
          {{ isEdit ? 'Modifiez les informations de cette fonction.' : 'Remplissez le formulaire pour creer une nouvelle fonction.' }}
        </p>
      </div>
      <router-link to="/admin/fonctions" class="btn btn-outline-secondary">
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
              <label for="nom" class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
              <input
                id="nom"
                v-model="form.nom"
                type="text"
                class="form-control"
                required
                placeholder="Ex: Directeur National"
              >
            </div>

            <div class="mb-3">
              <label for="niveau_administratif" class="form-label fw-bold">Niveau administratif <span class="text-danger">*</span></label>
              <select id="niveau_administratif" v-model="form.niveau_administratif" class="form-select" required>
                <option value="">-- Selectionnez --</option>
                <option value="SEN">SEN - Secretariat Executif National</option>
                <option value="SEP">SEP - Secretariat Executif Provincial</option>
                <option value="SEL">SEL - Secretariat Executif Local</option>
                <option value="TOUS">TOUS - Tous les niveaux</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="type_poste" class="form-label fw-bold">Type de poste</label>
              <input
                id="type_poste"
                v-model="form.type_poste"
                type="text"
                class="form-control"
                placeholder="Ex: Direction, Coordination, Support"
              >
            </div>

            <div class="mb-3">
              <label for="description" class="form-label fw-bold">Description</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control"
                rows="3"
                placeholder="Description de la fonction..."
              ></textarea>
            </div>

            <div class="mb-4">
              <div class="form-check">
                <input
                  id="est_chef"
                  v-model="form.est_chef"
                  type="checkbox"
                  class="form-check-input"
                >
                <label for="est_chef" class="form-check-label fw-bold">
                  Est un poste de chef / responsable
                </label>
              </div>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-save me-1"></i>
                {{ isEdit ? 'Mettre a jour' : 'Enregistrer' }}
              </button>
              <router-link to="/admin/fonctions" class="btn btn-outline-secondary">Annuler</router-link>
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

const form = reactive({
  nom: '',
  niveau_administratif: '',
  type_poste: '',
  description: '',
  est_chef: false,
})

async function loadFonction() {
  loadingData.value = true
  loadError.value = ''
  try {
    const { data } = await client.get('/admin/fonctions/' + route.params.id)
    const f = data.data || data
    form.nom = f.nom || ''
    form.niveau_administratif = f.niveau_administratif || ''
    form.type_poste = f.type_poste || ''
    form.description = f.description || ''
    form.est_chef = !!f.est_chef
  } catch (e) {
    console.error('Erreur chargement fonction:', e)
    loadError.value = 'Impossible de charger cette fonction.'
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = []
  try {
    const payload = { ...form, est_chef: form.est_chef ? 1 : 0 }
    if (isEdit.value) {
      await client.put('/admin/fonctions/' + route.params.id, payload)
    } else {
      await client.post('/admin/fonctions', payload)
    }
    router.push('/admin/fonctions')
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

onMounted(() => {
  if (isEdit.value) {
    loadFonction()
  }
})
</script>
