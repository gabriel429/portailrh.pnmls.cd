<template>
  <div class="sep-dashboard">
    <!-- ═══ HERO ═══ -->
    <div class="sep-hero">
      <div class="sep-hero-bg"></div>
      <div class="sep-hero-inner">
        <div class="sep-hero-left">
          <div class="sep-hero-badge">
            <i class="fas fa-map-marked-alt"></i>
          </div>
          <div>
            <div class="sep-hero-greeting">Bienvenue,</div>
            <h1 class="sep-hero-name">
              {{ auth.agent ? auth.agent.prenom + ' ' + auth.agent.nom : auth.user?.name || 'SEP' }}
            </h1>
            <div class="sep-hero-role">
              <i class="fas fa-map-marker-alt me-1"></i>
              Secrétariat Exécutif Provincial
              <span v-if="data.province?.nom" class="sep-hero-province-badge">
                {{ data.province.nom }}
              </span>
            </div>
            <div class="sep-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>
        <div class="sep-hero-kpis">
          <div class="sep-kpi sep-kpi-clickable" @click="router.push('/rh/agents')">
            <div class="sep-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="sep-kpi-val">{{ data.agents?.actifs ?? '-' }}</div>
              <div class="sep-kpi-lbl">Agents actifs</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sep-kpi sep-kpi-clickable" @click="router.push('/rh/pointages/monthly')">
            <div class="sep-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="sep-kpi-val">{{ data.attendance?.today_rate ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="sep-kpi-lbl">Présence</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sep-kpi sep-kpi-clickable" @click="router.push('/requests')">
            <div class="sep-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="sep-kpi-val">{{ data.requests?.en_attente ?? 0 }}</div>
              <div class="sep-kpi-lbl">En attente</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sep-kpi sep-kpi-clickable" @click="router.push('/plan-travail')">
            <div class="sep-kpi-icon"><i class="fas fa-bullseye"></i></div>
            <div>
              <div class="sep-kpi-val">{{ data.plan_travail?.avg_completion ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="sep-kpi-lbl">Plan annuel</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord provincial..." />

    <div v-else-if="loadError" class="alert alert-warning mx-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <template v-else>
      <!-- ═══ PROVINCE INFO ═══ -->
      <div class="sep-section" v-if="data.province">
        <div class="sep-province-card">
          <div class="sep-province-icon">
            <i class="fas fa-city"></i>
          </div>
          <div class="sep-province-info">
            <div class="sep-province-name">{{ data.province.nom }}</div>
            <div class="sep-province-details">
              <span v-if="data.province.nom_gouverneur">
                <i class="fas fa-user-tie me-1"></i>Gouverneur : {{ data.province.nom_gouverneur }}
              </span>
              <span v-if="data.province.nom_secretariat_executif" class="sep-province-sep">
                <i class="fas fa-id-badge me-1"></i>SEP : {{ data.province.nom_secretariat_executif }}
              </span>
              <span v-if="data.province.ville_secretariat">
                <i class="fas fa-map-pin me-1"></i>{{ data.province.ville_secretariat }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ QUICK ACTIONS ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#e0f9f5;color:#0ea5e9;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Actions rapides</h3>
            <p class="sep-section-sub">Accès direct aux modules provinciaux</p>
          </div>
        </div>
        <div class="sep-actions">
          <router-link v-for="a in quickActions" :key="a.to" :to="a.to" class="sep-action">
            <div class="sep-action-glow" :style="{ background: a.color }"></div>
            <div class="sep-action-icon" :style="{ background: a.bg, color: a.color }">
              <i class="fas" :class="a.icon"></i>
            </div>
            <div class="sep-action-text">
              <div class="sep-action-label">{{ a.label }}</div>
              <div class="sep-action-desc">{{ a.desc }}</div>
            </div>
            <i class="fas fa-chevron-right sep-action-arrow"></i>
          </router-link>
        </div>
      </div>

      <!-- ═══ KEY METRICS ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Indicateurs clés</h3>
            <p class="sep-section-sub">Vue d'ensemble de la province</p>
          </div>
        </div>
        <div class="sep-metrics">
          <div v-for="m in metrics" :key="m.label" class="sep-metric sep-clickable" @click="router.push(m.route)">
            <div class="sep-metric-header">
              <div class="sep-metric-icon" :style="{ background: m.bg, color: m.color }">
                <i class="fas" :class="m.icon"></i>
              </div>
              <span v-if="m.alert" class="sep-metric-alert">
                <i class="fas fa-exclamation-circle"></i>
              </span>
            </div>
            <div class="sep-metric-val" :style="{ color: m.color }">{{ m.value }}</div>
            <div class="sep-metric-lbl">{{ m.label }}</div>
            <div class="sep-metric-bar">
              <div class="sep-metric-bar-fill" :style="{ background: m.color, width: m.pct + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ DÉPARTEMENTS DE LA PROVINCE ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fas fa-building"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Départements de la province</h3>
            <p class="sep-section-sub">{{ (data.departments || []).length }} département(s) — {{ data.agents?.actifs ?? 0 }} agents actifs</p>
          </div>
        </div>
        <div class="sep-dept-grid">
          <div
            v-for="dept in (data.departments || [])" :key="dept.id"
            class="sep-dept-card sep-clickable"
            @click="openDeptDrilldown(dept.id)"
          >
            <div class="sep-dept-header">
              <div class="sep-dept-badge">
                <i class="fas fa-building"></i>
              </div>
              <div class="sep-dept-info">
                <div class="sep-dept-name">{{ dept.nom }}</div>
                <div class="sep-dept-code">{{ dept.code }}</div>
              </div>
              <i class="fas fa-chevron-right sep-drill-arrow"></i>
            </div>
            <div class="sep-dept-stats">
              <div class="sep-dept-stat">
                <div class="sep-dept-stat-val">{{ dept.total ?? 0 }}</div>
                <div class="sep-dept-stat-lbl">Total</div>
              </div>
              <div class="sep-dept-stat">
                <div class="sep-dept-stat-val" style="color:#059669;">{{ dept.actifs ?? 0 }}</div>
                <div class="sep-dept-stat-lbl">Actifs</div>
              </div>
            </div>
            <div class="sep-dept-bar-wrap">
              <div class="sep-dept-bar-bg">
                <div class="sep-dept-bar-fill"
                  :style="{ width: deptPct(dept.actifs) + '%' }"></div>
              </div>
              <div class="sep-dept-bar-label">{{ dept.actifs ?? 0 }} actifs / {{ data.agents?.actifs ?? 0 }}</div>
            </div>
          </div>
          <div v-if="!(data.departments || []).length" class="sep-dept-empty">
            <i class="fas fa-building"></i>
            <span>Aucun département configuré</span>
          </div>
        </div>
      </div>

      <!-- ═══ PRÉSENCE PAR ORGANE ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-user-check"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Présence dans la province</h3>
            <p class="sep-section-sub">{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }} présents aujourd'hui ({{ data.attendance?.today_rate ?? 0 }}%)</p>
          </div>
        </div>
        <div class="sep-presence-grid">
          <!-- Global -->
          <div class="sep-presence-card sep-presence-global">
            <div class="sep-presence-card-head">
              <i class="fas fa-globe-africa"></i> Province entière
            </div>
            <div class="sep-presence-big">{{ data.attendance?.today_rate ?? 0 }}%</div>
            <div class="sep-presence-sub">{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }}</div>
            <div class="sep-presence-item">
              <span>Aujourd'hui</span>
              <span class="fw-bold">{{ data.attendance?.today_rate ?? 0 }}%</span>
            </div>
            <div class="sep-presence-bar">
              <div class="sep-presence-fill" style="background:linear-gradient(90deg,#059669,#34d399);" :style="{ width: (data.attendance?.today_rate ?? 0) + '%' }"></div>
            </div>
            <div class="sep-presence-item">
              <span>Moy. mensuelle</span>
              <span class="fw-bold">{{ data.attendance?.monthly_avg_rate ?? 0 }}%</span>
            </div>
            <div class="sep-presence-bar">
              <div class="sep-presence-fill" style="background:linear-gradient(90deg,#0ea5e9,#38bdf8);" :style="{ width: (data.attendance?.monthly_avg_rate ?? 0) + '%' }"></div>
            </div>
          </div>
          <!-- Par organe dans la province -->
          <div v-for="o in presenceOrganes" :key="o.code" class="sep-presence-card sep-organe-clickable" @click="router.push('/rh/pointages/monthly')">
            <div class="sep-presence-card-head" :style="{ color: o.color }">
              <i class="fas" :class="o.icon"></i> {{ o.label }}
              <i class="fas fa-chevron-right sep-drill-arrow" style="margin-left:auto;"></i>
            </div>
            <div class="sep-presence-big" :style="{ color: o.color }">{{ o.today_rate }}%</div>
            <div class="sep-presence-sub">{{ o.today_present }} / {{ o.total_active }} agents</div>
            <div class="sep-presence-item">
              <span>Aujourd'hui</span>
              <span class="fw-bold">{{ o.today_rate }}%</span>
            </div>
            <div class="sep-presence-bar">
              <div class="sep-presence-fill" :style="{ background: o.color, width: o.today_rate + '%' }"></div>
            </div>
            <div class="sep-presence-item">
              <span>Moy. mensuelle</span>
              <span class="fw-bold">{{ o.monthly_rate }}%</span>
            </div>
            <div class="sep-presence-bar">
              <div class="sep-presence-fill" :style="{ background: o.color, width: o.monthly_rate + '%', opacity: .6 }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PLAN DE TRAVAIL SEP ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-tasks"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Plan de travail {{ currentYear }} — niveau SEP</h3>
            <p class="sep-section-sub">{{ data.plan_travail?.terminee ?? 0 }} / {{ data.plan_travail?.total ?? 0 }} activités terminées ({{ data.plan_travail?.avg_completion ?? 0 }}% avancement)</p>
          </div>
        </div>

        <div class="sep-plan-global-row">
          <div class="sep-plan-ring-wrap">
            <svg viewBox="0 0 120 120" class="sep-ring-svg">
              <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="10"/>
              <circle cx="60" cy="60" r="52" fill="none" stroke="#0ea5e9" stroke-width="10"
                stroke-linecap="round"
                :stroke-dasharray="ringDash"
                :stroke-dashoffset="ringOffset"
                transform="rotate(-90 60 60)"/>
            </svg>
            <div class="sep-ring-center">
              <div class="sep-ring-val">{{ data.plan_travail?.avg_completion ?? 0 }}%</div>
              <div class="sep-ring-lbl">Global</div>
            </div>
          </div>
          <div class="sep-plan-trims">
            <div v-for="t in (data.plan_travail?.by_trimestre || [])" :key="t.trimestre" class="sep-trim">
              <div class="sep-trim-head">
                <span class="sep-trim-name">{{ t.trimestre }}</span>
                <span class="sep-trim-pct">{{ t.avg_pourcentage }}%</span>
              </div>
              <div class="sep-trim-bar">
                <div class="sep-trim-fill" :style="{ width: t.avg_pourcentage + '%' }"></div>
              </div>
              <div class="sep-trim-detail">{{ t.terminee }}/{{ t.total }} terminées</div>
            </div>
          </div>
        </div>

        <!-- Voir par département -->
        <div v-if="data.departments?.length" class="sep-plan-dept-row">
          <div class="sep-plan-dept-hint"><i class="fas fa-building me-1"></i>Détail par département :</div>
          <div class="sep-plan-dept-chips">
            <button v-for="dept in data.departments" :key="dept.id" class="sep-plan-dept-chip" @click="openDeptDrilldown(dept.id, 'pta')">
              {{ dept.code || dept.nom }}
              <i class="fas fa-chevron-right" style="font-size:.6em;margin-left:4px;"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- ═══ ACTIVITÉS RÉCENTES ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#fce7f3;color:#db2777;">
            <i class="fas fa-stream"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Activités récentes</h3>
            <p class="sep-section-sub">Dernières mises à jour provinciales</p>
          </div>
        </div>
        <div class="sep-recent-grid">
          <!-- Communiqués -->
          <div class="sep-recent-col">
            <div class="sep-recent-head" style="border-color:#0891b2;">
              <div class="sep-recent-head-icon" style="background:#cffafe;color:#0891b2;">
                <i class="fas fa-bullhorn"></i>
              </div>
              <div>
                <div class="sep-recent-head-title">Communiqués</div>
                <div class="sep-recent-head-count">{{ data.communiques?.actifs ?? 0 }} actifs</div>
              </div>
              <span v-if="data.communiques?.urgents" class="sep-alert-badge">{{ data.communiques.urgents }} urgent(s)</span>
            </div>
            <div v-if="data.communiques?.recent?.length" class="sep-recent-body">
              <router-link v-for="c in data.communiques.recent" :key="c.id" :to="'/communiques/' + c.id" class="sep-recent-item">
                <div class="sep-recent-dot" style="background:#0891b2;"></div>
                <div class="sep-recent-info">
                  <div class="sep-recent-title">{{ c.titre }}</div>
                  <div class="sep-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(c.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="sep-recent-empty">
              <div class="sep-empty-icon-wrap" style="background:#ecfeff;">
                <i class="fas fa-inbox" style="color:#0891b2;"></i>
              </div>
              <span>Aucun communiqué récent</span>
            </div>
          </div>

          <!-- Demandes en attente -->
          <div class="sep-recent-col">
            <div class="sep-recent-head" style="border-color:#d97706;">
              <div class="sep-recent-head-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <div class="sep-recent-head-title">Demandes en attente</div>
                <div class="sep-recent-head-count">{{ data.requests?.en_attente ?? 0 }} en attente</div>
              </div>
            </div>
            <div v-if="data.requests?.recent_pending?.length" class="sep-recent-body">
              <router-link v-for="r in data.requests.recent_pending" :key="r.id" :to="'/requests/' + r.id" class="sep-recent-item">
                <div class="sep-recent-dot" style="background:#d97706;"></div>
                <div class="sep-recent-info">
                  <div class="sep-recent-title">{{ r.type }} — {{ r.agent?.prenom }} {{ r.agent?.nom }}</div>
                  <div class="sep-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(r.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="sep-recent-empty">
              <div class="sep-empty-icon-wrap" style="background:#fef9ee;">
                <i class="fas fa-check-circle" style="color:#16a34a;"></i>
              </div>
              <span>Aucune demande en attente</span>
            </div>
          </div>

          <!-- Signalements -->
          <div class="sep-recent-col">
            <div class="sep-recent-head" style="border-color:#dc2626;">
              <div class="sep-recent-head-icon" style="background:#fee2e2;color:#dc2626;">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div>
                <div class="sep-recent-head-title">Signalements</div>
                <div class="sep-recent-head-count">{{ data.signalements?.ouvert ?? 0 }} ouverts</div>
              </div>
              <span v-if="data.signalements?.haute_severite" class="sep-alert-badge">{{ data.signalements.haute_severite }} haute</span>
            </div>
            <div v-if="data.signalements?.recent?.length" class="sep-recent-body">
              <router-link v-for="s in data.signalements.recent" :key="s.id" :to="'/signalements/' + s.id" class="sep-recent-item">
                <div class="sep-recent-dot" :class="'sev-bg-' + s.severite"></div>
                <div class="sep-recent-info">
                  <div class="sep-recent-title">{{ s.type }}
                    <span class="sep-sev-badge" :class="'sev-' + s.severite">{{ s.severite }}</span>
                  </div>
                  <div class="sep-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(s.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="sep-recent-empty">
              <div class="sep-empty-icon-wrap" style="background:#fef2f2;">
                <i class="fas fa-shield-alt" style="color:#dc2626;"></i>
              </div>
              <span>Aucun signalement ouvert</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ CONGÉS & DISPONIBILITÉS ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#fef3c7;color:#f59e0b;">
            <i class="fas fa-umbrella-beach"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Congés & Disponibilités</h3>
            <p class="sep-section-sub">{{ data.holidays?.active_today ?? 0 }} agents en congé aujourd'hui — {{ data.holidays?.pending ?? 0 }} demandes en attente</p>
          </div>
        </div>
        <div class="sep-holidays-grid">
          <div class="sep-holiday-stats-card">
            <div class="sep-holiday-stat-item">
              <div class="sep-holiday-stat-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="fas fa-check-circle"></i>
              </div>
              <div>
                <div class="sep-holiday-stat-val">{{ data.holidays?.approved ?? 0 }}</div>
                <div class="sep-holiday-stat-lbl">Approuvés</div>
              </div>
            </div>
            <div class="sep-holiday-stat-item">
              <div class="sep-holiday-stat-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <div class="sep-holiday-stat-val">{{ data.holidays?.pending ?? 0 }}</div>
                <div class="sep-holiday-stat-lbl">En attente</div>
              </div>
            </div>
            <div class="sep-holiday-stat-item">
              <div class="sep-holiday-stat-icon" style="background:#e0f2fe;color:#0ea5e9;">
                <i class="fas fa-percent"></i>
              </div>
              <div>
                <div class="sep-holiday-stat-val">{{ data.holidays?.taux_utilisation_pct ?? 0 }}%</div>
                <div class="sep-holiday-stat-lbl">Taux utilisation</div>
              </div>
            </div>
          </div>

          <div class="sep-holiday-list-card">
            <div class="sep-holiday-list-head">
              <i class="fas fa-user-clock"></i> En congé aujourd'hui ({{ data.holidays?.active_today ?? 0 }})
            </div>
            <div v-if="data.holidays?.agents_en_conge_today?.length" class="sep-holiday-list">
              <div v-for="h in data.holidays.agents_en_conge_today.slice(0, 5)" :key="h.id" class="sep-holiday-item">
                <div class="sep-holiday-item-agent">{{ h.agent }}</div>
                <div class="sep-holiday-item-info">
                  <span class="sep-holiday-badge">{{ h.type }}</span>
                  <span class="sep-holiday-dates">{{ h.date_debut }} → {{ h.date_fin }}</span>
                </div>
              </div>
            </div>
            <div v-else class="sep-recent-empty" style="padding:1.5rem;">
              <i class="fas fa-check-circle"></i>
              <span>Aucun agent en congé</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ AFFECTATIONS & POSTES VACANTS ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#fee2e2;color:#dc2626;">
            <i class="fas fa-user-slash"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Affectations & Postes vacants</h3>
            <p class="sep-section-sub">{{ data.affectations?.postes_vacants ?? 0 }} postes vacants — {{ data.affectations?.sans_affectation ?? 0 }} agents sans affectation</p>
          </div>
        </div>
        <div class="sep-affectations-row">
          <div class="sep-affectation-card sep-affectation-alert sep-clickable" @click="router.push('/rh/affectations')">
            <div class="sep-affectation-icon" style="background:#fee2e2;color:#dc2626;">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
              <div class="sep-affectation-val">{{ data.affectations?.postes_vacants ?? 0 }}</div>
              <div class="sep-affectation-lbl">Postes vacants</div>
            </div>
          </div>
          <div class="sep-affectation-card sep-affectation-warning sep-clickable" @click="router.push('/rh/agents?sans_affectation=1')">
            <div class="sep-affectation-icon" style="background:#fef3c7;color:#d97706;">
              <i class="fas fa-user-slash"></i>
            </div>
            <div>
              <div class="sep-affectation-val">{{ data.affectations?.sans_affectation ?? 0 }}</div>
              <div class="sep-affectation-lbl">Sans affectation</div>
            </div>
          </div>
          <div class="sep-affectation-card sep-clickable" @click="router.push('/rh/affectations?mobilite=1')">
            <div class="sep-affectation-icon" style="background:#e0f2fe;color:#0ea5e9;">
              <i class="fas fa-exchange-alt"></i>
            </div>
            <div>
              <div class="sep-affectation-val">{{ (data.affectations?.mobilite_30_jours ?? []).length }}</div>
              <div class="sep-affectation-lbl">Mobilité (30j)</div>
            </div>
          </div>
          <div class="sep-affectation-card sep-clickable" @click="router.push('/rh/affectations?actif=1')">
            <div class="sep-affectation-icon" style="background:#dcfce7;color:#16a34a;">
              <i class="fas fa-briefcase"></i>
            </div>
            <div>
              <div class="sep-affectation-val">{{ data.affectations?.actives ?? 0 }}</div>
              <div class="sep-affectation-lbl">Affectations actives</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ TÂCHES ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Gestion des tâches</h3>
            <p class="sep-section-sub">{{ data.taches?.total ?? 0 }} tâches dans la province</p>
          </div>
        </div>
        <div class="sep-task-grid">
          <div class="sep-task-card sep-clickable" @click="router.push('/taches?statut=nouvelle')">
            <div class="sep-task-card-icon" style="background:#dbeafe;color:#3b82f6;">
              <i class="fas fa-plus-circle"></i>
            </div>
            <div class="sep-task-card-val">{{ data.taches?.nouvelle ?? 0 }}</div>
            <div class="sep-task-card-lbl">Nouvelles</div>
          </div>
          <div class="sep-task-card sep-clickable" @click="router.push('/taches?statut=en_cours')">
            <div class="sep-task-card-icon" style="background:#fef3c7;color:#d97706;">
              <i class="fas fa-spinner"></i>
            </div>
            <div class="sep-task-card-val">{{ data.taches?.en_cours ?? 0 }}</div>
            <div class="sep-task-card-lbl">En cours</div>
          </div>
          <div class="sep-task-card sep-clickable" @click="router.push('/taches?statut=terminee')">
            <div class="sep-task-card-icon" style="background:#dcfce7;color:#16a34a;">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="sep-task-card-val">{{ data.taches?.terminee ?? 0 }}</div>
            <div class="sep-task-card-lbl">Terminées</div>
          </div>
          <div class="sep-task-card sep-clickable" :class="{ 'sep-task-card-alert': (data.taches?.overdue ?? 0) > 0 }" @click="router.push('/taches?statut=en_retard')">
            <div class="sep-task-card-icon" style="background:#fee2e2;color:#dc2626;">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="sep-task-card-val">{{ data.taches?.overdue ?? 0 }}</div>
            <div class="sep-task-card-lbl">En retard</div>
          </div>
        </div>
        <div v-if="(data.taches?.total ?? 0) > 0" class="sep-task-progress">
          <div class="sep-task-progress-bar">
            <div class="sep-task-progress-fill" :style="{ width: taskCompletionPct + '%' }"></div>
          </div>
          <div class="sep-task-progress-lbl">{{ taskCompletionPct }}% terminées</div>
        </div>
      </div>

      <!-- ═══ DRILL-DOWN DÉPARTEMENT ═══ -->
      <Teleport to="body">
        <Transition name="drill-fade">
          <div v-if="deptDrillOpen" class="drill-overlay" @click.self="closeDeptDrilldown">
            <div class="drill-panel">
              <div class="drill-header" style="background:#0ea5e9;">
                <div class="drill-header-left">
                  <div>
                    <div class="drill-header-title" v-if="deptDrillData">
                      <i class="fas fa-building"></i> {{ deptDrillData.department?.nom }}
                    </div>
                    <div class="drill-header-title" v-else><i class="fas fa-spinner fa-spin"></i> Chargement...</div>
                    <div class="drill-header-sub" v-if="deptDrillData">
                      {{ deptDrillData.effectifs?.total ?? 0 }} agents · {{ deptDrillData.department?.code ?? '' }}
                    </div>
                  </div>
                </div>
                <button class="drill-close" @click="closeDeptDrilldown"><i class="fas fa-times"></i></button>
              </div>
              <div class="drill-body">
                <div v-if="deptDrillLoading" class="drill-loading">
                  <i class="fas fa-circle-notch fa-spin fa-2x" style="color:#0ea5e9;"></i>
                  <p>Chargement…</p>
                </div>
                <template v-else-if="deptDrillData">
                  <!-- Onglets section -->
                  <div class="drill-section-tabs">
                    <button class="drill-section-tab" :class="{ active: deptDrillSection === 'effectifs' }" @click="deptDrillSection = 'effectifs'">
                      <i class="fas fa-users"></i> Effectifs
                    </button>
                    <button class="drill-section-tab" :class="{ active: deptDrillSection === 'presence' }" @click="deptDrillSection = 'presence'">
                      <i class="fas fa-user-check"></i> Présence
                    </button>
                    <button class="drill-section-tab" :class="{ active: deptDrillSection === 'pta' }" @click="deptDrillSection = 'pta'">
                      <i class="fas fa-tasks"></i> PTA
                    </button>
                  </div>

                  <!-- ─── EFFECTIFS ─── -->
                  <template v-if="deptDrillSection === 'effectifs'">
                    <div class="drill-dept-grid">
                      <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.effectifs?.total ?? 0 }}</div>
                        <div class="drill-prov-stat-lbl">Agents</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#059669;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.effectifs?.actifs ?? 0 }}</div>
                        <div class="drill-prov-stat-lbl">Actifs</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#d97706;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.effectifs?.suspendus ?? 0 }}</div>
                        <div class="drill-prov-stat-lbl">Suspendus</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#64748b;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.effectifs?.anciens ?? 0 }}</div>
                        <div class="drill-prov-stat-lbl">Anciens</div>
                      </div>
                    </div>
                    <div v-if="deptDrillData.agents?.length" class="drill-prov-section-title" style="margin-top:16px;">
                      <i class="fas fa-user"></i> Agents ({{ deptDrillData.agents.length }})
                    </div>
                    <div v-if="deptDrillData.agents?.length" class="drill-prov-agents-table">
                      <div v-for="a in deptDrillData.agents" :key="a.id" class="drill-prov-agent-row">
                        <div class="drill-prov-agent-avatar"
                          :style="{ background: a.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: a.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
                          <i :class="a.sexe === 'F' ? 'fas fa-female' : 'fas fa-male'"></i>
                        </div>
                        <div class="drill-prov-agent-info">
                          <div class="drill-prov-agent-name">{{ a.nom }}</div>
                          <div class="drill-prov-agent-fn">{{ a.fonction }}</div>
                        </div>
                      </div>
                    </div>
                    <div v-else class="drill-empty">
                      <i class="fas fa-inbox"></i>
                      <p>Aucun agent dans ce département</p>
                    </div>
                  </template>

                  <!-- ─── PRÉSENCE ─── -->
                  <template v-else-if="deptDrillSection === 'presence'">
                    <div class="drill-dept-grid">
                      <div class="drill-prov-stat-card" style="border-color:#059669;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.presence?.today_present ?? 0 }}</div>
                        <div class="drill-prov-stat-lbl">Présents</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#d97706;">
                        <div class="drill-prov-stat-val">{{ (deptDrillData.presence?.total_active ?? 0) - (deptDrillData.presence?.today_present ?? 0) }}</div>
                        <div class="drill-prov-stat-lbl">Absents</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.presence?.today_rate ?? 0 }}%</div>
                        <div class="drill-prov-stat-lbl">Taux jour</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#7c3aed;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.presence?.monthly_rate ?? 0 }}%</div>
                        <div class="drill-prov-stat-lbl">Moy. mois</div>
                      </div>
                    </div>
                    <div v-if="deptDrillData.agents?.length" class="drill-prov-section-title" style="margin-top:16px;">
                      <i class="fas fa-user-check"></i> Agents actifs ({{ deptDrillData.presence?.total_active ?? 0 }})
                    </div>
                    <div v-if="deptDrillData.agents?.length" class="drill-prov-agents-table">
                      <div v-for="a in deptDrillData.agents" :key="a.id" class="drill-prov-agent-row">
                        <div class="drill-prov-agent-avatar"
                          :style="{ background: a.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: a.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
                          <i :class="a.sexe === 'F' ? 'fas fa-female' : 'fas fa-male'"></i>
                        </div>
                        <div class="drill-prov-agent-info">
                          <div class="drill-prov-agent-name">{{ a.nom }}</div>
                          <div class="drill-prov-agent-fn">{{ a.fonction }}</div>
                        </div>
                      </div>
                    </div>
                    <div v-else class="drill-empty"><i class="fas fa-inbox"></i><p>Aucune donnée de présence</p></div>
                  </template>

                  <!-- ─── PTA ─── -->
                  <template v-else>
                    <div class="drill-dept-grid">
                      <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.pta?.total ?? 0 }}</div>
                        <div class="drill-prov-stat-lbl">Activités</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#059669;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.pta?.terminee ?? 0 }}</div>
                        <div class="drill-prov-stat-lbl">Terminées</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#d97706;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.pta?.en_cours ?? 0 }}</div>
                        <div class="drill-prov-stat-lbl">En cours</div>
                      </div>
                      <div class="drill-prov-stat-card" style="border-color:#7c3aed;">
                        <div class="drill-prov-stat-val">{{ deptDrillData.pta?.avg ?? 0 }}%</div>
                        <div class="drill-prov-stat-lbl">Avancement</div>
                      </div>
                    </div>
                    <template v-if="deptDrillData.activites?.length">
                      <div class="drill-prov-section-title" style="margin-top:16px;">
                        <i class="fas fa-clipboard-list"></i> Activités PTA {{ new Date().getFullYear() }} ({{ deptDrillData.activites.length }})
                      </div>
                      <div class="drill-prov-activites">
                        <div v-for="act in deptDrillData.activites" :key="act.id" class="drill-prov-activite">
                          <div class="drill-activite-head">
                            <div class="drill-activite-pct" :style="{ color: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#94a3b8' }">{{ act.pourcentage }}%</div>
                            <div class="drill-activite-info">
                              <div class="drill-activite-titre">{{ act.titre }}</div>
                              <div class="drill-activite-meta">
                                <span v-if="act.categorie" class="drill-activite-cat">{{ act.categorie }}</span>
                                <span v-if="act.trimestre">{{ act.trimestre }}</span>
                                <span v-if="act.date_debut">{{ act.date_debut }} → {{ act.date_fin || '?' }}</span>
                              </div>
                            </div>
                          </div>
                          <div class="drill-item-bar" style="margin-top:4px;">
                            <div class="drill-item-bar-fill" :style="{ width: Math.min(act.pourcentage, 100) + '%', background: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#e2e8f0' }"></div>
                          </div>
                        </div>
                      </div>
                    </template>
                    <div v-else class="drill-empty"><i class="fas fa-inbox"></i><p>Aucune activité PTA dans ce département</p></div>
                  </template>
                </template>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- ═══ FOOTER ═══ -->
      <div class="sep-footer">
        <div class="sep-footer-inner">
          <div class="sep-footer-left">
            <i class="fas fa-map-marked-alt"></i>
            <span>Tableau de bord SEP</span>
          </div>
          <div class="sep-footer-badge">Province · {{ data.province?.code ?? '' }}</div>
          <div>Mis à jour à {{ currentTime }}</div>
        </div>
      </div>
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
const loadError = ref(null)
const data = ref({})
const currentYear = new Date().getFullYear()

const today = computed(() => new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
}))

const currentTime = computed(() => new Date().toLocaleTimeString('fr-FR', {
  hour: '2-digit', minute: '2-digit',
}))

const taskCompletionPct = computed(() => {
  const total = data.value.taches?.total ?? 0
  const done = data.value.taches?.terminee ?? 0
  return total > 0 ? Math.round((done / total) * 100) : 0
})

// ─── DRILL-DOWN DÉPARTEMENT ───
const deptDrillOpen = ref(false)
const deptDrillLoading = ref(false)
const deptDrillData = ref(null)
const deptDrillSection = ref('effectifs')

async function openDeptDrilldown(id, section = 'effectifs') {
  deptDrillOpen.value = true
  deptDrillLoading.value = true
  deptDrillData.value = null
  deptDrillSection.value = section
  try {
    const { data: result } = await client.get(`/dashboard/executive/department/${id}`)
    deptDrillData.value = result.data ?? result
  } catch (e) {
    deptDrillData.value = null
  } finally {
    deptDrillLoading.value = false
  }
}

function closeDeptDrilldown() {
  deptDrillOpen.value = false
  deptDrillData.value = null
  deptDrillSection.value = 'effectifs'
}

// ─── QUICK ACTIONS ───
const quickActions = [
  { to: '/rh/agents', label: 'Gestion agents', desc: 'Agents de la province', icon: 'fa-users', color: '#0ea5e9', bg: '#e0f2fe' },
  { to: '/plan-travail', label: 'PTA provincial', desc: 'Suivi plan annuel SEP', icon: 'fa-tasks', color: '#d97706', bg: '#fef3c7' },
  { to: '/requests', label: 'Demandes', desc: 'Validation provinciale', icon: 'fa-paper-plane', color: '#059669', bg: '#d1fae5' },
  { to: '/signalements', label: 'Signalements', desc: 'Alertes province', icon: 'fa-flag', color: '#dc2626', bg: '#fee2e2' },
  { to: '/rh/pointages/monthly', label: 'Pointages', desc: 'Présence province', icon: 'fa-clock', color: '#7c3aed', bg: '#ede9fe' },
  { to: '/rh/communiques', label: 'Communiqués', desc: 'Informations officielles', icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe' },
]

// ─── METRICS ───
const maxMetric = computed(() => {
  const vals = [
    data.value.agents?.total ?? 0,
    data.value.agents?.actifs ?? 0,
    data.value.requests?.en_attente ?? 0,
    data.value.requests?.approuve ?? 0,
    data.value.signalements?.ouvert ?? 0,
    data.value.taches?.en_cours ?? 0,
  ]
  return Math.max(...vals, 1)
})

function pct(val) {
  return Math.min(((val ?? 0) / maxMetric.value) * 100, 100)
}

const metrics = computed(() => [
  { label: 'Agents total', value: data.value.agents?.total ?? 0, icon: 'fa-users', color: '#0ea5e9', bg: '#e0f2fe', pct: pct(data.value.agents?.total), alert: false, route: '/rh/agents' },
  { label: 'Agents actifs', value: data.value.agents?.actifs ?? 0, icon: 'fa-user-check', color: '#059669', bg: '#d1fae5', pct: pct(data.value.agents?.actifs), alert: false, route: '/rh/agents' },
  { label: 'Demandes en attente', value: data.value.requests?.en_attente ?? 0, icon: 'fa-hourglass-half', color: '#d97706', bg: '#fef3c7', pct: pct(data.value.requests?.en_attente), alert: (data.value.requests?.en_attente ?? 0) > 5, route: '/requests' },
  { label: 'Signalements ouverts', value: data.value.signalements?.ouvert ?? 0, icon: 'fa-exclamation-circle', color: '#dc2626', bg: '#fee2e2', pct: pct(data.value.signalements?.ouvert), alert: (data.value.signalements?.ouvert ?? 0) > 0, route: '/signalements' },
  { label: 'Tâches en cours', value: data.value.taches?.en_cours ?? 0, icon: 'fa-spinner', color: '#7c3aed', bg: '#ede9fe', pct: pct(data.value.taches?.en_cours), alert: false, route: '/taches' },
  { label: 'Congés actifs', value: data.value.holidays?.active_today ?? 0, icon: 'fa-umbrella-beach', color: '#f59e0b', bg: '#fef3c7', pct: pct(data.value.holidays?.active_today), alert: false, route: '/rh/conges' },
])

// ─── PRÉSENCE PAR ORGANE ───
const presenceOrganes = computed(() => {
  const att = data.value.attendance?.by_organe || {}
  return [
    { code: 'SEP', label: 'SEP (Provincial)', icon: 'fa-map-marked-alt', color: '#0ea5e9', today_present: att.sep?.today_present ?? 0, today_rate: att.sep?.today_rate ?? 0, monthly_rate: att.sep?.monthly_avg_rate ?? 0, total_active: att.sep?.total_active_agents ?? 0 },
    { code: 'SEL', label: 'SEL (Local)', icon: 'fa-map-pin', color: '#0d9488', today_present: att.sel?.today_present ?? 0, today_rate: att.sel?.today_rate ?? 0, monthly_rate: att.sel?.monthly_avg_rate ?? 0, total_active: att.sel?.total_active_agents ?? 0 },
  ]
})

// ─── DÉPARTEMENT helper ───
function deptPct(actifs) {
  const total = data.value.agents?.actifs || 1
  return Math.min(Math.round(((actifs ?? 0) / total) * 100), 100)
}

// ─── PLAN ring ───
const ringCirc = 2 * Math.PI * 52
const ringDash = ringCirc.toFixed(1)
const ringOffset = computed(() => {
  const pctVal = data.value.plan_travail?.avg_completion ?? 0
  return (ringCirc - (ringCirc * pctVal / 100)).toFixed(1)
})

function formatTime(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1) return "À l'instant"
  if (diff < 60) return `Il y a ${diff} min`
  if (diff < 1440) return `Il y a ${Math.floor(diff / 60)}h`
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

onMounted(async () => {
  try {
    const { data: result } = await client.get('/dashboard/sep')
    data.value = result
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Impossible de charger le tableau de bord provincial.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.sep-dashboard { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }

/* ═══════════ HERO ═══════════ */
.sep-hero {
  position: relative; border-radius: 20px; overflow: hidden; margin-bottom: 1.8rem;
  background: linear-gradient(135deg, #0a2030 0%, #0c3347 30%, #0a5473 60%, #0ea5e9 100%);
  box-shadow: 0 8px 32px rgba(0, 30, 60, .25);
}
.sep-hero-bg {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 400px 300px at 85% 20%, rgba(14,165,233,.3) 0%, transparent 70%),
    radial-gradient(ellipse 200px 200px at 10% 80%, rgba(56,189,248,.12) 0%, transparent 60%);
  pointer-events: none;
}
.sep-hero-inner {
  position: relative; z-index: 1; padding: 2rem 2.5rem;
  display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;
}
.sep-hero-left { display: flex; align-items: center; gap: 1.2rem; color: #fff; }
.sep-hero-badge {
  width: 60px; height: 60px; border-radius: 16px; flex-shrink: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.15), rgba(255,255,255,.05));
  border: 1px solid rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: #7dd3fc; backdrop-filter: blur(8px);
}
.sep-hero-greeting { font-size: .82rem; opacity: .6; font-weight: 500; letter-spacing: .5px; text-transform: uppercase; }
.sep-hero-name { font-size: 1.5rem; font-weight: 800; margin: .1rem 0 .35rem; line-height: 1.15; color: #fff; }
.sep-hero-role { font-size: .78rem; font-weight: 600; opacity: .75; margin-bottom: .2rem; display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
.sep-hero-province-badge {
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25);
  padding: .1rem .5rem; border-radius: 8px; font-size: .7rem; font-weight: 700; color: #e0f2fe;
}
.sep-hero-date { font-size: .72rem; opacity: .45; text-transform: capitalize; }

.sep-hero-kpis {
  display: flex; align-items: center; gap: 0;
  background: rgba(255,255,255,.08); border-radius: 16px; padding: .8rem 1.2rem;
  border: 1px solid rgba(255,255,255,.1); backdrop-filter: blur(8px);
}
.sep-kpi { display: flex; align-items: center; gap: .6rem; padding: 0 1rem; color: #fff; }
.sep-kpi-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.1); display: flex; align-items: center;
  justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.sep-kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.kpi-unit { font-size: .85rem; font-weight: 600; opacity: .7; }
.sep-kpi-lbl { font-size: .62rem; opacity: .5; text-transform: uppercase; letter-spacing: .4px; margin-top: .1rem; font-weight: 600; }
.kpi-divider { width: 1px; height: 36px; background: rgba(255,255,255,.12); margin: 0 .2rem; }

/* ═══════════ PROVINCE CARD ═══════════ */
.sep-province-card {
  display: flex; align-items: center; gap: 1rem; padding: 1.1rem 1.3rem;
  background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
  border: 1px solid #bae6fd; border-radius: 14px; margin-bottom: 0;
}
.sep-province-icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #0ea5e9, #38bdf8);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: #fff; flex-shrink: 0;
}
.sep-province-name { font-size: 1.05rem; font-weight: 800; color: #0c4a6e; margin-bottom: .25rem; }
.sep-province-details { display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: .78rem; color: #0369a1; }
.sep-province-sep { font-weight: 600; color: #0ea5e9; }

/* ═══════════ SECTIONS ═══════════ */
.sep-section { margin-bottom: 1.8rem; }
.sep-section-head { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.sep-section-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0;
}
.sep-section-title { font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; }
.sep-section-sub { font-size: .72rem; color: #94a3b8; margin: 0; font-weight: 500; }

/* ═══════════ QUICK ACTIONS ═══════════ */
.sep-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sep-action {
  position: relative; display: flex; align-items: center; gap: .8rem; padding: 1rem 1.1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; overflow: hidden;
}
.sep-action-glow { position: absolute; top: 0; left: 0; width: 3px; height: 100%; opacity: 0; transition: opacity .25s; }
.sep-action:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sep-action:hover .sep-action-glow { opacity: 1; }
.sep-action-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.sep-action-text { flex: 1; min-width: 0; }
.sep-action-label { font-size: .84rem; font-weight: 700; line-height: 1.2; }
.sep-action-desc { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
.sep-action-arrow { font-size: .65rem; color: #cbd5e1; transition: all .2s; }
.sep-action:hover .sep-action-arrow { color: #0ea5e9; transform: translateX(3px); }

/* ═══════════ METRICS ═══════════ */
.sep-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sep-metric {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.1rem; transition: all .25s; position: relative;
}
.sep-clickable { cursor: pointer; }
.sep-metric:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sep-metric-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .7rem; }
.sep-metric-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: .9rem; }
.sep-metric-alert { color: #ef4444; font-size: .75rem; animation: pulse-alert 2s infinite; }
@keyframes pulse-alert { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
.sep-metric-val { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.sep-metric-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.sep-metric-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: .7rem; }
.sep-metric-bar-fill { height: 100%; border-radius: 4px; transition: width .8s ease; min-width: 3px; }

/* ═══════════ DÉPARTEMENTS ═══════════ */
.sep-dept-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sep-dept-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  border-top: 4px solid #0ea5e9; padding: 1.2rem; transition: all .25s;
}
.sep-dept-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sep-dept-header { display: flex; align-items: center; gap: .7rem; margin-bottom: 1rem; }
.sep-dept-badge {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  background: linear-gradient(135deg, #0ea5e9, #38bdf8);
  display: flex; align-items: center; justify-content: center;
  font-size: .82rem; color: #fff;
}
.sep-dept-info { flex: 1; min-width: 0; }
.sep-dept-name { font-size: .88rem; font-weight: 700; color: #1e293b; }
.sep-dept-code { font-size: .68rem; color: #94a3b8; }
.sep-drill-arrow { font-size: .65rem; color: #cbd5e1; margin-left: auto; transition: all .2s; }
.sep-dept-card:hover .sep-drill-arrow { color: #0ea5e9; transform: translateX(3px); }
.sep-kpi-clickable { cursor: pointer; border-radius: 10px; transition: background .15s; }
.sep-kpi-clickable:hover { background: rgba(255,255,255,.18); }
.sep-organe-clickable { cursor: pointer; }
.sep-organe-clickable:hover { border-color: #7dd3fc; box-shadow: 0 4px 16px rgba(14,165,233,.12); }
.sep-organe-clickable:hover .sep-drill-arrow { color: #0ea5e9; transform: translateX(3px); }
.sep-plan-dept-row { margin-top: 1.2rem; display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.sep-plan-dept-hint { font-size: .72rem; color: #94a3b8; font-weight: 600; white-space: nowrap; }
.sep-plan-dept-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
.sep-plan-dept-chip {
  display: inline-flex; align-items: center; padding: .25rem .6rem;
  background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: .72rem; font-weight: 600; color: #475569; cursor: pointer;
  transition: all .15s;
}
.sep-plan-dept-chip:hover { background: #e0f2fe; border-color: #0ea5e9; color: #0369a1; }
.sep-dept-stats { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-bottom: .75rem; }
.sep-dept-stat { text-align: center; padding: .5rem; background: #f8fafc; border-radius: 10px; }
.sep-dept-stat-val { font-size: 1.3rem; font-weight: 800; line-height: 1; color: #1e293b; }
.sep-dept-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: .15rem; }
.sep-dept-bar-wrap {}
.sep-dept-bar-bg { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.sep-dept-bar-fill {
  height: 100%; border-radius: 6px;
  background: linear-gradient(90deg, #0ea5e9, #38bdf8); transition: width .8s ease; min-width: 2px;
}
.sep-dept-bar-label { font-size: .62rem; color: #94a3b8; margin-top: .25rem; }
.sep-dept-empty {
  grid-column: 1/-1; background: #f8fafc; border-radius: 14px; border: 1px dashed #e5e7eb;
  padding: 2rem; text-align: center; color: #cbd5e1; font-size: .85rem;
  display: flex; flex-direction: column; align-items: center; gap: .5rem;
}
.sep-dept-empty i { font-size: 1.5rem; }

/* ═══════════ PRÉSENCE ═══════════ */
.sep-presence-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sep-presence-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s;
}
.sep-presence-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sep-presence-global { border-top: 4px solid #059669; }
.sep-presence-card-head { font-size: .88rem; font-weight: 700; color: #1e293b; margin-bottom: .8rem; display: flex; align-items: center; gap: .5rem; }
.sep-presence-big { font-size: 2rem; font-weight: 800; text-align: center; line-height: 1; margin-bottom: .15rem; }
.sep-presence-sub { font-size: .7rem; color: #94a3b8; text-align: center; margin-bottom: .8rem; }
.sep-presence-item { display: flex; justify-content: space-between; font-size: .75rem; color: #475569; margin-bottom: .25rem; }
.sep-presence-bar { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; margin-bottom: .6rem; }
.sep-presence-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }

/* ═══════════ PLAN DE TRAVAIL ═══════════ */
.sep-plan-global-row {
  display: flex; align-items: flex-start; gap: 1.5rem; padding: 1.3rem;
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
}
.sep-plan-ring-wrap { position: relative; flex-shrink: 0; }
.sep-ring-svg { width: 120px; height: 120px; }
.sep-ring-center {
  position: absolute; inset: 0; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.sep-ring-val { font-size: 1.5rem; font-weight: 800; color: #0ea5e9; line-height: 1; }
.sep-ring-lbl { font-size: .62rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; }
.sep-plan-trims { flex: 1; display: flex; flex-direction: column; gap: .75rem; }
.sep-trim-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .3rem; }
.sep-trim-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sep-trim-pct { font-size: .78rem; font-weight: 700; color: #0ea5e9; }
.sep-trim-bar { height: 8px; background: #f1f5f9; border-radius: 8px; overflow: hidden; }
.sep-trim-fill {
  height: 100%; border-radius: 8px; transition: width .8s ease;
  background: linear-gradient(90deg, #0ea5e9, #38bdf8);
}
.sep-trim-detail { font-size: .65rem; color: #94a3b8; margin-top: .2rem; }

/* ═══════════ RECENT ═══════════ */
.sep-recent-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sep-recent-col {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; display: flex; flex-direction: column;
}
.sep-recent-head {
  display: flex; align-items: center; gap: .6rem; padding: .9rem 1rem;
  border-bottom: 2px solid; background: #fafbfc;
}
.sep-recent-head-icon {
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
}
.sep-recent-head-title { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sep-recent-head-count { font-size: .65rem; color: #94a3b8; }
.sep-alert-badge {
  margin-left: auto; font-size: .62rem; font-weight: 700; color: #fff;
  background: #ef4444; padding: .15rem .5rem; border-radius: 6px; white-space: nowrap;
}
.sep-recent-body { flex: 1; max-height: 250px; overflow-y: auto; }
.sep-recent-item {
  display: flex; align-items: flex-start; gap: .6rem; padding: .65rem 1rem;
  border-bottom: 1px solid #f8fafc; text-decoration: none; color: inherit; transition: background .15s;
}
.sep-recent-item:hover { background: #f0f9ff; }
.sep-recent-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: .35rem; }
.sep-recent-info { flex: 1; min-width: 0; }
.sep-recent-title { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.sep-recent-time { font-size: .65rem; color: #94a3b8; margin-top: .15rem; }
.sep-recent-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: .4rem; padding: 2rem 1rem; color: #cbd5e1; font-size: .82rem;
}
.sep-recent-empty i { font-size: 1.3rem; }
.sev-bg-basse { background: #22c55e; }
.sev-bg-moyenne { background: #f59e0b; }
.sev-bg-haute { background: #ef4444; }
.sep-sev-badge {
  display: inline-block; font-size: .58rem; font-weight: 700; padding: .1rem .4rem;
  border-radius: 4px; margin-left: .3rem; text-transform: uppercase; letter-spacing: .3px;
}
.sev-basse { background: #dcfce7; color: #16a34a; }
.sev-moyenne { background: #fef3c7; color: #d97706; }
.sev-haute { background: #fee2e2; color: #dc2626; }

/* ═══════════ CONGÉS ═══════════ */
.sep-holidays-grid { display: grid; grid-template-columns: 300px 1fr; gap: .75rem; }
.sep-holiday-stats-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; display: flex; flex-direction: column; gap: .65rem;
}
.sep-holiday-stat-item {
  display: flex; align-items: center; gap: .7rem; padding: .6rem;
  background: #f8fafc; border-radius: 10px;
}
.sep-holiday-stat-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0;
}
.sep-holiday-stat-val { font-size: 1.5rem; font-weight: 800; line-height: 1; color: #1e293b; }
.sep-holiday-stat-lbl { font-size: .68rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }
.sep-holiday-list-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; overflow: hidden;
}
.sep-holiday-list-head {
  padding: .9rem 1.1rem; background: #f8fafc; border-bottom: 1px solid #e5e7eb;
  font-size: .82rem; font-weight: 700; color: #1e293b;
}
.sep-holiday-list { max-height: 260px; overflow-y: auto; }
.sep-holiday-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: .7rem 1.1rem; border-bottom: 1px solid #f8fafc;
}
.sep-holiday-item:hover { background: #f0f9ff; }
.sep-holiday-item-agent { font-size: .78rem; font-weight: 600; color: #1e293b; }
.sep-holiday-item-info { display: flex; align-items: center; gap: .5rem; font-size: .7rem; color: #64748b; }
.sep-holiday-badge {
  background: #fef3c7; color: #d97706; padding: .15rem .45rem;
  border-radius: 6px; font-weight: 600; font-size: .65rem;
}

/* ═══════════ AFFECTATIONS ═══════════ */
.sep-affectations-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.sep-affectation-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; display: flex; align-items: center; gap: .8rem; transition: all .25s;
}
.sep-affectation-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sep-affectation-alert { border-left: 3px solid #dc2626; }
.sep-affectation-warning { border-left: 3px solid #d97706; }
.sep-affectation-icon {
  width: 48px; height: 48px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0;
}
.sep-affectation-val { font-size: 1.75rem; font-weight: 800; line-height: 1; color: #1e293b; }
.sep-affectation-lbl { font-size: .68rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .2rem; }

/* ═══════════ TÂCHES ═══════════ */
.sep-task-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.sep-task-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1rem; text-align: center; transition: all .25s;
}
.sep-task-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.06); }
.sep-task-card-alert { border-color: #fca5a5; background: #fef2f2; }
.sep-task-card-icon {
  width: 40px; height: 40px; border-radius: 12px; display: inline-flex;
  align-items: center; justify-content: center; font-size: .9rem; margin-bottom: .5rem;
}
.sep-task-card-val { font-size: 1.6rem; font-weight: 800; color: #1e293b; line-height: 1; }
.sep-task-card-lbl { font-size: .68rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .2rem; }
.sep-task-progress { margin-top: .75rem; }
.sep-task-progress-bar { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.sep-task-progress-fill {
  height: 100%; border-radius: 6px; min-width: 2px;
  background: linear-gradient(90deg, #0ea5e9, #38bdf8); transition: width .8s ease;
}
.sep-task-progress-lbl { font-size: .65rem; color: #94a3b8; margin-top: .25rem; text-align: right; }

/* ═══════════ EMPTY ═══════════ */
.sep-empty-icon-wrap {
  width: 48px; height: 48px; border-radius: 50%; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: .2rem;
}

/* ═══════════ FOOTER ═══════════ */
.sep-footer { margin-top: .5rem; padding: 1rem 0; border-top: 1px solid #e5e7eb; }
.sep-footer-inner {
  display: flex; align-items: center; justify-content: space-between;
  font-size: .72rem; color: #94a3b8;
}
.sep-footer-left { display: flex; align-items: center; gap: .4rem; }
.sep-footer-badge {
  background: #e0f2fe; padding: .2rem .6rem; border-radius: 6px;
  font-weight: 600; font-size: .65rem; color: #0ea5e9;
}

/* ═══════════ DRILL-DOWN ═══════════ */
.drill-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1050;
  display: flex; align-items: stretch; justify-content: flex-end;
}
.drill-panel {
  width: 480px; max-width: 95vw; background: #fff; display: flex; flex-direction: column;
  box-shadow: -8px 0 40px rgba(0,0,0,.15); overflow: hidden;
}
.drill-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.2rem 1.4rem; color: #fff; flex-shrink: 0;
}
.drill-header-left { display: flex; align-items: center; gap: 1rem; }
.drill-header-title { font-size: 1rem; font-weight: 800; display: flex; align-items: center; gap: .5rem; }
.drill-header-sub { font-size: .72rem; opacity: .7; margin-top: .2rem; }
.drill-close {
  background: rgba(255,255,255,.15); border: none; color: #fff;
  width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: .85rem;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.drill-close:hover { background: rgba(255,255,255,.25); }
.drill-body { flex: 1; overflow-y: auto; padding: 1.2rem; }
.drill-loading { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 3rem; color: #94a3b8; }
.drill-empty { display: flex; flex-direction: column; align-items: center; gap: .6rem; padding: 2rem; color: #94a3b8; font-size: .85rem; }
.drill-empty i { font-size: 1.5rem; }
.drill-dept-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; margin-bottom: 1rem; }
.drill-prov-stat-card {
  background: #f8fafc; border-radius: 12px; border-left: 3px solid; padding: .8rem 1rem; text-align: center;
}
.drill-prov-stat-val { font-size: 1.5rem; font-weight: 800; color: #1e293b; line-height: 1; }
.drill-prov-stat-lbl { font-size: .65rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: .2rem; }
.drill-prov-section-title { font-size: .82rem; font-weight: 700; color: #475569; display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; }
.drill-section-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: .5rem; }
.drill-section-tab {
  display: flex; align-items: center; gap: .35rem; padding: .35rem .75rem;
  font-size: .75rem; font-weight: 600; border-radius: 8px; border: 1px solid #e5e7eb;
  background: #f8fafc; color: #64748b; cursor: pointer; transition: all .15s;
}
.drill-section-tab:hover { border-color: #0ea5e9; color: #0ea5e9; }
.drill-section-tab.active { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
.drill-prov-activites { display: flex; flex-direction: column; gap: .6rem; }
.drill-prov-activite { background: #f8fafc; border-radius: 10px; padding: .7rem .9rem; }
.drill-activite-head { display: flex; align-items: flex-start; gap: .75rem; }
.drill-activite-pct { font-size: 1rem; font-weight: 800; min-width: 42px; }
.drill-activite-info { flex: 1; min-width: 0; }
.drill-activite-titre { font-size: .78rem; font-weight: 600; color: #1e293b; }
.drill-activite-meta { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .15rem; font-size: .67rem; color: #94a3b8; }
.drill-activite-cat { background: #e0f2fe; color: #0369a1; border-radius: 4px; padding: .1rem .35rem; font-weight: 600; }
.drill-item-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: .5rem; }
.drill-item-bar-fill { height: 100%; border-radius: 4px; transition: width .6s ease; }
.drill-prov-agents-table { display: flex; flex-direction: column; gap: .4rem; }
.drill-prov-agent-row {
  display: flex; align-items: center; gap: .7rem; padding: .6rem .8rem;
  background: #f8fafc; border-radius: 10px; transition: background .15s;
}
.drill-prov-agent-row:hover { background: #e0f2fe; }
.drill-prov-agent-avatar {
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
}
.drill-prov-agent-name { font-size: .78rem; font-weight: 600; color: #1e293b; }
.drill-prov-agent-fn { font-size: .68rem; color: #94a3b8; }

/* Transitions */
.drill-fade-enter-active, .drill-fade-leave-active { transition: opacity .25s; }
.drill-fade-enter-from, .drill-fade-leave-to { opacity: 0; }

/* ═══════════ RESPONSIVE ═══════════ */
@media (max-width: 1100px) {
  .sep-hero-inner { flex-direction: column; align-items: flex-start; }
  .sep-hero-kpis { width: 100%; justify-content: space-between; }
}
@media (max-width: 991.98px) {
  .sep-metrics { grid-template-columns: repeat(2, 1fr); }
  .sep-actions { grid-template-columns: repeat(2, 1fr); }
  .sep-dept-grid { grid-template-columns: repeat(2, 1fr); }
  .sep-presence-grid { grid-template-columns: repeat(2, 1fr); }
  .sep-recent-grid { grid-template-columns: 1fr; }
  .sep-holidays-grid { grid-template-columns: 1fr; }
  .sep-affectations-row { grid-template-columns: repeat(2, 1fr); }
  .sep-task-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 767.98px) {
  .sep-dashboard { padding: 0 .5rem 1.5rem; }
  .sep-hero { border-radius: 16px; margin-bottom: 1.2rem; }
  .sep-hero-inner { padding: 1.3rem 1.2rem; gap: 1rem; }
  .sep-hero-badge { width: 48px; height: 48px; font-size: 1.2rem; }
  .sep-hero-name { font-size: 1.2rem; }
  .sep-hero-kpis { padding: .6rem .8rem; flex-wrap: wrap; gap: .5rem; }
  .sep-kpi { padding: 0 .6rem; }
  .sep-kpi-val { font-size: 1.15rem; }
  .kpi-divider { display: none; }
  .sep-actions { grid-template-columns: 1fr 1fr; gap: .5rem; }
  .sep-metrics { grid-template-columns: repeat(2, 1fr); }
  .sep-dept-grid { grid-template-columns: 1fr; }
  .sep-presence-grid { grid-template-columns: 1fr; }
  .sep-affectations-row { grid-template-columns: repeat(2, 1fr); }
  .sep-plan-global-row { flex-direction: column; align-items: center; }
  .sep-task-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 575.98px) {
  .sep-actions { grid-template-columns: 1fr; }
  .sep-affectations-row { grid-template-columns: 1fr 1fr; }
}
</style>
