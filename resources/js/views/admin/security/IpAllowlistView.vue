<template>
  <div class="ip-allowlist">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-shield-halved"></i>
        </div>
        <div>
          <h4 class="mb-1 fw-bold text-white">Liste blanche IP</h4>
          <p class="mb-0" style="color:rgba(255,255,255,.65);font-size:.88rem;">
            Exceptions à la restriction anti-VPN
          </p>
        </div>
      </div>
    </div>

    <div class="info-banner">
      <i class="fas fa-circle-info me-2"></i>
      Les connexions depuis une adresse IP identifiée comme VPN/datacenter sont refusées à la connexion.
      Les adresses ci-dessous contournent cette restriction.
    </div>

    <!-- Add form -->
    <form class="add-card" @submit.prevent="addEntry">
      <div class="add-fields">
        <input
          v-model.trim="form.ip_address"
          type="text"
          class="add-input"
          placeholder="Adresse IP (ex. 41.243.12.7)"
          required
        />
        <input
          v-model.trim="form.label"
          type="text"
          class="add-input"
          placeholder="Libellé (ex. Bureau RH National)"
        />
        <button type="submit" class="add-btn" :disabled="submitting">
          <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="fas fa-plus me-1"></i>Ajouter
        </button>
      </div>
      <p v-if="formError" class="add-error">{{ formError }}</p>
    </form>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-grow" style="width:3rem;height:3rem;color:#0077B5;"></div>
      <p class="mt-3 text-muted fw-semibold">Chargement...</p>
    </div>

    <template v-else>
      <div v-if="entries.length" class="data-card">
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Adresse IP</th>
                <th>Libellé</th>
                <th>Ajoutée par</th>
                <th>Date</th>
                <th class="text-end pe-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in entries" :key="entry.id">
                <td><span class="code-badge">{{ entry.ip_address }}</span></td>
                <td>{{ entry.label || '-' }}</td>
                <td class="text-muted">{{ entry.creator?.name || '-' }}</td>
                <td class="text-muted">{{ formatDate(entry.created_at) }}</td>
                <td class="text-end pe-3">
                  <button class="action-btn action-btn-danger" title="Retirer" @click="askDelete(entry)">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="empty-state">
        <i class="fas fa-shield-halved"></i>
        <p>Aucune adresse IP en liste blanche.</p>
      </div>
    </template>

    <ConfirmModal
      :show="showConfirmDelete"
      title="Confirmer le retrait"
      :message="`Retirer l'adresse ${pendingDeleteEntry?.ip_address} de la liste blanche ?`"
      :loading="confirmLoading"
      @confirm="confirmDelete"
      @cancel="showConfirmDelete = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const ui = useUiStore()
const loading = ref(true)
const entries = ref([])
const submitting = ref(false)
const formError = ref('')
const form = ref({ ip_address: '', label: '' })

const showConfirmDelete = ref(false)
const confirmLoading = ref(false)
const pendingDeleteEntry = ref(null)

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

async function fetchEntries() {
  loading.value = true
  try {
    const { data } = await client.get('/superadmin/ip-allowlist')
    entries.value = data || []
  } catch (e) {
    ui.addToast('Erreur lors du chargement de la liste blanche.', 'danger')
  } finally {
    loading.value = false
  }
}

async function addEntry() {
  formError.value = ''
  submitting.value = true
  try {
    await client.post('/superadmin/ip-allowlist', form.value)
    form.value = { ip_address: '', label: '' }
    ui.addToast('Adresse IP ajoutée à la liste blanche.', 'success')
    fetchEntries()
  } catch (e) {
    formError.value = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {}).flat()[0]
      || 'Erreur lors de l\'ajout.'
  } finally {
    submitting.value = false
  }
}

function askDelete(entry) {
  pendingDeleteEntry.value = entry
  showConfirmDelete.value = true
}

async function confirmDelete() {
  if (!pendingDeleteEntry.value) return
  confirmLoading.value = true
  try {
    await client.delete(`/superadmin/ip-allowlist/${pendingDeleteEntry.value.id}`)
    showConfirmDelete.value = false
    fetchEntries()
  } catch (e) {
    ui.addToast('Erreur lors du retrait de l\'adresse IP.', 'danger')
  } finally {
    confirmLoading.value = false
  }
}

onMounted(fetchEntries)
</script>

<style scoped>
.page-hero {
  background: linear-gradient(135deg, #0f172a 0%, #0c4a6e 55%, #0077B5 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(0, 119, 181, .25);
  margin-bottom: 1.25rem;
}
.page-hero-content { display: flex; align-items: center; gap: 1rem; }
.page-hero-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; color: #fff; backdrop-filter: blur(4px);
}

.info-banner {
  background: #e0f2fe;
  color: #005a87;
  border: 1px solid #bae6fd;
  border-radius: 12px;
  padding: .85rem 1.1rem;
  font-size: .85rem;
  margin-bottom: 1.25rem;
}

.add-card {
  background: #fff;
  border-radius: 14px;
  padding: 1rem 1.25rem;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #f1f5f9;
  margin-bottom: 1.25rem;
}
.add-fields { display: flex; gap: .6rem; flex-wrap: wrap; }
.add-input {
  flex: 1;
  min-width: 200px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: .5rem .85rem;
  font-size: .88rem;
}
.add-input:focus {
  border-color: #0077B5;
  outline: none;
  box-shadow: 0 0 0 3px rgba(0, 119, 181, .1);
}
.add-btn {
  background: #0077B5;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: .5rem 1.25rem;
  font-weight: 600;
  font-size: .85rem;
  white-space: nowrap;
  transition: background .2s;
}
.add-btn:hover:not(:disabled) { background: #005a87; }
.add-btn:disabled { opacity: .7; cursor: not-allowed; }
.add-error { color: #dc2626; font-size: .8rem; margin: .6rem 0 0; }

.data-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}
.data-table { margin-bottom: 0; }
.data-table thead th {
  background: #f8fafc;
  border: none;
  font-size: .78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #64748b;
  padding: .85rem 1rem;
}
.data-table tbody td {
  padding: .75rem 1rem;
  border-color: #f1f5f9;
  vertical-align: middle;
  font-size: .88rem;
}
.data-table tbody tr:hover { background: #f8fafc; }

.code-badge {
  background: #e2e8f0;
  color: #475569;
  font-size: .75rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
  font-family: monospace;
}

.action-btn {
  width: 32px; height: 32px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-size: .8rem;
  transition: all .2s;
  cursor: pointer;
}
.action-btn-danger:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  background: #fff;
  border: 2px dashed #e2e8f0;
  border-radius: 14px;
}
.empty-state i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: .75rem; display: block; }
.empty-state p { color: #94a3b8; margin: 0; font-weight: 500; }

@media (max-width: 767.98px) {
  .page-hero { padding: 1.2rem; border-radius: 12px; }
  .page-hero-icon { width: 42px; height: 42px; font-size: 1.1rem; }
  .add-fields { flex-direction: column; }
  .add-input { min-width: 0; }
}
</style>
