<template>
  <div class="py-4">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="d-flex align-items-center gap-3">
        <div class="page-hero-icon">
          <i class="fas fa-medal"></i>
        </div>
        <div>
          <h4 class="mb-0 text-white fw-bold" style="font-size:1.15rem">Grades</h4>
          <p class="mb-0 text-white-50" style="font-size:.85rem">Gestion des grades par categorie</p>
        </div>
      </div>
      <router-link to="/admin/grades/create" class="hero-btn">
        <i class="fas fa-plus"></i> Nouveau grade
      </router-link>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" style="color:#eab308"></div>
      <p class="mt-2 text-muted">Chargement...</p>
    </div>

    <!-- Grouped Tables -->
    <template v-else>
      <div v-if="Object.keys(grouped).length === 0" class="empty-state">
        <i class="fas fa-medal"></i>
        <p>Aucun grade enregistre.</p>
      </div>

      <div v-for="(grades, categorie) in grouped" :key="categorie" class="category-group">
        <!-- Category Header -->
        <div class="category-header">
          <div class="d-flex align-items-center gap-2">
            <span class="category-badge" :class="categorieBadgeClass(categorie)">{{ categorie }}</span>
            <h6 class="mb-0 fw-semibold" style="font-size:.9rem">
              Categorie {{ categorie }}
            </h6>
          </div>
          <span class="count-badge">{{ grades.length }} grade{{ grades.length > 1 ? 's' : '' }}</span>
        </div>

        <!-- Table -->
        <div class="data-card">
          <div class="table-responsive">
            <table class="table data-table">
              <thead>
                <tr>
                  <th style="width:80px">Ordre</th>
                  <th>Libelle</th>
                  <th>Categorie</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="grade in grades" :key="grade.id">
                  <td>
                    <span class="ordre-badge">{{ grade.ordre }}</span>
                  </td>
                  <td class="fw-semibold">
                    <div class="d-flex align-items-center gap-2">
                      <div class="grade-icon-sm">
                        <i class="fas fa-medal"></i>
                      </div>
                      {{ grade.libelle }}
                    </div>
                  </td>
                  <td>
                    <span class="badge" :class="categorieBadgeClass(grade.categorie)">{{ grade.categorie }}</span>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <router-link
                        :to="`/admin/grades/${grade.id}/edit`"
                        class="action-btn"
                        title="Modifier"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button
                        class="action-btn action-btn-danger"
                        title="Supprimer"
                        @click="deleteGrade(grade)"
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

<style scoped>
.page-hero {
  background: linear-gradient(135deg, #eab308 0%, #ca8a04 50%, #a16207 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  box-shadow: 0 8px 32px rgba(234, 179, 8, .25);
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

/* Category Group */
.category-group {
  margin-bottom: 1.5rem;
}
.category-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .75rem 1rem;
  margin-bottom: .5rem;
}
.category-badge {
  font-size: .78rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 8px;
}
.count-badge {
  background: #fefce8;
  color: #a16207;
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

/* Grade icon in table row */
.grade-icon-sm {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #fefce8;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ca8a04;
  font-size: .75rem;
  flex-shrink: 0;
}
.ordre-badge {
  background: #f1f5f9;
  color: #64748b;
  font-size: .78rem;
  font-weight: 700;
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
  border-color: #eab308;
  color: #ca8a04;
  background: #fefce8;
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
</style>
