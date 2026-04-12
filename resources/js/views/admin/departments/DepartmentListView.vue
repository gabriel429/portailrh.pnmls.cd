<template>
  <div class="dept-list">

    <!-- ═══ HERO ═══ -->
    <div class="dl-hero">
      <div class="dl-hero-left">
        <div class="dl-hero-icon"><i class="fas fa-building"></i></div>
        <div>
          <h4 class="dl-hero-title">Départements</h4>
          <p class="dl-hero-sub">{{ total }} département{{ total > 1 ? 's' : '' }} enregistré{{ total > 1 ? 's' : '' }}</p>
        </div>
      </div>
      <router-link to="/admin/departments/create" class="dl-hero-btn">
        <i class="fas fa-plus"></i><span>Nouveau</span>
      </router-link>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="dl-stats">
      <div class="dl-stat">
        <span class="dl-stat-val">{{ total }}</span>
        <span class="dl-stat-label">Total</span>
      </div>
      <div class="dl-stat dl-stat-green">
        <span class="dl-stat-val">{{ countActifs }}</span>
        <span class="dl-stat-label">Actifs système</span>
      </div>
      <div class="dl-stat dl-stat-gray">
        <span class="dl-stat-val">{{ countHistoriques }}</span>
        <span class="dl-stat-label">Historisation</span>
      </div>
    </div>

    <!-- ═══ TOOLBAR ═══ -->
    <div class="dl-toolbar">
      <!-- Tabs filtre -->
      <div class="dl-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="dl-tab"
          :class="{ active: activeTab === tab.key }"
          @click="setTab(tab.key)"
        >
          {{ tab.label }}
        </button>
      </div>
      <!-- Search -->
      <div class="dl-search-wrap">
        <i class="fas fa-search dl-search-icon"></i>
        <input
          v-model="search"
          type="text"
          class="dl-search"
          placeholder="Rechercher..."
          @input="debouncedFetch"
        />
      </div>
    </div>

    <!-- ═══ LOADING ═══ -->
    <div v-if="loading" class="dl-loading">
      <div class="dl-spinner"></div>
      <p>Chargement...</p>
    </div>

    <template v-else>

      <!-- ═══ TABLE DESKTOP ═══ -->
      <div v-if="departments.length" class="dl-card dl-desktop-only">
        <table class="dl-table">
          <thead>
            <tr>
              <th style="width:90px">Code</th>
              <th>Département</th>
              <th>Directeur</th>
              <th class="text-center" style="width:80px">Agents</th>
              <th class="text-center" style="width:80px">Sections</th>
              <th class="text-center" style="width:110px">Système</th>
              <th class="text-end" style="width:90px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="dept in departments" :key="dept.id" :class="{ 'row-inactive': !dept.pris_en_charge }">
              <td><span class="dl-code">{{ dept.code }}</span></td>
              <td>
                <div class="dl-name-cell">
                  <div class="dl-avatar" :class="dept.pris_en_charge ? 'dl-avatar-active' : 'dl-avatar-inactive'">
                    <i class="fas fa-building"></i>
                  </div>
                  <span class="dl-dept-name">{{ dept.nom }}</span>
                </div>
              </td>
              <td>
                <div v-if="dept.directeur" class="dl-dir-cell">
                  <div class="dl-dir-avatar">{{ initials(dept.directeur) }}</div>
                  <span class="dl-dir-name">{{ dept.directeur.prenom }} {{ dept.directeur.nom }}</span>
                </div>
                <span v-else class="dl-no-dir">— non assigné</span>
              </td>
              <td class="text-center"><span class="dl-pill dl-pill-purple">{{ dept.agents_count ?? 0 }}</span></td>
              <td class="text-center"><span class="dl-pill dl-pill-blue">{{ dept.sections_count ?? 0 }}</span></td>
              <td class="text-center">
                <button
                  class="dl-switch"
                  :class="dept.pris_en_charge ? 'dl-switch-on' : 'dl-switch-off'"
                  :disabled="toggling === dept.id"
                  @click="togglePrisEnCharge(dept)"
                  :title="dept.pris_en_charge ? 'Désactiver du système' : 'Activer dans le système'"
                >
                  <span class="dl-switch-thumb" :class="{ 'dl-switch-thumb-on': dept.pris_en_charge }"></span>
                </button>
              </td>
              <td class="text-end">
                <div class="dl-actions">
                  <router-link :to="`/admin/departments/${dept.id}/edit`" class="dl-btn" title="Modifier">
                    <i class="fas fa-pen"></i>
                  </router-link>
                  <button class="dl-btn dl-btn-danger" title="Supprimer" @click="confirmDelete(dept)">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="dl-pagination">
          <small class="dl-page-info">Page {{ currentPage }} / {{ lastPage }}</small>
          <div class="dl-page-btns">
            <button class="dl-page-btn" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
              <i class="fas fa-chevron-left"></i>
            </button>
            <button
              v-for="p in pagesArray"
              :key="p"
              class="dl-page-btn"
              :class="{ 'dl-page-btn-active': p === currentPage }"
              @click="goToPage(p)"
            >{{ p }}</button>
            <button class="dl-page-btn" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- ═══ CARDS MOBILE ═══ -->
      <div class="dl-mobile-only">
        <div v-if="departments.length" class="dl-cards">
          <div v-for="dept in departments" :key="dept.id" class="dl-card-item" :class="{ 'dl-card-inactive': !dept.pris_en_charge }">
            <!-- Card header -->
            <div class="dl-card-header">
              <div class="dl-card-avatar" :class="dept.pris_en_charge ? 'dl-avatar-active' : 'dl-avatar-inactive'">
                <i class="fas fa-building"></i>
              </div>
              <div class="dl-card-info">
                <div class="dl-card-name">{{ dept.nom }}</div>
                <span class="dl-code">{{ dept.code }}</span>
              </div>
              <button
                class="dl-switch dl-switch-sm"
                :class="dept.pris_en_charge ? 'dl-switch-on' : 'dl-switch-off'"
                :disabled="toggling === dept.id"
                @click="togglePrisEnCharge(dept)"
              >
                <span class="dl-switch-thumb" :class="{ 'dl-switch-thumb-on': dept.pris_en_charge }"></span>
              </button>
            </div>

            <!-- Directeur -->
            <div class="dl-card-dir">
              <i class="fas fa-user-tie dl-dir-icon"></i>
              <span v-if="dept.directeur" class="dl-dir-name">{{ dept.directeur.prenom }} {{ dept.directeur.nom }}</span>
              <span v-else class="dl-no-dir">Directeur non assigné</span>
            </div>

            <!-- Stats row -->
            <div class="dl-card-stats">
              <div class="dl-card-stat">
                <i class="fas fa-users"></i>
                <span>{{ dept.agents_count ?? 0 }} agents</span>
              </div>
              <div class="dl-card-stat">
                <i class="fas fa-layer-group"></i>
                <span>{{ dept.sections_count ?? 0 }} sections</span>
              </div>
              <div class="dl-card-stat">
                <span :class="dept.pris_en_charge ? 'dl-badge-active' : 'dl-badge-histo'">
                  {{ dept.pris_en_charge ? 'Actif système' : 'Historisation' }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="dl-card-foot">
              <router-link :to="`/admin/departments/${dept.id}/edit`" class="dl-card-action">
                <i class="fas fa-pen me-1"></i>Modifier
              </router-link>
              <button class="dl-card-action dl-card-action-danger" @click="confirmDelete(dept)">
                <i class="fas fa-trash-alt me-1"></i>Supprimer
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination mobile -->
        <div v-if="lastPage > 1" class="dl-pagination">
          <small class="dl-page-info">Page {{ currentPage }} / {{ lastPage }}</small>
          <div class="dl-page-btns">
            <button class="dl-page-btn" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
              <i class="fas fa-chevron-left"></i>
            </button>
            <button class="dl-page-btn" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-if="!departments.length" class="dl-empty">
        <i class="fas fa-building"></i>
        <p>Aucun département trouvé</p>
      </div>

    </template>

    <!-- ═══ DELETE MODAL ═══ -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="dl-overlay" @click.self="showDeleteModal = false">
        <div class="dl-modal">
          <div class="dl-modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
          <h5 class="fw-bold mb-2">Confirmer la suppression</h5>
          <p class="text-muted mb-3" style="font-size:.9rem;">
            Supprimer le département <strong>{{ deleteTarget?.nom }}</strong> ?
            Cette action est irréversible.
          </p>
          <div class="d-flex gap-2 justify-content-center">
            <button class="dl-modal-btn dl-modal-cancel" @click="showDeleteModal = false">
              <i class="fas fa-times me-1"></i>Annuler
            </button>
            <button class="dl-modal-btn dl-modal-delete" @click="executeDelete" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-trash-alt me-1"></i>Supprimer
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'

const loading      = ref(true)
const departments  = ref([])
const search       = ref('')
const currentPage  = ref(1)
const lastPage     = ref(1)
const total        = ref(0)
const totalAll     = ref(0) // total sans filtre pour stats
const countActifs  = ref(0)
const countHistoriques = ref(0)
const activeTab    = ref('tous')
const toggling     = ref(null)
const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting     = ref(false)
let debounceTimer  = null

const tabs = [
  { key: 'tous',      label: 'Tous' },
  { key: 'actifs',    label: 'Actifs système' },
  { key: 'historique',label: 'Historisation' },
]

const pagesArray = computed(() => {
  const pages = []
  for (let i = 1; i <= lastPage.value; i++) pages.push(i)
  return pages
})

function initials(agent) {
  if (!agent) return '?'
  return ((agent.prenom?.[0] ?? '') + (agent.nom?.[0] ?? '')).toUpperCase() || '?'
}

async function fetchDepartments() {
  loading.value = true
  const params = { search: search.value, page: currentPage.value, per_page: 50 }
  if (activeTab.value === 'actifs')     params.pris_en_charge = true
  if (activeTab.value === 'historique') params.pris_en_charge = false
  try {
    const { data } = await client.get('/admin/departments', { params })
    departments.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value    = data.last_page    || 1
    total.value       = data.total        || 0
  } catch (e) {
    console.error('Erreur chargement departements:', e)
  } finally {
    loading.value = false
  }
}

async function fetchStats() {
  try {
    const [resAll, resActif] = await Promise.all([
      client.get('/admin/departments', { params: { per_page: 1 } }),
      client.get('/admin/departments', { params: { per_page: 1, pris_en_charge: true } }),
    ])
    totalAll.value        = resAll.data.total  || 0
    countActifs.value     = resActif.data.total || 0
    countHistoriques.value = totalAll.value - countActifs.value
  } catch {}
}

function setTab(key) {
  activeTab.value = key
  currentPage.value = 1
  fetchDepartments()
}

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { currentPage.value = 1; fetchDepartments() }, 400)
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchDepartments()
}

async function togglePrisEnCharge(dept) {
  toggling.value = dept.id
  try {
    const { data } = await client.patch(`/admin/departments/${dept.id}/toggle-pris-en-charge`)
    dept.pris_en_charge = data.pris_en_charge
    // Recalc stats
    countActifs.value     += data.pris_en_charge ? 1 : -1
    countHistoriques.value = totalAll.value - countActifs.value
  } catch (e) {
    console.error('Erreur toggle:', e)
    alert('Erreur lors de la mise à jour.')
  } finally {
    toggling.value = null
  }
}

function confirmDelete(dept) {
  deleteTarget.value  = dept
  showDeleteModal.value = true
}

async function executeDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await client.delete(`/admin/departments/${deleteTarget.value.id}`)
    showDeleteModal.value = false
    deleteTarget.value    = null
    fetchStats()
    fetchDepartments()
  } catch (e) {
    alert('Erreur lors de la suppression.')
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  fetchStats()
  fetchDepartments()
})
</script>

<style scoped>
/* ── Wrapper ───────────────────────────────────────────── */
.dept-list { padding-bottom: 2rem; }

/* ── Hero ───────────────────────────────────────────────── */
.dl-hero {
  background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
  border-radius: 16px;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 8px 28px rgba(124,58,237,.25);
}
.dl-hero-left { display: flex; align-items: center; gap: .85rem; }
.dl-hero-icon {
  width: 48px; height: 48px; border-radius: 13px;
  background: rgba(255,255,255,.18);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; color: #fff;
}
.dl-hero-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: #fff; }
.dl-hero-sub   { margin: 0; font-size: .8rem; color: rgba(255,255,255,.65); }
.dl-hero-btn {
  display: inline-flex; align-items: center; gap: .45rem;
  background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3);
  color: #fff; padding: .48rem 1.1rem; border-radius: 10px;
  font-weight: 600; font-size: .85rem; text-decoration: none; white-space: nowrap;
  transition: background .2s;
}
.dl-hero-btn:hover { background: rgba(255,255,255,.35); color: #fff; }

/* ── Stats ──────────────────────────────────────────────── */
.dl-stats {
  display: flex; gap: .75rem; margin-bottom: 1rem; flex-wrap: wrap;
}
.dl-stat {
  flex: 1; min-width: 90px;
  background: #fff; border-radius: 12px;
  padding: .75rem 1rem; text-align: center;
  border: 1px solid #f1f5f9;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.dl-stat-val   { display: block; font-size: 1.5rem; font-weight: 700; color: #1e293b; }
.dl-stat-label { display: block; font-size: .72rem; color: #64748b; font-weight: 600; margin-top: .1rem; }
.dl-stat-green .dl-stat-val { color: #059669; }
.dl-stat-gray  .dl-stat-val { color: #64748b; }

/* ── Toolbar ────────────────────────────────────────────── */
.dl-toolbar {
  display: flex; align-items: center; gap: .75rem;
  flex-wrap: wrap; margin-bottom: 1rem;
  background: #fff; border-radius: 13px;
  padding: .75rem 1rem;
  border: 1px solid #f1f5f9;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.dl-tabs { display: flex; gap: .35rem; }
.dl-tab {
  padding: .35rem .9rem; border-radius: 8px;
  border: 1px solid #e2e8f0; background: #f8fafc;
  color: #64748b; font-size: .8rem; font-weight: 600;
  cursor: pointer; transition: all .2s;
}
.dl-tab.active {
  background: #7c3aed; border-color: #7c3aed; color: #fff;
}
.dl-search-wrap { position: relative; flex: 1; min-width: 180px; }
.dl-search-icon {
  position: absolute; left: .75rem; top: 50%;
  transform: translateY(-50%); color: #94a3b8; font-size: .82rem;
}
.dl-search {
  width: 100%; padding: .45rem 1rem .45rem 2.2rem;
  border: 1px solid #e2e8f0; border-radius: 9px;
  font-size: .85rem; transition: border-color .2s;
}
.dl-search:focus { border-color: #7c3aed; outline: none; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }

/* ── Loading ────────────────────────────────────────────── */
.dl-loading { text-align: center; padding: 3rem 1rem; background: #fff; border-radius: 14px; }
.dl-spinner {
  width: 40px; height: 40px; border: 3px solid #f1f5f9;
  border-top-color: #7c3aed; border-radius: 50%;
  animation: spin .7s linear infinite; margin: 0 auto .75rem;
}
@keyframes spin { to { transform: rotate(360deg); } }
.dl-loading p { color: #94a3b8; font-weight: 500; margin: 0; }

/* ── Table (desktop) ────────────────────────────────────── */
.dl-card {
  background: #fff; border-radius: 14px;
  border: 1px solid #f1f5f9; overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.dl-table { width: 100%; border-collapse: collapse; margin: 0; }
.dl-table thead th {
  background: #f8fafc; padding: .8rem 1rem;
  font-size: .75rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .4px; color: #64748b; border-bottom: 1px solid #f1f5f9;
}
.dl-table tbody td {
  padding: .72rem 1rem; border-bottom: 1px solid #f8fafc;
  vertical-align: middle; font-size: .87rem;
}
.dl-table tbody tr:last-child td { border-bottom: none; }
.dl-table tbody tr { transition: background .15s; }
.dl-table tbody tr:hover { background: #fafbff; }
.dl-table tbody tr.row-inactive { opacity: .72; }
.dl-code {
  background: #e2e8f0; color: #475569;
  font-size: .74rem; font-weight: 700;
  padding: 3px 9px; border-radius: 6px; font-family: monospace;
}
.dl-name-cell  { display: flex; align-items: center; gap: .6rem; }
.dl-dept-name  { font-weight: 600; color: #1e293b; }
.dl-avatar {
  width: 32px; height: 32px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: .7rem; color: #fff; flex-shrink: 0;
}
.dl-avatar-active   { background: linear-gradient(135deg,#7c3aed,#6d28d9); }
.dl-avatar-inactive { background: #cbd5e1; }
.dl-dir-cell  { display: flex; align-items: center; gap: .5rem; }
.dl-dir-avatar {
  width: 28px; height: 28px; border-radius: 8px;
  background: linear-gradient(135deg,#0ea5e9,#0284c7);
  color: #fff; font-size: .7rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.dl-dir-name  { font-size: .84rem; color: #1e293b; font-weight: 500; }
.dl-no-dir    { font-size: .8rem; color: #94a3b8; font-style: italic; }
.dl-pill {
  font-size: .74rem; font-weight: 700;
  padding: 3px 9px; border-radius: 6px;
}
.dl-pill-purple { background: rgba(124,58,237,.1); color: #6d28d9; }
.dl-pill-blue   { background: rgba(14,165,233,.1);  color: #0284c7; }

/* ── Switch toggle ──────────────────────────────────────── */
.dl-switch {
  position: relative; width: 42px; height: 22px;
  border-radius: 11px; border: none; cursor: pointer;
  transition: background .25s; flex-shrink: 0;
  display: inline-block; padding: 0;
}
.dl-switch-sm { width: 38px; height: 20px; border-radius: 10px; }
.dl-switch-on  { background: #10b981; }
.dl-switch-off { background: #cbd5e1; }
.dl-switch:disabled { opacity: .55; cursor: not-allowed; }
.dl-switch-thumb {
  position: absolute; top: 3px; left: 3px;
  width: 16px; height: 16px; border-radius: 50%;
  background: #fff; transition: left .25s;
  box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.dl-switch-sm .dl-switch-thumb { width: 14px; height: 14px; top: 3px; left: 3px; }
.dl-switch-thumb-on  { left: calc(100% - 19px); }
.dl-switch-sm .dl-switch-thumb-on { left: calc(100% - 17px); }

/* ── Action btns ────────────────────────────────────────── */
.dl-actions { display: inline-flex; gap: .35rem; }
.dl-btn {
  width: 30px; height: 30px; border-radius: 8px;
  border: 1px solid #e2e8f0; background: #fff;
  color: #64748b; font-size: .78rem;
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; text-decoration: none; transition: all .2s;
}
.dl-btn:hover      { border-color: #7c3aed; color: #7c3aed; background: #faf5ff; }
.dl-btn-danger:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

/* ── Pagination ─────────────────────────────────────────── */
.dl-pagination {
  display: flex; justify-content: space-between; align-items: center;
  padding: .7rem 1rem; border-top: 1px solid #f1f5f9;
}
.dl-page-info { font-size: .78rem; color: #64748b; font-weight: 600; }
.dl-page-btns { display: flex; gap: .25rem; }
.dl-page-btn {
  min-width: 30px; height: 30px; border-radius: 7px;
  border: 1px solid #e2e8f0; background: #fff;
  color: #64748b; font-size: .8rem; font-weight: 600;
  cursor: pointer; transition: all .2s; padding: 0 .45rem;
}
.dl-page-btn:hover        { border-color: #7c3aed; color: #7c3aed; }
.dl-page-btn-active       { background: #7c3aed; border-color: #7c3aed; color: #fff; }
.dl-page-btn:disabled     { opacity: .45; cursor: not-allowed; }

/* ── Mobile cards ───────────────────────────────────────── */
.dl-cards { display: flex; flex-direction: column; gap: .75rem; }
.dl-card-item {
  background: #fff; border-radius: 14px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
  overflow: hidden;
}
.dl-card-inactive { border-left: 3px solid #cbd5e1; opacity: .85; }
.dl-card-header {
  display: flex; align-items: center; gap: .75rem;
  padding: .9rem 1rem .6rem;
}
.dl-card-info { flex: 1; min-width: 0; }
.dl-card-name { font-weight: 700; color: #1e293b; font-size: .92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dl-card-dir  {
  display: flex; align-items: center; gap: .5rem;
  padding: .4rem 1rem; background: #f8fafc;
  font-size: .82rem;
}
.dl-dir-icon  { color: #94a3b8; font-size: .78rem; }
.dl-card-stats {
  display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
  padding: .5rem 1rem;
  border-top: 1px solid #f8fafc;
}
.dl-card-stat { display: flex; align-items: center; gap: .3rem; font-size: .78rem; color: #64748b; }
.dl-card-stat i { font-size: .7rem; color: #94a3b8; }
.dl-badge-active { background: #d1fae5; color: #065f46; font-size: .72rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.dl-badge-histo  { background: #f1f5f9; color: #64748b; font-size: .72rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.dl-card-foot {
  display: flex; gap: .5rem;
  padding: .6rem 1rem; border-top: 1px solid #f8fafc;
}
.dl-card-action {
  flex: 1; text-align: center; padding: .42rem .5rem;
  border-radius: 8px; border: 1px solid #e2e8f0;
  font-size: .8rem; font-weight: 600; color: #64748b;
  text-decoration: none; background: #fff; cursor: pointer; transition: all .2s;
}
.dl-card-action:hover { border-color: #7c3aed; color: #7c3aed; background: #faf5ff; }
.dl-card-action-danger:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

/* ── Empty / Responsive visibility ─────────────────────── */
.dl-empty {
  text-align: center; padding: 3rem 1rem;
  background: #fff; border-radius: 14px;
  border: 2px dashed #e2e8f0;
}
.dl-empty i { font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: .75rem; }
.dl-empty p { color: #94a3b8; margin: 0; font-weight: 500; }
.dl-desktop-only { display: block; }
.dl-mobile-only  { display: none; }
@media (max-width: 768px) {
  .dl-desktop-only { display: none; }
  .dl-mobile-only  { display: block; }
  .dl-hero { padding: 1rem; }
  .dl-hero-btn span { display: none; }
  .dl-hero-btn { padding: .5rem .75rem; }
  .dl-stats { gap: .5rem; }
  .dl-stat  { padding: .6rem .5rem; }
  .dl-stat-val { font-size: 1.2rem; }
  .dl-toolbar { flex-direction: column; align-items: stretch; }
  .dl-tabs  { flex-wrap: wrap; }
}

/* ── Modal ──────────────────────────────────────────────── */
.dl-overlay {
  position: fixed; inset: 0;
  background: rgba(15,23,42,.45); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; animation: fadeIn .2s ease;
}
.dl-modal {
  background: #fff; border-radius: 16px;
  padding: 2rem; width: 92%; max-width: 400px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,.15);
  animation: scaleIn .25s ease;
}
.dl-modal-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: #fef2f2; color: #ef4444;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; margin: 0 auto 1rem;
}
.dl-modal-btn {
  padding: .48rem 1.2rem; border-radius: 10px;
  font-weight: 600; font-size: .85rem; border: none;
  cursor: pointer; transition: all .2s;
  display: inline-flex; align-items: center;
}
.dl-modal-cancel { background: #f1f5f9; color: #64748b; }
.dl-modal-cancel:hover { background: #e2e8f0; }
.dl-modal-delete { background: #ef4444; color: #fff; }
.dl-modal-delete:hover { background: #dc2626; }
.dl-modal-delete:disabled { opacity: .7; cursor: not-allowed; }
@keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
@keyframes scaleIn { from { opacity:0; transform:scale(.92); } to { opacity:1; transform:scale(1); } }
</style>
