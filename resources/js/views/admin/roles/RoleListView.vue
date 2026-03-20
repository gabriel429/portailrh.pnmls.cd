<template>
  <div class="py-4">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="d-flex align-items-center gap-3">
        <div class="page-hero-icon">
          <i class="fas fa-user-tag"></i>
        </div>
        <div>
          <h4 class="mb-0 text-white fw-bold" style="font-size:1.15rem">Roles</h4>
          <p class="mb-0 text-white-50" style="font-size:.85rem">Gestion des roles du systeme</p>
        </div>
      </div>
      <router-link to="/admin/roles/create" class="hero-btn">
        <i class="fas fa-plus"></i> Nouveau role
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
              placeholder="Rechercher un role..."
              @input="debouncedFetch"
            />
          </div>
        </div>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
          <span class="count-badge">{{ total }} role(s) trouve(s)</span>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color:#14b8a6"></div>
      <p class="mt-2 text-muted">Chargement...</p>
    </div>

    <!-- Table -->
    <div v-else>
      <div v-if="roles.length === 0" class="empty-state">
        <i class="fas fa-user-tag"></i>
        <p>Aucun role trouve.</p>
      </div>

      <div v-else class="data-card">
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Nom du role</th>
                <th>Description</th>
                <th class="text-center">Agents</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="role in roles" :key="role.id">
                <td class="fw-semibold">
                  <div class="d-flex align-items-center gap-2">
                    <div class="role-icon-sm">
                      <i class="fas fa-user-tag"></i>
                    </div>
                    {{ role.nom_role }}
                  </div>
                </td>
                <td class="text-muted">{{ role.description || '-' }}</td>
                <td class="text-center">
                  <span class="agent-count-badge">{{ role.agents_count ?? 0 }}</span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <router-link
                      :to="`/admin/roles/${role.id}/edit`"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button
                      class="action-btn action-btn-danger"
                      title="Supprimer"
                      @click="deleteRole(role)"
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

const loading = ref(true)
const roles = ref([])
const search = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

let debounceTimer = null

const pagesArray = computed(() => {
  const pages = []
  for (let i = 1; i <= lastPage.value; i++) {
    pages.push(i)
  }
  return pages
})

async function fetchRoles() {
  loading.value = true
  try {
    const { data } = await client.get('/admin/roles', {
      params: { search: search.value, page: currentPage.value },
    })
    roles.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    total.value = data.total || 0
  } catch (e) {
    console.error('Erreur chargement roles:', e)
    alert('Erreur lors du chargement des roles.')
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    fetchRoles()
  }, 400)
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  fetchRoles()
}

async function deleteRole(role) {
  if (!confirm(`Etes-vous sur de vouloir supprimer le role "${role.nom_role}" ?`)) return
  try {
    await client.delete(`/admin/roles/${role.id}`)
    fetchRoles()
  } catch (e) {
    console.error('Erreur suppression role:', e)
    alert('Erreur lors de la suppression du role.')
  }
}

onMounted(() => {
  fetchRoles()
})
</script>

<style scoped>
.page-hero {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 50%, #115e59 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(20, 184, 166, .25);
  margin-bottom: 1.5rem;
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
  border-color: #14b8a6;
  outline: none;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, .1);
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
  color: #0d9488;
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

/* Role icon in table row */
.role-icon-sm {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #f0fdfa;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #14b8a6;
  font-size: .75rem;
  flex-shrink: 0;
}
.agent-count-badge {
  background: #f0fdfa;
  color: #0d9488;
  font-size: .78rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 8px;
  display: inline-block;
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
  border-color: #14b8a6;
  color: #14b8a6;
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
  background: #14b8a6;
  border-color: #14b8a6;
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
    font-size: .75rem;
    padding: .3rem .6rem;
  }
}
</style>
