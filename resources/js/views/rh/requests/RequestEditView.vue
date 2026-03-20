<template>
  <div class="py-4">
    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>

    <div v-else-if="demande" class="row justify-content-center">
      <div class="col-lg-8">
        <!-- Header -->
        <div class="mb-4">
          <router-link :to="{ name: 'requests.show', params: { id: demande.id } }" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Retour aux details
          </router-link>
          <h4 class="mt-2"><i class="fas fa-edit me-2"></i> Modifier la Demande #{{ demande.id }}</h4>
        </div>

        <!-- Not authorized -->
        <div v-if="!isRH" class="alert alert-danger">
          <i class="fas fa-lock me-2"></i> Seuls les agents RH peuvent modifier le statut d'une demande.
        </div>

        <div v-else class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4">
            <!-- Read-only request info -->
            <div class="row mb-4">
              <div class="col-md-6">
                <h6 class="text-muted mb-1">Agent</h6>
                <p class="mb-0">
                  <strong>{{ demande.agent?.prenom }} {{ demande.agent?.nom }}</strong>
                  <br>
                  <small class="text-muted">{{ demande.agent?.id_agent }}</small>
                </p>
              </div>
              <div class="col-md-6">
                <h6 class="text-muted mb-1">Type</h6>
                <span class="badge bg-info text-dark">{{ formatType(demande.type) }}</span>
              </div>
              <div class="col-md-6 mt-3">
                <h6 class="text-muted mb-1">Periode</h6>
                <p class="mb-0">
                  Du {{ formatDate(demande.date_debut) }}
                  <template v-if="demande.date_fin">
                    au {{ formatDate(demande.date_fin) }}
                  </template>
                </p>
              </div>
              <div class="col-md-6 mt-3">
                <h6 class="text-muted mb-1">Description</h6>
                <p class="mb-0 text-truncate" style="max-width: 300px;">{{ demande.description }}</p>
              </div>
            </div>

            <hr>

            <!-- Edit form -->
            <form @submit.prevent="handleSubmit">
              <!-- Statut -->
              <div class="mb-4">
                <label for="statut" class="form-label">
                  <strong>Statut</strong> <span class="text-danger">*</span>
                </label>
                <select
                  v-model="form.statut" id="statut"
                  class="form-select" :class="{ 'is-invalid': errors.statut }"
                >
                  <option value="en_attente">En attente</option>
                  <option value="approuve">Approuve</option>
                  <option value="rejete">Rejete</option>
                  <option value="annule">Annule</option>
                </select>
                <div v-if="errors.statut" class="invalid-feedback d-block">{{ errors.statut[0] }}</div>
              </div>

              <!-- Remarques -->
              <div class="mb-4">
                <label for="remarques" class="form-label">
                  <strong>Remarques</strong> <span class="text-muted fw-normal">(optionnel)</span>
                </label>
                <textarea
                  v-model="form.remarques" id="remarques" rows="4"
                  class="form-control" :class="{ 'is-invalid': errors.remarques }"
                  placeholder="Expliquez votre decision ou ajoutez un commentaire..."
                ></textarea>
                <small class="text-muted">Expliquez votre decision ou ajoutez un commentaire</small>
                <div v-if="errors.remarques" class="invalid-feedback d-block">{{ errors.remarques[0] }}</div>
              </div>

              <!-- Buttons -->
              <div class="d-flex gap-2 justify-content-end">
                <router-link
                  :to="{ name: 'requests.show', params: { id: demande.id } }"
                  class="btn btn-outline-secondary"
                >
                  <i class="fas fa-times me-2"></i> Annuler
                </router-link>
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-save me-1"></i>
                  Enregistrer
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Not found -->
    <div v-else class="text-center py-5">
      <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
      <h5>Demande introuvable</h5>
      <router-link :to="{ name: 'requests.index' }" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left me-2"></i> Retour aux demandes
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { get, update } from '@/api/requests'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()

const loading = ref(true)
const demande = ref(null)
const isRH = ref(false)
const errors = ref({})
const submitting = ref(false)

const form = ref({
  statut: 'en_attente',
  remarques: '',
})

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatType(type) {
  if (!type) return ''
  return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
}

async function handleSubmit() {
  errors.value = {}
  submitting.value = true

  try {
    await update(demande.value.id, {
      statut: form.value.statut,
      remarques: form.value.remarques,
    })
    ui.addToast('Demande modifiee avec succes.', 'success')
    router.push({ name: 'requests.show', params: { id: demande.value.id } })
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else if (err.response?.status === 403) {
      ui.addToast(err.response.data.message || 'Action non autorisee.', 'danger')
    } else {
      ui.addToast('Erreur lors de la modification.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await get(route.params.id)
    demande.value = data.data
    isRH.value = data.isRH

    // Pre-fill form with current values
    form.value.statut = demande.value.statut || 'en_attente'
    form.value.remarques = demande.value.remarques || ''
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast('Vous n\'avez pas acces a cette demande.', 'danger')
      router.push({ name: 'requests.index' })
    } else {
      ui.addToast('Erreur lors du chargement de la demande.', 'danger')
    }
  } finally {
    loading.value = false
  }
})
</script>
