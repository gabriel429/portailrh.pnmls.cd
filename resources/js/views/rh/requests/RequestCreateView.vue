<template>
  <div class="py-4" style="max-width: 760px; margin: 0 auto;">
    <!-- Header -->
    <div class="mb-4">
      <router-link :to="{ name: 'requests.index' }" class="text-muted text-decoration-none small">
        <i class="fas fa-arrow-left me-1"></i> Retour aux demandes
      </router-link>
      <h4 class="mt-2 mb-1"><i class="fas fa-plus-circle me-2"></i>Nouvelle Demande</h4>
      <p class="text-muted">Remplissez le formulaire ci-dessous pour soumettre votre demande.</p>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form @submit.prevent="handleSubmit" enctype="multipart/form-data">

          <!-- Section: Agent -->
          <h6 class="fw-bold mb-3 pb-2 border-bottom">
            <i class="fas fa-user me-2 text-primary"></i> Demandeur
          </h6>

          <!-- Current agent banner -->
          <div v-if="currentAgent" class="d-flex align-items-center gap-2 p-3 bg-light rounded mb-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                 style="width:36px;height:36px;font-size:.75rem;font-weight:700;">
              {{ initials(currentAgent) }}
            </div>
            <div>
              <div class="fw-semibold small">{{ currentAgent.prenom }} {{ currentAgent.nom }}</div>
              <div class="text-muted" style="font-size:.75rem;">{{ currentAgent.id_agent }}</div>
            </div>
          </div>

          <!-- RH: select agent -->
          <div v-if="isRH" class="mb-4">
            <label class="form-label fw-semibold">Agent <span class="text-danger">*</span></label>
            <select v-model="form.agent_id" class="form-select" :class="{ 'is-invalid': errors.agent_id }">
              <option value="">-- Selectionner un agent --</option>
              <option v-for="a in agents" :key="a.id" :value="a.id">
                {{ a.prenom }} {{ a.nom }} ({{ a.id_agent }})
              </option>
            </select>
            <div v-if="errors.agent_id" class="invalid-feedback d-block">{{ errors.agent_id[0] }}</div>
          </div>

          <!-- Section: Type -->
          <h6 class="fw-bold mb-3 pb-2 border-bottom mt-4">
            <i class="fas fa-list-check me-2 text-primary"></i> Type de demande
          </h6>

          <div class="row g-2 mb-3">
            <div v-for="t in typeOptions" :key="t.value" class="col-6 col-md-4">
              <div
                class="border rounded-3 p-3 text-center cursor-pointer"
                :class="{
                  'border-primary bg-primary bg-opacity-10': form.type === t.value,
                  'border-light': form.type !== t.value
                }"
                style="cursor:pointer;transition:all .2s;"
                @click="form.type = t.value"
              >
                <i :class="t.icon" class="d-block mb-1" style="font-size:1.3rem;"></i>
                <small class="fw-semibold">{{ t.label }}</small>
              </div>
            </div>
          </div>
          <div v-if="errors.type" class="text-danger small mb-3">{{ errors.type[0] }}</div>

          <!-- Section: Details -->
          <h6 class="fw-bold mb-3 pb-2 border-bottom mt-4">
            <i class="fas fa-info-circle me-2 text-success"></i> Details de la demande
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
          <h6 class="fw-bold mb-3 pb-2 border-bottom mt-4">
            <i class="fas fa-paperclip me-2 text-warning"></i> Lettre de demande
            <span class="text-muted fw-normal small ms-1">(optionnel)</span>
          </h6>

          <!-- Upload zone -->
          <div
            v-if="!filePreview"
            class="border border-2 border-dashed rounded-3 p-4 text-center"
            style="cursor:pointer;transition:all .2s;"
            @click="$refs.fileInput.click()"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="handleDrop"
            :class="{ 'border-primary bg-primary bg-opacity-10': isDragging }"
          >
            <i class="fas fa-cloud-upload-alt d-block mb-2 text-primary" style="font-size:2rem;"></i>
            <div class="fw-semibold small">Glissez votre fichier ici ou cliquez pour parcourir</div>
            <div class="text-muted" style="font-size:.75rem;">PDF, DOC, DOCX, JPG, PNG - Max 5 Mo</div>
          </div>

          <!-- File preview -->
          <div v-else class="d-flex align-items-center gap-2 p-3 bg-light border rounded-3">
            <i class="fas fa-file-alt text-success" style="font-size:1.2rem;"></i>
            <div class="flex-grow-1" style="min-width:0;">
              <div class="fw-semibold small text-truncate">{{ filePreview.name }}</div>
              <div class="text-muted" style="font-size:.7rem;">{{ filePreview.size }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeFile">
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
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted"><i class="fas fa-lock me-1"></i> Votre demande sera envoyee pour approbation</small>
            <div class="d-flex gap-2">
              <router-link :to="{ name: 'requests.index' }" class="btn btn-outline-secondary">Annuler</router-link>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
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
  { value: 'conge', label: 'Conge', icon: 'fas fa-umbrella-beach text-warning' },
  { value: 'absence', label: 'Absence', icon: 'fas fa-user-slash text-danger' },
  { value: 'permission', label: 'Permission', icon: 'fas fa-door-open text-primary' },
  { value: 'formation', label: 'Formation', icon: 'fas fa-graduation-cap text-success' },
  { value: 'renforcement_capacites', label: 'Renforcement', icon: 'fas fa-chart-line text-info' },
]

function initials(agent) {
  if (!agent) return ''
  const p = (agent.prenom || '').charAt(0).toUpperCase()
  const n = (agent.nom || '').charAt(0).toUpperCase()
  return p + n
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
  if (isRH.value && form.value.agent_id) formData.append('agent_id', form.value.agent_id)
  if (selectedFile.value) formData.append('lettre_demande', selectedFile.value)

  try {
    await create(formData)
    ui.addToast('Demande creee avec succes.', 'success')
    router.push({ name: 'requests.index' })
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de la creation.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  // Pre-select current agent
  if (currentAgent.value) {
    form.value.agent_id = currentAgent.value.id
  }

  // RH: load agents list
  if (isRH.value) {
    try {
      const { data } = await client.get('/agents', { params: { actifs: 1 } })
      agents.value = data.data ?? data
    } catch {
      // Silently fail - agents list is optional for non-RH
    }
  }
})
</script>

<style scoped>
@media (max-width: 767.98px) {
    .card { border-radius: 12px; }
    .card-header { padding: .75rem 1rem; font-size: .88rem; }
    .card-body { padding: .85rem; }
    .badge { font-size: .7rem; }
    .btn-sm { font-size: .78rem; }
}
</style>
