<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="notif-hero">
      <h2><i class="fas fa-bell me-2"></i>Notifications</h2>
      <p>Restez informe de toutes les activites du portail</p>
      <div class="notif-hero-stats">
        <div>
          <div class="notif-hero-stat-val">{{ meta.total }}</div>
          <div class="notif-hero-stat-lbl">Total</div>
        </div>
        <div>
          <div class="notif-hero-stat-val">{{ nonLuesCount }}</div>
          <div class="notif-hero-stat-lbl">Non lues</div>
        </div>
      </div>
    </div>

    <!-- Actions (filtres) -->
    <div class="notif-actions">
      <button class="notif-action-btn" :class="{ active: !filtre }" @click="setFiltre('')">Toutes</button>
      <button class="notif-action-btn" :class="{ active: filtre === 'non_lues' }" @click="setFiltre('non_lues')">Non lues</button>
      <button class="notif-action-btn" :class="{ active: filtre === 'demande' }" @click="setFiltre('demande')">Demandes</button>
      <button class="notif-action-btn" :class="{ active: filtre === 'communique' }" @click="setFiltre('communique')">Communiques</button>
      <button class="notif-action-btn" :class="{ active: filtre === 'plan_travail' }" @click="setFiltre('plan_travail')">Plan de travail</button>
      <button class="notif-action-btn" :class="{ active: filtre === 'tache' }" @click="setFiltre('tache')">Taches</button>
      <button class="notif-action-btn" :class="{ active: filtre === 'document_travail' }" @click="setFiltre('document_travail')">Documents</button>
      <button v-if="nonLuesCount > 0" class="notif-action-btn mark-all ms-auto" @click="handleMarkAllRead">
        <i class="fas fa-check-double me-1"></i> Tout marquer lu
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement des notifications..." />
    </div>

    <!-- Notification list -->
    <template v-else-if="notifications.length">
      <div class="notif-list">
        <div
          v-for="notif in notifications"
          :key="notif.id"
          class="notif-card"
          :class="{ unread: !notif.lu }"
        >
          <div class="notif-card-icon" :style="{ background: (notif.couleur || '#6b7280') + '15', color: notif.couleur || '#6b7280' }">
            <i class="fas" :class="notif.icone || 'fa-bell'"></i>
          </div>
          <div class="notif-card-content">
            <div class="notif-card-title">{{ notif.titre }}</div>
            <div class="notif-card-msg">{{ notif.message }}</div>
            <div class="notif-card-meta">
              <span class="notif-card-time"><i class="fas fa-clock me-1"></i>{{ timeAgo(notif.created_at) }}</span>
              <span class="notif-card-type">{{ typeLabel(notif.type) }}</span>
              <span v-if="notif.emetteur" class="notif-card-time"><i class="fas fa-user me-1"></i>{{ notif.emetteur.name }}</span>
            </div>
          </div>
          <div class="notif-card-actions">
            <button v-if="notif.lien" class="notif-card-btn read-btn" @click="handleReadAndGo(notif)">
              <i class="fas fa-eye"></i> Voir
            </button>
            <button v-else-if="!notif.lu" class="notif-card-btn read-btn" @click="handleMarkRead(notif)">
              <i class="fas fa-check"></i> Lu
            </button>
            <button class="notif-card-btn del-btn" @click="handleDelete(notif)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="d-flex justify-content-center mt-4">
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
              <button class="page-link" @click="loadNotifications(meta.current_page - 1)">&laquo;</button>
            </li>
            <li
              v-for="page in paginationPages"
              :key="page"
              class="page-item"
              :class="{ active: page === meta.current_page }"
            >
              <button class="page-link" @click="loadNotifications(page)">{{ page }}</button>
            </li>
            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
              <button class="page-link" @click="loadNotifications(meta.current_page + 1)">&raquo;</button>
            </li>
          </ul>
        </nav>
      </div>
    </template>

    <!-- Empty -->
    <div v-else class="notif-empty">
      <div class="notif-empty-icon"><i class="fas fa-bell-slash"></i></div>
      <h5>Aucune notification</h5>
      <p>Vous n'avez pas encore de notifications{{ filtre ? ' dans cette categorie' : '' }}.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { list, markRead, markAllRead, remove } from '@/api/notifications'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const notifications = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null })
const nonLuesCount = ref(0)
const filtre = ref('')

const paginationPages = computed(() => {
  const pages = []
  const total = meta.value.last_page
  const current = meta.value.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function loadNotifications(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filtre.value) params.filtre = filtre.value
    const { data } = await list(params)
    notifications.value = data.data
    meta.value = data.meta
    nonLuesCount.value = data.nonLuesCount
  } catch {
    ui.addToast('Erreur lors du chargement des notifications.', 'danger')
  } finally {
    loading.value = false
  }
}

function setFiltre(f) {
  filtre.value = f
  loadNotifications(1)
}

async function handleMarkRead(notif) {
  try {
    await markRead(notif.id)
    notif.lu = true
    nonLuesCount.value = Math.max(0, nonLuesCount.value - 1)
  } catch {
    ui.addToast('Erreur.', 'danger')
  }
}

async function handleReadAndGo(notif) {
  try {
    const { data } = await markRead(notif.id)
    notif.lu = true
    nonLuesCount.value = Math.max(0, nonLuesCount.value - 1)
    if (data.lien) {
      router.push(data.lien)
    }
  } catch {
    ui.addToast('Erreur.', 'danger')
  }
}

async function handleMarkAllRead() {
  try {
    await markAllRead()
    notifications.value.forEach(n => { n.lu = true })
    nonLuesCount.value = 0
    ui.addToast('Toutes les notifications marquees comme lues.', 'success')
  } catch {
    ui.addToast('Erreur.', 'danger')
  }
}

async function handleDelete(notif) {
  try {
    await remove(notif.id)
    notifications.value = notifications.value.filter(n => n.id !== notif.id)
    if (!notif.lu) nonLuesCount.value = Math.max(0, nonLuesCount.value - 1)
    meta.value.total--
    ui.addToast('Notification supprimee.', 'success')
  } catch {
    ui.addToast('Erreur.', 'danger')
  }
}

function typeLabel(type) {
  const map = {
    demande: 'Demande',
    demande_modifiee: 'Modification',
    demande_approuvee: 'Approbation',
    demande_rejetee: 'Rejet',
    plan_travail: 'Plan de travail',
    tache: 'Tache',
    communique: 'Communique',
    message: 'Message',
    document_travail: 'Document',
  }
  return map[type] || 'Notification'
}

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const now = new Date()
  const diffMs = now - d
  const diffMin = Math.floor(diffMs / 60000)
  const diffH = Math.floor(diffMs / 3600000)
  const diffD = Math.floor(diffMs / 86400000)
  if (diffMin < 1) return 'A l\'instant'
  if (diffMin < 60) return `Il y a ${diffMin} min`
  if (diffH < 24) return `Il y a ${diffH}h`
  if (diffD < 30) return `Il y a ${diffD}j`
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => loadNotifications())
</script>

<style scoped>
.notif-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #003f5c 100%);
  border-radius: 18px;
  padding: 2rem 2.2rem;
  margin-bottom: 1.5rem;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.notif-hero::after {
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
.notif-hero::before {
  content: '';
  position: absolute;
  top: -40%; right: -8%;
  width: 240px; height: 240px;
  border-radius: 50%;
  background: rgba(255,255,255,.07);
}
.notif-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .3rem; }
.notif-hero p { font-size: .85rem; opacity: .8; margin: 0; }
.notif-hero-stats { display: flex; gap: 1.5rem; margin-top: 1rem; }
.notif-hero-stat-val { font-size: 1.5rem; font-weight: 800; }
.notif-hero-stat-lbl { font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .5px; }

.notif-actions { display: flex; gap: .6rem; margin-bottom: 1.2rem; flex-wrap: wrap; }
.notif-action-btn {
  padding: .4rem .9rem; border-radius: 20px; font-size: .78rem; font-weight: 600;
  border: 2px solid #e5e7eb; background: #fff; color: #6b7280; cursor: pointer; transition: all .2s;
}
.notif-action-btn:hover { border-color: #0077B5; color: #0077B5; }
.notif-action-btn.active { background: #0077B5; border-color: #0077B5; color: #fff; }
.notif-action-btn.mark-all { background: #f0f9ff; border-color: #bae6fd; color: #0077B5; }
.notif-action-btn.mark-all:hover { background: #0077B5; color: #fff; border-color: #0077B5; }

.notif-list { display: flex; flex-direction: column; gap: .5rem; }
.notif-card {
  display: flex; align-items: center; gap: 1rem; padding: 1rem 1.2rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  transition: all .2s; position: relative;
}
.notif-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.06); transform: translateY(-1px); }
.notif-card.unread { background: #f0f9ff; border-color: #bae6fd; }
.notif-card.unread::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0;
  width: 4px; background: #0077B5; border-radius: 14px 0 0 14px;
}
.notif-card-icon {
  width: 46px; height: 46px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.notif-card-content { flex: 1; min-width: 0; }
.notif-card-title { font-weight: 700; font-size: .88rem; color: #1e293b; margin-bottom: .2rem; }
.notif-card-msg { font-size: .8rem; color: #6b7280; line-height: 1.4; }
.notif-card-meta { display: flex; align-items: center; gap: .5rem; margin-top: .3rem; }
.notif-card-time { font-size: .72rem; color: #9ca3af; }
.notif-card-type {
  font-size: .65rem; font-weight: 600; padding: .15rem .5rem;
  border-radius: 6px; background: #f3f4f6; color: #6b7280;
}
.notif-card-actions { display: flex; flex-direction: column; gap: .3rem; flex-shrink: 0; }
.notif-card-btn {
  padding: .25rem .6rem; border-radius: 6px; font-size: .7rem; font-weight: 600;
  text-align: center; transition: all .2s; border: none; cursor: pointer;
}
.notif-card-btn.read-btn { background: #f0f9ff; color: #0077B5; }
.notif-card-btn.read-btn:hover { background: #0077B5; color: #fff; }
.notif-card-btn.del-btn { background: #fef2f2; color: #ef4444; }
.notif-card-btn.del-btn:hover { background: #ef4444; color: #fff; }

.notif-empty { text-align: center; padding: 3rem 1rem; color: #9ca3af; }
.notif-empty-icon {
  width: 64px; height: 64px; border-radius: 50%; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; margin: 0 auto 1rem; color: #d1d5db;
}

@media (max-width: 576px) {
  .notif-card { flex-wrap: wrap; gap: .6rem; }
  .notif-card-actions { flex-direction: row; width: 100%; }
}

/* ── Tablet/Mobile Responsive ── */
@media (max-width: 767.98px) {
  .notif-hero {
    padding: 1.25rem 1rem;
    border-radius: 12px;
  }
  .notif-hero::after {
    width: 120px;
    height: 120px;
  }
  .notif-hero h2 {
    font-size: 1.1rem;
  }
  .notif-hero p {
    font-size: .78rem;
  }
  .notif-hero-stats {
    gap: 1rem;
    margin-top: .75rem;
  }
  .notif-hero-stat-val {
    font-size: 1.2rem;
  }
  .notif-hero-stat-lbl {
    font-size: .65rem;
  }
  .notif-actions {
    gap: .4rem;
    margin-bottom: 1rem;
  }
  .notif-action-btn {
    padding: .35rem .7rem;
    font-size: .72rem;
  }
  .notif-card {
    padding: .85rem 1rem;
    border-radius: 10px;
    gap: .75rem;
  }
  .notif-card-icon {
    width: 38px;
    height: 38px;
    font-size: .95rem;
    border-radius: 10px;
  }
  .notif-card-title {
    font-size: .82rem;
  }
  .notif-card-msg {
    font-size: .75rem;
  }
  .notif-card-meta {
    flex-wrap: wrap;
    gap: .35rem;
  }
  .notif-card-time {
    font-size: .68rem;
  }
  .notif-card-type {
    font-size: .6rem;
  }
  .notif-card-btn {
    font-size: .65rem;
    padding: .2rem .5rem;
  }
  .notif-empty-icon {
    width: 52px;
    height: 52px;
    font-size: 1.2rem;
  }
  .notif-empty h5 {
    font-size: 1rem;
  }
  .notif-empty p {
    font-size: .82rem;
  }
  .pagination .page-link {
    font-size: .75rem;
    padding: .3rem .6rem;
  }
}
</style>
