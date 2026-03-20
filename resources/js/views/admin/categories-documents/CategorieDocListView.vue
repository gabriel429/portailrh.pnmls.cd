<template>
  <div class="categorie-doc-page">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-tags"></i>
        </div>
        <div>
          <h4 class="text-white mb-0 fw-bold">Categories de Documents</h4>
          <small class="text-white-50">Gestion des categories</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="count-badge" v-if="categories.length > 0">
          {{ categories.length }} categorie{{ categories.length > 1 ? 's' : '' }}
        </span>
        <router-link :to="{ name: 'admin.categories-documents.create' }" class="hero-btn">
          <i class="fas fa-plus"></i> Nouvelle Categorie
        </router-link>
      </div>
    </div>

    <!-- Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px;">
      {{ error }}
      <button type="button" class="btn-close" @click="error = null"></button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color: #f59e0b;" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="text-muted mt-2 mb-0" style="font-size: .88rem;">Chargement des categories...</p>
    </div>

    <!-- Data Table -->
    <div v-else>
      <div v-if="categories.length === 0" class="empty-state">
        <i class="fas fa-tags"></i>
        <p>Aucune categorie trouvee.</p>
      </div>

      <div v-else class="data-card">
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Icone</th>
                <th>Actif</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="cat-icon-box">
                      <i :class="cat.icone || 'fas fa-folder'" ></i>
                    </div>
                    <span class="fw-500">{{ cat.nom }}</span>
                  </div>
                </td>
                <td>
                  <div v-if="cat.icone" class="d-flex align-items-center gap-2">
                    <span class="icon-preview">
                      <i :class="cat.icone"></i>
                    </span>
                    <code class="icon-code">{{ cat.icone }}</code>
                  </div>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <span class="status-pill" :class="cat.actif ? 'status-active' : 'status-inactive'">
                    <span class="status-dot"></span>
                    {{ cat.actif ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1">
                    <router-link
                      :to="{ name: 'admin.categories-documents.edit', params: { id: cat.id } }"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button class="action-btn action-btn-danger" @click="deleteCategorie(cat)" title="Supprimer">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'

const categories = ref([])
const loading = ref(false)
const error = ref(null)

async function fetchCategories() {
  loading.value = true
  error.value = null
  try {
    const response = await client.get('/admin/categories-documents')
    categories.value = response.data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement des categories.'
  } finally {
    loading.value = false
  }
}

async function deleteCategorie(cat) {
  if (!confirm('Etes-vous sur de vouloir supprimer cette categorie ?')) return
  try {
    await client.delete(`/admin/categories-documents/${cat.id}`)
    categories.value = categories.value.filter(c => c.id !== cat.id)
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors de la suppression.'
  }
}

onMounted(fetchCategories)
</script>

<style scoped>
.categorie-doc-page {
  padding: 1.5rem 0;
}

.page-hero {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(245, 158, 11, .25);
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
}

.hero-btn:hover {
  background: rgba(255, 255, 255, .35);
  color: #fff;
}

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
  background: #fffbeb;
}

.cat-icon-box {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: #fffbeb;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #d97706;
  font-size: .85rem;
  flex-shrink: 0;
}

.fw-500 {
  font-weight: 500;
}

.icon-preview {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  background: #fef3c7;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #b45309;
  font-size: .78rem;
}

.icon-code {
  background: #f8fafc;
  color: #64748b;
  font-size: .75rem;
  padding: 2px 8px;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: .78rem;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 20px;
}

.status-pill.status-active {
  background: #f0fdf4;
  color: #16a34a;
}

.status-pill.status-inactive {
  background: #f1f5f9;
  color: #94a3b8;
}

.status-dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: currentColor;
}

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
  border-color: #f59e0b;
  color: #d97706;
  background: #fffbeb;
}

.action-btn-danger:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  border: 2px dashed #e2e8f0;
  border-radius: 14px;
  background: #fff;
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

/* ── Mobile Responsive ── */
@media (max-width: 767.98px) {
  .page-hero {
    padding: 1.25rem 1rem;
    border-radius: 12px;
    flex-direction: column;
    align-items: flex-start;
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
  .cat-icon-box {
    width: 28px;
    height: 28px;
    font-size: .75rem;
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
}
</style>
