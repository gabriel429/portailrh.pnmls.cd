<template>
  <div class="loading-overlay">
    <div class="text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p v-if="message" class="mt-2 text-muted">{{ message }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
    message: { type: String, default: '' },
})
</script>
