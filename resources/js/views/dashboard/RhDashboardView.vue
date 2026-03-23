<template>
  <div class="rh-dashboard">
    <!-- HERO -->
    <div class="rh-hero">
      <div class="rh-hero-bg"></div>
      <div class="rh-hero-inner">
        <div class="rh-hero-left">
          <div class="rh-hero-badge">
            <i class="fas fa-user-tie"></i>
          </div>
          <div>
            <div class="rh-hero-greeting">Tableau de bord</div>
            <h1 class="rh-hero-name">Ressources Humaines</h1>
            <div class="rh-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>
        <div class="rh-hero-kpis">
          <div class="rh-kpi">
            <div class="rh-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="rh-kpi-val">{{ d.agents?.actifs ?? '-' }}</div>
              <div class="rh-kpi-lbl">Agents actifs</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rh-kpi">
            <div class="rh-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="rh-kpi-val">{{ d.attendance?.today_rate ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="rh-kpi-lbl">Presence</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rh-kpi">
            <div class="rh-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="rh-kpi-val">{{ d.requests?.en_attente ?? 0 }}</div>
              <div class="rh-kpi-lbl">En attente</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rh-kpi">
            <div class="rh-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
              <div class="rh-kpi-val">{{ d.signalements?.ouvert ?? 0 }}</div>
              <div class="rh-kpi-lbl">Signalements</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord RH..." />

    <template v-else>
      <!-- ACTIONS RAPIDES -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#e0f2fe;color:#0077B5;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Actions rapides</h3>
            <p class="rh-section-sub">Acces direct aux modules RH</p>
          </div>
        </div>
        <div class="rh-actions">
          <router-link v-for="a in quickActions" :key="a.to" :to="a.to" class="rh-action">
            <div class="rh-action-glow" :style="{ background: a.color }"></div>
            <div class="rh-action-icon" :style="{ background: a.bg, color: a.color }">
              <i class="fas" :class="a.icon"></i>
            </div>
            <div class="rh-action-text">
              <div class="rh-action-label">{{ a.label }}</div>
              <div class="rh-action-desc">{{ a.desc }}</div>
            </div>
            <i class="fas fa-chevron-right rh-action-arrow"></i>
          </router-link>
        </div>
      </div>

      <!-- INDICATEURS CLES -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Indicateurs cles</h3>
            <p class="rh-section-sub">Vue d'ensemble des ressources humaines</p>
          </div>
        </div>
        <div class="rh-metrics">
          <div v-for="m in metrics" :key="m.label" class="rh-metric">
            <div class="rh-metric-header">
              <div class="rh-metric-icon" :style="{ background: m.bg, color: m.color }">
                <i class="fas" :class="m.icon"></i>
              </div>
              <span v-if="m.alert" class="rh-metric-alert">
                <i class="fas fa-exclamation-circle"></i>
              </span>
            </div>
            <div class="rh-metric-val" :style="{ color: m.color }">{{ m.value }}</div>
            <div class="rh-metric-lbl">{{ m.label }}</div>
            <div class="rh-metric-bar">
              <div class="rh-metric-bar-fill" :style="{ background: m.color, width: m.pct + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- EFFECTIFS PAR ORGANE -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fas fa-sitemap"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Effectifs par organe</h3>
            <p class="rh-section-sub">{{ d.agents?.total ?? 0 }} agents au total, dont {{ d.agents?.actifs ?? 0 }} actifs</p>
          </div>
        </div>
        <div class="rh-organe-grid">
          <div v-for="o in organeCards" :key="o.code" class="rh-organe-card" :style="{ borderTop: '4px solid ' + o.color }">
            <div class="rh-organe-header">
              <div class="rh-organe-badge" :style="{ background: o.color }">{{ o.code }}</div>
              <div>
                <div class="rh-organe-name">{{ o.nom }}</div>
                <div class="rh-organe-sub">{{ orgPct(o.total) }}% de l'effectif</div>
              </div>
            </div>
            <div class="rh-organe-stats">
              <div class="rh-organe-stat">
                <div class="rh-organe-stat-val" style="color:#1e293b;">{{ o.total }}</div>
                <div class="rh-organe-stat-lbl">Total</div>
              </div>
              <div class="rh-organe-stat">
                <div class="rh-organe-stat-val" style="color:#059669;">{{ o.actifs }}</div>
                <div class="rh-organe-stat-lbl">Actifs</div>
              </div>
            </div>
            <div class="rh-organe-bar-bg">
              <div class="rh-organe-bar-fill" :style="{ background: o.color, width: orgPct(o.actifs) + '%' }"></div>
            </div>
          </div>

          <!-- Repartition par sexe -->
          <div class="rh-organe-card" style="border-top: 4px solid #8b5cf6;">
            <div class="rh-organe-header">
              <div class="rh-organe-badge" style="background:#8b5cf6;"><i class="fas fa-venus-mars" style="font-size:.7rem;"></i></div>
              <div>
                <div class="rh-organe-name">Repartition par sexe</div>
                <div class="rh-organe-sub">Agents actifs</div>
              </div>
            </div>
            <div class="rh-organe-stats">
              <div class="rh-organe-stat">
                <div class="rh-organe-stat-val" style="color:#2563eb;">{{ d.agents?.by_sexe?.M ?? d.agents?.by_sexe?.Masculin ?? 0 }}</div>
                <div class="rh-organe-stat-lbl">Hommes</div>
              </div>
              <div class="rh-organe-stat">
                <div class="rh-organe-stat-val" style="color:#ec4899;">{{ d.agents?.by_sexe?.F ?? d.agents?.by_sexe?.Feminin ?? 0 }}</div>
                <div class="rh-organe-stat-lbl">Femmes</div>
              </div>
            </div>
            <div class="rh-sexe-bars">
              <div class="rh-sexe-bar">
                <div class="rh-sexe-fill" style="background:#2563eb;" :style="{ width: sexePct('M') + '%' }"></div>
                <div class="rh-sexe-fill" style="background:#ec4899;" :style="{ width: sexePct('F') + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PRESENCE (7 derniers jours) -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-user-check"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Presence</h3>
            <p class="rh-section-sub">{{ d.attendance?.today_present ?? 0 }} / {{ d.attendance?.total_active_agents ?? 0 }} presents aujourd'hui ({{ d.attendance?.today_rate ?? 0 }}%)</p>
          </div>
        </div>
        <div class="rh-presence-row">
          <!-- Global card -->
          <div class="rh-presence-global">
            <div class="rh-presence-big" :class="presenceColor">{{ d.attendance?.today_rate ?? 0 }}%</div>
            <div class="rh-presence-sub-text">Taux aujourd'hui</div>
            <div class="rh-presence-detail">
              <div class="rh-presence-item">
                <span>Presents</span>
                <span class="fw-bold">{{ d.attendance?.today_present ?? 0 }}</span>
              </div>
              <div class="rh-presence-item">
                <span>Effectif actif</span>
                <span class="fw-bold">{{ d.attendance?.total_active_agents ?? 0 }}</span>
              </div>
              <div class="rh-presence-item">
                <span>Moy. mensuelle</span>
                <span class="fw-bold">{{ d.attendance?.monthly_avg_rate ?? 0 }}%</span>
              </div>
            </div>
          </div>
          <!-- Weekly chart -->
          <div class="rh-presence-chart">
            <div class="rh-chart-title">7 derniers jours</div>
            <div class="rh-bars">
              <div v-for="day in weeklyData" :key="day.date" class="rh-bar-col">
                <div class="rh-bar-value">{{ day.rate }}%</div>
                <div class="rh-bar-wrap">
                  <div class="rh-bar-fill" :style="{ height: day.rate + '%' }" :class="barColor(day.rate)"></div>
                </div>
                <div class="rh-bar-label">{{ day.label }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- DOCUMENTS & DEMANDES (side by side) -->
      <div class="rh-section">
        <div class="rh-two-cols">
          <!-- Demandes par type -->
          <div class="rh-col-card">
            <div class="rh-col-head" style="border-color:#d97706;">
              <div class="rh-col-head-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-paper-plane"></i>
              </div>
              <div>
                <div class="rh-col-head-title">Demandes par type</div>
                <div class="rh-col-head-count">{{ d.requests?.total ?? 0 }} au total</div>
              </div>
            </div>
            <div class="rh-col-body">
              <div v-for="(count, type) in (d.requests?.by_type || {})" :key="type" class="rh-type-row">
                <span class="rh-type-name">{{ type }}</span>
                <div class="rh-type-bar-wrap">
                  <div class="rh-type-bar" style="background:#fbbf24;" :style="{ width: typePct(count, d.requests?.total) + '%' }"></div>
                </div>
                <span class="rh-type-count">{{ count }}</span>
              </div>
              <div v-if="!Object.keys(d.requests?.by_type || {}).length" class="rh-empty">
                <i class="fas fa-inbox"></i> Aucune demande
              </div>
            </div>
          </div>

          <!-- Documents par type -->
          <div class="rh-col-card">
            <div class="rh-col-head" style="border-color:#6366f1;">
              <div class="rh-col-head-icon" style="background:#e0e7ff;color:#6366f1;">
                <i class="fas fa-folder-open"></i>
              </div>
              <div>
                <div class="rh-col-head-title">Documents</div>
                <div class="rh-col-head-count">{{ d.documents?.total ?? 0 }} au total &middot; {{ d.documents?.expires ?? 0 }} expires</div>
              </div>
            </div>
            <div class="rh-col-body">
              <div class="rh-doc-summary">
                <div class="rh-doc-stat">
                  <div class="rh-doc-stat-val" style="color:#059669;">{{ d.documents?.valides ?? 0 }}</div>
                  <div class="rh-doc-stat-lbl">Valides</div>
                </div>
                <div class="rh-doc-stat">
                  <div class="rh-doc-stat-val" style="color:#dc2626;">{{ d.documents?.expires ?? 0 }}</div>
                  <div class="rh-doc-stat-lbl">Expires</div>
                </div>
                <div class="rh-doc-stat">
                  <div class="rh-doc-stat-val" style="color:#1e293b;">{{ d.documents?.total ?? 0 }}</div>
                  <div class="rh-doc-stat-lbl">Total</div>
                </div>
              </div>
              <div v-for="(count, type) in (d.documents?.by_type || {})" :key="type" class="rh-type-row">
                <span class="rh-type-name">{{ type }}</span>
                <div class="rh-type-bar-wrap">
                  <div class="rh-type-bar" style="background:#818cf8;" :style="{ width: typePct(count, d.documents?.total) + '%' }"></div>
                </div>
                <span class="rh-type-count">{{ count }}</span>
              </div>
              <div v-if="!Object.keys(d.documents?.by_type || {}).length" class="rh-empty">
                <i class="fas fa-inbox"></i> Aucun document
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTIVITES RECENTES -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#fce7f3;color:#db2777;">
            <i class="fas fa-stream"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Activites recentes</h3>
            <p class="rh-section-sub">Dernieres mises a jour</p>
          </div>
        </div>
        <div class="rh-recent-grid">
          <!-- Demandes recentes -->
          <div class="rh-recent-col">
            <div class="rh-recent-head" style="border-color:#d97706;">
              <div class="rh-recent-head-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <div class="rh-recent-head-title">Dernieres demandes</div>
                <div class="rh-recent-head-count">{{ d.requests?.en_attente ?? 0 }} en attente</div>
              </div>
            </div>
            <div v-if="d.requests?.recent?.length" class="rh-recent-body">
              <router-link v-for="r in d.requests.recent" :key="r.id" :to="'/requests/' + r.id" class="rh-recent-item">
                <div class="rh-recent-dot" :class="'statut-bg-' + r.statut"></div>
                <div class="rh-recent-info">
                  <div class="rh-recent-title">
                    {{ r.type }}
                    <span class="rh-statut-badge" :class="'statut-' + r.statut">{{ statutLabel(r.statut) }}</span>
                  </div>
                  <div class="rh-recent-subtitle" v-if="r.agent">{{ r.agent.prenom }} {{ r.agent.nom }}</div>
                  <div class="rh-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(r.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="rh-recent-empty">
              <i class="fas fa-check-circle"></i>
              <span>Aucune demande</span>
            </div>
          </div>

          <!-- Signalements recents -->
          <div class="rh-recent-col">
            <div class="rh-recent-head" style="border-color:#dc2626;">
              <div class="rh-recent-head-icon" style="background:#fee2e2;color:#dc2626;">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div>
                <div class="rh-recent-head-title">Signalements</div>
                <div class="rh-recent-head-count">{{ d.signalements?.ouvert ?? 0 }} ouverts &middot; {{ d.signalements?.en_cours ?? 0 }} en cours</div>
              </div>
              <span v-if="d.signalements?.haute_severite" class="rh-alert-badge">{{ d.signalements.haute_severite }} haute</span>
            </div>
            <div v-if="d.signalements?.recent?.length" class="rh-recent-body">
              <router-link v-for="s in d.signalements.recent" :key="s.id" :to="'/signalements/' + s.id" class="rh-recent-item">
                <div class="rh-recent-dot" :class="'sev-bg-' + s.severite"></div>
                <div class="rh-recent-info">
                  <div class="rh-recent-title">
                    {{ s.type }}
                    <span class="rh-sev-badge" :class="'sev-' + s.severite">{{ s.severite }}</span>
                  </div>
                  <div class="rh-recent-subtitle" v-if="s.agent">{{ s.agent.prenom }} {{ s.agent.nom }}</div>
                  <div class="rh-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(s.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="rh-recent-empty">
              <i class="fas fa-shield-alt"></i>
              <span>Aucun signalement</span>
            </div>
          </div>

          <!-- Communiques -->
          <div class="rh-recent-col">
            <div class="rh-recent-head" style="border-color:#0891b2;">
              <div class="rh-recent-head-icon" style="background:#cffafe;color:#0891b2;">
                <i class="fas fa-bullhorn"></i>
              </div>
              <div>
                <div class="rh-recent-head-title">Communiques</div>
                <div class="rh-recent-head-count">{{ d.communiques?.actifs ?? 0 }} actifs</div>
              </div>
              <span v-if="d.communiques?.urgents" class="rh-alert-badge">{{ d.communiques.urgents }} urgent(s)</span>
            </div>
            <div v-if="d.communiques?.recent?.length" class="rh-recent-body">
              <router-link v-for="c in d.communiques.recent" :key="c.id" :to="'/communiques/' + c.id" class="rh-recent-item">
                <div class="rh-recent-dot" :style="{ background: c.urgence ? '#ef4444' : '#0891b2' }"></div>
                <div class="rh-recent-info">
                  <div class="rh-recent-title">{{ c.titre }}</div>
                  <div class="rh-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(c.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="rh-recent-empty">
              <i class="fas fa-inbox"></i>
              <span>Aucun communique</span>
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
const d = ref({})

const today = computed(() => new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
}))

const quickActions = [
  { to: '/rh/agents', label: 'Gestion agents', desc: 'Consulter et gerer les agents', icon: 'fa-users', color: '#0077B5', bg: '#e0f2fe' },
  { to: '/rh/agents/create', label: 'Nouvel agent', desc: 'Creer une fiche agent', icon: 'fa-user-plus', color: '#059669', bg: '#d1fae5' },
  { to: '/rh/pointages/daily', label: 'Pointages du jour', desc: 'Saisie des presences', icon: 'fa-clock', color: '#7c3aed', bg: '#ede9fe' },
  { to: '/requests', label: 'Demandes', desc: 'Gerer les demandes', icon: 'fa-paper-plane', color: '#d97706', bg: '#fef3c7' },
  { to: '/signalements', label: 'Signalements', desc: 'Consulter les alertes', icon: 'fa-flag', color: '#dc2626', bg: '#fee2e2' },
  { to: '/rh/communiques/create', label: 'Communique', desc: 'Publier un communique', icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe' },
]

const maxMetric = computed(() => {
  const vals = [
    d.value.agents?.total ?? 0,
    d.value.agents?.actifs ?? 0,
    d.value.requests?.total ?? 0,
    d.value.documents?.total ?? 0,
    d.value.signalements?.total ?? 0,
  ]
  return Math.max(...vals, 1)
})

function pct(val) {
  return Math.min(((val ?? 0) / maxMetric.value) * 100, 100)
}

const metrics = computed(() => [
  { label: 'Agents total', value: d.value.agents?.total ?? 0, icon: 'fa-users', color: '#0077B5', bg: '#e0f2fe', pct: pct(d.value.agents?.total), alert: false },
  { label: 'Agents actifs', value: d.value.agents?.actifs ?? 0, icon: 'fa-user-check', color: '#059669', bg: '#d1fae5', pct: pct(d.value.agents?.actifs), alert: false },
  { label: 'Suspendus', value: d.value.agents?.suspendus ?? 0, icon: 'fa-user-slash', color: '#d97706', bg: '#fef3c7', pct: pct(d.value.agents?.suspendus), alert: (d.value.agents?.suspendus ?? 0) > 0 },
  { label: 'Nouveaux ce mois', value: d.value.agents?.new_this_month ?? 0, icon: 'fa-user-plus', color: '#8b5cf6', bg: '#ede9fe', pct: pct(d.value.agents?.new_this_month), alert: false },
  { label: 'Demandes en attente', value: d.value.requests?.en_attente ?? 0, icon: 'fa-hourglass-half', color: '#ea580c', bg: '#fff7ed', pct: pct(d.value.requests?.en_attente), alert: (d.value.requests?.en_attente ?? 0) > 5 },
  { label: 'Demandes approuvees', value: d.value.requests?.approuve ?? 0, icon: 'fa-check-double', color: '#16a34a', bg: '#dcfce7', pct: pct(d.value.requests?.approuve), alert: false },
  { label: 'Signalements ouverts', value: d.value.signalements?.ouvert ?? 0, icon: 'fa-exclamation-circle', color: '#dc2626', bg: '#fee2e2', pct: pct(d.value.signalements?.ouvert), alert: (d.value.signalements?.haute_severite ?? 0) > 0 },
  { label: 'Documents', value: d.value.documents?.total ?? 0, icon: 'fa-folder-open', color: '#6366f1', bg: '#e0e7ff', pct: pct(d.value.documents?.total), alert: (d.value.documents?.expires ?? 0) > 0 },
])

const organeCards = computed(() => {
  const bo = d.value.agents?.by_organe || {}
  return [
    { code: 'SEN', nom: 'Secretariat Executif National', total: bo.sen?.total ?? 0, actifs: bo.sen?.actifs ?? 0, color: '#0077B5' },
    { code: 'SEP', nom: 'Secretariat Executif Provincial', total: bo.sep?.total ?? 0, actifs: bo.sep?.actifs ?? 0, color: '#0ea5e9' },
    { code: 'SEL', nom: 'Secretariat Executif Local', total: bo.sel?.total ?? 0, actifs: bo.sel?.actifs ?? 0, color: '#0d9488' },
  ]
})

function orgPct(count) {
  const total = d.value.agents?.total || 1
  return Math.round((count / total) * 100)
}

function sexePct(sexe) {
  const by = d.value.agents?.by_sexe || {}
  const m = by.M ?? by.Masculin ?? 0
  const f = by.F ?? by.Feminin ?? 0
  const total = m + f || 1
  const val = sexe === 'M' ? m : f
  return Math.round((val / total) * 100)
}

const presenceColor = computed(() => {
  const rate = d.value.attendance?.today_rate ?? 0
  if (rate >= 80) return 'text-green'
  if (rate >= 50) return 'text-amber'
  return 'text-red'
})

const weeklyData = computed(() => {
  const weekly = d.value.attendance?.weekly || []
  const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']
  return weekly.map(w => ({
    ...w,
    label: dayNames[new Date(w.date).getDay()],
  }))
})

function barColor(rate) {
  if (rate >= 80) return 'bar-green'
  if (rate >= 50) return 'bar-amber'
  return 'bar-red'
}

function typePct(count, total) {
  return Math.min(Math.round(((count ?? 0) / (total || 1)) * 100), 100)
}

function statutLabel(s) {
  const map = { en_attente: 'En attente', approuve: 'Approuve', rejete: 'Rejete', annule: 'Annule' }
  return map[s] || s
}

function formatTime(iso) {
  if (!iso) return '-'
  const dd = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - dd) / 60000)
  if (diff < 1) return "A l'instant"
  if (diff < 60) return `Il y a ${diff} min`
  if (diff < 1440) return `Il y a ${Math.floor(diff / 60)}h`
  return dd.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

onMounted(async () => {
  try {
    const { data: result } = await client.get('/rh/dashboard')
    d.value = result
  } catch (e) {
    console.error('Erreur chargement dashboard RH:', e)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.rh-dashboard { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }

/* HERO */
.rh-hero {
  position: relative; border-radius: 20px; overflow: hidden; margin-bottom: 1.8rem;
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 30%, #0c4a6e 60%, #0077B5 100%);
  box-shadow: 0 8px 32px rgba(0, 30, 60, .25);
}
.rh-hero-bg {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 400px 300px at 85% 20%, rgba(0,119,181,.25) 0%, transparent 70%),
    radial-gradient(ellipse 200px 200px at 10% 80%, rgba(56,189,248,.1) 0%, transparent 60%);
  pointer-events: none;
}
.rh-hero-inner {
  position: relative; z-index: 1; padding: 2rem 2.5rem;
  display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;
}
.rh-hero-left { display: flex; align-items: center; gap: 1.2rem; color: #fff; }
.rh-hero-badge {
  width: 60px; height: 60px; border-radius: 16px; flex-shrink: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.15), rgba(255,255,255,.05));
  border: 1px solid rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: #38bdf8; backdrop-filter: blur(8px);
}
.rh-hero-greeting { font-size: .82rem; opacity: .6; font-weight: 500; letter-spacing: .5px; text-transform: uppercase; }
.rh-hero-name { font-size: 1.5rem; font-weight: 800; margin: .1rem 0 .35rem; line-height: 1.15; color: #fff; }
.rh-hero-date { font-size: .72rem; opacity: .45; text-transform: capitalize; }

.rh-hero-kpis {
  display: flex; align-items: center;
  background: rgba(255,255,255,.08); border-radius: 16px; padding: .8rem 1.2rem;
  border: 1px solid rgba(255,255,255,.1); backdrop-filter: blur(8px);
}
.rh-kpi { display: flex; align-items: center; gap: .6rem; padding: 0 1rem; color: #fff; }
.rh-kpi-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.1); display: flex; align-items: center;
  justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.rh-kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.kpi-unit { font-size: .85rem; font-weight: 600; opacity: .7; }
.rh-kpi-lbl { font-size: .62rem; opacity: .5; text-transform: uppercase; letter-spacing: .4px; margin-top: .1rem; font-weight: 600; }
.kpi-divider { width: 1px; height: 36px; background: rgba(255,255,255,.12); margin: 0 .2rem; }

/* SECTIONS */
.rh-section { margin-bottom: 1.8rem; }
.rh-section-head { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.rh-section-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0;
}
.rh-section-title { font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; }
.rh-section-sub { font-size: .72rem; color: #94a3b8; margin: 0; font-weight: 500; }

/* ACTIONS */
.rh-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.rh-action {
  position: relative; display: flex; align-items: center; gap: .8rem; padding: 1rem 1.1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; overflow: hidden;
}
.rh-action-glow { position: absolute; top: 0; left: 0; width: 3px; height: 100%; opacity: 0; transition: opacity .25s; }
.rh-action:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rh-action:hover .rh-action-glow { opacity: 1; }
.rh-action-icon {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.rh-action-text { flex: 1; min-width: 0; }
.rh-action-label { font-size: .84rem; font-weight: 700; line-height: 1.2; }
.rh-action-desc { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
.rh-action-arrow { font-size: .65rem; color: #cbd5e1; transition: all .2s; }
.rh-action:hover .rh-action-arrow { color: #0077B5; transform: translateX(3px); }

/* METRICS */
.rh-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.rh-metric {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.1rem; transition: all .25s;
}
.rh-metric:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rh-metric-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .7rem; }
.rh-metric-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem;
}
.rh-metric-alert { color: #ef4444; font-size: .75rem; animation: pulse-alert 2s infinite; }
@keyframes pulse-alert { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
.rh-metric-val { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.rh-metric-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.rh-metric-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: .7rem; }
.rh-metric-bar-fill { height: 100%; border-radius: 4px; transition: width .8s ease; min-width: 3px; }

/* ORGANE CARDS */
.rh-organe-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.rh-organe-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s;
}
.rh-organe-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rh-organe-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.rh-organe-badge {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center;
  font-size: .72rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.rh-organe-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
.rh-organe-sub { font-size: .68rem; color: #94a3b8; }
.rh-organe-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; margin-bottom: .8rem; }
.rh-organe-stat { text-align: center; padding: .5rem .25rem; background: #f8fafc; border-radius: 10px; }
.rh-organe-stat-val { font-size: 1.3rem; font-weight: 800; line-height: 1; }
.rh-organe-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }
.rh-organe-bar-bg { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.rh-organe-bar-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }
.rh-sexe-bars { margin-top: .6rem; }
.rh-sexe-bar { display: flex; height: 8px; border-radius: 8px; overflow: hidden; background: #f1f5f9; }
.rh-sexe-fill { height: 100%; transition: width .8s ease; }

/* PRESENCE */
.rh-presence-row { display: grid; grid-template-columns: 280px 1fr; gap: .75rem; }
.rh-presence-global {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.5rem; text-align: center;
}
.rh-presence-big { font-size: 3rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.text-green { color: #059669; }
.text-amber { color: #d97706; }
.text-red { color: #dc2626; }
.rh-presence-sub-text { font-size: .75rem; color: #94a3b8; margin-bottom: 1rem; }
.rh-presence-detail { text-align: left; }
.rh-presence-item { display: flex; justify-content: space-between; font-size: .78rem; color: #475569; padding: .3rem 0; border-bottom: 1px solid #f1f5f9; }

.rh-presence-chart {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem 1.5rem;
}
.rh-chart-title { font-size: .78rem; font-weight: 700; color: #64748b; margin-bottom: .8rem; }
.rh-bars { display: flex; align-items: flex-end; gap: .5rem; height: 140px; }
.rh-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; }
.rh-bar-value { font-size: .62rem; font-weight: 700; color: #64748b; margin-bottom: .3rem; }
.rh-bar-wrap { flex: 1; width: 100%; max-width: 40px; background: #f1f5f9; border-radius: 6px 6px 0 0; overflow: hidden; display: flex; align-items: flex-end; }
.rh-bar-fill { width: 100%; border-radius: 6px 6px 0 0; transition: height .8s ease; min-height: 3px; }
.bar-green { background: linear-gradient(0deg, #059669, #34d399); }
.bar-amber { background: linear-gradient(0deg, #d97706, #fbbf24); }
.bar-red { background: linear-gradient(0deg, #dc2626, #f87171); }
.rh-bar-label { font-size: .62rem; color: #94a3b8; font-weight: 600; margin-top: .3rem; }

/* TWO COLUMNS */
.rh-two-cols { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.rh-col-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; display: flex; flex-direction: column;
}
.rh-col-head {
  display: flex; align-items: center; gap: .6rem; padding: .9rem 1rem;
  border-bottom: 2px solid; background: #fafbfc;
}
.rh-col-head-icon {
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
}
.rh-col-head-title { font-size: .82rem; font-weight: 700; color: #1e293b; }
.rh-col-head-count { font-size: .65rem; color: #94a3b8; }
.rh-col-body { padding: 1rem; flex: 1; }
.rh-type-row { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; }
.rh-type-name { font-size: .75rem; color: #475569; min-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rh-type-bar-wrap { flex: 1; height: 8px; background: #f1f5f9; border-radius: 8px; overflow: hidden; }
.rh-type-bar { height: 100%; border-radius: 8px; transition: width .8s ease; min-width: 3px; }
.rh-type-count { font-size: .78rem; font-weight: 700; color: #1e293b; min-width: 28px; text-align: right; }
.rh-doc-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-bottom: 1rem; }
.rh-doc-stat { text-align: center; padding: .6rem .25rem; background: #f8fafc; border-radius: 10px; }
.rh-doc-stat-val { font-size: 1.3rem; font-weight: 800; line-height: 1; }
.rh-doc-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: .1rem; }
.rh-empty { text-align: center; padding: 1.5rem; color: #cbd5e1; font-size: .82rem; }

/* RECENT ACTIVITY */
.rh-recent-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.rh-recent-col {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; display: flex; flex-direction: column;
}
.rh-recent-head {
  display: flex; align-items: center; gap: .6rem; padding: .9rem 1rem;
  border-bottom: 2px solid; background: #fafbfc;
}
.rh-recent-head-icon {
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
}
.rh-recent-head-title { font-size: .82rem; font-weight: 700; color: #1e293b; }
.rh-recent-head-count { font-size: .65rem; color: #94a3b8; }
.rh-alert-badge {
  margin-left: auto; font-size: .62rem; font-weight: 700; color: #fff;
  background: #ef4444; padding: .15rem .5rem; border-radius: 6px; white-space: nowrap;
}
.rh-recent-body { flex: 1; max-height: 300px; overflow-y: auto; }
.rh-recent-item {
  display: flex; align-items: flex-start; gap: .6rem; padding: .65rem 1rem;
  border-bottom: 1px solid #f8fafc; text-decoration: none; color: inherit;
  transition: background .15s;
}
.rh-recent-item:hover { background: #f0f9ff; }
.rh-recent-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: .35rem; }
.rh-recent-info { flex: 1; min-width: 0; }
.rh-recent-title { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.rh-recent-subtitle { font-size: .7rem; color: #64748b; }
.rh-recent-time { font-size: .65rem; color: #94a3b8; margin-top: .15rem; }
.rh-recent-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: .4rem; padding: 2rem 1rem; color: #cbd5e1; font-size: .82rem;
}
.rh-recent-empty i { font-size: 1.3rem; }

/* Status badges */
.statut-bg-en_attente { background: #fbbf24; }
.statut-bg-approuve { background: #22c55e; }
.statut-bg-rejete { background: #ef4444; }
.statut-bg-annule { background: #94a3b8; }
.rh-statut-badge {
  display: inline-block; font-size: .58rem; font-weight: 700; padding: .1rem .4rem;
  border-radius: 4px; margin-left: .3rem; text-transform: uppercase; letter-spacing: .3px;
}
.statut-en_attente { background: #fef3c7; color: #d97706; }
.statut-approuve { background: #dcfce7; color: #16a34a; }
.statut-rejete { background: #fee2e2; color: #dc2626; }
.statut-annule { background: #f1f5f9; color: #64748b; }

/* Severity badges */
.sev-bg-basse { background: #22c55e; }
.sev-bg-moyenne { background: #f59e0b; }
.sev-bg-haute { background: #ef4444; }
.rh-sev-badge {
  display: inline-block; font-size: .58rem; font-weight: 700; padding: .1rem .4rem;
  border-radius: 4px; margin-left: .3rem; text-transform: uppercase; letter-spacing: .3px;
}
.sev-basse { background: #dcfce7; color: #16a34a; }
.sev-moyenne { background: #fef3c7; color: #d97706; }
.sev-haute { background: #fee2e2; color: #dc2626; }

/* RESPONSIVE */
@media (max-width: 1100px) {
  .rh-hero-inner { flex-direction: column; align-items: flex-start; }
  .rh-hero-kpis { width: 100%; justify-content: space-between; }
}
@media (max-width: 991.98px) {
  .rh-metrics { grid-template-columns: repeat(2, 1fr); }
  .rh-actions { grid-template-columns: repeat(2, 1fr); }
  .rh-organe-grid { grid-template-columns: repeat(2, 1fr); }
  .rh-presence-row { grid-template-columns: 1fr; }
  .rh-two-cols { grid-template-columns: 1fr; }
  .rh-recent-grid { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
  .rh-dashboard { padding: 0 .5rem 1.5rem; }
  .rh-hero { border-radius: 16px; margin-bottom: 1.2rem; }
  .rh-hero-inner { padding: 1.3rem 1.2rem; gap: 1rem; }
  .rh-hero-badge { width: 48px; height: 48px; font-size: 1.2rem; }
  .rh-hero-name { font-size: 1.2rem; }
  .rh-hero-kpis { padding: .6rem .8rem; flex-wrap: wrap; }
  .rh-kpi { padding: 0 .6rem; }
  .rh-kpi-val { font-size: 1.15rem; }
  .kpi-divider { height: 28px; }
  .rh-actions { grid-template-columns: 1fr 1fr; gap: .5rem; }
  .rh-metrics { gap: .5rem; }
  .rh-metric { padding: .85rem; }
  .rh-metric-val { font-size: 1.4rem; }
  .rh-organe-grid { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
  .rh-hero-kpis { border-radius: 12px; }
  .rh-kpi { padding: .4rem .4rem; }
  .rh-kpi-icon { display: none; }
  .rh-kpi-val { font-size: 1.1rem; }
  .kpi-divider { height: 24px; }
  .rh-actions { grid-template-columns: 1fr; }
  .rh-action-arrow { display: none; }
  .rh-metrics { grid-template-columns: repeat(2, 1fr); }
  .rh-bars { gap: .3rem; }
}
</style>
