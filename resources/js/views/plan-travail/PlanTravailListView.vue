<template>
  <div class="container py-4">
    <!-- Hero -->
    <div class="pt-hero">
      <div class="pt-hero-body">
        <div class="pt-hero-text">
          <h2><i class="fas fa-calendar-alt me-2"></i>Plan de Travail Annuel {{ filters.annee }}</h2>
          <p>Activites planifiees, en cours et terminees de votre unite organisationnelle.</p>
          <div class="pt-hero-stats">
            <div>
              <div class="pt-hero-stat-val">{{ stats.total }}</div>
              <div class="pt-hero-stat-lbl">Total</div>
            </div>
            <div>
              <div class="pt-hero-stat-val">{{ stats.avg_pourcentage }}%</div>
              <div class="pt-hero-stat-lbl">Progression</div>
            </div>
          </div>
        </div>
        <div class="pt-hero-actions">
          <button v-if="canEdit" class="pt-hero-btn" @click="openCreateModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle activite
          </button>
          <div class="pt-hero-filters">
            <select v-model="filters.annee" class="pt-filter-select" @change="loadPlan">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="accessDenied && !loading" class="pt-alert pt-alert-danger">
      <div class="pt-alert-icon"><i class="fas fa-lock"></i></div>
      <div>
        <h5>Acces restreint</h5>
        <p>{{ accessDeniedMessage }}</p>
      </div>
    </div>

    <!-- Status filter cards (always visible) -->
    <div class="pt-filter-grid">
      <button class="pt-filter-card pt-filter-all" :class="{ active: !filters.statut && !filters.trimestre }" @click="setFilter('', '')">
        <div class="pt-filter-icon"><i class="fas fa-th-large"></i></div>
        <div class="pt-filter-info">
          <div class="pt-filter-name">Toutes</div>
          <div class="pt-filter-count">{{ stats.total }} activite{{ stats.total > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button class="pt-filter-card pt-filter-planned" :class="{ active: filters.statut === 'planifiee' }" @click="setFilter('planifiee', '')">
        <div class="pt-filter-icon"><i class="fas fa-clock"></i></div>
        <div class="pt-filter-info">
          <div class="pt-filter-name">Planifiees</div>
          <div class="pt-filter-count">{{ stats.planifiee }} activite{{ stats.planifiee > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button class="pt-filter-card pt-filter-progress" :class="{ active: filters.statut === 'en_cours' }" @click="setFilter('en_cours', '')">
        <div class="pt-filter-icon"><i class="fas fa-spinner"></i></div>
        <div class="pt-filter-info">
          <div class="pt-filter-name">En cours</div>
          <div class="pt-filter-count">{{ stats.en_cours }} activite{{ stats.en_cours > 1 ? 's' : '' }}</div>
        </div>
      </button>
      <button class="pt-filter-card pt-filter-done" :class="{ active: filters.statut === 'terminee' }" @click="setFilter('terminee', '')">
        <div class="pt-filter-icon"><i class="fas fa-check-circle"></i></div>
        <div class="pt-filter-info">
          <div class="pt-filter-name">Terminees</div>
          <div class="pt-filter-count">{{ stats.terminee }} activite{{ stats.terminee > 1 ? 's' : '' }}</div>
        </div>
      </button>
    </div>

    <!-- Trimester filter (always visible) -->
    <div class="pt-trim-bar">
      <button
        v-for="t in trimestres" :key="t.value"
        class="pt-trim-btn"
        :class="{ active: filters.trimestre === t.value }"
        @click="setFilter(filters.statut, t.value)"
      >
        {{ t.label }}
      </button>
    </div>

    <!-- Planification global filters (dept / province / niveau) -->
    <div v-if="isGlobalPta && (filterDepts.length || filterProvinces.length)" class="pt-planif-filters">
      <select v-model="filters.departement_id" class="pt-filter-select" @change="loadPlan">
        <option value="">Tous les departements</option>
        <option v-for="d in filterDepts" :key="d.id" :value="d.id">{{ d.nom }}</option>
      </select>
      <select v-model="filters.province_id" class="pt-filter-select" @change="loadPlan">
        <option value="">Toutes les provinces</option>
        <option v-for="p in filterProvinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
      </select>
      <select v-model="filters.niveau_administratif" class="pt-filter-select" @change="loadPlan">
        <option value="">Tous les niveaux</option>
        <option value="SEN">SEN (National)</option>
        <option value="SEP">SEP (Provincial)</option>
        <option value="SEL">SEL (Local)</option>
      </select>
      <button v-if="filters.departement_id || filters.province_id || filters.niveau_administratif" class="pt-reset-planif-btn" @click="resetPlanifFilters">
        <i class="fas fa-times me-1"></i>Reinitialiser
      </button>
    </div>

    <!-- Global progress bar -->
    <div v-if="stats.total > 0" class="pt-progress-bar">
      <div class="pt-progress-header">
        <span class="pt-progress-label">Progression globale</span>
        <span class="pt-progress-val">{{ stats.avg_pourcentage }}%</span>
      </div>
      <div class="pt-progress-track">
        <div class="pt-progress-fill" :style="{ width: stats.avg_pourcentage + '%' }"></div>
      </div>
    </div>

    <!-- Section header when filtered -->
    <div v-if="filters.statut || filters.trimestre" class="pt-section-header">
      <div class="pt-section-title">
        <i class="fas fa-filter" style="color:#7c3aed;"></i>
        <span v-if="filters.statut">{{ statutLabel(filters.statut) }}</span>
        <span v-if="filters.statut && filters.trimestre"> &middot; </span>
        <span v-if="filters.trimestre">{{ triLabel(filters.trimestre) }}</span>
        <span class="pt-section-badge">{{ flatActivites.length }} activite{{ flatActivites.length > 1 ? 's' : '' }}</span>
      </div>
      <button class="pt-back-btn" @click="setFilter('', '')">
        <i class="fas fa-arrow-left"></i> Tout afficher
      </button>
    </div>

    <!-- Loading spinner (initial load only) -->
    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement du plan de travail..." />
    </div>

    <template v-else>
      <!-- Activity cards -->
      <div v-if="flatActivites.length" class="pt-grid" :class="{ 'pt-filtering': filtering }">
        <div v-for="a in flatActivites" :key="a.id" class="pt-card" style="cursor:pointer" @click="openDetailPopup(a.id)">
          <div class="pt-card-top">
            <div class="pt-card-status-icon" :class="statutIconClass(a.statut)">
              <i :class="statutIconName(a.statut)"></i>
            </div>
            <div class="pt-card-info">
              <a href="#" class="pt-card-title" @click.prevent.stop="openDetailPopup(a.id)">
                {{ a.titre }}
              </a>
              <div v-if="a.description" class="pt-card-desc">{{ truncate(a.description, 100) }}</div>
              <div class="pt-card-tags">
                  <span v-if="a.categorie" class="pt-meta-badge">{{ a.categorie }}</span>
                <span :class="statutBadgeClass(a.statut)">{{ statutLabel(a.statut) }}</span>
              </div>
              <div class="pt-quarter-strip" :aria-label="`Chronogramme ${a.titre}`">
                <span
                  v-for="slot in trimestreSlots(a)"
                  :key="`${a.id}-${slot.label}`"
                  class="pt-quarter-chip"
                  :class="{ active: slot.active }"
                >
                  {{ slot.label }}
                </span>
              </div>
            </div>
          </div>
          <div class="pt-card-meta">
            <span class="pt-meta-item">
              <i class="fas fa-building me-1"></i>{{ a.niveau_administratif }}
              <template v-if="a.departement"> - {{ a.departement.nom }}</template>
            </span>
              <span v-if="a.responsable_code" class="pt-meta-item">
                <i class="fas fa-user-tie me-1"></i>{{ a.responsable_code }}
              </span>
              <span v-if="provinceSummary(a)" class="pt-meta-item">
                <i class="fas fa-map-marker-alt me-1"></i>{{ provinceSummary(a) }}
              </span>
              <span v-if="a.cout_cdf !== null && a.cout_cdf !== undefined" class="pt-meta-item">
                <i class="fas fa-coins me-1"></i>{{ formatCurrency(a.cout_cdf) }} CDF
              </span>
            <span v-if="a.date_debut" class="pt-meta-item">
              <i class="fas fa-calendar me-1"></i>{{ formatShortDate(a.date_debut) }}
              <template v-if="a.date_fin"> &rarr; {{ formatShortDate(a.date_fin) }}</template>
            </span>
          </div>
          <div class="pt-card-progress">
            <div class="pt-card-progress-track">
              <div
                class="pt-card-progress-fill"
                :class="a.pourcentage >= 100 ? 'done' : ''"
                :style="{ width: a.pourcentage + '%' }"
              ></div>
            </div>
            <span class="pt-card-progress-val">{{ a.pourcentage }}%</span>
          </div>
          <div class="pt-card-footer">
            <span class="pt-card-date">
              <i class="fas fa-clock me-1"></i>{{ formatDate(a.created_at) }}
            </span>
            <div class="pt-card-actions">
              <button class="pt-act-btn pt-act-view" @click.stop="openDetailPopup(a.id)">
                <i class="fas fa-eye"></i> Voir
              </button>
              <button v-if="canEdit" class="pt-act-btn pt-act-edit" @click.stop="openEditModal(a.id)">
                <i class="fas fa-edit"></i> Modifier
              </button>
              <button v-if="canEdit" class="pt-act-btn pt-act-delete" @click.stop="handleDeleteFromCard(a.id, $event)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="pt-empty">
        <div class="pt-empty-icon"><i class="fas fa-calendar-alt"></i></div>
        <template v-if="filters.statut || filters.trimestre">
          <h5>Aucune activite trouvee</h5>
          <p>Il n'y a pas d'activites correspondant a ces filtres.</p>
          <button class="pt-back-btn mt-3" style="display:inline-flex;" @click="setFilter('', '')">
            <i class="fas fa-arrow-left"></i> Tout afficher
          </button>
        </template>
        <template v-else>
          <h5>Aucune activite pour l'annee {{ filters.annee }}</h5>
          <p>Le plan de travail n'a pas encore d'activites enregistrees.</p>
          <button v-if="canEdit" class="pt-hero-btn mt-3" style="display:inline-flex;" @click="openCreateModal">
            <i class="fas fa-plus-circle me-1"></i> Creer la premiere activite
          </button>
        </template>
      </div>
    </template>

    <!-- Detail Popup -->
    <Teleport to="body">
      <Transition name="ptd-fade">
        <div v-if="detailOpen" class="ptd-overlay" @click.self="closeDetailPopup">
          <div class="ptd-dialog">
            <!-- Loading -->
            <div v-if="detailLoading" class="ptd-loading">
              <i class="fas fa-spinner fa-spin"></i> Chargement...
            </div>

            <template v-else-if="detailActivite">
              <!-- Header -->
              <div class="ptd-header">
                <div class="ptd-header-icon" :class="detailStatutBadge(detailActivite.statut).replace('pt-badge ', 'ptd-si-')">
                  <i :class="detailActivite.statut === 'terminee' ? 'fas fa-check-circle' : detailActivite.statut === 'en_cours' ? 'fas fa-spinner' : 'fas fa-clock'"></i>
                </div>
                <div class="ptd-header-info">
                  <h3 class="ptd-title">{{ detailActivite.titre }}</h3>
                  <div class="ptd-sub">
                    {{ detailActivite.annee }}
                    <template v-if="detailActivite.trimestre"> &middot; {{ detailActivite.trimestre }}</template>
                    &middot; {{ detailActivite.niveau_administratif }}
                    <template v-if="detailActivite.categorie"> &middot; {{ detailActivite.categorie }}</template>
                  </div>
                </div>
                <button class="ptd-close" @click="closeDetailPopup"><i class="fas fa-times"></i></button>
              </div>

              <!-- Body scrollable -->
              <div class="ptd-body">
                <!-- Status badge + progress -->
                <div class="ptd-progress-section">
                  <span :class="detailStatutBadge(detailActivite.statut)">{{ detailStatutLabel(detailActivite.statut) }}</span>
                  <div class="ptd-progress">
                    <div class="ptd-progress-track">
                      <div class="ptd-progress-fill" :class="detailActivite.pourcentage >= 100 ? 'done' : ''" :style="{ width: detailActivite.pourcentage + '%' }"></div>
                    </div>
                    <span class="ptd-progress-val">{{ detailActivite.pourcentage }}%</span>
                  </div>
                </div>

                <!-- Info grid -->
                <div class="ptd-info-grid">
                  <div class="ptd-info-item">
                    <i class="fas fa-bullseye"></i>
                    <div><span class="ptd-info-lbl">Objectif</span><span class="ptd-info-val">{{ detailActivite.objectif || 'Non renseigne' }}</span></div>
                  </div>
                  <div class="ptd-info-item">
                    <i class="fas fa-tag"></i>
                    <div><span class="ptd-info-lbl">Rubrique</span><span class="ptd-info-val">{{ detailActivite.categorie || 'Non renseignee' }}</span></div>
                  </div>
                  <div class="ptd-info-item">
                    <i class="fas fa-trophy"></i>
                    <div><span class="ptd-info-lbl">Resultat attendu</span><span class="ptd-info-val">{{ detailActivite.resultat_attendu || 'Non renseigne' }}</span></div>
                  </div>
                  <div class="ptd-info-item">
                    <i class="fas fa-user-tie"></i>
                    <div><span class="ptd-info-lbl">Responsable</span><span class="ptd-info-val">{{ detailActivite.responsable_code || 'Non renseigne' }}</span></div>
                  </div>
                  <div class="ptd-info-item">
                    <i class="fas fa-coins"></i>
                    <div><span class="ptd-info-lbl">Cout en CDF</span><span class="ptd-info-val">{{ detailActivite.cout_cdf != null ? formatCurrency(detailActivite.cout_cdf) + ' CDF' : 'Non renseigne' }}</span></div>
                  </div>
                  <div class="ptd-info-item">
                    <i class="fas fa-building"></i>
                    <div>
                      <span class="ptd-info-lbl">Niveau</span>
                      <span class="ptd-info-val">
                        {{ detailActivite.niveau_administratif }}
                        <template v-if="detailActivite.departement"> - {{ detailActivite.departement.nom }}</template>
                        <template v-if="detailProvinceSummary(detailActivite)"> - {{ detailProvinceSummary(detailActivite) }}</template>
                        <template v-if="detailActivite.localite"> - {{ detailActivite.localite.nom }}</template>
                      </span>
                    </div>
                  </div>
                  <div class="ptd-info-item">
                    <i class="fas fa-check-double"></i>
                    <div><span class="ptd-info-lbl">Validation</span><span class="ptd-info-val">{{ detailValidationLabel(detailActivite.validation_niveau) }}</span></div>
                  </div>
                  <div v-if="detailActivite.date_debut || detailActivite.date_fin" class="ptd-info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                      <span class="ptd-info-lbl">Periode</span>
                      <span class="ptd-info-val">
                        {{ formatDate(detailActivite.date_debut) || '?' }} &rarr; {{ formatDate(detailActivite.date_fin) || '?' }}
                        <span v-if="detailIsOverdue()" style="color:#ef4444; font-weight:700; margin-left:.3rem;">En retard</span>
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Chronogramme -->
                <div class="ptd-chrono">
                  <span class="ptd-info-lbl" style="margin-bottom:.3rem; display:block;">Chronogramme</span>
                  <div class="ptd-chrono-strip">
                    <span v-for="slot in trimestreSlots(detailActivite)" :key="slot.label" class="ptd-chrono-chip" :class="{ active: slot.active }">{{ slot.label }}</span>
                  </div>
                </div>

                <!-- Description -->
                <div v-if="detailActivite.description" class="ptd-section">
                  <h6><i class="fas fa-file-alt me-1"></i> Description</h6>
                  <p>{{ detailActivite.description }}</p>
                </div>

                <!-- Observations -->
                <div v-if="detailActivite.observations" class="ptd-section">
                  <h6><i class="fas fa-comment-alt me-1"></i> Observations</h6>
                  <p>{{ detailActivite.observations }}</p>
                </div>

                <!-- Taches -->
                <div v-if="detailActivite.taches?.length" class="ptd-section">
                  <h6><i class="fas fa-tasks me-1"></i> Taches liees ({{ detailActivite.taches.length }})</h6>
                  <div class="ptd-taches">
                    <div v-for="t in detailActivite.taches" :key="t.id" class="ptd-tache">
                      <span class="ptd-tache-name">{{ t.titre }}</span>
                      <span :class="detailStatutBadge(t.statut)" style="font-size:.68rem;">{{ detailStatutLabel(t.statut) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Meta -->
                <div class="ptd-meta">
                  <span><i class="fas fa-user me-1"></i> {{ detailActivite.createur?.nom_complet ?? 'N/A' }}</span>
                  <span><i class="fas fa-clock me-1"></i> {{ detailFormatDateTime(detailActivite.created_at) }}</span>
                </div>

                <!-- Agents assignes -->
                <div v-if="detailActivite.assigned_agents?.length" class="ptd-section">
                  <h6><i class="fas fa-users me-1"></i> Agents assignes ({{ detailActivite.assigned_agents.length }})</h6>
                  <div class="ptd-agents-list">
                    <span v-for="a in detailActivite.assigned_agents" :key="a.id" class="ptd-agent-pill">
                      <i class="fas fa-user-circle me-1"></i>{{ a.nom_complet }}
                      <span v-if="a.fonction" class="ptd-agent-fn"> &mdash; {{ a.fonction }}</span>
                    </span>
                  </div>
                </div>

                <!-- Mise a jour rapide -->
                <div v-if="detailCanUpdateStatut && detailActivite.statut !== 'terminee'" class="ptd-update">
                  <h6><i class="fas fa-sync-alt me-1"></i> Mise a jour rapide</h6>
                  <form @submit.prevent="handleDetailUpdateStatut" class="ptd-update-form">
                    <div class="ptd-update-row">
                      <select v-model="detailStatutForm.statut" class="ptm-input">
                        <option value="planifiee">Planifiee</option>
                        <option value="en_cours">En cours</option>
                        <option value="terminee">Terminee</option>
                      </select>
                      <div class="ptd-range-wrap">
                        <input v-model.number="detailStatutForm.pourcentage" type="range" min="0" max="100" step="5">
                        <span>{{ detailStatutForm.pourcentage }}%</span>
                      </div>
                    </div>
                    <textarea v-model="detailStatutForm.observations" class="ptm-input ptm-textarea" rows="2" placeholder="Observations..."></textarea>
                    <button type="submit" class="ptd-update-btn" :disabled="detailUpdating">
                      <i :class="detailUpdating ? 'fas fa-spinner fa-spin' : 'fas fa-save'" class="me-1"></i>
                      {{ detailUpdating ? 'Enregistrement...' : 'Mettre a jour' }}
                    </button>
                  </form>
                </div>

                <!-- Actions -->
                <div v-if="detailCanEdit" class="ptd-actions">
                  <button class="ptd-act-btn ptd-act-edit" @click="closeDetailPopup(); openEditModal(detailActivite.id)">
                    <i class="fas fa-edit me-1"></i> Modifier
                  </button>
                  <router-link :to="{ name: 'plan-travail.show', params: { id: detailActivite.id } }" class="ptd-act-btn ptd-act-full">
                    <i class="fas fa-external-link-alt me-1"></i> Page complete
                  </router-link>
                </div>
              </div>
            </template>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Create Modal -->
    <teleport to="body">
      <div v-if="showCreateModal" class="ptm-overlay" @click.self="closeCreateModal">
        <div class="ptm-dialog">
          <div class="ptm-header">
            <div class="ptm-header-icon"><i class="fas fa-plus-circle"></i></div>
            <div>
              <h4 class="ptm-title">Nouvelle activite</h4>
              <p class="ptm-subtitle">Ajouter une activite au plan de travail</p>
            </div>
            <button class="ptm-close" @click="closeCreateModal"><i class="fas fa-times"></i></button>
          </div>

          <form @submit.prevent="handleCreateSubmit" class="ptm-body">
            <!-- Titre -->
            <div class="ptm-field">
              <label class="ptm-label">Titre <span class="ptm-req">*</span></label>
              <input v-model="createForm.titre" type="text" class="ptm-input" :class="{ 'ptm-err': createErrors.titre }" placeholder="Titre de l'activite" maxlength="255">
              <span v-if="createErrors.titre" class="ptm-err-msg">{{ createErrors.titre[0] }}</span>
            </div>

              <div class="ptm-row">
                <div class="ptm-field ptm-half">
                  <label class="ptm-label">Rubrique / categorie</label>
                  <input v-model="createForm.categorie" list="pta-categories-list" type="text" class="ptm-input" placeholder="Ex. Leadership">
                  <datalist id="pta-categories-list">
                    <option v-for="item in createCategories" :key="item" :value="item"></option>
                  </datalist>
                </div>
                <div class="ptm-field ptm-half">
                  <label class="ptm-label">Responsable</label>
                  <input v-model="createForm.responsable_code" list="pta-responsables-list" type="text" class="ptm-input" placeholder="Ex. DPSE">
                  <datalist id="pta-responsables-list">
                    <option v-for="item in createResponsables" :key="item" :value="item"></option>
                  </datalist>
                </div>
              </div>

              <div class="ptm-field">
                <label class="ptm-label">Cout en CDF</label>
                <input v-model.number="createForm.cout_cdf" type="number" class="ptm-input" min="0" step="0.01" placeholder="0">
              </div>

            <div class="ptm-field">
              <label class="ptm-label">Objectif strategique</label>
              <textarea v-model="createForm.objectif" class="ptm-input ptm-textarea" rows="2" placeholder="Objectif strategique de l'activite"></textarea>
            </div>

            <div class="ptm-field">
              <label class="ptm-label">Resultat attendu</label>
              <textarea v-model="createForm.resultat_attendu" class="ptm-input ptm-textarea" rows="2" placeholder="Resultat attendu"></textarea>
            </div>

            <!-- Niveau administratif -->
            <div class="ptm-field">
              <label class="ptm-label">Niveau administratif <span class="ptm-req">*</span></label>
              <div class="ptm-card-row">
                <button v-for="n in niveauOptions" :key="n.value" type="button" class="ptm-opt-card" :class="{ active: createForm.niveau_administratif === n.value }" @click="createForm.niveau_administratif = n.value; onNiveauChange()">
                  {{ n.label }}
                </button>
              </div>
              <span v-if="createErrors.niveau_administratif" class="ptm-err-msg">{{ createErrors.niveau_administratif[0] }}</span>
            </div>

            <!-- Departement (SEN) -->
            <div v-if="createForm.niveau_administratif === 'SEN'" class="ptm-field">
              <label class="ptm-label">Departement</label>
              <select v-model="createForm.departement_id" class="ptm-input">
                <option value="">-- Choisir --</option>
                <option v-for="d in createDepartments" :key="d.id" :value="d.id">{{ d.nom }}</option>
              </select>
            </div>

            <!-- Province (SEP / SEL) -->
            <div v-if="createForm.niveau_administratif === 'SEL'" class="ptm-field">
              <label class="ptm-label">Province</label>
              <select v-model="createForm.province_id" class="ptm-input">
                <option value="">-- Choisir --</option>
                <option v-for="p in createProvinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
              </select>
            </div>

            <div v-if="createForm.niveau_administratif === 'SEP'" class="ptm-field">
              <label class="ptm-label">Provinces concernees</label>
              <select v-model="createForm.province_ids" class="ptm-input" multiple size="6">
                <option v-for="p in createProvinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
              </select>
            </div>

            <!-- Localite (SEL) -->
            <div v-if="createForm.niveau_administratif === 'SEL'" class="ptm-field">
              <label class="ptm-label">Localite</label>
              <select v-model="createForm.localite_id" class="ptm-input">
                <option value="">-- Choisir --</option>
                <option v-for="l in createLocalites" :key="l.id" :value="l.id">{{ l.nom }}</option>
              </select>
            </div>

            <!-- Row: Annee + Trimestre -->
            <div class="ptm-row">
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Annee <span class="ptm-req">*</span></label>
                <input v-model.number="createForm.annee" type="number" class="ptm-input" min="2020" max="2040">
                <span v-if="createErrors.annee" class="ptm-err-msg">{{ createErrors.annee[0] }}</span>
              </div>
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Trimestre</label>
                <select v-model="createForm.trimestre" class="ptm-input">
                  <option v-for="t in trimestreOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
              </div>
            </div>

            <div class="ptm-field">
              <label class="ptm-label">Chronogramme</label>
              <div class="ptm-card-row">
                <label class="ptm-check"><input v-model="createForm.trimestre_1" type="checkbox"> T1</label>
                <label class="ptm-check"><input v-model="createForm.trimestre_2" type="checkbox"> T2</label>
                <label class="ptm-check"><input v-model="createForm.trimestre_3" type="checkbox"> T3</label>
                <label class="ptm-check"><input v-model="createForm.trimestre_4" type="checkbox"> T4</label>
              </div>
            </div>

            <div class="ptm-field">
              <label class="ptm-label">Validation</label>
              <select v-model="createForm.validation_niveau" class="ptm-input">
                <option value="">-- Choisir --</option>
                <option value="direction">Direction</option>
                <option value="coordination_nationale">Coordination nationale</option>
                <option value="coordination_provinciale">Coordination provinciale</option>
              </select>
            </div>

            <!-- Statut + Pourcentage -->
            <div class="ptm-row">
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Statut <span class="ptm-req">*</span></label>
                <select v-model="createForm.statut" class="ptm-input">
                  <option v-for="s in statutOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <span v-if="createErrors.statut" class="ptm-err-msg">{{ createErrors.statut[0] }}</span>
              </div>
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Pourcentage</label>
                <input v-model.number="createForm.pourcentage" type="number" class="ptm-input" min="0" max="100">
              </div>
            </div>

            <!-- Dates -->
            <div class="ptm-row">
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Date debut</label>
                <input v-model="createForm.date_debut" type="date" class="ptm-input">
              </div>
              <div class="ptm-field ptm-half">
                <label class="ptm-label">Date fin</label>
                <input v-model="createForm.date_fin" type="date" class="ptm-input">
              </div>
            </div>

            <!-- Description -->
            <div class="ptm-field">
              <label class="ptm-label">Description</label>
              <textarea v-model="createForm.description" class="ptm-input ptm-textarea" rows="3" placeholder="Description de l'activite..."></textarea>
            </div>

            <!-- Observations -->
            <div class="ptm-field">
              <label class="ptm-label">Observations</label>
              <textarea v-model="createForm.observations" class="ptm-input ptm-textarea" rows="2" placeholder="Observations (facultatif)..."></textarea>
            </div>

            <!-- Agents assignes (SEN attachés, visible when list loaded by planification role) -->
            <div v-if="createAgentsSen.length" class="ptm-field">
              <label class="ptm-label">Agents assignes <span class="ptm-hint">(Attaches SEN responsables)</span></label>
              <div class="ptm-agents-check-list">
                <label
                  v-for="a in createAgentsSen"
                  :key="a.id"
                  class="ptm-agent-check"
                  :class="{ active: createForm.assigned_agent_ids.includes(a.id) }"
                >
                  <input type="checkbox" :value="a.id" v-model="createForm.assigned_agent_ids" style="display:none">
                  <i class="fas fa-user-circle me-1"></i>
                  {{ a.nom_complet }}
                  <span v-if="a.fonction" class="ptm-agent-fn"> &mdash; {{ a.fonction }}</span>
                </label>
              </div>
              <div v-if="createForm.assigned_agent_ids.length" class="ptm-field-hint">
                {{ createForm.assigned_agent_ids.length }} agent(s) selectionne(s)
              </div>
            </div>

            <!-- Footer -->
            <div class="ptm-footer">
              <button type="button" class="ptm-btn-cancel" @click="closeCreateModal">Annuler</button>
              <button type="submit" class="ptm-btn-submit" :disabled="createSubmitting">
                <i v-if="createSubmitting" class="fas fa-spinner fa-spin me-1"></i>
                <i v-else class="fas fa-plus-circle me-1"></i>
                {{ createSubmitting ? 'Creation...' : 'Creer l\'activite' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

    <!-- Edit modal -->
    <PlanTravailEditModal
      :show="showEditModal"
      :plan-travail-id="editingPlanTravailId"
      @close="closeEditModal"
      @updated="handlePlanTravailUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { list, create, get, getCreateData, updateStatut, remove } from '@/api/planTravail'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import PlanTravailEditModal from '@/components/plan-travail/PlanTravailEditModal.vue'

const ui = useUiStore()
const router = useRouter()
const auth = useAuthStore()
const isPlanification = computed(() => auth.isPlanification || auth.isSEN || auth.isAdminNT || auth.isSuperAdmin)
const loading = ref(true)
const filtering = ref(false)
const initialLoadDone = ref(false)
const accessDenied = ref(false)
const accessDeniedMessage = ref("Vous ne pouvez consulter que le PTA de votre departement.")
const groupees = ref({})
const stats = ref({ total: 0, planifiee: 0, en_cours: 0, terminee: 0, avg_pourcentage: 0 })
const canEdit = ref(false)
const isGlobalPta = ref(false)
const filterDepts = ref([])
const filterProvinces = ref([])
const filters = ref({ annee: new Date().getFullYear(), trimestre: '', statut: '', departement_id: '', province_id: '', niveau_administratif: '' })

const trimestres = [
  { value: '', label: 'Tous' },
  { value: 'T1', label: 'T1' },
  { value: 'T2', label: 'T2' },
  { value: 'T3', label: 'T3' },
  { value: 'T4', label: 'T4' },
]

const years = computed(() => {
  const arr = []
  for (let y = new Date().getFullYear() + 1; y >= 2023; y--) arr.push(y)
  return arr
})

const flatActivites = computed(() => {
  const all = []
  for (const tri of Object.keys(groupees.value)) {
    for (const a of groupees.value[tri]) {
      all.push({ ...a, trimestre: tri })
    }
  }
  return all
})

async function loadPlan() {
  if (!initialLoadDone.value) {
    loading.value = true
  }
  filtering.value = true
  try {
    accessDenied.value = false
    const params = { annee: filters.value.annee }
    if (filters.value.trimestre) params.trimestre = filters.value.trimestre
    if (filters.value.statut) params.statut = filters.value.statut
    if (filters.value.departement_id) params.departement_id = filters.value.departement_id
    if (filters.value.province_id) params.province_id = filters.value.province_id
    if (filters.value.niveau_administratif) params.niveau_administratif = filters.value.niveau_administratif
    const { data } = await list(params)
    groupees.value = data.groupees
    stats.value = data.stats
    canEdit.value = data.canEdit
    isGlobalPta.value = data.isGlobalPta ?? false
    if (data.filterOptions) {
      filterDepts.value = data.filterOptions.departments || []
      filterProvinces.value = data.filterOptions.provinces || []
    }
  } catch (err) {
    if (err.response?.status === 403) {
      accessDenied.value = true
      accessDeniedMessage.value = err.response?.data?.message || 'Vous ne pouvez consulter que le PTA de votre departement.'
      groupees.value = {}
      stats.value = { total: 0, planifiee: 0, en_cours: 0, terminee: 0, avg_pourcentage: 0 }
      canEdit.value = false
      ui.addToast(accessDeniedMessage.value, 'warning')
    } else {
      ui.addToast('Erreur lors du chargement du plan de travail.', 'danger')
    }
  } finally {
    loading.value = false
    filtering.value = false
    initialLoadDone.value = true
  }
}

function setFilter(statut, trimestre) {
  filters.value.statut = statut
  filters.value.trimestre = trimestre
  loadPlan()
}

function resetPlanifFilters() {
  filters.value.departement_id = ''
  filters.value.province_id = ''
  filters.value.niveau_administratif = ''
  loadPlan()
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

function triLabel(tri) {
  const map = {
    T1: '1er Trimestre (Jan-Mar)',
    T2: '2e Trimestre (Avr-Jun)',
    T3: '3e Trimestre (Jul-Sep)',
    T4: '4e Trimestre (Oct-Dec)',
    Annuel: 'Activites annuelles',
  }
  return map[tri] || tri
}

function statutLabel(statut) {
  const map = { terminee: 'Terminee', en_cours: 'En cours', planifiee: 'Planifiee' }
  return map[statut] || statut
}

function statutBadgeClass(statut) {
  const map = { terminee: 'pt-badge done', en_cours: 'pt-badge progress', planifiee: 'pt-badge planned' }
  return map[statut] || 'pt-badge planned'
}

function statutIconClass(statut) {
  const map = { terminee: 'pt-si-done', en_cours: 'pt-si-progress', planifiee: 'pt-si-planned' }
  return map[statut] || 'pt-si-planned'
}

function statutIconName(statut) {
  const map = { terminee: 'fas fa-check-circle', en_cours: 'fas fa-spinner', planifiee: 'fas fa-clock' }
  return map[statut] || 'fas fa-clock'
}

function formatShortDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

/* ── Create modal ── */
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createErrors = ref({})
const createDepartments = ref([])
const createProvinces = ref([])
const createLocalites = ref([])
const createCategories = ref([])
const createResponsables = ref([])

/* ── Edit modal ── */
const showEditModal = ref(false)
const editingPlanTravailId = ref(null)
const createAgentsSen = ref([])

const niveauOptions = [
  { value: 'SEN', label: 'SEN (National)' },
  { value: 'SEP', label: 'SEP (Provincial)' },
  { value: 'SEL', label: 'SEL (Local)' },
]
const statutOptions = [
  { value: 'planifiee', label: 'Planifiee' },
  { value: 'en_cours', label: 'En cours' },
  { value: 'terminee', label: 'Terminee' },
]
const trimestreOptions = [
  { value: '', label: 'Annuel' },
  { value: 'T1', label: 'T1 (Jan-Mar)' },
  { value: 'T2', label: 'T2 (Avr-Jun)' },
  { value: 'T3', label: 'T3 (Jul-Sep)' },
  { value: 'T4', label: 'T4 (Oct-Dec)' },
]

function defaultCreateForm() {
  return {
    titre: '',
    categorie: '',
    objectif: '',
    responsable_code: '',
    cout_cdf: '',
    niveau_administratif: '',
    validation_niveau: '',
    departement_id: '',
    province_id: '',
    province_ids: [],
    localite_id: '',
    annee: new Date().getFullYear(),
    trimestre: '',
    trimestre_1: false,
    trimestre_2: false,
    trimestre_3: false,
    trimestre_4: false,
    statut: 'planifiee',
    pourcentage: 0,
    date_debut: '',
    date_fin: '',
    description: '',
    resultat_attendu: '',
    observations: '',
    assigned_agent_ids: [],
  }
}
const createForm = ref(defaultCreateForm())

async function openCreateModal() {
  createForm.value = defaultCreateForm()
  createErrors.value = {}
  showCreateModal.value = true
  try {
    const { data } = await getCreateData()
    createDepartments.value = data.departments || []
    createProvinces.value = data.provinces || []
    createLocalites.value = data.localites || []
    createCategories.value = data.categories || []
    createResponsables.value = data.responsables || []
    createAgentsSen.value = data.agents_sen || []
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast(err.response?.data?.message || 'Vous ne pouvez creer des activites PTA que pour votre departement.', 'warning')
      showCreateModal.value = false
    }
  }
}

function closeCreateModal() {
  showCreateModal.value = false
}

function onNiveauChange() {
  if (createForm.value.niveau_administratif !== 'SEN') createForm.value.departement_id = ''
  if (createForm.value.niveau_administratif !== 'SEL') createForm.value.province_id = ''
  if (createForm.value.niveau_administratif !== 'SEP') createForm.value.province_ids = []
  if (createForm.value.niveau_administratif !== 'SEL') createForm.value.localite_id = ''
}

async function handleCreateSubmit() {
  createSubmitting.value = true
  createErrors.value = {}
  try {
    const payload = { ...createForm.value }
    if (!payload.trimestre) delete payload.trimestre
    if (!payload.departement_id) delete payload.departement_id
    if (!payload.province_id) delete payload.province_id
    if (!payload.province_ids?.length) delete payload.province_ids
    if (!payload.localite_id) delete payload.localite_id
    if (!payload.date_debut) delete payload.date_debut
    if (!payload.date_fin) delete payload.date_fin
    if (!payload.description) delete payload.description
    if (!payload.objectif) delete payload.objectif
    if (!payload.resultat_attendu) delete payload.resultat_attendu
    if (!payload.observations) delete payload.observations
    if (!payload.categorie) delete payload.categorie
    if (!payload.responsable_code) delete payload.responsable_code
    if (payload.cout_cdf === '' || payload.cout_cdf === null) delete payload.cout_cdf
    if (!payload.assigned_agent_ids?.length) delete payload.assigned_agent_ids
    await create(payload)
    ui.addToast('Activite creee avec succes !', 'success')
    showCreateModal.value = false
    loadPlan()
  } catch (err) {
    if (err.response?.status === 422) {
      createErrors.value = err.response.data.errors || {}
    } else if (err.response?.status === 403) {
      ui.addToast(err.response?.data?.message || 'Vous ne pouvez creer des activites PTA que pour votre departement.', 'warning')
    } else {
      ui.addToast('Erreur lors de la creation.', 'danger')
    }
  } finally {
    createSubmitting.value = false
  }
}

// Edit modal functions
function openEditModal(planTravailId) {
  editingPlanTravailId.value = planTravailId
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  editingPlanTravailId.value = null
}

function handlePlanTravailUpdated() {
  loadPlan()
}

async function handleDeleteFromCard(id, evt) {
  evt.stopPropagation()
  if (!confirm('Supprimer cette activite PTA ? Cette action est irreversible.')) return
  try {
    await remove(id)
    ui.addToast('Activite supprimee.', 'success')
    loadPlan()
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur lors de la suppression.', 'danger')
  }
}

function activeTrimestres(activite) {
  const values = []
  if (activite.trimestre_1) values.push('T1')
  if (activite.trimestre_2) values.push('T2')
  if (activite.trimestre_3) values.push('T3')
  if (activite.trimestre_4) values.push('T4')
  if (!values.length && activite.trimestre) values.push(activite.trimestre)
  return values
}

function trimestreSlots(activite) {
  const active = new Set(activeTrimestres(activite))
  return ['T1', 'T2', 'T3', 'T4'].map((label) => ({ label, active: active.has(label) }))
}

function provinceSummary(activite) {
  const names = (activite.provinces || []).map((province) => province.nom).filter(Boolean)
  if (names.length) return names.join(', ')
  return activite.province?.nom || ''
}

function formatCurrency(value) {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount)
}

/* ── Detail popup ── */
const detailOpen = ref(false)
const detailLoading = ref(false)
const detailActivite = ref(null)
const detailCanEdit = ref(false)
const detailCanUpdateStatut = ref(false)
const detailUpdating = ref(false)
const detailStatutForm = ref({ statut: '', pourcentage: 0, observations: '' })

async function openDetailPopup(id) {
  detailOpen.value = true
  detailLoading.value = true
  detailActivite.value = null
  try {
    const { data } = await get(id)
    detailActivite.value = data.data
    detailCanEdit.value = data.canEdit
    detailCanUpdateStatut.value = data.canUpdateStatut
    detailStatutForm.value = {
      statut: data.data.statut,
      pourcentage: data.data.pourcentage,
      observations: data.data.observations || '',
    }
  } catch (err) {
    ui.addToast('Erreur lors du chargement.', 'danger')
    detailOpen.value = false
  } finally {
    detailLoading.value = false
  }
}

function closeDetailPopup() {
  detailOpen.value = false
  detailActivite.value = null
}

async function handleDetailUpdateStatut() {
  detailUpdating.value = true
  try {
    const { data } = await updateStatut(detailActivite.value.id, detailStatutForm.value)
    detailActivite.value = data.data
    detailStatutForm.value = {
      statut: data.data.statut,
      pourcentage: data.data.pourcentage,
      observations: data.data.observations || '',
    }
    ui.addToast('Statut mis a jour.', 'success')
    loadPlan()
  } catch (err) {
    ui.addToast(err.response?.data?.message || 'Erreur.', 'danger')
  } finally {
    detailUpdating.value = false
  }
}

function detailStatutBadge(statut) {
  const map = { terminee: 'pt-badge done', en_cours: 'pt-badge progress', planifiee: 'pt-badge planned' }
  return map[statut] || 'pt-badge planned'
}

function detailStatutLabel(statut) {
  const map = { terminee: 'Terminee', en_cours: 'En cours', planifiee: 'Planifiee' }
  return map[statut] || statut
}

function detailValidationLabel(value) {
  const map = { direction: 'Direction', coordination_nationale: 'Coordination nationale', coordination_provinciale: 'Coordination provinciale' }
  return map[value] || 'Non renseigne'
}

function detailProvinceSummary(activite) {
  const names = (activite.provinces || []).map(p => p.nom).filter(Boolean)
  if (names.length) return names.join(', ')
  return activite.province?.nom || ''
}

function detailFormatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' a ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function detailIsOverdue() {
  if (!detailActivite.value?.date_fin || detailActivite.value.statut === 'terminee') return false
  return new Date(detailActivite.value.date_fin) < new Date()
}

onMounted(() => loadPlan())
</script>

<style scoped>
/* ── Hero ── */
.pt-hero {
  background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
  border-radius: 18px; padding: 2rem 2.2rem; margin-bottom: 1.5rem; color: #fff;
  position: relative; overflow: hidden;
}
.pt-hero::before {
  content: ''; position: absolute; top: -40%; right: -8%;
  width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.07);
}
.pt-hero-body {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 1.5rem; position: relative; z-index: 1;
}
.pt-hero-text h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .3rem; }
.pt-hero-text p { font-size: .85rem; opacity: .8; margin: 0; }
.pt-hero-stats { display: flex; gap: 1.5rem; margin-top: 1rem; }
.pt-hero-stat-val { font-size: 1.5rem; font-weight: 800; }
.pt-hero-stat-lbl { font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .5px; }
.pt-hero-actions { display: flex; flex-direction: column; align-items: flex-end; gap: .75rem; flex-shrink: 0; }
.pt-hero-btn {
  display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.2rem;
  border-radius: 10px; font-size: .85rem; font-weight: 700;
  background: rgba(255,255,255,.18); color: #fff; text-decoration: none;
  border: 1px solid rgba(255,255,255,.25); transition: all .2s; cursor: pointer;
}
.pt-hero-btn:hover { background: rgba(255,255,255,.3); color: #fff; }
.pt-hero-filters { display: flex; gap: .5rem; }
.pt-alert {
  display: flex; align-items: flex-start; gap: .9rem;
  padding: 1rem 1.1rem; margin-bottom: 1rem; border-radius: 14px;
  border: 1px solid transparent;
}
.pt-alert-danger {
  background: #fff7ed;
  border-color: #fdba74;
  color: #9a3412;
}
.pt-alert-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(234, 88, 12, .12); color: #c2410c; flex-shrink: 0;
}
.pt-alert h5 { margin: 0 0 .2rem; font-size: .95rem; font-weight: 700; }
.pt-alert p { margin: 0; font-size: .82rem; }
.pt-filter-select {
  background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
  color: #fff; border-radius: 8px; padding: .35rem .7rem; font-size: .8rem;
}
.pt-filter-select option { color: #1e293b; background: #fff; }
.pt-filter-select:focus { outline: none; border-color: rgba(255,255,255,.5); }

/* ── Status filter cards ── */
.pt-filter-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: .8rem; margin-bottom: 1rem;
}
.pt-filter-card {
  display: flex; align-items: center; gap: .7rem; padding: .9rem 1rem;
  background: #fff; border: 2px solid #e5e7eb; border-radius: 14px;
  color: #374151; transition: all .25s; cursor: pointer;
}
.pt-filter-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }

.pt-quarter-strip {
  display: inline-flex;
  gap: .35rem;
  margin-top: .65rem;
  flex-wrap: wrap;
}

.pt-quarter-chip {
  min-width: 42px;
  padding: .22rem .5rem;
  border-radius: 999px;
  border: 1px solid #d1d5db;
  background: #f8fafc;
  color: #64748b;
  font-size: .72rem;
  font-weight: 700;
  text-align: center;
}

.pt-quarter-chip.active {
  border-color: #8b5cf6;
  background: linear-gradient(135deg, #8b5cf6, #6d28d9);
  color: #fff;
  box-shadow: 0 4px 10px rgba(109, 40, 217, .18);
}

.pt-filter-all .pt-filter-icon { background: #ede9fe; color: #7c3aed; }
.pt-filter-all:hover { border-color: #7c3aed; color: #7c3aed; }
.pt-filter-all.active { background: linear-gradient(135deg, #7c3aed, #6d28d9); border-color: #7c3aed; color: #fff; box-shadow: 0 4px 16px rgba(124,58,237,.25); }
.pt-filter-all.active .pt-filter-icon { background: rgba(255,255,255,.2); color: #fff; }

.pt-filter-planned .pt-filter-icon { background: #f1f5f9; color: #64748b; }
.pt-filter-planned:hover { border-color: #64748b; }
.pt-filter-planned.active { background: linear-gradient(135deg, #64748b, #475569); border-color: #64748b; color: #fff; box-shadow: 0 4px 16px rgba(100,116,139,.25); }
.pt-filter-planned.active .pt-filter-icon { background: rgba(255,255,255,.2); color: #fff; }

.pt-filter-progress .pt-filter-icon { background: #dbeafe; color: #2563eb; }
.pt-filter-progress:hover { border-color: #2563eb; color: #1e40af; }
.pt-filter-progress.active { background: linear-gradient(135deg, #2563eb, #1d4ed8); border-color: #2563eb; color: #fff; box-shadow: 0 4px 16px rgba(37,99,235,.25); }
.pt-filter-progress.active .pt-filter-icon { background: rgba(255,255,255,.2); color: #fff; }

.pt-filter-done .pt-filter-icon { background: #dcfce7; color: #16a34a; }
.pt-filter-done:hover { border-color: #16a34a; color: #166534; }
.pt-filter-done.active { background: linear-gradient(135deg, #16a34a, #15803d); border-color: #16a34a; color: #fff; box-shadow: 0 4px 16px rgba(22,163,74,.25); }
.pt-filter-done.active .pt-filter-icon { background: rgba(255,255,255,.2); color: #fff; }

.pt-filter-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.pt-filter-info { flex: 1; min-width: 0; text-align: left; }
.pt-filter-name { font-size: .82rem; font-weight: 700; line-height: 1.2; }
.pt-filter-count { font-size: .7rem; opacity: .6; }
.pt-filter-card.active .pt-filter-count { opacity: .8; }

/* ── Trimester pills ── */
.pt-trim-bar { display: flex; gap: .4rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.pt-trim-btn {
  padding: .35rem .85rem; border-radius: 20px; font-size: .78rem; font-weight: 600;
  border: 1px solid #e2e8f0; background: #fff; color: #64748b;
  cursor: pointer; transition: all .2s;
}
.pt-trim-btn:hover { border-color: #7c3aed; color: #7c3aed; }
.pt-trim-btn.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

/* ── Progress bar ── */
.pt-progress-bar {
  background: #fff; border-radius: 14px; padding: 1rem 1.25rem;
  border: 1px solid #e5e7eb; box-shadow: 0 2px 12px rgba(0,0,0,.04); margin-bottom: 1.25rem;
}
.pt-progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
.pt-progress-label { font-size: .82rem; font-weight: 700; color: #1e293b; }
.pt-progress-val { font-size: .82rem; font-weight: 700; color: #7c3aed; }
.pt-progress-track { height: 8px; border-radius: 6px; background: #f1f5f9; overflow: hidden; }
.pt-progress-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #7c3aed, #a78bfa); transition: width .4s; }

/* ── Section header ── */
.pt-section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1rem; padding-bottom: .6rem; border-bottom: 2px solid #f3f4f6;
}
.pt-section-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: .5rem; }
.pt-section-badge { font-size: .72rem; font-weight: 700; padding: .2rem .6rem; border-radius: 20px; background: #ede9fe; color: #7c3aed; }
.pt-back-btn {
  display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .8rem;
  border-radius: 8px; font-size: .78rem; font-weight: 600;
  background: #f3f4f6; color: #6b7280; text-decoration: none; border: none; cursor: pointer; transition: all .2s;
}
.pt-back-btn:hover { background: #e5e7eb; color: #374151; }

/* ── Activity cards grid ── */
.pt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; }
.pt-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,.04); overflow: hidden; transition: all .2s;
  display: flex; flex-direction: column;
}
.pt-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.pt-card-top { display: flex; align-items: flex-start; gap: .8rem; padding: 1.2rem 1.2rem .6rem; }
.pt-card-status-icon {
  width: 44px; height: 44px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.pt-si-planned { background: #f1f5f9; color: #64748b; }
.pt-si-progress { background: #dbeafe; color: #2563eb; }
.pt-si-done { background: #dcfce7; color: #16a34a; }

.pt-card-info { flex: 1; min-width: 0; }
.pt-card-title { font-weight: 700; font-size: .9rem; color: #1e293b; text-decoration: none; display: block; line-height: 1.3; margin-bottom: .25rem; }
.pt-card-title:hover { color: #7c3aed; }
.pt-card-desc { font-size: .78rem; color: #9ca3af; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: .4rem; }
.pt-card-tags { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }

.pt-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 6px; font-size: .72rem; font-weight: 600; }
.pt-badge.planned { background: #f1f5f9; color: #475569; }
.pt-badge.progress { background: #dbeafe; color: #1e40af; }
.pt-badge.done { background: #dcfce7; color: #166534; }
.pt-meta-badge { font-size: .68rem; font-weight: 600; padding: .2rem .55rem; border-radius: 6px; background: #f3f4f6; color: #6b7280; }

.pt-card-meta { padding: .4rem 1.2rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.pt-meta-item { font-size: .75rem; color: #9ca3af; display: flex; align-items: center; }

.pt-card-progress { padding: .4rem 1.2rem; display: flex; align-items: center; gap: .6rem; }
.pt-card-progress-track { flex: 1; height: 6px; border-radius: 6px; background: #f1f5f9; overflow: hidden; }
.pt-card-progress-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #7c3aed, #a78bfa); transition: width .4s; }
.pt-card-progress-fill.done { background: linear-gradient(90deg, #16a34a, #4ade80); }
.pt-card-progress-val { font-size: .75rem; font-weight: 700; color: #7c3aed; flex-shrink: 0; min-width: 35px; text-align: right; }

.pt-card-footer {
  border-top: 1px solid #f3f4f6; padding: .7rem 1.2rem;
  display: flex; align-items: center; justify-content: space-between; margin-top: auto; gap: .5rem;
}
.pt-card-date { font-size: .72rem; color: #9ca3af; }
.pt-card-actions { display: flex; gap: .4rem; }
.pt-act-btn {
  display: inline-flex; align-items: center; gap: .25rem; padding: .3rem .65rem;
  border-radius: 8px; font-size: .72rem; font-weight: 600; text-decoration: none;
  border: 1px solid #e2e8f0; background: #fff; cursor: pointer; transition: all .2s;
}
.pt-act-view { color: #7c3aed; }
.pt-act-view:hover { background: #f5f3ff; border-color: #7c3aed; }
.pt-act-edit { color: #d97706; }
.pt-act-edit:hover { background: #fffbeb; border-color: #d97706; }

/* ── Empty state ── */
.pt-empty { text-align: center; padding: 3rem 1rem; color: #9ca3af; }

/* ── Filtering overlay ── */
.pt-filtering { opacity: 0.4; pointer-events: none; transition: opacity .2s; }
.pt-empty-icon {
  width: 64px; height: 64px; border-radius: 50%; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; color: #d1d5db;
}

/* ── Mobile ── */
@media (max-width: 576px) {
  .pt-hero { padding: 1.4rem 1.2rem; border-radius: 14px; }
  .pt-hero-body { flex-direction: column; gap: 1rem; }
  .pt-hero-actions { align-items: stretch; width: 100%; }
  .pt-hero-btn { justify-content: center; }
  .pt-filter-grid { grid-template-columns: repeat(2, 1fr); }
  .pt-grid { grid-template-columns: 1fr; }
  .pt-section-header { flex-direction: column; align-items: flex-start; gap: .5rem; }
  .pt-card-footer { flex-direction: column; align-items: flex-start; gap: .5rem; }
  .pt-card-actions { width: 100%; }
  .pt-act-btn { flex: 1; justify-content: center; }
}

/* ── Create Modal (ptm-*) ── */
.ptm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  animation: ptmFadeIn .2s ease;
}
@keyframes ptmFadeIn { from { opacity: 0; } to { opacity: 1; } }

.ptm-dialog {
  background: #fff; border-radius: 20px; width: 100%; max-width: 620px;
  max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.25);
  animation: ptmSlideUp .25s ease;
}
@keyframes ptmSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.ptm-header {
  display: flex; align-items: center; gap: .8rem;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f3f4f6;
  background: linear-gradient(135deg, #faf5ff 0%, #f5f3ff 100%);
  border-radius: 20px 20px 0 0;
}
.ptm-header-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.ptm-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.ptm-subtitle { font-size: .78rem; color: #64748b; margin: 0; }
.ptm-close {
  margin-left: auto; background: none; border: none; cursor: pointer;
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; color: #94a3b8;
  transition: all .2s; font-size: 1rem;
}
.ptm-close:hover { background: #fee2e2; color: #ef4444; }

.ptm-body { padding: 1.25rem 1.5rem; }

.ptm-field { margin-bottom: 1rem; }
.ptm-label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
.ptm-req { color: #ef4444; }

.ptm-input {
  width: 100%; padding: .55rem .8rem; border: 1.5px solid #e2e8f0;
  border-radius: 10px; font-size: .85rem; color: #1e293b;
  background: #f8fafc; transition: all .2s;
}
.ptm-input:focus { outline: none; border-color: #7c3aed; background: #fff; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.ptm-input.ptm-err { border-color: #ef4444; }
.ptm-textarea { resize: vertical; min-height: 60px; }

.ptm-err-msg { display: block; font-size: .72rem; color: #ef4444; margin-top: .25rem; }

.ptm-row { display: flex; gap: .75rem; }
.ptm-half { flex: 1; min-width: 0; }

.ptm-card-row { display: flex; gap: .5rem; flex-wrap: wrap; }
.ptm-opt-card {
  padding: .45rem .85rem; border-radius: 10px; font-size: .8rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b;
  cursor: pointer; transition: all .2s;
}
.ptm-opt-card:hover { border-color: #7c3aed; color: #7c3aed; }
.ptm-opt-card.active { background: linear-gradient(135deg, #7c3aed, #6d28d9); border-color: #7c3aed; color: #fff; }

.ptm-footer {
  display: flex; gap: .75rem; justify-content: flex-end;
  padding-top: 1rem; border-top: 1px solid #f3f4f6; margin-top: .5rem;
}
.ptm-btn-cancel {
  padding: .55rem 1.2rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .2s;
}
.ptm-btn-cancel:hover { background: #f3f4f6; }
.ptm-btn-submit {
  padding: .55rem 1.5rem; border-radius: 10px; font-size: .82rem; font-weight: 700;
  border: none; background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff;
  cursor: pointer; transition: all .2s;
}
.ptm-btn-submit:hover { box-shadow: 0 4px 16px rgba(124,58,237,.3); }
.ptm-btn-submit:disabled { opacity: .6; cursor: not-allowed; }

@media (max-width: 576px) {
  .ptm-dialog { max-width: 100%; border-radius: 16px; }
  .ptm-header { padding: 1rem 1.1rem; border-radius: 16px 16px 0 0; }
  .ptm-body { padding: 1rem 1.1rem; }
  .ptm-row { flex-direction: column; gap: 0; }
  .ptm-card-row { flex-direction: column; }
  .ptm-opt-card { text-align: center; }
  .ptm-footer { flex-direction: column; }
  .ptm-btn-cancel, .ptm-btn-submit { width: 100%; text-align: center; }
}

/* ── Detail Popup (ptd-*) ── */
.ptd-fade-enter-active, .ptd-fade-leave-active { transition: opacity .25s ease; }
.ptd-fade-enter-from, .ptd-fade-leave-to { opacity: 0; }

.ptd-overlay {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,.35); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
}
.ptd-dialog {
  background: rgba(255,255,255,.92); border-radius: 22px;
  width: 100%; max-width: 680px; max-height: 88vh; overflow-y: auto;
  box-shadow: 0 30px 70px rgba(0,0,0,.22);
  animation: ptdSlide .25s ease;
}
@keyframes ptdSlide { from { opacity: 0; transform: translateY(24px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }

.ptd-loading { padding: 3rem; text-align: center; color: #7c3aed; font-size: 1rem; }

.ptd-header {
  display: flex; align-items: flex-start; gap: .8rem;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,.07);
  background: linear-gradient(135deg, rgba(124,58,237,.08) 0%, rgba(109,40,217,.04) 100%);
  border-radius: 22px 22px 0 0;
}
.ptd-header-icon {
  flex-shrink: 0; width: 40px; height: 40px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff;
}
.ptd-si-done { background: linear-gradient(135deg, #10b981, #34d399); }
.ptd-si-progress { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
.ptd-si-planned { background: linear-gradient(135deg, #64748b, #94a3b8); }
.ptd-header-info { flex: 1; min-width: 0; }
.ptd-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.3; }
.ptd-sub { font-size: .75rem; color: #64748b; margin-top: .15rem; }

.ptd-close {
  flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%;
  border: none; background: rgba(0,0,0,.06); color: #64748b;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: .2s;
}
.ptd-close:hover { background: rgba(239,68,68,.15); color: #ef4444; }

.ptd-body { padding: 1.25rem 1.5rem; }

.ptd-progress-section {
  display: flex; align-items: center; gap: 1rem;
  margin-bottom: 1.2rem; flex-wrap: wrap;
}
.ptd-progress { flex: 1; min-width: 150px; display: flex; align-items: center; gap: .6rem; }
.ptd-progress-track { flex: 1; height: 7px; border-radius: 6px; background: #f1f5f9; overflow: hidden; }
.ptd-progress-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #7c3aed, #a78bfa); transition: width .4s; }
.ptd-progress-fill.done { background: linear-gradient(90deg, #10b981, #34d399); }
.ptd-progress-val { font-size: .8rem; font-weight: 700; color: #7c3aed; white-space: nowrap; }

.ptd-info-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: .6rem;
  margin-bottom: 1.2rem;
}
.ptd-info-item {
  display: flex; gap: .5rem; align-items: flex-start;
  padding: .55rem .7rem; border-radius: 10px; background: rgba(124,58,237,.04);
  border: 1px solid rgba(124,58,237,.06);
}
.ptd-info-item > i { color: #7c3aed; font-size: .78rem; margin-top: .15rem; }
.ptd-info-lbl { display: block; font-size: .65rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; }
.ptd-info-val { display: block; font-size: .82rem; font-weight: 600; color: #1e293b; }

.ptd-chrono { margin-bottom: 1.2rem; }
.ptd-chrono-strip { display: flex; gap: .4rem; }
.ptd-chrono-chip {
  padding: .25rem .65rem; border-radius: 14px; font-size: .72rem; font-weight: 600;
  background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0;
}
.ptd-chrono-chip.active { background: #ede9fe; color: #7c3aed; border-color: #c4b5fd; }

.ptd-section { margin-bottom: 1.1rem; }
.ptd-section h6 { font-size: .82rem; font-weight: 700; color: #1e293b; margin-bottom: .4rem; }
.ptd-section p { font-size: .82rem; color: #475569; margin: 0; white-space: pre-line; }

.ptd-taches { display: flex; flex-direction: column; gap: .3rem; }
.ptd-tache {
  display: flex; align-items: center; justify-content: space-between; gap: .5rem;
  padding: .4rem .6rem; border-radius: 8px; background: #f8fafc; border: 1px solid #f1f5f9;
}
.ptd-tache-name { font-size: .78rem; font-weight: 600; color: #334155; }

.ptd-meta {
  display: flex; gap: 1.5rem; font-size: .72rem; color: #94a3b8;
  padding: .7rem 0; border-top: 1px solid #f3f4f6; margin-bottom: .8rem;
}

.ptd-update {
  background: rgba(124,58,237,.03); border: 1px solid rgba(124,58,237,.08);
  border-radius: 14px; padding: 1rem; margin-bottom: .8rem;
}
.ptd-update h6 { font-size: .82rem; font-weight: 700; color: #7c3aed; margin-bottom: .6rem; }
.ptd-update-form { display: flex; flex-direction: column; gap: .5rem; }
.ptd-update-row { display: flex; gap: .5rem; align-items: center; }
.ptd-update-row select { flex: 0 0 130px; }
.ptd-range-wrap { display: flex; align-items: center; gap: .4rem; flex: 1; }
.ptd-range-wrap input[type="range"] { flex: 1; accent-color: #7c3aed; }
.ptd-range-wrap span { font-size: .78rem; font-weight: 700; color: #7c3aed; min-width: 35px; }
.ptd-update-btn {
  align-self: flex-end; padding: .4rem 1rem; border-radius: 10px; font-size: .78rem;
  font-weight: 600; border: none; background: #7c3aed; color: #fff; cursor: pointer; transition: .2s;
}
.ptd-update-btn:hover { background: #6d28d9; }
.ptd-update-btn:disabled { opacity: .6; cursor: default; }

.ptd-actions {
  display: flex; gap: .5rem; padding-top: .5rem; border-top: 1px solid #f3f4f6;
}
.ptd-act-btn {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .4rem .9rem; border-radius: 10px; font-size: .78rem; font-weight: 600;
  text-decoration: none; border: none; cursor: pointer; transition: .2s;
}
.ptd-act-edit { background: #ede9fe; color: #7c3aed; }
.ptd-act-edit:hover { background: #ddd6fe; }
.ptd-act-full { background: #f1f5f9; color: #64748b; }
.ptd-act-full:hover { background: #e2e8f0; color: #475569; }

@media (max-width: 576px) {
  .ptd-dialog { max-width: 100%; border-radius: 16px; }
  .ptd-info-grid { grid-template-columns: 1fr; }
  .ptd-update-row { flex-direction: column; }
  .ptd-update-row select { flex: 1; width: 100%; }
  .ptd-actions { flex-direction: column; }
  .ptd-act-btn { justify-content: center; width: 100%; }
}

/* ── Planification global filters ── */
.pt-planif-filters {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  align-items: center;
  padding: .5rem 1rem;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}
.pt-filter-select {
  padding: .3rem .6rem;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  background: #fff;
  font-size: .82rem;
  color: #495057;
  cursor: pointer;
  min-width: 160px;
}
.pt-filter-select:focus { outline: none; border-color: #0d6efd; }
.pt-reset-planif-btn {
  padding: .3rem .7rem;
  border: 1px solid #dc3545;
  border-radius: 6px;
  background: transparent;
  color: #dc3545;
  font-size: .78rem;
  cursor: pointer;
  transition: background .15s;
}
.pt-reset-planif-btn:hover { background: #dc3545; color: #fff; }

/* ── Delete card button ── */
.pt-act-delete {
  background: transparent;
  border: 1px solid #dc3545;
  color: #dc3545;
  padding: .25rem .5rem;
  border-radius: 5px;
  font-size: .78rem;
  cursor: pointer;
  transition: background .15s;
}
.pt-act-delete:hover { background: #dc3545; color: #fff; }

/* ── Detail popup: agents ── */
.ptd-agents-list {
  display: flex;
  flex-wrap: wrap;
  gap: .4rem;
  margin-top: .35rem;
}
.ptd-agent-pill {
  display: inline-flex;
  align-items: center;
  padding: .25rem .6rem;
  background: rgba(13,110,253,.08);
  border: 1px solid rgba(13,110,253,.2);
  border-radius: 20px;
  font-size: .8rem;
  color: #495057;
}
.ptd-agent-fn { color: #6c757d; font-size: .75rem; }

/* ── Create modal: agents checkboxes ── */
.ptm-agents-check-list {
  display: flex;
  flex-wrap: wrap;
  gap: .4rem;
  margin-top: .25rem;
}
.ptm-agent-check {
  display: inline-flex;
  align-items: center;
  padding: .3rem .7rem;
  border: 1px solid #dee2e6;
  border-radius: 20px;
  cursor: pointer;
  font-size: .82rem;
  color: #495057;
  transition: background .15s, border-color .15s;
  user-select: none;
}
.ptm-agent-check:hover { border-color: #0d6efd; }
.ptm-agent-check.active {
  background: rgba(13,110,253,.1);
  border-color: #0d6efd;
  color: #0d6efd;
}
.ptm-agent-fn { color: #6c757d; font-size: .76rem; }
.ptm-hint { color: #6c757d; font-size: .78rem; font-weight: 400; }
.ptm-field-hint { font-size: .77rem; color: #6c757d; margin-top: .2rem; }
</style>
