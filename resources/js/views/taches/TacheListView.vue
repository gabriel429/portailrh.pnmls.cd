<template>
  <div class="rh-modern taches-modern">
    <div class="rh-shell taches-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>{{ pageTitle }}</h1>
            <p class="rh-sub">{{ pageSubtitle }}</p>
          </div>
          <div v-if="canManageTaches" class="col-lg-4">
            <div class="hero-tools">
              <template v-if="auth.isSENA">
                <router-link v-if="!isSENScope" :to="{ name: 'taches.index', query: { scope: 'sen' } }" class="btn-rh alt">
                  <i class="fas fa-users me-1"></i> Tâches sous mon suivi
                </router-link>
                <router-link v-else :to="{ name: 'taches.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Mes tâches
                </router-link>
              </template>
              <template v-else-if="auth.isSEP">
                <router-link v-if="!isProvinceScope" :to="{ name: 'taches.index', query: { scope: 'province' } }" class="btn-rh alt">
                  <i class="fas fa-map-marker-alt me-1"></i> Tâches de la province
                </router-link>
                <router-link v-else :to="{ name: 'taches.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Mes tâches
                </router-link>
              </template>
              <template v-else>
                <router-link v-if="!showAssignedByMe" :to="{ name: 'taches.assigned-by-me' }" class="btn-rh alt">
                  <i class="fas fa-clipboard-list me-1"></i> Tâches assignées par moi
                </router-link>
                <router-link v-else :to="{ name: 'taches.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour à mes tâches
                </router-link>
              </template>
              <button type="button" class="btn-rh main" @click="openCreateModal">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle tâche
              </button>
            </div>
          </div>
        </div>
      </section>

      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement des tâches..." />
      </div>

      <template v-else>
        <div class="tache-status-tabs mt-3">
          <button
            v-for="tab in statusTabs"
            :key="tab.key"
            class="tache-tab"
            :class="{ active: statusFilter === tab.key, [tab.color]: true }"
            @click="statusFilter = tab.key"
          >
            <i :class="tab.icon + ' me-1'"></i>
            <span class="tache-tab-label">{{ tab.label }}</span>
            <span class="tache-tab-count">{{ tab.count }}</span>
          </button>
        </div>

        <div class="dash-panel mt-2">
          <header class="panel-head">
            <div>
              <h3 class="panel-title">
                <i :class="showAssignedByMe ? 'fas fa-clipboard-list me-2 text-success' : 'fas fa-clipboard-check me-2 text-primary'"></i>
                {{ panelTitle }}
              </h3>
              <p class="panel-sub">{{ panelSubtitle }}</p>
            </div>
            <div class="task-filters">
              <label for="source-filter" class="task-filter-label">Source</label>
              <select id="source-filter" v-model="sourceFilter" class="form-select form-select-sm task-filter-select">
                <option value="all">Toutes</option>
                <option value="direction">Direction</option>
                <option value="sen">SEN</option>
                <option value="sep">SEP</option>
                <option value="secom">SECOM</option>
                <option value="sel">SEL</option>
                <option value="aaf_local">AAF / RH local</option>
                <option value="autre">Autre</option>
              </select>
            </div>
          </header>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Origine</th>
                  <th>{{ showAssignedByMe || isDeptScope || isSENScope || isProvinceScope ? 'Assigne a' : 'De' }}</th>
                  <th>Priorite</th>
                  <th>Statut</th>
                  <th>Échéance</th>
                  <th>Date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in filteredTaches" :key="t.id">
                  <td>
                    <strong>{{ t.titre }}</strong>
                    <br v-if="t.description"><small class="text-muted">{{ truncate(t.description, 60) }}</small>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">{{ sourceTypeLabel(t.source_type) }}</span>
                    <br><small class="text-muted">{{ sourceEmetteurLabel(t.source_emetteur) }}</small>
                    <template v-if="t.activite_plan">
                      <br><small class="text-muted">{{ t.activite_plan.titre }}</small>
                    </template>
                  </td>
                  <td>{{ (showAssignedByMe || isDeptScope || isSENScope || isProvinceScope) ? (t.agent?.nom_complet ?? '-') : (t.createur?.nom_complet ?? '-') }}</td>
                  <td><span :class="prioriteBadge(t.priorite)">{{ capitalize(t.priorite) }}</span></td>
                  <td><span :class="statutBadge(t.statut)">{{ statutLabel(t.statut) }}</span></td>
                  <td>
                    <template v-if="t.date_echeance">
                      {{ formatDate(t.date_echeance) }}
                      <br v-if="isOverdue(t)"><small class="text-danger">En retard</small>
                    </template>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>{{ formatDate(t.created_at) }}</td>
                  <td>
                    <router-link :to="{ name: 'taches.show', params: { id: t.id } }" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i>
                    </router-link>
                  </td>
                </tr>
                <tr v-if="!filteredTaches.length">
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    {{ emptyStateText }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>

    <div v-if="createModalOpen" class="task-modal-overlay" @click.self="closeCreateModal">
      <div class="task-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="task-create-title">
        <button type="button" class="task-modal-close" aria-label="Fermer" @click="closeCreateModal">
          <i class="fas fa-times"></i>
        </button>

        <header class="task-modal-header">
          <div class="task-modal-icon"><i class="fas fa-plus-circle"></i></div>
          <div>
            <h2 id="task-create-title">Nouvelle tâche</h2>
            <p>{{ createPageSubtitle }}</p>
          </div>
        </header>

        <div v-if="loadingCreateData" class="text-center py-5">
          <LoadingSpinner message="Chargement du formulaire..." />
        </div>

        <form v-else class="task-create-form" @submit.prevent="handleCreateSubmit">
          <div v-if="createErrors.length" class="alert alert-danger">
            <ul class="mb-0">
              <li v-for="(err, i) in createErrors" :key="i">{{ err }}</li>
            </ul>
          </div>

          <div class="task-form-grid">
            <div>
              <label for="task_source_type" class="form-label fw-bold">Origine <span class="text-danger">*</span></label>
              <select id="task_source_type" v-model="createForm.source_type" class="form-select" required>
                <option value="hors_pta">Hors PTA</option>
                <option value="pta">Issue du PTA</option>
              </select>
            </div>

            <div>
              <label for="task_source_emetteur" class="form-label fw-bold">Source <span class="text-danger">*</span></label>
              <select id="task_source_emetteur" v-model="createForm.source_emetteur" class="form-select" required>
                <option v-for="source in createSourceEmetteurs" :key="source.value" :value="source.value">
                  {{ source.label }}
                </option>
              </select>
            </div>

            <div>
              <label for="task_date_tache" class="form-label fw-bold">Date de la tâche</label>
              <input id="task_date_tache" v-model="createForm.date_tache" type="date" class="form-control">
            </div>

            <div v-if="createForm.source_type === 'pta'" class="task-form-full">
              <label for="task_activite_plan_id" class="form-label fw-bold">Activité PTA liée <span class="text-danger">*</span></label>
              <select id="task_activite_plan_id" v-model="createForm.activite_plan_id" class="form-select" required>
                <option value="">-- Choisir une activité du PTA --</option>
                <option v-for="activite in createActivitesPta" :key="activite.id" :value="activite.id">
                  {{ activiteLabel(activite) }}
                </option>
              </select>
            </div>

            <div class="task-form-wide">
              <label for="task_agent_id" class="form-label fw-bold">Assigner à <span class="text-danger">*</span></label>
              <select id="task_agent_id" v-model="createForm.agent_id" class="form-select" required>
                <option value="">-- Choisir un agent --</option>
                <option v-for="ag in createAgents" :key="ag.id" :value="ag.id">
                  {{ ag.prenom }} {{ ag.nom }} ({{ ag.matricule || 'N/A' }})
                </option>
              </select>
            </div>

            <div>
              <label for="task_priorite" class="form-label fw-bold">Priorité <span class="text-danger">*</span></label>
              <select id="task_priorite" v-model="createForm.priorite" class="form-select" required>
                <option value="faible">Faible</option>
                <option value="normale">Normale</option>
                <option value="haute">Haute</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>

            <div>
              <label for="task_date_echeance" class="form-label fw-bold">Échéance</label>
              <input id="task_date_echeance" v-model="createForm.date_echeance" type="date" class="form-control">
            </div>

            <div class="task-form-full">
              <label for="task_titre" class="form-label fw-bold">Titre de la tâche <span class="text-danger">*</span></label>
              <input id="task_titre" v-model="createForm.titre" type="text" class="form-control" required placeholder="Ex: Préparer le rapport mensuel">
            </div>

            <div class="task-form-full">
              <label for="task_description" class="form-label fw-bold">Description</label>
              <textarea id="task_description" v-model="createForm.description" class="form-control" rows="4" placeholder="Détails et instructions pour l'agent..."></textarea>
            </div>

            <div class="task-form-full">
              <label for="task_documents" class="form-label fw-bold">Documents joints</label>
              <input
                id="task_documents"
                ref="createDocumentsInput"
                type="file"
                class="form-control"
                multiple
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                @change="handleCreateDocumentsChange"
              >
              <small class="text-muted d-block mt-1">Vous pouvez joindre un ou plusieurs documents au moment de l'assignation.</small>
              <div v-if="createSelectedDocuments.length" class="task-file-list mt-2">
                <div v-for="(file, index) in createSelectedDocuments" :key="file.name + index" class="task-file-item">
                  <div>
                    <strong>{{ file.name }}</strong>
                    <small class="d-block text-muted">{{ formatFileSize(file.size) }}</small>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeCreateDocument(index)">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <footer class="task-modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="closeCreateModal">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="submittingCreate">
              <span v-if="submittingCreate" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-paper-plane me-1"></i> Assigner la tâche
            </button>
          </footer>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { create, getCreateData, list } from '@/api/taches'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()
const auth = useAuthStore()
const loading = ref(true)
const mesTaches = ref([])
const tachesCreees = ref([])
const isDirecteur = ref(false)
const canManageTaches = ref(false)
const sourceFilter = ref('all')
const statusFilter = ref(route.query.statut ?? 'all')
const createModalOpen = ref(false)
const loadingCreateData = ref(false)
const submittingCreate = ref(false)
const createErrors = ref([])
const createAgents = ref([])
const createActivitesPta = ref([])
const createSourceEmetteurs = ref([])
const createSelectedDocuments = ref([])
const createDocumentsInput = ref(null)
const createScopeFlags = ref({ isSENScope: false, isSENAScope: false, isProvinceScope: false, isLocalScope: false })
const createForm = ref(defaultCreateForm())

const isDeptScope = computed(() => route.query.scope === 'departement')
const isSENScope = computed(() => route.query.scope === 'sen')
const isProvinceScope = computed(() => route.query.scope === 'province')
const showAssignedByMe = computed(() => route.name === 'taches.assigned-by-me')

const createPageSubtitle = computed(() => {
  if (createScopeFlags.value.isLocalScope) return 'Assigner une tâche à un agent local de votre ressort.'
  if (createScopeFlags.value.isProvinceScope) return 'Assigner une tâche à un agent provincial et organiser son suivi.'
  if (createScopeFlags.value.isSENAScope) return 'Assigner une tâche uniquement aux attachés du SEN, aux directeurs de département et aux SEP suivis par le Secrétariat de direction.'
  if (createScopeFlags.value.isSENScope) return 'Assigner une tâche à un agent du Secrétariat exécutif national.'
  return 'Assigner une tâche à un agent de votre département.'
})

watch(() => route.query.statut, (val) => {
  statusFilter.value = val ?? 'all'
})

watch(() => route.fullPath, () => {
  loadTaches()
})

function isOverdue(tache) {
  if (!tache.date_echeance || tache.validation_statut === 'validee') return false
  return new Date(tache.date_echeance) < new Date()
}

const sourceFilteredTaches = computed(() => {
  const items = showAssignedByMe.value ? tachesCreees.value : mesTaches.value
  return items.filter((t) => matchesSourceFilter(t.source_emetteur))
})

const statusTabs = computed(() => {
  const items = sourceFilteredTaches.value
  return [
    { key: 'all', label: 'Toutes', icon: 'fas fa-list', color: 'tab-all', count: items.length },
    { key: 'nouvelle', label: 'En attente', icon: 'fas fa-star', color: 'tab-nouvelle', count: items.filter((t) => t.statut === 'nouvelle').length },
    { key: 'en_cours', label: 'En cours', icon: 'fas fa-spinner', color: 'tab-en-cours', count: items.filter((t) => t.statut === 'en_cours').length },
    { key: 'bloquee', label: 'Bloquees', icon: 'fas fa-ban', color: 'tab-bloquee', count: items.filter((t) => t.statut === 'bloquee').length },
    { key: 'terminee', label: 'Terminées', icon: 'fas fa-check-circle', color: 'tab-terminee', count: items.filter((t) => t.statut === 'terminee').length },
    { key: 'en_retard', label: 'En retard', icon: 'fas fa-exclamation-triangle', color: 'tab-en-retard', count: items.filter((t) => isOverdue(t)).length },
  ]
})

const filteredTaches = computed(() => {
  let items = sourceFilteredTaches.value
  if (statusFilter.value === 'en_retard') {
    items = items.filter((t) => isOverdue(t))
  } else if (statusFilter.value !== 'all') {
    items = items.filter((t) => t.statut === statusFilter.value)
  }
  return items
})

const pageTitle = computed(() => {
  if (isDeptScope.value) return 'Tâches du département'
  if (isSENScope.value) return auth.isSENA ? 'Tâches sous mon suivi' : 'Tâches du SEN'
  if (isProvinceScope.value) return 'Tâches de la province'
  return showAssignedByMe.value ? 'Tâches assignées par moi' : 'Mes tâches'
})

const pageSubtitle = computed(() => {
  if (isDeptScope.value) return 'Vue d’ensemble des tâches assignées aux agents du département.'
  if (isSENScope.value) return auth.isSENA
    ? 'Tâches des attachés du SEN, des directeurs de département et des SEP suivis par le Secrétariat de direction.'
    : 'Toutes les tâches assignées aux agents du Secrétariat exécutif national.'
  if (isProvinceScope.value) return 'Vue provinciale des tâches assignées aux agents de votre province.'
  return showAssignedByMe.value
    ? 'Suivi des tâches que vous attribuez aux agents de votre structure.'
    : 'Espace des tâches qui vous sont attribuées par votre direction, le SEN, le SEP ou le SEL.'
})

const panelTitle = computed(() => {
  if (isDeptScope.value) return 'Tâches du département'
  if (isSENScope.value) return auth.isSENA ? 'Tâches sous mon suivi' : 'Tâches du SEN'
  if (isProvinceScope.value) return 'Tâches de la province'
  return showAssignedByMe.value ? 'Tâches que j’ai assignées' : 'Tâches qui me sont assignées'
})

const panelSubtitle = computed(() => {
  if (isDeptScope.value) return 'Toutes les tâches assignées aux agents du département.'
  if (isSENScope.value) return auth.isSENA
    ? 'Vue d’ensemble des tâches autorisées dans votre périmètre SENA.'
    : 'Vue d’ensemble des tâches assignées aux agents du SEN.'
  if (isProvinceScope.value) return 'Toutes les tâches assignées aux agents de la province.'
  return showAssignedByMe.value
    ? 'Liste des tâches que vous avez affectées aux agents.'
    : 'Tâches attribuées par votre direction, le SEN, le SEP ou le SEL.'
})

const emptyStateText = computed(() => {
  if (isDeptScope.value) return 'Aucune tâche pour ce filtre dans ce département.'
  if (isSENScope.value) return auth.isSENA ? 'Aucune tâche dans votre périmètre SENA pour ce filtre.' : 'Aucune tâche SEN pour ce filtre.'
  if (isProvinceScope.value) return 'Aucune tâche pour ce filtre dans cette province.'
  return showAssignedByMe.value ? 'Aucune tâche assignée par vous pour ce filtre.' : 'Aucune tâche assignée pour ce filtre.'
})

async function loadTaches() {
  loading.value = true
  try {
    const params = isDeptScope.value
      ? { scope: 'departement' }
      : isSENScope.value
        ? { scope: 'sen' }
        : isProvinceScope.value
          ? { scope: 'province' }
          : {}

    const { data } = await list(params)
    mesTaches.value = data.mesTaches || []
    tachesCreees.value = data.tachesCreees || []
    isDirecteur.value = Boolean(data.isDirecteur)
    canManageTaches.value = Boolean(
      data.canManageTaches ||
      data.isDirecteur ||
      auth.isSENA ||
      auth.isAssistant ||
      auth.isSEP ||
      auth.user?.role?.nom_role === 'SEL'
    )
  } catch {
    ui.addToast('Erreur lors du chargement des tâches.', 'danger')
  } finally {
    loading.value = false
  }
}

function defaultCreateForm() {
  return {
    agent_id: '',
    titre: '',
    description: '',
    source_type: 'hors_pta',
    source_emetteur: 'directeur',
    activite_plan_id: '',
    priorite: 'normale',
    date_tache: '',
    date_echeance: '',
  }
}

async function openCreateModal() {
  createModalOpen.value = true
  createErrors.value = []
  createForm.value = defaultCreateForm()
  createSelectedDocuments.value = []
  if (createDocumentsInput.value) createDocumentsInput.value.value = ''
  await loadCreateData()
}

function closeCreateModal() {
  if (submittingCreate.value) return
  createModalOpen.value = false
  createErrors.value = []
  removeCreateQuery()
}

async function loadCreateData() {
  loadingCreateData.value = true
  try {
    const { data } = await getCreateData()
    createAgents.value = data.data.agents || []
    createActivitesPta.value = data.data.activites_pta || []
    createSourceEmetteurs.value = data.data.source_emetteurs || []
    createForm.value.source_emetteur = data.data.default_source_emetteur || 'directeur'
    createScopeFlags.value = {
      isSENScope: Boolean(data.data.isSENScope),
      isSENAScope: Boolean(data.data.isSENAScope),
      isProvinceScope: Boolean(data.data.isProvinceScope),
      isLocalScope: Boolean(data.data.isLocalScope),
    }
  } catch (err) {
    createModalOpen.value = false
    if (err.response?.status === 403) {
      ui.addToast('Accès refusé. Seuls les gestionnaires habilités peuvent créer des tâches.', 'danger')
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors du chargement du formulaire.', 'danger')
    }
    removeCreateQuery()
  } finally {
    loadingCreateData.value = false
  }
}

async function handleCreateSubmit() {
  createErrors.value = []
  submittingCreate.value = true
  try {
    const payload = new FormData()
    Object.entries(createForm.value).forEach(([key, value]) => {
      if (key === 'activite_plan_id' && createForm.value.source_type !== 'pta') return
      if (value !== null && value !== undefined && value !== '') payload.append(key, value)
    })

    createSelectedDocuments.value.forEach((file) => {
      payload.append('documents[]', file)
    })

    await create(payload)
    ui.addToast('Tâche créée avec succès.', 'success')
    createModalOpen.value = false
    removeCreateQuery()
    await loadTaches()
  } catch (err) {
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors || {}
      createErrors.value = Object.values(validationErrors).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de la création.', 'danger')
    }
  } finally {
    submittingCreate.value = false
  }
}

function handleCreateDocumentsChange(event) {
  createSelectedDocuments.value = Array.from(event.target.files || [])
}

function removeCreateDocument(index) {
  createSelectedDocuments.value.splice(index, 1)
  if (createDocumentsInput.value) {
    const dataTransfer = new DataTransfer()
    createSelectedDocuments.value.forEach((file) => dataTransfer.items.add(file))
    createDocumentsInput.value.files = dataTransfer.files
  }
}

function removeCreateQuery() {
  if (!route.query.create) return
  const query = { ...route.query }
  delete query.create
  router.replace({ name: route.name, query })
}

function formatFileSize(size) {
  if (!size) return '0 Ko'
  if (size >= 1024 * 1024) return `${(size / (1024 * 1024)).toFixed(1)} Mo`
  return `${Math.round(size / 1024)} Ko`
}

function activiteLabel(activite) {
  const trimestre = activite.trimestre ? ` (${activite.trimestre})` : ''
  return `${activite.titre} - ${activite.annee}${trimestre}`
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? `${str.substring(0, len)}...` : str
}

function prioriteBadge(priorite) {
  const map = {
    urgente: 'badge bg-danger',
    haute: 'badge bg-warning text-dark',
    normale: 'badge bg-secondary',
    faible: 'badge bg-info text-dark',
  }
  return map[priorite] || 'badge bg-secondary'
}

function statutBadge(statut) {
  const map = {
    terminee: 'badge bg-success',
    en_cours: 'badge bg-primary',
    nouvelle: 'badge bg-secondary',
    bloquee: 'badge bg-danger',
  }
  return map[statut] || 'badge bg-secondary'
}

function statutLabel(statut) {
  const map = {
    terminee: 'Terminée',
    en_cours: 'En cours',
    nouvelle: 'En attente',
    bloquee: 'Bloquee',
  }
  return map[statut] || capitalize(statut)
}

function sourceTypeLabel(sourceType) {
  return sourceType === 'pta' ? 'PTA' : 'Hors PTA'
}

function sourceEmetteurLabel(source) {
  const map = {
    directeur: 'Direction',
    assistant_departement: 'Direction',
    sen: 'SEN',
    sep: 'SEP',
    secom: 'SECOM',
    sel: 'SEL',
    aaf_local: 'AAF / RH local',
    autre: 'Autre',
  }
  return map[source] || source
}

function matchesSourceFilter(source) {
  if (sourceFilter.value === 'all') return true
  if (sourceFilter.value === 'direction') return ['directeur', 'assistant_departement'].includes(source)
  return source === sourceFilter.value
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => {
  if (route.query.statut) {
    const valid = ['nouvelle', 'en_cours', 'bloquee', 'terminee', 'en_retard']
    if (valid.includes(route.query.statut)) statusFilter.value = route.query.statut
  }
  loadTaches().then(() => {
    if (route.query.create) openCreateModal()
  })
})

watch(statusFilter, (val) => {
  const query = { ...route.query }
  if (val === 'all') {
    delete query.statut
  } else {
    query.statut = val
  }
  router.replace({ query })
})
</script>

<style scoped>
.taches-modern {
  width: 100%;
  max-width: none;
  padding: 1.5rem 1.5rem 2rem;
}

.taches-shell {
  width: 100%;
  max-width: none;
  padding: 0;
}

.taches-shell .rh-hero {
  position: relative;
  min-height: 200px;
  padding: 2rem 2.25rem;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, .42);
  border-radius: 18px;
  background: linear-gradient(135deg, #087ec0 0%, #3d9a87 100%);
  box-shadow: 0 18px 38px rgba(2, 93, 128, .18);
}

.taches-shell .rh-hero::after {
  right: -2rem;
  top: -3rem;
  width: 14rem;
  height: 14rem;
  opacity: .14;
}

.taches-shell .rh-hero .row {
  min-height: 132px;
}

.taches-shell .rh-title {
  margin: 0 0 .55rem;
  font-size: 1.6rem;
  line-height: 1.15;
  color: #fff;
}

.taches-shell .rh-sub {
  max-width: 760px;
  margin: 0;
  color: rgba(255, 255, 255, .86);
  font-weight: 600;
}

.taches-shell .hero-tools {
  align-items: flex-end;
  justify-content: flex-end;
  gap: .65rem;
}

.taches-shell .hero-tools .btn-rh {
  border: 1px solid rgba(255, 255, 255, .5);
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(14, 132, 197, .94), rgba(0, 125, 118, .94));
  box-shadow: 0 12px 28px rgba(6, 78, 107, .22);
}

.taches-shell .tache-status-tabs {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: .85rem;
  margin-top: 1.5rem !important;
}

.taches-shell .tache-tab {
  min-height: 74px;
  justify-content: flex-start;
  gap: .75rem;
  padding: 1rem;
  border: 1px solid rgba(148, 163, 184, .28);
  border-radius: 14px;
  background: rgba(255, 255, 255, .86);
  box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
  color: #334155;
}

.taches-shell .tache-tab i {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: #e0f2fe;
  color: #0277b5;
}

.taches-shell .tache-tab-label {
  flex: 1;
  text-align: left;
}

.taches-shell .tache-tab-count {
  min-width: 1.55rem;
  height: 1.55rem;
}

.taches-shell .tache-tab.active.tab-all {
  border-color: #0277b5;
  background: linear-gradient(135deg, #0277b5, #006494);
  color: #fff;
  box-shadow: 0 16px 36px rgba(0, 119, 181, .25);
}

.taches-shell .tache-tab.active.tab-all i,
.taches-shell .tache-tab.active i {
  background: rgba(255, 255, 255, .22);
  color: currentColor;
}

.taches-shell .dash-panel {
  margin-top: 1.5rem !important;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, .25);
  border-radius: 14px;
  background: rgba(255, 255, 255, .9);
  box-shadow: 0 16px 38px rgba(15, 23, 42, .08);
}

.taches-shell .panel-head {
  gap: 1rem;
  padding: 1.1rem 1.25rem;
}

.taches-shell .panel-title {
  margin-bottom: .2rem;
  color: #334155;
}

.taches-shell .table {
  margin-bottom: 0;
}

.taches-shell .table thead th {
  background: linear-gradient(135deg, rgba(224, 242, 254, .72), rgba(240, 253, 250, .72));
  color: #1e3a5f;
  font-size: .78rem;
  letter-spacing: .02em;
  text-transform: uppercase;
}

@media (max-width: 767.98px) {
  .taches-modern {
    padding: 1rem .75rem 1.5rem;
  }

  .taches-shell .rh-hero {
    min-height: auto;
    padding: 1.35rem 1rem;
    border-radius: 14px;
  }

  .rh-hero .row,
  .rh-hero .col-lg-4 {
    text-align: center;
  }

  .hero-tools {
    justify-content: center;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
  }

  .rh-title {
    font-size: 1.3rem;
  }

  .rh-sub {
    font-size: .85rem;
  }

  .panel-title {
    font-size: 1rem;
  }

  .panel-sub {
    font-size: .8rem;
  }

  .task-filters {
    width: 100%;
    justify-content: center;
  }

  .table {
    font-size: .82rem;
  }

  .table th,
  .table td {
    padding: .5rem .4rem;
  }

  .table td:last-child {
    white-space: nowrap;
  }

  .table th:nth-child(2),
  .table td:nth-child(2),
  .table th:nth-child(6),
  .table td:nth-child(6),
  .table th:nth-child(7),
  .table td:nth-child(7) {
    display: none;
  }

  .btn {
    padding: .25rem .4rem;
    font-size: .75rem;
  }

  .dash-panel {
    margin-top: .75rem !important;
  }
}

.task-filters {
  display: flex;
  align-items: center;
  gap: .6rem;
}

.task-filter-label {
  font-size: .82rem;
  font-weight: 700;
  color: #64748b;
}

.task-filter-select {
  min-width: 150px;
}

.tache-status-tabs {
  display: flex;
  gap: .5rem;
  flex-wrap: wrap;
}

.tache-tab {
  display: flex;
  align-items: center;
  gap: .35rem;
  padding: .5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: .6rem;
  background: #fff;
  font-size: .85rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all .2s;
}

.tache-tab:hover {
  border-color: #94a3b8;
  color: #334155;
}

.tache-tab-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.4rem;
  height: 1.4rem;
  padding: 0 .35rem;
  border-radius: 999px;
  font-size: .72rem;
  font-weight: 700;
  background: #e2e8f0;
  color: #475569;
}

.tache-tab.active.tab-all {
  border-color: #475569;
  color: #1e293b;
  background: #f1f5f9;
}

.tache-tab.active.tab-all .tache-tab-count {
  background: #475569;
  color: #fff;
}

.tache-tab.active.tab-nouvelle {
  border-color: #6366f1;
  color: #4338ca;
  background: #eef2ff;
}

.tache-tab.active.tab-nouvelle .tache-tab-count {
  background: #6366f1;
  color: #fff;
}

.tache-tab.active.tab-en-cours {
  border-color: #3b82f6;
  color: #1d4ed8;
  background: #eff6ff;
}

.tache-tab.active.tab-en-cours .tache-tab-count {
  background: #3b82f6;
  color: #fff;
}

.tache-tab.active.tab-bloquee {
  border-color: #ef4444;
  color: #b91c1c;
  background: #fff1f2;
}

.tache-tab.active.tab-bloquee .tache-tab-count {
  background: #ef4444;
  color: #fff;
}

.tache-tab.active.tab-terminee {
  border-color: #22c55e;
  color: #15803d;
  background: #f0fdf4;
}

.tache-tab.active.tab-terminee .tache-tab-count {
  background: #22c55e;
  color: #fff;
}

.tache-tab.active.tab-en-retard {
  border-color: #ef4444;
  color: #b91c1c;
  background: #fef2f2;
}

.tache-tab.active.tab-en-retard .tache-tab-count {
  background: #ef4444;
  color: #fff;
}

.task-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1060;
  display: grid;
  place-items: center;
  padding: 1rem;
  background: rgba(15, 23, 42, .48);
  backdrop-filter: blur(8px);
}

.task-modal-dialog {
  position: relative;
  width: min(940px, calc(100vw - 2rem));
  max-height: calc(100dvh - 2rem);
  overflow: auto;
  border-radius: 18px;
  background: rgba(255, 255, 255, .98);
  border: 1px solid rgba(226, 232, 240, .95);
  box-shadow: 0 28px 70px rgba(15, 23, 42, .28);
}

.task-modal-close {
  position: absolute;
  top: .85rem;
  right: .85rem;
  z-index: 2;
  width: 36px;
  height: 36px;
  border: 1px solid rgba(148, 163, 184, .35);
  border-radius: 10px;
  background: rgba(255, 255, 255, .9);
  color: #475569;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.task-modal-close:hover {
  color: #ef4444;
  border-color: #fecaca;
}

.task-modal-header {
  display: flex;
  align-items: flex-start;
  gap: .85rem;
  padding: 1.35rem 1.5rem 1rem;
  color: #fff;
  background:
    radial-gradient(ellipse at 90% 0%, rgba(255,255,255,.12) 0%, transparent 56%),
    linear-gradient(135deg, #0a1628 0%, #0f2847 34%, #0c4a6e 66%, #0077B5 100%);
}

.task-modal-icon {
  width: 44px;
  height: 44px;
  flex: 0 0 44px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, .16);
  border: 1px solid rgba(255, 255, 255, .22);
}

.task-modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
}

.task-modal-header p {
  margin: .2rem 2.4rem 0 0;
  color: rgba(255, 255, 255, .78);
  font-size: .86rem;
}

.task-create-form {
  padding: 1.2rem 1.5rem 1.35rem;
}

.task-form-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .95rem;
}

.task-form-wide {
  grid-column: span 2;
}

.task-form-full {
  grid-column: 1 / -1;
}

.task-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: .7rem;
  margin-top: 1.15rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.task-file-list {
  display: grid;
  gap: .65rem;
}

.task-file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  padding: .75rem .9rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
}

@media (max-width: 767.98px) {
  .tache-status-tabs {
    gap: .3rem;
  }

  .tache-tab {
    padding: .35rem .6rem;
    font-size: .75rem;
    border-radius: .45rem;
  }

  .tache-tab-label {
    display: none;
  }

  .tache-tab-count {
    font-size: .68rem;
    min-width: 1.2rem;
    height: 1.2rem;
  }

  .task-modal-overlay {
    padding: .5rem;
  }

  .task-modal-dialog {
    width: calc(100vw - 1rem);
    max-height: calc(100dvh - 1rem);
    border-radius: 14px;
  }

  .task-modal-header,
  .task-create-form {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .task-form-grid {
    grid-template-columns: 1fr;
  }

  .task-form-wide {
    grid-column: 1 / -1;
  }

  .task-modal-footer {
    display: grid;
    grid-template-columns: 1fr;
  }

  .task-modal-footer .btn {
    width: 100%;
  }
}
</style>
