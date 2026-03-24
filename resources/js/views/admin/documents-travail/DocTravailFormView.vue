<template>
  <div class="py-4">
    <div class="d-flex align-items-center mb-4">
      <router-link :to="{ name: 'admin.documents-travail.index' }" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
      </router-link>
      <h4 class="mb-0">{{ isEdit ? 'Modifier le Document' : 'Nouveau Document de Travail' }}</h4>
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
            <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
            <input type="text" id="titre" v-model="form.titre" class="form-control" :class="{ 'is-invalid': validationErrors.titre }" required>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" v-model="form.description" class="form-control" :class="{ 'is-invalid': validationErrors.description }" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label for="categorie" class="form-label">Categorie</label>
            <input type="text" id="categorie" v-model="form.categorie" class="form-control" :class="{ 'is-invalid': validationErrors.categorie }">
          </div>

          <div class="mb-3">
            <label for="fichier" class="form-label">
              Fichier
              <span v-if="!isEdit" class="text-danger">*</span>
              <small v-if="isEdit" class="text-muted">(laisser vide pour conserver le fichier actuel)</small>
            </label>
            <input type="file" id="fichier" ref="fichierInput" class="form-control" :class="{ 'is-invalid': validationErrors.fichier }" @change="onFileChange" :required="!isEdit">
            <div v-if="isEdit && currentFileName" class="form-text">
              Fichier actuel : <strong>{{ currentFileName }}</strong>
            </div>
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
            <router-link :to="{ name: 'admin.documents-travail.index' }" class="btn btn-secondary">Annuler</router-link>
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
const currentFileName = ref('')
const fichierInput = ref(null)
const selectedFile = ref(null)

const form = ref({
  titre: '',
  description: '',
  categorie: '',
  actif: true,
})

function onFileChange(event) {
  selectedFile.value = event.target.files[0] || null
}

async function fetchDocument() {
  loadingData.value = true
  error.value = null
  try {
    const response = await client.get(`/admin/documents-travail/${route.params.id}`)
    const doc = response.data.data || response.data
    form.value = {
      titre: doc.titre || '',
      description: doc.description || '',
      categorie: doc.categorie || '',
      actif: !!doc.actif,
    }
    currentFileName.value = doc.fichier_nom || doc.fichier || ''
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement du document.'
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  submitting.value = true
  error.value = null
  validationErrors.value = {}

  const formData = new FormData()
  formData.append('titre', form.value.titre)
  formData.append('description', form.value.description || '')
  formData.append('categorie', form.value.categorie || '')
  formData.append('actif', form.value.actif ? '1' : '0')

  if (selectedFile.value) {
    formData.append('fichier', selectedFile.value)
  }

  try {
    if (isEdit.value) {
      formData.append('_method', 'PUT')
      await client.post(`/admin/documents-travail/${route.params.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    } else {
      await client.post('/admin/documents-travail', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }
    router.push({ name: 'admin.documents-travail.index' })
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
    fetchDocument()
  }
})
</script>
