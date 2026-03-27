<template>
  <GenericEditModal
    :show="show"
    title="Modifier la Demande"
    icon="fa-edit"
    size="lg"
    :loading="loading"
    loading-message="Chargement de la demande..."
    :submitting="submitting"
    @close="$emit('close')"
    @save="handleSubmit"
    save-text="Enregistrer"
    saving-text="Enregistrement..."
    save-icon="fa-save"
  >
    <!-- Not authorized -->
    <div v-if="!isRH && !loading" class="text-center py-4">
      <i class="fas fa-lock fa-3x text-danger mb-3 d-block"></i>
      <h5>Accès refusé</h5>
      <p class="text-muted">Seuls les agents RH peuvent modifier le statut d'une demande.</p>
    </div>

    <!-- Not found -->
    <div v-else-if="!demande && !loading" class="text-center py-4">
      <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
      <h5>Demande introuvable</h5>
      <p class="text-muted">Cette demande n'existe pas ou n'est plus accessible.</p>
    </div>

    <!-- Edit form -->
    <div v-else-if="demande && isRH && !loading">
      <!-- Read-only request info -->
      <div class="info-grid mb-4">
        <div class="info-card">
          <div class="info-label"><i class="fas fa-user me-1"></i> Agent</div>
          <div class="info-value">{{ demande.agent?.prenom }} {{ demande.agent?.nom }}</div>
          <div class="info-sub">{{ demande.agent?.id_agent }}</div>
        </div>
        <div class="info-card">
          <div class="info-label"><i class="fas fa-tag me-1"></i> Type</div>
          <div class="info-value">
            <span class="req-badge type">{{ formatType(demande.type) }}</span>
          </div>
        </div>
        <div class="info-card">
          <div class="info-label"><i class="fas fa-calendar me-1"></i> Période</div>
          <div class="info-value">
            {{ formatDate(demande.date_debut) }}
            <template v-if="demande.date_fin"> - {{ formatDate(demande.date_fin) }}</template>
          </div>
        </div>
        <div class="info-card">
          <div class="info-label"><i class="fas fa-align-left me-1"></i> Description</div>
          <div class="info-value info-desc">{{ demande.description }}</div>
        </div>
      </div>

      <hr class="my-4">

      <!-- Edit form -->
      <h6 class="section-title"><i class="fas fa-gavel me-2"></i> Décision</h6>

      <form @submit.prevent="handleSubmit">
        <!-- Statut cards -->
        <div class="statut-grid mb-4">
          <div
            v-for="s in statutOptions" :key="s.value"
            class="statut-card"
            :class="{ active: form.statut === s.value, [s.color]: true }"
            @click="form.statut = s.value"
          >
            <i :class="s.icon" class="statut-icon"></i>
            <span class="statut-label">{{ s.label }}</span>
          </div>
        </div>
        <div v-if="errors.statut" class="text-danger small mb-3">
          <i class="fas fa-exclamation-circle me-1"></i>{{ errors.statut[0] }}
        </div>

        <!-- Remarques -->
        <div class="mb-4">
          <label class="form-label fw-semibold">
            <i class="fas fa-comment-alt me-1 text-muted"></i> Remarques
            <span class="text-muted fw-normal ms-1">(optionnel)</span>
          </label>
          <textarea
            v-model="form.remarques" rows="4"
            class="form-control" :class="{ 'is-invalid': errors.remarques }"
            placeholder="Expliquez votre décision ou ajoutez un commentaire..."
          ></textarea>
          <div v-if="errors.remarques" class="invalid-feedback d-block">{{ errors.remarques[0] }}</div>
        </div>
      </form>
    </div>
  </GenericEditModal>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { get, update } from '@/api/requests'
import GenericEditModal from '@/components/common/GenericEditModal.vue'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  requestId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['close', 'updated'])

const auth = useAuthStore()
const ui = useUiStore()

const loading = ref(false)
const demande = ref(null)
const isRH = ref(false)
const errors = ref({})
const submitting = ref(false)

const form = ref({
  statut: 'en_attente',
  remarques: '',
})

const statutOptions = [
  { value: 'en_attente', label: 'En attente', icon: 'fas fa-hourglass-half', color: 'c-warning' },
  { value: 'approuvé', label: 'Approuvé', icon: 'fas fa-check-circle', color: 'c-success' },
  { value: 'rejeté', label: 'Rejeté', icon: 'fas fa-times-circle', color: 'c-danger' },
  { value: 'annulé', label: 'Annulé', icon: 'fas fa-ban', color: 'c-secondary' },
]

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatType(type) {
  if (!type) return ''
  return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
}

async function handleSubmit() {
  if (!demande.value || !isRH.value) return

  errors.value = {}
  submitting.value = true

  try {
    await update(demande.value.id, {
      statut: form.value.statut,
      remarques: form.value.remarques,
    })
    ui.addToast('Demande modifiée avec succès.', 'success')
    emit('updated', demande.value)
    emit('close')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else if (err.response?.status === 403) {
      ui.addToast(err.response.data.message || 'Action non autorisée.', 'danger')
    } else {
      ui.addToast('Erreur lors de la modification.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

async function loadRequest() {
  if (!props.requestId) {
    demande.value = null
    isRH.value = false
    return
  }

  loading.value = true
  try {
    const { data } = await get(props.requestId)
    demande.value = data.data
    isRH.value = data.isRH

    form.value.statut = demande.value.statut || 'en_attente'
    form.value.remarques = demande.value.remarques || ''
  } catch (err) {
    demande.value = null
    isRH.value = false
    if (err.response?.status === 403) {
      ui.addToast('Vous n\'avez pas accès à cette demande.', 'danger')
    } else {
      ui.addToast('Erreur lors du chargement de la demande.', 'danger')
    }
  } finally {
    loading.value = false
  }
}

// Watch for modal opening
watch(() => props.show, (newVal) => {
  if (newVal) {
    nextTick(() => {
      loadRequest()
    })
  } else {
    // Reset data when modal closes
    demande.value = null
    isRH.value = false
    errors.value = {}
    submitting.value = false
    form.value = { statut: 'en_attente', remarques: '' }
  }
})
</script>

<style scoped>
.section-title {
  font-weight: 700;
  font-size: .95rem;
  color: #1e293b;
  margin-bottom: 1rem;
}

/* Info grid */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.info-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1rem;
  border: 1px solid #f1f5f9;
}

.info-label {
  font-size: .75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: .35rem;
}

.info-value {
  font-size: .9rem;
  font-weight: 600;
  color: #1e293b;
}

.info-sub {
  font-size: .78rem;
  color: #94a3b8;
  margin-top: .15rem;
}

.info-desc {
  font-weight: 400;
  font-size: .85rem;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.req-badge.type {
  background: #e0f2fe;
  color: #075985;
  padding: 3px 10px;
  border-radius: 6px;
  font-size: .8rem;
  font-weight: 600;
}

/* Statut selection grid */
.statut-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .75rem;
}

.statut-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
  padding: 1rem .75rem;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  cursor: pointer;
  transition: all .2s;
  background: #fff;
}

.statut-card:hover {
  border-color: #94a3b8;
}

.statut-card .statut-icon {
  font-size: 1.4rem;
  color: #94a3b8;
  transition: color .2s;
}

.statut-card .statut-label {
  font-size: .78rem;
  font-weight: 600;
  color: #64748b;
}

.statut-card.active.c-warning {
  border-color: #f59e0b;
  background: #fffbeb;
}
.statut-card.active.c-warning .statut-icon {
  color: #f59e0b;
}
.statut-card.active.c-warning .statut-label {
  color: #92400e;
}

.statut-card.active.c-success {
  border-color: #22c55e;
  background: #f0fdf4;
}
.statut-card.active.c-success .statut-icon {
  color: #22c55e;
}
.statut-card.active.c-success .statut-label {
  color: #166534;
}

.statut-card.active.c-danger {
  border-color: #ef4444;
  background: #fef2f2;
}
.statut-card.active.c-danger .statut-icon {
  color: #ef4444;
}
.statut-card.active.c-danger .statut-label {
  color: #991b1b;
}

.statut-card.active.c-secondary {
  border-color: #64748b;
  background: #f8fafc;
}
.statut-card.active.c-secondary .statut-icon {
  color: #64748b;
}
.statut-card.active.c-secondary .statut-label {
  color: #334155;
}

/* Form */
.form-control {
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  font-size: .88rem;
}

.form-control:focus {
  border-color: #0077B5;
  box-shadow: 0 0 0 3px rgba(0,119,181,.1);
}

.form-label {
  font-size: .85rem;
  color: #475569;
}

@media (max-width: 767.98px) {
  .info-grid {
    grid-template-columns: 1fr;
  }

  .statut-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .statut-card {
    padding: .75rem .5rem;
  }

  .statut-card .statut-icon {
    font-size: 1.1rem;
  }

  .statut-card .statut-label {
    font-size: .72rem;
  }
}
</style>