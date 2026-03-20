<template>
  <div class="department-list">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-building"></i>
        </div>
        <div>
          <h4 class="mb-1 fw-bold text-white">Departements</h4>
          <p class="mb-0" style="color:rgba(255,255,255,.65);font-size:.88rem;">Gestion des departements du systeme</p>
        </div>
      </div>
      <router-link to="/admin/departments/create" class="hero-btn">
        <i class="fas fa-plus"></i>Nouveau departement
      </router-link>
    </div>

    <!-- Search -->
    <div class="search-box">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="position-relative flex-grow-1" style="max-width:400px;">
          <i class="fas fa-search search-icon"></i>
          <input
            v-model="search"
            type="text"
            class="search-input"
            placeholder="Rechercher un departement..."
            @input="debouncedFetch"
          />
        </div>
        <span class="count-badge">{{ total }} departement(s)</span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-grow" style="width:3rem;height:3rem;color:#7c3aed;"></div>
      <p class="mt-3 text-muted fw-semibold">Chargement des departements...</p>
    </div>

    <template v-else>
      <!-- Table -->
      <div v-if="departments.length" class="data-card">
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Province</th>
                <th class="text-center">Agents</th>
                <th class="text-center">Sections</th>
                <th class="text-end pe-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="dept in departments" :key="dept.id">
                <td>
                  <span class="code-badge">{{ dept.code }}</span>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="item-avatar">
                      <i class="fas fa-building"></i>
                    </div>
                    <span class="fw-semibold">{{ dept.nom }}</span>
                  </div>
                </td>
                <td>
                  <span v-if="dept.province" class="province-tag">
                    <i class="fas fa-map-marker-alt me-1"></i>{{ dept.province.nom }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td class="text-center">
                  <span class="count-pill count-pill-primary">{{ dept.agents_count ?? 0 }}</span>
                </td>
                <td class="text-center">
                  <span class="count-pill count-pill-info">{{ dept.sections_count ?? 0 }}</span>
                </td>
                <td class="text-end pe-3">
                  <div class="d-inline-flex gap-1">
                    <router-link
                      :to="`/admin/departments/${dept.id}/edit`"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-pen"></i>
                    </router-link>
                    <button
                      class="action-btn action-btn-danger"
                      title="Supprimer"
                      @click="confirmDelete(dept)"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="pagination-footer">
          <small class="text-muted fw-semibold">Page {{ currentPage }} sur {{ lastPage }}</small>
          <nav>
            <ul class="pagination pagination-sm mb-0 modern-pagination">
              <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                <button class="page-link" @click="goToPage(currentPage - 1)">
                  <i class="fas fa-chevron-left"></i>
                </button>
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
                <button class="page-link" @click="goToPage(currentPage + 1)">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <i class="fas fa-building"></i>
        <p>Aucun departement trouve</p>
      </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="delete-overlay" @click.self="showDeleteModal = false">
        <div class="delete-modal">
          <div class="delete-modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <h5 class="fw-bold mb-2">Confirmer la suppression</h5>
          <p class="text-muted mb-3" style="font-size:.9rem;">
            Etes-vous sur de vouloir supprimer le departement
            <strong class="text-dark">{{ deleteTarget?.nom }}</strong> ?
            Cette action est irreversible.
          </p>
          <div class="d-flex gap-2 justify-content-center">
            <button class="btn-modal btn-modal-cancel" @click="showDeleteModal = false">
              <i class="fas fa-times me-1"></i>Annuler
            </button>
            <button class="btn-modal btn-modal-delete" @click="executeDelete" :disabled="deleting">
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

const loading = ref(true)
const departments = ref([])
const search = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

let debounceTimer = null

const pagesArray = computed(() => {
  const pages = []
  for (let i = 1; i <= lastPage.value; i++) {
    pages.push(i)
  }
  return pages
})

async function fetchDepartments() {
  loading.value = true
  try {
    const { data } = await client.get('/admin/departments', {
      params: { search: search.value, page: currentPage.value },
    })
    departments.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    total.value = data.total || 0
  } catch (e) {
    console.error('Erreur chargement departements:', e)
    alert('Erreur lors du chargement des departements.')
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    fetchDepartments()
  }, 400)
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchDepartments()
}

function confirmDelete(dept) {
  deleteTarget.value = dept
  showDeleteModal.value = true
}

async function executeDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await client.delete(`/admin/departments/${deleteTarget.value.id}`)
    showDeleteModal.value = false
    deleteTarget.value = null
    fetchDepartments()
  } catch (e) {
    console.error('Erreur suppression departement:', e)
    alert('Erreur lors de la suppression du departement.')
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  fetchDepartments()
})
</script>

<style scoped>
/* Hero */
.page-hero {
  background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(124, 58, 237, .25);
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
  background: rgba(255,255,255,.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #fff;
  backdrop-filter: blur(4px);
}
.hero-btn {
  background: rgba(255,255,255,.2);
  border: 1px solid rgba(255,255,255,.3);
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
  background: rgba(255,255,255,.35);
  color: #fff;
}

/* Search */
.search-box {
  background: #fff;
  border-radius: 14px;
  padding: 1rem 1.25rem;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #f1f5f9;
  margin-bottom: 1.25rem;
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
  border-color: #7c3aed;
  outline: none;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, .1);
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

/* Data Card & Table */
.data-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
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

/* Item Avatar */
.item-avatar {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: #fff;
  font-size: .7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Province Tag */
.province-tag {
  background: #faf5ff;
  color: #7c3aed;
  font-size: .8rem;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 6px;
}

/* Badges */
.code-badge {
  background: #e2e8f0;
  color: #475569;
  font-size: .75rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
  font-family: monospace;
}
.count-pill {
  font-size: .75rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
}
.count-pill-primary {
  background: rgba(124, 58, 237, .1);
  color: #6d28d9;
}
.count-pill-info {
  background: rgba(14, 165, 233, .1);
  color: #0284c7;
}

/* Action Buttons */
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
  border-color: #7c3aed;
  color: #7c3aed;
  background: #faf5ff;
}
.action-btn-danger:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

/* Pagination */
.pagination-footer {
  padding: .75rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #f1f5f9;
}
.modern-pagination .page-link {
  border-radius: 8px;
  margin: 0 2px;
  border: 1px solid #e2e8f0;
  color: #64748b;
  font-size: .82rem;
  font-weight: 600;
  padding: .35rem .7rem;
  transition: all .2s;
}
.modern-pagination .page-link:hover {
  background: #faf5ff;
  border-color: #7c3aed;
  color: #7c3aed;
}
.modern-pagination .page-item.active .page-link {
  background: #7c3aed;
  border-color: #7c3aed;
  color: #fff;
}
.modern-pagination .page-item.disabled .page-link {
  color: #cbd5e1;
  background: transparent;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  background: #fff;
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

/* Delete Modal */
.delete-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, .45);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn .2s ease;
}
.delete-modal {
  background: #fff;
  border-radius: 16px;
  padding: 2rem;
  width: 92%;
  max-width: 420px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,.15);
  animation: scaleIn .25s ease;
}
.delete-modal-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: #fef2f2;
  color: #ef4444;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  margin: 0 auto 1rem;
}
.btn-modal {
  padding: .5rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: .85rem;
  border: none;
  cursor: pointer;
  transition: all .2s;
  display: inline-flex;
  align-items: center;
}
.btn-modal-cancel {
  background: #f1f5f9;
  color: #64748b;
}
.btn-modal-cancel:hover {
  background: #e2e8f0;
}
.btn-modal-delete {
  background: #ef4444;
  color: #fff;
}
.btn-modal-delete:hover {
  background: #dc2626;
}
.btn-modal-delete:disabled {
  opacity: .7;
  cursor: not-allowed;
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes scaleIn {
  from { opacity: 0; transform: scale(.92); }
  to { opacity: 1; transform: scale(1); }
}

/* Responsive */
@media (max-width: 767.98px) {
  .page-hero {
    padding: 1.2rem;
    border-radius: 12px;
  }
  .page-hero-icon {
    width: 42px;
    height: 42px;
    font-size: 1.1rem;
  }
  .page-hero h4 {
    font-size: 1rem;
  }
  .hero-btn {
    padding: .45rem 1rem;
    font-size: .8rem;
  }
  .search-input {
    font-size: .82rem;
  }
  .delete-modal {
    padding: 1.5rem;
  }
}
</style>
