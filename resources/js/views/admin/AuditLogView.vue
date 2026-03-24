<template>
  <div>
    <!-- Header -->
    <div class="audit-header">
      <div>
        <h4 class="mb-1"><i class="fas fa-shield-alt me-2"></i>Audit & Modifications</h4>
        <p class="text-muted mb-0">Journal de toutes les modifications du systeme</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Recherche</label>
            <input v-model="filters.search" type="text" class="form-control form-control-sm" placeholder="Nom, table, IP..." @input="debouncedFetch">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Table</label>
            <select v-model="filters.table_name" class="form-select form-select-sm" @change="fetchLogs">
              <option value="">Toutes</option>
              <option v-for="t in tables" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Action</label>
            <select v-model="filters.action" class="form-select form-select-sm" @change="fetchLogs">
              <option value="">Toutes</option>
              <option value="CREATE">Cr&eacute;ation</option>
              <option value="UPDATE">Modification</option>
              <option value="DELETE">Suppression</option>
              <option value="REVERT">Restauration</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Utilisateur</label>
            <select v-model="filters.user_id" class="form-select form-select-sm" @change="fetchLogs">
              <option value="">Tous</option>
              <option v-for="u in users" :key="u.user_id" :value="u.user_id">{{ u.user_name }}</option>
            </select>
          </div>
          <div class="col-md-1">
            <label class="form-label small">De</label>
            <input v-model="filters.date_from" type="date" class="form-control form-control-sm" @change="fetchLogs">
          </div>
          <div class="col-md-1">
            <label class="form-label small">A</label>
            <input v-model="filters.date_to" type="date" class="form-control form-control-sm" @change="fetchLogs">
          </div>
          <div class="col-md-1">
            <button class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters">
              <i class="fas fa-undo"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
    </div>

    <!-- Table -->
    <div v-else class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Date / Heure</th>
              <th>Utilisateur</th>
              <th>Action</th>
              <th>Table</th>
              <th>ID</th>
              <th>Adresse IP</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="text-nowrap small">{{ formatDate(log.created_at) }}</td>
              <td>{{ log.user_name }}</td>
              <td>
                <span class="badge" :class="actionBadge(log.action)">{{ actionLabel(log.action) }}</span>
              </td>
              <td><code>{{ log.table_name }}</code></td>
              <td class="text-muted">#{{ log.record_id }}</td>
              <td class="small text-muted">{{ log.ip_address }}</td>
              <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-primary me-1" @click="toggleDetail(log.id)" title="Voir le detail">
                  <i class="fas" :class="expandedId === log.id ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <button
                  v-if="log.action !== 'REVERT'"
                  class="btn btn-sm btn-outline-warning"
                  @click="confirmRevert(log)"
                  :disabled="reverting === log.id"
                  title="Restaurer"
                >
                  <i class="fas fa-undo"></i>
                </button>
              </td>
            </tr>
            <tr v-if="!logs.length">
              <td colspan="7" class="text-center text-muted py-4">Aucun enregistrement</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Expanded detail -->
      <div v-if="expandedId && expandedLog" class="border-top p-3 bg-light">
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-danger"><i class="fas fa-minus-circle me-1"></i>Avant</h6>
            <pre class="bg-white p-2 rounded border small" style="max-height:300px;overflow:auto;">{{ formatJson(expandedLog.donnees_avant) }}</pre>
          </div>
          <div class="col-md-6">
            <h6 class="text-success"><i class="fas fa-plus-circle me-1"></i>Apres</h6>
            <pre class="bg-white p-2 rounded border small" style="max-height:300px;overflow:auto;">{{ formatJson(expandedLog.donnees_apres) }}</pre>
          </div>
        </div>
        <div class="mt-2 small text-muted">
          <strong>User-Agent:</strong> {{ expandedLog.user_agent || '-' }}
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ pagination.total }} resultats</small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
              <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">&laquo;</a>
            </li>
            <li v-for="p in visiblePages" :key="p" class="page-item" :class="{ active: p === pagination.current_page }">
              <a class="page-link" href="#" @click.prevent="goToPage(p)">{{ p }}</a>
            </li>
            <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
              <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">&raquo;</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Revert confirmation modal -->
    <div v-if="revertTarget" class="modal-backdrop-custom" @click.self="revertTarget = null">
      <div class="modal-box">
        <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirmer la restauration</h5>
        <p>Voulez-vous restaurer cette modification ?</p>
        <p class="small text-muted">
          <strong>Action:</strong> {{ actionLabel(revertTarget.action) }}<br>
          <strong>Table:</strong> {{ revertTarget.table_name }} #{{ revertTarget.record_id }}<br>
          <strong>Par:</strong> {{ revertTarget.user_name }} le {{ formatDate(revertTarget.created_at) }}
        </p>
        <div class="d-flex gap-2 justify-content-end">
          <button class="btn btn-sm btn-secondary" @click="revertTarget = null">Annuler</button>
          <button class="btn btn-sm btn-warning" @click="doRevert" :disabled="reverting">
            <span v-if="reverting" class="spinner-border spinner-border-sm me-1"></span>
            Restaurer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'

const logs = ref([])
const loading = ref(true)
const tables = ref([])
const users = ref([])
const expandedId = ref(null)
const revertTarget = ref(null)
const reverting = ref(false)
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({
    search: '', table_name: '', action: '', user_id: '', date_from: '', date_to: ''
})

let debounceTimer = null

const expandedLog = computed(() => logs.value.find(l => l.id === expandedId.value))

const visiblePages = computed(() => {
    const pages = []
    const cp = pagination.value.current_page
    const lp = pagination.value.last_page
    const start = Math.max(1, cp - 2)
    const end = Math.min(lp, cp + 2)
    for (let i = start; i <= end; i++) pages.push(i)
    return pages
})

function actionBadge(action) {
    return {
        'bg-success': action === 'CREATE',
        'bg-primary': action === 'UPDATE',
        'bg-danger': action === 'DELETE',
        'bg-warning text-dark': action === 'REVERT',
    }
}

function actionLabel(action) {
    return { CREATE: 'Creation', UPDATE: 'Modification', DELETE: 'Suppression', REVERT: 'Restauration' }[action] || action
}

function formatDate(d) {
    if (!d) return '-'
    const dt = new Date(d)
    return dt.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
        + ' ' + dt.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function formatJson(data) {
    if (!data) return '(vide)'
    return JSON.stringify(data, null, 2)
}

function toggleDetail(id) {
    expandedId.value = expandedId.value === id ? null : id
}

function debouncedFetch() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(fetchLogs, 400)
}

async function fetchLogs(page = 1) {
    loading.value = true
    try {
        const params = { page, per_page: 25 }
        if (filters.value.search) params.search = filters.value.search
        if (filters.value.table_name) params.table_name = filters.value.table_name
        if (filters.value.action) params.action = filters.value.action
        if (filters.value.user_id) params.user_id = filters.value.user_id
        if (filters.value.date_from) params.date_from = filters.value.date_from
        if (filters.value.date_to) params.date_to = filters.value.date_to

        const { data } = await client.get('/superadmin/audit-logs', { params })
        logs.value = data.data
        pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
    } catch (e) {
        console.error('Failed to load audit logs', e)
    } finally {
        loading.value = false
    }
}

function goToPage(p) {
    if (p < 1 || p > pagination.value.last_page) return
    fetchLogs(p)
}

function resetFilters() {
    filters.value = { search: '', table_name: '', action: '', user_id: '', date_from: '', date_to: '' }
    fetchLogs()
}

function confirmRevert(log) {
    revertTarget.value = log
}

async function doRevert() {
    if (!revertTarget.value) return
    reverting.value = true
    try {
        await client.post(`/superadmin/audit-logs/${revertTarget.value.id}/revert`)
        revertTarget.value = null
        fetchLogs(pagination.value.current_page)
    } catch (e) {
        alert(e.response?.data?.message || 'Erreur lors de la restauration.')
    } finally {
        reverting.value = false
    }
}

async function fetchFiltersData() {
    try {
        const [tablesRes, usersRes] = await Promise.all([
            client.get('/superadmin/audit-logs/tables'),
            client.get('/superadmin/audit-logs/users'),
        ])
        tables.value = tablesRes.data
        users.value = usersRes.data
    } catch { /* ignore */ }
}

onMounted(() => {
    fetchLogs()
    fetchFiltersData()
})
</script>

<style scoped>
.audit-header {
    background: linear-gradient(135deg, #4f46e5, #3730a3);
    color: #fff;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}
.audit-header .text-muted { color: rgba(255,255,255,.7) !important; }

.modal-backdrop-custom {
    position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
}
.modal-box {
    background: #fff; border-radius: 12px; padding: 1.5rem; max-width: 480px; width: 90%;
    box-shadow: 0 10px 40px rgba(0,0,0,.2);
}
</style>
