<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement de la tâche..." />
      </div>

      <template v-else-if="hasLoadedTache">
        <section class="rh-hero">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>{{ tache.titre }}</h1>
              <p class="rh-sub">
                Assignée à {{ tache.agent?.nom_complet }} par {{ tache.createur?.nom_complet }}
              </p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <span v-if="isOverdue" class="badge bg-danger me-2"><i class="fas fa-clock me-1"></i>En retard</span>
                <router-link :to="{ name: 'taches.index' }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
                <template v-if="canManage && tache.statut !== 'terminee'">
                  <button class="btn-rh main" @click="openEditPanel">
                    <i class="fas fa-edit me-1"></i> Modifier
                  </button>
                  <button class="btn-rh danger" :disabled="deleting" @click="handleDelete">
                    <i class="fas fa-trash me-1"></i> Supprimer
                  </button>
                </template>
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
                  <h3 class="panel-title"><i class="fas fa-info-circle me-2 text-primary"></i>Détails</h3>
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

                  <dt class="col-sm-4 text-muted">Créée par</dt>
                  <dd class="col-sm-8">{{ tache.createur?.nom_complet }}</dd>

                  <template v-if="tache.date_tache">
                    <dt class="col-sm-4 text-muted">Date de la tâche</dt>
                    <dd class="col-sm-8">{{ formatDate(tache.date_tache) }}</dd>
                  </template>

                  <dt class="col-sm-4 text-muted">Assignée à</dt>
                  <dd class="col-sm-8">{{ tache.agent?.nom_complet }}</dd>

                  <dt class="col-sm-4 text-muted">Date de création</dt>
                  <dd class="col-sm-8">{{ formatDateTime(tache.created_at) }}</dd>

                  <template v-if="tache.date_echeance">
                    <dt class="col-sm-4 text-muted">Échéance</dt>
                    <dd class="col-sm-8">
                      {{ formatDate(tache.date_echeance) }}
                      <span v-if="isOverdue" class="badge bg-danger ms-1">En retard</span>
                    </dd>
                  </template>

                  <dt class="col-sm-4 text-muted">Progression</dt>
                  <dd class="col-sm-8">
                    <div class="task-progress-wrap">
                      <div class="progress task-progress-bar">
                        <div class="progress-bar" :class="tache.pourcentage >= 100 ? 'bg-success' : 'bg-primary'" :style="{ width: `${tache.pourcentage || 0}%` }"></div>
                      </div>
                      <strong>{{ tache.pourcentage || 0 }}%</strong>
                    </div>
                  </dd>
                </dl>

                <template v-if="tache.description">
                  <hr>
                  <div style="white-space: pre-wrap;">{{ tache.description }}</div>
                </template>

                <template v-if="documentsByType.initial.length || documentsByType.progression.length || documentsByType.final.length">
                  <hr>
                  <div class="task-doc-sections">
                    <div v-if="documentsByType.initial.length">
                      <h6 class="fw-bold mb-2">Documents d'assignation</h6>
                      <div class="task-doc-list">
                        <button v-for="doc in documentsByType.initial" :key="doc.id" type="button" class="task-doc-item" @click="downloadDocument(doc)">
                          <i class="fas fa-paperclip"></i>
                          <span>{{ doc.nom_original }}</span>
                        </button>
                      </div>
                    </div>
                    <div v-if="documentsByType.progression.length" class="mt-3">
                      <h6 class="fw-bold mb-2">Documents de progression</h6>
                      <div class="task-doc-list">
                        <button v-for="doc in documentsByType.progression" :key="doc.id" type="button" class="task-doc-item" @click="downloadDocument(doc)">
                          <i class="fas fa-file-upload"></i>
                          <span>{{ doc.nom_original }}</span>
                        </button>
                      </div>
                    </div>
                    <div v-if="documentsByType.final.length" class="mt-3">
                      <h6 class="fw-bold mb-2">Documents finaux</h6>
                      <div class="task-doc-list">
                        <button v-for="doc in documentsByType.final" :key="doc.id" type="button" class="task-doc-item" @click="downloadDocument(doc)">
                          <i class="fas fa-file-check"></i>
                          <span>{{ doc.nom_original }}</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
            </div>

            <!-- Formulaire changement de statut (agent assigné, SEN, SENA, créateur) -->
            <div v-if="(isAssigne || canManage) && tache.statut !== 'terminee'" class="dash-panel mt-3">
              <header class="panel-head">
                <div>
                  <h3 class="panel-title"><i class="fas fa-exchange-alt me-2 text-warning"></i>Mettre à jour le statut</h3>
                </div>
              </header>
              <div class="p-3">
                <form @submit.prevent="handleUpdateStatut">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label for="statut" class="form-label fw-bold">Nouveau statut <span class="text-danger">*</span></label>
                      <select v-model="statutForm.statut" class="form-select" id="statut" required>
                        <option value="en_cours" :selected="tache.statut === 'nouvelle'">En cours</option>
                        <option value="terminee">Terminée</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label for="pourcentage" class="form-label fw-bold">Progression (%) <span class="text-danger">*</span></label>
                      <input v-model.number="statutForm.pourcentage" type="number" min="0" max="100" class="form-control" id="pourcentage" required>
                    </div>
                    <div class="col-md-4">
                      <label for="document_statut" class="form-label fw-bold">Document justificatif</label>
                      <input id="document_statut" ref="statusDocumentInput" type="file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png" @change="handleStatusDocumentChange">
                    </div>
                    <div class="col-12">
                      <label for="contenu_statut" class="form-label fw-bold">Commentaire <span class="text-danger">*</span></label>
                      <textarea v-model="statutForm.contenu" class="form-control" id="contenu_statut" rows="3" required
                                placeholder="Décrivez l'avancement ou le résultat..."></textarea>
                    </div>
                    <div class="col-12">
                      <div class="alert alert-info py-2 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Le document est obligatoire si vous passez la tâche à Terminée ou si vous modifiez le pourcentage de progression.
                      </div>
                      <div v-if="statusDocumentName" class="task-inline-file mt-2">
                        <span><i class="fas fa-file me-1"></i>{{ statusDocumentName }}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeStatusDocument">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-warning" :disabled="updatingStatut">
                        <span v-if="updatingStatut" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="fas fa-sync-alt me-1"></i> Mettre à jour
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
                    <div v-if="comm.documents?.length" class="task-doc-list mt-2">
                      <button v-for="doc in comm.documents" :key="doc.id" type="button" class="task-doc-item task-doc-item-sm" @click="downloadDocument(doc)">
                        <i class="fas fa-paperclip"></i>
                        <span>{{ doc.nom_original }}</span>
                      </button>
                    </div>
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

      <!-- ═══ PANNEAU MODIFICATION (SENA / créateur) ═══ -->
      <div v-if="showEditPanel" class="modal-backdrop-overlay" @click.self="showEditPanel = false">
        <div class="edit-tache-panel">
          <div class="edit-tache-head">
            <h5><i class="fas fa-edit me-2"></i>Modifier la tâche</h5>
            <button class="btn-close" @click="showEditPanel = false"></button>
          </div>
          <div class="edit-tache-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Titre *</label>
              <input v-model="editForm.titre" type="text" class="form-control" />
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Description</label>
              <textarea v-model="editForm.description" class="form-control" rows="3"></textarea>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Priorité</label>
                <select v-model="editForm.priorite" class="form-select">
                  <option value="normale">Normale</option>
                  <option value="haute">Haute</option>
                  <option value="urgente">Urgente</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Source émetteur</label>
                <select v-model="editForm.source_emetteur" class="form-select">
                  <option value="directeur">Directeur</option>
                  <option value="sen">SEN</option>
                  <option value="sep">SEP</option>
                  <option value="sel">SEL</option>
                  <option value="autre">Autre</option>
                </select>
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Échéance</label>
                <input v-model="editForm.date_echeance" type="date" class="form-control" />
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Date de la tâche</label>
                <input v-model="editForm.date_tache" type="date" class="form-control" />
              </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
              <button class="btn btn-outline-secondary" @click="showEditPanel = false">Annuler</button>
              <button class="btn btn-primary" :disabled="saving" @click="handleEdit">
                <i class="fas fa-save me-1"></i>{{ saving ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="!hasLoadedTache" class="text-center py-5">
        <i class="fas fa-lock fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted">Tâche introuvable ou accès refusé.</p>
        <button class="btn btn-outline-primary mt-2" @click="router.back()">
          <i class="fas fa-arrow-left me-1"></i> Retour
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { get, updateStatut, addCommentaire, update, destroy } from '@/api/taches'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()
const auth = useAuthStore()

const loading = ref(true)
const tache = ref(null)
const isCreateur = ref(false)
const isAssigne = ref(false)
const hasLoadedTache = computed(() => !!tache.value?.id)

const updatingStatut = ref(false)
const addingComment = ref(false)
const statusDocument = ref(null)
const statusDocumentInput = ref(null)

const statutForm = ref({ statut: 'en_cours', pourcentage: 0, contenu: '' })
const commentForm = ref({ contenu: '' })

const commentaires = computed(() => {
  if (!tache.value?.commentaires) return []
  return [...tache.value.commentaires].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const isOverdue = computed(() => {
  if (!tache.value?.date_echeance || tache.value.statut === 'terminee') return false
  return new Date(tache.value.date_echeance) < new Date()
})

const documentsByType = computed(() => {
  const documents = tache.value?.documents || []
  return {
    initial: documents.filter((doc) => doc.type_document === 'initial'),
    progression: documents.filter((doc) => doc.type_document === 'progression'),
    final: documents.filter((doc) => doc.type_document === 'final'),
  }
})

const statusDocumentName = computed(() => statusDocument.value?.name || '')

function extractTachePayload(responseData) {
  const payload = responseData?.data?.tache ?? responseData?.data ?? responseData?.tache ?? responseData

  if (!payload || typeof payload !== 'object' || !payload.id) {
    return null
  }

  return payload
}

function applyTacheResponse(responseData) {
  const payload = extractTachePayload(responseData)

  if (!payload) {
    throw new Error('Payload tache invalide')
  }

  tache.value = payload
  isCreateur.value = Boolean(responseData?.isCreateur ?? responseData?.meta?.isCreateur ?? isCreateur.value)
  isAssigne.value = Boolean(responseData?.isAssigne ?? responseData?.meta?.isAssigne ?? isAssigne.value)
  statutForm.value = {
    statut: payload.statut === 'nouvelle' ? 'en_cours' : (payload.statut || 'en_cours'),
    pourcentage: payload.pourcentage ?? 0,
    contenu: '',
  }
}

async function loadTache() {
  try {
    const { data } = await get(route.params.id)
    applyTacheResponse(data)
  } catch {
    tache.value = null
    isCreateur.value = false
    isAssigne.value = false
    ui.addToast('Tâche introuvable ou accès refusé.', 'danger')
  } finally {
    loading.value = false
  }
}

// Re-charger si navigation SPA entre deux taches (Vue Router réutilise le composant)
watch(() => route.params.id, (newId, oldId) => {
  if (newId && newId !== oldId) {
    tache.value = null
    loading.value = true
    loadTache()
  }
})

async function handleUpdateStatut() {
  updatingStatut.value = true
  try {
    const payload = new FormData()
    payload.append('statut', statutForm.value.statut)
    payload.append('pourcentage', String(statutForm.value.pourcentage ?? 0))
    payload.append('contenu', statutForm.value.contenu)
    if (statusDocument.value) {
      payload.append('document', statusDocument.value)
    }

    const { data } = await updateStatut(route.params.id, payload)
    applyTacheResponse(data)
    removeStatusDocument()
    ui.addToast('Statut mis à jour avec succès.', 'success')
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la mise à jour.', 'danger')
  } finally {
    updatingStatut.value = false
  }
}

async function handleAddComment() {
  addingComment.value = true
  try {
    const { data } = await addCommentaire(route.params.id, commentForm.value)
    applyTacheResponse(data)
    commentForm.value = { contenu: '' }
    ui.addToast('Commentaire ajouté.', 'success')
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur.', 'danger')
  } finally {
    addingComment.value = false
  }
}

function handleStatusDocumentChange(event) {
  statusDocument.value = event.target.files?.[0] || null
}

function removeStatusDocument() {
  statusDocument.value = null
  if (statusDocumentInput.value) {
    statusDocumentInput.value.value = ''
  }
}

function downloadDocument(document) {
  if (!document?.download_url) return
  window.open(document.download_url, '_blank')
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
  const map = { terminee: 'Terminée', en_cours: 'En cours', nouvelle: 'Nouvelle' }
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
    ' à ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function sourceEmetteurLabel(source) {
  const map = {
    directeur: 'Directeur',
    assistant_departement: 'Direction',
    sen: 'SEN',
    sep: 'SEP',
    sel: 'SEL',
    autre: 'Autre',
  }
  return map[source] || source
}

onMounted(() => loadTache())

/* ── Edition / Suppression (SENA / créateur) ── */
const canManage = computed(() => auth.isSENA || auth.isSEN || isCreateur.value)
const showEditPanel = ref(false)
const saving = ref(false)
const deleting = ref(false)
const editForm = ref({ titre: '', description: '', priorite: 'normale', date_echeance: '', date_tache: '', source_emetteur: '' })

function openEditPanel() {
  editForm.value = {
    titre:          tache.value.titre          || '',
    description:    tache.value.description    || '',
    priorite:       tache.value.priorite       || 'normale',
    date_echeance:  tache.value.date_echeance  ? tache.value.date_echeance.substring(0, 10) : '',
    date_tache:     tache.value.date_tache     ? tache.value.date_tache.substring(0, 10) : '',
    source_emetteur: tache.value.source_emetteur || '',
  }
  showEditPanel.value = true
}

async function handleEdit() {
  saving.value = true
  try {
    const { data } = await update(route.params.id, editForm.value)
    applyTacheResponse(data)
    showEditPanel.value = false
    ui.addToast('Tâche mise à jour.', 'success')
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la modification.', 'danger')
  } finally {
    saving.value = false
  }
}

async function handleDelete() {
  if (!confirm('Supprimer cette tâche définitivement ?')) return
  deleting.value = true
  try {
    await destroy(route.params.id)
    ui.addToast('Tâche supprimée.', 'success')
    router.push({ name: 'taches.index' })
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
    deleting.value = false
  }
}
</script>

<style scoped>
.task-progress-wrap {
  display: flex;
  align-items: center;
  gap: .75rem;
}

.task-progress-bar {
  flex: 1;
  height: 10px;
  border-radius: 999px;
  overflow: hidden;
}

.task-doc-list {
  display: flex;
  flex-wrap: wrap;
  gap: .6rem;
}

.task-doc-item {
  border: 1px solid #dbeafe;
  background: #eff6ff;
  color: #1d4ed8;
  border-radius: 999px;
  padding: .45rem .8rem;
  font-size: .82rem;
  display: inline-flex;
  align-items: center;
  gap: .45rem;
}

.task-doc-item-sm {
  padding: .35rem .7rem;
  font-size: .76rem;
}

.task-inline-file {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  padding: .7rem .85rem;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    dl.row dt { font-size: .8rem; }
    dl.row dd { font-size: .85rem; margin-bottom: .6rem; }
    .badge { font-size: .7rem; }
  .task-progress-wrap { align-items: flex-start; flex-direction: column; }
}

/* Bouton danger hero-tools */
.btn-rh.danger {
  background: #ef4444; color: #fff; border: none; border-radius: 8px;
  padding: .45rem 1rem; font-size: .85rem; cursor: pointer; font-weight: 600;
  display: inline-flex; align-items: center; gap: .3rem;
  transition: background .2s;
}
.btn-rh.danger:hover { background: #dc2626; }
.btn-rh.danger:disabled { opacity: .6; cursor: not-allowed; }

/* Panneau édition tâche */
.modal-backdrop-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.45);
  z-index: 1050; display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.edit-tache-panel {
  background: #fff; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,.18);
  width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto;
}
.edit-tache-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb;
}
.edit-tache-head h5 { margin: 0; font-weight: 700; color: #1e293b; }
.edit-tache-body { padding: 1.25rem; }
</style>
