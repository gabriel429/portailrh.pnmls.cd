<template>
  <div class="rh-dashboard">
    <!-- HERO -->
    <div class="rh-hero">
      <div class="rh-hero-bg"></div>
      <div class="rh-hero-inner">
        <div class="rh-hero-left">
          <div class="rh-hero-badge">
            <img v-if="auth.agent?.photo" :src="'/' + auth.agent.photo" alt="Photo" class="rh-hero-photo" @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display=''">
            <i class="fas fa-user-tie" :style="auth.agent?.photo ? { display: 'none' } : {}"></i>
          </div>
          <div>
            <div class="rh-hero-greeting">Tableau de bord</div>
            <h1 class="rh-hero-name">Ressources Humaines</h1>
            <div class="rh-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>
        <div class="rh-hero-kpis">
          <div class="rh-kpi" @click="openOrganeDrilldown('SEN', 'effectifs')">
            <div class="rh-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="rh-kpi-val">{{ d.agents?.actifs ?? '-' }}</div>
              <div class="rh-kpi-lbl">Agents actifs</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rh-kpi" @click="openOrganeDrilldown('SEN', 'presence')">
            <div class="rh-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="rh-kpi-val">{{ d.attendance?.today_rate ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="rh-kpi-lbl">Presence</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rh-kpi" @click="router.push('/requests')">
            <div class="rh-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="rh-kpi-val">{{ d.requests?.en_attente ?? 0 }}</div>
              <div class="rh-kpi-lbl">En attente</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="rh-kpi" @click="router.push('/signalements')">
            <div class="rh-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
              <div class="rh-kpi-val">{{ d.signalements?.ouvert ?? 0 }}</div>
              <div class="rh-kpi-lbl">Signalements</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord RH..." />

    <div v-else-if="loadError" class="alert alert-warning mx-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <template v-else>
      <!-- ACTIONS RAPIDES -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#e0f2fe;color:#0077B5;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Actions rapides</h3>
            <p class="rh-section-sub">Acces direct aux modules RH</p>
          </div>
        </div>
        <div class="rh-actions">
          <router-link v-for="a in quickActions" :key="a.to" :to="a.to" class="rh-action">
            <div class="rh-action-glow" :style="{ background: a.color }"></div>
            <div class="rh-action-icon" :style="{ background: a.bg, color: a.color }">
              <i class="fas" :class="a.icon"></i>
            </div>
            <div class="rh-action-text">
              <div class="rh-action-label">{{ a.label }}</div>
              <div class="rh-action-desc">{{ a.desc }}</div>
            </div>
            <i class="fas fa-chevron-right rh-action-arrow"></i>
          </router-link>
        </div>
      </div>

      <!-- INDICATEURS CLES -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Indicateurs cles</h3>
            <p class="rh-section-sub">Vue d'ensemble des ressources humaines</p>
          </div>
        </div>
        <div class="rh-metrics">
          <div v-for="m in metrics" :key="m.label" class="rh-metric" @click="m.drill ? m.drill() : router.push(m.to)">
            <div class="rh-metric-header">
              <div class="rh-metric-icon" :style="{ background: m.bg, color: m.color }">
                <i class="fas" :class="m.icon"></i>
              </div>
              <span v-if="m.alert" class="rh-metric-alert">
                <i class="fas fa-exclamation-circle"></i>
              </span>
            </div>
            <div class="rh-metric-val" :style="{ color: m.color }">{{ m.value }}</div>
            <div class="rh-metric-lbl">{{ m.label }}</div>
            <div class="rh-metric-bar">
              <div class="rh-metric-bar-fill" :style="{ background: m.color, width: m.pct + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- STATUTS DES AGENTS -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-user-clock"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Statuts des agents</h3>
            <p class="rh-section-sub">Agents en congé, mission, formation ou suspension</p>
          </div>
        </div>
        <div class="as-grid">
          <div
            v-for="st in statusCards"
            :key="st.key"
            class="as-card"
            :class="{ 'as-card-open': expandedStatus === st.key }"
            :style="{ borderTop: '4px solid ' + st.color }"
          >
            <div class="as-card-header" @click="toggleStatus(st.key)">
              <div class="as-card-left">
                <div class="as-card-badge" :style="{ background: st.bg, color: st.color }">
                  <i class="fas" :class="st.icon"></i>
                </div>
                <div>
                  <div class="as-card-label">{{ st.label }}</div>
                  <div class="as-card-count" :style="{ color: st.color }">
                    {{ agentStatusCount(st.key) }}
                    <span class="as-card-count-unit">agent{{ agentStatusCount(st.key) > 1 ? 's' : '' }}</span>
                  </div>
                </div>
              </div>
              <div class="as-card-toggle" :class="{ open: expandedStatus === st.key }">
                <i class="fas fa-chevron-down"></i>
              </div>
            </div>

            <!-- Expanded agent list -->
            <div v-if="expandedStatus === st.key" class="as-card-body">
              <div v-if="agentStatusList(st.key).length" class="as-agent-list">
                <div v-for="agent in agentStatusList(st.key)" :key="agent.id" class="as-agent-row">
                  <div class="as-agent-avatar" :style="{ background: st.color }">
                    {{ agentInitials(agent) }}
                  </div>
                  <div class="as-agent-info">
                    <div class="as-agent-name">{{ agent.prenom }} {{ agent.nom }}</div>
                    <div class="as-agent-meta">
                      <span v-if="agent.id_agent" class="as-agent-tag">
                        <i class="fas fa-id-badge me-1"></i>{{ agent.id_agent }}
                      </span>
                      <span v-if="agent.organe" class="as-agent-tag">
                        <i class="fas fa-building me-1"></i>{{ agent.organe }}
                      </span>
                      <span v-if="agent.poste" class="as-agent-tag">
                        <i class="fas fa-briefcase me-1"></i>{{ agent.poste }}
                      </span>
                    </div>
                    <div class="as-agent-dates">
                      <i class="fas fa-calendar-alt me-1"></i>
                      {{ formatShortDate(agent.date_debut) }}
                      <template v-if="agent.date_fin">
                        <i class="fas fa-arrow-right mx-1" style="font-size:.55rem;opacity:.5;"></i>
                        {{ formatShortDate(agent.date_fin) }}
                      </template>
                      <span v-else class="as-no-end">En cours</span>
                    </div>
                    <div v-if="agent.motif" class="as-agent-motif">
                      <i class="fas fa-comment me-1"></i>{{ agent.motif }}
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="as-empty">
                <i class="fas fa-check-circle"></i>
                <span>Aucun agent {{ st.label.toLowerCase() }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- EFFECTIFS PAR ORGANE -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fas fa-sitemap"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Effectifs par organe</h3>
            <p class="rh-section-sub">{{ d.agents?.total ?? 0 }} agents au total, dont {{ d.agents?.actifs ?? 0 }} actifs</p>
          </div>
        </div>
        <div class="rh-organe-grid">
          <div v-for="o in organeCards" :key="o.code" class="rh-organe-card" :style="{ borderTop: '4px solid ' + o.color }" @click="openOrganeDrilldown(o.code, 'effectifs')">
            <div class="rh-organe-header">
              <div class="rh-organe-badge" :style="{ background: o.color }">{{ o.code }}</div>
              <div>
                <div class="rh-organe-name">{{ o.nom }}</div>
                <div class="rh-organe-sub">{{ orgPct(o.total) }}% de l'effectif</div>
              </div>
            </div>
            <div class="rh-organe-stats">
              <div class="rh-organe-stat">
                <div class="rh-organe-stat-val" style="color:#1e293b;">{{ o.total }}</div>
                <div class="rh-organe-stat-lbl">Total</div>
              </div>
              <div class="rh-organe-stat">
                <div class="rh-organe-stat-val" style="color:#059669;">{{ o.actifs }}</div>
                <div class="rh-organe-stat-lbl">Actifs</div>
              </div>
            </div>
            <div class="rh-organe-bar-bg">
              <div class="rh-organe-bar-fill" :style="{ background: o.color, width: orgPct(o.actifs) + '%' }"></div>
            </div>
          </div>

          <!-- Repartition par sexe -->
          <div class="rh-organe-card" style="border-top: 4px solid #8b5cf6;">
            <div class="rh-organe-header">
              <div class="rh-organe-badge" style="background:#8b5cf6;"><i class="fas fa-venus-mars" style="font-size:.7rem;"></i></div>
              <div>
                <div class="rh-organe-name">Repartition par sexe</div>
                <div class="rh-organe-sub">Agents actifs</div>
              </div>
            </div>
            <div class="rh-organe-stats">
              <div class="rh-organe-stat">
                <div class="rh-organe-stat-val" style="color:#2563eb;">{{ d.agents?.by_sexe?.M ?? d.agents?.by_sexe?.Masculin ?? 0 }}</div>
                <div class="rh-organe-stat-lbl">Hommes</div>
              </div>
              <div class="rh-organe-stat">
                <div class="rh-organe-stat-val" style="color:#ec4899;">{{ d.agents?.by_sexe?.F ?? d.agents?.by_sexe?.Feminin ?? 0 }}</div>
                <div class="rh-organe-stat-lbl">Femmes</div>
              </div>
            </div>
            <div class="rh-sexe-bars">
              <div class="rh-sexe-bar">
                <div class="rh-sexe-fill" style="background:#2563eb;" :style="{ width: sexePct('M') + '%' }"></div>
                <div class="rh-sexe-fill" style="background:#ec4899;" :style="{ width: sexePct('F') + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PRESENCE (7 derniers jours) -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-user-check"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Presence</h3>
            <p class="rh-section-sub">{{ d.attendance?.today_present ?? 0 }} / {{ d.attendance?.total_active_agents ?? 0 }} presents aujourd'hui ({{ d.attendance?.today_rate ?? 0 }}%)</p>
          </div>
        </div>
        <div class="rh-presence-row">
          <!-- Global card -->
          <div class="rh-presence-global">
            <div class="rh-presence-big" :class="presenceColor">{{ d.attendance?.today_rate ?? 0 }}%</div>
            <div class="rh-presence-sub-text">Taux aujourd'hui</div>
            <div class="rh-presence-detail">
              <div class="rh-presence-item">
                <span>Presents</span>
                <span class="fw-bold">{{ d.attendance?.today_present ?? 0 }}</span>
              </div>
              <div class="rh-presence-item">
                <span>Effectif actif</span>
                <span class="fw-bold">{{ d.attendance?.total_active_agents ?? 0 }}</span>
              </div>
              <div class="rh-presence-item">
                <span>Moy. mensuelle</span>
                <span class="fw-bold">{{ d.attendance?.monthly_avg_rate ?? 0 }}%</span>
              </div>
            </div>
          </div>
          <!-- Weekly chart -->
          <div class="rh-presence-chart">
            <div class="rh-chart-title">7 derniers jours</div>
            <div class="rh-bars">
              <div v-for="day in weeklyData" :key="day.date" class="rh-bar-col">
                <div class="rh-bar-value">{{ day.rate }}%</div>
                <div class="rh-bar-wrap">
                  <div class="rh-bar-fill" :style="{ height: day.rate + '%' }" :class="barColor(day.rate)"></div>
                </div>
                <div class="rh-bar-label">{{ day.label }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- DOCUMENTS & DEMANDES (side by side) -->
      <div class="rh-section">
        <div class="rh-two-cols">
          <!-- Demandes par type -->
          <div class="rh-col-card">
            <div class="rh-col-head" style="border-color:#d97706;">
              <div class="rh-col-head-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-paper-plane"></i>
              </div>
              <div>
                <div class="rh-col-head-title">Demandes par type</div>
                <div class="rh-col-head-count">{{ d.requests?.total ?? 0 }} au total</div>
              </div>
            </div>
            <div class="rh-col-body">
              <div v-for="(count, type) in (d.requests?.by_type || {})" :key="type" class="rh-type-row">
                <span class="rh-type-name">{{ type }}</span>
                <div class="rh-type-bar-wrap">
                  <div class="rh-type-bar" style="background:#fbbf24;" :style="{ width: typePct(count, d.requests?.total) + '%' }"></div>
                </div>
                <span class="rh-type-count">{{ count }}</span>
              </div>
              <div v-if="!Object.keys(d.requests?.by_type || {}).length" class="rh-empty">
                <i class="fas fa-inbox"></i> Aucune demande
              </div>
            </div>
          </div>

          <!-- Documents par type -->
          <div class="rh-col-card">
            <div class="rh-col-head" style="border-color:#6366f1;">
              <div class="rh-col-head-icon" style="background:#e0e7ff;color:#6366f1;">
                <i class="fas fa-folder-open"></i>
              </div>
              <div>
                <div class="rh-col-head-title">Documents</div>
                <div class="rh-col-head-count">{{ d.documents?.total ?? 0 }} au total &middot; {{ d.documents?.expires ?? 0 }} expires</div>
              </div>
            </div>
            <div class="rh-col-body">
              <div class="rh-doc-summary">
                <div class="rh-doc-stat">
                  <div class="rh-doc-stat-val" style="color:#059669;">{{ d.documents?.valides ?? 0 }}</div>
                  <div class="rh-doc-stat-lbl">Valides</div>
                </div>
                <div class="rh-doc-stat">
                  <div class="rh-doc-stat-val" style="color:#dc2626;">{{ d.documents?.expires ?? 0 }}</div>
                  <div class="rh-doc-stat-lbl">Expires</div>
                </div>
                <div class="rh-doc-stat">
                  <div class="rh-doc-stat-val" style="color:#1e293b;">{{ d.documents?.total ?? 0 }}</div>
                  <div class="rh-doc-stat-lbl">Total</div>
                </div>
              </div>
              <div v-for="(count, type) in (d.documents?.by_type || {})" :key="type" class="rh-type-row">
                <span class="rh-type-name">{{ type }}</span>
                <div class="rh-type-bar-wrap">
                  <div class="rh-type-bar" style="background:#818cf8;" :style="{ width: typePct(count, d.documents?.total) + '%' }"></div>
                </div>
                <span class="rh-type-count">{{ count }}</span>
              </div>
              <div v-if="!Object.keys(d.documents?.by_type || {}).length" class="rh-empty">
                <i class="fas fa-inbox"></i> Aucun document
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTIVITES RECENTES -->
      <div class="rh-section">
        <div class="rh-section-head">
          <div class="rh-section-icon" style="background:#fce7f3;color:#db2777;">
            <i class="fas fa-stream"></i>
          </div>
          <div>
            <h3 class="rh-section-title">Activites recentes</h3>
            <p class="rh-section-sub">Dernieres mises a jour</p>
          </div>
        </div>
        <div class="rh-recent-grid">
          <!-- Demandes recentes -->
          <div class="rh-recent-col">
            <div class="rh-recent-head" style="border-color:#d97706;">
              <div class="rh-recent-head-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <div class="rh-recent-head-title">Dernieres demandes</div>
                <div class="rh-recent-head-count">{{ d.requests?.en_attente ?? 0 }} en attente</div>
              </div>
            </div>
            <div v-if="d.requests?.recent?.length" class="rh-recent-body">
              <router-link v-for="r in d.requests.recent" :key="r.id" :to="'/requests/' + r.id" class="rh-recent-item">
                <div class="rh-recent-dot" :class="'statut-bg-' + r.statut"></div>
                <div class="rh-recent-info">
                  <div class="rh-recent-title">
                    {{ r.type }}
                    <span class="rh-statut-badge" :class="'statut-' + r.statut">{{ statutLabel(r.statut) }}</span>
                  </div>
                  <div class="rh-recent-subtitle" v-if="r.agent">{{ r.agent.prenom }} {{ r.agent.nom }}</div>
                  <div class="rh-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(r.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="rh-recent-empty">
              <i class="fas fa-check-circle"></i>
              <span>Aucune demande</span>
            </div>
          </div>

          <!-- Signalements recents -->
          <div class="rh-recent-col">
            <div class="rh-recent-head" style="border-color:#dc2626;">
              <div class="rh-recent-head-icon" style="background:#fee2e2;color:#dc2626;">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div>
                <div class="rh-recent-head-title">Signalements</div>
                <div class="rh-recent-head-count">{{ d.signalements?.ouvert ?? 0 }} ouverts &middot; {{ d.signalements?.en_cours ?? 0 }} en cours</div>
              </div>
              <span v-if="d.signalements?.haute_severite" class="rh-alert-badge">{{ d.signalements.haute_severite }} haute</span>
            </div>
            <div v-if="d.signalements?.recent?.length" class="rh-recent-body">
              <router-link v-for="s in d.signalements.recent" :key="s.id" :to="'/signalements/' + s.id" class="rh-recent-item">
                <div class="rh-recent-dot" :class="'sev-bg-' + s.severite"></div>
                <div class="rh-recent-info">
                  <div class="rh-recent-title">
                    {{ s.type }}
                    <span class="rh-sev-badge" :class="'sev-' + s.severite">{{ s.severite }}</span>
                  </div>
                  <div class="rh-recent-subtitle" v-if="s.agent">{{ s.agent.prenom }} {{ s.agent.nom }}</div>
                  <div class="rh-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(s.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="rh-recent-empty">
              <i class="fas fa-shield-alt"></i>
              <span>Aucun signalement</span>
            </div>
          </div>

          <!-- Communiques -->
          <div class="rh-recent-col">
            <div class="rh-recent-head" style="border-color:#0891b2;">
              <div class="rh-recent-head-icon" style="background:#cffafe;color:#0891b2;">
                <i class="fas fa-bullhorn"></i>
              </div>
              <div>
                <div class="rh-recent-head-title">Communiques</div>
                <div class="rh-recent-head-count">{{ d.communiques?.actifs ?? 0 }} actifs</div>
              </div>
              <span v-if="d.communiques?.urgents" class="rh-alert-badge">{{ d.communiques.urgents }} urgent(s)</span>
            </div>
            <div v-if="d.communiques?.recent?.length" class="rh-recent-body">
              <router-link v-for="c in d.communiques.recent" :key="c.id" :to="'/communiques/' + c.id" class="rh-recent-item">
                <div class="rh-recent-dot" :style="{ background: c.urgence ? '#ef4444' : '#0891b2' }"></div>
                <div class="rh-recent-info">
                  <div class="rh-recent-title">{{ c.titre }}</div>
                  <div class="rh-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(c.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="rh-recent-empty">
              <i class="fas fa-inbox"></i>
              <span>Aucun communique</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ DRILL-DOWN MODAL ═══ -->
      <Teleport to="body">
        <Transition name="drill-fade">
          <div v-if="drilldownOpen" class="drill-overlay" @click.self="closeDrilldown">
            <Transition name="drill-slide" mode="out-in">
              <div class="drill-panel" :key="drilldownLevel">
                <!-- Header -->
                <div class="drill-header" :style="{ background: drilldownColor }">
                  <div class="drill-header-left">
                    <button v-if="drilldownLevel === 'province' || drilldownLevel === 'department'" class="drill-back" @click="drilldownLevel === 'department' ? backToPrevious() : backToOrgane()">
                      <i class="fas fa-arrow-left"></i>
                    </button>
                    <div>
                      <div class="drill-header-title" v-if="drilldownLevel === 'organe' && drilldownOrgane">
                        <i class="fas fa-sitemap"></i> {{ drilldownOrgane.nom }}
                      </div>
                      <div class="drill-header-title" v-else-if="drilldownLevel === 'province' && drilldownProvince">
                        <i class="fas fa-map-marker-alt"></i> {{ drilldownProvince.province.nom }}
                      </div>
                      <div class="drill-header-title" v-else-if="drilldownLevel === 'department' && drilldownDepartment">
                        <i class="fas fa-building"></i> {{ drilldownDepartment.department.nom }}
                      </div>
                      <div class="drill-header-title" v-else>
                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                      </div>
                      <div class="drill-header-sub" v-if="drilldownLevel === 'organe' && drilldownOrgane">
                        <template v-if="drilldownSection === 'effectifs'">{{ drilldownOrgane.summary.total }} agents · {{ drilldownOrgane.items.length }} {{ drilldownOrgane.type_items }}</template>
                        <template v-else-if="drilldownSection === 'presence'">Présence · {{ drilldownOrgane.items.length }} {{ drilldownOrgane.type_items }}</template>
                        <template v-else>Plan de travail {{ new Date().getFullYear() }} · {{ drilldownOrgane.items.length }} {{ drilldownOrgane.type_items }}</template>
                      </div>
                      <div class="drill-header-sub" v-else-if="drilldownLevel === 'province' && drilldownProvince">
                        {{ drilldownProvince.effectifs.total }} agents · {{ drilldownProvince.province.ville_secretariat || '' }}
                      </div>
                      <div class="drill-header-sub" v-else-if="drilldownLevel === 'department' && drilldownDepartment">
                        <template v-if="drilldownSection === 'effectifs'">{{ drilldownDepartment.effectifs.total }} agents · {{ drilldownDepartment.department.code || '' }}</template>
                        <template v-else-if="drilldownSection === 'presence'">Présence · {{ drilldownDepartment.effectifs.actifs }} agents actifs</template>
                        <template v-else>PTA {{ new Date().getFullYear() }} · {{ drilldownDepartment.pta?.total || 0 }} activités</template>
                      </div>
                    </div>
                  </div>
                  <button class="drill-close" @click="closeDrilldown"><i class="fas fa-times"></i></button>
                </div>

                <!-- Body -->
                <div class="drill-body">
                  <div v-if="drilldownLoading" class="drill-loading">
                    <i class="fas fa-circle-notch fa-spin fa-2x" :style="{ color: drilldownColor }"></i>
                    <p>Chargement des données…</p>
                  </div>

                  <!-- ── LEVEL 1 : ORGANE → ITEMS ── -->
                  <template v-else-if="drilldownLevel === 'organe' && drilldownOrgane">
                    <div class="drill-summary">
                      <template v-if="drilldownSection === 'effectifs'">
                        <div class="drill-summary-item">
                          <div class="drill-summary-val">{{ drilldownOrgane.summary.actifs }}</div>
                          <div class="drill-summary-lbl">Actifs</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#d97706;">{{ drilldownOrgane.summary.suspendus }}</div>
                          <div class="drill-summary-lbl">Suspendus</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#64748b;">{{ drilldownOrgane.summary.anciens }}</div>
                          <div class="drill-summary-lbl">Anciens</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#0077B5;">{{ drilldownOrgane.summary.total }}</div>
                          <div class="drill-summary-lbl">Total</div>
                        </div>
                      </template>
                      <template v-else-if="drilldownSection === 'presence'">
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" :style="{ color: drilldownColor }">{{ drilldownOrgane.summary.actifs }}</div>
                          <div class="drill-summary-lbl">Agents actifs</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#059669;">{{ drilldownOrgane.items.reduce((s, i) => s + (i.presence?.today_present || 0), 0) }}</div>
                          <div class="drill-summary-lbl">Présents</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#d97706;">{{ drilldownOrgane.summary.actifs - drilldownOrgane.items.reduce((s, i) => s + (i.presence?.today_present || 0), 0) }}</div>
                          <div class="drill-summary-lbl">Absents</div>
                        </div>
                      </template>
                      <template v-else>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" :style="{ color: drilldownColor }">{{ drilldownOrgane.pta?.total || 0 }}</div>
                          <div class="drill-summary-lbl">Activités</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#059669;">{{ drilldownOrgane.pta?.terminee || 0 }}</div>
                          <div class="drill-summary-lbl">Terminées</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#d97706;">{{ drilldownOrgane.pta?.en_cours || 0 }}</div>
                          <div class="drill-summary-lbl">En cours</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#0077B5;">{{ drilldownOrgane.pta?.avg || 0 }}%</div>
                          <div class="drill-summary-lbl">Moy. avancement</div>
                        </div>
                      </template>
                    </div>

                    <div class="drill-items-grid">
                      <div
                        v-for="item in drilldownOrgane.items" :key="item.id"
                        class="drill-item-card drill-item-clickable"
                        @click="drilldownOrgane.type_items === 'provinces' ? openProvinceDrilldown(item.id) : openDepartmentDrilldown(item.id)"
                      >
                        <div class="drill-item-head">
                          <div class="drill-item-badge" :style="{ background: drilldownColor }">
                            <i :class="drilldownOrgane.type_items === 'provinces' ? 'fas fa-map-marker-alt' : 'fas fa-building'"></i>
                          </div>
                          <div class="drill-item-info">
                            <div class="drill-item-name">{{ item.nom }}</div>
                            <div class="drill-item-sub" v-if="item.ville_secretariat">{{ item.ville_secretariat }}</div>
                            <div class="drill-item-sub" v-else-if="item.code">{{ item.code }}</div>
                          </div>
                          <i class="fas fa-chevron-right drill-item-arrow"></i>
                        </div>
                        <div class="drill-item-stats">
                          <template v-if="drilldownSection === 'effectifs'">
                            <div class="drill-item-stat">
                              <span class="drill-stat-val">{{ item.effectifs.total }}</span>
                              <span class="drill-stat-lbl">Agents</span>
                            </div>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" style="color:#059669;">{{ item.effectifs.actifs }}</span>
                              <span class="drill-stat-lbl">Actifs</span>
                            </div>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" style="color:#d97706;">{{ item.effectifs.suspendus }}</span>
                              <span class="drill-stat-lbl">Suspendus</span>
                            </div>
                          </template>
                          <template v-else-if="drilldownSection === 'presence'">
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" :style="{ color: drilldownColor }">{{ item.presence.today_rate }}%</span>
                              <span class="drill-stat-lbl">Aujourd'hui</span>
                            </div>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" style="color:#059669;">{{ item.presence.today_present }}</span>
                              <span class="drill-stat-lbl">Présents</span>
                            </div>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" style="color:#d97706;">{{ item.presence.monthly_rate }}%</span>
                              <span class="drill-stat-lbl">Moy. mois</span>
                            </div>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" style="color:#64748b;">{{ item.effectifs.actifs }}</span>
                              <span class="drill-stat-lbl">Actifs</span>
                            </div>
                          </template>
                          <template v-else>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" :style="{ color: drilldownColor }">{{ item.pta?.avg || 0 }}%</span>
                              <span class="drill-stat-lbl">Avancement</span>
                            </div>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" style="color:#059669;">{{ item.pta?.terminee || 0 }}</span>
                              <span class="drill-stat-lbl">Terminées</span>
                            </div>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" style="color:#d97706;">{{ (item.pta?.total || 0) - (item.pta?.terminee || 0) }}</span>
                              <span class="drill-stat-lbl">En cours</span>
                            </div>
                            <div class="drill-item-stat">
                              <span class="drill-stat-val" style="color:#64748b;">{{ item.pta?.total || 0 }}</span>
                              <span class="drill-stat-lbl">Total</span>
                            </div>
                          </template>
                        </div>
                        <div class="drill-item-bar">
                          <div class="drill-item-bar-fill" :style="{
                            width: (drilldownSection === 'pta' ? (item.pta?.avg || 0) : drilldownSection === 'presence' ? item.presence.today_rate : (item.effectifs.total > 0 ? Math.round(item.effectifs.actifs / item.effectifs.total * 100) : 0)) + '%',
                            background: drilldownColor
                          }"></div>
                        </div>
                      </div>
                    </div>

                    <div v-if="drilldownOrgane.items.length === 0" class="drill-empty">
                      <i class="fas fa-inbox"></i>
                      <p>Aucune donnée disponible</p>
                    </div>

                    <template v-if="drilldownSection === 'pta' && drilldownOrgane.activites?.length">
                      <div class="drill-prov-section-title" style="margin-top:16px;">
                        <i class="fas fa-clipboard-list"></i> Activités PTA {{ new Date().getFullYear() }} ({{ drilldownOrgane.activites.length }})
                      </div>
                      <div class="drill-prov-activites">
                        <div v-for="act in drilldownOrgane.activites" :key="act.id" class="drill-prov-activite">
                          <div class="drill-activite-head">
                            <div class="drill-activite-pct" :style="{ color: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#94a3b8' }">{{ act.pourcentage }}%</div>
                            <div class="drill-activite-info">
                              <div class="drill-activite-titre">{{ act.titre }}</div>
                              <div class="drill-activite-meta">
                                <span v-if="act.categorie" class="drill-activite-cat">{{ act.categorie }}</span>
                                <span v-if="act.departement"><i class="fas fa-building" style="font-size:0.7em;"></i> {{ act.departement }}</span>
                                <span v-if="act.trimestre">{{ act.trimestre }}</span>
                              </div>
                            </div>
                          </div>
                          <div class="drill-item-bar" style="margin-top:4px;">
                            <div class="drill-item-bar-fill" :style="{ width: act.pourcentage + '%', background: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#e2e8f0' }"></div>
                          </div>
                        </div>
                      </div>
                    </template>
                  </template>

                  <!-- ── LEVEL 2 : PROVINCE DETAIL ── -->
                  <template v-else-if="drilldownLevel === 'province' && drilldownProvince">
                    <div class="drill-prov-info">
                      <div class="drill-prov-info-item" v-if="drilldownProvince.province.nom_secretariat_executif">
                        <i class="fas fa-user-tie"></i>
                        <span>SE : {{ drilldownProvince.province.nom_secretariat_executif }}</span>
                      </div>
                      <div class="drill-prov-info-item" v-if="drilldownProvince.province.nom_gouverneur">
                        <i class="fas fa-landmark"></i>
                        <span>Gouverneur : {{ drilldownProvince.province.nom_gouverneur }}</span>
                      </div>
                      <div class="drill-prov-info-item" v-if="drilldownProvince.province.telephone">
                        <i class="fas fa-phone"></i>
                        <span>{{ drilldownProvince.province.telephone }}</span>
                      </div>
                      <div class="drill-prov-info-item" v-if="drilldownProvince.province.email">
                        <i class="fas fa-envelope"></i>
                        <span>{{ drilldownProvince.province.email }}</span>
                      </div>
                    </div>

                    <div class="drill-prov-stats">
                      <template v-if="drilldownSection === 'effectifs'">
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#e0f2fe;color:#0077B5;"><i class="fas fa-users"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.effectifs.total }}</div>
                          <div class="drill-prov-stat-lbl">Agents</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-user-check"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.effectifs.actifs }}</div>
                          <div class="drill-prov-stat-lbl">Actifs</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-user-slash"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.effectifs.suspendus }}</div>
                          <div class="drill-prov-stat-lbl">Suspendus</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#f1f5f9;color:#64748b;"><i class="fas fa-user-clock"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.effectifs.anciens }}</div>
                          <div class="drill-prov-stat-lbl">Anciens</div>
                        </div>
                      </template>
                      <template v-else-if="drilldownSection === 'presence'">
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-user-check"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.presence.today_present }}</div>
                          <div class="drill-prov-stat-lbl">Présents</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-user-times"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.presence.total_active - drilldownProvince.presence.today_present }}</div>
                          <div class="drill-prov-stat-lbl">Absents</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-chart-line"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.presence.today_rate }}%</div>
                          <div class="drill-prov-stat-lbl">Taux jour</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#e0f2fe;color:#0077B5;"><i class="fas fa-calendar-check"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.presence.monthly_rate }}%</div>
                          <div class="drill-prov-stat-lbl">Moy. mois</div>
                        </div>
                      </template>
                      <template v-else>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#e0f2fe;color:#0077B5;"><i class="fas fa-tasks"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.pta.total }}</div>
                          <div class="drill-prov-stat-lbl">Activités</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-check-circle"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.pta.terminee }}</div>
                          <div class="drill-prov-stat-lbl">Terminées</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-spinner"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.pta.en_cours }}</div>
                          <div class="drill-prov-stat-lbl">En cours</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-percentage"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.pta.avg }}%</div>
                          <div class="drill-prov-stat-lbl">Avancement</div>
                        </div>
                      </template>
                    </div>

                    <template v-if="drilldownSection === 'effectifs'">
                      <div class="drill-prov-section-title"><i class="fas fa-sitemap"></i> Répartition par organe</div>
                      <div class="drill-prov-organe-row">
                        <div class="drill-prov-organe-chip" style="border-color:#0077B5;">
                          <span class="drill-prov-organe-code" style="color:#0077B5;">SEN</span>
                          <span>{{ drilldownProvince.by_organe.sen ?? 0 }}</span>
                        </div>
                        <div class="drill-prov-organe-chip" style="border-color:#0ea5e9;">
                          <span class="drill-prov-organe-code" style="color:#0ea5e9;">SEP</span>
                          <span>{{ drilldownProvince.by_organe.sep ?? 0 }}</span>
                        </div>
                        <div class="drill-prov-organe-chip" style="border-color:#0d9488;">
                          <span class="drill-prov-organe-code" style="color:#0d9488;">SEL</span>
                          <span>{{ drilldownProvince.by_organe.sel ?? 0 }}</span>
                        </div>
                      </div>

                      <div v-if="drilldownProvince.departments.length" class="drill-prov-section-title"><i class="fas fa-building"></i> Départements</div>
                      <div class="drill-prov-dept-grid">
                        <div v-for="dept in drilldownProvince.departments" :key="dept.id" class="drill-prov-dept drill-item-clickable" @click="openDepartmentDrilldown(dept.id)">
                          <div class="drill-prov-dept-name">{{ dept.nom }} <i class="fas fa-chevron-right" style="font-size:0.7em;opacity:0.5;margin-left:4px;"></i></div>
                          <div class="drill-prov-dept-count">{{ dept.actifs }} <small>actifs</small> / {{ dept.total }}</div>
                        </div>
                      </div>

                      <div v-if="drilldownProvince.agents.length" class="drill-prov-section-title"><i class="fas fa-user"></i> Agents ({{ drilldownProvince.agents.length }})</div>
                      <div class="drill-prov-agents-table">
                        <div v-for="a in drilldownProvince.agents" :key="a.id" class="drill-prov-agent-row drill-agent-clickable" @click="selectedAgent = selectedAgent?.id === a.id ? null : a">
                          <div class="drill-prov-agent-avatar" :style="{ background: a.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: a.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
                            <i :class="a.sexe === 'F' ? 'fas fa-female' : 'fas fa-male'"></i>
                          </div>
                          <div class="drill-prov-agent-info">
                            <div class="drill-prov-agent-name">{{ a.nom }}</div>
                            <div class="drill-prov-agent-fn">{{ a.fonction }}</div>
                          </div>
                          <i class="fas fa-address-card drill-agent-contact-icon"></i>
                        </div>
                      </div>
                    </template>

                    <template v-else>
                      <div class="drill-prov-section-title"><i class="fas fa-clipboard-list"></i> Activités PTA {{ new Date().getFullYear() }} ({{ drilldownProvince.activites?.length || 0 }})</div>
                      <div v-if="drilldownProvince.activites?.length" class="drill-prov-activites">
                        <div v-for="act in drilldownProvince.activites" :key="act.id" class="drill-prov-activite">
                          <div class="drill-activite-head">
                            <div class="drill-activite-pct" :style="{ color: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#94a3b8' }">{{ act.pourcentage }}%</div>
                            <div class="drill-activite-info">
                              <div class="drill-activite-titre">{{ act.titre }}</div>
                              <div class="drill-activite-meta">
                                <span v-if="act.categorie" class="drill-activite-tag">{{ act.categorie }}</span>
                                <span v-if="act.trimestre" class="drill-activite-tag">{{ act.trimestre }}</span>
                              </div>
                            </div>
                          </div>
                          <div class="drill-activite-bar">
                            <div class="drill-activite-bar-fill" :style="{ width: act.pourcentage + '%', background: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#e2e8f0' }"></div>
                          </div>
                        </div>
                      </div>
                      <div v-else class="drill-empty">
                        <i class="fas fa-clipboard"></i>
                        <p>Aucune activité PTA pour cette province</p>
                      </div>
                    </template>
                  </template>

                  <!-- ── LEVEL 3 : DÉPARTEMENT DETAIL ── -->
                  <template v-else-if="drilldownLevel === 'department' && drilldownDepartment">
                    <template v-if="drilldownSection === 'effectifs'">
                      <div class="drill-dept-grid">
                        <div class="drill-prov-stat-card" style="border-color:#0077B5;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.effectifs?.total ?? 0 }}</div>
                          <div class="drill-prov-stat-lbl">Agents</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#059669;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.effectifs?.actifs ?? 0 }}</div>
                          <div class="drill-prov-stat-lbl">Actifs</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#d97706;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.effectifs?.suspendus ?? 0 }}</div>
                          <div class="drill-prov-stat-lbl">Suspendus</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#64748b;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.effectifs?.anciens ?? 0 }}</div>
                          <div class="drill-prov-stat-lbl">Anciens</div>
                        </div>
                      </div>
                      <div v-if="drilldownDepartment.agents?.length" class="drill-prov-section-title" style="margin-top:16px;">
                        <i class="fas fa-user"></i> Agents ({{ drilldownDepartment.agents.length }})
                      </div>
                      <div v-if="drilldownDepartment.agents?.length" class="drill-prov-agents-table">
                        <div v-for="a in drilldownDepartment.agents" :key="a.id" class="drill-prov-agent-row drill-agent-clickable" @click="selectedAgent = selectedAgent?.id === a.id ? null : a">
                          <div class="drill-prov-agent-avatar" :style="{ background: a.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: a.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
                            <i :class="a.sexe === 'F' ? 'fas fa-female' : 'fas fa-male'"></i>
                          </div>
                          <div class="drill-prov-agent-info">
                            <div class="drill-prov-agent-name">{{ a.nom }}</div>
                            <div class="drill-prov-agent-fn">{{ a.fonction }}</div>
                          </div>
                          <i class="fas fa-address-card drill-agent-contact-icon"></i>
                        </div>
                      </div>
                      <div v-else class="drill-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Aucun agent dans ce département</p>
                      </div>
                    </template>

                    <template v-else-if="drilldownSection === 'presence'">
                      <div class="drill-dept-grid">
                        <div class="drill-prov-stat-card" style="border-color:#059669;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.presence?.today_present ?? 0 }}</div>
                          <div class="drill-prov-stat-lbl">Présents auj.</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#d97706;">
                          <div class="drill-prov-stat-val">{{ (drilldownDepartment.presence?.total_active ?? 0) - (drilldownDepartment.presence?.today_present ?? 0) }}</div>
                          <div class="drill-prov-stat-lbl">Absents</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#0077B5;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.presence?.today_rate ?? 0 }}%</div>
                          <div class="drill-prov-stat-lbl">Taux auj.</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#7c3aed;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.presence?.monthly_rate ?? 0 }}%</div>
                          <div class="drill-prov-stat-lbl">Moy. mensuelle</div>
                        </div>
                      </div>
                      <div v-if="drilldownDepartment.agents?.length" class="drill-prov-section-title" style="margin-top:16px;">
                        <i class="fas fa-user"></i> Agents actifs ({{ drilldownDepartment.agents.length }})
                      </div>
                      <div v-if="drilldownDepartment.agents?.length" class="drill-prov-agents-table">
                        <div v-for="a in drilldownDepartment.agents" :key="a.id" class="drill-prov-agent-row drill-agent-clickable" @click="selectedAgent = selectedAgent?.id === a.id ? null : a">
                          <div class="drill-prov-agent-avatar" :style="{ background: a.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: a.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
                            <i :class="a.sexe === 'F' ? 'fas fa-female' : 'fas fa-male'"></i>
                          </div>
                          <div class="drill-prov-agent-info">
                            <div class="drill-prov-agent-name">{{ a.nom }}</div>
                            <div class="drill-prov-agent-fn">{{ a.fonction }}</div>
                          </div>
                          <i class="fas fa-address-card drill-agent-contact-icon"></i>
                        </div>
                      </div>
                      <div v-else class="drill-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Aucun agent dans ce département</p>
                      </div>
                    </template>

                    <template v-else>
                      <div class="drill-dept-grid">
                        <div class="drill-prov-stat-card" style="border-color:#0077B5;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.pta?.total ?? 0 }}</div>
                          <div class="drill-prov-stat-lbl">Activités PTA</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#059669;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.pta?.terminee ?? 0 }}</div>
                          <div class="drill-prov-stat-lbl">Terminées</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#d97706;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.pta?.en_cours ?? 0 }}</div>
                          <div class="drill-prov-stat-lbl">En cours</div>
                        </div>
                        <div class="drill-prov-stat-card" style="border-color:#7c3aed;">
                          <div class="drill-prov-stat-val">{{ drilldownDepartment.pta?.avg ?? 0 }}%</div>
                          <div class="drill-prov-stat-lbl">Progression moy.</div>
                        </div>
                      </div>
                      <template v-if="drilldownDepartment.activites?.length">
                        <div class="drill-prov-section-title" style="margin-top:16px;">
                          <i class="fas fa-clipboard-list"></i> Activités PTA {{ new Date().getFullYear() }} ({{ drilldownDepartment.activites.length }})
                        </div>
                        <div class="drill-prov-activites">
                          <div v-for="act in drilldownDepartment.activites" :key="act.id" class="drill-prov-activite">
                            <div class="drill-activite-head">
                              <div class="drill-activite-pct" :style="{ color: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#94a3b8' }">{{ act.pourcentage }}%</div>
                              <div class="drill-activite-info">
                                <div class="drill-activite-titre">{{ act.titre }}</div>
                                <div class="drill-activite-meta">
                                  <span v-if="act.categorie" class="drill-activite-cat">{{ act.categorie }}</span>
                                  <span v-if="act.trimestre">{{ act.trimestre }}</span>
                                </div>
                              </div>
                            </div>
                            <div class="drill-item-bar" style="margin-top:4px;">
                              <div class="drill-item-bar-fill" :style="{ width: Math.min(act.pourcentage, 100) + '%', background: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#e2e8f0' }"></div>
                            </div>
                          </div>
                        </div>
                      </template>
                      <div v-else class="drill-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Aucune activité PTA dans ce département</p>
                      </div>
                    </template>
                  </template>
                </div>
              </div>
            </Transition>
          </div>
        </Transition>

        <!-- Contact popup overlay -->
        <Transition name="drill-contact">
          <div v-if="selectedAgent" class="drill-contact-overlay" @click.self="selectedAgent = null">
            <div class="drill-agent-contact-card">
              <div class="drill-contact-header">
                <div class="drill-contact-avatar" :style="{ background: selectedAgent.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: selectedAgent.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
                  <i :class="selectedAgent.sexe === 'F' ? 'fas fa-female' : 'fas fa-male'" style="font-size:1.4rem;"></i>
                </div>
                <div>
                  <div class="drill-contact-name">{{ selectedAgent.nom }}</div>
                  <div class="drill-contact-fn">{{ selectedAgent.fonction }}</div>
                </div>
                <button class="drill-contact-close" @click.stop="selectedAgent = null"><i class="fas fa-times"></i></button>
              </div>
              <div class="drill-contact-body">
                <div v-if="selectedAgent.matricule" class="drill-contact-item"><i class="fas fa-id-badge"></i><span>{{ selectedAgent.matricule }}</span></div>
                <div v-if="selectedAgent.grade" class="drill-contact-item"><i class="fas fa-medal"></i><span>{{ selectedAgent.grade }}</span></div>
                <a v-if="selectedAgent.email" :href="'mailto:' + selectedAgent.email" class="drill-contact-item drill-contact-link" @click.stop><i class="fas fa-envelope"></i><span>{{ selectedAgent.email }}</span></a>
                <a v-if="selectedAgent.telephone" :href="'tel:' + selectedAgent.telephone" class="drill-contact-item drill-contact-link" @click.stop><i class="fas fa-phone"></i><span>{{ selectedAgent.telephone }}</span></a>
                <div v-if="!selectedAgent.email && !selectedAgent.telephone && !selectedAgent.matricule" class="drill-contact-empty">Aucune info de contact</div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()

const auth = useAuthStore()
const loading = ref(true)
const d = ref({})

const today = computed(() => new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
}))

const quickActions = [
  { to: '/rh/agents', label: 'Gestion agents', desc: 'Consulter et gerer les agents', icon: 'fa-users', color: '#0077B5', bg: '#e0f2fe' },
  { to: '/rh/agents/create', label: 'Nouvel agent', desc: 'Creer une fiche agent', icon: 'fa-user-plus', color: '#059669', bg: '#d1fae5' },
  { to: '/rh/holidays/planning', label: 'Gestion des congés', desc: 'Planning et statuts des agents', icon: 'fa-calendar-alt', color: '#10b981', bg: '#ecfdf5' },
  { to: '/rh/pointages/daily', label: 'Pointages du jour', desc: 'Saisie des presences', icon: 'fa-clock', color: '#7c3aed', bg: '#ede9fe' },
  { to: '/requests', label: 'Demandes', desc: 'Gerer les demandes', icon: 'fa-paper-plane', color: '#d97706', bg: '#fef3c7' },
  { to: '/signalements', label: 'Signalements', desc: 'Consulter les alertes', icon: 'fa-flag', color: '#dc2626', bg: '#fee2e2' },
  { to: '/rh/communiques/create', label: 'Communique', desc: 'Publier un communique', icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe' },
]

const maxMetric = computed(() => {
  const vals = [
    d.value.agents?.total ?? 0,
    d.value.agents?.actifs ?? 0,
    d.value.requests?.total ?? 0,
    d.value.documents?.total ?? 0,
    d.value.signalements?.total ?? 0,
  ]
  return Math.max(...vals, 1)
})

function pct(val) {
  return Math.min(((val ?? 0) / maxMetric.value) * 100, 100)
}

const metrics = computed(() => [
  { label: 'Agents total', value: d.value.agents?.total ?? 0, icon: 'fa-users', color: '#0077B5', bg: '#e0f2fe', pct: pct(d.value.agents?.total), alert: false, to: '/rh/agents', drill: () => openOrganeDrilldown('SEN', 'effectifs') },
  { label: 'Agents actifs', value: d.value.agents?.actifs ?? 0, icon: 'fa-user-check', color: '#059669', bg: '#d1fae5', pct: pct(d.value.agents?.actifs), alert: false, to: '/rh/agents', drill: () => openOrganeDrilldown('SEN', 'effectifs') },
  { label: 'Suspendus', value: d.value.agents?.suspendus ?? 0, icon: 'fa-user-slash', color: '#d97706', bg: '#fef3c7', pct: pct(d.value.agents?.suspendus), alert: (d.value.agents?.suspendus ?? 0) > 0, to: '/rh/agents', drill: () => openOrganeDrilldown('SEN', 'effectifs') },
  { label: 'Nouveaux ce mois', value: d.value.agents?.new_this_month ?? 0, icon: 'fa-user-plus', color: '#8b5cf6', bg: '#ede9fe', pct: pct(d.value.agents?.new_this_month), alert: false, to: '/rh/agents' },
  { label: 'Demandes en attente', value: d.value.requests?.en_attente ?? 0, icon: 'fa-hourglass-half', color: '#ea580c', bg: '#fff7ed', pct: pct(d.value.requests?.en_attente), alert: (d.value.requests?.en_attente ?? 0) > 5, to: '/requests' },
  { label: 'Demandes approuvees', value: d.value.requests?.approuve ?? 0, icon: 'fa-check-double', color: '#16a34a', bg: '#dcfce7', pct: pct(d.value.requests?.approuve), alert: false, to: '/requests' },
  { label: 'Signalements ouverts', value: d.value.signalements?.ouvert ?? 0, icon: 'fa-exclamation-circle', color: '#dc2626', bg: '#fee2e2', pct: pct(d.value.signalements?.ouvert), alert: (d.value.signalements?.haute_severite ?? 0) > 0, to: '/signalements' },
  { label: 'Documents', value: d.value.documents?.total ?? 0, icon: 'fa-folder-open', color: '#6366f1', bg: '#e0e7ff', pct: pct(d.value.documents?.total), alert: (d.value.documents?.expires ?? 0) > 0, to: '/documents' },
])

const organeCards = computed(() => {
  const bo = d.value.agents?.by_organe || {}
  return [
    { code: 'SEN', nom: 'Secrétariat Exécutif National', total: bo.sen?.total ?? 0, actifs: bo.sen?.actifs ?? 0, color: '#0077B5' },
    { code: 'SEP', nom: 'Secrétariat Exécutif Provincial', total: bo.sep?.total ?? 0, actifs: bo.sep?.actifs ?? 0, color: '#0ea5e9' },
    { code: 'SEL', nom: 'Secrétariat Exécutif Local', total: bo.sel?.total ?? 0, actifs: bo.sel?.actifs ?? 0, color: '#0d9488' },
  ]
})

function orgPct(count) {
  const total = d.value.agents?.total || 1
  return Math.round((count / total) * 100)
}

function sexePct(sexe) {
  const by = d.value.agents?.by_sexe || {}
  const m = by.M ?? by.Masculin ?? 0
  const f = by.F ?? by.Feminin ?? 0
  const total = m + f || 1
  const val = sexe === 'M' ? m : f
  return Math.round((val / total) * 100)
}

const presenceColor = computed(() => {
  const rate = d.value.attendance?.today_rate ?? 0
  if (rate >= 80) return 'text-green'
  if (rate >= 50) return 'text-amber'
  return 'text-red'
})

const weeklyData = computed(() => {
  const weekly = d.value.attendance?.weekly || []
  const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']
  return weekly.map(w => ({
    ...w,
    label: dayNames[new Date(w.date).getDay()],
  }))
})

function barColor(rate) {
  if (rate >= 80) return 'bar-green'
  if (rate >= 50) return 'bar-amber'
  return 'bar-red'
}

function typePct(count, total) {
  return Math.min(Math.round(((count ?? 0) / (total || 1)) * 100), 100)
}

function statutLabel(s) {
  const map = { en_attente: 'En attente', approuve: 'Approuve', rejete: 'Rejete', annule: 'Annule' }
  return map[s] || s
}

function formatTime(iso) {
  if (!iso) return '-'
  const dd = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - dd) / 60000)
  if (diff < 1) return "A l'instant"
  if (diff < 60) return `Il y a ${diff} min`
  if (diff < 1440) return `Il y a ${Math.floor(diff / 60)}h`
  return dd.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const loadError = ref(null)

// Agent statuses
const expandedStatus = ref(null)

const statusCards = [
  { key: 'en_conge', label: 'En congé', icon: 'fa-umbrella-beach', color: '#0ea5e9', bg: '#e0f2fe' },
  { key: 'en_mission', label: 'En mission', icon: 'fa-plane-departure', color: '#8b5cf6', bg: '#ede9fe' },
  { key: 'en_formation', label: 'En formation', icon: 'fa-graduation-cap', color: '#f59e0b', bg: '#fef3c7' },
  { key: 'suspendu', label: 'Suspendu', icon: 'fa-user-slash', color: '#ef4444', bg: '#fee2e2' },
]

function toggleStatus(key) {
  expandedStatus.value = expandedStatus.value === key ? null : key
}

function agentStatusCount(key) {
  return d.value.agent_statuses?.counts?.[key] ?? 0
}

function agentStatusList(key) {
  return d.value.agent_statuses?.details?.[key] ?? []
}

function agentInitials(agent) {
  return ((agent.prenom || '').charAt(0) + (agent.nom || '').charAt(0)).toUpperCase()
}

function formatShortDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

// ═══════════ DRILL-DOWN STATE ═══════════
const drilldownOpen = ref(false)
const drilldownLoading = ref(false)
const drilldownOrgane = ref(null)
const drilldownProvince = ref(null)
const drilldownDepartment = ref(null)
const drilldownLevel = ref('organe')
const drilldownSection = ref('effectifs')
const selectedAgent = ref(null)

const drilldownColor = computed(() => {
  const code = drilldownOrgane.value?.organe || ''
  if (code === 'SEP') return '#0ea5e9'
  if (code === 'SEL') return '#0d9488'
  return '#0077B5'
})

async function openOrganeDrilldown(code, section = 'effectifs') {
  drilldownOpen.value = true
  drilldownLoading.value = true
  drilldownLevel.value = 'organe'
  drilldownSection.value = section
  drilldownOrgane.value = null
  drilldownProvince.value = null
  drilldownDepartment.value = null
  try {
    const { data: result } = await client.get(`/dashboard/executive/organe/${code}`)
    drilldownOrgane.value = result.data ?? result
  } catch (e) {
    console.error('Drill-down organe error:', e)
  } finally {
    drilldownLoading.value = false
  }
}

async function openProvinceDrilldown(id) {
  drilldownLoading.value = true
  drilldownLevel.value = 'province'
  drilldownProvince.value = null
  selectedAgent.value = null
  selectedAgent.value = null
  try {
    const { data: result } = await client.get(`/dashboard/executive/province/${id}`)
    drilldownProvince.value = result.data ?? result
  } catch (e) {
    console.error('Drill-down province error:', e)
  } finally {
    drilldownLoading.value = false
  }
}

async function openDepartmentDrilldown(id) {
  drilldownLoading.value = true
  drilldownLevel.value = 'department'
  drilldownDepartment.value = null
  selectedAgent.value = null
  try {
    const { data: result } = await client.get(`/dashboard/executive/department/${id}`)
    drilldownDepartment.value = result.data ?? result
  } catch (e) {
    console.error('Drill-down department error:', e)
  } finally {
    drilldownLoading.value = false
  }
}

function closeDrilldown() {
  drilldownOpen.value = false
  drilldownOrgane.value = null
  drilldownProvince.value = null
  selectedAgent.value = null
  drilldownDepartment.value = null
  drilldownLevel.value = 'organe'
  selectedAgent.value = null
}

function backToOrgane() {
  selectedAgent.value = null
  drilldownLevel.value = 'organe'
  drilldownProvince.value = null
  drilldownDepartment.value = null
  selectedAgent.value = null
}

function backToPrevious() {
  if (drilldownLevel.value === 'department' && drilldownProvince.value) {
    drilldownLevel.value = 'province'
    drilldownDepartment.value = null
  } else {
    backToOrgane()
  }
}

onMounted(async () => {
  try {
    const { data: result } = await client.get('/rh/dashboard')
    d.value = result
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Impossible de charger le tableau de bord RH.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.rh-dashboard { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }

/* HERO */
.rh-hero {
  position: relative; border-radius: 20px; overflow: hidden; margin-bottom: 1.8rem;
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 30%, #0c4a6e 60%, #0077B5 100%);
  box-shadow: 0 8px 32px rgba(0, 30, 60, .25);
}
.rh-hero-bg {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 400px 300px at 85% 20%, rgba(0,119,181,.25) 0%, transparent 70%),
    radial-gradient(ellipse 200px 200px at 10% 80%, rgba(56,189,248,.1) 0%, transparent 60%);
  pointer-events: none;
}
.rh-hero-inner {
  position: relative; z-index: 1; padding: 2rem 2.5rem;
  display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;
}
.rh-hero-left { display: flex; align-items: center; gap: 1.2rem; color: #fff; }
.rh-hero-badge {
  width: 60px; height: 60px; border-radius: 16px; flex-shrink: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.15), rgba(255,255,255,.05));
  border: 1px solid rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: #38bdf8; backdrop-filter: blur(8px);
  overflow: hidden;
}
.rh-hero-photo {
  width: 100%; height: 100%; object-fit: cover;
}
.rh-hero-greeting { font-size: .82rem; opacity: .6; font-weight: 500; letter-spacing: .5px; text-transform: uppercase; }
.rh-hero-name { font-size: 1.5rem; font-weight: 800; margin: .1rem 0 .35rem; line-height: 1.15; color: #fff; }
.rh-hero-date { font-size: .72rem; opacity: .45; text-transform: capitalize; }

.rh-hero-kpis {
  display: flex; align-items: center;
  background: rgba(255,255,255,.08); border-radius: 16px; padding: .8rem 1.2rem;
  border: 1px solid rgba(255,255,255,.1); backdrop-filter: blur(8px);
}
.rh-kpi { display: flex; align-items: center; gap: .6rem; padding: 0 1rem; color: #fff; text-decoration: none; cursor: pointer; transition: opacity .2s; }
.rh-kpi:hover { opacity: .85; color: #fff; }
.rh-kpi-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.1); display: flex; align-items: center;
  justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.rh-kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.kpi-unit { font-size: .85rem; font-weight: 600; opacity: .7; }
.rh-kpi-lbl { font-size: .62rem; opacity: .5; text-transform: uppercase; letter-spacing: .4px; margin-top: .1rem; font-weight: 600; }
.kpi-divider { width: 1px; height: 36px; background: rgba(255,255,255,.12); margin: 0 .2rem; }

/* SECTIONS */
.rh-section { margin-bottom: 1.8rem; }
.rh-section-head { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.rh-section-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0;
}
.rh-section-title { font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; }
.rh-section-sub { font-size: .72rem; color: #94a3b8; margin: 0; font-weight: 500; }

/* ACTIONS */
.rh-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.rh-action {
  position: relative; display: flex; align-items: center; gap: .8rem; padding: 1rem 1.1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; overflow: hidden;
}
.rh-action-glow { position: absolute; top: 0; left: 0; width: 3px; height: 100%; opacity: 0; transition: opacity .25s; }
.rh-action:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rh-action:hover .rh-action-glow { opacity: 1; }
.rh-action-icon {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.rh-action-text { flex: 1; min-width: 0; }
.rh-action-label { font-size: .84rem; font-weight: 700; line-height: 1.2; }
.rh-action-desc { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
.rh-action-arrow { font-size: .65rem; color: #cbd5e1; transition: all .2s; }
.rh-action:hover .rh-action-arrow { color: #0077B5; transform: translateX(3px); }

/* METRICS */
.rh-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.rh-metric {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.1rem; transition: all .25s; text-decoration: none; color: inherit; cursor: pointer; display: block;
}
.rh-metric:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rh-metric-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .7rem; }
.rh-metric-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem;
}
.rh-metric-alert { color: #ef4444; font-size: .75rem; animation: pulse-alert 2s infinite; }
@keyframes pulse-alert { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
.rh-metric-val { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.rh-metric-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.rh-metric-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: .7rem; }
.rh-metric-bar-fill { height: 100%; border-radius: 4px; transition: width .8s ease; min-width: 3px; }

/* ORGANE CARDS */
.rh-organe-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.rh-organe-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s; text-decoration: none; color: inherit; cursor: pointer; display: block;
}
.rh-organe-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.rh-organe-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.rh-organe-badge {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center;
  font-size: .72rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.rh-organe-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
.rh-organe-sub { font-size: .68rem; color: #94a3b8; }
.rh-organe-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; margin-bottom: .8rem; }
.rh-organe-stat { text-align: center; padding: .5rem .25rem; background: #f8fafc; border-radius: 10px; }
.rh-organe-stat-val { font-size: 1.3rem; font-weight: 800; line-height: 1; }
.rh-organe-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }
.rh-organe-bar-bg { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.rh-organe-bar-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }
.rh-sexe-bars { margin-top: .6rem; }
.rh-sexe-bar { display: flex; height: 8px; border-radius: 8px; overflow: hidden; background: #f1f5f9; }
.rh-sexe-fill { height: 100%; transition: width .8s ease; }

/* PRESENCE */
.rh-presence-row { display: grid; grid-template-columns: 280px 1fr; gap: .75rem; }
.rh-presence-global {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.5rem; text-align: center;
}
.rh-presence-big { font-size: 3rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.text-green { color: #059669; }
.text-amber { color: #d97706; }
.text-red { color: #dc2626; }
.rh-presence-sub-text { font-size: .75rem; color: #94a3b8; margin-bottom: 1rem; }
.rh-presence-detail { text-align: left; }
.rh-presence-item { display: flex; justify-content: space-between; font-size: .78rem; color: #475569; padding: .3rem 0; border-bottom: 1px solid #f1f5f9; }

.rh-presence-chart {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem 1.5rem;
}
.rh-chart-title { font-size: .78rem; font-weight: 700; color: #64748b; margin-bottom: .8rem; }
.rh-bars { display: flex; align-items: flex-end; gap: .5rem; height: 140px; }
.rh-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; }
.rh-bar-value { font-size: .62rem; font-weight: 700; color: #64748b; margin-bottom: .3rem; }
.rh-bar-wrap { flex: 1; width: 100%; max-width: 40px; background: #f1f5f9; border-radius: 6px 6px 0 0; overflow: hidden; display: flex; align-items: flex-end; }
.rh-bar-fill { width: 100%; border-radius: 6px 6px 0 0; transition: height .8s ease; min-height: 3px; }
.bar-green { background: linear-gradient(0deg, #059669, #34d399); }
.bar-amber { background: linear-gradient(0deg, #d97706, #fbbf24); }
.bar-red { background: linear-gradient(0deg, #dc2626, #f87171); }
.rh-bar-label { font-size: .62rem; color: #94a3b8; font-weight: 600; margin-top: .3rem; }

/* TWO COLUMNS */
.rh-two-cols { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.rh-col-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; display: flex; flex-direction: column;
}
.rh-col-head {
  display: flex; align-items: center; gap: .6rem; padding: .9rem 1rem;
  border-bottom: 2px solid; background: #fafbfc;
}
.rh-col-head-icon {
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
}
.rh-col-head-title { font-size: .82rem; font-weight: 700; color: #1e293b; }
.rh-col-head-count { font-size: .65rem; color: #94a3b8; }
.rh-col-body { padding: 1rem; flex: 1; }
.rh-type-row { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; }
.rh-type-name { font-size: .75rem; color: #475569; min-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rh-type-bar-wrap { flex: 1; height: 8px; background: #f1f5f9; border-radius: 8px; overflow: hidden; }
.rh-type-bar { height: 100%; border-radius: 8px; transition: width .8s ease; min-width: 3px; }
.rh-type-count { font-size: .78rem; font-weight: 700; color: #1e293b; min-width: 28px; text-align: right; }
.rh-doc-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-bottom: 1rem; }
.rh-doc-stat { text-align: center; padding: .6rem .25rem; background: #f8fafc; border-radius: 10px; }
.rh-doc-stat-val { font-size: 1.3rem; font-weight: 800; line-height: 1; }
.rh-doc-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: .1rem; }
.rh-empty { text-align: center; padding: 1.5rem; color: #cbd5e1; font-size: .82rem; }

/* RECENT ACTIVITY */
.rh-recent-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.rh-recent-col {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; display: flex; flex-direction: column;
}
.rh-recent-head {
  display: flex; align-items: center; gap: .6rem; padding: .9rem 1rem;
  border-bottom: 2px solid; background: #fafbfc;
}
.rh-recent-head-icon {
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
}
.rh-recent-head-title { font-size: .82rem; font-weight: 700; color: #1e293b; }
.rh-recent-head-count { font-size: .65rem; color: #94a3b8; }
.rh-alert-badge {
  margin-left: auto; font-size: .62rem; font-weight: 700; color: #fff;
  background: #ef4444; padding: .15rem .5rem; border-radius: 6px; white-space: nowrap;
}
.rh-recent-body { flex: 1; max-height: 300px; overflow-y: auto; }
.rh-recent-item {
  display: flex; align-items: flex-start; gap: .6rem; padding: .65rem 1rem;
  border-bottom: 1px solid #f8fafc; text-decoration: none; color: inherit;
  transition: background .15s;
}
.rh-recent-item:hover { background: #f0f9ff; }
.rh-recent-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: .35rem; }
.rh-recent-info { flex: 1; min-width: 0; }
.rh-recent-title { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.rh-recent-subtitle { font-size: .7rem; color: #64748b; }
.rh-recent-time { font-size: .65rem; color: #94a3b8; margin-top: .15rem; }
.rh-recent-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: .4rem; padding: 2rem 1rem; color: #cbd5e1; font-size: .82rem;
}
.rh-recent-empty i { font-size: 1.3rem; }

/* Status badges */
.statut-bg-en_attente { background: #fbbf24; }
.statut-bg-approuve { background: #22c55e; }
.statut-bg-rejete { background: #ef4444; }
.statut-bg-annule { background: #94a3b8; }
.rh-statut-badge {
  display: inline-block; font-size: .58rem; font-weight: 700; padding: .1rem .4rem;
  border-radius: 4px; margin-left: .3rem; text-transform: uppercase; letter-spacing: .3px;
}
.statut-en_attente { background: #fef3c7; color: #d97706; }
.statut-approuve { background: #dcfce7; color: #16a34a; }
.statut-rejete { background: #fee2e2; color: #dc2626; }
.statut-annule { background: #f1f5f9; color: #64748b; }

/* Severity badges */
.sev-bg-basse { background: #22c55e; }
.sev-bg-moyenne { background: #f59e0b; }
.sev-bg-haute { background: #ef4444; }
.rh-sev-badge {
  display: inline-block; font-size: .58rem; font-weight: 700; padding: .1rem .4rem;
  border-radius: 4px; margin-left: .3rem; text-transform: uppercase; letter-spacing: .3px;
}
.sev-basse { background: #dcfce7; color: #16a34a; }
.sev-moyenne { background: #fef3c7; color: #d97706; }
.sev-haute { background: #fee2e2; color: #dc2626; }

/* AGENT STATUS CARDS */
.as-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.as-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; transition: all .25s;
}
.as-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.06); }
.as-card-open { box-shadow: 0 8px 32px rgba(0,0,0,.1); }
.as-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.1rem; cursor: pointer; transition: background .15s;
}
.as-card-header:hover { background: #fafbfc; }
.as-card-left { display: flex; align-items: center; gap: .75rem; }
.as-card-badge {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.as-card-label { font-size: .78rem; font-weight: 600; color: #64748b; }
.as-card-count { font-size: 1.5rem; font-weight: 800; line-height: 1.1; }
.as-card-count-unit { font-size: .68rem; font-weight: 600; opacity: .6; }
.as-card-toggle {
  width: 28px; height: 28px; border-radius: 8px; background: #f1f5f9;
  display: flex; align-items: center; justify-content: center;
  font-size: .65rem; color: #94a3b8; transition: all .2s;
}
.as-card-toggle.open { transform: rotate(180deg); background: #e2e8f0; }

.as-card-body {
  border-top: 1px solid #f1f5f9; max-height: 320px; overflow-y: auto;
  animation: asSlideDown .2s ease;
}
@keyframes asSlideDown { from { opacity: 0; max-height: 0; } to { opacity: 1; max-height: 320px; } }

.as-agent-list { padding: .5rem; }
.as-agent-row {
  display: flex; align-items: flex-start; gap: .65rem;
  padding: .65rem .6rem; border-radius: 10px; transition: background .15s;
}
.as-agent-row:hover { background: #f8fafc; }
.as-agent-avatar {
  width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: .65rem; font-weight: 700;
}
.as-agent-info { flex: 1; min-width: 0; }
.as-agent-name { font-size: .82rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.as-agent-meta { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .25rem; }
.as-agent-tag {
  display: inline-flex; align-items: center; font-size: .65rem; font-weight: 600;
  padding: .1rem .45rem; border-radius: 5px; background: #f1f5f9; color: #64748b;
}
.as-agent-dates {
  font-size: .68rem; color: #94a3b8; margin-top: .25rem;
  display: flex; align-items: center; gap: .15rem;
}
.as-no-end {
  font-size: .6rem; font-weight: 600; background: #fef3c7; color: #d97706;
  padding: .05rem .35rem; border-radius: 4px; margin-left: .25rem;
}
.as-agent-motif {
  font-size: .68rem; color: #94a3b8; margin-top: .2rem; font-style: italic;
  display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;
}
.as-empty {
  display: flex; flex-direction: column; align-items: center; gap: .4rem;
  padding: 1.5rem; color: #cbd5e1; font-size: .82rem;
}
.as-empty i { font-size: 1.2rem; color: #d1fae5; }

/* RESPONSIVE */
@media (max-width: 1100px) {
  .rh-hero-inner { flex-direction: column; align-items: flex-start; }
  .rh-hero-kpis { width: 100%; justify-content: space-between; }
}
@media (max-width: 991.98px) {
  .rh-metrics { grid-template-columns: repeat(2, 1fr); }
  .rh-actions { grid-template-columns: repeat(2, 1fr); }
  .rh-organe-grid { grid-template-columns: repeat(2, 1fr); }
  .rh-presence-row { grid-template-columns: 1fr; }
  .rh-two-cols { grid-template-columns: 1fr; }
  .rh-recent-grid { grid-template-columns: 1fr; }
  .as-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 767.98px) {
  .rh-dashboard { padding: 0 .5rem 1.5rem; }
  .rh-hero { border-radius: 16px; margin-bottom: 1.2rem; }
  .rh-hero-inner { padding: 1.3rem 1.2rem; gap: 1rem; }
  .rh-hero-badge { width: 48px; height: 48px; font-size: 1.2rem; }
  .rh-hero-name { font-size: 1.2rem; }
  .rh-hero-kpis { padding: .6rem .8rem; flex-wrap: wrap; gap: .5rem; }
  .rh-kpi { padding: 0 .6rem; }
  .rh-kpi-val { font-size: 1.15rem; }
  .kpi-divider { display: none; }
  .rh-actions { grid-template-columns: 1fr 1fr; gap: .5rem; }
  .rh-metrics { gap: .5rem; }
  .rh-metric { padding: .85rem; }
  .rh-metric-val { font-size: 1.4rem; }
  .rh-organe-grid { grid-template-columns: 1fr; }
  .as-grid { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
  .rh-hero-kpis { border-radius: 12px; }
  .rh-kpi { padding: .4rem .4rem; flex: 0 0 calc(50% - .5rem); }
  .rh-kpi-icon { display: none; }
  .rh-kpi-val { font-size: 1.1rem; }
  .rh-actions { grid-template-columns: 1fr; }
  .rh-action-arrow { display: none; }
  .rh-metrics { grid-template-columns: repeat(2, 1fr); }
  .rh-bars { gap: .3rem; }
}

/* ═══════════ DRILL-DOWN OVERLAY ═══════════ */
.drill-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(15,23,42,.55); backdrop-filter: blur(6px);
  display: flex; justify-content: flex-end;
}
.drill-panel {
  width: 580px; max-width: 95vw; height: 100vh;
  background: #f8fafc; display: flex; flex-direction: column;
  box-shadow: -12px 0 48px rgba(0,0,0,.18);
  overflow: hidden;
}
.drill-header {
  padding: 1.5rem 1.8rem; color: #fff; flex-shrink: 0;
  display: flex; align-items: center; justify-content: space-between;
}
.drill-header-left { display: flex; align-items: center; gap: .8rem; min-width: 0; }
.drill-header-title { font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: .5rem; }
.drill-header-sub { font-size: .72rem; opacity: .8; margin-top: .15rem; }
.drill-back, .drill-close {
  width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,.2);
  background: rgba(255,255,255,.1); color: #fff; display: flex; align-items: center;
  justify-content: center; cursor: pointer; font-size: .85rem; transition: all .2s; flex-shrink: 0;
}
.drill-back:hover, .drill-close:hover { background: rgba(255,255,255,.2); }
.drill-body { flex: 1; overflow-y: auto; padding: 1.4rem 1.6rem; }
.drill-loading { text-align: center; padding: 3rem 1rem; }
.drill-loading p { margin-top: .8rem; color: #94a3b8; font-size: .82rem; }
.drill-summary {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; margin-bottom: 1.4rem;
}
.drill-summary-item {
  background: #fff; border-radius: 12px; padding: .8rem; text-align: center; border: 1px solid #e5e7eb;
}
.drill-summary-val { font-size: 1.5rem; font-weight: 800; color: #059669; }
.drill-summary-lbl { font-size: .62rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; }
.drill-items-grid { display: flex; flex-direction: column; gap: .6rem; }
.drill-item-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 1rem 1.2rem; transition: all .25s;
}
.drill-item-clickable { cursor: pointer; }
.drill-item-clickable:hover {
  border-color: #93c5fd; transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0,119,181,.1);
}
.drill-item-head { display: flex; align-items: center; gap: .7rem; margin-bottom: .7rem; }
.drill-item-badge {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; color: #fff; font-size: .75rem; flex-shrink: 0;
}
.drill-item-info { flex: 1; min-width: 0; }
.drill-item-name { font-size: .88rem; font-weight: 700; color: #1e293b; }
.drill-item-sub { font-size: .68rem; color: #94a3b8; }
.drill-item-arrow { color: #cbd5e1; font-size: .6rem; transition: all .2s; }
.drill-item-clickable:hover .drill-item-arrow { color: #0077B5; transform: translateX(3px); }
.drill-item-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .4rem; margin-bottom: .6rem; }
.drill-item-stat { text-align: center; }
.drill-stat-val { font-size: 1rem; font-weight: 800; display: block; }
.drill-stat-lbl { font-size: .58rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; }
.drill-item-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.drill-item-bar-fill { height: 100%; border-radius: 4px; transition: width .6s ease; min-width: 2px; }
.drill-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
.drill-empty i { font-size: 2rem; margin-bottom: .5rem; display: block; }

/* Province detail */
.drill-prov-info {
  display: flex; flex-wrap: wrap; gap: .5rem .8rem; margin-bottom: 1.2rem;
  padding: .8rem 1rem; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
}
.drill-prov-info-item { display: flex; align-items: center; gap: .4rem; font-size: .78rem; color: #475569; }
.drill-prov-info-item i { color: #94a3b8; font-size: .7rem; }
.drill-prov-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; margin-bottom: 1.2rem; }
.drill-prov-stat-card {
  background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: .8rem; text-align: center;
}
.drill-prov-stat-icon {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; margin: 0 auto .4rem; font-size: .85rem;
}
.drill-prov-stat-val { font-size: 1.4rem; font-weight: 800; line-height: 1; color: #1e293b; }
.drill-prov-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: .15rem; }
.drill-dept-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; margin-bottom: 1rem; }
.drill-prov-section-title {
  font-size: .82rem; font-weight: 700; color: #334155;
  display: flex; align-items: center; gap: .4rem;
  margin: 1.2rem 0 .6rem; padding-bottom: .3rem; border-bottom: 1px solid #e5e7eb;
}
.drill-prov-section-title i { font-size: .72rem; color: #94a3b8; }
.drill-prov-organe-row { display: flex; gap: .5rem; margin-bottom: .5rem; }
.drill-prov-organe-chip {
  display: flex; align-items: center; gap: .5rem;
  padding: .4rem .8rem; border-radius: 8px; border: 1.5px solid;
  background: #fff; font-size: .82rem; font-weight: 700;
}
.drill-prov-organe-code { font-weight: 800; font-size: .7rem; }
.drill-prov-dept-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; }
.drill-prov-dept {
  background: #fff; border-radius: 10px; border: 1px solid #e5e7eb;
  padding: .6rem .8rem; display: flex; align-items: center; justify-content: space-between;
}
.drill-prov-dept-name { font-size: .78rem; font-weight: 600; color: #334155; }
.drill-prov-dept-count { font-size: .78rem; font-weight: 700; color: #0077B5; }
.drill-prov-dept-count small { color: #94a3b8; font-weight: 500; }
.drill-prov-agents-table { display: flex; flex-direction: column; gap: .35rem; }
.drill-prov-agent-row {
  display: flex; align-items: center; gap: .6rem;
  padding: .5rem .7rem; background: #fff; border-radius: 10px; border: 1px solid #f1f5f9;
}
.drill-prov-agent-avatar {
  width: 32px; height: 32px; border-radius: 8px; display: flex;
  align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0;
}
.drill-prov-agent-info { flex: 1; min-width: 0; }
.drill-prov-agent-name { font-size: .78rem; font-weight: 600; color: #1e293b; }
.drill-prov-agent-fn { font-size: .65rem; color: #94a3b8; }

/* Agent clickable */
.drill-agent-clickable { cursor: pointer; transition: all .15s ease; position: relative; }
.drill-agent-clickable:hover { background: #f8fafc; border-color: #e2e8f0; transform: translateX(2px); }
.drill-agent-contact-icon { color: #cbd5e1; font-size: .72rem; transition: color .15s; flex-shrink: 0; }
.drill-agent-clickable:hover .drill-agent-contact-icon { color: #0077B5; }

/* Contact popup overlay */
.drill-contact-overlay {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(15, 23, 42, .45); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
}
.drill-agent-contact-card {
  background: #fff; border-radius: 16px; width: 320px; max-width: 90vw;
  box-shadow: 0 20px 60px rgba(0,0,0,.25); overflow: hidden;
}
.drill-contact-header {
  display: flex; align-items: center; gap: .7rem;
  padding: .9rem 1rem; background: linear-gradient(135deg, #f8fafc, #eef2ff);
  border-bottom: 1px solid #e2e8f0;
}
.drill-contact-avatar {
  width: 46px; height: 46px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0;
}
.drill-contact-name { font-size: .9rem; font-weight: 700; color: #1e293b; }
.drill-contact-fn { font-size: .72rem; color: #64748b; margin-top: .1rem; }
.drill-contact-close {
  margin-left: auto; background: none; border: none; color: #94a3b8;
  cursor: pointer; font-size: .85rem; padding: .3rem; border-radius: 8px; transition: all .15s;
}
.drill-contact-close:hover { color: #ef4444; background: #fef2f2; }
.drill-contact-body { padding: .8rem 1rem; display: flex; flex-direction: column; gap: .5rem; }
.drill-contact-item {
  display: flex; align-items: center; gap: .6rem;
  font-size: .8rem; color: #334155; padding: .45rem .6rem;
  background: #f8fafc; border-radius: 10px; border: 1px solid #f1f5f9;
}
.drill-contact-item i { width: 20px; text-align: center; color: #94a3b8; font-size: .75rem; }
.drill-contact-link { text-decoration: none; color: #0077B5; cursor: pointer; transition: all .15s; }
.drill-contact-link:hover { background: #e0f2fe; border-color: #bae6fd; }
.drill-contact-empty { font-size: .76rem; color: #94a3b8; text-align: center; padding: .8rem; font-style: italic; }

/* Contact popup transitions */
.drill-contact-enter-active { transition: opacity .2s ease; }
.drill-contact-leave-active { transition: opacity .15s ease; }
.drill-contact-enter-from, .drill-contact-leave-to { opacity: 0; }
.drill-contact-enter-active .drill-agent-contact-card { animation: drillContactPop .25s ease; }
.drill-contact-leave-active .drill-agent-contact-card { animation: drillContactPop .2s ease reverse; }
@keyframes drillContactPop {
  from { opacity: 0; transform: scale(.9) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Activités PTA */
.drill-prov-activites { display: flex; flex-direction: column; gap: .5rem; }
.drill-prov-activite { background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .6rem .75rem; }
.drill-activite-head { display: flex; gap: .6rem; align-items: flex-start; }
.drill-activite-pct { font-size: 1.1rem; font-weight: 700; min-width: 3rem; text-align: right; }
.drill-activite-info { flex: 1; min-width: 0; }
.drill-activite-titre { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.drill-activite-meta { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .25rem; font-size: .65rem; color: #64748b; }
.drill-activite-meta span { background: #f1f5f9; padding: .1rem .4rem; border-radius: .25rem; }
.drill-activite-cat { font-weight: 600; }
.drill-activite-tag { font-size: .6rem; background: #f1f5f9; color: #64748b; padding: .1rem .4rem; border-radius: .25rem; }
.drill-activite-bar { height: 4px; background: #f1f5f9; border-radius: 2px; margin-top: .35rem; overflow: hidden; }
.drill-activite-bar-fill { height: 100%; border-radius: 2px; transition: width .4s ease; }

/* Transitions */
.drill-fade-enter-active, .drill-fade-leave-active { transition: all .3s ease; }
.drill-fade-enter-from, .drill-fade-leave-to { opacity: 0; }
.drill-slide-enter-active { transition: all .35s cubic-bezier(.16,1,.3,1); }
.drill-slide-leave-active { transition: all .25s ease-in; }
.drill-slide-enter-from { transform: translateX(40px); opacity: 0; }
.drill-slide-leave-to { transform: translateX(-20px); opacity: 0; }

/* Responsive drill */
@media (max-width: 640px) {
  .drill-panel { width: 100vw; }
  .drill-summary { grid-template-columns: repeat(2, 1fr); }
  .drill-prov-stats { grid-template-columns: repeat(2, 1fr); }
  .drill-dept-grid { grid-template-columns: repeat(2, 1fr); }
  .drill-prov-dept-grid { grid-template-columns: 1fr; }
  .drill-item-stats { grid-template-columns: repeat(2, 1fr); }
}

/* Clickable cards */
.rh-organe-card { cursor: pointer; transition: all .25s; }
.rh-organe-card:hover {
  border-color: #93c5fd; transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,119,181,.12);
}
.rh-metric { cursor: pointer; transition: all .25s; }
.rh-metric:hover {
  border-color: #93c5fd; transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,119,181,.1);
}
</style>
