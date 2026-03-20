<template>
  <div class="py-4">
    <!-- Hero Header -->
    <div class="docs-hero mb-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h2 class="fw-bold mb-1">
            <i class="fas fa-folder-open me-2"></i>Gestion Electronique de Documents
          </h2>
          <p class="mb-0 opacity-75">Organisez, consultez et gerez vos documents en toute simplicite</p>
        </div>
        <router-link :to="{ name: 'documents.create' }" class="btn btn-upload">
          <i class="fas fa-cloud-upload-alt me-2"></i>Uploader un document
        </router-link>
      </div>
    </div>

    <!-- Stats Pills -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md">
        <div class="stat-pill">
          <div class="stat-pill-icon" style="background:#e6f7ef;color:#28a745;">
            <i class="fas fa-file-alt"></i>
          </div>
          <div>
            <div class="stat-pill-val">{{ stats.total }}</div>
            <div class="stat-pill-label">Total</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="stat-pill">
          <div class="stat-pill-icon" style="background:#e8f4fd;color:#0077B5;">
            <i class="fas fa-id-card"></i>
          </div>
          <div>
            <div class="stat-pill-val">{{ stats.identite }}</div>
            <div class="stat-pill-label">Identite</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="stat-pill">
          <div class="stat-pill-icon" style="background:#fff3e0;color:#e67e22;">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <div>
            <div class="stat-pill-val">{{ stats.parcours }}</div>
            <div class="stat-pill-label">Parcours</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="stat-pill">
          <div class="stat-pill-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-briefcase"></i>
          </div>
          <div>
            <div class="stat-pill-val">{{ stats.carriere }}</div>
            <div class="stat-pill-label">Carriere</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="stat-pill">
          <div class="stat-pill-icon" style="background:#e6f7ef;color:#28a745;">
            <i class="fas fa-plane"></i>
          </div>
          <div>
            <div class="stat-pill-val">{{ stats.mission }}</div>
            <div class="stat-pill-label">Mission</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-card mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-semibold">
            <i class="fas fa-filter me-1" style="color:#0077B5;"></i>Categorie
          </label>
          <select v-model="filters.categorie" class="form-select" @change="fetchDocuments(1)">
            <option value="">Toutes les categories</option>
            <option value="identite">Identite</option>
            <option value="parcours">Parcours</option>
            <option value="carriere">Carriere</option>
            <option value="mission">Mission</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label fw-semibold">
            <i class="fas fa-search me-1" style="color:#0077B5;"></i>Recherche
          </label>
          <input
            v-model="filters.search"
            type="text"
            class="form-control"
            placeholder="Rechercher un document..."
            @keyup.enter="fetchDocuments(1)"
          />
        </div>
        <div class="col-md-3">
          <div class="d-flex gap-2">
            <button class="btn btn-primary-custom flex-grow-1" @click="fetchDocuments(1)">
              <i class="fas fa-search me-1"></i>Filtrer
            </button>
            <button class="btn btn-reset" @click="resetFilters">
              <i class="fas fa-redo"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color:#0077B5;" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="text-muted mt-2">Chargement des documents...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="documents.length === 0" class="empty-state">
      <div class="empty-state-icon">
        <i class="fas fa-folder-open"></i>
      </div>
      <h5>Aucun document trouve</h5>
      <p>Vous n'avez pas encore de documents dans votre dossier. Commencez par uploader votre premier document.</p>
      <router-link :to="{ name: 'documents.create' }" class="btn btn-primary-custom">
        <i class="fas fa-cloud-upload-alt me-2"></i>Uploader un document
      </router-link>
    </div>

    <!-- Documents Grid -->
    <div v-else class="row g-3">
      <div v-for="doc in documents" :key="doc.id" class="col-md-6 col-lg-4">
        <div class="doc-card">
          <div class="doc-card-top">
            <div :class="['doc-icon-wrap', getFileIconClass(doc)]">
              <i :class="getFileIcon(doc)"></i>
            </div>
            <div class="doc-card-info">
              <h6>{{ getDocName(doc) }}</h6>
              <div v-if="getDocDescription(doc)" class="doc-card-desc">
                {{ getDocDescription(doc) }}
              </div>
              <div class="mb-2">
                <span :class="['doc-badge', doc.type]">
                  <i :class="getCategoryIcon(doc.type)"></i>
                  {{ getCategoryLabel(doc.type) }}
                </span>
              </div>
              <div class="doc-meta">
                <span><i class="fas fa-calendar-alt"></i> {{ formatDate(doc.created_at) }}</span>
              </div>
            </div>
          </div>
          <div class="doc-card-actions">
            <router-link :to="{ name: 'documents.show', params: { id: doc.id } }" class="doc-action view">
              <i class="fas fa-eye"></i> Voir
            </router-link>
            <button class="doc-action download" @click="handleDownload(doc)">
              <i class="fas fa-download"></i> Telecharger
            </button>
            <button class="doc-action delete" @click="confirmDelete(doc)">
              <i class="fas fa-trash-alt"></i> Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meta.total > 0 && meta.last_page > 1" class="pagination-wrap mt-4">
      <div class="page-info">
        Affichage <strong>{{ meta.from }}</strong> a <strong>{{ meta.to }}</strong>
        sur <strong>{{ meta.total }}</strong> documents
      </div>
      <nav>
        <ul class="pagination mb-0">
          <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
            <button class="page-link" @click="fetchDocuments(meta.current_page - 1)">
              <i class="fas fa-chevron-left"></i>
            </button>
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
            <button class="page-link" @click="fetchDocuments(meta.current_page + 1)">
              <i class="fas fa-chevron-right"></i>
            </button>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer le document"
      message="Etes-vous sur de vouloir supprimer ce document ? Cette action est irreversible."
      :loading="deleteLoading"
      @confirm="doDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { list, download, remove } from '@/api/documents'
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
  if (ext === 'pdf') return 'pdf'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'image'
  if (['doc', 'docx'].includes(ext)) return 'word'
  if (['xls', 'xlsx'].includes(ext)) return 'excel'
  return 'other'
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

onMounted(() => {
  fetchDocuments()
})
</script>

<style scoped>
/* Hero Header */
.docs-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005885 50%, #004165 100%);
  border-radius: 16px;
  padding: 2.5rem 2rem;
  position: relative;
  overflow: hidden;
  color: #fff;
}
.docs-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.docs-hero > * {
  position: relative;
  z-index: 2;
}
.btn-upload {
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.4);
  color: #fff;
  font-weight: 700;
  padding: 0.6rem 1.5rem;
  border-radius: 12px;
  transition: all 0.2s;
  backdrop-filter: blur(4px);
  text-decoration: none;
}
.btn-upload:hover {
  background: #fff;
  color: #0077B5;
  border-color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

/* Stat Pills */
.stat-pill {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 0.65rem 1.1rem;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  height: 100%;
}
.stat-pill-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
}
.stat-pill-val {
  font-weight: 800;
  font-size: 1.2rem;
  color: #1a1a2e;
}
.stat-pill-label {
  font-size: 0.75rem;
  color: #6c757d;
}

/* Filter Card */
.filter-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  border: 1px solid #e9ecef;
  padding: 1.5rem;
}
.filter-card .form-control,
.filter-card .form-select {
  border-radius: 10px;
  border: 2px solid #e9ecef;
  padding: 0.6rem 1rem;
  transition: border-color 0.2s;
}
.filter-card .form-control:focus,
.filter-card .form-select:focus {
  border-color: #0077B5;
  box-shadow: 0 0 0 0.2rem rgba(0, 119, 181, 0.15);
}
.btn-primary-custom {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none;
  color: #fff;
  font-weight: 600;
  border-radius: 10px;
  padding: 0.6rem 1.5rem;
  transition: all 0.2s;
}
.btn-primary-custom:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 119, 181, 0.3);
  color: #fff;
}
.btn-reset {
  background: #f5f5f5;
  border: 2px solid #e9ecef;
  color: #666;
  border-radius: 10px;
  padding: 0.6rem 0.85rem;
  transition: all 0.2s;
}
.btn-reset:hover {
  background: #e9ecef;
  color: #333;
}

/* Document Card */
.doc-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.doc-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}
.doc-card-top {
  padding: 1.5rem 1.25rem 1rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  flex: 1;
}
.doc-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1.4rem;
}
.doc-icon-wrap.pdf { background: #fdeaec; color: #dc3545; }
.doc-icon-wrap.image { background: #e0f4ff; color: #0dcaf0; }
.doc-icon-wrap.word { background: #e3effe; color: #0d6efd; }
.doc-icon-wrap.excel { background: #e6f7ef; color: #28a745; }
.doc-icon-wrap.other { background: #f5f5f5; color: #6c757d; }
.doc-card-info h6 {
  font-weight: 700;
  color: #1a1a2e;
  font-size: 0.95rem;
  margin-bottom: 0.3rem;
  line-height: 1.3;
}
.doc-card-desc {
  color: #6c757d;
  font-size: 0.82rem;
  line-height: 1.4;
  margin-bottom: 0.4rem;
}
.doc-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.78rem;
  color: #999;
}
.doc-meta span {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.doc-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.6rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.75rem;
}
.doc-badge.identite { background: #e8f4fd; color: #0077B5; }
.doc-badge.parcours { background: #fff3e0; color: #e67e22; }
.doc-badge.carriere { background: #ede9fe; color: #7c3aed; }
.doc-badge.mission { background: #e6f7ef; color: #28a745; }

/* Card Footer Actions */
.doc-card-actions {
  display: flex;
  border-top: 1px solid #f0f2f5;
}
.doc-card-actions .doc-action {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.7rem 0.5rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #6c757d;
  text-decoration: none;
  border: none;
  background: none;
  transition: all 0.15s;
  cursor: pointer;
}
.doc-card-actions .doc-action + .doc-action {
  border-left: 1px solid #f0f2f5;
}
.doc-card-actions .doc-action:hover { background: #f8f9fc; }
.doc-card-actions .doc-action.view:hover { color: #0077B5; }
.doc-card-actions .doc-action.download:hover { color: #28a745; }
.doc-card-actions .doc-action.delete:hover { color: #dc3545; background: #fef5f5; }

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: #fff;
  border-radius: 14px;
  border: 2px dashed #dee2e6;
}
.empty-state-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e8f4fd, #cce5f6);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  font-size: 2rem;
  color: #0077B5;
}
.empty-state h5 { font-weight: 700; color: #1a1a2e; }
.empty-state p { color: #6c757d; max-width: 400px; margin: 0 auto 1.25rem; }

/* Pagination */
.pagination-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}
.page-info {
  color: #6c757d;
  font-size: 0.88rem;
}
.pagination .page-link {
  border-radius: 8px !important;
  margin: 0 2px;
  border: none;
  color: #333;
  font-weight: 600;
}
.pagination .page-item.active .page-link {
  background: linear-gradient(135deg, #0077B5, #005885);
  border-color: transparent;
  color: #fff;
}
</style>
