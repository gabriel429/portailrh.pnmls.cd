<template>
  <div class="rh-modern">
    <div class="rh-list-shell">
      <!-- Loading -->
      <LoadingSpinner v-if="loading" message="Chargement du pointage..." />

      <template v-else-if="pointage">
        <section class="rh-hero">
          <div class="row g-2 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-edit me-2"></i>Modifier le pointage</h1>
              <p class="rh-sub">{{ formatDate(pointage.date_pointage) }} - {{ pointage.agent?.prenom }} {{ pointage.agent?.nom }}</p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'rh.pointages.show', params: { id: pointage.id } }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour details
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="rh-list-card p-3 p-lg-4">
          <!-- Validation errors -->
          <div v-if="errors.length > 0" class="alert alert-danger">
            <strong>Erreurs de validation</strong>
            <ul class="mb-0 mt-2">
              <li v-for="(error, i) in errors" :key="i">{{ error }}</li>
            </ul>
          </div>

          <form @submit.prevent="submitForm" class="row g-3">
            <!-- Read-only fields -->
            <div class="col-md-6">
              <label class="form-label">Agent</label>
              <input type="text" class="form-control" :value="`${pointage.agent?.prenom} ${pointage.agent?.nom}`" disabled>
            </div>
            <div class="col-md-6">
              <label class="form-label">Matricule</label>
              <input type="text" class="form-control" :value="pointage.agent?.id_agent" disabled>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date pointage</label>
              <input type="text" class="form-control" :value="formatDate(pointage.date_pointage)" disabled>
            </div>

            <!-- Editable fields -->
            <div class="col-md-6">
              <label for="heure_entree" class="form-label">Heure d'entree</label>
              <input type="time" class="form-control" id="heure_entree" v-model="form.heure_entree" @change="calculateHeures">
            </div>

            <div class="col-md-6">
              <label for="heure_sortie" class="form-label">Heure de sortie</label>
              <input type="time" class="form-control" id="heure_sortie" v-model="form.heure_sortie" @change="calculateHeures">
            </div>

            <div class="col-md-6">
              <label for="heures_travaillees" class="form-label">Heures travaillees</label>
              <input type="number" step="0.5" class="form-control" id="heures_travaillees" v-model="form.heures_travaillees" placeholder="ex: 8.5">
            </div>

            <div class="col-12">
              <label for="observations" class="form-label">Observations</label>
              <textarea class="form-control" id="observations" v-model="form.observations" rows="4"></textarea>
            </div>

            <div class="col-12 d-flex gap-2 mt-3">
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fas fa-save me-2"></i>Enregistrer
              </button>
              <router-link :to="{ name: 'rh.pointages.show', params: { id: pointage.id } }" class="btn btn-outline-secondary">Annuler</router-link>
            </div>
          </form>
        </div>
      </template>

      <!-- Not found -->
      <div v-else class="text-center py-5">
        <i class="fas fa-exclamation-triangle fa-4x text-muted mb-3 d-block"></i>
        <h5 class="text-muted">Pointage introuvable</h5>
        <router-link :to="{ name: 'rh.pointages.index' }" class="btn btn-primary mt-2">
          <i class="fas fa-arrow-left me-2"></i>Retour a la liste
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import * as pointagesApi from '@/api/pointages'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const submitting = ref(false)
const pointage = ref(null)
const errors = ref([])

const form = reactive({
    heure_entree: '',
    heure_sortie: '',
    heures_travaillees: '',
    observations: '',
})

function formatDate(dateStr) {
    if (!dateStr) return 'N/A'
    const d = new Date(dateStr)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function extractTime(timeStr) {
    if (!timeStr) return ''
    // Handle datetime format (e.g., "2026-03-20T08:00:00.000000Z")
    if (timeStr.length > 5) {
        const d = new Date(timeStr)
        if (!isNaN(d.getTime())) {
            return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
        }
    }
    return timeStr.substring(0, 5)
}

function calculateHeures() {
    if (form.heure_entree && form.heure_sortie) {
        const entree = new Date(`2000-01-01T${form.heure_entree}`)
        const sortie = new Date(`2000-01-01T${form.heure_sortie}`)
        const diff = (sortie - entree) / (1000 * 60 * 60)
        if (diff > 0) {
            form.heures_travaillees = diff.toFixed(1)
        }
    }
}

async function fetchPointage() {
    loading.value = true
    try {
        const { data } = await pointagesApi.get(route.params.id)
        pointage.value = data

        // Populate form
        form.heure_entree = extractTime(data.heure_entree)
        form.heure_sortie = extractTime(data.heure_sortie)
        form.heures_travaillees = data.heures_travaillees || ''
        form.observations = data.observations || ''
    } catch {
        ui.addToast('Erreur lors du chargement du pointage.', 'danger')
        pointage.value = null
    } finally {
        loading.value = false
    }
}

async function submitForm() {
    errors.value = []
    submitting.value = true

    try {
        const payload = {
            heure_entree: form.heure_entree || null,
            heure_sortie: form.heure_sortie || null,
            heures_travaillees: form.heures_travaillees ? parseFloat(form.heures_travaillees) : null,
            observations: form.observations || null,
        }

        await pointagesApi.update(pointage.value.id, payload)
        ui.addToast('Pointage modifie avec succes.', 'success')
        router.push({ name: 'rh.pointages.show', params: { id: pointage.value.id } })
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            const validationErrors = err.response.data.errors
            errors.value = Object.values(validationErrors).flat()
        } else {
            ui.addToast('Erreur lors de la modification.', 'danger')
        }
    } finally {
        submitting.value = false
    }
}

onMounted(() => {
    fetchPointage()
})
</script>
