<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle Tache</h1>
            <p class="rh-sub">Assigner une tache a un agent de votre departement.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'taches.index' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div v-if="loadingAgents" class="text-center py-5">
        <LoadingSpinner message="Chargement..." />
      </div>

      <div v-else class="dash-panel mt-3">
        <div class="p-4">
          <div v-if="errors.length" class="alert alert-danger">
            <ul class="mb-0">
              <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
            </ul>
          </div>

          <form @submit.prevent="handleSubmit" class="row g-3">
            <div class="col-md-4">
              <label for="source_type" class="form-label fw-bold">Origine <span class="text-danger">*</span></label>
              <select v-model="form.source_type" class="form-select" id="source_type" required>
                <option value="hors_pta">Hors PTA</option>
                <option value="pta">Issue du PTA</option>
              </select>
            </div>

            <div class="col-md-4">
              <label for="source_emetteur" class="form-label fw-bold">Source <span class="text-danger">*</span></label>
              <select v-model="form.source_emetteur" class="form-select" id="source_emetteur" required>
                <option v-for="source in sourceEmetteurs" :key="source.value" :value="source.value">
                  {{ source.label }}
                </option>
              </select>
            </div>

            <div class="col-md-4">
              <label for="date_tache" class="form-label fw-bold">Date de la tache</label>
              <input v-model="form.date_tache" type="date" class="form-control" id="date_tache">
            </div>

            <div v-if="form.source_type === 'pta'" class="col-12">
              <label for="activite_plan_id" class="form-label fw-bold">Activite PTA liee <span class="text-danger">*</span></label>
              <select v-model="form.activite_plan_id" class="form-select" id="activite_plan_id" required>
                <option value="">-- Choisir une activite du PTA --</option>
                <option v-for="activite in activitesPta" :key="activite.id" :value="activite.id">
                  {{ activiteLabel(activite) }}
                </option>
              </select>
            </div>

            <!-- Agent -->
            <div class="col-md-6">
              <label for="agent_id" class="form-label fw-bold">Assigner a <span class="text-danger">*</span></label>
              <select v-model="form.agent_id" class="form-select" id="agent_id" required>
                <option value="">-- Choisir un agent --</option>
                <option v-for="ag in agents" :key="ag.id" :value="ag.id">
                  {{ ag.prenom }} {{ ag.nom }} ({{ ag.id_agent }})
                </option>
              </select>
            </div>

            <!-- Priorite -->
            <div class="col-md-3">
              <label for="priorite" class="form-label fw-bold">Priorite <span class="text-danger">*</span></label>
              <select v-model="form.priorite" class="form-select" id="priorite" required>
                <option value="normale">Normale</option>
                <option value="haute">Haute</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>

            <!-- Date echeance -->
            <div class="col-md-3">
              <label for="date_echeance" class="form-label fw-bold">Echeance</label>
              <input v-model="form.date_echeance" type="date" class="form-control" id="date_echeance">
            </div>

            <!-- Titre -->
            <div class="col-12">
              <label for="titre" class="form-label fw-bold">Titre de la tache <span class="text-danger">*</span></label>
              <input v-model="form.titre" type="text" class="form-control" id="titre" required
                     placeholder="Ex: Preparer le rapport mensuel">
            </div>

            <!-- Description -->
            <div class="col-12">
              <label for="description" class="form-label fw-bold">Description</label>
              <textarea v-model="form.description" class="form-control" id="description" rows="5"
                        placeholder="Details et instructions pour l'agent..."></textarea>
            </div>

            <div class="col-12">
              <label for="documents" class="form-label fw-bold">Documents joints</label>
              <input
                id="documents"
                ref="documentsInput"
                type="file"
                class="form-control"
                multiple
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                @change="handleDocumentsChange"
              >
              <small class="text-muted d-block mt-1">
                Vous pouvez joindre un ou plusieurs documents au moment de l'assignation.
              </small>
              <div v-if="selectedDocuments.length" class="task-file-list mt-2">
                <div v-for="(file, index) in selectedDocuments" :key="file.name + index" class="task-file-item">
                  <div>
                    <strong>{{ file.name }}</strong>
                    <small class="d-block text-muted">{{ formatFileSize(file.size) }}</small>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeDocument(index)">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Boutons -->
            <div class="col-12 mt-3">
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-paper-plane me-1"></i> Assigner la tache
              </button>
              <router-link :to="{ name: 'taches.index' }" class="btn btn-outline-secondary ms-2">Annuler</router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { create, getCreateData } from '@/api/taches'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const ui = useUiStore()

const loadingAgents = ref(true)
const submitting = ref(false)
const errors = ref([])
const agents = ref([])
const activitesPta = ref([])
const sourceEmetteurs = ref([])
const selectedDocuments = ref([])
const documentsInput = ref(null)
const form = ref({
  agent_id: '',
  titre: '',
  description: '',
  source_type: 'hors_pta',
  source_emetteur: 'directeur',
  activite_plan_id: '',
  priorite: 'normale',
  date_tache: '',
  date_echeance: '',
})

async function loadAgents() {
  try {
    const { data } = await getCreateData()
    agents.value = data.data.agents || []
    activitesPta.value = data.data.activites_pta || []
    sourceEmetteurs.value = data.data.source_emetteurs || []
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast('Acces refuse. Seuls les directeurs peuvent creer des taches.', 'danger')
      router.push({ name: 'taches.index' })
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors du chargement.', 'danger')
    }
  } finally {
    loadingAgents.value = false
  }
}

async function handleSubmit() {
  errors.value = []
  submitting.value = true
  try {
    const payload = new FormData()
    Object.entries(form.value).forEach(([key, value]) => {
      if (key === 'activite_plan_id' && form.value.source_type !== 'pta') {
        return
      }

      if (value !== null && value !== undefined && value !== '') {
        payload.append(key, value)
      }
    })

    selectedDocuments.value.forEach((file) => {
      payload.append('documents[]', file)
    })

    await create(payload)
    ui.addToast('Tache creee avec succes.', 'success')
    router.push({ name: 'taches.index' })
  } catch (err) {
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors || {}
      errors.value = Object.values(validationErrors).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de la creation.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

function handleDocumentsChange(event) {
  const files = Array.from(event.target.files || [])
  selectedDocuments.value = files
}

function removeDocument(index) {
  selectedDocuments.value.splice(index, 1)
  if (documentsInput.value) {
    const dataTransfer = new DataTransfer()
    selectedDocuments.value.forEach((file) => dataTransfer.items.add(file))
    documentsInput.value.files = dataTransfer.files
  }
}

function formatFileSize(size) {
  if (!size) return '0 Ko'
  if (size >= 1024 * 1024) return `${(size / (1024 * 1024)).toFixed(1)} Mo`
  return `${Math.round(size / 1024)} Ko`
}


function activiteLabel(activite) {
  const trimestre = activite.trimestre ? ` (${activite.trimestre})` : ''
  return `${activite.titre} - ${activite.annee}${trimestre}`
}
onMounted(() => loadAgents())
</script>

<style scoped>
@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; padding: 1rem; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    .form-label { font-size: .82rem; }
    .btn { font-size: .85rem; }
}

  .task-file-list {
    display: grid;
    gap: .65rem;
  }

  .task-file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .75rem .9rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
  }
</style>
