<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Chargement...</span>
        </div>
      </div>

      <template v-else-if="demande">
        <!-- Hero -->
        <section class="rh-hero">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <router-link :to="{ name: 'requests.index' }" class="back-link">
                <i class="fas fa-arrow-left me-1"></i> Retour aux demandes
              </router-link>
              <h1 class="rh-title mt-2"><i class="fas fa-file-alt me-2"></i>Demande #{{ demande.id }}</h1>
              <p class="rh-sub">Soumise le {{ formatDateTime(demande.created_at) }}</p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <span :class="statusHeroBadge(demande.statut)">
                  <i :class="statusIcon(demande.statut)" class="me-1"></i>
                  {{ statusLabel(demande.statut) }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <div id="demande-print-area">
          <!-- Info cards -->
          <div class="info-row mt-3">
            <div class="dash-panel info-card">
              <div class="info-icon blue"><i class="fas fa-user"></i></div>
              <div>
                <div class="info-label">Agent</div>
                <div class="info-value">{{ demande.agent?.prenom }} {{ demande.agent?.nom }}</div>
                <div class="info-sub">{{ demande.agent?.id_agent }} · {{ demande.agent?.poste_actuel }}</div>
              </div>
            </div>
            <div class="dash-panel info-card">
              <div class="info-icon teal"><i class="fas fa-tag"></i></div>
              <div>
                <div class="info-label">Type de demande</div>
                <div class="info-value">{{ formatType(demande.type) }}</div>
              </div>
            </div>
            <div class="dash-panel info-card">
              <div class="info-icon purple"><i class="fas fa-calendar-alt"></i></div>
              <div>
                <div class="info-label">Periode</div>
                <div class="info-value">
                  {{ formatDate(demande.date_debut) }}
                  <template v-if="demande.date_fin"> - {{ formatDate(demande.date_fin) }}</template>
                </div>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div class="dash-panel mt-3">
            <div class="panel-header">
              <h6 class="panel-title"><i class="fas fa-align-left me-2"></i> Description</h6>
            </div>
            <div class="panel-body">
              <p class="desc-text">{{ demande.description }}</p>
            </div>
          </div>

          <!-- Remarques -->
          <div v-if="demande.remarques" class="dash-panel mt-3">
            <div class="panel-header">
              <h6 class="panel-title"><i class="fas fa-comment-alt me-2"></i> Remarques RH</h6>
            </div>
            <div class="panel-body">
              <div class="remark-box">
                <i class="fas fa-quote-left remark-icon"></i>
                <p class="mb-0">{{ demande.remarques }}</p>
              </div>
            </div>
          </div>

          <!-- Lettre de demande -->
          <div v-if="demande.lettre_demande" class="dash-panel mt-3">
            <div class="panel-header">
              <h6 class="panel-title"><i class="fas fa-paperclip me-2"></i> Lettre de demande</h6>
            </div>
            <div class="panel-body">
              <div class="file-box">
                <div class="file-icon"><i class="fas fa-file-alt"></i></div>
                <div class="file-info">
                  <span class="file-name">{{ fileName(demande.lettre_demande) }}</span>
                </div>
                <a :href="storageUrl(demande.lettre_demande)" target="_blank" class="btn-rh outline">
                  <i class="fas fa-download me-1"></i> Telecharger
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Workflow Timeline -->
        <div v-if="workflow.length" class="dash-panel mt-3">
          <WorkflowTimeline :steps="workflow" />
        </div>

        <!-- Validation actions (for authorized users on pending requests) -->
        <div v-if="canValidate && demande.statut === 'en_attente'" class="dash-panel mt-3">
          <div class="p-4">
            <h6 class="section-title"><i class="fas fa-gavel me-2"></i> Action de validation</h6>
            <p class="text-muted small mb-3">
              Cette demande attend votre validation à l'étape <strong>{{ currentStepLabel }}</strong>.
            </p>

            <!-- Reject reason -->
            <div v-if="showRejectForm" class="mb-3">
              <label class="form-label fw-semibold">
                <i class="fas fa-comment-alt me-1 text-muted"></i> Motif du rejet
                <span class="text-muted fw-normal ms-1">(optionnel)</span>
              </label>
              <textarea
                v-model="rejectRemarques" rows="3"
                class="form-control"
                placeholder="Expliquez le motif du rejet..."
              ></textarea>
            </div>

            <div class="d-flex gap-2 flex-wrap">
              <button
                v-if="!showRejectForm"
                class="btn-rh success"
                :disabled="validating"
                @click="handleValidate"
              >
                <span v-if="validating" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-check-circle me-1"></i>
                Valider cette étape
              </button>
              <button
                v-if="!showRejectForm"
                class="btn-rh danger-outline"
                @click="showRejectForm = true"
              >
                <i class="fas fa-times-circle me-1"></i> Rejeter
              </button>
              <template v-if="showRejectForm">
                <button
                  class="btn-rh danger"
                  :disabled="validating"
                  @click="handleReject"
                >
                  <span v-if="validating" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-ban me-1"></i>
                  Confirmer le rejet
                </button>
                <button class="btn-rh outline" @click="showRejectForm = false; rejectRemarques = ''">
                  Annuler
                </button>
              </template>
            </div>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="action-bar mt-3">
          <router-link :to="{ name: 'requests.index' }" class="btn-rh outline">
            <i class="fas fa-arrow-left me-1"></i> Retour
          </router-link>
          <div class="d-flex gap-2 flex-wrap">
            <button class="btn-rh success" @click="handlePrint">
              <i class="fas fa-print me-1"></i> Imprimer
            </button>
            <router-link
              v-if="isRH"
              :to="{ name: 'requests.edit', params: { id: demande.id } }"
              class="btn-rh warning"
            >
              <i class="fas fa-edit me-1"></i> Modifier le statut
            </router-link>
            <button v-if="canDelete" class="btn-rh danger" @click="showDeleteModal = true">
              <i class="fas fa-trash me-1"></i> Supprimer
            </button>
          </div>
        </div>
      </template>

      <!-- Not found -->
      <div v-else class="dash-panel mt-3">
        <div class="p-5 text-center">
          <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
          <h5>Demande introuvable</h5>
          <router-link :to="{ name: 'requests.index' }" class="btn-rh main mt-3">
            <i class="fas fa-arrow-left me-1"></i> Retour aux demandes
          </router-link>
        </div>
      </div>
    </div>

    <!-- Confirm delete modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer la demande"
      message="Etes-vous sur de vouloir supprimer cette demande ?"
      :loading="deleting"
      @confirm="handleDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { get, remove, validateStep, rejectStep } from '@/api/requests'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import WorkflowTimeline from '@/components/common/WorkflowTimeline.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()

const loading = ref(true)
const demande = ref(null)
const isRH = ref(false)
const isOwner = ref(false)
const canValidate = ref(false)
const workflow = ref([])
const showDeleteModal = ref(false)
const deleting = ref(false)
const validating = ref(false)
const showRejectForm = ref(false)
const rejectRemarques = ref('')

const currentStepLabel = computed(() => {
  const current = workflow.value.find(s => s.status === 'current')
  return current?.label || ''
})

const canDelete = computed(() => {
  if (!demande.value) return false
  if (isRH.value) return true
  return isOwner.value && demande.value.statut === 'en_attente'
})

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' a ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function formatType(type) {
  if (!type) return ''
  return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
}

function statusLabel(statut) {
  const labels = {
    en_attente: 'En attente',
    'approuvé': 'Approuve',
    'rejeté': 'Rejete',
    'annulé': 'Annule',
  }
  return labels[statut] || statut
}

function statusIcon(statut) {
  const icons = {
    en_attente: 'fas fa-hourglass-half',
    'approuvé': 'fas fa-check-circle',
    'rejeté': 'fas fa-times-circle',
    'annulé': 'fas fa-ban',
  }
  return icons[statut] || 'fas fa-circle'
}

function statusHeroBadge(statut) {
  const base = 'hero-status-badge'
  const colors = {
    en_attente: 'warning',
    'approuvé': 'success',
    'rejeté': 'danger',
    'annulé': 'secondary',
  }
  return `${base} ${colors[statut] || 'secondary'}`
}

function fileName(path) {
  if (!path) return ''
  return path.split('/').pop()
}

function storageUrl(path) {
  return '/storage/' + path
}

function handlePrint() {
  window.print()
}

async function handleDelete() {
  deleting.value = true
  try {
    await remove(demande.value.id)
    ui.addToast('Demande supprimee avec succes.', 'success')
    showDeleteModal.value = false
    router.push({ name: 'requests.index' })
  } catch (err) {
    const msg = err.response?.data?.message || 'Erreur lors de la suppression.'
    ui.addToast(msg, 'danger')
  } finally {
    deleting.value = false
  }
}

async function handleValidate() {
  validating.value = true
  try {
    const { data } = await validateStep(demande.value.id)
    demande.value = data.data
    workflow.value = data.workflow || []
    canValidate.value = false
    ui.addToast(data.message || 'Étape validée avec succès.', 'success')
  } catch (err) {
    const msg = err.response?.data?.message || 'Erreur lors de la validation.'
    ui.addToast(msg, 'danger')
  } finally {
    validating.value = false
  }
}

async function handleReject() {
  validating.value = true
  try {
    const { data } = await rejectStep(demande.value.id, { remarques: rejectRemarques.value })
    demande.value = data.data
    workflow.value = data.workflow || []
    canValidate.value = false
    showRejectForm.value = false
    rejectRemarques.value = ''
    ui.addToast(data.message || 'Demande rejetée.', 'warning')
  } catch (err) {
    const msg = err.response?.data?.message || 'Erreur lors du rejet.'
    ui.addToast(msg, 'danger')
  } finally {
    validating.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await get(route.params.id)
    demande.value = data.data
    isRH.value = data.isRH
    isOwner.value = data.isOwner
    canValidate.value = data.canValidate || false
    workflow.value = data.workflow || []
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast('Vous n\'avez pas acces a cette demande.', 'danger')
      router.push({ name: 'requests.index' })
    } else {
      ui.addToast('Erreur lors du chargement de la demande.', 'danger')
    }
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.back-link { color: rgba(255,255,255,.7); text-decoration: none; font-size: .85rem; transition: color .2s; }
.back-link:hover { color: #fff; }

/* Hero status badge */
.hero-status-badge { display: inline-flex; align-items: center; padding: .5rem 1.25rem; border-radius: 12px; font-weight: 700; font-size: .9rem; }
.hero-status-badge.warning { background: rgba(245,158,11,.2); color: #fcd34d; }
.hero-status-badge.success { background: rgba(34,197,94,.2); color: #86efac; }
.hero-status-badge.danger { background: rgba(239,68,68,.2); color: #fca5a5; }
.hero-status-badge.secondary { background: rgba(148,163,184,.2); color: #cbd5e1; }

/* Info row */
.info-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.info-card { display: flex; align-items: flex-start; gap: 1rem; padding: 1.25rem; }
.info-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.info-icon.blue { background: #dbeafe; color: #2563eb; }
.info-icon.teal { background: #ccfbf1; color: #0d9488; }
.info-icon.purple { background: #ede9fe; color: #7c3aed; }
.info-label { font-size: .72rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
.info-value { font-size: .92rem; font-weight: 700; color: #1e293b; margin-top: .15rem; }
.info-sub { font-size: .78rem; color: #94a3b8; margin-top: .1rem; }

/* Panel */
.panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
.panel-title { margin: 0; font-size: .88rem; font-weight: 700; color: #334155; }
.panel-body { padding: 1.25rem; }

.desc-text { margin: 0; font-size: .9rem; line-height: 1.7; color: #334155; white-space: pre-wrap; }

.remark-box { background: #eff6ff; border-left: 4px solid #0077B5; border-radius: 0 10px 10px 0; padding: 1rem 1.25rem; position: relative; font-size: .88rem; color: #334155; line-height: 1.6; }
.remark-icon { position: absolute; top: .5rem; left: .75rem; color: #93c5fd; font-size: .7rem; }

/* File box */
.file-box { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
.file-icon { width: 42px; height: 42px; border-radius: 10px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.file-info { flex-grow: 1; min-width: 0; }
.file-name { font-weight: 600; font-size: .88rem; color: #1e293b; word-break: break-all; }

/* Action bar */
.action-bar { display: flex; align-items: center; justify-content: space-between; padding: 1rem 0; flex-wrap: wrap; gap: .75rem; }

/* Buttons */
.btn-rh { display: inline-flex; align-items: center; gap: .35rem; padding: .55rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: .85rem; border: none; cursor: pointer; transition: all .2s; text-decoration: none; }
.btn-rh.main { background: linear-gradient(135deg, #0077B5, #005a87); color: #fff; }
.btn-rh.main:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,119,181,.3); }
.btn-rh.outline { background: #fff; color: #64748b; border: 1px solid #e2e8f0; }
.btn-rh.outline:hover { background: #f8fafc; color: #334155; }
.btn-rh.success { background: #16a34a; color: #fff; }
.btn-rh.success:hover { background: #15803d; }
.btn-rh.warning { background: #d97706; color: #fff; }
.btn-rh.warning:hover { background: #b45309; }
.btn-rh.danger { background: #ef4444; color: #fff; }
.btn-rh.danger:hover { background: #dc2626; }
.btn-rh.danger-outline { background: #fff; color: #ef4444; border: 1px solid #fecaca; }
.btn-rh.danger-outline:hover { background: #fef2f2; border-color: #ef4444; }
.section-title { font-weight: 700; font-size: .95rem; color: #1e293b; margin-bottom: .5rem; }

@media (max-width: 767.98px) {
  .info-row { grid-template-columns: 1fr; }
  .info-card { padding: 1rem; }
  .hero-status-badge { font-size: .8rem; padding: .4rem 1rem; }
  .file-box { flex-direction: column; text-align: center; gap: .75rem; }
  .action-bar { flex-direction: column; align-items: stretch; }
  .action-bar > div { display: flex; flex-direction: column; gap: .5rem; }
  .btn-rh { justify-content: center; width: 100%; }
}

@media print {
  .rh-hero, .action-bar { display: none !important; }
  .rh-modern { background: #fff !important; padding: 0 !important; }
  .dash-panel { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
