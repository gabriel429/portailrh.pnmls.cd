<template>
  <div class="py-4">
    <h4 class="mb-4"><i class="fas fa-rocket me-2"></i>Deploiement & Maintenance</h4>

    <div class="row g-4">
      <!-- Git Pull -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h5><i class="fas fa-code-branch me-2 text-primary"></i>Git Pull</h5>
            <p class="text-muted small">Recuperer les derniers changements depuis le depot GitHub.</p>
            <button class="btn btn-primary" @click="runAction('git-pull')" :disabled="running">
              <span v-if="running === 'git-pull'" class="spinner-border spinner-border-sm me-1"></span>
              Executer git pull
            </button>
          </div>
        </div>
      </div>

      <!-- Migrate -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h5><i class="fas fa-database me-2 text-success"></i>Migration</h5>
            <p class="text-muted small">Executer les migrations en attente.</p>
            <button class="btn btn-success" @click="runAction('migrate')" :disabled="running">
              <span v-if="running === 'migrate'" class="spinner-border spinner-border-sm me-1"></span>
              Executer migrate
            </button>
          </div>
        </div>
      </div>

      <!-- Migrate Fresh (danger) -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm border-danger">
          <div class="card-body">
            <h5><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Migrate Fresh</h5>
            <p class="text-muted small">Supprimer toutes les tables et re-executer les migrations. <strong class="text-danger">Attention : toutes les donnees seront perdues !</strong></p>
            <button class="btn btn-danger" @click="runAction('migrate-fresh')" :disabled="running">
              <span v-if="running === 'migrate-fresh'" class="spinner-border spinner-border-sm me-1"></span>
              Executer migrate:fresh
            </button>
          </div>
        </div>
      </div>

      <!-- Deploy actions -->
      <div class="col-md-6" v-for="action in deployActions" :key="action.key">
        <div class="card border-0 shadow-sm">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0">
                <i :class="['fas', action.icon, 'me-2']" :style="{ color: action.color }"></i>{{ action.label }}
              </h6>
              <small class="text-muted">{{ action.desc }}</small>
            </div>
            <button class="btn btn-outline-primary btn-sm" @click="runAction(action.key)" :disabled="running">
              <span v-if="running === action.key" class="spinner-border spinner-border-sm me-1"></span>
              Deployer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Result -->
    <div v-if="result" class="mt-4 alert" :class="result.success ? 'alert-success' : 'alert-warning'">
      <div class="d-flex justify-content-between align-items-start">
        <h6 class="mb-2">Resultat :</h6>
        <button type="button" class="btn-close" @click="result = null"></button>
      </div>
      <pre class="mb-0 small" style="white-space: pre-wrap;">{{ result.output }}</pre>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
const running = ref(null)
const result = ref(null)

const deployActions = [
  { key: 'deploy-organes', label: 'Deployer Organes', desc: 'Creer la table organes et seeder SEN/SEP/SEL', icon: 'fa-sitemap', color: '#8b5cf6' },
  { key: 'deploy-users', label: 'Deployer Users', desc: 'Ajouter agent_id et role_id a la table users', icon: 'fa-user-shield', color: '#059669' },
  { key: 'deploy-departments', label: 'Deployer Departements', desc: 'Seeder les departements (DAF, DPP, DSE...)', icon: 'fa-building', color: '#7c3aed' },
  { key: 'deploy-grades', label: 'Deployer Grades', desc: 'Creer la table grades et seeder les categories', icon: 'fa-medal', color: '#eab308' },
  { key: 'deploy-affectations', label: 'Deployer Affectations', desc: 'Creer les tables sections, cellules, localites, affectations', icon: 'fa-project-diagram', color: '#0ea5e9' },
  { key: 'deploy-institutions', label: 'Deployer Institutions', desc: 'Creer les tables institutions et categories', icon: 'fa-landmark', color: '#d97706' },
  { key: 'deploy-messages', label: 'Deployer Messages', desc: 'Creer la table messages', icon: 'fa-envelope', color: '#6366f1' },
  { key: 'deploy-communiques', label: 'Deployer Communiques', desc: 'Creer la table communiques', icon: 'fa-bullhorn', color: '#f43f5e' },
  { key: 'deploy-taches', label: 'Deployer Taches', desc: 'Creer les tables taches et commentaires', icon: 'fa-tasks', color: '#14b8a6' },
  { key: 'deploy-plan-travail', label: 'Deployer Plan Travail', desc: 'Creer la table activite_plans', icon: 'fa-calendar-alt', color: '#0077B5' },
  { key: 'deploy-rename-roles', label: 'Renommer Roles', desc: 'Renommer les roles systeme', icon: 'fa-edit', color: '#64748b' },
  { key: 'deploy-domaine-etudes', label: 'Deployer Domaine Etudes', desc: 'Ajouter la colonne domaine_etudes aux agents', icon: 'fa-graduation-cap', color: '#0d9488' },
]

async function runAction(action) {
  if (!confirm('Executer cette action ?')) return
  running.value = action
  result.value = null
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
    const res = await axios.post(`/admin/deployment/${action}`, {}, {
      headers: {
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      withCredentials: true,
      validateStatus: s => s < 500,
    })
    if (res.status >= 200 && res.status < 400) {
      result.value = { success: true, output: res.data?.message || 'Action executee avec succes.' }
      ui.addToast('Action executee avec succes', 'success')
    } else {
      result.value = { success: false, output: res.data?.message || 'L\'action a retourne un statut inattendu.' }
      ui.addToast('L\'action a retourne un avertissement', 'warning')
    }
  } catch (e) {
    const msg = e.response?.data?.message || e.response?.data || e.message
    result.value = { success: false, output: typeof msg === 'string' ? msg : JSON.stringify(msg) }
    ui.addToast('Erreur lors de l\'execution', 'danger')
  } finally {
    running.value = null
  }
}
</script>
