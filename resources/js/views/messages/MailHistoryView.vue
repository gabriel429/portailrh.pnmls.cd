<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="mailhist-hero">
      <h2><i class="fas fa-paper-plane me-2"></i>Historique des emails</h2>
      <p>Emails envoyes depuis le portail</p>
      <div class="mailhist-hero-stats">
        <div>
          <div class="mailhist-stat-val">{{ meta.total }}</div>
          <div class="mailhist-stat-lbl">Total</div>
        </div>
      </div>
    </div>

    <!-- Recherche -->
    <div class="mailhist-toolbar">
      <div class="mailhist-search-wrap">
        <i class="fas fa-search mailhist-search-icon"></i>
        <input
          v-model="search"
          type="text"
          class="mailhist-search"
          placeholder="Rechercher par sujet, destinataire..."
          @input="onSearch"
        />
        <button v-if="search" class="mailhist-search-clear" @click="clearSearch">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-2 text-muted small">Chargement...</div>
    </div>

    <!-- Liste -->
    <template v-else-if="items.length">
      <div class="mailhist-list">
        <div
          v-for="item in items"
          :key="item.id"
          class="mailhist-card"
          :class="{ 'has-reply': item.response_received_at }"
          @click="toggleExpand(item.id)"
        >
          <div class="mailhist-card-icon">
            <i class="fas fa-envelope-open-text"></i>
          </div>
          <div class="mailhist-card-body">
            <div class="mailhist-card-top">
              <span class="mailhist-subject">{{ item.subject }}</span>
              <span class="mailhist-date">{{ formatDate(item.sent_at) }}</span>
            </div>
            <div class="mailhist-card-to">
              <i class="fas fa-user me-1"></i>
              <span>{{ item.recipient_name }}</span>
              <span class="mailhist-email">&lt;{{ item.recipient_email }}&gt;</span>
            </div>
            <div v-if="item.attachment_name" class="mailhist-attach">
              <i class="fas fa-paperclip me-1"></i>{{ item.attachment_name }}
            </div>
            <!-- Badge reponse -->
            <div v-if="item.response_received_at" class="mailhist-reply-badge">
              <i class="fas fa-reply me-1"></i>
              Reponse reçue le {{ formatDate(item.response_received_at) }}
            </div>
            <!-- Contenu expandé -->
            <div v-if="expanded === item.id" class="mailhist-expand">
              <hr class="mailhist-sep">
              <div class="mailhist-expand-label">Message envoye :</div>
              <div class="mailhist-expand-body">{{ item.body }}</div>
              <template v-if="item.response_body_preview">
                <hr class="mailhist-sep">
                <div class="mailhist-expand-label">
                  <i class="fas fa-reply me-1"></i>Reponse de {{ item.response_from_email }} :
                </div>
                <div class="mailhist-expand-body mailhist-expand-reply">{{ item.response_body_preview }}</div>
              </template>
            </div>
          </div>
          <div class="mailhist-card-chevron">
            <i class="fas" :class="expanded === item.id ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="d-flex justify-content-center mt-4">
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
              <button class="page-link" @click="loadPage(meta.current_page - 1)">&laquo;</button>
            </li>
            <li
              v-for="p in paginationPages"
              :key="p"
              class="page-item"
              :class="{ active: p === meta.current_page }"
            >
              <button class="page-link" @click="loadPage(p)">{{ p }}</button>
            </li>
            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
              <button class="page-link" @click="loadPage(meta.current_page + 1)">&raquo;</button>
            </li>
          </ul>
        </nav>
      </div>
    </template>

    <!-- Vide -->
    <div v-else class="mailhist-empty">
      <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
      <p>Aucun email envoye{{ search ? ' pour cette recherche' : '' }}.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'

const items = ref([])
const meta = ref({ total: 0, current_page: 1, last_page: 1, per_page: 20 })
const loading = ref(false)
const search = ref('')
const expanded = ref(null)
let searchTimer = null

async function fetchHistory(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (search.value) params.search = search.value
    const { data } = await client.get('/mail-history', { params })
    items.value = data.data
    meta.value = data.meta
    expanded.value = null
  } catch (e) {
    items.value = []
  } finally {
    loading.value = false
  }
}

function loadPage(page) {
  if (page < 1 || page > meta.value.last_page) return
  fetchHistory(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function onSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchHistory(1), 400)
}

function clearSearch() {
  search.value = ''
  fetchHistory(1)
}

function toggleExpand(id) {
  expanded.value = expanded.value === id ? null : id
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

const paginationPages = computed(() => {
  const { current_page, last_page } = meta.value
  const range = []
  const delta = 2
  for (let i = Math.max(1, current_page - delta); i <= Math.min(last_page, current_page + delta); i++) {
    range.push(i)
  }
  return range
})

onMounted(() => fetchHistory())
</script>

<style scoped>
.mailhist-hero {
  background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
  color: #fff;
  border-radius: 16px;
  padding: 28px 32px;
  margin-bottom: 24px;
}
.mailhist-hero h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: 4px; }
.mailhist-hero p { opacity: .8; margin-bottom: 16px; }
.mailhist-hero-stats { display: flex; gap: 32px; }
.mailhist-stat-val { font-size: 2rem; font-weight: 800; }
.mailhist-stat-lbl { font-size: .8rem; opacity: .8; }

.mailhist-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}
.mailhist-search-wrap {
  position: relative;
  flex: 1;
  max-width: 420px;
}
.mailhist-search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}
.mailhist-search {
  width: 100%;
  padding: 8px 36px 8px 36px;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  font-size: .9rem;
  background: var(--card-bg, #fff);
  color: var(--text-primary, #111827);
}
.mailhist-search:focus { outline: none; border-color: #0ea5e9; }
.mailhist-search-clear {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  font-size: .85rem;
}

.mailhist-list { display: flex; flex-direction: column; gap: 10px; }

.mailhist-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  background: var(--card-bg, #fff);
  border: 1.5px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: box-shadow .15s, border-color .15s;
}
.mailhist-card:hover { box-shadow: 0 2px 12px rgba(14,165,233,.1); border-color: #0ea5e9; }
.mailhist-card.has-reply { border-left: 4px solid #0ea5e9; }

.mailhist-card-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #e0f2fe;
  color: #0ea5e9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.mailhist-card-body { flex: 1; min-width: 0; }
.mailhist-card-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
.mailhist-subject { font-weight: 600; font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.mailhist-date { font-size: .78rem; color: #9ca3af; white-space: nowrap; flex-shrink: 0; }
.mailhist-card-to { font-size: .83rem; color: #6b7280; margin-bottom: 2px; }
.mailhist-email { color: #9ca3af; margin-left: 4px; }
.mailhist-attach { font-size: .78rem; color: #6b7280; margin-top: 2px; }

.mailhist-reply-badge {
  display: inline-flex;
  align-items: center;
  margin-top: 6px;
  padding: 3px 10px;
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 999px;
  font-size: .78rem;
  font-weight: 600;
}

.mailhist-expand { margin-top: 10px; }
.mailhist-sep { margin: 10px 0; border-color: var(--border-color, #e5e7eb); }
.mailhist-expand-label { font-size: .8rem; font-weight: 600; color: #6b7280; margin-bottom: 6px; }
.mailhist-expand-body {
  font-size: .87rem;
  background: var(--bg-subtle, #f9fafb);
  border-radius: 8px;
  padding: 10px 14px;
  white-space: pre-wrap;
  max-height: 180px;
  overflow-y: auto;
}
.mailhist-expand-reply { background: #e0f2fe; color: #0c4a6e; }

.mailhist-card-chevron { color: #9ca3af; font-size: .85rem; flex-shrink: 0; padding-top: 2px; }

.mailhist-empty {
  text-align: center;
  padding: 60px 0;
  color: #9ca3af;
}
</style>
