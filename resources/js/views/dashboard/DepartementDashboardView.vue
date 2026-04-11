<template>
  <div class="dept-dashboard">

    <!-- ═══ HERO ═══ -->
    <div class="dept-hero">
      <div class="dept-hero-bg"></div>
      <div class="dept-hero-inner">
        <div class="dept-hero-left">
          <!-- Avatar photo / initiales -->
          <div class="dept-hero-avatar">
            <img v-if="deptPhotoUrl" :src="deptPhotoUrl" :alt="deptFullName"
              class="dept-hero-avatar-photo" @error="handlePhotoError">
            <span v-else class="dept-hero-avatar-initials">{{ deptInitials }}</span>
          </div>
          <div>
            <div class="dept-hero-greeting">{{ deptGreeting }},</div>
            <h1 class="dept-hero-name">{{ deptCivility }} {{ deptFullName }}</h1>
            <div class="dept-hero-fonction" v-if="deptFonction">
              <i class="fas fa-id-badge me-1"></i>{{ deptFonction }}
            </div>
            <div class="dept-hero-role">
              <i class="fas fa-building me-1"></i>
              {{ auth.isDirecteur ? 'Directeur de Département' : 'Assistant de Département' }}
              <span v-if="data?.department?.nom" class="dept-hero-dept-badge">
                {{ data.department.nom }}
              </span>
            </div>
            <div class="dept-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>

        <!-- KPIs hero -->
        <div class="dept-hero-kpis">
          <div class="dept-kpi" @click="router.push('/agents')">
            <div class="dept-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="dept-kpi-val">{{ data?.agents?.actifs ?? '-' }}</div>
              <div class="dept-kpi-lbl">Agents actifs</div>
            </div>
            <i class="fas fa-arrow-right dept-kpi-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="dept-kpi" @click="router.push('/taches')">
            <div class="dept-kpi-icon"><i class="fas fa-tasks"></i></div>
            <div>
              <div class="dept-kpi-val">{{ data?.taches?.en_cours ?? 0 }}</div>
              <div class="dept-kpi-lbl">Tâches en cours</div>
            </div>
            <i class="fas fa-arrow-right dept-kpi-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="dept-kpi" @click="router.push('/requests')">
            <div class="dept-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="dept-kpi-val">{{ data?.requests?.en_attente ?? 0 }}</div>
              <div class="dept-kpi-lbl">Demandes en attente</div>
            </div>
            <i class="fas fa-arrow-right dept-kpi-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="dept-kpi">
            <div class="dept-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="dept-kpi-val">{{ data?.attendance?.today_rate ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="dept-kpi-lbl">Présence aujourd'hui</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="dept-kpi">
            <div class="dept-kpi-icon"><i class="fas fa-umbrella-beach"></i></div>
            <div>
              <div class="dept-kpi-val">{{ data?.conges?.actifs ?? 0 }}</div>
              <div class="dept-kpi-lbl">Congés actifs</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord..." />

    <div v-else-if="loadError" class="alert alert-warning mx-3 mt-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <template v-else>
      <!-- ═══ ACTIONS RAPIDES ═══ -->
      <div class="dept-section">
        <div class="dept-section-head">
          <div class="dept-section-icon" style="background:#e0f2fe;color:#0077B5;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="dept-section-title">Actions rapides</h3>
            <p class="dept-section-sub">Accès direct aux modules de gestion</p>
          </div>
        </div>
        <div class="dept-actions">
          <router-link to="/taches/create" class="dept-action-card">
            <div class="dept-action-glow" style="background:#0077B5;"></div>
            <div class="dept-action-icon" style="background:#e0f2fe;color:#0077B5;"><i class="fas fa-plus-circle"></i></div>
            <div class="dept-action-label">Nouvelle tâche</div>
          </router-link>
          <router-link to="/agents" class="dept-action-card">
            <div class="dept-action-glow" style="background:#059669;"></div>
            <div class="dept-action-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-users"></i></div>
            <div class="dept-action-label">Agents</div>
          </router-link>
          <router-link to="/requests" class="dept-action-card">
            <div class="dept-action-glow" style="background:#7c3aed;"></div>
            <div class="dept-action-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-file-signature"></i></div>
            <div class="dept-action-label">Demandes</div>
          </router-link>
          <router-link to="/pointages" class="dept-action-card">
            <div class="dept-action-glow" style="background:#d97706;"></div>
            <div class="dept-action-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-user-clock"></i></div>
            <div class="dept-action-label">Présences</div>
          </router-link>
          <router-link to="/taches" class="dept-action-card">
            <div class="dept-action-glow" style="background:#dc2626;"></div>
            <div class="dept-action-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-tasks"></i></div>
            <div class="dept-action-label">Toutes les tâches</div>
          </router-link>
          <router-link to="/holiday-planning" class="dept-action-card">
            <div class="dept-action-glow" style="background:#0891b2;"></div>
            <div class="dept-action-icon" style="background:#cffafe;color:#0891b2;"><i class="fas fa-calendar-alt"></i></div>
            <div class="dept-action-label">Planning congés</div>
          </router-link>
        </div>
      </div>

      <!-- ═══ GRILLE PRINCIPALE ═══ -->
      <div class="dept-main-grid">

        <!-- ─── Gauche : Tâches récentes + Performance équipe ─── -->
        <div class="dept-col-left">

          <!-- Alertes -->
          <div class="dept-alerts" v-if="hasAlerts">
            <div v-if="data.taches.overdue > 0" class="dept-alert dept-alert-danger">
              <i class="fas fa-exclamation-circle me-2"></i>
              <strong>{{ data.taches.overdue }}</strong> tâche{{ data.taches.overdue > 1 ? 's' : '' }} en retard
            </div>
            <div v-if="data.requests.en_attente > 0" class="dept-alert dept-alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>{{ data.requests.en_attente }}</strong> demande{{ data.requests.en_attente > 1 ? 's' : '' }} en attente de traitement
            </div>
          </div>

          <!-- Tâches récentes -->
          <div class="dept-card">
            <div class="dept-card-head">
              <div class="dept-card-icon" style="background:#e0f2fe;color:#0077B5;">
                <i class="fas fa-tasks"></i>
              </div>
              <div>
                <h4 class="dept-card-title">Tâches récentes</h4>
                <p class="dept-card-sub">Suivi de l'exécution du département</p>
              </div>
              <router-link to="/taches" class="dept-card-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
            </div>

            <div v-if="data.recent_taches?.length === 0" class="dept-empty">
              <i class="fas fa-check-circle"></i>
              <p>Aucune tâche pour le moment</p>
            </div>
            <div v-else class="dept-tasks-list">
              <div v-for="t in data.recent_taches" :key="t.id" class="dept-task-row">
                <div class="dept-task-info">
                  <div class="dept-task-title">{{ t.titre }}</div>
                  <div class="dept-task-meta">
                    <span class="dept-tag" :class="statutClass(t.statut)">{{ statutLabel(t.statut) }}</span>
                    <span class="dept-task-agent" v-if="t.agent">
                      <i class="fas fa-user-circle me-1"></i>{{ t.agent.prenom }} {{ t.agent.nom }}
                    </span>
                    <span class="dept-task-due" v-if="t.date_echeance" :class="{ 'text-danger': isOverdue(t) }">
                      <i class="fas fa-clock me-1"></i>{{ formatDate(t.date_echeance) }}
                    </span>
                  </div>
                </div>
                <div class="dept-task-progress">
                  <div class="dept-progress-bar">
                    <div class="dept-progress-fill"
                      :style="{ width: (t.pourcentage || 0) + '%', background: progressColor(t.pourcentage) }">
                    </div>
                  </div>
                  <span class="dept-progress-pct">{{ t.pourcentage ?? 0 }}%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Performance équipe -->
          <div class="dept-card dept-card-mt">
            <div class="dept-card-head">
              <div class="dept-card-icon" style="background:#d1fae5;color:#059669;">
                <i class="fas fa-chart-bar"></i>
              </div>
              <div>
                <h4 class="dept-card-title">Performance de l'équipe</h4>
                <p class="dept-card-sub">Vue synthétique par agent</p>
              </div>
            </div>

            <div v-if="data.team_performance?.length === 0" class="dept-empty">
              <i class="fas fa-users"></i>
              <p>Aucun agent actif dans le département</p>
            </div>
            <div v-else class="dept-table-wrap">
              <table class="dept-table">
                <thead>
                  <tr>
                    <th>Agent</th>
                    <th class="text-center">Tâches</th>
                    <th class="text-center">Réalisation</th>
                    <th class="text-center">Retard</th>
                    <th class="text-center">Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ag in data.team_performance" :key="ag.id" @click="router.push('/agents/' + ag.id)" class="dept-table-row">
                    <td>
                      <div class="dept-agent-cell">
                        <div class="dept-agent-avatar">
                          <img v-if="ag.photo" :src="'/' + ag.photo" :alt="ag.prenom"
                            class="dept-agent-photo" @error="$event.target.style.display='none'">
                          <span v-else class="dept-agent-initials">{{ agentInitials(ag) }}</span>
                        </div>
                        <div>
                          <div class="dept-agent-name">{{ ag.prenom }} {{ ag.nom }}</div>
                          <div class="dept-agent-fonction">{{ ag.fonction ?? '—' }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <span class="dept-badge-total">{{ ag.taches_total }}</span>
                    </td>
                    <td class="text-center">
                      <div class="dept-mini-progress">
                        <div class="dept-mini-fill" :style="{ width: ag.avg_completion + '%', background: progressColor(ag.avg_completion) }"></div>
                      </div>
                      <span class="dept-mini-pct">{{ ag.avg_completion }}%</span>
                    </td>
                    <td class="text-center">
                      <span v-if="ag.taches_overdue > 0" class="dept-badge-danger">{{ ag.taches_overdue }}</span>
                      <span v-else class="dept-badge-ok"><i class="fas fa-check"></i></span>
                    </td>
                    <td class="text-center">
                      <span class="dept-perf-badge" :class="perfClass(ag.avg_completion)">{{ perfLabel(ag.avg_completion) }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ─── Droite : Infos Dept + Demandes en attente ─── -->
        <div class="dept-col-right">

          <!-- Résumé département -->
          <div class="dept-card">
            <div class="dept-card-head">
              <div class="dept-card-icon" style="background:#ede9fe;color:#7c3aed;">
                <i class="fas fa-building"></i>
              </div>
              <div>
                <h4 class="dept-card-title">{{ data.department?.nom ?? 'Mon département' }}</h4>
                <p class="dept-card-sub" v-if="data.department?.province">Province : {{ data.department.province }}</p>
              </div>
            </div>
            <div class="dept-kpi-grid">
              <div class="dept-mini-kpi">
                <div class="dept-mini-kpi-val" style="color:#0077B5;">{{ data.agents?.actifs ?? 0 }}</div>
                <div class="dept-mini-kpi-lbl">Agents actifs</div>
              </div>
              <div class="dept-mini-kpi">
                <div class="dept-mini-kpi-val" style="color:#059669;">{{ data.taches?.terminees ?? 0 }}</div>
                <div class="dept-mini-kpi-lbl">Tâches terminées</div>
              </div>
              <div class="dept-mini-kpi">
                <div class="dept-mini-kpi-val" style="color:#d97706;">{{ data.taches?.en_cours ?? 0 }}</div>
                <div class="dept-mini-kpi-lbl">En cours</div>
              </div>
              <div class="dept-mini-kpi">
                <div class="dept-mini-kpi-val" style="color:#dc2626;">{{ data.taches?.overdue ?? 0 }}</div>
                <div class="dept-mini-kpi-lbl">En retard</div>
              </div>
              <div class="dept-mini-kpi">
                <div class="dept-mini-kpi-val" style="color:#0891b2;">{{ data.attendance?.monthly_rate ?? 0 }}%</div>
                <div class="dept-mini-kpi-lbl">Présence (mois)</div>
              </div>
              <div class="dept-mini-kpi">
                <div class="dept-mini-kpi-val" style="color:#6d28d9;">{{ data.conges?.actifs ?? 0 }}</div>
                <div class="dept-mini-kpi-lbl">Congés actifs</div>
              </div>
            </div>
          </div>

          <!-- Demandes en attente -->
          <div class="dept-card dept-card-mt">
            <div class="dept-card-head">
              <div class="dept-card-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-file-signature"></i>
              </div>
              <div>
                <h4 class="dept-card-title">Demandes en attente</h4>
                <p class="dept-card-sub">Workflow Agent → Directeur → RH</p>
              </div>
              <router-link to="/requests" class="dept-card-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
            </div>

            <div v-if="data.pending_requests?.length === 0" class="dept-empty">
              <i class="fas fa-check-double"></i>
              <p>Aucune demande en attente</p>
            </div>

            <div v-else class="dept-requests-list">
              <div v-for="req in data.pending_requests" :key="req.id" class="dept-request-row">
                <div class="dept-request-info">
                  <div class="dept-request-type">
                    <i :class="requestIcon(req.type)" class="me-1"></i>{{ requestLabel(req.type) }}
                  </div>
                  <div class="dept-request-agent" v-if="req.agent">
                    {{ req.agent.prenom }} {{ req.agent.nom }}
                  </div>
                  <div class="dept-request-date">
                    <i class="fas fa-clock me-1"></i>{{ formatDate(req.created_at) }}
                  </div>
                </div>
                <!-- Directeur seulement peut viser -->
                <div class="dept-request-actions" v-if="auth.isDirecteur">
                  <router-link :to="'/requests/' + req.id" class="dept-btn dept-btn-primary" title="Voir & viser">
                    <i class="fas fa-eye"></i>
                  </router-link>
                </div>
                <div class="dept-request-actions" v-else>
                  <router-link :to="'/requests/' + req.id" class="dept-btn dept-btn-outline" title="Voir">
                    <i class="fas fa-eye"></i>
                  </router-link>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const auth   = useAuthStore()

// ─── État ───────────────────────────────────────────────────
const loading   = ref(true)
const loadError = ref(null)
const data      = ref({})

const photoIndex = ref(0)

// ─── Données agent connecté ─────────────────────────────────
const deptIsFemme = computed(() => {
  const s = auth.agent?.sexe?.toLowerCase() ?? ''
  return s === 'f' || s === 'femme' || s === 'féminin'
})
const deptCivility  = computed(() => deptIsFemme.value ? 'Mme' : 'M.')
const deptGreeting  = computed(() => {
  const h = new Date().getHours()
  const base = h < 12 ? 'Bonjour' : h < 18 ? 'Bon après-midi' : 'Bonsoir'
  return deptIsFemme.value ? base : base
})
const deptFullName  = computed(() => {
  const a = auth.agent
  if (!a) return ''
  return [a.prenom, a.postnom, a.nom].filter(Boolean).join(' ')
})
const deptFonction  = computed(() => auth.agent?.poste_actuel || auth.agent?.fonction || null)
const deptInitials  = computed(() => {
  const a = auth.agent
  if (!a) return 'D'
  return ((a.prenom?.[0] ?? '') + (a.nom?.[0] ?? '')).toUpperCase() || 'D'
})
const deptPhotoUrl = computed(() => {
  const photo = auth.agent?.photo
  if (!photo) return null
  const p = photo.trim()
  const candidates = []
  if (/^https?:\/\//i.test(p)) {
    candidates.push(p)
  } else {
    const n = p.replace(/^\/+/, '')
    candidates.push(`/${n}`)
    if (!n.startsWith('storage/')) candidates.push(`/storage/${n}`)
    if (!n.startsWith('uploads/') && !n.includes('/')) candidates.push(`/uploads/profiles/${n}`)
  }
  const uniq = [...new Set(candidates)]
  return uniq[photoIndex.value] ?? null
})
function handlePhotoError() {
  const photo = auth.agent?.photo
  if (!photo) return
  const p = photo.trim()
  const n = p.replace(/^\/+/, '')
  const max = [p, `/${n}`, `/storage/${n}`, `/uploads/profiles/${n}`].filter(Boolean).length
  if (photoIndex.value < max - 1) photoIndex.value++
  else photoIndex.value = max
}

// ─── Date du jour ───────────────────────────────────────────
const today = computed(() => {
  return new Date().toLocaleDateString('fr-CD', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  })
})

// ─── Alertes ────────────────────────────────────────────────
const hasAlerts = computed(() =>
  (data.value?.taches?.overdue ?? 0) > 0 ||
  (data.value?.requests?.en_attente ?? 0) > 0
)

// ─── Chargement des données ─────────────────────────────────
async function loadData() {
  loading.value   = true
  loadError.value = null
  try {
    const res = await client.get('/dashboard/department')
    data.value = res.data?.data ?? res.data ?? {}
  } catch (err) {
    loadError.value = err?.response?.data?.message ?? 'Impossible de charger le tableau de bord.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

// ─── Helpers ────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-CD', { day: '2-digit', month: 'short', year: 'numeric' })
}

function isOverdue(t) {
  if (!t.date_echeance || t.statut === 'terminee') return false
  return new Date(t.date_echeance) < new Date()
}

function agentInitials(ag) {
  return ((ag.prenom?.[0] ?? '') + (ag.nom?.[0] ?? '')).toUpperCase() || '?'
}

function statutLabel(s) {
  return { nouvelle: 'Nouvelle', en_cours: 'En cours', terminee: 'Terminée' }[s] ?? s
}
function statutClass(s) {
  return { nouvelle: 'tag-nouvelle', en_cours: 'tag-en-cours', terminee: 'tag-terminee' }[s] ?? ''
}
function progressColor(pct) {
  if (pct >= 80) return '#059669'
  if (pct >= 50) return '#d97706'
  return '#dc2626'
}
function perfClass(pct) {
  if (pct >= 80) return 'perf-bon'
  if (pct >= 50) return 'perf-moyen'
  return 'perf-faible'
}
function perfLabel(pct) {
  if (pct >= 80) return 'Bon'
  if (pct >= 50) return 'Moyen'
  return 'Faible'
}
function requestIcon(type) {
  const icons = {
    conge: 'fas fa-umbrella-beach',
    formation: 'fas fa-graduation-cap',
    autorisation: 'fas fa-file-alt',
    attestation: 'fas fa-certificate',
  }
  return icons[type] ?? 'fas fa-file'
}
function requestLabel(type) {
  const labels = {
    conge: 'Congé',
    formation: 'Formation',
    autorisation: 'Autorisation',
    attestation: 'Attestation',
  }
  return labels[type] ?? type ?? 'Demande'
}
</script>

<style scoped>
/* ─── Conteneur principal ───────────────────────────────── */
.dept-dashboard {
  min-height: 100vh;
  background: #f0f4fa;
}

/* ─── Hero ─────────────────────────────────────────────── */
.dept-hero {
  position: relative;
  background: linear-gradient(135deg, #1e3a5f 0%, #1565c0 60%, #0d47a1 100%);
  color: #fff;
  padding: 0;
  overflow: hidden;
}
.dept-hero-bg {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 80% 0%, rgba(255,255,255,.12) 0%, transparent 60%),
    radial-gradient(ellipse at 10% 100%, rgba(0,0,0,.18) 0%, transparent 55%);
  pointer-events: none;
}
.dept-hero-inner {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.5rem;
  padding: 2rem 2.5rem;
}
.dept-hero-left {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex: 1;
  min-width: 0;
}

/* Avatar */
.dept-hero-avatar {
  width: 68px; height: 68px;
  border-radius: 50%;
  flex-shrink: 0;
  overflow: hidden;
  border: 2.5px solid rgba(255,255,255,.35);
  box-shadow: 0 4px 16px rgba(0,0,0,.25);
  background: linear-gradient(135deg, rgba(101,163,213,.5), rgba(30,58,95,.6));
  display: flex; align-items: center; justify-content: center;
}
.dept-hero-avatar-photo { width: 100%; height: 100%; object-fit: cover; }
.dept-hero-avatar-initials { font-size: 1.4rem; font-weight: 800; color: #fff; letter-spacing: .5px; }

/* Textes hero */
.dept-hero-greeting { font-size: .85rem; opacity: .8; text-transform: uppercase; letter-spacing: 1px; }
h1.dept-hero-name { font-size: 1.4rem; font-weight: 800; margin: .2rem 0; }
.dept-hero-fonction { font-size: .88rem; opacity: .9; margin-bottom: .35rem; }
.dept-hero-role {
  background: rgba(255,255,255,.15);
  backdrop-filter: blur(4px);
  border-radius: 100px;
  padding: .3rem .85rem;
  font-size: .82rem;
  display: inline-flex; align-items: center; gap: .5rem;
  margin-bottom: .35rem;
}
.dept-hero-dept-badge {
  background: rgba(255,255,255,.25);
  border-radius: 100px;
  padding: .1rem .6rem;
  font-size: .78rem;
  font-weight: 600;
}
.dept-hero-date { font-size: .8rem; opacity: .75; }

/* KPIs hero */
.dept-hero-kpis {
  display: flex;
  align-items: center;
  gap: .5rem;
  flex-wrap: wrap;
}
.dept-kpi {
  display: flex;
  align-items: center;
  gap: .65rem;
  background: rgba(255,255,255,.12);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 14px;
  padding: .75rem 1rem;
  cursor: pointer;
  transition: background .2s;
  min-width: 120px;
}
.dept-kpi:hover { background: rgba(255,255,255,.22); }
.dept-kpi-icon {
  width: 36px; height: 36px;
  background: rgba(255,255,255,.2);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
}
.dept-kpi-val { font-size: 1.4rem; font-weight: 800; line-height: 1.1; }
.dept-kpi-lbl { font-size: .72rem; opacity: .8; }
.dept-kpi-arrow { opacity: .5; font-size: .75rem; margin-left: auto; }
.kpi-unit { font-size: .8rem; font-weight: 400; }
.kpi-divider { width: 1px; height: 44px; background: rgba(255,255,255,.2); }

/* ─── Sections communes ─────────────────────────────────── */
.dept-section { padding: 1.75rem 2rem 0; }
.dept-section-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
.dept-section-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.dept-section-title { font-size: 1rem; font-weight: 700; margin: 0; }
.dept-section-sub { font-size: .8rem; color: #6b7280; margin: 0; }

/* ─── Actions rapides ───────────────────────────────────── */
.dept-actions {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: .75rem;
}
.dept-action-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .6rem;
  padding: 1.1rem .75rem;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
  text-decoration: none;
  color: #1f2937;
  transition: transform .18s, box-shadow .18s;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid #e5e7eb;
}
.dept-action-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.1); }
.dept-action-glow {
  position: absolute;
  top: -30px; left: 50%;
  transform: translateX(-50%);
  width: 80px; height: 80px;
  border-radius: 50%;
  opacity: .1;
  pointer-events: none;
}
.dept-action-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.dept-action-label { font-size: .8rem; font-weight: 600; text-align: center; }

/* ─── Grille principale ─────────────────────────────────── */
.dept-main-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 1.5rem;
  padding: 1.5rem 2rem 2rem;
}
@media (max-width: 1024px) {
  .dept-main-grid { grid-template-columns: 1fr; }
}

/* ─── Cards ─────────────────────────────────────────────── */
.dept-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #e5e7eb;
  padding: 1.25rem;
}
.dept-card-mt { margin-top: 1.25rem; }
.dept-card-head {
  display: flex;
  align-items: center;
  gap: .85rem;
  margin-bottom: 1rem;
}
.dept-card-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.dept-card-title { font-size: .95rem; font-weight: 700; margin: 0; }
.dept-card-sub { font-size: .78rem; color: #6b7280; margin: 0; }
.dept-card-link { margin-left: auto; font-size: .8rem; color: #0077B5; text-decoration: none; white-space: nowrap; }
.dept-card-link:hover { text-decoration: underline; }

/* ─── Alertes ───────────────────────────────────────────── */
.dept-alerts { margin-bottom: 1.1rem; display: flex; flex-direction: column; gap: .5rem; }
.dept-alert {
  padding: .65rem 1rem;
  border-radius: 10px;
  font-size: .85rem;
}
.dept-alert-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.dept-alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }

/* ─── Liste tâches ──────────────────────────────────────── */
.dept-tasks-list { display: flex; flex-direction: column; gap: .6rem; }
.dept-task-row {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  padding: .65rem .85rem;
  border: 1px solid #f3f4f6;
  border-radius: 10px;
  background: #fafafa;
  transition: background .15s;
}
.dept-task-row:hover { background: #f0f4ff; }
.dept-task-info { flex: 1; min-width: 0; }
.dept-task-title { font-size: .88rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dept-task-meta { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; margin-top: .2rem; }
.dept-task-agent { font-size: .75rem; color: #6b7280; }
.dept-task-due { font-size: .75rem; color: #6b7280; }
.text-danger { color: #dc2626 !important; }

/* tags statut */
.dept-tag { font-size: .7rem; padding: .15rem .5rem; border-radius: 100px; font-weight: 600; }
.tag-nouvelle { background: #e0f2fe; color: #0369a1; }
.tag-en-cours { background: #fef3c7; color: #92400e; }
.tag-terminee { background: #d1fae5; color: #065f46; }

/* Barre de progression */
.dept-task-progress { display: flex; align-items: center; gap: .5rem; min-width: 90px; }
.dept-progress-bar { flex: 1; height: 6px; background: #e5e7eb; border-radius: 100px; overflow: hidden; }
.dept-progress-fill { height: 100%; border-radius: 100px; transition: width .3s; }
.dept-progress-pct { font-size: .75rem; font-weight: 600; color: #374151; width: 32px; text-align: right; }

/* ─── Tableau performance ───────────────────────────────── */
.dept-table-wrap { overflow-x: auto; }
.dept-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
.dept-table th { padding: .55rem .75rem; background: #f9fafb; color: #6b7280; font-size: .73rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
.dept-table td { padding: .55rem .75rem; border-bottom: 1px solid #f3f4f6; }
.dept-table-row { cursor: pointer; transition: background .15s; }
.dept-table-row:hover { background: #f0f4ff; }

.dept-agent-cell { display: flex; align-items: center; gap: .65rem; }
.dept-agent-avatar { width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #e0f2fe; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dept-agent-photo { width: 100%; height: 100%; object-fit: cover; }
.dept-agent-initials { font-size: .75rem; font-weight: 700; color: #0077B5; }
.dept-agent-name { font-weight: 600; }
.dept-agent-fonction { font-size: .73rem; color: #6b7280; }

.dept-badge-total { background: #e0f2fe; color: #0369a1; padding: .2rem .55rem; border-radius: 6px; font-weight: 700; font-size: .8rem; }
.dept-badge-danger { background: #fee2e2; color: #b91c1c; padding: .2rem .55rem; border-radius: 6px; font-weight: 700; font-size: .8rem; }
.dept-badge-ok { background: #d1fae5; color: #065f46; padding: .2rem .55rem; border-radius: 6px; font-size: .8rem; }

.dept-mini-progress { width: 60px; height: 6px; background: #e5e7eb; border-radius: 100px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: .35rem; }
.dept-mini-fill { height: 100%; border-radius: 100px; }
.dept-mini-pct { font-size: .78rem; font-weight: 600; color: #374151; }

.dept-perf-badge { padding: .2rem .6rem; border-radius: 100px; font-size: .73rem; font-weight: 700; }
.perf-bon { background: #d1fae5; color: #065f46; }
.perf-moyen { background: #fef3c7; color: #92400e; }
.perf-faible { background: #fee2e2; color: #b91c1c; }

/* ─── Mini KPI grid ─────────────────────────────────────── */
.dept-kpi-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .75rem;
  margin-top: .25rem;
}
.dept-mini-kpi {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: .75rem;
  text-align: center;
}
.dept-mini-kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1.1; }
.dept-mini-kpi-lbl { font-size: .72rem; color: #6b7280; margin-top: .2rem; }

/* ─── Liste demandes ────────────────────────────────────── */
.dept-requests-list { display: flex; flex-direction: column; gap: .6rem; }
.dept-request-row {
  display: flex; align-items: center; justify-content: space-between; gap: .75rem;
  padding: .65rem .85rem;
  background: #fafafa;
  border: 1px solid #f3f4f6;
  border-radius: 10px;
  transition: background .15s;
}
.dept-request-row:hover { background: #fef3c7; }
.dept-request-info { flex: 1; min-width: 0; }
.dept-request-type { font-size: .85rem; font-weight: 600; }
.dept-request-agent { font-size: .75rem; color: #6b7280; }
.dept-request-date { font-size: .73rem; color: #9ca3af; margin-top: .15rem; }
.dept-request-actions { display: flex; gap: .4rem; flex-shrink: 0; }

.dept-btn {
  width: 32px; height: 32px;
  border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .85rem;
  text-decoration: none;
  cursor: pointer;
  border: none;
  transition: background .15s;
}
.dept-btn-primary { background: #0077B5; color: #fff; }
.dept-btn-primary:hover { background: #005a8e; }
.dept-btn-outline { background: #e5e7eb; color: #374151; }
.dept-btn-outline:hover { background: #d1d5db; }

/* ─── Empty state ───────────────────────────────────────── */
.dept-empty {
  text-align: center;
  padding: 2rem 1rem;
  color: #9ca3af;
}
.dept-empty i { font-size: 2rem; margin-bottom: .5rem; display: block; }
.dept-empty p { margin: 0; font-size: .85rem; }

/* ─── Dark mode ─────────────────────────────────────────── */
html.dark .dept-dashboard { background: #111827; }
html.dark .dept-card { background: #1f2937 !important; border-color: #374151 !important; }
html.dark .dept-card-title { color: #f9fafb !important; }
html.dark .dept-card-sub { color: #9ca3af !important; }
html.dark .dept-action-card { background: #1f2937 !important; border-color: #374151 !important; color: #f9fafb !important; }
html.dark .dept-task-row { background: #1a2234 !important; border-color: #374151 !important; }
html.dark .dept-task-row:hover { background: #1e3a5f !important; }
html.dark .dept-task-title { color: #f3f4f6 !important; }
html.dark .dept-table th { background: #111827 !important; color: #9ca3af !important; border-color: #374151 !important; }
html.dark .dept-table td { border-color: #1f2937 !important; color: #e5e7eb !important; }
html.dark .dept-table-row:hover { background: #1e3a5f !important; }
html.dark .dept-mini-kpi { background: #111827 !important; border-color: #374151 !important; }
html.dark .dept-mini-kpi-lbl { color: #9ca3af !important; }
html.dark .dept-request-row { background: #1a2234 !important; border-color: #374151 !important; }
html.dark .dept-request-row:hover { background: #1e3a5f !important; }
html.dark .dept-request-agent { color: #9ca3af !important; }
html.dark .dept-request-date { color: #6b7280 !important; }
html.dark .dept-section-title { color: #f9fafb !important; }
html.dark .dept-section-sub { color: #9ca3af !important; }
html.dark .dept-action-label { color: #f3f4f6 !important; }
html.dark .dept-agent-name { color: #f3f4f6 !important; }
html.dark .dept-agent-fonction { color: #9ca3af !important; }
html.dark .dept-progress-pct { color: #d1d5db !important; }

/* ─── Responsive ────────────────────────────────────────── */
@media (max-width: 768px) {
  .dept-hero-inner { flex-direction: column; padding: 1.25rem; }
  .dept-hero-kpis { width: 100%; overflow-x: auto; padding-bottom: .5rem; }
  .dept-kpi { min-width: 110px; }
  .dept-section { padding: 1rem 1rem 0; }
  .dept-main-grid { padding: 1rem; gap: 1rem; }
  .dept-actions { grid-template-columns: repeat(3, 1fr); }
  h1.dept-hero-name { font-size: 1.1rem; }
}
@media (max-width: 480px) {
  .dept-actions { grid-template-columns: repeat(2, 1fr); }
  .dept-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
