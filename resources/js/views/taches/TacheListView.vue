<template>
  <div class="rh-modern taches-modern">
    <div class="rh-shell taches-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>{{ pageTitle }}</h1>
            <p class="rh-sub">{{ pageSubtitle }}</p>
          </div>
          <div v-if="canCreateTaches" class="col-lg-4">
            <div class="hero-tools">
              <template v-if="canManageTaches && auth.isSENA">
                <router-link v-if="!isSENScope" :to="{ name: 'taches.index', query: { scope: 'sen' } }" class="btn-rh alt">
                  <i class="fas fa-users me-1"></i> Tâches sous mon suivi
                </router-link>
                <router-link v-else :to="{ name: 'taches.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Mes tâches
                </router-link>
              </template>
              <template v-else-if="canManageTaches && auth.isSEP">
                <router-link v-if="!isProvinceScope" :to="{ name: 'taches.index', query: { scope: 'province' } }" class="btn-rh alt">
                  <i class="fas fa-map-marker-alt me-1"></i> Tâches de la province
                </router-link>
                <router-link v-else :to="{ name: 'taches.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Mes tâches
                </router-link>
              </template>
              <template v-else-if="canManageTaches || tachesCreees.length || showAssignedByMe">
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

      <div class="tache-status-tabs mt-3">
        <button
          v-for="tab in statusTabs"
          :key="tab.key"
          type="button"
          class="tache-tab"
          :class="{ active: statusFilter === tab.key, [tab.color]: true }"
          @click="setStatusFilter(tab.key)"
        >
          <i :class="tab.icon + ' me-1'"></i>
          <span class="tache-tab-label">{{ tab.label }}</span>
          <span class="tache-tab-count">{{ tab.count }}</span>
        </button>
      </div>

      <div id="agenda" class="dash-panel mt-2">
        <header class="panel-head">
          <div>
            <h3 class="panel-title">
              <i :class="showAssignedByMe ? 'fas fa-clipboard-list me-2 text-success' : 'fas fa-clipboard-check me-2 text-primary'"></i>
              {{ panelTitle }}
            </h3>
            <p class="panel-sub">{{ panelSubtitle }}</p>
          </div>
          <div class="task-panel-tools">
            <div class="task-view-toggle" role="group" aria-label="Mode d'affichage">
              <button type="button" :class="{ active: taskViewMode === 'list' }" @click="setTaskViewMode('list')">
                <i class="fas fa-list"></i>
                Liste
              </button>
              <button type="button" :class="{ active: taskViewMode === 'calendar' }" @click="setTaskViewMode('calendar')">
                <i class="fas fa-calendar-alt"></i>
                Calendrier
              </button>
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
          </div>
        </header>
        <div v-if="taskViewMode === 'calendar'" class="task-calendar-view" :aria-busy="loading">
          <div class="task-calendar-toolbar">
            <div>
              <span class="task-calendar-eyebrow">Agenda des échéances</span>
              <h4>{{ calendarMonthLabel }}</h4>
            </div>
            <div class="task-calendar-actions">
              <button type="button" @click="moveCalendar(-1)" title="Mois précédent">
                <i class="fas fa-chevron-left"></i>
              </button>
              <button type="button" class="task-calendar-today" @click="goToCurrentMonth">Aujourd'hui</button>
              <button type="button" @click="moveCalendar(1)" title="Mois suivant">
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>

          <div class="task-calendar-summary">
            <span><b>{{ filteredTaches.length }}</b> tâche(s)</span>
            <span><b>{{ calendarTasksInMonth.length }}</b> ce mois</span>
            <span><b>{{ calendarOverdueCount }}</b> en retard</span>
            <span><b>{{ calendarWithoutDeadlineCount }}</b> sans échéance</span>
          </div>

          <div v-if="loading" class="task-calendar-loading">
            <LoadingSpinner message="Chargement du calendrier..." />
          </div>

          <div v-else class="task-calendar-layout">
            <section class="task-calendar-board" aria-label="Calendrier des tâches">
              <div class="task-calendar-weekdays">
                <span v-for="day in calendarWeekdays" :key="day">{{ day }}</span>
              </div>
              <div class="task-calendar-grid">
                <button
                  v-for="day in calendarDays"
                  :key="day.dateKey"
                  type="button"
                  class="task-calendar-day"
                  :class="{
                    muted: !day.inMonth,
                    today: day.isToday,
                    selected: calendarSelectedDate === day.dateKey,
                    busy: day.tasks.length,
                  }"
                  @click="selectCalendarDate(day.dateKey)"
                >
                  <span class="task-calendar-date">{{ day.label }}</span>
                  <span v-if="day.tasks.length" class="task-calendar-count">{{ day.tasks.length }}</span>
                  <span
                    v-for="task in day.tasks.slice(0, 3)"
                    :key="task.id"
                    class="task-calendar-event"
                    :class="[calendarPriorityClass(task), { overdue: isOverdue(task) }]"
                  >
                    {{ task.titre }}
                  </span>
                  <span v-if="day.tasks.length > 3" class="task-calendar-more">
                    +{{ day.tasks.length - 3 }} autre(s)
                  </span>
                </button>
              </div>
            </section>

            <aside class="task-calendar-detail">
              <div class="task-calendar-detail-head">
                <span>Détail</span>
                <strong>{{ selectedCalendarDateLabel }}</strong>
              </div>
              <div v-if="selectedCalendarTasks.length" class="task-calendar-detail-list">
                <router-link
                  v-for="task in selectedCalendarTasks"
                  :key="task.id"
                  :to="{ name: 'taches.show', params: { id: task.id } }"
                  class="task-calendar-detail-item"
                >
                  <span class="task-calendar-dot" :class="calendarPriorityClass(task)"></span>
                  <span>
                    <strong>{{ task.titre }}</strong>
                    <small>
                      {{ statutLabel(task.statut) }}
                      <template v-if="task.agent || task.createur">
                        · {{ (showAssignedByMe || isDeptScope || isSENScope || isProvinceScope) ? (task.agent?.nom_complet ?? '-') : (task.createur?.nom_complet ?? '-') }}
                      </template>
                    </small>
                  </span>
                  <i class="fas fa-arrow-right"></i>
                </router-link>
              </div>
              <div v-else class="task-calendar-empty">
                <i class="fas fa-calendar-check"></i>
                <span>Aucune tâche prévue ce jour.</span>
              </div>
            </aside>
          </div>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover mb-0" :aria-busy="loading">
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
              <tr v-if="loading" class="task-table-loading">
                <td colspan="8">
                  <LoadingSpinner message="Chargement des données..." />
                </td>
              </tr>
              <template v-else>
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
              </template>
              <tr v-if="!loading && !filteredTaches.length">
                <td colspan="8" class="text-center text-muted py-4">
                  <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                  {{ emptyStateText }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
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
              <select
                v-if="createCanMultiAssign"
                id="task_agent_id"
                v-model="createForm.agent_ids"
                class="form-select"
                multiple
                required
                size="6"
              >
                <option v-for="ag in createAgents" :key="ag.id" :value="ag.id">
                  {{ agentOptionLabel(ag) }}
                </option>
              </select>
              <select v-else id="task_agent_id" v-model="createForm.agent_id" class="form-select" required>
                <option value="">-- Choisir un agent --</option>
                <option v-for="ag in createAgents" :key="ag.id" :value="ag.id">
                  {{ agentOptionLabel(ag) }}
                </option>
              </select>
              <small v-if="createCanMultiAssign" class="text-muted d-block mt-1">Maintenez Ctrl pour choisir plusieurs agents.</small>
            </div>

            <div class="task-form-wide">
              <label for="task_validation_responsable_id" class="form-label fw-bold">Validateur final <span class="text-danger">*</span></label>
              <select id="task_validation_responsable_id" v-model="createForm.validation_responsable_id" class="form-select" required>
                <option value="">-- Choisir le validateur autorisé --</option>
                <option v-for="validator in createValidators" :key="validator.id" :value="validator.id">
                  {{ agentOptionLabel(validator) }}
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
const canCreateTaches = ref(false)
const sourceFilter = ref('all')
const validStatusFilters = ['all', 'nouvelle', 'en_cours', 'bloquee', 'terminee', 'en_retard']
const normalizeStatusFilter = (value) => {
  const filter = Array.isArray(value) ? value[0] : value
  return validStatusFilters.includes(filter) ? filter : 'all'
}
const statusFilter = ref(normalizeStatusFilter(route.query.statut))
const taskViewMode = ref(route.hash === '#agenda' ? 'calendar' : 'list')
const calendarCursor = ref(startOfMonth(new Date()))
const calendarSelectedDate = ref(toDateKey(new Date()))
const createModalOpen = ref(false)
const loadingCreateData = ref(false)
const submittingCreate = ref(false)
const createErrors = ref([])
const createAgents = ref([])
const createValidators = ref([])
const createActivitesPta = ref([])
const createSourceEmetteurs = ref([])
const createSelectedDocuments = ref([])
const createDocumentsInput = ref(null)
const createCanMultiAssign = ref(false)
const createScopeFlags = ref({ isSENScope: false, isSENAScope: false, isProvinceScope: false, isLocalScope: false })
const createForm = ref(defaultCreateForm())

const isDeptScope = computed(() => route.query.scope === 'departement')
const isSENScope = computed(() => route.query.scope === 'sen')
const isProvinceScope = computed(() => route.query.scope === 'province')
const showAssignedByMe = computed(() => route.name === 'taches.assigned-by-me')

const createPageSubtitle = computed(() => {
  if (createScopeFlags.value.isLocalScope) return 'Assigner une tâche à un agent local de votre ressort.'
  if (createScopeFlags.value.isProvinceScope) return 'Assigner une tâche à un agent de la province ou au SEL rattaché.'
  if (createScopeFlags.value.isSENAScope) return 'Assigner une tâche uniquement aux attachés du SEN, aux directeurs de département et aux SEP suivis par le Secrétariat de direction.'
  if (createScopeFlags.value.isSENScope) return 'Assigner une tâche à un agent du Secrétariat exécutif national.'
  return 'Assigner une tâche à un agent de votre département.'
})

watch(() => route.query.statut, (val) => {
  const nextFilter = normalizeStatusFilter(val)
  if (statusFilter.value !== nextFilter) {
    statusFilter.value = nextFilter
  }
})

watch(() => route.hash, (hash) => {
  if (hash === '#agenda') taskViewMode.value = 'calendar'
})

watch(() => [route.name, route.query.scope], () => {
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

const calendarWeekdays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const calendarMonthLabel = computed(() => calendarCursor.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }))
const calendarTasksByDate = computed(() => {
  const grouped = {}
  filteredTaches.value.forEach((task) => {
    const key = taskDateKey(task)
    if (!key) return
    if (!grouped[key]) grouped[key] = []
    grouped[key].push(task)
  })

  Object.values(grouped).forEach((items) => {
    items.sort((a, b) => priorityWeight(b.priorite) - priorityWeight(a.priorite))
  })

  return grouped
})
const calendarDays = computed(() => {
  const monthStart = startOfMonth(calendarCursor.value)
  const gridStart = new Date(monthStart)
  gridStart.setDate(monthStart.getDate() - ((monthStart.getDay() + 6) % 7))

  return Array.from({ length: 42 }, (_, index) => {
    const date = new Date(gridStart)
    date.setDate(gridStart.getDate() + index)
    const dateKey = toDateKey(date)

    return {
      date,
      dateKey,
      label: date.getDate(),
      inMonth: date.getMonth() === calendarCursor.value.getMonth(),
      isToday: dateKey === toDateKey(new Date()),
      tasks: calendarTasksByDate.value[dateKey] || [],
    }
  })
})
const calendarTasksInMonth = computed(() => {
  const month = calendarCursor.value.getMonth()
  const year = calendarCursor.value.getFullYear()
  return filteredTaches.value.filter((task) => {
    const date = parseTaskDate(task.date_echeance)
    return date && date.getMonth() === month && date.getFullYear() === year
  })
})
const calendarOverdueCount = computed(() => filteredTaches.value.filter((task) => isOverdue(task)).length)
const calendarWithoutDeadlineCount = computed(() => filteredTaches.value.filter((task) => !task.date_echeance).length)
const selectedCalendarTasks = computed(() => calendarTasksByDate.value[calendarSelectedDate.value] || [])
const selectedCalendarDateLabel = computed(() => {
  const date = parseDateKey(calendarSelectedDate.value)
  return date ? date.toLocaleDateString('fr-FR', { weekday: 'long', day: '2-digit', month: 'long' }) : ''
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
    canCreateTaches.value = Boolean(data.canCreateTaches || auth.agent?.id || auth.user?.agent?.id || canManageTaches.value)
  } catch {
    ui.addToast('Erreur lors du chargement des tâches.', 'danger')
  } finally {
    loading.value = false
  }
}

function defaultCreateForm() {
  return {
    agent_id: '',
    agent_ids: [],
    validation_responsable_id: '',
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
    createValidators.value = data.data.validators || []
    createActivitesPta.value = data.data.activites_pta || []
    createSourceEmetteurs.value = data.data.source_emetteurs || []
    createForm.value.source_emetteur = data.data.default_source_emetteur || 'directeur'
    createCanMultiAssign.value = Boolean(data.data.can_multi_assign)
    if (!createCanMultiAssign.value && data.data.current_agent_id && createAgents.value.length === 1) {
      createForm.value.agent_id = data.data.current_agent_id
    }
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
      if (key === 'agent_ids') {
        if (createCanMultiAssign.value) value.forEach((id) => payload.append('agent_ids[]', id))
        return
      }
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

function agentOptionLabel(agent) {
  const name = agent.nom_complet || `${agent.prenom || ''} ${agent.nom || ''}`.trim()
  const meta = [agent.role, agent.matricule].filter(Boolean).join(' · ')
  return meta ? `${name} (${meta})` : name
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

function setStatusFilter(filter) {
  statusFilter.value = normalizeStatusFilter(filter)
}

function setTaskViewMode(mode) {
  taskViewMode.value = mode === 'calendar' ? 'calendar' : 'list'
}

function moveCalendar(offset) {
  calendarCursor.value = addMonths(calendarCursor.value, offset)
  calendarSelectedDate.value = toDateKey(calendarCursor.value)
}

function goToCurrentMonth() {
  const today = new Date()
  calendarCursor.value = startOfMonth(today)
  calendarSelectedDate.value = toDateKey(today)
}

function selectCalendarDate(dateKey) {
  calendarSelectedDate.value = dateKey
  const date = parseDateKey(dateKey)
  if (date && date.getMonth() !== calendarCursor.value.getMonth()) {
    calendarCursor.value = startOfMonth(date)
  }
}

function startOfMonth(date) {
  return new Date(date.getFullYear(), date.getMonth(), 1)
}

function addMonths(date, amount) {
  return new Date(date.getFullYear(), date.getMonth() + amount, 1)
}

function parseTaskDate(dateStr) {
  if (!dateStr) return null
  if (/^\d{4}-\d{2}-\d{2}/.test(dateStr)) {
    const [year, month, day] = dateStr.substring(0, 10).split('-').map(Number)
    return new Date(year, month - 1, day)
  }
  const date = new Date(dateStr)
  return Number.isNaN(date.getTime()) ? null : date
}

function parseDateKey(dateKey) {
  if (!dateKey) return null
  const [year, month, day] = dateKey.split('-').map(Number)
  return new Date(year, month - 1, day)
}

function toDateKey(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function taskDateKey(task) {
  const date = parseTaskDate(task.date_echeance)
  return date ? toDateKey(date) : ''
}

function priorityWeight(priority) {
  return { urgente: 4, haute: 3, normale: 2, faible: 1 }[priority] || 0
}

function calendarPriorityClass(task) {
  return `priority-${task.priorite || 'normale'}`
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
  statusFilter.value = normalizeStatusFilter(route.query.statut)
  loadTaches().then(() => {
    if (route.query.create) openCreateModal()
  })
})

watch(statusFilter, (val) => {
  const nextFilter = normalizeStatusFilter(val)
  if (val !== nextFilter) {
    statusFilter.value = nextFilter
    return
  }

  const currentFilter = normalizeStatusFilter(route.query.statut)
  const hasStatusQuery = Object.prototype.hasOwnProperty.call(route.query, 'statut')
  if (currentFilter === nextFilter && (nextFilter !== 'all' || !hasStatusQuery)) return

  const query = { ...route.query }
  if (nextFilter === 'all') {
    delete query.statut
  } else {
    query.statut = nextFilter
  }
  router.replace({ name: route.name, query })
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

.task-table-loading td {
  height: 220px;
  padding: 2.5rem 1rem !important;
  background: rgba(248, 250, 252, .56);
  text-align: center;
  vertical-align: middle;
}

.task-panel-tools {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: .75rem;
  flex-wrap: wrap;
}

.task-view-toggle {
  display: inline-flex;
  gap: .25rem;
  padding: .25rem;
  border: 1px solid #dbe7ef;
  border-radius: 999px;
  background: #f8fafc;
}

.task-view-toggle button {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  min-height: 34px;
  padding: 0 .85rem;
  border: 0;
  border-radius: 999px;
  color: #475569;
  background: transparent;
  font-size: .82rem;
  font-weight: 800;
}

.task-view-toggle button.active {
  color: #fff;
  background: linear-gradient(135deg, #0277b5, #0f766e);
  box-shadow: 0 8px 18px rgba(2, 119, 181, .22);
}

.task-calendar-view {
  padding: 1.15rem;
  border-top: 1px solid rgba(226, 232, 240, .78);
  background:
    linear-gradient(135deg, rgba(248, 250, 252, .92), rgba(240, 253, 250, .78));
}

.task-calendar-toolbar,
.task-calendar-summary,
.task-calendar-actions,
.task-calendar-detail-head {
  display: flex;
  align-items: center;
}

.task-calendar-toolbar {
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: .8rem;
}

.task-calendar-eyebrow {
  display: block;
  color: #0f766e;
  font-size: .72rem;
  font-weight: 900;
  letter-spacing: .05em;
  text-transform: uppercase;
}

.task-calendar-toolbar h4 {
  margin: .15rem 0 0;
  color: #0f172a;
  font-size: 1.2rem;
  font-weight: 900;
  text-transform: capitalize;
}

.task-calendar-actions {
  gap: .4rem;
}

.task-calendar-actions button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 38px;
  min-height: 38px;
  border: 1px solid #dbe7ef;
  border-radius: 11px;
  color: #0f5f76;
  background: #fff;
  font-weight: 900;
}

.task-calendar-actions button:hover {
  border-color: #8fd3e8;
  background: #eef9fc;
}

.task-calendar-actions .task-calendar-today {
  padding: 0 .9rem;
}

.task-calendar-summary {
  gap: .6rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.task-calendar-summary span {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  min-height: 32px;
  padding: 0 .75rem;
  border: 1px solid #dbe7ef;
  border-radius: 999px;
  color: #475569;
  background: rgba(255, 255, 255, .84);
  font-size: .82rem;
  font-weight: 700;
}

.task-calendar-summary b {
  color: #0f172a;
}

.task-calendar-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 1rem;
  align-items: stretch;
}

.task-calendar-board,
.task-calendar-detail {
  border: 1px solid rgba(203, 213, 225, .72);
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 14px 30px rgba(15, 23, 42, .06);
}

.task-calendar-board {
  overflow: hidden;
}

.task-calendar-weekdays,
.task-calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.task-calendar-weekdays {
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.task-calendar-weekdays span {
  padding: .72rem .5rem;
  color: #64748b;
  font-size: .72rem;
  font-weight: 900;
  text-align: center;
  text-transform: uppercase;
}

.task-calendar-day {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: .32rem;
  min-height: 116px;
  padding: .65rem;
  border: 0;
  border-right: 1px solid #eef2f7;
  border-bottom: 1px solid #eef2f7;
  background: #fff;
  text-align: left;
}

.task-calendar-day:nth-child(7n) {
  border-right: 0;
}

.task-calendar-day:hover,
.task-calendar-day.selected {
  background: #f0f9ff;
  box-shadow: inset 0 0 0 2px rgba(2, 119, 181, .24);
}

.task-calendar-day.muted {
  background: #f8fafc;
  color: #94a3b8;
}

.task-calendar-day.today .task-calendar-date {
  color: #fff;
  background: #0277b5;
}

.task-calendar-day.busy::after {
  content: '';
  position: absolute;
  top: .7rem;
  right: .7rem;
  width: .46rem;
  height: .46rem;
  border-radius: 999px;
  background: #0f766e;
}

.task-calendar-date {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 10px;
  color: #334155;
  font-weight: 900;
}

.task-calendar-count {
  position: absolute;
  top: .55rem;
  right: 1.45rem;
  min-width: 20px;
  padding: .05rem .35rem;
  border-radius: 999px;
  color: #075985;
  background: #e0f2fe;
  font-size: .68rem;
  font-weight: 900;
  text-align: center;
}

.task-calendar-event,
.task-calendar-more {
  display: block;
  overflow: hidden;
  max-width: 100%;
  border-radius: 8px;
  padding: .22rem .45rem;
  color: #334155;
  background: #f1f5f9;
  font-size: .72rem;
  font-weight: 800;
  line-height: 1.25;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-calendar-event.priority-urgente,
.task-calendar-dot.priority-urgente {
  background: #fee2e2;
  color: #b91c1c;
}

.task-calendar-event.priority-haute,
.task-calendar-dot.priority-haute {
  background: #fef3c7;
  color: #92400e;
}

.task-calendar-event.priority-normale,
.task-calendar-dot.priority-normale {
  background: #dbeafe;
  color: #1d4ed8;
}

.task-calendar-event.priority-faible,
.task-calendar-dot.priority-faible {
  background: #dcfce7;
  color: #166534;
}

.task-calendar-event.overdue {
  box-shadow: inset 3px 0 0 #ef4444;
}

.task-calendar-more {
  color: #64748b;
  background: transparent;
  padding-left: 0;
}

.task-calendar-detail {
  display: flex;
  min-height: 100%;
  flex-direction: column;
  padding: 1rem;
}

.task-calendar-detail-head {
  justify-content: space-between;
  gap: .75rem;
  margin-bottom: .9rem;
  padding-bottom: .75rem;
  border-bottom: 1px solid #e2e8f0;
}

.task-calendar-detail-head span {
  color: #64748b;
  font-size: .72rem;
  font-weight: 900;
  text-transform: uppercase;
}

.task-calendar-detail-head strong {
  color: #0f172a;
  font-size: .9rem;
  text-align: right;
  text-transform: capitalize;
}

.task-calendar-detail-list {
  display: grid;
  gap: .65rem;
}

.task-calendar-detail-item {
  display: grid;
  grid-template-columns: 10px minmax(0, 1fr) 16px;
  gap: .65rem;
  align-items: center;
  padding: .72rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  color: inherit;
  background: #f8fafc;
  text-decoration: none;
}

.task-calendar-detail-item:hover {
  border-color: #8fd3e8;
  background: #eef9fc;
}

.task-calendar-detail-item strong,
.task-calendar-detail-item small {
  display: block;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-calendar-detail-item strong {
  color: #0f172a;
  font-size: .86rem;
}

.task-calendar-detail-item small {
  margin-top: .1rem;
  color: #64748b;
  font-size: .72rem;
}

.task-calendar-dot {
  width: 10px;
  height: 34px;
  border-radius: 999px;
}

.task-calendar-empty,
.task-calendar-loading {
  display: grid;
  place-items: center;
  gap: .65rem;
  min-height: 220px;
  color: #94a3b8;
  text-align: center;
}

.task-calendar-empty i {
  font-size: 1.8rem;
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

  .task-panel-tools {
    width: 100%;
    justify-content: center;
  }

  .task-calendar-view {
    padding: .85rem;
  }

  .task-calendar-toolbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .task-calendar-layout {
    grid-template-columns: 1fr;
  }

  .task-calendar-grid,
  .task-calendar-weekdays {
    min-width: 680px;
  }

  .task-calendar-board {
    overflow-x: auto;
  }

  .task-calendar-day {
    min-height: 104px;
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
  place-items: start center;
  padding: 1.25rem;
  overflow-y: auto;
  background: rgba(15, 23, 42, .52);
  backdrop-filter: blur(6px);
}

.task-modal-dialog {
  position: relative;
  width: min(980px, 100%);
  max-height: calc(100dvh - 2.5rem);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  border-radius: 10px;
  background: #ffffff;
  border: 1px solid #dbe4ef;
  box-shadow: 0 24px 56px rgba(15, 23, 42, .22);
}

.task-modal-close {
  position: absolute;
  top: .85rem;
  right: .85rem;
  z-index: 2;
  width: 36px;
  height: 36px;
  border: 1px solid #dbe4ef;
  border-radius: 8px;
  background: #fff;
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
  gap: .9rem;
  padding: 1.1rem 1.35rem;
  color: #0f172a;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.task-modal-icon {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #e0f2fe;
  border: 1px solid #bae6fd;
  color: #0369a1;
}

.task-modal-header h2 {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 800;
}

.task-modal-header p {
  margin: .25rem 2.4rem 0 0;
  color: #64748b;
  font-size: .86rem;
}

.task-create-form {
  padding: 1.25rem 1.35rem 1.35rem;
  overflow-y: auto;
}

.task-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem 1.1rem;
  align-items: start;
}

.task-form-grid > div {
  min-width: 0;
}

.task-create-form .form-label {
  display: block;
  margin-bottom: .35rem;
  color: #334155;
  font-size: .86rem;
}

.task-create-form .form-control,
.task-create-form .form-select {
  min-height: 42px;
  border-color: #cbd5e1;
  border-radius: 8px;
  color: #0f172a;
}

.task-create-form textarea.form-control {
  min-height: 104px;
  resize: vertical;
}

.task-create-form select[multiple] {
  min-height: 142px;
  padding-top: .55rem;
  padding-bottom: .55rem;
}

.task-form-wide {
  grid-column: 1 / -1;
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
  background: #fff;
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
