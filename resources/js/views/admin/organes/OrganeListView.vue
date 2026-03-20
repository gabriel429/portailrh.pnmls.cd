<template>
  <div class="organe-list-page">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="page-hero-content">
        <div class="page-hero-icon">
          <i class="fas fa-sitemap"></i>
        </div>
        <div>
          <h1 class="hero-title">Organes</h1>
          <p class="hero-subtitle">Gestion des organes du systeme</p>
        </div>
      </div>
      <router-link :to="{ name: 'admin.organes.create' }" class="hero-btn">
        <i class="fas fa-plus"></i> Nouvel Organe
      </router-link>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px;">
      {{ error }}
      <button type="button" class="btn-close" @click="error = null"></button>
    </div>

    <!-- Count Badge -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <span class="count-badge">
        <i class="fas fa-layer-group me-1"></i>
        {{ organes.length }} organe{{ organes.length !== 1 ? 's' : '' }}
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="data-card">
      <div class="text-center py-5">
        <div class="spinner-border" style="color: #8b5cf6;" role="status">
          <span class="visually-hidden">Chargement...</span>
        </div>
        <p class="text-muted mt-2 mb-0" style="font-size: .88rem;">Chargement des organes...</p>
      </div>
    </div>

    <!-- Data Table -->
    <div v-else>
      <div v-if="organes.length === 0" class="empty-state">
        <i class="fas fa-sitemap"></i>
        <p>Aucun organe trouve.</p>
        <router-link :to="{ name: 'admin.organes.create' }" class="btn btn-sm mt-2" style="background: #8b5cf6; color: #fff; border-radius: 8px;">
          <i class="fas fa-plus me-1"></i> Creer un organe
        </router-link>
      </div>

      <div v-else class="data-card">
        <div class="table-responsive">
          <table class="table data-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Sigle</th>
                <th>Code</th>
                <th>Actif</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="organe in organes" :key="organe.id">
                <td>
                  <span class="fw-semibold" style="color: #1e293b;">{{ organe.nom }}</span>
                </td>
                <td>
                  <span style="color: #475569;">{{ organe.sigle || '-' }}</span>
                </td>
                <td>
                  <span class="code-badge">{{ organe.code }}</span>
                </td>
                <td>
                  <span class="status-indicator">
                    <span class="status-dot" :class="organe.actif ? 'dot-active' : 'dot-inactive'"></span>
                    {{ organe.actif ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <router-link
                      :to="{ name: 'admin.organes.edit', params: { id: organe.id } }"
                      class="action-btn"
                      title="Modifier"
                    >
                      <i class="fas fa-pen"></i>
                    </router-link>
                    <button
                      class="action-btn action-btn-danger"
                      @click="deleteOrgane(organe)"
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
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'

const organes = ref([])
const loading = ref(false)
const error = ref(null)

async function fetchOrganes() {
  loading.value = true
  error.value = null
  try {
    const response = await client.get('/admin/organes')
    organes.value = response.data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement des organes.'
  } finally {
    loading.value = false
  }
}

async function deleteOrgane(organe) {
  if (!confirm('Etes-vous sur de vouloir supprimer cet organe ?')) return
  try {
    await client.delete(`/admin/organes/${organe.id}`)
    organes.value = organes.value.filter(o => o.id !== organe.id)
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors de la suppression.'
  }
}

onMounted(fetchOrganes)
</script>

<style scoped>
.organe-list-page {
  padding: 1.5rem 0;
}

/* ── Hero ── */
.page-hero {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 50%, #6d28d9 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(139, 92, 246, .25);
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

/* ── Code Badge ── */
.code-badge {
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  background: #f5f3ff;
  color: #7c3aed;
  font-size: .78rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
  border: 1px solid #ede9fe;
}

/* ── Status ── */
.status-indicator {
  display: inline-flex;
  align-items: center;
  font-size: .85rem;
  color: #475569;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 6px;
}
.dot-active {
  background: #22c55e;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, .2);
}
.dot-inactive {
  background: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, .2);
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
  border-color: #8b5cf6;
  color: #8b5cf6;
  background: #f5f3ff;
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
</style>
