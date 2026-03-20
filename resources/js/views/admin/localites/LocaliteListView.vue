<template>
  <div class="py-4">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-map-pin"></i>
        </div>
        <div>
          <h4 class="mb-0 text-white fw-bold" style="font-size:1.15rem">Localites</h4>
          <p class="mb-0 text-white-50" style="font-size:.85rem">Gestion des localites</p>
        </div>
      </div>
      <router-link to="/admin/localites/create" class="hero-btn">
        <i class="fas fa-plus"></i> Nouvelle localite
      </router-link>
    </div>

    <!-- Search -->
    <div class="search-box">
      <div class="row align-items-center">
        <div class="col-md-6">
          <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input
              v-model="search"
              type="text"
              class="search-input"
              placeholder="Rechercher une localite..."
              @input="debouncedFetch"
            />
          </div>
        </div>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
          <span class="count-badge">{{ total }} localite(s) trouvee(s)</span>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color:#0d9488"></div>
      <p class="mt-2 text-muted">Chargement des localites...</p>
    </div>

    <!-- Table -->
    <div v-else>
      <div v-if="localites.length === 0" class="empty-state">
        <i class="fas fa-map-pin"></i>
        <p>Aucune localite trouvee.</p>
      </div>

      <div v-else class="data-card">
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Type</th>
                <th>Province</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in localites" :key="l.id">
                <td class="fw-semibold">
                  <div class="d-flex align-items-center gap-2">
                    <div class="localite-icon-sm">
                      <i class="fas fa-map-pin"></i>
                    </div>
                    {{ l.nom }}
                  </div>
                </td>
                <td>
                  <span
                    class="type-badge"
                    :class="{
                      'type-ville': l.type === 'ville',
                      'type-commune': l.type === 'commune',
                      'type-territoire': l.type === 'territoire',
                      'type-zone': l.type === 'zone_de_sante',
                      'type-autre': l.type === 'autre',
                    }"
                  >
                    {{ formatType(l.type) }}
                  </span>
                </td>
                <td>
                  <span v-if="l.province" class="text-muted">
                    <i class="fas fa-map-marker-alt me-1"></i>{{ l.province.nom }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <router-link
                      :to="`/admin/localites/${l.id}/edit`"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      class="action-btn action-btn-danger"
                      title="Supprimer"
                      @click="destroy(l)"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="d-flex justify-content-between align-items-center px-3 py-3" style="border-top:1px solid #f1f5f9">
          <small class="text-muted">Page {{ currentPage }} sur {{ lastPage }}</small>
          <nav>
            <ul class="pagination pagination-sm mb-0 modern-pagination">
              <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                <button class="page-link" @click="goToPage(currentPage - 1)">&laquo;</button>
              </li>
              <li
                v-for="p in pagesArray"
                :key="p"
                class="page-item"
                :class="{ active: p === currentPage }"
              >
                <button class="page-link" @click="goToPage(p)">{{ p }}</button>
              </li>
              <li class="page-item" :class="{ disabled: currentPage >= lastPage }">
                <button class="page-link" @click="goToPage(currentPage + 1)">&raquo;</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'

const localites = ref([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
let debounceTimer = null

const typeLabels = {
  territoire: 'Territoire',
  zone_de_sante: 'Zone de sante',
  commune: 'Commune',
  ville: 'Ville',
  autre: 'Autre',
}

function formatType(type) {
  return typeLabels[type] || type || '-'
}

const pagesArray = computed(() => {
  const pages = []
  for (let i = 1; i <= lastPage.value; i++) pages.push(i)
  return pages
})

async function fetchData() {
  loading.value = true
  error.value = ''
  try {
    const params = { page: currentPage.value }
    if (search.value) params.search = search.value
    const { data } = await client.get('/admin/localites', { params })
    localites.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    total.value = data.total || 0
  } catch (e) {
    console.error('Erreur chargement localites:', e)
    error.value = 'Impossible de charger les localites.'
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { currentPage.value = 1; fetchData() }, 400)
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchData()
}

async function destroy(l) {
  if (!confirm('Etes-vous sur de vouloir supprimer cette localite ?')) return
  try {
    await client.delete('/admin/localites/' + l.id)
    await fetchData()
  } catch (e) {
    console.error('Erreur suppression:', e)
    alert('Erreur lors de la suppression.')
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
/* Hero */
.page-hero {
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(13, 148, 136, .25);
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
}
.hero-btn:hover {
  background: rgba(255, 255, 255, .35);
  color: #fff;
}

/* Search */
.search-box {
  background: #fff;
  border-radius: 14px;
  padding: 1rem 1.25rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
  border: 1px solid #f1f5f9;
  margin-bottom: 1.25rem;
}
.search-wrapper {
  position: relative;
}
.search-input {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: .5rem 1rem .5rem 2.5rem;
  font-size: .88rem;
  width: 100%;
  transition: border-color .2s;
}
.search-input:focus {
  border-color: #0d9488;
  outline: none;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, .1);
}
.search-icon {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}
.count-badge {
  background: #f0fdfa;
  color: #0f766e;
  font-size: .78rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
}

/* Table Card */
.data-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}
.data-table {
  margin-bottom: 0;
}
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
.data-table tbody tr {
  transition: background .15s;
}
.data-table tbody tr:hover {
  background: #f8fafc;
}

/* Localite icon in table row */
.localite-icon-sm {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #f0fdfa;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0d9488;
  font-size: .75rem;
  flex-shrink: 0;
}

/* Type badges */
.type-badge {
  font-size: .78rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
  display: inline-block;
}
.type-ville {
  background: #eff6ff;
  color: #2563eb;
}
.type-commune {
  background: #f0fdf4;
  color: #16a34a;
}
.type-territoire {
  background: #ecfeff;
  color: #0891b2;
}
.type-zone {
  background: #fffbeb;
  color: #d97706;
}
.type-autre {
  background: #f1f5f9;
  color: #64748b;
}

/* Actions */
.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-size: .8rem;
  transition: all .2s;
  cursor: pointer;
  text-decoration: none;
}
.action-btn:hover {
  border-color: #0d9488;
  color: #0d9488;
  background: #f0fdfa;
}
.action-btn-danger:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  border: 2px dashed #e2e8f0;
  border-radius: 14px;
}
.empty-state i {
  font-size: 2.5rem;
  color: #cbd5e1;
  margin-bottom: .75rem;
  display: block;
}
.empty-state p {
  color: #94a3b8;
  margin: 0;
  font-weight: 500;
}

/* Pagination */
.modern-pagination .page-link {
  border-radius: 8px;
  margin: 0 2px;
  border: 1px solid #e2e8f0;
  color: #64748b;
  font-size: .82rem;
  font-weight: 600;
  padding: .35rem .7rem;
}
.modern-pagination .page-item.active .page-link {
  background: #0d9488;
  border-color: #0d9488;
}
</style>
