<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-plus-circle me-2"></i>Nouveau signalement</h1>
            <p class="rh-sub">Declarer un incident et fixer sa severite initiale.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'signalements.index' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour liste
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
            <label for="agent_id" class="form-label">Agent</label>
            <select v-model="form.agent_id" class="form-select" id="agent_id" required>
              <option value="">Selectionner un agent</option>
              <option v-for="ag in agents" :key="ag.id" :value="ag.id">
                ({{ ag.id_agent }}) {{ ag.prenom }} {{ ag.nom }}
              </option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="type" class="form-label">Type</label>
            <input v-model="form.type" type="text" class="form-control" id="type" required>
          </div>

          <div class="col-md-6">
            <label for="severite" class="form-label">Severite</label>
            <select v-model="form.severite" class="form-select" id="severite" required>
              <option value="">Selectionner</option>
              <option value="basse">Basse</option>
              <option value="moyenne">Moyenne</option>
              <option value="haute">Haute</option>
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
              <i v-else class="fas fa-save me-2"></i>Creer le signalement
            </button>
            <router-link :to="{ name: 'signalements.index' }" class="btn btn-outline-secondary">Annuler</router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { create, getAgents } from '@/api/signalements'

const router = useRouter()
const ui = useUiStore()

const agents = ref([])
const errors = ref([])
const submitting = ref(false)
const form = ref({
  agent_id: '',
  type: '',
  description: '',
  observations: '',
  severite: '',
})

async function loadAgents() {
  try {
    const { data } = await getAgents()
    agents.value = data.data
  } catch {
    ui.addToast('Erreur lors du chargement des agents.', 'danger')
  }
}

async function handleSubmit() {
  errors.value = []
  submitting.value = true
  try {
    await create(form.value)
    ui.addToast('Signalement cree avec succes.', 'success')
    router.push({ name: 'signalements.index' })
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
