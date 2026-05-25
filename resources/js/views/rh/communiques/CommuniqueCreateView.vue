<template>
  <div class="container-fluid" style="max-width:960px;padding-top:2rem;padding-bottom:3rem;">

    <!-- Hero -->
    <div class="communique-hero">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h2><i class="fas fa-bullhorn me-2"></i> {{ isEdit ? 'Modifier le Communiqué' : 'Nouveau Communiqué' }}</h2>
          <p>{{ isEdit ? 'Modifier ce communiqué officiel.' : 'Publier un communiqué à destination de tous les agents.' }}</p>
        </div>
        <router-link :to="backRoute" class="btn btn-light btn-sm" style="border-radius:8px;font-weight:600;">
          <i class="fas fa-arrow-left me-1"></i> Retour
        </router-link>
      </div>
    </div>

    <div v-if="loadingEdit" class="text-center py-5">
      <LoadingSpinner message="Chargement du communiqué..." />
    </div>

    <template v-else>
      <!-- Validation errors -->
      <div v-if="errors.length" class="alert alert-danger alert-dismissible fade show mb-3" style="border-radius:10px;">
        <strong><i class="fas fa-exclamation-triangle me-1"></i> Erreurs de validation :</strong>
        <ul class="mb-0 mt-2">
          <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
        </ul>
        <button type="button" class="btn-close" @click="errors = []"></button>
      </div>

      <form @submit.prevent="handleSubmit">

        <!-- Section 1: Contenu -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#0891b2,#0e7490)">
              <i class="fas fa-file-signature"></i>
            </div>
            <div>
              <h5>Contenu du communiqué</h5>
              <small>Titre et corps du message</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label for="titre" class="form-label fw-bold">Titre du communiqué <span class="text-danger">*</span></label>
              <input v-model="form.titre" type="text" class="form-control" id="titre" required
                     placeholder="Ex: Communiqué relatif aux congés annuels">
            </div>
            <div class="col-12">
              <label for="contenu" class="form-label fw-bold">Contenu <span class="text-danger">*</span></label>
              <textarea v-model="form.contenu" class="form-control" id="contenu" rows="8" required
                        placeholder="Rédigez le contenu du communiqué..."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">Pièces jointes</label>
              <div class="attachment-zone">
                <input
                  ref="attachmentInput"
                  type="file"
                  class="d-none"
                  multiple
                  @change="handleAttachmentChange"
                >
                <button type="button" class="attachment-picker" @click="openAttachmentPicker">
                  <i class="fas fa-paperclip"></i>
                  Ajouter des fichiers
                </button>
                <span>10 fichiers max, 10 Mo par fichier.</span>
              </div>
              <div v-if="selectedAttachments.length" class="attachment-list">
                <div
                  v-for="(file, index) in selectedAttachments"
                  :key="`${file.name}-${file.size}-${index}`"
                  class="attachment-item"
                >
                  <i class="fas fa-file"></i>
                  <span>{{ file.name }}</span>
                  <small>{{ formatFileSize(file.size) }}</small>
                  <button type="button" title="Retirer" @click="removeSelectedAttachment(index)">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div v-if="existingAttachments.length" class="attachment-list existing">
                <div
                  v-for="file in existingAttachments"
                  :key="file.id"
                  class="attachment-item"
                >
                  <i class="fas fa-file-lines"></i>
                  <a :href="file.url" target="_blank" rel="noopener">{{ file.name }}</a>
                  <small>{{ formatFileSize(file.size) }}</small>
                  <button type="button" title="Supprimer" @click="removeExistingAttachment(file.id)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: Paramètres -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
              <i class="fas fa-cog"></i>
            </div>
            <div>
              <h5>Paramètres de publication</h5>
              <small>Urgence, signataire et expiration</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="urgence" class="form-label fw-bold">Niveau d'urgence <span class="text-danger">*</span></label>
              <select v-model="form.urgence" class="form-select" id="urgence" required>
                <option value="normal">Normal</option>
                <option value="important">Important</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="signataire" class="form-label fw-bold">Signataire (au nom de)</label>
              <input v-model="form.signataire" type="text" class="form-control" id="signataire"
                     placeholder="Ex: Le Secrétaire Exécutif National">
            </div>
            <div class="col-md-4">
              <label for="date_expiration" class="form-label fw-bold">Date d'expiration</label>
              <input v-model="form.date_expiration" type="date" class="form-control" id="date_expiration">
              <small class="text-muted">Laisser vide pour un communiqué sans expiration.</small>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input v-model="form.actif" class="form-check-input" type="checkbox" id="actif">
                <label class="form-check-label" for="actif">
                  Publier immédiatement (actif)
                </label>
              </div>
            </div>
            <div class="col-12">
              <div class="mail-option">
                <input v-model="form.notify_by_mail" class="form-check-input" type="checkbox" id="notify_by_mail">
                <label class="form-check-label" for="notify_by_mail">
                  Notifier par mail les agents
                  <span>Envoi uniquement aux e-mails professionnels.</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-3 justify-content-end">
          <router-link :to="backRoute" class="btn btn-cancel">
            <i class="fas fa-times me-1"></i> Annuler
          </router-link>
          <button type="submit" class="btn btn-submit" :disabled="submitting">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-paper-plane me-1"></i>
            {{ isEdit ? 'Mettre à jour' : 'Publier le communiqué' }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { create, update, get } from '@/api/communiques'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const isEdit = computed(() => !!route.query.edit)
const fromSen = computed(() => route.query.from === 'sen')
const backRoute = computed(() => fromSen.value ? { name: 'dashboard' } : { name: 'rh.communiques.index' })
const loadingEdit = ref(false)
const submitting = ref(false)
const errors = ref([])
const attachmentInput = ref(null)
const selectedAttachments = ref([])
const existingAttachments = ref([])
const removedAttachmentIds = ref([])
const form = ref({
  titre: '',
  contenu: '',
  urgence: 'normal',
  signataire: '',
  date_expiration: '',
  actif: true,
  notify_by_mail: false,
})

async function loadCommunique() {
  if (!route.query.edit) return
  loadingEdit.value = true
  try {
    const { data } = await get(route.query.edit)
    const c = data.data
    form.value = {
      titre: c.titre || '',
      contenu: c.contenu || '',
      urgence: c.urgence || 'normal',
      signataire: c.signataire || '',
      date_expiration: c.date_expiration ? c.date_expiration.split('T')[0] : '',
      actif: !!c.actif,
      notify_by_mail: false,
    }
    existingAttachments.value = Array.isArray(c.attachments) ? c.attachments : []
  } catch {
    ui.addToast('Communique introuvable.', 'danger')
    router.push(backRoute.value)
  } finally {
    loadingEdit.value = false
  }
}

async function handleSubmit() {
  errors.value = []
  submitting.value = true
  try {
    const payload = buildPayload()

    if (isEdit.value) {
      await update(route.query.edit, payload)
      ui.addToast('Communique mis à jour avec succes.', 'success')
    } else {
      await create(payload)
      ui.addToast('Communiqué publié avec succès.', 'success')
    }
    router.push(backRoute.value)
  } catch (err) {
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors || {}
      errors.value = Object.values(validationErrors).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de l\'operation.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

function buildPayload() {
  const payload = new FormData()

  Object.entries(form.value).forEach(([key, value]) => {
    if (typeof value === 'boolean') {
      payload.append(key, value ? '1' : '0')
      return
    }

    payload.append(key, value ?? '')
  })

  selectedAttachments.value.forEach(file => payload.append('attachments[]', file))
  removedAttachmentIds.value.forEach(id => payload.append('remove_attachment_ids[]', String(id)))

  return payload
}

function openAttachmentPicker() {
  attachmentInput.value?.click()
}

function handleAttachmentChange(event) {
  const files = Array.from(event.target.files || [])
  const nextAttachments = [...selectedAttachments.value]

  for (const file of files) {
    if (nextAttachments.length >= 10) {
      errors.value = ['Maximum 10 pièces jointes par communiqué.']
      break
    }

    if (file.size > 10 * 1024 * 1024) {
      errors.value = [`${file.name} dépasse 10 Mo.`]
      continue
    }

    const alreadyAdded = nextAttachments.some(item =>
      item.name === file.name
      && item.size === file.size
      && item.lastModified === file.lastModified
    )

    if (!alreadyAdded) {
      nextAttachments.push(file)
    }
  }

  selectedAttachments.value = nextAttachments
  event.target.value = ''
}

function removeSelectedAttachment(index) {
  selectedAttachments.value = selectedAttachments.value.filter((_, itemIndex) => itemIndex !== index)
}

function removeExistingAttachment(id) {
  existingAttachments.value = existingAttachments.value.filter(file => file.id !== id)
  if (!removedAttachmentIds.value.includes(id)) {
    removedAttachmentIds.value.push(id)
  }
}

function formatFileSize(bytes) {
  const size = Number(bytes || 0)
  if (!size) return ''
  if (size < 1024 * 1024) return `${Math.round(size / 1024)} Ko`
  return `${(size / (1024 * 1024)).toFixed(1)} Mo`
}

onMounted(() => loadCommunique())
</script>

<style scoped>
.communique-hero {
    background: linear-gradient(135deg, #0077B5 0%, #005885 100%);
    color: #fff;
    border-radius: 16px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}
.communique-hero::after {
    content: '';
    position: absolute;
    right: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 200px;
    height: 200px;
    background: url('/images/pnmls.jpeg') center/contain no-repeat;
    opacity: 0.10;
    pointer-events: none;
}
.communique-hero h2 { font-weight: 700; margin-bottom: .25rem; }
.communique-hero p { opacity: .85; margin-bottom: 0; }
.form-section {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    padding: 2rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}
.form-section-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.5rem;
    padding-bottom: .75rem;
    border-bottom: 2px solid #e9ecef;
}
.form-section-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #fff; flex-shrink: 0;
}
.form-section-header h5 { font-weight: 700; margin-bottom: 0; color: #1a1a2e; }
.form-section-header small { color: #6c757d; font-weight: 400; }
.btn-submit {
    background: linear-gradient(135deg, #0077B5, #005885);
    border: none; color: #fff; font-weight: 600;
    padding: .7rem 2rem; border-radius: 10px;
    transition: transform .15s, box-shadow .15s;
}
.btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,119,181,.35);
    color: #fff;
}
.btn-submit:disabled { opacity: .7; cursor: not-allowed; }
.btn-cancel {
    background: #f2f4f7; color: #344054; font-weight: 600;
    border: 1.5px solid #d0d5dd; border-radius: 10px;
    padding: .7rem 2rem; text-decoration: none;
    transition: background .15s;
}
.btn-cancel:hover { background: #e4e7ec; color: #1a1a2e; }
.mail-option {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: .8rem .9rem;
    border: 1px solid rgba(0,119,181,.18);
    border-radius: 10px;
    background: rgba(232,244,253,.72);
}
.mail-option label {
    display: grid;
    gap: .12rem;
    font-weight: 700;
    color: #1a1a2e;
}
.mail-option span {
    color: #667085;
    font-size: .8rem;
    font-weight: 500;
}
.attachment-zone {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .65rem;
    padding: .85rem;
    border: 1px dashed #b8d7e2;
    border-radius: 10px;
    background: #f8fbfc;
    color: #667085;
    font-size: .84rem;
}
.attachment-picker {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    border: 0;
    border-radius: 8px;
    background: #0077B5;
    color: #fff;
    padding: .48rem .85rem;
    font-weight: 700;
}
.attachment-list {
    display: grid;
    gap: .5rem;
    margin-top: .65rem;
}
.attachment-list.existing {
    border-top: 1px solid #eef2f7;
    padding-top: .65rem;
}
.attachment-item {
    display: grid;
    grid-template-columns: 22px minmax(0, 1fr) auto 30px;
    align-items: center;
    gap: .55rem;
    padding: .55rem .65rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #334155;
}
.attachment-item span,
.attachment-item a {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #1e293b;
    text-decoration: none;
    font-weight: 600;
}
.attachment-item small {
    color: #64748b;
    font-size: .75rem;
}
.attachment-item button {
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 8px;
    background: #fee2e2;
    color: #dc2626;
}

@media (max-width: 767.98px) {
    .communique-hero {
        padding: 1.25rem 1rem;
        border-radius: 10px;
    }
    .communique-hero::after {
        width: 120px;
        height: 120px;
        right: -10px;
    }
    .communique-hero h2 { font-size: 1.2rem; }
    .form-section {
        padding: 1.25rem 1rem;
        border-radius: 10px;
    }
    .form-section-header h5 { font-size: 1rem; }
    .form-section-icon { width: 36px; height: 36px; font-size: 0.95rem; }
    .btn-submit, .btn-cancel { padding: 0.6rem 1.25rem; font-size: 0.9rem; }
    .d-flex.gap-3.justify-content-end {
        flex-direction: column;
    }
    .d-flex.gap-3.justify-content-end .btn {
        width: 100%;
    }
    .attachment-zone {
        align-items: stretch;
        flex-direction: column;
    }
    .attachment-picker {
        justify-content: center;
    }
    .attachment-item {
        grid-template-columns: 22px minmax(0, 1fr) 30px;
    }
    .attachment-item small {
        display: none;
    }
}
</style>
