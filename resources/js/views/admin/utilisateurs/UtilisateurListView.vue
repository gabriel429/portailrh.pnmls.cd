<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Utilisateurs</h4>
      <router-link :to="{ name: 'admin.utilisateurs.create' }" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nouvel Utilisateur
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
              <input type="text" v-model="search" class="form-control" placeholder="Rechercher par nom ou email..." @input="onSearchInput">
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
              <th>Nom</th>
              <th>Email</th>
              <th>Agent</th>
              <th>Role</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="utilisateurs.length === 0">
              <td colspan="5" class="text-center text-muted py-4">Aucun utilisateur trouve.</td>
            </tr>
            <tr v-for="user in utilisateurs" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.agent ? `${user.agent.nom} ${user.agent.postnom || ''} ${user.agent.prenom || ''}`.trim() : '-' }}</td>
              <td>
                <span v-if="user.role" class="badge bg-info text-dark">{{ user.role.nom }}</span>
                <span v-else class="text-muted">-</span>
              </td>
              <td class="text-end">
                <router-link :to="{ name: 'admin.utilisateurs.edit', params: { id: user.id } }" class="btn btn-sm btn-outline-primary me-1">
                  <i class="fas fa-edit"></i>
                </router-link>
                <button class="btn btn-sm btn-outline-danger" @click="deleteUtilisateur(user)">
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

const utilisateurs = ref([])
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

async function fetchUtilisateurs(page = 1) {
  loading.value = true
  error.value = null
  try {
    const response = await client.get('/admin/utilisateurs', {
      params: { search: search.value, page },
    })
    utilisateurs.value = response.data.data
    pagination.value = {
      currentPage: response.data.current_page,
      lastPage: response.data.last_page,
      total: response.data.total,
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement des utilisateurs.'
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchUtilisateurs(1)
  }, 400)
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.lastPage) return
  fetchUtilisateurs(page)
}

async function deleteUtilisateur(user) {
  if (!confirm('Etes-vous sur de vouloir supprimer cet utilisateur ?')) return
  try {
    await client.delete(`/admin/utilisateurs/${user.id}`)
    fetchUtilisateurs(pagination.value.currentPage)
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors de la suppression.'
  }
}

onMounted(() => fetchUtilisateurs())
</script>
