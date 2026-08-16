<template>
  <div v-if="isPWA" ref="rootEl" class="sync-status-bar" :class="'sync-' + status">
    <button
      type="button"
      class="sync-indicator"
      :title="message"
      :aria-label="message"
      :aria-expanded="showPanel"
      @click="togglePanel"
    >
      <span class="sync-dot" aria-hidden="true"></span>
      <i class="fas" :class="iconClass"></i>
      <span class="sync-label d-none d-xxl-inline">{{ label }}</span>
    </button>

    <div v-if="showPanel" class="sync-panel">
      <div class="sync-panel-header">
        <span>File de synchronisation</span>
        <button type="button" class="sync-panel-close" @click="showPanel = false">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div v-if="queueItemsLoading" class="sync-panel-empty">Chargement...</div>
      <div v-else-if="!queueItems.length" class="sync-panel-empty">
        <i class="fas fa-check-circle"></i> Rien en attente
      </div>
      <ul v-else class="sync-panel-list">
        <li v-for="item in queueItems" :key="item.id" class="sync-panel-item" :class="'item-' + item.status">
          <div class="sync-item-main">
            <span class="sync-item-entity">{{ entityLabel(item.entity) }}</span>
            <span class="sync-item-status" :class="'badge-' + item.status">{{ statusLabel(item.status) }}</span>
          </div>
          <p v-if="item.last_error && ['error', 'blocked_auth', 'retryable_error'].includes(item.status)" class="sync-item-error">
            {{ item.last_error }}
          </p>
          <div v-if="['error', 'blocked_auth', 'retryable_error'].includes(item.status)" class="sync-item-actions">
            <button type="button" class="sync-item-btn retry" :disabled="actingOn === item.id" @click="retry(item)">
              <i class="fas fa-redo"></i> Réessayer
            </button>
            <button type="button" class="sync-item-btn cancel" :disabled="actingOn === item.id" @click="cancel(item)">
              <i class="fas fa-trash"></i> Supprimer
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import syncService from '@/services/syncService'
import offlineAssetPreparation from '@/services/offlineAssetPreparation'

const isPWA = ref(true) // PWA is always available
const status = ref('online') // offline | online | syncing | synced | error
const message = ref('Système PWA prêt')
const syncStats = ref({})
const offlinePreparation = ref(null)
let statsInterval = null

const rootEl = ref(null)
const showPanel = ref(false)
const queueItems = ref([])
const queueItemsLoading = ref(false)
const actingOn = ref(null)

const ENTITY_LABELS = {
  pointage: 'Pointage',
  tache: 'Tâche',
  demande: 'Demande',
  conge: 'Congé',
  signalement: 'Signalement',
  plan_travail: 'Plan de travail',
}

function entityLabel(entity) {
  return ENTITY_LABELS[entity] || entity
}

const STATUS_LABELS = {
  pending: 'En attente',
  syncing: 'Synchronisation...',
  synced: 'Synchronisé',
  retryable_error: 'Nouvelle tentative prévue',
  error: 'Échec',
  blocked_auth: 'Connexion requise',
}

function statusLabel(statusValue) {
  return STATUS_LABELS[statusValue] || statusValue
}

async function loadQueueItems() {
  queueItemsLoading.value = true
  try {
    queueItems.value = await syncService.getQueueItemsDetailed()
  } finally {
    queueItemsLoading.value = false
  }
}

async function togglePanel() {
  showPanel.value = !showPanel.value
  if (showPanel.value) {
    await loadQueueItems()
  }
}

async function retry(item) {
  actingOn.value = item.id
  try {
    await syncService.retryQueueItem(item.id)
    await loadQueueItems()
  } finally {
    actingOn.value = null
  }
}

async function cancel(item) {
  actingOn.value = item.id
  try {
    await syncService.cancelQueueItem(item.id)
    await loadQueueItems()
  } finally {
    actingOn.value = null
  }
}

function handleClickOutside(event) {
  if (showPanel.value && rootEl.value && !rootEl.value.contains(event.target)) {
    showPanel.value = false
  }
}

const iconClass = computed(() => ({
  'fa-sync-alt': status.value === 'syncing',
  'fa-check-circle': status.value === 'synced',
  'fa-wifi': status.value === 'online',
  'fa-wifi-slash': status.value === 'offline',
  'fa-exclamation-circle': status.value === 'error',
}))

const label = computed(() => {
  if (offlinePreparation.value?.status === 'preparing') {
    return `Hors ligne ${offlinePreparation.value.percent}%`
  }
  if (offlinePreparation.value?.status === 'paused') {
    return `Hors ligne ${offlinePreparation.value.percent}%`
  }

  switch (status.value) {
    case 'syncing':
      return syncStats.value.total ? `Sync ${syncStats.value.total}` : 'Sync...'
    case 'synced':
      return 'Prêt'
    case 'online':
      return syncStats.value.total ? `${syncStats.value.total} à envoyer` : 'En ligne'
    case 'offline':
      return syncStats.value.total ? `Hors ligne (${syncStats.value.total})` : 'Hors ligne'
    case 'error':
      return 'Erreur'
    default:
      return 'PWA'
  }
})

// Mise à jour du statut réseau et de synchronisation
async function updateStatus() {
  try {
    const isOnline = navigator.onLine
    const stats = await syncService.getSyncStats()
    syncStats.value = stats

    if (offlinePreparation.value?.status === 'preparing') {
      status.value = 'syncing'
      message.value = `Préparation hors ligne : ${offlinePreparation.value.completed}/${offlinePreparation.value.total} ressources`
      return
    }

    if (offlinePreparation.value?.status === 'paused' && !isOnline) {
      status.value = 'offline'
      message.value = `Préparation suspendue à ${offlinePreparation.value.percent} %, reprise à la reconnexion`
      return
    }

    if (!isOnline) {
      status.value = 'offline'
      message.value = stats.total > 0 ?
        `Mode hors ligne - ${stats.total} pointage(s) en attente` :
        'Mode hors ligne - aucune donnée en attente'
    } else if (stats.isSyncing) {
      status.value = 'syncing'
      message.value = 'Synchronisation en cours...'
    } else if (stats.total > 0) {
      status.value = 'online'
      message.value = `${stats.total} pointage(s) en attente de synchronisation`
    } else if (stats.errors > 0) {
      status.value = 'error'
      message.value = `${stats.errors} erreur(s) de synchronisation`
    } else {
      status.value = 'synced'
      message.value = 'Toutes les données sont synchronisées'
    }
  } catch (error) {
    status.value = 'error'
    message.value = 'Erreur lors de la vérification du statut'
  }
}

function handleOfflinePreparation(event) {
  offlinePreparation.value = event.detail

  if (event.detail.status === 'preparing') {
    status.value = 'syncing'
    message.value = `Préparation hors ligne : ${event.detail.completed}/${event.detail.total} ressources`
  } else if (event.detail.status === 'paused') {
    status.value = 'offline'
    message.value = `Préparation suspendue à ${event.detail.percent} %, reprise à la reconnexion`
  } else if (event.detail.status === 'ready') {
    status.value = 'synced'
    message.value = event.detail.persistent
      ? 'Application complète disponible hors ligne avec stockage persistant'
      : 'Application complète disponible hors ligne'
  } else if (event.detail.status === 'error') {
    status.value = 'error'
    message.value = event.detail.error || 'Préparation hors ligne impossible'
  }
}

onMounted(() => {
  isPWA.value = 'serviceWorker' in navigator || window.matchMedia?.('(display-mode: standalone)')?.matches
  offlinePreparation.value = offlineAssetPreparation.getState()

  // Mise à jour initiale
  updateStatus()

  // Écouter les changements de connexion
  window.addEventListener('online', updateStatus)
  window.addEventListener('offline', updateStatus)
  window.addEventListener('epnmls:offline-preparation', handleOfflinePreparation)

  // Mise à jour périodique du statut
  statsInterval = setInterval(() => {
    updateStatus()
    if (showPanel.value) {
      loadQueueItems()
    }
  }, 5000) // Toutes les 5 secondes

  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('online', updateStatus)
  window.removeEventListener('offline', updateStatus)
  window.removeEventListener('epnmls:offline-preparation', handleOfflinePreparation)
  document.removeEventListener('click', handleClickOutside)
  if (statsInterval) {
    clearInterval(statsInterval)
  }
})
</script>

<style scoped>
.sync-status-bar {
  position: relative;
  display: inline-flex;
  align-items: center;
  margin-right: .35rem;
}
.sync-indicator {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  gap: .42rem;
  padding: .35rem .68rem;
  border: 1px solid rgba(255, 255, 255, .24);
  border-radius: 999px;
  color: #fff;
  background: rgba(255, 255, 255, .12);
  box-shadow:
    0 8px 18px rgba(0, 30, 50, .12),
    0 1px 0 rgba(255, 255, 255, .22) inset;
  cursor: pointer;
  font: inherit;
  font-size: .73rem;
  font-weight: 850;
  line-height: 1;
  transition: transform .2s ease, background .2s ease, border-color .2s ease;
}
.sync-indicator:hover {
  transform: translateY(-1px);
  background: rgba(255, 255, 255, .18);
}
.sync-indicator:focus-visible {
  outline: 2px solid #fff;
  outline-offset: 2px;
}
.sync-dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: currentColor;
  box-shadow: 0 0 0 4px color-mix(in srgb, currentColor 18%, transparent);
}
.sync-offline .sync-indicator { color: #fecaca; border-color: rgba(248, 113, 113, .36); background: rgba(239, 68, 68, .16); }
.sync-online .sync-indicator { color: #bae6fd; border-color: rgba(125, 211, 252, .36); background: rgba(14, 165, 233, .14); }
.sync-syncing .sync-indicator { color: #fde68a; border-color: rgba(245, 158, 11, .38); background: rgba(245, 158, 11, .16); }
.sync-syncing .fa-sync-alt { animation: spin 1.5s linear infinite; }
.sync-synced .sync-indicator { color: #bbf7d0; border-color: rgba(74, 222, 128, .36); background: rgba(34, 197, 94, .16); }
.sync-error .sync-indicator { color: #fecaca; border-color: rgba(248, 113, 113, .4); background: rgba(220, 38, 38, .16); }
.sync-label { white-space: nowrap; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@supports not (color: color-mix(in srgb, white 50%, transparent)) {
  .sync-dot {
    box-shadow: 0 0 0 4px rgba(255, 255, 255, .12);
  }
}

:global(html.dark) .sync-indicator {
  border-color: rgba(255, 255, 255, .12);
  background: rgba(255, 255, 255, .08);
}

.sync-panel {
  position: absolute;
  top: calc(100% + .5rem);
  right: 0;
  width: min(340px, 90vw);
  max-height: 380px;
  display: flex;
  flex-direction: column;
  background: #fff;
  color: #1e293b;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, .18);
  z-index: 1050;
  overflow: hidden;
}
.sync-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .7rem .9rem;
  border-bottom: 1px solid #e2e8f0;
  font-size: .8rem;
  font-weight: 800;
}
.sync-panel-close {
  border: none;
  background: none;
  color: #94a3b8;
  font-size: .8rem;
  cursor: pointer;
  padding: .2rem;
}
.sync-panel-close:hover { color: #1e293b; }
.sync-panel-empty {
  padding: 1.4rem 1rem;
  text-align: center;
  color: #94a3b8;
  font-size: .8rem;
}
.sync-panel-list {
  list-style: none;
  margin: 0;
  padding: .4rem;
  overflow-y: auto;
}
.sync-panel-item {
  padding: .55rem .6rem;
  border-radius: 10px;
  margin-bottom: .25rem;
}
.sync-panel-item:hover { background: #f8fafc; }
.sync-item-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
  font-size: .78rem;
  font-weight: 700;
}
.sync-item-status {
  font-size: .62rem;
  font-weight: 700;
  padding: .12rem .45rem;
  border-radius: 999px;
  text-transform: uppercase;
  letter-spacing: .3px;
  white-space: nowrap;
}
.badge-pending, .badge-retryable_error { background: #fef9c3; color: #b45309; }
.badge-syncing { background: #dbeafe; color: #1d4ed8; }
.badge-synced { background: #dcfce7; color: #15803d; }
.badge-error, .badge-blocked_auth { background: #fee2e2; color: #b91c1c; }
.sync-item-error {
  margin: .3rem 0 0;
  font-size: .7rem;
  color: #b91c1c;
  line-height: 1.3;
}
.sync-item-actions {
  display: flex;
  gap: .4rem;
  margin-top: .4rem;
}
.sync-item-btn {
  flex: 1;
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 8px;
  padding: .3rem .5rem;
  font-size: .68rem;
  font-weight: 700;
  cursor: pointer;
  transition: background .15s;
}
.sync-item-btn:disabled { opacity: .5; cursor: not-allowed; }
.sync-item-btn.retry:hover:not(:disabled) { background: #e0f2fe; border-color: #7dd3fc; color: #0077B5; }
.sync-item-btn.cancel:hover:not(:disabled) { background: #fee2e2; border-color: #fca5a5; color: #b91c1c; }

:global(html.dark) .sync-panel {
  background: var(--dm-bg-card);
  color: var(--dm-text);
  border-color: var(--dm-border);
}
:global(html.dark) .sync-panel-header {
  border-bottom-color: var(--dm-border);
}
:global(html.dark) .sync-panel-item:hover {
  background: var(--dm-bg-card2);
}
:global(html.dark) .sync-item-btn {
  background: var(--dm-bg-card2);
  border-color: var(--dm-border);
  color: var(--dm-text);
}
</style>
