<template>
  <div class="container py-4">

    <!-- Retour -->
    <router-link :to="{ name: 'renforcements.index' }" class="fv-back">
      <i class="fas fa-arrow-left me-1"></i> Formations
    </router-link>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-success" role="status"></div>
    </div>

    <template v-else-if="formation">

      <!-- Hero formation -->
      <div class="fv-hero" :class="'fv-hero-' + formation.statut">
        <div class="fv-hero-inner">
          <div class="fv-hero-left">
            <div class="fv-hero-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div>
              <div class="fv-hero-statut">{{ statutLabel(formation.statut) }}</div>
              <h2 class="fv-hero-titre">{{ formation.titre }}</h2>
              <div class="fv-hero-meta">
                <span v-if="formation.date_debut"><i class="fas fa-calendar me-1"></i>{{ formatDate(formation.date_debut) }}</span>
                <span v-if="formation.date_fin"><i class="fas fa-calendar-check me-1"></i>{{ formatDate(formation.date_fin) }}</span>
                <span v-if="formation.lieu"><i class="fas fa-map-marker-alt me-1"></i>{{ formation.lieu }}</span>
                <span v-if="formation.formateur"><i class="fas fa-user-tie me-1"></i>{{ formation.formateur }}</span>
                <span v-if="formation.budget"><i class="fas fa-dollar-sign me-1"></i>{{ formatBudget(formation.budget) }}</span>
              </div>
            </div>
          </div>
          <div class="fv-hero-actions">
            <router-link :to="{ name: 'renforcements.edit', params: { id: formation.id } }" class="fv-btn-edit">
              <i class="fas fa-edit me-1"></i> Modifier
            </router-link>
            <button v-if="formation.statut === 'planifiee'" class="fv-btn-start" @click="changeStatut('en_cours')" :disabled="changingStatut">
              <i class="fas fa-play me-1"></i> Démarrer
            </button>
            <button v-if="formation.statut === 'en_cours'" class="fv-btn-finish" @click="changeStatut('terminee')" :disabled="changingStatut">
              <i class="fas fa-check me-1"></i> Terminer
            </button>
          </div>
        </div>
      </div>

      <div class="fv-grid">

        <!-- Infos + Description -->
        <div class="fv-col-main">
          <div class="fv-card" v-if="formation.description || formation.objectif">
            <div class="fv-card-head"><i class="fas fa-info-circle me-2 text-success"></i>Détails</div>
            <div v-if="formation.description" class="fv-section">
              <div class="fv-section-label">Description</div>
              <p class="fv-text">{{ formation.description }}</p>
            </div>
            <div v-if="formation.objectif" class="fv-section">
              <div class="fv-section-label">Objectif</div>
              <p class="fv-text">{{ formation.objectif }}</p>
            </div>
          </div>

          <!-- Bénéficiaires -->
          <div class="fv-card">
            <div class="fv-card-head">
              <i class="fas fa-users me-2 text-success"></i>
              Bénéficiaires
              <span class="fv-badge-count">{{ formation.beneficiaires?.length ?? 0 }}</span>
            </div>

            <!-- Ajout bénéficiaire -->
            <div v-if="['planifiee', 'en_cours'].includes(formation.statut)" class="fv-benef-add">
              <div class="fv-benef-search-wrap">
                <i class="fas fa-search fv-benef-icon"></i>
                <input v-model="agentSearch" type="text" placeholder="Ajouter un bénéficiaire..." class="fv-benef-input" @input="onAgentSearch">
              </div>
              <div v-if="agentResults.length" class="fv-agent-dropdown">
                <button v-for="a in agentResults" :key="a.id" type="button" class="fv-agent-option" @click="addBeneficiaire(a)">
                  <span class="fv-agent-name">{{ a.prenom }} {{ a.nom }}</span>
                  <span class="fv-agent-fn">{{ a.fonction || '' }}</span>
                </button>
              </div>
            </div>

            <div v-if="!(formation.beneficiaires?.length)" class="fv-empty-small">
              <i class="fas fa-users text-muted"></i> Aucun bénéficiaire enregistré.
            </div>

            <div v-else class="fv-benef-table-wrap">
              <table class="fv-benef-table">
                <thead>
                  <tr>
                    <th>Agent</th>
                    <th>Statut</th>
                    <th>Évaluation</th>
                    <th v-if="canEdit"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="b in formation.beneficiaires" :key="b.id">
                    <td>
                      <div class="fv-benef-name">{{ b.agent?.prenom }} {{ b.agent?.nom }}</div>
                      <div class="fv-benef-dept">{{ b.agent?.departement?.nom || '' }}</div>
                    </td>
                    <td>
                      <select v-if="canEdit" v-model="b.statut" class="fv-benef-select" @change="updateBeneficiaire(b)">
                        <option value="inscrit">Inscrit</option>
                        <option value="confirme">Confirmé</option>
                        <option value="present">Présent</option>
                        <option value="absent">Absent</option>
                        <option value="certifie">Certifié</option>
                      </select>
                      <span v-else class="fv-benef-badge" :class="'bst-' + b.statut">{{ benStatutLabel(b.statut) }}</span>
                    </td>
                    <td>
                      <input v-if="canEdit" v-model="b.note_evaluation" type="number" min="0" max="100" class="fv-note-input" placeholder="Note/100" @change="updateBeneficiaire(b)">
                      <span v-else-if="b.note_evaluation" class="fv-note">{{ b.note_evaluation }}/100</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td v-if="canEdit">
                      <button class="fv-save-benef" @click="updateBeneficiaire(b)" :disabled="savingBenef === b.id">
                        <i class="fas" :class="savingBenef === b.id ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Sidebar infos -->
        <div class="fv-col-side">
          <div class="fv-card">
            <div class="fv-card-head"><i class="fas fa-clock me-2 text-success"></i>Informations</div>
            <dl class="fv-dl">
              <dt>Statut</dt>
              <dd><span class="fv-badge" :class="'fbadge-' + formation.statut">{{ statutLabel(formation.statut) }}</span></dd>
              <dt v-if="formation.date_debut">Début</dt>
              <dd v-if="formation.date_debut">{{ formatDate(formation.date_debut) }}</dd>
              <dt v-if="formation.date_fin">Fin</dt>
              <dd v-if="formation.date_fin">{{ formatDate(formation.date_fin) }}</dd>
              <dt v-if="formation.lieu">Lieu</dt>
              <dd v-if="formation.lieu">{{ formation.lieu }}</dd>
              <dt v-if="formation.formateur">Formateur</dt>
              <dd v-if="formation.formateur">{{ formation.formateur }}</dd>
              <dt v-if="formation.budget">Budget</dt>
              <dd v-if="formation.budget" class="fv-budget">{{ formatBudget(formation.budget) }}</dd>
              <dt>Créé par</dt>
              <dd>{{ formation.createur?.prenom }} {{ formation.createur?.nom }}</dd>
            </dl>
          </div>

          <!-- Stats bénéficiaires -->
          <div class="fv-card" v-if="formation.beneficiaires?.length">
            <div class="fv-card-head"><i class="fas fa-chart-pie me-2 text-success"></i>Participation</div>
            <div class="fv-bstat" v-for="s in benStatuts" :key="s.key">
              <div class="fv-bstat-label">{{ s.label }}</div>
              <div class="fv-bstat-bar-wrap">
                <div class="fv-bstat-bar" :style="{ width: bStatPct(s.key) + '%', background: s.color }"></div>
              </div>
              <div class="fv-bstat-val" :style="{ color: s.color }">{{ bStatCount(s.key) }}</div>
            </div>
          </div>
        </div>

      </div>
    </template>

    <div v-else class="alert alert-warning">Formation introuvable.</div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()
const loading = ref(true)
const formation = ref(null)
const changingStatut = ref(false)
const savingBenef    = ref(null)
const agentSearch    = ref('')
const agentResults   = ref([])
let searchTimeout = null

const canEdit = computed(() => auth.isChefSectionRenforcement || auth.isRH || auth.isSEN)

const benStatuts = [
  { key: 'inscrit',  label: 'Inscrits',    color: '#0ea5e9' },
  { key: 'confirme', label: 'Confirmés',   color: '#7c3aed' },
  { key: 'present',  label: 'Présents',    color: '#d97706' },
  { key: 'certifie', label: 'Certifiés',   color: '#059669' },
  { key: 'absent',   label: 'Absents',     color: '#dc2626' },
]

function bStatCount(k) {
  return (formation.value?.beneficiaires || []).filter(b => b.statut === k).length
}
function bStatPct(k) {
  const total = formation.value?.beneficiaires?.length || 0
  return total ? Math.round(bStatCount(k) / total * 100) : 0
}

async function loadFormation() {
  loading.value = true
  try {
    const { data } = await client.get(`/renforcements/${route.params.id}`)
    formation.value = data.data || data
  } finally {
    loading.value = false
  }
}

async function changeStatut(statut) {
  changingStatut.value = true
  try {
    await client.put(`/renforcements/${route.params.id}`, { statut })
    formation.value.statut = statut
  } finally {
    changingStatut.value = false
  }
}

async function updateBeneficiaire(b) {
  savingBenef.value = b.id
  try {
    await client.put(`/renforcements/${route.params.id}/beneficiaire/${b.id}`, {
      statut: b.statut,
      note_evaluation: b.note_evaluation || null,
    })
  } finally {
    savingBenef.value = null
  }
}

async function onAgentSearch() {
  clearTimeout(searchTimeout)
  if (agentSearch.value.trim().length < 2) { agentResults.value = []; return }
  searchTimeout = setTimeout(async () => {
    try {
      const { data } = await client.get('/agents', { params: { search: agentSearch.value, per_page: 8 } })
      const existing = new Set((formation.value?.beneficiaires || []).map(b => b.agent_id))
      agentResults.value = (data.data || data).filter(a => !existing.has(a.id))
    } catch { agentResults.value = [] }
  }, 300)
}

async function addBeneficiaire(a) {
  try {
    await client.post(`/renforcements/${route.params.id}/beneficiaire`, { agent_id: a.id })
    agentSearch.value = ''
    agentResults.value = []
    loadFormation()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur ajout bénéficiaire')
  }
}

function formatDate(v) {
  if (!v) return ''
  return new Date(v).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}
function formatBudget(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v)
}
function statutLabel(s) {
  return { planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' }[s] ?? s
}
function benStatutLabel(s) {
  return { inscrit: 'Inscrit', confirme: 'Confirmé', present: 'Présent', absent: 'Absent', certifie: 'Certifié' }[s] ?? s
}

onMounted(loadFormation)
</script>

<style scoped>
.fv-back { display: inline-flex; align-items: center; gap: .4rem; font-size: .82rem; font-weight: 600; color: #059669; text-decoration: none; margin-bottom: 1.2rem; }
.fv-back:hover { color: #047857; }

/* Hero */
.fv-hero { border-radius: 16px; overflow: hidden; margin-bottom: 1.5rem; color: #fff; box-shadow: 0 6px 24px rgba(0,0,0,.15); }
.fv-hero-planifiee { background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%); }
.fv-hero-en_cours  { background: linear-gradient(135deg, #78350f 0%, #d97706 100%); }
.fv-hero-terminee  { background: linear-gradient(135deg, #052e16 0%, #059669 100%); }
.fv-hero-annulee   { background: linear-gradient(135deg, #1e293b 0%, #475569 100%); }
.fv-hero-inner { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; padding: 1.8rem 2rem; flex-wrap: wrap; }
.fv-hero-left { display: flex; align-items: flex-start; gap: 1rem; flex: 1; min-width: 0; }
.fv-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.fv-hero-statut { font-size: .7rem; opacity: .6; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
.fv-hero-titre { font-size: 1.35rem; font-weight: 800; margin: .2rem 0 .5rem; }
.fv-hero-meta { display: flex; flex-wrap: wrap; gap: .8rem; font-size: .78rem; opacity: .75; }
.fv-hero-actions { display: flex; gap: .6rem; flex-wrap: wrap; }
.fv-btn-edit { display: inline-flex; align-items: center; padding: .5rem 1.1rem; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3); border-radius: 10px; color: #fff; font-size: .82rem; font-weight: 700; text-decoration: none; transition: all .2s; }
.fv-btn-edit:hover { background: rgba(255,255,255,.25); color: #fff; }
.fv-btn-start  { display: inline-flex; align-items: center; padding: .5rem 1.1rem; background: #22c55e; border: none; border-radius: 10px; color: #fff; font-size: .82rem; font-weight: 700; cursor: pointer; transition: all .2s; }
.fv-btn-finish { display: inline-flex; align-items: center; padding: .5rem 1.1rem; background: #059669; border: none; border-radius: 10px; color: #fff; font-size: .82rem; font-weight: 700; cursor: pointer; }

/* Grid */
.fv-grid { display: grid; grid-template-columns: 1fr 280px; gap: 1.2rem; }
.fv-card { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px; padding: 1.3rem; margin-bottom: 1rem; }
.fv-card-head { font-size: .9rem; font-weight: 800; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; gap: .3rem; }
.fv-badge-count { margin-left: auto; background: #d1fae5; color: #065f46; font-size: .7rem; font-weight: 700; padding: .15rem .55rem; border-radius: 20px; }
.fv-section { margin-bottom: .9rem; }
.fv-section-label { font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; margin-bottom: .3rem; }
.fv-text { font-size: .85rem; color: #374151; margin: 0; line-height: 1.6; }

/* DL sidebar */
.fv-dl { display: grid; grid-template-columns: auto 1fr; gap: .4rem 1rem; margin: 0; font-size: .82rem; }
.fv-dl dt { color: #94a3b8; font-weight: 600; align-self: start; }
.fv-dl dd { margin: 0; color: #374151; font-weight: 600; word-break: break-word; }
.fv-budget { color: #059669; font-weight: 800; }
.fv-badge { display: inline-flex; padding: .15rem .55rem; border-radius: 7px; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
.fbadge-planifiee { background: #e0f2fe; color: #0369a1; }
.fbadge-en_cours  { background: #fef3c7; color: #92400e; }
.fbadge-terminee  { background: #d1fae5; color: #065f46; }
.fbadge-annulee   { background: #f1f5f9; color: #64748b; }

/* Benef stats sidebar */
.fv-bstat { display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem; }
.fv-bstat-label { font-size: .72rem; color: #64748b; width: 70px; flex-shrink: 0; }
.fv-bstat-bar-wrap { flex: 1; height: 6px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.fv-bstat-bar { height: 100%; border-radius: 4px; transition: width .6s ease; min-width: 2px; }
.fv-bstat-val { font-size: .72rem; font-weight: 700; width: 20px; text-align: right; }

/* Table bénéficiaires */
.fv-benef-table-wrap { overflow-x: auto; }
.fv-benef-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.fv-benef-table th { font-size: .68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; padding: .5rem .7rem; border-bottom: 1.5px solid #f1f5f9; text-align: left; }
.fv-benef-table td { padding: .6rem .7rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.fv-benef-table tr:last-child td { border-bottom: none; }
.fv-benef-name { font-weight: 700; color: #1e293b; }
.fv-benef-dept { font-size: .7rem; color: #94a3b8; }
.fv-benef-select { padding: .25rem .55rem; border: 1.5px solid #e5e7eb; border-radius: 7px; font-size: .75rem; background: #fff; cursor: pointer; }
.fv-benef-badge { display: inline-flex; padding: .15rem .5rem; border-radius: 7px; font-size: .7rem; font-weight: 700; }
.bst-inscrit  { background: #e0f2fe; color: #0369a1; }
.bst-confirme { background: #ede9fe; color: #6d28d9; }
.bst-present  { background: #fef3c7; color: #92400e; }
.bst-certifie { background: #d1fae5; color: #065f46; }
.bst-absent   { background: #fee2e2; color: #991b1b; }
.fv-note-input { width: 80px; padding: .25rem .55rem; border: 1.5px solid #e5e7eb; border-radius: 7px; font-size: .78rem; }
.fv-note { font-size: .78rem; font-weight: 700; color: #059669; }
.fv-save-benef { background: none; border: none; color: #059669; cursor: pointer; font-size: .85rem; padding: .3rem; border-radius: 6px; }
.fv-save-benef:hover { background: #d1fae5; }

/* Ajout bénéficiaire */
.fv-benef-add { position: relative; margin-bottom: .8rem; }
.fv-benef-search-wrap { position: relative; }
.fv-benef-icon { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .82rem; pointer-events: none; }
.fv-benef-input { width: 100%; padding: .6rem .85rem .6rem 2.2rem; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: .85rem; }
.fv-benef-input:focus { outline: none; border-color: #059669; }
.fv-agent-dropdown { position: absolute; top: 100%; left: 0; right: 0; z-index: 50; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.1); max-height: 220px; overflow-y: auto; }
.fv-agent-option { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: .65rem 1rem; border: none; background: none; cursor: pointer; text-align: left; }
.fv-agent-option:hover { background: #f0fdf4; }
.fv-agent-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
.fv-agent-fn { font-size: .72rem; color: #94a3b8; }
.fv-empty-small { font-size: .8rem; color: #94a3b8; font-style: italic; padding: .5rem 0; }

@media (max-width: 768px) {
  .fv-grid { grid-template-columns: 1fr; }
  .fv-hero-inner { flex-direction: column; }
}
</style>
