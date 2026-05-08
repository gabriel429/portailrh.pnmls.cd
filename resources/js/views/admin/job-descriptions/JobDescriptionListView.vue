<template>
  <div class="jd-page">
    <div class="jd-header">
      <div>
        <div class="jd-eyebrow">Paramètres d’administration</div>
        <h1>Job Description</h1>
        <p>Attribuez des missions, responsabilités et compétences attendues à chaque fonction ou poste.</p>
      </div>
      <button class="jd-primary-btn" type="button" @click="startCreate">
        <i class="fas fa-plus-circle"></i>
        Nouvelle Job Description
      </button>
    </div>

    <div class="jd-stats">
      <div class="jd-stat">
        <span>{{ summary.total }}</span>
        <small>Total créées</small>
      </div>
      <div class="jd-stat">
        <span>{{ summary.actives }}</span>
        <small>Actives</small>
      </div>
      <div class="jd-stat">
        <span>{{ summary.fonctions_couvertes }}</span>
        <small>Fonctions couvertes</small>
      </div>
    </div>

    <div class="jd-toolbar">
      <div class="jd-search">
        <i class="fas fa-search"></i>
        <input
          v-model="filters.search"
          type="search"
          placeholder="Rechercher une fonction, une mission ou un service..."
          @input="debouncedFetch"
        >
      </div>
      <select v-model="filters.fonction_id" @change="resetPageAndFetch">
        <option value="">Toutes les fonctions</option>
        <option v-for="fonction in fonctions" :key="fonction.id" :value="fonction.id">
          {{ fonction.nom }}
        </option>
      </select>
      <select v-model="filters.actif" @change="resetPageAndFetch">
        <option value="">Tous les statuts</option>
        <option value="1">Actives</option>
        <option value="0">Inactives</option>
      </select>
    </div>

    <div v-if="coveredFunctions.length" class="jd-covered">
      <div class="jd-covered-title">Fonctions ayant déjà une Job Description</div>
      <div class="jd-covered-list">
        <button
          v-for="fonction in coveredFunctions"
          :key="fonction.id"
          class="jd-covered-chip"
          type="button"
          @click="filterByFunction(fonction.id)"
        >
          {{ fonction.nom }}
          <span>{{ fonction.job_descriptions_count }}</span>
        </button>
      </div>
    </div>

    <div class="jd-grid">
      <section class="jd-panel">
        <div class="jd-panel-head">
          <div>
            <h2>Descriptions enregistrées</h2>
            <p>{{ total }} résultat{{ total > 1 ? 's' : '' }}</p>
          </div>
          <button class="jd-ghost-btn" type="button" @click="fetchData">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
          </button>
        </div>

        <div v-if="loading" class="jd-loading">
          <span class="spinner-border spinner-border-sm"></span>
          Chargement des descriptions...
        </div>

        <div v-else-if="error" class="jd-error">
          <i class="fas fa-exclamation-triangle"></i>
          {{ error }}
        </div>

        <div v-else-if="jobDescriptions.length" class="jd-table-wrap">
          <table class="jd-table">
            <thead>
              <tr>
                <th>Fonction / poste</th>
                <th>Job Description</th>
                <th>Entité concernée</th>
                <th>Mis à jour</th>
                <th>Statut</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="job in jobDescriptions" :key="job.id">
                <td>
                  <strong>{{ job.fonction?.nom || 'Fonction non renseignée' }}</strong>
                  <small>{{ niveauLabel(job.fonction?.niveau_administratif) }} · {{ typeLabel(job.fonction?.type_poste) }}</small>
                </td>
                <td>
                  <strong>{{ job.titre }}</strong>
                  <small>{{ truncate(job.mission_principale, 90) || 'Mission principale non renseignée.' }}</small>
                </td>
                <td>{{ job.service_section_departement || '-' }}</td>
                <td>{{ formatDate(job.updated_at) }}</td>
                <td>
                  <span :class="['jd-status', job.actif ? 'active' : 'inactive']">
                    {{ job.actif ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="jd-row-actions">
                  <button type="button" title="Modifier" @click="editJob(job)">
                    <i class="fas fa-pen"></i>
                  </button>
                  <button type="button" title="Supprimer" class="danger" @click="deleteJob(job)">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="jd-empty">
          <i class="fas fa-clipboard-list"></i>
          <h3>Aucune Job Description trouvée</h3>
          <p>Créez une première description et associez-la à une fonction.</p>
        </div>

        <div v-if="lastPage > 1" class="jd-pagination">
          <button type="button" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
            <i class="fas fa-chevron-left"></i>
          </button>
          <span>Page {{ currentPage }} / {{ lastPage }}</span>
          <button type="button" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </section>

      <aside class="jd-form-panel">
        <div class="jd-panel-head compact">
          <div>
            <h2>{{ editingId ? 'Modifier la Job Description' : 'Créer une Job Description' }}</h2>
            <p>Les champs remplis seront visibles dans le profil de l’agent.</p>
          </div>
        </div>

        <form class="jd-form" @submit.prevent="submit">
          <div v-if="formErrors.length" class="jd-error">
            <i class="fas fa-exclamation-circle"></i>
            <div>
              <div v-for="message in formErrors" :key="message">{{ message }}</div>
            </div>
          </div>

          <label>
            Fonction / poste
            <select v-model="form.fonction_id" required>
              <option value="">Sélectionnez une fonction</option>
              <option v-for="fonction in fonctions" :key="fonction.id" :value="fonction.id">
                {{ fonction.nom }}
              </option>
            </select>
          </label>

          <label>
            Titre
            <input v-model.trim="form.titre" type="text" required placeholder="Ex. Description du poste de Directeur">
          </label>

          <label>
            Mission principale du poste
            <textarea v-model.trim="form.mission_principale" rows="3" placeholder="Décrivez la mission générale du poste."></textarea>
          </label>

          <label>
            Responsabilités principales
            <textarea v-model.trim="form.responsabilites_principales" rows="5" placeholder="Une responsabilité par ligne si possible."></textarea>
          </label>

          <label>
            Tâches spécifiques
            <textarea v-model.trim="form.taches_specifiques" rows="5" placeholder="Listez les tâches opérationnelles du poste."></textarea>
          </label>

          <label>
            Compétences attendues
            <textarea v-model.trim="form.competences_attendues" rows="4" placeholder="Compétences techniques, comportementales ou managériales."></textarea>
          </label>

          <label>
            Service, section ou département concerné
            <input v-model.trim="form.service_section_departement" type="text" placeholder="Ex. Département Planification">
          </label>

          <label class="jd-toggle">
            <input v-model="form.actif" type="checkbox">
            <span>Job Description active et visible côté agent</span>
          </label>

          <div class="jd-form-actions">
            <button class="jd-primary-btn" type="submit" :disabled="saving">
              <i v-if="saving" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-save"></i>
              {{ editingId ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
            <button class="jd-secondary-btn" type="button" @click="resetForm">
              Annuler
            </button>
          </div>
        </form>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formErrors = ref([])
const jobDescriptions = ref([])
const fonctions = ref([])
const summary = ref({ total: 0, actives: 0, fonctions_couvertes: 0 })
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const editingId = ref(null)
let debounceTimer = null

const filters = reactive({
  search: '',
  fonction_id: '',
  actif: '',
})

const form = reactive({
  fonction_id: '',
  titre: '',
  mission_principale: '',
  responsabilites_principales: '',
  taches_specifiques: '',
  competences_attendues: '',
  service_section_departement: '',
  actif: true,
})

const coveredFunctions = computed(() => fonctions.value.filter((fonction) => Number(fonction.job_descriptions_count || 0) > 0))

async function fetchOptions() {
  const { data } = await client.get('/admin/job-descriptions/options')
  fonctions.value = data.fonctions || []
  if (data.summary) summary.value = data.summary
}

async function fetchData() {
  loading.value = true
  error.value = ''
  try {
    const params = { page: currentPage.value, per_page: 20 }
    if (filters.search) params.search = filters.search
    if (filters.fonction_id) params.fonction_id = filters.fonction_id
    if (filters.actif !== '') params.actif = filters.actif

    const { data } = await client.get('/admin/job-descriptions', { params })
    jobDescriptions.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    total.value = data.total || 0
    if (data.summary) summary.value = data.summary
  } catch (err) {
    error.value = err.response?.data?.message || 'Impossible de charger les Job Descriptions.'
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(resetPageAndFetch, 350)
}

function resetPageAndFetch() {
  currentPage.value = 1
  fetchData()
}

function filterByFunction(id) {
  filters.fonction_id = id
  resetPageAndFetch()
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchData()
}

function startCreate() {
  resetForm()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function editJob(job) {
  editingId.value = job.id
  form.fonction_id = job.fonction_id || ''
  form.titre = job.titre || ''
  form.mission_principale = job.mission_principale || ''
  form.responsabilites_principales = job.responsabilites_principales || ''
  form.taches_specifiques = job.taches_specifiques || ''
  form.competences_attendues = job.competences_attendues || ''
  form.service_section_departement = job.service_section_departement || ''
  form.actif = !!job.actif
  formErrors.value = []
}

function resetForm() {
  editingId.value = null
  form.fonction_id = ''
  form.titre = ''
  form.mission_principale = ''
  form.responsabilites_principales = ''
  form.taches_specifiques = ''
  form.competences_attendues = ''
  form.service_section_departement = ''
  form.actif = true
  formErrors.value = []
}

async function submit() {
  saving.value = true
  formErrors.value = []
  try {
    const payload = { ...form, actif: form.actif ? 1 : 0 }
    const request = editingId.value
      ? client.put(`/admin/job-descriptions/${editingId.value}`, payload)
      : client.post('/admin/job-descriptions', payload)

    const { data } = await request
    ui.addToast(data.message || 'Job Description enregistrée.', 'success')
    resetForm()
    await fetchOptions()
    await fetchData()
  } catch (err) {
    if (err.response?.status === 422 && err.response.data?.errors) {
      formErrors.value = Object.values(err.response.data.errors).flat()
    } else {
      formErrors.value = [err.response?.data?.message || 'Erreur lors de l’enregistrement.']
    }
  } finally {
    saving.value = false
  }
}

async function deleteJob(job) {
  if (!confirm(`Supprimer la Job Description « ${job.titre} » ?`)) return
  try {
    const { data } = await client.delete(`/admin/job-descriptions/${job.id}`)
    ui.addToast(data.message || 'Job Description supprimée.', 'success')
    await fetchOptions()
    await fetchData()
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Suppression impossible.', 'danger')
  }
}

function niveauLabel(value) {
  return ({ SEN: 'SEN', SEP: 'SEP', SEL: 'SEL', TOUS: 'Tous niveaux' })[value] || value || '-'
}

function typeLabel(value) {
  return (value || '-').toString().replace(/_/g, ' ')
}

function truncate(value, length) {
  if (!value) return ''
  return value.length > length ? value.slice(0, length) + '...' : value
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(async () => {
  await fetchOptions()
  await fetchData()
})
</script>

<style scoped>
.jd-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.jd-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  padding: 1.35rem;
  border-radius: 8px;
  background: linear-gradient(135deg, #0f766e 0%, #0e7490 100%);
  color: #fff;
}
.jd-eyebrow {
  font-size: .76rem;
  text-transform: uppercase;
  font-weight: 700;
  opacity: .8;
}
.jd-header h1 {
  margin: .15rem 0;
  font-size: 1.55rem;
  font-weight: 800;
}
.jd-header p {
  margin: 0;
  color: rgba(255, 255, 255, .82);
  max-width: 720px;
}
.jd-primary-btn,
.jd-secondary-btn,
.jd-ghost-btn,
.jd-covered-chip,
.jd-row-actions button,
.jd-pagination button {
  border: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .45rem;
  cursor: pointer;
}
.jd-primary-btn {
  min-height: 42px;
  padding: .65rem 1rem;
  border-radius: 8px;
  background: #0f172a;
  color: #fff;
  font-weight: 800;
  white-space: nowrap;
}
.jd-primary-btn:disabled {
  opacity: .7;
  cursor: not-allowed;
}
.jd-secondary-btn {
  min-height: 42px;
  padding: .65rem 1rem;
  border-radius: 8px;
  background: #f1f5f9;
  color: #475569;
  font-weight: 700;
}
.jd-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .75rem;
}
.jd-stat {
  padding: 1rem;
  border-radius: 8px;
  background: #fff;
  border: 1px solid #e2e8f0;
}
.jd-stat span {
  display: block;
  font-size: 1.55rem;
  font-weight: 900;
  color: #0f766e;
}
.jd-stat small {
  color: #64748b;
  font-weight: 700;
}
.jd-toolbar {
  display: grid;
  grid-template-columns: minmax(240px, 1fr) minmax(180px, 260px) minmax(150px, 180px);
  gap: .75rem;
  align-items: center;
}
.jd-search {
  position: relative;
}
.jd-search i {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}
.jd-toolbar input,
.jd-toolbar select,
.jd-form input,
.jd-form select,
.jd-form textarea {
  width: 100%;
  border: 1px solid #dbe3ee;
  border-radius: 8px;
  background: #fff;
  color: #0f172a;
  font-size: .9rem;
  padding: .7rem .85rem;
}
.jd-search input {
  padding-left: 2.35rem;
}
.jd-form textarea {
  resize: vertical;
  line-height: 1.45;
}
.jd-covered,
.jd-panel,
.jd-form-panel {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
}
.jd-covered {
  padding: 1rem;
}
.jd-covered-title {
  font-size: .86rem;
  font-weight: 800;
  color: #334155;
  margin-bottom: .75rem;
}
.jd-covered-list {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
}
.jd-covered-chip {
  border-radius: 999px;
  padding: .45rem .75rem;
  background: #ecfeff;
  color: #155e75;
  font-size: .82rem;
  font-weight: 700;
}
.jd-covered-chip span {
  min-width: 22px;
  height: 22px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #cffafe;
}
.jd-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(320px, 420px);
  gap: 1rem;
  align-items: start;
}
.jd-panel,
.jd-form-panel {
  overflow: hidden;
}
.jd-form-panel {
  position: sticky;
  top: 84px;
}
.jd-panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: .75rem;
  padding: 1rem 1.1rem;
  border-bottom: 1px solid #e2e8f0;
}
.jd-panel-head.compact {
  align-items: flex-start;
}
.jd-panel-head h2 {
  margin: 0;
  font-size: 1rem;
  font-weight: 850;
  color: #0f172a;
}
.jd-panel-head p {
  margin: .15rem 0 0;
  color: #64748b;
  font-size: .82rem;
}
.jd-ghost-btn {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: #f8fafc;
  color: #475569;
}
.jd-loading,
.jd-error,
.jd-empty {
  margin: 1rem;
  padding: 1rem;
  border-radius: 8px;
}
.jd-loading {
  color: #475569;
  background: #f8fafc;
}
.jd-error {
  display: flex;
  gap: .65rem;
  align-items: flex-start;
  color: #991b1b;
  background: #fef2f2;
  border: 1px solid #fecaca;
}
.jd-empty {
  text-align: center;
  border: 1px dashed #cbd5e1;
  color: #64748b;
}
.jd-empty i {
  font-size: 2rem;
  color: #94a3b8;
}
.jd-empty h3 {
  margin: .65rem 0 .25rem;
  color: #334155;
  font-size: 1rem;
}
.jd-table-wrap {
  overflow-x: auto;
}
.jd-table {
  width: 100%;
  border-collapse: collapse;
}
.jd-table th {
  padding: .85rem 1rem;
  background: #f8fafc;
  color: #64748b;
  font-size: .72rem;
  text-transform: uppercase;
  font-weight: 800;
  white-space: nowrap;
}
.jd-table td {
  padding: .9rem 1rem;
  border-top: 1px solid #edf2f7;
  vertical-align: top;
  color: #334155;
  font-size: .88rem;
}
.jd-table td strong,
.jd-table td small {
  display: block;
}
.jd-table td small {
  margin-top: .25rem;
  color: #64748b;
}
.jd-status {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  border-radius: 999px;
  padding: .25rem .65rem;
  font-size: .74rem;
  font-weight: 800;
}
.jd-status.active {
  background: #dcfce7;
  color: #166534;
}
.jd-status.inactive {
  background: #f1f5f9;
  color: #64748b;
}
.jd-row-actions {
  white-space: nowrap;
}
.jd-row-actions button {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f8fafc;
  color: #475569;
  margin-left: .35rem;
}
.jd-row-actions button:hover {
  background: #e0f2fe;
  color: #0369a1;
}
.jd-row-actions button.danger:hover {
  background: #fee2e2;
  color: #dc2626;
}
.jd-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: .75rem;
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
}
.jd-pagination button {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: #f8fafc;
  color: #334155;
}
.jd-pagination button:disabled {
  opacity: .45;
  cursor: not-allowed;
}
.jd-form {
  display: flex;
  flex-direction: column;
  gap: .85rem;
  padding: 1rem;
}
.jd-form label {
  display: flex;
  flex-direction: column;
  gap: .38rem;
  color: #334155;
  font-size: .82rem;
  font-weight: 800;
}
.jd-toggle {
  flex-direction: row !important;
  align-items: center;
  gap: .65rem !important;
  padding: .8rem;
  border-radius: 8px;
  background: #f8fafc;
}
.jd-toggle input {
  width: 18px;
  height: 18px;
}
.jd-form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .65rem;
  padding-top: .35rem;
}

@media (max-width: 1100px) {
  .jd-grid {
    grid-template-columns: 1fr;
  }
  .jd-form-panel {
    position: static;
  }
}
@media (max-width: 760px) {
  .jd-header,
  .jd-toolbar {
    grid-template-columns: 1fr;
  }
  .jd-header {
    display: grid;
  }
  .jd-stats {
    grid-template-columns: 1fr;
  }
  .jd-primary-btn,
  .jd-secondary-btn {
    width: 100%;
  }
}
</style>
