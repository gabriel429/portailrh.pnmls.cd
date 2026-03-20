<template>
  <teleport to="body">
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ title }}</h5>
            <button type="button" class="btn-close" @click="$emit('cancel')"></button>
          </div>
          <div class="modal-body">
            <p>{{ message }}</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="$emit('cancel')">Annuler</button>
            <button type="button" class="btn btn-danger" :disabled="loading" @click="$emit('confirm')">
              <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
              Confirmer
            </button>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmation' },
    message: { type: String, default: 'Etes-vous sur de vouloir continuer ?' },
    loading: { type: Boolean, default: false },
})

defineEmits(['confirm', 'cancel'])
</script>
