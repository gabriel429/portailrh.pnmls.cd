<template>
  <div class="py-4">
    <div class="d-flex align-items-center mb-4">
      <router-link :to="{ name: 'admin.categories-documents.index' }" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
      </router-link>
      <h4 class="mb-0">{{ isEdit ? 'Modifier la Categorie' : 'Nouvelle Categorie de Documents' }}</h4>
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
          <div class="mb-3">
            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" id="nom" v-model="form.nom" class="form-control" :class="{ 'is-invalid': validationErrors.nom }" required>
          </div>

          <div class="mb-3">
            <label for="icone" class="form-label">Icone (classe Font Awesome)</label>
            <div class="input-group">
              <span class="input-group-text">
                <i :class="form.icone || 'fas fa-question'" style="width: 16px; text-align: center;"></i>
              </span>
              <input type="text" id="icone" v-model="form.icone" class="form-control" :class="{ 'is-invalid': validationErrors.icone }" placeholder="ex: fas fa-folder">
            </div>
            <div class="form-text">Entrez une classe Font Awesome, ex: <code>fas fa-folder</code>, <code>fas fa-file-pdf</code></div>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" id="actif" v-model="form.actif" class="form-check-input">
            <label for="actif" class="form-check-label">Actif</label>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
              {{ isEdit ? 'Mettre a jour' : 'Creer' }}
            </button>
            <router-link :to="{ name: 'admin.categories-documents.index' }" class="btn btn-secondary">Annuler</router-link>
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

const form = ref({
  nom: '',
  icone: '',
  actif: true,
})

async function fetchCategorie() {
  loadingData.value = true
  error.value = null
  try {
    const response = await client.get(`/admin/categories-documents/${route.params.id}`)
    const cat = response.data.data || response.data
    form.value = {
      nom: cat.nom || '',
      icone: cat.icone || '',
      actif: !!cat.actif,
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement de la categorie.'
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  submitting.value = true
  error.value = null
  validationErrors.value = {}
  try {
    if (isEdit.value) {
      await client.put(`/admin/categories-documents/${route.params.id}`, form.value)
    } else {
      await client.post('/admin/categories-documents', form.value)
    }
    router.push({ name: 'admin.categories-documents.index' })
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
    fetchCategorie()
  }
})
</script>
