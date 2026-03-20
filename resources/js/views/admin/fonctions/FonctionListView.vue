<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="fas fa-briefcase me-2"></i>Fonctions</h4>
        <p class="text-muted mb-0">Gestion des fonctions du personnel</p>
      </div>
      <router-link to="/admin/fonctions/create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nouvelle fonction
      </router-link>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="input-group">
              <input
                v-model="search"
                type="text"
                class="form-control"
                placeholder="Rechercher par nom..."
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
          <div class="col-md-4">
            <select v-model="niveau" class="form-select" @change="fetchData">
              <option value="">Tous les niveaux</option>
              <option value="SEN">SEN</option>
              <option value="SEP">SEP</option>
              <option value="SEL">SEL</option>
              <option value="TOUS">TOUS</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement des fonctions...</p>
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
                <th>Nom</th>
                <th>Niveau Admin</th>
                <th>Type Poste</th>
                <th>Est Chef</th>
                <th style="width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="fonctions.length === 0">
                <td colspan="5" class="text-center text-muted py-4">
                  <i class="fas fa-briefcase fa-2x mb-2 d-block"></i>
                  Aucune fonction trouvee.
                </td>
              </tr>
              <tr v-for="f in fonctions" :key="f.id">
                <td class="fw-semibold">{{ f.nom }}</td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'bg-primary': f.niveau_administratif === 'SEN',
                      'bg-info': f.niveau_administratif === 'SEP',
                      'bg-success': f.niveau_administratif === 'SEL',
                      'bg-secondary': f.niveau_administratif === 'TOUS',
                    }"
                  >
                    {{ f.niveau_administratif }}
                  </span>
                </td>
                <td>{{ f.type_poste || '-' }}</td>
                <td>
                  <span v-if="f.est_chef" class="badge bg-warning text-dark">Oui</span>
                  <span v-else class="text-muted">Non</span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <router-link
                      :to="'/admin/fonctions/' + f.id + '/edit'"
                      class="btn btn-outline-warning"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      class="btn btn-outline-danger"
                      title="Supprimer"
                      @click="destroy(f)"
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

const fonctions = ref([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const niveau = ref('')
const currentPage = ref(1)
const lastPage = ref(1)

async function fetchData() {
  loading.value = true
  error.value = ''
  try {
    const params = { page: currentPage.value }
    if (search.value) params.search = search.value
    if (niveau.value) params.niveau = niveau.value
    const { data } = await client.get('/admin/fonctions', { params })
    fonctions.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
  } catch (e) {
    console.error('Erreur chargement fonctions:', e)
    error.value = 'Impossible de charger les fonctions.'
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchData()
}

async function destroy(f) {
  if (!confirm('Etes-vous sur de vouloir supprimer cette fonction ?')) return
  try {
    await client.delete('/admin/fonctions/' + f.id)
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
