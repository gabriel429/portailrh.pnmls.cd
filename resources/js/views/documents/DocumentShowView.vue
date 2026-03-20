<template>
  <div class="py-4">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border" style="color:#0077B5;" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
          <p class="text-muted mt-2">Chargement du document...</p>
        </div>

        <template v-else-if="document">
          <!-- Back + Title -->
          <div class="d-flex align-items-center mb-4">
            <router-link :to="{ name: 'documents.index' }" class="btn btn-back-circle me-3">
              <i class="fas fa-arrow-left"></i>
            </router-link>
            <h3 class="mb-0 fw-bold" style="color:#1a1a2e;">Details du document</h3>
          </div>

          <!-- Main Card -->
          <div class="detail-card mb-4">
            <div class="detail-card-header">
              <div :class="['doc-icon-lg', fileIconClass]">
                <i :class="fileIcon"></i>
              </div>
              <div>
                <h4 class="fw-bold mb-1" style="color:#1a1a2e;">{{ docName }}</h4>
                <span :class="['doc-badge', document.type]">
                  <i :class="categoryIcon"></i>
                  {{ categoryLabel }}
                </span>
              </div>
            </div>

            <div class="detail-card-body">
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="detail-field">
                    <label><i class="fas fa-tag me-2" style="color:#0077B5;"></i>Type / Categorie</label>
                    <p>{{ categoryLabel }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="detail-field">
                    <label><i class="fas fa-check-circle me-2" style="color:#28a745;"></i>Statut</label>
                    <p>
                      <span v-if="document.statut === 'valide'" class="badge bg-success">Valide</span>
                      <span v-else class="badge bg-secondary">{{ document.statut }}</span>
                    </p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="detail-field">
                    <label><i class="fas fa-user me-2" style="color:#0077B5;"></i>Agent</label>
                    <p>{{ document.agent?.prenom }} {{ document.agent?.nom }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="detail-field">
                    <label><i class="fas fa-calendar-alt me-2" style="color:#e67e22;"></i>Date d'ajout</label>
                    <p>{{ formatDate(document.created_at) }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="detail-field">
                    <label><i class="fas fa-file me-2" style="color:#6c757d;"></i>Format</label>
                    <p>{{ fileMeta.extension ? fileMeta.extension.toUpperCase() : '-' }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="detail-field">
                    <label><i class="fas fa-weight me-2" style="color:#7c3aed;"></i>Taille</label>
                    <p>{{ formatFileSize(fileMeta.size) }}</p>
                  </div>
                </div>
                <div class="col-12">
                  <div class="detail-field">
                    <label><i class="fas fa-align-left me-2" style="color:#0077B5;"></i>Description</label>
                    <p>{{ docDescription || 'Aucune description' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- File Preview -->
            <div v-if="document.fichier" class="file-preview-section">
              <template v-if="isImage">
                <img :src="'/' + document.fichier" alt="Document" class="img-fluid rounded" style="max-height: 400px;" />
              </template>
              <template v-else-if="fileMeta.extension === 'pdf'">
                <div class="text-center py-4">
                  <i class="fas fa-file-pdf fa-4x mb-3" style="color:#dc3545;"></i>
                  <p class="text-muted">Fichier PDF</p>
                </div>
              </template>
              <template v-else>
                <div class="text-center py-4">
                  <i class="fas fa-file fa-4x mb-3" style="color:#6c757d;"></i>
                  <p class="text-muted">{{ fileMeta.extension ? fileMeta.extension.toUpperCase() : 'Fichier' }}</p>
                </div>
              </template>
            </div>

            <!-- Actions -->
            <div class="detail-card-footer">
              <button class="btn btn-download" @click="handleDownload">
                <i class="fas fa-download me-2"></i>Telecharger
              </button>
              <router-link :to="{ name: 'documents.index' }" class="btn btn-back-list">
                <i class="fas fa-list me-2"></i>Retour a la liste
              </router-link>
              <button class="btn btn-delete-doc ms-auto" @click="showDeleteModal = true">
                <i class="fas fa-trash-alt me-2"></i>Supprimer
              </button>
            </div>
          </div>
        </template>

        <!-- Not Found -->
        <div v-else class="text-center py-5">
          <i class="fas fa-file-excel fa-4x mb-3" style="color:#dc3545;"></i>
          <h5 class="fw-bold">Document introuvable</h5>
          <p class="text-muted">Le document demande n'existe pas ou vous n'y avez pas acces.</p>
          <router-link :to="{ name: 'documents.index' }" class="btn btn-primary-custom">
            <i class="fas fa-arrow-left me-2"></i>Retour aux documents
          </router-link>
        </div>

      </div>
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
import { useRoute, useRouter } from 'vue-router'
import { get, download, remove } from '@/api/documents'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const document = ref(null)
const fileMeta = ref({ size: 0, extension: '', exists: false })
const showDeleteModal = ref(false)
const deleteLoading = ref(false)

const docName = computed(() => {
  if (!document.value?.description) return 'Document sans nom'
  const parts = document.value.description.split(' | ')
  return parts[0] || 'Document sans nom'
})

const docDescription = computed(() => {
  if (!document.value?.description) return ''
  const parts = document.value.description.split(' | ')
  return parts.length > 1 ? parts.slice(1).join(' | ') : ''
})

const categoryIcon = computed(() => {
  const icons = {
    identite: 'fas fa-id-card',
    parcours: 'fas fa-graduation-cap',
    carriere: 'fas fa-briefcase',
    mission: 'fas fa-plane',
  }
  return icons[document.value?.type] || 'fas fa-file'
})

const categoryLabel = computed(() => {
  const labels = {
    identite: 'Identite',
    parcours: 'Parcours',
    carriere: 'Carriere',
    mission: 'Mission',
  }
  return labels[document.value?.type] || document.value?.type || '-'
})

const fileIconClass = computed(() => {
  const ext = fileMeta.value.extension
  if (ext === 'pdf') return 'pdf'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'image'
  if (['doc', 'docx'].includes(ext)) return 'word'
  return 'other'
})

const fileIcon = computed(() => {
  const ext = fileMeta.value.extension
  if (ext === 'pdf') return 'fas fa-file-pdf'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'fas fa-file-image'
  if (['doc', 'docx'].includes(ext)) return 'fas fa-file-word'
  return 'fas fa-file'
})

const isImage = computed(() => {
  return ['jpg', 'jpeg', 'png', 'gif'].includes(fileMeta.value.extension)
})

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatFileSize(bytes) {
  if (!bytes || bytes === 0) return '-'
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB'
  return (bytes / 1024).toFixed(1) + ' KB'
}

async function fetchDocument() {
  loading.value = true
  try {
    const { data } = await get(route.params.id)
    document.value = data.document
    fileMeta.value = data.file_meta || { size: 0, extension: '', exists: false }
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast('Vous n\'avez pas acces a ce document.', 'danger')
    } else {
      ui.addToast('Erreur lors du chargement du document.', 'danger')
    }
    document.value = null
  } finally {
    loading.value = false
  }
}

async function handleDownload() {
  try {
    const response = await download(document.value.id)
    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const a = window.document.createElement('a')
    a.href = url
    const filename = document.value.fichier ? document.value.fichier.split('/').pop() : `document_${document.value.id}`
    a.download = filename
    window.document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    window.document.body.removeChild(a)
  } catch (err) {
    ui.addToast('Erreur lors du telechargement.', 'danger')
  }
}

async function doDelete() {
  deleteLoading.value = true
  try {
    await remove(document.value.id)
    ui.addToast('Document supprime avec succes.', 'success')
    showDeleteModal.value = false
    router.push({ name: 'documents.index' })
  } catch (err) {
    ui.addToast('Erreur lors de la suppression.', 'danger')
  } finally {
    deleteLoading.value = false
  }
}

onMounted(() => {
  fetchDocument()
})
</script>

<style scoped>
/* Back Button */
.btn-back-circle {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 2px solid #e9ecef;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  text-decoration: none;
  transition: all 0.2s;
  background: #fff;
}
.btn-back-circle:hover {
  border-color: #0077B5;
  color: #0077B5;
  background: #e8f4fd;
}

/* Detail Card */
.detail-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
  overflow: hidden;
}
.detail-card-header {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.75rem 1.5rem;
  border-bottom: 2px solid #f0f2f5;
  background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
}
.doc-icon-lg {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  flex-shrink: 0;
}
.doc-icon-lg.pdf { background: #fdeaec; color: #dc3545; }
.doc-icon-lg.image { background: #e0f4ff; color: #0dcaf0; }
.doc-icon-lg.word { background: #e3effe; color: #0d6efd; }
.doc-icon-lg.other { background: #f5f5f5; color: #6c757d; }

.doc-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.25rem 0.7rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.8rem;
}
.doc-badge.identite { background: #e8f4fd; color: #0077B5; }
.doc-badge.parcours { background: #fff3e0; color: #e67e22; }
.doc-badge.carriere { background: #ede9fe; color: #7c3aed; }
.doc-badge.mission { background: #e6f7ef; color: #28a745; }

.detail-card-body {
  padding: 1.5rem;
}
.detail-field label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.35rem;
}
.detail-field p {
  font-size: 0.95rem;
  color: #1a1a2e;
  margin-bottom: 0;
  font-weight: 500;
}

/* File Preview */
.file-preview-section {
  padding: 1.5rem;
  border-top: 2px solid #f0f2f5;
  background: #f8f9fc;
  text-align: center;
}

/* Footer Actions */
.detail-card-footer {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  border-top: 2px solid #f0f2f5;
  flex-wrap: wrap;
}
.btn-download {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none;
  color: #fff;
  font-weight: 600;
  padding: 0.6rem 1.25rem;
  border-radius: 10px;
  transition: all 0.2s;
}
.btn-download:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 119, 181, 0.3);
  color: #fff;
}
.btn-back-list {
  background: #f5f5f5;
  border: 2px solid #e9ecef;
  color: #666;
  font-weight: 600;
  padding: 0.6rem 1.25rem;
  border-radius: 10px;
  transition: all 0.2s;
  text-decoration: none;
}
.btn-back-list:hover {
  background: #e9ecef;
  color: #333;
}
.btn-delete-doc {
  background: #fef5f5;
  border: 2px solid #fdeaec;
  color: #dc3545;
  font-weight: 600;
  padding: 0.6rem 1.25rem;
  border-radius: 10px;
  transition: all 0.2s;
}
.btn-delete-doc:hover {
  background: #fdeaec;
  border-color: #dc3545;
  color: #dc3545;
}
.btn-primary-custom {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none;
  color: #fff;
  font-weight: 600;
  border-radius: 10px;
  padding: 0.6rem 1.5rem;
  text-decoration: none;
}
.btn-primary-custom:hover {
  color: #fff;
}
</style>
