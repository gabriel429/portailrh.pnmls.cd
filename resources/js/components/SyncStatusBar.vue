<template>
  <div v-if="isPWA" class="sync-status-bar" :class="'sync-' + status">
    <div class="sync-indicator" :title="message">
      <i class="fas" :class="iconClass"></i>
      <span class="sync-label d-none d-lg-inline">{{ label }}</span>
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
  'fa-sync': status.value === 'syncing',
  'fa-cloud-check': status.value === 'synced',
  'fa-wifi': status.value === 'online',
  'fa-wifi-slash': status.value === 'offline',
  'fa-exclamation-triangle': status.value === 'error',
}))

const label = computed(() => {
  switch (status.value) {
    case 'syncing':
      return syncStats.value.total ? `Sync (${syncStats.value.total})` : 'Sync...'
    case 'synced':
      return 'Synchro'
    case 'online':
      return syncStats.value.total ? `${syncStats.value.total} en attente` : 'En ligne'
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
        `Mode offline - ${stats.total} pointage(s) en attente` :
        'Mode offline - Aucune donnée en attente'
    } else if (stats.isSyncing) {
      status.value = 'syncing'
      message.value = `Synchronisation en cours...`
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
  display: inline-flex; align-items: center; margin-right: .5rem;
}
.sync-indicator {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .25rem .6rem; border-radius: 999px;
  font-size: .72rem; font-weight: 600; cursor: default;
  transition: all .3s ease;
}
.sync-offline .sync-indicator { background: rgba(239,68,68,.12); color: #dc2626; }
.sync-online .sync-indicator { background: rgba(14,165,233,.12); color: #0ea5e9; }
.sync-syncing .sync-indicator { background: rgba(217,119,6,.12); color: #d97706; }
.sync-syncing .fa-sync { animation: spin 1.5s linear infinite; }
.sync-synced .sync-indicator { background: rgba(5,150,105,.12); color: #059669; }
.sync-error .sync-indicator { background: rgba(220,38,38,.12); color: #dc2626; }
.sync-label { white-space: nowrap; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
