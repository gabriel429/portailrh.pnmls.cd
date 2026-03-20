<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement du communique..." />
      </div>

      <template v-else-if="communique">
        <section class="rh-hero">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-bullhorn me-2"></i>{{ communique.titre }}</h1>
              <p class="rh-sub">
                Publie le {{ formatDateTime(communique.created_at) }}
                <template v-if="communique.auteur"> par {{ communique.auteur.name }}</template>
              </p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'rh.communiques.create', query: { edit: communique.id } }" class="btn-rh main">
                  <i class="fas fa-edit me-1"></i> Modifier
                </router-link>
                <router-link :to="{ name: 'rh.communiques.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="row g-3 mt-1">
          <div class="col-lg-8">
            <div class="dash-panel">
              <div class="p-4">
                <!-- Badges -->
                <div class="d-flex gap-2 mb-3">
                  <span :class="urgenceBadge(communique.urgence)">{{ capitalize(communique.urgence) }}</span>
                  <span :class="communique.actif ? 'badge bg-success' : 'badge bg-secondary'">
                    {{ communique.actif ? 'Actif' : 'Inactif' }}
                  </span>
                  <span v-if="communique.date_expiration && isExpired(communique.date_expiration)" class="badge bg-danger">
                    Expire
                  </span>
                </div>

                <!-- Contenu -->
                <div style="white-space: pre-wrap; line-height: 1.8; font-size: 1.05rem;">{{ communique.contenu }}</div>

                <!-- Signataire -->
                <div v-if="communique.signataire" class="mt-4 pt-3 border-top">
                  <small class="text-muted">Signataire</small>
                  <p class="fw-bold mb-0">{{ communique.signataire }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="dash-panel">
              <div class="p-3">
                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Informations</h6>
                <dl class="row mb-0">
                  <dt class="col-sm-5 text-muted">Auteur</dt>
                  <dd class="col-sm-7">{{ communique.auteur?.name ?? 'N/A' }}</dd>

                  <dt class="col-sm-5 text-muted">Date</dt>
                  <dd class="col-sm-7">{{ formatDateTime(communique.created_at) }}</dd>

                  <dt class="col-sm-5 text-muted">Expiration</dt>
                  <dd class="col-sm-7">
                    {{ communique.date_expiration ? formatDate(communique.date_expiration) : 'Illimite' }}
                  </dd>
                </dl>
              </div>
            </div>

            <div class="dash-panel mt-3">
              <div class="p-3">
                <h6 class="mb-2"><i class="fas fa-cog me-2"></i>Actions</h6>
                <button class="btn btn-danger btn-sm w-100" @click="confirmDelete">
                  <i class="fas fa-trash me-2"></i>Supprimer
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>

      <ConfirmModal
        :show="showDeleteModal"
        title="Supprimer le communique"
        message="Etes-vous sur de vouloir supprimer ce communique ?"
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
import { get, remove } from '@/api/communiques'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const communique = ref(null)
const showDeleteModal = ref(false)
const deleting = ref(false)

async function loadCommunique() {
  try {
    const { data } = await get(route.params.id)
    communique.value = data.data
  } catch {
    ui.addToast('Communique introuvable.', 'danger')
    router.push({ name: 'rh.communiques.index' })
  } finally {
    loading.value = false
  }
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function urgenceBadge(urgence) {
  const map = { urgent: 'badge bg-danger', important: 'badge bg-warning text-dark', normal: 'badge bg-info text-white' }
  return map[urgence] || 'badge bg-secondary'
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function isExpired(dateStr) {
  if (!dateStr) return false
  return new Date(dateStr) < new Date()
}

function confirmDelete() {
  showDeleteModal.value = true
}

async function handleDelete() {
  deleting.value = true
  try {
    await remove(communique.value.id)
    ui.addToast('Communique supprime.', 'success')
    router.push({ name: 'rh.communiques.index' })
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  } finally {
    deleting.value = false
    showDeleteModal.value = false
  }
}

onMounted(() => loadCommunique())
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
