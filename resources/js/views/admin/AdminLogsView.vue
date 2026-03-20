<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="fas fa-file-alt me-2"></i>Journaux Systeme</h4>
        <p class="text-muted mb-0 small">Derniers 300 lignes du fichier <code>laravel.log</code></p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" @click="fetchLogs" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="fas fa-sync-alt me-1"></i>Actualiser
        </button>
        <button class="btn btn-outline-danger btn-sm" @click="clearLogs" :disabled="clearing">
          <span v-if="clearing" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="fas fa-trash me-1"></i>Vider les logs
        </button>
      </div>
    </div>

    <!-- File info -->
    <div v-if="logFile" class="alert alert-light py-2 mb-3 small">
      <i class="fas fa-folder-open me-1"></i> <strong>Fichier :</strong> {{ logFile }}
      <span class="ms-3"><strong>Lignes :</strong> {{ lines.length }}</span>
    </div>

    <!-- Loading state -->
    <div v-if="loading && lines.length === 0" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">Chargement des logs...</p>
    </div>

    <!-- Log content -->
    <div v-else-if="lines.length > 0" class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div ref="logContainer" class="log-container" @scroll="handleScroll">
          <pre class="log-content mb-0"><template v-for="(line, i) in lines" :key="i"><span class="log-line" :class="getLineClass(line)">{{ line }}</span>
</template></pre>
        </div>
      </div>
      <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ lines.length }} lignes affichees</small>
        <button class="btn btn-sm btn-outline-secondary" @click="scrollToBottom">
          <i class="fas fa-arrow-down me-1"></i>Aller en bas
        </button>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="card border-0 shadow-sm">
      <div class="card-body text-center py-5">
        <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
        <h5 class="text-muted">Aucun log</h5>
        <p class="text-muted">Le fichier de log est vide.</p>
      </div>
    </div>

    <!-- Confirm clear modal -->
    <ConfirmModal
      :show="showClearModal"
      title="Vider les logs"
      message="Etes-vous sur de vouloir vider le fichier de log ? Cette action est irreversible."
      :loading="clearing"
      @confirm="doClear"
      @cancel="showClearModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const ui = useUiStore()

const loading = ref(false)
const clearing = ref(false)
const lines = ref([])
const logFile = ref('')
const logContainer = ref(null)
const showClearModal = ref(false)
const autoScroll = ref(true)

function getLineClass(line) {
  if (!line) return ''
  if (line.includes('.ERROR') || line.includes('Error') || line.includes('CRITICAL')) return 'log-error'
  if (line.includes('.WARNING') || line.includes('Warning')) return 'log-warning'
  if (line.includes('.INFO')) return 'log-info'
  if (line.includes('.DEBUG')) return 'log-debug'
  if (line.startsWith('#') || line.includes('Stack trace')) return 'log-trace'
  return ''
}

function handleScroll() {
  if (!logContainer.value) return
  const el = logContainer.value
  autoScroll.value = el.scrollTop + el.clientHeight >= el.scrollHeight - 50
}

function scrollToBottom() {
  if (!logContainer.value) return
  logContainer.value.scrollTop = logContainer.value.scrollHeight
}

async function fetchLogs() {
  loading.value = true
  try {
    const { data } = await client.get('/admin/logs')
    lines.value = data.lines || []
    logFile.value = data.file || ''
    if (autoScroll.value) {
      await nextTick()
      scrollToBottom()
    }
  } catch (err) {
    console.error('Error fetching logs:', err)
    ui.addToast('Erreur lors du chargement des logs', 'danger')
  } finally {
    loading.value = false
  }
}

function clearLogs() {
  showClearModal.value = true
}

async function doClear() {
  clearing.value = true
  try {
    await client.post('/admin/logs/clear')
    lines.value = []
    showClearModal.value = false
    ui.addToast('Logs effaces avec succes', 'success')
  } catch (err) {
    console.error('Error clearing logs:', err)
    ui.addToast('Erreur lors de la suppression des logs', 'danger')
  } finally {
    clearing.value = false
  }
}

onMounted(() => {
  fetchLogs()
})
</script>

<style scoped>
.log-container {
  max-height: 600px;
  overflow-y: auto;
  background: #1e1e2e;
  border-radius: 0;
}
.log-content {
  font-family: 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
  font-size: 0.78rem;
  line-height: 1.5;
  padding: 1rem;
  color: #cdd6f4;
  white-space: pre-wrap;
  word-break: break-all;
}
.log-line {
  display: block;
  padding: 1px 4px;
  border-radius: 2px;
}
.log-line:hover {
  background: rgba(255, 255, 255, 0.05);
}
.log-error {
  color: #f38ba8;
}
.log-warning {
  color: #fab387;
}
.log-info {
  color: #89b4fa;
}
.log-debug {
  color: #a6adc8;
}
.log-trace {
  color: #6c7086;
  font-style: italic;
}
</style>
