<template>
  <div class="container py-4">

    <!-- Header -->
    <div class="fc-header">
      <router-link :to="{ name: 'renforcements.index' }" class="fc-back">
        <i class="fas fa-arrow-left me-1"></i> Formations
      </router-link>
      <div class="fc-header-body">
        <div class="fc-header-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div>
          <h2 class="fc-title">{{ isEdit ? 'Modifier la formation' : 'Planifier une formation' }}</h2>
          <p class="fc-sub">{{ isEdit ? 'Mettez à jour les informations de la formation.' : 'Remplissez les informations pour créer une nouvelle formation.' }}</p>
        </div>
      </div>
    </div>

    <div v-if="loadingData" class="text-center py-5">
      <div class="spinner-border text-success" role="status"></div>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="fc-form">

      <!-- Infos générales -->
      <div class="fc-card">
        <div class="fc-card-head"><i class="fas fa-info-circle me-2 text-success"></i>Informations générales</div>
        <div class="fc-grid-2">
          <div class="fc-field fc-field-full">
            <label class="fc-label">Titre <span class="fc-req">*</span></label>
            <input v-model="form.titre" type="text" class="fc-input" :class="{ 'fc-error': errors.titre }" placeholder="Intitulé de la formation" required>
            <span v-if="errors.titre" class="fc-err-msg">{{ errors.titre[0] }}</span>
          </div>
          <div class="fc-field fc-field-full">
            <label class="fc-label">Description</label>
            <textarea v-model="form.description" class="fc-textarea" rows="3" placeholder="Contexte et description..."></textarea>
          </div>
          <div class="fc-field fc-field-full">
            <label class="fc-label">Objectif</label>
            <textarea v-model="form.objectif" class="fc-textarea" rows="2" placeholder="Objectif pédagogique..."></textarea>
          </div>
        </div>
      </div>

      <!-- Logistique -->
      <div class="fc-card">
        <div class="fc-card-head"><i class="fas fa-map-marker-alt me-2 text-success"></i>Logistique</div>
        <div class="fc-grid-2">
          <div class="fc-field">
            <label class="fc-label">Date de début <span class="fc-req">*</span></label>
            <input v-model="form.date_debut" type="date" class="fc-input" :class="{ 'fc-error': errors.date_debut }" required>
            <span v-if="errors.date_debut" class="fc-err-msg">{{ errors.date_debut[0] }}</span>
          </div>
          <div class="fc-field">
            <label class="fc-label">Date de fin <span class="fc-req">*</span></label>
            <input v-model="form.date_fin" type="date" class="fc-input" :class="{ 'fc-error': errors.date_fin }" required>
            <span v-if="errors.date_fin" class="fc-err-msg">{{ errors.date_fin[0] }}</span>
          </div>
          <div class="fc-field">
            <label class="fc-label">Lieu</label>
            <input v-model="form.lieu" type="text" class="fc-input" placeholder="Ex : Salle de conférence A">
          </div>
          <div class="fc-field">
            <label class="fc-label">Formateur / Organisme</label>
            <input v-model="form.formateur" type="text" class="fc-input" placeholder="Nom du formateur ou organisme">
          </div>
          <div class="fc-field">
            <label class="fc-label">Budget (USD)</label>
            <div class="fc-input-group">
              <span class="fc-input-prefix">$</span>
              <input v-model="form.budget" type="number" min="0" step="0.01" class="fc-input fc-input-prefixed" placeholder="0.00">
            </div>
          </div>
          <div v-if="isEdit" class="fc-field">
            <label class="fc-label">Statut</label>
            <select v-model="form.statut" class="fc-input">
              <option value="planifiee">Planifiée</option>
              <option value="en_cours">En cours</option>
              <option value="terminee">Terminée</option>
              <option value="annulee">Annulée</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Bénéficiaires -->
      <div class="fc-card">
        <div class="fc-card-head"><i class="fas fa-users me-2 text-success"></i>Bénéficiaires</div>

        <!-- Recherche agent -->
        <div class="fc-benef-search-wrap">
          <div class="fc-benef-search-input-wrap">
            <i class="fas fa-search fc-benef-search-icon"></i>
            <input v-model="agentSearch" type="text" placeholder="Rechercher un agent PNMLS..." class="fc-input"
              style="padding-left: 2.2rem;" @input="onAgentSearch">
          </div>
          <div v-if="agentResults.length" class="fc-agent-dropdown">
            <button v-for="a in agentResults" :key="a.id" type="button" class="fc-agent-option" @click="addBeneficiaire(a)">
              <span class="fc-agent-opt-name">{{ a.prenom }} {{ a.nom }}</span>
              <span class="fc-agent-opt-fn">{{ a.fonction || a.departement?.nom || '' }}</span>
            </button>
          </div>
        </div>

        <!-- Liste bénéficiaires -->
        <div v-if="beneficiaires.length" class="fc-benef-list">
          <div v-for="(b, idx) in beneficiaires" :key="b.id" class="fc-benef-tag">
            <span class="fc-benef-name">{{ b.prenom }} {{ b.nom }}</span>
            <button type="button" class="fc-benef-remove" @click="removeBeneficiaire(idx)"><i class="fas fa-times"></i></button>
          </div>
        </div>
        <p v-else class="fc-benef-hint">Aucun bénéficiaire ajouté pour l'instant.</p>
      </div>

      <!-- Erreur globale -->
      <div v-if="globalError" class="alert alert-danger">{{ globalError }}</div>

      <!-- Actions -->
      <div class="fc-actions">
        <router-link :to="{ name: 'renforcements.index' }" class="fc-btn-secondary">Annuler</router-link>
        <button type="submit" class="fc-btn-submit" :disabled="submitting">
          <span v-if="submitting"><i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...</span>
          <span v-else><i class="fas fa-check me-1"></i>{{ isEdit ? 'Mettre à jour' : 'Créer la formation' }}</span>
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import client from '@/api/client'

const route  = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)

const loadingData = ref(false)
const submitting  = ref(false)
const globalError = ref(null)
const errors = ref({})

const form = ref({
  titre: '', description: '', objectif: '',
  date_debut: '', date_fin: '', lieu: '', formateur: '',
  budget: '', statut: 'planifiee',
})

const beneficiaires = ref([])
const agentSearch   = ref('')
const agentResults  = ref([])
let searchTimeout = null

async function onAgentSearch() {
  clearTimeout(searchTimeout)
  if (agentSearch.value.trim().length < 2) { agentResults.value = []; return }
  searchTimeout = setTimeout(async () => {
    try {
      const { data } = await client.get('/agents', { params: { search: agentSearch.value, per_page: 8 } })
      const existing = new Set(beneficiaires.value.map(b => b.id))
      agentResults.value = (data.data || data).filter(a => !existing.has(a.id))
    } catch { agentResults.value = [] }
  }, 300)
}

function addBeneficiaire(a) {
  if (!beneficiaires.value.find(b => b.id === a.id)) {
    beneficiaires.value.push(a)
  }
  agentSearch.value = ''
  agentResults.value = []
}

function removeBeneficiaire(idx) {
  beneficiaires.value.splice(idx, 1)
}

async function loadFormation() {
  loadingData.value = true
  try {
    const { data } = await client.get(`/renforcements/${route.params.id}`)
    const f = data.data || data
    Object.assign(form.value, {
      titre: f.titre, description: f.description || '', objectif: f.objectif || '',
      date_debut: f.date_debut?.slice(0, 10) || '',
      date_fin: f.date_fin?.slice(0, 10) || '',
      lieu: f.lieu || '', formateur: f.formateur || '',
      budget: f.budget || '', statut: f.statut,
    })
    beneficiaires.value = (f.beneficiaires || []).map(b => b.agent).filter(Boolean)
  } finally {
    loadingData.value = false
  }
}

async function handleSubmit() {
  submitting.value = true
  globalError.value = null
  errors.value = {}
  try {
    const payload = {
      ...form.value,
      beneficiaires: beneficiaires.value.map(b => b.id),
    }
    if (isEdit.value) {
      await client.put(`/renforcements/${route.params.id}`, payload)
    } else {
      await client.post('/renforcements', payload)
    }
    router.push({ name: 'renforcements.index' })
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      globalError.value = 'Veuillez corriger les erreurs dans le formulaire.'
    } else {
      globalError.value = e.response?.data?.message || 'Une erreur est survenue.'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  if (isEdit.value) loadFormation()
})
</script>

<style scoped>
.fc-header { margin-bottom: 1.5rem; }
.fc-back { display: inline-flex; align-items: center; gap: .4rem; font-size: .82rem; font-weight: 600; color: #059669; text-decoration: none; margin-bottom: 1rem; }
.fc-back:hover { color: #047857; }
.fc-header-body { display: flex; align-items: center; gap: 1rem; }
.fc-header-icon { width: 48px; height: 48px; border-radius: 14px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.fc-title { font-size: 1.3rem; font-weight: 800; color: #1e293b; margin: 0; }
.fc-sub { font-size: .78rem; color: #94a3b8; margin: 0; }

.fc-form { display: flex; flex-direction: column; gap: 1.2rem; }
.fc-card { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 16px; padding: 1.4rem; }
.fc-card-head { font-size: .9rem; font-weight: 800; color: #1e293b; margin-bottom: 1.1rem; display: flex; align-items: center; }

.fc-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.fc-field { display: flex; flex-direction: column; gap: .35rem; }
.fc-field-full { grid-column: span 2; }
.fc-label { font-size: .78rem; font-weight: 700; color: #374151; }
.fc-req { color: #dc2626; }
.fc-input { width: 100%; padding: .6rem .85rem; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: .85rem; transition: border-color .2s; }
.fc-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.08); }
.fc-input.fc-error { border-color: #dc2626; }
.fc-textarea { width: 100%; padding: .6rem .85rem; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: .85rem; resize: vertical; min-height: 70px; transition: border-color .2s; }
.fc-textarea:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.08); }
.fc-err-msg { font-size: .72rem; color: #dc2626; }
.fc-input-group { position: relative; }
.fc-input-prefix { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .85rem; font-weight: 700; }
.fc-input-prefixed { padding-left: 2rem; }

.fc-benef-search-wrap { position: relative; margin-bottom: .8rem; }
.fc-benef-search-input-wrap { position: relative; }
.fc-benef-search-icon { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .82rem; pointer-events: none; }
.fc-agent-dropdown { position: absolute; top: 100%; left: 0; right: 0; z-index: 50; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12); max-height: 220px; overflow-y: auto; }
.fc-agent-option { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: .65rem 1rem; border: none; background: none; cursor: pointer; text-align: left; transition: background .15s; }
.fc-agent-option:hover { background: #f0fdf4; }
.fc-agent-opt-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
.fc-agent-opt-fn { font-size: .72rem; color: #94a3b8; }
.fc-benef-list { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
.fc-benef-tag { display: inline-flex; align-items: center; gap: .4rem; background: #d1fae5; border-radius: 8px; padding: .25rem .65rem; font-size: .78rem; font-weight: 700; color: #065f46; }
.fc-benef-remove { background: none; border: none; cursor: pointer; color: #6b7280; padding: 0; font-size: .65rem; display: flex; align-items: center; }
.fc-benef-remove:hover { color: #dc2626; }
.fc-benef-hint { font-size: .78rem; color: #94a3b8; font-style: italic; margin: 0; }

.fc-actions { display: flex; justify-content: flex-end; gap: .75rem; }
.fc-btn-secondary { display: inline-flex; align-items: center; padding: .6rem 1.4rem; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: .85rem; font-weight: 600; color: #64748b; text-decoration: none; transition: all .2s; }
.fc-btn-secondary:hover { border-color: #cbd5e1; background: #f8fafc; }
.fc-btn-submit { display: inline-flex; align-items: center; gap: .4rem; padding: .6rem 1.6rem; background: #059669; color: #fff; border: none; border-radius: 10px; font-size: .85rem; font-weight: 700; cursor: pointer; transition: all .2s; }
.fc-btn-submit:hover:not(:disabled) { background: #047857; }
.fc-btn-submit:disabled { opacity: .6; cursor: not-allowed; }

@media (max-width: 600px) {
  .fc-grid-2 { grid-template-columns: 1fr; }
  .fc-field-full { grid-column: span 1; }
}
</style>
