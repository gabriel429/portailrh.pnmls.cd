<template>
  <div class="container-fluid py-4">
    <!-- Loading -->
    <LoadingSpinner v-if="loading" message="Chargement du profil agent..." />

    <template v-else-if="agent">
      <!-- Agent header -->
      <div class="agent-header">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div v-if="agent.photo && !agent._photoError" class="agent-avatar-lg">
              <img :src="'/' + agent.photo" :alt="agent.nom_complet" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;border:3px solid rgba(255,255,255,0.3);" @error="agent._photoError = true">
            </div>
            <div v-else class="rounded-circle d-flex align-items-center justify-content-center" style="width:80px;height:80px;background:rgba(255,255,255,0.15);">
              <i class="fas fa-user fa-2x"></i>
            </div>
            <div>
              <h2 class="mb-1">{{ agent.prenom }} {{ agent.nom }}</h2>
              <div class="d-flex gap-2 align-items-center flex-wrap">
                <span class="badge bg-light text-dark">{{ agent.id_agent }}</span>
                <span v-if="agent.organe" class="badge bg-info">{{ agent.organe }}</span>
                <span v-if="agent.statut === 'actif'" class="badge bg-success">Actif</span>
                <span v-else-if="agent.statut === 'suspendu'" class="badge bg-warning text-dark">Suspendu</span>
                <span v-else class="badge bg-secondary">{{ capitalize(agent.statut) }}</span>
              </div>
              <small v-if="agent.fonction" class="opacity-75 mt-1 d-block">{{ agent.fonction }}</small>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap no-print">
            <button class="btn btn-light btn-sm" @click="printAgent">
              <i class="fas fa-print me-1"></i> Imprimer
            </button>
            <button class="btn btn-warning btn-sm" @click="openEditModal">
              <i class="fas fa-edit me-1"></i> Modifier
            </button>
            <router-link :to="{ name: 'rh.agents.index' }" class="btn btn-outline-light btn-sm">
              <i class="fas fa-arrow-left me-1"></i> Retour
            </router-link>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Main column with tabs -->
        <div class="col-lg-8">
          <ul class="nav nav-tabs mb-3 no-print" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link" :class="{ active: activeTab === 'informations' }" @click="activeTab = 'informations'" type="button">
                <i class="fas fa-user me-1"></i> Informations
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" :class="{ active: activeTab === 'demandes' }" @click="activeTab = 'demandes'" type="button">
                <i class="fas fa-file-signature me-1"></i> Demandes
                <span v-if="pendingRequestsCount > 0" class="badge bg-warning text-dark tab-badge">{{ pendingRequestsCount }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" :class="{ active: activeTab === 'parcours' }" @click="activeTab = 'parcours'" type="button">
                <i class="fas fa-route me-1"></i> Parcours
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" :class="{ active: activeTab === 'documents' }" @click="activeTab = 'documents'" type="button">
                <i class="fas fa-folder me-1"></i> Documents
                <span class="badge bg-secondary tab-badge">{{ documentsCount }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" :class="{ active: activeTab === 'messages' }" @click="activeTab = 'messages'" type="button">
                <i class="fas fa-envelope me-1"></i> Messages
                <span v-if="unreadMessagesCount > 0" class="badge bg-danger tab-badge">{{ unreadMessagesCount }}</span>
              </button>
            </li>
          </ul>

          <div class="tab-content">

            <!-- TAB 1: INFORMATIONS -->
            <div v-if="printing || activeTab === 'informations'">
              <!-- Informations personnelles -->
              <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                  <h5 class="mb-0"><i class="fas fa-user me-2 text-primary"></i>Informations Personnelles</h5>
                </div>
                <div class="card-body">
                  <!-- Identite -->
                  <div class="row mb-2">
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Prenom</label>
                      <p class="mb-0 fw-semibold">{{ agent.prenom }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Post-nom</label>
                      <p class="mb-0 fw-semibold">{{ agent.postnom || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Nom</label>
                      <p class="mb-0 fw-semibold">{{ agent.nom }}</p>
                    </div>
                  </div>
                  <hr class="my-2">
                  <!-- Naissance -->
                  <div class="row mb-2">
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Date de naissance</label>
                      <p class="mb-0">{{ formatDate(agent.date_naissance) }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Lieu de naissance</label>
                      <p class="mb-0">{{ agent.lieu_naissance || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Sexe</label>
                      <p class="mb-0">{{ agent.sexe === 'M' ? 'Masculin' : agent.sexe === 'F' ? 'Feminin' : (agent.sexe || 'N/A') }}</p>
                    </div>
                  </div>
                  <hr class="my-2">
                  <!-- Famille -->
                  <div class="row mb-2">
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Situation familiale</label>
                      <p class="mb-0">{{ agent.situation_familiale || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Nombre d'enfants</label>
                      <p class="mb-0">{{ agent.nombre_enfants ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Adresse</label>
                      <p class="mb-0">{{ agent.adresse || 'N/A' }}</p>
                    </div>
                  </div>
                  <hr class="my-2">
                  <!-- Contact -->
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Telephone</label>
                      <p class="mb-0">{{ agent.telephone || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">E-mail prive</label>
                      <p class="mb-0">{{ agent.email_prive || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">E-mail institutionnel</label>
                      <p class="mb-0">{{ agent.email_professionnel || 'N/A' }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Informations professionnelles -->
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                  <h5 class="mb-0"><i class="fas fa-briefcase me-2 text-primary"></i>Informations Professionnelles</h5>
                </div>
                <div class="card-body">
                  <div class="row mb-2">
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Organe</label>
                      <p class="mb-0 fw-semibold">{{ agent.organe || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Fonction</label>
                      <p class="mb-0 fw-semibold">{{ agent.fonction || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Date d'embauche</label>
                      <p class="mb-0">{{ formatDate(agent.date_embauche) }}</p>
                    </div>
                  </div>
                  <hr class="my-2">
                  <div class="row mb-2">
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Province</label>
                      <p class="mb-0">{{ agent.province ? (agent.province.nom_province || agent.province.nom) : (isNational ? 'National' : 'N/A') }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Departement</label>
                      <p class="mb-0">{{ agent.departement ? agent.departement.nom : 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Annee d'engagement</label>
                      <p class="mb-0">{{ agent.annee_engagement_programme || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Anciennete</label>
                      <p class="mb-0 fw-semibold">
                        <template v-if="agent.anciennete !== null && agent.anciennete !== undefined">
                          {{ agent.anciennete }} an{{ agent.anciennete > 1 ? 's' : '' }}
                        </template>
                        <template v-else>N/A</template>
                      </p>
                    </div>
                  </div>
                  <hr class="my-2">
                  <div class="row mb-2">
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Matricule de l'Etat</label>
                      <p class="mb-0">{{ agent.matricule_etat || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Provenance matricule</label>
                      <p class="mb-0">{{ agent.institution ? agent.institution.nom : 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Grade de l'Etat</label>
                      <p class="mb-0">{{ agent.grade ? agent.grade.libelle : 'N/A' }}</p>
                    </div>
                  </div>
                  <hr class="my-2">
                  <div class="row mb-2">
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Niveau d'etudes</label>
                      <p class="mb-0">{{ agent.niveau_etudes || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Domaine d'etudes</label>
                      <p class="mb-0">{{ agent.domaine_etudes || 'N/A' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB 2: DEMANDES -->
            <div v-if="printing || activeTab === 'demandes'">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                  <h5 class="mb-0"><i class="fas fa-file-signature me-2 text-primary"></i>Demandes ({{ requestsCount }})</h5>
                </div>
                <div class="card-body p-0">
                  <template v-if="requestsCount > 0">
                    <div class="table-responsive">
                      <table class="table table-hover mb-0">
                        <thead class="table-light">
                          <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Date debut</th>
                            <th>Date fin</th>
                            <th>Statut</th>
                            <th>Remarques</th>
                            <th>Date</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="req in sortedRequests" :key="req.id">
                            <td><strong>{{ capitalize(req.type) }}</strong></td>
                            <td>{{ truncate(req.description, 50) }}</td>
                            <td>{{ formatDate(req.date_debut) }}</td>
                            <td>{{ formatDate(req.date_fin) }}</td>
                            <td>
                              <span v-if="req.statut === 'en_attente'" class="badge bg-warning text-dark">En attente</span>
                              <span v-else-if="req.statut === 'approuve'" class="badge bg-success">Approuve</span>
                              <span v-else-if="req.statut === 'rejete'" class="badge bg-danger">Rejete</span>
                              <span v-else class="badge bg-secondary">Annule</span>
                            </td>
                            <td>{{ truncate(req.remarques, 30) || '-' }}</td>
                            <td><small class="text-muted">{{ formatDate(req.created_at) }}</small></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </template>
                  <div v-else class="text-center py-5">
                    <i class="fas fa-file-signature fa-3x text-muted mb-3 d-block" style="opacity:.5;"></i>
                    <h6 class="text-muted">Aucune demande</h6>
                    <p class="text-muted small">Cet agent n'a soumis aucune demande</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB 3: PARCOURS -->
            <div v-if="printing || activeTab === 'parcours'">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                  <h5 class="mb-0"><i class="fas fa-route me-2 text-primary"></i>Parcours et Carriere</h5>
                </div>
                <div class="card-body">
                  <template v-if="affectationsCount > 0">
                    <div class="ps-2">
                      <div
                        v-for="affectation in sortedAffectations"
                        :key="affectation.id"
                        class="timeline-item"
                      >
                        <div class="timeline-dot" :class="affectation.actif ? 'active' : 'ended'"></div>
                        <div class="d-flex justify-content-between align-items-start mb-1">
                          <div>
                            <h6 class="mb-1">
                              {{ affectation.fonction ? affectation.fonction.nom : 'Fonction non definie' }}
                            </h6>
                            <div class="d-flex gap-2 flex-wrap mb-1">
                              <span class="badge bg-primary">{{ affectation.niveau_administratif_label || affectation.niveau_administratif || '' }}</span>
                              <span class="badge bg-outline-secondary border">{{ capitalize(affectation.niveau || '') }}</span>
                            </div>
                            <small v-if="affectation.department" class="text-muted d-block">
                              <i class="fas fa-building me-1"></i>{{ affectation.department.nom }}
                            </small>
                            <small v-if="affectation.province" class="text-muted d-block">
                              <i class="fas fa-map-marker-alt me-1"></i>{{ affectation.province.nom || affectation.province.nom_province }}
                            </small>
                            <small v-if="affectation.remarque" class="text-muted fst-italic d-block mt-1">{{ affectation.remarque }}</small>
                          </div>
                          <div class="text-end">
                            <span v-if="affectation.actif" class="badge bg-success">En cours</span>
                            <span v-else class="badge bg-secondary">Termine</span>
                            <small class="text-muted d-block mt-1">
                              {{ formatDate(affectation.date_debut) || '?' }}
                              <template v-if="affectation.date_fin">- {{ formatDate(affectation.date_fin) }}</template>
                              <template v-else-if="affectation.actif"> - Aujourd'hui</template>
                            </small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>
                  <div v-else class="text-center py-5">
                    <i class="fas fa-route fa-3x text-muted mb-3 d-block" style="opacity:.5;"></i>
                    <h6 class="text-muted">Aucune affectation</h6>
                    <p class="text-muted small">Le parcours de cet agent n'a pas encore ete renseigne</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB 4: DOCUMENTS -->
            <div v-if="printing || activeTab === 'documents'">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                  <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-folder me-2 text-primary"></i>Documents ({{ documentsCount }})</h5>
                  </div>
                </div>
                <div class="card-body">
                  <template v-if="documentsCount > 0">
                    <div v-for="docType in docTypes" :key="docType.type" class="mb-4">
                      <h6 class="mb-3">
                        <i class="fas me-2" :class="[docType.icon, docType.color]"></i>
                        <strong>{{ docType.label }}</strong>
                        <span class="badge bg-secondary">{{ getDocsByType(docType.type).length }}</span>
                      </h6>
                      <template v-if="getDocsByType(docType.type).length > 0">
                        <div
                          v-for="doc in getDocsByType(docType.type)"
                          :key="doc.id"
                          class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2"
                        >
                          <div>
                            <p class="mb-1"><strong>{{ doc.description }}</strong></p>
                            <small class="text-muted">{{ formatDateTime(doc.created_at) }}</small>
                          </div>
                        </div>
                      </template>
                      <p v-else class="text-muted small ms-3">Aucun document de type {{ docType.label }}</p>
                    </div>
                  </template>
                  <div v-else class="text-center py-5">
                    <i class="fas fa-folder fa-3x text-muted mb-3 d-block" style="opacity:.5;"></i>
                    <h6 class="text-muted">Aucun document</h6>
                    <p class="text-muted small mb-3">Cet agent n'a pas encore de documents</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB 5: MESSAGES -->
            <div v-if="printing || activeTab === 'messages'">
              <!-- Message history -->
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                  <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Historique des messages ({{ messagesCount }})</h5>
                </div>
                <div class="card-body">
                  <template v-if="messagesCount > 0">
                    <div
                      v-for="msg in sortedMessages"
                      :key="msg.id"
                      class="message-card"
                      :class="{ unread: !msg.lu }"
                    >
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                          <h6 class="mb-0">{{ msg.sujet }}</h6>
                          <small class="text-muted">
                            <i class="fas fa-user me-1"></i>{{ msg.sender ? msg.sender.name : 'Systeme' }}
                            &bull; {{ formatDateTime(msg.created_at) }}
                          </small>
                        </div>
                        <span v-if="!msg.lu" class="badge bg-warning text-dark">Non lu</span>
                        <span v-else class="badge bg-light text-muted">Lu</span>
                      </div>
                      <p class="mb-0">{{ msg.contenu }}</p>
                    </div>
                  </template>
                  <div v-else class="text-center py-5">
                    <i class="fas fa-envelope fa-3x text-muted mb-3 d-block" style="opacity:.5;"></i>
                    <h6 class="text-muted">Aucun message</h6>
                    <p class="text-muted small">Aucun message n'a ete envoye a cet agent</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4 no-print">
          <!-- Resume rapide -->
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-bottom">
              <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Resume</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <small class="text-muted">ID Agent</small>
                <p class="mb-0 fw-bold">{{ agent.id_agent }}</p>
              </div>
              <div class="mb-3">
                <small class="text-muted">Membre depuis</small>
                <p class="mb-0">{{ formatDate(agent.created_at) }}</p>
              </div>
              <div class="mb-3">
                <small class="text-muted">Derniere modification</small>
                <p class="mb-0">{{ formatDateTime(agent.updated_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Statistiques -->
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-bottom">
              <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Statistiques</h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Documents</span>
                <strong>{{ documentsCount }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Demandes totales</span>
                <strong>{{ requestsCount }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Demandes en attente</span>
                <strong class="text-warning">{{ pendingRequestsCount }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Affectations</span>
                <strong>{{ affectationsCount }}</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Messages</span>
                <strong>{{ messagesCount }}</strong>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
              <h5 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Actions</h5>
            </div>
            <div class="card-body">
              <button class="btn btn-warning btn-sm w-100 mb-2" @click="openEditModal">
                <i class="fas fa-edit me-2"></i> Modifier
              </button>
              <button class="btn btn-danger btn-sm w-100" @click="confirmDelete">
                <i class="fas fa-trash me-2"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer l'agent"
      :message="'Etes-vous sur de vouloir supprimer ' + (agent ? agent.prenom + ' ' + agent.nom : '') + ' ? Cette action est irreversible.'"
      :loading="deleting"
      @confirm="doDelete"
      @cancel="showDeleteModal = false"
    />

    <!-- Agent Edit Modal -->
    <AgentEditModal
      :show="showEditModal"
      :agent-id="editAgentId"
      @close="closeEditModal"
      @updated="onAgentUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { get, remove } from '@/api/agents'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import AgentEditModal from '@/components/agents/AgentEditModal.vue'

const router = useRouter()
const route = useRoute()
const ui = useUiStore()

// State
const loading = ref(true)
const agent = ref(null)
const activeTab = ref('informations')
const showDeleteModal = ref(false)
const deleting = ref(false)
const printing = ref(false)
const showEditModal = ref(false)
const editAgentId = ref(null)

function openEditModal() {
    if (agent.value) {
        editAgentId.value = agent.value.id
        showEditModal.value = true
    }
}

function closeEditModal() {
    showEditModal.value = false
    editAgentId.value = null
}

function onAgentUpdated() {
    closeEditModal()
    fetchAgent()
}

// Document types config
const docTypes = [
    { type: 'identite', label: 'Identite', icon: 'fa-id-card', color: 'text-danger' },
    { type: 'parcours', label: 'Parcours', icon: 'fa-graduation-cap', color: 'text-info' },
    { type: 'carriere', label: 'Carriere', icon: 'fa-briefcase', color: 'text-warning' },
    { type: 'mission', label: 'Missions', icon: 'fa-plane', color: 'text-success' },
]

// Computed stats
const documentsCount = computed(() => agent.value?.documents?.length || 0)
const requestsCount = computed(() => agent.value?.requests?.length || 0)
const pendingRequestsCount = computed(() =>
    (agent.value?.requests || []).filter(r => r.statut === 'en_attente').length
)
const affectationsCount = computed(() => agent.value?.affectations?.length || 0)
const messagesCount = computed(() => agent.value?.messages?.length || 0)
const unreadMessagesCount = computed(() =>
    (agent.value?.messages || []).filter(m => !m.lu).length
)
const isNational = computed(() =>
    agent.value?.organe && agent.value.organe.toLowerCase().includes('national')
)

// Sorted arrays
const sortedRequests = computed(() =>
    [...(agent.value?.requests || [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
)
const sortedAffectations = computed(() =>
    [...(agent.value?.affectations || [])].sort((a, b) => new Date(b.date_debut) - new Date(a.date_debut))
)
const sortedMessages = computed(() =>
    [...(agent.value?.messages || [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
)

// Helpers
function capitalize(str) {
    if (!str) return ''
    return str.charAt(0).toUpperCase() + str.slice(1)
}

function truncate(str, length) {
    if (!str) return ''
    return str.length > length ? str.substring(0, length) + '...' : str
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A'
    try {
        const d = new Date(dateStr)
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
    } catch {
        return dateStr
    }
}

function formatDateTime(dateStr) {
    if (!dateStr) return 'N/A'
    try {
        const d = new Date(dateStr)
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
            ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    } catch {
        return dateStr
    }
}

function getDocsByType(type) {
    return (agent.value?.documents || []).filter(d => d.type === type)
}

function printAgent() {
    printing.value = true
    nextTick(() => {
        window.print()
        printing.value = false
    })
}

// Fetch
async function fetchAgent() {
    loading.value = true
    try {
        const { data } = await get(route.params.id)
        agent.value = data.agent
    } catch (err) {
        console.error('Error fetching agent:', err)
        ui.addToast('Erreur lors du chargement de l\'agent', 'danger')
        router.push({ name: 'rh.agents.index' })
    } finally {
        loading.value = false
    }
}

// Delete
function confirmDelete() {
    showDeleteModal.value = true
}

async function doDelete() {
    if (!agent.value) return
    deleting.value = true
    try {
        await remove(agent.value.id)
        ui.addToast('Agent supprime avec succes', 'success')
        router.push({ name: 'rh.agents.index' })
    } catch (err) {
        console.error('Error deleting agent:', err)
        ui.addToast('Erreur lors de la suppression', 'danger')
    } finally {
        deleting.value = false
        showDeleteModal.value = false
    }
}

onMounted(() => {
    fetchAgent()
})
</script>

<style scoped>
.agent-header {
    background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
    color: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.agent-header::after {
    content: '';
    position: absolute;
    right: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 200px;
    height: 200px;
    background: url('/images/pnmls.jpeg') center/contain no-repeat;
    opacity: 0.10;
    pointer-events: none;
}
.agent-header .badge { font-size: 0.85rem; }
.nav-tabs .nav-link { font-weight: 500; color: #666; }
.nav-tabs .nav-link.active { color: #0077B5; border-bottom: 3px solid #0077B5; }
.tab-badge { font-size: 0.7rem; vertical-align: middle; }
.timeline-item {
    position: relative;
    padding-left: 30px;
    padding-bottom: 20px;
    border-left: 2px solid #e5e5e5;
}
.timeline-item:last-child { border-left: 2px solid transparent; }
.timeline-dot {
    position: absolute;
    left: -8px;
    top: 4px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid white;
}
.timeline-dot.active { background-color: #28a745; }
.timeline-dot.ended { background-color: #6c757d; }
.message-card {
    border-left: 4px solid #0077B5;
    background: #f8f9fa;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}
.message-card.unread {
    border-left-color: #ffc107;
    background: #fffef5;
}

/* Mobile responsive */
@media (max-width: 767.98px) {
    .agent-header {
        padding: 20px 16px;
        border-radius: 6px;
    }
    .agent-header::after {
        width: 120px;
        height: 120px;
        right: -10px;
    }
    .agent-header h2 {
        font-size: 1.25rem;
    }
    .agent-header .badge {
        font-size: 0.75rem;
    }
    .nav-tabs .nav-link {
        font-size: 0.85rem;
        padding: 0.4rem 0.6rem;
    }
    .nav-tabs .nav-link i {
        display: none;
    }
    .tab-badge {
        font-size: 0.6rem;
    }
    .timeline-item {
        padding-left: 20px;
        padding-bottom: 14px;
    }
    .message-card {
        padding: 10px;
        margin-bottom: 10px;
    }
    .card-body {
        padding: 0.75rem;
    }
    .table th, .table td {
        padding: 0.4rem 0.5rem;
        font-size: 0.85rem;
    }
}

/* Print styles */
@media print {
    .agent-header {
        background: #0077B5 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        padding: 20px;
        margin-bottom: 15px;
    }
    .agent-header::after { display: none; }

    .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }

    .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; margin-bottom: 12px; }
    .card-header { padding: 10px 15px; }
    .card-body { padding: 10px 15px; }

    .tab-content > div { margin-bottom: 15px; }

    /* Section titles for print */
    .tab-content > div > .card > .card-header h5 {
        font-size: 1rem;
    }
}
</style>
