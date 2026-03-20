<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="fas fa-user-tag me-2"></i>{{ isEdit ? 'Modifier le role' : 'Nouveau role' }}
        </h4>
        <p class="text-muted mb-0">
          {{ isEdit ? 'Modifier les informations du role' : 'Ajouter un nouveau role au systeme' }}
        </p>
      </div>
      <router-link to="/admin/roles" class="btn btn-outline-secondary">
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
            <!-- Nom du role -->
            <div class="col-md-6">
              <label for="nom_role" class="form-label">Nom du role <span class="text-danger">*</span></label>
              <input
                id="nom_role"
                v-model="form.nom_role"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom_role }"
                placeholder="Ex: Chef de section"
                required
              />
              <div v-if="errors.nom_role" class="invalid-feedback">{{ errors.nom_role[0] }}</div>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label for="description" class="form-label">Description</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
                rows="4"
                placeholder="Description du role (optionnel)"
              ></textarea>
              <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex justify-content-end gap-2 mt-4">
            <router-link to="/admin/roles" class="btn btn-outline-secondary">Annuler</router-link>
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
  nom_role: '',
  description: '',
})

async function loadRole() {
  loadingData.value = true
  try {
    const { data } = await client.get(`/admin/roles/${route.params.id}`)
    const r = data.data || data
    form.value = {
      nom_role: r.nom_role || '',
      description: r.description || '',
    }
  } catch (e) {
    console.error('Erreur chargement role:', e)
    alert('Erreur lors du chargement du role.')
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await client.put(`/admin/roles/${route.params.id}`, form.value)
    } else {
      await client.post('/admin/roles', form.value)
    }
    router.push('/admin/roles')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      console.error('Erreur sauvegarde role:', e)
      alert('Erreur lors de la sauvegarde du role.')
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (isEdit.value) {
    loadRole()
  }
})
</script>
