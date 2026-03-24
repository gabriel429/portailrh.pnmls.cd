<template>
  <div class="py-4">
    <div class="d-flex align-items-center mb-4">
      <router-link :to="{ name: 'admin.organes.index' }" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
      </router-link>
      <h4 class="mb-0">{{ isEdit ? 'Modifier l\'Organe' : 'Nouvel Organe' }}</h4>
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
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" id="code" v-model="form.code" class="form-control" :class="{ 'is-invalid': validationErrors.code }" required>
            </div>

            <div class="col-md-6 mb-3">
              <label for="sigle" class="form-label">Sigle <span class="text-danger">*</span></label>
              <input type="text" id="sigle" v-model="form.sigle" class="form-control" :class="{ 'is-invalid': validationErrors.sigle }" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" id="nom" v-model="form.nom" class="form-control" :class="{ 'is-invalid': validationErrors.nom }" required>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" v-model="form.description" class="form-control" :class="{ 'is-invalid': validationErrors.description }" rows="3"></textarea>
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
            <router-link :to="{ name: 'admin.organes.index' }" class="btn btn-secondary">Annuler</router-link>
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
  code: '',
  nom: '',
  sigle: '',
  description: '',
  actif: true,
})

async function fetchOrgane() {
  loadingData.value = true
  error.value = null
  try {
    const response = await client.get(`/admin/organes/${route.params.id}`)
    const organe = response.data.data || response.data
    form.value = {
      code: organe.code || '',
      nom: organe.nom || '',
      sigle: organe.sigle || '',
      description: organe.description || '',
      actif: !!organe.actif,
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement de l\'organe.'
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
      await client.put(`/admin/organes/${route.params.id}`, form.value)
    } else {
      await client.post('/admin/organes', form.value)
    }
    router.push({ name: 'admin.organes.index' })
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
    fetchOrgane()
  }
})
</script>
