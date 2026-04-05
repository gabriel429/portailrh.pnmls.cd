<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement de l'activite..." />
      </div>

      <div v-else-if="accessDenied" class="dash-panel">
        <div class="p-4">
          <div class="pta-denied-icon mb-3"><i class="fas fa-lock"></i></div>
          <h3 class="panel-title mb-2">Acces restreint</h3>
          <p class="text-muted mb-3">{{ accessDeniedMessage }}</p>
          <router-link :to="{ name: 'plan-travail.index' }" class="btn-rh alt">
            <i class="fas fa-arrow-left me-1"></i> Retour au PTA
          </router-link>
        </div>
      </div>

      <template v-else-if="activite">
        <section class="rh-hero">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-calendar-check me-2"></i>{{ activite.titre }}</h1>
              <p class="rh-sub">
                Plan de Travail {{ activite.annee }}
                <template v-if="activite.trimestre"> | {{ activite.trimestre }}</template>
                | {{ activite.niveau_administratif }}
                <template v-if="activite.categorie"> | {{ activite.categorie }}</template>
              </p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'plan-travail.index', query: { annee: activite.annee } }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
                <router-link v-if="canEdit" :to="{ name: 'plan-travail.edit', params: { id: activite.id } }" class="btn-rh main">
                  <i class="fas fa-edit me-1"></i> Modifier
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="row mt-3 g-3">
          <!-- Colonne gauche: Detail -->
          <div class="col-lg-7">
            <div class="dash-panel">
              <header class="panel-head">
                <div>
                  <h3 class="panel-title"><i class="fas fa-info-circle me-2 text-primary"></i>Details de l'activite</h3>
                </div>
                <div class="d-flex gap-2">
                  <span :class="statutBadge(activite.statut)">{{ statutLabel(activite.statut) }}</span>
                </div>
              </header>
              <div class="p-3">
                <dl class="row mb-0">
                  <dt class="col-sm-4 text-muted">Objectif</dt>
                  <dd class="col-sm-8">{{ activite.objectif || 'Non renseigne' }}</dd>

                  <dt class="col-sm-4 text-muted">Rubrique</dt>
                  <dd class="col-sm-8">{{ activite.categorie || 'Non renseignee' }}</dd>

                  <dt class="col-sm-4 text-muted">Resultat attendu</dt>
                  <dd class="col-sm-8">{{ activite.resultat_attendu || 'Non renseigne' }}</dd>

                  <dt class="col-sm-4 text-muted">Responsable</dt>
                  <dd class="col-sm-8">{{ activite.responsable_code || 'Non renseigne' }}</dd>

                  <dt class="col-sm-4 text-muted">Cout en CDF</dt>
                  <dd class="col-sm-8">{{ activite.cout_cdf !== null && activite.cout_cdf !== undefined ? formatCurrency(activite.cout_cdf) + ' CDF' : 'Non renseigne' }}</dd>

                  <dt class="col-sm-4 text-muted">Niveau</dt>
                  <dd class="col-sm-8">
                    {{ activite.niveau_administratif }}
                    <template v-if="activite.departement"> - {{ activite.departement.nom }}</template>
                    <template v-if="provinceSummary"> - {{ provinceSummary }}</template>
                    <template v-if="activite.localite"> - {{ activite.localite.nom }}</template>
                  </dd>

                  <dt class="col-sm-4 text-muted">Annee</dt>
                  <dd class="col-sm-8">{{ activite.annee }}</dd>

                  <dt class="col-sm-4 text-muted">Validation</dt>
                  <dd class="col-sm-8">{{ validationLabel(activite.validation_niveau) }}</dd>

                  <dt class="col-sm-4 text-muted">Trimestre</dt>
                  <dd class="col-sm-8">{{ triLabel(activite.trimestre) }}</dd>

                  <dt class="col-sm-4 text-muted">Chronogramme</dt>
                  <dd class="col-sm-8">{{ chronogrammeLabel(activite) }}</dd>

                  <template v-if="activite.date_debut || activite.date_fin">
                    <dt class="col-sm-4 text-muted">Periode</dt>
                    <dd class="col-sm-8">
                      {{ formatDate(activite.date_debut) || '?' }}
                      &rarr;
                      {{ formatDate(activite.date_fin) || '?' }}
                      <span v-if="isOverdue" class="badge bg-danger ms-1">En retard</span>
                    </dd>
                  </template>

                  <dt class="col-sm-4 text-muted">Cree par</dt>
                  <dd class="col-sm-8">{{ activite.createur?.nom_complet ?? 'N/A' }}</dd>

                  <dt class="col-sm-4 text-muted">Date de creation</dt>
                  <dd class="col-sm-8">{{ formatDateTime(activite.created_at) }}</dd>
                </dl>

                <!-- Barre de progression -->
                <div class="mt-3 p-3 rounded" style="background: #f0f4f8;">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold">Progression</span>
                    <span class="fw-bold" :class="activite.pourcentage >= 100 ? 'text-success' : 'text-primary'">{{ activite.pourcentage }}%</span>
                  </div>
                  <div class="progress" style="height: 12px;">
                    <div class="progress-bar" :class="activite.pourcentage >= 100 ? 'bg-success' : 'bg-primary'" :style="{ width: activite.pourcentage + '%' }"></div>
                  </div>
                </div>

                <template v-if="activite.description">
                  <hr>
                  <h6 class="fw-bold mb-2">Description</h6>
                  <div style="white-space: pre-wrap;">{{ activite.description }}</div>
                </template>

                <template v-if="activite.observations">
                  <hr>
                  <h6 class="fw-bold mb-2">Observations</h6>
                  <div style="white-space: pre-wrap;" class="text-muted">{{ activite.observations }}</div>
                </template>

                <template v-if="activite.taches?.length">
                  <hr>
                  <h6 class="fw-bold mb-2">Taches liees</h6>
                  <div class="d-flex flex-column gap-2">
                    <router-link
                      v-for="tache in activite.taches"
                      :key="tache.id"
                      :to="{ name: 'taches.show', params: { id: tache.id } }"
                      class="d-flex justify-content-between align-items-center text-decoration-none border rounded p-2"
                    >
                      <div>
                        <strong class="text-dark">{{ tache.titre }}</strong>
                        <div v-if="tache.agent?.nom_complet" class="small text-muted">{{ tache.agent.nom_complet }}</div>
                      </div>
                      <span :class="statutBadge(tache.statut)">{{ statutLabel(tache.statut) }}</span>
                    </router-link>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- Colonne droite: Mise a jour rapide -->
          <div class="col-lg-5">
            <div v-if="canUpdateStatut && activite.statut !== 'terminee'" class="dash-panel">
              <header class="panel-head">
                <div>
                  <h3 class="panel-title"><i class="fas fa-sync-alt me-2 text-warning"></i>Mise a jour rapide</h3>
                </div>
              </header>
              <div class="p-3">
                <form @submit.prevent="handleUpdateStatut">
                  <div class="mb-3">
                    <label for="update_statut" class="form-label fw-bold">Statut</label>
                    <select v-model="statutForm.statut" class="form-select" id="update_statut" required>
                      <option value="planifiee">Planifiee</option>
                      <option value="en_cours">En cours</option>
                      <option value="terminee">Terminee</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label for="update_pourcentage" class="form-label fw-bold">Progression (%) : {{ statutForm.pourcentage }}%</label>
                    <input v-model.number="statutForm.pourcentage" type="range" class="form-range" id="update_pourcentage" min="0" max="100" step="5">
                  </div>

                  <div class="mb-3">
                    <label for="update_observations" class="form-label fw-bold">Observations</label>
                    <textarea v-model="statutForm.observations" class="form-control" id="update_observations" rows="3"></textarea>
                  </div>

                  <button type="submit" class="btn btn-warning w-100" :disabled="updatingStatut">
                    <span v-if="updatingStatut" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="fas fa-save me-1"></i> Mettre a jour
                  </button>
                </form>
              </div>
            </div>

            <div v-if="canEdit" class="dash-panel" :class="canUpdateStatut && activite.statut !== 'terminee' ? 'mt-3' : ''">
              <header class="panel-head">
                <div>
                  <h3 class="panel-title"><i class="fas fa-cog me-2 text-secondary"></i>Actions</h3>
                </div>
              </header>
              <div class="p-3">
                <router-link :to="{ name: 'plan-travail.edit', params: { id: activite.id } }" class="btn btn-outline-primary w-100 mb-2">
                  <i class="fas fa-edit me-1"></i> Modifier completement
                </router-link>
                <button class="btn btn-outline-danger w-100" @click="confirmDelete">
                  <i class="fas fa-trash me-1"></i> Supprimer
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>

      <ConfirmModal
        :show="showDeleteModal"
        title="Supprimer l'activite"
        message="Supprimer cette activite du plan de travail ?"
        :loading="deleting"
        @confirm="handleDelete"
        @cancel="showDeleteModal = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { get, remove, updateStatut } from '@/api/planTravail'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const activite = ref(null)
const accessDenied = ref(false)
const accessDeniedMessage = ref("Vous ne pouvez consulter que le PTA de votre departement.")
const canEdit = ref(false)
const canUpdateStatut = ref(false)

const updatingStatut = ref(false)
const showDeleteModal = ref(false)
const deleting = ref(false)

const statutForm = ref({ statut: '', pourcentage: 0, observations: '' })

const isOverdue = computed(() => {
  if (!activite.value?.date_fin || activite.value.statut === 'terminee') return false
  return new Date(activite.value.date_fin) < new Date()
})

const provinceSummary = computed(() => {
  if (!activite.value) return ''
  const names = (activite.value.provinces || []).map((province) => province.nom).filter(Boolean)
  if (names.length) return names.join(', ')
  return activite.value.province?.nom || ''
})

async function loadActivite() {
  try {
    accessDenied.value = false
    const { data } = await get(route.params.id)
    activite.value = data.data
    canEdit.value = data.canEdit
    canUpdateStatut.value = data.canUpdateStatut
    statutForm.value = {
      statut: data.data.statut,
      pourcentage: data.data.pourcentage,
      observations: data.data.observations || '',
    }
  } catch (err) {
    if (err.response?.status === 403) {
      accessDenied.value = true
      accessDeniedMessage.value = err.response?.data?.message || 'Vous ne pouvez consulter que le PTA de votre departement.'
      ui.addToast(accessDeniedMessage.value, 'warning')
    } else {
      ui.addToast('Activite introuvable.', 'danger')
      router.push({ name: 'plan-travail.index' })
    }
  } finally {
    loading.value = false
  }
}

async function handleUpdateStatut() {
  updatingStatut.value = true
  try {
    const { data } = await updateStatut(route.params.id, statutForm.value)
    activite.value = data.data
    ui.addToast('Statut mis a jour.', 'success')
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur.', 'danger')
  } finally {
    updatingStatut.value = false
  }
}

function confirmDelete() {
  showDeleteModal.value = true
}

async function handleDelete() {
  deleting.value = true
  try {
    await remove(activite.value.id)
    ui.addToast('Activite supprimee.', 'success')
    router.push({ name: 'plan-travail.index' })
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur.', 'danger')
  } finally {
    deleting.value = false
    showDeleteModal.value = false
  }
}

function triLabel(tri) {
  const map = { T1: '1er Trimestre', T2: '2e Trimestre', T3: '3e Trimestre', T4: '4e Trimestre' }
  return map[tri] || 'Annuel'
}

function statutBadge(statut) {
  const map = { terminee: 'badge bg-success', en_cours: 'badge bg-primary', planifiee: 'badge bg-secondary' }
  return map[statut] || 'badge bg-secondary'
}

function statutLabel(statut) {
  const map = { terminee: 'Terminee', en_cours: 'En cours', planifiee: 'Planifiee' }
  return map[statut] || statut
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' a ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function validationLabel(value) {
  const map = {
    direction: 'Direction',
    coordination_nationale: 'Coordination nationale',
    coordination_provinciale: 'Coordination provinciale',
  }
  return map[value] || 'Non renseigne'
}

function chronogrammeLabel(activity) {
  const values = []
  if (activity.trimestre_1) values.push('T1')
  if (activity.trimestre_2) values.push('T2')
  if (activity.trimestre_3) values.push('T3')
  if (activity.trimestre_4) values.push('T4')
  if (!values.length && activity.trimestre) values.push(activity.trimestre)
  return values.length ? values.join(', ') : 'Annuel'
}

function formatCurrency(value) {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount)
}

onMounted(() => loadActivite())
</script>

<style scoped>
.pta-denied-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(234, 88, 12, .12);
  color: #c2410c;
  font-size: 1.2rem;
}
@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    dl.row dt { font-size: .8rem; }
    dl.row dd { font-size: .85rem; margin-bottom: .6rem; }
    .badge { font-size: .7rem; }
}
</style>
