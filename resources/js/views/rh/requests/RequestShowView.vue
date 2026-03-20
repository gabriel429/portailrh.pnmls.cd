<template>
  <div class="py-4">
    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
    </div>

    <div v-else-if="demande" class="row justify-content-center" id="demande-print-area">
      <div class="col-lg-8">
        <!-- Header -->
        <div class="mb-4">
          <router-link :to="{ name: 'requests.index' }" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Retour
          </router-link>
          <h4 class="mt-2"><i class="fas fa-file-alt me-2"></i> Demande #{{ demande.id }}</h4>
        </div>

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4">

            <!-- Status & date -->
            <div class="d-flex justify-content-between align-items-start mb-4">
              <div>
                <h6 class="text-muted mb-1">Statut</h6>
                <span :class="statusBadgeClass(demande.statut)" style="font-size:1em;">
                  {{ statusLabel(demande.statut) }}
                </span>
              </div>
              <div>
                <small class="text-muted">Creee le {{ formatDateTime(demande.created_at) }}</small>
              </div>
            </div>

            <hr>

            <!-- Agent info -->
            <div class="mb-4">
              <h6 class="mb-3">Agent</h6>
              <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3"
                     style="width:40px;height:40px;font-size:.8rem;font-weight:700;">
                  {{ initials(demande.agent) }}
                </div>
                <div>
                  <strong>{{ demande.agent?.prenom }} {{ demande.agent?.nom }}</strong>
                  <br>
                  <small class="text-muted">{{ demande.agent?.id_agent }}</small>
                  <br>
                  <small class="text-muted">{{ demande.agent?.poste_actuel }}</small>
                </div>
              </div>
            </div>

            <hr>

            <!-- Request details -->
            <div class="row mb-4">
              <div class="col-md-6">
                <h6 class="mb-2">Type</h6>
                <span class="badge bg-info text-dark">{{ formatType(demande.type) }}</span>
              </div>
              <div class="col-md-6">
                <h6 class="mb-2">Periode</h6>
                <p class="mb-0">
                  Du <strong>{{ formatDate(demande.date_debut) }}</strong>
                  <template v-if="demande.date_fin">
                    au <strong>{{ formatDate(demande.date_fin) }}</strong>
                  </template>
                </p>
              </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
              <h6 class="mb-2">Description</h6>
              <div class="bg-light p-3 rounded">{{ demande.description }}</div>
            </div>

            <!-- Remarques -->
            <div v-if="demande.remarques" class="mb-4">
              <h6 class="mb-2">Remarques</h6>
              <div class="alert alert-info mb-0">{{ demande.remarques }}</div>
            </div>

            <!-- Lettre de demande -->
            <div v-if="demande.lettre_demande" class="mb-4">
              <h6 class="mb-2"><i class="fas fa-paperclip me-1"></i> Lettre de demande</h6>
              <div class="d-flex align-items-center gap-2 p-3 bg-light rounded">
                <i class="fas fa-file-alt text-primary" style="font-size:1.3rem;"></i>
                <div class="flex-grow-1">
                  <span class="fw-semibold">{{ fileName(demande.lettre_demande) }}</span>
                </div>
                <a :href="storageUrl(demande.lettre_demande)" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-download me-1"></i> Telecharger
                </a>
              </div>
            </div>

            <hr>

            <!-- Action buttons -->
            <div class="d-flex gap-2 justify-content-end flex-wrap">
              <router-link :to="{ name: 'requests.index' }" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Retour
              </router-link>

              <!-- RH: approve / reject button (links to edit page) -->
              <router-link
                v-if="isRH"
                :to="{ name: 'requests.edit', params: { id: demande.id } }"
                class="btn btn-warning"
              >
                <i class="fas fa-edit me-2"></i> Modifier le statut
              </router-link>

              <!-- Print button -->
              <button class="btn btn-success" @click="handlePrint">
                <i class="fas fa-print me-2"></i> Imprimer
              </button>

              <!-- Delete (own request & en_attente, or RH) -->
              <button
                v-if="canDelete"
                class="btn btn-danger"
                @click="showDeleteModal = true"
              >
                <i class="fas fa-trash me-2"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error / not found -->
    <div v-else class="text-center py-5">
      <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
      <h5>Demande introuvable</h5>
      <router-link :to="{ name: 'requests.index' }" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left me-2"></i> Retour aux demandes
      </router-link>
    </div>

    <!-- Confirm delete modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer la demande"
      message="Etes-vous sur de vouloir supprimer cette demande ?"
      :loading="deleting"
      @confirm="handleDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { get, remove } from '@/api/requests'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()

const loading = ref(true)
const demande = ref(null)
const isRH = ref(false)
const isOwner = ref(false)
const showDeleteModal = ref(false)
const deleting = ref(false)

const canDelete = computed(() => {
  if (!demande.value) return false
  if (isRH.value) return true
  return isOwner.value && demande.value.statut === 'en_attente'
})

function initials(agent) {
  if (!agent) return ''
  return ((agent.prenom || '').charAt(0) + (agent.nom || '').charAt(0)).toUpperCase()
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' a ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function formatType(type) {
  if (!type) return ''
  return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
}

function statusLabel(statut) {
  const labels = {
    en_attente: 'En attente',
    'approuvé': 'Approuve',
    'rejeté': 'Rejete',
    'annulé': 'Annule',
  }
  return labels[statut] || statut
}

function statusBadgeClass(statut) {
  const classes = {
    en_attente: 'badge bg-warning text-dark',
    'approuvé': 'badge bg-success',
    'rejeté': 'badge bg-danger',
    'annulé': 'badge bg-secondary',
  }
  return classes[statut] || 'badge bg-secondary'
}

function fileName(path) {
  if (!path) return ''
  return path.split('/').pop()
}

function storageUrl(path) {
  return '/storage/' + path
}

function handlePrint() {
  window.print()
}

async function handleDelete() {
  deleting.value = true
  try {
    await remove(demande.value.id)
    ui.addToast('Demande supprimee avec succes.', 'success')
    showDeleteModal.value = false
    router.push({ name: 'requests.index' })
  } catch (err) {
    const msg = err.response?.data?.message || 'Erreur lors de la suppression.'
    ui.addToast(msg, 'danger')
  } finally {
    deleting.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await get(route.params.id)
    demande.value = data.data
    isRH.value = data.isRH
    isOwner.value = data.isOwner
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast('Vous n\'avez pas acces a cette demande.', 'danger')
      router.push({ name: 'requests.index' })
    } else {
      ui.addToast('Erreur lors du chargement de la demande.', 'danger')
    }
  } finally {
    loading.value = false
  }
})
</script>
