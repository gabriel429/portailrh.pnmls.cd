<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement de la tache..." />
      </div>

      <template v-else-if="tache">
        <section class="rh-hero">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>{{ tache.titre }}</h1>
              <p class="rh-sub">
                Assignee a {{ tache.agent?.nom_complet }} par {{ tache.createur?.nom_complet }}
              </p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'taches.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="row mt-3 g-3">
          <!-- Colonne gauche: Detail + Changement statut -->
          <div class="col-lg-7">
            <!-- Detail de la tache -->
            <div class="dash-panel">
              <header class="panel-head">
                <div>
                  <h3 class="panel-title"><i class="fas fa-info-circle me-2 text-primary"></i>Details</h3>
                </div>
                <div class="d-flex gap-2 align-items-center">
                  <span :class="prioriteBadge(tache.priorite)">{{ capitalize(tache.priorite) }}</span>
                  <span :class="statutBadge(tache.statut)">{{ statutLabel(tache.statut) }}</span>
                </div>
              </header>
              <div class="p-3">
                <dl class="row mb-0">
                  <dt class="col-sm-4 text-muted">Origine</dt>
                  <dd class="col-sm-8">
                    {{ tache.source_type === 'pta' ? 'Issue du PTA' : 'Hors PTA' }}
                    <template v-if="tache.activite_plan">
                      <br><small class="text-muted">{{ tache.activite_plan.titre }} - {{ tache.activite_plan.annee }}</small>
                    </template>
                  </dd>

                  <dt class="col-sm-4 text-muted">Source</dt>
                  <dd class="col-sm-8">{{ sourceEmetteurLabel(tache.source_emetteur) }}</dd>

                  <dt class="col-sm-4 text-muted">Creee par</dt>
                  <dd class="col-sm-8">{{ tache.createur?.nom_complet }}</dd>

                  <template v-if="tache.date_tache">
                    <dt class="col-sm-4 text-muted">Date de la tache</dt>
                    <dd class="col-sm-8">{{ formatDate(tache.date_tache) }}</dd>
                  </template>

                  <dt class="col-sm-4 text-muted">Assignee a</dt>
                  <dd class="col-sm-8">{{ tache.agent?.nom_complet }}</dd>

                  <dt class="col-sm-4 text-muted">Date de creation</dt>
                  <dd class="col-sm-8">{{ formatDateTime(tache.created_at) }}</dd>

                  <template v-if="tache.date_echeance">
                    <dt class="col-sm-4 text-muted">Echeance</dt>
                    <dd class="col-sm-8">
                      {{ formatDate(tache.date_echeance) }}
                      <span v-if="isOverdue" class="badge bg-danger ms-1">En retard</span>
                    </dd>
                  </template>
                </dl>

                <template v-if="tache.description">
                  <hr>
                  <div style="white-space: pre-wrap;">{{ tache.description }}</div>
                </template>
              </div>
            </div>

            <!-- Formulaire changement de statut (agent assigne uniquement) -->
            <div v-if="isAssigne && tache.statut !== 'terminee'" class="dash-panel mt-3">
              <header class="panel-head">
                <div>
                  <h3 class="panel-title"><i class="fas fa-exchange-alt me-2 text-warning"></i>Mettre a jour le statut</h3>
                </div>
              </header>
              <div class="p-3">
                <form @submit.prevent="handleUpdateStatut">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label for="statut" class="form-label fw-bold">Nouveau statut <span class="text-danger">*</span></label>
                      <select v-model="statutForm.statut" class="form-select" id="statut" required>
                        <option value="en_cours" :selected="tache.statut === 'nouvelle'">En cours</option>
                        <option value="terminee">Terminee</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label for="contenu_statut" class="form-label fw-bold">Commentaire <span class="text-danger">*</span></label>
                      <textarea v-model="statutForm.contenu" class="form-control" id="contenu_statut" rows="3" required
                                placeholder="Decrivez l'avancement ou le resultat..."></textarea>
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-warning" :disabled="updatingStatut">
                        <span v-if="updatingStatut" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="fas fa-sync-alt me-1"></i> Mettre a jour
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <!-- Formulaire commentaire libre -->
            <div class="dash-panel mt-3">
              <header class="panel-head">
                <div>
                  <h3 class="panel-title"><i class="fas fa-comment me-2 text-info"></i>Ajouter un commentaire</h3>
                </div>
              </header>
              <div class="p-3">
                <form @submit.prevent="handleAddComment">
                  <div class="mb-3">
                    <textarea v-model="commentForm.contenu" class="form-control" rows="2" required
                              placeholder="Votre commentaire..."></textarea>
                  </div>
                  <button type="submit" class="btn btn-sm btn-outline-primary" :disabled="addingComment">
                    <span v-if="addingComment" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="fas fa-paper-plane me-1"></i> Envoyer
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Colonne droite: Timeline commentaires -->
          <div class="col-lg-5">
            <div class="dash-panel">
              <header class="panel-head">
                <div>
                  <h3 class="panel-title"><i class="fas fa-history me-2 text-success"></i>Historique</h3>
                  <p class="panel-sub">{{ commentaires.length }} commentaire{{ commentaires.length > 1 ? 's' : '' }}</p>
                </div>
              </header>
              <div class="p-3">
                <template v-if="commentaires.length">
                  <div
                    v-for="comm in commentaires"
                    :key="comm.id"
                    class="border-start border-3 rounded p-3 mb-2"
                    :class="comm.nouveau_statut ? 'border-warning' : 'border-info'"
                  >
                    <div v-if="comm.ancien_statut && comm.nouveau_statut" class="mb-2">
                      <span :class="statutBadge(comm.ancien_statut)">{{ statutLabel(comm.ancien_statut) }}</span>
                      <i class="fas fa-arrow-right mx-1 text-muted" style="font-size: 0.7rem;"></i>
                      <span :class="statutBadge(comm.nouveau_statut)">{{ statutLabel(comm.nouveau_statut) }}</span>
                    </div>
                    <p class="mb-1">{{ comm.contenu }}</p>
                    <small class="text-muted">
                      <i class="fas fa-user me-1"></i>{{ comm.agent?.nom_complet }}
                      &bull; {{ formatDateTime(comm.created_at) }}
                    </small>
                  </div>
                </template>
                <div v-else class="text-center py-3 text-muted">
                  <i class="fas fa-comments fa-2x mb-2 d-block"></i>
                  <p class="mb-0">Aucun commentaire.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { get, updateStatut, addCommentaire } from '@/api/taches'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const tache = ref(null)
const isCreateur = ref(false)
const isAssigne = ref(false)

const updatingStatut = ref(false)
const addingComment = ref(false)

const statutForm = ref({ statut: 'en_cours', contenu: '' })
const commentForm = ref({ contenu: '' })

const commentaires = computed(() => {
  if (!tache.value?.commentaires) return []
  return [...tache.value.commentaires].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const isOverdue = computed(() => {
  if (!tache.value?.date_echeance || tache.value.statut === 'terminee') return false
  return new Date(tache.value.date_echeance) < new Date()
})

async function loadTache() {
  try {
    const { data } = await get(route.params.id)
    tache.value = data.data
    isCreateur.value = data.isCreateur
    isAssigne.value = data.isAssigne
  } catch {
    ui.addToast('Tache introuvable.', 'danger')
    router.push({ name: 'taches.index' })
  } finally {
    loading.value = false
  }
}

async function handleUpdateStatut() {
  updatingStatut.value = true
  try {
    const { data } = await updateStatut(route.params.id, statutForm.value)
    tache.value = data.data
    statutForm.value = { statut: 'en_cours', contenu: '' }
    ui.addToast('Statut mis a jour avec succes.', 'success')
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la mise a jour.', 'danger')
  } finally {
    updatingStatut.value = false
  }
}

async function handleAddComment() {
  addingComment.value = true
  try {
    const { data } = await addCommentaire(route.params.id, commentForm.value)
    tache.value = data.data
    commentForm.value = { contenu: '' }
    ui.addToast('Commentaire ajoute.', 'success')
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur.', 'danger')
  } finally {
    addingComment.value = false
  }
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function prioriteBadge(priorite) {
  const map = { urgente: 'badge bg-danger', haute: 'badge bg-warning text-dark', normale: 'badge bg-secondary' }
  return map[priorite] || 'badge bg-secondary'
}

function statutBadge(statut) {
  const map = { terminee: 'badge bg-success', en_cours: 'badge bg-primary', nouvelle: 'badge bg-secondary' }
  return map[statut] || 'badge bg-secondary'
}

function statutLabel(statut) {
  const map = { terminee: 'Terminee', en_cours: 'En cours', nouvelle: 'Nouvelle' }
  return map[statut] || capitalize(statut)
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

function sourceEmetteurLabel(source) {
  const map = {
    directeur: 'Directeur',
    assistant_departement: 'Assistant du departement',
    sen: 'SEN / Coordination',
    autre: 'Autre',
  }
  return map[source] || source
}

onMounted(() => loadTache())
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
