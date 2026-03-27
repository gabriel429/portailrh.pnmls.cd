<template>
  <div class="container py-4">
    <div class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-2">Redirection...</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

onMounted(() => {
  // Redirect to the agent show page - editing is done via modal
  router.replace({ name: 'rh.agents.show', params: { id: route.params.id } })
})
</script>
