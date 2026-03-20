<template>
  <div class="toast-container">
    <div
      v-for="toast in ui.toasts"
      :key="toast.id"
      class="toast-item"
      :class="toast.type"
      @click="ui.removeToast(toast.id)"
    >
      <i :class="iconClass(toast.type)"></i>
      <span>{{ toast.message }}</span>
    </div>
  </div>
</template>

<script setup>
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()

function iconClass(type) {
    const map = {
        success: 'fas fa-check-circle',
        danger: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle',
    }
    return map[type] || 'fas fa-bell'
}
</script>
