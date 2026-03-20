<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Categories de Documents</h4>
      <router-link :to="{ name: 'admin.categories-documents.create' }" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nouvelle Categorie
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
              <th>Nom</th>
              <th>Icone</th>
              <th>Actif</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="categories.length === 0">
              <td colspan="4" class="text-center text-muted py-4">Aucune categorie trouvee.</td>
            </tr>
            <tr v-for="cat in categories" :key="cat.id">
              <td>{{ cat.nom }}</td>
              <td>
                <i v-if="cat.icone" :class="cat.icone" class="me-1"></i>
                <code v-if="cat.icone">{{ cat.icone }}</code>
                <span v-else class="text-muted">-</span>
              </td>
              <td>
                <span class="badge" :class="cat.actif ? 'bg-success' : 'bg-secondary'">
                  {{ cat.actif ? 'Oui' : 'Non' }}
                </span>
              </td>
              <td class="text-end">
                <router-link :to="{ name: 'admin.categories-documents.edit', params: { id: cat.id } }" class="btn btn-sm btn-outline-primary me-1">
                  <i class="fas fa-edit"></i>
                </router-link>
                <button class="btn btn-sm btn-outline-danger" @click="deleteCategorie(cat)">
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
