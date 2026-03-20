<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1"><i class="fas fa-medal me-2"></i>Grades</h4>
        <p class="text-muted mb-0">Gestion des grades par categorie</p>
      </div>
      <router-link to="/admin/grades/create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nouveau grade
      </router-link>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement...</p>
    </div>

    <!-- Grouped Tables -->
    <template v-else>
      <div v-if="Object.keys(grouped).length === 0" class="text-center text-muted py-5">
        Aucun grade enregistre.
      </div>

      <div v-for="(grades, categorie) in grouped" :key="categorie" class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center">
          <span class="badge me-2" :class="categorieBadgeClass(categorie)">{{ categorie }}</span>
          <h6 class="mb-0 fw-semibold">
            Categorie {{ categorie }}
            <small class="text-muted fw-normal ms-2">({{ grades.length }} grade{{ grades.length > 1 ? 's' : '' }})</small>
          </h6>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th style="width: 80px">Ordre</th>
                <th>Libelle</th>
                <th>Categorie</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="grade in grades" :key="grade.id">
                <td><span class="badge bg-secondary">{{ grade.ordre }}</span></td>
                <td class="fw-semibold">{{ grade.libelle }}</td>
                <td>
                  <span class="badge" :class="categorieBadgeClass(grade.categorie)">{{ grade.categorie }}</span>
                </td>
                <td class="text-end">
                  <router-link
                    :to="`/admin/grades/${grade.id}/edit`"
                    class="btn btn-sm btn-outline-primary me-1"
                    title="Modifier"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    class="btn btn-sm btn-outline-danger"
                    title="Supprimer"
                    @click="deleteGrade(grade)"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'

const loading = ref(true)
const grouped = ref({})
const allGrades = ref([])

function categorieBadgeClass(cat) {
  switch (cat) {
    case 'A': return 'bg-success'
    case 'B': return 'bg-warning text-dark'
    case 'C': return 'bg-danger'
    default: return 'bg-secondary'
  }
}

async function fetchGrades() {
  loading.value = true
  try {
    const { data } = await client.get('/admin/grades')
    allGrades.value = data.data || []
    grouped.value = data.grouped || {}

    // If the API doesn't return grouped, build it client-side
    if (Object.keys(grouped.value).length === 0 && allGrades.value.length > 0) {
      const g = {}
      for (const grade of allGrades.value) {
        const cat = grade.categorie || 'Autre'
        if (!g[cat]) g[cat] = []
        g[cat].push(grade)
      }
      grouped.value = g
    }
  } catch (e) {
    console.error('Erreur chargement grades:', e)
    alert('Erreur lors du chargement des grades.')
  } finally {
    loading.value = false
  }
}

async function deleteGrade(grade) {
  if (!confirm(`Etes-vous sur de vouloir supprimer le grade "${grade.libelle}" ?`)) return
  try {
    await client.delete(`/admin/grades/${grade.id}`)
    fetchGrades()
  } catch (e) {
    console.error('Erreur suppression grade:', e)
    alert('Erreur lors de la suppression du grade.')
  }
}

onMounted(() => {
  fetchGrades()
})
</script>
