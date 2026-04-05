<template>
  <teleport to="body">
    <div v-if="show" class="rcm-overlay" @click.self="close">
      <div class="rcm-dialog">
        <div class="rcm-header">
          <h5 class="rcm-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle Demande</h5>
          <button class="rcm-close" @click="close"><i class="fas fa-times"></i></button>
        </div>
        <div class="rcm-body">
          <form @submit.prevent="handleSubmit" enctype="multipart/form-data">
            <!-- Agent (RH only) -->
            <div v-if="isRH" class="mb-3">
              <label class="rcm-label">
                <i class="fas fa-users me-1 text-muted"></i>
                Sélectionner l'agent <span class="text-danger">*</span>
              </label>
              <select v-model="form.agent_id" class="form-select form-select-sm" :class="{ 'is-invalid': errors.agent_id }">
                <option value="">-- Choisir un agent --</option>
                <option v-for="a in agents" :key="a.id" :value="a.id">
                  {{ a.prenom }} {{ a.nom }} ({{ a.id_agent }})
                </option>
              </select>
              <div v-if="errors.agent_id" class="invalid-feedback d-block">{{ errors.agent_id[0] }}</div>
            </div>

            <!-- Agent connecté (non-RH) -->
            <div v-else-if="currentAgent" class="agent-banner mb-3">
              <div class="agent-avatar">{{ initials(currentAgent) }}</div>
              <div class="flex-grow-1">
                <div class="fw-semibold small">{{ currentAgent.prenom }} {{ currentAgent.nom }}</div>
                <div class="text-muted" style="font-size:.72rem;">{{ currentAgent.id_agent }}</div>
              </div>
            </div>

            <!-- Type -->
            <div class="mb-3">
              <label class="rcm-label">
                <i class="fas fa-list-check me-1 text-muted"></i>
                Type de demande <span class="text-danger">*</span>
              </label>
              <div class="type-grid-modal">
                <div
                  v-for="t in typeOptions" :key="t.value"
                  class="type-card-modal"
                  :class="{ active: form.type === t.value }"
                  @click="form.type = t.value"
                >
                  <i :class="t.icon"></i>
                  <span>{{ t.label }}</span>
                </div>
              </div>
              <div v-if="errors.type" class="text-danger small mt-1">{{ errors.type[0] }}</div>
            </div>

            <!-- Dates -->
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="rcm-label">
                  <i class="fas fa-calendar-alt me-1 text-muted"></i>
                  Date début <span class="text-danger">*</span>
                </label>
                <input
                  type="date" v-model="form.date_debut"
                  class="form-control form-control-sm" :class="{ 'is-invalid': errors.date_debut }"
                >
                <div v-if="errors.date_debut" class="invalid-feedback d-block">{{ errors.date_debut[0] }}</div>
              </div>
              <div class="col-6">
                <label class="rcm-label">
                  <i class="fas fa-calendar-check me-1 text-muted"></i>
                  Date fin
                </label>
                <input
                  type="date" v-model="form.date_fin"
                  class="form-control form-control-sm" :class="{ 'is-invalid': errors.date_fin }"
                  :min="form.date_debut"
                >
                <div v-if="errors.date_fin" class="invalid-feedback d-block">{{ errors.date_fin[0] }}</div>
              </div>
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label class="rcm-label">
                <i class="fas fa-align-left me-1 text-muted"></i>
                Description <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="form.description" rows="3"
                class="form-control form-control-sm" :class="{ 'is-invalid': errors.description }"
                placeholder="Décrivez le motif de votre demande..."
              ></textarea>
              <div v-if="errors.description" class="invalid-feedback d-block">{{ errors.description[0] }}</div>
            </div>

            <!-- Motivation (renforcement_capacites) -->
            <div v-if="form.type === 'renforcement_capacites'" class="mb-3">
              <label class="rcm-label">
                <i class="fas fa-lightbulb me-1 text-muted"></i>
                Motivation (fonction/poste) <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="form.motivation" rows="4"
                class="form-control form-control-sm" :class="{ 'is-invalid': errors.motivation }"
                placeholder="Expliquez comment ce renforcement est lié à votre fonction..."
              ></textarea>
              <div v-if="errors.motivation" class="invalid-feedback d-block">{{ errors.motivation[0] }}</div>
            </div>

            <!-- File upload -->
            <div class="mb-3">
              <label class="rcm-label">
                <i class="fas fa-paperclip me-1 text-muted"></i>
                Lettre de demande (optionnel)
              </label>

              <div v-if="!filePreview" class="upload-zone-sm" @click="$refs.fileInput.click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Cliquer pour parcourir</span>
              </div>

              <div v-else class="file-preview-sm">
                <i class="fas fa-file-alt"></i>
                <div class="flex-grow-1">
                  <div class="file-name-sm">{{ filePreview.name }}</div>
                  <div class="file-size-sm">{{ filePreview.size }}</div>
                </div>
                <button type="button" class="btn-remove-sm" @click="removeFile">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <input
                ref="fileInput" type="file" class="d-none"
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                @change="handleFileSelect"
              >
              <div v-if="errors.lettre_demande" class="text-danger small mt-1">{{ errors.lettre_demande[0] }}</div>
            </div>

            <!-- Submit -->
            <div class="d-flex gap-2 justify-content-end">
              <button type="button" class="btn btn-sm btn-secondary" @click="close">Annuler</button>
              <button type="submit" class="btn btn-sm btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-paper-plane me-1"></i>
                Soumettre
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { create } from '@/api/requests'
import client from '@/api/client'

const props = defineProps({
  show: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'created'])

const auth = useAuthStore()
const ui = useUiStore()

const isRH = computed(() => auth.hasAdminAccess)
const currentAgent = computed(() => auth.agent)

const form = ref({
  agent_id: '',
  type: '',
  date_debut: '',
  date_fin: '',
  description: '',
  motivation: '',
})

const agents = ref([])
const errors = ref({})
const submitting = ref(false)
const selectedFile = ref(null)
const filePreview = ref(null)
const fileInput = ref(null)

const typeOptions = [
  { value: 'conge', label: 'Congé', icon: 'fas fa-umbrella-beach' },
  { value: 'absence', label: 'Absence', icon: 'fas fa-user-slash' },
  { value: 'permission', label: 'Permission', icon: 'fas fa-door-open' },
  { value: 'renforcement_capacites', label: 'Renforcement des capacités', icon: 'fas fa-graduation-cap' },
]

function initials(agent) {
  if (!agent) return ''
  return ((agent.prenom || '').charAt(0) + (agent.nom || '').charAt(0)).toUpperCase()
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    const sizeMb = (file.size / 1024 / 1024).toFixed(2)
    filePreview.value = { name: file.name, size: sizeMb + ' Mo' }
  }
}

function removeFile() {
  selectedFile.value = null
  filePreview.value = null
  if (fileInput.value) fileInput.value.value = ''
}

function close() {
  emit('close')
}

function resetForm() {
  form.value = {
    agent_id: currentAgent.value?.id || '',
    type: '',
    date_debut: '',
    date_fin: '',
    description: '',
    motivation: '',
  }
  errors.value = {}
  removeFile()
}

async function handleSubmit() {
  errors.value = {}
  submitting.value = true

  const formData = new FormData()
  formData.append('type', form.value.type)
  formData.append('description', form.value.description)
  formData.append('date_debut', form.value.date_debut)
  if (form.value.date_fin) formData.append('date_fin', form.value.date_fin)
  if (form.value.motivation) formData.append('motivation', form.value.motivation)
  if (form.value.agent_id) formData.append('agent_id', form.value.agent_id)
  if (selectedFile.value) formData.append('lettre_demande', selectedFile.value)

  try {
    await create(formData)
    ui.addToast('Demande créée avec succès.', 'success')
    resetForm()
    emit('created')
    close()
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de la création.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

watch(() => props.show, async (newVal) => {
  if (newVal) {
    resetForm()
    if (isRH.value && agents.value.length === 0) {
      try {
        const { data } = await client.get('/agents', { params: { actifs: 1 } })
        const raw = data.data ?? data
        agents.value = Array.isArray(raw) && raw[0]?.agents
          ? raw.flatMap(g => g.agents)
          : raw
      } catch {
        // Silently fail
      }
    }
  }
})
</script>

<style scoped>
/* Overlay */
.rcm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 2100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Dialog */
.rcm-dialog {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Header */
.rcm-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 2px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, #f8fafc, transparent);
}

.rcm-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 800;
  color: #1e293b;
}

.rcm-close {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.rcm-close:hover {
  background: #f1f5f9;
  color: #475569;
}

/* Body */
.rcm-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.rcm-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.375rem;
}

/* Agent banner */
.agent-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
  border: 2px solid #0077B5;
  border-radius: 12px;
}

.agent-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
}

/* Type grid - compact */
.type-grid-modal {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.type-card-modal {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s;
  background: #fff;
  font-size: 0.85rem;
  font-weight: 600;
}

.type-card-modal:hover {
  border-color: #94a3b8;
  transform: translateY(-1px);
}

.type-card-modal.active {
  border-color: #0077B5;
  background: #f0f9ff;
  color: #0077B5;
}

.type-card-modal i {
  font-size: 1.1rem;
  color: #94a3b8;
}

.type-card-modal.active i {
  color: #0077B5;
}

/* Upload zone - compact */
.upload-zone-sm {
  border: 2px dashed #d1d5db;
  border-radius: 10px;
  padding: 1rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.85rem;
}

.upload-zone-sm:hover {
  border-color: #0077B5;
  background: #f0f9ff;
}

.upload-zone-sm i {
  font-size: 1.5rem;
  color: #0077B5;
}

/* File preview - compact */
.file-preview-sm {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 10px;
}

.file-preview-sm i {
  font-size: 1.2rem;
  color: #16a34a;
}

.file-name-sm {
  font-weight: 600;
  font-size: 0.8rem;
  color: #1e293b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-size-sm {
  font-size: 0.7rem;
  color: #94a3b8;
}

.btn-remove-sm {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #ef4444;
  border-radius: 6px;
  padding: 0.25rem 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.8rem;
}

.btn-remove-sm:hover {
  background: #fee2e2;
}

/* Forms */
.form-control,
.form-select {
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-size: 0.85rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #0077B5;
  box-shadow: 0 0 0 3px rgba(0, 119, 181, 0.1);
}

/* Mobile */
@media (max-width: 575.98px) {
  .rcm-dialog {
    max-width: 100%;
    border-radius: 16px 16px 0 0;
    max-height: 95vh;
  }

  .type-grid-modal {
    grid-template-columns: 1fr;
  }
}
</style>
