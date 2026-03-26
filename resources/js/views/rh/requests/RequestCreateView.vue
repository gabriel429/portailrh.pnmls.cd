<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <!-- Hero -->
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <router-link :to="{ name: 'requests.index' }" class="back-link">
              <i class="fas fa-arrow-left me-1"></i> Retour aux demandes
            </router-link>
            <h1 class="rh-title mt-2"><i class="fas fa-plus-circle me-2"></i>Nouvelle Demande</h1>
            <p class="rh-sub">Remplissez le formulaire ci-dessous pour soumettre votre demande.</p>
          </div>
        </div>
      </section>

      <div class="dash-panel mt-3">
        <div class="p-4">
          <form @submit.prevent="handleSubmit" enctype="multipart/form-data">

            <!-- Section: Agent -->
            <h6 class="section-title">
              <i class="fas fa-user me-2"></i> Demandeur
            </h6>

            <!-- Agent connecté (non-RH): affiché comme présélectionné -->
            <div v-if="!isRH && currentAgent" class="agent-selected-banner mb-4">
              <div class="d-flex align-items-center gap-3">
                <div class="agent-avatar">{{ initials(currentAgent) }}</div>
                <div class="flex-grow-1">
                  <div class="fw-semibold">{{ currentAgent.prenom }} {{ currentAgent.nom }}</div>
                  <div class="text-muted small">Matricule: {{ currentAgent.id_agent }}</div>
                </div>
                <div class="agent-status">
                  <i class="fas fa-check-circle text-success me-1"></i>
                  <span class="small fw-semibold text-success">Demandeur</span>
                </div>
              </div>
            </div>

            <!-- RH: sélection d'agent -->
            <div v-if="isRH" class="mb-4">
              <label class="form-label fw-semibold">
                <i class="fas fa-users me-1 text-muted"></i>
                Sélectionner l'agent <span class="text-danger">*</span>
              </label>
              <select v-model="form.agent_id" class="form-select" :class="{ 'is-invalid': errors.agent_id }">
                <option value="">-- Choisir un agent pour cette demande --</option>
                <option v-for="a in agents" :key="a.id" :value="a.id">
                  {{ a.prenom }} {{ a.nom }} ({{ a.id_agent }})
                </option>
              </select>
              <div v-if="errors.agent_id" class="invalid-feedback d-block">{{ errors.agent_id[0] }}</div>
              <div class="form-text">
                <i class="fas fa-info-circle me-1"></i>
                En tant qu'administrateur RH, vous pouvez créer une demande au nom d'un autre agent.
              </div>
            </div>

            <!-- Section: Type -->
            <h6 class="section-title mt-4">
              <i class="fas fa-list-check me-2"></i> Type de demande
            </h6>

            <div class="type-grid mb-3">
              <div
                v-for="t in typeOptions" :key="t.value"
                class="type-card"
                :class="{ active: form.type === t.value }"
                @click="form.type = t.value"
              >
                <i :class="t.icon" class="type-icon"></i>
                <span class="type-label">{{ t.label }}</span>
              </div>
            </div>
            <div v-if="errors.type" class="text-danger small mb-3"><i class="fas fa-exclamation-circle me-1"></i>{{ errors.type[0] }}</div>

            <!-- Section: Details -->
            <h6 class="section-title mt-4">
              <i class="fas fa-info-circle me-2"></i> Details de la demande
            </h6>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">
                  <i class="fas fa-calendar-alt me-1 text-muted"></i> Date de debut <span class="text-danger">*</span>
                </label>
                <input
                  type="date" v-model="form.date_debut"
                  class="form-control" :class="{ 'is-invalid': errors.date_debut }"
                >
                <div v-if="errors.date_debut" class="invalid-feedback d-block">{{ errors.date_debut[0] }}</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">
                  <i class="fas fa-calendar-check me-1 text-muted"></i> Date de fin
                  <span class="text-muted fw-normal">(optionnel)</span>
                </label>
                <input
                  type="date" v-model="form.date_fin"
                  class="form-control" :class="{ 'is-invalid': errors.date_fin }"
                  :min="form.date_debut"
                >
                <div v-if="errors.date_fin" class="invalid-feedback d-block">{{ errors.date_fin[0] }}</div>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">
                <i class="fas fa-align-left me-1 text-muted"></i> Description <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="form.description" rows="4"
                class="form-control" :class="{ 'is-invalid': errors.description }"
                placeholder="Decrivez le motif de votre demande..."
              ></textarea>
              <div v-if="errors.description" class="invalid-feedback d-block">{{ errors.description[0] }}</div>
            </div>

            <!-- Section: File upload -->
            <h6 class="section-title mt-4">
              <i class="fas fa-paperclip me-2"></i> Lettre de demande
              <span class="text-muted fw-normal small ms-1">(optionnel)</span>
            </h6>

            <!-- Upload zone -->
            <div
              v-if="!filePreview"
              class="upload-zone"
              @click="$refs.fileInput.click()"
              @dragover.prevent="isDragging = true"
              @dragleave="isDragging = false"
              @drop.prevent="handleDrop"
              :class="{ dragging: isDragging }"
            >
              <i class="fas fa-cloud-upload-alt upload-icon"></i>
              <div class="fw-semibold small">Glissez votre fichier ici ou cliquez pour parcourir</div>
              <div class="text-muted" style="font-size:.75rem;">PDF, DOC, DOCX, JPG, PNG - Max 5 Mo</div>
            </div>

            <!-- File preview -->
            <div v-else class="file-preview">
              <div class="file-icon"><i class="fas fa-file-alt"></i></div>
              <div class="file-info">
                <div class="file-name">{{ filePreview.name }}</div>
                <div class="file-size">{{ filePreview.size }}</div>
              </div>
              <button type="button" class="btn-rh danger-sm" @click="removeFile">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>

            <input
              ref="fileInput" type="file" class="d-none"
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
              @change="handleFileSelect"
            >
            <div v-if="errors.lettre_demande" class="text-danger small mt-1">{{ errors.lettre_demande[0] }}</div>

            <!-- Submit -->
            <hr class="my-4">
            <div class="submit-bar">
              <small class="text-muted"><i class="fas fa-lock me-1"></i> Votre demande sera envoyee pour approbation</small>
              <div class="d-flex gap-2">
                <router-link :to="{ name: 'requests.index' }" class="btn-rh outline">Annuler</router-link>
                <button type="submit" class="btn-rh main" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-paper-plane me-1"></i>
                  Soumettre
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { create } from '@/api/requests'
import client from '@/api/client'

const router = useRouter()
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
})

const agents = ref([])
const errors = ref({})
const submitting = ref(false)
const isDragging = ref(false)
const selectedFile = ref(null)
const filePreview = ref(null)
const fileInput = ref(null)

const typeOptions = [
  { value: 'conge', label: 'Conge', icon: 'fas fa-umbrella-beach' },
  { value: 'absence', label: 'Absence', icon: 'fas fa-user-slash' },
  { value: 'permission', label: 'Permission', icon: 'fas fa-door-open' },
  { value: 'formation', label: 'Formation', icon: 'fas fa-graduation-cap' },
  { value: 'renforcement_capacites', label: 'Renforcement', icon: 'fas fa-chart-line' },
]

function initials(agent) {
  if (!agent) return ''
  return ((agent.prenom || '').charAt(0) + (agent.nom || '').charAt(0)).toUpperCase()
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) setFile(file)
}

function handleDrop(event) {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) setFile(file)
}

function setFile(file) {
  selectedFile.value = file
  const sizeMb = (file.size / 1024 / 1024).toFixed(2)
  filePreview.value = { name: file.name, size: sizeMb + ' Mo' }
}

function removeFile() {
  selectedFile.value = null
  filePreview.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function handleSubmit() {
  errors.value = {}
  submitting.value = true

  const formData = new FormData()
  formData.append('type', form.value.type)
  formData.append('description', form.value.description)
  formData.append('date_debut', form.value.date_debut)
  if (form.value.date_fin) formData.append('date_fin', form.value.date_fin)

  // Toujours envoyer agent_id:
  // - Pour RH: l'agent sélectionné (ou connecté par défaut)
  // - Pour agents normaux: l'agent connecté obligatoirement
  if (form.value.agent_id) {
    formData.append('agent_id', form.value.agent_id)
  }

  if (selectedFile.value) formData.append('lettre_demande', selectedFile.value)

  try {
    await create(formData)
    ui.addToast('Demande créée avec succès.', 'success')
    router.push({ name: 'requests.index' })
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

onMounted(async () => {
  if (currentAgent.value) {
    form.value.agent_id = currentAgent.value.id
  }

  if (isRH.value) {
    try {
      const { data } = await client.get('/agents', { params: { actifs: 1 } })
      agents.value = data.data ?? data
    } catch {
      // Silently fail
    }
  }
})
</script>

<style scoped>
.back-link { color: rgba(255,255,255,.7); text-decoration: none; font-size: .85rem; transition: color .2s; }
.back-link:hover { color: #fff; }

.section-title { font-weight: 700; font-size: .92rem; color: #1e293b; padding-bottom: .5rem; border-bottom: 2px solid #f1f5f9; }
.section-title i { color: #0077B5; }

/* Agent banners */
.agent-banner { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
.agent-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #0077B5, #005a87); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; }

/* Agent sélectionné (pour agents non-RH) */
.agent-selected-banner {
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
  border: 2px solid #0077B5;
  border-radius: 16px;
  position: relative;
}
.agent-selected-banner::before {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: linear-gradient(135deg, #0077B5, #0ea5e9);
  border-radius: 16px;
  z-index: -1;
  opacity: 0.1;
}
.agent-status {
  display: flex;
  align-items: center;
  padding: 0.375rem 0.75rem;
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.2);
  border-radius: 8px;
}

/* Type grid */
.type-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: .6rem; }
.type-card { display: flex; flex-direction: column; align-items: center; gap: .45rem; padding: 1rem .5rem; border-radius: 12px; border: 2px solid #e2e8f0; cursor: pointer; transition: all .2s; background: #fff; }
.type-card:hover { border-color: #94a3b8; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.06); }
.type-card.active { border-color: #0077B5; background: #f0f9ff; }
.type-card .type-icon { font-size: 1.4rem; color: #94a3b8; transition: color .2s; }
.type-card.active .type-icon { color: #0077B5; }
.type-card .type-label { font-size: .75rem; font-weight: 600; color: #64748b; }
.type-card.active .type-label { color: #0077B5; }

/* Form */
.form-control, .form-select { border-radius: 10px; border: 1px solid #e2e8f0; font-size: .88rem; }
.form-control:focus, .form-select:focus { border-color: #0077B5; box-shadow: 0 0 0 3px rgba(0,119,181,.1); }
.form-label { font-size: .85rem; color: #475569; }

/* Upload zone */
.upload-zone { border: 2px dashed #d1d5db; border-radius: 14px; padding: 2rem; text-align: center; cursor: pointer; transition: all .2s; }
.upload-zone:hover { border-color: #0077B5; background: #f0f9ff; }
.upload-zone.dragging { border-color: #0077B5; background: #f0f9ff; }
.upload-icon { font-size: 2rem; color: #0077B5; margin-bottom: .5rem; display: block; }

/* File preview */
.file-preview { display: flex; align-items: center; gap: .75rem; padding: 1rem; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; }
.file-icon { width: 38px; height: 38px; border-radius: 10px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.file-info { flex-grow: 1; min-width: 0; }
.file-name { font-weight: 600; font-size: .85rem; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.file-size { font-size: .72rem; color: #94a3b8; }
.btn-rh.danger-sm { width: 32px; height: 32px; border-radius: 8px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; }
.btn-rh.danger-sm:hover { background: #fee2e2; }

/* Submit bar */
.submit-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; }

/* Buttons */
.btn-rh { display: inline-flex; align-items: center; gap: .35rem; padding: .55rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: .85rem; border: none; cursor: pointer; transition: all .2s; text-decoration: none; }
.btn-rh.main { background: linear-gradient(135deg, #0077B5, #005a87); color: #fff; }
.btn-rh.main:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,119,181,.3); }
.btn-rh.main:disabled { opacity: .6; transform: none; }
.btn-rh.outline { background: #fff; color: #64748b; border: 1px solid #e2e8f0; }
.btn-rh.outline:hover { background: #f8fafc; color: #334155; }

@media (max-width: 767.98px) {
  .type-grid { grid-template-columns: repeat(3, 1fr); }
  .type-card { padding: .75rem .35rem; }
  .type-card .type-icon { font-size: 1.1rem; }
  .type-card .type-label { font-size: .68rem; }
  .submit-bar { flex-direction: column; align-items: stretch; text-align: center; }
  .submit-bar > div { justify-content: center; }
}

@media (max-width: 449.98px) {
  .type-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
