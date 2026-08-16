<template>
  <div class="evaluation-form-page">
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon"><i class="fas fa-clipboard-list"></i></div>
        <div>
          <h1 class="hero-title">{{ isEdit ? "Modifier l'évaluation" : 'Nouvelle évaluation' }}</h1>
          <p class="hero-subtitle" v-if="agent.id">{{ agent.prenom }} {{ agent.nom }} — {{ periodLabel }}</p>
        </div>
      </div>
      <router-link :to="{ name: 'performance.dashboard' }" class="hero-btn">
        <i class="fas fa-arrow-left"></i> Retour
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color:#0077B5;" role="status"></div>
    </div>

    <template v-else>
      <div v-if="!isEdit" class="data-card form-card">
        <div class="panel-heading"><h6>Agent &amp; période</h6></div>
        <div class="filters-grid">
          <div class="filter-field">
            <label>Agent</label>
            <select v-model="form.agent_id" class="form-select" :disabled="!!presetAgentId">
              <option value="">Sélectionner...</option>
              <option v-for="a in agentOptions" :key="a.id" :value="a.id">{{ a.prenom }} {{ a.nom }} ({{ a.matricule_etat || 'sans matricule' }})</option>
            </select>
          </div>
          <div class="filter-field">
            <label>Type de période</label>
            <select v-model="form.periode_type" class="form-select">
              <option value="trimestriel">Trimestriel</option>
              <option value="annuel">Annuel</option>
            </select>
          </div>
          <div class="filter-field">
            <label>Année</label>
            <select v-model.number="form.periode_annee" class="form-select">
              <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
          <div v-if="form.periode_type === 'trimestriel'" class="filter-field">
            <label>Trimestre</label>
            <select v-model.number="form.periode_trimestre" class="form-select">
              <option :value="1">T1</option>
              <option :value="2">T2</option>
              <option :value="3">T3</option>
              <option :value="4">T4</option>
            </select>
          </div>
        </div>
      </div>

      <div class="data-card form-card">
        <div class="panel-heading">
          <h6>Critères d'évaluation</h6>
          <span class="score-preview">Score manuel estimé: <strong>{{ estimatedScore.toFixed(1) }}</strong> / 100</span>
        </div>
        <div class="criteres-list">
          <div v-for="critere in criteres" :key="critere.id" class="critere-row">
            <div class="critere-label">
              <strong>{{ critere.libelle }}</strong>
              <small>Poids {{ critere.poids }}%</small>
            </div>
            <div class="critere-note">
              <button
                v-for="n in [0, 1, 2, 3, 4, 5]"
                :key="n"
                type="button"
                class="note-btn"
                :class="{ active: form.notes[critere.id]?.note === n }"
                @click="setNote(critere.id, n)"
              >{{ n }}</button>
            </div>
            <input
              v-model="form.notes[critere.id].commentaire"
              type="text"
              class="form-control critere-comment"
              placeholder="Commentaire (optionnel)"
            >
          </div>
        </div>
      </div>

      <div class="data-card form-card">
        <div class="panel-heading"><h6>Appréciation générale</h6></div>
        <textarea v-model="form.commentaire_general" class="form-control" rows="3" placeholder="Synthèse, points forts, axes d'amélioration..."></textarea>
      </div>

      <div class="form-actions">
        <button class="btn btn-outline-secondary" :disabled="saving" @click="save(false)">Enregistrer brouillon</button>
        <button class="btn btn-primary" :disabled="saving || !canSubmit" @click="save(true)">Soumettre pour validation</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const isEdit = computed(() => !!route.params.id)
const presetAgentId = computed(() => route.query.agent_id || null)
const currentYear = new Date().getFullYear()
const yearOptions = [currentYear, currentYear - 1, currentYear - 2]

const loading = ref(true)
const saving = ref(false)
const agent = ref({})
const criteres = ref([])
const agentOptions = ref([])
const evaluationId = ref(route.params.id || null)

const form = reactive({
  agent_id: presetAgentId.value || '',
  periode_type: route.query.trimestre === 'annuel' ? 'annuel' : 'trimestriel',
  periode_annee: Number(route.query.annee) || currentYear,
  periode_trimestre: Number(route.query.trimestre) || currentQuarter(),
  commentaire_general: '',
  notes: {},
})

function currentQuarter() {
  return Math.ceil((new Date().getMonth() + 1) / 3)
}

const periodLabel = computed(() => {
  return form.periode_type === 'annuel' ? `Année ${form.periode_annee}` : `T${form.periode_trimestre} ${form.periode_annee}`
})

const estimatedScore = computed(() => {
  let weightedSum = 0
  let weightTotal = 0
  for (const critere of criteres.value) {
    const note = form.notes[critere.id]?.note
    if (note === undefined || note === null) continue
    weightedSum += (note / 5) * 100 * critere.poids
    weightTotal += critere.poids
  }
  return weightTotal > 0 ? weightedSum / weightTotal : 0
})

const canSubmit = computed(() => {
  return criteres.value.length > 0 && criteres.value.every(c => form.notes[c.id]?.note !== undefined && form.notes[c.id]?.note !== null)
})

function setNote(critereId, note) {
  form.notes[critereId] = { ...form.notes[critereId], note }
}

async function loadCriteres() {
  const response = await client.get('/evaluations/criteres')
  criteres.value = response.data.data
  for (const critere of criteres.value) {
    if (!form.notes[critere.id]) {
      form.notes[critere.id] = { note: null, commentaire: '' }
    }
  }
}

async function loadAgentOptions() {
  if (presetAgentId.value || isEdit.value) return
  try {
    const response = await client.get('/agents', { params: { per_page: 100 } })
    const grouped = response.data.data || {}
    agentOptions.value = Object.values(grouped).flatMap(g => g.agents || [])
  } catch (e) {
    // liste optionnelle
  }
}

async function loadAgent(id) {
  if (!id) return
  try {
    const response = await client.get(`/agents/${id}`)
    agent.value = response.data.data || response.data
  } catch (e) {
    // pas bloquant pour le formulaire
  }
}

async function loadExisting() {
  if (!isEdit.value) return
  const response = await client.get(`/evaluations/${evaluationId.value}`)
  const evaluation = response.data.data
  form.agent_id = evaluation.agent_id
  form.periode_type = evaluation.periode_type
  form.periode_annee = evaluation.periode_annee
  form.periode_trimestre = evaluation.periode_trimestre
  form.commentaire_general = evaluation.commentaire_general || ''
  agent.value = evaluation.agent

  for (const detail of evaluation.details || []) {
    form.notes[detail.evaluation_critere_id] = { note: detail.note, commentaire: detail.commentaire || '' }
  }
}

async function save(submit) {
  if (!form.agent_id) {
    ui.addToast('Veuillez sélectionner un agent.', 'warning')
    return
  }

  const details = criteres.value
    .filter(c => form.notes[c.id]?.note !== undefined && form.notes[c.id]?.note !== null)
    .map(c => ({
      evaluation_critere_id: c.id,
      note: form.notes[c.id].note,
      commentaire: form.notes[c.id].commentaire || null,
    }))

  if (details.length === 0) {
    ui.addToast('Veuillez noter au moins un critère.', 'warning')
    return
  }

  saving.value = true
  try {
    let evaluation
    if (isEdit.value) {
      const response = await client.put(`/evaluations/${evaluationId.value}`, {
        commentaire_general: form.commentaire_general,
        details,
      })
      evaluation = response.data.data
    } else {
      const response = await client.post('/evaluations', {
        agent_id: form.agent_id,
        periode_type: form.periode_type,
        periode_annee: form.periode_annee,
        periode_trimestre: form.periode_type === 'annuel' ? 0 : form.periode_trimestre,
        commentaire_general: form.commentaire_general,
        details,
      })
      evaluation = response.data.data
      evaluationId.value = evaluation.id
    }

    if (submit) {
      if (!canSubmit.value) {
        ui.addToast('Veuillez noter tous les critères avant de soumettre.', 'warning')
        saving.value = false
        return
      }
      await client.post(`/evaluations/${evaluationId.value}/submit`)
      ui.addToast('Évaluation soumise pour validation.', 'success')
    } else {
      ui.addToast('Brouillon enregistré.', 'success')
    }

    router.push({ name: 'performance.agents.show', params: { id: form.agent_id } })
  } catch (e) {
    ui.addToast(e.response?.data?.message || "Erreur lors de l'enregistrement.", 'danger')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await loadCriteres()
    await Promise.all([loadAgentOptions(), loadAgent(presetAgentId.value), loadExisting()])
  } finally {
    loading.value = false
  }
})

watch(() => form.agent_id, (id) => {
  if (id && !agent.value?.id) loadAgent(id)
})
</script>

<style scoped>
.evaluation-form-page { padding: 1.5rem 0; }

.page-hero {
  background: linear-gradient(135deg, #0f172a 0%, #0c4a6e 55%, #0077B5 100%);
  border-radius: 16px; padding: 1.5rem 2rem; display: flex; align-items: center;
  justify-content: space-between; flex-wrap: wrap; gap: 1rem;
  box-shadow: 0 8px 32px rgba(0,119,181,.25); margin-bottom: 1.25rem;
}
.page-hero-content { display: flex; align-items: center; gap: 1rem; }
.page-hero-icon {
  width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff;
}
.hero-title { margin: 0; font-size: 1.35rem; font-weight: 700; color: #fff; }
.hero-subtitle { margin: 0; font-size: .85rem; color: rgba(255,255,255,.8); }
.hero-btn {
  background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3); color: #fff;
  padding: .5rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: .85rem;
  text-decoration: none; display: inline-flex; align-items: center; gap: .5rem;
}
.hero-btn:hover { background: rgba(255,255,255,.35); color: #fff; }

.data-card {
  background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #f1f5f9; padding: 1.25rem; margin-bottom: 1.25rem;
}
.panel-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.panel-heading h6 { color: #0f172a; font-weight: 800; margin: 0; }
.score-preview { font-size: .85rem; color: #475569; }
.score-preview strong { color: #0077B5; font-size: 1rem; }

.filters-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .85rem; }
.filter-field label {
  display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase;
  color: #64748b; margin-bottom: .3rem;
}

.criteres-list { display: grid; gap: 1rem; }
.critere-row {
  display: grid; grid-template-columns: minmax(160px, 1fr) auto minmax(200px, 1fr);
  align-items: center; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9;
}
.critere-row:last-child { border-bottom: 0; padding-bottom: 0; }
.critere-label strong { display: block; color: #0f172a; }
.critere-label small { color: #64748b; }
.critere-note { display: inline-flex; gap: .35rem; }
.note-btn {
  width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0;
  background: #fff; color: #475569; font-weight: 700;
}
.note-btn.active { background: #0077B5; border-color: #0077B5; color: #fff; }

.form-actions { display: flex; justify-content: flex-end; gap: .75rem; }

@media (max-width: 991.98px) {
  .filters-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .critere-row { grid-template-columns: 1fr; }
}
</style>
