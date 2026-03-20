<template>
  <div class="rh-modern">
    <div class="rh-list-shell">
      <!-- Loading -->
      <LoadingSpinner v-if="loading" message="Chargement du pointage..." />

      <template v-else-if="pointage">
        <section class="rh-hero">
          <div class="row g-2 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-file-alt me-2"></i>Details du pointage</h1>
              <p class="rh-sub">{{ formatDate(pointage.date_pointage) }} - {{ pointage.agent?.prenom }} {{ pointage.agent?.nom }}</p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'rh.pointages.edit', params: { id: pointage.id } }" class="btn-rh main">
                  <i class="fas fa-edit me-1"></i> Modifier
                </router-link>
                <router-link :to="{ name: 'rh.pointages.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="row g-3">
          <div class="col-lg-8">
            <div class="rh-list-card p-3 p-lg-4 mb-3">
              <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Informations pointage</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <small class="text-muted">Date</small>
                  <p class="mb-0 fw-bold">{{ formatDate(pointage.date_pointage) }}</p>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Entree</small>
                  <p class="mb-0">{{ pointage.heure_entree ? formatTime(pointage.heure_entree) : 'Non enregistree' }}</p>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Sortie</small>
                  <p class="mb-0">{{ pointage.heure_sortie ? formatTime(pointage.heure_sortie) : 'Non enregistree' }}</p>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Heures travaillees</small>
                  <p class="mb-0">{{ pointage.heures_travaillees ? pointage.heures_travaillees + ' heures' : '-' }}</p>
                </div>
              </div>
            </div>

            <div v-if="pointage.observations" class="rh-list-card p-3 p-lg-4">
              <h5 class="mb-2"><i class="fas fa-sticky-note me-2"></i>Observations</h5>
              <p class="mb-0">{{ pointage.observations }}</p>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="rh-list-card p-3 mb-3">
              <h6 class="mb-2"><i class="fas fa-user me-2"></i>Agent</h6>
              <p class="mb-1"><strong>{{ pointage.agent?.prenom }} {{ pointage.agent?.nom }}</strong></p>
              <p class="mb-1"><small class="text-muted">Matricule:</small> {{ pointage.agent?.id_agent }}</p>
              <p class="mb-0"><small class="text-muted">Email:</small> {{ pointage.agent?.email }}</p>
            </div>

            <div class="rh-list-card p-3 mb-3">
              <h6 class="mb-2"><i class="fas fa-chart-bar me-2"></i>Resume</h6>
              <p class="mb-1"><small class="text-muted">ID:</small> #{{ pointage.id }}</p>
              <p class="mb-1"><small class="text-muted">Cree le:</small> {{ formatDateTime(pointage.created_at) }}</p>
              <p class="mb-0"><small class="text-muted">Maj:</small> {{ formatDateTime(pointage.updated_at) }}</p>
            </div>

            <div class="rh-list-card p-3">
              <h6 class="mb-2"><i class="fas fa-cog me-2"></i>Actions</h6>
              <button type="button" class="btn btn-danger w-100 btn-sm" @click="confirmDelete">
                <i class="fas fa-trash me-2"></i>Supprimer
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- Not found -->
      <div v-else class="text-center py-5">
        <i class="fas fa-exclamation-triangle fa-4x text-muted mb-3 d-block"></i>
        <h5 class="text-muted">Pointage introuvable</h5>
        <router-link :to="{ name: 'rh.pointages.index' }" class="btn btn-primary mt-2">
          <i class="fas fa-arrow-left me-2"></i>Retour a la liste
        </router-link>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer le pointage"
      message="Etes-vous sur de vouloir supprimer ce pointage ? Cette action est irreversible."
      :loading="deleting"
      @confirm="doDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import * as pointagesApi from '@/api/pointages'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const pointage = ref(null)
const showDeleteModal = ref(false)
const deleting = ref(false)

function formatDate(dateStr) {
    if (!dateStr) return 'N/A'
    const d = new Date(dateStr)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatTime(timeStr) {
    if (!timeStr) return '-'
    if (timeStr.length > 5) {
        const d = new Date(timeStr)
        if (!isNaN(d.getTime())) {
            return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
        }
    }
    return timeStr.substring(0, 5)
}

function formatDateTime(dateStr) {
    if (!dateStr) return 'N/A'
    const d = new Date(dateStr)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function fetchPointage() {
    loading.value = true
    try {
        const { data } = await pointagesApi.get(route.params.id)
        pointage.value = data
    } catch {
        ui.addToast('Erreur lors du chargement du pointage.', 'danger')
        pointage.value = null
    } finally {
        loading.value = false
    }
}

function confirmDelete() {
    showDeleteModal.value = true
}

async function doDelete() {
    deleting.value = true
    try {
        await pointagesApi.remove(pointage.value.id)
        ui.addToast('Pointage supprime avec succes.', 'success')
        showDeleteModal.value = false
        router.push({ name: 'rh.pointages.index' })
    } catch {
        ui.addToast('Erreur lors de la suppression.', 'danger')
    } finally {
        deleting.value = false
    }
}

onMounted(() => {
    fetchPointage()
})
</script>
