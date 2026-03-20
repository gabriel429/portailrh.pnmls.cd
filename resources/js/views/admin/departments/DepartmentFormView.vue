<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="fas fa-building me-2"></i>{{ isEdit ? 'Modifier le departement' : 'Nouveau departement' }}
        </h4>
        <p class="text-muted mb-0">
          {{ isEdit ? 'Modifier les informations du departement' : 'Ajouter un nouveau departement au systeme' }}
        </p>
      </div>
      <router-link to="/admin/departments" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Retour
      </router-link>
    </div>

    <!-- Loading -->
    <div v-if="loadingData" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement...</p>
    </div>

    <!-- Form -->
    <div v-else class="card border-0 shadow-sm">
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="row g-3">
            <!-- Code -->
            <div class="col-md-4">
              <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
              <input
                id="code"
                v-model="form.code"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.code }"
                placeholder="Ex: DEP-FIN"
                required
              />
              <div v-if="errors.code" class="invalid-feedback">{{ errors.code[0] }}</div>
            </div>

            <!-- Nom -->
            <div class="col-md-8">
              <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
              <input
                id="nom"
                v-model="form.nom"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom }"
                placeholder="Nom du departement"
                required
              />
              <div v-if="errors.nom" class="invalid-feedback">{{ errors.nom[0] }}</div>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label for="description" class="form-label">Description</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
                rows="3"
                placeholder="Description du departement (optionnel)"
              ></textarea>
              <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
            </div>

            <!-- Province -->
            <div class="col-md-6">
              <label for="province_id" class="form-label">Province <span class="text-danger">*</span></label>
              <select
                id="province_id"
                v-model="form.province_id"
                class="form-select"
                :class="{ 'is-invalid': errors.province_id }"
                required
              >
                <option value="">-- Selectionner une province --</option>
                <option v-for="prov in provinces" :key="prov.id" :value="prov.id">
                  {{ prov.nom }}
                </option>
              </select>
              <div v-if="errors.province_id" class="invalid-feedback">{{ errors.province_id[0] }}</div>
              <div v-if="loadingProvinces" class="form-text">
                <span class="spinner-border spinner-border-sm me-1"></span>Chargement des provinces...
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex justify-content-end gap-2 mt-4">
            <router-link to="/admin/departments" class="btn btn-outline-secondary">Annuler</router-link>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              {{ isEdit ? 'Mettre a jour' : 'Enregistrer' }}
            </button>
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
const loadingProvinces = ref(false)
const saving = ref(false)
const errors = ref({})
const provinces = ref([])

const form = ref({
  code: '',
  nom: '',
  description: '',
  province_id: '',
})

async function loadProvinces() {
  loadingProvinces.value = true
  try {
    const { data } = await client.get('/admin/provinces', { params: { per_page: 100 } })
    provinces.value = data.data || []
  } catch (e) {
    console.error('Erreur chargement provinces:', e)
  } finally {
    loadingProvinces.value = false
  }
}

async function loadDepartment() {
  loadingData.value = true
  try {
    const { data } = await client.get(`/admin/departments/${route.params.id}`)
    const d = data.data || data
    form.value = {
      code: d.code || '',
      nom: d.nom || '',
      description: d.description || '',
      province_id: d.province_id || '',
    }
  } catch (e) {
    console.error('Erreur chargement departement:', e)
    alert('Erreur lors du chargement du departement.')
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await client.put(`/admin/departments/${route.params.id}`, form.value)
    } else {
      await client.post('/admin/departments', form.value)
    }
    router.push('/admin/departments')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      console.error('Erreur sauvegarde departement:', e)
      alert('Erreur lors de la sauvegarde du departement.')
    }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadProvinces()
  if (isEdit.value) {
    loadDepartment()
  }
})
</script>
