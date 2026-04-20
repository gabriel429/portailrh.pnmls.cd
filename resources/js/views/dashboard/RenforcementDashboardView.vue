<template>
  <div class="rc-dashboard">

    <!-- ═══ HERO ═══ -->
    <div class="rc-hero">
      <div class="rc-hero-bg"></div>
      <div class="rc-hero-inner">
        <div class="rc-hero-left">
          <div class="rc-hero-avatar">
            <img v-if="rcPhotoUrl" :src="rcPhotoUrl" :alt="rcFullName"
              class="rc-hero-avatar-photo" @error="handlePhotoError">
            <span v-else class="rc-hero-avatar-initials">{{ rcInitials }}</span>
          </div>
          <div>
            <div class="rc-hero-greeting">{{ rcGreeting }},</div>
            <h1 class="rc-hero-name">{{ rcCivility }} {{ rcFullName }}</h1>
            <div class="rc-hero-role" v-if="rcFonction">
              <i class="fas fa-id-badge me-1"></i>{{ rcFonction }}
            </div>
            <div class="rc-hero-role">
              <i class="fas fa-graduation-cap me-1"></i>
              Section Renforcement des Capacités — DRRC
            </div>
            <div class="rc-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>
        <div class="rc-hero-kpis">
          <div class="rc-kpi rc-kpi-clickable" @click="router.push('/renforcements')">
            <div class="rc-kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div>
              <div class="rc-kpi-val">{{ data.formations?.total ?? '-' }}</div>
              <div class="rc-kpi-lbl">Formations</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rc-kpi rc-kpi-clickable" @click="router.push('/renforcements?statut=en_cours')">
            <div class="rc-kpi-icon"><i class="fas fa-spinner"></i></div>
            <div>
              <div class="rc-kpi-val">{{ data.formations?.en_cours ?? 0 }}</div>
              <div class="rc-kpi-lbl">En cours</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rc-kpi rc-kpi-clickable" @click="router.push('/requests?type=renforcement_capacites')">
            <div class="rc-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="rc-kpi-val">{{ data.demandes?.en_attente ?? 0 }}</div>
              <div class="rc-kpi-lbl">Demandes</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rc-kpi">
            <div class="rc-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="rc-kpi-val">{{ data.formations?.nb_beneficiaires_annee ?? 0 }}</div>
              <div class="rc-kpi-lbl">Bénéficiaires {{ currentYear }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord Renforcement..." />

    <div v-else-if="loadError" class="alert alert-warning mx-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <template v-else>

      <!-- ═══ ACTIONS RAPIDES ═══ -->
      <div class="rc-section">
        <div class="rc-section-head">
          <div class="rc-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="rc-section-title">Actions rapides</h3>
            <p class="rc-section-sub">Accès direct aux modules du Renforcement des Capacités</p>
          </div>
        </div>
        <div class="rc-actions">
          <router-link v-for="a in quickActions" :key="a.to" :to="a.to" class="rc-action">
            <div class="rc-action-glow" :style="{ background: a.color }"></div>
            <div class="rc-action-icon" :style="{ background: a.bg, color: a.color }">
              <i class="fas" :class="a.icon"></i>
            </div>
            <div class="rc-action-text">
              <div class="rc-action-label">{{ a.label }}</div>
              <div class="rc-action-desc">{{ a.desc }}</div>
            </div>
            <i class="fas fa-chevron-right rc-action-arrow"></i>
          </router-link>
        </div>
      </div>

      <!-- ═══ INDICATEURS FORMATIONS ═══ -->
      <div class="rc-section">
        <div class="rc-section-head">
          <div class="rc-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="rc-section-title">Formations {{ currentYear }}</h3>
            <p class="rc-section-sub">{{ data.formations?.annee ?? 0 }} formations cette année — {{ formatBudget(data.formations?.budget_total) }} budget</p>
          </div>
        </div>
        <div class="rc-stat-grid">
          <div class="rc-stat-card rc-clickable" @click="router.push('/renforcements?statut=planifiee')">
            <div class="rc-stat-icon" style="background:#e0f2fe;color:#0ea5e9;">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="rc-stat-val" style="color:#0ea5e9;">{{ data.formations?.planifiee ?? 0 }}</div>
            <div class="rc-stat-lbl">Planifiées</div>
            <div class="rc-stat-bar">
              <div class="rc-stat-bar-fill" style="background:#0ea5e9;" :style="{ width: fmtPct(data.formations?.planifiee, data.formations?.total) + '%' }"></div>
            </div>
          </div>
          <div class="rc-stat-card rc-clickable" @click="router.push('/renforcements?statut=en_cours')">
            <div class="rc-stat-icon" style="background:#fef3c7;color:#d97706;">
              <i class="fas fa-spinner"></i>
            </div>
            <div class="rc-stat-val" style="color:#d97706;">{{ data.formations?.en_cours ?? 0 }}</div>
            <div class="rc-stat-lbl">En cours</div>
            <div class="rc-stat-bar">
              <div class="rc-stat-bar-fill" style="background:#d97706;" :style="{ width: fmtPct(data.formations?.en_cours, data.formations?.total) + '%' }"></div>
            </div>
          </div>
          <div class="rc-stat-card rc-clickable" @click="router.push('/renforcements?statut=terminee')">
            <div class="rc-stat-icon" style="background:#d1fae5;color:#059669;">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="rc-stat-val" style="color:#059669;">{{ data.formations?.terminee ?? 0 }}</div>
            <div class="rc-stat-lbl">Terminées</div>
            <div class="rc-stat-bar">
              <div class="rc-stat-bar-fill" style="background:#059669;" :style="{ width: fmtPct(data.formations?.terminee, data.formations?.total) + '%' }"></div>
            </div>
          </div>
          <div class="rc-stat-card">
            <div class="rc-stat-icon" style="background:#ede9fe;color:#7c3aed;">
              <i class="fas fa-users"></i>
            </div>
            <div class="rc-stat-val" style="color:#7c3aed;">{{ data.formations?.nb_beneficiaires_annee ?? 0 }}</div>
            <div class="rc-stat-lbl">Bénéficiaires</div>
            <div class="rc-stat-bar">
              <div class="rc-stat-bar-fill" style="background:#7c3aed;width:100%;"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ FORMATIONS À VENIR ═══ -->
      <div class="rc-section" v-if="(data.formations_a_venir || []).length">
        <div class="rc-section-head">
          <div class="rc-section-icon" style="background:#cffafe;color:#0891b2;">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div>
            <h3 class="rc-section-title">Formations à venir (30 jours)</h3>
            <p class="rc-section-sub">{{ (data.formations_a_venir || []).length }} formation(s) programmée(s)</p>
          </div>
        </div>
        <div class="rc-upcoming-list">
          <router-link v-for="f in data.formations_a_venir" :key="f.id" :to="'/renforcements/' + f.id" class="rc-upcoming-item">
            <div class="rc-upcoming-dot"></div>
            <div class="rc-upcoming-info">
              <div class="rc-upcoming-titre">{{ f.titre }}</div>
              <div class="rc-upcoming-meta">
                <span><i class="fas fa-calendar me-1"></i>{{ formatDate(f.date_debut) }}</span>
                <span v-if="f.lieu"><i class="fas fa-map-marker-alt me-1"></i>{{ f.lieu }}</span>
              </div>
            </div>
            <span class="rc-upcoming-badge">{{ daysUntil(f.date_debut) }}</span>
          </router-link>
        </div>
      </div>

      <!-- ═══ GRID: FORMATIONS RÉCENTES + DEMANDES EN ATTENTE ═══ -->
      <div class="rc-section">
        <div class="rc-dual-grid">

          <!-- Formations récentes -->
          <div>
            <div class="rc-section-head">
              <div class="rc-section-icon" style="background:#d1fae5;color:#059669;">
                <i class="fas fa-chalkboard-teacher"></i>
              </div>
              <div>
                <h3 class="rc-section-title">Formations récentes</h3>
                <p class="rc-section-sub">{{ data.formations?.total ?? 0 }} au total</p>
              </div>
              <router-link to="/renforcements" class="rc-section-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
            </div>
            <div v-if="!(data.recent_formations?.length)" class="rc-empty">
              <div class="rc-empty-icon" style="background:#d1fae5;"><i class="fas fa-chalkboard-teacher" style="color:#059669;"></i></div>
              <span>Aucune formation enregistrée</span>
            </div>
            <div v-else class="rc-formation-list">
              <router-link v-for="f in data.recent_formations" :key="f.id" :to="'/renforcements/' + f.id" class="rc-formation-row">
                <div class="rc-formation-left">
                  <div class="rc-formation-statut-dot" :class="'fstatut-' + f.statut"></div>
                  <div class="rc-formation-info">
                    <div class="rc-formation-titre">{{ f.titre }}</div>
                    <div class="rc-formation-meta">
                      <span class="rc-tag" :class="'fstatut-tag-' + f.statut">{{ statutLabel(f.statut) }}</span>
                      <span v-if="f.date_debut"><i class="fas fa-calendar me-1"></i>{{ formatDate(f.date_debut) }}</span>
                      <span v-if="f.nb_beneficiaires"><i class="fas fa-users me-1"></i>{{ f.nb_beneficiaires }}</span>
                      <span v-if="f.budget" class="rc-budget">{{ formatBudget(f.budget) }}</span>
                    </div>
                  </div>
                </div>
                <i class="fas fa-chevron-right rc-row-arrow"></i>
              </router-link>
            </div>
          </div>

          <!-- Demandes en attente -->
          <div>
            <div class="rc-section-head">
              <div class="rc-section-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <h3 class="rc-section-title">Demandes en attente</h3>
                <p class="rc-section-sub">{{ data.demandes?.en_attente ?? 0 }} à traiter</p>
              </div>
              <router-link to="/requests?type=renforcement_capacites" class="rc-section-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
            </div>
            <div v-if="!(data.recent_demandes?.length)" class="rc-empty">
              <div class="rc-empty-icon" style="background:#fef9ee;"><i class="fas fa-check-circle" style="color:#16a34a;"></i></div>
              <span>Aucune demande en attente</span>
            </div>
            <div v-else class="rc-demande-list">
              <router-link v-for="d in data.recent_demandes" :key="d.id" :to="'/requests/' + d.id" class="rc-demande-row">
                <div class="rc-demande-dot" style="background:#d97706;"></div>
                <div class="rc-demande-info">
                  <div class="rc-demande-agent">{{ d.agent?.prenom }} {{ d.agent?.nom }}</div>
                  <div class="rc-demande-meta">
                    <span class="rc-tag rc-tag-pending">En attente</span>
                    <span><i class="fas fa-clock me-1"></i>{{ formatTime(d.created_at) }}</span>
                  </div>
                  <div v-if="d.description" class="rc-demande-desc">{{ truncate(d.description, 80) }}</div>
                </div>
                <i class="fas fa-chevron-right rc-row-arrow"></i>
              </router-link>
            </div>

            <!-- Stats rapides demandes -->
            <div class="rc-demande-stats">
              <div class="rc-demande-stat rc-clickable" @click="router.push('/requests?type=renforcement_capacites&statut=approuvé')">
                <span class="rc-demande-stat-val" style="color:#059669;">{{ data.demandes?.approuve ?? 0 }}</span>
                <span class="rc-demande-stat-lbl">Approuvées</span>
              </div>
              <div class="rc-demande-stat rc-clickable" @click="router.push('/requests?type=renforcement_capacites&statut=rejeté')">
                <span class="rc-demande-stat-val" style="color:#dc2626;">{{ data.demandes?.rejete ?? 0 }}</span>
                <span class="rc-demande-stat-lbl">Rejetées</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ TÂCHES ═══ -->
      <div class="rc-section">
        <div class="rc-section-head">
          <div class="rc-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div>
            <h3 class="rc-section-title">Mes tâches</h3>
            <p class="rc-section-sub">{{ data.taches?.total ?? 0 }} tâches assignées</p>
          </div>
          <router-link to="/taches" class="rc-section-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
        </div>
        <div class="rc-task-grid">
          <div class="rc-task-card rc-clickable" @click="router.push('/taches?statut=nouvelle')">
            <div class="rc-task-icon" style="background:#dbeafe;color:#3b82f6;"><i class="fas fa-plus-circle"></i></div>
            <div class="rc-task-val">{{ data.taches?.nouvelle ?? 0 }}</div>
            <div class="rc-task-lbl">Nouvelles</div>
          </div>
          <div class="rc-task-card rc-clickable" @click="router.push('/taches?statut=en_cours')">
            <div class="rc-task-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-spinner"></i></div>
            <div class="rc-task-val">{{ data.taches?.en_cours ?? 0 }}</div>
            <div class="rc-task-lbl">En cours</div>
          </div>
          <div class="rc-task-card rc-clickable" @click="router.push('/taches?statut=terminee')">
            <div class="rc-task-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
            <div class="rc-task-val">{{ data.taches?.terminee ?? 0 }}</div>
            <div class="rc-task-lbl">Terminées</div>
          </div>
          <div class="rc-task-card rc-clickable" :class="{ 'rc-task-alert': (data.taches?.overdue ?? 0) > 0 }" @click="router.push('/taches?statut=en_retard')">
            <div class="rc-task-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="rc-task-val">{{ data.taches?.overdue ?? 0 }}</div>
            <div class="rc-task-lbl">En retard</div>
          </div>
        </div>

        <div v-if="(data.recent_taches || []).length" class="rc-task-list">
          <router-link v-for="t in data.recent_taches" :key="t.id" :to="'/taches/' + t.id" class="rc-task-row">
            <div class="rc-task-prio" :class="'prio-' + t.priorite"></div>
            <div class="rc-task-info">
              <div class="rc-task-titre">{{ t.titre }}</div>
              <div class="rc-task-meta">
                <span class="rc-tag" :class="tStatutClass(t.statut)">{{ tStatutLabel(t.statut) }}</span>
                <span v-if="t.date_echeance" class="rc-task-due" :class="{ 'rc-overdue': isOverdue(t) }">
                  <i class="fas fa-clock me-1"></i>{{ formatDate(t.date_echeance) }}
                </span>
              </div>
            </div>
            <div class="rc-task-prog">
              <div class="rc-prog-track">
                <div class="rc-prog-fill" :style="{ width: (t.pourcentage || 0) + '%', background: progColor(t.pourcentage) }"></div>
              </div>
              <span class="rc-prog-pct">{{ t.pourcentage ?? 0 }}%</span>
            </div>
          </router-link>
        </div>
      </div>

      <!-- ═══ PLAN DE TRAVAIL ═══ -->
      <div class="rc-section">
        <div class="rc-section-head">
          <div class="rc-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-tasks"></i>
          </div>
          <div>
            <h3 class="rc-section-title">Plan de travail {{ currentYear }}</h3>
            <p class="rc-section-sub">{{ data.plan_travail?.terminee ?? 0 }} / {{ data.plan_travail?.total ?? 0 }} activités terminées ({{ data.plan_travail?.avg ?? 0 }}%)</p>
          </div>
          <router-link to="/plan-travail" class="rc-section-link">Voir PTA <i class="fas fa-arrow-right"></i></router-link>
        </div>
        <div class="rc-pta-row">
          <div class="rc-pta-ring-wrap">
            <svg viewBox="0 0 120 120" class="rc-ring-svg">
              <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="10"/>
              <circle cx="60" cy="60" r="52" fill="none" stroke="#059669" stroke-width="10"
                stroke-linecap="round"
                :stroke-dasharray="ringCirc"
                :stroke-dashoffset="ringOffset"
                transform="rotate(-90 60 60)"/>
            </svg>
            <div class="rc-ring-center">
              <div class="rc-ring-val">{{ data.plan_travail?.avg ?? 0 }}%</div>
              <div class="rc-ring-lbl">Global</div>
            </div>
          </div>
          <div class="rc-pta-stats">
            <div class="rc-pta-stat">
              <div class="rc-pta-stat-icon" style="background:#e0f2fe;color:#0ea5e9;"><i class="fas fa-list"></i></div>
              <div>
                <div class="rc-pta-stat-val">{{ data.plan_travail?.total ?? 0 }}</div>
                <div class="rc-pta-stat-lbl">Activités totales</div>
              </div>
            </div>
            <div class="rc-pta-stat">
              <div class="rc-pta-stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-check-circle"></i></div>
              <div>
                <div class="rc-pta-stat-val">{{ data.plan_travail?.terminee ?? 0 }}</div>
                <div class="rc-pta-stat-lbl">Terminées</div>
              </div>
            </div>
            <div class="rc-pta-stat">
              <div class="rc-pta-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-spinner"></i></div>
              <div>
                <div class="rc-pta-stat-val">{{ data.plan_travail?.en_cours ?? 0 }}</div>
                <div class="rc-pta-stat-lbl">En cours</div>
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
const auth = useAuthStore()
const loading = ref(true)
const loadError = ref(null)
const data = ref({})
const currentYear = new Date().getFullYear()

// ─── PHOTO & IDENTITÉ ─────────────────────────────────────────────────────────
const photoIndex = ref(0)

const rcPhotoUrl = computed(() => {
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
  return [...new Set(candidates)][photoIndex.value] ?? null
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

const isFemme = computed(() => {
  const s = (auth.agent?.sexe ?? '').toLowerCase()
  return s === 'f' || s === 'femme' || s === 'féminin'
})

const rcCivility  = computed(() => isFemme.value ? 'Mme' : 'M.')
const rcGreeting  = computed(() => isFemme.value ? 'Bienvenue' : 'Bienvenu')
const rcFullName  = computed(() => {
  const a = auth.agent
  if (!a) return auth.user?.name || 'Chef Section RC'
  return `${a.prenom ?? ''} ${a.nom ?? ''}`.trim()
})
const rcInitials  = computed(() => {
  const a = auth.agent
  if (!a) return 'RC'
  return ((a.prenom?.[0] ?? '') + (a.nom?.[0] ?? '')).toUpperCase() || 'RC'
})
const rcFonction  = computed(() => auth.agent?.fonction || auth.agent?.poste_actuel || null)

const today = computed(() => new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
}))

// ─── QUICK ACTIONS ────────────────────────────────────────────────────────────
const quickActions = [
  { to: '/renforcements', label: 'Formations', desc: 'Gérer les formations RC', icon: 'fa-chalkboard-teacher', color: '#059669', bg: '#d1fae5' },
  { to: '/renforcements/create', label: 'Planifier', desc: 'Nouvelle formation', icon: 'fa-plus-circle', color: '#0ea5e9', bg: '#e0f2fe' },
  { to: '/requests?type=renforcement_capacites', label: 'Demandes', desc: 'Traiter les demandes RC', icon: 'fa-paper-plane', color: '#d97706', bg: '#fef3c7' },
  { to: '/plan-travail', label: 'Plan travail', desc: 'Activités PTA RC', icon: 'fa-tasks', color: '#7c3aed', bg: '#ede9fe' },
  { to: '/taches', label: 'Mes tâches', desc: 'Suivi de mes tâches', icon: 'fa-clipboard-list', color: '#0891b2', bg: '#cffafe' },
  { to: '/messages', label: 'Messages', desc: 'Communication RH', icon: 'fa-envelope', color: '#be185d', bg: '#fce7f3' },
]

// ─── HELPERS ──────────────────────────────────────────────────────────────────
function fmtPct(val, total) {
  if (!total) return 0
  return Math.min(Math.round(((val ?? 0) / total) * 100), 100)
}

function formatBudget(val) {
  if (!val) return '—'
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(val)
}

function formatDate(val) {
  if (!val) return ''
  return new Date(val).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatTime(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1) return "À l'instant"
  if (diff < 60) return `Il y a ${diff} min`
  if (diff < 1440) return `Il y a ${Math.floor(diff / 60)}h`
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

function truncate(str, n) {
  if (!str) return ''
  return str.length > n ? str.slice(0, n) + '…' : str
}

function daysUntil(dateStr) {
  if (!dateStr) return ''
  const diff = Math.ceil((new Date(dateStr) - new Date()) / 86400000)
  if (diff === 0) return 'Aujourd\'hui'
  if (diff === 1) return 'Demain'
  return `Dans ${diff} j`
}

function statutLabel(s) {
  const map = { planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' }
  return map[s] ?? s
}

function tStatutLabel(s) {
  const map = { nouvelle: 'Nouvelle', en_cours: 'En cours', terminee: 'Terminée', suspendue: 'Suspendue' }
  return map[s] ?? s
}

function tStatutClass(s) {
  const map = { nouvelle: 'tag-nouvelle', en_cours: 'tag-en-cours', terminee: 'tag-terminee', suspendue: 'tag-suspendue' }
  return map[s] ?? ''
}

function isOverdue(t) {
  if (!t.date_echeance || t.statut === 'terminee') return false
  return new Date(t.date_echeance) < new Date()
}

function progColor(pct) {
  if (pct >= 80) return '#059669'
  if (pct >= 50) return '#d97706'
  return '#dc2626'
}

// ─── RING PTA ─────────────────────────────────────────────────────────────────
const ringCirc = (2 * Math.PI * 52).toFixed(1)
const ringOffset = computed(() => {
  const pctVal = data.value.plan_travail?.avg ?? 0
  return ((2 * Math.PI * 52) - (2 * Math.PI * 52 * pctVal / 100)).toFixed(1)
})

// ─── LOAD DATA ────────────────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const { data: result } = await client.get('/dashboard/renforcement')
    data.value = result
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Impossible de charger le tableau de bord Renforcement.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.rc-dashboard { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }

/* ═══ HERO ═══ */
.rc-hero {
  position: relative; border-radius: 20px; overflow: hidden; margin-bottom: 1.8rem;
  background: linear-gradient(135deg, #052e16 0%, #065f46 30%, #047857 60%, #059669 100%);
  box-shadow: 0 8px 32px rgba(5, 46, 22, .28);
}
.rc-hero-bg {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 400px 300px at 85% 20%, rgba(52,211,153,.25) 0%, transparent 70%),
    radial-gradient(ellipse 200px 200px at 10% 80%, rgba(16,185,129,.12) 0%, transparent 60%);
  pointer-events: none;
}
.rc-hero-inner {
  position: relative; z-index: 1; padding: 2rem 2.5rem;
  display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;
}
.rc-hero-left { display: flex; align-items: center; gap: 1.2rem; color: #fff; }

.rc-hero-avatar {
  width: 68px; height: 68px; border-radius: 50%; flex-shrink: 0; overflow: hidden;
  border: 2.5px solid rgba(255,255,255,.35);
  box-shadow: 0 4px 16px rgba(0,0,0,.25);
  background: linear-gradient(135deg, rgba(52,211,153,.5), rgba(4,120,87,.6));
  display: flex; align-items: center; justify-content: center;
}
.rc-hero-avatar-photo { width: 100%; height: 100%; object-fit: cover; }
.rc-hero-avatar-initials { font-size: 1.4rem; font-weight: 800; color: #fff; }

.rc-hero-greeting { font-size: .82rem; opacity: .6; font-weight: 500; letter-spacing: .5px; text-transform: uppercase; }
.rc-hero-name { font-size: 1.5rem; font-weight: 800; margin: .1rem 0 .2rem; color: #fff; }
.rc-hero-role { font-size: .78rem; font-weight: 600; opacity: .75; margin-bottom: .2rem; display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
.rc-hero-date { font-size: .72rem; opacity: .45; text-transform: capitalize; }

.rc-hero-kpis {
  display: flex; align-items: center; gap: 0;
  background: rgba(255,255,255,.08); border-radius: 16px; padding: .8rem 1.2rem;
  border: 1px solid rgba(255,255,255,.1); backdrop-filter: blur(8px);
}
.rc-kpi { display: flex; align-items: center; gap: .6rem; padding: 0 1rem; color: #fff; }
.rc-kpi-clickable { cursor: pointer; transition: opacity .2s; }
.rc-kpi-clickable:hover { opacity: .85; }
.rc-kpi-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.1); display: flex; align-items: center;
  justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.rc-kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.rc-kpi-lbl { font-size: .62rem; opacity: .5; text-transform: uppercase; letter-spacing: .4px; margin-top: .1rem; font-weight: 600; }
.kpi-divider { width: 1px; height: 36px; background: rgba(255,255,255,.12); margin: 0 .2rem; }

/* ═══ SECTIONS ═══ */
.rc-section { margin-bottom: 1.8rem; }
.rc-section-head { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.rc-section-icon {
  width: 40px; height: 40px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0;
}
.rc-section-title { font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0; }
.rc-section-sub { font-size: .72rem; color: #94a3b8; margin: 0; font-weight: 500; }
.rc-section-link {
  margin-left: auto; font-size: .75rem; font-weight: 600; color: #059669;
  text-decoration: none; display: flex; align-items: center; gap: .3rem; white-space: nowrap;
}
.rc-section-link:hover { color: #047857; }

/* ═══ QUICK ACTIONS ═══ */
.rc-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.rc-action {
  position: relative; display: flex; align-items: center; gap: .8rem; padding: 1rem 1.1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; overflow: hidden;
}
.rc-action-glow { position: absolute; top: 0; left: 0; width: 3px; height: 100%; opacity: 0; transition: opacity .25s; }
.rc-action:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rc-action:hover .rc-action-glow { opacity: 1; }
.rc-action-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.rc-action-text { flex: 1; min-width: 0; }
.rc-action-label { font-size: .84rem; font-weight: 700; }
.rc-action-desc { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
.rc-action-arrow { font-size: .65rem; color: #cbd5e1; transition: all .2s; }
.rc-action:hover .rc-action-arrow { color: #059669; transform: translateX(3px); }

/* ═══ STAT GRID ═══ */
.rc-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.rc-stat-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.1rem; transition: all .25s;
}
.rc-clickable { cursor: pointer; }
.rc-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rc-stat-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: .9rem; margin-bottom: .7rem; }
.rc-stat-val { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.rc-stat-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.rc-stat-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: .7rem; }
.rc-stat-bar-fill { height: 100%; border-radius: 4px; transition: width .8s ease; min-width: 3px; }

/* ═══ UPCOMING ═══ */
.rc-upcoming-list { display: flex; flex-direction: column; gap: .5rem; }
.rc-upcoming-item {
  display: flex; align-items: center; gap: .75rem; padding: .8rem 1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
  text-decoration: none; color: #374151; transition: all .2s;
}
.rc-upcoming-item:hover { border-color: #a7f3d0; box-shadow: 0 4px 16px rgba(5,150,105,.08); }
.rc-upcoming-dot { width: 10px; height: 10px; border-radius: 50%; background: #059669; flex-shrink: 0; }
.rc-upcoming-info { flex: 1; min-width: 0; }
.rc-upcoming-titre { font-size: .88rem; font-weight: 700; color: #1e293b; }
.rc-upcoming-meta { font-size: .72rem; color: #94a3b8; display: flex; gap: .8rem; margin-top: .2rem; }
.rc-upcoming-badge {
  font-size: .7rem; font-weight: 700; background: #d1fae5; color: #059669;
  padding: .2rem .6rem; border-radius: 8px; white-space: nowrap;
}

/* ═══ DUAL GRID ═══ */
.rc-dual-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }

/* ═══ FORMATION LIST ═══ */
.rc-formation-list { display: flex; flex-direction: column; gap: .4rem; }
.rc-formation-row {
  display: flex; align-items: center; justify-content: space-between; gap: .75rem;
  padding: .75rem 1rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
  text-decoration: none; color: #374151; transition: all .2s;
}
.rc-formation-row:hover { border-color: #a7f3d0; box-shadow: 0 4px 12px rgba(5,150,105,.08); }
.rc-formation-left { display: flex; align-items: flex-start; gap: .7rem; flex: 1; min-width: 0; }
.rc-formation-statut-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: .35rem; }
.fstatut-planifiee { background: #0ea5e9; }
.fstatut-en_cours  { background: #d97706; }
.fstatut-terminee  { background: #059669; }
.fstatut-annulee   { background: #94a3b8; }
.rc-formation-titre { font-size: .85rem; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rc-formation-meta { font-size: .7rem; color: #94a3b8; display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .2rem; }
.rc-budget { color: #059669; font-weight: 600; }
.rc-row-arrow { font-size: .65rem; color: #cbd5e1; flex-shrink: 0; }

/* ═══ DEMANDE LIST ═══ */
.rc-demande-list { display: flex; flex-direction: column; gap: .4rem; margin-bottom: .75rem; }
.rc-demande-row {
  display: flex; align-items: flex-start; gap: .75rem; padding: .75rem 1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
  text-decoration: none; color: #374151; transition: all .2s;
}
.rc-demande-row:hover { border-color: #fde68a; box-shadow: 0 4px 12px rgba(217,119,6,.08); }
.rc-demande-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: .35rem; }
.rc-demande-info { flex: 1; min-width: 0; }
.rc-demande-agent { font-size: .85rem; font-weight: 700; color: #1e293b; }
.rc-demande-meta { font-size: .7rem; color: #94a3b8; display: flex; gap: .5rem; margin-top: .2rem; }
.rc-demande-desc { font-size: .72rem; color: #64748b; margin-top: .3rem; font-style: italic; }
.rc-demande-stats { display: flex; gap: .75rem; margin-top: .5rem; }
.rc-demande-stat {
  flex: 1; display: flex; align-items: center; gap: .5rem; padding: .6rem .9rem;
  background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;
  transition: all .2s;
}
.rc-demande-stat:hover { border-color: #cbd5e1; }
.rc-demande-stat-val { font-size: 1.1rem; font-weight: 800; }
.rc-demande-stat-lbl { font-size: .7rem; color: #94a3b8; font-weight: 500; }

/* ═══ TAGS ═══ */
.rc-tag {
  display: inline-flex; align-items: center; padding: .15rem .5rem; border-radius: 6px;
  font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
}
.fstatut-tag-planifiee { background: #e0f2fe; color: #0369a1; }
.fstatut-tag-en_cours  { background: #fef3c7; color: #92400e; }
.fstatut-tag-terminee  { background: #d1fae5; color: #065f46; }
.fstatut-tag-annulee   { background: #f1f5f9; color: #64748b; }
.rc-tag-pending        { background: #fef3c7; color: #92400e; }
.tag-nouvelle  { background: #dbeafe; color: #1e40af; }
.tag-en-cours  { background: #fef3c7; color: #92400e; }
.tag-terminee  { background: #d1fae5; color: #065f46; }
.tag-suspendue { background: #f1f5f9; color: #64748b; }

/* ═══ TÂCHES ═══ */
.rc-task-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-bottom: 1rem; }
.rc-task-card {
  display: flex; flex-direction: column; align-items: center; padding: 1.1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  transition: all .25s; text-align: center;
}
.rc-task-card.rc-clickable { cursor: pointer; }
.rc-task-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rc-task-card.rc-task-alert { border-color: #fecaca; }
.rc-task-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: .9rem; margin-bottom: .6rem; }
.rc-task-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.rc-task-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-top: .2rem; }

.rc-task-list { display: flex; flex-direction: column; gap: .4rem; }
.rc-task-row {
  display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
  text-decoration: none; color: #374151; transition: all .2s;
}
.rc-task-row:hover { border-color: #c4b5fd; box-shadow: 0 4px 12px rgba(124,58,237,.08); }
.rc-task-prio { width: 4px; height: 36px; border-radius: 4px; flex-shrink: 0; }
.prio-haute   { background: #dc2626; }
.prio-moyenne { background: #d97706; }
.prio-basse   { background: #16a34a; }
.rc-task-info { flex: 1; min-width: 0; }
.rc-task-titre { font-size: .85rem; font-weight: 700; color: #1e293b; }
.rc-task-meta { font-size: .7rem; color: #94a3b8; display: flex; gap: .5rem; margin-top: .2rem; flex-wrap: wrap; }
.rc-task-due { font-size: .7rem; }
.rc-overdue { color: #dc2626; font-weight: 700; }
.rc-task-prog { display: flex; align-items: center; gap: .5rem; width: 100px; flex-shrink: 0; }
.rc-prog-track { flex: 1; height: 6px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.rc-prog-fill { height: 100%; border-radius: 4px; transition: width .6s ease; }
.rc-prog-pct { font-size: .72rem; font-weight: 700; color: #64748b; white-space: nowrap; }

/* ═══ PTA ═══ */
.rc-pta-row { display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; }
.rc-pta-ring-wrap { position: relative; width: 120px; height: 120px; flex-shrink: 0; }
.rc-ring-svg { width: 120px; height: 120px; }
.rc-ring-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.rc-ring-val { font-size: 1.4rem; font-weight: 800; color: #059669; }
.rc-ring-lbl { font-size: .65rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.rc-pta-stats { display: flex; flex-direction: column; gap: .75rem; flex: 1; }
.rc-pta-stat {
  display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
}
.rc-pta-stat-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
.rc-pta-stat-val { font-size: 1.2rem; font-weight: 800; line-height: 1; }
.rc-pta-stat-lbl { font-size: .7rem; color: #94a3b8; font-weight: 500; }

/* ═══ EMPTY ═══ */
.rc-empty { display: flex; flex-direction: column; align-items: center; gap: .6rem; padding: 2rem; background: #f8fafc; border-radius: 12px; color: #94a3b8; font-size: .82rem; }
.rc-empty-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }

/* ═══ RESPONSIVE ═══ */
@media (max-width: 768px) {
  .rc-hero-inner { flex-direction: column; padding: 1.5rem 1rem; }
  .rc-hero-kpis { flex-wrap: wrap; gap: .5rem; padding: .6rem .8rem; }
  .rc-actions { grid-template-columns: repeat(2, 1fr); }
  .rc-stat-grid { grid-template-columns: repeat(2, 1fr); }
  .rc-task-grid { grid-template-columns: repeat(2, 1fr); }
  .rc-dual-grid { grid-template-columns: 1fr; }
  .rc-pta-row { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 480px) {
  .rc-actions { grid-template-columns: 1fr; }
  .rc-stat-grid { grid-template-columns: repeat(2, 1fr); }
  .rc-task-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
