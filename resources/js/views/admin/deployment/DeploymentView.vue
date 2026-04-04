<template>
  <div class="deployment-page">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-rocket"></i>
        </div>
        <div>
          <h4 class="text-white mb-0 fw-bold">Deploiement</h4>
          <small class="text-white-50">Actions de deploiement et maintenance</small>
        </div>
      </div>
      <RouterLink :to="{ name: 'admin.agents.import' }" class="hero-action-btn">
        <i class="fas fa-file-import me-2"></i>
        Charger fichiers agents
      </RouterLink>
    </div>

    <!-- Result Alert -->
    <div v-if="result" class="result-alert" :class="result.success ? 'result-success' : 'result-error'">
      <div class="d-flex justify-content-between align-items-start">
        <div class="d-flex align-items-center gap-2">
          <i class="fas" :class="result.success ? 'fa-check-circle' : 'fa-exclamation-triangle'"></i>
          <strong>{{ result.success ? 'Succes' : 'Avertissement' }}</strong>
        </div>
        <button class="result-close" @click="result = null">&times;</button>
      </div>
      <pre class="result-output">{{ result.output }}</pre>
    </div>

    <!-- Section: Deploiement -->
    <div class="section-block">
      <div class="section-header">
        <div class="section-icon section-icon-deploy">
          <i class="fas fa-code-branch"></i>
        </div>
        <h6 class="section-title">Deploiement</h6>
      </div>
      <div class="row g-3">
        <!-- Git Pull -->
        <div class="col-md-6 col-lg-4">
          <div class="deploy-card">
            <div class="deploy-card-icon" style="background: #eff6ff; color: #2563eb;">
              <i class="fas fa-code-branch"></i>
            </div>
            <div class="deploy-card-body">
              <h6 class="deploy-card-title">Git Pull</h6>
              <p class="deploy-card-desc">Recuperer les derniers changements depuis le depot GitHub.</p>
            </div>
            <button class="deploy-btn deploy-btn-blue" @click="runAction('git-pull')" :disabled="running">
              <span v-if="running === 'git-pull'" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-play me-1"></i>
              Executer
            </button>
          </div>
        </div>

        <!-- Migrate -->
        <div class="col-md-6 col-lg-4">
          <div class="deploy-card">
            <div class="deploy-card-icon" style="background: #f0fdf4; color: #16a34a;">
              <i class="fas fa-database"></i>
            </div>
            <div class="deploy-card-body">
              <h6 class="deploy-card-title">Migration</h6>
              <p class="deploy-card-desc">Executer les migrations en attente puis vider les caches Laravel.</p>
            </div>
            <button class="deploy-btn deploy-btn-green" @click="runAction('migrate')" :disabled="running">
              <span v-if="running === 'migrate'" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-play me-1"></i>
              Executer
            </button>
          </div>
        </div>

        <!-- Migrate Fresh -->
        <div class="col-md-6 col-lg-4">
          <div class="deploy-card deploy-card-danger">
            <div class="deploy-card-icon" style="background: #fef2f2; color: #dc2626;">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="deploy-card-body">
              <h6 class="deploy-card-title">Migrate Fresh</h6>
              <p class="deploy-card-desc">Supprimer toutes les tables et re-executer. <strong class="text-danger">Donnees perdues !</strong></p>
            </div>
            <button class="deploy-btn deploy-btn-red" @click="runAction('migrate-fresh')" :disabled="running">
              <span v-if="running === 'migrate-fresh'" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-play me-1"></i>
              Executer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Section: Modules -->
    <div class="section-block">
      <div class="section-header">
        <div class="section-icon section-icon-modules">
          <i class="fas fa-cubes"></i>
        </div>
        <h6 class="section-title">Deploiement des Modules</h6>
      </div>
      <div class="row g-3">
        <div v-for="action in deployActions" :key="action.key" class="col-md-6 col-lg-4">
          <div class="deploy-card">
            <div class="deploy-card-icon" :style="{ background: getIconBg(action.color), color: action.color }">
              <i :class="['fas', action.icon]"></i>
            </div>
            <div class="deploy-card-body">
              <h6 class="deploy-card-title">{{ action.label }}</h6>
              <p class="deploy-card-desc">{{ action.desc }}</p>
            </div>
            <button class="deploy-btn" @click="runAction(action.key)" :disabled="running">
              <span v-if="running === action.key" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-play me-1"></i>
              Deployer
            </button>
          </div>
        </div>
      </div>
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
  { key: 'seed-superadmin', label: 'Seed SuperAdmin', desc: 'Creer ou mettre a jour le compte SuperAdmin', icon: 'fa-shield-alt', color: '#7c3aed' },
  { key: 'deploy-holidays', label: 'Deployer Conges & Statuts', desc: 'Creer les tables holiday_plannings, agent_statuses et holidays', icon: 'fa-umbrella-beach', color: '#0ea5e9' },
]

function getIconBg(color) {
  return color + '15'
}

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

<style scoped>
.deployment-page {
  padding: 1.5rem 0;
}

.page-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #003f5f 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(0, 119, 181, .25);
  margin-bottom: 1.5rem;
  position: relative;
  overflow: hidden;
}
.page-hero::after {
    content: '';
    position: absolute;
    right: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 200px;
    height: 200px;
    background: url('/images/pnmls.jpeg') center/contain no-repeat;
    opacity: 0.10;
    pointer-events: none;
}

.page-hero-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.hero-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .25rem;
  padding: .85rem 1.1rem;
  border-radius: 12px;
  background: rgba(255, 255, 255, .16);
  color: #fff;
  text-decoration: none;
  font-weight: 700;
  border: 1px solid rgba(255, 255, 255, .22);
  backdrop-filter: blur(8px);
  transition: transform .2s ease, background .2s ease;
}

.hero-action-btn:hover {
  color: #fff;
  background: rgba(255, 255, 255, .24);
  transform: translateY(-1px);
}

.page-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, .15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #fff;
}

/* Result Alert */
.result-alert {
  border-radius: 14px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.25rem;
  border: 1px solid;
}

.result-success {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #166534;
}

.result-error {
  background: #fffbeb;
  border-color: #fde68a;
  color: #92400e;
}

.result-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: inherit;
  opacity: .6;
  line-height: 1;
}

.result-close:hover {
  opacity: 1;
}

.result-output {
  margin: .75rem 0 0;
  font-size: .82rem;
  white-space: pre-wrap;
  word-break: break-all;
  background: rgba(0, 0, 0, .04);
  padding: .75rem;
  border-radius: 8px;
}

/* Section Blocks */
.section-block {
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  margin-bottom: 1rem;
}

.section-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .9rem;
}

.section-icon-deploy {
  background: #eff6ff;
  color: #2563eb;
}

.section-icon-modules {
  background: #faf5ff;
  color: #7c3aed;
}

.section-title {
  margin: 0;
  font-weight: 700;
  color: #1e293b;
  font-size: .95rem;
}

/* Deploy Cards */
.deploy-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
  border: 1px solid #f1f5f9;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  height: 100%;
  transition: all .2s;
}

.deploy-card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
  transform: translateY(-2px);
}

.deploy-card-danger {
  border-color: #fecaca;
}

.deploy-card-danger:hover {
  border-color: #fca5a5;
}

.deploy-card-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  margin-bottom: .75rem;
}

.deploy-card-body {
  flex: 1;
  margin-bottom: .75rem;
}

.deploy-card-title {
  font-weight: 600;
  font-size: .9rem;
  color: #1e293b;
  margin-bottom: .25rem;
}

.deploy-card-desc {
  font-size: .8rem;
  color: #64748b;
  margin: 0;
  line-height: 1.4;
}

.deploy-btn {
  width: 100%;
  padding: .5rem 1rem;
  border-radius: 10px;
  font-size: .82rem;
  font-weight: 600;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #475569;
  cursor: pointer;
  transition: all .2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.deploy-btn:hover:not(:disabled) {
  background: #0077B5;
  border-color: #0077B5;
  color: #fff;
}

.deploy-btn:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.deploy-btn-blue {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #2563eb;
}

.deploy-btn-blue:hover:not(:disabled) {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

.deploy-btn-green {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #16a34a;
}

.deploy-btn-green:hover:not(:disabled) {
  background: #16a34a;
  border-color: #16a34a;
  color: #fff;
}

.deploy-btn-red {
  background: #fef2f2;
  border-color: #fecaca;
  color: #dc2626;
}

.deploy-btn-red:hover:not(:disabled) {
  background: #dc2626;
  border-color: #dc2626;
  color: #fff;
}

/* ── Mobile Responsive ── */
@media (max-width: 767.98px) {
  .page-hero {
    padding: 1.25rem 1rem;
    border-radius: 12px;
  }
  .page-hero::after {
    width: 120px;
    height: 120px;
  }
  .page-hero h4 {
    font-size: 1.1rem;
  }
  .page-hero small {
    font-size: .78rem;
  }
  .page-hero-icon {
    width: 42px;
    height: 42px;
    font-size: 1.1rem;
    border-radius: 10px;
  }
  .result-alert {
    border-radius: 10px;
    padding: .85rem 1rem;
  }
  .result-output {
    font-size: .75rem;
    padding: .6rem;
  }
  .section-block {
    margin-bottom: 1.5rem;
  }
  .deploy-card {
    border-radius: 10px;
    padding: 1rem;
  }
  .deploy-card-icon {
    width: 36px;
    height: 36px;
    font-size: .95rem;
    border-radius: 10px;
  }
  .deploy-card-title {
    font-size: .85rem;
  }
  .deploy-card-desc {
    font-size: .75rem;
  }
  .deploy-btn {
    font-size: .78rem;
    padding: .4rem .85rem;
  }
}
</style>
