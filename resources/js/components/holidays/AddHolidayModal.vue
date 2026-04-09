<template>
  <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">
            <i class="fas fa-user-clock me-2"></i>
            Planifier un congé d'agent
          </h5>
          <button type="button" class="btn-close btn-close-white" @click="$emit('close')"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="row g-3">
              <!-- Agent -->
              <div class="col-12">
                <label class="form-label fw-medium">Agent <span class="text-danger">*</span></label>
                <select
                  class="form-select"
                  v-model="form.agent_id"
                  :class="{ 'is-invalid': errors.agent_id }"
                  required
                >
                  <option value="">-- Sélectionnez un agent --</option>
                  <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                    {{ agent.nom_complet }} {{ agent.fonction ? `(${agent.fonction})` : '' }}
                  </option>
                </select>
                <div v-if="errors.agent_id" class="invalid-feedback">{{ errors.agent_id }}</div>
              </div>

              <!-- Type de congé -->
              <div class="col-md-6">
                <label class="form-label fw-medium">Type de congé <span class="text-danger">*</span></label>
                <select
                  class="form-select"
                  v-model="form.type_conge"
                  :class="{ 'is-invalid': errors.type_conge }"
                  required
                >
                  <option value="annuel">Congé annuel</option>
                  <option value="maladie">Congé maladie</option>
                  <option value="maternite">Congé maternité</option>
                  <option value="paternite">Congé paternité</option>
                  <option value="urgence">Congé d'urgence</option>
                  <option value="special">Congé spécial</option>
                </select>
                <div v-if="errors.type_conge" class="invalid-feedback">{{ errors.type_conge }}</div>
              </div>

              <!-- Planning lié (optionnel) -->
              <div class="col-md-6" v-if="plannings.length > 0">
                <label class="form-label fw-medium">Planning lié</label>
                <select class="form-select" v-model="form.holiday_planning_id">
                  <option value="">-- Aucun --</option>
                  <option v-for="p in plannings" :key="p.id" :value="p.id">
                    {{ p.nom_structure || p.type_structure }} — {{ p.annee }}
                  </option>
                </select>
              </div>

              <!-- Date de début -->
              <div class="col-md-6">
                <label class="form-label fw-medium">Date de début <span class="text-danger">*</span></label>
                <input
                  type="date"
                  class="form-control"
                  v-model="form.date_debut"
                  :class="{ 'is-invalid': errors.date_debut }"
                  required
                />
                <div v-if="errors.date_debut" class="invalid-feedback">{{ errors.date_debut }}</div>
              </div>

              <!-- Date de fin -->
              <div class="col-md-6">
                <label class="form-label fw-medium">Date de fin <span class="text-danger">*</span></label>
                <input
                  type="date"
                  class="form-control"
                  v-model="form.date_fin"
                  :min="form.date_debut"
                  :class="{ 'is-invalid': errors.date_fin }"
                  required
                />
                <div v-if="errors.date_fin" class="invalid-feedback">{{ errors.date_fin }}</div>
              </div>

              <!-- Durée calculée -->
              <div class="col-12" v-if="dureeCalculee > 0">
                <div class="alert alert-info py-2 mb-0">
                  <i class="fas fa-clock me-2"></i>
                  Durée : <strong>{{ dureeCalculee }} jour(s)</strong>
                  <span v-if="form.date_debut && form.date_fin" class="ms-2 text-muted">
                    — Reprise prévue le {{ dateReprise }}
                  </span>
                </div>
              </div>

              <!-- Observation -->
              <div class="col-12">
                <label class="form-label fw-medium">Observation</label>
                <textarea
                  class="form-control"
                  v-model="form.observation"
                  rows="2"
                  placeholder="Notes ou observation sur ce congé..."
                ></textarea>
              </div>

              <!-- Intérim assuré par -->
              <div class="col-12">
                <label class="form-label fw-medium">Intérim assuré par</label>
                <select class="form-select" v-model="form.interim_assure_par">
                  <option value="">-- Sélectionnez un agent --</option>
                  <option
                    v-for="agent in availableInterimAgents"
                    :key="agent.id"
                    :value="agent.id"
                  >
                    {{ agent.nom_complet }} {{ agent.fonction ? `(${agent.fonction})` : '' }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Erreur globale -->
            <div v-if="globalError" class="alert alert-danger mt-3 mb-0">
              <i class="fas fa-exclamation-circle me-2"></i>
              {{ globalError }}
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')" :disabled="submitting">
            Annuler
          </button>
          <button type="button" class="btn btn-primary" @click="submitForm" :disabled="submitting || !isValid">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-save me-1"></i>
            Planifier le congé
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const props = defineProps({
  show: { type: Boolean, required: true },
  agents: { type: Array, default: () => [] },
  plannings: { type: Array, default: () => [] }
})

const emit = defineEmits(['close', 'created'])

const ui = useUiStore()
const submitting = ref(false)
const globalError = ref('')
const errors = ref({})

const form = ref({
  agent_id: '',
  type_conge: 'annuel',
  date_debut: '',
  date_fin: '',
  observation: '',
  interim_assure_par: '',
  holiday_planning_id: ''
})

const dureeCalculee = computed(() => {
  if (!form.value.date_debut || !form.value.date_fin) return 0
  const start = new Date(form.value.date_debut)
  const end = new Date(form.value.date_fin)
  if (end < start) return 0
  return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1
})

const dateReprise = computed(() => {
  if (!form.value.date_fin) return ''
  const d = new Date(form.value.date_fin)
  d.setDate(d.getDate() + 1)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
})

const availableInterimAgents = computed(() => {
  return props.agents.filter(a => a.id !== parseInt(form.value.agent_id))
})

const isValid = computed(() => {
  return form.value.agent_id && form.value.date_debut && form.value.date_fin && form.value.type_conge
})

async function submitForm() {
  if (!isValid.value || submitting.value) return

  submitting.value = true
  globalError.value = ''
  errors.value = {}

  try {
    const payload = {
      agent_id: form.value.agent_id,
      type_conge: form.value.type_conge,
      date_debut: form.value.date_debut,
      date_fin: form.value.date_fin,
      motif: form.value.observation || 'Congé planifié par RH',
      observation: form.value.observation || null,
      interim_assure_par: form.value.interim_assure_par || null,
      holiday_planning_id: form.value.holiday_planning_id || null,
      is_planning: true
    }

    await client.post('/holidays', payload)
    ui.addToast('Congé planifié avec succès', 'success')
    emit('created')
  } catch (error) {
    if (error.response?.status === 422) {
      const data = error.response.data
      if (data.errors) {
        errors.value = {}
        Object.keys(data.errors).forEach(key => {
          errors.value[key] = data.errors[key][0]
        })
      }
      globalError.value = data.message || 'Erreur de validation'
    } else {
      globalError.value = 'Une erreur est survenue lors de la création du congé'
    }
  } finally {
    submitting.value = false
  }
}
</script>
