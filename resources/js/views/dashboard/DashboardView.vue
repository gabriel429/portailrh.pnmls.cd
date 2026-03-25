<template>
  <SenDashboardView v-if="auth.isSEN" />
  <div v-else class="container py-4">
    <!-- Hero -->
    <div class="dash-hero">
      <div class="dash-hero-avatar">
        <i class="fas fa-user"></i>
      </div>
      <div class="dash-hero-text">
        <h2>Bienvenu(e), {{ auth.agent ? auth.agent.nom + ' ' + auth.agent.prenom : auth.user?.name || 'Utilisateur' }}</h2>
        <p v-if="auth.agent?.fonction" class="dash-hero-fonction">{{ auth.agent.fonction }}</p>
        <p class="dash-hero-date">{{ today }}</p>
      </div>
      <div class="dash-hero-stats">
        <div>
          <div class="dash-hero-stat-val">{{ stats.documents ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">Documents</div>
        </div>
        <div>
          <div class="dash-hero-stat-val">{{ stats.messages_non_lus ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">Messages</div>
        </div>
        <div>
          <div class="dash-hero-stat-val">{{ stats.communiques ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">Communiques</div>
        </div>
        <div>
          <div class="dash-hero-stat-val">{{ stats.requests_pending ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">En attente</div>
        </div>
        <div>
          <div class="dash-hero-stat-val">{{ stats.requests_approved ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">Approuvees</div>
        </div>
        <div>
          <div class="dash-hero-stat-val">{{ stats.absences ?? 0 }}</div>
          <div class="dash-hero-stat-lbl">Absences</div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement du tableau de bord..." />
    </div>

    <div v-else-if="loadError" class="alert alert-warning mx-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <template v-else>
      <!-- Quick Actions -->
      <div class="dash-section-header">
        <div class="dash-section-title">
          <i class="fas fa-bolt" style="color:#0077B5;"></i>
          Actions rapides
        </div>
      </div>
      <div class="dash-action-grid">
        <button
          class="dash-action-card"
          @click="openRequestModal"
        >
          <div class="dash-action-icon" style="background:#e0f2fe;color:#0077B5;">
            <i class="fas fa-plus-circle"></i>
          </div>
          <div class="dash-action-info">
            <div class="dash-action-name">Nouvelle demande</div>
            <div class="dash-action-desc">Conge, attestation...</div>
          </div>
        </button>
        <button
          class="dash-action-card"
          @click="openUploadModal"
        >
          <div class="dash-action-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-cloud-upload-alt"></i>
          </div>
          <div class="dash-action-info">
            <div class="dash-action-name">Uploader document</div>
            <div class="dash-action-desc">Ajouter un fichier</div>
          </div>
        </button>
        <router-link
          v-for="action in quickActions"
          :key="action.to"
          :to="action.to"
          class="dash-action-card"
        >
          <div class="dash-action-icon" :style="{ background: action.bg, color: action.color }">
            <i class="fas" :class="action.icon"></i>
          </div>
          <div class="dash-action-info">
            <div class="dash-action-name">{{ action.label }}</div>
            <div class="dash-action-desc">{{ action.desc }}</div>
          </div>
        </router-link>
      </div>

      <!-- Stat Cards -->
      <div class="dash-section-header">
        <div class="dash-section-title">
          <i class="fas fa-chart-bar" style="color:#0077B5;"></i>
          Mes statistiques
        </div>
      </div>
      <div class="dash-stat-grid">
        <div v-for="card in statCards" :key="card.label" class="dash-stat-card">
          <div class="dash-stat-top">
            <div class="dash-stat-icon" :style="{ background: card.bg, color: card.color }">
              <i class="fas" :class="card.icon"></i>
            </div>
            <div class="dash-stat-info">
              <div class="dash-stat-val">{{ card.value }}</div>
              <div class="dash-stat-lbl">{{ card.label }}</div>
            </div>
          </div>
          <div class="dash-stat-bar">
            <div class="dash-stat-bar-fill" :style="{ background: card.color, width: card.pct + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="dash-section-header">
        <div class="dash-section-title">
          <i class="fas fa-history" style="color:#0077B5;"></i>
          Activite recente
        </div>
      </div>
      <div v-if="activities.length" class="dash-activity-grid">
        <div v-for="(activity, index) in activities" :key="index" class="dash-activity-card">
          <div class="dash-activity-top">
            <div class="dash-activity-icon" :class="'dash-act-' + (activity.type || 'default')">
              <i class="fas" :class="activityIcon(activity.type)"></i>
            </div>
            <div class="dash-activity-info">
              <div class="dash-activity-text">{{ activity.description }}</div>
              <div class="dash-activity-time"><i class="fas fa-clock me-1"></i>{{ formatTime(activity.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="dash-empty">
        <div class="dash-empty-icon"><i class="fas fa-inbox"></i></div>
        <h5>Aucune activite recente</h5>
        <p>Vos dernieres actions et notifications apparaitront ici.</p>
        <button class="dash-action-btn" @click="openRequestModal">
          <i class="fas fa-plus me-1"></i>Nouvelle demande
        </button>
      </div>

      <!-- Info Cards -->
      <div class="dash-info-grid">
        <div class="dash-info-card">
          <div class="dash-info-top">
            <div class="dash-info-icon" style="background:#e0f2fe;color:#0077B5;">
              <i class="fas fa-info-circle"></i>
            </div>
            <div class="dash-info-content">
              <div class="dash-info-title">Besoin d'aide ?</div>
              <div class="dash-info-text">Pour toute question relative a votre dossier RH, contactez la Section Ressources Humaines ou consultez les documents de travail.</div>
            </div>
          </div>
          <div class="dash-info-footer">
            <router-link to="/documents-travail" class="dash-info-link">
              <i class="fas fa-file-invoice me-1"></i> Documents de travail
            </router-link>
          </div>
        </div>
        <div class="dash-info-card">
          <div class="dash-info-top">
            <div class="dash-info-icon" style="background:#fef3c7;color:#d97706;">
              <i class="fas fa-bell"></i>
            </div>
            <div class="dash-info-content">
              <div class="dash-info-title">Notifications</div>
              <div class="dash-info-text">Restez informe des mises a jour sur vos demandes, documents et communiques importants de l'organisation.</div>
            </div>
          </div>
          <div class="dash-info-footer">
            <router-link to="/notifications" class="dash-info-link">
              <i class="fas fa-bell me-1"></i> Voir mes notifications
            </router-link>
          </div>
        </div>
      </div>
    </template>

    <!-- Request Create Modal -->
    <teleport to="body">
      <div v-if="showRequestModal" class="rcm-overlay" @click.self="closeRequestModal">
        <div class="rcm-dialog">
          <div class="rcm-header">
            <h5 class="rcm-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle Demande</h5>
            <button class="rcm-close" @click="closeRequestModal"><i class="fas fa-times"></i></button>
          </div>
          <div class="rcm-body">
            <form @submit.prevent="handleReqSubmit" enctype="multipart/form-data">
              <div v-if="currentAgent && !isRH" class="rcm-agent-banner">
                <div class="rcm-agent-avatar">{{ agentInitials(currentAgent) }}</div>
                <div>
                  <div class="fw-semibold small">{{ currentAgent.prenom }} {{ currentAgent.nom }}</div>
                  <div class="text-muted" style="font-size:.75rem;">{{ currentAgent.id_agent }}</div>
                </div>
              </div>
              <div v-if="isRH" class="mb-3">
                <label class="rcm-label">Agent <span class="text-danger">*</span></label>
                <select v-model="reqForm.agent_id" class="rcm-input" :class="{ 'is-invalid': reqErrors.agent_id }">
                  <option value="">-- Selectionner un agent --</option>
                  <option v-for="a in reqAgents" :key="a.id" :value="a.id">{{ a.prenom }} {{ a.nom }} ({{ a.id_agent }})</option>
                </select>
                <div v-if="reqErrors.agent_id" class="rcm-error">{{ reqErrors.agent_id[0] }}</div>
              </div>
              <label class="rcm-label">Type de demande <span class="text-danger">*</span></label>
              <div class="rcm-type-grid">
                <div v-for="t in reqTypeOptions" :key="t.value" class="rcm-type-card" :class="{ active: reqForm.type === t.value }" @click="reqForm.type = t.value">
                  <i :class="t.icon" class="rcm-type-icon"></i>
                  <span class="rcm-type-label">{{ t.label }}</span>
                </div>
              </div>
              <div v-if="reqErrors.type" class="rcm-error mb-2">{{ reqErrors.type[0] }}</div>
              <div class="rcm-row mt-3">
                <div class="rcm-col">
                  <label class="rcm-label"><i class="fas fa-calendar-alt me-1 text-muted"></i> Date debut <span class="text-danger">*</span></label>
                  <input type="date" v-model="reqForm.date_debut" class="rcm-input" :class="{ 'is-invalid': reqErrors.date_debut }">
                  <div v-if="reqErrors.date_debut" class="rcm-error">{{ reqErrors.date_debut[0] }}</div>
                </div>
                <div class="rcm-col">
                  <label class="rcm-label"><i class="fas fa-calendar-check me-1 text-muted"></i> Date fin <span class="text-muted fw-normal">(optionnel)</span></label>
                  <input type="date" v-model="reqForm.date_fin" class="rcm-input" :class="{ 'is-invalid': reqErrors.date_fin }" :min="reqForm.date_debut">
                  <div v-if="reqErrors.date_fin" class="rcm-error">{{ reqErrors.date_fin[0] }}</div>
                </div>
              </div>
              <div class="mt-3">
                <label class="rcm-label"><i class="fas fa-align-left me-1 text-muted"></i> Description <span class="text-danger">*</span></label>
                <textarea v-model="reqForm.description" rows="3" class="rcm-input rcm-textarea" :class="{ 'is-invalid': reqErrors.description }" placeholder="Decrivez le motif de votre demande..."></textarea>
                <div v-if="reqErrors.description" class="rcm-error">{{ reqErrors.description[0] }}</div>
              </div>
              <div class="mt-3">
                <label class="rcm-label"><i class="fas fa-paperclip me-1 text-muted"></i> Lettre de demande <span class="text-muted fw-normal">(optionnel)</span></label>
                <div v-if="!reqFilePreview" class="rcm-upload-zone" @click="reqFileInput.click()" @dragover.prevent="reqIsDragging = true" @dragleave="reqIsDragging = false" @drop.prevent="reqHandleDrop" :class="{ dragging: reqIsDragging }">
                  <i class="fas fa-cloud-upload-alt rcm-upload-icon"></i>
                  <div class="fw-semibold small">Glissez ou cliquez pour parcourir</div>
                  <div class="text-muted" style="font-size:.7rem;">PDF, DOC, DOCX, JPG, PNG - Max 5 Mo</div>
                </div>
                <div v-else class="rcm-file-preview">
                  <div class="rcm-file-icon-box"><i class="fas fa-file-alt"></i></div>
                  <div class="rcm-file-info">
                    <div class="rcm-file-name">{{ reqFilePreview.name }}</div>
                    <div class="rcm-file-size">{{ reqFilePreview.size }}</div>
                  </div>
                  <button type="button" class="rcm-file-remove" @click="reqRemoveFile"><i class="fas fa-trash-alt"></i></button>
                </div>
                <input ref="reqFileInput" type="file" class="d-none" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" @change="reqHandleFileSelect">
                <div v-if="reqErrors.lettre_demande" class="rcm-error">{{ reqErrors.lettre_demande[0] }}</div>
              </div>
              <div class="rcm-footer">
                <button type="button" class="rcm-btn rcm-btn-cancel" @click="closeRequestModal">Annuler</button>
                <button type="submit" class="rcm-btn rcm-btn-submit" :disabled="reqSubmitting">
                  <span v-if="reqSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-paper-plane me-1"></i> Soumettre
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Document Upload Modal -->
    <teleport to="body">
      <div v-if="showUploadModal" class="dum-overlay" @click.self="closeUploadModal">
        <div class="dum-dialog">
          <div class="dum-header">
            <h5 class="dum-title"><i class="fas fa-cloud-upload-alt me-2"></i>Uploader un Document</h5>
            <button class="dum-close" @click="closeUploadModal"><i class="fas fa-times"></i></button>
          </div>
          <div class="dum-body">
            <form @submit.prevent="handleUplSubmit" enctype="multipart/form-data">
              <div class="mb-3">
                <label class="dum-label"><i class="fas fa-file me-1 text-muted"></i> Fichier <span class="text-danger">*</span></label>
                <div v-if="!uplFilePreview" class="dum-upload-zone" @click="uplFileInput.click()" @dragover.prevent="uplIsDragging = true" @dragleave="uplIsDragging = false" @drop.prevent="uplHandleDrop" :class="{ dragging: uplIsDragging }">
                  <i class="fas fa-cloud-upload-alt dum-upload-icon"></i>
                  <div class="fw-semibold small">Glissez votre fichier ici ou cliquez pour parcourir</div>
                  <div class="text-muted" style="font-size:.7rem;">Tous formats acceptes - Max 10 Mo</div>
                </div>
                <div v-else class="dum-file-preview">
                  <div class="dum-file-icon-box"><i class="fas fa-file-alt"></i></div>
                  <div class="dum-file-info">
                    <div class="dum-file-name">{{ uplFilePreview.name }}</div>
                    <div class="dum-file-size">{{ uplFilePreview.size }} &middot; {{ uplFilePreview.ext }}</div>
                  </div>
                  <button type="button" class="dum-file-remove" @click="uplRemoveFile"><i class="fas fa-trash-alt"></i></button>
                </div>
                <input ref="uplFileInput" type="file" class="d-none" @change="uplHandleFileSelect">
                <div v-if="uplErrors.fichier" class="dum-error">{{ uplErrors.fichier[0] }}</div>
              </div>
              <div class="mb-3">
                <label class="dum-label"><i class="fas fa-tag me-1 text-muted"></i> Nom du document <span class="text-danger">*</span></label>
                <input type="text" v-model="uplForm.nom_document" class="dum-input" :class="{ 'is-invalid': uplErrors.nom_document }" placeholder="Ex: Carte d'identite, Diplome, Contrat...">
                <div v-if="uplErrors.nom_document" class="dum-error">{{ uplErrors.nom_document[0] }}</div>
              </div>
              <div class="mb-3">
                <label class="dum-label"><i class="fas fa-folder me-1 text-muted"></i> Categorie</label>
                <div class="dum-cat-grid">
                  <div v-for="c in uplCatOptions" :key="c.value" class="dum-cat-card" :class="{ active: uplForm.categories_document_id === c.value }" @click="uplForm.categories_document_id = uplForm.categories_document_id === c.value ? '' : c.value">
                    <i :class="c.icon" class="dum-cat-icon"></i>
                    <span class="dum-cat-label">{{ c.label }}</span>
                  </div>
                </div>
                <div v-if="uplErrors.categories_document_id" class="dum-error">{{ uplErrors.categories_document_id[0] }}</div>
              </div>
              <div class="mb-3">
                <label class="dum-label"><i class="fas fa-align-left me-1 text-muted"></i> Description <span class="text-muted fw-normal">(optionnel)</span></label>
                <textarea v-model="uplForm.description" rows="2" class="dum-input dum-textarea" :class="{ 'is-invalid': uplErrors.description }" placeholder="Ajoutez une description..."></textarea>
                <div v-if="uplErrors.description" class="dum-error">{{ uplErrors.description[0] }}</div>
              </div>
              <div class="dum-footer">
                <button type="button" class="dum-btn dum-btn-cancel" @click="closeUploadModal">Annuler</button>
                <button type="submit" class="dum-btn dum-btn-submit" :disabled="uplSubmitting">
                  <span v-if="uplSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-cloud-upload-alt me-1"></i> Uploader
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineAsyncComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { create as createRequest } from '@/api/requests'
import { create as createDocument } from '@/api/documents'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
const SenDashboardView = defineAsyncComponent(() => import('@/views/dashboard/SenDashboardView.vue'))

const auth = useAuthStore()
const ui = useUiStore()
const loading = ref(true)
const stats = ref({})
const activities = ref([])

const today = computed(() => {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

const quickActions = [
  { to: '/plan-travail', label: 'Plan de travail', desc: 'Consulter ou creer', icon: 'fa-tasks', color: '#d97706', bg: '#fef3c7' },
  { to: '/profile', label: 'Mon profil', desc: 'Voir mes infos', icon: 'fa-user-circle', color: '#7c3aed', bg: '#ede9fe' },
]

const maxStat = computed(() => {
  const vals = [
    stats.value.documents ?? 0,
    stats.value.messages_non_lus ?? 0,
    stats.value.communiques ?? 0,
    stats.value.requests_pending ?? 0,
    stats.value.requests_approved ?? 0,
    stats.value.absences ?? 0,
  ]
  return Math.max(...vals, 1)
})

const statCards = computed(() => [
  { label: 'Documents', value: stats.value.documents ?? 0, icon: 'fa-folder-open', color: '#0077B5', bg: '#e0f2fe', pct: ((stats.value.documents ?? 0) / maxStat.value) * 100 },
  { label: 'Messages non lus', value: stats.value.messages_non_lus ?? 0, icon: 'fa-envelope', color: '#8b5cf6', bg: '#ede9fe', pct: ((stats.value.messages_non_lus ?? 0) / maxStat.value) * 100 },
  { label: 'Communiques du SEN', value: stats.value.communiques ?? 0, icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe', pct: ((stats.value.communiques ?? 0) / maxStat.value) * 100 },
  { label: 'Demandes en attente', value: stats.value.requests_pending ?? 0, icon: 'fa-clock', color: '#d97706', bg: '#fef3c7', pct: ((stats.value.requests_pending ?? 0) / maxStat.value) * 100 },
  { label: 'Demandes approuvees', value: stats.value.requests_approved ?? 0, icon: 'fa-check-circle', color: '#059669', bg: '#d1fae5', pct: ((stats.value.requests_approved ?? 0) / maxStat.value) * 100 },
  { label: 'Absences', value: stats.value.absences ?? 0, icon: 'fa-calendar-times', color: '#dc2626', bg: '#fee2e2', pct: ((stats.value.absences ?? 0) / maxStat.value) * 100 },
])

function activityIcon(type) {
  const map = { request: 'fa-paper-plane', document: 'fa-file-alt', absence: 'fa-calendar-times', approval: 'fa-check-circle' }
  return map[type] || 'fa-bell'
}

function formatTime(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1) return "A l'instant"
  if (diff < 60) return `Il y a ${diff} min`
  if (diff < 1440) return `Il y a ${Math.floor(diff / 60)}h`
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const loadError = ref(null)

// ── Request Create Modal ──
const showRequestModal = ref(false)
const reqSubmitting = ref(false)
const reqErrors = ref({})
const reqIsDragging = ref(false)
const reqSelectedFile = ref(null)
const reqFilePreview = ref(null)
const reqFileInput = ref(null)
const reqAgents = ref([])
const isRH = computed(() => auth.hasAdminAccess)
const currentAgent = computed(() => auth.agent)

const reqTypeOptions = [
  { value: 'conge', label: 'Conge', icon: 'fas fa-umbrella-beach' },
  { value: 'absence', label: 'Absence', icon: 'fas fa-user-slash' },
  { value: 'permission', label: 'Permission', icon: 'fas fa-door-open' },
  { value: 'formation', label: 'Formation', icon: 'fas fa-graduation-cap' },
  { value: 'renforcement_capacites', label: 'Renforcement', icon: 'fas fa-chart-line' },
]

function defaultReqForm() {
  return { agent_id: currentAgent.value?.id || '', type: '', date_debut: '', date_fin: '', description: '' }
}
const reqForm = ref(defaultReqForm())

async function openRequestModal() {
  reqForm.value = defaultReqForm()
  reqErrors.value = {}
  reqSelectedFile.value = null
  reqFilePreview.value = null
  showRequestModal.value = true
  if (isRH.value && !reqAgents.value.length) {
    try {
      const { data } = await client.get('/agents', { params: { actifs: 1 } })
      reqAgents.value = data.data ?? data
    } catch {}
  }
}

function closeRequestModal() { showRequestModal.value = false }

function reqHandleFileSelect(e) { const f = e.target.files[0]; if (f) reqSetFile(f) }
function reqHandleDrop(e) { reqIsDragging.value = false; const f = e.dataTransfer.files[0]; if (f) reqSetFile(f) }
function reqSetFile(file) {
  reqSelectedFile.value = file
  reqFilePreview.value = { name: file.name, size: (file.size / 1024 / 1024).toFixed(2) + ' Mo' }
}
function reqRemoveFile() { reqSelectedFile.value = null; reqFilePreview.value = null; if (reqFileInput.value) reqFileInput.value.value = '' }

function agentInitials(a) { return a ? ((a.prenom || '').charAt(0) + (a.nom || '').charAt(0)).toUpperCase() : '' }

async function handleReqSubmit() {
  reqErrors.value = {}
  reqSubmitting.value = true
  const fd = new FormData()
  fd.append('type', reqForm.value.type)
  fd.append('description', reqForm.value.description)
  fd.append('date_debut', reqForm.value.date_debut)
  if (reqForm.value.date_fin) fd.append('date_fin', reqForm.value.date_fin)
  if (isRH.value && reqForm.value.agent_id) fd.append('agent_id', reqForm.value.agent_id)
  if (reqSelectedFile.value) fd.append('lettre_demande', reqSelectedFile.value)
  try {
    await createRequest(fd)
    ui.addToast('Demande creee avec succes.', 'success')
    showRequestModal.value = false
  } catch (err) {
    if (err.response?.status === 422) reqErrors.value = err.response.data.errors || {}
    else ui.addToast(err.response?.data?.message || 'Erreur lors de la creation.', 'danger')
  } finally { reqSubmitting.value = false }
}

// ── Document Upload Modal ──
const showUploadModal = ref(false)
const uplSubmitting = ref(false)
const uplErrors = ref({})
const uplIsDragging = ref(false)
const uplSelectedFile = ref(null)
const uplFilePreview = ref(null)
const uplFileInput = ref(null)

const uplCatOptions = [
  { value: 'identite', label: 'Identite', icon: 'fas fa-id-card' },
  { value: 'parcours', label: 'Parcours', icon: 'fas fa-graduation-cap' },
  { value: 'carriere', label: 'Carriere', icon: 'fas fa-briefcase' },
  { value: 'mission', label: 'Mission', icon: 'fas fa-plane' },
]

function defaultUplForm() { return { nom_document: '', categories_document_id: '', description: '' } }
const uplForm = ref(defaultUplForm())

function openUploadModal() {
  uplForm.value = defaultUplForm()
  uplErrors.value = {}
  uplSelectedFile.value = null
  uplFilePreview.value = null
  showUploadModal.value = true
}
function closeUploadModal() { showUploadModal.value = false }

function uplHandleFileSelect(e) { const f = e.target.files[0]; if (f) uplSetFile(f) }
function uplHandleDrop(e) { uplIsDragging.value = false; const f = e.dataTransfer.files[0]; if (f) uplSetFile(f) }
function uplSetFile(file) {
  uplSelectedFile.value = file
  const ext = file.name.split('.').pop().toUpperCase()
  uplFilePreview.value = { name: file.name, size: (file.size / 1024 / 1024).toFixed(2) + ' Mo', ext }
}
function uplRemoveFile() { uplSelectedFile.value = null; uplFilePreview.value = null; if (uplFileInput.value) uplFileInput.value.value = '' }

async function handleUplSubmit() {
  uplErrors.value = {}
  uplSubmitting.value = true
  const fd = new FormData()
  fd.append('nom_document', uplForm.value.nom_document)
  if (uplForm.value.categories_document_id) fd.append('categories_document_id', uplForm.value.categories_document_id)
  if (uplForm.value.description) fd.append('description', uplForm.value.description)
  if (uplSelectedFile.value) fd.append('fichier', uplSelectedFile.value)
  try {
    await createDocument(fd)
    ui.addToast('Document uploade avec succes.', 'success')
    showUploadModal.value = false
  } catch (err) {
    if (err.response?.status === 422) uplErrors.value = err.response.data.errors || {}
    else ui.addToast(err.response?.data?.message || 'Erreur lors de l\'upload.', 'danger')
  } finally { uplSubmitting.value = false }
}

onMounted(async () => {
  try {
    const { data } = await client.get('/dashboard')
    stats.value = data.stats || data
    activities.value = data.activities || []
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Impossible de charger les donnees du tableau de bord.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* Hero */
.dash-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #004165 100%);
  border-radius: 18px; padding: 2rem 2.2rem; margin-bottom: 1.5rem; color: #fff;
  position: relative; overflow: hidden;
  display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}
.dash-hero::before {
  content: ''; position: absolute; top: -40%; right: -8%;
  width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.07);
}
.dash-hero-avatar {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(255,255,255,.18); display: flex; align-items: center;
  justify-content: center; font-size: 1.3rem; flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.15);
}
.dash-hero-text { flex: 1; min-width: 150px; }
.dash-hero-text h2 { font-size: 1.3rem; font-weight: 700; margin: 0 0 .2rem; }
.dash-hero-fonction { font-size: .85rem; opacity: .85; margin: 0 0 .15rem; font-weight: 500; }
.dash-hero-date { font-size: .78rem; opacity: .6; margin: 0; text-transform: capitalize; }
.dash-hero-stats { display: flex; gap: 1.5rem; margin-left: auto; }
.dash-hero-stat-val { font-size: 1.4rem; font-weight: 800; text-align: center; }
.dash-hero-stat-lbl { font-size: .68rem; opacity: .7; text-transform: uppercase; letter-spacing: .5px; text-align: center; }

/* Section headers */
.dash-section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: .8rem; padding-bottom: .5rem; border-bottom: 2px solid #f3f4f6;
}
.dash-section-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: .5rem; }

/* Quick Actions */
.dash-action-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .8rem; margin-bottom: 1.5rem; }
.dash-action-card {
  display: flex; align-items: center; gap: .7rem; padding: .9rem 1rem;
  background: #fff; border: 2px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; cursor: pointer;
}
.dash-action-card:hover { border-color: #0077B5; color: #0077B5; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,119,181,.1); }
.dash-action-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
  justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.dash-action-info { flex: 1; min-width: 0; }
.dash-action-name { font-size: .82rem; font-weight: 700; line-height: 1.2; }
.dash-action-desc { font-size: .7rem; opacity: .6; }

/* Stat Cards */
.dash-stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.dash-stat-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); padding: 1.2rem; transition: all .2s;
}
.dash-stat-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dash-stat-top { display: flex; align-items: center; gap: .8rem; margin-bottom: .8rem; }
.dash-stat-icon {
  width: 44px; height: 44px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.dash-stat-info { flex: 1; min-width: 0; }
.dash-stat-val { font-size: 1.6rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
.dash-stat-lbl { font-size: .72rem; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.dash-stat-bar { height: 4px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
.dash-stat-bar-fill { height: 100%; border-radius: 4px; transition: width .6s ease; min-width: 4px; }

/* Activity */
.dash-activity-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.dash-activity-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); padding: 1rem 1.2rem; transition: all .2s;
}
.dash-activity-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dash-activity-top { display: flex; align-items: flex-start; gap: .8rem; }
.dash-activity-icon {
  width: 38px; height: 38px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0;
}
.dash-act-request { background: #e0f2fe; color: #0077B5; }
.dash-act-document { background: #d1fae5; color: #059669; }
.dash-act-absence { background: #fee2e2; color: #dc2626; }
.dash-act-approval { background: #fef3c7; color: #d97706; }
.dash-act-default { background: #f1f5f9; color: #64748b; }
.dash-activity-info { flex: 1; min-width: 0; }
.dash-activity-text { font-size: .85rem; font-weight: 600; color: #1e293b; line-height: 1.3; margin-bottom: .3rem; }
.dash-activity-time { font-size: .72rem; color: #9ca3af; }

/* Empty */
.dash-empty { text-align: center; padding: 3rem 1rem; color: #9ca3af; margin-bottom: 1.5rem; }
.dash-empty-icon {
  width: 64px; height: 64px; border-radius: 50%; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; color: #d1d5db;
}
.dash-action-btn {
  display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .9rem;
  border-radius: 8px; font-size: .8rem; font-weight: 600; background: #e0f2fe;
  color: #0077B5; text-decoration: none; border: 1px solid #bae6fd; transition: all .2s;
}
.dash-action-btn:hover { background: #0077B5; color: #fff; border-color: #0077B5; }

/* Info Cards */
.dash-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
.dash-info-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); overflow: hidden; transition: all .2s;
  display: flex; flex-direction: column;
}
.dash-info-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dash-info-top { display: flex; align-items: flex-start; gap: .8rem; padding: 1.2rem 1.2rem .6rem; }
.dash-info-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.dash-info-content { flex: 1; min-width: 0; }
.dash-info-title { font-weight: 700; font-size: .9rem; color: #1e293b; margin-bottom: .3rem; }
.dash-info-text { font-size: .78rem; color: #9ca3af; line-height: 1.5; }
.dash-info-footer {
  border-top: 1px solid #f3f4f6; padding: .7rem 1.2rem;
  margin-top: auto;
}
.dash-info-link {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .78rem; font-weight: 600; color: #0077B5; text-decoration: none; transition: all .2s;
}
.dash-info-link:hover { color: #005a87; }

/* Responsive */
@media (max-width: 576px) {
  .dash-hero { flex-direction: column; align-items: flex-start; padding: 1.5rem 1.2rem; }
  .dash-hero-stats { margin-left: 0; margin-top: .5rem; gap: 1rem; flex-wrap: wrap; }
  .dash-hero-stat-val { font-size: 1.1rem; }
  .dash-action-grid { grid-template-columns: repeat(2, 1fr); }
  .dash-stat-grid { grid-template-columns: repeat(2, 1fr); }
  .dash-activity-grid { grid-template-columns: 1fr; }
  .dash-info-grid { grid-template-columns: 1fr; }
}

/* ── Request Create Modal (rcm-*) ── */
.rcm-overlay { position: fixed; inset: 0; z-index: 1060; background: rgba(0,0,0,.55); display: flex; align-items: center; justify-content: center; padding: 1rem; animation: rcmFadeIn .2s; }
@keyframes rcmFadeIn { from { opacity: 0; } to { opacity: 1; } }
.rcm-dialog { background: #fff; border-radius: 18px; width: 100%; max-width: 640px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 24px 48px rgba(0,0,0,.18); animation: rcmSlideUp .25s ease-out; overflow: hidden; }
@keyframes rcmSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.rcm-header { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.5rem; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; }
.rcm-title { margin: 0; font-size: 1.05rem; font-weight: 700; }
.rcm-close { width: 32px; height: 32px; border-radius: 8px; border: none; background: rgba(255,255,255,.15); color: #fff; font-size: .9rem; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .2s; }
.rcm-close:hover { background: rgba(255,255,255,.3); }
.rcm-body { padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; }
.rcm-label { display: block; font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
.rcm-input { width: 100%; border-radius: 10px; border: 1px solid #e2e8f0; padding: .5rem .75rem; font-size: .85rem; transition: border-color .2s, box-shadow .2s; }
.rcm-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.1); }
.rcm-input.is-invalid { border-color: #ef4444; }
.rcm-textarea { resize: vertical; min-height: 70px; }
.rcm-error { font-size: .75rem; color: #ef4444; margin-top: .2rem; }
.rcm-agent-banner { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.rcm-agent-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #059669, #047857); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; }
.rcm-type-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: .5rem; margin-bottom: .5rem; }
.rcm-type-card { display: flex; flex-direction: column; align-items: center; gap: .3rem; padding: .7rem .3rem; border-radius: 10px; border: 2px solid #e2e8f0; cursor: pointer; transition: all .2s; background: #fff; }
.rcm-type-card:hover { border-color: #94a3b8; transform: translateY(-1px); }
.rcm-type-card.active { border-color: #059669; background: #ecfdf5; }
.rcm-type-icon { font-size: 1.1rem; color: #94a3b8; transition: color .2s; }
.rcm-type-card.active .rcm-type-icon { color: #059669; }
.rcm-type-label { font-size: .68rem; font-weight: 600; color: #64748b; }
.rcm-type-card.active .rcm-type-label { color: #059669; }
.rcm-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.rcm-upload-zone { border: 2px dashed #d1d5db; border-radius: 12px; padding: 1.2rem; text-align: center; cursor: pointer; transition: all .2s; }
.rcm-upload-zone:hover, .rcm-upload-zone.dragging { border-color: #059669; background: #ecfdf5; }
.rcm-upload-icon { font-size: 1.5rem; color: #059669; margin-bottom: .3rem; display: block; }
.rcm-file-preview { display: flex; align-items: center; gap: .6rem; padding: .7rem .85rem; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; }
.rcm-file-icon-box { width: 32px; height: 32px; border-radius: 8px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
.rcm-file-info { flex: 1; min-width: 0; }
.rcm-file-name { font-weight: 600; font-size: .8rem; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rcm-file-size { font-size: .68rem; color: #94a3b8; }
.rcm-file-remove { width: 28px; height: 28px; border-radius: 6px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .7rem; padding: 0; }
.rcm-file-remove:hover { background: #fee2e2; }
.rcm-footer { display: flex; align-items: center; justify-content: flex-end; gap: .6rem; padding-top: 1rem; margin-top: 1rem; border-top: 1px solid #f1f5f9; }
.rcm-btn { display: inline-flex; align-items: center; gap: .3rem; padding: .5rem 1.1rem; border-radius: 10px; font-size: .82rem; font-weight: 600; border: none; cursor: pointer; transition: all .2s; }
.rcm-btn-cancel { background: #fff; color: #64748b; border: 1px solid #e2e8f0; }
.rcm-btn-cancel:hover { background: #f8fafc; color: #334155; }
.rcm-btn-submit { background: linear-gradient(135deg, #059669, #047857); color: #fff; }
.rcm-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,150,105,.3); }
.rcm-btn-submit:disabled { opacity: .6; transform: none; }

@media (max-width: 576px) {
  .rcm-overlay { padding: .5rem; }
  .rcm-dialog { max-height: 95vh; border-radius: 14px; }
  .rcm-header { padding: .85rem 1rem; }
  .rcm-body { padding: 1rem; }
  .rcm-type-grid { grid-template-columns: repeat(3, 1fr); }
  .rcm-row { grid-template-columns: 1fr; }
  .rcm-footer { flex-direction: column; align-items: stretch; }
  .rcm-btn { justify-content: center; }
}
@media (max-width: 449.98px) { .rcm-type-grid { grid-template-columns: repeat(2, 1fr); } }

/* ── Document Upload Modal (dum-*) ── */
.dum-overlay { position: fixed; inset: 0; z-index: 1060; background: rgba(0,0,0,.55); display: flex; align-items: center; justify-content: center; padding: 1rem; animation: dumFadeIn .2s; }
@keyframes dumFadeIn { from { opacity: 0; } to { opacity: 1; } }
.dum-dialog { background: #fff; border-radius: 18px; width: 100%; max-width: 580px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 24px 48px rgba(0,0,0,.18); animation: dumSlideUp .25s ease-out; overflow: hidden; }
@keyframes dumSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.dum-header { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.5rem; background: linear-gradient(135deg, #0077B5 0%, #005a87 100%); color: #fff; }
.dum-title { margin: 0; font-size: 1.05rem; font-weight: 700; }
.dum-close { width: 32px; height: 32px; border-radius: 8px; border: none; background: rgba(255,255,255,.15); color: #fff; font-size: .9rem; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .2s; }
.dum-close:hover { background: rgba(255,255,255,.3); }
.dum-body { padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; }
.dum-label { display: block; font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
.dum-input { width: 100%; border-radius: 10px; border: 1px solid #e2e8f0; padding: .5rem .75rem; font-size: .85rem; transition: border-color .2s, box-shadow .2s; }
.dum-input:focus { outline: none; border-color: #0077B5; box-shadow: 0 0 0 3px rgba(0,119,181,.1); }
.dum-input.is-invalid { border-color: #ef4444; }
.dum-textarea { resize: vertical; min-height: 60px; }
.dum-error { font-size: .75rem; color: #ef4444; margin-top: .2rem; }
.dum-upload-zone { border: 2px dashed #bde0f5; border-radius: 14px; padding: 1.5rem; text-align: center; cursor: pointer; transition: all .2s; background: #f8fbfe; }
.dum-upload-zone:hover, .dum-upload-zone.dragging { border-color: #0077B5; background: #e8f4fd; }
.dum-upload-icon { font-size: 2rem; color: #0077B5; margin-bottom: .4rem; display: block; }
.dum-file-preview { display: flex; align-items: center; gap: .7rem; padding: .8rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; }
.dum-file-icon-box { width: 36px; height: 36px; border-radius: 10px; background: #e8f4fd; color: #0077B5; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
.dum-file-info { flex: 1; min-width: 0; }
.dum-file-name { font-weight: 600; font-size: .82rem; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dum-file-size { font-size: .7rem; color: #94a3b8; }
.dum-file-remove { width: 28px; height: 28px; border-radius: 6px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .7rem; padding: 0; }
.dum-file-remove:hover { background: #fee2e2; }
.dum-cat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; }
.dum-cat-card { display: flex; flex-direction: column; align-items: center; gap: .3rem; padding: .7rem .3rem; border-radius: 10px; border: 2px solid #e2e8f0; cursor: pointer; transition: all .2s; background: #fff; }
.dum-cat-card:hover { border-color: #94a3b8; transform: translateY(-1px); }
.dum-cat-card.active { border-color: #0077B5; background: #e8f4fd; }
.dum-cat-icon { font-size: 1.1rem; color: #94a3b8; transition: color .2s; }
.dum-cat-card.active .dum-cat-icon { color: #0077B5; }
.dum-cat-label { font-size: .68rem; font-weight: 600; color: #64748b; }
.dum-cat-card.active .dum-cat-label { color: #0077B5; }
.dum-footer { display: flex; align-items: center; justify-content: flex-end; gap: .6rem; padding-top: 1rem; margin-top: .5rem; border-top: 1px solid #f1f5f9; }
.dum-btn { display: inline-flex; align-items: center; gap: .3rem; padding: .5rem 1.1rem; border-radius: 10px; font-size: .82rem; font-weight: 600; border: none; cursor: pointer; transition: all .2s; }
.dum-btn-cancel { background: #fff; color: #64748b; border: 1px solid #e2e8f0; }
.dum-btn-cancel:hover { background: #f8fafc; color: #334155; }
.dum-btn-submit { background: linear-gradient(135deg, #0077B5, #005a87); color: #fff; }
.dum-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,119,181,.3); }
.dum-btn-submit:disabled { opacity: .6; transform: none; }

@media (max-width: 576px) {
  .dum-overlay { padding: .5rem; }
  .dum-dialog { max-height: 95vh; border-radius: 14px; }
  .dum-header { padding: .85rem 1rem; }
  .dum-body { padding: 1rem; }
  .dum-cat-grid { grid-template-columns: repeat(2, 1fr); }
  .dum-footer { flex-direction: column; align-items: stretch; }
  .dum-btn { justify-content: center; }
}
</style>
