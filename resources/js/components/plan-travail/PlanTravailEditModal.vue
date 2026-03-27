<template>
  <GenericEditModal
    :show="show"
    title="Modifier l'Activité"
    icon="fa-edit"
    size="xl"
    :loading="loading"
    loading-message="Chargement de l'activité..."
    :submitting="submitting"
    @close="$emit('close')"
    @save="handleSubmit"
    save-text="Mettre à jour"
    saving-text="Mise à jour..."
    save-icon="fa-save"
  >
    <!-- Not found -->
    <div v-if="!planTravail && !loading" class="text-center py-4">
      <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
      <h5>Activité introuvable</h5>
      <p class="text-muted">Cette activité n'existe pas ou n'est plus accessible.</p>
    </div>

    <!-- Edit form -->
    <div v-else-if="planTravail && !loading">
      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0">
          <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
        </ul>
      </div>

      <form @submit.prevent="handleSubmit">
        <!-- Titre -->
        <div class="mb-3">
          <label for="titre" class="form-label fw-bold">Titre de l'activité <span class="text-danger">*</span></label>
          <input v-model="form.titre" type="text" class="form-control" id="titre" required>
        </div>

        <div class="row g-3">
          <!-- Niveau administratif -->
          <div class="col-md-4">
            <label for="niveau_administratif" class="form-label fw-bold">Niveau <span class="text-danger">*</span></label>
            <select v-model="form.niveau_administratif" class="form-select" id="niveau_administratif" required @change="onNiveauChange">
              <option value="">-- Choisir --</option>
              <option value="SEN">SEN (National)</option>
              <option value="SEP">SEP (Provincial)</option>
              <option value="SEL">SEL (Local)</option>
            </select>
          </div>

          <div v-if="form.niveau_administratif === 'SEN'" class="col-md-4">
            <label for="departement_id" class="form-label fw-bold">Département</label>
            <select v-model="form.departement_id" class="form-select" id="departement_id">
              <option value="">-- Direction / Tous --</option>
              <option v-for="d in formData.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
            </select>
          </div>

          <div v-if="form.niveau_administratif === 'SEP' || form.niveau_administratif === 'SEL'" class="col-md-4">
            <label for="province_id" class="form-label fw-bold">Province</label>
            <select v-model="form.province_id" class="form-select" id="province_id">
              <option value="">-- Choisir --</option>
              <option v-for="p in formData.provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
            </select>
          </div>

          <div v-if="form.niveau_administratif === 'SEL'" class="col-md-4">
            <label for="localite_id" class="form-label fw-bold">Localité</label>
            <select v-model="form.localite_id" class="form-select" id="localite_id">
              <option value="">-- Choisir --</option>
              <option v-for="l in formData.localites" :key="l.id" :value="l.id">{{ l.nom }}</option>
            </select>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-3">
            <label for="annee" class="form-label fw-bold">Année <span class="text-danger">*</span></label>
            <input v-model.number="form.annee" type="number" class="form-control" id="annee" min="2020" max="2040" required>
          </div>

          <div class="col-md-3">
            <label for="trimestre" class="form-label fw-bold">Trimestre</label>
            <select v-model="form.trimestre" class="form-select" id="trimestre">
              <option value="">Annuel</option>
              <option value="T1">T1 (Jan-Mar)</option>
              <option value="T2">T2 (Avr-Jun)</option>
              <option value="T3">T3 (Jul-Sep)</option>
              <option value="T4">T4 (Oct-Dec)</option>
            </select>
          </div>

          <div class="col-md-3">
            <label for="statut" class="form-label fw-bold">Statut <span class="text-danger">*</span></label>
            <select v-model="form.statut" class="form-select" id="statut" required>
              <option value="planifiee">Planifiée</option>
              <option value="en_cours">En cours</option>
              <option value="terminee">Terminée</option>
            </select>
          </div>

          <div class="col-md-3">
            <label for="pourcentage" class="form-label fw-bold">Progression (%)</label>
            <input v-model.number="form.pourcentage" type="number" class="form-control" id="pourcentage" min="0" max="100">
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label for="date_debut" class="form-label fw-bold">Date de début</label>
            <input v-model="form.date_debut" type="date" class="form-control" id="date_debut">
          </div>

          <div class="col-md-6">
            <label for="date_fin" class="form-label fw-bold">Date de fin</label>
            <input v-model="form.date_fin" type="date" class="form-control" id="date_fin">
          </div>
        </div>

        <div class="mb-3 mt-3">
          <label for="description" class="form-label fw-bold">Description</label>
          <textarea v-model="form.description" class="form-control" id="description" rows="4"></textarea>
        </div>

        <div class="mb-3">
          <label for="observations" class="form-label fw-bold">Observations</label>
          <textarea v-model="form.observations" class="form-control" id="observations" rows="3"></textarea>
        </div>
      </form>
    </div>
  </GenericEditModal>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useUiStore } from '@/stores/ui'
import { get, update, getCreateData } from '@/api/planTravail'
import GenericEditModal from '@/components/common/GenericEditModal.vue'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  planTravailId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['close', 'updated'])

const ui = useUiStore()

const loading = ref(false)
const submitting = ref(false)
const errors = ref([])
const planTravail = ref(null)
const formData = ref({ departments: [], provinces: [], localites: [] })
const form = ref({
  titre: '',
  niveau_administratif: '',
  departement_id: '',
  province_id: '',
  localite_id: '',
  annee: new Date().getFullYear(),
  trimestre: '',
  statut: 'planifiee',
  pourcentage: 0,
  date_debut: '',
  date_fin: '',
  description: '',
  observations: '',
})

async function loadPlanTravail() {
  if (!props.planTravailId) {
    planTravail.value = null
    return
  }

  loading.value = true
  try {
    const [activiteResp, createResp] = await Promise.all([
      get(props.planTravailId),
      getCreateData(),
    ])
    const a = activiteResp.data.data
    planTravail.value = a
    formData.value = createResp.data
    form.value = {
      titre: a.titre || '',
      niveau_administratif: a.niveau_administratif || '',
      departement_id: a.departement_id || '',
      province_id: a.province_id || '',
      localite_id: a.localite_id || '',
      annee: a.annee || new Date().getFullYear(),
      trimestre: a.trimestre || '',
      statut: a.statut || 'planifiee',
      pourcentage: a.pourcentage ?? 0,
      date_debut: a.date_debut ? a.date_debut.split('T')[0] : '',
      date_fin: a.date_fin ? a.date_fin.split('T')[0] : '',
      description: a.description || '',
      observations: a.observations || '',
    }
  } catch {
    planTravail.value = null
    ui.addToast('Activité introuvable.', 'danger')
  } finally {
    loading.value = false
  }
}

function onNiveauChange() {
  if (form.value.niveau_administratif !== 'SEN') form.value.departement_id = ''
  if (form.value.niveau_administratif !== 'SEP' && form.value.niveau_administratif !== 'SEL') form.value.province_id = ''
  if (form.value.niveau_administratif !== 'SEL') form.value.localite_id = ''
}

async function handleSubmit() {
  if (!planTravail.value) return

  errors.value = []
  submitting.value = true
  try {
    await update(planTravail.value.id, form.value)
    ui.addToast('Activité mise à jour.', 'success')
    emit('updated', planTravail.value)
    emit('close')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = Object.values(err.response.data.errors || {}).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

// Watch for modal opening
watch(() => props.show, (newVal) => {
  if (newVal) {
    nextTick(() => {
      loadPlanTravail()
    })
  } else {
    // Reset data when modal closes
    planTravail.value = null
    errors.value = []
    submitting.value = false
    formData.value = { departments: [], provinces: [], localites: [] }
    form.value = {
      titre: '',
      niveau_administratif: '',
      departement_id: '',
      province_id: '',
      localite_id: '',
      annee: new Date().getFullYear(),
      trimestre: '',
      statut: 'planifiee',
      pourcentage: 0,
      date_debut: '',
      date_fin: '',
      description: '',
      observations: '',
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

  .row.g-3 > .col-md-3,
  .row.g-3 > .col-md-4,
  .row.g-3 > .col-md-6 {
    margin-bottom: 1rem;
  }
}
</style>