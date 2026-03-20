<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <!-- Hero -->
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-bullhorn me-2"></i>Communiques Officiels</h1>
            <p class="rh-sub">Gestion des annonces diffusees a tous les agents.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'rh.communiques.create' }" class="btn-rh main">
                <i class="fas fa-plus-circle me-1"></i> Nouveau communique
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement des communiques..." />
      </div>

      <!-- Table -->
      <div v-else-if="communiques.length" class="dash-panel mt-3">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Titre</th>
                <th>Urgence</th>
                <th>Signataire</th>
                <th>Date</th>
                <th>Expiration</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in communiques" :key="c.id">
                <td>
                  <strong>{{ c.titre }}</strong>
                  <br><small class="text-muted">{{ truncate(c.contenu, 60) }}</small>
                </td>
                <td>
                  <span :class="urgenceBadge(c.urgence)">{{ capitalize(c.urgence) }}</span>
                </td>
                <td>{{ c.signataire || '-' }}</td>
                <td>{{ formatDateTime(c.created_at) }}</td>
                <td>
                  <template v-if="c.date_expiration">
                    {{ formatDate(c.date_expiration) }}
                    <br v-if="isExpired(c.date_expiration)">
                    <small v-if="isExpired(c.date_expiration)" class="text-danger">Expire</small>
                  </template>
                  <span v-else class="text-muted">Illimite</span>
                </td>
                <td>
                  <span :class="c.actif ? 'badge bg-success' : 'badge bg-secondary'">
                    {{ c.actif ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <router-link
                      :to="{ name: 'communiques.show-public', params: { id: c.id } }"
                      class="btn btn-sm btn-outline-info"
                      title="Voir"
                    >
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <router-link
                      :to="{ name: 'rh.communiques.create', query: { edit: c.id } }"
                      class="btn btn-sm btn-outline-primary"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      class="btn btn-sm btn-outline-danger"
                      title="Supprimer"
                      @click="confirmDelete(c)"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <small class="text-muted">
            Affichage {{ meta.from ?? 0 }} a {{ meta.to ?? 0 }} sur {{ meta.total }} communiques
          </small>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <button class="page-link" @click="loadCommuniques(meta.current_page - 1)">&laquo;</button>
              </li>
              <li
                v-for="page in paginationPages"
                :key="page"
                class="page-item"
                :class="{ active: page === meta.current_page }"
              >
                <button class="page-link" @click="loadCommuniques(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <button class="page-link" @click="loadCommuniques(meta.current_page + 1)">&raquo;</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="dash-panel mt-3">
        <div class="text-center py-5 text-muted">
          <i class="fas fa-inbox fa-4x mb-3 d-block"></i>
          <h5>Aucun communique publie.</h5>
          <router-link :to="{ name: 'rh.communiques.create' }" class="btn btn-primary mt-2">
            <i class="fas fa-plus me-2"></i>Publier un communique
          </router-link>
        </div>
      </div>

      <ConfirmModal
        :show="showDeleteModal"
        title="Supprimer le communique"
        :message="`Supprimer le communique '${deleteTarget?.titre ?? ''}' ?`"
        :loading="deleting"
        @confirm="handleDelete"
        @cancel="showDeleteModal = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { list, remove } from '@/api/communiques'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()
const loading = ref(true)
const communiques = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

const paginationPages = computed(() => {
  const pages = []
  const total = meta.value.last_page
  const current = meta.value.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function loadCommuniques(page = 1) {
  loading.value = true
  try {
    const { data } = await list({ page })
    communiques.value = data.data
    meta.value = data.meta
  } catch {
    ui.addToast('Erreur lors du chargement des communiques.', 'danger')
  } finally {
    loading.value = false
  }
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

function urgenceBadge(urgence) {
  const map = { urgent: 'badge bg-danger', important: 'badge bg-warning text-dark', normal: 'badge bg-info text-white' }
  return map[urgence] || 'badge bg-secondary'
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function isExpired(dateStr) {
  if (!dateStr) return false
  return new Date(dateStr) < new Date()
}

function confirmDelete(c) {
  deleteTarget.value = c
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await remove(deleteTarget.value.id)
    ui.addToast('Communique supprime.', 'success')
    showDeleteModal.value = false
    deleteTarget.value = null
    await loadCommuniques(meta.value.current_page)
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  } finally {
    deleting.value = false
  }
}

onMounted(() => loadCommuniques())
</script>

<style scoped>
/* ── Mobile responsive styles ── */
@media (max-width: 768px) {
  .rh-hero .row {
    text-align: center;
  }
  .rh-hero .col-lg-4 {
    text-align: center;
  }
  .hero-tools {
    justify-content: center;
    display: flex;
  }
  .rh-title {
    font-size: 1.3rem;
  }
  .rh-sub {
    font-size: 0.85rem;
  }

  /* Table compact */
  .table {
    font-size: 0.82rem;
  }
  .table th,
  .table td {
    padding: 0.5rem 0.4rem;
    white-space: nowrap;
  }

  /* Hide Signataire (3rd), Date (4th), Expiration (5th) */
  .table th:nth-child(3),
  .table td:nth-child(3),
  .table th:nth-child(4),
  .table td:nth-child(4),
  .table th:nth-child(5),
  .table td:nth-child(5) {
    display: none;
  }

  /* Compact action buttons */
  .d-flex.gap-1 .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
  }

  /* Pagination */
  .pagination {
    flex-wrap: wrap;
    gap: 2px;
  }
  .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.78rem;
  }
}

@media (max-width: 576px) {
  /* Also hide Urgence (2nd) */
  .table th:nth-child(2),
  .table td:nth-child(2) {
    display: none;
  }
}
</style>
