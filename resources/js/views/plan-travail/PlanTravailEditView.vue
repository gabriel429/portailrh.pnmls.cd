<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement de l’activité..." />
      </div>

      <template v-else>
        <section class="rh-hero">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-edit me-2"></i>Modifier l’activité</h1>
              <p class="rh-sub">Plan de Travail Annuel {{ form.annee }}</p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'plan-travail.show', params: { id: route.params.id } }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="dash-panel mt-3">
          <div class="p-4">
            <div v-if="errors.length" class="alert alert-danger">
              <ul class="mb-0">
                <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
              </ul>
            </div>

            <form @submit.prevent="handleSubmit">
              <!-- Titre -->
              <div class="mb-3">
                <label for="titre" class="form-label fw-bold">Titre de l’activité <span class="text-danger">*</span></label>
                <input v-model="form.titre" type="text" class="form-control" id="titre" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label for="categorie" class="form-label fw-bold">Rubrique / categorie</label>
                  <input v-model="form.categorie" list="pta-categories-edit" type="text" class="form-control" id="categorie" placeholder="Ex. Leadership">
                  <datalist id="pta-categories-edit">
                    <option v-for="item in formData.categories" :key="item" :value="item"></option>
                  </datalist>
                </div>

                <div class="col-md-4">
                  <label for="cout_cdf" class="form-label fw-bold">Cout en CDF</label>
                  <input v-model.number="form.cout_cdf" type="number" step="0.01" min="0" class="form-control" id="cout_cdf" placeholder="0">
                </div>
              </div>

              <div class="mb-3">
                <label for="objectif" class="form-label fw-bold">Objectif strategique</label>
                <textarea v-model="form.objectif" class="form-control" id="objectif" rows="2"></textarea>
              </div>

              <div class="mb-3">
                <label for="resultat_attendu" class="form-label fw-bold">Resultat attendu</label>
                <textarea v-model="form.resultat_attendu" class="form-control" id="resultat_attendu" rows="2"></textarea>
              </div>

              <div class="row g-3">
                <!-- Niveau administratif -->
                <div class="col-md-4">
                  <label for="niveau_administratif" class="form-label fw-bold">Niveau <span class="text-danger">*</span></label>
                  <select v-model="form.niveau_administratif" class="form-select" id="niveau_administratif" required @change="onNiveauChange">
                    <option value="">-- Choisir --</option>
                    <option value="SEN">SEN (National)</option>
                    <option value="SEP">SEP (Provincial)</option>
                    <option value="SEL">SEL (Local)</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label for="validation_niveau" class="form-label fw-bold">Validation</label>
                  <select v-model="form.validation_niveau" class="form-select" id="validation_niveau">
                    <option value="">-- Choisir --</option>
                    <option value="direction">Direction</option>
                    <option value="coordination_nationale">Coordination nationale</option>
                    <option value="coordination_provinciale">Coordination provinciale</option>
                  </select>
                </div>

                <div v-if="isPlanificationSenAssignment" class="col-md-6">
                  <label for="assignment_target" class="form-label fw-bold">
                    Département ou attachés du SEN <span class="text-danger">*</span>
                  </label>
                  <select v-model="form.assignment_target" class="form-select" id="assignment_target" required @change="onAssignmentTargetChange">
                    <option value="">-- Choisir d'abord la cible --</option>
                    <option v-for="target in assignmentTargets" :key="target.value" :value="target.value">
                      {{ target.label }} ({{ target.agent_count }} agent{{ target.agent_count > 1 ? 's' : '' }})
                    </option>
                  </select>
                  <div class="form-text">Les agents affiches ci-dessous dependent uniquement de ce choix.</div>
                </div>

                <div v-if="isPlanificationSenAssignment && form.assignment_target" class="col-md-6">
                  <label for="assigned_agent_id" class="form-label fw-bold">
                    Attribuer l’activité à <span v-if="assignableAgents.length" class="text-danger">*</span>
                  </label>
                  <select
                    :value="selectedAssignedAgentId"
                    class="form-select"
                    id="assigned_agent_id"
                    :required="assignableAgents.length > 0"
                    :disabled="assignableAgents.length === 0"
                    @change="setAssignedAgent($event.target.value)"
                  >
                    <option value="">{{ assignableAgents.length ? '-- Choisir un agent --' : 'Aucun agent disponible pour cette cible' }}</option>
                    <option v-for="agent in assignableAgents" :key="agent.id" :value="agent.id">
                      {{ agentOptionLabel(agent) }}
                    </option>
                  </select>
                </div>

                <div v-else-if="form.niveau_administratif === 'SEN'" class="col-md-4">
                  <label for="departement_id" class="form-label fw-bold">Département</label>
                  <select v-model="form.departement_id" class="form-select" id="departement_id">
                    <option value="">-- Direction / Tous --</option>
                    <option v-for="d in formData.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                  </select>
                </div>

                <div v-if="form.niveau_administratif === 'SEL'" class="col-md-4">
                  <label for="province_id" class="form-label fw-bold">Province</label>
                  <select v-model="form.province_id" class="form-select" id="province_id">
                    <option value="">-- Choisir --</option>
                    <option v-for="p in formData.provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
                  </select>
                </div>

                <div v-if="form.niveau_administratif === 'SEP'" class="col-md-8">
                  <label for="province_ids" class="form-label fw-bold">Provinces concernees</label>
                  <select v-model="form.province_ids" class="form-select" id="province_ids" multiple size="6">
                    <option v-for="p in formData.provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
                  </select>
                  <div class="form-text">Maintenez Ctrl pour sélectionner plusieurs provinces.</div>
                </div>

                <div v-if="form.niveau_administratif === 'SEL'" class="col-md-4">
                  <label for="localite_id" class="form-label fw-bold">Localite</label>
                  <select v-model="form.localite_id" class="form-select" id="localite_id">
                    <option value="">-- Choisir --</option>
                    <option v-for="l in formData.localites" :key="l.id" :value="l.id">{{ l.nom }}</option>
                  </select>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-3">
                  <label for="annee" class="form-label fw-bold">Annee <span class="text-danger">*</span></label>
                  <input v-model.number="form.annee" type="number" class="form-control" id="annee" min="2020" max="2040" required>
                </div>

                <div class="col-md-3">
                  <label for="trimestre" class="form-label fw-bold">Trimestre</label>
                  <select v-model="form.trimestre" class="form-select" id="trimestre">
                    <option value="">Annuel</option>
                    <option value="T1">T1 (Jan-Mar)</option>
                    <option value="T2">T2 (Avr-Jun)</option>
                    <option value="T3">T3 (Jul-Sep)</option>
                    <option value="T4">T4 (Oct-Dec)</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold">Chronogramme</label>
                  <div class="d-flex flex-wrap gap-3 pt-2">
                    <label class="form-check-label"><input v-model="form.trimestre_1" class="form-check-input me-1" type="checkbox"> T1</label>
                    <label class="form-check-label"><input v-model="form.trimestre_2" class="form-check-input me-1" type="checkbox"> T2</label>
                    <label class="form-check-label"><input v-model="form.trimestre_3" class="form-check-input me-1" type="checkbox"> T3</label>
                    <label class="form-check-label"><input v-model="form.trimestre_4" class="form-check-input me-1" type="checkbox"> T4</label>
                  </div>
                </div>

                <div class="col-md-3">
                  <label for="statut" class="form-label fw-bold">Statut <span class="text-danger">*</span></label>
                  <select v-model="form.statut" class="form-select" id="statut" required>
                    <option value="planifiee">Planifiee</option>
                    <option value="en_cours">En cours</option>
                    <option value="terminee">Terminee</option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label for="pourcentage" class="form-label fw-bold">Progression (%)</label>
                  <input v-model.number="form.pourcentage" type="number" class="form-control" id="pourcentage" min="0" max="100">
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <label for="date_debut" class="form-label fw-bold">Date de début</label>
                  <input v-model="form.date_debut" type="date" class="form-control" id="date_debut">
                </div>

                <div class="col-md-6">
                  <label for="date_fin" class="form-label fw-bold">Date de fin</label>
                  <input v-model="form.date_fin" type="date" class="form-control" id="date_fin">
                </div>
              </div>

              <div class="mb-3 mt-3">
                <label for="description" class="form-label fw-bold">Description</label>
                <textarea v-model="form.description" class="form-control" id="description" rows="4"></textarea>
              </div>

              <div class="mb-3">
                <label for="observations" class="form-label fw-bold">Observations</label>
                <textarea v-model="form.observations" class="form-control" id="observations" rows="3"></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-save me-1"></i> Mettre à jour
                </button>
                <router-link :to="{ name: 'plan-travail.show', params: { id: route.params.id } }" class="btn btn-outline-secondary">Annuler</router-link>
              </div>
            </form>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { get, update, getCreateData } from '@/api/planTravail'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const submitting = ref(false)
const errors = ref([])
const formData = ref({ departments: [], provinces: [], localites: [], categories: [], responsables: [], assignment_targets: [], sen_attache_agents: [], department_agents: {} })
const form = ref({
  titre: '',
  categorie: '',
  objectif: '',
  responsable_code: '',
  assignment_target: '',
  assigned_agent_ids: [],
  cout_cdf: '',
  niveau_administratif: '',
  validation_niveau: '',
  departement_id: '',
  province_id: '',
  province_ids: [],
  localite_id: '',
  annee: new Date().getFullYear(),
  trimestre: '',
  trimestre_1: false,
  trimestre_2: false,
  trimestre_3: false,
  trimestre_4: false,
  statut: 'planifiee',
  pourcentage: 0,
  date_debut: '',
  date_fin: '',
  description: '',
  resultat_attendu: '',
  observations: '',
})

const isPlanificationSenAssignment = computed(() =>
  Boolean(formData.value.is_planification_role) && form.value.niveau_administratif === 'SEN'
)

const assignmentTargets = computed(() => formData.value.assignment_targets || [])
const selectedAssignedAgentId = computed(() => form.value.assigned_agent_ids?.[0] || '')

const assignableAgents = computed(() => {
  const target = form.value.assignment_target

  if (!target) return []

  if (target === 'sen_attaches') {
    return formData.value.sen_attache_agents || formData.value.agents_sen || []
  }

  if (target.startsWith('department:')) {
    const departmentId = target.replace('department:', '')
    const grouped = formData.value.department_agents || {}
    return grouped[departmentId] || grouped[Number(departmentId)] || []
  }

  return []
})

async function loadActivite() {
  try {
    const [activiteResp, createResp] = await Promise.all([
      get(route.params.id),
      getCreateData(),
    ])
    const a = activiteResp.data.data
    formData.value = createResp.data
    form.value = {
      titre: a.titre || '',
      categorie: a.categorie || '',
      objectif: a.objectif || '',
      responsable_code: a.responsable_code || '',
      assignment_target: resolveAssignmentTarget(a, createResp.data),
      assigned_agent_ids: (a.assigned_agent_ids || []).slice(0, 1),
      cout_cdf: a.cout_cdf ?? '',
      niveau_administratif: a.niveau_administratif || '',
      validation_niveau: a.validation_niveau || '',
      departement_id: a.departement_id || '',
      province_id: a.province_id || '',
      province_ids: a.province_ids || (a.province_id ? [a.province_id] : []),
      localite_id: a.localite_id || '',
      annee: a.annee || new Date().getFullYear(),
      trimestre: a.trimestre || '',
      trimestre_1: !!a.trimestre_1,
      trimestre_2: !!a.trimestre_2,
      trimestre_3: !!a.trimestre_3,
      trimestre_4: !!a.trimestre_4,
      statut: a.statut || 'planifiee',
      pourcentage: a.pourcentage ?? 0,
      date_debut: a.date_debut ? a.date_debut.split('T')[0] : '',
      date_fin: a.date_fin ? a.date_fin.split('T')[0] : '',
      description: a.description || '',
      resultat_attendu: a.resultat_attendu || '',
      observations: a.observations || '',
    }
  } catch {
    ui.addToast('Activité introuvable.', 'danger')
    router.push({ name: 'plan-travail.index' })
  } finally {
    loading.value = false
  }
}

function onNiveauChange() {
  if (form.value.niveau_administratif !== 'SEN') {
    form.value.departement_id = ''
    form.value.assignment_target = ''
    form.value.assigned_agent_ids = []
  }
  if (form.value.niveau_administratif !== 'SEL') form.value.province_id = ''
  if (form.value.niveau_administratif !== 'SEP') form.value.province_ids = []
  if (form.value.niveau_administratif !== 'SEL') form.value.localite_id = ''
}

function resolveAssignmentTarget(activity, options) {
  const assignedId = Number(activity.assigned_agent_ids?.[0] || 0)
  const departmentId = activity.departement_id
  const grouped = options.department_agents || {}

  if (assignedId) {
    if (departmentId) {
      const departmentAgents = grouped[departmentId] || grouped[Number(departmentId)] || []
      if (departmentAgents.some((agent) => Number(agent.id) === assignedId)) {
        return `department:${departmentId}`
      }
    }

    const senAgents = options.sen_attache_agents || options.agents_sen || []
    if (senAgents.some((agent) => Number(agent.id) === assignedId)) {
      return 'sen_attaches'
    }

    const matchedDepartment = Object.entries(grouped).find(([, agents]) =>
      (agents || []).some((agent) => Number(agent.id) === assignedId)
    )
    if (matchedDepartment) return `department:${matchedDepartment[0]}`
  }

  if (departmentId) return `department:${departmentId}`
  if ((activity.responsable_code || '').toLowerCase().includes('attache')) return 'sen_attaches'

  return ''
}

function onAssignmentTargetChange() {
  form.value.assigned_agent_ids = []

  if (form.value.assignment_target === 'sen_attaches') {
    form.value.departement_id = ''
    form.value.responsable_code = 'Attachés SEN'
    return
  }

  if (form.value.assignment_target?.startsWith('department:')) {
    const departmentId = form.value.assignment_target.replace('department:', '')
    const target = assignmentTargets.value.find((item) => item.value === form.value.assignment_target)
    form.value.departement_id = departmentId
    form.value.responsable_code = target?.label?.slice(0, 30) || ''
  }
}

function setAssignedAgent(agentId) {
  form.value.assigned_agent_ids = agentId ? [Number(agentId)] : []
}

function agentOptionLabel(agent) {
  return agent?.fonction ? `${agent.nom_complet} - ${agent.fonction}` : agent?.nom_complet
}

watch(assignableAgents, (agents) => {
  if (!selectedAssignedAgentId.value) return

  const stillAvailable = agents.some((agent) => Number(agent.id) === Number(selectedAssignedAgentId.value))
  if (!stillAvailable) {
    form.value.assigned_agent_ids = []
  }
})

function buildPayload() {
  const payload = { ...form.value }

  if (!payload.departement_id) delete payload.departement_id
  if (!payload.province_id) delete payload.province_id
  if (!payload.localite_id) delete payload.localite_id
  if (!payload.trimestre) delete payload.trimestre
  if (!payload.date_debut) delete payload.date_debut
  if (!payload.date_fin) delete payload.date_fin
  if (!payload.description) delete payload.description
  if (!payload.objectif) delete payload.objectif
  if (!payload.resultat_attendu) delete payload.resultat_attendu
  if (!payload.observations) delete payload.observations
  if (!payload.categorie) delete payload.categorie
  if (!payload.responsable_code) delete payload.responsable_code
  if (!isPlanificationSenAssignment.value) {
    delete payload.assignment_target
    delete payload.assigned_agent_ids
  } else {
    if (!payload.assignment_target) delete payload.assignment_target
    if (!payload.assigned_agent_ids?.length && !payload.assignment_target) delete payload.assigned_agent_ids
  }
  if (payload.cout_cdf === '' || payload.cout_cdf === null) delete payload.cout_cdf
  if (!payload.province_ids?.length) delete payload.province_ids

  return payload
}

function validateAssignmentSelection() {
  if (!isPlanificationSenAssignment.value) return true

  if (!form.value.assignment_target) {
    errors.value = ['Choisissez d’abord un département ou les attachés du SEN.']
    return false
  }

  if (assignableAgents.value.length > 0 && !selectedAssignedAgentId.value) {
    errors.value = ['Choisissez l’agent à qui attribuer cette activité.']
    return false
  }

  return true
}

async function handleSubmit() {
  errors.value = []
  if (!validateAssignmentSelection()) return
  submitting.value = true
  try {
    await update(route.params.id, buildPayload())
    ui.addToast('Activité mise à jour.', 'success')
    router.push({ name: 'plan-travail.show', params: { id: route.params.id } })
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = Object.values(err.response.data.errors || {}).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => loadActivite())
</script>

<style scoped>
@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; padding: 1rem; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    .form-label { font-size: .82rem; }
    .btn { font-size: .85rem; }
}
</style>
