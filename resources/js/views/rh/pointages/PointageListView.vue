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
              <button class="btn-rh main" @click="openCreateModal">
                <i class="fas fa-plus me-1"></i> Nouveau pointage
              </button>
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
          <button class="btn btn-primary mt-2" @click="openCreateModal">
            <i class="fas fa-plus me-2"></i>Creer un pointage
          </button>
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

  <!-- Create pointage modal -->
  <teleport to="body">
    <div v-if="showCreateModal" class="pcm-overlay" @click.self="closeCreateModal">
      <div class="pcm-dialog">
        <div class="pcm-header">
          <div>
            <h4 class="pcm-title"><i class="fas fa-clipboard-check me-2"></i>Saisie des pointages</h4>
            <p class="pcm-sub">Saisie groupee par departement. Selectionnez un departement puis chargez les agents.</p>
          </div>
          <button class="pcm-close" @click="closeCreateModal"><i class="fas fa-times"></i></button>
        </div>

        <div class="pcm-body">
          <!-- Step 1: Select department + date -->
          <div class="pcm-step1">
            <div class="pcm-form-row">
              <div class="pcm-field pcm-grow">
                <label class="pcm-label">Departement / Service <span class="text-danger">*</span></label>
                <select v-model="cm_department" class="pcm-select">
                  <option value="">-- Selectionner --</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                </select>
              </div>
              <div class="pcm-field">
                <label class="pcm-label">Date <span class="text-danger">*</span></label>
                <input type="date" v-model="cm_date" class="pcm-input">
              </div>
              <div class="pcm-field pcm-btn-field">
                <button type="button" class="pcm-btn-load" @click="cm_loadAgents" :disabled="cm_loadingAgents">
                  <span v-if="cm_loadingAgents" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-search me-1"></i> Charger
                </button>
              </div>
            </div>
          </div>

          <!-- Loading -->
          <div v-if="cm_loadingAgents" class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2 text-muted small">Chargement des agents...</p>
          </div>

          <!-- Step 2: Agents table -->
          <div v-else-if="cm_agentsLoaded && cm_agents.length > 0">
            <div class="pcm-info-bar">
              <i class="fas fa-info-circle me-1"></i>
              <strong>{{ cm_deptName }}</strong> — {{ cm_agents.length }} agent(s)
              <span v-if="cm_recordedCount > 0" class="pcm-badge-ok ms-2">{{ cm_recordedCount }} deja pointe(s)</span>
            </div>

            <!-- Errors -->
            <div v-if="cm_errors.length" class="pcm-errors">
              <ul><li v-for="(e, i) in cm_errors" :key="i">{{ e }}</li></ul>
            </div>

            <!-- Table -->
            <div class="pcm-table-wrap">
              <table class="pcm-table">
                <thead>
                  <tr>
                    <th style="width:30%">Agent</th>
                    <th style="width:20%">Arrivee</th>
                    <th style="width:20%">Sortie</th>
                    <th style="width:12%">Heures</th>
                    <th style="width:18%">Obs.</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="a in cm_agents" :key="a.id" :class="{ 'pcm-row-ok': a.pointage_existant }">
                    <td>
                      <div class="pcm-agent-name">{{ a.prenom }} {{ a.nom }}
                        <span v-if="a.pointage_existant" class="pcm-badge-ok" style="font-size:.55rem;"><i class="fas fa-check"></i></span>
                      </div>
                    </td>
                    <td><input type="time" v-model="a.heure_entree" class="pcm-time-input"></td>
                    <td><input type="time" v-model="a.heure_sortie" class="pcm-time-input"></td>
                    <td class="text-muted">{{ cm_calcHours(a) }}</td>
                    <td><input type="text" v-model="a.observations" class="pcm-obs-input" placeholder="..."></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Quick actions -->
            <div class="pcm-quick-actions">
              <button type="button" class="pcm-btn-quick" @click="cm_fillAll">
                <i class="fas fa-clock me-1"></i> Remplir tout (08:00 - 16:00)
              </button>
              <button type="button" class="pcm-btn-quick pcm-btn-quick-danger" @click="cm_clearAll">
                <i class="fas fa-eraser me-1"></i> Effacer
              </button>
            </div>
          </div>

          <!-- No agents -->
          <div v-else-if="cm_agentsLoaded && cm_agents.length === 0" class="pcm-empty">
            <i class="fas fa-users-slash fa-2x mb-2"></i>
            <p>Aucun agent actif dans ce departement</p>
          </div>

          <!-- Initial state -->
          <div v-else-if="!cm_loadingAgents" class="pcm-empty">
            <i class="fas fa-building fa-2x mb-2"></i>
            <p>Selectionnez un departement et une date puis cliquez "Charger"</p>
          </div>
        </div>

        <div class="pcm-footer">
          <button v-if="cm_agentsLoaded && cm_agents.length > 0" type="button" class="pcm-btn-save" @click="cm_submit" :disabled="cm_submitting">
            <span v-if="cm_submitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-save me-1"></i> Enregistrer les pointages
          </button>
          <button class="pcm-btn-cancel" @click="closeCreateModal">Fermer</button>
        </div>
      </div>
    </div>
  </teleport>
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

// ── Create pointage modal ──
const showCreateModal = ref(false)
const cm_department = ref('')
const cm_date = ref(new Date().toISOString().split('T')[0])
const cm_agents = ref([])
const cm_agentsLoaded = ref(false)
const cm_loadingAgents = ref(false)
const cm_submitting = ref(false)
const cm_errors = ref([])

const cm_deptName = computed(() => {
    const d = departments.value.find(x => x.id == cm_department.value)
    return d ? d.nom : ''
})
const cm_recordedCount = computed(() => cm_agents.value.filter(a => a.pointage_existant).length)

function openCreateModal() {
    cm_department.value = ''
    cm_date.value = new Date().toISOString().split('T')[0]
    cm_agents.value = []
    cm_agentsLoaded.value = false
    cm_errors.value = []
    showCreateModal.value = true
}
function closeCreateModal() { showCreateModal.value = false }

function cm_calcHours(a) {
    if (!a.heure_entree || !a.heure_sortie) return '-'
    const e = new Date(`2000-01-01T${a.heure_entree}`)
    const s = new Date(`2000-01-01T${a.heure_sortie}`)
    const diff = (s - e) / (1000 * 60 * 60)
    return diff > 0 ? diff.toFixed(1) + 'h' : '-'
}

function cm_fillAll() {
    cm_agents.value.forEach(a => {
        if (!a.heure_entree) a.heure_entree = '08:00'
        if (!a.heure_sortie) a.heure_sortie = '16:00'
    })
}
function cm_clearAll() {
    cm_agents.value.forEach(a => { a.heure_entree = ''; a.heure_sortie = ''; a.observations = '' })
}

async function cm_loadAgents() {
    if (cm_department.value === '') { ui.addToast('Selectionnez un departement.', 'warning'); return }
    if (!cm_date.value) { ui.addToast('Selectionnez une date.', 'warning'); return }
    cm_loadingAgents.value = true
    cm_agentsLoaded.value = false
    cm_errors.value = []
    try {
        const { data } = await pointagesApi.agentsByDepartment({ department_id: cm_department.value, date: cm_date.value })
      const agentsData = data.data || data.agents || []
      cm_agents.value = agentsData.map(a => ({
            ...a,
            heure_entree: a.pointage_existant?.heure_entree || '',
            heure_sortie: a.pointage_existant?.heure_sortie || '',
            observations: a.pointage_existant?.observations || '',
        }))
        cm_agentsLoaded.value = true
    } catch {
        ui.addToast('Erreur lors du chargement des agents.', 'danger')
    } finally {
        cm_loadingAgents.value = false
    }
}

async function cm_submit() {
    cm_errors.value = []
    const pointagesData = cm_agents.value
        .filter(a => a.heure_entree || a.heure_sortie)
        .map(a => ({ agent_id: a.id, heure_entree: a.heure_entree || null, heure_sortie: a.heure_sortie || null, observations: a.observations || null }))
    if (pointagesData.length === 0) { ui.addToast('Saisissez au moins une heure.', 'warning'); return }
    cm_submitting.value = true
    try {
        const { data } = await pointagesApi.storeBulk({ date_pointage: cm_date.value, pointages: pointagesData })
        ui.addToast(data.message || 'Pointages enregistres.', 'success')
        closeCreateModal()
        fetchPointages(meta.value.current_page)
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            cm_errors.value = Object.values(err.response.data.errors).flat()
        } else {
            ui.addToast('Erreur lors de l\'enregistrement.', 'danger')
        }
    } finally {
        cm_submitting.value = false
    }
}
</script>

<style scoped>
/* ── Create Pointage Modal (pcm-*) ── */
.pcm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(15,23,42,.55); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: pcmFade .2s ease;
}
@keyframes pcmFade { from { opacity: 0 } to { opacity: 1 } }
@keyframes pcmSlide { from { opacity: 0; transform: translateY(24px) } to { opacity: 1; transform: translateY(0) } }

.pcm-dialog {
  background: #fff; border-radius: 18px; width: 100%; max-width: 780px;
  box-shadow: 0 24px 48px rgba(0,0,0,.18);
  animation: pcmSlide .25s ease;
  display: flex; flex-direction: column; max-height: 92vh;
}

.pcm-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #0077B5 0%, #005885 100%);
  border-radius: 18px 18px 0 0; color: #fff;
}
.pcm-title { font-size: 1.1rem; font-weight: 700; margin: 0; }
.pcm-sub { font-size: .75rem; opacity: .85; margin: .2rem 0 0; }
.pcm-close {
  background: rgba(255,255,255,.15); border: none; color: #fff;
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .2s; flex-shrink: 0;
}
.pcm-close:hover { background: rgba(255,255,255,.3); }

.pcm-body { padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; }

/* Step 1 */
.pcm-form-row { display: flex; gap: .75rem; align-items: flex-end; }
.pcm-grow { flex: 1; }
.pcm-field { display: flex; flex-direction: column; }
.pcm-btn-field { justify-content: flex-end; }
.pcm-label { font-size: .75rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
.pcm-input, .pcm-select {
  border: 1.5px solid #e2e8f0; border-radius: 10px; padding: .5rem .7rem;
  font-size: .82rem; color: #1e293b; background: #fff; transition: border-color .2s;
}
.pcm-input:focus, .pcm-select:focus {
  outline: none; border-color: #0077B5; box-shadow: 0 0 0 3px rgba(0,119,181,.1);
}
.pcm-btn-load {
  padding: .5rem 1rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: none; background: #0077B5; color: #fff; cursor: pointer; transition: all .2s;
  white-space: nowrap;
}
.pcm-btn-load:hover:not(:disabled) { background: #005885; }
.pcm-btn-load:disabled { opacity: .6; cursor: not-allowed; }

/* Info bar */
.pcm-info-bar {
  background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
  padding: .5rem .85rem; font-size: .78rem; color: #1e40af; margin: .75rem 0;
}
.pcm-badge-ok {
  display: inline-block; padding: 1px 6px; border-radius: 8px;
  font-size: .65rem; font-weight: 700; background: #dcfce7; color: #166534;
}

/* Errors */
.pcm-errors {
  background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
  padding: .5rem .85rem; font-size: .78rem; color: #991b1b; margin-bottom: .5rem;
}
.pcm-errors ul { margin: 0; padding-left: 1.2rem; }

/* Table */
.pcm-table-wrap { overflow-x: auto; max-height: 45vh; overflow-y: auto; }
.pcm-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.pcm-table thead { position: sticky; top: 0; z-index: 1; }
.pcm-table th {
  background: #f1f5f9; color: #475569; text-align: left;
  padding: .45rem .5rem; font-size: .68rem; text-transform: uppercase; font-weight: 700;
}
.pcm-table td { padding: .4rem .5rem; border-bottom: 1px solid #f1f5f9; }
.pcm-row-ok { background: #f0fdf4; }
.pcm-agent-name { font-weight: 600; font-size: .8rem; white-space: nowrap; }
.pcm-time-input {
  border: 1.5px solid #e2e8f0; border-radius: 8px; padding: .3rem .5rem;
  font-size: .8rem; width: 100%; transition: border-color .2s;
}
.pcm-time-input:focus { outline: none; border-color: #0077B5; }
.pcm-obs-input {
  border: 1.5px solid #e2e8f0; border-radius: 8px; padding: .3rem .5rem;
  font-size: .78rem; width: 100%; transition: border-color .2s;
}
.pcm-obs-input:focus { outline: none; border-color: #0077B5; }

/* Quick actions */
.pcm-quick-actions { display: flex; gap: .5rem; margin-top: .75rem; }
.pcm-btn-quick {
  padding: .35rem .75rem; border-radius: 8px; font-size: .72rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer;
  transition: all .2s;
}
.pcm-btn-quick:hover { background: #f1f5f9; }
.pcm-btn-quick-danger { color: #dc2626; border-color: #fecaca; }
.pcm-btn-quick-danger:hover { background: #fef2f2; }

/* Empty */
.pcm-empty { text-align: center; padding: 2rem; color: #94a3b8; }
.pcm-empty i { display: block; opacity: .5; }
.pcm-empty p { margin: 0; font-size: .82rem; }

/* Footer */
.pcm-footer {
  display: flex; gap: .75rem; justify-content: flex-end;
  padding: .85rem 1.5rem; border-top: 1px solid #f3f4f6;
}
.pcm-btn-save {
  padding: .5rem 1.2rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: none; background: #0077B5; color: #fff; cursor: pointer; transition: all .2s;
}
.pcm-btn-save:hover:not(:disabled) { background: #005885; }
.pcm-btn-save:disabled { opacity: .6; cursor: not-allowed; }
.pcm-btn-cancel {
  padding: .5rem 1.2rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .2s;
}
.pcm-btn-cancel:hover { background: #f3f4f6; }

/* ── Existing responsive ── */
@media (max-width: 767.98px) {
    .rh-table th:nth-child(2), .rh-table td:nth-child(2),
    .rh-table th:nth-child(4), .rh-table td:nth-child(4),
    .rh-table th:nth-child(5), .rh-table td:nth-child(5) { display: none; }
    .rh-table th, .rh-table td { padding: .4rem .3rem; font-size: .76rem; }
    .rh-table th { font-size: .65rem; }
    .btn-group-sm .btn { padding: .2rem .35rem; font-size: .68rem; }
    .rh-list-card .row.g-3 > [class*="col-md"] { flex: 0 0 100%; max-width: 100%; }
    .d-flex.gap-2.mb-3 .btn { font-size: .78rem; padding: .3rem .55rem; }

    .pcm-dialog { max-width: 100%; border-radius: 14px; }
    .pcm-header { padding: 1rem; border-radius: 14px 14px 0 0; }
    .pcm-body { padding: 1rem; }
    .pcm-form-row { flex-direction: column; }
    .pcm-footer { padding: .75rem 1rem; }
    .pcm-quick-actions { flex-wrap: wrap; }
    .pcm-table th:nth-child(5), .pcm-table td:nth-child(5) { display: none; }
}

@media (max-width: 575.98px) {
    .rh-table th:nth-child(6), .rh-table td:nth-child(6) { display: none; }
    .rh-table th, .rh-table td { padding: .35rem .25rem; font-size: .72rem; }
    .rh-table th { font-size: .62rem; }
    .btn-group-sm .btn { padding: .18rem .3rem; font-size: .65rem; }

    .pcm-table th:nth-child(4), .pcm-table td:nth-child(4) { display: none; }
}
</style>
