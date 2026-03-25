<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="doc-hero">
      <div class="doc-hero-content">
        <div>
          <h2><i class="fas fa-folder-open me-2"></i>Gestion Electronique de Documents</h2>
          <p>Organisez, consultez et gerez vos documents en toute simplicite</p>
        </div>
        <button class="doc-upload-btn" @click="openUploadModal">
          <i class="fas fa-cloud-upload-alt me-2"></i>Uploader un document
        </button>
      </div>
      <div class="doc-hero-stats">
        <div>
          <div class="doc-hero-stat-val">{{ stats.total }}</div>
          <div class="doc-hero-stat-lbl">Total</div>
        </div>
        <div>
          <div class="doc-hero-stat-val">{{ stats.identite }}</div>
          <div class="doc-hero-stat-lbl">Identite</div>
        </div>
        <div>
          <div class="doc-hero-stat-val">{{ stats.parcours }}</div>
          <div class="doc-hero-stat-lbl">Parcours</div>
        </div>
        <div>
          <div class="doc-hero-stat-val">{{ stats.carriere }}</div>
          <div class="doc-hero-stat-lbl">Carriere</div>
        </div>
        <div>
          <div class="doc-hero-stat-val">{{ stats.mission }}</div>
          <div class="doc-hero-stat-lbl">Mission</div>
        </div>
      </div>
    </div>

    <!-- Category filter cards -->
    <div class="doc-cat-grid">
      <button
        class="doc-cat-all"
        :class="{ active: !filters.categorie }"
        @click="filters.categorie = ''; fetchDocuments(1)"
      >
        <div class="doc-cat-icon"><i class="fas fa-th-large"></i></div>
        <div class="doc-cat-info">
          <div class="doc-cat-name">Toutes</div>
          <div class="doc-cat-count">{{ stats.total }} document{{ stats.total > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button
        class="doc-cat-card"
        :class="{ active: filters.categorie === 'identite' }"
        @click="filters.categorie = 'identite'; fetchDocuments(1)"
      >
        <div class="doc-cat-icon"><i class="fas fa-id-card"></i></div>
        <div class="doc-cat-info">
          <div class="doc-cat-name">Identite</div>
          <div class="doc-cat-count">{{ stats.identite }} document{{ stats.identite > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button
        class="doc-cat-card"
        :class="{ active: filters.categorie === 'parcours' }"
        @click="filters.categorie = 'parcours'; fetchDocuments(1)"
      >
        <div class="doc-cat-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="doc-cat-info">
          <div class="doc-cat-name">Parcours</div>
          <div class="doc-cat-count">{{ stats.parcours }} document{{ stats.parcours > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button
        class="doc-cat-card"
        :class="{ active: filters.categorie === 'carriere' }"
        @click="filters.categorie = 'carriere'; fetchDocuments(1)"
      >
        <div class="doc-cat-icon"><i class="fas fa-briefcase"></i></div>
        <div class="doc-cat-info">
          <div class="doc-cat-name">Carriere</div>
          <div class="doc-cat-count">{{ stats.carriere }} document{{ stats.carriere > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button
        class="doc-cat-card"
        :class="{ active: filters.categorie === 'mission' }"
        @click="filters.categorie = 'mission'; fetchDocuments(1)"
      >
        <div class="doc-cat-icon"><i class="fas fa-plane"></i></div>
        <div class="doc-cat-info">
          <div class="doc-cat-name">Mission</div>
          <div class="doc-cat-count">{{ stats.mission }} document{{ stats.mission > 1 ? 's' : '' }}</div>
        </div>
      </button>
    </div>

    <!-- Search bar -->
    <div class="doc-search-bar">
      <div class="doc-search-input-wrap">
        <i class="fas fa-search doc-search-icon"></i>
        <input
          v-model="filters.search"
          type="text"
          class="doc-search-input"
          placeholder="Rechercher un document..."
          @keyup.enter="fetchDocuments(1)"
        />
      </div>
      <button class="doc-search-btn" @click="fetchDocuments(1)">
        <i class="fas fa-search me-1"></i>Filtrer
      </button>
      <button v-if="filters.search || filters.categorie" class="doc-reset-btn" @click="resetFilters">
        <i class="fas fa-redo"></i>
      </button>
    </div>

    <!-- Section header when filtered -->
    <div v-if="filters.categorie" class="doc-section-header">
      <div class="doc-section-title">
        <i class="fas fa-folder-open" style="color:#0077B5;"></i>
        {{ getCategoryLabel(filters.categorie) }}
        <span class="doc-section-badge">{{ meta.total }} document{{ meta.total > 1 ? 's' : '' }}</span>
      </div>
      <button class="doc-back-btn" @click="resetFilters">
        <i class="fas fa-arrow-left"></i> Toutes les categories
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color:#0077B5;" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="text-muted mt-2">Chargement des documents...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="documents.length === 0" class="doc-empty">
      <div class="doc-empty-icon"><i class="fas fa-folder-open"></i></div>
      <template v-if="filters.categorie">
        <h5>Aucun document dans &laquo; {{ getCategoryLabel(filters.categorie) }} &raquo;</h5>
        <p>Il n'y a pas encore de documents dans cette categorie.</p>
        <button class="doc-back-btn mt-3" style="display:inline-flex;" @click="resetFilters">
          <i class="fas fa-arrow-left"></i> Voir toutes les categories
        </button>
      </template>
      <template v-else>
        <h5>Aucun document trouve</h5>
        <p>Vous n'avez pas encore de documents dans votre dossier. Commencez par uploader votre premier document.</p>
        <button class="doc-upload-action" @click="openUploadModal">
          <i class="fas fa-cloud-upload-alt me-2"></i>Uploader un document
        </button>
      </template>
    </div>

    <!-- Documents Grid -->
    <template v-else>
      <div class="doc-grid">
        <div v-for="doc in documents" :key="doc.id" class="doc-card">
          <div class="doc-card-top">
            <div class="doc-card-icon" :class="getFileIconClass(doc)">
              <i :class="getFileIcon(doc)"></i>
            </div>
            <div class="doc-card-info">
              <div class="doc-card-title">{{ getDocName(doc) }}</div>
              <div v-if="getDocDescription(doc)" class="doc-card-desc">{{ getDocDescription(doc) }}</div>
            </div>
          </div>
          <div class="doc-card-meta">
            <span class="doc-meta-badge doc-meta-cat" :class="doc.type">
              <i :class="getCategoryIcon(doc.type)" class="me-1"></i>{{ getCategoryLabel(doc.type) }}
            </span>
            <span class="doc-meta-badge">.{{ getFileExtension(doc).toUpperCase() }}</span>
          </div>
          <div class="doc-card-footer">
            <span class="doc-card-date"><i class="fas fa-clock me-1"></i>{{ formatDate(doc.created_at) }}</span>
            <div class="doc-card-actions">
              <button class="doc-action-btn doc-action-view" @click="openDetail(doc.id)">
                <i class="fas fa-eye"></i> Voir
              </button>
              <button class="doc-action-btn doc-action-download" @click="handleDownload(doc)">
                <i class="fas fa-download"></i> Telecharger
              </button>
              <button class="doc-action-btn doc-action-delete" @click="confirmDelete(doc)">
                <i class="fas fa-trash-alt"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.total > 0 && meta.last_page > 1" class="doc-pagination">
        <div class="doc-page-info">
          Affichage <strong>{{ meta.from }}</strong> a <strong>{{ meta.to }}</strong>
          sur <strong>{{ meta.total }}</strong> documents
        </div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
              <button class="page-link" @click="fetchDocuments(meta.current_page - 1)">&laquo;</button>
            </li>
            <li
              v-for="page in visiblePages"
              :key="page"
              class="page-item"
              :class="{ active: page === meta.current_page }"
            >
              <button class="page-link" @click="fetchDocuments(page)">{{ page }}</button>
            </li>
            <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }">
              <button class="page-link" @click="fetchDocuments(meta.current_page + 1)">&raquo;</button>
            </li>
          </ul>
        </nav>
      </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer le document"
      message="Etes-vous sur de vouloir supprimer ce document ? Cette action est irreversible."
      :loading="deleteLoading"
      @confirm="doDelete"
      @cancel="showDeleteModal = false"
    />

    <!-- Detail Modal -->
    <teleport to="body">
      <div v-if="showDetailModal" class="ddm-overlay" @click.self="showDetailModal = false">
        <div class="ddm-dialog">
          <!-- Header -->
          <div class="ddm-header">
            <h5 class="ddm-title"><i class="fas fa-file-alt me-2"></i>Details du document</h5>
            <button class="ddm-close" @click="showDetailModal = false"><i class="fas fa-times"></i></button>
          </div>

          <!-- Loading -->
          <div v-if="detailLoading" class="ddm-loading">
            <div class="spinner-border" style="color:#0077B5;" role="status"></div>
            <p class="text-muted mt-2 mb-0">Chargement...</p>
          </div>

          <!-- Body -->
          <div v-else-if="detailDoc" class="ddm-body">
            <!-- Doc header -->
            <div class="ddm-doc-header">
              <div :class="['ddm-doc-icon', getFileIconClass(detailDoc)]">
                <i :class="getFileIcon(detailDoc)"></i>
              </div>
              <div class="ddm-doc-title-wrap">
                <div class="ddm-doc-title">{{ detailDocName(detailDoc) }}</div>
                <div class="ddm-doc-badges">
                  <span :class="['ddm-badge-cat', detailDoc.type]">
                    <i :class="getCategoryIcon(detailDoc.type)" class="me-1"></i>{{ getCategoryLabel(detailDoc.type) }}
                  </span>
                  <span class="ddm-badge-ext">.{{ getFileExtension(detailDoc).toUpperCase() }}</span>
                </div>
              </div>
            </div>

            <!-- Info grid -->
            <div class="ddm-info-grid">
              <div class="ddm-info-card">
                <div class="ddm-info-icon ddm-ic-blue"><i class="fas fa-user"></i></div>
                <div>
                  <div class="ddm-info-label">Agent</div>
                  <div class="ddm-info-value">{{ detailDoc.agent?.prenom }} {{ detailDoc.agent?.nom }}</div>
                </div>
              </div>
              <div class="ddm-info-card">
                <div class="ddm-info-icon ddm-ic-orange"><i class="fas fa-calendar-alt"></i></div>
                <div>
                  <div class="ddm-info-label">Date d'ajout</div>
                  <div class="ddm-info-value">{{ formatDate(detailDoc.created_at) }}</div>
                </div>
              </div>
              <div class="ddm-info-card">
                <div class="ddm-info-icon ddm-ic-purple"><i class="fas fa-weight"></i></div>
                <div>
                  <div class="ddm-info-label">Taille</div>
                  <div class="ddm-info-value">{{ formatFileSize(detailFileMeta.size) }}</div>
                </div>
              </div>
              <div v-if="detailDoc.statut" class="ddm-info-card">
                <div class="ddm-info-icon ddm-ic-green"><i class="fas fa-check-circle"></i></div>
                <div>
                  <div class="ddm-info-label">Statut</div>
                  <div class="ddm-info-value">
                    <span v-if="detailDoc.statut === 'valide'" class="ddm-status-ok">Valide</span>
                    <span v-else class="ddm-status-other">{{ detailDoc.statut }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Description -->
            <div v-if="detailDocDesc(detailDoc)" class="ddm-section">
              <h6 class="ddm-section-title"><i class="fas fa-align-left me-2"></i>Description</h6>
              <p class="ddm-desc">{{ detailDocDesc(detailDoc) }}</p>
            </div>

            <!-- File preview -->
            <div v-if="detailDoc.fichier" class="ddm-section">
              <h6 class="ddm-section-title"><i class="fas fa-paperclip me-2"></i>Fichier</h6>
              <div v-if="detailIsImage()" class="ddm-preview">
                <img :src="'/' + detailDoc.fichier" alt="Document" class="ddm-preview-img" />
              </div>
              <div v-else class="ddm-file">
                <div :class="['ddm-file-icon', getFileIconClass(detailDoc)]">
                  <i :class="getFileIcon(detailDoc)"></i>
                </div>
                <span class="ddm-file-name">{{ detailDoc.fichier.split('/').pop() }}</span>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div v-if="detailDoc && !detailLoading" class="ddm-footer">
            <button class="ddm-btn ddm-btn-primary" @click="downloadDetail">
              <i class="fas fa-download me-1"></i> Telecharger
            </button>
            <button class="ddm-btn ddm-btn-danger" @click="deleteFromDetail">
              <i class="fas fa-trash-alt me-1"></i> Supprimer
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Upload modal -->
    <teleport to="body">
      <div v-if="showUploadModal" class="dum-overlay" @click.self="closeUploadModal">
        <div class="dum-dialog">
          <!-- Header -->
          <div class="dum-header">
            <h5 class="dum-title"><i class="fas fa-cloud-upload-alt me-2"></i>Uploader un Document</h5>
            <button class="dum-close" @click="closeUploadModal"><i class="fas fa-times"></i></button>
          </div>

          <!-- Body -->
          <div class="dum-body">
            <form @submit.prevent="handleUploadSubmit" enctype="multipart/form-data">

              <!-- File upload zone -->
              <div class="mb-3">
                <label class="dum-label"><i class="fas fa-file me-1 text-muted"></i> Fichier <span class="text-danger">*</span></label>
                <div
                  v-if="!uploadFilePreview"
                  class="dum-upload-zone"
                  @click="uploadFileInput.click()"
                  @dragover.prevent="uploadIsDragging = true"
                  @dragleave="uploadIsDragging = false"
                  @drop.prevent="uploadHandleDrop"
                  :class="{ dragging: uploadIsDragging }"
                >
                  <i class="fas fa-cloud-upload-alt dum-upload-icon"></i>
                  <div class="fw-semibold small">Glissez votre fichier ici ou cliquez pour parcourir</div>
                  <div class="text-muted" style="font-size:.7rem;">Tous formats acceptes - Max 10 Mo</div>
                </div>
                <div v-else class="dum-file-preview">
                  <div class="dum-file-icon-box"><i class="fas fa-file-alt"></i></div>
                  <div class="dum-file-info">
                    <div class="dum-file-name">{{ uploadFilePreview.name }}</div>
                    <div class="dum-file-size">{{ uploadFilePreview.size }} &middot; {{ uploadFilePreview.ext }}</div>
                  </div>
                  <button type="button" class="dum-file-remove" @click="uploadRemoveFile"><i class="fas fa-trash-alt"></i></button>
                </div>
                <input ref="uploadFileInput" type="file" class="d-none" @change="uploadHandleFileSelect">
                <div v-if="uploadErrors.fichier" class="dum-error">{{ uploadErrors.fichier[0] }}</div>
              </div>

              <!-- Nom document -->
              <div class="mb-3">
                <label class="dum-label"><i class="fas fa-tag me-1 text-muted"></i> Nom du document <span class="text-danger">*</span></label>
                <input type="text" v-model="uploadForm.nom_document" class="dum-input" :class="{ 'is-invalid': uploadErrors.nom_document }" placeholder="Ex: Carte d'identite, Diplome, Contrat...">
                <div v-if="uploadErrors.nom_document" class="dum-error">{{ uploadErrors.nom_document[0] }}</div>
              </div>

              <!-- Categorie -->
              <div class="mb-3">
                <label class="dum-label"><i class="fas fa-folder me-1 text-muted"></i> Categorie</label>
                <div class="dum-cat-grid">
                  <div
                    v-for="c in categoryOptions" :key="c.value"
                    class="dum-cat-card"
                    :class="{ active: uploadForm.categories_document_id === c.value }"
                    @click="uploadForm.categories_document_id = uploadForm.categories_document_id === c.value ? '' : c.value"
                  >
                    <i :class="c.icon" class="dum-cat-icon"></i>
                    <span class="dum-cat-label">{{ c.label }}</span>
                  </div>
                </div>
                <div v-if="uploadErrors.categories_document_id" class="dum-error">{{ uploadErrors.categories_document_id[0] }}</div>
              </div>

              <!-- Description -->
              <div class="mb-3">
                <label class="dum-label"><i class="fas fa-align-left me-1 text-muted"></i> Description <span class="text-muted fw-normal">(optionnel)</span></label>
                <textarea v-model="uploadForm.description" rows="2" class="dum-input dum-textarea" :class="{ 'is-invalid': uploadErrors.description }" placeholder="Ajoutez une description..."></textarea>
                <div v-if="uploadErrors.description" class="dum-error">{{ uploadErrors.description[0] }}</div>
              </div>

              <!-- Footer -->
              <div class="dum-footer">
                <button type="button" class="dum-btn dum-btn-cancel" @click="closeUploadModal">Annuler</button>
                <button type="submit" class="dum-btn dum-btn-submit" :disabled="uploadSubmitting">
                  <span v-if="uploadSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-cloud-upload-alt me-1"></i>
                  Uploader
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { list, get, create, download, remove } from '@/api/documents'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const router = useRouter()
const ui = useUiStore()

const loading = ref(false)
const documents = ref([])
const stats = ref({ total: 0, identite: 0, parcours: 0, carriere: 0, mission: 0 })
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0, from: 0, to: 0 })

const filters = ref({
  categorie: '',
  search: '',
})

const showDeleteModal = ref(false)
const deleteLoading = ref(false)
const docToDelete = ref(null)

// Detail modal
const showDetailModal = ref(false)
const detailLoading = ref(false)
const detailDoc = ref(null)
const detailFileMeta = ref({ size: 0, extension: '', exists: false })

// Upload modal
const showUploadModal = ref(false)
const uploadSubmitting = ref(false)
const uploadErrors = ref({})
const uploadIsDragging = ref(false)
const uploadSelectedFile = ref(null)
const uploadFilePreview = ref(null)
const uploadFileInput = ref(null)

const categoryOptions = [
  { value: 'identite', label: 'Identite', icon: 'fas fa-id-card' },
  { value: 'parcours', label: 'Parcours', icon: 'fas fa-graduation-cap' },
  { value: 'carriere', label: 'Carriere', icon: 'fas fa-briefcase' },
  { value: 'mission', label: 'Mission', icon: 'fas fa-plane' },
]

function defaultUploadForm() {
  return { nom_document: '', categories_document_id: '', description: '' }
}
const uploadForm = ref(defaultUploadForm())

function openUploadModal() {
  uploadForm.value = defaultUploadForm()
  uploadErrors.value = {}
  uploadSelectedFile.value = null
  uploadFilePreview.value = null
  showUploadModal.value = true
}

function closeUploadModal() {
  showUploadModal.value = false
}

function uploadHandleFileSelect(event) {
  const file = event.target.files[0]
  if (file) uploadSetFile(file)
}

function uploadHandleDrop(event) {
  uploadIsDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) uploadSetFile(file)
}

function uploadSetFile(file) {
  uploadSelectedFile.value = file
  const sizeMb = (file.size / 1024 / 1024).toFixed(2)
  const ext = file.name.split('.').pop().toUpperCase()
  uploadFilePreview.value = { name: file.name, size: sizeMb + ' Mo', ext }
}

function uploadRemoveFile() {
  uploadSelectedFile.value = null
  uploadFilePreview.value = null
  if (uploadFileInput.value) uploadFileInput.value.value = ''
}

async function handleUploadSubmit() {
  uploadErrors.value = {}
  uploadSubmitting.value = true
  const formData = new FormData()
  formData.append('nom_document', uploadForm.value.nom_document)
  if (uploadForm.value.categories_document_id) formData.append('categories_document_id', uploadForm.value.categories_document_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  if (uploadSelectedFile.value) formData.append('fichier', uploadSelectedFile.value)
  try {
    await create(formData)
    ui.addToast('Document uploade avec succes.', 'success')
    showUploadModal.value = false
    await fetchDocuments(1)
  } catch (err) {
    if (err.response?.status === 422) {
      uploadErrors.value = err.response.data.errors || {}
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de l\'upload.', 'danger')
    }
  } finally {
    uploadSubmitting.value = false
  }
}

const visiblePages = computed(() => {
  const pages = []
  const current = meta.value.current_page
  const last = meta.value.last_page
  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

async function fetchDocuments(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filters.value.categorie) params.categorie = filters.value.categorie
    if (filters.value.search) params.search = filters.value.search

    const { data } = await list(params)
    documents.value = data.data
    stats.value = data.stats
    meta.value = data.meta
  } catch (err) {
    ui.addToast('Erreur lors du chargement des documents.', 'danger')
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.value.categorie = ''
  filters.value.search = ''
  fetchDocuments(1)
}

function getDocName(doc) {
  if (!doc.description) return 'Document sans nom'
  const parts = doc.description.split(' | ')
  return parts[0] || 'Document sans nom'
}

function getDocDescription(doc) {
  if (!doc.description) return ''
  const parts = doc.description.split(' | ')
  return parts.length > 1 ? parts.slice(1).join(' | ') : ''
}

function getFileExtension(doc) {
  if (!doc.fichier) return 'other'
  const ext = doc.fichier.split('.').pop().toLowerCase()
  return ext
}

function getFileIconClass(doc) {
  const ext = getFileExtension(doc)
  if (ext === 'pdf') return 'doc-ic-pdf'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'doc-ic-img'
  if (['doc', 'docx'].includes(ext)) return 'doc-ic-doc'
  if (['xls', 'xlsx'].includes(ext)) return 'doc-ic-xls'
  return 'doc-ic-other'
}

function getFileIcon(doc) {
  const ext = getFileExtension(doc)
  if (ext === 'pdf') return 'fas fa-file-pdf'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'fas fa-file-image'
  if (['doc', 'docx'].includes(ext)) return 'fas fa-file-word'
  if (['xls', 'xlsx'].includes(ext)) return 'fas fa-file-excel'
  return 'fas fa-file'
}

function getCategoryIcon(type) {
  const icons = {
    identite: 'fas fa-id-card',
    parcours: 'fas fa-graduation-cap',
    carriere: 'fas fa-briefcase',
    mission: 'fas fa-plane',
  }
  return icons[type] || 'fas fa-file'
}

function getCategoryLabel(type) {
  const labels = {
    identite: 'Identite',
    parcours: 'Parcours',
    carriere: 'Carriere',
    mission: 'Mission',
  }
  return labels[type] || type
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

async function handleDownload(doc) {
  try {
    const response = await download(doc.id)
    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    // Deduce filename from fichier path
    const filename = doc.fichier ? doc.fichier.split('/').pop() : `document_${doc.id}`
    a.download = filename
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
  } catch (err) {
    ui.addToast('Erreur lors du telechargement.', 'danger')
  }
}

function confirmDelete(doc) {
  docToDelete.value = doc
  showDeleteModal.value = true
}

async function doDelete() {
  if (!docToDelete.value) return
  deleteLoading.value = true
  try {
    await remove(docToDelete.value.id)
    ui.addToast('Document supprime avec succes.', 'success')
    showDeleteModal.value = false
    docToDelete.value = null
    fetchDocuments(meta.value.current_page)
  } catch (err) {
    ui.addToast('Erreur lors de la suppression.', 'danger')
  } finally {
    deleteLoading.value = false
  }
}

// Detail modal
async function openDetail(id) {
  showDetailModal.value = true
  detailLoading.value = true
  detailDoc.value = null
  try {
    const { data } = await get(id)
    detailDoc.value = data.document
    detailFileMeta.value = data.file_meta || { size: 0, extension: '', exists: false }
  } catch (err) {
    showDetailModal.value = false
    ui.addToast('Erreur lors du chargement du document.', 'danger')
  } finally {
    detailLoading.value = false
  }
}

function detailDocName(doc) {
  if (!doc?.description) return 'Document sans nom'
  const parts = doc.description.split(' | ')
  return parts[0] || 'Document sans nom'
}

function detailDocDesc(doc) {
  if (!doc?.description) return ''
  const parts = doc.description.split(' | ')
  return parts.length > 1 ? parts.slice(1).join(' | ') : ''
}

function formatFileSize(bytes) {
  if (!bytes || bytes === 0) return '-'
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB'
  return (bytes / 1024).toFixed(1) + ' KB'
}

function detailIsImage() {
  return ['jpg', 'jpeg', 'png', 'gif'].includes(detailFileMeta.value.extension)
}

async function downloadDetail() {
  if (!detailDoc.value) return
  try {
    const response = await download(detailDoc.value.id)
    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const a = window.document.createElement('a')
    a.href = url
    const filename = detailDoc.value.fichier ? detailDoc.value.fichier.split('/').pop() : `document_${detailDoc.value.id}`
    a.download = filename
    window.document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    window.document.body.removeChild(a)
  } catch (err) {
    ui.addToast('Erreur lors du telechargement.', 'danger')
  }
}

function deleteFromDetail() {
  showDetailModal.value = false
  confirmDelete(detailDoc.value)
}

onMounted(() => {
  fetchDocuments()
})
</script>

<style scoped>
/* ── Hero ── */
.doc-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #004165 100%);
  border-radius: 18px;
  padding: 2rem 2.2rem;
  margin-bottom: 1.5rem;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.doc-hero::before {
  content: '';
  position: absolute;
  top: -40%;
  right: -8%;
  width: 240px;
  height: 240px;
  border-radius: 50%;
  background: rgba(255, 255, 255, .07);
}
.doc-hero-content {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  position: relative;
  z-index: 1;
}
.doc-hero h2 {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0 0 .3rem;
}
.doc-hero p {
  font-size: .85rem;
  opacity: .8;
  margin: 0;
}
.doc-hero-stats {
  display: flex;
  gap: 1.5rem;
  margin-top: 1rem;
  position: relative;
  z-index: 1;
}
.doc-hero-stat-val {
  font-size: 1.5rem;
  font-weight: 800;
}
.doc-hero-stat-lbl {
  font-size: .7rem;
  opacity: .7;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.doc-upload-btn {
  display: inline-flex;
  align-items: center;
  background: rgba(255, 255, 255, .18);
  border: 2px solid rgba(255, 255, 255, .35);
  color: #fff;
  font-weight: 700;
  padding: .55rem 1.4rem;
  border-radius: 12px;
  transition: all .25s;
  backdrop-filter: blur(4px);
  text-decoration: none;
  white-space: nowrap;
  flex-shrink: 0;
}
.doc-upload-btn:hover {
  background: #fff;
  color: #0077B5;
  border-color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
}

/* ── Category filter cards ── */
.doc-cat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: .8rem;
  margin-bottom: 1.5rem;
}
.doc-cat-card,
.doc-cat-all {
  display: flex;
  align-items: center;
  gap: .7rem;
  padding: .9rem 1rem;
  background: #fff;
  border: 2px solid #e5e7eb;
  border-radius: 14px;
  text-decoration: none;
  color: #374151;
  transition: all .25s;
  cursor: pointer;
}
.doc-cat-card:hover {
  border-color: #0077B5;
  color: #0077B5;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 119, 181, .1);
}
.doc-cat-card.active {
  background: linear-gradient(135deg, #0077B5, #005a87);
  border-color: #0077B5;
  color: #fff;
  box-shadow: 0 4px 16px rgba(0, 119, 181, .25);
}
.doc-cat-all:hover {
  border-color: #28a745;
  color: #28a745;
  transform: translateY(-2px);
}
.doc-cat-all.active {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  border-color: #28a745;
  color: #fff;
  box-shadow: 0 4px 16px rgba(40, 167, 69, .25);
}
.doc-cat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
  background: #e8f4fd;
  color: #0077B5;
}
.doc-cat-card.active .doc-cat-icon,
.doc-cat-all.active .doc-cat-icon {
  background: rgba(255, 255, 255, .2);
  color: #fff;
}
.doc-cat-all .doc-cat-icon {
  background: #e6f7ef;
  color: #28a745;
}
.doc-cat-info {
  flex: 1;
  min-width: 0;
  text-align: left;
}
.doc-cat-name {
  font-size: .82rem;
  font-weight: 700;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.doc-cat-count {
  font-size: .7rem;
  opacity: .6;
}
.doc-cat-card.active .doc-cat-count,
.doc-cat-all.active .doc-cat-count {
  opacity: .8;
}

/* ── Search bar ── */
.doc-search-bar {
  display: flex;
  align-items: center;
  gap: .6rem;
  margin-bottom: 1.5rem;
  background: #fff;
  border: 2px solid #e5e7eb;
  border-radius: 14px;
  padding: .5rem .7rem;
  transition: border-color .2s;
}
.doc-search-bar:focus-within {
  border-color: #0077B5;
  box-shadow: 0 0 0 .2rem rgba(0, 119, 181, .1);
}
.doc-search-input-wrap {
  display: flex;
  align-items: center;
  flex: 1;
  gap: .5rem;
}
.doc-search-icon {
  color: #9ca3af;
  font-size: .9rem;
  margin-left: .4rem;
}
.doc-search-input {
  border: none;
  outline: none;
  flex: 1;
  font-size: .88rem;
  color: #1e293b;
  background: transparent;
  padding: .4rem 0;
}
.doc-search-input::placeholder {
  color: #9ca3af;
}
.doc-search-btn {
  display: inline-flex;
  align-items: center;
  padding: .45rem 1.1rem;
  border-radius: 10px;
  font-size: .82rem;
  font-weight: 600;
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: #fff;
  border: none;
  cursor: pointer;
  transition: all .2s;
  white-space: nowrap;
}
.doc-search-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 119, 181, .3);
}
.doc-reset-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  font-size: .82rem;
  background: #f3f4f6;
  color: #6b7280;
  border: none;
  cursor: pointer;
  transition: all .2s;
  flex-shrink: 0;
}
.doc-reset-btn:hover {
  background: #e5e7eb;
  color: #374151;
}

/* ── Section header (filtered) ── */
.doc-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  padding-bottom: .6rem;
  border-bottom: 2px solid #f3f4f6;
}
.doc-section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.doc-section-badge {
  font-size: .72rem;
  font-weight: 700;
  padding: .2rem .6rem;
  border-radius: 20px;
  background: #e8f4fd;
  color: #0077B5;
}
.doc-back-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .35rem .8rem;
  border-radius: 8px;
  font-size: .78rem;
  font-weight: 600;
  background: #f3f4f6;
  color: #6b7280;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all .2s;
}
.doc-back-btn:hover {
  background: #e5e7eb;
  color: #374151;
}

/* ── Document grid ── */
.doc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}
.doc-card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
  overflow: hidden;
  transition: all .2s;
  display: flex;
  flex-direction: column;
}
.doc-card:hover {
  box-shadow: 0 6px 24px rgba(0, 0, 0, .08);
  transform: translateY(-2px);
}
.doc-card-top {
  display: flex;
  align-items: flex-start;
  gap: .8rem;
  padding: 1.2rem 1.2rem .6rem;
}
.doc-card-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.doc-ic-pdf { background: #fee2e2; color: #dc2626; }
.doc-ic-img { background: #e0f2fe; color: #0284c7; }
.doc-ic-doc { background: #dbeafe; color: #2563eb; }
.doc-ic-xls { background: #dcfce7; color: #16a34a; }
.doc-ic-other { background: #f1f5f9; color: #64748b; }

.doc-card-info {
  flex: 1;
  min-width: 0;
}
.doc-card-title {
  font-weight: 700;
  font-size: .9rem;
  color: #1e293b;
  margin-bottom: .2rem;
  line-height: 1.3;
}
.doc-card-desc {
  font-size: .78rem;
  color: #9ca3af;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* ── Meta badges ── */
.doc-card-meta {
  padding: .5rem 1.2rem;
  display: flex;
  align-items: center;
  gap: .6rem;
  flex-wrap: wrap;
}
.doc-meta-badge {
  font-size: .68rem;
  font-weight: 600;
  padding: .2rem .55rem;
  border-radius: 6px;
  background: #f3f4f6;
  color: #6b7280;
}
.doc-meta-cat.identite { background: #e8f4fd; color: #0077B5; }
.doc-meta-cat.parcours { background: #fff3e0; color: #e67e22; }
.doc-meta-cat.carriere { background: #ede9fe; color: #7c3aed; }
.doc-meta-cat.mission { background: #e6f7ef; color: #28a745; }

/* ── Card footer ── */
.doc-card-footer {
  border-top: 1px solid #f3f4f6;
  padding: .7rem 1.2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  flex-wrap: wrap;
  gap: .5rem;
}
.doc-card-date {
  font-size: .72rem;
  color: #9ca3af;
}
.doc-card-actions {
  display: flex;
  align-items: center;
  gap: .4rem;
}
.doc-action-btn {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  padding: .3rem .65rem;
  border-radius: 8px;
  font-size: .72rem;
  font-weight: 600;
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all .2s;
}
.doc-action-view {
  background: #e8f4fd;
  color: #0077B5;
  border-color: #bde0f5;
}
.doc-action-view:hover {
  background: #0077B5;
  color: #fff;
  border-color: #0077B5;
}
.doc-action-download {
  background: #e6f7ef;
  color: #28a745;
  border-color: #b7ebc9;
}
.doc-action-download:hover {
  background: #28a745;
  color: #fff;
  border-color: #28a745;
}
.doc-action-delete {
  background: #fef2f2;
  color: #dc3545;
  border-color: #fecaca;
}
.doc-action-delete:hover {
  background: #dc3545;
  color: #fff;
  border-color: #dc3545;
}

/* ── Empty state ── */
.doc-empty {
  text-align: center;
  padding: 3rem 1rem;
  color: #9ca3af;
}
.doc-empty-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e8f4fd, #cce5f6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin: 0 auto 1rem;
  color: #0077B5;
}
.doc-empty h5 {
  font-weight: 700;
  color: #1e293b;
}
.doc-empty p {
  color: #6c757d;
  max-width: 400px;
  margin: 0 auto 1.25rem;
}
.doc-upload-action {
  display: inline-flex;
  align-items: center;
  padding: .5rem 1.2rem;
  border-radius: 10px;
  font-weight: 600;
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: #fff;
  text-decoration: none;
  transition: all .2s;
}
.doc-upload-action:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 119, 181, .3);
  color: #fff;
}

/* ── Pagination ── */
.doc-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 1.5rem;
  padding: .8rem 1.2rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}
.doc-page-info {
  font-size: .82rem;
  color: #6b7280;
}
.doc-pagination .pagination .page-link {
  border-radius: 8px !important;
  margin: 0 2px;
  border: none;
  color: #374151;
  font-weight: 600;
  font-size: .82rem;
}
.doc-pagination .pagination .page-item.active .page-link {
  background: linear-gradient(135deg, #0077B5, #005a87);
  border-color: transparent;
  color: #fff;
}

/* ── Detail modal ── */
.ddm-overlay {
  position: fixed; inset: 0; z-index: 1060;
  background: rgba(0,0,0,.55);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: ddmFadeIn .2s;
}
@keyframes ddmFadeIn { from { opacity: 0; } to { opacity: 1; } }
.ddm-dialog {
  background: #fff; border-radius: 18px; width: 100%; max-width: 620px;
  max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: 0 24px 48px rgba(0,0,0,.18);
  animation: ddmSlideUp .25s ease-out;
  overflow: hidden;
}
@keyframes ddmSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.ddm-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.1rem 1.5rem;
  background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
  color: #fff;
}
.ddm-title { margin: 0; font-size: 1.05rem; font-weight: 700; }
.ddm-close {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: rgba(255,255,255,.15); color: #fff; font-size: .9rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .2s;
}
.ddm-close:hover { background: rgba(255,255,255,.3); }
.ddm-loading { padding: 3rem; text-align: center; }
.ddm-body { padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; }

/* Doc header in modal */
.ddm-doc-header {
  display: flex; align-items: flex-start; gap: .9rem; margin-bottom: 1.1rem;
  padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9;
}
.ddm-doc-icon {
  width: 52px; height: 52px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; flex-shrink: 0;
}
.ddm-doc-icon.doc-ic-pdf { background: #fee2e2; color: #dc2626; }
.ddm-doc-icon.doc-ic-img { background: #e0f2fe; color: #0284c7; }
.ddm-doc-icon.doc-ic-doc { background: #dbeafe; color: #2563eb; }
.ddm-doc-icon.doc-ic-xls { background: #dcfce7; color: #16a34a; }
.ddm-doc-icon.doc-ic-other { background: #f1f5f9; color: #64748b; }
.ddm-doc-title-wrap { flex: 1; min-width: 0; }
.ddm-doc-title { font-size: 1rem; font-weight: 700; color: #1e293b; line-height: 1.3; margin-bottom: .4rem; }
.ddm-doc-badges { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.ddm-badge-cat {
  display: inline-flex; align-items: center; padding: .2rem .6rem;
  border-radius: 6px; font-size: .72rem; font-weight: 600;
}
.ddm-badge-cat.identite { background: #e8f4fd; color: #0077B5; }
.ddm-badge-cat.parcours { background: #fff3e0; color: #e67e22; }
.ddm-badge-cat.carriere { background: #ede9fe; color: #7c3aed; }
.ddm-badge-cat.mission { background: #e6f7ef; color: #28a745; }
.ddm-badge-ext {
  display: inline-flex; padding: .2rem .5rem; border-radius: 6px;
  font-size: .68rem; font-weight: 600; background: #f3f4f6; color: #6b7280;
}

/* Info grid */
.ddm-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; margin-bottom: 1.1rem; }
.ddm-info-card {
  display: flex; align-items: flex-start; gap: .65rem;
  padding: .75rem .85rem; background: #f8fafc; border-radius: 10px; border: 1px solid #f1f5f9;
}
.ddm-info-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: .78rem; flex-shrink: 0;
}
.ddm-ic-blue { background: #dbeafe; color: #2563eb; }
.ddm-ic-orange { background: #ffedd5; color: #ea580c; }
.ddm-ic-purple { background: #ede9fe; color: #7c3aed; }
.ddm-ic-green { background: #dcfce7; color: #16a34a; }
.ddm-info-label { font-size: .65rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.ddm-info-value { font-size: .82rem; font-weight: 700; color: #1e293b; margin-top: .05rem; }
.ddm-status-ok {
  display: inline-flex; padding: .15rem .5rem; border-radius: 6px;
  font-size: .72rem; font-weight: 600; background: #dcfce7; color: #166534;
}
.ddm-status-other {
  display: inline-flex; padding: .15rem .5rem; border-radius: 6px;
  font-size: .72rem; font-weight: 600; background: #f3f4f6; color: #475569;
}

/* Sections */
.ddm-section { margin-bottom: 1rem; }
.ddm-section-title { font-size: .82rem; font-weight: 700; color: #334155; margin-bottom: .5rem; }
.ddm-desc {
  font-size: .85rem; line-height: 1.7; color: #334155; white-space: pre-wrap;
  margin: 0; background: #f8fafc; border-radius: 10px; padding: .85rem 1rem;
}

/* Preview */
.ddm-preview { text-align: center; background: #f8fafc; border-radius: 10px; padding: 1rem; }
.ddm-preview-img { max-width: 100%; max-height: 300px; border-radius: 8px; }

/* File display */
.ddm-file {
  display: flex; align-items: center; gap: .75rem;
  padding: .75rem 1rem; background: #f8fafc; border-radius: 10px; border: 1px solid #f1f5f9;
}
.ddm-file-icon {
  width: 36px; height: 36px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0;
}
.ddm-file-name { flex: 1; font-weight: 600; font-size: .82rem; color: #1e293b; word-break: break-all; }

/* Footer */
.ddm-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: .85rem 1.5rem; border-top: 1px solid #f1f5f9;
  background: #fafafa; flex-wrap: wrap; gap: .5rem;
}
.ddm-btn {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .45rem 1rem; border-radius: 10px; font-size: .8rem; font-weight: 600;
  border: none; cursor: pointer; text-decoration: none; transition: all .2s;
}
.ddm-btn-primary { background: linear-gradient(135deg, #0077B5, #005a87); color: #fff; }
.ddm-btn-primary:hover { box-shadow: 0 4px 12px rgba(0,119,181,.3); }
.ddm-btn-danger { background: #ef4444; color: #fff; }
.ddm-btn-danger:hover { background: #dc2626; }

/* ── Mobile responsive ── */
@media (max-width: 576px) {
  .doc-hero {
    padding: 1.3rem 1.1rem;
    border-radius: 14px;
  }
  .doc-hero h2 {
    font-size: 1.1rem;
  }
  .doc-hero p {
    font-size: .78rem;
  }
  .doc-hero::before {
    width: 160px;
    height: 160px;
    right: -12%;
  }
  .doc-hero-content {
    flex-direction: column;
  }
  .doc-upload-btn {
    width: 100%;
    justify-content: center;
    padding: .5rem 1rem;
    font-size: .85rem;
  }
  .doc-hero-stats {
    flex-wrap: wrap;
    gap: 1rem;
  }
  .doc-hero-stat-val {
    font-size: 1.2rem;
  }
  .doc-cat-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .doc-cat-card,
  .doc-cat-all {
    padding: .7rem .8rem;
  }
  .doc-cat-icon {
    width: 34px;
    height: 34px;
    font-size: .85rem;
  }
  .doc-cat-name {
    font-size: .75rem;
  }
  .doc-search-bar {
    border-radius: 12px;
    padding: .4rem .5rem;
  }
  .doc-search-btn {
    padding: .4rem .8rem;
    font-size: .78rem;
  }
  .doc-section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: .5rem;
  }
  .doc-grid {
    grid-template-columns: 1fr;
  }
  .doc-card-top {
    padding: 1rem 1rem .5rem;
  }
  .doc-card-icon {
    width: 38px;
    height: 38px;
    font-size: .95rem;
  }
  .doc-card-title {
    font-size: .85rem;
  }
  .doc-card-meta {
    padding: .4rem 1rem;
  }
  .doc-card-footer {
    padding: .6rem 1rem;
    flex-direction: column;
    align-items: flex-start;
    gap: .5rem;
  }
  .doc-card-actions {
    width: 100%;
  }
  .doc-action-btn {
    flex: 1;
    justify-content: center;
    font-size: .68rem;
    padding: .3rem .4rem;
  }
  .doc-pagination {
    flex-direction: column;
    gap: .6rem;
    text-align: center;
    padding: .8rem;
  }
  .doc-empty {
    padding: 2rem 1rem;
  }
  .doc-empty-icon {
    width: 56px;
    height: 56px;
    font-size: 1.3rem;
  }
  /* Modal responsive */
  .ddm-overlay { padding: .5rem; }
  .ddm-dialog { max-height: 95vh; border-radius: 14px; }
  .ddm-header { padding: .85rem 1rem; }
  .ddm-body { padding: 1rem; }
  .ddm-info-grid { grid-template-columns: 1fr; }
  .ddm-footer { padding: .75rem 1rem; flex-direction: column; align-items: stretch; }
  .ddm-btn { justify-content: center; width: 100%; }
}

/* ── Upload modal (dum-*) ── */
.dum-overlay {
  position: fixed; inset: 0; z-index: 1060;
  background: rgba(0,0,0,.55);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: dumFadeIn .2s;
}
@keyframes dumFadeIn { from { opacity: 0; } to { opacity: 1; } }
.dum-dialog {
  background: #fff; border-radius: 18px; width: 100%; max-width: 580px;
  max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: 0 24px 48px rgba(0,0,0,.18);
  animation: dumSlideUp .25s ease-out;
  overflow: hidden;
}
@keyframes dumSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.dum-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.1rem 1.5rem;
  background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
  color: #fff;
}
.dum-title { margin: 0; font-size: 1.05rem; font-weight: 700; }
.dum-close {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: rgba(255,255,255,.15); color: #fff; font-size: .9rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .2s;
}
.dum-close:hover { background: rgba(255,255,255,.3); }
.dum-body { padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; }

.dum-label { display: block; font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
.dum-input {
  width: 100%; border-radius: 10px; border: 1px solid #e2e8f0; padding: .5rem .75rem;
  font-size: .85rem; transition: border-color .2s, box-shadow .2s;
}
.dum-input:focus { outline: none; border-color: #0077B5; box-shadow: 0 0 0 3px rgba(0,119,181,.1); }
.dum-input.is-invalid { border-color: #ef4444; }
.dum-textarea { resize: vertical; min-height: 60px; }
.dum-error { font-size: .75rem; color: #ef4444; margin-top: .2rem; }

/* Upload zone */
.dum-upload-zone {
  border: 2px dashed #bde0f5; border-radius: 14px; padding: 1.5rem; text-align: center;
  cursor: pointer; transition: all .2s; background: #f8fbfe;
}
.dum-upload-zone:hover, .dum-upload-zone.dragging { border-color: #0077B5; background: #e8f4fd; }
.dum-upload-icon { font-size: 2rem; color: #0077B5; margin-bottom: .4rem; display: block; }

/* File preview */
.dum-file-preview {
  display: flex; align-items: center; gap: .7rem; padding: .8rem 1rem;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
}
.dum-file-icon-box {
  width: 36px; height: 36px; border-radius: 10px; background: #e8f4fd; color: #0077B5;
  display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0;
}
.dum-file-info { flex: 1; min-width: 0; }
.dum-file-name { font-weight: 600; font-size: .82rem; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dum-file-size { font-size: .7rem; color: #94a3b8; }
.dum-file-remove {
  width: 28px; height: 28px; border-radius: 6px; background: #fef2f2; color: #ef4444;
  border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: .7rem; padding: 0;
}
.dum-file-remove:hover { background: #fee2e2; }

/* Category grid */
.dum-cat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; }
.dum-cat-card {
  display: flex; flex-direction: column; align-items: center; gap: .3rem;
  padding: .7rem .3rem; border-radius: 10px; border: 2px solid #e2e8f0;
  cursor: pointer; transition: all .2s; background: #fff;
}
.dum-cat-card:hover { border-color: #94a3b8; transform: translateY(-1px); }
.dum-cat-card.active { border-color: #0077B5; background: #e8f4fd; }
.dum-cat-icon { font-size: 1.1rem; color: #94a3b8; transition: color .2s; }
.dum-cat-card.active .dum-cat-icon { color: #0077B5; }
.dum-cat-label { font-size: .68rem; font-weight: 600; color: #64748b; }
.dum-cat-card.active .dum-cat-label { color: #0077B5; }

/* Footer */
.dum-footer {
  display: flex; align-items: center; justify-content: flex-end; gap: .6rem;
  padding-top: 1rem; margin-top: .5rem; border-top: 1px solid #f1f5f9;
}
.dum-btn {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .5rem 1.1rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: none; cursor: pointer; transition: all .2s;
}
.dum-btn-cancel { background: #fff; color: #64748b; border: 1px solid #e2e8f0; }
.dum-btn-cancel:hover { background: #f8fafc; color: #334155; }
.dum-btn-submit { background: linear-gradient(135deg, #0077B5, #005a87); color: #fff; }
.dum-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,119,181,.3); }
.dum-btn-submit:disabled { opacity: .6; transform: none; }

/* Upload modal mobile */
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
