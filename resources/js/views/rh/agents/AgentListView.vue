<template>
  <div class="rh-modern">
    <div class="rh-list-shell">

      <!-- Hero section -->
      <section class="rh-hero">
        <div class="row g-2 align-items-center mb-3">
          <div class="col-lg-8">
            <h1 class="rh-title"><i class="fas fa-users me-2"></i>Gestion des agents</h1>
            <p class="rh-sub">Administrez les profils PNMLS, roles, statuts et informations administratives.</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools d-flex gap-2 justify-content-lg-end">
              <button type="button" class="btn-rh main" style="background:#28a745;border-color:#28a745;" @click="showExportModal = true">
                <i class="fas fa-file-csv me-1"></i> Exporter
              </button>
              <router-link :to="{ name: 'rh.agents.create' }" class="btn-rh main">
                <i class="fas fa-user-plus me-1"></i> Ajouter un agent
              </router-link>
            </div>
          </div>
        </div>

        <!-- Search bar -->
        <div class="row">
          <div class="col-lg-12">
            <form @submit.prevent="applySearch">
              <div class="input-group">
                <input
                  type="text"
                  v-model="searchInput"
                  class="form-control"
                  placeholder="Rechercher... (nom, email, matricule, province, grade, fonction, niveau etude)"
                >
                <button class="btn btn-primary" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button v-if="filters.search" class="btn btn-outline-secondary" type="button" @click="clearSearch">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Filters row -->
        <div class="row mt-3 g-2">
          <div class="col-md-3">
            <select v-model="filters.organe" class="form-select form-select-sm" @change="fetchAgents">
              <option value="">Tous les organes</option>
              <option value="SEN">SEN - Secretariat Executif National</option>
              <option value="SEP">SEP - Secretariat Executif Provincial</option>
              <option value="SEL">SEL - Secretariat Executif Local</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filters.province_id" class="form-select form-select-sm" @change="fetchAgents">
              <option value="">Toutes les provinces</option>
              <option v-for="p in provinces" :key="p.id" :value="p.id">{{ p.nom_province || p.nom }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filters.department_id" class="form-select form-select-sm" @change="fetchAgents">
              <option value="">Tous les departements</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filters.statut" class="form-select form-select-sm" @change="fetchAgents">
              <option value="">Tous les statuts</option>
              <option value="actif">Actif</option>
              <option value="suspendu">Suspendu</option>
              <option value="ancien">Ancien</option>
            </select>
          </div>
        </div>
      </section>

      <!-- Stats (always visible once loaded) -->
      <div v-if="stats.total > 0 && !loading" class="kpi-grid mb-4">
          <div class="kpi-card">
            <div class="kpi-value">{{ stats.total }}</div>
            <div class="kpi-label">Total agents</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value" style="color:#0077B5;">{{ stats.sen }}</div>
            <div class="kpi-label">SEN</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value" style="color:#0ea5e9;">{{ stats.sep }}</div>
            <div class="kpi-label">SEP</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value" style="color:#0d9488;">{{ stats.sel }}</div>
            <div class="kpi-label">SEL</div>
          </div>
        </div>

      <!-- Loading spinner (initial load only) -->
      <LoadingSpinner v-if="loading" message="Chargement des agents..." />

      <template v-else>
        <div :class="{ 'ag-filtering': filtering }">
        <!-- Agents grouped by organe -->
        <template v-if="agentsByOrgane.length > 0">
          <div
            v-for="organe in agentsByOrgane"
            :key="organe.label"
            class="card mb-4 border-0 shadow-sm"
            :style="{ borderTop: '4px solid ' + organe.color }"
          >
            <div class="card-header border-0" :style="{ backgroundColor: organe.bg }">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="card-title mb-0" :style="{ color: organe.color }">
                    <i class="fas" :class="organe.icon" style="margin-right:0.5rem;"></i>{{ organe.label }}
                  </h5>
                  <small class="text-muted">{{ organe.agents.length }} agent{{ organe.agents.length > 1 ? 's' : '' }}</small>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="rh-table-wrap">
                <table class="rh-table">
                  <thead>
                    <tr>
                      <th>Nom et Prenom</th>
                      <th>Email prive</th>
                      <th>Email professionnel</th>
                      <th>Telephone</th>
                      <th>Poste</th>
                      <th v-if="isOrganeNational(organe.label)">Departement/Service</th>
                      <th v-else>Province</th>
                      <th>Matricule de l'Etat</th>
                      <th>Anciennete</th>
                      <th>Statut</th>
                      <!-- <th style="width:100px;">Actions</th> -->
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="agent in organe.agents"
                      :key="agent.id"
                      class="agent-row"
                      style="cursor:pointer;"
                      @click="goToAgent(agent.id)"
                    >
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <div v-if="agent.photo && !agent._photoError" class="agent-avatar">
                            <img :src="'/' + agent.photo" :alt="agent.nom_complet" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" @error="agent._photoError = true">
                          </div>
                          <div v-else class="agent-avatar-initials rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.75rem;font-weight:700;">
                            {{ getInitials(agent) }}
                          </div>
                          <span>{{ agent.prenom }} {{ agent.nom }}</span>
                        </div>
                      </td>
                      <td>{{ agent.email_prive || 'N/A' }}</td>
                      <td>{{ agent.email_professionnel || 'N/A' }}</td>
                      <td>{{ agent.telephone || 'N/A' }}</td>
                      <td>{{ agent.poste_actuel || 'N/A' }}</td>
                      <td v-if="isOrganeNational(organe.label)">
                        {{ agent.departement ? agent.departement.nom : 'Service rattache au SEN' }}
                      </td>
                      <td v-else>{{ agent.province ? agent.province.nom : 'N/A' }}</td>
                      <td>{{ agent.matricule_etat || 'N/A' }}</td>
                      <td>
                        <template v-if="agent.anciennete !== null">
                          {{ agent.anciennete }} an{{ agent.anciennete > 1 ? 's' : '' }}
                        </template>
                        <template v-else>N/A</template>
                      </td>
                      <td>
                        <span v-if="agent.statut === 'actif'" class="rh-pill st-ok">Actif</span>
                        <span v-else-if="agent.statut === 'suspendu'" class="rh-pill st-mid">Suspendu</span>
                        <span v-else class="rh-pill st-neutral">{{ capitalize(agent.statut) }}</span>
                      </td>
                      <!-- Actions column hidden
                      <td @click.stop>
                        <div class="btn-group btn-group-sm">
                          <router-link
                            :to="{ name: 'rh.agents.show', params: { id: agent.id } }"
                            class="btn btn-outline-primary btn-sm"
                            title="Voir"
                          >
                            <i class="fas fa-eye"></i>
                          </router-link>
                          <router-link
                            :to="{ name: 'rh.agents.edit', params: { id: agent.id } }"
                            class="btn btn-outline-warning btn-sm"
                            title="Modifier"
                          >
                            <i class="fas fa-edit"></i>
                          </router-link>
                          <button
                            class="btn btn-outline-danger btn-sm"
                            title="Supprimer"
                            @click="confirmDelete(agent)"
                          >
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </td>
                      -->
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="rh-list-card text-center py-5">
          <i class="fas fa-users fa-4x text-muted mb-3 d-block"></i>
          <h5 class="text-muted">Aucun agent</h5>
          <p class="text-muted">Il n'y a aucun agent enregistre.</p>
          <router-link :to="{ name: 'rh.agents.create' }" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Ajouter un agent
          </router-link>
        </div>
        </div>
      </template>
    </div>

    <!-- Export Modal -->
    <teleport to="body">
      <div v-if="showExportModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog">
          <div class="modal-content border-0">
            <div class="modal-header" style="background:#28a745;color:#fff;">
              <h5 class="modal-title"><i class="fas fa-file-csv me-2"></i>Exporter la liste des agents</h5>
              <button type="button" class="btn-close btn-close-white" @click="showExportModal = false"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Organe</label>
                <select v-model="exportFilters.organe" class="form-select" @change="updateExportFilters">
                  <option value="tous">Tous les organes</option>
                  <option value="SEN">Secretariat Executif National (SEN)</option>
                  <option value="SEP">Secretariat Executif Provincial (SEP)</option>
                  <option value="SEL">Secretariat Executif Local (SEL)</option>
                </select>
              </div>
              <div v-if="showExportProvince" class="mb-3">
                <label class="form-label fw-bold">Province</label>
                <select v-model="exportFilters.province_id" class="form-select">
                  <option value="">Toutes les provinces</option>
                  <option v-for="p in provinces" :key="p.id" :value="p.id">{{ p.nom_province || p.nom }}</option>
                </select>
              </div>
              <div v-if="showExportDept" class="mb-3">
                <label class="form-label fw-bold">Departement</label>
                <select v-model="exportFilters.departement_id" class="form-select">
                  <option value="">Tous les departements</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                </select>
              </div>
              <div class="alert alert-info py-2">
                <i class="fas fa-info-circle me-1"></i>
                Le fichier CSV sera telecharge et peut etre ouvert avec Excel.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showExportModal = false">Annuler</button>
              <button type="button" class="btn btn-success" :disabled="exporting" @click="doExport">
                <span v-if="exporting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-download me-1"></i> Telecharger CSV
              </button>
            </div>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Supprimer l'agent"
      :message="'Etes-vous sur de vouloir supprimer ' + (agentToDelete ? agentToDelete.prenom + ' ' + agentToDelete.nom : '') + ' ? Cette action est irreversible.'"
      :loading="deleting"
      @confirm="doDelete"
      @cancel="showDeleteModal = false"
    />

    <!-- Agent Show Modal -->
    <teleport to="body">
      <div v-if="showAgentModal" class="asm-overlay" @click.self="closeAgentModal">
        <div class="asm-dialog">
          <!-- Loading -->
          <div v-if="agentModalLoading" class="asm-loading">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2 text-muted">Chargement du profil...</p>
          </div>

          <template v-else-if="selectedAgent">
            <!-- Header -->
            <div class="asm-header">
              <div class="asm-header-left">
                <div v-if="selectedAgent.photo && !selectedAgent._photoError" class="asm-avatar">
                  <img :src="'/' + selectedAgent.photo" :alt="selectedAgent.nom" class="asm-avatar-img" @error="selectedAgent._photoError = true">
                </div>
                <div v-else class="asm-avatar asm-avatar-initials">
                  {{ getInitials(selectedAgent) }}
                </div>
                <div>
                  <h4 class="asm-name">{{ selectedAgent.prenom }} {{ selectedAgent.nom }}</h4>
                  <div class="asm-badges">
                    <span class="asm-badge asm-badge-id">{{ selectedAgent.id_agent }}</span>
                    <span v-if="selectedAgent.organe" class="asm-badge asm-badge-organe">{{ selectedAgent.organe }}</span>
                    <span v-if="selectedAgent.statut === 'actif'" class="asm-badge asm-badge-ok">Actif</span>
                    <span v-else-if="selectedAgent.statut === 'suspendu'" class="asm-badge asm-badge-warn">Suspendu</span>
                    <span v-else class="asm-badge asm-badge-neutral">{{ capitalize(selectedAgent.statut) }}</span>
                  </div>
                  <div v-if="selectedAgent.fonction" class="asm-fonction">{{ selectedAgent.fonction }}</div>
                </div>
              </div>
              <button class="asm-close" @click="closeAgentModal"><i class="fas fa-times"></i></button>
            </div>

            <!-- Tabs -->
            <div class="asm-tabs">
              <button class="asm-tab" :class="{ active: agentTab === 'informations' }" @click="agentTab = 'informations'">
                <i class="fas fa-user me-1"></i> Infos
              </button>
              <button class="asm-tab" :class="{ active: agentTab === 'demandes' }" @click="agentTab = 'demandes'">
                <i class="fas fa-file-signature me-1"></i> Demandes
                <span v-if="sm_pendingRequestsCount > 0" class="asm-tab-badge warn">{{ sm_pendingRequestsCount }}</span>
              </button>
              <button class="asm-tab" :class="{ active: agentTab === 'parcours' }" @click="agentTab = 'parcours'">
                <i class="fas fa-route me-1"></i> Parcours
              </button>
              <button class="asm-tab" :class="{ active: agentTab === 'documents' }" @click="agentTab = 'documents'">
                <i class="fas fa-folder me-1"></i> Docs
                <span v-if="sm_documentsCount > 0" class="asm-tab-badge">{{ sm_documentsCount }}</span>
              </button>
              <button class="asm-tab" :class="{ active: agentTab === 'messages' }" @click="agentTab = 'messages'">
                <i class="fas fa-envelope me-1"></i> Msgs
                <span v-if="sm_unreadMessagesCount > 0" class="asm-tab-badge warn">{{ sm_unreadMessagesCount }}</span>
              </button>
            </div>

            <!-- Tab content -->
            <div class="asm-body">

              <!-- TAB: Informations -->
              <div v-if="agentTab === 'informations'">
                <div class="asm-section-title"><i class="fas fa-user me-1"></i> Informations personnelles</div>
                <div class="asm-info-grid">
                  <div class="asm-info-item"><span class="asm-info-label">Prenom</span><span class="asm-info-value">{{ selectedAgent.prenom }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Post-nom</span><span class="asm-info-value">{{ selectedAgent.postnom || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Nom</span><span class="asm-info-value">{{ selectedAgent.nom }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Date naissance</span><span class="asm-info-value">{{ sm_formatDate(selectedAgent.date_naissance) }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Lieu naissance</span><span class="asm-info-value">{{ selectedAgent.lieu_naissance || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Sexe</span><span class="asm-info-value">{{ selectedAgent.sexe === 'M' ? 'Masculin' : selectedAgent.sexe === 'F' ? 'Feminin' : (selectedAgent.sexe || 'N/A') }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Situation familiale</span><span class="asm-info-value">{{ selectedAgent.situation_familiale || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Enfants</span><span class="asm-info-value">{{ selectedAgent.nombre_enfants ?? 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Telephone</span><span class="asm-info-value">{{ selectedAgent.telephone || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Email prive</span><span class="asm-info-value">{{ selectedAgent.email_prive || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Email pro</span><span class="asm-info-value">{{ selectedAgent.email_professionnel || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Adresse</span><span class="asm-info-value">{{ selectedAgent.adresse || 'N/A' }}</span></div>
                </div>

                <div class="asm-section-title mt-3"><i class="fas fa-briefcase me-1"></i> Informations professionnelles</div>
                <div class="asm-info-grid">
                  <div class="asm-info-item"><span class="asm-info-label">Organe</span><span class="asm-info-value fw-bold">{{ selectedAgent.organe || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Fonction</span><span class="asm-info-value fw-bold">{{ selectedAgent.fonction || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Date embauche</span><span class="asm-info-value">{{ sm_formatDate(selectedAgent.date_embauche) }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Province</span><span class="asm-info-value">{{ selectedAgent.province ? (selectedAgent.province.nom_province || selectedAgent.province.nom) : 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Departement</span><span class="asm-info-value">{{ selectedAgent.departement ? selectedAgent.departement.nom : 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Anciennete</span><span class="asm-info-value">{{ selectedAgent.anciennete !== null && selectedAgent.anciennete !== undefined ? selectedAgent.anciennete + ' an' + (selectedAgent.anciennete > 1 ? 's' : '') : 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Matricule Etat</span><span class="asm-info-value">{{ selectedAgent.matricule_etat || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Grade Etat</span><span class="asm-info-value">{{ selectedAgent.grade ? selectedAgent.grade.libelle : 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Niveau etudes</span><span class="asm-info-value">{{ selectedAgent.niveau_etudes || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Domaine etudes</span><span class="asm-info-value">{{ selectedAgent.domaine_etudes || 'N/A' }}</span></div>
                  <div class="asm-info-item"><span class="asm-info-label">Annee engagement</span><span class="asm-info-value">{{ selectedAgent.annee_engagement_programme || 'N/A' }}</span></div>
                </div>

                <!-- Stats row -->
                <div class="asm-stats-row mt-3">
                  <div class="asm-stat"><span class="asm-stat-val">{{ sm_documentsCount }}</span><span class="asm-stat-lbl">Documents</span></div>
                  <div class="asm-stat"><span class="asm-stat-val">{{ sm_requestsCount }}</span><span class="asm-stat-lbl">Demandes</span></div>
                  <div class="asm-stat"><span class="asm-stat-val text-warning">{{ sm_pendingRequestsCount }}</span><span class="asm-stat-lbl">En attente</span></div>
                  <div class="asm-stat"><span class="asm-stat-val">{{ sm_affectationsCount }}</span><span class="asm-stat-lbl">Affectations</span></div>
                  <div class="asm-stat"><span class="asm-stat-val">{{ sm_messagesCount }}</span><span class="asm-stat-lbl">Messages</span></div>
                </div>
              </div>

              <!-- TAB: Demandes -->
              <div v-if="agentTab === 'demandes'">
                <template v-if="sm_requestsCount > 0">
                  <div v-for="req in sm_sortedRequests" :key="req.id" class="asm-req-card">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <strong>{{ capitalize(req.type) }}</strong>
                        <div class="text-muted small">{{ sm_truncate(req.description, 80) }}</div>
                        <div class="text-muted small mt-1">{{ sm_formatDate(req.date_debut) }} - {{ sm_formatDate(req.date_fin) }}</div>
                      </div>
                      <span v-if="req.statut === 'en_attente'" class="asm-badge asm-badge-warn">En attente</span>
                      <span v-else-if="req.statut === 'approuve'" class="asm-badge asm-badge-ok">Approuve</span>
                      <span v-else-if="req.statut === 'rejete'" class="asm-badge asm-badge-err">Rejete</span>
                      <span v-else class="asm-badge asm-badge-neutral">Annule</span>
                    </div>
                  </div>
                </template>
                <div v-else class="asm-empty"><i class="fas fa-file-signature fa-2x mb-2"></i><p>Aucune demande</p></div>
              </div>

              <!-- TAB: Parcours -->
              <div v-if="agentTab === 'parcours'">
                <template v-if="sm_affectationsCount > 0">
                  <div v-for="aff in sm_sortedAffectations" :key="aff.id" class="asm-timeline-item">
                    <div class="asm-tl-dot" :class="aff.actif ? 'active' : ''"></div>
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <strong>{{ aff.fonction ? aff.fonction.nom : 'Non definie' }}</strong>
                        <div class="d-flex gap-1 mt-1 flex-wrap">
                          <span class="asm-badge asm-badge-organe" style="font-size:.65rem;">{{ aff.niveau_administratif }}</span>
                          <span class="asm-badge asm-badge-neutral" style="font-size:.65rem;">{{ capitalize(aff.niveau || '') }}</span>
                        </div>
                        <div v-if="aff.department" class="text-muted small mt-1"><i class="fas fa-building me-1"></i>{{ aff.department.nom }}</div>
                        <div v-if="aff.province" class="text-muted small"><i class="fas fa-map-marker-alt me-1"></i>{{ aff.province.nom || aff.province.nom_province }}</div>
                        <div v-if="aff.remarque" class="text-muted small fst-italic mt-1">{{ aff.remarque }}</div>
                      </div>
                      <div class="text-end">
                        <span v-if="aff.actif" class="asm-badge asm-badge-ok">En cours</span>
                        <span v-else class="asm-badge asm-badge-neutral">Termine</span>
                        <div class="text-muted small mt-1">{{ sm_formatDate(aff.date_debut) }}<template v-if="aff.date_fin"> - {{ sm_formatDate(aff.date_fin) }}</template><template v-else-if="aff.actif"> - Auj.</template></div>
                      </div>
                    </div>
                  </div>
                </template>
                <div v-else class="asm-empty"><i class="fas fa-route fa-2x mb-2"></i><p>Aucune affectation</p></div>
              </div>

              <!-- TAB: Documents -->
              <div v-if="agentTab === 'documents'">
                <template v-if="sm_documentsCount > 0">
                  <div v-for="dt in sm_docTypes" :key="dt.type" class="mb-3">
                    <div class="asm-section-title" style="font-size:.8rem;">
                      <i class="fas me-1" :class="[dt.icon, dt.color]"></i> {{ dt.label }}
                      <span class="asm-badge asm-badge-neutral" style="font-size:.6rem;">{{ sm_getDocsByType(dt.type).length }}</span>
                    </div>
                    <div v-if="sm_getDocsByType(dt.type).length > 0">
                      <div v-for="doc in sm_getDocsByType(dt.type)" :key="doc.id" class="asm-doc-item">
                        <strong>{{ doc.description }}</strong>
                        <small class="text-muted ms-2">{{ sm_formatDateTime(doc.created_at) }}</small>
                      </div>
                    </div>
                    <p v-else class="text-muted small ms-3">Aucun</p>
                  </div>
                </template>
                <div v-else class="asm-empty"><i class="fas fa-folder fa-2x mb-2"></i><p>Aucun document</p></div>
              </div>

              <!-- TAB: Messages -->
              <div v-if="agentTab === 'messages'">
                <template v-if="sm_messagesCount > 0">
                  <div v-for="msg in sm_sortedMessages" :key="msg.id" class="asm-msg-card" :class="{ unread: !msg.lu }">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                      <div>
                        <strong>{{ msg.sujet }}</strong>
                        <div class="text-muted small"><i class="fas fa-user me-1"></i>{{ msg.sender ? msg.sender.name : 'Systeme' }} &bull; {{ sm_formatDateTime(msg.created_at) }}</div>
                      </div>
                      <span v-if="!msg.lu" class="asm-badge asm-badge-warn">Non lu</span>
                    </div>
                    <p class="mb-0 small">{{ msg.contenu }}</p>
                  </div>
                </template>
                <div v-else class="asm-empty"><i class="fas fa-envelope fa-2x mb-2"></i><p>Aucun message</p></div>
              </div>
            </div>

            <!-- Footer -->
            <div class="asm-footer">
              <button class="asm-btn-print" @click="printAgentFiche">
                <i class="fas fa-print me-1"></i> Imprimer
              </button>
              <button class="asm-btn-edit" @click="openEditModal">
                <i class="fas fa-edit me-1"></i> Modifier
              </button>
              <button class="asm-btn-close" @click="closeAgentModal">Fermer</button>
            </div>
          </template>
        </div>
      </div>
    </teleport>

    <!-- Agent Edit Modal -->
    <AgentEditModal
      :show="showEditModal"
      :agent-id="agentToEdit"
      @close="closeEditModal"
      @updated="onAgentUpdated"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { list, get, remove, exportCsv, getFormOptions } from '@/api/agents'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import AgentEditModal from '@/components/agents/AgentEditModal.vue'

const router = useRouter()
const ui = useUiStore()

// State
const loading = ref(true)
const filtering = ref(false)
const initialLoadDone = ref(false)
const agentsByOrgane = ref([])
const stats = ref({ total: 0, sen: 0, sep: 0, sel: 0 })
const provinces = ref([])
const departments = ref([])

// Search and filters
const searchInput = ref('')
const filters = reactive({
    search: '',
    organe: '',
    province_id: '',
    department_id: '',
    statut: '',
})

// Delete modal
const showDeleteModal = ref(false)
const agentToDelete = ref(null)
const deleting = ref(false)

// Export modal
const showExportModal = ref(false)
const exporting = ref(false)
const exportFilters = reactive({
    organe: 'tous',
    province_id: '',
    departement_id: '',
})

// Edit modal
const showEditModal = ref(false)
const agentToEdit = ref(null)

const showExportProvince = computed(() => {
    const val = exportFilters.organe
    return val === 'SEP' || val === 'SEL' || val === 'tous'
})

const showExportDept = computed(() => {
    const val = exportFilters.organe
    return val === 'SEN' || val === 'tous'
})

function updateExportFilters() {
    if (!showExportProvince.value) exportFilters.province_id = ''
    if (!showExportDept.value) exportFilters.departement_id = ''
}

// Helpers
function capitalize(str) {
    if (!str) return ''
    return str.charAt(0).toUpperCase() + str.slice(1)
}

function getInitials(agent) {
    const p = agent.prenom ? agent.prenom.charAt(0).toUpperCase() : ''
    const n = agent.nom ? agent.nom.charAt(0).toUpperCase() : ''
    return p + n
}

function isOrganeNational(label) {
    return label && label.toLowerCase().includes('national')
}

/* ── Agent Show Modal ── */
const showAgentModal = ref(false)
const agentModalLoading = ref(false)
const selectedAgent = ref(null)
const agentTab = ref('informations')

const sm_documentsCount = computed(() => selectedAgent.value?.documents?.length || 0)
const sm_requestsCount = computed(() => selectedAgent.value?.requests?.length || 0)
const sm_pendingRequestsCount = computed(() =>
    (selectedAgent.value?.requests || []).filter(r => r.statut === 'en_attente').length
)
const sm_affectationsCount = computed(() => selectedAgent.value?.affectations?.length || 0)
const sm_messagesCount = computed(() => selectedAgent.value?.messages?.length || 0)
const sm_unreadMessagesCount = computed(() =>
    (selectedAgent.value?.messages || []).filter(m => !m.lu).length
)
const sm_sortedRequests = computed(() =>
    [...(selectedAgent.value?.requests || [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
)
const sm_sortedAffectations = computed(() =>
    [...(selectedAgent.value?.affectations || [])].sort((a, b) => new Date(b.date_debut) - new Date(a.date_debut))
)
const sm_sortedMessages = computed(() =>
    [...(selectedAgent.value?.messages || [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
)

const sm_docTypes = [
    { type: 'identite', label: 'Identite', icon: 'fa-id-card', color: 'text-danger' },
    { type: 'parcours', label: 'Parcours', icon: 'fa-graduation-cap', color: 'text-info' },
    { type: 'carriere', label: 'Carriere', icon: 'fa-briefcase', color: 'text-warning' },
    { type: 'mission', label: 'Missions', icon: 'fa-plane', color: 'text-success' },
]

function sm_getDocsByType(type) {
    return (selectedAgent.value?.documents || []).filter(d => d.type === type)
}

function sm_formatDate(dateStr) {
    if (!dateStr) return 'N/A'
    try {
        const d = new Date(dateStr)
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
    } catch { return dateStr }
}

function sm_formatDateTime(dateStr) {
    if (!dateStr) return 'N/A'
    try {
        const d = new Date(dateStr)
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
            ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    } catch { return dateStr }
}

function sm_truncate(str, length) {
    if (!str) return ''
    return str.length > length ? str.substring(0, length) + '...' : str
}

async function goToAgent(id) {
    selectedAgent.value = null
    agentTab.value = 'informations'
    showAgentModal.value = true
    agentModalLoading.value = true
    try {
        const { data } = await get(id)
        selectedAgent.value = data.agent
    } catch (err) {
        console.error('Error fetching agent:', err)
        ui.addToast('Erreur lors du chargement de l\'agent', 'danger')
        showAgentModal.value = false
    } finally {
        agentModalLoading.value = false
    }
}

function closeAgentModal() { showAgentModal.value = false }

function printAgentFiche() {
    const a = selectedAgent.value
    if (!a) return

    const sexeLabel = a.sexe === 'M' ? 'Masculin' : a.sexe === 'F' ? 'Feminin' : (a.sexe || 'N/A')
    const provinceName = a.province ? (a.province.nom_province || a.province.nom) : 'N/A'
    const deptName = a.departement ? a.departement.nom : 'N/A'
    const gradeName = a.grade ? a.grade.libelle : 'N/A'
    const anciennete = a.anciennete != null ? a.anciennete + ' an' + (a.anciennete > 1 ? 's' : '') : 'N/A'

    const photoHtml = a.photo
        ? `<img src="/${a.photo}" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #0077B5;">`
        : `<div style="width:90px;height:90px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#0077B5;border:3px solid #0077B5;">${(a.prenom || '').charAt(0).toUpperCase()}${(a.nom || '').charAt(0).toUpperCase()}</div>`

    const affRows = (a.affectations || [])
        .sort((x, y) => new Date(y.date_debut) - new Date(x.date_debut))
        .map(aff => `<tr>
            <td>${aff.fonction ? aff.fonction.nom : 'N/A'}</td>
            <td>${aff.niveau_administratif || 'N/A'}</td>
            <td>${aff.department ? aff.department.nom : '-'}</td>
            <td>${aff.province ? (aff.province.nom || aff.province.nom_province) : '-'}</td>
            <td>${sm_formatDate(aff.date_debut)}</td>
            <td>${aff.date_fin ? sm_formatDate(aff.date_fin) : (aff.actif ? 'En cours' : '-')}</td>
        </tr>`).join('')

    const reqRows = (a.requests || [])
        .sort((x, y) => new Date(y.created_at) - new Date(x.created_at))
        .map(r => `<tr>
            <td>${capitalize(r.type)}</td>
            <td>${r.description ? r.description.substring(0, 60) : '-'}</td>
            <td>${sm_formatDate(r.date_debut)} - ${sm_formatDate(r.date_fin)}</td>
            <td>${r.statut === 'en_attente' ? 'En attente' : r.statut === 'approuve' ? 'Approuve' : r.statut === 'rejete' ? 'Rejete' : 'Annule'}</td>
        </tr>`).join('')

    const html = `<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>Fiche Agent - ${a.prenom} ${a.nom}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Tahoma,sans-serif;font-size:12px;color:#1e293b;padding:20px 30px;}
.header{display:flex;align-items:center;gap:20px;border-bottom:3px solid #0077B5;padding-bottom:15px;margin-bottom:20px;}
.header-info h1{font-size:20px;color:#0077B5;margin-bottom:4px;}
.header-info .badges span{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600;margin-right:5px;}
.badge-id{background:#e2e8f0;color:#475569;}
.badge-organe{background:#dbeafe;color:#1e40af;}
.badge-actif{background:#dcfce7;color:#166534;}
.badge-suspendu{background:#fef9c3;color:#854d0e;}
.section{margin-bottom:18px;}
.section h2{font-size:13px;color:#0077B5;border-bottom:1.5px solid #e2e8f0;padding-bottom:4px;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;}
.info-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px 16px;}
.info-item .label{font-size:10px;color:#94a3b8;font-weight:500;display:block;}
.info-item .value{font-size:12px;}
table{width:100%;border-collapse:collapse;font-size:11px;}
th{background:#f1f5f9;color:#475569;text-align:left;padding:5px 8px;font-size:10px;text-transform:uppercase;}
td{padding:5px 8px;border-bottom:1px solid #f1f5f9;}
.stats{display:flex;gap:12px;margin-top:10px;}
.stat{flex:1;text-align:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 4px;}
.stat-val{font-size:18px;font-weight:800;color:#1e293b;display:block;}
.stat-lbl{font-size:9px;color:#64748b;text-transform:uppercase;font-weight:600;}
.footer{margin-top:24px;text-align:center;font-size:10px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:10px;}
@media print{body{padding:10px 20px;} @page{margin:10mm;}}
</style></head><body>
<div class="header">
    ${photoHtml}
    <div class="header-info">
        <h1>${a.prenom} ${a.postnom || ''} ${a.nom}</h1>
        <div>${a.fonction || ''}</div>
        <div class="badges" style="margin-top:4px;">
            <span class="badge-id">${a.id_agent}</span>
            ${a.organe ? `<span class="badge-organe">${a.organe}</span>` : ''}
            <span class="${a.statut === 'actif' ? 'badge-actif' : 'badge-suspendu'}">${capitalize(a.statut || '')}</span>
        </div>
    </div>
</div>

<div class="section">
    <h2>Informations personnelles</h2>
    <div class="info-grid">
        <div class="info-item"><span class="label">Prenom</span><span class="value">${a.prenom || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Post-nom</span><span class="value">${a.postnom || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Nom</span><span class="value">${a.nom || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Date naissance</span><span class="value">${sm_formatDate(a.date_naissance)}</span></div>
        <div class="info-item"><span class="label">Lieu naissance</span><span class="value">${a.lieu_naissance || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Sexe</span><span class="value">${sexeLabel}</span></div>
        <div class="info-item"><span class="label">Situation familiale</span><span class="value">${a.situation_familiale || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Enfants</span><span class="value">${a.nombre_enfants ?? 'N/A'}</span></div>
        <div class="info-item"><span class="label">Telephone</span><span class="value">${a.telephone || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Email prive</span><span class="value">${a.email_prive || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Email pro</span><span class="value">${a.email_professionnel || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Adresse</span><span class="value">${a.adresse || 'N/A'}</span></div>
    </div>
</div>

<div class="section">
    <h2>Informations professionnelles</h2>
    <div class="info-grid">
        <div class="info-item"><span class="label">Organe</span><span class="value" style="font-weight:700;">${a.organe || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Fonction</span><span class="value" style="font-weight:700;">${a.fonction || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Date embauche</span><span class="value">${sm_formatDate(a.date_embauche)}</span></div>
        <div class="info-item"><span class="label">Province</span><span class="value">${provinceName}</span></div>
        <div class="info-item"><span class="label">Departement</span><span class="value">${deptName}</span></div>
        <div class="info-item"><span class="label">Anciennete</span><span class="value">${anciennete}</span></div>
        <div class="info-item"><span class="label">Matricule Etat</span><span class="value">${a.matricule_etat || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Grade Etat</span><span class="value">${gradeName}</span></div>
        <div class="info-item"><span class="label">Niveau etudes</span><span class="value">${a.niveau_etudes || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Domaine etudes</span><span class="value">${a.domaine_etudes || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Annee engagement</span><span class="value">${a.annee_engagement_programme || 'N/A'}</span></div>
    </div>
    <div class="stats">
        <div class="stat"><span class="stat-val">${(a.documents || []).length}</span><span class="stat-lbl">Documents</span></div>
        <div class="stat"><span class="stat-val">${(a.requests || []).length}</span><span class="stat-lbl">Demandes</span></div>
        <div class="stat"><span class="stat-val">${(a.affectations || []).length}</span><span class="stat-lbl">Affectations</span></div>
        <div class="stat"><span class="stat-val">${(a.messages || []).length}</span><span class="stat-lbl">Messages</span></div>
    </div>
</div>

${affRows ? `<div class="section">
    <h2>Parcours / Affectations</h2>
    <table><thead><tr><th>Fonction</th><th>Niveau</th><th>Departement</th><th>Province</th><th>Debut</th><th>Fin</th></tr></thead>
    <tbody>${affRows}</tbody></table>
</div>` : ''}

${reqRows ? `<div class="section">
    <h2>Demandes</h2>
    <table><thead><tr><th>Type</th><th>Description</th><th>Periode</th><th>Statut</th></tr></thead>
    <tbody>${reqRows}</tbody></table>
</div>` : ''}

<div class="footer">Fiche generee le ${new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })} - Portail RH PNMLS</div>
</body></html>`

    const w = window.open('', '_blank')
    w.document.write(html)
    w.document.close()
    w.onload = () => { w.print() }
}

// Edit Modal functions
function openEditModal() {
    if (selectedAgent.value) {
        agentToEdit.value = selectedAgent.value.id
        showEditModal.value = true
    }
}

function closeEditModal() {
    showEditModal.value = false
    agentToEdit.value = null
}

function onAgentUpdated() {
    closeEditModal()
    fetchAgents() // Refresh the list
    if (selectedAgent.value) {
        // Refresh the selected agent in the modal
        goToAgent(selectedAgent.value.id)
    }
}

// Fetch agents
async function fetchAgents() {
    if (!initialLoadDone.value) {
        loading.value = true
    }
    filtering.value = true
    try {
        const params = {}
        if (filters.search) params.search = filters.search
        if (filters.organe) params.organe = filters.organe
        if (filters.province_id) params.province_id = filters.province_id
        if (filters.department_id) params.department_id = filters.department_id
        if (filters.statut) params.statut = filters.statut

        const { data } = await list(params)
        agentsByOrgane.value = data.agentsByOrgane || []
        stats.value = data.stats || { total: 0, sen: 0, sep: 0, sel: 0 }
    } catch (err) {
        console.error('Error fetching agents:', err)
        ui.addToast('Erreur lors du chargement des agents', 'danger')
    } finally {
        loading.value = false
        filtering.value = false
        initialLoadDone.value = true
    }
}

// Fetch form options (provinces, departments for filters)
async function fetchOptions() {
    try {
        const { data } = await getFormOptions()
        provinces.value = data.provinces || []
        departments.value = data.departments || []
    } catch (err) {
        console.error('Error fetching options:', err)
    }
}

// Search
function applySearch() {
    filters.search = searchInput.value
    fetchAgents()
}

function clearSearch() {
    searchInput.value = ''
    filters.search = ''
    fetchAgents()
}

// Delete
function confirmDelete(agent) {
    agentToDelete.value = agent
    showDeleteModal.value = true
}

async function doDelete() {
    if (!agentToDelete.value) return
    deleting.value = true
    try {
        await remove(agentToDelete.value.id)
        ui.addToast('Agent supprime avec succes', 'success')
        showDeleteModal.value = false
        agentToDelete.value = null
        await fetchAgents()
    } catch (err) {
        console.error('Error deleting agent:', err)
        ui.addToast('Erreur lors de la suppression', 'danger')
    } finally {
        deleting.value = false
    }
}

// Export
async function doExport() {
    exporting.value = true
    try {
        const params = {}
        if (exportFilters.organe && exportFilters.organe !== 'tous') params.organe = exportFilters.organe
        if (exportFilters.province_id) params.province_id = exportFilters.province_id
        if (exportFilters.departement_id) params.departement_id = exportFilters.departement_id

        const response = await exportCsv(params)

        // Create download link from blob
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url

        // Extract filename from Content-Disposition header or use default
        const disposition = response.headers['content-disposition']
        let filename = 'agents_export.csv'
        if (disposition) {
            const match = disposition.match(/filename="?([^";\s]+)"?/)
            if (match) filename = match[1]
        }
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        showExportModal.value = false
        ui.addToast('Export CSV telecharge avec succes', 'success')
    } catch (err) {
        console.error('Error exporting:', err)
        ui.addToast('Erreur lors de l\'export', 'danger')
    } finally {
        exporting.value = false
    }
}

onMounted(() => {
    fetchOptions()
    fetchAgents()
})
</script>

<style scoped>
/* ── Filtering overlay ── */
.ag-filtering { opacity: 0.4; pointer-events: none; transition: opacity .2s; }

/* ── KPI cards ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .75rem;
}
.kpi-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: .9rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.kpi-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
}
.kpi-label {
    font-size: .72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-top: .35rem;
}

/* ── Card header organe ── */
.card-header {
    padding: .85rem 1rem;
}
.card-title {
    font-weight: 800;
    font-size: 1rem;
}

/* ── Table ── */
.rh-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.rh-table {
    width: 100%;
    border-collapse: collapse;
}
.rh-table th, .rh-table td {
    padding: .55rem .5rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: .84rem;
}
.rh-table th {
    font-size: .72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .04em;
    background: #f8fafc;
    white-space: nowrap;
}
.agent-row:hover {
    background: #f0f9ff;
}

/* ── Action buttons ── */
.btn-group-sm .btn {
    padding: .25rem .45rem;
    font-size: .75rem;
}

/* ── Form controls in hero ── */
.rh-hero .form-control,
.rh-hero .form-select {
    border-radius: 10px;
    font-size: .85rem;
    border: 1.5px solid rgba(255,255,255,.25);
    background: rgba(255,255,255,.15);
    color: #fff;
}
.rh-hero .form-control::placeholder { color: rgba(255,255,255,.6); }
.rh-hero .form-control:focus,
.rh-hero .form-select:focus {
    background: rgba(255,255,255,.25);
    border-color: rgba(255,255,255,.5);
    color: #fff;
    box-shadow: none;
}
.rh-hero .form-select option { color: #333; background: #fff; }
.rh-hero .btn-primary {
    background: rgba(255,255,255,.2);
    border: 1.5px solid rgba(255,255,255,.3);
    color: #fff;
}
.rh-hero .btn-outline-secondary {
    border-color: rgba(255,255,255,.3);
    color: rgba(255,255,255,.8);
}

/* ── Modal ── */
.modal-content { border-radius: 16px; overflow: hidden; }

/* ═══════════════════════════════════════════
   Responsive
   ═══════════════════════════════════════════ */
@media (max-width: 767.98px) {
    /* KPI grid */
    .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: .5rem; }
    .kpi-card { padding: .65rem .5rem; border-radius: 10px; }
    .kpi-value { font-size: 1.2rem; }
    .kpi-label { font-size: .65rem; }

    /* Card header */
    .card-header { padding: .65rem .75rem; }
    .card-title { font-size: .88rem; }

    /* Table — hide secondary columns on mobile */
    .rh-table th:nth-child(3),
    .rh-table td:nth-child(3),
    .rh-table th:nth-child(4),
    .rh-table td:nth-child(4),
    .rh-table th:nth-child(7),
    .rh-table td:nth-child(7),
    .rh-table th:nth-child(8),
    .rh-table td:nth-child(8) {
        display: none;
    }

    .rh-table th, .rh-table td {
        padding: .4rem .3rem;
        font-size: .76rem;
    }
    .rh-table th { font-size: .65rem; }

    /* Avatar */
    .agent-avatar-initials,
    .agent-avatar img {
        width: 26px !important;
        height: 26px !important;
        font-size: .65rem !important;
    }

    /* Action buttons */
    .btn-group-sm .btn {
        padding: .2rem .35rem;
        font-size: .68rem;
    }

    /* Hero filters */
    .rh-hero .input-group {
        flex-wrap: nowrap;
    }
    .rh-hero .form-control,
    .rh-hero .form-select {
        font-size: .8rem;
    }

    /* Modal */
    .modal-dialog { margin: .75rem; }
    .modal-header { padding: .75rem 1rem; }
    .modal-header h5 { font-size: .95rem; }
    .modal-body { padding: .9rem; }
    .modal-footer { padding: .6rem .9rem; }
}

@media (max-width: 575.98px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .kpi-value { font-size: 1rem; }

    /* Hide even more columns on very small screens */
    .rh-table th:nth-child(5),
    .rh-table td:nth-child(5),
    .rh-table th:nth-child(6),
    .rh-table td:nth-child(6) {
        display: none;
    }

    .rh-table th, .rh-table td {
        padding: .35rem .25rem;
        font-size: .72rem;
    }
}

/* ── Agent Show Modal (asm-*) ── */
.asm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: asmFadeIn .2s ease;
}
@keyframes asmFadeIn { from { opacity: 0; } to { opacity: 1; } }

.asm-dialog {
  background: #fff; border-radius: 20px; width: 100%; max-width: 720px;
  max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.25);
  animation: asmSlideUp .25s ease;
}
@keyframes asmSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.asm-loading { text-align: center; padding: 3rem; }

.asm-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f3f4f6;
  background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
  border-radius: 20px 20px 0 0; color: #fff;
}
.asm-header-left { display: flex; align-items: center; gap: .8rem; }
.asm-avatar {
  width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.3); overflow: hidden;
  display: flex; align-items: center; justify-content: center;
}
.asm-avatar-img { width: 100%; height: 100%; object-fit: cover; }
.asm-avatar-initials { background: rgba(255,255,255,.15); font-size: 1.2rem; font-weight: 700; color: #fff; }
.asm-name { font-size: 1.05rem; font-weight: 700; margin: 0; }
.asm-badges { display: flex; gap: .3rem; flex-wrap: wrap; margin-top: .2rem; }
.asm-badge { padding: .15rem .5rem; border-radius: 6px; font-size: .68rem; font-weight: 600; }
.asm-badge-id { background: rgba(255,255,255,.2); color: #fff; }
.asm-badge-organe { background: #0ea5e9; color: #fff; }
.asm-badge-ok { background: #22c55e; color: #fff; }
.asm-badge-warn { background: #f59e0b; color: #fff; }
.asm-badge-err { background: #ef4444; color: #fff; }
.asm-badge-neutral { background: #e2e8f0; color: #64748b; }
.asm-fonction { font-size: .78rem; opacity: .8; margin-top: .2rem; }
.asm-close {
  background: rgba(255,255,255,.15); border: none; cursor: pointer;
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; color: #fff;
  transition: all .2s; font-size: .95rem; flex-shrink: 0;
}
.asm-close:hover { background: rgba(255,255,255,.3); }

/* Tabs */
.asm-tabs {
  display: flex; border-bottom: 1px solid #f3f4f6;
  padding: 0 1rem; overflow-x: auto; background: #f8fafc;
}
.asm-tab {
  padding: .65rem .8rem; border: none; background: none;
  font-size: .78rem; font-weight: 600; color: #64748b;
  cursor: pointer; white-space: nowrap; border-bottom: 2px solid transparent;
  transition: all .2s;
}
.asm-tab:hover { color: #0077B5; }
.asm-tab.active { color: #0077B5; border-bottom-color: #0077B5; }
.asm-tab-badge {
  display: inline-block; min-width: 16px; padding: 0 4px; border-radius: 8px;
  font-size: .6rem; font-weight: 700; text-align: center; margin-left: 3px;
  background: #e2e8f0; color: #64748b;
}
.asm-tab-badge.warn { background: #f59e0b; color: #fff; }

/* Body */
.asm-body { padding: 1.25rem 1.5rem; max-height: 55vh; overflow-y: auto; }

.asm-section-title {
  font-size: .82rem; font-weight: 700; color: #0077B5;
  padding-bottom: .3rem; border-bottom: 1px solid #f3f4f6; margin-bottom: .6rem;
}

.asm-info-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem;
}
.asm-info-item { padding: .3rem 0; }
.asm-info-label { display: block; font-size: .68rem; color: #94a3b8; font-weight: 500; }
.asm-info-value { font-size: .82rem; color: #1e293b; }

.asm-stats-row {
  display: flex; gap: .5rem; padding: .75rem; background: #f8fafc;
  border-radius: 10px; border: 1px solid #e2e8f0;
}
.asm-stat { flex: 1; text-align: center; }
.asm-stat-val { display: block; font-size: 1.1rem; font-weight: 800; color: #1e293b; }
.asm-stat-lbl { font-size: .62rem; color: #64748b; text-transform: uppercase; font-weight: 600; }

/* Request cards */
.asm-req-card {
  border-left: 3px solid #0077B5; background: #f8fafc;
  border-radius: 6px; padding: .65rem .85rem; margin-bottom: .5rem;
}

/* Timeline */
.asm-timeline-item {
  position: relative; padding-left: 24px; padding-bottom: .75rem;
  border-left: 2px solid #e5e5e5; margin-left: 6px;
}
.asm-timeline-item:last-child { border-left-color: transparent; }
.asm-tl-dot {
  position: absolute; left: -7px; top: 4px;
  width: 12px; height: 12px; border-radius: 50%;
  background: #6c757d; border: 2px solid #fff;
}
.asm-tl-dot.active { background: #22c55e; }

/* Doc items */
.asm-doc-item {
  padding: .35rem .5rem; border-bottom: 1px solid #f3f4f6;
  font-size: .82rem;
}

/* Message cards */
.asm-msg-card {
  border-left: 3px solid #0077B5; background: #f8fafc;
  border-radius: 6px; padding: .65rem .85rem; margin-bottom: .5rem;
}
.asm-msg-card.unread { border-left-color: #f59e0b; background: #fffef5; }

/* Empty state */
.asm-empty { text-align: center; padding: 2rem; color: #94a3b8; }
.asm-empty i { opacity: .5; display: block; }
.asm-empty p { margin: 0; font-size: .85rem; }

/* Footer */
.asm-footer {
  display: flex; gap: .75rem; justify-content: flex-end;
  padding: .85rem 1.5rem; border-top: 1px solid #f3f4f6;
}
.asm-btn-edit {
  padding: .45rem 1rem; border-radius: 10px; font-size: .8rem; font-weight: 600;
  border: none; background: #f59e0b; color: #fff; cursor: pointer;
  text-decoration: none; transition: all .2s;
}
.asm-btn-edit:hover { background: #d97706; color: #fff; }
.asm-btn-print {
  padding: .45rem 1rem; border-radius: 10px; font-size: .8rem; font-weight: 600;
  border: none; background: #0077B5; color: #fff; cursor: pointer;
  transition: all .2s;
}
.asm-btn-print:hover { background: #005885; }
.asm-btn-close {
  padding: .45rem 1.2rem; border-radius: 10px; font-size: .8rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer;
  transition: all .2s;
}
.asm-btn-close:hover { background: #f3f4f6; }

@media (max-width: 576px) {
  .asm-dialog { max-width: 100%; border-radius: 16px; }
  .asm-header { padding: 1rem; border-radius: 16px 16px 0 0; flex-direction: column; gap: .5rem; }
  .asm-close { position: absolute; top: .75rem; right: .75rem; }
  .asm-body { padding: 1rem; max-height: 50vh; }
  .asm-info-grid { grid-template-columns: repeat(2, 1fr); }
  .asm-stats-row { flex-wrap: wrap; }
  .asm-stat { min-width: 30%; }
  .asm-footer { padding: .75rem 1rem; }
  .asm-tabs { padding: 0 .5rem; }
  .asm-tab { padding: .5rem .6rem; font-size: .72rem; }
  .asm-tab i { display: none; }
}
</style>
