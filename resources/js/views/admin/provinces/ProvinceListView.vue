<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="fas fa-map me-2"></i>Provinces</h4>
        <p class="text-muted mb-0">Gestion des provinces</p>
      </div>
      <router-link to="/admin/provinces/create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nouvelle province
      </router-link>
    </div>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body py-3">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
              <input
                v-model="search"
                type="text"
                class="form-control"
                placeholder="Rechercher une province..."
                @input="debouncedFetch"
              />
            </div>
          </div>
          <div class="col-md-6 text-md-end mt-2 mt-md-0">
            <small class="text-muted">{{ total }} province(s) trouvee(s)</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement...</p>
    </div>

    <!-- Table -->
    <div v-else class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="bg-light">
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Ville Secretariat</th>
              <th class="text-center">Agents</th>
              <th class="text-center">Departements</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="provinces.length === 0">
              <td colspan="6" class="text-center text-muted py-4">Aucune province trouvee.</td>
            </tr>
            <tr v-for="province in provinces" :key="province.id">
              <td><span class="badge bg-secondary">{{ province.code }}</span></td>
              <td class="fw-semibold">{{ province.nom }}</td>
              <td>{{ province.ville_secretariat || '-' }}</td>
              <td class="text-center">
                <span class="badge bg-primary bg-opacity-10 text-primary">{{ province.agents_count ?? 0 }}</span>
              </td>
              <td class="text-center">
                <span class="badge bg-info bg-opacity-10 text-info">{{ province.departments_count ?? 0 }}</span>
              </td>
              <td class="text-end">
                <router-link
                  :to="`/admin/provinces/${province.id}/edit`"
                  class="btn btn-sm btn-outline-primary me-1"
                  title="Modifier"
                >
                  <i class="fas fa-edit"></i>
                </router-link>
                <button
                  class="btn btn-sm btn-outline-danger"
                  title="Supprimer"
                  @click="deleteProvince(province)"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Page {{ currentPage }} sur {{ lastPage }}</small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: currentPage <= 1 }">
              <button class="page-link" @click="goToPage(currentPage - 1)">&laquo;</button>
            </li>
            <li
              v-for="p in pagesArray"
              :key="p"
              class="page-item"
              :class="{ active: p === currentPage }"
            >
              <button class="page-link" @click="goToPage(p)">{{ p }}</button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage >= lastPage }">
              <button class="page-link" @click="goToPage(currentPage + 1)">&raquo;</button>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'

const loading = ref(true)
const provinces = ref([])
const search = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

let debounceTimer = null

const pagesArray = computed(() => {
  const pages = []
  for (let i = 1; i <= lastPage.value; i++) {
    pages.push(i)
  }
  return pages
})

async function fetchProvinces() {
  loading.value = true
  try {
    const { data } = await client.get('/admin/provinces', {
      params: { search: search.value, page: currentPage.value },
    })
    provinces.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    total.value = data.total || 0
  } catch (e) {
    console.error('Erreur chargement provinces:', e)
    alert('Erreur lors du chargement des provinces.')
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    fetchProvinces()
  }, 400)
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchProvinces()
}

async function deleteProvince(province) {
  if (!confirm(`Etes-vous sur de vouloir supprimer la province "${province.nom}" ?`)) return
  try {
    await client.delete(`/admin/provinces/${province.id}`)
    fetchProvinces()
  } catch (e) {
    console.error('Erreur suppression province:', e)
    alert('Erreur lors de la suppression de la province.')
  }
}

onMounted(() => {
  fetchProvinces()
})
</script>
