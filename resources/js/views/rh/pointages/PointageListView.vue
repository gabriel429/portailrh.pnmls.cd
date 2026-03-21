<template>
  <div class="rh-modern">
    <div class="rh-list-shell">
      <section class="rh-hero">
        <div class="row g-2 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-clock me-2"></i>Gestion des pointages</h1>
            <p class="rh-sub">Suivi des presences, absences et heures travaillees.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'rh.pointages.create' }" class="btn-rh main">
                <i class="fas fa-plus me-1"></i> Nouveau pointage
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <!-- Navigation tabs -->
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <router-link :to="{ name: 'rh.pointages.index' }" class="btn btn-primary">
          <i class="fas fa-list me-2"></i>Liste
        </router-link>
        <router-link :to="{ name: 'rh.pointages.daily' }" class="btn btn-outline-secondary">
          <i class="fas fa-calendar-alt me-2"></i>Par jour
        </router-link>
        <router-link :to="{ name: 'rh.pointages.monthly' }" class="btn btn-outline-secondary">
          <i class="fas fa-chart-bar me-2"></i>Rapport mensuel
        </router-link>
      </div>

      <!-- Filters -->
      <div class="rh-list-card p-3 mb-3">
        <form @submit.prevent="applyFilters" class="row g-3 align-items-end">
          <div class="col-md-2">
            <label for="filter-date-debut" class="form-label">Date debut</label>
            <input type="date" id="filter-date-debut" v-model="filters.date_debut" class="form-control">
          </div>
          <div class="col-md-2">
            <label for="filter-date-fin" class="form-label">Date fin</label>
            <input type="date" id="filter-date-fin" v-model="filters.date_fin" class="form-control">
          </div>
          <div class="col-md-3">
            <label for="filter-department" class="form-label">Departement</label>
            <select id="filter-department" v-model="filters.department_id" class="form-select">
              <option value="">Tous</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.nom }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="filter-organe" class="form-label">Organe</label>
            <select id="filter-organe" v-model="filters.organe" class="form-select">
              <option value="">Tous les organes</option>
              <option value="Secretariat Executif National">National (SEN)</option>
              <option value="Secretariat Executif Provincial">Provincial (SEP)</option>
              <option value="Secretariat Executif Local">Local (SEL)</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
              <i class="fas fa-filter me-1"></i> Filtrer
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="resetFilters">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </form>
      </div>

      <!-- Loading -->
      <LoadingSpinner v-if="loading" message="Chargement des pointages..." />

      <!-- Table -->
      <div v-else class="rh-list-card p-3 p-lg-4">
        <template v-if="pointages.length > 0">
          <div class="rh-table-wrap">
            <table class="rh-table">
              <thead>
                <tr>
                  <th>Agent</th>
                  <th>Matricule</th>
                  <th>Date</th>
                  <th>Entree</th>
                  <th>Sortie</th>
                  <th>Heures</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pointage in pointages" :key="pointage.id">
                  <td><strong>{{ pointage.agent?.prenom }} {{ pointage.agent?.nom }}</strong></td>
                  <td>{{ pointage.agent?.id_agent }}</td>
                  <td>{{ formatDate(pointage.date_pointage) }}</td>
                  <td>
                    <span v-if="pointage.heure_entree" class="status-chip st-ok">{{ formatTime(pointage.heure_entree) }}</span>
                    <span v-else class="status-chip st-neutral">-</span>
                  </td>
                  <td>
                    <span v-if="pointage.heure_sortie" class="status-chip st-mid">{{ formatTime(pointage.heure_sortie) }}</span>
                    <span v-else class="status-chip st-neutral">-</span>
                  </td>
                  <td>
                    <strong v-if="pointage.heures_travaillees">{{ pointage.heures_travaillees }}h</strong>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <router-link :to="{ name: 'rh.pointages.show', params: { id: pointage.id } }" class="btn btn-outline-primary" title="Details">
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link :to="{ name: 'rh.pointages.edit', params: { id: pointage.id } }" class="btn btn-outline-warning" title="Modifier">
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button type="button" class="btn btn-outline-danger" title="Supprimer" @click="confirmDelete(pointage)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
            <div class="text-muted small">
              Affichage {{ meta.from || 0 }} a {{ meta.to || 0 }} sur {{ meta.total || 0 }} pointages
            </div>
            <Pagination
              :current-page="meta.current_page"
              :last-page="meta.last_page"
              :pages="paginationPages"
              :has-pages="meta.last_page > 1"
              @page-change="goToPage"
            />
          </div>
        </template>

        <div v-else class="text-center py-5">
          <i class="fas fa-clock fa-4x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Aucun pointage</h5>
          <p class="text-muted">Il n'y a aucun pointage enregistre.</p>
          <router-link :to="{ name: 'rh.pointages.create' }" class="btn btn-primary mt-2">
            <i class="fas fa-plus me-2"></i>Creer un pointage
          </router-link>
        </div>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer le pointage"
      :message="`Etes-vous sur de vouloir supprimer le pointage de ${deleteTarget?.agent?.prenom} ${deleteTarget?.agent?.nom} du ${formatDate(deleteTarget?.date_pointage)} ?`"
      :loading="deleting"
      @confirm="doDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import * as pointagesApi from '@/api/pointages'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import Pagination from '@/components/common/Pagination.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const pointages = ref([])
const meta = ref({})
const departments = ref([])

const filters = reactive({
    date_debut: '',
    date_fin: '',
    department_id: '',
    organe: '',
})

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

const paginationPages = computed(() => {
    const pages = []
    const current = meta.value.current_page || 1
    const last = meta.value.last_page || 1
    const delta = 2

    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
            pages.push(i)
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...')
        }
    }
    return pages
})

function formatDate(dateStr) {
    if (!dateStr) return 'N/A'
    const d = new Date(dateStr)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatTime(timeStr) {
    if (!timeStr) return '-'
    // Handle both "HH:mm" and "HH:mm:ss" and datetime formats
    if (timeStr.length > 5) {
        const d = new Date(timeStr)
        if (!isNaN(d.getTime())) {
            return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
        }
    }
    return timeStr.substring(0, 5)
}

async function fetchPointages(page = 1) {
    loading.value = true
    try {
        const params = { page, per_page: 15 }
        if (filters.date_debut) params.date_debut = filters.date_debut
        if (filters.date_fin) params.date_fin = filters.date_fin
        if (filters.department_id) params.department_id = filters.department_id
        if (filters.organe) params.organe = filters.organe

        const { data } = await pointagesApi.list(params)
        pointages.value = data.data
        meta.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            from: data.from,
            to: data.to,
            total: data.total,
        }
    } catch (err) {
        ui.addToast('Erreur lors du chargement des pointages.', 'danger')
    } finally {
        loading.value = false
    }
}

async function fetchDepartments() {
    try {
        const { data } = await client.get('/agents/form-options')
        departments.value = data.departments || []
    } catch {
        // form-options may not exist yet
    }
}

function applyFilters() {
    fetchPointages(1)
}

function resetFilters() {
    filters.date_debut = ''
    filters.date_fin = ''
    filters.department_id = ''
    filters.organe = ''
    fetchPointages(1)
}

function goToPage(page) {
    fetchPointages(page)
}

function confirmDelete(pointage) {
    deleteTarget.value = pointage
    showDeleteModal.value = true
}

async function doDelete() {
    if (!deleteTarget.value) return
    deleting.value = true
    try {
        await pointagesApi.remove(deleteTarget.value.id)
        ui.addToast('Pointage supprime avec succes.', 'success')
        showDeleteModal.value = false
        deleteTarget.value = null
        fetchPointages(meta.value.current_page)
    } catch {
        ui.addToast('Erreur lors de la suppression.', 'danger')
    } finally {
        deleting.value = false
    }
}

onMounted(() => {
    fetchPointages()
    fetchDepartments()
})
</script>

<style scoped>
@media (max-width: 767.98px) {
    /* Hide Matricule(2), Entree(4), Sortie(5) */
    .rh-table th:nth-child(2),
    .rh-table td:nth-child(2),
    .rh-table th:nth-child(4),
    .rh-table td:nth-child(4),
    .rh-table th:nth-child(5),
    .rh-table td:nth-child(5) { display: none; }

    /* Compact table */
    .rh-table th, .rh-table td { padding: .4rem .3rem; font-size: .76rem; }
    .rh-table th { font-size: .65rem; }

    /* Action buttons */
    .btn-group-sm .btn { padding: .2rem .35rem; font-size: .68rem; }

    /* Filters */
    .rh-list-card .row.g-3 > [class*="col-md"] { flex: 0 0 100%; max-width: 100%; }

    /* Nav tabs wrap */
    .d-flex.gap-2.mb-3 .btn { font-size: .78rem; padding: .3rem .55rem; }
}

@media (max-width: 575.98px) {
    /* Also hide Heures(6) */
    .rh-table th:nth-child(6),
    .rh-table td:nth-child(6) { display: none; }

    .rh-table th, .rh-table td { padding: .35rem .25rem; font-size: .72rem; }
    .rh-table th { font-size: .62rem; }

    .btn-group-sm .btn { padding: .18rem .3rem; font-size: .65rem; }
}
</style>
