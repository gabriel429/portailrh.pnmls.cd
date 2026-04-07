<template>
  <div class="rh-modern">
    <div class="rh-list-shell">

      <!-- Hero section -->
      <section class="agents-hero">
        <div class="container-fluid px-0">
          <div class="row g-3 align-items-center mb-4">
            <div class="col-lg-7">
              <div class="hero-text">
                <h1 class="hero-title">
                  <div class="hero-icon-wrap">
                    <i class="fas fa-users"></i>
                  </div>
                  Gestion des agents
                </h1>
                <p class="hero-subtitle">Gérez l'ensemble des agents PNMLS, leurs profils, statuts et affectations</p>
                <div v-if="filterSansAffectation" class="mt-2">
                  <span class="badge bg-warning text-dark">
                    <i class="fas fa-filter me-1"></i>Agents sans affectation
                    <button class="btn-close ms-1" style="font-size:.5rem" @click="filterSansAffectation=false; fetchAgents()"></button>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-lg-5">
              <div class="hero-actions">
                <button type="button" class="hero-btn export" @click="showExportModal = true">
                  <i class="fas fa-file-csv"></i>
                  <span>Exporter CSV</span>
                </button>
                <button type="button" class="hero-btn create" @click="showCreateModal = true">
                  <i class="fas fa-user-plus"></i>
                  <span>Nouvel agent</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Search bar -->
          <div class="search-section mb-3">
            <form @submit.prevent="applySearch">
              <div class="search-wrapper">
                <div class="search-icon">
                  <i class="fas fa-search"></i>
                </div>
                <input
                  type="text"
                  v-model="searchInput"
                  class="search-input"
                  placeholder="Rechercher par nom, email, matricule, province, grade..."
                >
                <button v-if="filters.search" class="search-clear" type="button" @click="clearSearch">
                  <i class="fas fa-times"></i>
                </button>
                <button class="search-submit" type="submit">
                  Rechercher
                </button>
              </div>
            </form>
          </div>

          <!-- Filters -->
          <div class="filters-section">
            <div class="row g-2">
              <div class="col-md-3">
                <div class="filter-group">
                  <i class="fas fa-building filter-icon"></i>
                  <select v-model="filters.organe" class="filter-select" @change="fetchAgents">
                    <option value="">Tous les organes</option>
                    <option value="SEN">SEN - National</option>
                    <option value="SEP">SEP - Provincial</option>
                    <option value="SEL">SEL - Local</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="filter-group">
                  <i class="fas fa-map-marker-alt filter-icon"></i>
                  <select v-model="filters.province_id" class="filter-select" @change="fetchAgents">
                    <option value="">Toutes les provinces</option>
                    <option v-for="p in provinces" :key="p.id" :value="p.id">{{ p.nom_province || p.nom }}</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="filter-group">
                  <i class="fas fa-briefcase filter-icon"></i>
                  <select v-model="filters.department_id" class="filter-select" @change="fetchAgents">
                    <option value="">Tous les départements</option>
                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="filter-group">
                  <i class="fas fa-check-circle filter-icon"></i>
                  <select v-model="filters.statut" class="filter-select" @change="fetchAgents">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actif</option>
                    <option value="suspendu">Suspendu</option>
                    <option value="ancien">Ancien</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Stats Cards -->
      <div v-if="stats.total > 0 && !loading" class="stats-cards">
        <div class="stat-card total">
          <div class="stat-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.total }}</div>
            <div class="stat-label">Total agents</div>
          </div>
        </div>
        <div class="stat-card sen">
          <div class="stat-icon">
            <i class="fas fa-building"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.sen }}</div>
            <div class="stat-label">SEN</div>
          </div>
        </div>
        <div class="stat-card sep">
          <div class="stat-icon">
            <i class="fas fa-landmark"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.sep }}</div>
            <div class="stat-label">SEP</div>
          </div>
        </div>
        <div class="stat-card sel">
          <div class="stat-icon">
            <i class="fas fa-city"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.sel }}</div>
            <div class="stat-label">SEL</div>
          </div>
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
            class="agents-card"
          >
            <div class="agents-card-header" :data-organe="organe.label.toLowerCase().includes('national') ? 'sen' : organe.label.toLowerCase().includes('provincial') ? 'sep' : 'sel'">
              <div class="organe-badge">
                <i class="fas" :class="organe.icon"></i>
              </div>
              <div class="organe-info">
                <h3 class="organe-title">{{ organe.label }}</h3>
                <p class="organe-count">{{ organe.agents.length }} agent{{ organe.agents.length > 1 ? 's' : '' }}</p>
              </div>
            </div>
            <div class="agents-card-body">
              <div class="agents-table-wrapper">
                <table class="agents-table">
                  <thead>
                    <tr>
                      <th>Agent</th>
                      <th>Contact</th>
                      <th>Poste</th>
                      <th v-if="isOrganeNational(organe.label)">Département</th>
                      <th v-else>Province</th>
                      <th>Matricule</th>
                      <th>Ancienneté</th>
                      <th>Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="agent in organe.agents"
                      :key="agent.id"
                      class="agent-row"
                      @click="goToAgent(agent.id)"
                    >
                      <td>
                        <div class="agent-cell">
                          <div v-if="agent.photo && !agent._photoError" class="agent-photo">
                            <img :src="'/' + agent.photo" :alt="agent.nom_complet" @error="agent._photoError = true">
                          </div>
                          <div v-else class="agent-initials">
                            {{ getInitials(agent) }}
                          </div>
                          <div class="agent-name">
                            <strong>{{ agent.prenom }} {{ agent.nom }}</strong>
                            <small v-if="agent.email_professionnel">{{ agent.email_professionnel }}</small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div class="contact-info">
                          <div v-if="agent.telephone">
                            <i class="fas fa-phone"></i> {{ agent.telephone }}
                          </div>
                          <div v-if="agent.email_prive" class="text-muted small">
                            <i class="fas fa-envelope"></i> {{ agent.email_prive }}
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="poste-label">{{ agent.poste_actuel || 'Non défini' }}</span>
                      </td>
                      <td v-if="isOrganeNational(organe.label)">
                        <span class="dept-label">{{ agent.departement ? agent.departement.nom : 'Service SEN' }}</span>
                      </td>
                      <td v-else>
                        <span class="province-label">{{ agent.province ? agent.province.nom : 'N/A' }}</span>
                      </td>
                      <td>
                        <code class="matricule-code">{{ agent.matricule_etat || 'N/A' }}</code>
                      </td>
                      <td>
                        <div class="anciennete-badge" v-if="agent.anciennete !== null">
                          {{ agent.anciennete }} an{{ agent.anciennete > 1 ? 's' : '' }}
                        </div>
                        <span v-else class="text-muted">N/A</span>
                      </td>
                      <td>
                        <span v-if="agent.statut === 'actif'" class="status-pill active">
                          <i class="fas fa-check-circle"></i> Actif
                        </span>
                        <span v-else-if="agent.statut === 'suspendu'" class="status-pill suspended">
                          <i class="fas fa-pause-circle"></i> Suspendu
                        </span>
                        <span v-else class="status-pill inactive">
                          <i class="fas fa-times-circle"></i> {{ capitalize(agent.statut) }}
                        </span>
                      </td>
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

    <!-- Agent Create Modal -->
    <AgentCreateModal
      :show="showCreateModal"
      @close="closeCreateModal"
      @created="onAgentCreated"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { list, get, remove, exportCsv, getFormOptions } from '@/api/agents'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import AgentEditModal from '@/components/agents/AgentEditModal.vue'
import AgentCreateModal from '@/components/agents/AgentCreateModal.vue'

const router = useRouter()
const route = useRoute()
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
const filterSansAffectation = ref(false)
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

// Create modal
const showCreateModal = ref(false)

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

function closeCreateModal() {
    showCreateModal.value = false
}

function onAgentCreated() {
    closeCreateModal()
    fetchAgents() // Refresh the list
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
        if (filterSansAffectation.value) params.sans_affectation = 1

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
    if (route.query.sans_affectation) filterSansAffectation.value = true
    fetchOptions()
    fetchAgents()
})
</script>

<style scoped>
/* ═══════════════════════════════════════════
   HERO SECTION
   ═══════════════════════════════════════════ */
.agents-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
  border-radius: 24px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 10px 40px rgba(0, 119, 181, 0.15);
}

.hero-text {
  color: #fff;
}

.hero-title {
  font-size: 2rem;
  font-weight: 800;
  margin: 0 0 0.5rem 0;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.hero-icon-wrap {
  width: 56px;
  height: 56px;
  background: rgba(255,255,255,0.15);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.hero-subtitle {
  font-size: 0.95rem;
  margin: 0;
  opacity: 0.9;
}

.hero-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.hero-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  border: none;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.hero-btn i {
  font-size: 1rem;
}

.hero-btn.export {
  background: #10b981;
  color: #fff;
}

.hero-btn.export:hover {
  background: #059669;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
}

.hero-btn.create {
  background: #fff;
  color: #0077B5;
}

.hero-btn.create:hover {
  background: #f0f9ff;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(255,255,255,0.3);
}

/* Search Section */
.search-section {
  margin-top: 1.5rem;
}

.search-wrapper {
  display: flex;
  align-items: center;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  border-radius: 14px;
  border: 1.5px solid rgba(255,255,255,0.2);
  padding: 0.5rem;
  transition: all 0.3s;
}

.search-wrapper:focus-within {
  background: rgba(255,255,255,0.25);
  border-color: rgba(255,255,255,0.4);
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.search-icon {
  padding: 0 0.75rem;
  color: rgba(255,255,255,0.8);
  font-size: 1rem;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  color: #fff;
  font-size: 0.95rem;
  padding: 0.5rem;
  outline: none;
}

.search-input::placeholder {
  color: rgba(255,255,255,0.6);
}

.search-clear {
  background: rgba(255,255,255,0.15);
  border: none;
  color: #fff;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 0.5rem;
}

.search-clear:hover {
  background: rgba(255,255,255,0.25);
}

.search-submit {
  background: rgba(255,255,255,0.2);
  border: none;
  color: #fff;
  padding: 0.65rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.search-submit:hover {
  background: rgba(255,255,255,0.3);
}

/* Filters Section */
.filters-section {
  margin-top: 1rem;
}

.filter-group {
  position: relative;
  display: flex;
  align-items: center;
  background: rgba(255,255,255,0.1);
  border-radius: 12px;
  border: 1.5px solid rgba(255,255,255,0.15);
  overflow: hidden;
  transition: all 0.3s;
}

.filter-group:hover {
  background: rgba(255,255,255,0.15);
  border-color: rgba(255,255,255,0.25);
}

.filter-icon {
  position: absolute;
  left: 1rem;
  color: rgba(255,255,255,0.7);
  font-size: 0.9rem;
  pointer-events: none;
  z-index: 1;
}

.filter-select {
  flex: 1;
  background: transparent;
  border: none;
  color: #fff;
  padding: 0.75rem 0.75rem 0.75rem 2.75rem;
  font-size: 0.88rem;
  font-weight: 500;
  cursor: pointer;
  outline: none;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  padding-right: 2.5rem;
}

.filter-select option {
  background: #fff;
  color: #333;
}

/* ═══════════════════════════════════════════
   STATS CARDS
   ═══════════════════════════════════════════ */
.stats-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: #fff;
  border-radius: 20px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  border: 1px solid rgba(0,0,0,0.04);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--stat-color-1), var(--stat-color-2));
}

.stat-card.total {
  --stat-color-1: #6366f1;
  --stat-color-2: #8b5cf6;
}

.stat-card.sen {
  --stat-color-1: #0077B5;
  --stat-color-2: #005a87;
}

.stat-card.sep {
  --stat-color-1: #0ea5e9;
  --stat-color-2: #0284c7;
}

.stat-card.sel {
  --stat-color-1: #10b981;
  --stat-color-2: #059669;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0,0,0,0.12);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-card.total .stat-icon {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
}

.stat-card.sen .stat-icon {
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: #fff;
}

.stat-card.sep .stat-icon {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #fff;
}

.stat-card.sel .stat-icon {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  margin-bottom: 0.25rem;
  background: linear-gradient(135deg, var(--stat-color-1), var(--stat-color-2));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* ═══════════════════════════════════════════
   AGENTS CARDS
   ═══════════════════════════════════════════ */
.agents-card {
  background: #fff;
  border-radius: 20px;
  margin-bottom: 1.5rem;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  border: 1px solid rgba(0,0,0,0.04);
  transition: all 0.3s;
}

.agents-card:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.agents-card-header {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  border-bottom: 1px solid #f1f5f9;
  position: relative;
}

.agents-card-header::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
}

.agents-card-header[data-organe="sen"]::before {
  background: linear-gradient(180deg, #0077B5, #005a87);
}

.agents-card-header[data-organe="sep"]::before {
  background: linear-gradient(180deg, #0ea5e9, #0284c7);
}

.agents-card-header[data-organe="sel"]::before {
  background: linear-gradient(180deg, #10b981, #059669);
}

.organe-badge {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.agents-card-header[data-organe="sen"] .organe-badge {
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: #fff;
}

.agents-card-header[data-organe="sep"] .organe-badge {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #fff;
}

.agents-card-header[data-organe="sel"] .organe-badge {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff;
}

.organe-info {
  flex: 1;
}

.organe-title {
  font-size: 1.1rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
  color: #1e293b;
}

.organe-count {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0;
  font-weight: 500;
}

.agents-card-body {
  padding: 0;
}

.agents-table-wrapper {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.agents-table {
  width: 100%;
  border-collapse: collapse;
}

.agents-table th {
  background: #f8fafc;
  padding: 1rem 1.25rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
  border-bottom: 1px solid #e2e8f0;
}

.agents-table td {
  padding: 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.9rem;
  color: #475569;
}

.agent-row {
  cursor: pointer;
  transition: all 0.2s;
}

.agent-row:hover {
  background: #f8fafc;
}

.agent-row:hover td:first-child {
  color: #0077B5;
}

.agent-cell {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.agent-photo,
.agent-initials {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  flex-shrink: 0;
  overflow: hidden;
}

.agent-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.agent-initials {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 700;
}

.agent-name {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.agent-name strong {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.95rem;
}

.agent-name small {
  font-size: 0.8rem;
  color: #94a3b8;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.contact-info div {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.contact-info i {
  width: 16px;
  color: #94a3b8;
  font-size: 0.75rem;
}

.poste-label,
.dept-label,
.province-label {
  font-weight: 500;
  color: #1e293b;
}

.matricule-code {
  background: #f1f5f9;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-family: 'Courier New', monospace;
  color: #475569;
  font-weight: 600;
}

.anciennete-badge {
  background: #e0f2fe;
  color: #0369a1;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-block;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.9rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-pill i {
  font-size: 0.8rem;
}

.status-pill.active {
  background: #d1fae5;
  color: #065f46;
}

.status-pill.suspended {
  background: #fef3c7;
  color: #92400e;
}

.status-pill.inactive {
  background: #f1f5f9;
  color: #64748b;
}

/* Empty State */
.rh-list-card {
  background: #fff;
  border-radius: 20px;
  padding: 3rem 2rem;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.rh-list-card i {
  color: #cbd5e1;
}

/* Filtering Overlay */
.ag-filtering {
  opacity: 0.5;
  pointer-events: none;
  transition: opacity 0.2s;
}

/* ═══════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════ */
@media (max-width: 1024px) {
  .stats-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .agents-hero {
    padding: 1.5rem;
    border-radius: 20px;
  }

  .hero-title {
    font-size: 1.5rem;
    flex-direction: column;
    align-items: flex-start;
  }

  .hero-icon-wrap {
    width: 48px;
    height: 48px;
    font-size: 1.2rem;
  }

  .hero-actions {
    justify-content: flex-start;
    width: 100%;
  }

  .hero-btn {
    flex: 1;
    justify-content: center;
  }

  .stats-cards {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .stat-card {
    padding: 1.25rem;
  }

  .agents-table th:nth-child(3),
  .agents-table td:nth-child(3),
  .agents-table th:nth-child(6),
  .agents-table td:nth-child(6) {
    display: none;
  }

  .agents-table th,
  .agents-table td {
    padding: 0.875rem;
    font-size: 0.85rem;
  }

  .agent-photo,
  .agent-initials {
    width: 40px;
    height: 40px;
  }
}

@media (max-width: 576px) {
  .agents-table th:nth-child(2),
  .agents-table td:nth-child(2),
  .agents-table th:nth-child(5),
  .agents-table td:nth-child(5) {
    display: none;
  }

  .filter-select {
    font-size: 0.8rem;
  }

  .search-input {
    font-size: 0.85rem;
  }
}

/* ═══════════════════════════════════════════
   MODALS (keeping existing styles)
   ═══════════════════════════════════════════ */
.modal-content {
  border-radius: 16px;
  overflow: hidden;
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
