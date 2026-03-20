<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Organes</h4>
      <router-link :to="{ name: 'admin.organes.create' }" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nouvel Organe
      </router-link>
    </div>

    <div v-if="error" class="alert alert-danger alert-dismissible fade show">
      {{ error }}
      <button type="button" class="btn-close" @click="error = null"></button>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>

    <div v-else class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Sigle</th>
              <th>Actif</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="organes.length === 0">
              <td colspan="5" class="text-center text-muted py-4">Aucun organe trouve.</td>
            </tr>
            <tr v-for="organe in organes" :key="organe.id">
              <td>{{ organe.code }}</td>
              <td>{{ organe.nom }}</td>
              <td>{{ organe.sigle }}</td>
              <td>
                <span class="badge" :class="organe.actif ? 'bg-success' : 'bg-secondary'">
                  {{ organe.actif ? 'Oui' : 'Non' }}
                </span>
              </td>
              <td class="text-end">
                <router-link :to="{ name: 'admin.organes.edit', params: { id: organe.id } }" class="btn btn-sm btn-outline-primary me-1">
                  <i class="fas fa-edit"></i>
                </router-link>
                <button class="btn btn-sm btn-outline-danger" @click="deleteOrgane(organe)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
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
