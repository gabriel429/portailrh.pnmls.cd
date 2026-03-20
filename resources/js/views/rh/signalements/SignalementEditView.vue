<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement du signalement..." />
      </div>

      <template v-else>
        <section class="rh-hero">
          <div class="row g-2 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-edit me-2"></i>Modifier le signalement</h1>
              <p class="rh-sub">Dossier #{{ signalement?.id }}</p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'signalements.show', params: { id: route.params.id } }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="rh-list-card p-3 p-lg-4 mt-3">
          <div v-if="errors.length" class="alert alert-danger">
            <strong>Erreurs de validation</strong>
            <ul class="mb-0 mt-2">
              <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
            </ul>
          </div>

          <form @submit.prevent="handleSubmit" class="row g-3">
            <div class="col-md-6">
              <label for="type" class="form-label">Type</label>
              <input v-model="form.type" type="text" class="form-control" id="type" required>
            </div>

            <div class="col-md-6">
              <label for="severite" class="form-label">Severite</label>
              <select v-model="form.severite" class="form-select" id="severite" required>
                <option value="basse">Basse</option>
                <option value="moyenne">Moyenne</option>
                <option value="haute">Haute</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="statut" class="form-label">Statut</label>
              <select v-model="form.statut" class="form-select" id="statut" required>
                <option value="ouvert">Ouvert</option>
                <option value="en_cours">En cours</option>
                <option value="résolu">Resolu</option>
                <option value="fermé">Ferme</option>
              </select>
            </div>

            <div class="col-12">
              <label for="description" class="form-label">Description</label>
              <textarea v-model="form.description" class="form-control" id="description" rows="4" required></textarea>
            </div>

            <div class="col-12">
              <label for="observations" class="form-label">Observations</label>
              <textarea v-model="form.observations" class="form-control" id="observations" rows="3"></textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-3">
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-save me-2"></i>Enregistrer
              </button>
              <router-link :to="{ name: 'signalements.show', params: { id: route.params.id } }" class="btn btn-outline-secondary">
                Annuler
              </router-link>
            </div>
          </form>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { get, update } from '@/api/signalements'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const submitting = ref(false)
const errors = ref([])
const signalement = ref(null)
const form = ref({
  type: '',
  description: '',
  observations: '',
  severite: '',
  statut: '',
})

async function loadSignalement() {
  try {
    const { data } = await get(route.params.id)
    signalement.value = data.data
    form.value = {
      type: data.data.type || '',
      description: data.data.description || '',
      observations: data.data.observations || '',
      severite: data.data.severite || '',
      statut: data.data.statut || 'ouvert',
    }
  } catch {
    ui.addToast('Signalement introuvable.', 'danger')
    router.push({ name: 'signalements.index' })
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  errors.value = []
  submitting.value = true
  try {
    await update(route.params.id, form.value)
    ui.addToast('Signalement modifie avec succes.', 'success')
    router.push({ name: 'signalements.show', params: { id: route.params.id } })
  } catch (err) {
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors || {}
      errors.value = Object.values(validationErrors).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de la modification.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => loadSignalement())
</script>
