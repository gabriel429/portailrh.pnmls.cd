<template>
  <div class="section-list-page">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-layer-group"></i>
        </div>
        <div>
          <h4 class="text-white mb-0 fw-bold">Sections</h4>
          <p class="text-white-50 mb-0" style="font-size:.88rem">Gestion des sections</p>
        </div>
      </div>
      <router-link to="/admin/sections/create" class="hero-btn">
        <i class="fas fa-plus"></i> Nouvelle section
      </router-link>
    </div>

    <!-- Search -->
    <div class="search-box">
      <div class="row g-3 align-items-center">
        <div class="col-md-9">
          <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input
              v-model="search"
              type="text"
              class="search-input"
              placeholder="Rechercher par nom ou code..."
              @input="debouncedFetch"
              @keyup.enter="fetchData"
            >
          </div>
        </div>
        <div class="col-md-3 text-end">
          <span class="count-badge">{{ sections.length }} resultat{{ sections.length !== 1 ? 's' : '' }}</span>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color:#0ea5e9" role="status"></div>
      <p class="mt-2 text-muted">Chargement des sections...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="alert alert-danger rounded-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <!-- Table -->
    <template v-else>
      <div class="data-card">
        <div class="table-responsive" v-if="sections.length > 0">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Departement</th>
                <th>Type</th>
                <th>Agents</th>
                <th style="width:110px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in sections" :key="s.id">
                <td>
                  <span class="code-badge">{{ s.code }}</span>
                </td>
                <td class="fw-semibold">{{ s.nom }}</td>
                <td>{{ s.department?.nom || '-' }}</td>
                <td>
                  <span class="type-badge" :class="s.type === 'section' ? 'type-section' : 'type-service'">
                    {{ s.type === 'section' ? 'Section' : 'Service rattache' }}
                  </span>
                </td>
                <td>
                  <span class="agent-count-badge">{{ s.cellules_count ?? 0 }}</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <router-link
                      :to="'/admin/sections/' + s.id + '/edit'"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-pen"></i>
                    </router-link>
                    <button
                      class="action-btn action-btn-danger"
                      title="Supprimer"
                      @click="destroy(s)"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty state -->
        <div v-else class="empty-state">
          <i class="fas fa-layer-group"></i>
          <p>Aucune section trouvee.</p>
        </div>
      </div>

      <!-- Pagination -->
      <nav v-if="lastPage > 1" class="mt-4">
        <ul class="pagination modern-pagination justify-content-center mb-0">
          <li class="page-item" :class="{ disabled: currentPage <= 1 }">
            <button class="page-link" @click="goToPage(currentPage - 1)">
              <i class="fas fa-chevron-left" style="font-size:.7rem"></i>
            </button>
          </li>
          <li
            v-for="p in lastPage"
            :key="p"
            class="page-item"
            :class="{ active: p === currentPage }"
          >
            <button class="page-link" @click="goToPage(p)">{{ p }}</button>
          </li>
          <li class="page-item" :class="{ disabled: currentPage >= lastPage }">
            <button class="page-link" @click="goToPage(currentPage + 1)">
              <i class="fas fa-chevron-right" style="font-size:.7rem"></i>
            </button>
          </li>
        </ul>
      </nav>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'

const sections = ref([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const currentPage = ref(1)
const lastPage = ref(1)

let debounceTimer = null

async function fetchData() {
  loading.value = true
  error.value = ''
  try {
    const params = { page: currentPage.value }
    if (search.value) params.search = search.value
    const { data } = await client.get('/admin/sections', { params })
    sections.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
  } catch (e) {
    console.error('Erreur chargement sections:', e)
    error.value = 'Impossible de charger les sections.'
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

async function destroy(s) {
  if (!confirm('Etes-vous sur de vouloir supprimer cette section ?')) return
  try {
    await client.delete('/admin/sections/' + s.id)
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
.section-list-page {
  padding: 1.5rem 0;
}

/* ── Hero ── */
.page-hero {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(14, 165, 233, .25);
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

/* ── Search ── */
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
  border-color: #0ea5e9;
  outline: none;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, .1);
}
.search-icon {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}
.count-badge {
  background: #f1f5f9;
  color: #64748b;
  font-size: .78rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
}

/* ── Data Card & Table ── */
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

/* ── Code Badge ── */
.code-badge {
  background: #e2e8f0;
  color: #475569;
  font-size: .75rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
  font-family: monospace;
}

/* ── Type Badges ── */
.type-badge {
  font-size: .75rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
  display: inline-block;
}
.type-section {
  background: #dbeafe;
  color: #1d4ed8;
}
.type-service {
  background: #e0e7ff;
  color: #4338ca;
}

/* ── Agent Count Badge ── */
.agent-count-badge {
  background: #f0f9ff;
  color: #0284c7;
  font-size: .78rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 6px;
  display: inline-block;
}

/* ── Action Buttons ── */
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
  border-color: #0ea5e9;
  color: #0ea5e9;
  background: #f0f9ff;
}
.action-btn-danger:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

/* ── Empty State ── */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  border: 2px dashed #e2e8f0;
  border-radius: 14px;
  margin: 1.5rem;
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

/* ── Pagination ── */
.modern-pagination .page-link {
  border-radius: 8px;
  margin: 0 2px;
  border: 1px solid #e2e8f0;
  color: #64748b;
  font-size: .85rem;
  min-width: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modern-pagination .page-item.active .page-link {
  background: #0ea5e9;
  border-color: #0ea5e9;
  color: #fff;
}
.modern-pagination .page-link:hover {
  background: #f0f9ff;
  border-color: #0ea5e9;
  color: #0ea5e9;
}
.modern-pagination .page-item.disabled .page-link {
  background: #f8fafc;
  color: #cbd5e1;
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
  .page-hero p {
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
  .search-box {
    border-radius: 10px;
    padding: .85rem 1rem;
  }
  .search-input {
    font-size: .82rem;
  }
  .data-card {
    border-radius: 10px;
  }
  .data-table thead th {
    font-size: .72rem;
    padding: .65rem .75rem;
  }
  .data-table tbody td {
    font-size: .82rem;
    padding: .6rem .75rem;
  }
  .action-btn {
    width: 28px;
    height: 28px;
    font-size: .72rem;
  }
  .empty-state {
    padding: 2rem 1rem;
    border-radius: 10px;
  }
  .empty-state i {
    font-size: 2rem;
  }
  .modern-pagination .page-link {
    font-size: .78rem;
    min-width: 30px;
  }
}
</style>
