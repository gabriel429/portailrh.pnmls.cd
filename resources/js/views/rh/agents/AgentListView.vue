<template>
  <div class="rh-modern">
    <div class="rh-list-shell">

      <!-- Hero section -->
      <section class="rh-hero">
        <div class="row g-2 align-items-center mb-3">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-users me-2"></i>Gestion des agents</h1>
            <p class="rh-sub">Administrez les profils PNMLS, roles, statuts et informations administratives.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools d-flex gap-2 justify-content-lg-end">
              <button type="button" class="btn-rh main" style="background:#28a745;border-color:#28a745;" @click="showExportModal = true">
                <i class="fas fa-file-csv me-1"></i> Exporter
              </button>
              <router-link :to="{ name: 'rh.agents.create' }" class="btn-rh main">
                <i class="fas fa-user-plus me-1"></i> Ajouter un agent
              </router-link>
            </div>
          </div>
        </div>

        <!-- Search bar -->
        <div class="row">
          <div class="col-lg-12">
            <form @submit.prevent="applySearch">
              <div class="input-group">
                <input
                  type="text"
                  v-model="searchInput"
                  class="form-control"
                  placeholder="Rechercher... (nom, email, matricule, province, grade, fonction, niveau etude)"
                >
                <button class="btn btn-primary" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button v-if="filters.search" class="btn btn-outline-secondary" type="button" @click="clearSearch">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Filters row -->
        <div class="row mt-3 g-2">
          <div class="col-md-3">
            <select v-model="filters.organe" class="form-select form-select-sm" @change="fetchAgents">
              <option value="">Tous les organes</option>
              <option value="SEN">SEN - Secretariat Executif National</option>
              <option value="SEP">SEP - Secretariat Executif Provincial</option>
              <option value="SEL">SEL - Secretariat Executif Local</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filters.province_id" class="form-select form-select-sm" @change="fetchAgents">
              <option value="">Toutes les provinces</option>
              <option v-for="p in provinces" :key="p.id" :value="p.id">{{ p.nom_province || p.nom }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filters.department_id" class="form-select form-select-sm" @change="fetchAgents">
              <option value="">Tous les departements</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filters.statut" class="form-select form-select-sm" @change="fetchAgents">
              <option value="">Tous les statuts</option>
              <option value="actif">Actif</option>
              <option value="suspendu">Suspendu</option>
              <option value="ancien">Ancien</option>
            </select>
          </div>
        </div>
      </section>

      <!-- Loading state -->
      <LoadingSpinner v-if="loading" message="Chargement des agents..." />

      <!-- Content -->
      <template v-else>
        <!-- Stats -->
        <div v-if="stats.total > 0" class="kpi-grid mb-4">
          <div class="kpi-card">
            <div class="kpi-value">{{ stats.total }}</div>
            <div class="kpi-label">Total agents</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value" style="color:#0077B5;">{{ stats.sen }}</div>
            <div class="kpi-label">SEN</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value" style="color:#0ea5e9;">{{ stats.sep }}</div>
            <div class="kpi-label">SEP</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value" style="color:#0d9488;">{{ stats.sel }}</div>
            <div class="kpi-label">SEL</div>
          </div>
        </div>

        <!-- Agents grouped by organe -->
        <template v-if="agentsByOrgane.length > 0">
          <div
            v-for="organe in agentsByOrgane"
            :key="organe.label"
            class="card mb-4 border-0 shadow-sm"
            :style="{ borderTop: '4px solid ' + organe.color }"
          >
            <div class="card-header border-0" :style="{ backgroundColor: organe.bg }">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="card-title mb-0" :style="{ color: organe.color }">
                    <i class="fas" :class="organe.icon" style="margin-right:0.5rem;"></i>{{ organe.label }}
                  </h5>
                  <small class="text-muted">{{ organe.agents.length }} agent{{ organe.agents.length > 1 ? 's' : '' }}</small>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="rh-table-wrap">
                <table class="rh-table">
                  <thead>
                    <tr>
                      <th>Nom et Prenom</th>
                      <th>Email prive</th>
                      <th>Email professionnel</th>
                      <th>Telephone</th>
                      <th>Poste</th>
                      <th v-if="isOrganeNational(organe.label)">Departement/Service</th>
                      <th v-else>Province</th>
                      <th>Matricule de l'Etat</th>
                      <th>Anciennete</th>
                      <th>Statut</th>
                      <th style="width:100px;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="agent in organe.agents"
                      :key="agent.id"
                      class="agent-row"
                      style="cursor:pointer;"
                      @click="goToAgent(agent.id)"
                    >
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <div v-if="agent.photo" class="agent-avatar">
                            <img :src="'/' + agent.photo" :alt="agent.nom_complet" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                          </div>
                          <div v-else class="agent-avatar-initials rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.75rem;font-weight:700;">
                            {{ getInitials(agent) }}
                          </div>
                          <span>{{ agent.prenom }} {{ agent.nom }}</span>
                        </div>
                      </td>
                      <td>{{ agent.email_prive || 'N/A' }}</td>
                      <td>{{ agent.email_professionnel || 'N/A' }}</td>
                      <td>{{ agent.telephone || 'N/A' }}</td>
                      <td>{{ agent.poste_actuel || 'N/A' }}</td>
                      <td v-if="isOrganeNational(organe.label)">
                        {{ agent.departement ? agent.departement.nom : 'Service rattache au SEN' }}
                      </td>
                      <td v-else>{{ agent.province ? agent.province.nom : 'N/A' }}</td>
                      <td>{{ agent.matricule_etat || 'N/A' }}</td>
                      <td>
                        <template v-if="agent.anciennete !== null">
                          {{ agent.anciennete }} an{{ agent.anciennete > 1 ? 's' : '' }}
                        </template>
                        <template v-else>N/A</template>
                      </td>
                      <td>
                        <span v-if="agent.statut === 'actif'" class="rh-pill st-ok">Actif</span>
                        <span v-else-if="agent.statut === 'suspendu'" class="rh-pill st-mid">Suspendu</span>
                        <span v-else class="rh-pill st-neutral">{{ capitalize(agent.statut) }}</span>
                      </td>
                      <td @click.stop>
                        <div class="btn-group btn-group-sm">
                          <router-link
                            :to="{ name: 'rh.agents.show', params: { id: agent.id } }"
                            class="btn btn-outline-primary btn-sm"
                            title="Voir"
                          >
                            <i class="fas fa-eye"></i>
                          </router-link>
                          <router-link
                            :to="{ name: 'rh.agents.edit', params: { id: agent.id } }"
                            class="btn btn-outline-warning btn-sm"
                            title="Modifier"
                          >
                            <i class="fas fa-edit"></i>
                          </router-link>
                          <button
                            class="btn btn-outline-danger btn-sm"
                            title="Supprimer"
                            @click="confirmDelete(agent)"
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
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="rh-list-card text-center py-5">
          <i class="fas fa-users fa-4x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Aucun agent</h5>
          <p class="text-muted">Il n'y a aucun agent enregistre.</p>
          <router-link :to="{ name: 'rh.agents.create' }" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Ajouter un agent
          </router-link>
        </div>
      </template>
    </div>

    <!-- Export Modal -->
    <teleport to="body">
      <div v-if="showExportModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog">
          <div class="modal-content border-0">
            <div class="modal-header" style="background:#28a745;color:#fff;">
              <h5 class="modal-title"><i class="fas fa-file-csv me-2"></i>Exporter la liste des agents</h5>
              <button type="button" class="btn-close btn-close-white" @click="showExportModal = false"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Organe</label>
                <select v-model="exportFilters.organe" class="form-select" @change="updateExportFilters">
                  <option value="tous">Tous les organes</option>
                  <option value="SEN">Secretariat Executif National (SEN)</option>
                  <option value="SEP">Secretariat Executif Provincial (SEP)</option>
                  <option value="SEL">Secretariat Executif Local (SEL)</option>
                </select>
              </div>
              <div v-if="showExportProvince" class="mb-3">
                <label class="form-label fw-bold">Province</label>
                <select v-model="exportFilters.province_id" class="form-select">
                  <option value="">Toutes les provinces</option>
                  <option v-for="p in provinces" :key="p.id" :value="p.id">{{ p.nom_province || p.nom }}</option>
                </select>
              </div>
              <div v-if="showExportDept" class="mb-3">
                <label class="form-label fw-bold">Departement</label>
                <select v-model="exportFilters.departement_id" class="form-select">
                  <option value="">Tous les departements</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                </select>
              </div>
              <div class="alert alert-info py-2">
                <i class="fas fa-info-circle me-1"></i>
                Le fichier CSV sera telecharge et peut etre ouvert avec Excel.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showExportModal = false">Annuler</button>
              <button type="button" class="btn btn-success" :disabled="exporting" @click="doExport">
                <span v-if="exporting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-download me-1"></i> Telecharger CSV
              </button>
            </div>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer l'agent"
      :message="'Etes-vous sur de vouloir supprimer ' + (agentToDelete ? agentToDelete.prenom + ' ' + agentToDelete.nom : '') + ' ? Cette action est irreversible.'"
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
import { list, remove, exportCsv, getFormOptions } from '@/api/agents'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const ui = useUiStore()

// State
const loading = ref(true)
const agentsByOrgane = ref([])
const stats = ref({ total: 0, sen: 0, sep: 0, sel: 0 })
const provinces = ref([])
const departments = ref([])

// Search and filters
const searchInput = ref('')
const filters = reactive({
    search: '',
    organe: '',
    province_id: '',
    department_id: '',
    statut: '',
})

// Delete modal
const showDeleteModal = ref(false)
const agentToDelete = ref(null)
const deleting = ref(false)

// Export modal
const showExportModal = ref(false)
const exporting = ref(false)
const exportFilters = reactive({
    organe: 'tous',
    province_id: '',
    departement_id: '',
})

const showExportProvince = computed(() => {
    const val = exportFilters.organe
    return val === 'SEP' || val === 'SEL' || val === 'tous'
})

const showExportDept = computed(() => {
    const val = exportFilters.organe
    return val === 'SEN' || val === 'tous'
})

function updateExportFilters() {
    if (!showExportProvince.value) exportFilters.province_id = ''
    if (!showExportDept.value) exportFilters.departement_id = ''
}

// Helpers
function capitalize(str) {
    if (!str) return ''
    return str.charAt(0).toUpperCase() + str.slice(1)
}

function getInitials(agent) {
    const p = agent.prenom ? agent.prenom.charAt(0).toUpperCase() : ''
    const n = agent.nom ? agent.nom.charAt(0).toUpperCase() : ''
    return p + n
}

function isOrganeNational(label) {
    return label && label.toLowerCase().includes('national')
}

function goToAgent(id) {
    router.push({ name: 'rh.agents.show', params: { id } })
}

// Fetch agents
async function fetchAgents() {
    loading.value = true
    try {
        const params = {}
        if (filters.search) params.search = filters.search
        if (filters.organe) params.organe = filters.organe
        if (filters.province_id) params.province_id = filters.province_id
        if (filters.department_id) params.department_id = filters.department_id
        if (filters.statut) params.statut = filters.statut

        const { data } = await list(params)
        agentsByOrgane.value = data.agentsByOrgane || []
        stats.value = data.stats || { total: 0, sen: 0, sep: 0, sel: 0 }
    } catch (err) {
        console.error('Error fetching agents:', err)
        ui.addToast('Erreur lors du chargement des agents', 'danger')
    } finally {
        loading.value = false
    }
}

// Fetch form options (provinces, departments for filters)
async function fetchOptions() {
    try {
        const { data } = await getFormOptions()
        provinces.value = data.provinces || []
        departments.value = data.departments || []
    } catch (err) {
        console.error('Error fetching options:', err)
    }
}

// Search
function applySearch() {
    filters.search = searchInput.value
    fetchAgents()
}

function clearSearch() {
    searchInput.value = ''
    filters.search = ''
    fetchAgents()
}

// Delete
function confirmDelete(agent) {
    agentToDelete.value = agent
    showDeleteModal.value = true
}

async function doDelete() {
    if (!agentToDelete.value) return
    deleting.value = true
    try {
        await remove(agentToDelete.value.id)
        ui.addToast('Agent supprime avec succes', 'success')
        showDeleteModal.value = false
        agentToDelete.value = null
        await fetchAgents()
    } catch (err) {
        console.error('Error deleting agent:', err)
        ui.addToast('Erreur lors de la suppression', 'danger')
    } finally {
        deleting.value = false
    }
}

// Export
async function doExport() {
    exporting.value = true
    try {
        const params = {}
        if (exportFilters.organe && exportFilters.organe !== 'tous') params.organe = exportFilters.organe
        if (exportFilters.province_id) params.province_id = exportFilters.province_id
        if (exportFilters.departement_id) params.departement_id = exportFilters.departement_id

        const response = await exportCsv(params)

        // Create download link from blob
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url

        // Extract filename from Content-Disposition header or use default
        const disposition = response.headers['content-disposition']
        let filename = 'agents_export.csv'
        if (disposition) {
            const match = disposition.match(/filename="?([^";\s]+)"?/)
            if (match) filename = match[1]
        }
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        showExportModal.value = false
        ui.addToast('Export CSV telecharge avec succes', 'success')
    } catch (err) {
        console.error('Error exporting:', err)
        ui.addToast('Erreur lors de l\'export', 'danger')
    } finally {
        exporting.value = false
    }
}

onMounted(() => {
    fetchOptions()
    fetchAgents()
})
</script>

<style scoped>
/* ── KPI cards ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .75rem;
}
.kpi-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: .9rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.kpi-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
}
.kpi-label {
    font-size: .72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-top: .35rem;
}

/* ── Card header organe ── */
.card-header {
    padding: .85rem 1rem;
}
.card-title {
    font-weight: 800;
    font-size: 1rem;
}

/* ── Table ── */
.rh-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.rh-table {
    width: 100%;
    border-collapse: collapse;
}
.rh-table th, .rh-table td {
    padding: .55rem .5rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: .84rem;
}
.rh-table th {
    font-size: .72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .04em;
    background: #f8fafc;
    white-space: nowrap;
}
.agent-row:hover {
    background: #f0f9ff;
}

/* ── Action buttons ── */
.btn-group-sm .btn {
    padding: .25rem .45rem;
    font-size: .75rem;
}

/* ── Form controls in hero ── */
.rh-hero .form-control,
.rh-hero .form-select {
    border-radius: 10px;
    font-size: .85rem;
    border: 1.5px solid rgba(255,255,255,.25);
    background: rgba(255,255,255,.15);
    color: #fff;
}
.rh-hero .form-control::placeholder { color: rgba(255,255,255,.6); }
.rh-hero .form-control:focus,
.rh-hero .form-select:focus {
    background: rgba(255,255,255,.25);
    border-color: rgba(255,255,255,.5);
    color: #fff;
    box-shadow: none;
}
.rh-hero .form-select option { color: #333; background: #fff; }
.rh-hero .btn-primary {
    background: rgba(255,255,255,.2);
    border: 1.5px solid rgba(255,255,255,.3);
    color: #fff;
}
.rh-hero .btn-outline-secondary {
    border-color: rgba(255,255,255,.3);
    color: rgba(255,255,255,.8);
}

/* ── Modal ── */
.modal-content { border-radius: 16px; overflow: hidden; }

/* ═══════════════════════════════════════════
   Responsive
   ═══════════════════════════════════════════ */
@media (max-width: 767.98px) {
    /* KPI grid */
    .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: .5rem; }
    .kpi-card { padding: .65rem .5rem; border-radius: 10px; }
    .kpi-value { font-size: 1.2rem; }
    .kpi-label { font-size: .65rem; }

    /* Card header */
    .card-header { padding: .65rem .75rem; }
    .card-title { font-size: .88rem; }

    /* Table — hide secondary columns on mobile */
    .rh-table th:nth-child(3),
    .rh-table td:nth-child(3),
    .rh-table th:nth-child(4),
    .rh-table td:nth-child(4),
    .rh-table th:nth-child(7),
    .rh-table td:nth-child(7),
    .rh-table th:nth-child(8),
    .rh-table td:nth-child(8) {
        display: none;
    }

    .rh-table th, .rh-table td {
        padding: .4rem .3rem;
        font-size: .76rem;
    }
    .rh-table th { font-size: .65rem; }

    /* Avatar */
    .agent-avatar-initials,
    .agent-avatar img {
        width: 26px !important;
        height: 26px !important;
        font-size: .65rem !important;
    }

    /* Action buttons */
    .btn-group-sm .btn {
        padding: .2rem .35rem;
        font-size: .68rem;
    }

    /* Hero filters */
    .rh-hero .input-group {
        flex-wrap: nowrap;
    }
    .rh-hero .form-control,
    .rh-hero .form-select {
        font-size: .8rem;
    }

    /* Modal */
    .modal-dialog { margin: .75rem; }
    .modal-header { padding: .75rem 1rem; }
    .modal-header h5 { font-size: .95rem; }
    .modal-body { padding: .9rem; }
    .modal-footer { padding: .6rem .9rem; }
}

@media (max-width: 575.98px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .kpi-value { font-size: 1rem; }

    /* Hide even more columns on very small screens */
    .rh-table th:nth-child(5),
    .rh-table td:nth-child(5),
    .rh-table th:nth-child(6),
    .rh-table td:nth-child(6) {
        display: none;
    }

    .rh-table th, .rh-table td {
        padding: .35rem .25rem;
        font-size: .72rem;
    }
}
</style>
