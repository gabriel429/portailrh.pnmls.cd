<template>
  <div class="container-fluid py-4">
    <!-- Loading -->
    <LoadingSpinner v-if="loading" message="Chargement du profil agent..." />

    <template v-else-if="agent">
      <div class="agent-letterhead">
        <img src="/images/logo-pnmls.png" alt="Logo PNMLS" class="agent-letterhead-logo">
        <div>
          <span>Programme National Multisectoriel de Lutte contre le Sida</span>
          <strong>Fiche agent</strong>
        </div>
      </div>

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
            <button v-if="canManageAgentDocuments" class="btn btn-success btn-sm" :disabled="dossierDownloading" @click="downloadAgentDossier">
              <span v-if="dossierDownloading" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-download me-1"></i> Télécharger le dossier
            </button>
            <button v-if="canManageAgentDocuments" class="btn btn-light btn-sm" @click="openDocumentUploadModal">
              <i class="fas fa-cloud-upload-alt me-1"></i> Ajouter document
            </button>
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

      <div class="agent-overview-grid no-print">
        <div class="agent-overview-card">
          <span class="agent-overview-label">Identite</span>
          <strong>{{ agent.nom_complet }}</strong>
          <small>{{ agent.matricule_etat || agent.id_agent }}</small>
        </div>
        <div class="agent-overview-card">
          <span class="agent-overview-label">Affectation</span>
          <strong>{{ agent.departement?.nom || agent.province?.nom || 'Non renseignee' }}</strong>
          <small>{{ agent.organe || 'Organe non renseigne' }}</small>
        </div>
        <div class="agent-overview-card">
          <span class="agent-overview-label">Fonction</span>
          <strong>{{ agent.fonction || agent.poste_actuel || 'Non renseignee' }}</strong>
          <small>{{ agent.grade?.libelle || agent.grade_etat || 'Grade non renseigne' }}</small>
        </div>
        <div class="agent-overview-card">
          <span class="agent-overview-label">Dossier</span>
          <strong>{{ documentsCount }} document{{ documentsCount > 1 ? 's' : '' }}</strong>
          <small>{{ affectationsCount }} affectation{{ affectationsCount > 1 ? 's' : '' }}</small>
          <button
            v-if="canManageAgentDocuments"
            class="agent-overview-action"
            type="button"
            :disabled="dossierDownloading"
            @click="downloadAgentDossier"
          >
            <span v-if="dossierDownloading" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-download me-1"></i>
            Télécharger tout le dossier
          </button>
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
                      <label class="text-muted small">Telephone professionnel</label>
                      <p class="mb-0">{{ agent.telephone_professionnel || agent.telephone || 'N/A' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Telephone prive</label>
                      <p class="mb-0">{{ agent.telephone_prive || 'N/A' }}</p>
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
                    <div class="col-md-4 mb-3">
                      <label class="text-muted small">Role applicatif</label>
                      <p class="mb-0">{{ agent.role?.nom_role || 'N/A' }}</p>
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
                    <div v-if="canManageAgentDocuments" class="d-flex gap-2 no-print">
                      <button class="btn btn-sm btn-primary" @click="openDocumentUploadModal">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Ajouter
                      </button>
                      <button class="btn btn-sm btn-outline-primary" :disabled="dossierDownloading" @click="downloadAgentDossier">
                        <i class="fas fa-file-archive me-1"></i> Dossier ZIP
                      </button>
                    </div>
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
                            <p class="mb-1"><strong>{{ doc.nom_document || getDocumentName(doc) }}</strong></p>
                            <small v-if="doc.description_detail" class="text-muted d-block">{{ doc.description_detail }}</small>
                            <small class="text-muted">{{ formatDateTime(doc.created_at) }} - {{ doc.statut || 'valide' }}</small>
                          </div>
                          <div v-if="canManageAgentDocuments" class="d-flex gap-2 no-print">
                            <button class="btn btn-sm btn-outline-primary" @click="downloadAgentDocument(doc)">
                              <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="deleteAgentDocument(doc)">
                              <i class="fas fa-trash"></i>
                            </button>
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
              <button v-if="canManageAgentDocuments" class="btn btn-primary btn-sm w-100 mb-2" @click="openDocumentUploadModal">
                <i class="fas fa-cloud-upload-alt me-2"></i> Ajouter un document
              </button>
              <button v-if="canManageAgentDocuments" class="btn btn-outline-primary btn-sm w-100 mb-2" :disabled="dossierDownloading" @click="downloadAgentDossier">
                <span v-if="dossierDownloading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fas fa-file-archive me-2"></i> Télécharger le dossier complet
              </button>
              <button class="btn btn-warning btn-sm w-100 mb-2" @click="openEditModal">
                <i class="fas fa-edit me-2"></i> Modifier
              </button>
              <button
                class="btn btn-sm w-100"
                :class="canDeleteAgent ? 'btn-danger' : 'btn-secondary'"
                :disabled="!canDeleteAgent"
                :title="canDeleteAgent ? 'Supprimer l agent' : 'Reserve a la Section RH'"
                @click="confirmDelete"
              >
                <i :class="canDeleteAgent ? 'fas fa-trash me-2' : 'fas fa-lock me-2'"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="agent-signature-panel">
        <div>
          <span class="agent-signature-kicker">Validation du dossier administratif</span>
          <strong>{{ senSignatureTitle }}</strong>
        </div>
        <div class="agent-signature-box">
          <div class="agent-signature-line"></div>
          <strong>{{ senSignatureName }}</strong>
          <span>Signature et cachet</span>
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

    <!-- Document Upload Modal -->
    <teleport to="body">
      <div v-if="showDocumentUploadModal" class="agent-doc-overlay" @click.self="closeDocumentUploadModal">
        <div class="agent-doc-dialog">
          <div class="agent-doc-header">
            <div>
              <h5 class="mb-1"><i class="fas fa-cloud-upload-alt me-2"></i>Ajouter un document</h5>
              <p class="mb-0 text-muted small">Dossier de {{ agent?.nom_complet }}</p>
            </div>
            <button class="btn-close" type="button" @click="closeDocumentUploadModal"></button>
          </div>
          <form class="agent-doc-body" @submit.prevent="submitAgentDocument">
            <div v-if="documentUploadErrors.general" class="alert alert-danger py-2">{{ documentUploadErrors.general }}</div>

            <div class="mb-3">
              <label class="form-label fw-bold">Fichier <span class="text-danger">*</span></label>
              <input ref="documentFileInput" type="file" class="form-control" @change="handleDocumentFileChange">
              <div v-if="selectedDocumentFile" class="form-text">{{ selectedDocumentFile.name }} - {{ formatFileSize(selectedDocumentFile.size) }}</div>
              <div v-if="documentUploadErrors.fichier" class="text-danger small mt-1">{{ documentUploadErrors.fichier[0] }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Nom du document <span class="text-danger">*</span></label>
              <input v-model="documentUploadForm.nom_document" type="text" class="form-control" placeholder="Ex. Contrat, diplôme, carte d'identité">
              <div v-if="documentUploadErrors.nom_document" class="text-danger small mt-1">{{ documentUploadErrors.nom_document[0] }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Catégorie</label>
              <select v-model="documentUploadForm.categories_document_id" class="form-select">
                <option v-for="category in DOCUMENT_CATEGORY_OPTIONS" :key="category.value" :value="category.value">
                  {{ category.fullLabel }}
                </option>
              </select>
              <div v-if="selectedDocumentCategory" class="form-text">
                {{ selectedDocumentCategory.description }}
              </div>
              <div v-if="documentUploadErrors.categories_document_id" class="text-danger small mt-1">{{ documentUploadErrors.categories_document_id[0] }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Description</label>
              <textarea v-model="documentUploadForm.description" class="form-control" rows="3" maxlength="500"></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary" @click="closeDocumentUploadModal">Annuler</button>
              <button type="submit" class="btn btn-primary" :disabled="documentUploading">
                <span v-if="documentUploading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-save me-1"></i> Enregistrer
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { get, remove, downloadDossier } from '@/api/agents'
import { create as createDocument, download as downloadDocument, remove as removeDocument } from '@/api/documents'
import { DOCUMENT_CATEGORY_OPTIONS, normalizeDocumentCategory } from '@/constants/documentCategories'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import AgentEditModal from '@/components/agents/AgentEditModal.vue'

const router = useRouter()
const route = useRoute()
const ui = useUiStore()
const auth = useAuthStore()

// State
const loading = ref(true)
const agent = ref(null)
const activeTab = ref('informations')
const showDeleteModal = ref(false)
const deleting = ref(false)
const printing = ref(false)
const showEditModal = ref(false)
const editAgentId = ref(null)
const showDocumentUploadModal = ref(false)
const documentUploading = ref(false)
const dossierDownloading = ref(false)
const documentFileInput = ref(null)
const selectedDocumentFile = ref(null)
const documentUploadErrors = ref({})
const documentUploadForm = ref(defaultDocumentUploadForm())

function defaultDocumentUploadForm() {
    return {
        nom_document: '',
        categories_document_id: 'identite',
        description: '',
    }
}

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
const docTypes = DOCUMENT_CATEGORY_OPTIONS.map((category) => ({
    type: category.value,
    label: category.fullLabel,
    icon: category.icon.replace('fas ', ''),
    color: category.color,
}))

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
const canManageAgentDocuments = computed(() =>
    Boolean(agent.value?.permissions?.can_manage_documents) || auth.isSuperAdmin || auth.isRH
)
const canDeleteAgent = computed(() => auth.canDeleteAgents)
const selectedDocumentCategory = computed(() =>
    DOCUMENT_CATEGORY_OPTIONS.find((category) => category.value === documentUploadForm.value.categories_document_id)
)
const senSignatureTitle = computed(() =>
    agent.value?.signature?.sen_title || 'Secrétaire Exécutif National (SEN)'
)
const senSignatureName = computed(() =>
    agent.value?.signature?.sen_name || 'Nom du SEN non renseigné'
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
    return (agent.value?.documents || []).filter(d => normalizeDocumentCategory(d.type) === type)
}

function getDocumentName(doc) {
    if (doc?.nom_document) return doc.nom_document
    const parts = (doc?.description || '').split(' | ')
    return parts[0] || `Document ${doc?.id || ''}`.trim()
}

function formatFileSize(bytes) {
    if (!bytes) return '0 KB'
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`
    return `${(bytes / 1024).toFixed(1)} KB`
}

function openDocumentUploadModal() {
    if (!canManageAgentDocuments.value) return
    documentUploadForm.value = defaultDocumentUploadForm()
    documentUploadErrors.value = {}
    selectedDocumentFile.value = null
    if (documentFileInput.value) documentFileInput.value.value = ''
    showDocumentUploadModal.value = true
}

function closeDocumentUploadModal() {
    showDocumentUploadModal.value = false
}

function handleDocumentFileChange(event) {
    selectedDocumentFile.value = event.target.files?.[0] || null
}

async function submitAgentDocument() {
    if (!agent.value) return

    documentUploadErrors.value = {}
    documentUploading.value = true

    const payload = new FormData()
    payload.append('agent_id', agent.value.id)
    payload.append('nom_document', documentUploadForm.value.nom_document)
    payload.append('categories_document_id', documentUploadForm.value.categories_document_id)
    if (documentUploadForm.value.description) payload.append('description', documentUploadForm.value.description)
    if (selectedDocumentFile.value) payload.append('fichier', selectedDocumentFile.value)

    try {
        await createDocument(payload)
        ui.addToast('Document ajoute au dossier agent.', 'success')
        showDocumentUploadModal.value = false
        await fetchAgent()
        activeTab.value = 'documents'
    } catch (err) {
        if (err.response?.status === 422) {
            documentUploadErrors.value = err.response.data.errors || {}
        } else {
            documentUploadErrors.value = { general: err.response?.data?.message || 'Erreur lors de l ajout du document.' }
        }
    } finally {
        documentUploading.value = false
    }
}

async function downloadAgentDocument(doc) {
    try {
        const response = await downloadDocument(doc.id)
        const blob = new Blob([response.data])
        const url = window.URL.createObjectURL(blob)
        const link = window.document.createElement('a')
        link.href = url
        link.download = doc.fichier ? doc.fichier.split('/').pop() : `document-${doc.id}`
        window.document.body.appendChild(link)
        link.click()
        window.URL.revokeObjectURL(url)
        window.document.body.removeChild(link)
    } catch {
        ui.addToast('Erreur lors du telechargement du document.', 'danger')
    }
}

async function deleteAgentDocument(doc) {
    if (!canManageAgentDocuments.value) return
    if (!confirm(`Supprimer le document "${getDocumentName(doc)}" ?`)) return

    try {
        await removeDocument(doc.id)
        ui.addToast('Document supprime du dossier agent.', 'success')
        await fetchAgent()
        activeTab.value = 'documents'
    } catch (err) {
        ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression du document.', 'danger')
    }
}

async function downloadAgentDossier() {
    if (!agent.value || !canManageAgentDocuments.value) return

    dossierDownloading.value = true
    try {
        const response = await downloadDossier(agent.value.id)
        const blob = new Blob([response.data], { type: 'application/zip' })
        const url = window.URL.createObjectURL(blob)
        const link = window.document.createElement('a')
        const safeName = (agent.value.nom_complet || `agent-${agent.value.id}`).toLowerCase().replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '')
        link.href = url
        link.download = `dossier-agent-${safeName || agent.value.id}.zip`
        window.document.body.appendChild(link)
        link.click()
        window.URL.revokeObjectURL(url)
        window.document.body.removeChild(link)
    } catch (err) {
        ui.addToast(err.response?.data?.message || 'Erreur lors du telechargement du dossier.', 'danger')
    } finally {
        dossierDownloading.value = false
    }
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
    if (!canDeleteAgent.value) {
        ui.addToast('L assistant RH ne peut pas supprimer un agent.', 'warning')
        return
    }

    showDeleteModal.value = true
}

async function doDelete() {
    if (!agent.value || !canDeleteAgent.value) return
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
.agent-letterhead {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #fff;
    border: 1px solid #dbeafe;
    border-radius: 8px;
    padding: 14px 18px;
    margin-bottom: 16px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
}
.agent-letterhead-logo {
    width: 68px;
    height: 68px;
    object-fit: contain;
    flex: 0 0 auto;
}
.agent-letterhead span {
    display: block;
    color: #64748b;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .03em;
    text-transform: uppercase;
}
.agent-letterhead strong {
    display: block;
    color: #075985;
    font-size: 1.2rem;
    margin-top: 3px;
}
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
.agent-overview-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.agent-overview-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 14px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
    min-width: 0;
}
.agent-overview-label {
    display: block;
    font-size: .72rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 700;
    margin-bottom: 5px;
}
.agent-overview-card strong,
.agent-overview-card small {
    display: block;
    overflow-wrap: anywhere;
}
.agent-overview-card strong {
    color: #0f172a;
    font-size: .94rem;
}
.agent-overview-card small {
    color: #64748b;
    margin-top: 4px;
}
.agent-overview-action {
    width: 100%;
    margin-top: 12px;
    border: 0;
    border-radius: 6px;
    padding: 8px 10px;
    background: #0f766e;
    color: #fff;
    font-weight: 700;
    font-size: .82rem;
}
.agent-overview-action:disabled {
    opacity: .7;
    cursor: not-allowed;
}
.agent-doc-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 2050;
}
.agent-doc-dialog {
    width: min(560px, 100%);
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 24px 80px rgba(15, 23, 42, .3);
    overflow: hidden;
}
.agent-doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
}
.agent-doc-body {
    padding: 1.25rem;
}
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
.agent-signature-panel {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 22px;
    margin: 22px 0 4px;
    break-inside: avoid;
}
.agent-signature-kicker {
    display: block;
    color: #64748b;
    font-size: .78rem;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.agent-signature-panel > div:first-child strong {
    color: #0f172a;
    font-size: 1rem;
}
.agent-signature-box {
    width: min(320px, 100%);
    text-align: center;
}
.agent-signature-line {
    border-top: 1px solid #0f172a;
    margin: 48px 0 10px;
}
.agent-signature-box strong,
.agent-signature-box span {
    display: block;
}
.agent-signature-box strong {
    color: #0f172a;
    font-size: .95rem;
    text-transform: uppercase;
}
.agent-signature-box span {
    color: #64748b;
    font-size: .78rem;
    margin-top: 4px;
}

/* Mobile responsive */
@media (max-width: 767.98px) {
    .agent-letterhead {
        align-items: flex-start;
        padding: 12px;
    }
    .agent-letterhead-logo {
        width: 52px;
        height: 52px;
    }
    .agent-letterhead strong {
        font-size: 1rem;
    }
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
    .agent-overview-grid {
        grid-template-columns: 1fr;
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
    .agent-signature-panel {
        display: block;
        padding: 16px;
    }
    .agent-signature-box {
        margin-top: 18px;
    }
    .table th, .table td {
        padding: 0.4rem 0.5rem;
        font-size: 0.85rem;
    }
}

/* Print styles */
@media print {
    .agent-letterhead {
        box-shadow: none !important;
        border: 1px solid #dbeafe !important;
        padding: 10px 12px;
        margin-bottom: 12px;
    }
    .agent-header {
        background: #0077B5 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        padding: 20px;
        margin-bottom: 15px;
    }
    .agent-header::after { display: none; }

    .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
    .agent-signature-panel {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        margin-top: 20px;
    }

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
