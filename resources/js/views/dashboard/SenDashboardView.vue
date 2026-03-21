<template>
  <div class="sen-dashboard">
    <!-- ═══ HERO ═══ -->
    <div class="sen-hero">
      <div class="sen-hero-bg"></div>
      <div class="sen-hero-inner">
        <div class="sen-hero-left">
          <div class="sen-hero-badge">
            <i class="fas fa-crown"></i>
          </div>
          <div>
            <div class="sen-hero-greeting">Bienvenue,</div>
            <h1 class="sen-hero-name">
              {{ auth.agent ? auth.agent.prenom + ' ' + auth.agent.nom : auth.user?.name || 'SEN' }}
            </h1>
            <div class="sen-hero-role">
              <i class="fas fa-shield-alt me-1"></i>
              Secretariat Executif National
            </div>
            <div class="sen-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>
        <div class="sen-hero-kpis">
          <div class="sen-kpi">
            <div class="sen-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="sen-kpi-val">{{ data.agents?.actifs ?? '-' }}</div>
              <div class="sen-kpi-lbl">Agents actifs</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sen-kpi">
            <div class="sen-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="sen-kpi-val">{{ data.attendance?.today_rate ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="sen-kpi-lbl">Presence</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sen-kpi">
            <div class="sen-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="sen-kpi-val">{{ data.requests?.en_attente ?? 0 }}</div>
              <div class="sen-kpi-lbl">En attente</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sen-kpi">
            <div class="sen-kpi-icon"><i class="fas fa-bullseye"></i></div>
            <div>
              <div class="sen-kpi-val">{{ data.plan_travail?.avg_completion ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="sen-kpi-lbl">Plan annuel</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord executif..." />

    <template v-else>
      <!-- ═══ QUICK ACTIONS ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#e0f2fe;color:#0077B5;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Actions rapides</h3>
            <p class="sen-section-sub">Acces direct aux modules cles</p>
          </div>
        </div>
        <div class="sen-actions">
          <router-link v-for="a in quickActions" :key="a.to" :to="a.to" class="sen-action">
            <div class="sen-action-glow" :style="{ background: a.color }"></div>
            <div class="sen-action-icon" :style="{ background: a.bg, color: a.color }">
              <i class="fas" :class="a.icon"></i>
            </div>
            <div class="sen-action-text">
              <div class="sen-action-label">{{ a.label }}</div>
              <div class="sen-action-desc">{{ a.desc }}</div>
            </div>
            <i class="fas fa-chevron-right sen-action-arrow"></i>
          </router-link>
        </div>
      </div>

      <!-- ═══ KEY METRICS ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Indicateurs cles</h3>
            <p class="sen-section-sub">Vue d'ensemble de l'organisation</p>
          </div>
        </div>
        <div class="sen-metrics">
          <div v-for="m in metrics" :key="m.label" class="sen-metric">
            <div class="sen-metric-header">
              <div class="sen-metric-icon" :style="{ background: m.bg, color: m.color }">
                <i class="fas" :class="m.icon"></i>
              </div>
              <span v-if="m.alert" class="sen-metric-alert">
                <i class="fas fa-exclamation-circle"></i>
              </span>
            </div>
            <div class="sen-metric-val" :style="{ color: m.color }">{{ m.value }}</div>
            <div class="sen-metric-lbl">{{ m.label }}</div>
            <div class="sen-metric-bar">
              <div class="sen-metric-bar-fill" :style="{ background: m.color, width: m.pct + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ AGENTS PAR ORGANE (full width, detailed) ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fas fa-sitemap"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Effectifs par organe</h3>
            <p class="sen-section-sub">{{ data.agents?.total ?? 0 }} agents au total, dont {{ data.agents?.actifs ?? 0 }} actifs</p>
          </div>
        </div>
        <div class="sen-organe-grid">
          <div v-for="o in organeCards" :key="o.code" class="sen-organe-card" :style="{ borderTop: '4px solid ' + o.color }">
            <div class="sen-organe-header">
              <div class="sen-organe-badge" :style="{ background: o.color }">{{ o.code }}</div>
              <div>
                <div class="sen-organe-name">{{ o.nom }}</div>
                <div class="sen-organe-sub">{{ orgPct(o.total) }}% de l'effectif total</div>
              </div>
            </div>
            <div class="sen-organe-stats">
              <div class="sen-organe-stat">
                <div class="sen-organe-stat-val" style="color:#1e293b;">{{ o.total }}</div>
                <div class="sen-organe-stat-lbl">Total</div>
              </div>
              <div class="sen-organe-stat">
                <div class="sen-organe-stat-val" style="color:#059669;">{{ o.actifs }}</div>
                <div class="sen-organe-stat-lbl">Actifs</div>
              </div>
              <div class="sen-organe-stat">
                <div class="sen-organe-stat-val" style="color:#d97706;">{{ o.suspendus }}</div>
                <div class="sen-organe-stat-lbl">Suspendus</div>
              </div>
              <div class="sen-organe-stat">
                <div class="sen-organe-stat-val" style="color:#64748b;">{{ o.anciens }}</div>
                <div class="sen-organe-stat-lbl">Anciens</div>
              </div>
            </div>
            <div class="sen-organe-bar-wrap">
              <div class="sen-organe-bar-bg">
                <div class="sen-organe-bar-fill" :style="{ background: o.color, width: orgPct(o.actifs) + '%' }"></div>
              </div>
              <div class="sen-organe-bar-label">{{ o.actifs }} actifs sur {{ data.agents?.actifs ?? 0 }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PRESENCE PAR ORGANE ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-user-check"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Presence par organe</h3>
            <p class="sen-section-sub">{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }} presents aujourd'hui ({{ data.attendance?.today_rate ?? 0 }}%)</p>
          </div>
        </div>
        <div class="sen-presence-grid">
          <!-- Global -->
          <div class="sen-presence-card sen-presence-global">
            <div class="sen-presence-card-head">
              <i class="fas fa-globe-africa"></i> Global
            </div>
            <div class="sen-presence-big">{{ data.attendance?.today_rate ?? 0 }}%</div>
            <div class="sen-presence-sub">{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }}</div>
            <div class="sen-presence-item">
              <span>Aujourd'hui</span>
              <span class="fw-bold">{{ data.attendance?.today_rate ?? 0 }}%</span>
            </div>
            <div class="sen-presence-bar">
              <div class="sen-presence-fill" style="background:linear-gradient(90deg,#059669,#34d399);" :style="{ width: (data.attendance?.today_rate ?? 0) + '%' }"></div>
            </div>
            <div class="sen-presence-item">
              <span>Moy. mensuelle</span>
              <span class="fw-bold">{{ data.attendance?.monthly_avg_rate ?? 0 }}%</span>
            </div>
            <div class="sen-presence-bar">
              <div class="sen-presence-fill" style="background:linear-gradient(90deg,#0077B5,#38bdf8);" :style="{ width: (data.attendance?.monthly_avg_rate ?? 0) + '%' }"></div>
            </div>
          </div>
          <!-- Par organe -->
          <div v-for="o in presenceOrganes" :key="o.code" class="sen-presence-card">
            <div class="sen-presence-card-head" :style="{ color: o.color }">
              <i class="fas" :class="o.icon"></i> {{ o.label }}
            </div>
            <div class="sen-presence-big" :style="{ color: o.color }">{{ o.today_rate }}%</div>
            <div class="sen-presence-sub">{{ o.today_present }} / {{ o.total_active }} agents</div>
            <div class="sen-presence-item">
              <span>Aujourd'hui</span>
              <span class="fw-bold">{{ o.today_rate }}%</span>
            </div>
            <div class="sen-presence-bar">
              <div class="sen-presence-fill" :style="{ background: o.color, width: o.today_rate + '%' }"></div>
            </div>
            <div class="sen-presence-item">
              <span>Moy. mensuelle</span>
              <span class="fw-bold">{{ o.monthly_rate }}%</span>
            </div>
            <div class="sen-presence-bar">
              <div class="sen-presence-fill" :style="{ background: o.color, width: o.monthly_rate + '%', opacity: .6 }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PLAN DE TRAVAIL PAR ORGANE ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-tasks"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Plan de travail {{ currentYear }} par organe</h3>
            <p class="sen-section-sub">{{ data.plan_travail?.terminee ?? 0 }} / {{ data.plan_travail?.total ?? 0 }} activites terminees ({{ data.plan_travail?.avg_completion ?? 0 }}% global)</p>
          </div>
        </div>

        <!-- Global trimestres -->
        <div class="sen-plan-global-row">
          <div class="sen-plan-ring-wrap">
            <svg viewBox="0 0 120 120" class="sen-ring-svg">
              <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="10"/>
              <circle cx="60" cy="60" r="52" fill="none" stroke="#0077B5" stroke-width="10"
                stroke-linecap="round"
                :stroke-dasharray="ringDash"
                :stroke-dashoffset="ringOffset"
                transform="rotate(-90 60 60)"/>
            </svg>
            <div class="sen-ring-center">
              <div class="sen-ring-val">{{ data.plan_travail?.avg_completion ?? 0 }}%</div>
              <div class="sen-ring-lbl">Global</div>
            </div>
          </div>
          <div class="sen-plan-trims">
            <div v-for="t in (data.plan_travail?.by_trimestre || [])" :key="t.trimestre" class="sen-trim">
              <div class="sen-trim-head">
                <span class="sen-trim-name">{{ t.trimestre }}</span>
                <span class="sen-trim-pct">{{ t.avg_pourcentage }}%</span>
              </div>
              <div class="sen-trim-bar">
                <div class="sen-trim-fill" :style="{ width: t.avg_pourcentage + '%' }"></div>
              </div>
              <div class="sen-trim-detail">{{ t.terminee }}/{{ t.total }} terminees</div>
            </div>
          </div>
        </div>

        <!-- Par organe -->
        <div class="sen-plan-organe-grid">
          <div v-for="po in planOrganes" :key="po.code" class="sen-plan-organe-card" :style="{ borderLeft: '4px solid ' + po.color }">
            <div class="sen-plan-organe-head">
              <div class="sen-plan-organe-badge" :style="{ background: po.color }">{{ po.code }}</div>
              <div class="sen-plan-organe-info">
                <div class="sen-plan-organe-name">{{ po.label }}</div>
                <div class="sen-plan-organe-summary">{{ po.terminee }}/{{ po.total }} terminees &middot; <strong>{{ po.avg }}%</strong></div>
              </div>
              <div class="sen-plan-organe-pct" :style="{ color: po.color }">{{ po.avg }}%</div>
            </div>
            <div class="sen-plan-organe-trims">
              <div v-for="t in po.trims" :key="t.trimestre" class="sen-plan-organe-trim">
                <div class="sen-plan-organe-trim-head">
                  <span>{{ t.trimestre }}</span>
                  <span class="fw-bold">{{ t.avg_pourcentage }}%</span>
                </div>
                <div class="sen-plan-organe-trim-bar">
                  <div class="sen-plan-organe-trim-fill" :style="{ width: t.avg_pourcentage + '%', background: po.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ ACTIVITES RECENTES ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#fce7f3;color:#db2777;">
            <i class="fas fa-stream"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Activites recentes</h3>
            <p class="sen-section-sub">Dernieres mises a jour organisationnelles</p>
          </div>
        </div>
        <div class="sen-recent-grid">
          <!-- Communiques -->
          <div class="sen-recent-col">
            <div class="sen-recent-head" style="border-color:#0891b2;">
              <div class="sen-recent-head-icon" style="background:#cffafe;color:#0891b2;">
                <i class="fas fa-bullhorn"></i>
              </div>
              <div>
                <div class="sen-recent-head-title">Communiques</div>
                <div class="sen-recent-head-count">{{ data.communiques?.actifs ?? 0 }} actifs</div>
              </div>
              <span v-if="data.communiques?.urgents" class="sen-alert-badge">{{ data.communiques.urgents }} urgent(s)</span>
            </div>
            <div v-if="data.communiques?.recent?.length" class="sen-recent-body">
              <router-link v-for="c in data.communiques.recent" :key="c.id" :to="'/communiques/' + c.id" class="sen-recent-item">
                <div class="sen-recent-dot" style="background:#0891b2;"></div>
                <div class="sen-recent-info">
                  <div class="sen-recent-title">{{ c.titre }}</div>
                  <div class="sen-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(c.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="sen-recent-empty">
              <i class="fas fa-inbox"></i>
              <span>Aucun communique</span>
            </div>
          </div>

          <!-- Demandes en attente -->
          <div class="sen-recent-col">
            <div class="sen-recent-head" style="border-color:#d97706;">
              <div class="sen-recent-head-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <div class="sen-recent-head-title">Demandes en attente</div>
                <div class="sen-recent-head-count">{{ data.requests?.en_attente ?? 0 }} en attente</div>
              </div>
            </div>
            <div v-if="data.requests?.recent_pending?.length" class="sen-recent-body">
              <div v-for="r in data.requests.recent_pending" :key="r.id" class="sen-recent-item">
                <div class="sen-recent-dot" style="background:#d97706;"></div>
                <div class="sen-recent-info">
                  <div class="sen-recent-title">{{ r.type }} — {{ r.agent?.prenom }} {{ r.agent?.nom }}</div>
                  <div class="sen-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(r.created_at) }}</div>
                </div>
              </div>
            </div>
            <div v-else class="sen-recent-empty">
              <i class="fas fa-check-circle"></i>
              <span>Aucune demande</span>
            </div>
          </div>

          <!-- Signalements -->
          <div class="sen-recent-col">
            <div class="sen-recent-head" style="border-color:#dc2626;">
              <div class="sen-recent-head-icon" style="background:#fee2e2;color:#dc2626;">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div>
                <div class="sen-recent-head-title">Signalements</div>
                <div class="sen-recent-head-count">{{ data.signalements?.ouvert ?? 0 }} ouverts</div>
              </div>
              <span v-if="data.signalements?.haute_severite" class="sen-alert-badge">{{ data.signalements.haute_severite }} haute</span>
            </div>
            <div v-if="data.signalements?.recent?.length" class="sen-recent-body">
              <router-link v-for="s in data.signalements.recent" :key="s.id" :to="'/signalements/' + s.id" class="sen-recent-item">
                <div class="sen-recent-dot" :class="'sev-bg-' + s.severite"></div>
                <div class="sen-recent-info">
                  <div class="sen-recent-title">{{ s.type }}
                    <span class="sen-sev-badge" :class="'sev-' + s.severite">{{ s.severite }}</span>
                  </div>
                  <div class="sen-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(s.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="sen-recent-empty">
              <i class="fas fa-shield-alt"></i>
              <span>Aucun signalement</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ TACHES ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Gestion des taches</h3>
            <p class="sen-section-sub">{{ data.taches?.total ?? 0 }} taches au total</p>
          </div>
        </div>
        <div class="sen-taches-card">
          <div class="sen-task-items">
            <div class="sen-task-row">
              <div class="sen-task-dot" style="background:#3b82f6;"></div>
              <span>Nouvelles</span>
              <span class="sen-task-count">{{ data.taches?.nouvelle ?? 0 }}</span>
            </div>
            <div class="sen-task-row">
              <div class="sen-task-dot" style="background:#f59e0b;"></div>
              <span>En cours</span>
              <span class="sen-task-count">{{ data.taches?.en_cours ?? 0 }}</span>
            </div>
            <div class="sen-task-row">
              <div class="sen-task-dot" style="background:#22c55e;"></div>
              <span>Terminees</span>
              <span class="sen-task-count">{{ data.taches?.terminee ?? 0 }}</span>
            </div>
            <div v-if="data.taches?.overdue" class="sen-task-row sen-task-overdue">
              <div class="sen-task-dot" style="background:#ef4444;"></div>
              <span><i class="fas fa-exclamation-triangle me-1"></i>En retard</span>
              <span class="sen-task-count">{{ data.taches.overdue }}</span>
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

const maxMetric = computed(() => {
  const vals = [
    data.value.agents?.total ?? 0,
    data.value.agents?.actifs ?? 0,
    data.value.requests?.en_attente ?? 0,
    data.value.requests?.approuve ?? 0,
    data.value.signalements?.ouvert ?? 0,
    data.value.taches?.en_cours ?? 0,
    data.value.communiques?.actifs ?? 0,
    data.value.documents?.total ?? 0,
  ]
  return Math.max(...vals, 1)
})

const metrics = computed(() => [
  { label: 'Agents total', value: data.value.agents?.total ?? 0, icon: 'fa-users', color: '#0077B5', bg: '#e0f2fe', pct: pct(data.value.agents?.total), alert: false },
  { label: 'Agents actifs', value: data.value.agents?.actifs ?? 0, icon: 'fa-user-check', color: '#059669', bg: '#d1fae5', pct: pct(data.value.agents?.actifs), alert: false },
  { label: 'Demandes en attente', value: data.value.requests?.en_attente ?? 0, icon: 'fa-hourglass-half', color: '#d97706', bg: '#fef3c7', pct: pct(data.value.requests?.en_attente), alert: (data.value.requests?.en_attente ?? 0) > 5 },
  { label: 'Demandes approuvees', value: data.value.requests?.approuve ?? 0, icon: 'fa-check-double', color: '#16a34a', bg: '#dcfce7', pct: pct(data.value.requests?.approuve), alert: false },
  { label: 'Signalements ouverts', value: data.value.signalements?.ouvert ?? 0, icon: 'fa-exclamation-circle', color: '#dc2626', bg: '#fee2e2', pct: pct(data.value.signalements?.ouvert), alert: (data.value.signalements?.ouvert ?? 0) > 0 },
  { label: 'Taches en cours', value: data.value.taches?.en_cours ?? 0, icon: 'fa-spinner', color: '#7c3aed', bg: '#ede9fe', pct: pct(data.value.taches?.en_cours), alert: false },
  { label: 'Communiques actifs', value: data.value.communiques?.actifs ?? 0, icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe', pct: pct(data.value.communiques?.actifs), alert: false },
  { label: 'Documents', value: data.value.documents?.total ?? 0, icon: 'fa-folder-open', color: '#6366f1', bg: '#e0e7ff', pct: pct(data.value.documents?.total), alert: false },
])

function pct(val) {
  return Math.min(((val ?? 0) / maxMetric.value) * 100, 100)
}

// ─── ORGANES: agents breakdown ───
const organeCards = computed(() => {
  const bo = data.value.agents?.by_organe || {}
  return [
    { code: 'SEN', nom: 'Secretariat Executif National', total: bo.sen?.total ?? 0, actifs: bo.sen?.actifs ?? 0, suspendus: bo.sen?.suspendus ?? 0, anciens: bo.sen?.anciens ?? 0, color: '#0077B5' },
    { code: 'SEP', nom: 'Secretariat Executif Provincial', total: bo.sep?.total ?? 0, actifs: bo.sep?.actifs ?? 0, suspendus: bo.sep?.suspendus ?? 0, anciens: bo.sep?.anciens ?? 0, color: '#0ea5e9' },
    { code: 'SEL', nom: 'Secretariat Executif Local', total: bo.sel?.total ?? 0, actifs: bo.sel?.actifs ?? 0, suspendus: bo.sel?.suspendus ?? 0, anciens: bo.sel?.anciens ?? 0, color: '#0d9488' },
  ]
})

function orgPct(count) {
  const total = data.value.agents?.actifs || 1
  return Math.round((count / total) * 100)
}

// ─── PRESENCE par organe ───
const presenceOrganes = computed(() => {
  const att = data.value.attendance?.by_organe || {}
  return [
    { code: 'SEN', label: 'National', icon: 'fa-flag', color: '#0077B5', today_present: att.sen?.today_present ?? 0, today_rate: att.sen?.today_rate ?? 0, monthly_rate: att.sen?.monthly_avg_rate ?? 0, total_active: att.sen?.total_active_agents ?? 0 },
    { code: 'SEP', label: 'Provincial', icon: 'fa-map-marked-alt', color: '#0ea5e9', today_present: att.sep?.today_present ?? 0, today_rate: att.sep?.today_rate ?? 0, monthly_rate: att.sep?.monthly_avg_rate ?? 0, total_active: att.sep?.total_active_agents ?? 0 },
    { code: 'SEL', label: 'Local', icon: 'fa-map-pin', color: '#0d9488', today_present: att.sel?.today_present ?? 0, today_rate: att.sel?.today_rate ?? 0, monthly_rate: att.sel?.monthly_avg_rate ?? 0, total_active: att.sel?.total_active_agents ?? 0 },
  ]
})

// ─── PLAN par organe ───
const planOrganes = computed(() => {
  const bo = data.value.plan_travail?.by_organe || {}
  return [
    { code: 'SEN', label: 'National (SEN)', color: '#0077B5', total: bo.sen?.total ?? 0, terminee: bo.sen?.terminee ?? 0, avg: bo.sen?.avg_completion ?? 0, trims: bo.sen?.by_trimestre || [] },
    { code: 'SEP', label: 'Provincial (SEP)', color: '#0ea5e9', total: bo.sep?.total ?? 0, terminee: bo.sep?.terminee ?? 0, avg: bo.sep?.avg_completion ?? 0, trims: bo.sep?.by_trimestre || [] },
    { code: 'SEL', label: 'Local (SEL)', color: '#0d9488', total: bo.sel?.total ?? 0, terminee: bo.sel?.terminee ?? 0, avg: bo.sel?.avg_completion ?? 0, trims: bo.sel?.by_trimestre || [] },
  ]
})

const ringCirc = 2 * Math.PI * 52
const ringDash = ringCirc.toFixed(1)
const ringOffset = computed(() => {
  const pctVal = data.value.plan_travail?.avg_completion ?? 0
  return (ringCirc - (ringCirc * pctVal / 100)).toFixed(1)
})

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
.sen-dashboard { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }

/* ═══════════ HERO ═══════════ */
.sen-hero {
  position: relative; border-radius: 20px; overflow: hidden; margin-bottom: 1.8rem;
  background: linear-gradient(135deg, #0a1628 0%, #0f2847 30%, #0c4a6e 60%, #0077B5 100%);
  box-shadow: 0 8px 32px rgba(0, 30, 60, .25);
}
.sen-hero-bg {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 400px 300px at 85% 20%, rgba(0,119,181,.25) 0%, transparent 70%),
    radial-gradient(ellipse 200px 200px at 10% 80%, rgba(56,189,248,.1) 0%, transparent 60%);
  pointer-events: none;
}
.sen-hero-inner {
  position: relative; z-index: 1; padding: 2rem 2.5rem;
  display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;
}
.sen-hero-left { display: flex; align-items: center; gap: 1.2rem; color: #fff; }
.sen-hero-badge {
  width: 60px; height: 60px; border-radius: 16px; flex-shrink: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.15), rgba(255,255,255,.05));
  border: 1px solid rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: #fbbf24; backdrop-filter: blur(8px);
}
.sen-hero-greeting { font-size: .82rem; opacity: .6; font-weight: 500; letter-spacing: .5px; text-transform: uppercase; }
.sen-hero-name { font-size: 1.5rem; font-weight: 800; margin: .1rem 0 .35rem; line-height: 1.15; color: #fff; }
.sen-hero-role { font-size: .78rem; font-weight: 600; opacity: .75; margin-bottom: .2rem; display: flex; align-items: center; }
.sen-hero-date { font-size: .72rem; opacity: .45; text-transform: capitalize; }

.sen-hero-kpis {
  display: flex; align-items: center; gap: 0;
  background: rgba(255,255,255,.08); border-radius: 16px; padding: .8rem 1.2rem;
  border: 1px solid rgba(255,255,255,.1); backdrop-filter: blur(8px);
}
.sen-kpi { display: flex; align-items: center; gap: .6rem; padding: 0 1rem; color: #fff; }
.sen-kpi-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.1); display: flex; align-items: center;
  justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.sen-kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.kpi-unit { font-size: .85rem; font-weight: 600; opacity: .7; }
.sen-kpi-lbl { font-size: .62rem; opacity: .5; text-transform: uppercase; letter-spacing: .4px; margin-top: .1rem; font-weight: 600; }
.kpi-divider { width: 1px; height: 36px; background: rgba(255,255,255,.12); margin: 0 .2rem; }

/* ═══════════ SECTIONS ═══════════ */
.sen-section { margin-bottom: 1.8rem; }
.sen-section-head { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.sen-section-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0;
}
.sen-section-title { font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; }
.sen-section-sub { font-size: .72rem; color: #94a3b8; margin: 0; font-weight: 500; }

/* ═══════════ QUICK ACTIONS ═══════════ */
.sen-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sen-action {
  position: relative; display: flex; align-items: center; gap: .8rem; padding: 1rem 1.1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; overflow: hidden;
}
.sen-action-glow { position: absolute; top: 0; left: 0; width: 3px; height: 100%; opacity: 0; transition: opacity .25s; }
.sen-action:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-action:hover .sen-action-glow { opacity: 1; }
.sen-action-icon {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.sen-action-text { flex: 1; min-width: 0; }
.sen-action-label { font-size: .84rem; font-weight: 700; line-height: 1.2; }
.sen-action-desc { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
.sen-action-arrow { font-size: .65rem; color: #cbd5e1; transition: all .2s; }
.sen-action:hover .sen-action-arrow { color: #0077B5; transform: translateX(3px); }

/* ═══════════ METRICS ═══════════ */
.sen-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.sen-metric {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.1rem; transition: all .25s; position: relative;
}
.sen-metric:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-metric-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .7rem; }
.sen-metric-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem;
}
.sen-metric-alert { color: #ef4444; font-size: .75rem; animation: pulse-alert 2s infinite; }
@keyframes pulse-alert { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
.sen-metric-val { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.sen-metric-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.sen-metric-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: .7rem; }
.sen-metric-bar-fill { height: 100%; border-radius: 4px; transition: width .8s ease; min-width: 3px; }

/* ═══════════ ORGANE CARDS (agents) ═══════════ */
.sen-organe-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sen-organe-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.3rem; transition: all .25s;
}
.sen-organe-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-organe-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.sen-organe-badge {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center;
  font-size: .72rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.sen-organe-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
.sen-organe-sub { font-size: .68rem; color: #94a3b8; }
.sen-organe-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; margin-bottom: 1rem; }
.sen-organe-stat { text-align: center; padding: .5rem .25rem; background: #f8fafc; border-radius: 10px; }
.sen-organe-stat-val { font-size: 1.3rem; font-weight: 800; line-height: 1; }
.sen-organe-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }
.sen-organe-bar-wrap {}
.sen-organe-bar-bg { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.sen-organe-bar-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }
.sen-organe-bar-label { font-size: .62rem; color: #94a3b8; margin-top: .25rem; }

/* ═══════════ PRESENCE PAR ORGANE ═══════════ */
.sen-presence-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.sen-presence-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s;
}
.sen-presence-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-presence-global { border-top: 4px solid #059669; }
.sen-presence-card-head {
  font-size: .88rem; font-weight: 700; color: #1e293b; margin-bottom: .8rem;
  display: flex; align-items: center; gap: .5rem;
}
.sen-presence-big { font-size: 2rem; font-weight: 800; text-align: center; line-height: 1; margin-bottom: .15rem; }
.sen-presence-sub { font-size: .7rem; color: #94a3b8; text-align: center; margin-bottom: .8rem; }
.sen-presence-item { display: flex; justify-content: space-between; font-size: .75rem; color: #475569; margin-bottom: .25rem; }
.sen-presence-bar { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; margin-bottom: .6rem; }
.sen-presence-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }

/* ═══════════ PLAN DE TRAVAIL ═══════════ */
.sen-plan-global-row {
  display: flex; align-items: flex-start; gap: 1.5rem; padding: 1.3rem;
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  margin-bottom: .75rem;
}
.sen-plan-ring-wrap { position: relative; flex-shrink: 0; }
.sen-ring-svg { width: 120px; height: 120px; }
.sen-ring-center {
  position: absolute; inset: 0; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.sen-ring-val { font-size: 1.5rem; font-weight: 800; color: #0077B5; line-height: 1; }
.sen-ring-lbl { font-size: .62rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; }
.sen-plan-trims { flex: 1; display: flex; flex-direction: column; gap: .75rem; }
.sen-trim {}
.sen-trim-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .3rem; }
.sen-trim-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sen-trim-pct { font-size: .78rem; font-weight: 700; color: #0077B5; }
.sen-trim-bar { height: 8px; background: #f1f5f9; border-radius: 8px; overflow: hidden; }
.sen-trim-fill {
  height: 100%; border-radius: 8px; transition: width .8s ease;
  background: linear-gradient(90deg, #0077B5, #38bdf8);
}
.sen-trim-detail { font-size: .65rem; color: #94a3b8; margin-top: .2rem; }

.sen-plan-organe-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sen-plan-organe-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s;
}
.sen-plan-organe-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-plan-organe-head { display: flex; align-items: center; gap: .6rem; margin-bottom: 1rem; }
.sen-plan-organe-badge {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center;
  font-size: .65rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.sen-plan-organe-info { flex: 1; min-width: 0; }
.sen-plan-organe-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sen-plan-organe-summary { font-size: .68rem; color: #94a3b8; }
.sen-plan-organe-pct { font-size: 1.4rem; font-weight: 800; flex-shrink: 0; }
.sen-plan-organe-trims { display: flex; flex-direction: column; gap: .5rem; }
.sen-plan-organe-trim {}
.sen-plan-organe-trim-head { display: flex; justify-content: space-between; font-size: .72rem; color: #475569; margin-bottom: .2rem; }
.sen-plan-organe-trim-bar { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.sen-plan-organe-trim-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }

/* ═══════════ RECENT ACTIVITY ═══════════ */
.sen-recent-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sen-recent-col {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; display: flex; flex-direction: column;
}
.sen-recent-head {
  display: flex; align-items: center; gap: .6rem; padding: .9rem 1rem;
  border-bottom: 2px solid; background: #fafbfc;
}
.sen-recent-head-icon {
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
}
.sen-recent-head-title { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sen-recent-head-count { font-size: .65rem; color: #94a3b8; }
.sen-alert-badge {
  margin-left: auto; font-size: .62rem; font-weight: 700; color: #fff;
  background: #ef4444; padding: .15rem .5rem; border-radius: 6px; white-space: nowrap;
}
.sen-recent-body { flex: 1; max-height: 250px; overflow-y: auto; }
.sen-recent-item {
  display: flex; align-items: flex-start; gap: .6rem; padding: .65rem 1rem;
  border-bottom: 1px solid #f8fafc; text-decoration: none; color: inherit;
  transition: background .15s;
}
.sen-recent-item:hover { background: #f0f9ff; }
.sen-recent-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: .35rem; }
.sen-recent-info { flex: 1; min-width: 0; }
.sen-recent-title { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.sen-recent-time { font-size: .65rem; color: #94a3b8; margin-top: .15rem; }
.sen-recent-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: .4rem; padding: 2rem 1rem; color: #cbd5e1; font-size: .82rem;
}
.sen-recent-empty i { font-size: 1.3rem; }

.sev-bg-basse { background: #22c55e; }
.sev-bg-moyenne { background: #f59e0b; }
.sev-bg-haute { background: #ef4444; }
.sen-sev-badge {
  display: inline-block; font-size: .58rem; font-weight: 700; padding: .1rem .4rem;
  border-radius: 4px; margin-left: .3rem; text-transform: uppercase; letter-spacing: .3px;
}
.sev-basse { background: #dcfce7; color: #16a34a; }
.sev-moyenne { background: #fef3c7; color: #d97706; }
.sev-haute { background: #fee2e2; color: #dc2626; }

/* ═══════════ TACHES ═══════════ */
.sen-taches-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.3rem; max-width: 500px;
}
.sen-task-items { display: flex; flex-direction: column; gap: .5rem; }
.sen-task-row {
  display: flex; align-items: center; gap: .6rem; padding: .55rem .8rem;
  background: #f8fafc; border-radius: 10px; font-size: .82rem; color: #475569;
}
.sen-task-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.sen-task-count { margin-left: auto; font-weight: 800; color: #1e293b; font-size: .9rem; }
.sen-task-overdue { background: #fef2f2; color: #dc2626; }
.sen-task-overdue .sen-task-count { color: #dc2626; }

/* ═══════════ RESPONSIVE ═══════════ */
@media (max-width: 1100px) {
  .sen-hero-inner { flex-direction: column; align-items: flex-start; }
  .sen-hero-kpis { width: 100%; justify-content: space-between; }
}
@media (max-width: 991.98px) {
  .sen-metrics { grid-template-columns: repeat(2, 1fr); }
  .sen-actions { grid-template-columns: repeat(2, 1fr); }
  .sen-organe-grid { grid-template-columns: 1fr; }
  .sen-presence-grid { grid-template-columns: repeat(2, 1fr); }
  .sen-plan-organe-grid { grid-template-columns: 1fr; }
  .sen-recent-grid { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
  .sen-dashboard { padding: 0 .5rem 1.5rem; }
  .sen-hero { border-radius: 16px; margin-bottom: 1.2rem; }
  .sen-hero-inner { padding: 1.3rem 1.2rem; gap: 1rem; }
  .sen-hero-badge { width: 48px; height: 48px; font-size: 1.2rem; }
  .sen-hero-name { font-size: 1.2rem; }
  .sen-hero-kpis { padding: .6rem .8rem; gap: 0; flex-wrap: wrap; }
  .sen-kpi { padding: 0 .6rem; }
  .sen-kpi-val { font-size: 1.15rem; }
  .kpi-divider { height: 28px; }
  .sen-actions { grid-template-columns: 1fr 1fr; gap: .5rem; }
  .sen-action { padding: .75rem .85rem; }
  .sen-metrics { gap: .5rem; }
  .sen-metric { padding: .85rem; }
  .sen-metric-val { font-size: 1.4rem; }
  .sen-presence-grid { grid-template-columns: 1fr 1fr; }
  .sen-plan-global-row { flex-direction: column; align-items: center; }
}
@media (max-width: 575.98px) {
  .sen-hero-kpis { border-radius: 12px; }
  .sen-kpi { padding: .4rem .4rem; }
  .sen-kpi-icon { display: none; }
  .sen-kpi-val { font-size: 1.1rem; }
  .kpi-divider { height: 24px; }
  .sen-actions { grid-template-columns: 1fr; }
  .sen-action-arrow { display: none; }
  .sen-metrics { grid-template-columns: repeat(2, 1fr); }
  .sen-presence-grid { grid-template-columns: 1fr; }
  .sen-organe-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
