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
import { useRouter } from 'vue-router'

const router = useRouter()

onMounted(() => {
  // Redirect to the planning list - editing is done via modal
  router.replace({ name: 'rh.holidays.planning' })
})
</script>