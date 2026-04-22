<template>
  <div class="dept-dashboard">

    <!-- ════════════════════ HERO ════════════════════ -->
    <div class="dept-hero">
      <div class="dept-hero-bg"></div>
      <div class="dept-hero-inner">

        <!-- Gauche : Avatar + identité -->
        <div class="dept-hero-left">
          <div class="dept-hero-avatar">
            <img v-if="deptPhotoUrl" :src="deptPhotoUrl" :alt="deptFullName"
              class="dept-hero-avatar-photo" @error="handlePhotoError">
            <span v-else class="dept-hero-avatar-initials">{{ deptInitials }}</span>
          </div>
          <div>
            <div class="dept-hero-greeting">{{ deptGreeting }},</div>
            <h1 class="dept-hero-name">{{ deptCivility }} {{ deptFullName }}</h1>
            <div class="dept-hero-fonction" v-if="deptFonction">
              <i class="fas fa-id-badge me-1"></i>{{ deptFonction }}
            </div>
            <div class="dept-hero-role-pill">
              <i :class="auth.isDirecteur ? `fas fa-user-tie` : `fas fa-user-cog`" class="me-1"></i>
              {{ auth.isDirecteur ? 'Directeur de Département' : 'Assistant de Département' }}
              <span v-if="data?.department?.nom" class="dept-hero-dept-badge">
                {{ data.department.nom }}
              </span>
            </div>
            <div class="dept-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>

        <!-- Droite : KPIs -->
        <div class="dept-hero-kpis">
          <div class="dept-kpi-pill" @click="openDrill('effectifs')">
            <div class="dept-kpi-pill-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="dept-kpi-pill-val">{{ data?.agents?.actifs ?? '—' }}</div>
              <div class="dept-kpi-pill-lbl">Agents actifs</div>
            </div>
            <i class="fas fa-chevron-right dept-kpi-pill-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="dept-kpi-pill" @click="router.push('/taches')">
            <div class="dept-kpi-pill-icon"><i class="fas fa-tasks"></i></div>
            <div>
              <div class="dept-kpi-pill-val">{{ data?.taches?.en_cours ?? 0 }}</div>
              <div class="dept-kpi-pill-lbl">Tâches en cours</div>
            </div>
            <i class="fas fa-chevron-right dept-kpi-pill-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="dept-kpi-pill" @click="router.push('/requests')">
            <div class="dept-kpi-pill-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="dept-kpi-pill-val">{{ data?.requests?.en_attente ?? 0 }}</div>
              <div class="dept-kpi-pill-lbl">Demandes</div>
            </div>
            <i class="fas fa-chevron-right dept-kpi-pill-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="dept-kpi-pill" @click="openDrill('presence')">
            <div class="dept-kpi-pill-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="dept-kpi-pill-val">{{ data?.attendance?.today_rate ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="dept-kpi-pill-lbl">Présence</div>
            </div>
            <i class="fas fa-chevron-right dept-kpi-pill-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="dept-kpi-pill" @click="openDrill('conges')">
            <div class="dept-kpi-pill-icon"><i class="fas fa-umbrella-beach"></i></div>
            <div>
              <div class="dept-kpi-pill-val">{{ data?.conges?.actifs ?? 0 }}</div>
              <div class="dept-kpi-pill-lbl">Congés actifs</div>
            </div>
            <i class="fas fa-chevron-right dept-kpi-pill-arrow"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading / Error -->
    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord..." />
    <div v-else-if="loadError" class="dept-error-banner" :class="{ 'dept-error-info': loadErrorCode === 'DEPT_NOT_FUNCTIONAL' }">
      <i class="fas me-2" :class="loadErrorCode === 'DEPT_NOT_FUNCTIONAL' ? 'fa-info-circle' : 'fa-exclamation-triangle'"></i>
      {{ loadError }}
      <p v-if="loadErrorCode === 'DEPT_NOT_FUNCTIONAL'" class="dept-error-hint">
        Ce département est conservé à des fins d'affectation et d'historisation. Contactez l'administrateur pour l'activer dans le système.
      </p>
    </div>

    <template v-else>

      <!-- ════════ ACTIONS RAPIDES ════════ -->
      <div class="dept-section">
        <div class="dept-section-head">
          <div class="dept-section-icon" style="background:#e0f2fe;color:#0077B5;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="dept-section-title">Actions rapides</h3>
            <p class="dept-section-sub">Accès direct aux modules de gestion du département</p>
          </div>
        </div>
        <div class="dept-actions">
          <component
            :is="a.fn ? 'div' : 'router-link'"
            v-for="a in quickActions"
            :key="a.label"
            v-bind="a.fn ? {} : { to: a.to }"
            class="dept-action"
            @click="a.fn && a.fn()"
          >
            <div class="dept-action-glow" :style="{ background: a.color }"></div>
            <div class="dept-action-icon" :style="{ background: a.bg, color: a.color }">
              <i class="fas" :class="a.icon"></i>
            </div>
            <div class="dept-action-text">
              <div class="dept-action-label">{{ a.label }}</div>
              <div class="dept-action-desc">{{ a.desc }}</div>
            </div>
            <i class="fas fa-chevron-right dept-action-arrow"></i>
          </component>
        </div>
      </div>

      <!-- ════════ INDICATEURS CLÉS ════════ -->
      <div class="dept-section">
        <div class="dept-section-head">
          <div class="dept-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="dept-section-title">Indicateurs clés</h3>
            <p class="dept-section-sub">Vue d'ensemble du département — {{ data.department?.nom }}</p>
          </div>
        </div>
        <div class="dept-metrics">
          <div v-for="m in metrics" :key="m.label" class="dept-metric"
            @click="m.drillSection ? openDrill(m.drillSection) : router.push(m.to)">
            <div class="dept-metric-header">
              <div class="dept-metric-icon" :style="{ background: m.bg, color: m.color }">
                <i class="fas" :class="m.icon"></i>
              </div>
              <span v-if="m.alert" class="dept-metric-alert"><i class="fas fa-exclamation-circle"></i></span>
            </div>
            <div class="dept-metric-val" :style="{ color: m.color }">{{ m.value }}</div>
            <div class="dept-metric-lbl">{{ m.label }}</div>
            <div class="dept-metric-bar">
              <div class="dept-metric-bar-fill" :style="{ background: m.color, width: m.pct + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════ TÂCHES DU DÉPARTEMENT ════════ -->
      <div class="dept-section">
        <div class="dept-section-head">
          <div class="dept-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div>
            <h3 class="dept-section-title">Tâches du département</h3>
            <p class="dept-section-sub">{{ totalTaches }} tâches au total — {{ data.taches?.terminees ?? 0 }} terminées</p>
          </div>
          <router-link to="/taches" class="dept-section-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
        </div>

        <!-- Mini-cards statuts -->
        <div class="dept-task-cards">
          <div class="dept-task-card" @click="router.push('/taches')">
            <div class="dept-task-card-icon" style="background:#dbeafe;color:#3b82f6;"><i class="fas fa-plus-circle"></i></div>
            <div class="dept-task-card-val">{{ data.taches?.nouvelle ?? 0 }}</div>
            <div class="dept-task-card-lbl">Nouvelles</div>
          </div>
          <div class="dept-task-card" @click="router.push('/taches')">
            <div class="dept-task-card-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-spinner"></i></div>
            <div class="dept-task-card-val">{{ data.taches?.en_cours ?? 0 }}</div>
            <div class="dept-task-card-lbl">En cours</div>
          </div>
          <div class="dept-task-card" @click="router.push('/taches')">
            <div class="dept-task-card-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
            <div class="dept-task-card-val">{{ data.taches?.terminees ?? 0 }}</div>
            <div class="dept-task-card-lbl">Terminées</div>
          </div>
          <div class="dept-task-card" :class="{ 'dept-task-card-alert': (data.taches?.overdue ?? 0) > 0 }" @click="router.push('/taches')">
            <div class="dept-task-card-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="dept-task-card-val">{{ data.taches?.overdue ?? 0 }}</div>
            <div class="dept-task-card-lbl">En retard</div>
          </div>
        </div>

        <!-- Barre progression globale -->
        <div v-if="totalTaches > 0" class="dept-task-global-bar">
          <div class="dept-task-global-bar-track">
            <div class="dept-task-global-bar-fill" :style="{ width: taskCompletionPct + '%' }"></div>
          </div>
          <div class="dept-task-global-bar-lbl">{{ taskCompletionPct }}% des tâches terminées</div>
        </div>

        <!-- Liste tâches récentes -->
        <div class="dept-recent-tasks">
          <div v-if="!data.recent_taches?.length" class="dept-empty">
            <div class="dept-empty-icon-wrap" style="background:#f9fafb;"><i class="fas fa-check-circle" style="color:#059669;"></i></div>
            <span>Aucune tâche pour le moment</span>
          </div>
          <div v-else>
            <div class="dept-recent-tasks-head">Tâches récentes</div>
            <div class="dept-tasks-list">
              <router-link v-for="t in data.recent_taches" :key="t.id" :to="'/taches/' + t.id" class="dept-task-row">
                <div class="dept-task-priority" :class="'prio-' + t.priorite"></div>
                <div class="dept-task-info">
                  <div class="dept-task-title">{{ t.titre }}</div>
                  <div class="dept-task-meta">
                    <span class="dept-tag" :class="statutClass(t.statut)">{{ statutLabel(t.statut) }}</span>
                    <span v-if="t.agent" class="dept-task-agent">
                      <i class="fas fa-user-circle me-1"></i>{{ t.agent.prenom }} {{ t.agent.nom }}
                    </span>
                    <span v-if="t.date_echeance" class="dept-task-due" :class="{ 'dept-overdue': isOverdue(t) }">
                      <i class="fas fa-clock me-1"></i>{{ formatDate(t.date_echeance) }}
                    </span>
                  </div>
                </div>
                <div class="dept-task-prog">
                  <div class="dept-prog-track">
                    <div class="dept-prog-fill" :style="{ width: (t.pourcentage || 0) + '%', background: progressColor(t.pourcentage) }"></div>
                  </div>
                  <span class="dept-prog-pct">{{ t.pourcentage ?? 0 }}%</span>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════ PERFORMANCE ÉQUIPE ════════ -->
      <div class="dept-section">
        <div class="dept-section-head">
          <div class="dept-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="dept-section-title">Performance de l'équipe</h3>
            <p class="dept-section-sub">{{ data.agents?.actifs ?? 0 }} agents actifs — réalisation des tâches par agent</p>
          </div>
          <button class="dept-section-link-btn" @click="openDrill('effectifs')">Voir tous <i class="fas fa-arrow-right"></i></button>
        </div>
        <div v-if="!data.team_performance?.length" class="dept-empty">
          <div class="dept-empty-icon-wrap" style="background:#d1fae5;"><i class="fas fa-users" style="color:#059669;"></i></div>
          <span>Aucun agent actif dans le département</span>
        </div>
        <div v-else class="dept-table-wrap">
          <table class="dept-table">
            <thead>
              <tr>
                <th>Agent</th>
                <th class="text-center">Total tâches</th>
                <th class="text-center">Terminées</th>
                <th>Réalisation</th>
                <th class="text-center">En retard</th>
                <th class="text-center">Niveau</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ag in data.team_performance" :key="ag.id"
                class="dept-table-row" @click="openDrill('effectifs')">
                <td>
                  <div class="dept-agent-cell">
                    <div class="dept-agent-avatar">
                      <img v-if="ag.photo" :src="'/' + ag.photo" :alt="ag.prenom"
                        class="dept-agent-photo" @error="$event.target.style.display='none'">
                      <span v-else class="dept-agent-initials">{{ agentInitials(ag) }}</span>
                    </div>
                    <div>
                      <div class="dept-agent-name">{{ ag.prenom }} {{ ag.nom }}</div>
                      <div class="dept-agent-fonction">{{ ag.fonction ?? '—' }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-center"><span class="dept-badge-neutral">{{ ag.taches_total }}</span></td>
                <td class="text-center"><span class="dept-badge-ok">{{ ag.taches_done }}</span></td>
                <td>
                  <div class="dept-prog-cell">
                    <div class="dept-prog-track">
                      <div class="dept-prog-fill" :style="{ width: ag.avg_completion + '%', background: progressColor(ag.avg_completion) }"></div>
                    </div>
                    <span class="dept-prog-pct">{{ ag.avg_completion }}%</span>
                  </div>
                </td>
                <td class="text-center">
                  <span v-if="ag.taches_overdue > 0" class="dept-badge-danger">{{ ag.taches_overdue }}</span>
                  <span v-else class="dept-badge-ok"><i class="fas fa-check"></i></span>
                </td>
                <td class="text-center">
                  <span class="dept-perf-badge" :class="perfClass(ag.avg_completion)">{{ perfLabel(ag.avg_completion) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ════════ DEMANDES EN ATTENTE ════════ -->
      <div class="dept-section">
        <div class="dept-section-head">
          <div class="dept-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-file-signature"></i>
          </div>
          <div>
            <h3 class="dept-section-title">Demandes en attente</h3>
            <p class="dept-section-sub" v-if="auth.isDirecteur">
              En tant que Directeur, vous pouvez viser les demandes avant transmission aux RH
            </p>
            <p class="dept-section-sub" v-else>
              Suivi des demandes en cours de traitement dans votre département
            </p>
          </div>
          <router-link to="/requests" class="dept-section-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
        </div>
        <div v-if="!data.pending_requests?.length" class="dept-empty">
          <div class="dept-empty-icon-wrap" style="background:#dcfce7;"><i class="fas fa-check-double" style="color:#16a34a;"></i></div>
          <span>Aucune demande en attente</span>
          <span class="dept-empty-hint">Toutes les demandes sont traitées</span>
        </div>
        <div v-else class="dept-requests-grid">
          <div v-for="req in data.pending_requests" :key="req.id" class="dept-request-card">
            <div class="dept-request-card-head">
              <div class="dept-request-type-icon" :style="{ background: requestBg(req.type), color: requestColor(req.type) }">
                <i class="fas" :class="requestIcon(req.type)"></i>
              </div>
              <div class="dept-request-type-label">{{ requestLabel(req.type) }}</div>
              <span class="dept-request-badge-pending">en attente</span>
            </div>
            <div class="dept-request-agent" v-if="req.agent">
              <i class="fas fa-user-circle me-1" style="color:#94a3b8;"></i>
              {{ req.agent.prenom }} {{ req.agent.nom }}
            </div>
            <div class="dept-request-desc" v-if="req.description">{{ truncate(req.description, 90) }}</div>
            <div class="dept-request-footer">
              <span class="dept-request-date"><i class="fas fa-clock me-1"></i>{{ formatDate(req.created_at) }}</span>
              <div class="dept-request-actions">
                <router-link v-if="auth.isDirecteur" :to="'/requests/' + req.id" class="dept-btn dept-btn-primary">
                  <i class="fas fa-eye me-1"></i>Traiter
                </router-link>
                <router-link v-else :to="'/requests/' + req.id" class="dept-btn dept-btn-outline">
                  <i class="fas fa-eye me-1"></i>Voir
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════ PRÉSENCE & CONGÉS ════════ -->
      <div class="dept-section">
        <div class="dept-duo-row">

          <!-- Présence -->
          <div class="dept-duo-block">
            <div class="dept-section-head">
              <div class="dept-section-icon" style="background:#d1fae5;color:#059669;">
                <i class="fas fa-user-check"></i>
              </div>
              <div>
                <h3 class="dept-section-title">Présence</h3>
                <p class="dept-section-sub">
                  {{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_actifs ?? 0 }} présents aujourd'hui
                </p>
              </div>
              <button class="dept-section-link-btn" @click="openDrill('presence')">Détail <i class="fas fa-arrow-right"></i></button>
            </div>
            <div class="dept-presence-big">
              <div class="dept-presence-ring-wrap">
                <svg viewBox="0 0 120 120" class="dept-ring-svg">
                  <circle cx="60" cy="60" r="50" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                  <circle cx="60" cy="60" r="50" fill="none" stroke="#059669" stroke-width="10"
                    stroke-linecap="round"
                    :stroke-dasharray="presenceDash"
                    :stroke-dashoffset="presenceOffset"
                    transform="rotate(-90 60 60)"/>
                </svg>
                <div class="dept-ring-center">
                  <div class="dept-ring-val">{{ data.attendance?.today_rate ?? 0 }}%</div>
                  <div class="dept-ring-lbl">Aujourd'hui</div>
                </div>
              </div>
              <div class="dept-presence-stats">
                <div class="dept-presence-stat">
                  <span class="dept-presence-stat-val" style="color:#059669;">{{ data.attendance?.today_present ?? 0 }}</span>
                  <span class="dept-presence-stat-lbl">Présents</span>
                </div>
                <div class="dept-presence-stat">
                  <span class="dept-presence-stat-val" style="color:#dc2626;">
                    {{ (data.attendance?.total_actifs ?? 0) - (data.attendance?.today_present ?? 0) }}
                  </span>
                  <span class="dept-presence-stat-lbl">Absents</span>
                </div>
                <div class="dept-presence-stat">
                  <span class="dept-presence-stat-val" style="color:#0077B5;">{{ data.attendance?.monthly_rate ?? 0 }}%</span>
                  <span class="dept-presence-stat-lbl">Moy. mensuelle</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Congés -->
          <div class="dept-duo-block">
            <div class="dept-section-head">
              <div class="dept-section-icon" style="background:#fef3c7;color:#f59e0b;">
                <i class="fas fa-umbrella-beach"></i>
              </div>
              <div>
                <h3 class="dept-section-title">Congés</h3>
                <p class="dept-section-sub">{{ data.conges?.actifs ?? 0 }} agent(s) en congé aujourd'hui</p>
              </div>
              <router-link to="/mon-planning-conges" class="dept-section-link">Planning <i class="fas fa-arrow-right"></i></router-link>
            </div>
            <div class="dept-conges-grid">
              <div class="dept-conge-stat-card" style="border-color:#16a34a;">
                <div class="dept-conge-stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
                <div>
                  <div class="dept-conge-stat-val">{{ data.conges?.actifs ?? 0 }}</div>
                  <div class="dept-conge-stat-lbl">En cours aujourd'hui</div>
                </div>
              </div>
              <div class="dept-conge-stat-card" style="border-color:#d97706;">
                <div class="dept-conge-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-hourglass-half"></i></div>
                <div>
                  <div class="dept-conge-stat-val">{{ data.requests?.en_attente ?? 0 }}</div>
                  <div class="dept-conge-stat-lbl">Demandes en attente</div>
                </div>
              </div>
              <div class="dept-conge-stat-card" style="border-color:#0077B5;">
                <div class="dept-conge-stat-icon" style="background:#e0f2fe;color:#0077B5;"><i class="fas fa-calendar-check"></i></div>
                <div>
                  <div class="dept-conge-stat-val">{{ data.agents?.actifs ?? 0 }}</div>
                  <div class="dept-conge-stat-lbl">Agents disponibles</div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </template>
  </div>

  <!-- ════════════════════ DRILL-DOWN PANEL ════════════════════ -->
  <Teleport to="body">
    <div v-if="drillOpen" class="drill-overlay" @click.self="closeDrill">
      <div class="drill-panel">

        <!-- Header -->
        <div class="drill-header" :style="{ background: '#0077B5' }">
          <div class="drill-header-left">
            <div class="drill-header-icon">
              <i class="fas" :class="drillSection === 'effectifs' ? 'fa-users' : drillSection === 'presence' ? 'fa-user-check' : 'fa-umbrella-beach'"></i>
            </div>
            <div>
              <div class="drill-header-title">
                {{ drillSection === 'effectifs' ? 'Agents du département' : drillSection === 'presence' ? 'Présence — agents du département' : 'Congés — agents du département' }}
              </div>
              <div class="drill-header-sub">{{ data?.department?.nom }}</div>
            </div>
          </div>
          <button class="drill-close-btn" @click="closeDrill"><i class="fas fa-times"></i></button>
        </div>

        <!-- Tabs -->
        <div class="drill-tabs">
          <button class="drill-tab" :class="{ active: drillSection === 'effectifs' }" @click="drillSection = 'effectifs'">
            <i class="fas fa-users me-1"></i> Effectifs
          </button>
          <button class="drill-tab" :class="{ active: drillSection === 'presence' }" @click="drillSection = 'presence'">
            <i class="fas fa-user-check me-1"></i> Présence
          </button>
          <button class="drill-tab" :class="{ active: drillSection === 'conges' }" @click="drillSection = 'conges'">
            <i class="fas fa-umbrella-beach me-1"></i> Congés
          </button>
        </div>

        <!-- Body -->
        <div class="drill-body">

          <!-- Loading -->
          <div v-if="drillLoading" class="drill-loading">
            <i class="fas fa-spinner fa-spin"></i> Chargement…
          </div>

          <template v-else-if="drillAgents">

            <!-- Summary bar -->
            <div class="drill-summary">
              <div class="drill-summary-item">
                <div class="drill-summary-val">{{ drillAgents.length }}</div>
                <div class="drill-summary-lbl">Total agents</div>
              </div>
              <div class="drill-summary-sep"></div>
              <div class="drill-summary-item">
                <div class="drill-summary-val" style="color:#059669">{{ drillAgents.filter(a => a.statut === 'actif' || a.statut === 'disponible').length }}</div>
                <div class="drill-summary-lbl">Disponibles</div>
              </div>
              <div class="drill-summary-sep"></div>
              <div class="drill-summary-item">
                <div class="drill-summary-val" style="color:#0891b2">{{ drillAgents.filter(a => a.statut === 'en_conge').length }}</div>
                <div class="drill-summary-lbl">En congé</div>
              </div>
              <div class="drill-summary-sep"></div>
              <div class="drill-summary-item">
                <div class="drill-summary-val" style="color:#d97706">{{ drillAgents.filter(a => a.statut === 'en_mission').length }}</div>
                <div class="drill-summary-lbl">En mission</div>
              </div>
            </div>

            <!-- Agent list — Effectifs tab -->
            <template v-if="drillSection === 'effectifs'">
              <div class="drill-agent-list">
                <div v-for="ag in drillAgents" :key="ag.id" class="drill-agent-row">
                  <div class="drill-agent-avatar" :style="{ background: '#e0f2fe', color: '#0077B5' }">
                    <img v-if="ag.photo" :src="ag.photo" :alt="ag.prenom" @error="e => e.target.style.display='none'">
                    <span v-else>{{ ((ag.prenom?.[0] ?? '') + (ag.nom?.[0] ?? '')).toUpperCase() }}</span>
                  </div>
                  <div class="drill-agent-info">
                    <div class="drill-agent-name">{{ ag.prenom }} {{ ag.nom }}</div>
                    <div class="drill-agent-fonction">{{ ag.fonction || '—' }}</div>
                  </div>
                  <div class="drill-agent-meta">
                    <span class="drill-agent-statut" :class="`statut-${ag.statut}`">{{ statutLabel(ag.statut) }}</span>
                    <div class="drill-agent-tasks">
                      <i class="fas fa-tasks me-1" style="color:#d97706"></i>{{ ag.taches_en_cours }} en cours
                      <span v-if="ag.taches_overdue > 0" style="color:#dc2626;margin-left:.4rem"><i class="fas fa-exclamation-circle"></i> {{ ag.taches_overdue }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <!-- Présence tab -->
            <template v-else-if="drillSection === 'presence'">
              <div class="drill-agent-list">
                <div v-for="ag in drillAgents" :key="ag.id" class="drill-agent-row">
                  <div class="drill-agent-avatar" :style="{ background: '#dcfce7', color: '#059669' }">
                    <span>{{ ((ag.prenom?.[0] ?? '') + (ag.nom?.[0] ?? '')).toUpperCase() }}</span>
                  </div>
                  <div class="drill-agent-info">
                    <div class="drill-agent-name">{{ ag.prenom }} {{ ag.nom }}</div>
                    <div class="drill-agent-fonction">{{ ag.fonction || '—' }}</div>
                  </div>
                  <div class="drill-agent-meta">
                    <div class="drill-presence-bar-wrap">
                      <div class="drill-presence-bar">
                        <div class="drill-presence-bar-fill" :style="{ width: ag.taux_presence + '%', background: ag.taux_presence >= 80 ? '#059669' : ag.taux_presence >= 50 ? '#d97706' : '#dc2626' }"></div>
                      </div>
                      <span class="drill-presence-pct">{{ ag.taux_presence }}%</span>
                    </div>
                    <div style="font-size:.72rem;color:#94a3b8">{{ ag.jours_presents }}j présents ce mois</div>
                  </div>
                </div>
              </div>
            </template>

            <!-- Congés tab -->
            <template v-else>
              <div class="drill-agent-list">
                <div v-for="ag in drillAgents.filter(a => a.statut === 'en_conge')" :key="ag.id" class="drill-agent-row">
                  <div class="drill-agent-avatar" :style="{ background: '#cffafe', color: '#0891b2' }">
                    <span>{{ ((ag.prenom?.[0] ?? '') + (ag.nom?.[0] ?? '')).toUpperCase() }}</span>
                  </div>
                  <div class="drill-agent-info">
                    <div class="drill-agent-name">{{ ag.prenom }} {{ ag.nom }}</div>
                    <div class="drill-agent-fonction">{{ ag.fonction || '—' }}</div>
                  </div>
                  <div class="drill-agent-meta">
                    <span class="drill-agent-statut statut-en_conge">En congé</span>
                  </div>
                </div>
                <div v-if="!drillAgents.some(a => a.statut === 'en_conge')" class="drill-empty">
                  <i class="fas fa-check-circle" style="color:#059669;font-size:1.5rem;margin-bottom:.5rem"></i>
                  <div>Aucun agent en congé actuellement</div>
                </div>
              </div>
            </template>

          </template>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const auth   = useAuthStore()

const loading    = ref(true)
const loadError     = ref(null)
const loadErrorCode = ref(null)
const data       = ref({})
const photoIndex = ref(0)

// ─── DRILL-DOWN STATE ───────────────────────────────────────────────────────
const drillOpen    = ref(false)
const drillLoading = ref(false)
const drillAgents  = ref(null)
const drillSection = ref('effectifs') // 'effectifs' | 'presence' | 'conges'

// ─── Identité ────────────────────────────────────────────────
const deptIsFemme = computed(() => {
  const s = auth.agent?.sexe?.toLowerCase() ?? ''
  return s === 'f' || s === 'femme' || s === 'féminin'
})
const deptCivility  = computed(() => deptIsFemme.value ? 'Mme' : 'M.')
const deptGreeting  = computed(() => {
  const h = new Date().getHours()
  return h < 12 ? 'Bonjour' : h < 18 ? 'Bon après-midi' : 'Bonsoir'
})
const deptFullName = computed(() => {
  const a = auth.agent
  if (!a) return ''
  return [a.prenom, a.postnom, a.nom].filter(Boolean).join(' ')
})
const deptFonction = computed(() => auth.agent?.poste_actuel || auth.agent?.fonction || null)
const deptInitials = computed(() => {
  const a = auth.agent
  if (!a) return 'D'
  return ((a.prenom?.[0] ?? '') + (a.nom?.[0] ?? '')).toUpperCase() || 'D'
})
const deptPhotoUrl = computed(() => {
  const photo = auth.agent?.photo
  if (!photo) return null
  const p = photo.trim()
  if (/^https?:\/\//i.test(p)) return p
  const n = p.replace(/^\/+/, '')
  const candidates = [`/${n}`, `/storage/${n}`, `/uploads/profiles/${n}`]
  return [...new Set(candidates)][photoIndex.value] ?? null
})
function handlePhotoError() {
  const p = (auth.agent?.photo ?? '').trim().replace(/^\/+/, '')
  const max = 3
  if (photoIndex.value < max - 1) photoIndex.value++
  else photoIndex.value = max
}

// ─── Date ────────────────────────────────────────────────────
const today = computed(() =>
  new Date().toLocaleDateString('fr-CD', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  })
)

// ─── Actions rapides ─────────────────────────────────────────
const quickActions = computed(() => {
  const base = [
    { fn: () => openDrill('effectifs'), icon: 'fa-users',          color: '#059669', bg: '#d1fae5', label: 'Agents',            desc: 'Voir les agents du département' },
    { fn: () => openDrill('presence'),  icon: 'fa-user-clock',     color: '#d97706', bg: '#fef3c7', label: 'Présences',         desc: 'Suivi de présence du département' },
    { to: '/taches/create',             icon: 'fa-plus-circle',    color: '#0077B5', bg: '#e0f2fe', label: 'Nouvelle tâche',    desc: 'Créer et attribuer une tâche' },
    { to: '/requests',                  icon: 'fa-file-signature', color: '#7c3aed', bg: '#ede9fe', label: 'Demandes',          desc: auth.isDirecteur ? 'Viser et valider les demandes' : 'Suivre les demandes' },
    { to: '/taches',                    icon: 'fa-tasks',           color: '#dc2626', bg: '#fee2e2', label: 'Toutes les tâches', desc: 'Tableau de bord des tâches' },
    { to: '/mon-planning-conges',       icon: 'fa-calendar-alt',   color: '#0891b2', bg: '#cffafe', label: 'Congés',            desc: 'Planning et demandes de congé' },
  ]
  if (auth.isDirecteur) {
    base.push({ to: '/requests?statut=en_attente', icon: 'fa-stamp', color: '#b45309', bg: '#fef9c3', label: 'Visa Directeur', desc: 'Demandes à viser en priorité' })
  }
  return base
})

// ─── Métriques ───────────────────────────────────────────────
const totalTaches = computed(() =>
  (data.value?.taches?.en_cours ?? 0) +
  (data.value?.taches?.terminees ?? 0) +
  (data.value?.taches?.nouvelle ?? 0)
)
const taskCompletionPct = computed(() => {
  const t = totalTaches.value
  if (!t) return 0
  return Math.round(((data.value?.taches?.terminees ?? 0) / t) * 100)
})

const metrics = computed(() => {
  const agentTotal = Math.max(data.value?.agents?.total ?? 1, 1)
  const tTotal = Math.max(totalTaches.value, 1)
  return [
    { label: "Agents actifs",        icon: 'fa-users',              color: '#0077B5', bg: '#e0f2fe',
      value: data.value?.agents?.actifs ?? 0,             to: '/agents',          alert: false,
      drillSection: 'effectifs',
      pct: Math.min(((data.value?.agents?.actifs ?? 0) / agentTotal) * 100, 100) },
    { label: "Présence aujourd'hui", icon: 'fa-user-check',         color: '#059669', bg: '#d1fae5',
      value: (data.value?.attendance?.today_rate ?? 0) + '%', to: '/pointages',
      drillSection: 'presence',
      pct: data.value?.attendance?.today_rate ?? 0,
      alert: (data.value?.attendance?.today_rate ?? 100) < 60 },
    { label: "Tâches en cours",      icon: 'fa-spinner',            color: '#d97706', bg: '#fef3c7',
      value: data.value?.taches?.en_cours ?? 0,            to: '/taches',          alert: false,
      pct: Math.min(((data.value?.taches?.en_cours ?? 0) / tTotal) * 100, 100) },
    { label: "Tâches terminées",     icon: 'fa-check-circle',       color: '#059669', bg: '#dcfce7',
      value: data.value?.taches?.terminees ?? 0,           to: '/taches',          alert: false,
      pct: taskCompletionPct.value },
    { label: "En retard",            icon: 'fa-exclamation-triangle',color: '#dc2626', bg: '#fee2e2',
      value: data.value?.taches?.overdue ?? 0,             to: '/taches',
      pct: Math.min(((data.value?.taches?.overdue ?? 0) / tTotal) * 100, 100),
      alert: (data.value?.taches?.overdue ?? 0) > 0 },
    { label: "Demandes en attente",  icon: 'fa-hourglass-half',     color: '#7c3aed', bg: '#ede9fe',
      value: data.value?.requests?.en_attente ?? 0,        to: '/requests',
      pct: Math.min(((data.value?.requests?.en_attente ?? 0) / agentTotal) * 100, 100),
      alert: (data.value?.requests?.en_attente ?? 0) > 0 },
    { label: "Congés actifs",        icon: 'fa-umbrella-beach',     color: '#0891b2', bg: '#cffafe',
      value: data.value?.conges?.actifs ?? 0,              to: '/mon-planning-conges', alert: false,
      drillSection: 'conges',
      pct: Math.min(((data.value?.conges?.actifs ?? 0) / agentTotal) * 100, 100) },
  ]
})

// ─── Anneau présence ─────────────────────────────────────────
const presenceDash   = computed(() => Math.PI * 2 * 50)
const presenceOffset = computed(() => {
  const pct = data.value?.attendance?.today_rate ?? 0
  return presenceDash.value * (1 - pct / 100)
})

// ─── Chargement ──────────────────────────────────────────────
async function loadData() {
  loading.value   = true
  loadError.value     = null
  loadErrorCode.value  = null
  try {
    const res  = await client.get('/dashboard/department')
    data.value = res.data?.data ?? res.data ?? {}
  } catch (err) {
    loadError.value     = err?.response?.data?.message ?? 'Impossible de charger le tableau de bord.'
    loadErrorCode.value  = err?.response?.data?.code ?? null
  } finally {
    loading.value = false
  }
}
onMounted(loadData)

// ─── DRILL-DOWN METHODS ────────────────────────────────────────────────────
async function openDrill(section = 'effectifs') {
  drillSection.value = section
  drillOpen.value    = true
  if (drillAgents.value) return // already loaded
  drillLoading.value = true
  try {
    const { data: res } = await client.get('/dashboard/department/agents')
    drillAgents.value = res.data?.agents ?? res.agents ?? []
  } catch {
    drillAgents.value = []
  } finally {
    drillLoading.value = false
  }
}

function closeDrill() {
  drillOpen.value   = false
  drillAgents.value = null
  drillSection.value = 'effectifs'
}

// ─── Helpers ─────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-CD', { day: '2-digit', month: 'short', year: 'numeric' })
}
function truncate(str, n) {
  if (!str) return ''
  return str.length > n ? str.slice(0, n) + '…' : str
}
function isOverdue(t) {
  return !!(t.date_echeance && t.statut !== 'terminee' && new Date(t.date_echeance) < new Date())
}
function agentInitials(ag) {
  return ((ag.prenom?.[0] ?? '') + (ag.nom?.[0] ?? '')).toUpperCase() || '?'
}
function statutLabel(s) {
  return {
    // Task statuses
    nouvelle: 'Nouvelle', en_cours: 'En cours', terminee: 'Terminée',
    // Agent statuses
    actif: 'Actif', disponible: 'Disponible', inactif: 'Inactif',
    suspendu: 'Suspendu', en_conge: 'En congé', en_mission: 'En mission',
    en_formation: 'En formation',
  }[s] ?? s
}
function statutClass(s) {
  return { nouvelle: 'tag-nouvelle', en_cours: 'tag-en-cours', terminee: 'tag-terminee' }[s] ?? ''
}
function progressColor(pct) {
  if (!pct) return '#dc2626'
  if (pct >= 80) return '#059669'
  if (pct >= 50) return '#d97706'
  return '#dc2626'
}
function perfClass(pct) {
  if (pct >= 80) return 'perf-bon'
  if (pct >= 50) return 'perf-moyen'
  return 'perf-faible'
}
function perfLabel(pct) {
  if (pct >= 80) return 'Bon'
  if (pct >= 50) return 'Moyen'
  return 'Faible'
}
function requestIcon(type) {
  return { conge: 'fa-umbrella-beach', formation: 'fa-graduation-cap', autorisation: 'fa-file-alt', attestation: 'fa-certificate' }[type] ?? 'fa-file'
}
function requestColor(type) {
  return { conge: '#0891b2', formation: '#7c3aed', autorisation: '#d97706', attestation: '#059669' }[type] ?? '#6b7280'
}
function requestBg(type) {
  return { conge: '#cffafe', formation: '#ede9fe', autorisation: '#fef3c7', attestation: '#d1fae5' }[type] ?? '#f3f4f6'
}
function requestLabel(type) {
  return { conge: 'Congé', formation: 'Formation', autorisation: 'Autorisation', attestation: 'Attestation' }[type] ?? (type ?? 'Demande')
}
</script>

<style scoped>
/* ──────────────────────────────────────────────────────────
   Conteneur
────────────────────────────────────────────────────────── */
.dept-dashboard { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }

/* ──────────────────────────────────────────────────────────
   HERO
────────────────────────────────────────────────────────── */
.dept-hero {
  position: relative;
  border-radius: 20px; overflow: hidden; margin-bottom: 1.8rem;
  background: linear-gradient(135deg, #0a1628 0%, #0f2847 30%, #0c4a6e 60%, #0077B5 100%);
  box-shadow: 0 8px 32px rgba(0, 30, 60, .25);
  color: #fff;
}
.dept-hero-bg {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse at 80% 0%, rgba(255,255,255,.13) 0%, transparent 60%),
    radial-gradient(ellipse at 10% 100%, rgba(0,0,0,.18) 0%, transparent 55%);
  pointer-events: none;
}
.dept-hero-inner {
  position: relative;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 1.5rem; padding: 2rem 2.5rem;
}
.dept-hero-left { display: flex; align-items: center; gap: 1.25rem; flex: 1; min-width: 0; }

/* Avatar */
.dept-hero-avatar {
  width: 70px; height: 70px; border-radius: 50%; flex-shrink: 0;
  overflow: hidden; border: 3px solid rgba(255,255,255,.3);
  box-shadow: 0 4px 16px rgba(0,0,0,.3);
  background: linear-gradient(135deg, rgba(101,163,213,.5), rgba(30,58,95,.6));
  display: flex; align-items: center; justify-content: center;
}
.dept-hero-avatar-photo { width: 100%; height: 100%; object-fit: cover; }
.dept-hero-avatar-initials { font-size: 1.5rem; font-weight: 800; color: #fff; }

.dept-hero-greeting { font-size: .8rem; opacity: .75; text-transform: uppercase; letter-spacing: 1.2px; }
h1.dept-hero-name { font-size: 1.45rem; font-weight: 800; margin: .18rem 0 .3rem; }
.dept-hero-fonction { font-size: .85rem; opacity: .88; margin-bottom: .35rem; }
.dept-hero-role-pill {
  display: inline-flex; align-items: center; gap: .4rem;
  background: rgba(255,255,255,.15); backdrop-filter: blur(4px);
  border-radius: 100px; padding: .28rem .85rem; font-size: .8rem; margin-bottom: .3rem;
}
.dept-hero-dept-badge {
  background: rgba(255,255,255,.25); border-radius: 100px;
  padding: .1rem .55rem; font-size: .75rem; font-weight: 700;
}
.dept-hero-date { font-size: .78rem; opacity: .7; }

/* KPIs hero */
.dept-hero-kpis { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.dept-kpi-pill {
  display: flex; align-items: center; gap: .6rem;
  background: rgba(255,255,255,.12); backdrop-filter: blur(6px);
  border: 1px solid rgba(255,255,255,.18); border-radius: 14px;
  padding: .7rem 1rem; cursor: pointer; transition: background .2s; min-width: 110px;
}
.dept-kpi-pill:hover { background: rgba(255,255,255,.22); }
.dept-kpi-pill-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; flex-shrink: 0;
  background: rgba(255,255,255,.18);
}
.dept-kpi-pill-val { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
.dept-kpi-pill-lbl { font-size: .7rem; opacity: .8; }
.dept-kpi-pill-arrow { opacity: .45; font-size: .7rem; margin-left: auto; }
.kpi-unit { font-size: .75rem; font-weight: 400; }
.kpi-divider { width: 1px; height: 44px; background: rgba(255,255,255,.2); }

/* Error */
.dept-error-banner {
  margin-bottom: 1.8rem; padding: .85rem 1.25rem;
  background: #fef3c7; color: #92400e;
  border: 1px solid #fcd34d; border-radius: 12px; font-size: .9rem;
}
.dept-error-banner.dept-error-info {
  background: #eff6ff; color: #1e40af; border-color: #93c5fd;
}
.dept-error-hint {
  margin: .5rem 0 0; font-size: .82rem; opacity: .8;
}

/* ──────────────────────────────────────────────────────────
   Sections
────────────────────────────────────────────────────────── */
.dept-section { margin-bottom: 1.8rem; }
.dept-section-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.1rem; }
.dept-section-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.05rem; flex-shrink: 0;
}
.dept-section-title { font-size: 1rem; font-weight: 700; margin: 0; }
.dept-section-sub { font-size: .8rem; color: #6b7280; margin: 0; }
.dept-section-link { margin-left: auto; font-size: .8rem; color: #0077B5; text-decoration: none; white-space: nowrap; }
.dept-section-link:hover { text-decoration: underline; }
.dept-section-link-btn { margin-left: auto; font-size: .8rem; color: #0077B5; background: none; border: none; padding: 0; cursor: pointer; white-space: nowrap; font-family: inherit; }
.dept-section-link-btn:hover { text-decoration: underline; }

/* ──────────────────────────────────────────────────────────
   Actions rapides (style SEN/RH)
────────────────────────────────────────────────────────── */
.dept-actions {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: .85rem;
}
.dept-action {
  position: relative; display: flex; align-items: center; gap: .85rem;
  padding: 1rem 1.1rem; background: #fff; border-radius: 14px;
  box-shadow: 0 2px 8px rgba(0,0,0,.05); text-decoration: none; color: #1f2937;
  border: 1px solid #e5e7eb; overflow: hidden; transition: transform .18s, box-shadow .18s;
}
.dept-action:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.1); }
.dept-action-glow {
  position: absolute; top: -32px; left: 50%; transform: translateX(-50%);
  width: 90px; height: 90px; border-radius: 50%; opacity: .1; pointer-events: none;
}
.dept-action-icon {
  width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
}
.dept-action-text { flex: 1; min-width: 0; }
.dept-action-label { font-size: .88rem; font-weight: 700; }
.dept-action-desc { font-size: .74rem; color: #6b7280; margin-top: .1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dept-action-arrow { color: #cbd5e1; font-size: .8rem; flex-shrink: 0; }

/* ──────────────────────────────────────────────────────────
   Indicateurs clés
────────────────────────────────────────────────────────── */
.dept-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: .85rem;
}
.dept-metric {
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  padding: 1.1rem 1rem; cursor: pointer; transition: transform .18s, box-shadow .18s;
}
.dept-metric:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.08); }
.dept-metric-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .65rem; }
.dept-metric-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; font-size: .95rem;
}
.dept-metric-alert { color: #dc2626; font-size: .8rem; }
.dept-metric-val { font-size: 1.65rem; font-weight: 800; line-height: 1.1; }
.dept-metric-lbl { font-size: .75rem; color: #6b7280; margin: .2rem 0 .6rem; }
.dept-metric-bar { height: 5px; background: #f1f5f9; border-radius: 100px; overflow: hidden; }
.dept-metric-bar-fill { height: 100%; border-radius: 100px; transition: width .4s; }

/* ──────────────────────────────────────────────────────────
   Tâches — mini-cards
────────────────────────────────────────────────────────── */
.dept-task-cards {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: .85rem; margin-bottom: 1rem;
}
.dept-task-card {
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  padding: 1rem; text-align: center; cursor: pointer; transition: transform .18s, box-shadow .18s;
}
.dept-task-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.08); }
.dept-task-card-alert { border-color: #fca5a5; background: #fff5f5; animation: pulse 2s infinite; }
@keyframes pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(220,38,38,.15); }
  50%      { box-shadow: 0 0 0 8px rgba(220,38,38,0); }
}
.dept-task-card-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; margin: 0 auto .6rem;
}
.dept-task-card-val { font-size: 1.8rem; font-weight: 800; color: #1f2937; }
.dept-task-card-lbl { font-size: .75rem; color: #6b7280; margin-top: .2rem; }

/* Barre globale */
.dept-task-global-bar { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem; }
.dept-task-global-bar-track { flex: 1; height: 8px; background: #e5e7eb; border-radius: 100px; overflow: hidden; }
.dept-task-global-bar-fill {
  height: 100%; border-radius: 100px;
  background: linear-gradient(90deg, #059669, #34d399); transition: width .5s;
}
.dept-task-global-bar-lbl { font-size: .8rem; color: #6b7280; white-space: nowrap; }

/* Liste tâches récentes */
.dept-recent-tasks-head {
  font-size: .85rem; font-weight: 600; color: #374151;
  margin-bottom: .6rem; padding-bottom: .4rem; border-bottom: 1px solid #e5e7eb;
}
.dept-tasks-list { display: flex; flex-direction: column; gap: .5rem; }
.dept-task-row {
  display: flex; align-items: center; gap: .85rem;
  padding: .65rem .9rem; background: #f9fafb; border: 1px solid #f3f4f6;
  border-radius: 10px; text-decoration: none; color: inherit; transition: background .15s;
}
.dept-task-row:hover { background: #f0f4ff; }
.dept-task-priority { width: 4px; height: 36px; border-radius: 100px; flex-shrink: 0; background: #e5e7eb; }
.dept-task-priority.prio-haute   { background: #dc2626; }
.dept-task-priority.prio-normale { background: #d97706; }
.dept-task-priority.prio-basse   { background: #059669; }
.dept-task-info { flex: 1; min-width: 0; }
.dept-task-title { font-size: .88rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dept-task-meta { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; margin-top: .18rem; }
.dept-task-agent { font-size: .75rem; color: #6b7280; }
.dept-task-due   { font-size: .75rem; color: #6b7280; }
.dept-overdue    { color: #dc2626 !important; font-weight: 600; }

.dept-tag { font-size: .7rem; padding: .15rem .55rem; border-radius: 100px; font-weight: 600; }
.tag-nouvelle  { background: #dbeafe; color: #1d4ed8; }
.tag-en-cours  { background: #fef3c7; color: #92400e; }
.tag-terminee  { background: #d1fae5; color: #065f46; }

.dept-task-prog { display: flex; align-items: center; gap: .5rem; min-width: 100px; }
.dept-prog-track { flex: 1; height: 7px; background: #e5e7eb; border-radius: 100px; overflow: hidden; }
.dept-prog-fill  { height: 100%; border-radius: 100px; transition: width .3s; }
.dept-prog-pct   { font-size: .75rem; font-weight: 700; color: #374151; min-width: 32px; text-align: right; }
.dept-prog-cell  { display: flex; align-items: center; gap: .5rem; }
.dept-prog-cell .dept-prog-track { width: 80px; }

/* ──────────────────────────────────────────────────────────
   Tableau performance
────────────────────────────────────────────────────────── */
.dept-table-wrap { overflow-x: auto; }
.dept-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
.dept-table th {
  padding: .6rem .85rem; background: #f9fafb; color: #6b7280;
  font-size: .71rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 600;
  border-bottom: 1px solid #e5e7eb;
}
.dept-table td { padding: .6rem .85rem; border-bottom: 1px solid #f3f4f6; }
.dept-table-row { cursor: pointer; transition: background .15s; }
.dept-table-row:hover { background: #f0f4ff; }

.dept-agent-cell { display: flex; align-items: center; gap: .6rem; }
.dept-agent-avatar {
  width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
  overflow: hidden; background: #e0f2fe;
  display: flex; align-items: center; justify-content: center;
}
.dept-agent-photo    { width: 100%; height: 100%; object-fit: cover; }
.dept-agent-initials { font-size: .75rem; font-weight: 700; color: #0077B5; }
.dept-agent-name     { font-weight: 600; font-size: .85rem; }
.dept-agent-fonction { font-size: .72rem; color: #6b7280; }

.dept-badge-neutral { background: #f1f5f9; color: #334155; padding: .2rem .6rem; border-radius: 6px; font-weight: 700; font-size: .8rem; }
.dept-badge-ok      { background: #d1fae5; color: #065f46; padding: .2rem .6rem; border-radius: 6px; font-size: .8rem; }
.dept-badge-danger  { background: #fee2e2; color: #b91c1c; padding: .2rem .6rem; border-radius: 6px; font-weight: 700; font-size: .8rem; }

.dept-perf-badge { padding: .2rem .65rem; border-radius: 100px; font-size: .73rem; font-weight: 700; }
.perf-bon    { background: #d1fae5; color: #065f46; }
.perf-moyen  { background: #fef3c7; color: #92400e; }
.perf-faible { background: #fee2e2; color: #b91c1c; }

/* ──────────────────────────────────────────────────────────
   Demandes — grille
────────────────────────────────────────────────────────── */
.dept-requests-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: .85rem;
}
.dept-request-card {
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 1.1rem;
  display: flex; flex-direction: column; gap: .5rem; transition: box-shadow .2s;
}
.dept-request-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.09); }
.dept-request-card-head { display: flex; align-items: center; gap: .6rem; }
.dept-request-type-icon {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: .9rem;
}
.dept-request-type-label { font-size: .88rem; font-weight: 700; flex: 1; }
.dept-request-badge-pending {
  background: #fef3c7; color: #92400e; font-size: .7rem; font-weight: 600;
  padding: .15rem .55rem; border-radius: 100px;
}
.dept-request-agent { font-size: .8rem; color: #374151; }
.dept-request-desc  { font-size: .78rem; color: #6b7280; line-height: 1.4; }
.dept-request-footer { display: flex; align-items: center; justify-content: space-between; margin-top: .35rem; }
.dept-request-date   { font-size: .75rem; color: #9ca3af; }
.dept-request-actions { display: flex; gap: .4rem; }

.dept-btn {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .35rem .75rem; border-radius: 8px; font-size: .78rem; font-weight: 600;
  text-decoration: none; cursor: pointer; border: none; transition: background .15s;
}
.dept-btn-primary { background: #1565c0; color: #fff; }
.dept-btn-primary:hover { background: #0d47a1; }
.dept-btn-outline { background: #f1f5f9; color: #334155; }
.dept-btn-outline:hover { background: #e2e8f0; }

/* ──────────────────────────────────────────────────────────
   Présence & Congés
────────────────────────────────────────────────────────── */
.dept-duo-row {
  display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
}
.dept-duo-block {
  background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
  padding: 1.25rem; box-shadow: 0 2px 10px rgba(0,0,0,.05);
}
.dept-duo-block .dept-section-head { margin-bottom: .9rem; }

/* Anneau */
.dept-presence-big { display: flex; align-items: center; gap: 1.5rem; padding: .5rem 0; }
.dept-presence-ring-wrap { position: relative; width: 110px; height: 110px; flex-shrink: 0; }
.dept-ring-svg { width: 100%; height: 100%; }
.dept-ring-center {
  position: absolute; inset: 0;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.dept-ring-val { font-size: 1.4rem; font-weight: 800; color: #059669; }
.dept-ring-lbl { font-size: .7rem; color: #6b7280; }
.dept-presence-stats { display: flex; flex-direction: column; gap: .6rem; }
.dept-presence-stat  { display: flex; flex-direction: column; }
.dept-presence-stat-val { font-size: 1.3rem; font-weight: 800; }
.dept-presence-stat-lbl { font-size: .72rem; color: #6b7280; }

/* Congés */
.dept-conges-grid { display: flex; flex-direction: column; gap: .7rem; padding: .5rem 0; }
.dept-conge-stat-card {
  display: flex; align-items: center; gap: .75rem;
  border-left: 3px solid; border-radius: 10px; background: #fafafa; padding: .7rem 1rem;
}
.dept-conge-stat-icon {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: .9rem;
}
.dept-conge-stat-val { font-size: 1.35rem; font-weight: 800; }
.dept-conge-stat-lbl { font-size: .75rem; color: #6b7280; }

/* Empty state */
.dept-empty {
  text-align: center; padding: 2.5rem 1rem; color: #9ca3af;
  display: flex; flex-direction: column; align-items: center; gap: .5rem;
}
.dept-empty-icon-wrap {
  width: 52px; height: 52px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; margin-bottom: .25rem;
}
.dept-empty span { font-size: .88rem; }
.dept-empty-hint { font-size: .78rem; color: #cbd5e1; }

/* ──────────────────────────────────────────────────────────
   Dark mode
────────────────────────────────────────────────────────── */
html.dark .dept-action,
html.dark .dept-metric,
html.dark .dept-task-card,
html.dark .dept-duo-block,
html.dark .dept-request-card { background: #1e293b !important; border-color: #334155 !important; }
html.dark .dept-action-label,
html.dark .dept-section-title,
html.dark .dept-task-card-val,
html.dark .dept-metric-val,
html.dark .dept-agent-name,
html.dark .dept-ring-val { color: #f1f5f9 !important; }
html.dark .dept-action-desc,
html.dark .dept-section-sub,
html.dark .dept-metric-lbl,
html.dark .dept-task-card-lbl,
html.dark .dept-agent-fonction,
html.dark .dept-conge-stat-lbl,
html.dark .dept-presence-stat-lbl,
html.dark .dept-task-global-bar-lbl,
html.dark .dept-ring-lbl { color: #94a3b8 !important; }
html.dark .dept-action-arrow { color: #475569 !important; }
html.dark .dept-task-row { background: #1a2438 !important; border-color: #334155 !important; }
html.dark .dept-task-row:hover,
html.dark .dept-table-row:hover { background: #1e3a5f !important; }
html.dark .dept-task-title { color: #f1f5f9 !important; }
html.dark .dept-table th { background: #0f172a !important; color: #94a3b8 !important; border-color: #334155 !important; }
html.dark .dept-table td { border-color: #1e293b !important; color: #e2e8f0 !important; }
html.dark .dept-badge-neutral { background: #334155 !important; color: #e2e8f0 !important; }
html.dark .dept-request-type-label,
html.dark .dept-request-agent { color: #e2e8f0 !important; }
html.dark .dept-request-desc { color: #94a3b8 !important; }
html.dark .dept-conge-stat-card { background: #0f172a !important; }
html.dark .dept-metric-bar { background: #334155 !important; }
html.dark .dept-task-global-bar-track,
html.dark .dept-prog-track { background: #334155 !important; }
html.dark .dept-recent-tasks-head { color: #e2e8f0 !important; border-color: #334155 !important; }
html.dark .dept-error-banner { background: #1e293b !important; border-color: #475569 !important; color: #fbbf24 !important; }
html.dark .dept-error-banner.dept-error-info { background: #1e3a5f !important; border-color: #3b82f6 !important; color: #93c5fd !important; }

/* ──────────────────────────────────────────────────────────
   Responsive
────────────────────────────────────────────────────────── */
@media (max-width: 1150px) {
  .dept-hero-inner { flex-direction: column; align-items: flex-start; }
  .dept-hero-kpis { width: 100%; justify-content: space-between; flex-wrap: nowrap; }
  .kpi-divider { display: none; }
  .dept-kpi-pill { flex: 1; min-width: 0; }
}
@media (max-width: 1024px) {
  .dept-duo-row { grid-template-columns: 1fr; }
  .dept-task-cards { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .dept-hero { border-radius: 16px; margin-bottom: 1.2rem; }
  .dept-hero-inner { flex-direction: column; align-items: flex-start; padding: 1.25rem 1rem; gap: 1rem; }
  .dept-hero-left { gap: .85rem; }
  h1.dept-hero-name { font-size: 1.2rem; }
  .dept-hero-kpis { width: 100%; display: grid; grid-template-columns: repeat(2, 1fr); gap: .4rem; }
  .dept-kpi-pill { min-width: 0; }
  .kpi-divider { display: none; }
  .dept-section-head { flex-wrap: wrap; gap: .5rem; }
  .dept-section-link, .dept-section-link-btn { margin-left: 0; width: 100%; text-align: right; }
  .dept-actions { grid-template-columns: repeat(2, 1fr); }
  .dept-action-desc { display: none; }
  .dept-metrics { grid-template-columns: repeat(2, 1fr); }
  .dept-task-cards { grid-template-columns: repeat(2, 1fr); }
  .dept-requests-grid { grid-template-columns: 1fr; }
  .dept-table th:nth-child(2), .dept-table td:nth-child(2) { display: none; }
  .dept-table th:nth-child(5), .dept-table td:nth-child(5) { display: none; }
}
@media (max-width: 480px) {
  .dept-hero-avatar { width: 52px; height: 52px; }
  .dept-hero-avatar-initials { font-size: 1.1rem; }
  h1.dept-hero-name { font-size: 1.05rem; }
  .dept-hero-role-pill { font-size: .7rem; padding: .2rem .55rem; }
  .dept-kpi-pill-val { font-size: 1.1rem; }
  .dept-kpi-pill-icon { width: 30px; height: 30px; font-size: .85rem; }
  .dept-metrics { grid-template-columns: 1fr; }
  .dept-task-cards { grid-template-columns: repeat(2, 1fr); }
  .dept-error-banner { margin-bottom: .75rem; }
  .dept-actions { grid-template-columns: 1fr; }
  .dept-action-desc { display: block; }
}

/* ─── DRILL-DOWN PANEL ─────────────────────────────────────────────── */
.drill-overlay { position: fixed; inset: 0; z-index: 1100; background: rgba(15,23,42,.55); backdrop-filter: blur(4px); display: flex; align-items: stretch; justify-content: flex-end; }
.drill-panel { width: min(520px, 100vw); background: #f8fafc; display: flex; flex-direction: column; box-shadow: -8px 0 40px rgba(0,0,0,.2); animation: drillSlideIn .25s ease-out; }
@keyframes drillSlideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
.drill-header { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.25rem; color: #fff; }
.drill-header-left { display: flex; align-items: center; gap: .9rem; }
.drill-header-icon { width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.drill-header-title { font-size: .98rem; font-weight: 700; line-height: 1.2; }
.drill-header-sub { font-size: .75rem; opacity: .8; margin-top: .15rem; }
.drill-close-btn { width: 34px; height: 34px; border-radius: 9px; border: none; background: rgba(255,255,255,.18); color: #fff; font-size: .9rem; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: background .2s; }
.drill-close-btn:hover { background: rgba(255,255,255,.3); }
.drill-tabs { display: flex; border-bottom: 2px solid #f1f5f9; background: #fff; }
.drill-tab { flex: 1; padding: .65rem .5rem; border: none; background: none; font-size: .78rem; font-weight: 600; color: #94a3b8; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all .2s; }
.drill-tab.active { color: #0077B5; border-bottom-color: #0077B5; }
.drill-body { flex: 1; overflow-y: auto; padding: 1rem; }
.drill-loading { display: flex; align-items: center; justify-content: center; padding: 2rem; gap: .5rem; color: #94a3b8; }
.drill-summary { display: flex; align-items: center; gap: .5rem; background: #fff; border-radius: 12px; padding: .75rem 1rem; margin-bottom: 1rem; border: 1px solid #e2e8f0; }
.drill-summary-item { flex: 1; text-align: center; }
.drill-summary-val { font-size: 1.2rem; font-weight: 700; color: #1e293b; }
.drill-summary-lbl { font-size: .68rem; color: #94a3b8; margin-top: .1rem; }
.drill-summary-sep { width: 1px; height: 28px; background: #e2e8f0; }
.drill-agent-list { display: flex; flex-direction: column; gap: .5rem; }
.drill-agent-row { display: flex; align-items: center; gap: .75rem; padding: .65rem .8rem; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0; transition: background .15s; }
.drill-agent-row:hover { background: #f0f7ff; }
.drill-agent-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; overflow: hidden; }
.drill-agent-avatar img { width: 100%; height: 100%; object-fit: cover; }
.drill-agent-info { flex: 1; min-width: 0; }
.drill-agent-name { font-size: .84rem; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.drill-agent-fonction { font-size: .72rem; color: #94a3b8; margin-top: .1rem; }
.drill-agent-meta { display: flex; flex-direction: column; align-items: flex-end; gap: .2rem; flex-shrink: 0; }
.drill-agent-statut { font-size: .68rem; font-weight: 600; padding: .2rem .5rem; border-radius: 6px; }
.statut-actif, .statut-disponible { background: #dcfce7; color: #16a34a; }
.statut-en_conge { background: #cffafe; color: #0891b2; }
.statut-en_mission { background: #fef3c7; color: #d97706; }
.statut-suspendu { background: #fee2e2; color: #dc2626; }
.statut-en_formation { background: #ede9fe; color: #7c3aed; }
.drill-agent-tasks { font-size: .72rem; color: #64748b; }
.drill-presence-bar-wrap { display: flex; align-items: center; gap: .4rem; }
.drill-presence-bar { width: 60px; height: 6px; border-radius: 4px; background: #e2e8f0; overflow: hidden; }
.drill-presence-bar-fill { height: 100%; border-radius: 4px; transition: width .4s; }
.drill-presence-pct { font-size: .72rem; font-weight: 600; color: #475569; }
.drill-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; color: #94a3b8; font-size: .84rem; }
@media (max-width: 480px) {
  .drill-panel { width: 100vw; border-radius: 16px 16px 0 0; }
  .drill-overlay { align-items: flex-end; }
}
</style>
