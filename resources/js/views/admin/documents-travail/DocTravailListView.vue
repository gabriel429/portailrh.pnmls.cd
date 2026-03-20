<template>
  <div class="doc-travail-page">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-folder-open"></i>
        </div>
        <div>
          <h4 class="text-white mb-0 fw-bold">Documents de Travail</h4>
          <small class="text-white-50">Gestion des documents de travail</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="count-badge" v-if="pagination.total">
          {{ pagination.total }} document{{ pagination.total > 1 ? 's' : '' }}
        </span>
        <router-link :to="{ name: 'admin.documents-travail.create' }" class="hero-btn">
          <i class="fas fa-plus"></i> Nouveau Document
        </router-link>
      </div>
    </div>

    <!-- Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px;">
      {{ error }}
      <button type="button" class="btn-close" @click="error = null"></button>
    </div>

    <!-- Search Box -->
    <div class="search-box">
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input
          type="text"
          v-model="search"
          class="search-input"
          placeholder="Rechercher par titre..."
          @input="onSearchInput"
        >
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color: #0ea5e9;" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="text-muted mt-2 mb-0" style="font-size: .88rem;">Chargement des documents...</p>
    </div>

    <!-- Data Table -->
    <div v-else>
      <div v-if="documents.length === 0" class="empty-state">
        <i class="fas fa-folder-open"></i>
        <p>Aucun document trouve.</p>
      </div>

      <div v-else class="data-card">
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Titre</th>
                <th>Categorie</th>
                <th>Uploade par</th>
                <th>Actif</th>
                <th>Date</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in documents" :key="doc.id">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="doc-icon">
                      <i class="fas fa-file-alt"></i>
                    </div>
                    <span class="fw-500">{{ doc.titre }}</span>
                  </div>
                </td>
                <td>
                  <span v-if="doc.categorie" class="categorie-badge">
                    {{ doc.categorie }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <span class="text-muted">{{ doc.uploader ? doc.uploader.name : '-' }}</span>
                </td>
                <td>
                  <span class="status-dot" :class="doc.actif ? 'status-active' : 'status-inactive'"></span>
                  {{ doc.actif ? 'Oui' : 'Non' }}
                </td>
                <td>
                  <span class="text-muted">{{ formatDate(doc.created_at) }}</span>
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1">
                    <router-link
                      :to="{ name: 'admin.documents-travail.edit', params: { id: doc.id } }"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button class="action-btn action-btn-danger" @click="deleteDocument(doc)" title="Supprimer">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <nav v-if="pagination.lastPage > 1" class="mt-3">
        <ul class="pagination modern-pagination justify-content-center mb-0">
          <li class="page-item" :class="{ disabled: pagination.currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.currentPage - 1)">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
          <li
            v-for="page in paginationPages"
            :key="page"
            class="page-item"
            :class="{ active: page === pagination.currentPage, disabled: page === '...' }"
          >
            <a class="page-link" href="#" @click.prevent="page !== '...' && goToPage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.currentPage === pagination.lastPage }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.currentPage + 1)">
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'

const documents = ref([])
const loading = ref(false)
const error = ref(null)
const search = ref('')
let searchTimeout = null

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

const paginationPages = computed(() => {
  const pages = []
  const current = pagination.value.currentPage
  const last = pagination.value.lastPage

  if (last <= 7) {
    for (let i = 1; i <= last; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
      pages.push(i)
    }
    if (current < last - 2) pages.push('...')
    pages.push(last)
  }
  return pages
})

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

async function fetchDocuments(page = 1) {
  loading.value = true
  error.value = null
  try {
    const response = await client.get('/admin/documents-travail', {
      params: { search: search.value, page },
    })
    documents.value = response.data.data
    pagination.value = {
      currentPage: response.data.current_page,
      lastPage: response.data.last_page,
      total: response.data.total,
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement des documents.'
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchDocuments(1)
  }, 400)
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.lastPage) return
  fetchDocuments(page)
}

async function deleteDocument(doc) {
  if (!confirm('Etes-vous sur de vouloir supprimer ce document ?')) return
  try {
    await client.delete(`/admin/documents-travail/${doc.id}`)
    fetchDocuments(pagination.value.currentPage)
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors de la suppression.'
  }
}

onMounted(() => fetchDocuments())
</script>

<style scoped>
.doc-travail-page {
  padding: 1.5rem 0;
}

.page-hero {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(14, 165, 233, .25);
  margin-bottom: 1.5rem;
}

.page-hero-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, .15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #fff;
}

.count-badge {
  background: rgba(255, 255, 255, .2);
  color: #fff;
  font-size: .78rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
}

.hero-btn {
  background: rgba(255, 255, 255, .2);
  border: 1px solid rgba(255, 255, 255, .3);
  color: #fff;
  padding: .5rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: .85rem;
  text-decoration: none;
  transition: all .2s;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
}

.hero-btn:hover {
  background: rgba(255, 255, 255, .35);
  color: #fff;
}

.search-box {
  background: #fff;
  border-radius: 14px;
  padding: 1rem 1.25rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
  border: 1px solid #f1f5f9;
  margin-bottom: 1.25rem;
}

.search-wrapper {
  position: relative;
}

.search-input {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: .5rem 1rem .5rem 2.5rem;
  font-size: .88rem;
  width: 100%;
  transition: border-color .2s;
}

.search-input:focus {
  border-color: #0ea5e9;
  outline: none;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, .1);
}

.search-icon {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.data-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.data-table {
  margin-bottom: 0;
}

.data-table thead th {
  background: #f8fafc;
  border: none;
  font-size: .78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #64748b;
  padding: .85rem 1rem;
}

.data-table tbody td {
  padding: .75rem 1rem;
  border-color: #f1f5f9;
  vertical-align: middle;
  font-size: .88rem;
}

.data-table tbody tr {
  transition: background .15s;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.doc-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: #f0f9ff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0ea5e9;
  font-size: .85rem;
  flex-shrink: 0;
}

.fw-500 {
  font-weight: 500;
}

.categorie-badge {
  background: #e0f2fe;
  color: #0369a1;
  font-size: .78rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
}

.status-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 4px;
}

.status-active {
  background: #22c55e;
}

.status-inactive {
  background: #94a3b8;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-size: .8rem;
  transition: all .2s;
  cursor: pointer;
  text-decoration: none;
}

.action-btn:hover {
  border-color: #0ea5e9;
  color: #0ea5e9;
  background: #f0f9ff;
}

.action-btn-danger:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  border: 2px dashed #e2e8f0;
  border-radius: 14px;
  background: #fff;
}

.empty-state i {
  font-size: 2.5rem;
  color: #cbd5e1;
  margin-bottom: .75rem;
  display: block;
}

.empty-state p {
  color: #94a3b8;
  margin: 0;
  font-weight: 500;
}

.modern-pagination .page-link {
  border-radius: 8px;
  margin: 0 2px;
  border: 1px solid #e2e8f0;
  color: #64748b;
  font-size: .85rem;
}

.modern-pagination .page-item.active .page-link {
  background: #0ea5e9;
  border-color: #0ea5e9;
}

.modern-pagination .page-link:hover {
  background: #f0f9ff;
  color: #0ea5e9;
  border-color: #0ea5e9;
}

/* ── Mobile Responsive ── */
@media (max-width: 767.98px) {
  .page-hero {
    padding: 1.25rem 1rem;
    border-radius: 12px;
    flex-direction: column;
    align-items: flex-start;
  }
  .page-hero h4 {
    font-size: 1.1rem;
  }
  .page-hero small {
    font-size: .78rem;
  }
  .page-hero-icon {
    width: 42px;
    height: 42px;
    font-size: 1.1rem;
    border-radius: 10px;
  }
  .hero-btn {
    padding: .4rem .9rem;
    font-size: .78rem;
  }
  .search-box {
    border-radius: 10px;
    padding: .85rem 1rem;
  }
  .search-input {
    font-size: .82rem;
  }
  .data-card {
    border-radius: 10px;
  }
  .data-table thead th {
    font-size: .72rem;
    padding: .65rem .75rem;
  }
  .data-table tbody td {
    font-size: .82rem;
    padding: .6rem .75rem;
  }
  .doc-icon {
    width: 28px;
    height: 28px;
    font-size: .75rem;
  }
  .action-btn {
    width: 28px;
    height: 28px;
    font-size: .72rem;
  }
  .empty-state {
    padding: 2rem 1rem;
    border-radius: 10px;
  }
  .empty-state i {
    font-size: 2rem;
  }
  .modern-pagination .page-link {
    font-size: .78rem;
  }
}
</style>
