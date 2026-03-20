<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement du signalement..." />
      </div>

      <template v-else-if="signalement">
        <section class="rh-hero">
          <div class="row g-2 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-file-circle-exclamation me-2"></i>Detail du signalement</h1>
              <p class="rh-sub">Dossier #{{ signalement.id }} | {{ capitalize(signalement.type) }}</p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'signalements.edit', params: { id: signalement.id } }" class="btn-rh main">
                  <i class="fas fa-edit me-1"></i> Modifier
                </router-link>
                <router-link :to="{ name: 'signalements.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="row g-3 mt-1">
          <div class="col-lg-8">
            <div class="rh-list-card p-3 p-lg-4 mb-3">
              <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Informations incident</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <small class="text-muted">Type</small>
                  <p class="mb-0 fw-bold">{{ capitalize(signalement.type) }}</p>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Severite</small>
                  <p class="mb-0">
                    <span :class="severiteChip(signalement.severite)">{{ capitalize(signalement.severite) }}</span>
                  </p>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Statut</small>
                  <p class="mb-0">
                    <span :class="statutChip(signalement.statut)">{{ statutLabel(signalement.statut) }}</span>
                  </p>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Date creation</small>
                  <p class="mb-0">{{ formatDateTime(signalement.created_at) }}</p>
                </div>
                <div class="col-12">
                  <small class="text-muted">Description</small>
                  <p class="mb-0">{{ signalement.description }}</p>
                </div>
              </div>
            </div>

            <div v-if="signalement.observations" class="rh-list-card p-3 p-lg-4">
              <h5 class="mb-2"><i class="fas fa-sticky-note me-2"></i>Observations</h5>
              <p class="mb-0">{{ signalement.observations }}</p>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="rh-list-card p-3 mb-3">
              <h6 class="mb-2"><i class="fas fa-user me-2"></i>Agent concerne</h6>
              <p class="mb-1"><strong>{{ signalement.agent?.prenom }} {{ signalement.agent?.nom }}</strong></p>
              <p class="mb-1"><small class="text-muted">ID:</small> {{ signalement.agent?.id_agent }}</p>
              <p class="mb-0"><small class="text-muted">Email:</small> {{ signalement.agent?.email ?? 'N/A' }}</p>
            </div>

            <div class="rh-list-card p-3">
              <h6 class="mb-2"><i class="fas fa-cog me-2"></i>Actions</h6>
              <button class="btn btn-danger btn-sm w-100" @click="confirmDelete">
                <i class="fas fa-trash me-2"></i>Supprimer
              </button>
            </div>
          </div>
        </div>
      </template>

      <ConfirmModal
        :show="showDeleteModal"
        title="Supprimer le signalement"
        message="Etes-vous sur de supprimer ce signalement ?"
        :loading="deleting"
        @confirm="handleDelete"
        @cancel="showDeleteModal = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { get, remove } from '@/api/signalements'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const signalement = ref(null)
const showDeleteModal = ref(false)
const deleting = ref(false)

async function loadSignalement() {
  try {
    const { data } = await get(route.params.id)
    signalement.value = data.data
  } catch {
    ui.addToast('Signalement introuvable.', 'danger')
    router.push({ name: 'signalements.index' })
  } finally {
    loading.value = false
  }
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function severiteChip(sev) {
  const map = { haute: 'status-chip st-bad', moyenne: 'status-chip st-mid', basse: 'status-chip st-ok' }
  return map[sev] || 'status-chip st-neutral'
}

function statutChip(statut) {
  const map = { ouvert: 'status-chip st-bad', en_cours: 'status-chip st-mid', 'résolu': 'status-chip st-ok', 'fermé': 'status-chip st-neutral' }
  return map[statut] || 'status-chip st-neutral'
}

function statutLabel(statut) {
  const map = { ouvert: 'Ouvert', en_cours: 'En cours', 'résolu': 'Resolu', 'fermé': 'Ferme' }
  return map[statut] || capitalize(statut)
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function confirmDelete() {
  showDeleteModal.value = true
}

async function handleDelete() {
  deleting.value = true
  try {
    await remove(signalement.value.id)
    ui.addToast('Signalement supprime avec succes.', 'success')
    router.push({ name: 'signalements.index' })
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  } finally {
    deleting.value = false
    showDeleteModal.value = false
  }
}

onMounted(() => loadSignalement())
</script>

<style scoped>
@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    dl.row dt { font-size: .8rem; }
    dl.row dd { font-size: .85rem; margin-bottom: .6rem; }
    .badge { font-size: .7rem; }
}
</style>
