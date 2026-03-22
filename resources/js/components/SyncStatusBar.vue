<template>
  <div v-if="isDesktop" class="sync-status-bar" :class="'sync-' + status">
    <div class="sync-indicator" :title="message">
      <i class="fas" :class="iconClass"></i>
      <span class="sync-label d-none d-lg-inline">{{ label }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const isDesktop = ref(typeof window !== 'undefined' && window.desktopAPI?.isDesktop?.())
const status = ref('offline') // offline | online | syncing | synced | error
const message = ref('')

const iconClass = computed(() => ({
  'fa-cloud-arrow-up': status.value === 'syncing',
  'fa-cloud-check': status.value === 'synced',
  'fa-cloud': status.value === 'online',
  'fa-cloud-xmark': status.value === 'offline',
  'fa-triangle-exclamation': status.value === 'error',
}))

const label = computed(() => {
  switch (status.value) {
    case 'syncing': return 'Sync...'
    case 'synced': return 'Synchro'
    case 'online': return 'En ligne'
    case 'offline': return 'Hors ligne'
    case 'error': return 'Erreur'
    default: return ''
  }
})

onMounted(() => {
  if (isDesktop.value && window.desktopAPI) {
    window.desktopAPI.onSyncStatus((data) => {
      status.value = data.status || 'offline'
      message.value = data.message || ''
    })
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
.sync-syncing .fa-cloud-arrow-up { animation: pulse 1.5s infinite; }
.sync-synced .sync-indicator { background: rgba(5,150,105,.12); color: #059669; }
.sync-error .sync-indicator { background: rgba(220,38,38,.12); color: #dc2626; }
.sync-label { white-space: nowrap; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
</style>
