<template>
  <div
    v-if="show"
    class="modal fade show holiday-planning-modal"
    style="display: block; background: rgba(0,0,0,0.5); z-index: 2000 !important;"
    @click="handleBackdropClick"
  >
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="z-index: 2001 !important;" @click.stop>
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
                <!-- Département -->
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
                <!-- SEP / Structure Locale → dropdown de provinces -->
                <select
                  v-else-if="form.type_structure === 'sep' || form.type_structure === 'local'"
                  class="form-select"
                  v-model="form.structure_id"
                  :class="{ 'is-invalid': errors.structure_id }"
                  @change="onProvinceChange"
                  required
                >
                  <option value="">Sélectionner une province</option>
                  <option v-for="prov in provinces" :key="prov.id" :value="prov.id">
                    {{ prov.nom }}
                  </option>
                </select>
                <!-- Autres types (fallback) -->
                <input
                  v-else
                  type="text"
                  class="form-control"
                  v-model="form.nom_structure"
                  :class="{ 'is-invalid': errors.nom_structure }"
                  :placeholder="'Nom de la ' + getStructureLabel().toLowerCase()"
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

              <div class="col-12">
                <div class="planning-agents-head">
                  <div>
                    <label class="form-label required mb-1">Agents et échéances de congé</label>
                    <small class="text-muted d-block">Renseignez la période annuelle de chaque agent avant de créer le planning.</small>
                  </div>
                  <span class="badge bg-primary">{{ form.entries.length }} agent(s)</span>
                </div>

                <div v-if="form.entries.length" class="planning-agents-table">
                  <div class="planning-agent-row planning-agent-labels">
                    <span>Agent</span>
                    <span>Début</span>
                    <span>Fin</span>
                    <span>Jours ouvrables</span>
                  </div>
                  <div v-for="(entry, index) in form.entries" :key="entry.agent_id" class="planning-agent-row">
                    <div class="planning-agent-name">
                      <strong>{{ agentName(entry.agent) }}</strong>
                      <small>{{ entry.agent?.fonction || 'Agent' }}</small>
                    </div>
                    <div class="planning-agent-field" data-label="Début">
                      <input
                        v-model="entry.date_debut"
                        type="date"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': entryError(index, 'date_debut') }"
                        :min="`${form.annee}-01-01`"
                        :max="`${form.annee}-12-31`"
                        required
                      />
                    </div>
                    <div class="planning-agent-field" data-label="Fin">
                      <input
                        v-model="entry.date_fin"
                        type="date"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': entryError(index, 'date_fin') }"
                        :min="entry.date_debut || `${form.annee}-01-01`"
                        :max="`${form.annee}-12-31`"
                        required
                      />
                    </div>
                    <div class="planning-agent-days" :class="{ invalid: entry.date_debut && entry.date_fin && entryWorkingDays(entry) < 1 }">
                      {{ entryWorkingDays(entry) }} jour(s)
                    </div>
                    <div v-if="entryError(index, 'agent_id') || entryError(index, 'date_debut') || entryError(index, 'date_fin')" class="planning-agent-error">
                      {{ entryError(index, 'agent_id') || entryError(index, 'date_debut') || entryError(index, 'date_fin') }}
                    </div>
                  </div>
                </div>
                <div v-else class="alert alert-warning mb-0">
                  <i class="fas fa-exclamation-triangle me-1"></i>
                  Aucun agent actif n’est rattaché à votre périmètre. Le planning ne peut pas être créé.
                </div>
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
  provinces: {
    type: Array,
    default: () => []
  },
  agents: {
    type: Array,
    default: () => []
  },
  scopeInfo: {
    type: Object,
    default: () => ({ is_provincial: false, province_id: null, province_nom: null })
  },
  workflow: {
    type: Object,
    default: () => ({ responsibility: null })
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
  entries: [],
  periods_fermeture: [],
  notes: ''
})

// Computed
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => currentYear - 1 + i)
})

const isAutoFilledStructure = computed(() => {
  const type = form.value.type_structure
  if (type === 'sen' || type === 'sena') return true
  if (props.scopeInfo.is_provincial && (type === 'sep' || type === 'local')) return true
  return false
})

const isFormValid = computed(() => {
  return form.value.annee &&
         form.value.type_structure &&
         (form.value.structure_id || form.value.nom_structure) &&
         form.value.jours_conge_totaux > 0 &&
         form.value.entries.length > 0 &&
         form.value.entries.every(entry => entry.date_debut && entry.date_fin && entryWorkingDays(entry) > 0)
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

function agentName(agent) {
  return agent?.nom_complet || [agent?.prenom, agent?.nom, agent?.postnom].filter(Boolean).join(' ') || 'Agent'
}

function entryError(index, field) {
  const value = errors.value[`entries.${index}.${field}`]
  return Array.isArray(value) ? value[0] : value || ''
}

function entryWorkingDays(entry) {
  if (!entry.date_debut || !entry.date_fin) return 0
  const start = new Date(`${entry.date_debut}T00:00:00`)
  const end = new Date(`${entry.date_fin}T00:00:00`)
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return 0

  let days = 0
  const current = new Date(start)
  while (current <= end) {
    if (current.getDay() !== 0 && current.getDay() !== 6) days++
    current.setDate(current.getDate() + 1)
  }
  return days
}

function resetEntries() {
  const type = form.value.type_structure
  const structureId = Number(form.value.structure_id)
  const scopedAgents = props.agents.filter(agent => {
    if (!type || !structureId) return true
    if (type === 'department') return Number(agent.departement_id) === structureId
    if (type === 'sep' || type === 'local') return Number(agent.province_id) === structureId
    if (type === 'sen' || type === 'sena') return !agent.province_id
    return false
  })

  form.value.entries = scopedAgents.map(agent => ({
    agent_id: agent.id,
    agent,
    date_debut: '',
    date_fin: '',
    observation: ''
  }))
}

function applyResponsibility() {
  const responsibility = props.workflow?.responsibility
  if (!responsibility) return

  form.value.type_structure = responsibility.type
  form.value.structure_id = responsibility.structure_id

  if (responsibility.type === 'department') {
    form.value.nom_structure = props.departments.find(item => item.id == responsibility.structure_id)?.nom || responsibility.label
  } else {
    const province = props.provinces.find(item => item.id == responsibility.structure_id)
    form.value.nom_structure = `${responsibility.type === 'local' ? 'SEL' : 'SEP'} ${province?.nom || responsibility.label}`
  }
}

function onStructureTypeChange() {
  form.value.structure_id = ''
  form.value.nom_structure = ''
  errors.value = {}

  const type = form.value.type_structure

  if (type === 'sen') {
    form.value.structure_id = 1
    form.value.nom_structure = 'Secrétariat Exécutif National'
  } else if (type === 'sena') {
    form.value.structure_id = 1
    form.value.nom_structure = 'Secrétariat Exécutif National Adjoint'
  } else if (props.scopeInfo.is_provincial && type === 'sep') {
    form.value.structure_id = props.scopeInfo.province_id
    form.value.nom_structure = 'SEP ' + props.scopeInfo.province_nom
  } else if (props.scopeInfo.is_provincial && type === 'local') {
    form.value.structure_id = props.scopeInfo.province_id
    form.value.nom_structure = 'SEL ' + props.scopeInfo.province_nom
  }

  resetEntries()
}

function onProvinceChange() {
  const type = form.value.type_structure
  const prov = props.provinces.find(p => p.id == form.value.structure_id)
  if (prov) {
    form.value.nom_structure = type === 'local' ? 'SEL ' + prov.nom : 'SEP ' + prov.nom
  }
  resetEntries()
}

function onStructureChange() {
  if (form.value.type_structure === 'department') {
    const dept = props.departments.find(d => d.id == form.value.structure_id)
    if (dept) {
      form.value.nom_structure = dept.nom
    }
  }
  resetEntries()
}

function onStructureNameChange() {
  // nom_structure mis à jour directement par v-model; structure_id géré par onStructureTypeChange
}

function getJoursFeriesRDC(annee) {
  return [
    { start: `${annee}-01-01`, end: `${annee}-01-01`, nom: "Jour de l'An" },
    { start: `${annee}-01-04`, end: `${annee}-01-04`, nom: "Journée des Martyrs de l'Indépendance" },
    { start: `${annee}-01-16`, end: `${annee}-01-17`, nom: 'Journée des Héros Nationaux (Kabila & Lumumba)' },
    { start: `${annee}-04-06`, end: `${annee}-04-06`, nom: 'Journée du Combat de Simon Kimbangu et de la Conscience Africaine' },
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
      entries: [],
      periods_fermeture: [],
      notes: ''
    }
    resetEntries()
    applyResponsibility()
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

.planning-agents-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: .75rem;
}

.planning-agents-table {
  max-height: min(340px, 42dvh);
  overflow: auto;
  border: 1px solid #dbe4ef;
  border-radius: 8px;
}

.planning-agent-row {
  display: grid;
  grid-template-columns: minmax(180px, 1.4fr) minmax(140px, 1fr) minmax(140px, 1fr) 110px;
  align-items: center;
  gap: .75rem;
  padding: .75rem;
  background: #fff;
  border-bottom: 1px solid #e9ecef;
}

.planning-agent-row:last-child {
  border-bottom: 0;
}

.planning-agent-labels {
  position: sticky;
  top: 0;
  z-index: 1;
  color: #64748b;
  background: #f8fafc;
  font-size: .76rem;
  font-weight: 800;
  text-transform: uppercase;
}

.planning-agent-name,
.planning-agent-name small {
  display: block;
  min-width: 0;
}

.planning-agent-name small {
  color: #64748b;
}

.planning-agent-days {
  color: #166534;
  font-weight: 800;
}

.planning-agent-days.invalid,
.planning-agent-error {
  color: #b91c1c;
}

.planning-agent-error {
  grid-column: 2 / -1;
  font-size: .78rem;
}

@media (max-width: 767.98px) {
  .planning-agents-head {
    align-items: flex-start;
  }

  .planning-agents-table {
    max-height: 55dvh;
    border: 0;
    border-radius: 0;
  }

  .planning-agent-labels {
    display: none;
  }

  .planning-agent-row {
    grid-template-columns: 1fr;
    gap: .65rem;
    margin-bottom: .75rem;
    border: 1px solid #dbe4ef;
    border-radius: 8px;
    padding: .85rem;
  }

  .planning-agent-field::before {
    content: attr(data-label);
    display: block;
    margin-bottom: .25rem;
    color: #64748b;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
  }

  .planning-agent-error {
    grid-column: 1;
  }
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
  width: min(1080px, 100%);
  max-width: 1080px;
  height: 100%;
  margin: 0 auto;
}

.modal-content {
  height: 100%;
  max-height: 100%;
  overflow: hidden;
}

.holiday-planning-modal {
  --planning-modal-nav-offset: 64px;
  inset: var(--planning-modal-nav-offset) 0 0;
  width: auto;
  height: auto;
  padding: 1rem;
  overflow: hidden;
}

.modal-content > form {
  display: flex;
  flex: 1 1 auto;
  flex-direction: column;
  min-height: 0;
}

.modal-body {
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.modal-header,
.modal-footer {
  flex: 0 0 auto;
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
  .holiday-planning-modal {
    --planning-modal-nav-offset: 58px;
    padding: .5rem;
  }

  .modal-dialog {
    width: 100%;
    max-width: 100%;
    margin: 0;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .modal-footer {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .5rem;
  }

  .modal-footer .btn {
    min-width: 0;
    margin: 0;
  }

  .fermeture-item .row {
    row-gap: 0.5rem;
  }
}
</style>
