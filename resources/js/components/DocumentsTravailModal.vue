<template>
  <Teleport to="body">
    <Transition name="docs-modal-fade">
      <div v-if="open" class="docs-modal-overlay" @click.self="closeModal">
        <section class="docs-modal" role="dialog" aria-modal="true" aria-labelledby="docs-modal-title">
          <header class="docs-modal-header">
            <div class="docs-modal-title-wrap">
              <span class="docs-modal-icon"><i class="fas fa-file-invoice"></i></span>
              <div>
                <h2 id="docs-modal-title">Documents de travail</h2>
                <p>
                  <template v-if="auth.canManageDocsTravail">Gestion complete des documents RH</template>
                  <template v-else>{{ totalDocs }} document{{ totalDocs > 1 ? 's' : '' }} disponible{{ totalDocs > 1 ? 's' : '' }}</template>
                </p>
              </div>
            </div>
            <div class="docs-modal-head-actions">
              <button
                v-if="auth.canManageDocsTravail && mode === 'list'"
                class="docs-modal-primary"
                type="button"
                @click="startCreate"
              >
                <i class="fas fa-plus"></i>
                Nouveau
              </button>
              <button class="docs-modal-close" type="button" aria-label="Fermer" @click="closeModal">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </header>

          <template v-if="mode === 'list'">
            <div v-if="auth.canManageDocsTravail" class="docs-modal-toolbar">
              <div class="docs-modal-search">
                <i class="fas fa-search"></i>
                <input
                  v-model="search"
                  type="search"
                  placeholder="Rechercher un document..."
                  @input="onSearchInput"
                >
              </div>
              <span class="docs-modal-admin-badge">
                <i class="fas fa-shield-alt"></i>
                CRUD Super Admin
              </span>
            </div>

            <div class="docs-modal-categories" :class="{ 'is-loading': filtering }">
              <button class="docs-modal-cat" :class="{ active: !categorie }" type="button" @click="setCategorie('')">
                <span class="docs-modal-cat-icon"><i class="fas fa-th-large"></i></span>
                <span class="docs-modal-cat-text">
                  <strong>Tous</strong>
                  <small>{{ totalDocs }} document{{ totalDocs > 1 ? 's' : '' }}</small>
                </span>
              </button>
              <button
                v-for="cat in categories"
                :key="cat.id"
                class="docs-modal-cat"
                :class="{ active: categorie === cat.nom }"
                type="button"
                @click="setCategorie(cat.nom)"
              >
                <span class="docs-modal-cat-icon"><i class="fas" :class="cat.icone || 'fa-folder'"></i></span>
                <span class="docs-modal-cat-text">
                  <strong>{{ cat.nom }}</strong>
                  <small>{{ categoryCounts[cat.nom] || 0 }} document{{ (categoryCounts[cat.nom] || 0) > 1 ? 's' : '' }}</small>
                </span>
              </button>
            </div>
          </template>

          <div class="docs-modal-body">
            <div v-if="loading" class="docs-modal-loading">
              <i class="fas fa-circle-notch fa-spin"></i>
              <span>Chargement des documents...</span>
            </div>

            <template v-else-if="mode === 'list'">
              <div v-if="categorie" class="docs-modal-filter-head">
                <div>
                  <strong>{{ categorie }}</strong>
                  <span>{{ meta.total }} document{{ meta.total > 1 ? 's' : '' }}</span>
                </div>
                <button type="button" @click="setCategorie('')">
                  <i class="fas fa-arrow-left"></i>
                  Toutes
                </button>
              </div>

              <div v-if="documents.length" class="docs-modal-list" :class="{ 'is-loading': filtering }">
                <article v-for="doc in documents" :key="doc.id" class="docs-modal-doc" :class="{ 'is-inactive': auth.canManageDocsTravail && !doc.actif }">
                  <div class="docs-modal-doc-icon" :class="iconClass(doc.type_fichier)">
                    <i class="fas" :class="iconName(doc.type_fichier)"></i>
                  </div>
                  <div class="docs-modal-doc-content">
                    <div class="docs-modal-doc-title-row">
                      <h3>{{ doc.titre }}</h3>
                      <span v-if="auth.canManageDocsTravail" class="docs-modal-status" :class="doc.actif ? 'is-active' : 'is-off'">
                        {{ doc.actif ? 'Actif' : 'Inactif' }}
                      </span>
                    </div>
                    <p v-if="doc.description">{{ doc.description }}</p>
                    <div class="docs-modal-doc-meta">
                      <span v-if="doc.categorie">{{ doc.categorie }}</span>
                      <span>.{{ (doc.type_fichier || '').toUpperCase() }}</span>
                      <span v-if="doc.taille">{{ formatSize(doc.taille) }}</span>
                      <span>{{ formatDate(doc.created_at) }}</span>
                      <span v-if="auth.canManageDocsTravail && doc.uploader">{{ doc.uploader.name }}</span>
                    </div>
                  </div>
                  <div class="docs-modal-doc-actions">
                    <a :href="`/documents-travail/${doc.id}/view`" target="_blank" rel="noopener" title="Voir">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a :href="`/documents-travail/${doc.id}/download`" title="Telecharger">
                      <i class="fas fa-download"></i>
                    </a>
                    <button v-if="auth.canManageDocsTravail" type="button" title="Modifier" @click="startEdit(doc)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button v-if="auth.canManageDocsTravail" class="is-danger" type="button" title="Supprimer" @click="deleteDocument(doc)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </article>
              </div>

              <div v-else class="docs-modal-empty">
                <div><i class="fas fa-folder-open"></i></div>
                <h3>Aucun document</h3>
                <p>Les documents publies par l'administration apparaitront ici.</p>
              </div>
            </template>

            <form v-else class="docs-modal-form" @submit.prevent="submitForm">
              <div class="docs-modal-form-head">
                <button type="button" class="docs-modal-back" @click="returnToList">
                  <i class="fas fa-arrow-left"></i>
                </button>
                <div>
                  <h3>{{ editingDoc ? 'Modifier le document' : 'Nouveau document' }}</h3>
                  <p>{{ editingDoc ? 'Mettez a jour les informations ou remplacez le fichier.' : 'Publiez un document de travail pour les agents.' }}</p>
                </div>
              </div>

              <div v-if="formError" class="docs-modal-alert">
                {{ formError }}
              </div>

              <div v-if="Object.keys(validationErrors).length" class="docs-modal-alert">
                <div v-for="(messages, field) in validationErrors" :key="field">
                  {{ messages.join(', ') }}
                </div>
              </div>

              <label class="docs-modal-field">
                <span>Titre <strong>*</strong></span>
                <input v-model="form.titre" type="text" required>
              </label>

              <label class="docs-modal-field">
                <span>Description</span>
                <textarea v-model="form.description" rows="3"></textarea>
              </label>

              <label class="docs-modal-field">
                <span>Categorie</span>
                <input v-model="form.categorie" type="text" list="docs-modal-categories-list">
                <datalist id="docs-modal-categories-list">
                  <option v-for="cat in categories" :key="cat.id" :value="cat.nom"></option>
                </datalist>
              </label>

              <label class="docs-modal-field">
                <span>
                  Fichier
                  <strong v-if="!editingDoc">*</strong>
                </span>
                <input ref="fileInput" type="file" :required="!editingDoc" @change="onFileChange">
                <small v-if="editingDoc && currentFileName">Actuel : {{ currentFileName }}</small>
                <small v-if="selectedFile">Nouveau : {{ selectedFile.name }}</small>
              </label>

              <label class="docs-modal-check">
                <input v-model="form.actif" type="checkbox">
                <span>Document actif et visible aux agents</span>
              </label>

              <div class="docs-modal-form-actions">
                <button type="button" class="docs-modal-secondary" @click="returnToList">Annuler</button>
                <button type="submit" class="docs-modal-save" :disabled="submitting">
                  <i v-if="submitting" class="fas fa-circle-notch fa-spin"></i>
                  <i v-else class="fas fa-save"></i>
                  {{ editingDoc ? 'Mettre a jour' : 'Creer' }}
                </button>
              </div>
            </form>
          </div>

          <footer v-if="mode === 'list' && meta.last_page > 1" class="docs-modal-footer">
            <button type="button" :disabled="meta.current_page === 1 || filtering" @click="loadDocuments(meta.current_page - 1)">
              <i class="fas fa-chevron-left"></i>
            </button>
            <span>Page {{ meta.current_page }} / {{ meta.last_page }}</span>
            <button type="button" :disabled="meta.current_page === meta.last_page || filtering" @click="loadDocuments(meta.current_page + 1)">
              <i class="fas fa-chevron-right"></i>
            </button>
          </footer>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import client from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'

const OPEN_EVENT = 'epnmls:open-documents-travail'

const auth = useAuthStore()
const ui = useUiStore()
const open = ref(false)
const mode = ref('list')
const loading = ref(false)
const filtering = ref(false)
const loadedOnce = ref(false)
const documents = ref([])
const categories = ref([])
const categoryCounts = ref({})
const totalDocs = ref(0)
const categorie = ref('')
const search = ref('')
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })
const editingDoc = ref(null)
const submitting = ref(false)
const formError = ref(null)
const validationErrors = ref({})
const selectedFile = ref(null)
const fileInput = ref(null)
let searchTimeout = null

const form = ref({
  titre: '',
  description: '',
  categorie: '',
  actif: true,
})

const currentFileName = computed(() => currentFileNameFromDoc(editingDoc.value))

async function loadDocuments(page = 1) {
  if (page < 1 || (meta.value.last_page && page > meta.value.last_page)) return

  loading.value = !loadedOnce.value
  filtering.value = loadedOnce.value

  try {
    const params = { page }
    if (categorie.value) params.categorie = categorie.value
    if (auth.canManageDocsTravail) {
      params.per_page = 20
      if (search.value) params.search = search.value
    }

    const endpoint = auth.canManageDocsTravail ? '/admin/documents-travail' : '/documents-travail'
    const { data } = await client.get(endpoint, { params })
    documents.value = data.data || []
    categories.value = data.categories || []
    categoryCounts.value = data.categoryCounts || {}
    totalDocs.value = Number(data.totalDocs ?? data.total ?? 0)
    meta.value = normalizeMeta(data)
    loadedOnce.value = true
  } catch {
    ui.addToast('Erreur lors du chargement des documents.', 'danger')
  } finally {
    loading.value = false
    filtering.value = false
  }
}

function normalizeMeta(data) {
  return data.meta || {
    current_page: Number(data.current_page || 1),
    last_page: Number(data.last_page || 1),
    per_page: Number(data.per_page || 20),
    total: Number(data.total || documents.value.length),
    from: data.from || null,
    to: data.to || null,
  }
}

function openModal() {
  open.value = true
  mode.value = 'list'
  if (!loadedOnce.value && !loading.value) {
    loadDocuments()
  }
}

function closeModal() {
  open.value = false
}

function setCategorie(cat) {
  if (categorie.value === cat) return
  categorie.value = cat
  loadDocuments(1)
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadDocuments(1), 350)
}

function resetForm() {
  editingDoc.value = null
  form.value = {
    titre: '',
    description: '',
    categorie: categorie.value || '',
    actif: true,
  }
  selectedFile.value = null
  formError.value = null
  validationErrors.value = {}
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function startCreate() {
  if (!auth.canManageDocsTravail) return
  resetForm()
  mode.value = 'form'
}

function startEdit(doc) {
  if (!auth.canManageDocsTravail) return
  editingDoc.value = doc
  form.value = {
    titre: doc.titre || '',
    description: doc.description || '',
    categorie: doc.categorie || '',
    actif: !!doc.actif,
  }
  selectedFile.value = null
  formError.value = null
  validationErrors.value = {}
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  mode.value = 'form'
}

function returnToList() {
  mode.value = 'list'
  formError.value = null
  validationErrors.value = {}
}

function onFileChange(event) {
  selectedFile.value = event.target.files[0] || null
}

async function submitForm() {
  if (!auth.canManageDocsTravail) return

  submitting.value = true
  formError.value = null
  validationErrors.value = {}

  const formData = new FormData()
  formData.append('titre', form.value.titre)
  formData.append('description', form.value.description || '')
  formData.append('categorie', form.value.categorie || '')
  formData.append('actif', form.value.actif ? '1' : '0')

  if (selectedFile.value) {
    formData.append('fichier', selectedFile.value)
  }

  try {
    if (editingDoc.value) {
      formData.append('_method', 'PUT')
      await client.post(`/admin/documents-travail/${editingDoc.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      ui.addToast('Document mis a jour.', 'success')
    } else {
      await client.post('/admin/documents-travail', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      ui.addToast('Document cree.', 'success')
    }

    mode.value = 'list'
    loadedOnce.value = false
    await loadDocuments(editingDoc.value ? meta.value.current_page : 1)
    resetForm()
  } catch (e) {
    if (e.response?.status === 422) {
      validationErrors.value = e.response.data.errors || {}
    } else {
      formError.value = e.response?.data?.message || "Erreur lors de l'enregistrement."
    }
  } finally {
    submitting.value = false
  }
}

async function deleteDocument(doc) {
  if (!auth.canManageDocsTravail) return
  if (!window.confirm(`Supprimer le document "${doc.titre}" ?`)) return

  try {
    await client.delete(`/admin/documents-travail/${doc.id}`)
    ui.addToast('Document supprime.', 'success')
    const nextPage = documents.value.length === 1 && meta.value.current_page > 1
      ? meta.value.current_page - 1
      : meta.value.current_page
    loadedOnce.value = false
    await loadDocuments(nextPage)
  } catch (e) {
    ui.addToast(e.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  }
}

function currentFileNameFromDoc(doc) {
  const value = doc?.fichier_nom || doc?.fichier || ''
  return value.split(/[\\/]/).pop()
}

function iconClass(ext) {
  const e = (ext || '').toLowerCase()
  const map = {
    pdf: 'is-pdf',
    doc: 'is-doc',
    docx: 'is-doc',
    xls: 'is-xls',
    xlsx: 'is-xls',
    ppt: 'is-ppt',
    pptx: 'is-ppt',
    jpg: 'is-img',
    jpeg: 'is-img',
    png: 'is-img',
  }
  return map[e] || 'is-other'
}

function iconName(ext) {
  const e = (ext || '').toLowerCase()
  const map = {
    pdf: 'fa-file-pdf',
    doc: 'fa-file-word',
    docx: 'fa-file-word',
    xls: 'fa-file-excel',
    xlsx: 'fa-file-excel',
    ppt: 'fa-file-powerpoint',
    pptx: 'fa-file-powerpoint',
    jpg: 'fa-file-image',
    jpeg: 'fa-file-image',
    png: 'fa-file-image',
  }
  return map[e] || 'fa-file-alt'
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatSize(size) {
  const mb = Number(size || 0) / 1024 / 1024
  return `${mb.toFixed(mb >= 10 ? 0 : 1)} Mo`
}

function handleKeydown(event) {
  if (event.key === 'Escape' && open.value) {
    closeModal()
  }
}

watch(open, (value) => {
  document.body.classList.toggle('documents-modal-open', value)
})

watch(() => auth.canManageDocsTravail, () => {
  loadedOnce.value = false
  if (open.value) {
    loadDocuments(1)
  }
})

onMounted(() => {
  window.addEventListener(OPEN_EVENT, openModal)
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener(OPEN_EVENT, openModal)
  window.removeEventListener('keydown', handleKeydown)
  document.body.classList.remove('documents-modal-open')
  clearTimeout(searchTimeout)
})
</script>

<style scoped>
:global(body.documents-modal-open) {
  overflow: hidden;
}

.docs-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 13000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: rgba(15, 23, 42, .58);
  backdrop-filter: blur(8px);
}

.docs-modal {
  width: min(1060px, 100%);
  max-height: min(820px, calc(100dvh - 3rem));
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #f8fafc;
  border-radius: 18px;
  box-shadow: 0 28px 70px rgba(15, 23, 42, .3);
}

.docs-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.15rem 1.35rem;
  background: linear-gradient(135deg, #0f6f9d, #0a557b);
  color: #fff;
}

.docs-modal-title-wrap,
.docs-modal-head-actions,
.docs-modal-form-head {
  display: flex;
  align-items: center;
  gap: .85rem;
  min-width: 0;
}

.docs-modal-icon,
.docs-modal-close,
.docs-modal-back {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.docs-modal-icon {
  background: rgba(255, 255, 255, .16);
}

.docs-modal-header h2,
.docs-modal-form-head h3 {
  margin: 0;
  font-size: 1.08rem;
  font-weight: 850;
  line-height: 1.15;
}

.docs-modal-header p,
.docs-modal-form-head p {
  margin: .18rem 0 0;
  font-size: .78rem;
  opacity: .82;
}

.docs-modal-close,
.docs-modal-primary {
  border: 1px solid rgba(255, 255, 255, .24);
  background: rgba(255, 255, 255, .12);
  color: #fff;
  cursor: pointer;
}

.docs-modal-close:hover,
.docs-modal-primary:hover {
  background: rgba(255, 255, 255, .22);
}

.docs-modal-primary {
  min-height: 38px;
  border-radius: 11px;
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  padding: .45rem .8rem;
  font-size: .8rem;
  font-weight: 850;
}

.docs-modal-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  padding: .85rem 1rem 0;
  background: #fff;
}

.docs-modal-search {
  flex: 1;
  position: relative;
}

.docs-modal-search i {
  position: absolute;
  left: .8rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: .8rem;
}

.docs-modal-search input {
  width: 100%;
  min-height: 40px;
  border: 1px solid #dbe3ed;
  border-radius: 11px;
  padding: .5rem .75rem .5rem 2.2rem;
  font-size: .84rem;
}

.docs-modal-admin-badge {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
  padding: .45rem .75rem;
  font-size: .74rem;
  font-weight: 850;
  white-space: nowrap;
}

.docs-modal-categories {
  display: flex;
  gap: .65rem;
  overflow-x: auto;
  padding: .85rem 1rem;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
}

.docs-modal-categories.is-loading {
  opacity: .6;
  pointer-events: none;
}

.docs-modal-cat {
  min-width: 174px;
  display: inline-flex;
  align-items: center;
  gap: .62rem;
  padding: .68rem .75rem;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
  color: #334155;
  text-align: left;
  cursor: pointer;
  transition: border-color .18s, box-shadow .18s, transform .18s, background .18s, color .18s;
}

.docs-modal-cat:hover {
  transform: translateY(-1px);
  border-color: #93c5fd;
  box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
}

.docs-modal-cat.active {
  background: #0f6f9d;
  border-color: #0f6f9d;
  color: #fff;
}

.docs-modal-cat-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #e0f2fe;
  color: #0f6f9d;
  flex-shrink: 0;
}

.docs-modal-cat.active .docs-modal-cat-icon {
  background: rgba(255, 255, 255, .2);
  color: #fff;
}

.docs-modal-cat-text {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: .08rem;
}

.docs-modal-cat-text strong,
.docs-modal-cat-text small {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.docs-modal-cat-text strong {
  font-size: .78rem;
  line-height: 1.2;
}

.docs-modal-cat-text small {
  font-size: .68rem;
  opacity: .72;
}

.docs-modal-body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 1rem;
}

.docs-modal-loading,
.docs-modal-empty {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .7rem;
  color: #64748b;
  text-align: center;
}

.docs-modal-loading i {
  color: #0f6f9d;
  font-size: 1.55rem;
}

.docs-modal-empty div {
  width: 66px;
  height: 66px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #e2e8f0;
  color: #94a3b8;
  font-size: 1.45rem;
}

.docs-modal-empty h3 {
  margin: 0;
  color: #334155;
  font-size: .98rem;
}

.docs-modal-empty p {
  margin: 0;
  font-size: .82rem;
}

.docs-modal-filter-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .8rem;
  margin-bottom: .85rem;
  padding: .75rem .85rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
}

.docs-modal-filter-head div {
  min-width: 0;
}

.docs-modal-filter-head strong {
  display: block;
  color: #1e293b;
  font-size: .88rem;
}

.docs-modal-filter-head span {
  color: #64748b;
  font-size: .74rem;
}

.docs-modal-filter-head button,
.docs-modal-secondary,
.docs-modal-back {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .38rem;
  border: 1px solid #bfdbfe;
  border-radius: 9px;
  background: #eff6ff;
  color: #1d4ed8;
  padding: .45rem .7rem;
  font-size: .76rem;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
}

.docs-modal-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: .8rem;
  transition: opacity .18s;
}

.docs-modal-list.is-loading {
  opacity: .45;
  pointer-events: none;
}

.docs-modal-doc {
  display: flex;
  gap: .75rem;
  min-width: 0;
  padding: .95rem;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 4px 16px rgba(15, 23, 42, .04);
}

.docs-modal-doc.is-inactive {
  opacity: .68;
}

.docs-modal-doc-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1.05rem;
}

.docs-modal-doc-icon.is-pdf { background: #fee2e2; color: #dc2626; }
.docs-modal-doc-icon.is-doc { background: #dbeafe; color: #2563eb; }
.docs-modal-doc-icon.is-xls { background: #dcfce7; color: #16a34a; }
.docs-modal-doc-icon.is-ppt { background: #ffedd5; color: #ea580c; }
.docs-modal-doc-icon.is-img { background: #e0f2fe; color: #0284c7; }
.docs-modal-doc-icon.is-other { background: #f1f5f9; color: #64748b; }

.docs-modal-doc-content {
  flex: 1;
  min-width: 0;
}

.docs-modal-doc-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: .5rem;
}

.docs-modal-doc-content h3 {
  margin: 0;
  color: #1e293b;
  font-size: .9rem;
  font-weight: 850;
  line-height: 1.25;
}

.docs-modal-status {
  border-radius: 999px;
  padding: .16rem .45rem;
  font-size: .62rem;
  font-weight: 850;
  flex-shrink: 0;
}

.docs-modal-status.is-active {
  background: #dcfce7;
  color: #15803d;
}

.docs-modal-status.is-off {
  background: #e2e8f0;
  color: #64748b;
}

.docs-modal-doc-content p {
  margin: .25rem 0 0;
  color: #64748b;
  font-size: .76rem;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.docs-modal-doc-meta {
  display: flex;
  flex-wrap: wrap;
  gap: .35rem;
  margin-top: .55rem;
}

.docs-modal-doc-meta span {
  border-radius: 7px;
  background: #f1f5f9;
  color: #64748b;
  padding: .18rem .45rem;
  font-size: .65rem;
  font-weight: 750;
}

.docs-modal-doc-actions {
  display: flex;
  align-items: flex-start;
  gap: .38rem;
  flex-shrink: 0;
}

.docs-modal-doc-actions a,
.docs-modal-doc-actions button {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
  cursor: pointer;
}

.docs-modal-doc-actions a:hover,
.docs-modal-doc-actions button:hover {
  background: #1d4ed8;
  border-color: #1d4ed8;
  color: #fff;
}

.docs-modal-doc-actions .is-danger {
  background: #fef2f2;
  border-color: #fecaca;
  color: #dc2626;
}

.docs-modal-doc-actions .is-danger:hover {
  background: #dc2626;
  border-color: #dc2626;
  color: #fff;
}

.docs-modal-form {
  max-width: 760px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: .9rem;
  padding: .25rem 0 1rem;
}

.docs-modal-form-head {
  align-items: flex-start;
  margin-bottom: .25rem;
}

.docs-modal-form-head p {
  color: #64748b;
  opacity: 1;
}

.docs-modal-alert {
  border: 1px solid #fecaca;
  border-radius: 12px;
  background: #fef2f2;
  color: #b91c1c;
  padding: .75rem .9rem;
  font-size: .82rem;
  font-weight: 700;
}

.docs-modal-field {
  display: flex;
  flex-direction: column;
  gap: .35rem;
}

.docs-modal-field span,
.docs-modal-check span {
  color: #334155;
  font-size: .78rem;
  font-weight: 850;
}

.docs-modal-field strong {
  color: #dc2626;
}

.docs-modal-field input,
.docs-modal-field textarea {
  width: 100%;
  border: 1px solid #dbe3ed;
  border-radius: 11px;
  background: #fff;
  color: #1e293b;
  padding: .62rem .75rem;
  font-size: .86rem;
}

.docs-modal-field textarea {
  resize: vertical;
}

.docs-modal-field small {
  color: #64748b;
  font-size: .73rem;
}

.docs-modal-check {
  display: inline-flex;
  align-items: center;
  gap: .55rem;
}

.docs-modal-check input {
  width: 18px;
  height: 18px;
}

.docs-modal-form-actions {
  display: flex;
  justify-content: flex-end;
  gap: .6rem;
  padding-top: .3rem;
}

.docs-modal-save {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .45rem;
  border: 1px solid #0f6f9d;
  border-radius: 10px;
  background: #0f6f9d;
  color: #fff;
  padding: .55rem .9rem;
  font-size: .8rem;
  font-weight: 850;
  cursor: pointer;
}

.docs-modal-save:disabled {
  opacity: .62;
  cursor: wait;
}

.docs-modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .8rem;
  padding: .85rem 1rem;
  background: #fff;
  border-top: 1px solid #e5e7eb;
}

.docs-modal-footer button {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #334155;
  cursor: pointer;
}

.docs-modal-footer button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.docs-modal-footer span {
  font-size: .78rem;
  color: #475569;
  font-weight: 800;
}

.docs-modal-fade-enter-active,
.docs-modal-fade-leave-active {
  transition: opacity .18s ease;
}

.docs-modal-fade-enter-from,
.docs-modal-fade-leave-to {
  opacity: 0;
}

.docs-modal-fade-enter-active .docs-modal {
  animation: docsModalIn .22s ease-out;
}

@keyframes docsModalIn {
  from { transform: translateY(14px) scale(.98); }
  to { transform: translateY(0) scale(1); }
}

@media (max-width: 780px) {
  .docs-modal-overlay {
    align-items: flex-end;
    padding: .75rem;
  }

  .docs-modal {
    max-height: calc(100dvh - 1.5rem);
    border-radius: 18px;
  }

  .docs-modal-toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .docs-modal-admin-badge {
    justify-content: center;
  }

  .docs-modal-list {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 540px) {
  .docs-modal-overlay {
    padding: 0;
  }

  .docs-modal {
    max-height: 100dvh;
    height: 100dvh;
    border-radius: 0;
  }

  .docs-modal-header {
    padding: 1rem;
  }

  .docs-modal-primary {
    width: 38px;
    padding: 0;
  }

  .docs-modal-primary span,
  .docs-modal-primary {
    font-size: 0;
  }

  .docs-modal-primary i {
    font-size: .86rem;
  }

  .docs-modal-cat {
    min-width: 150px;
  }

  .docs-modal-doc {
    flex-wrap: wrap;
  }

  .docs-modal-doc-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .docs-modal-form-actions {
    flex-direction: column-reverse;
  }
}
</style>
