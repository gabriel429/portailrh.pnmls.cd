<template>
  <div class="py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="fas fa-project-diagram me-2"></i>Affectations</h4>
        <p class="text-muted mb-0 small">Gestion des affectations des agents aux fonctions et structures.</p>
      </div>
      <router-link :to="{ name: 'rh.affectations.create' }" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nouvelle affectation
      </router-link>
    </div>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body py-3">
        <form @submit.prevent="applySearch" class="row g-2 align-items-center">
          <div class="col-md-8">
            <div class="input-group">
              <input
                type="text"
                class="form-control"
                v-model="searchInput"
                placeholder="Rechercher par nom ou prenom de l'agent..."
              >
              <button class="btn btn-primary" type="submit">
                <i class="fas fa-search"></i>
              </button>
              <button v-if="search" class="btn btn-outline-secondary" type="button" @click="clearSearch">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          <div class="col-md-4 text-end">
            <small class="text-muted" v-if="total > 0">{{ total }} affectation{{ total > 1 ? 's' : '' }} trouvee{{ total > 1 ? 's' : '' }}</small>
          </div>
        </form>
      </div>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="loading" message="Chargement des affectations..." />

    <!-- Table -->
    <template v-else>
      <div v-if="affectations.length > 0" class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Agent</th>
                  <th>Fonction</th>
                  <th>Niveau Admin.</th>
                  <th>Departement</th>
                  <th>Section</th>
                  <th>Province</th>
                  <th>Actif</th>
                  <th style="width: 100px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="aff in affectations" :key="aff.id">
                  <td>
                    <span v-if="aff.agent">{{ aff.agent.prenom }} {{ aff.agent.nom }}</span>
                    <span v-else class="text-muted">N/A</span>
                  </td>
                  <td>
                    <span v-if="aff.fonction">{{ aff.fonction.nom }}</span>
                    <span v-else class="text-muted">N/A</span>
                  </td>
                  <td>
                    <span class="badge" :class="niveauBadge(aff.niveau_administratif)">
                      {{ aff.niveau_administratif }}
                    </span>
                  </td>
                  <td>
                    <span v-if="aff.department">{{ aff.department.nom }}</span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <span v-if="aff.section">{{ aff.section.nom }}</span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <span v-if="aff.province">{{ aff.province.nom }}</span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <span v-if="aff.actif" class="badge bg-success">Actif</span>
                    <span v-else class="badge bg-secondary">Inactif</span>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <router-link
                        :to="{ name: 'rh.affectations.edit', params: { id: aff.id } }"
                        class="btn btn-outline-warning btn-sm"
                        title="Modifier"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button
                        class="btn btn-outline-danger btn-sm"
                        title="Supprimer"
                        @click="confirmDelete(aff)"
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
        <div v-if="lastPage > 1" class="card-footer bg-white border-top">
          <Pagination
            :current-page="currentPage"
            :last-page="lastPage"
            :pages="paginationPages"
            :has-pages="lastPage > 1"
            @page-change="goToPage"
          />
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-project-diagram fa-3x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Aucune affectation</h5>
          <p class="text-muted">Il n'y a aucune affectation enregistree.</p>
          <router-link :to="{ name: 'rh.affectations.create' }" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Creer une affectation
          </router-link>
        </div>
      </div>
    </template>

    <!-- Delete modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer l'affectation"
      :message="deleteMessage"
      :loading="deleting"
      @confirm="doDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import Pagination from '@/components/common/Pagination.vue'

const ui = useUiStore()

// State
const loading = ref(true)
const affectations = ref([])
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const searchInput = ref('')
const search = ref('')

// Delete
const showDeleteModal = ref(false)
const affToDelete = ref(null)
const deleting = ref(false)

const deleteMessage = computed(() => {
  if (!affToDelete.value) return ''
  const agent = affToDelete.value.agent
  const name = agent ? `${agent.prenom} ${agent.nom}` : 'cet agent'
  return `Etes-vous sur de vouloir supprimer l'affectation de ${name} ? Cette action est irreversible.`
})

// Pagination pages
const paginationPages = computed(() => {
  const pages = []
  const total = lastPage.value
  const current = currentPage.value
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    if (current < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

function niveauBadge(niveau) {
  switch (niveau) {
    case 'SEN': return 'bg-primary'
    case 'SEP': return 'bg-info text-dark'
    case 'SEL': return 'bg-warning text-dark'
    default: return 'bg-secondary'
  }
}

async function fetchAffectations() {
  loading.value = true
  try {
    const params = { page: currentPage.value }
    if (search.value) params.search = search.value
    const { data } = await client.get('/admin/affectations', { params })
    affectations.value = data.data || []
    total.value = data.total || 0
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
  } catch (err) {
    console.error('Error fetching affectations:', err)
    ui.addToast('Erreur lors du chargement des affectations', 'danger')
  } finally {
    loading.value = false
  }
}

function applySearch() {
  search.value = searchInput.value
  currentPage.value = 1
  fetchAffectations()
}

function clearSearch() {
  searchInput.value = ''
  search.value = ''
  currentPage.value = 1
  fetchAffectations()
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  currentPage.value = page
  fetchAffectations()
}

function confirmDelete(aff) {
  affToDelete.value = aff
  showDeleteModal.value = true
}

async function doDelete() {
  if (!affToDelete.value) return
  deleting.value = true
  try {
    await client.delete(`/admin/affectations/${affToDelete.value.id}`)
    ui.addToast('Affectation supprimee avec succes', 'success')
    showDeleteModal.value = false
    affToDelete.value = null
    await fetchAffectations()
  } catch (err) {
    console.error('Error deleting affectation:', err)
    ui.addToast('Erreur lors de la suppression', 'danger')
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  fetchAffectations()
})
</script>
