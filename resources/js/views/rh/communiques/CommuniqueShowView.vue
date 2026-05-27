<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement du communique..." />
      </div>

      <template v-else-if="communique">
        <section class="rh-hero">
          <div class="ep-detail-hero-row">
            <div>
              <h1 class="rh-title"><i class="fas fa-bullhorn me-2"></i>{{ communique.titre }}</h1>
              <p class="rh-sub">
                Publié le {{ formatDateTime(communique.created_at) }}
                <template v-if="communique.auteur"> par {{ communique.auteur.name }}</template>
              </p>
            </div>
            <div>
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

        <div class="ep-detail-layout">
          <div class="ep-detail-main">
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

                <!-- Pièces jointes -->
                <div v-if="communique.attachments?.length" class="attachments-card mt-4 pt-3 border-top">
                  <h6><i class="fas fa-paperclip me-2"></i>Pièces jointes</h6>
                  <div class="attachments-list">
                    <a
                      v-for="file in communique.attachments"
                      :key="file.id"
                      class="attachment-link"
                      :href="file.url"
                      target="_blank"
                      rel="noopener"
                    >
                      <i class="fas fa-file-lines"></i>
                      <span>{{ file.name }}</span>
                      <small>{{ formatFileSize(file.size) }}</small>
                    </a>
                  </div>
                </div>

                <!-- Signataire -->
                <div v-if="communique.signataire" class="mt-4 pt-3 border-top">
                  <small class="text-muted">Signataire</small>
                  <p class="fw-bold mb-0">{{ communique.signataire }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="ep-detail-side">
            <div class="dash-panel">
              <div class="p-3">
                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Informations</h6>
                <dl class="ep-dl-grid mb-0">
                  <dt class="text-muted">Auteur</dt>
                  <dd>{{ communique.auteur?.name ?? 'N/A' }}</dd>

                  <dt class="text-muted">Date</dt>
                  <dd>{{ formatDateTime(communique.created_at) }}</dd>

                  <dt class="text-muted">Expiration</dt>
                  <dd>
                    {{ communique.date_expiration ? formatDate(communique.date_expiration) : 'Illimite' }}
                  </dd>

                  <dt class="text-muted">Pièces jointes</dt>
                  <dd>{{ communique.attachments?.length || 0 }}</dd>
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
        message="Êtes-vous sûr de vouloir supprimer ce communiqué ?"
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
import { get, markRead, remove } from '@/api/communiques'
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
    await markCommuniqueRead()
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

function formatFileSize(bytes) {
  const size = Number(bytes || 0)
  if (!size) return ''
  if (size < 1024 * 1024) return `${Math.round(size / 1024)} Ko`
  return `${(size / (1024 * 1024)).toFixed(1)} Mo`
}

function confirmDelete() {
  showDeleteModal.value = true
}

async function handleDelete() {
  deleting.value = true
  try {
    await remove(communique.value.id)
    ui.addToast('Communiqué supprimé.', 'success')
    router.push({ name: 'rh.communiques.index' })
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  } finally {
    deleting.value = false
    showDeleteModal.value = false
  }
}

async function markCommuniqueRead() {
  if (!communique.value || communique.value.has_read) return

  try {
    await markRead(communique.value.id)
    communique.value.has_read = true
    communique.value.read_count = Number(communique.value.read_count || 0) + 1
  } catch {
    // Le suivi de lecture ne doit pas bloquer l'affichage du communique.
  }
}

onMounted(() => loadCommunique())
</script>

<style scoped>
.attachments-card h6 {
    font-weight: 800;
    color: #1e293b;
}
.attachments-list {
    display: grid;
    gap: .55rem;
}
.attachment-link {
    display: grid;
    grid-template-columns: 24px minmax(0, 1fr) auto;
    align-items: center;
    gap: .65rem;
    padding: .7rem .8rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fbfc;
    color: #1e293b;
    text-decoration: none;
}
.attachment-link:hover {
    border-color: #0077B5;
    background: #f0f9ff;
}
.attachment-link span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 700;
}
.attachment-link small {
    color: #64748b;
    font-size: .78rem;
}
@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    .ep-dl-grid dt { font-size: .8rem; }
    .ep-dl-grid dd { font-size: .85rem; margin-bottom: .6rem; }
    .badge { font-size: .7rem; }
    .attachment-link {
        grid-template-columns: 24px minmax(0, 1fr);
    }
    .attachment-link small {
        grid-column: 2;
    }
}
</style>
