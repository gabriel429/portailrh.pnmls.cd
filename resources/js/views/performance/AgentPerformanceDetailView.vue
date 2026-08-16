<template>
  <div class="agent-perf-detail-page">
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon"><i class="fas fa-user-chart"></i></div>
        <div>
          <h1 class="hero-title">{{ agent.prenom }} {{ agent.nom }}</h1>
          <p class="hero-subtitle">{{ agent.poste_actuel || agent.fonction || '-' }} · {{ agent.organe || '-' }}</p>
        </div>
      </div>
      <router-link :to="{ name: 'performance.dashboard' }" class="hero-btn">
        <i class="fas fa-arrow-left"></i> Retour au tableau de bord
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color:#0077B5;" role="status"></div>
    </div>

    <template v-else>
      <div class="kpi-grid">
        <article class="kpi-tile tone-blue">
          <i class="fas fa-tasks"></i>
          <div><strong>{{ formatPercent(indicateurs.taux_completion_taches) }}</strong><span>Complétion tâches (année en cours)</span></div>
        </article>
        <article class="kpi-tile tone-teal">
          <i class="fas fa-calendar-check"></i>
          <div><strong>{{ formatPercent(indicateurs.taux_assiduite) }}</strong><span>Assiduité (année en cours)</span></div>
        </article>
        <article class="kpi-tile tone-amber">
          <i class="fas fa-star"></i>
          <div><strong>{{ formatScore(indicateurs.score_auto) }}</strong><span>Score auto (année en cours)</span></div>
        </article>
      </div>

      <div class="data-card">
        <div class="panel-heading">
          <h6>Historique des évaluations</h6>
        </div>
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Période</th>
                <th>Évaluateur</th>
                <th class="text-end">Score auto</th>
                <th class="text-end">Score manuel</th>
                <th class="text-end">Score global</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ev in evaluations" :key="ev.id">
                <td>{{ periodLabel(ev) }}</td>
                <td>{{ ev.evaluateur ? `${ev.evaluateur.prenom} ${ev.evaluateur.nom}` : '-' }}</td>
                <td class="text-end">{{ formatScore(ev.score_auto) }}</td>
                <td class="text-end">{{ formatScore(ev.score_manuel) }}</td>
                <td class="text-end fw-semibold">{{ formatScore(ev.score_global) }}</td>
                <td>
                  <span class="status-indicator">
                    <span class="status-dot" :class="statutDotClass(ev.statut)"></span>
                    {{ statutLabel(ev.statut) }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <router-link
                      v-if="ev.statut === 'brouillon' || ev.statut === 'rejetee'"
                      :to="{ name: 'performance.evaluations.edit', params: { id: ev.id } }"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-pen"></i>
                    </router-link>
                    <button
                      v-if="ev.statut === 'soumise' && canValidate"
                      class="action-btn action-btn-success"
                      title="Valider"
                      @click="validate(ev)"
                    >
                      <i class="fas fa-check"></i>
                    </button>
                    <button
                      v-if="ev.statut === 'soumise' && canValidate"
                      class="action-btn action-btn-danger"
                      title="Rejeter"
                      @click="openReject(ev)"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="evaluations.length === 0">
                <td colspan="7" class="text-center text-muted py-4">Aucune évaluation enregistrée pour cet agent.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div v-if="rejectTarget" class="reject-backdrop" @click.self="rejectTarget = null">
      <div class="reject-modal">
        <h6>Motif du rejet</h6>
        <textarea v-model="rejectMotif" class="form-control" rows="3" placeholder="Expliquez pourquoi cette évaluation est rejetée..."></textarea>
        <div class="reject-actions">
          <button class="btn btn-sm btn-light" @click="rejectTarget = null">Annuler</button>
          <button class="btn btn-sm btn-danger" :disabled="rejectMotif.trim().length < 5" @click="confirmReject">Rejeter</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const ui = useUiStore()
const auth = useAuthStore()

const loading = ref(true)
const agent = ref({})
const indicateurs = ref({})
const evaluations = ref([])
const rejectTarget = ref(null)
const rejectMotif = ref('')

const canValidate = computed(() => auth.isSEN || auth.isRHNational)

async function load() {
  loading.value = true
  try {
    const response = await client.get(`/performance/agents/${route.params.id}`)
    agent.value = response.data.data.agent
    indicateurs.value = response.data.data.indicateurs_courants
    evaluations.value = response.data.data.evaluations
  } catch (e) {
    ui.addToast(e.response?.data?.message || "Erreur lors du chargement du profil de performance.", 'danger')
  } finally {
    loading.value = false
  }
}

function periodLabel(ev) {
  return ev.periode_trimestre ? `T${ev.periode_trimestre} ${ev.periode_annee}` : `Année ${ev.periode_annee}`
}

function statutLabel(statut) {
  return {
    brouillon: 'Brouillon',
    soumise: 'Soumise',
    validee: 'Validée',
    rejetee: 'Rejetée',
  }[statut] || statut
}

function statutDotClass(statut) {
  return {
    brouillon: 'dot-pending',
    soumise: 'dot-pending',
    validee: 'dot-active',
    rejetee: 'dot-inactive',
  }[statut] || 'dot-inactive'
}

function formatScore(value) {
  if (value === null || value === undefined) return '-'
  return Number(value).toFixed(1)
}

function formatPercent(value) {
  if (value === null || value === undefined) return '-'
  return `${Number(value).toFixed(1)}%`
}

async function validate(ev) {
  try {
    await client.post(`/evaluations/${ev.id}/validate`)
    ui.addToast('Évaluation validée.', 'success')
    load()
  } catch (e) {
    ui.addToast(e.response?.data?.message || 'Erreur lors de la validation.', 'danger')
  }
}

function openReject(ev) {
  rejectTarget.value = ev
  rejectMotif.value = ''
}

async function confirmReject() {
  try {
    await client.post(`/evaluations/${rejectTarget.value.id}/reject`, { motif_rejet: rejectMotif.value })
    ui.addToast('Évaluation rejetée.', 'success')
    rejectTarget.value = null
    load()
  } catch (e) {
    ui.addToast(e.response?.data?.message || 'Erreur lors du rejet.', 'danger')
  }
}

onMounted(load)
</script>

<style scoped>
.agent-perf-detail-page { padding: 1.5rem 0; }

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

.kpi-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; margin-bottom: 1.25rem; }
.kpi-tile {
  display: flex; align-items: center; gap: .85rem; border: 1px solid #e2edf6;
  border-radius: 12px; background: #f8fbfd; padding: 1rem;
}
.kpi-tile i { display: grid; width: 42px; height: 42px; flex: 0 0 42px; place-items: center; border-radius: 10px; color: #fff; }
.kpi-tile strong { display: block; color: #0f172a; font-size: 1.2rem; font-weight: 800; line-height: 1; }
.kpi-tile span { color: #64748b; font-size: .78rem; }
.tone-blue i { background: #0ea5e9; }
.tone-teal i { background: #10b981; }
.tone-amber i { background: #f59e0b; }

.data-card {
  background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #f1f5f9; overflow: hidden;
}
.panel-heading { padding: 1rem 1.25rem 0; }
.panel-heading h6 { color: #0f172a; font-weight: 800; margin: 0; }

.data-table thead th {
  background: #f8fafc; border: none; font-size: .78rem; font-weight: 600;
  text-transform: uppercase; letter-spacing: .5px; color: #64748b; padding: .85rem 1rem;
}
.data-table tbody td { padding: .75rem 1rem; border-color: #f1f5f9; vertical-align: middle; font-size: .88rem; }

.status-indicator { display: inline-flex; align-items: center; font-size: .85rem; color: #475569; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.dot-active { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.2); }
.dot-inactive { background: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,.2); }
.dot-pending { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.2); }

.action-btn {
  width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center;
  justify-content: center; border: 1px solid #e2e8f0; background: #fff; color: #64748b;
  font-size: .8rem; text-decoration: none; cursor: pointer;
}
.action-btn:hover { border-color: #0077B5; color: #0077B5; background: #e0f2fe; }
.action-btn-success:hover { border-color: #22c55e; color: #22c55e; background: #f0fdf4; }
.action-btn-danger:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

.reject-backdrop {
  position: fixed; inset: 0; background: rgba(15,23,42,.55);
  display: grid; place-items: center; z-index: 1050;
}
.reject-modal {
  background: #fff; border-radius: 14px; padding: 1.25rem; width: min(420px, 92vw);
}
.reject-modal h6 { font-weight: 800; color: #0f172a; margin-bottom: .75rem; }
.reject-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; }

@media (max-width: 767.98px) {
  .kpi-grid { grid-template-columns: 1fr; }
}
</style>
