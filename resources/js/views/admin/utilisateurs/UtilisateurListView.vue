<template>
  <div class="user-list-page">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-user-shield"></i>
        </div>
        <div>
          <h1 class="hero-title">Utilisateurs</h1>
          <p class="hero-subtitle">Gestion des comptes utilisateurs</p>
        </div>
      </div>
      <router-link :to="{ name: 'admin.utilisateurs.create' }" class="hero-btn">
        <i class="fas fa-plus"></i> Nouvel Utilisateur
      </router-link>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px;">
      {{ error }}
      <button type="button" class="btn-close" @click="error = null"></button>
    </div>

    <!-- Search Box -->
    <div class="search-box">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="search-wrapper" style="flex: 1; max-width: 400px;">
          <i class="fas fa-search search-icon"></i>
          <input
            type="text"
            v-model="search"
            class="search-input"
            placeholder="Rechercher par nom ou email..."
            @input="onSearchInput"
          />
        </div>
        <span class="count-badge">
          <i class="fas fa-users me-1"></i>
          {{ pagination.total }} utilisateur{{ pagination.total !== 1 ? 's' : '' }}
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="data-card">
      <div class="text-center py-5">
        <div class="spinner-border" style="color: #059669;" role="status">
          <span class="visually-hidden">Chargement...</span>
        </div>
        <p class="text-muted mt-2 mb-0" style="font-size: .88rem;">Chargement des utilisateurs...</p>
      </div>
    </div>

    <!-- Data Table -->
    <div v-else>
      <div v-if="utilisateurs.length === 0" class="empty-state">
        <i class="fas fa-user-shield"></i>
        <p>Aucun utilisateur trouve.</p>
        <router-link :to="{ name: 'admin.utilisateurs.create' }" class="btn btn-sm mt-2" style="background: #059669; color: #fff; border-radius: 8px;">
          <i class="fas fa-plus me-1"></i> Creer un utilisateur
        </router-link>
      </div>

      <div v-else class="data-card">
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Role</th>
                <th>Agent lie</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in utilisateurs" :key="user.id">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar" :style="{ background: getAvatarColor(user) }">
                      {{ getInitials(user) }}
                    </div>
                    <span class="fw-semibold" style="color: #1e293b;">{{ user.name }}</span>
                  </div>
                </td>
                <td>
                  <span style="color: #475569;">{{ user.email }}</span>
                </td>
                <td>
                  <span v-if="user.role" class="role-badge">
                    {{ user.role.nom }}
                  </span>
                  <span v-else class="text-muted" style="font-size: .85rem;">-</span>
                </td>
                <td>
                  <span v-if="user.agent" style="color: #1e293b; font-size: .85rem;">
                    {{ `${user.agent.nom} ${user.agent.postnom || ''} ${user.agent.prenom || ''}`.trim() }}
                  </span>
                  <span v-else class="text-muted" style="font-size: .85rem;">-</span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <router-link
                      :to="{ name: 'admin.utilisateurs.edit', params: { id: user.id } }"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-pen"></i>
                    </router-link>
                    <button
                      class="action-btn action-btn-danger"
                      @click="deleteUtilisateur(user)"
                      title="Supprimer"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <nav v-if="pagination.lastPage > 1" class="mt-3 d-flex justify-content-center">
        <ul class="pagination modern-pagination mb-0">
          <li class="page-item" :class="{ disabled: pagination.currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.currentPage - 1)">
              <i class="fas fa-chevron-left" style="font-size: .7rem;"></i>
            </a>
          </li>
          <li
            v-for="page in paginationPages"
            :key="page"
            class="page-item"
            :class="{ active: page === pagination.currentPage, disabled: page === '...' }"
          >
            <a class="page-link" href="#" @click.prevent="page !== '...' && goToPage(page)">
              {{ page }}
            </a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.currentPage === pagination.lastPage }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.currentPage + 1)">
              <i class="fas fa-chevron-right" style="font-size: .7rem;"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client'

const utilisateurs = ref([])
const loading = ref(false)
const error = ref(null)
const search = ref('')
let searchTimeout = null

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

const paginationPages = computed(() => {
  const pages = []
  const current = pagination.value.currentPage
  const last = pagination.value.lastPage

  if (last <= 7) {
    for (let i = 1; i <= last; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
      pages.push(i)
    }
    if (current < last - 2) pages.push('...')
    pages.push(last)
  }
  return pages
})

function getInitials(user) {
  if (user.agent) {
    return ((user.agent.prenom || '')[0] || '') + ((user.agent.nom || '')[0] || '')
  }
  return (user.name || 'U').substring(0, 2).toUpperCase()
}

function getAvatarColor(user) {
  const colors = ['#059669', '#0891b2', '#7c3aed', '#db2777', '#ea580c', '#2563eb', '#4f46e5']
  const hash = (user.id || 0) % colors.length
  return colors[hash]
}

async function fetchUtilisateurs(page = 1) {
  loading.value = true
  error.value = null
  try {
    const response = await client.get('/admin/utilisateurs', {
      params: { search: search.value, page },
    })
    utilisateurs.value = response.data.data
    pagination.value = {
      currentPage: response.data.current_page,
      lastPage: response.data.last_page,
      total: response.data.total,
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement des utilisateurs.'
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchUtilisateurs(1)
  }, 400)
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.lastPage) return
  fetchUtilisateurs(page)
}

async function deleteUtilisateur(user) {
  if (!confirm('Etes-vous sur de vouloir supprimer cet utilisateur ?')) return
  try {
    await client.delete(`/admin/utilisateurs/${user.id}`)
    fetchUtilisateurs(pagination.value.currentPage)
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors de la suppression.'
  }
}

onMounted(() => fetchUtilisateurs())
</script>

<style scoped>
.user-list-page {
  padding: 1.5rem 0;
}

/* ── Hero ── */
.page-hero {
  background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(5, 150, 105, .25);
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
.hero-title {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 700;
  color: #fff;
}
.hero-subtitle {
  margin: 0;
  font-size: .85rem;
  color: rgba(255, 255, 255, .8);
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

/* ── Search Box ── */
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
  border-color: #059669;
  outline: none;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, .1);
}
.search-icon {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

/* ── Count Badge ── */
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

/* ── User Avatar ── */
.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .7rem;
  font-weight: 700;
  color: #fff;
  flex-shrink: 0;
}

/* ── Role Badge ── */
.role-badge {
  background: #ecfdf5;
  color: #059669;
  font-size: .78rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
  border: 1px solid #d1fae5;
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
  border-color: #059669;
  color: #059669;
  background: #ecfdf5;
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
  padding: .35rem .65rem;
}
.modern-pagination .page-link:hover {
  background: #ecfdf5;
  border-color: #059669;
  color: #059669;
}
.modern-pagination .page-item.active .page-link {
  background: #059669;
  border-color: #059669;
  color: #fff;
}
.modern-pagination .page-item.disabled .page-link {
  color: #cbd5e1;
  border-color: #f1f5f9;
}
</style>
