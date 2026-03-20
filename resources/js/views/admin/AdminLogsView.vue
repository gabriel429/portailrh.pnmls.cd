<template>
  <div class="logs-page">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-file-alt"></i>
        </div>
        <div>
          <h4 class="text-white mb-0 fw-bold">Logs Systeme</h4>
          <small class="text-white-50">Consultation des logs applicatifs</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="count-badge" v-if="lines.length > 0">
          {{ lines.length }} ligne{{ lines.length > 1 ? 's' : '' }}
        </span>
        <button class="hero-btn" @click="fetchLogs" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm"></span>
          <i v-else class="fas fa-sync-alt"></i>
          Actualiser
        </button>
        <button class="hero-btn hero-btn-danger" @click="clearLogs" :disabled="clearing">
          <span v-if="clearing" class="spinner-border spinner-border-sm"></span>
          <i v-else class="fas fa-trash"></i>
          Vider
        </button>
      </div>
    </div>

    <!-- File Info -->
    <div v-if="logFile" class="file-info-bar">
      <div class="d-flex align-items-center gap-3">
        <div class="file-info-item">
          <i class="fas fa-folder-open"></i>
          <span>{{ logFile }}</span>
        </div>
        <div class="file-info-item">
          <i class="fas fa-list-ol"></i>
          <span>{{ lines.length }} lignes</span>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && lines.length === 0" class="text-center py-5">
      <div class="spinner-border" style="color: #64748b;" role="status"></div>
      <p class="text-muted mt-2 mb-0" style="font-size: .88rem;">Chargement des logs...</p>
    </div>

    <!-- Log Content -->
    <div v-else-if="lines.length > 0" class="log-card">
      <div ref="logContainer" class="log-container" @scroll="handleScroll">
        <pre class="log-content mb-0"><template v-for="(line, i) in lines" :key="i"><span class="log-line" :class="getLineClass(line)"><span class="line-number">{{ i + 1 }}</span>{{ line }}</span>
</template></pre>
      </div>
      <div class="log-footer">
        <small class="text-muted">{{ lines.length }} lignes affichees</small>
        <button class="scroll-btn" @click="scrollToBottom">
          <i class="fas fa-arrow-down me-1"></i>Aller en bas
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <i class="fas fa-check-circle"></i>
      <p class="empty-title">Aucun log</p>
      <p>Le fichier de log est vide.</p>
    </div>

    <!-- Confirm Clear Modal -->
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
.logs-page {
  padding: 1.5rem 0;
}

.page-hero {
  background: linear-gradient(135deg, #64748b 0%, #475569 50%, #334155 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(100, 116, 139, .25);
  margin-bottom: 1.5rem;
}

.page-hero-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, .15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #fff;
}

.count-badge {
  background: rgba(255, 255, 255, .2);
  color: #fff;
  font-size: .78rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
}

.hero-btn {
  background: rgba(255, 255, 255, .2);
  border: 1px solid rgba(255, 255, 255, .3);
  color: #fff;
  padding: .5rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: .85rem;
  text-decoration: none;
  transition: all .2s;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  cursor: pointer;
}

.hero-btn:hover {
  background: rgba(255, 255, 255, .35);
  color: #fff;
}

.hero-btn:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.hero-btn-danger {
  background: rgba(239, 68, 68, .3);
  border-color: rgba(239, 68, 68, .4);
}

.hero-btn-danger:hover {
  background: rgba(239, 68, 68, .5);
}

/* File Info Bar */
.file-info-bar {
  background: #fff;
  border-radius: 14px;
  padding: .75rem 1.25rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
  border: 1px solid #f1f5f9;
  margin-bottom: 1.25rem;
}

.file-info-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .82rem;
  color: #64748b;
}

.file-info-item i {
  color: #94a3b8;
  font-size: .78rem;
}

/* Log Card */
.log-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.log-container {
  max-height: 600px;
  overflow-y: auto;
  background: #1e1e2e;
  border-radius: 0;
}

.log-content {
  font-family: 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
  font-size: .78rem;
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

.line-number {
  display: inline-block;
  width: 45px;
  text-align: right;
  margin-right: 12px;
  color: #585b70;
  user-select: none;
  font-size: .72rem;
}

.log-error {
  color: #f38ba8;
  background: rgba(243, 139, 168, .06);
}

.log-warning {
  color: #fab387;
  background: rgba(250, 179, 135, .06);
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

.log-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: .75rem 1.25rem;
  border-top: 1px solid #f1f5f9;
  background: #fff;
}

.scroll-btn {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: .35rem .85rem;
  font-size: .8rem;
  color: #64748b;
  cursor: pointer;
  transition: all .2s;
}

.scroll-btn:hover {
  background: #f1f5f9;
  color: #475569;
  border-color: #cbd5e1;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  border: 2px dashed #e2e8f0;
  border-radius: 14px;
  background: #fff;
}

.empty-state i {
  font-size: 2.5rem;
  color: #22c55e;
  margin-bottom: .75rem;
  display: block;
}

.empty-title {
  color: #475569;
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: .25rem;
}

.empty-state p {
  color: #94a3b8;
  margin: 0;
  font-weight: 500;
}

/* ── Mobile Responsive ── */
@media (max-width: 767.98px) {
  .page-hero {
    padding: 1.25rem 1rem;
    border-radius: 12px;
  }
  .page-hero h4 {
    font-size: 1.1rem;
  }
  .page-hero small {
    font-size: .78rem;
  }
  .page-hero-icon {
    width: 42px;
    height: 42px;
    font-size: 1.1rem;
    border-radius: 10px;
  }
  .hero-btn {
    padding: .4rem .9rem;
    font-size: .78rem;
  }
  .file-info-bar {
    border-radius: 10px;
    padding: .6rem 1rem;
  }
  .file-info-item {
    font-size: .75rem;
  }
  .log-card {
    border-radius: 10px;
  }
  .log-container {
    max-height: 400px;
  }
  .log-content {
    font-size: .7rem;
    padding: .75rem .5rem;
  }
  .line-number {
    width: 32px;
    margin-right: 8px;
    font-size: .65rem;
  }
  .log-footer {
    padding: .6rem 1rem;
  }
  .scroll-btn {
    font-size: .75rem;
    padding: .3rem .7rem;
  }
  .empty-state {
    padding: 2rem 1rem;
    border-radius: 10px;
  }
  .empty-state i {
    font-size: 2rem;
  }
  .empty-title {
    font-size: 1rem;
  }
}
</style>
