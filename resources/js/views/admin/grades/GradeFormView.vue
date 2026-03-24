<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="fas fa-medal me-2"></i>{{ isEdit ? 'Modifier le grade' : 'Nouveau grade' }}
        </h4>
        <p class="text-muted mb-0">
          {{ isEdit ? 'Modifier les informations du grade' : 'Ajouter un nouveau grade au systeme' }}
        </p>
      </div>
      <router-link to="/admin/grades" class="btn btn-outline-secondary">
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
            <!-- Categorie -->
            <div class="col-md-6">
              <label for="categorie" class="form-label">Categorie <span class="text-danger">*</span></label>
              <select
                id="categorie"
                v-model="form.categorie"
                class="form-select"
                :class="{ 'is-invalid': errors.categorie }"
                required
              >
                <option value="">-- Selectionner une categorie --</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
              </select>
              <div v-if="errors.categorie" class="invalid-feedback">{{ errors.categorie[0] }}</div>
            </div>

            <!-- Nom categorie -->
            <div class="col-md-6">
              <label for="nom_categorie" class="form-label">Nom de la categorie <span class="text-danger">*</span></label>
              <input
                id="nom_categorie"
                v-model="form.nom_categorie"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom_categorie }"
                placeholder="Ex: Cadres de direction"
                required
              />
              <div v-if="errors.nom_categorie" class="invalid-feedback">{{ errors.nom_categorie[0] }}</div>
            </div>

            <!-- Ordre -->
            <div class="col-md-4">
              <label for="ordre" class="form-label">Ordre <span class="text-danger">*</span></label>
              <input
                id="ordre"
                v-model.number="form.ordre"
                type="number"
                class="form-control"
                :class="{ 'is-invalid': errors.ordre }"
                placeholder="1"
                min="1"
                required
              />
              <div v-if="errors.ordre" class="invalid-feedback">{{ errors.ordre[0] }}</div>
            </div>

            <!-- Libelle -->
            <div class="col-md-8">
              <label for="libelle" class="form-label">Libelle <span class="text-danger">*</span></label>
              <input
                id="libelle"
                v-model="form.libelle"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.libelle }"
                placeholder="Ex: Directeur"
                required
              />
              <div v-if="errors.libelle" class="invalid-feedback">{{ errors.libelle[0] }}</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex justify-content-end gap-2 mt-4">
            <router-link to="/admin/grades" class="btn btn-outline-secondary">Annuler</router-link>
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
const saving = ref(false)
const errors = ref({})

const form = ref({
  categorie: '',
  nom_categorie: '',
  ordre: null,
  libelle: '',
})

async function loadGrade() {
  loadingData.value = true
  try {
    const { data } = await client.get(`/admin/grades/${route.params.id}`)
    const grade = data.data || data
    form.value = {
      categorie: grade.categorie || '',
      nom_categorie: grade.nom_categorie || '',
      ordre: grade.ordre ?? null,
      libelle: grade.libelle || '',
    }
  } catch (e) {
    console.error('Erreur chargement grade:', e)
    alert('Erreur lors du chargement du grade.')
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await client.put(`/admin/grades/${route.params.id}`, form.value)
    } else {
      await client.post('/admin/grades', form.value)
    }
    router.push('/admin/grades')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      console.error('Erreur sauvegarde grade:', e)
      alert('Erreur lors de la sauvegarde du grade.')
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (isEdit.value) {
    loadGrade()
  }
})
</script>
