<template>
  <div
    v-if="show"
    class="modal fade show"
    style="display: block; background: rgba(0,0,0,0.5);"
    @click="handleBackdropClick"
  >
    <div
      class="modal-dialog"
      :class="modalSizeClass"
      @click.stop
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i v-if="icon" :class="['fas', icon, 'me-2']"></i>
            {{ title }}
          </h5>
          <button
            type="button"
            class="btn-close"
            @click="$emit('close')"
            :disabled="loading"
          ></button>
        </div>

        <div class="modal-body" :class="{ 'p-0': noPadding }">
          <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">{{ loadingMessage }}</p>
          </div>

          <div v-else>
            <slot />
          </div>
        </div>

        <div class="modal-footer" v-if="showFooter">
          <slot name="footer">
            <button
              type="button"
              class="btn btn-secondary"
              @click="$emit('close')"
              :disabled="submitting"
            >
              {{ cancelText }}
            </button>
            <button
              v-if="showSaveButton"
              type="button"
              class="btn btn-primary"
              @click="$emit('save')"
              :disabled="submitting"
            >
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else-if="saveIcon" :class="['fas', saveIcon, 'me-2']"></i>
              {{ submitting ? savingText : saveText }}
            </button>
          </slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  icon: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  loading: {
    type: Boolean,
    default: false
  },
  loadingMessage: {
    type: String,
    default: 'Chargement...'
  },
  submitting: {
    type: Boolean,
    default: false
  },
  showFooter: {
    type: Boolean,
    default: true
  },
  showSaveButton: {
    type: Boolean,
    default: true
  },
  noPadding: {
    type: Boolean,
    default: false
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  cancelText: {
    type: String,
    default: 'Annuler'
  },
  saveText: {
    type: String,
    default: 'Enregistrer'
  },
  savingText: {
    type: String,
    default: 'Enregistrement...'
  },
  saveIcon: {
    type: String,
    default: 'fa-save'
  }
})

const emit = defineEmits(['close', 'save'])

const modalSizeClass = computed(() => {
  const classes = {
    sm: 'modal-sm',
    md: '',
    lg: 'modal-lg',
    xl: 'modal-xl'
  }
  return classes[props.size]
})

function handleBackdropClick() {
  if (props.closeOnBackdrop && !props.submitting) {
    emit('close')
  }
}
</script>

<style scoped>
.modal-dialog {
  margin: 1rem auto;
}

.modal-header {
  border-bottom: 1px solid #e9ecef;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
}

.modal-title {
  font-weight: 600;
  color: #333;
}

.modal-body {
  max-height: 70vh;
  overflow-y: auto;
}

.modal-footer {
  border-top: 1px solid #e9ecef;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
}

.btn {
  border-radius: 8px;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }

  .modal-body {
    max-height: 60vh;
    padding: 1rem;
  }

  .modal-header,
  .modal-footer {
    padding: 0.75rem 1rem;
  }
}
</style>