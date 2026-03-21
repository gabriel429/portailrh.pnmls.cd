<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="sen-hero">
      <div class="sen-hero-content">
        <div class="sen-hero-avatar">
          <i class="fas fa-landmark"></i>
        </div>
        <div class="sen-hero-text">
          <h2>Tableau de bord executif</h2>
          <p class="sen-hero-name">
            {{ auth.agent ? auth.agent.prenom + ' ' + auth.agent.nom : auth.user?.name || 'SEN' }}
          </p>
          <p class="sen-hero-date">{{ today }}</p>
        </div>
      </div>
      <div class="sen-hero-kpis">
        <div class="sen-kpi">
          <div class="sen-kpi-val">{{ data.agents?.actifs ?? '-' }}</div>
          <div class="sen-kpi-lbl">Agents actifs</div>
        </div>
        <div class="sen-kpi">
          <div class="sen-kpi-val">{{ data.attendance?.today_rate ?? 0 }}%</div>
          <div class="sen-kpi-lbl">Presence aujourd'hui</div>
        </div>
        <div class="sen-kpi">
          <div class="sen-kpi-val">{{ data.requests?.en_attente ?? 0 }}</div>
          <div class="sen-kpi-lbl">Demandes en attente</div>
        </div>
        <div class="sen-kpi">
          <div class="sen-kpi-val">{{ data.plan_travail?.avg_completion ?? 0 }}%</div>
          <div class="sen-kpi-lbl">Plan de travail</div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord executif..." />

    <template v-else>
      <!-- Quick Actions -->
      <div class="dash-section-header">
        <div class="dash-section-title"><i class="fas fa-bolt" style="color:#0077B5;"></i> Actions rapides</div>
      </div>
      <div class="dash-action-grid">
        <router-link v-for="a in quickActions" :key="a.to" :to="a.to" class="dash-action-card">
          <div class="dash-action-icon" :style="{ background: a.bg, color: a.color }">
            <i class="fas" :class="a.icon"></i>
          </div>
          <div class="dash-action-info">
            <div class="dash-action-name">{{ a.label }}</div>
            <div class="dash-action-desc">{{ a.desc }}</div>
          </div>
        </router-link>
      </div>

      <!-- Overview Stats -->
      <div class="dash-section-header">
        <div class="dash-section-title"><i class="fas fa-chart-bar" style="color:#0077B5;"></i> Vue d'ensemble</div>
      </div>
      <div class="overview-grid mb-4">
        <div v-for="c in overviewCards" :key="c.label" class="overview-card">
          <div class="overview-icon" :style="{ background: c.color + '18', color: c.color }">
            <i :class="['fas', c.icon]"></i>
          </div>
          <div class="overview-val">{{ c.value }}</div>
          <div class="overview-lbl">{{ c.label }}</div>
        </div>
      </div>

      <!-- Agents par Organe -->
      <div class="dash-section-header">
        <div class="dash-section-title"><i class="fas fa-sitemap" style="color:#0077B5;"></i> Repartition par organe</div>
      </div>
      <div class="row g-3 mb-4">
        <div v-for="o in organeCards" :key="o.code" class="col-md-4">
          <div class="organe-card" :style="{ borderTop: '4px solid ' + o.color }">
            <div class="d-flex align-items-center gap-2 mb-2">
              <div class="organe-icon" :style="{ background: o.color + '20', color: o.color }">
                <i :class="['fas', o.icon]"></i>
              </div>
              <div class="flex-grow-1">
                <div class="fw-bold" style="font-size:.88rem;">{{ o.nom }}</div>
              </div>
              <span class="badge text-white" :style="{ background: o.color }">{{ o.code }}</span>
            </div>
            <div class="organe-count" :style="{ color: o.color }">{{ o.count }}</div>
            <div class="organe-label">agents</div>
          </div>
        </div>
      </div>

      <!-- Plan de Travail -->
      <div class="dash-section-header">
        <div class="dash-section-title"><i class="fas fa-tasks" style="color:#0077B5;"></i> Progres strategique {{ currentYear }}</div>
      </div>
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="plan-global">
            <div class="plan-global-pct">{{ data.plan_travail?.avg_completion ?? 0 }}%</div>
            <div class="plan-global-label">Completion moyenne</div>
            <div class="plan-global-counts">
              {{ data.plan_travail?.terminee ?? 0 }} / {{ data.plan_travail?.total ?? 0 }} terminees
            </div>
          </div>
        </div>
        <div v-for="t in (data.plan_travail?.by_trimestre || [])" :key="t.trimestre" class="col">
          <div class="plan-trim">
            <div class="plan-trim-head">{{ t.trimestre }}</div>
            <div class="plan-trim-bar-bg">
              <div class="plan-trim-bar-fill" :style="{ width: t.avg_pourcentage + '%' }"></div>
            </div>
            <div class="plan-trim-info">{{ t.terminee }}/{{ t.total }} &middot; {{ t.avg_pourcentage }}%</div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="dash-section-header">
        <div class="dash-section-title"><i class="fas fa-history" style="color:#0077B5;"></i> Activites recentes</div>
      </div>
      <div class="row g-3 mb-4">
        <!-- Communiques -->
        <div class="col-md-4">
          <div class="recent-card">
            <div class="recent-header">
              <i class="fas fa-bullhorn me-2" style="color:#0891b2;"></i>
              Communiques ({{ data.communiques?.actifs ?? 0 }})
              <span v-if="data.communiques?.urgents" class="badge bg-danger ms-auto">{{ data.communiques.urgents }} urgent(s)</span>
            </div>
            <div v-if="data.communiques?.recent?.length" class="recent-list">
              <router-link v-for="c in data.communiques.recent" :key="c.id" :to="'/communiques/' + c.id" class="recent-item">
                <span class="recent-item-title">{{ c.titre }}</span>
                <span class="recent-item-time">{{ formatTime(c.created_at) }}</span>
              </router-link>
            </div>
            <div v-else class="recent-empty"><i class="fas fa-inbox me-1"></i>Aucun communique</div>
          </div>
        </div>

        <!-- Demandes en attente -->
        <div class="col-md-4">
          <div class="recent-card">
            <div class="recent-header">
              <i class="fas fa-clock me-2" style="color:#d97706;"></i>
              Demandes en attente ({{ data.requests?.en_attente ?? 0 }})
            </div>
            <div v-if="data.requests?.recent_pending?.length" class="recent-list">
              <div v-for="r in data.requests.recent_pending" :key="r.id" class="recent-item">
                <span class="recent-item-title">{{ r.type }} — {{ r.agent?.prenom }} {{ r.agent?.nom }}</span>
                <span class="recent-item-time">{{ formatTime(r.created_at) }}</span>
              </div>
            </div>
            <div v-else class="recent-empty"><i class="fas fa-check-circle me-1"></i>Aucune demande</div>
          </div>
        </div>

        <!-- Signalements -->
        <div class="col-md-4">
          <div class="recent-card">
            <div class="recent-header">
              <i class="fas fa-flag me-2" style="color:#dc2626;"></i>
              Signalements ({{ data.signalements?.ouvert ?? 0 }} ouverts)
              <span v-if="data.signalements?.haute_severite" class="badge bg-danger ms-auto">{{ data.signalements.haute_severite }} haute</span>
            </div>
            <div v-if="data.signalements?.recent?.length" class="recent-list">
              <router-link v-for="s in data.signalements.recent" :key="s.id" :to="'/signalements/' + s.id" class="recent-item">
                <div class="d-flex align-items-center gap-2">
                  <span class="sev-dot" :class="'sev-' + s.severite"></span>
                  <span class="recent-item-title">{{ s.type }}</span>
                </div>
                <span class="recent-item-time">{{ formatTime(s.created_at) }}</span>
              </router-link>
            </div>
            <div v-else class="recent-empty"><i class="fas fa-shield-alt me-1"></i>Aucun signalement</div>
          </div>
        </div>
      </div>

      <!-- Operational Health -->
      <div class="dash-section-header">
        <div class="dash-section-title"><i class="fas fa-heartbeat" style="color:#0077B5;"></i> Sante operationnelle</div>
      </div>
      <div class="row g-3">
        <!-- Attendance -->
        <div class="col-md-6">
          <div class="health-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-user-check me-2" style="color:#059669;"></i>Presence</h6>
            <div class="health-row">
              <span>Aujourd'hui</span>
              <strong>{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }} ({{ data.attendance?.today_rate ?? 0 }}%)</strong>
            </div>
            <div class="health-bar"><div class="health-bar-fill" style="background:#059669;" :style="{ width: (data.attendance?.today_rate ?? 0) + '%' }"></div></div>
            <div class="health-row mt-2">
              <span>Moyenne mensuelle</span>
              <strong>{{ data.attendance?.monthly_avg_rate ?? 0 }}%</strong>
            </div>
            <div class="health-bar"><div class="health-bar-fill" style="background:#0077B5;" :style="{ width: (data.attendance?.monthly_avg_rate ?? 0) + '%' }"></div></div>
          </div>
        </div>
        <!-- Taches -->
        <div class="col-md-6">
          <div class="health-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-tasks me-2" style="color:#7c3aed;"></i>Taches</h6>
            <div class="health-row"><span>Nouvelles</span><strong>{{ data.taches?.nouvelle ?? 0 }}</strong></div>
            <div class="health-row"><span>En cours</span><strong>{{ data.taches?.en_cours ?? 0 }}</strong></div>
            <div class="health-row"><span>Terminees</span><strong>{{ data.taches?.terminee ?? 0 }}</strong></div>
            <div v-if="data.taches?.overdue" class="health-row text-danger">
              <span><i class="fas fa-exclamation-triangle me-1"></i>En retard</span>
              <strong>{{ data.taches.overdue }}</strong>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const auth = useAuthStore()
const loading = ref(true)
const data = ref({})
const currentYear = new Date().getFullYear()

const today = computed(() => new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
}))

const quickActions = [
  { to: '/rh/communiques/create', label: 'Nouveau communique', desc: 'Publier un communique', icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe' },
  { to: '/rh/agents', label: 'Gestion agents', desc: 'Voir tous les agents', icon: 'fa-users', color: '#0077B5', bg: '#e0f2fe' },
  { to: '/signalements', label: 'Signalements', desc: 'Consulter les alertes', icon: 'fa-flag', color: '#dc2626', bg: '#fee2e2' },
  { to: '/plan-travail', label: 'Plan de travail', desc: 'Suivi strategique', icon: 'fa-tasks', color: '#d97706', bg: '#fef3c7' },
  { to: '/rh/pointages/monthly', label: 'Pointages', desc: 'Rapport mensuel', icon: 'fa-clock', color: '#7c3aed', bg: '#ede9fe' },
  { to: '/requests', label: 'Demandes', desc: 'Gerer les demandes', icon: 'fa-paper-plane', color: '#059669', bg: '#d1fae5' },
]

const overviewCards = computed(() => [
  { label: 'Agents total', value: data.value.agents?.total ?? 0, icon: 'fa-users', color: '#0077B5' },
  { label: 'Agents actifs', value: data.value.agents?.actifs ?? 0, icon: 'fa-user-check', color: '#059669' },
  { label: 'Demandes en attente', value: data.value.requests?.en_attente ?? 0, icon: 'fa-clock', color: '#d97706' },
  { label: 'Signalements ouverts', value: data.value.signalements?.ouvert ?? 0, icon: 'fa-flag', color: '#dc2626' },
  { label: 'Taches en cours', value: data.value.taches?.en_cours ?? 0, icon: 'fa-spinner', color: '#7c3aed' },
  { label: 'Communiques actifs', value: data.value.communiques?.actifs ?? 0, icon: 'fa-bullhorn', color: '#0891b2' },
  { label: 'Plan completion', value: (data.value.plan_travail?.avg_completion ?? 0) + '%', icon: 'fa-chart-line', color: '#059669' },
  { label: "Presence aujourd'hui", value: (data.value.attendance?.today_rate ?? 0) + '%', icon: 'fa-calendar-check', color: '#0ea5e9' },
  { label: 'Haute severite', value: data.value.signalements?.haute_severite ?? 0, icon: 'fa-exclamation-triangle', color: '#f43f5e' },
  { label: 'Taches en retard', value: data.value.taches?.overdue ?? 0, icon: 'fa-calendar-times', color: '#ef4444' },
  { label: 'Documents', value: data.value.documents?.total ?? 0, icon: 'fa-folder-open', color: '#6366f1' },
  { label: 'Agents suspendus', value: data.value.agents?.suspendus ?? 0, icon: 'fa-user-slash', color: '#64748b' },
])

const organeCards = computed(() => [
  { code: 'SEN', nom: 'Secretariat Executif National', count: data.value.agents?.by_organe?.sen ?? 0, icon: 'fa-flag', color: '#0077B5' },
  { code: 'SEP', nom: 'Secretariat Executif Provincial', count: data.value.agents?.by_organe?.sep ?? 0, icon: 'fa-map-marked-alt', color: '#0ea5e9' },
  { code: 'SEL', nom: 'Secretariat Executif Local', count: data.value.agents?.by_organe?.sel ?? 0, icon: 'fa-map-pin', color: '#0d9488' },
])

function formatTime(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1) return "A l'instant"
  if (diff < 60) return `Il y a ${diff} min`
  if (diff < 1440) return `Il y a ${Math.floor(diff / 60)}h`
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

onMounted(async () => {
  try {
    const { data: result } = await client.get('/dashboard/executive')
    data.value = result
  } catch (e) {
    console.error('Erreur chargement dashboard executif:', e)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* ── Hero ── */
.sen-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 40%, #0077B5 100%);
  border-radius: 18px; padding: 2rem 2.2rem; margin-bottom: 1.5rem; color: #fff;
  position: relative; overflow: hidden;
}
.sen-hero::before {
  content: ''; position: absolute; top: -50%; right: -10%;
  width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,.04);
}
.sen-hero-content { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.2rem; }
.sen-hero-avatar {
  width: 56px; height: 56px; border-radius: 14px;
  background: rgba(255,255,255,.12); display: flex; align-items: center;
  justify-content: center; font-size: 1.5rem; flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.15);
}
.sen-hero-text h2 { font-size: 1.35rem; font-weight: 800; margin: 0 0 .15rem; }
.sen-hero-name { font-size: .9rem; opacity: .85; margin: 0 0 .1rem; font-weight: 500; }
.sen-hero-date { font-size: .75rem; opacity: .55; margin: 0; text-transform: capitalize; }
.sen-hero-kpis { display: flex; gap: 2rem; flex-wrap: wrap; }
.sen-kpi { text-align: center; }
.sen-kpi-val { font-size: 1.6rem; font-weight: 800; line-height: 1.1; }
.sen-kpi-lbl { font-size: .68rem; opacity: .6; text-transform: uppercase; letter-spacing: .5px; margin-top: .15rem; }

/* ── Overview grid ── */
.overview-grid {
  display: grid; grid-template-columns: repeat(6, 1fr); gap: .65rem;
}
.overview-card {
  background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(0,0,0,.04); padding: .85rem .7rem;
  text-align: center; transition: all .2s;
}
.overview-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
.overview-icon {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .85rem;
  margin: 0 auto .5rem;
}
.overview-val { font-size: 1.35rem; font-weight: 800; color: #1e293b; line-height: 1; }
.overview-lbl { font-size: .65rem; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-top: .25rem; }

/* ── Organe cards ── */
.organe-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); padding: 1.2rem; text-align: center;
}
.organe-icon {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.organe-count { font-size: 2rem; font-weight: 800; line-height: 1; margin-top: .5rem; }
.organe-label { font-size: .7rem; color: #9ca3af; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; }

/* ── Plan de travail ── */
.plan-global {
  background: linear-gradient(135deg, #0077B5, #005a87); border-radius: 14px;
  padding: 1.5rem; color: #fff; text-align: center; height: 100%;
  display: flex; flex-direction: column; justify-content: center;
}
.plan-global-pct { font-size: 2.5rem; font-weight: 900; line-height: 1; }
.plan-global-label { font-size: .75rem; opacity: .7; text-transform: uppercase; margin-top: .3rem; }
.plan-global-counts { font-size: .8rem; opacity: .8; margin-top: .5rem; }

.plan-trim {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(0,0,0,.04); padding: 1rem; text-align: center;
  height: 100%; display: flex; flex-direction: column; justify-content: center;
}
.plan-trim-head { font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: .6rem; }
.plan-trim-bar-bg { height: 8px; background: #f3f4f6; border-radius: 8px; overflow: hidden; margin-bottom: .5rem; }
.plan-trim-bar-fill { height: 100%; background: linear-gradient(90deg, #0077B5, #0ea5e9); border-radius: 8px; transition: width .6s ease; }
.plan-trim-info { font-size: .72rem; color: #9ca3af; font-weight: 600; }

/* ── Recent cards ── */
.recent-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); overflow: hidden; height: 100%;
}
.recent-header {
  padding: .85rem 1rem; background: #f8fafc; border-bottom: 1px solid #f1f5f9;
  font-weight: 700; font-size: .85rem; color: #1e293b;
  display: flex; align-items: center;
}
.recent-list { max-height: 240px; overflow-y: auto; }
.recent-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: .6rem 1rem; border-bottom: 1px solid #f8fafc;
  text-decoration: none; color: inherit; transition: background .15s;
}
.recent-item:hover { background: #f0f9ff; }
.recent-item-title { font-size: .8rem; font-weight: 600; color: #1e293b; }
.recent-item-time { font-size: .68rem; color: #9ca3af; white-space: nowrap; margin-left: .5rem; }
.recent-empty { padding: 1.5rem; text-align: center; color: #9ca3af; font-size: .82rem; }

.sev-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sev-basse { background: #22c55e; }
.sev-moyenne { background: #f59e0b; }
.sev-haute { background: #ef4444; }

/* ── Health cards ── */
.health-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); padding: 1.2rem;
}
.health-row {
  display: flex; justify-content: space-between; align-items: center;
  font-size: .84rem; color: #374151; margin-bottom: .3rem;
}
.health-bar { height: 6px; background: #f3f4f6; border-radius: 6px; overflow: hidden; margin-bottom: .6rem; }
.health-bar-fill { height: 100%; border-radius: 6px; transition: width .6s ease; min-width: 2px; }

/* ── Responsive ── */
@media (max-width: 991.98px) {
  .overview-grid { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 767.98px) {
  .sen-hero { padding: 1.25rem 1rem; }
  .sen-hero-content { gap: .75rem; }
  .sen-hero-avatar { width: 44px; height: 44px; font-size: 1.2rem; }
  .sen-hero-text h2 { font-size: 1.1rem; }
  .sen-hero-kpis { gap: 1rem; }
  .sen-kpi-val { font-size: 1.2rem; }
  .overview-grid { grid-template-columns: repeat(3, 1fr); gap: .5rem; }
  .overview-card { padding: .65rem .5rem; }
  .overview-val { font-size: 1.1rem; }
  .overview-icon { width: 30px; height: 30px; font-size: .75rem; }
}
@media (max-width: 575.98px) {
  .overview-grid { grid-template-columns: repeat(2, 1fr); }
  .sen-hero-kpis { gap: .75rem; justify-content: space-between; width: 100%; }
}
</style>
