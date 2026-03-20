<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Documents de Travail</h4>
      <router-link :to="{ name: 'admin.documents-travail.create' }" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nouveau Document
      </router-link>
    </div>

    <div v-if="error" class="alert alert-danger alert-dismissible fade show">
      {{ error }}
      <button type="button" class="btn-close" @click="error = null"></button>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body py-2">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
              <input type="text" v-model="search" class="form-control" placeholder="Rechercher par titre..." @input="onSearchInput">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>

    <div v-else class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
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
            <tr v-if="documents.length === 0">
              <td colspan="6" class="text-center text-muted py-4">Aucun document trouve.</td>
            </tr>
            <tr v-for="doc in documents" :key="doc.id">
              <td>{{ doc.titre }}</td>
              <td>{{ doc.categorie || '-' }}</td>
              <td>{{ doc.uploader ? doc.uploader.name : '-' }}</td>
              <td>
                <span class="badge" :class="doc.actif ? 'bg-success' : 'bg-secondary'">
                  {{ doc.actif ? 'Oui' : 'Non' }}
                </span>
              </td>
              <td>{{ formatDate(doc.created_at) }}</td>
              <td class="text-end">
                <router-link :to="{ name: 'admin.documents-travail.edit', params: { id: doc.id } }" class="btn btn-sm btn-outline-primary me-1">
                  <i class="fas fa-edit"></i>
                </router-link>
                <button class="btn btn-sm btn-outline-danger" @click="deleteDocument(doc)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <nav v-if="pagination.lastPage > 1" class="mt-3">
      <ul class="pagination justify-content-center mb-0">
        <li class="page-item" :class="{ disabled: pagination.currentPage === 1 }">
          <a class="page-link" href="#" @click.prevent="goToPage(pagination.currentPage - 1)">
            <i class="fas fa-chevron-left"></i>
          </a>
        </li>
        <li v-for="page in paginationPages" :key="page" class="page-item" :class="{ active: page === pagination.currentPage, disabled: page === '...' }">
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
