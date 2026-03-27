<template>
  <div
    v-if="show"
    class="modal fade show"
    style="display: block; background: rgba(0,0,0,0.5); z-index: 2000 !important;"
    @click="handleBackdropClick"
  >
    <div class="modal-dialog modal-xl" style="z-index: 2001 !important;" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-calendar-alt me-2"></i>
            Détails du Planning - {{ planning?.nom_structure }}
          </h5>
          <button
            type="button"
            class="btn-close"
            @click="$emit('close')"
          ></button>
        </div>

        <div class="modal-body">
          <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Chargement des détails...</p>
          </div>

          <div v-else>
            <!-- Informations générales -->
            <div class="planning-info-card mb-4">
              <div class="card-header">
                <h6 class="mb-0">
                  <i class="fas fa-info-circle me-2"></i>
                  Informations Générales
                </h6>
                <div class="d-flex gap-2">
                  <button
                    v-if="canEdit && !editMode"
                    @click="toggleEditMode"
                    class="btn btn-sm btn-outline-primary"
                  >
                    <i class="fas fa-edit me-1"></i>
                    Modifier
                  </button>
                  <button
                    v-if="editMode"
                    @click="cancelEdit"
                    class="btn btn-sm btn-outline-secondary"
                  >
                    <i class="fas fa-times me-1"></i>
                    Annuler
                  </button>
                  <button
                    v-if="editMode"
                    @click="saveChanges"
                    class="btn btn-sm btn-success"
                    :disabled="submitting"
                  >
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-save me-1"></i>
                    {{ submitting ? 'Sauvegarde...' : 'Sauvegarder' }}
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Structure</label>
                    <div v-if="!editMode" class="form-control-plaintext">
                      {{ planning?.nom_structure }}
                    </div>
                    <input
                      v-else
                      type="text"
                      class="form-control"
                      v-model="editForm.nom_structure"
                      :class="{ 'is-invalid': errors.nom_structure }"
                    />
                    <div v-if="errors.nom_structure" class="invalid-feedback">
                      {{ errors.nom_structure }}
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Type de Structure</label>
                    <div class="form-control-plaintext">
                      <span class="badge bg-primary">{{ planning?.type_structure_label }}</span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Année</label>
                    <div class="form-control-plaintext">{{ planning?.annee }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Jours de congés autorisés</label>
                    <div v-if="!editMode" class="form-control-plaintext">
                      {{ planning?.jours_conge_totaux }} jours
                    </div>
                    <div v-else class="input-group">
                      <input
                        type="number"
                        class="form-control"
                        v-model="editForm.jours_conge_totaux"
                        :class="{ 'is-invalid': errors.jours_conge_totaux }"
                        min="1"
                        max="50"
                      />
                      <span class="input-group-text">jours</span>
                    </div>
                    <div v-if="errors.jours_conge_totaux" class="invalid-feedback">
                      {{ errors.jours_conge_totaux }}
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label fw-medium">Statut</label>
                    <div class="d-flex align-items-center gap-2">
                      <span
                        class="badge"
                        :class="planning?.valide ? 'bg-success' : 'bg-warning'"
                      >
                        {{ planning?.valide ? 'Validé' : 'En attente de validation' }}
                      </span>
                      <button
                        v-if="!planning?.valide && canValidate"
                        @click="validatePlanning"
                        class="btn btn-sm btn-success"
                        :disabled="submitting"
                      >
                        <i class="fas fa-check me-1"></i>
                        Valider le planning
                      </button>
                    </div>
                  </div>
                  <div class="col-12" v-if="planning?.notes">
                    <label class="form-label fw-medium">Notes</label>
                    <div v-if="!editMode" class="form-control-plaintext">
                      {{ planning?.notes }}
                    </div>
                    <textarea
                      v-else
                      class="form-control"
                      v-model="editForm.notes"
                      :class="{ 'is-invalid': errors.notes }"
                      rows="3"
                    ></textarea>
                    <div v-if="errors.notes" class="invalid-feedback">
                      {{ errors.notes }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Périodes de fermeture -->
            <div class="periods-card mb-4">
              <div class="card-header">
                <h6 class="mb-0">
                  <i class="fas fa-calendar-times me-2"></i>
                  Périodes de Fermeture Obligatoire
                </h6>
              </div>
              <div class="card-body">
                <div v-if="!editMode && (!planning?.periods_fermeture || planning?.periods_fermeture.length === 0)" class="text-muted text-center py-3">
                  Aucune période de fermeture définie
                </div>

                <div v-else-if="!editMode" class="periods-list">
                  <div
                    v-for="(periode, index) in planning?.periods_fermeture"
                    :key="index"
                    class="period-item"
                  >
                    <div class="row align-items-center">
                      <div class="col-md-4">
                        <div class="period-date">
                          <i class="fas fa-calendar-day me-2 text-primary"></i>
                          {{ formatDate(periode.start) }}
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="period-date">
                          <i class="fas fa-calendar-day me-2 text-danger"></i>
                          {{ formatDate(periode.end) }}
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="period-name">
                          <strong>{{ periode.nom || 'Période sans nom' }}</strong>
                          <small class="text-muted d-block">
                            {{ calculateDaysBetween(periode.start, periode.end) }} jour(s)
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Mode édition des périodes -->
                <div v-else class="periods-edit">
                  <div
                    v-for="(periode, index) in editForm.periods_fermeture"
                    :key="index"
                    class="period-edit-item"
                  >
                    <div class="row g-2 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label small">Date de début</label>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="periode.start"
                        />
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small">Date de fin</label>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="periode.end"
                          :min="periode.start"
                        />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small">Nom de la période</label>
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
                          @click="removePeriod(index)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary mt-3"
                    @click="addPeriod"
                  >
                    <i class="fas fa-plus me-1"></i>
                    Ajouter une période
                  </button>
                </div>
              </div>
            </div>

            <!-- Statistiques d'utilisation -->
            <div class="stats-card">
              <div class="card-header">
                <h6 class="mb-0">
                  <i class="fas fa-chart-bar me-2"></i>
                  Statistiques d'Utilisation
                </h6>
              </div>
              <div class="card-body">
                <div class="row g-4">
                  <div class="col-md-3">
                    <div class="stat-item">
                      <div class="stat-value text-primary">{{ planning?.jours_utilises || 0 }}</div>
                      <div class="stat-label">Jours utilisés</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="stat-item">
                      <div class="stat-value text-warning">{{ planning?.jours_restants || 0 }}</div>
                      <div class="stat-label">Jours restants</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="stat-item">
                      <div class="stat-value text-info">{{ planning?.total_conges || 0 }}</div>
                      <div class="stat-label">Demandes traitées</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="stat-item">
                      <div class="stat-value">{{ formatPercentage(planning?.pourcentage_utilisation) }}%</div>
                      <div class="stat-label">Taux d'utilisation</div>
                    </div>
                  </div>
                </div>

                <!-- Barre de progression -->
                <div class="progress mt-3" style="height: 12px;">
                  <div
                    class="progress-bar"
                    :class="{
                      'bg-success': (planning?.pourcentage_utilisation || 0) < 70,
                      'bg-warning': (planning?.pourcentage_utilisation || 0) >= 70 && (planning?.pourcentage_utilisation || 0) < 90,
                      'bg-danger': (planning?.pourcentage_utilisation || 0) >= 90
                    }"
                    :style="{ width: (planning?.pourcentage_utilisation || 0) + '%' }"
                  ></div>
                </div>
                <small class="text-muted">
                  {{ planning?.jours_utilises || 0 }} / {{ planning?.jours_conge_totaux || 0 }} jours utilisés
                </small>
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
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { format, differenceInDays, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  planning: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const ui = useUiStore()
const auth = useAuthStore()

// État
const loading = ref(false)
const submitting = ref(false)
const editMode = ref(false)
const errors = ref({})

const editForm = ref({
  nom_structure: '',
  jours_conge_totaux: 0,
  periods_fermeture: [],
  notes: ''
})

// Computed
const canEdit = computed(() => {
  if (!auth.user?.agent) return false
  return !props.planning?.valide || auth.user.agent.hasRole(['RH National', 'SEN'])
})

const canValidate = computed(() => {
  if (!auth.user?.agent) return false
  return auth.user.agent.hasRole(['RH National', 'SEN'])
})

// Méthodes
function formatDate(dateStr) {
  if (!dateStr) return ''
  try {
    return format(parseISO(dateStr), 'dd MMM yyyy', { locale: fr })
  } catch {
    return dateStr
  }
}

function formatPercentage(value) {
  if (value === null || value === undefined) return '0'
  return Math.round(value * 10) / 10
}

function calculateDaysBetween(start, end) {
  if (!start || !end) return 0
  try {
    return Math.abs(differenceInDays(parseISO(end), parseISO(start))) + 1
  } catch {
    return 0
  }
}

function toggleEditMode() {
  editMode.value = true
  resetEditForm()
}

function cancelEdit() {
  editMode.value = false
  errors.value = {}
}

function resetEditForm() {
  editForm.value = {
    nom_structure: props.planning?.nom_structure || '',
    jours_conge_totaux: props.planning?.jours_conge_totaux || 0,
    periods_fermeture: JSON.parse(JSON.stringify(props.planning?.periods_fermeture || [])),
    notes: props.planning?.notes || ''
  }
}

function addPeriod() {
  editForm.value.periods_fermeture.push({
    start: '',
    end: '',
    nom: ''
  })
}

function removePeriod(index) {
  editForm.value.periods_fermeture.splice(index, 1)
}

async function saveChanges() {
  if (submitting.value) return

  submitting.value = true
  errors.value = {}

  try {
    const formData = {
      ...editForm.value,
      // Filtrer les périodes vides
      periods_fermeture: editForm.value.periods_fermeture.filter(p => p.start && p.end)
    }

    const response = await client.put(`/holiday-plannings/${props.planning.id}`, formData)

    ui.addToast('Planning mis à jour avec succès', 'success')
    editMode.value = false
    emit('updated', response.data.planning)

  } catch (error) {
    console.error('Erreur mise à jour planning:', error)

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      ui.addToast(
        error.response?.data?.message || 'Erreur lors de la mise à jour',
        'danger'
      )
    }
  } finally {
    submitting.value = false
  }
}

async function validatePlanning() {
  if (!confirm('Êtes-vous sûr de vouloir valider ce planning ?')) return

  submitting.value = true

  try {
    await client.post(`/holiday-plannings/${props.planning.id}/validate`)
    ui.addToast('Planning validé avec succès', 'success')
    emit('updated')
  } catch (error) {
    console.error('Erreur validation planning:', error)
    ui.addToast(
      error.response?.data?.message || 'Erreur lors de la validation',
      'danger'
    )
  } finally {
    submitting.value = false
  }
}

function handleBackdropClick() {
  if (!editMode.value) {
    emit('close')
  }
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue) {
    editMode.value = false
    errors.value = {}
  }
})
</script>

<style scoped>
.planning-info-card,
.periods-card,
.stats-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.card-header {
  background: #f8f9fa;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-body {
  padding: 1.5rem;
}

.form-control-plaintext {
  padding: 0.375rem 0;
  margin-bottom: 0;
  background-color: transparent;
  border: solid transparent;
  border-width: 1px 0;
}

.period-item {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 0.75rem;
}

.period-item:last-child {
  margin-bottom: 0;
}

.period-date {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.period-name {
  text-align: right;
}

.period-edit-item {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 0.75rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #666;
  font-weight: 500;
}

.modal-dialog {
  max-width: 1000px;
}

.progress {
  border-radius: 6px;
}

.form-label.small {
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

/* Responsive */
@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .card-header .d-flex {
    justify-content: center;
  }

  .period-name {
    text-align: left;
    margin-top: 0.5rem;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .period-edit-item .row {
    row-gap: 0.5rem;
  }
}
</style>