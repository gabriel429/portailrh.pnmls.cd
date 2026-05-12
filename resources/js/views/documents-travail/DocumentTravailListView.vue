<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="dt-hero">
      <div class="dt-hero-main">
        <div>
          <h2><i class="fas fa-file-invoice me-2"></i>Documents de travail</h2>
          <p>Documents officiels mis à disposition par l'administration.</p>
        </div>
        <button v-if="canManageDocs" class="dt-add-btn" type="button" @click="startCreate">
          <i class="fas fa-plus"></i>
          Ajouter un document
        </button>
      </div>
      <div class="dt-hero-stats">
        <div>
          <div class="dt-hero-stat-val">{{ totalDocs }}</div>
          <div class="dt-hero-stat-lbl">Documents</div>
        </div>
        <div>
          <div class="dt-hero-stat-val">{{ categories.length }}</div>
          <div class="dt-hero-stat-lbl">Categories</div>
        </div>
      </div>
    </div>

    <!-- Category cards (always visible) -->
    <div class="dt-cat-grid">
      <button class="dt-cat-all" :class="{ active: !categorie }" @click="setCategorie('')">
        <div class="dt-cat-icon"><i class="fas fa-th-large"></i></div>
        <div class="dt-cat-info">
          <div class="dt-cat-name">Toutes</div>
          <div class="dt-cat-count">{{ totalDocs }} document{{ totalDocs > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button
        v-for="cat in categories"
        :key="cat.id"
        class="dt-cat-card"
        :class="{ active: categorie === cat.nom }"
        @click="setCategorie(cat.nom)"
      >
        <div class="dt-cat-icon"><i class="fas" :class="cat.icone || 'fa-folder'"></i></div>
        <div class="dt-cat-info">
          <div class="dt-cat-name">{{ cat.nom }}</div>
          <div class="dt-cat-count">{{ categoryCounts[cat.nom] || 0 }} document{{ (categoryCounts[cat.nom] || 0) > 1 ? 's' : '' }}</div>
        </div>
      </button>
    </div>

    <!-- Section header when filtered -->
    <div v-if="categorie" class="dt-section-header">
      <div class="dt-section-title">
        <i class="fas fa-folder-open" style="color:#ea580c;"></i>
        {{ categorie }}
        <span class="dt-section-badge">{{ meta.total }} document{{ meta.total > 1 ? 's' : '' }}</span>
      </div>
      <button class="dt-back-btn" @click="setCategorie('')">
        <i class="fas fa-arrow-left"></i> Toutes les catégories
      </button>
    </div>

    <!-- Loading spinner (initial load only) -->
    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement des documents..." />
    </div>

    <template v-else>
      <!-- Document grid -->
      <div v-if="documents.length" class="dt-grid" :class="{ 'dt-filtering': filtering }">
        <div v-for="doc in documents" :key="doc.id" class="dt-card">
          <div class="dt-card-top">
            <div class="dt-card-icon" :class="iconClass(doc.type_fichier)">
              <i class="fas" :class="iconName(doc.type_fichier)"></i>
            </div>
            <div class="dt-card-info">
              <div class="dt-card-title-row">
                <div class="dt-card-title">{{ doc.titre }}</div>
                <span v-if="canManageDocs" class="dt-status-badge" :class="doc.actif ? 'active' : 'inactive'">
                  {{ doc.actif ? 'Actif' : 'Inactif' }}
                </span>
              </div>
              <div v-if="doc.description" class="dt-card-desc">{{ doc.description }}</div>
            </div>
          </div>
          <div class="dt-card-meta">
            <span class="dt-meta-badge">{{ doc.categorie }}</span>
            <span class="dt-meta-badge">.{{ (doc.type_fichier || '').toUpperCase() }}</span>
            <span v-if="doc.taille" class="dt-meta-badge">{{ (doc.taille / 1024 / 1024).toFixed(1) }} Mo</span>
          </div>
          <div class="dt-card-footer">
            <span class="dt-card-date"><i class="fas fa-clock me-1"></i>{{ formatDate(doc.created_at) }}</span>
            <div class="dt-card-actions">
              <a :href="`/documents-travail/${doc.id}/view`" class="dt-card-btn dt-card-view" target="_blank" rel="noopener">
                <i class="fas fa-eye"></i> Voir
              </a>
              <a :href="`/documents-travail/${doc.id}/download`" class="dt-card-btn dt-card-dl">
                <i class="fas fa-download"></i> Télécharger
              </a>
              <button v-if="canManageDocs" type="button" class="dt-card-btn dt-card-edit" @click="startEdit(doc)">
                <i class="fas fa-edit"></i> Modifier
              </button>
              <button v-if="canManageDocs" type="button" class="dt-card-btn dt-card-delete" @click="deleteDocument(doc)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="d-flex justify-content-center mt-4">
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
              <button class="page-link" @click="loadDocuments(meta.current_page - 1)">&laquo;</button>
            </li>
            <li
              v-for="page in paginationPages"
              :key="page"
              class="page-item"
              :class="{ active: page === meta.current_page }"
            >
              <button class="page-link" @click="loadDocuments(page)">{{ page }}</button>
            </li>
            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
              <button class="page-link" @click="loadDocuments(meta.current_page + 1)">&raquo;</button>
            </li>
          </ul>
        </nav>
      </div>

      <!-- Empty -->
      <div v-if="!documents.length" class="dt-empty">
        <div class="dt-empty-icon"><i class="fas fa-folder-open"></i></div>
        <template v-if="categorie">
          <h5>Aucun document dans &laquo; {{ categorie }} &raquo;</h5>
          <p>Il n'y a pas encore de documents dans cette catégorie.</p>
          <button class="dt-back-btn mt-3" style="display:inline-flex;" @click="setCategorie('')">
            <i class="fas fa-arrow-left"></i> Voir toutes les categories
          </button>
        </template>
        <template v-else>
          <h5>Aucun document pour le moment</h5>
          <p>Les documents de travail seront publiés ici par l'administration.</p>
          <button v-if="canManageDocs" class="dt-add-empty-btn" type="button" @click="startCreate">
            <i class="fas fa-plus"></i> Ajouter un document
          </button>
        </template>
      </div>
    </template>

    <div v-if="showFormModal" class="dt-modal-overlay" @click.self="closeFormModal">
      <form class="dt-modal" @submit.prevent="submitDocument">
        <div class="dt-modal-head">
          <div>
            <h3>{{ editingDoc ? 'Modifier le document' : 'Ajouter un document' }}</h3>
            <p>{{ editingDoc ? 'Mettez a jour les informations du document.' : 'Publiez un document de travail depuis cet espace RH.' }}</p>
          </div>
          <button type="button" class="dt-modal-close" aria-label="Fermer" @click="closeFormModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div v-if="formError" class="dt-form-alert">{{ formError }}</div>
        <div v-if="Object.keys(validationErrors).length" class="dt-form-alert">
          <div v-for="(messages, field) in validationErrors" :key="field">{{ messages.join(', ') }}</div>
        </div>

        <label class="dt-field">
          <span>Titre <strong>*</strong></span>
          <input v-model="form.titre" type="text" required>
        </label>

        <label class="dt-field">
          <span>Description</span>
          <textarea v-model="form.description" rows="3"></textarea>
        </label>

        <label class="dt-field">
          <span>Categorie</span>
          <input v-model="form.categorie" type="text" list="dt-categories-list">
          <datalist id="dt-categories-list">
            <option v-for="cat in categories" :key="cat.id" :value="cat.nom"></option>
          </datalist>
        </label>

        <label class="dt-field">
          <span>Fichier <strong v-if="!editingDoc">*</strong></span>
          <input ref="fileInput" type="file" :required="!editingDoc" @change="onFileChange">
          <small v-if="editingDoc && currentFileName">Actuel : {{ currentFileName }}</small>
          <small v-if="selectedFile">Nouveau : {{ selectedFile.name }}</small>
        </label>

        <label class="dt-check">
          <input v-model="form.actif" type="checkbox">
          <span>Document actif et visible aux agents</span>
        </label>

        <div class="dt-modal-actions">
          <button type="button" class="dt-secondary-btn" @click="closeFormModal">Annuler</button>
          <button type="submit" class="dt-save-btn" :disabled="submitting">
            <i v-if="submitting" class="fas fa-circle-notch fa-spin"></i>
            <i v-else class="fas fa-save"></i>
            {{ editingDoc ? 'Mettre a jour' : 'Publier' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()
const auth = useAuthStore()
const loading = ref(true)
const filtering = ref(false)
const initialLoadDone = ref(false)
const documents = ref([])
const categories = ref([])
const categoryCounts = ref({})
const totalDocs = ref(0)
const categorie = ref('')
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })
const showFormModal = ref(false)
const editingDoc = ref(null)
const selectedFile = ref(null)
const fileInput = ref(null)
const submitting = ref(false)
const formError = ref(null)
const validationErrors = ref({})
const form = ref({
  titre: '',
  description: '',
  categorie: '',
  actif: true,
})

const canManageDocs = computed(() => auth.canManageDocsTravail)
const currentFileName = computed(() => {
  const value = editingDoc.value?.fichier_nom || editingDoc.value?.fichier || ''
  return value.split(/[\\/]/).pop()
})

const paginationPages = computed(() => {
  const pages = []
  const total = meta.value.last_page
  const current = meta.value.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function loadDocuments(page = 1) {
  if (!initialLoadDone.value) {
    loading.value = true
  }
  filtering.value = true
  try {
    const params = { page }
    if (categorie.value) params.categorie = categorie.value
    const endpoint = canManageDocs.value ? '/documents-travail/manage' : '/documents-travail'
    const { data } = await client.get(endpoint, { params })
    documents.value = data.data
    categories.value = data.categories
    categoryCounts.value = data.categoryCounts
    totalDocs.value = data.totalDocs
    meta.value = data.meta
  } catch {
    ui.addToast('Erreur lors du chargement des documents.', 'danger')
  } finally {
    loading.value = false
    filtering.value = false
    initialLoadDone.value = true
  }
}

function setCategorie(cat) {
  categorie.value = cat
  loadDocuments(1)
}

function resetForm() {
  editingDoc.value = null
  selectedFile.value = null
  formError.value = null
  validationErrors.value = {}
  form.value = {
    titre: '',
    description: '',
    categorie: categorie.value || '',
    actif: true,
  }
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function startCreate() {
  if (!canManageDocs.value) return
  resetForm()
  showFormModal.value = true
}

function startEdit(doc) {
  if (!canManageDocs.value) return
  editingDoc.value = doc
  selectedFile.value = null
  formError.value = null
  validationErrors.value = {}
  form.value = {
    titre: doc.titre || '',
    description: doc.description || '',
    categorie: doc.categorie || '',
    actif: !!doc.actif,
  }
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  showFormModal.value = true
}

function closeFormModal() {
  showFormModal.value = false
  resetForm()
}

function onFileChange(event) {
  selectedFile.value = event.target.files[0] || null
}

async function submitDocument() {
  if (!canManageDocs.value) return

  submitting.value = true
  formError.value = null
  validationErrors.value = {}

  const payload = new FormData()
  payload.append('titre', form.value.titre)
  payload.append('description', form.value.description || '')
  payload.append('categorie', form.value.categorie || '')
  payload.append('actif', form.value.actif ? '1' : '0')
  if (selectedFile.value) {
    payload.append('fichier', selectedFile.value)
  }

  try {
    if (editingDoc.value) {
      payload.append('_method', 'PUT')
      await client.post(`/documents-travail/${editingDoc.value.id}`, payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      ui.addToast('Document mis a jour.', 'success')
    } else {
      await client.post('/documents-travail', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      ui.addToast('Document publie.', 'success')
    }

    showFormModal.value = false
    const page = editingDoc.value ? meta.value.current_page : 1
    resetForm()
    await loadDocuments(page)
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors || {}
      return
    }
    formError.value = error.response?.data?.message || "Erreur lors de l'enregistrement."
  } finally {
    submitting.value = false
  }
}

async function deleteDocument(doc) {
  if (!canManageDocs.value) return
  if (!window.confirm(`Supprimer le document "${doc.titre}" ?`)) return

  try {
    await client.delete(`/documents-travail/${doc.id}`)
    ui.addToast('Document supprime.', 'success')
    const nextPage = documents.value.length === 1 && meta.value.current_page > 1
      ? meta.value.current_page - 1
      : meta.value.current_page
    await loadDocuments(nextPage)
  } catch (error) {
    ui.addToast(error.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  }
}

function iconClass(ext) {
  const e = (ext || '').toLowerCase()
  const map = { pdf: 'dt-ic-pdf', doc: 'dt-ic-doc', docx: 'dt-ic-doc', xls: 'dt-ic-xls', xlsx: 'dt-ic-xls', ppt: 'dt-ic-ppt', pptx: 'dt-ic-ppt', jpg: 'dt-ic-img', jpeg: 'dt-ic-img', png: 'dt-ic-img' }
  return map[e] || 'dt-ic-other'
}

function iconName(ext) {
  const e = (ext || '').toLowerCase()
  const map = { pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word', xls: 'fa-file-excel', xlsx: 'fa-file-excel', ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint', jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image' }
  return map[e] || 'fa-file-alt'
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => loadDocuments())
</script>

<style scoped>
.dt-hero {
  background: linear-gradient(135deg, #ea580c 0%, #c2410c 50%, #9a3412 100%);
  border-radius: 18px; padding: 2rem 2.2rem; margin-bottom: 1.5rem; color: #fff;
  position: relative; overflow: hidden;
}
.dt-hero::before {
  content: ''; position: absolute; top: -40%; right: -8%;
  width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.07);
}
.dt-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .3rem; }
.dt-hero p { font-size: .85rem; opacity: .8; margin: 0; }
.dt-hero-main { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; position: relative; z-index: 1; }
.dt-hero-stats { display: flex; gap: 1.5rem; margin-top: 1rem; }
.dt-hero-stat-val { font-size: 1.5rem; font-weight: 800; }
.dt-hero-stat-lbl { font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .5px; }
.dt-add-btn, .dt-add-empty-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
  border: 1px solid rgba(255,255,255,.28); border-radius: 10px;
  background: rgba(255,255,255,.18); color: #fff; padding: .55rem .9rem;
  font-size: .82rem; font-weight: 800; cursor: pointer; white-space: nowrap;
}
.dt-add-btn:hover { background: rgba(255,255,255,.28); }
.dt-add-empty-btn {
  margin-top: .75rem; border-color: #bfdbfe; background: #eff6ff; color: #2563eb;
}
.dt-add-empty-btn:hover { background: #2563eb; color: #fff; }

.dt-cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .8rem; margin-bottom: 1.5rem; }
.dt-cat-card, .dt-cat-all {
  display: flex; align-items: center; gap: .7rem; padding: .9rem 1rem;
  background: #fff; border: 2px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; cursor: pointer;
}
.dt-cat-card:hover { border-color: #ea580c; color: #ea580c; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(234,88,12,.1); }
.dt-cat-card.active { background: linear-gradient(135deg, #ea580c, #c2410c) !important; border-color: rgba(234,88,12,.85) !important; color: #fff !important; box-shadow: 0 12px 28px rgba(234,88,12,.24), inset 0 1px 0 rgba(255,255,255,.24) !important; }
.dt-cat-all:hover { border-color: #0077B5; color: #0077B5; transform: translateY(-2px); }
.dt-cat-all.active { background: linear-gradient(135deg, #0077B5, #005a87) !important; border-color: rgba(0,119,181,.85) !important; color: #fff !important; box-shadow: 0 12px 28px rgba(0,119,181,.24), inset 0 1px 0 rgba(255,255,255,.24) !important; }
.dt-cat-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
  justify-content: center; font-size: 1rem; flex-shrink: 0; background: #fff7ed; color: #ea580c;
}
.dt-cat-card.active .dt-cat-icon, .dt-cat-all.active .dt-cat-icon { background: rgba(255,255,255,.2) !important; color: #fff !important; }
.dt-cat-all .dt-cat-icon { background: #e0f2fe; color: #0077B5; }
.dt-cat-info { flex: 1; min-width: 0; text-align: left; }
.dt-cat-name { font-size: .82rem; font-weight: 700; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dt-cat-count { font-size: .7rem; opacity: .6; }
.dt-cat-card.active .dt-cat-count, .dt-cat-all.active .dt-cat-count { opacity: .8; }

.dt-section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1rem; padding-bottom: .6rem; border-bottom: 2px solid #f3f4f6;
}
.dt-section-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: .5rem; }
.dt-section-badge { font-size: .72rem; font-weight: 700; padding: .2rem .6rem; border-radius: 20px; background: #fff7ed; color: #ea580c; }
.dt-back-btn {
  display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .8rem;
  border-radius: 8px; font-size: .78rem; font-weight: 600;
  background: #f3f4f6; color: #6b7280; text-decoration: none; border: none; cursor: pointer; transition: all .2s;
}
.dt-back-btn:hover { background: #e5e7eb; color: #374151; }

.dt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
.dt-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); overflow: hidden; transition: all .2s; display: flex; flex-direction: column;
}
.dt-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dt-card-top { display: flex; align-items: flex-start; gap: .8rem; padding: 1.2rem 1.2rem .6rem; }
.dt-card-icon {
  width: 44px; height: 44px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.dt-ic-pdf { background: #fee2e2; color: #dc2626; }
.dt-ic-doc { background: #dbeafe; color: #2563eb; }
.dt-ic-xls { background: #dcfce7; color: #16a34a; }
.dt-ic-ppt { background: #ffedd5; color: #ea580c; }
.dt-ic-img { background: #e0f2fe; color: #0284c7; }
.dt-ic-other { background: #f1f5f9; color: #64748b; }

.dt-card-info { flex: 1; min-width: 0; }
.dt-card-title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
.dt-card-title { font-weight: 700; font-size: .9rem; color: #1e293b; margin-bottom: .2rem; line-height: 1.3; }
.dt-card-desc { font-size: .78rem; color: #9ca3af; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.dt-status-badge {
  flex-shrink: 0; border-radius: 999px; padding: .16rem .45rem;
  font-size: .62rem; font-weight: 800;
}
.dt-status-badge.active { background: #dcfce7; color: #15803d; }
.dt-status-badge.inactive { background: #e2e8f0; color: #64748b; }
.dt-card-meta { padding: .5rem 1.2rem; display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
.dt-meta-badge { font-size: .68rem; font-weight: 600; padding: .2rem .55rem; border-radius: 6px; background: #f3f4f6; color: #6b7280; }
.dt-card-footer {
  border-top: 1px solid #f3f4f6; padding: .7rem 1.2rem;
  display: flex; align-items: center; justify-content: space-between; margin-top: auto;
  gap: .75rem;
}
.dt-card-date { font-size: .72rem; color: #9ca3af; }
.dt-card-actions { display: inline-flex; align-items: center; gap: .4rem; flex-wrap: wrap; justify-content: flex-end; }
.dt-card-btn {
  display: inline-flex; align-items: center; gap: .3rem; padding: .35rem .8rem;
  border-radius: 8px; font-size: .78rem; font-weight: 600; text-decoration: none;
  border: 1px solid transparent; transition: all .2s; white-space: nowrap; cursor: pointer;
}
.dt-card-view { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
.dt-card-view:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
.dt-card-dl { background: #fff7ed; color: #ea580c; border-color: #fed7aa; }
.dt-card-dl:hover { background: #ea580c; color: #fff; border-color: #ea580c; }
.dt-card-edit { background: #ecfdf5; color: #047857; border-color: #bbf7d0; }
.dt-card-edit:hover { background: #047857; color: #fff; border-color: #047857; }
.dt-card-delete { background: #fef2f2; color: #dc2626; border-color: #fecaca; padding-inline: .65rem; }
.dt-card-delete:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

.dt-empty { text-align: center; padding: 3rem 1rem; color: #9ca3af; }

.dt-modal-overlay {
  position: fixed; inset: 0; z-index: 1080; display: flex; align-items: center; justify-content: center;
  background: rgba(15, 23, 42, .46); padding: 1rem; overflow-y: auto;
}
.dt-modal {
  box-sizing: border-box; width: min(620px, 100%); max-height: calc(100dvh - 2rem); overflow: auto;
  background: #fff; border-radius: 16px; box-shadow: 0 24px 70px rgba(15,23,42,.28);
  padding: 1.2rem; display: flex; flex-direction: column; gap: .85rem;
}
.dt-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.dt-modal-head h3 { margin: 0; color: #1e293b; font-size: 1.05rem; font-weight: 800; }
.dt-modal-head p { margin: .2rem 0 0; color: #64748b; font-size: .8rem; }
.dt-modal-close {
  width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0;
  background: #f8fafc; color: #64748b; cursor: pointer;
}
.dt-form-alert {
  border: 1px solid #fecaca; border-radius: 10px; background: #fef2f2;
  color: #b91c1c; padding: .65rem .75rem; font-size: .8rem; font-weight: 700;
}
.dt-field { display: flex; flex-direction: column; gap: .35rem; }
.dt-field span, .dt-check span { color: #334155; font-size: .78rem; font-weight: 800; }
.dt-field strong { color: #dc2626; }
.dt-field input, .dt-field textarea {
  width: 100%; border: 1px solid #dbe3ed; border-radius: 10px;
  padding: .6rem .7rem; color: #1e293b; font-size: .86rem;
}
.dt-field textarea { resize: vertical; }
.dt-field small { color: #64748b; font-size: .72rem; }
.dt-check { display: inline-flex; align-items: center; gap: .5rem; }
.dt-check input { width: 18px; height: 18px; }
.dt-modal-actions { display: flex; justify-content: flex-end; gap: .6rem; padding-top: .2rem; }
.dt-secondary-btn, .dt-save-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
  border-radius: 9px; padding: .52rem .85rem; font-size: .8rem; font-weight: 800; cursor: pointer;
}
.dt-secondary-btn { border: 1px solid #dbe3ed; background: #fff; color: #475569; }
.dt-save-btn { border: 1px solid #0f6f9d; background: #0f6f9d; color: #fff; }
.dt-save-btn:disabled { opacity: .65; cursor: wait; }

/* ── Filtering overlay ── */
.dt-filtering { opacity: 0.4; pointer-events: none; transition: opacity .2s; }
.dt-empty-icon {
  width: 64px; height: 64px; border-radius: 50%; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; color: #d1d5db;
}

@media (max-width: 576px) {
  .dt-modal-overlay {
    align-items: flex-start;
    padding: max(.75rem, env(safe-area-inset-top)) .75rem max(.75rem, env(safe-area-inset-bottom));
  }
  .dt-modal {
    max-height: calc(100dvh - 1.5rem);
    border-radius: 14px;
    padding: .95rem;
    gap: .7rem;
  }
  .dt-modal-head { gap: .75rem; }
  .dt-modal-close { width: 34px; height: 34px; flex: 0 0 34px; }
  .dt-field textarea { min-height: 84px; }
  .dt-hero-main { flex-direction: column; }
  .dt-add-btn { width: 100%; }
  .dt-cat-grid { grid-template-columns: repeat(2, 1fr); }
  .dt-grid { grid-template-columns: 1fr; }
  .dt-card-footer { align-items: flex-start; flex-direction: column; }
  .dt-card-actions { width: 100%; justify-content: flex-start; }
  .dt-modal-actions { flex-direction: column-reverse; }
}

@media (max-height: 700px) {
  .dt-modal-overlay {
    align-items: flex-start;
    padding-top: .75rem;
    padding-bottom: .75rem;
  }
  .dt-modal {
    max-height: calc(100dvh - 1.5rem);
  }
}
</style>
