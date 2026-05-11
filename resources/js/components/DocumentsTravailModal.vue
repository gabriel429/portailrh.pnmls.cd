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
                <p>{{ totalDocs }} document{{ totalDocs > 1 ? 's' : '' }} disponible{{ totalDocs > 1 ? 's' : '' }}</p>
              </div>
            </div>
            <button class="docs-modal-close" type="button" aria-label="Fermer" @click="closeModal">
              <i class="fas fa-times"></i>
            </button>
          </header>

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

          <div class="docs-modal-body">
            <div v-if="loading" class="docs-modal-loading">
              <i class="fas fa-circle-notch fa-spin"></i>
              <span>Chargement des documents...</span>
            </div>

            <template v-else>
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
                <article v-for="doc in documents" :key="doc.id" class="docs-modal-doc">
                  <div class="docs-modal-doc-icon" :class="iconClass(doc.type_fichier)">
                    <i class="fas" :class="iconName(doc.type_fichier)"></i>
                  </div>
                  <div class="docs-modal-doc-content">
                    <h3>{{ doc.titre }}</h3>
                    <p v-if="doc.description">{{ doc.description }}</p>
                    <div class="docs-modal-doc-meta">
                      <span>{{ doc.categorie }}</span>
                      <span>.{{ (doc.type_fichier || '').toUpperCase() }}</span>
                      <span v-if="doc.taille">{{ formatSize(doc.taille) }}</span>
                      <span>{{ formatDate(doc.created_at) }}</span>
                    </div>
                  </div>
                  <div class="docs-modal-doc-actions">
                    <a :href="`/documents-travail/${doc.id}/view`" target="_blank" rel="noopener" title="Voir">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a :href="`/documents-travail/${doc.id}/download`" title="Telecharger">
                      <i class="fas fa-download"></i>
                    </a>
                  </div>
                </article>
              </div>

              <div v-else class="docs-modal-empty">
                <div><i class="fas fa-folder-open"></i></div>
                <h3>Aucun document</h3>
                <p>Les documents publies par l'administration apparaitront ici.</p>
              </div>
            </template>
          </div>

          <footer v-if="meta.last_page > 1" class="docs-modal-footer">
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
import { onMounted, onUnmounted, ref, watch } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const OPEN_EVENT = 'epnmls:open-documents-travail'

const ui = useUiStore()
const open = ref(false)
const loading = ref(false)
const filtering = ref(false)
const loadedOnce = ref(false)
const documents = ref([])
const categories = ref([])
const categoryCounts = ref({})
const totalDocs = ref(0)
const categorie = ref('')
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })

async function loadDocuments(page = 1) {
  if (page < 1 || (meta.value.last_page && page > meta.value.last_page)) return

  loading.value = !loadedOnce.value
  filtering.value = loadedOnce.value

  try {
    const params = { page }
    if (categorie.value) params.categorie = categorie.value

    const { data } = await client.get('/documents-travail', { params })
    documents.value = data.data || []
    categories.value = data.categories || []
    categoryCounts.value = data.categoryCounts || {}
    totalDocs.value = Number(data.totalDocs || 0)
    meta.value = data.meta || { current_page: 1, last_page: 1, total: documents.value.length, from: null, to: null }
    loadedOnce.value = true
  } catch {
    ui.addToast('Erreur lors du chargement des documents.', 'danger')
  } finally {
    loading.value = false
    filtering.value = false
  }
}

function openModal() {
  open.value = true
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

onMounted(() => {
  window.addEventListener(OPEN_EVENT, openModal)
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener(OPEN_EVENT, openModal)
  window.removeEventListener('keydown', handleKeydown)
  document.body.classList.remove('documents-modal-open')
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
  width: min(980px, 100%);
  max-height: min(760px, calc(100dvh - 3rem));
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

.docs-modal-title-wrap {
  display: flex;
  align-items: center;
  gap: .85rem;
  min-width: 0;
}

.docs-modal-icon,
.docs-modal-close {
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

.docs-modal-header h2 {
  margin: 0;
  font-size: 1.08rem;
  font-weight: 850;
  line-height: 1.15;
}

.docs-modal-header p {
  margin: .18rem 0 0;
  font-size: .78rem;
  opacity: .82;
}

.docs-modal-close {
  border: 1px solid rgba(255, 255, 255, .24);
  background: rgba(255, 255, 255, .12);
  color: #fff;
  cursor: pointer;
}

.docs-modal-close:hover {
  background: rgba(255, 255, 255, .22);
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

.docs-modal-filter-head button {
  display: inline-flex;
  align-items: center;
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

.docs-modal-doc-content h3 {
  margin: 0;
  color: #1e293b;
  font-size: .9rem;
  font-weight: 850;
  line-height: 1.25;
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

.docs-modal-doc-actions a {
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
}

.docs-modal-doc-actions a:hover {
  background: #1d4ed8;
  border-color: #1d4ed8;
  color: #fff;
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
}
</style>
