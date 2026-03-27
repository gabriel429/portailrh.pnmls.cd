<template>
  <GenericEditModal
    :show="show"
    title="Modifier le Signalement"
    icon="fa-edit"
    size="lg"
    :loading="loading"
    loading-message="Chargement du signalement..."
    :submitting="submitting"
    @close="$emit('close')"
    @save="handleSubmit"
    save-text="Enregistrer"
    saving-text="Enregistrement..."
    save-icon="fa-save"
  >
    <!-- Not found -->
    <div v-if="!signalement && !loading" class="text-center py-4">
      <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
      <h5>Signalement introuvable</h5>
      <p class="text-muted">Ce signalement n'existe pas ou n'est plus accessible.</p>
    </div>

    <!-- Edit form -->
    <div v-else-if="signalement && !loading">
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
          <label for="severite" class="form-label">Sévérité</label>
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
            <option value="résolu">Résolu</option>
            <option value="fermé">Fermé</option>
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
      </form>
    </div>
  </GenericEditModal>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useUiStore } from '@/stores/ui'
import { get, update } from '@/api/signalements'
import GenericEditModal from '@/components/common/GenericEditModal.vue'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  signalementId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['close', 'updated'])

const ui = useUiStore()

const loading = ref(false)
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
  if (!props.signalementId) {
    signalement.value = null
    return
  }

  loading.value = true
  try {
    const { data } = await get(props.signalementId)
    signalement.value = data.data
    form.value = {
      type: data.data.type || '',
      description: data.data.description || '',
      observations: data.data.observations || '',
      severite: data.data.severite || '',
      statut: data.data.statut || 'ouvert',
    }
  } catch {
    signalement.value = null
    ui.addToast('Signalement introuvable.', 'danger')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  if (!signalement.value) return

  errors.value = []
  submitting.value = true
  try {
    await update(signalement.value.id, form.value)
    ui.addToast('Signalement modifié avec succès.', 'success')
    emit('updated', signalement.value)
    emit('close')
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

// Watch for modal opening
watch(() => props.show, (newVal) => {
  if (newVal) {
    nextTick(() => {
      loadSignalement()
    })
  } else {
    // Reset data when modal closes
    signalement.value = null
    errors.value = []
    submitting.value = false
    form.value = {
      type: '',
      description: '',
      observations: '',
      severite: '',
      statut: '',
    }
  }
})
</script>

<style scoped>
/* Form styles matching the original design */
.form-control, .form-select {
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  font-size: .88rem;
}

.form-control:focus, .form-select:focus {
  border-color: #0077B5;
  box-shadow: 0 0 0 3px rgba(0,119,181,.1);
}

.form-label {
  font-size: .85rem;
  color: #475569;
  font-weight: 600;
}

.alert-danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  border-radius: 10px;
  font-size: .85rem;
}

@media (max-width: 767.98px) {
  .form-label {
    font-size: .82rem;
  }
}
</style>