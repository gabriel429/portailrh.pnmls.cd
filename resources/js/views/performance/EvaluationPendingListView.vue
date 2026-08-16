<template>
  <div class="evaluation-pending-page">
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <h1 class="hero-title">Évaluations en attente de validation</h1>
          <p class="hero-subtitle">{{ evaluations.length }} évaluation(s) soumise(s)</p>
        </div>
      </div>
      <router-link :to="{ name: 'performance.dashboard' }" class="hero-btn">
        <i class="fas fa-arrow-left"></i> Retour au tableau de bord
      </router-link>
    </div>

    <div class="data-card">
      <div class="table-responsive">
        <table class="table data-table">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Organe</th>
              <th>Période</th>
              <th>Évaluateur</th>
              <th class="text-end">Score global</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody v-if="!loading">
            <tr v-for="ev in evaluations" :key="ev.id">
              <td>
                <span class="fw-semibold" style="color:#1e293b;">{{ ev.agent?.prenom }} {{ ev.agent?.nom }}</span>
                <small class="d-block text-muted">{{ ev.agent?.matricule_etat || '-' }}</small>
              </td>
              <td><span class="code-badge">{{ ev.organe || '-' }}</span></td>
              <td>{{ periodLabel(ev) }}</td>
              <td>{{ ev.evaluateur ? `${ev.evaluateur.prenom} ${ev.evaluateur.nom}` : '-' }}</td>
              <td class="text-end fw-semibold">{{ formatScore(ev.score_global) }}</td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <router-link :to="{ name: 'performance.agents.show', params: { id: ev.agent_id } }" class="action-btn" title="Voir le détail">
                    <i class="fas fa-eye"></i>
                  </router-link>
                  <button class="action-btn action-btn-success" title="Valider" @click="validate(ev)">
                    <i class="fas fa-check"></i>
                  </button>
                  <button class="action-btn action-btn-danger" title="Rejeter" @click="openReject(ev)">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="evaluations.length === 0">
              <td colspan="6" class="text-center text-muted py-4">Aucune évaluation en attente.</td>
            </tr>
          </tbody>
        </table>
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border" style="color:#0077B5;" role="status"></div>
        </div>
      </div>
    </div>

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
import { onMounted, ref } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
const loading = ref(true)
const evaluations = ref([])
const rejectTarget = ref(null)
const rejectMotif = ref('')

async function load() {
  loading.value = true
  try {
    const response = await client.get('/evaluations', { params: { statut: 'soumise', per_page: 100 } })
    evaluations.value = response.data.data
  } catch (e) {
    ui.addToast(e.response?.data?.message || 'Erreur lors du chargement de la file de validation.', 'danger')
  } finally {
    loading.value = false
  }
}

function periodLabel(ev) {
  return ev.periode_trimestre ? `T${ev.periode_trimestre} ${ev.periode_annee}` : `Année ${ev.periode_annee}`
}

function formatScore(value) {
  if (value === null || value === undefined) return '-'
  return Number(value).toFixed(1)
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
.evaluation-pending-page { padding: 1.5rem 0; }

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
  border: 1px solid #f1f5f9; overflow: hidden;
}
.data-table thead th {
  background: #f8fafc; border: none; font-size: .78rem; font-weight: 600;
  text-transform: uppercase; letter-spacing: .5px; color: #64748b; padding: .85rem 1rem;
}
.data-table tbody td { padding: .75rem 1rem; border-color: #f1f5f9; vertical-align: middle; font-size: .88rem; }
.data-table tbody tr:hover { background: #f8fafc; }

.code-badge {
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  background: rgba(0, 119, 181, .1); color: #005a87; font-size: .78rem;
  font-weight: 600; padding: 3px 10px; border-radius: 6px; border: 1px solid #bae6fd;
}

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
.reject-modal { background: #fff; border-radius: 14px; padding: 1.25rem; width: min(420px, 92vw); }
.reject-modal h6 { font-weight: 800; color: #0f172a; margin-bottom: .75rem; }
.reject-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; }
</style>
