<template>
  <div class="sena-dashboard">

    <!-- ════════════════════ HERO ════════════════════ -->
    <div class="sena-hero">
      <div class="sena-hero-bg"></div>
      <div class="sena-hero-inner">

        <!-- Gauche : Avatar + identité -->
        <div class="sena-hero-left">
          <div class="sena-hero-avatar">
            <img v-if="senaPhotoUrl" :src="senaPhotoUrl" :alt="senaFullName"
              class="sena-hero-avatar-photo" @error="handlePhotoError">
            <span v-else class="sena-hero-avatar-initials">{{ senaInitials }}</span>
          </div>
          <div class="sena-hero-text">
            <div class="sena-hero-greeting">{{ senaGreeting }},</div>
            <h1 class="sena-hero-name">{{ senaCivility }} {{ senaFullName }}</h1>
            <div class="sena-hero-fonction" v-if="senaFonction">
              <i class="fas fa-id-badge me-1"></i>{{ senaFonction }}
            </div>
            <div class="sena-hero-role-pill">
              <i class="fas fa-star me-1"></i>
              Secretariat de Direction
              <span v-if="data?.scope_label === 'sena'" class="sena-hero-scope-badge">
                {{ data?.managed_agents_count ?? 0 }} agents cibles
              </span>
            </div>
            <div class="sena-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>

        <!-- Droite : KPIs -->
        <div class="sena-hero-kpis">
          <div class="sena-kpi-pill" @click="openSenaDrill('taches')">
            <div class="sena-kpi-pill-icon"><i class="fas fa-tasks"></i></div>
            <div>
              <div class="sena-kpi-pill-val">{{ myTasksEnCours }}</div>
              <div class="sena-kpi-pill-lbl">Mes tâches</div>
            </div>
            <i class="fas fa-chevron-right sena-kpi-pill-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="sena-kpi-pill" @click="router.push('/requests')">
            <div class="sena-kpi-pill-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="sena-kpi-pill-val">{{ data?.pending_requests?.length ?? 0 }}</div>
              <div class="sena-kpi-pill-lbl">À signer</div>
            </div>
            <i class="fas fa-chevron-right sena-kpi-pill-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="sena-kpi-pill" @click="openSenaDrill('presence')">
            <div class="sena-kpi-pill-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="sena-kpi-pill-val">{{ data?.attendance?.today_rate ?? 0 }}<span style="font-size:.7rem;">%</span></div>
              <div class="sena-kpi-pill-lbl">{{ data?.scope_label === 'sena' ? 'Présence périmètre' : 'Présence SEN' }}</div>
            </div>
            <i class="fas fa-chevron-right sena-kpi-pill-arrow"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="sena-kpi-pill" @click="router.push('/signalements')">
            <div class="sena-kpi-pill-icon"><i class="fas fa-flag"></i></div>
            <div>
              <div class="sena-kpi-pill-val">{{ data?.signalements?.length ?? 0 }}</div>
              <div class="sena-kpi-pill-lbl">Signalements</div>
            </div>
            <i class="fas fa-chevron-right sena-kpi-pill-arrow"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- ════════ Loading / Error ════════ -->
    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement du tableau de bord..." />
    </div>
    <div v-else-if="loadError" class="alert alert-warning mx-3 mt-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <template v-else>
      <div class="sena-body">

        <!-- ─── MES TÂCHES ─────────────────────────────────── -->
        <div class="sena-section">
          <div class="sena-section-header">
            <div class="sena-section-icon" style="background:#e0f2fe;color:#0077B5;">
              <i class="fas fa-tasks"></i>
            </div>
            <div>
              <h3 class="sena-section-title">Mes tâches</h3>
              <p class="sena-section-sub">{{ data.my_tasks?.length ?? 0 }} tâche(s) assignée(s)</p>
            </div>
            <router-link to="/taches" class="sena-section-btn ms-auto">
              Tout voir <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
          <div v-if="!data.my_tasks?.length" class="sena-empty">
            <i class="fas fa-check-circle"></i> Aucune tâche en cours.
          </div>
          <div v-else class="sena-tasks-list">
            <div v-for="t in data.my_tasks" :key="t.id"
              class="sena-task-row"
              :class="isTaskOverdue(t) ? 'sena-task-overdue' : ''">
              <div class="sena-task-left">
                <span class="sena-task-badge" :class="taskStatutClass(t.statut)">
                  {{ taskStatutLabel(t.statut) }}
                </span>
                <router-link :to="`/taches/${t.id}`" class="sena-task-title">{{ t.titre }}</router-link>
              </div>
              <div class="sena-task-right">
                <div class="sena-task-progress">
                  <div class="sena-task-progress-bar" :style="{ width: (t.pourcentage ?? 0) + '%', background: taskProgressColor(t.pourcentage) }"></div>
                </div>
                <span class="sena-task-pct">{{ t.pourcentage ?? 0 }}%</span>
                <span v-if="t.date_echeance" class="sena-task-due" :class="isTaskOverdue(t) ? 'sena-due-urgent' : ''">
                  <i class="fas fa-clock me-1"></i>{{ fmtDate(t.date_echeance) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ─── AGENDA : ÉCHÉANCES À VENIR ─────────────────── -->
        <div class="sena-section">
          <div class="sena-section-header">
            <div class="sena-section-icon" style="background:#fef9c3;color:#ca8a04;">
              <i class="fas fa-calendar-check"></i>
            </div>
            <div>
              <h3 class="sena-section-title">Agenda — Échéances à venir</h3>
              <p class="sena-section-sub">
                {{ data?.scope_label === 'sena'
                  ? 'Tâches de votre périmètre avec échéance dans les 7 prochains jours'
                  : 'Tâches SEN avec échéance dans les 7 prochains jours' }}
              </p>
            </div>
          </div>
          <div v-if="!data.upcoming_deadlines?.length" class="sena-empty">
            <i class="fas fa-calendar-check"></i> Aucune échéance imminente.
          </div>
          <div v-else class="sena-agenda-list">
            <div v-for="t in data.upcoming_deadlines" :key="t.id"
              class="sena-agenda-row"
              :class="daysUntil(t.date_echeance) <= 1 ? 'sena-agenda-urgent' : ''">
              <div class="sena-agenda-date-block">
                <div class="sena-agenda-day">{{ agendaDay(t.date_echeance) }}</div>
                <div class="sena-agenda-month">{{ agendaMonth(t.date_echeance) }}</div>
              </div>
              <div class="sena-agenda-info">
                <router-link :to="`/taches/${t.id}`" class="sena-agenda-title">{{ t.titre }}</router-link>
                <div class="sena-agenda-meta">
                  <span class="sena-agenda-agent" v-if="t.agent">
                    <i class="fas fa-user-circle me-1"></i>{{ t.agent.prenom }} {{ t.agent.nom }}
                  </span>
                </div>
              </div>
              <div class="sena-agenda-badge"
                :class="daysUntil(t.date_echeance) <= 1 ? 'sena-agenda-badge-urgent' : 'sena-agenda-badge-normal'">
                {{ daysUntil(t.date_echeance) === 0 ? "Aujourd'hui" : daysUntil(t.date_echeance) === 1 ? 'Demain' : `J-${daysUntil(t.date_echeance)}` }}
              </div>
            </div>
          </div>
        </div>

        <!-- ─── DEMANDES EN ATTENTE DE SIGNATURE ───────────── -->
        <div class="sena-section">
          <div class="sena-section-header">
            <div class="sena-section-icon" style="background:#fce7f3;color:#db2777;">
              <i class="fas fa-file-signature"></i>
            </div>
            <div>
              <h3 class="sena-section-title">Demandes en attente de signature</h3>
              <p class="sena-section-sub">{{ data.pending_requests?.length ?? 0 }} demande(s) à traiter</p>
            </div>
            <router-link to="/requests" class="sena-section-btn ms-auto">
              Tout voir <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
          <div v-if="!data.pending_requests?.length" class="sena-empty">
            <i class="fas fa-inbox"></i> Aucune demande en attente.
          </div>
          <div v-else class="sena-requests-list">
            <div v-for="r in data.pending_requests" :key="r.id" class="sena-request-row">
              <div class="sena-request-agent" v-if="r.agent">
                <div class="sena-request-avatar-wrap">
                  <img v-if="r.agent.photo" :src="`/${r.agent.photo.replace(/^\//, '')}`"
                    class="sena-request-avatar-photo" @error="(e) => e.target.style.display='none'">
                  <span v-else class="sena-request-avatar-initials">
                    {{ (r.agent.prenom?.[0] ?? '') + (r.agent.nom?.[0] ?? '') }}
                  </span>
                </div>
                <span class="sena-request-agent-name">{{ r.agent.prenom }} {{ r.agent.nom }}</span>
              </div>
              <div class="sena-request-type">{{ requestTypeLabel(r.type) }}</div>
              <div class="sena-request-steps">
                <span class="sena-step" :class="r.validated_at_director ? 'sena-step-done' : 'sena-step-pending'" title="Directeur">
                  <i class="fas fa-user-tie"></i>
                </span>
                <span class="sena-step-line"></span>
                <span class="sena-step" :class="r.validated_at_rh ? 'sena-step-done' : 'sena-step-pending'" title="RH">
                  <i class="fas fa-id-card"></i>
                </span>
                <span class="sena-step-line"></span>
                <span class="sena-step" :class="r.validated_at_sen ? 'sena-step-done' : 'sena-step-active'" title="SEN/SENA">
                  <i class="fas fa-stamp"></i>
                </span>
              </div>
              <div class="sena-request-date">{{ fmtDate(r.created_at) }}</div>
              <router-link :to="`/requests/${r.id}`" class="sena-request-btn">
                <i class="fas fa-eye"></i>
              </router-link>
            </div>
          </div>
        </div>

        <!-- ─── PRÉSENCE DU JOUR ────────────────────────────── -->
        <div class="sena-section sena-section-split">
          <div class="sena-presence-card">
            <div class="sena-section-header">
              <div class="sena-section-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="fas fa-user-check"></i>
              </div>
              <div>
                <h3 class="sena-section-title">Présence du jour</h3>
                <p class="sena-section-sub">{{ data?.scope_label === 'sena' ? 'Agents suivis' : 'Agents SEN' }} · {{ today }}</p>
              </div>
              <button type="button" class="sena-section-btn ms-auto" @click="openSenaDrill('presence')">
                Détail <i class="fas fa-arrow-right ms-1"></i>
              </button>
            </div>
            <div class="sena-presence-stats">
              <div class="sena-presence-big">
                <div class="sena-presence-ring-wrap">
                  <svg class="sena-ring-svg" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                    <circle cx="60" cy="60" r="50" fill="none" stroke="#16a34a" stroke-width="10"
                      stroke-dasharray="314"
                      :stroke-dashoffset="314 - (314 * (data.attendance?.today_rate ?? 0) / 100)"
                      stroke-linecap="round" transform="rotate(-90 60 60)"/>
                  </svg>
                  <div class="sena-ring-center">
                    <div class="sena-ring-val">{{ data.attendance?.today_rate ?? 0 }}%</div>
                    <div class="sena-ring-lbl">Présents</div>
                  </div>
                </div>
              </div>
              <div class="sena-presence-items">
                <div class="sena-presence-item">
                  <i class="fas fa-users" style="color:#0077B5;"></i>
                  <span>{{ data.attendance?.total_actifs ?? 0 }} agents actifs</span>
                </div>
                <div class="sena-presence-item">
                  <i class="fas fa-user-check" style="color:#16a34a;"></i>
                  <span>{{ data.attendance?.today_present ?? 0 }} présents aujourd'hui</span>
                </div>
                <div class="sena-presence-item">
                  <i class="fas fa-calendar-week" style="color:#7c3aed;"></i>
                  <span>{{ data.attendance?.monthly_rate ?? 0 }}% taux mensuel</span>
                </div>
              </div>
            </div>
          </div>

          <!-- ─── PTA GLOBAL SEN ──────────────────────────── -->
          <div class="sena-pta-card">
            <div class="sena-section-header">
              <div class="sena-section-icon" style="background:#ede9fe;color:#7c3aed;">
                <i class="fas fa-chart-pie"></i>
              </div>
              <div>
                <h3 class="sena-section-title">{{ data?.scope_label === 'sena' ? 'PTA — Avancement du périmètre' : 'PTA — Avancement SEN' }}</h3>
                <p class="sena-section-sub">{{ currentYear }} · {{ data.pta?.total ?? 0 }} activité(s)</p>
              </div>
              <button type="button" class="sena-section-btn ms-auto" @click="openSenaDrill('pta')">
                Détail <i class="fas fa-arrow-right ms-1"></i>
              </button>
            </div>
            <div class="sena-pta-progress-wrap">
              <div class="sena-pta-big-pct">{{ data.pta?.avg_completion ?? 0 }}<span class="sena-pta-pct-unit">%</span></div>
              <div class="sena-pta-bar-wrap">
                <div class="sena-pta-bar">
                  <div class="sena-pta-bar-fill" :style="{ width: (data.pta?.avg_completion ?? 0) + '%' }"></div>
                </div>
                <div class="sena-pta-pct-label">Avancement moyen</div>
              </div>
            </div>
            <div class="sena-pta-pills">
              <div class="sena-pta-pill sena-pta-pill-done">
                <span>{{ data.pta?.terminee ?? 0 }}</span> Terminées
              </div>
              <div class="sena-pta-pill sena-pta-pill-wip">
                <span>{{ data.pta?.en_cours ?? 0 }}</span> En cours
              </div>
              <div class="sena-pta-pill sena-pta-pill-new">
                <span>{{ data.pta?.nouvelle ?? 0 }}</span> Nouvelles
              </div>
            </div>
            <router-link to="/plan-travail" class="sena-section-btn mt-2 d-inline-flex">
              Voir le PTA <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
        </div>

        <!-- ─── COMMUNIQUÉS RÉCENTS ─────────────────────────── -->
        <div class="sena-section">
          <div class="sena-section-header">
            <div class="sena-section-icon" style="background:#fff7ed;color:#ea580c;">
              <i class="fas fa-bullhorn"></i>
            </div>
            <div>
              <h3 class="sena-section-title">Communiqués récents</h3>
              <p class="sena-section-sub">Dernières publications actives</p>
            </div>
            <router-link to="/communiques" class="sena-section-btn ms-auto">
              Tout voir <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
          <div v-if="!data.communiques?.length" class="sena-empty">
            <i class="fas fa-newspaper"></i> Aucun communiqué récent.
          </div>
          <div v-else class="sena-communiques-list">
            <router-link v-for="c in data.communiques" :key="c.id"
              :to="`/communiques/${c.id}`" class="sena-communique-row">
              <div class="sena-communique-urgence" :class="c.urgence ? 'sena-urgence-high' : 'sena-urgence-normal'">
                <i class="fas" :class="c.urgence ? 'fa-exclamation' : 'fa-info'"></i>
              </div>
              <div class="sena-communique-info">
                <div class="sena-communique-titre">{{ c.titre }}</div>
                <div class="sena-communique-meta">
                  <span v-if="c.signataire">{{ c.signataire }}</span>
                  <span class="sena-communique-date">{{ fmtDate(c.created_at) }}</span>
                </div>
              </div>
              <i class="fas fa-chevron-right sena-communique-arrow"></i>
            </router-link>
          </div>
        </div>

        <!-- ─── SIGNALEMENTS EN COURS ──────────────────────── -->
        <div class="sena-section">
          <div class="sena-section-header">
            <div class="sena-section-icon" style="background:#fee2e2;color:#dc2626;">
              <i class="fas fa-flag"></i>
            </div>
            <div>
              <h3 class="sena-section-title">Signalements en cours</h3>
              <p class="sena-section-sub">{{ data.signalements?.length ?? 0 }} signalement(s) actif(s)</p>
            </div>
            <router-link to="/signalements" class="sena-section-btn ms-auto">
              Tout voir <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
          <div v-if="!data.signalements?.length" class="sena-empty">
            <i class="fas fa-check-circle"></i> Aucun signalement actif.
          </div>
          <div v-else class="sena-signalements-list">
            <router-link v-for="s in data.signalements" :key="s.id"
              :to="`/signalements/${s.id}`" class="sena-signalement-row">
              <div class="sena-signalement-severite"
                :class="`sena-sev-${s.severite ?? 'normale'}`">
                <i class="fas fa-flag"></i>
              </div>
              <div class="sena-signalement-info">
                <div class="sena-signalement-type">{{ signalementTypeLabel(s.type) }}</div>
                <div class="sena-signalement-meta">
                  <span class="sena-signalement-statut" :class="`sena-stat-${s.statut}`">{{ s.statut }}</span>
                  <span class="sena-signalement-date">{{ fmtDate(s.created_at) }}</span>
                  <span v-if="s.is_anonymous" class="sena-signalement-anon"><i class="fas fa-user-secret"></i> Anonyme</span>
                </div>
              </div>
              <i class="fas fa-chevron-right sena-signalement-arrow"></i>
            </router-link>
          </div>
        </div>

      </div><!-- /.sena-body -->
    </template>

    <Teleport to="body">
      <div v-if="senaDrillOpen" class="sena-drill-overlay" @click.self="closeSenaDrill">
        <aside class="sena-drill-panel" role="dialog" aria-modal="true">
          <div class="sena-drill-header">
            <div class="sena-drill-header-left">
              <div class="sena-drill-header-icon">
                <i class="fas" :class="senaDrillIcon"></i>
              </div>
              <div>
                <div class="sena-drill-title">{{ senaDrillTitle }}</div>
                <div class="sena-drill-sub">{{ data?.scope_label === 'sena' ? 'Secrétariat de Direction' : 'SEN' }} · {{ today }}</div>
              </div>
            </div>
            <button type="button" class="sena-drill-close" aria-label="Fermer" @click="closeSenaDrill">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="sena-drill-body">
            <div class="sena-drill-tabs">
              <button type="button" class="sena-drill-tab" :class="{ active: senaDrillSection === 'taches' }" @click="setSenaDrillSection('taches')">
                <i class="fas fa-tasks"></i> Tâches
              </button>
              <button type="button" class="sena-drill-tab" :class="{ active: senaDrillSection === 'presence' }" @click="setSenaDrillSection('presence')">
                <i class="fas fa-user-check"></i> Présence
              </button>
              <button type="button" class="sena-drill-tab" :class="{ active: senaDrillSection === 'pta' }" @click="setSenaDrillSection('pta')">
                <i class="fas fa-chart-pie"></i> PTA
              </button>
            </div>

            <template v-if="senaDrillSection === 'taches'">
              <div class="sena-drill-stat-grid">
                <div class="sena-drill-stat-card" style="border-color:#0077B5;">
                  <div class="sena-drill-stat-val">{{ data.my_tasks?.length ?? 0 }}</div>
                  <div class="sena-drill-stat-lbl">Mes tâches</div>
                </div>
                <div class="sena-drill-stat-card" style="border-color:#d97706;">
                  <div class="sena-drill-stat-val">{{ myTasksEnCours }}</div>
                  <div class="sena-drill-stat-lbl">En cours</div>
                </div>
                <div class="sena-drill-stat-card" style="border-color:#dc2626;">
                  <div class="sena-drill-stat-val">{{ overdueTasksCount }}</div>
                  <div class="sena-drill-stat-lbl">En retard</div>
                </div>
                <div class="sena-drill-stat-card" style="border-color:#7c3aed;">
                  <div class="sena-drill-stat-val">{{ data.upcoming_deadlines?.length ?? 0 }}</div>
                  <div class="sena-drill-stat-lbl">Échéances</div>
                </div>
              </div>
              <div class="sena-drill-section-title">
                <i class="fas fa-list-check"></i> Dernières tâches
              </div>
              <div v-if="data.my_tasks?.length" class="sena-drill-list">
                <router-link v-for="t in data.my_tasks" :key="t.id" :to="`/taches/${t.id}`" class="sena-drill-row" @click="closeSenaDrill">
                  <div class="sena-drill-row-icon" :class="isTaskOverdue(t) ? 'danger' : ''">
                    <i class="fas" :class="isTaskOverdue(t) ? 'fa-exclamation-triangle' : 'fa-tasks'"></i>
                  </div>
                  <div class="sena-drill-row-info">
                    <div class="sena-drill-row-title">{{ t.titre }}</div>
                    <div class="sena-drill-row-sub">{{ taskStatutLabel(t.statut) }} · {{ t.date_echeance ? fmtDate(t.date_echeance) : 'Sans échéance' }}</div>
                  </div>
                  <div class="sena-drill-progress">
                    <div class="sena-drill-progress-bar" :style="{ width: (t.pourcentage ?? 0) + '%', background: taskProgressColor(t.pourcentage) }"></div>
                  </div>
                </router-link>
              </div>
              <div v-else class="sena-drill-empty">
                <i class="fas fa-check-circle"></i>
                <span>Aucune tâche à afficher.</span>
              </div>
            </template>

            <template v-else-if="senaDrillSection === 'presence'">
              <div class="sena-drill-stat-grid">
                <button type="button" class="sena-drill-stat-card sena-drill-clickable" :class="{ active: senaPresenceFilter === 'present' }" style="border-color:#059669;" @click="setSenaPresenceFilter('present')">
                  <div class="sena-drill-stat-val">{{ data.attendance?.today_present ?? 0 }}</div>
                  <div class="sena-drill-stat-lbl">Présents</div>
                </button>
                <button type="button" class="sena-drill-stat-card sena-drill-clickable" :class="{ active: senaPresenceFilter === 'absent' }" style="border-color:#d97706;" @click="setSenaPresenceFilter('absent')">
                  <div class="sena-drill-stat-val">{{ senaAbsentCount }}</div>
                  <div class="sena-drill-stat-lbl">Absents</div>
                </button>
                <button type="button" class="sena-drill-stat-card sena-drill-clickable" :class="{ active: senaPresenceFilter === 'all' }" style="border-color:#0077B5;" @click="setSenaPresenceFilter('all')">
                  <div class="sena-drill-stat-val">{{ data.attendance?.today_rate ?? 0 }}%</div>
                  <div class="sena-drill-stat-lbl">Taux jour</div>
                </button>
                <button type="button" class="sena-drill-stat-card sena-drill-clickable" :class="{ active: senaPresenceFilter === 'all' }" style="border-color:#7c3aed;" @click="setSenaPresenceFilter('all')">
                  <div class="sena-drill-stat-val">{{ data.attendance?.monthly_rate ?? 0 }}%</div>
                  <div class="sena-drill-stat-lbl">Moy. mois</div>
                </button>
              </div>
              <div class="sena-drill-section-title">
                <i class="fas fa-user-check"></i> {{ senaPresenceFilterTitle(filteredSenaAttendanceAgents.length) }}
              </div>
              <div v-if="filteredSenaAttendanceAgents.length" class="sena-drill-list">
                <div v-for="a in filteredSenaAttendanceAgents" :key="a.id" class="sena-drill-agent-row">
                  <div class="sena-drill-avatar">
                    <img v-if="a.photo" :src="agentPhotoUrl(a.photo)" :alt="a.prenom" @error="e => e.target.style.display='none'">
                    <span v-else>{{ senaAgentInitials(a) }}</span>
                  </div>
                  <div class="sena-drill-row-info">
                    <div class="sena-drill-row-title">{{ a.prenom }} {{ a.nom }}</div>
                    <div class="sena-drill-row-sub">{{ a.fonction || 'Fonction non renseignée' }}</div>
                    <div class="sena-drill-presence-note" :class="isSenaPresent(a) ? 'present' : 'absent'">
                      <i class="fas" :class="isSenaPresent(a) ? 'fa-check-circle' : 'fa-clock'"></i>
                      {{ isSenaPresent(a) ? `Pointé ${a.heure_entree || ''}` : 'Non pointé aujourd’hui' }}
                    </div>
                  </div>
                  <div class="sena-drill-agent-meta">
                    <span>{{ a.taux_presence ?? 0 }}%</span>
                    <small>{{ a.jours_presents ?? 0 }}j ce mois</small>
                  </div>
                </div>
              </div>
              <div v-else class="sena-drill-empty">
                <i class="fas fa-user-check"></i>
                <span>Aucun agent dans ce filtre.</span>
              </div>
            </template>

            <template v-else>
              <div class="sena-drill-stat-grid">
                <div class="sena-drill-stat-card" style="border-color:#7c3aed;">
                  <div class="sena-drill-stat-val">{{ data.pta?.total ?? 0 }}</div>
                  <div class="sena-drill-stat-lbl">Activités</div>
                </div>
                <div class="sena-drill-stat-card" style="border-color:#059669;">
                  <div class="sena-drill-stat-val">{{ data.pta?.terminee ?? 0 }}</div>
                  <div class="sena-drill-stat-lbl">Terminées</div>
                </div>
                <div class="sena-drill-stat-card" style="border-color:#d97706;">
                  <div class="sena-drill-stat-val">{{ data.pta?.en_cours ?? 0 }}</div>
                  <div class="sena-drill-stat-lbl">En cours</div>
                </div>
                <div class="sena-drill-stat-card" style="border-color:#0077B5;">
                  <div class="sena-drill-stat-val">{{ data.pta?.avg_completion ?? 0 }}%</div>
                  <div class="sena-drill-stat-lbl">Avancement</div>
                </div>
              </div>
              <div class="sena-drill-section-title">
                <i class="fas fa-clipboard-list"></i> Activités PTA {{ currentYear }}
              </div>
              <div v-if="data.pta_activities?.length" class="sena-drill-list">
                <div v-for="act in data.pta_activities" :key="act.id" class="sena-drill-activity">
                  <div class="sena-drill-activity-pct" :style="{ color: taskProgressColor(act.pourcentage) }">{{ act.pourcentage ?? 0 }}%</div>
                  <div class="sena-drill-row-info">
                    <div class="sena-drill-row-title">{{ act.titre }}</div>
                    <div class="sena-drill-row-sub">
                      <span v-if="act.categorie">{{ act.categorie }}</span>
                      <span v-if="act.departement"> · {{ act.departement.nom }}</span>
                      <span v-if="act.trimestre"> · {{ act.trimestre }}</span>
                    </div>
                    <div class="sena-drill-progress wide">
                      <div class="sena-drill-progress-bar" :style="{ width: Math.min(act.pourcentage ?? 0, 100) + '%', background: taskProgressColor(act.pourcentage) }"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="sena-drill-empty">
                <i class="fas fa-clipboard"></i>
                <span>Aucune activité PTA à afficher.</span>
              </div>
            </template>
          </div>
        </aside>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router  = useRouter()
const auth    = useAuthStore()
const loading = ref(true)
const loadError = ref(null)
const data    = ref({})
const currentYear = new Date().getFullYear()
const senaDrillOpen = ref(false)
const senaDrillSection = ref('presence')
const senaPresenceFilter = ref('all')

const senaDrillTitle = computed(() => ({
  taches: 'Suivi des tâches',
  presence: 'Présence du périmètre',
  pta: 'Plan de travail annuel',
}[senaDrillSection.value] ?? 'Détail')
)
const senaDrillIcon = computed(() => ({
  taches: 'fa-tasks',
  presence: 'fa-user-check',
  pta: 'fa-chart-pie',
}[senaDrillSection.value] ?? 'fa-chart-line')
)

// ─── Identity helpers ──────────────────────────────────────
const senaIsFemme = computed(() => {
  const s = (auth.agent?.sexe ?? '').toLowerCase()
  return s === 'f' || s === 'femme' || s === 'féminin'
})
const senaCivility  = computed(() => auth.agent ? (senaIsFemme.value ? 'Mme' : 'M.') : '')
const senaGreeting  = computed(() => senaIsFemme.value ? 'Bienvenue' : 'Bienvenu')
const senaFullName  = computed(() => auth.agent ? `${auth.agent.prenom || ''} ${auth.agent.nom || ''}`.trim() : (auth.user?.name || 'SENA'))
const senaFonction  = computed(() => auth.agent?.fonction || auth.agent?.poste_actuel || null)
const senaInitials  = computed(() => {
  const a = auth.agent
  if (!a) return 'S'
  return ((a.prenom?.[0] ?? '') + (a.nom?.[0] ?? '')).toUpperCase() || 'S'
})

const photoIndex = ref(0)
const senaPhotoUrl = computed(() => {
  const photo = auth.agent?.photo
  if (!photo) return null
  const p = photo.trim()
  const candidates = []
  if (/^https?:\/\//i.test(p)) {
    candidates.push(p)
  } else {
    const n = p.replace(/^\/+/, '')
    candidates.push(`/${n}`)
    candidates.push(`/storage/${n}`)
    candidates.push(`/uploads/${n}`)
  }
  return candidates[photoIndex.value] ?? null
})
function handlePhotoError() {
  photoIndex.value++
}

const today = computed(() => new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
}))

// ─── KPI computed ──────────────────────────────────────────
const myTasksEnCours = computed(() =>
  (data.value.my_tasks ?? []).filter(t => t.statut !== 'terminee').length
)
const overdueTasksCount = computed(() =>
  (data.value.my_tasks ?? []).filter(isTaskOverdue).length
)
const senaAttendanceAgents = computed(() => data.value.attendance_agents ?? [])
const senaAbsentCount = computed(() =>
  Math.max((data.value.attendance?.total_actifs ?? 0) - (data.value.attendance?.today_present ?? 0), 0)
)
const filteredSenaAttendanceAgents = computed(() => {
  if (senaPresenceFilter.value === 'present') {
    return senaAttendanceAgents.value.filter(isSenaPresent)
  }
  if (senaPresenceFilter.value === 'absent') {
    return senaAttendanceAgents.value.filter(a => !isSenaPresent(a))
  }
  return senaAttendanceAgents.value
})

function openSenaDrill(section = 'presence') {
  setSenaDrillSection(section)
  senaDrillOpen.value = true
}
function closeSenaDrill() {
  senaDrillOpen.value = false
  senaPresenceFilter.value = 'all'
}
function setSenaDrillSection(section = 'presence') {
  senaDrillSection.value = section
  if (section !== 'presence') senaPresenceFilter.value = 'all'
}
function setSenaPresenceFilter(filter = 'all') {
  senaPresenceFilter.value = filter
}
function isSenaPresent(agent) {
  return agent?.presence_status === 'present'
}
function senaPresenceFilterTitle(total = 0) {
  const labels = {
    all: 'Tous les agents',
    present: 'Agents présents aujourd’hui',
    absent: 'Agents non pointés aujourd’hui',
  }
  return `${labels[senaPresenceFilter.value] ?? labels.all} (${total})`
}
function senaAgentInitials(agent) {
  return ((agent?.prenom?.[0] ?? '') + (agent?.nom?.[0] ?? '')).toUpperCase() || 'S'
}
function agentPhotoUrl(photo) {
  if (!photo) return null
  if (/^https?:\/\//i.test(photo)) return photo
  return '/' + String(photo).replace(/^\/+/, '')
}

// ─── Task helpers ──────────────────────────────────────────
function taskStatutLabel(s) {
  return { nouvelle: 'Nouvelle', en_cours: 'En cours', terminee: 'Terminée', bloquee: 'Bloquée' }[s] ?? s
}
function taskStatutClass(s) {
  return { nouvelle: 'badge-nouvelle', en_cours: 'badge-encours', terminee: 'badge-terminee', bloquee: 'badge-bloquee' }[s] ?? ''
}
function taskProgressColor(pct) {
  if ((pct ?? 0) >= 80) return '#16a34a'
  if ((pct ?? 0) >= 50) return '#d97706'
  return '#3b82f6'
}
function isTaskOverdue(t) {
  if (!t.date_echeance || t.statut === 'terminee') return false
  return new Date(t.date_echeance) < new Date()
}

// ─── Agenda helpers ────────────────────────────────────────
function daysUntil(dateStr) {
  const d = new Date(dateStr)
  const n = new Date(); n.setHours(0,0,0,0); d.setHours(0,0,0,0)
  return Math.max(0, Math.round((d - n) / 86400000))
}
function agendaDay(dateStr)   { return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit' }) }
function agendaMonth(dateStr) { return new Date(dateStr).toLocaleDateString('fr-FR', { month: 'short' }).replace('.','') }

// ─── Request helpers ───────────────────────────────────────
function requestTypeLabel(t) {
  const map = {
    conge: 'Congé', attestation: 'Attestation', avance: 'Avance',
    permission: 'Permission', autre: 'Autre',
  }
  return map[t] ?? t
}

// ─── Signalement helpers ───────────────────────────────────
function signalementTypeLabel(t) {
  const map = {
    harcèlement: 'Harcèlement', fraude: 'Fraude', vol: 'Vol',
    conflit: 'Conflit', autre: 'Autre',
  }
  return map[t] ?? (t ?? 'Signalement')
}

// ─── Date formatter ────────────────────────────────────────
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

// ─── Data fetch ────────────────────────────────────────────
onMounted(async () => {
  try {
    const { data: d } = await client.get('/dashboard/sena')
    data.value = d
  } catch (e) {
    loadError.value = 'Impossible de charger le tableau de bord.'
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* ══════════ LAYOUT ══════════ */
.sena-dashboard { min-height: 100vh; background: #f1f5f9; }
.sena-body { width: 100%; padding: 1.25rem clamp(1rem, 2vw, 2rem); display: flex; flex-direction: column; gap: 1rem; max-width: none; margin: 0; }

/* ══════════ HERO ══════════ */
.sena-hero {
  position: relative; overflow: hidden;
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #0077B5 100%);
  border-radius: 0 0 20px 20px;
}
.sena-hero-bg {
  position: absolute; inset: 0; pointer-events: none;
  background: radial-gradient(ellipse at 80% 40%, rgba(251,191,36,.12) 0%, transparent 55%),
              radial-gradient(ellipse at 20% 80%, rgba(0,119,181,.25) 0%, transparent 50%);
}
.sena-hero-inner {
  position: relative; z-index: 1; padding: 1.75rem 2rem;
  display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;
}
.sena-hero-left { display: flex; align-items: center; gap: 1.1rem; color: #fff; }
.sena-hero-avatar {
  width: 64px; height: 64px; border-radius: 50%; flex-shrink: 0; overflow: hidden;
  border: 2.5px solid rgba(255,255,255,.35);
  box-shadow: 0 4px 16px rgba(0,0,0,.25);
  background: linear-gradient(135deg, rgba(251,191,36,.45), rgba(14,59,111,.6));
  display: flex; align-items: center; justify-content: center;
}
.sena-hero-avatar-photo { width: 100%; height: 100%; object-fit: cover; }
.sena-hero-avatar-initials { font-size: 1.3rem; font-weight: 800; color: #fff; }
.sena-hero-greeting { font-size: .8rem; opacity: .55; font-weight: 500; letter-spacing: .5px; text-transform: uppercase; }
.sena-hero-name { font-size: 1.45rem; font-weight: 800; margin: .1rem 0 .3rem; color: #fff; }
.sena-hero-fonction { font-size: .8rem; color: rgba(255,255,255,.7); margin-bottom: .25rem; }
.sena-hero-role-pill {
  display: inline-flex; align-items: center; gap: .3rem;
  background: rgba(251,191,36,.18); color: #fbbf24;
  font-size: .72rem; font-weight: 700; padding: .25rem .65rem;
  border-radius: 20px; border: 1px solid rgba(251,191,36,.3);
  margin-bottom: .25rem; letter-spacing: .2px;
}
.sena-hero-scope-badge {
  display: inline-flex;
  align-items: center;
  padding: .12rem .42rem;
  border-radius: 999px;
  background: rgba(255,255,255,.14);
  color: #fff7ed;
  border: 1px solid rgba(255,255,255,.18);
  font-size: .62rem;
  font-weight: 700;
}
.sena-hero-date { font-size: .7rem; opacity: .4; text-transform: capitalize; }

/* ─── Hero KPIs ─── */
.sena-hero-kpis {
  display: flex; align-items: center; gap: 0;
  background: rgba(255,255,255,.08); border-radius: 14px;
  border: 1px solid rgba(255,255,255,.15); padding: .5rem .75rem; flex-shrink: 0;
}
.kpi-divider { width: 1px; height: 32px; background: rgba(255,255,255,.15); margin: 0 .5rem; }
.sena-kpi-pill {
  display: flex; align-items: center; gap: .5rem; padding: .4rem .6rem;
  border-radius: 10px; cursor: pointer; transition: background .18s; white-space: nowrap;
}
.sena-kpi-pill:hover { background: rgba(255,255,255,.1); }
.sena-kpi-pill-icon {
  width: 32px; height: 32px; border-radius: 8px;
  background: rgba(255,255,255,.12); color: #fff;
  display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.sena-kpi-pill-val { font-size: 1.15rem; font-weight: 800; color: #fff; line-height: 1; }
.sena-kpi-pill-lbl { font-size: .6rem; color: rgba(255,255,255,.55); font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.sena-kpi-pill-arrow { font-size: .6rem; color: rgba(255,255,255,.35); margin-left: .25rem; }

/* ══════════ SECTIONS ══════════ */
.sena-section {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem 1.3rem; overflow: hidden;
}
.sena-section-split { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: none; border: none; padding: 0; }
.sena-section-split > div { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 1.2rem 1.3rem; }
.sena-section-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.sena-section-icon {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: .9rem;
}
.sena-section-title { font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; }
.sena-section-sub { font-size: .7rem; color: #94a3b8; margin: 0; font-weight: 500; }
.sena-section-btn {
  display: inline-flex; align-items: center; font-size: .72rem; font-weight: 700;
  color: #0077B5; text-decoration: none; padding: .3rem .7rem;
  background: #e0f2fe; border-radius: 8px; transition: background .15s; white-space: nowrap;
}
.sena-section-btn:hover { background: #bae6fd; color: #0077B5; }
.sena-empty { text-align: center; padding: 1.5rem; color: #94a3b8; font-size: .85rem; }
.sena-empty i { display: block; font-size: 1.3rem; margin-bottom: .4rem; }

/* ══════════ MES TÂCHES ══════════ */
.sena-tasks-list { display: flex; flex-direction: column; gap: .45rem; }
.sena-task-row {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  padding: .6rem .75rem; border-radius: 10px; background: #f8fafc; border: 1px solid #e5e7eb;
  transition: border-color .15s;
}
.sena-task-row:hover { border-color: #94a3b8; }
.sena-task-overdue { border-color: #fca5a5 !important; background: #fff5f5 !important; }
.sena-task-left { display: flex; align-items: center; gap: .6rem; min-width: 0; flex: 1; }
.sena-task-badge {
  flex-shrink: 0; font-size: .6rem; font-weight: 700; padding: .18rem .45rem;
  border-radius: 6px; text-transform: uppercase; letter-spacing: .4px;
}
.badge-nouvelle  { background: #dbeafe; color: #1d4ed8; }
.badge-encours   { background: #fef9c3; color: #b45309; }
.badge-terminee  { background: #dcfce7; color: #15803d; }
.badge-bloquee   { background: #fee2e2; color: #b91c1c; }
.sena-task-title { font-size: .82rem; font-weight: 600; color: #1e293b; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px; }
.sena-task-title:hover { color: #0077B5; }
.sena-task-right { display: flex; align-items: center; gap: .6rem; flex-shrink: 0; }
.sena-task-progress { width: 60px; height: 5px; background: #e5e7eb; border-radius: 99px; overflow: hidden; }
.sena-task-progress-bar { height: 100%; border-radius: 99px; transition: width .3s; }
.sena-task-pct { font-size: .7rem; font-weight: 700; color: #475569; min-width: 30px; text-align: right; }
.sena-task-due { font-size: .65rem; color: #94a3b8; white-space: nowrap; }
.sena-due-urgent { color: #dc2626 !important; font-weight: 700; }

/* ══════════ AGENDA ══════════ */
.sena-agenda-list { display: flex; flex-direction: column; gap: .45rem; }
.sena-agenda-row {
  display: flex; align-items: center; gap: .85rem; padding: .65rem .75rem;
  border-radius: 10px; background: #f8fafc; border: 1px solid #e5e7eb;
}
.sena-agenda-urgent { border-color: #fca5a5 !important; background: #fff5f5 !important; }
.sena-agenda-date-block {
  flex-shrink: 0; width: 42px; text-align: center;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: .3rem .2rem;
}
.sena-agenda-day   { font-size: 1.1rem; font-weight: 800; color: #0077B5; line-height: 1; }
.sena-agenda-month { font-size: .62rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.sena-agenda-info  { flex: 1; min-width: 0; }
.sena-agenda-title { font-size: .82rem; font-weight: 600; color: #1e293b; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sena-agenda-title:hover { color: #0077B5; }
.sena-agenda-meta  { display: flex; align-items: center; gap: .5rem; margin-top: .15rem; }
.sena-agenda-agent { font-size: .65rem; color: #64748b; }
.sena-agenda-badge { flex-shrink: 0; font-size: .62rem; font-weight: 700; padding: .2rem .55rem; border-radius: 8px; }
.sena-agenda-badge-normal { background: #dbeafe; color: #1d4ed8; }
.sena-agenda-badge-urgent { background: #fee2e2; color: #b91c1c; }

/* ══════════ DEMANDES ══════════ */
.sena-requests-list { display: flex; flex-direction: column; gap: .45rem; }
.sena-request-row {
  display: flex; align-items: center; gap: .75rem; padding: .65rem .75rem;
  border-radius: 10px; background: #f8fafc; border: 1px solid #e5e7eb;
}
.sena-request-agent { display: flex; align-items: center; gap: .45rem; min-width: 140px; flex-shrink: 0; }
.sena-request-avatar-wrap {
  width: 28px; height: 28px; border-radius: 50%; overflow: hidden;
  background: linear-gradient(135deg,#bae6fd,#e0e7ff); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: .65rem; font-weight: 700; color: #3730a3;
}
.sena-request-avatar-photo { width: 100%; height: 100%; object-fit: cover; }
.sena-request-agent-name { font-size: .78rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px; }
.sena-request-type { flex: 1; font-size: .8rem; font-weight: 600; color: #374151; }
.sena-request-date { font-size: .65rem; color: #94a3b8; flex-shrink: 0; }
.sena-request-btn {
  flex-shrink: 0; width: 28px; height: 28px; border-radius: 8px;
  background: #e0f2fe; color: #0077B5; display: flex; align-items: center; justify-content: center;
  text-decoration: none; font-size: .75rem; transition: background .15s;
}
.sena-request-btn:hover { background: #bae6fd; }

/* ─── Steps workflow ─── */
.sena-request-steps { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
.sena-step {
  width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center;
  justify-content: center; font-size: .55rem; border: 1.5px solid transparent;
}
.sena-step-done   { background: #dcfce7; color: #15803d; border-color: #86efac; }
.sena-step-pending{ background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }
.sena-step-active { background: #fef9c3; color: #b45309; border-color: #fde047; }
.sena-step-line   { width: 12px; height: 1.5px; background: #e5e7eb; }

/* ══════════ PRÉSENCE ══════════ */
.sena-presence-stats { display: flex; align-items: center; gap: 1.2rem; padding-top: .5rem; }
.sena-presence-big { flex-shrink: 0; }
.sena-presence-ring-wrap { position: relative; }
.sena-ring-svg { width: 100px; height: 100px; }
.sena-ring-center {
  position: absolute; inset: 0; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.sena-ring-val { font-size: 1.3rem; font-weight: 800; color: #16a34a; line-height: 1; }
.sena-ring-lbl { font-size: .55rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; }
.sena-presence-items { display: flex; flex-direction: column; gap: .5rem; }
.sena-presence-item { display: flex; align-items: center; gap: .5rem; font-size: .78rem; color: #374151; }
.sena-presence-item i { width: 16px; text-align: center; }

/* ══════════ PTA ══════════ */
.sena-pta-progress-wrap { display: flex; align-items: center; gap: 1rem; padding: .6rem 0; }
.sena-pta-big-pct { font-size: 2.2rem; font-weight: 900; color: #7c3aed; line-height: 1; flex-shrink: 0; }
.sena-pta-pct-unit { font-size: 1rem; }
.sena-pta-bar-wrap { flex: 1; }
.sena-pta-bar { height: 8px; background: #e5e7eb; border-radius: 99px; overflow: hidden; margin-bottom: .3rem; }
.sena-pta-bar-fill { height: 100%; background: linear-gradient(90deg, #7c3aed, #a855f7); border-radius: 99px; transition: width .4s; }
.sena-pta-pct-label { font-size: .68rem; color: #94a3b8; font-weight: 600; }
.sena-pta-pills { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .25rem; }
.sena-pta-pill { display: flex; align-items: center; gap: .3rem; font-size: .7rem; padding: .25rem .55rem; border-radius: 8px; font-weight: 600; }
.sena-pta-pill span { font-weight: 800; font-size: .82rem; }
.sena-pta-pill-done { background: #dcfce7; color: #15803d; }
.sena-pta-pill-wip  { background: #fef9c3; color: #b45309; }
.sena-pta-pill-new  { background: #dbeafe; color: #1d4ed8; }

/* ══════════ COMMUNIQUÉS ══════════ */
.sena-communiques-list { display: flex; flex-direction: column; gap: .35rem; }
.sena-communique-row {
  display: flex; align-items: center; gap: .75rem; padding: .65rem .75rem;
  border-radius: 10px; background: #f8fafc; border: 1px solid #e5e7eb;
  text-decoration: none; transition: border-color .15s;
}
.sena-communique-row:hover { border-color: #ea580c; }
.sena-communique-urgence {
  flex-shrink: 0; width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center; font-size: .75rem;
}
.sena-urgence-high   { background: #fee2e2; color: #dc2626; }
.sena-urgence-normal { background: #fff7ed; color: #ea580c; }
.sena-communique-info { flex: 1; min-width: 0; }
.sena-communique-titre { font-size: .82rem; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sena-communique-meta { display: flex; align-items: center; gap: .5rem; margin-top: .1rem; font-size: .65rem; color: #94a3b8; }
.sena-communique-date { margin-left: auto; }
.sena-communique-arrow { flex-shrink: 0; font-size: .65rem; color: #cbd5e1; }

/* ══════════ SIGNALEMENTS ══════════ */
.sena-signalements-list { display: flex; flex-direction: column; gap: .35rem; }
.sena-signalement-row {
  display: flex; align-items: center; gap: .75rem; padding: .65rem .75rem;
  border-radius: 10px; background: #f8fafc; border: 1px solid #e5e7eb;
  text-decoration: none; transition: border-color .15s;
}
.sena-signalement-row:hover { border-color: #dc2626; }
.sena-signalement-severite {
  flex-shrink: 0; width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center; font-size: .75rem;
}
.sena-sev-haute    { background: #fee2e2; color: #dc2626; }
.sena-sev-moyenne  { background: #fff7ed; color: #ea580c; }
.sena-sev-normale  { background: #fef9c3; color: #ca8a04; }
.sena-signalement-info { flex: 1; min-width: 0; }
.sena-signalement-type { font-size: .82rem; font-weight: 700; color: #1e293b; text-transform: capitalize; }
.sena-signalement-meta { display: flex; align-items: center; gap: .5rem; margin-top: .1rem; font-size: .65rem; }
.sena-signalement-statut { padding: .15rem .4rem; border-radius: 6px; font-weight: 700; text-transform: capitalize; background: #fef9c3; color: #b45309; }
.sena-signalement-date  { color: #94a3b8; }
.sena-signalement-anon  { color: #7c3aed; }
.sena-signalement-arrow { flex-shrink: 0; font-size: .65rem; color: #cbd5e1; }

/* ══════════ RESPONSIVE ══════════ */
.sena-drill-overlay { position: fixed; inset: 0; z-index: 9999; background: rgba(15,23,42,.55); backdrop-filter: blur(6px); display: flex; align-items: stretch; justify-content: flex-end; }
.sena-drill-panel { width: 580px; max-width: 95vw; background: #f8fafc; display: flex; flex-direction: column; overflow: hidden; box-shadow: -12px 0 48px rgba(0,0,0,.18); animation: senaDrillSlide .25s ease-out; }
@keyframes senaDrillSlide { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
.sena-drill-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.45rem 1.7rem; color: #fff; background: linear-gradient(135deg, #0f172a 0%, #0077B5 100%); }
.sena-drill-header-left { display: flex; align-items: center; gap: .9rem; min-width: 0; }
.sena-drill-header-icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,.16); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sena-drill-title { font-size: 1.05rem; font-weight: 800; line-height: 1.2; }
.sena-drill-sub { font-size: .76rem; opacity: .78; margin-top: .2rem; text-transform: capitalize; }
.sena-drill-close { width: 36px; height: 36px; border-radius: 10px; border: none; background: rgba(255,255,255,.18); color: #fff; cursor: pointer; }
.sena-drill-close:hover { background: rgba(255,255,255,.3); }
.sena-drill-body { flex: 1; overflow-y: auto; padding: 1.25rem 1.45rem 1.6rem; }
.sena-drill-tabs { display: flex; gap: .45rem; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: .6rem; }
.sena-drill-tab { flex: 1; border: 1px solid #e2e8f0; background: #fff; color: #64748b; border-radius: 10px; padding: .58rem .75rem; font-size: .78rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; gap: .38rem; cursor: pointer; }
.sena-drill-tab.active { background: #0077B5; border-color: #0077B5; color: #fff; box-shadow: 0 8px 18px rgba(0,119,181,.2); }
.sena-drill-stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
.sena-drill-stat-card { border: 1px solid #e2e8f0; border-left: 4px solid #0077B5; background: #fff; border-radius: 12px; padding: .88rem 1rem; text-align: left; min-height: 76px; }
button.sena-drill-stat-card { font: inherit; cursor: pointer; }
.sena-drill-clickable { transition: transform .18s, box-shadow .18s, background .18s; }
.sena-drill-clickable:hover, .sena-drill-clickable.active { transform: translateY(-1px); background: #f0f9ff; box-shadow: 0 10px 22px rgba(14,165,233,.15); }
.sena-drill-stat-val { font-size: 1.55rem; font-weight: 900; color: #1e293b; line-height: 1; }
.sena-drill-stat-lbl { font-size: .66rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-top: .28rem; }
.sena-drill-section-title { font-size: .84rem; font-weight: 800; color: #475569; display: flex; align-items: center; gap: .5rem; margin: 1rem 0 .7rem; }
.sena-drill-list { display: flex; flex-direction: column; gap: .5rem; }
.sena-drill-row, .sena-drill-agent-row, .sena-drill-activity { display: flex; align-items: center; gap: .8rem; padding: .78rem .9rem; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; text-decoration: none; color: inherit; transition: background .15s, border-color .15s, box-shadow .15s; }
.sena-drill-row:hover, .sena-drill-agent-row:hover, .sena-drill-activity:hover { background: #f0f9ff; border-color: #bae6fd; box-shadow: 0 6px 18px rgba(14,165,233,.08); }
.sena-drill-row-icon, .sena-drill-avatar { width: 42px; height: 42px; border-radius: 50%; background: #e0f2fe; color: #0077B5; display: flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 800; flex-shrink: 0; overflow: hidden; }
.sena-drill-row-icon.danger { background: #fee2e2; color: #dc2626; }
.sena-drill-avatar img { width: 100%; height: 100%; object-fit: cover; }
.sena-drill-row-info { flex: 1; min-width: 0; }
.sena-drill-row-title { font-size: .86rem; font-weight: 800; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sena-drill-row-sub { font-size: .72rem; color: #94a3b8; margin-top: .12rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sena-drill-progress { width: 78px; height: 7px; border-radius: 999px; background: #e2e8f0; overflow: hidden; flex-shrink: 0; }
.sena-drill-progress.wide { width: 100%; margin-top: .45rem; }
.sena-drill-progress-bar { height: 100%; border-radius: 999px; transition: width .4s; }
.sena-drill-presence-note { margin-top: .22rem; font-size: .7rem; font-weight: 800; }
.sena-drill-presence-note.present { color: #059669; }
.sena-drill-presence-note.absent { color: #d97706; }
.sena-drill-agent-meta { display: flex; flex-direction: column; align-items: flex-end; gap: .12rem; color: #475569; font-weight: 800; font-size: .76rem; }
.sena-drill-agent-meta small { color: #94a3b8; font-weight: 700; }
.sena-drill-activity { align-items: flex-start; }
.sena-drill-activity-pct { width: 52px; flex-shrink: 0; text-align: right; font-size: 1.08rem; font-weight: 900; }
.sena-drill-empty { min-height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .55rem; color: #94a3b8; font-size: .86rem; font-weight: 700; text-align: center; }
.sena-drill-empty i { color: #059669; font-size: 1.55rem; }

@media (max-width: 768px) {
  .sena-hero-inner  { flex-direction: column; align-items: flex-start; }
  .sena-hero-kpis   { width: 100%; overflow-x: auto; }
  .sena-section-split { grid-template-columns: 1fr; }
  .sena-task-right  { display: none; }
  .sena-request-steps { display: none; }
  .sena-drill-panel { width: 100vw; max-width: 100vw; height: 92vh; border-radius: 16px 16px 0 0; }
  .sena-drill-overlay { align-items: flex-end; }
  .sena-drill-body { padding: 1rem; }
  .sena-drill-agent-row, .sena-drill-activity { align-items: flex-start; }
  .sena-drill-progress:not(.wide) { display: none; }
}

/* ══════════ DARK MODE ══════════ */
.dark .sena-dashboard  { background: #0f172a; }
.dark .sena-section    { background: #1e293b; border-color: #334155; }
.dark .sena-section-split > div { background: #1e293b; border-color: #334155; }
.dark .sena-section-title { color: #f1f5f9; }
.dark .sena-section-sub   { color: #64748b; }
.dark .sena-task-row, .dark .sena-agenda-row,
.dark .sena-request-row, .dark .sena-communique-row, .dark .sena-signalement-row { background: #0f172a; border-color: #334155; }
.dark .sena-task-title, .dark .sena-agenda-title { color: #f1f5f9; }
.dark .sena-agenda-date-block { background: #1e293b; border-color: #334155; }
.dark .sena-ring-val { color: #4ade80; }
.dark .sena-pta-big-pct { color: #a78bfa; }
.dark .sena-hero-kpis { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); }
/* Accueil compact logiciel */
.sena-hero {
  border-radius: 0 0 14px 14px;
  background: #0077B5;
  box-shadow: 0 4px 18px rgba(0, 119, 181, .18);
}
.sena-hero-bg {
  background: linear-gradient(90deg, rgba(255,255,255,.08), rgba(255,255,255,0));
}
.sena-hero-inner {
  padding: .95rem 1.25rem;
  gap: 1rem;
}
.sena-hero-left { gap: .85rem; }
.sena-hero-avatar {
  width: 48px;
  height: 48px;
  border-radius: 10px;
}
.sena-hero-greeting {
  font-size: .68rem;
  opacity: .72;
  font-weight: 700;
}
.sena-hero-name {
  font-size: 1.15rem;
  margin: .05rem 0 0;
}
.sena-hero-fonction,
.sena-hero-role-pill,
.sena-hero-date {
  display: none;
}
.sena-hero-kpis {
  border-radius: 10px;
  padding: .48rem .65rem;
  background: rgba(255,255,255,.12);
  border-color: rgba(255,255,255,.16);
}
.sena-kpi-pill {
  gap: .45rem;
  padding: 0 .65rem;
  border-radius: 8px;
}
.sena-kpi-pill-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  font-size: .75rem;
}
.sena-kpi-pill-val { font-size: 1.15rem; }
.sena-kpi-pill-lbl {
  font-size: .56rem;
  opacity: .7;
  letter-spacing: .2px;
  font-weight: 700;
  text-transform: uppercase;
}
.sena-kpi-pill-arrow { display: none; }
.kpi-divider {
  height: 28px;
  margin: 0 .1rem;
}
.sena-body { gap: .75rem; }
.sena-section-head {
  gap: .55rem;
  margin-bottom: .55rem;
}
.sena-section-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  font-size: .82rem;
}
.sena-section-title { font-size: .9rem; }
.sena-section-sub { display: none; }
.sena-section {
  border-radius: 8px;
}
</style>
