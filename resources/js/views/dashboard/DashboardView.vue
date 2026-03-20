<template>
  <div class="py-4">
    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord..." />
    <div v-else>
      <h4 class="mb-4">Bienvenue, {{ auth.agent?.prenom || auth.user?.name }}</h4>
      <div class="row g-3">
        <div class="col-md-3">
          <div class="card text-center p-3">
            <i class="fas fa-folder-open text-primary fa-2x mb-2"></i>
            <h6>Documents</h6>
            <h3>{{ stats.documents || 0 }}</h3>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center p-3">
            <i class="fas fa-clock text-warning fa-2x mb-2"></i>
            <h6>Demandes en attente</h6>
            <h3>{{ stats.requests_pending || 0 }}</h3>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center p-3">
            <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
            <h6>Demandes approuvees</h6>
            <h3>{{ stats.requests_approved || 0 }}</h3>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center p-3">
            <i class="fas fa-calendar-times text-danger fa-2x mb-2"></i>
            <h6>Absences</h6>
            <h3>{{ stats.absences || 0 }}</h3>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const auth = useAuthStore()
const loading = ref(true)
const stats = ref({})

onMounted(async () => {
    try {
        const { data } = await client.get('/dashboard')
        stats.value = data
    } catch {
        // API not yet available, show empty
    } finally {
        loading.value = false
    }
})
</script>
