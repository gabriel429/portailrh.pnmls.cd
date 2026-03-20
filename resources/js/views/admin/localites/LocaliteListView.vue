<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="fas fa-map-pin me-2"></i>Localites</h4>
        <p class="text-muted mb-0">Gestion des localites geographiques</p>
      </div>
      <router-link to="/admin/localites/create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nouvelle localite
      </router-link>
    </div>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-8">
            <div class="input-group">
              <input
                v-model="search"
                type="text"
                class="form-control"
                placeholder="Rechercher par nom ou code..."
                @keyup.enter="fetchData"
              >
              <button class="btn btn-outline-secondary" type="button" @click="fetchData">
                <i class="fas fa-search"></i>
              </button>
              <button v-if="search" class="btn btn-outline-secondary" type="button" @click="search = ''; fetchData()">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement des localites...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <!-- Table -->
    <template v-else>
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="text-muted small">
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Province</th>
                <th>Affectations</th>
                <th style="width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="localites.length === 0">
                <td colspan="6" class="text-center text-muted py-4">
                  <i class="fas fa-map-pin fa-2x mb-2 d-block"></i>
                  Aucune localite trouvee.
                </td>
              </tr>
              <tr v-for="l in localites" :key="l.id">
                <td><code>{{ l.code }}</code></td>
                <td class="fw-semibold">{{ l.nom }}</td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'bg-primary': l.type === 'ville',
                      'bg-success': l.type === 'commune',
                      'bg-info': l.type === 'territoire',
                      'bg-warning text-dark': l.type === 'zone_de_sante',
                      'bg-secondary': l.type === 'autre',
                    }"
                  >
                    {{ formatType(l.type) }}
                  </span>
                </td>
                <td>{{ l.province?.nom || '-' }}</td>
                <td>
                  <span class="badge bg-secondary">{{ l.affectations_count ?? 0 }}</span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <router-link
                      :to="'/admin/localites/' + l.id + '/edit'"
                      class="btn btn-outline-warning"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      class="btn btn-outline-danger"
                      title="Supprimer"
                      @click="destroy(l)"
                    >
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
      <nav v-if="lastPage > 1" class="mt-4">
        <ul class="pagination justify-content-center mb-0">
          <li class="page-item" :class="{ disabled: currentPage <= 1 }">
            <button class="page-link" @click="goToPage(currentPage - 1)">&laquo;</button>
          </li>
          <li
            v-for="p in lastPage"
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
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'

const localites = ref([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const currentPage = ref(1)
const lastPage = ref(1)

const typeLabels = {
  territoire: 'Territoire',
  zone_de_sante: 'Zone de sante',
  commune: 'Commune',
  ville: 'Ville',
  autre: 'Autre',
}

function formatType(type) {
  return typeLabels[type] || type || '-'
}

async function fetchData() {
  loading.value = true
  error.value = ''
  try {
    const params = { page: currentPage.value }
    if (search.value) params.search = search.value
    const { data } = await client.get('/admin/localites', { params })
    localites.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
  } catch (e) {
    console.error('Erreur chargement localites:', e)
    error.value = 'Impossible de charger les localites.'
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchData()
}

async function destroy(l) {
  if (!confirm('Etes-vous sur de vouloir supprimer cette localite ?')) return
  try {
    await client.delete('/admin/localites/' + l.id)
    await fetchData()
  } catch (e) {
    console.error('Erreur suppression:', e)
    alert('Erreur lors de la suppression.')
  }
}

onMounted(() => {
  fetchData()
})
</script>
