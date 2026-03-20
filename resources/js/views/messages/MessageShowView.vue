<template>
  <div class="container py-4" style="max-width: 800px;">
    <!-- Bouton retour -->
    <router-link :to="{ name: 'dashboard' }" class="btn btn-outline-secondary btn-sm mb-3">
      <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
    </router-link>

    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement du message..." />
    </div>

    <template v-else-if="message">
      <!-- Carte message avec logo PNMLS en fond -->
      <div class="card shadow-sm border-0" style="overflow: hidden; position: relative;">
        <!-- Logo en arriere-plan watermark -->
        <div class="watermark-wrapper" style="
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          opacity: 0.06;
          pointer-events: none;
          z-index: 0;
        ">
          <img src="/images/logo-pnmls.png" alt="" class="watermark-logo" style="width: 350px; height: auto;">
        </div>

        <!-- En-tete du message -->
        <div class="card-header border-0 text-white" style="background: linear-gradient(135deg, #0077B5, #005a87); position: relative; z-index: 1;">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; flex-shrink: 0;">
              <i class="fas fa-envelope-open-text fa-lg text-white"></i>
            </div>
            <div>
              <h4 class="mb-1">{{ message.sujet }}</h4>
              <small class="opacity-75">
                <i class="fas fa-user me-1"></i> {{ message.sender?.name ?? 'Direction RH' }}
                &bull;
                <i class="fas fa-calendar me-1"></i> {{ formatDateTime(message.created_at) }}
              </small>
            </div>
          </div>
        </div>

        <!-- Corps du message -->
        <div class="card-body" style="position: relative; z-index: 1; min-height: 250px; padding: 2rem;">
          <div class="mb-3">
            <span :class="message.lu ? 'badge bg-success' : 'badge bg-warning text-dark'">
              <i class="fas" :class="message.lu ? 'fa-check-double' : 'fa-envelope'" style="margin-right: 4px;"></i>
              {{ message.lu ? 'Lu' : 'Non lu' }}
            </span>
          </div>
          <div style="font-size: 1.05rem; line-height: 1.8; white-space: pre-wrap;">{{ message.contenu }}</div>
        </div>

        <!-- Pied de page -->
        <div class="card-footer bg-light border-0 text-muted small" style="position: relative; z-index: 1;">
          <div class="d-flex justify-content-between align-items-center">
            <span><i class="fas fa-building me-1"></i> Programme National Multisectoriel de Lutte contre le Sida</span>
            <span><i class="fas fa-clock me-1"></i> Recu {{ timeAgo(message.created_at) }}</span>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="text-center py-5 text-muted">
      <i class="fas fa-envelope fa-4x mb-3 d-block"></i>
      <h5>Message introuvable</h5>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const message = ref(null)

async function loadMessage() {
  try {
    const { data } = await client.get(`/messages/${route.params.id}`)
    message.value = data.data
  } catch {
    ui.addToast('Message introuvable.', 'danger')
    router.push({ name: 'dashboard' })
  } finally {
    loading.value = false
  }
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' a ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const now = new Date()
  const diffMs = now - d
  const diffMin = Math.floor(diffMs / 60000)
  const diffH = Math.floor(diffMs / 3600000)
  const diffD = Math.floor(diffMs / 86400000)
  if (diffMin < 1) return 'A l\'instant'
  if (diffMin < 60) return `Il y a ${diffMin} min`
  if (diffH < 24) return `Il y a ${diffH}h`
  if (diffD < 30) return `Il y a ${diffD}j`
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => loadMessage())
</script>

<style scoped>
@media (max-width: 767.98px) {
    /* Fix logo watermark overflow on small screens */
    .watermark-logo {
        max-width: 100% !important;
        width: 100% !important;
    }

    /* Reduce card body padding */
    .card-body {
        padding: 1rem !important;
    }

    /* Slightly compact header title */
    .card-header h4 {
        font-size: 1.1rem;
    }

    /* Stack footer items vertically to avoid overflow */
    .card-footer .d-flex {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>
