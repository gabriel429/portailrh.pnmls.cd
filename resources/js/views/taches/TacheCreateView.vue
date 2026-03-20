<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle Tache</h1>
            <p class="rh-sub">Assigner une tache a un agent de votre departement.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'taches.index' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div v-if="loadingAgents" class="text-center py-5">
        <LoadingSpinner message="Chargement..." />
      </div>

      <div v-else class="dash-panel mt-3">
        <div class="p-4">
          <div v-if="errors.length" class="alert alert-danger">
            <ul class="mb-0">
              <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
            </ul>
          </div>

          <form @submit.prevent="handleSubmit" class="row g-3">
            <!-- Agent -->
            <div class="col-md-6">
              <label for="agent_id" class="form-label fw-bold">Assigner a <span class="text-danger">*</span></label>
              <select v-model="form.agent_id" class="form-select" id="agent_id" required>
                <option value="">-- Choisir un agent --</option>
                <option v-for="ag in agents" :key="ag.id" :value="ag.id">
                  {{ ag.prenom }} {{ ag.nom }} ({{ ag.id_agent }})
                </option>
              </select>
            </div>

            <!-- Priorite -->
            <div class="col-md-3">
              <label for="priorite" class="form-label fw-bold">Priorite <span class="text-danger">*</span></label>
              <select v-model="form.priorite" class="form-select" id="priorite" required>
                <option value="normale">Normale</option>
                <option value="haute">Haute</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>

            <!-- Date echeance -->
            <div class="col-md-3">
              <label for="date_echeance" class="form-label fw-bold">Echeance</label>
              <input v-model="form.date_echeance" type="date" class="form-control" id="date_echeance">
            </div>

            <!-- Titre -->
            <div class="col-12">
              <label for="titre" class="form-label fw-bold">Titre de la tache <span class="text-danger">*</span></label>
              <input v-model="form.titre" type="text" class="form-control" id="titre" required
                     placeholder="Ex: Preparer le rapport mensuel">
            </div>

            <!-- Description -->
            <div class="col-12">
              <label for="description" class="form-label fw-bold">Description</label>
              <textarea v-model="form.description" class="form-control" id="description" rows="5"
                        placeholder="Details et instructions pour l'agent..."></textarea>
            </div>

            <!-- Boutons -->
            <div class="col-12 mt-3">
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-paper-plane me-1"></i> Assigner la tache
              </button>
              <router-link :to="{ name: 'taches.index' }" class="btn btn-outline-secondary ms-2">Annuler</router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { create, getCreateData } from '@/api/taches'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const ui = useUiStore()

const loadingAgents = ref(true)
const submitting = ref(false)
const errors = ref([])
const agents = ref([])
const form = ref({
  agent_id: '',
  titre: '',
  description: '',
  priorite: 'normale',
  date_echeance: '',
})

async function loadAgents() {
  try {
    const { data } = await getCreateData()
    agents.value = data.data
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast('Acces refuse. Seuls les directeurs peuvent creer des taches.', 'danger')
      router.push({ name: 'taches.index' })
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors du chargement.', 'danger')
    }
  } finally {
    loadingAgents.value = false
  }
}

async function handleSubmit() {
  errors.value = []
  submitting.value = true
  try {
    await create(form.value)
    ui.addToast('Tache creee avec succes.', 'success')
    router.push({ name: 'taches.index' })
  } catch (err) {
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors || {}
      errors.value = Object.values(validationErrors).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de la creation.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => loadAgents())
</script>

<style scoped>
@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; padding: 1rem; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    .form-label { font-size: .82rem; }
    .btn { font-size: .85rem; }
}
</style>
