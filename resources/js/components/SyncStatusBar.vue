<template>
  <div v-if="isPWA" class="sync-status-bar" :class="'sync-' + status">
    <div class="sync-indicator" :title="message" :aria-label="message">
      <span class="sync-dot" aria-hidden="true"></span>
      <i class="fas" :class="iconClass"></i>
      <span class="sync-label d-none d-xxl-inline">{{ label }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import syncService from '@/services/syncService'

const isPWA = ref(true) // PWA is always available
const status = ref('online') // offline | online | syncing | synced | error
const message = ref('Système PWA prêt')
const syncStats = ref({})
let statsInterval = null

const iconClass = computed(() => ({
  'fa-sync-alt': status.value === 'syncing',
  'fa-check-circle': status.value === 'synced',
  'fa-wifi': status.value === 'online',
  'fa-wifi-slash': status.value === 'offline',
  'fa-exclamation-circle': status.value === 'error',
}))

const label = computed(() => {
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

onMounted(() => {
  isPWA.value = 'serviceWorker' in navigator || window.matchMedia?.('(display-mode: standalone)')?.matches

  // Mise à jour initiale
  updateStatus()

  // Écouter les changements de connexion
  window.addEventListener('online', updateStatus)
  window.addEventListener('offline', updateStatus)

  // Mise à jour périodique du statut
  statsInterval = setInterval(updateStatus, 5000) // Toutes les 5 secondes
})

onUnmounted(() => {
  window.removeEventListener('online', updateStatus)
  window.removeEventListener('offline', updateStatus)
  if (statsInterval) {
    clearInterval(statsInterval)
  }
})
</script>

<style scoped>
.sync-status-bar {
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
  cursor: default;
  font-size: .73rem;
  font-weight: 850;
  line-height: 1;
  transition: transform .2s ease, background .2s ease, border-color .2s ease;
}
.sync-indicator:hover {
  transform: translateY(-1px);
  background: rgba(255, 255, 255, .18);
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
</style>
