<template>
  <div
    v-if="show"
    class="modal fade show"
    style="display: block; background: rgba(0,0,0,0.5); z-index: 2000 !important;"
    @click="handleBackdropClick"
  >
    <div class="modal-dialog modal-lg" style="z-index: 2001 !important;" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-plus-circle me-2"></i>
            Nouveau Planning de Congés
          </h5>
          <button
            type="button"
            class="btn-close"
            @click="$emit('close')"
          ></button>
        </div>

        <form @submit.prevent="submitForm">
          <div class="modal-body">
            <div class="row g-3">
              <!-- Année -->
              <div class="col-md-6">
                <label class="form-label required">Année</label>
                <select
                  class="form-select"
                  v-model="form.annee"
                  :class="{ 'is-invalid': errors.annee }"
                  required
                >
                  <option value="">Sélectionner une année</option>
                  <option v-for="year in availableYears" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>
                <div v-if="errors.annee" class="invalid-feedback">{{ errors.annee }}</div>
              </div>

              <!-- Type de structure -->
              <div class="col-md-6">
                <label class="form-label required">Type de structure</label>
                <select
                  class="form-select"
                  v-model="form.type_structure"
                  :class="{ 'is-invalid': errors.type_structure }"
                  @change="onStructureTypeChange"
                  required
                >
                  <option value="">Sélectionner un type</option>
                  <option v-if="!scopeInfo.is_provincial" value="department">Département</option>
                  <option v-if="!scopeInfo.is_provincial" value="sen">SEN</option>
                  <option v-if="!scopeInfo.is_provincial" value="sena">SENA</option>
                  <option value="sep">SEP Provincial</option>
                  <option value="local">Structure Locale</option>
                </select>
                <div v-if="errors.type_structure" class="invalid-feedback">{{ errors.type_structure }}</div>
              </div>

              <!-- Structure spécifique -->
              <div class="col-12" v-if="form.type_structure && !isAutoFilledStructure">
                <label class="form-label required">
                  {{ getStructureLabel() }}
                </label>
                <select
                  v-if="form.type_structure === 'department'"
                  class="form-select"
                  v-model="form.structure_id"
                  :class="{ 'is-invalid': errors.structure_id }"
                  @change="onStructureChange"
                  required
                >
                  <option value="">Sélectionner un département</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                    {{ dept.nom }}
                  </option>
                </select>
                <input
                  v-else
                  type="text"
                  class="form-control"
                  v-model="form.nom_structure"
                  :class="{ 'is-invalid': errors.nom_structure }"
                  :placeholder="'Nom de la ' + getStructureLabel().toLowerCase()"
                  @input="onStructureNameChange"
                  required
                />
                <div v-if="errors.structure_id || errors.nom_structure" class="invalid-feedback">
                  {{ errors.structure_id || errors.nom_structure }}
                </div>
              </div>

              <!-- Jours de congés totaux -->
              <div class="col-md-6">
                <label class="form-label required">Jours de congés autorisés</label>
                <div class="input-group">
                  <input
                    type="number"
                    class="form-control"
                    v-model="form.jours_conge_totaux"
                    :class="{ 'is-invalid': errors.jours_conge_totaux }"
                    min="1"
                    max="50"
                    required
                  />
                  <span class="input-group-text">jours</span>
                </div>
                <div v-if="errors.jours_conge_totaux" class="invalid-feedback">
                  {{ errors.jours_conge_totaux }}
                </div>
                <small class="form-text text-muted">
                  Nombre total de jours de congés autorisés pour l'année
                </small>
              </div>

              <!-- Périodes de fermeture -->
              <div class="col-12">
                <label class="form-label">Périodes de fermeture obligatoire</label>
                <div class="fermetures-section">
                  <div
                    v-for="(periode, index) in form.periods_fermeture"
                    :key="index"
                    class="fermeture-item"
                  >
                    <div class="row g-2 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label small">Date de début</label>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="periode.start"
                          :class="{ 'is-invalid': errors[`periods_fermeture.${index}.start`] }"
                        />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small">Date de fin</label>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="periode.end"
                          :class="{ 'is-invalid': errors[`periods_fermeture.${index}.end`] }"
                          :min="periode.start"
                        />
                      </div>
                      <div class="col-md-3">
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="periode.nom"
                          placeholder="Ex: Congés de Noël"
                        />
                      </div>
                      <div class="col-md-1">
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-danger"
                          @click="removeFermeture(index)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex gap-2 mt-2 flex-wrap">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary"
                      @click="addFermeture"
                    >
                      <i class="fas fa-plus me-1"></i>
                      Ajouter une période de fermeture
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-success"
                      @click="applyJoursFeriesRDC"
                    >
                      <i class="fas fa-calendar-check me-1"></i>
                      Appliquer les jours fériés RDC
                    </button>
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea
                  class="form-control"
                  v-model="form.notes"
                  :class="{ 'is-invalid': errors.notes }"
                  rows="3"
                  placeholder="Notes supplémentaires sur le planning..."
                ></textarea>
                <div v-if="errors.notes" class="invalid-feedback">{{ errors.notes }}</div>
              </div>
            </div>

            <!-- Aperçu -->
            <div v-if="form.nom_structure && form.jours_conge_totaux" class="planning-preview mt-4">
              <h6 class="text-primary">
                <i class="fas fa-eye me-1"></i>
                Aperçu du planning
              </h6>
              <div class="preview-card">
                <div class="row g-3">
                  <div class="col-md-6">
                    <strong>Structure:</strong> {{ form.nom_structure }}
                  </div>
                  <div class="col-md-6">
                    <strong>Année:</strong> {{ form.annee }}
                  </div>
                  <div class="col-md-6">
                    <strong>Jours autorisés:</strong> {{ form.jours_conge_totaux }} jours
                  </div>
                  <div class="col-md-6">
                    <strong>Périodes de fermeture:</strong> {{ form.periods_fermeture.length }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              @click="$emit('close')"
              :disabled="submitting"
            >
              Annuler
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="submitting || !isFormValid"
            >
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-save me-2"></i>
              {{ submitting ? 'Création...' : 'Créer le planning' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  departments: {
    type: Array,
    default: () => []
  },
  scopeInfo: {
    type: Object,
    default: () => ({ is_provincial: false, province_id: null, province_nom: null })
  }
})

const emit = defineEmits(['close', 'created'])

const ui = useUiStore()

// État
const submitting = ref(false)
const errors = ref({})

const form = ref({
  annee: new Date().getFullYear(),
  type_structure: '',
  structure_id: '',
  nom_structure: '',
  jours_conge_totaux: 30,
  periods_fermeture: [],
  notes: ''
})

// Computed
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => currentYear - 1 + i)
})

const isAutoFilledStructure = computed(() => {
  return props.scopeInfo.is_provincial && form.value.type_structure === 'sep'
})

const isFormValid = computed(() => {
  return form.value.annee &&
         form.value.type_structure &&
         (form.value.structure_id || form.value.nom_structure) &&
         form.value.jours_conge_totaux > 0
})

// Méthodes
function getStructureLabel() {
  const labels = {
    'department': 'Département',
    'sen': 'SEN',
    'sena': 'SENA',
    'sep': 'SEP Provincial',
    'local': 'Structure Locale'
  }
  return labels[form.value.type_structure] || 'Structure'
}

function onStructureTypeChange() {
  form.value.structure_id = ''
  form.value.nom_structure = ''
  errors.value = {}

  // Auto-fill province name for provincial SEP
  if (props.scopeInfo.is_provincial && form.value.type_structure === 'sep') {
    form.value.nom_structure = 'SEP ' + props.scopeInfo.province_nom
    form.value.structure_id = Date.now()
  }
}

function onStructureChange() {
  if (form.value.type_structure === 'department') {
    const dept = props.departments.find(d => d.id == form.value.structure_id)
    if (dept) {
      form.value.nom_structure = dept.nom
    }
  }
}

function onStructureNameChange() {
  if (form.value.type_structure !== 'department') {
    // Générer un ID fictif pour les autres structures
    form.value.structure_id = Date.now()
  }
}

function getJoursFeriesRDC(annee) {
  return [
    { start: `${annee}-01-01`, end: `${annee}-01-01`, nom: "Jour de l'An" },
    { start: `${annee}-01-04`, end: `${annee}-01-04`, nom: "Journée des Martyrs de l'Indépendance" },
    { start: `${annee}-01-16`, end: `${annee}-01-17`, nom: 'Journée des Héros Nationaux (Kabila & Lumumba)' },
    { start: `${annee}-04-02`, end: `${annee}-04-02`, nom: 'Journée de Simon Kimbangu' },
    { start: `${annee}-05-01`, end: `${annee}-05-01`, nom: 'Fête du Travail' },
    { start: `${annee}-05-17`, end: `${annee}-05-17`, nom: 'Journée de la Libération' },
    { start: `${annee}-06-30`, end: `${annee}-06-30`, nom: "Fête de l'Indépendance" },
    { start: `${annee}-08-01`, end: `${annee}-08-01`, nom: 'Fête des Parents' },
    { start: `${annee}-12-25`, end: `${annee}-12-25`, nom: 'Noël' },
  ]
}

function applyJoursFeriesRDC() {
  const annee = form.value.annee || new Date().getFullYear()
  const feries = getJoursFeriesRDC(annee)
  // Éviter les doublons : ne pas ajouter un jour férié déjà présent
  const existing = form.value.periods_fermeture.map(p => p.start + p.nom)
  feries.forEach(f => {
    if (!existing.includes(f.start + f.nom)) {
      form.value.periods_fermeture.push({ ...f })
    }
  })
}

function addFermeture() {
  form.value.periods_fermeture.push({
    start: '',
    end: '',
    nom: ''
  })
}

function removeFermeture(index) {
  form.value.periods_fermeture.splice(index, 1)
}

function handleBackdropClick() {
  emit('close')
}

async function submitForm() {
  if (submitting.value || !isFormValid.value) return

  submitting.value = true
  errors.value = {}

  try {
    const formData = {
      ...form.value,
      // Filtrer les périodes de fermeture vides
      periods_fermeture: form.value.periods_fermeture.filter(p => p.start && p.end)
    }

    const response = await client.post('/holiday-plannings', formData)

    ui.addToast('Planning créé avec succès', 'success')
    emit('created', response.data.planning)

  } catch (error) {
    console.error('Erreur création planning:', error)

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      ui.addToast(
        error.response?.data?.message || 'Erreur lors de la création du planning',
        'danger'
      )
    }
  } finally {
    submitting.value = false
  }
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue) {
    // Réinitialiser le formulaire
    form.value = {
      annee: new Date().getFullYear(),
      type_structure: '',
      structure_id: '',
      nom_structure: '',
      jours_conge_totaux: 30,
      periods_fermeture: [],
      notes: ''
    }
    errors.value = {}
  }
})
</script>

<style scoped>
.required::after {
  content: ' *';
  color: #dc3545;
}

.fermetures-section {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1rem;
  background: #f8f9fa;
}

.fermeture-item {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 0.75rem;
}

.fermeture-item:last-child {
  margin-bottom: 0;
}

.planning-preview {
  border-top: 1px solid #e9ecef;
  padding-top: 1rem;
}

.preview-card {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 0.5rem;
}

.modal-dialog {
  max-width: 800px;
}

.form-label.small {
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.input-group-text {
  background: #f8f9fa;
  border-color: #dee2e6;
}

.btn-outline-primary {
  border-color: #0077B5;
  color: #0077B5;
}

.btn-outline-primary:hover {
  background-color: #0077B5;
  border-color: #0077B5;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }

  .fermeture-item .row {
    row-gap: 0.5rem;
  }
}
</style>