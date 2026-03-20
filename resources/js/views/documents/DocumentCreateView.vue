<template>
  <div class="py-4">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <!-- Hero -->
        <div class="upload-hero mb-4">
          <div class="hero-content">
            <router-link :to="{ name: 'documents.index' }" class="btn-back">
              <i class="fas fa-arrow-left"></i> Retour
            </router-link>
            <h2>
              <i class="fas fa-cloud-upload-alt me-2"></i>Uploader un Document
            </h2>
            <p>Ajoutez un nouveau document a votre dossier personnel</p>
          </div>
        </div>

        <form @submit.prevent="submitForm">
          <!-- Section 1: Document Info -->
          <div class="form-card mb-4">
            <div class="form-card-header">
              <div class="form-card-icon" style="background:linear-gradient(135deg,#0077B5,#005885);">
                <i class="fas fa-file-signature"></i>
              </div>
              <h5>Informations du document</h5>
            </div>
            <div class="form-card-body">
              <!-- Nom du document -->
              <div class="mb-4">
                <label class="field-label">Nom du document <span class="req">*</span></label>
                <input
                  v-model="form.nom_document"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.nom_document }"
                  placeholder="Ex: Diplome de licence en informatique"
                  required
                />
                <div class="field-hint">Donnez un nom clair et explicite a votre document</div>
                <div v-if="errors.nom_document" class="invalid-feedback d-block">{{ errors.nom_document[0] }}</div>
              </div>

              <!-- Categorie (visual cards) -->
              <div class="mb-4">
                <label class="field-label">Categorie <span class="req">*</span></label>
                <div class="cat-cards">
                  <div
                    v-for="cat in categories"
                    :key="cat.value"
                    class="cat-card"
                    :class="{ selected: form.categories_document_id === cat.value }"
                    @click="form.categories_document_id = cat.value"
                  >
                    <div class="cat-card-icon" :style="cat.style">
                      <i :class="cat.icon"></i>
                    </div>
                    <div>
                      <div class="cat-card-text">{{ cat.label }}</div>
                      <div class="cat-card-sub">{{ cat.sub }}</div>
                    </div>
                  </div>
                </div>
                <div v-if="errors.categories_document_id" class="invalid-feedback d-block">{{ errors.categories_document_id[0] }}</div>
              </div>

              <!-- Description -->
              <div>
                <label class="field-label">Description <span style="color:#999;font-weight:400;">(optionnel)</span></label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="form-control"
                  :class="{ 'is-invalid': errors.description }"
                  placeholder="Decrivez brievement le contenu du document..."
                  maxlength="500"
                ></textarea>
                <div class="field-hint">Max 500 caracteres</div>
                <div v-if="errors.description" class="invalid-feedback d-block">{{ errors.description[0] }}</div>
              </div>
            </div>
          </div>

          <!-- Section 2: File Upload -->
          <div class="form-card mb-4">
            <div class="form-card-header">
              <div class="form-card-icon" style="background:linear-gradient(135deg,#0077B5,#005885);">
                <i class="fas fa-cloud-upload-alt"></i>
              </div>
              <h5>Fichier a uploader</h5>
            </div>
            <div class="form-card-body">
              <input
                ref="fileInput"
                type="file"
                class="d-none"
                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                @change="onFileSelect"
              />

              <!-- Drop Zone -->
              <div
                v-if="!selectedFile"
                class="upload-zone"
                :class="{ 'drag-over': isDragging }"
                @click="$refs.fileInput.click()"
                @dragover.prevent="isDragging = true"
                @dragleave="isDragging = false"
                @drop.prevent="onDrop"
              >
                <div class="upload-zone-icon">
                  <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h6>Glissez votre fichier ici</h6>
                <p class="upload-hint">ou cliquez pour parcourir vos fichiers</p>
                <div class="upload-formats">
                  <span class="format-badge pdf"><i class="fas fa-file-pdf"></i> PDF</span>
                  <span class="format-badge img"><i class="fas fa-file-image"></i> JPG</span>
                  <span class="format-badge img"><i class="fas fa-file-image"></i> PNG</span>
                </div>
                <button type="button" class="btn btn-browse mt-3" @click.stop="$refs.fileInput.click()">
                  <i class="fas fa-folder-open me-2"></i>Parcourir
                </button>
              </div>

              <!-- File Preview -->
              <div v-else class="file-preview show">
                <div class="file-preview-inner">
                  <div :class="['file-thumb', fileThumbClass]">
                    <i :class="fileThumbIcon"></i>
                  </div>
                  <div class="file-info">
                    <div class="file-name">{{ selectedFile.name }}</div>
                    <div class="file-size">{{ formatFileSize(selectedFile.size) }}</div>
                  </div>
                  <button type="button" class="btn-clear-file" @click="clearFile">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>

              <div class="field-hint mt-2"><i class="fas fa-info-circle me-1"></i>Taille maximum : 10 MB</div>
              <div v-if="errors.fichier" class="invalid-feedback d-block">{{ errors.fichier[0] }}</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex gap-2 justify-content-between align-items-center mb-4">
            <router-link :to="{ name: 'documents.index' }" class="btn btn-cancel">
              <i class="fas fa-times me-2"></i>Annuler
            </router-link>
            <button type="submit" class="btn btn-submit" :disabled="submitting">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-cloud-upload-alt me-2"></i>
              {{ submitting ? 'Upload en cours...' : 'Uploader le document' }}
            </button>
          </div>
        </form>

        <!-- Security Banner -->
        <div class="info-banner">
          <div class="info-banner-icon"><i class="fas fa-shield-alt"></i></div>
          <p><strong>Securite :</strong> Vos documents sont accessibles uniquement par vous et l'administration RH autorisee.</p>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { create } from '@/api/documents'
import { useUiStore } from '@/stores/ui'

const router = useRouter()
const ui = useUiStore()

const fileInput = ref(null)
const selectedFile = ref(null)
const isDragging = ref(false)
const submitting = ref(false)
const errors = ref({})

const form = ref({
  nom_document: '',
  categories_document_id: '',
  description: '',
})

const categories = [
  {
    value: 'identite',
    label: 'Identite',
    sub: "Carte d'identite, passeport...",
    icon: 'fas fa-id-card',
    style: 'background:#e8f4fd;color:#0077B5;',
  },
  {
    value: 'parcours',
    label: 'Parcours',
    sub: 'Diplomes, certificats...',
    icon: 'fas fa-graduation-cap',
    style: 'background:#fff3e0;color:#e67e22;',
  },
  {
    value: 'carriere',
    label: 'Carriere',
    sub: 'Experiences, references...',
    icon: 'fas fa-briefcase',
    style: 'background:#ede9fe;color:#7c3aed;',
  },
  {
    value: 'mission',
    label: 'Mission',
    sub: 'Rapports, attestations...',
    icon: 'fas fa-plane',
    style: 'background:#e6f7ef;color:#28a745;',
  },
]

const fileThumbClass = computed(() => {
  if (!selectedFile.value) return 'other'
  const ext = selectedFile.value.name.split('.').pop().toLowerCase()
  if (ext === 'pdf') return 'pdf'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'image'
  return 'other'
})

const fileThumbIcon = computed(() => {
  if (!selectedFile.value) return 'fas fa-file'
  const ext = selectedFile.value.name.split('.').pop().toLowerCase()
  if (ext === 'pdf') return 'fas fa-file-pdf'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'fas fa-file-image'
  return 'fas fa-file'
})

function formatFileSize(bytes) {
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB'
  return (bytes / 1024).toFixed(1) + ' KB'
}

function onFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
  }
}

function onDrop(event) {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) {
    selectedFile.value = file
  }
}

function clearFile() {
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

async function submitForm() {
  errors.value = {}

  // Client-side validation
  if (!form.value.nom_document.trim()) {
    errors.value.nom_document = ['Le nom du document est requis.']
    return
  }
  if (!selectedFile.value) {
    errors.value.fichier = ['Veuillez selectionner un fichier.']
    return
  }

  submitting.value = true

  const formData = new FormData()
  formData.append('nom_document', form.value.nom_document)
  formData.append('fichier', selectedFile.value)
  if (form.value.categories_document_id) {
    formData.append('categories_document_id', form.value.categories_document_id)
  }
  if (form.value.description) {
    formData.append('description', form.value.description)
  }

  try {
    await create(formData)
    ui.addToast('Document uploade avec succes.', 'success')
    router.push({ name: 'documents.index' })
  } catch (err) {
    if (err.response?.status === 422 && err.response.data.errors) {
      errors.value = err.response.data.errors
    } else {
      ui.addToast('Erreur lors de l\'upload du document.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
/* Hero */
.upload-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005885 50%, #004165 100%);
  border-radius: 16px;
  padding: 2rem;
  position: relative;
  overflow: hidden;
  color: #fff;
}
.upload-hero::after {
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
.upload-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-content {
  position: relative;
  z-index: 2;
}
.upload-hero h2 {
  font-weight: 800;
  font-size: 1.6rem;
  margin-bottom: 0.25rem;
}
.upload-hero p {
  opacity: 0.85;
  margin-bottom: 0;
  font-size: 0.95rem;
}
.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
  transition: color 0.2s;
}
.btn-back:hover {
  color: #fff;
}

/* Form Card */
.form-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
  overflow: hidden;
}
.form-card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 2px solid #f0f2f5;
}
.form-card-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  color: #fff;
  flex-shrink: 0;
}
.form-card-header h5 {
  font-weight: 700;
  margin-bottom: 0;
  color: #1a1a2e;
  font-size: 1.05rem;
}
.form-card-body {
  padding: 1.5rem;
}

/* Form Fields */
.field-label {
  font-weight: 700;
  color: #1a1a2e;
  font-size: 0.9rem;
  margin-bottom: 0.4rem;
}
.field-label .req {
  color: #dc3545;
  margin-left: 0.15rem;
}
.field-hint {
  font-size: 0.78rem;
  color: #999;
  margin-top: 0.3rem;
}
.form-card .form-control,
.form-card .form-select {
  border-radius: 10px;
  border: 2px solid #e9ecef;
  padding: 0.65rem 1rem;
  font-size: 0.92rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.form-card .form-control:focus,
.form-card .form-select:focus {
  border-color: #0077B5;
  box-shadow: 0 0 0 0.2rem rgba(0, 119, 181, 0.12);
}

/* Category Cards */
.cat-cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}
.cat-card {
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.cat-card:hover {
  border-color: #0077B5;
  background: #e8f4fd;
}
.cat-card.selected {
  border-color: #0077B5;
  background: #e8f4fd;
}
.cat-card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
}
.cat-card-text {
  font-weight: 600;
  font-size: 0.88rem;
  color: #1a1a2e;
}
.cat-card-sub {
  font-size: 0.75rem;
  color: #999;
}

/* Upload Zone */
.upload-zone {
  border: 2px dashed #d1d5db;
  border-radius: 16px;
  padding: 3rem 2rem;
  text-align: center;
  background: linear-gradient(180deg, #f9fafb 0%, #fff 100%);
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}
.upload-zone:hover {
  border-color: #0077B5;
  background: linear-gradient(180deg, #e8f4fd 0%, #fff 100%);
}
.upload-zone.drag-over {
  border-color: #0077B5;
  background: #e8f4fd;
  transform: scale(1.01);
}
.upload-zone-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e8f4fd, #cce5f6);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
  transition: transform 0.3s;
}
.upload-zone:hover .upload-zone-icon {
  transform: scale(1.1);
}
.upload-zone-icon i {
  font-size: 2rem;
  color: #0077B5;
}
.upload-zone h6 {
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 0.25rem;
}
.upload-hint {
  color: #999;
  font-size: 0.85rem;
}
.upload-formats {
  display: inline-flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
}
.format-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.6rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
}
.format-badge.pdf { background: #fdeaec; color: #dc3545; }
.format-badge.img { background: #e0f4ff; color: #0dcaf0; }
.btn-browse {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none;
  color: #fff;
  font-weight: 600;
  padding: 0.5rem 1.25rem;
  border-radius: 10px;
  transition: all 0.2s;
}
.btn-browse:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 14px rgba(0, 119, 181, 0.3);
  color: #fff;
}

/* File Preview */
.file-preview {
  background: #f8f9fc;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1rem 1.25rem;
}
.file-preview-inner {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.file-thumb {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1.3rem;
}
.file-thumb.pdf { background: #fdeaec; color: #dc3545; }
.file-thumb.image { background: #e0f4ff; color: #0dcaf0; }
.file-thumb.other { background: #f5f5f5; color: #6c757d; }
.file-info {
  flex: 1;
}
.file-name {
  font-weight: 700;
  color: #1a1a2e;
  font-size: 0.9rem;
}
.file-size {
  font-size: 0.78rem;
  color: #999;
}
.btn-clear-file {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: #fef5f5;
  color: #dc3545;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
  cursor: pointer;
}
.btn-clear-file:hover {
  background: #fdeaec;
  transform: scale(1.1);
}

/* Info Banner */
.info-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background: linear-gradient(135deg, #e8f4fd, #f0f7ff);
  border: 1px solid #b3d9f2;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
}
.info-banner-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #0077B5;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}
.info-banner p {
  margin: 0;
  font-size: 0.88rem;
  color: #1a1a2e;
}
.info-banner strong {
  color: #004165;
}

/* Buttons */
.btn-submit {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none;
  color: #fff;
  font-weight: 700;
  padding: 0.7rem 1.75rem;
  border-radius: 12px;
  font-size: 0.95rem;
  transition: all 0.2s;
}
.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 119, 181, 0.3);
  color: #fff;
}
.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.btn-cancel {
  background: #f5f5f5;
  border: 2px solid #e9ecef;
  color: #666;
  font-weight: 600;
  padding: 0.7rem 1.5rem;
  border-radius: 12px;
  transition: all 0.2s;
  text-decoration: none;
}
.btn-cancel:hover {
  background: #e9ecef;
  color: #333;
}

@media (max-width: 576px) {
  .cat-cards {
    grid-template-columns: 1fr;
  }
}
</style>
