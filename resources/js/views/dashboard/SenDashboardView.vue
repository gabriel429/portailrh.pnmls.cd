<template>
  <div class="sen-dashboard">
    <!-- ═══ HERO ═══ -->
    <div class="sen-hero">
      <div class="sen-hero-bg"></div>
      <div class="sen-hero-inner">
        <div class="sen-hero-left">
          <div class="sen-hero-badge">
            <i class="fas fa-crown"></i>
          </div>
          <div>
            <div class="sen-hero-greeting">Bienvenue,</div>
            <h1 class="sen-hero-name">
              {{ auth.agent ? auth.agent.prenom + ' ' + auth.agent.nom : auth.user?.name || 'SEN' }}
            </h1>
            <div class="sen-hero-role">
              <i class="fas fa-shield-alt me-1"></i>
              Secrétariat Exécutif National
            </div>
            <div class="sen-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>
        <div class="sen-hero-kpis">
          <div class="sen-kpi">
            <div class="sen-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="sen-kpi-val">{{ data.agents?.actifs ?? '-' }}</div>
              <div class="sen-kpi-lbl">Agents actifs</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sen-kpi">
            <div class="sen-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="sen-kpi-val">{{ data.attendance?.today_rate ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="sen-kpi-lbl">Présence</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sen-kpi">
            <div class="sen-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="sen-kpi-val">{{ data.requests?.en_attente ?? 0 }}</div>
              <div class="sen-kpi-lbl">En attente</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sen-kpi">
            <div class="sen-kpi-icon"><i class="fas fa-bullseye"></i></div>
            <div>
              <div class="sen-kpi-val">{{ data.plan_travail?.avg_completion ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="sen-kpi-lbl">Plan annuel</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord exécutif..." />

    <div v-else-if="loadError" class="alert alert-warning mx-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <template v-else>
      <!-- ═══ QUICK ACTIONS ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#e0f2fe;color:#0077B5;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Actions rapides</h3>
            <p class="sen-section-sub">Accès direct aux modules clés</p>
          </div>
        </div>
        <div class="sen-actions">
          <router-link v-for="a in quickActions" :key="a.to" :to="a.to" class="sen-action">
            <div class="sen-action-glow" :style="{ background: a.color }"></div>
            <div class="sen-action-icon" :style="{ background: a.bg, color: a.color }">
              <i class="fas" :class="a.icon"></i>
            </div>
            <div class="sen-action-text">
              <div class="sen-action-label">{{ a.label }}</div>
              <div class="sen-action-desc">{{ a.desc }}</div>
            </div>
            <i class="fas fa-chevron-right sen-action-arrow"></i>
          </router-link>
        </div>
      </div>

      <!-- ═══ KEY METRICS ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Indicateurs clés</h3>
            <p class="sen-section-sub">Vue d'ensemble de l'organisation</p>
          </div>
        </div>
        <div class="sen-metrics">
          <div v-for="m in metrics" :key="m.label" class="sen-metric sen-metric-clickable" @click="router.push(m.route)">
            <div class="sen-metric-header">
              <div class="sen-metric-icon" :style="{ background: m.bg, color: m.color }">
                <i class="fas" :class="m.icon"></i>
              </div>
              <span v-if="m.alert" class="sen-metric-alert">
                <i class="fas fa-exclamation-circle"></i>
              </span>
            </div>
            <div class="sen-metric-val" :style="{ color: m.color }">{{ m.value }}</div>
            <div class="sen-metric-lbl">{{ m.label }}</div>
            <div class="sen-metric-bar">
              <div class="sen-metric-bar-fill" :style="{ background: m.color, width: m.pct + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ AGENTS PAR ORGANE (full width, detailed) ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fas fa-sitemap"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Effectifs par organe</h3>
            <p class="sen-section-sub">{{ data.agents?.total ?? 0 }} agents au total, dont {{ data.agents?.actifs ?? 0 }} actifs</p>
          </div>
        </div>
        <div class="sen-organe-grid">
          <div v-for="o in organeCards" :key="o.code" class="sen-organe-card sen-organe-clickable" :style="{ borderTop: '4px solid ' + o.color }" @click="openOrganeDrilldown(o.code, 'effectifs')">
            <div class="sen-organe-header">
              <div class="sen-organe-badge" :style="{ background: o.color }">{{ o.code }}</div>
              <div>
                <div class="sen-organe-name">{{ o.nom }}</div>
                <div class="sen-organe-sub">{{ orgPct(o.total) }}% de l'effectif total</div>
              </div>
              <i class="fas fa-chevron-right sen-drill-arrow"></i>
            </div>
            <div class="sen-organe-stats">
              <div class="sen-organe-stat">
                <div class="sen-organe-stat-val" style="color:#1e293b;">{{ o.total }}</div>
                <div class="sen-organe-stat-lbl">Total</div>
              </div>
              <div class="sen-organe-stat">
                <div class="sen-organe-stat-val" style="color:#059669;">{{ o.actifs }}</div>
                <div class="sen-organe-stat-lbl">Actifs</div>
              </div>
              <div class="sen-organe-stat">
                <div class="sen-organe-stat-val" style="color:#d97706;">{{ o.suspendus }}</div>
                <div class="sen-organe-stat-lbl">Suspendus</div>
              </div>
              <div class="sen-organe-stat">
                <div class="sen-organe-stat-val" style="color:#64748b;">{{ o.anciens }}</div>
                <div class="sen-organe-stat-lbl">Anciens</div>
              </div>
            </div>
            <div class="sen-organe-bar-wrap">
              <div class="sen-organe-bar-bg">
                <div class="sen-organe-bar-fill" :style="{ background: o.color, width: orgPct(o.actifs) + '%' }"></div>
              </div>
              <div class="sen-organe-bar-label">{{ o.actifs }} actifs sur {{ data.agents?.actifs ?? 0 }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PRESENCE PAR ORGANE ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-user-check"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Présence par organe</h3>
            <p class="sen-section-sub">{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }} présents aujourd'hui ({{ data.attendance?.today_rate ?? 0 }}%)</p>
          </div>
        </div>
        <div class="sen-presence-grid">
          <!-- Global -->
          <div class="sen-presence-card sen-presence-global">
            <div class="sen-presence-card-head">
              <i class="fas fa-globe-africa"></i> Global
            </div>
            <div class="sen-presence-big">{{ data.attendance?.today_rate ?? 0 }}%</div>
            <div class="sen-presence-sub">{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }}</div>
            <div class="sen-presence-item">
              <span>Aujourd'hui</span>
              <span class="fw-bold">{{ data.attendance?.today_rate ?? 0 }}%</span>
            </div>
            <div class="sen-presence-bar">
              <div class="sen-presence-fill" style="background:linear-gradient(90deg,#059669,#34d399);" :style="{ width: (data.attendance?.today_rate ?? 0) + '%' }"></div>
            </div>
            <div class="sen-presence-item">
              <span>Moy. mensuelle</span>
              <span class="fw-bold">{{ data.attendance?.monthly_avg_rate ?? 0 }}%</span>
            </div>
            <div class="sen-presence-bar">
              <div class="sen-presence-fill" style="background:linear-gradient(90deg,#0077B5,#38bdf8);" :style="{ width: (data.attendance?.monthly_avg_rate ?? 0) + '%' }"></div>
            </div>
          </div>
          <!-- Par organe -->
          <div v-for="o in presenceOrganes" :key="o.code" class="sen-presence-card sen-organe-clickable" @click="openOrganeDrilldown(o.code, 'presence')">
            <div class="sen-presence-card-head" :style="{ color: o.color }">
              <i class="fas" :class="o.icon"></i> {{ o.label }}
              <i class="fas fa-chevron-right sen-drill-arrow" style="margin-left:auto;"></i>
            </div>
            <div class="sen-presence-big" :style="{ color: o.color }">{{ o.today_rate }}%</div>
            <div class="sen-presence-sub">{{ o.today_present }} / {{ o.total_active }} agents</div>
            <div class="sen-presence-item">
              <span>Aujourd'hui</span>
              <span class="fw-bold">{{ o.today_rate }}%</span>
            </div>
            <div class="sen-presence-bar">
              <div class="sen-presence-fill" :style="{ background: o.color, width: o.today_rate + '%' }"></div>
            </div>
            <div class="sen-presence-item">
              <span>Moy. mensuelle</span>
              <span class="fw-bold">{{ o.monthly_rate }}%</span>
            </div>
            <div class="sen-presence-bar">
              <div class="sen-presence-fill" :style="{ background: o.color, width: o.monthly_rate + '%', opacity: .6 }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PLAN DE TRAVAIL PAR ORGANE ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-tasks"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Plan de travail {{ currentYear }} par organe</h3>
            <p class="sen-section-sub">{{ data.plan_travail?.terminee ?? 0 }} / {{ data.plan_travail?.total ?? 0 }} activités terminées ({{ data.plan_travail?.avg_completion ?? 0 }}% global)</p>
          </div>
        </div>

        <!-- Global trimestres -->
        <div class="sen-plan-global-row">
          <div class="sen-plan-ring-wrap">
            <svg viewBox="0 0 120 120" class="sen-ring-svg">
              <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="10"/>
              <circle cx="60" cy="60" r="52" fill="none" stroke="#0077B5" stroke-width="10"
                stroke-linecap="round"
                :stroke-dasharray="ringDash"
                :stroke-dashoffset="ringOffset"
                transform="rotate(-90 60 60)"/>
            </svg>
            <div class="sen-ring-center">
              <div class="sen-ring-val">{{ data.plan_travail?.avg_completion ?? 0 }}%</div>
              <div class="sen-ring-lbl">Global</div>
            </div>
          </div>
          <div class="sen-plan-trims">
            <div v-for="t in (data.plan_travail?.by_trimestre || [])" :key="t.trimestre" class="sen-trim">
              <div class="sen-trim-head">
                <span class="sen-trim-name">{{ t.trimestre }}</span>
                <span class="sen-trim-pct">{{ t.avg_pourcentage }}%</span>
              </div>
              <div class="sen-trim-bar">
                <div class="sen-trim-fill" :style="{ width: t.avg_pourcentage + '%' }"></div>
              </div>
              <div class="sen-trim-detail">{{ t.terminee }}/{{ t.total }} terminées</div>
            </div>
          </div>
        </div>

        <!-- Par organe -->
        <div class="sen-plan-organe-grid">
          <div v-for="po in planOrganes" :key="po.code" class="sen-plan-organe-card sen-organe-clickable" :style="{ borderLeft: '4px solid ' + po.color }" @click="openOrganeDrilldown(po.code, 'pta')">
            <div class="sen-plan-organe-head">
              <div class="sen-plan-organe-badge" :style="{ background: po.color }">{{ po.code }}</div>
              <div class="sen-plan-organe-info">
                <div class="sen-plan-organe-name">{{ po.label }}</div>
                <div class="sen-plan-organe-summary">{{ po.terminee }}/{{ po.total }} terminées &middot; <strong>{{ po.avg }}%</strong></div>
              </div>
              <div class="sen-plan-organe-pct" :style="{ color: po.color }">{{ po.avg }}%</div>
            </div>
            <div class="sen-plan-organe-trims">
              <div v-for="t in po.trims" :key="t.trimestre" class="sen-plan-organe-trim">
                <div class="sen-plan-organe-trim-head">
                  <span>{{ t.trimestre }}</span>
                  <span class="fw-bold">{{ t.avg_pourcentage }}%</span>
                </div>
                <div class="sen-plan-organe-trim-bar">
                  <div class="sen-plan-organe-trim-fill" :style="{ width: t.avg_pourcentage + '%', background: po.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ ACTIVITES RECENTES ═══ -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#fce7f3;color:#db2777;">
            <i class="fas fa-stream"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Activités récentes</h3>
            <p class="sen-section-sub">Dernières mises à jour organisationnelles</p>
          </div>
        </div>
        <div class="sen-recent-grid">
          <!-- Communiques -->
          <div class="sen-recent-col">
            <div class="sen-recent-head" style="border-color:#0891b2;">
              <div class="sen-recent-head-icon" style="background:#cffafe;color:#0891b2;">
                <i class="fas fa-bullhorn"></i>
              </div>
              <div>
                <div class="sen-recent-head-title">Communiqués</div>
                <div class="sen-recent-head-count">{{ data.communiques?.actifs ?? 0 }} actifs</div>
              </div>
              <span v-if="data.communiques?.urgents" class="sen-alert-badge">{{ data.communiques.urgents }} urgent(s)</span>
            </div>
            <div v-if="data.communiques?.recent?.length" class="sen-recent-body">
              <router-link v-for="c in data.communiques.recent" :key="c.id" :to="'/communiques/' + c.id" class="sen-recent-item">
                <div class="sen-recent-dot" style="background:#0891b2;"></div>
                <div class="sen-recent-info">
                  <div class="sen-recent-title">{{ c.titre }}</div>
                  <div class="sen-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(c.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="sen-recent-empty">
              <div class="sen-empty-icon-wrap" style="background:#ecfeff;">
                <i class="fas fa-inbox" style="color:#0891b2;"></i>
              </div>
              <span>Aucun communiqué récent</span>
              <span class="sen-empty-hint">Les communiqués publiés apparaîtront ici</span>
            </div>
          </div>

          <!-- Demandes en attente -->
          <div class="sen-recent-col">
            <div class="sen-recent-head" style="border-color:#d97706;">
              <div class="sen-recent-head-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <div class="sen-recent-head-title">Demandes en attente</div>
                <div class="sen-recent-head-count">{{ data.requests?.en_attente ?? 0 }} en attente</div>
              </div>
            </div>
            <div v-if="data.requests?.recent_pending?.length" class="sen-recent-body">
              <div v-for="r in data.requests.recent_pending" :key="r.id" class="sen-recent-item">
                <div class="sen-recent-dot" style="background:#d97706;"></div>
                <div class="sen-recent-info">
                  <div class="sen-recent-title">{{ r.type }} — {{ r.agent?.prenom }} {{ r.agent?.nom }}</div>
                  <div class="sen-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(r.created_at) }}</div>
                </div>
              </div>
            </div>
            <div v-else class="sen-recent-empty">
              <div class="sen-empty-icon-wrap" style="background:#fef9ee;">
                <i class="fas fa-check-circle" style="color:#16a34a;"></i>
              </div>
              <span>Aucune demande en attente</span>
              <span class="sen-empty-hint">Toutes les demandes sont traitées</span>
            </div>
          </div>

          <!-- Signalements -->
          <div class="sen-recent-col">
            <div class="sen-recent-head" style="border-color:#dc2626;">
              <div class="sen-recent-head-icon" style="background:#fee2e2;color:#dc2626;">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div>
                <div class="sen-recent-head-title">Signalements</div>
                <div class="sen-recent-head-count">{{ data.signalements?.ouvert ?? 0 }} ouverts</div>
              </div>
              <span v-if="data.signalements?.haute_severite" class="sen-alert-badge">{{ data.signalements.haute_severite }} haute</span>
            </div>
            <div v-if="data.signalements?.recent?.length" class="sen-recent-body">
              <router-link v-for="s in data.signalements.recent" :key="s.id" :to="'/signalements/' + s.id" class="sen-recent-item">
                <div class="sen-recent-dot" :class="'sev-bg-' + s.severite"></div>
                <div class="sen-recent-info">
                  <div class="sen-recent-title">{{ s.type }}
                    <span class="sen-sev-badge" :class="'sev-' + s.severite">{{ s.severite }}</span>
                  </div>
                  <div class="sen-recent-time"><i class="fas fa-clock me-1"></i>{{ formatTime(s.created_at) }}</div>
                </div>
              </router-link>
            </div>
            <div v-else class="sen-recent-empty">
              <div class="sen-empty-icon-wrap" style="background:#fef2f2;">
                <i class="fas fa-shield-alt" style="color:#dc2626;"></i>
              </div>
              <span>Aucun signalement ouvert</span>
              <span class="sen-empty-hint">Aucune alerte à traiter</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ CONGÉS & DISPONIBILITÉS ═══ [NOUVEAU] -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#fef3c7;color:#f59e0b;">
            <i class="fas fa-umbrella-beach"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Congés & Disponibilités</h3>
            <p class="sen-section-sub">{{ data.holidays?.active_today ?? 0 }} agents en congé aujourd'hui — {{ data.holidays?.pending ?? 0 }} demandes en attente</p>
          </div>
        </div>
        <div class="sen-holidays-grid">
          <!-- Stats congés -->
          <div class="sen-holiday-stats-card">
            <div class="sen-holiday-stat-item">
              <div class="sen-holiday-stat-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="fas fa-check-circle"></i>
              </div>
              <div>
                <div class="sen-holiday-stat-val">{{ data.holidays?.approved ?? 0 }}</div>
                <div class="sen-holiday-stat-lbl">Approuvés</div>
              </div>
            </div>
            <div class="sen-holiday-stat-item">
              <div class="sen-holiday-stat-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <div class="sen-holiday-stat-val">{{ data.holidays?.pending ?? 0 }}</div>
                <div class="sen-holiday-stat-lbl">En attente</div>
              </div>
            </div>
            <div class="sen-holiday-stat-item">
              <div class="sen-holiday-stat-icon" style="background:#e0f2fe;color:#0077B5;">
                <i class="fas fa-percent"></i>
              </div>
              <div>
                <div class="sen-holiday-stat-val">{{ data.holidays?.taux_utilisation_pct ?? 0 }}%</div>
                <div class="sen-holiday-stat-lbl">Taux utilisation</div>
              </div>
            </div>
          </div>

          <!-- Agents en congé aujourd'hui -->
          <div class="sen-holiday-list-card">
            <div class="sen-holiday-list-head">
              <i class="fas fa-user-clock"></i> En congé aujourd'hui ({{ data.holidays?.active_today ?? 0 }})
            </div>
            <div v-if="data.holidays?.agents_en_conge_today?.length" class="sen-holiday-list">
              <div v-for="h in data.holidays.agents_en_conge_today.slice(0, 5)" :key="h.id" class="sen-holiday-item">
                <div class="sen-holiday-item-agent">{{ h.agent }}</div>
                <div class="sen-holiday-item-info">
                  <span class="sen-holiday-badge">{{ h.type }}</span>
                  <span class="sen-holiday-dates">{{ h.date_debut }} → {{ h.date_fin }}</span>
                </div>
              </div>
            </div>
            <div v-else class="sen-recent-empty" style="padding:1.5rem;">
              <i class="fas fa-check-circle"></i>
              <span>Aucun agent en congé</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ AFFECTATIONS & POSTES VACANTS ═══ [NOUVEAU] -->
      <div class="sen-section">
        <div class="sen-section-head">
          <div class="sen-section-icon" style="background:#fee2e2;color:#dc2626;">
            <i class="fas fa-user-slash"></i>
          </div>
          <div>
            <h3 class="sen-section-title">Affectations & Postes vacants</h3>
            <p class="sen-section-sub">{{ data.affectations?.postes_vacants ?? 0 }} postes vacants — {{ data.affectations?.agents_sans_affectation ?? 0 }} agents sans affectation</p>
          </div>
        </div>
        <div class="sen-affectations-row">
          <div class="sen-affectation-card sen-affectation-alert sen-clickable" @click="router.push('/rh/affectations')">
            <div class="sen-affectation-icon" style="background:#fee2e2;color:#dc2626;">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
              <div class="sen-affectation-val">{{ data.affectations?.postes_vacants ?? 0 }}</div>
              <div class="sen-affectation-lbl">Postes vacants</div>
            </div>
          </div>
          <div class="sen-affectation-card sen-affectation-warning sen-clickable" @click="router.push('/rh/affectations')">
            <div class="sen-affectation-icon" style="background:#fef3c7;color:#d97706;">
              <i class="fas fa-user-slash"></i>
            </div>
            <div>
              <div class="sen-affectation-val">{{ data.affectations?.agents_sans_affectation ?? 0 }}</div>
              <div class="sen-affectation-lbl">Agents sans affectation</div>
            </div>
          </div>
          <div class="sen-affectation-card sen-clickable" @click="router.push('/rh/affectations')">
            <div class="sen-affectation-icon" style="background:#e0f2fe;color:#0077B5;">
              <i class="fas fa-exchange-alt"></i>
            </div>
            <div>
              <div class="sen-affectation-val">{{ (data.affectations?.mobilite_30_jours ?? []).length }}</div>
              <div class="sen-affectation-lbl">Mobilité (30j)</div>
            </div>
          </div>
          <div class="sen-affectation-card sen-clickable" @click="router.push('/rh/affectations')">
            <div class="sen-affectation-icon" style="background:#dcfce7;color:#16a34a;">
              <i class="fas fa-briefcase"></i>
            </div>
            <div>
              <div class="sen-affectation-val">{{ data.affectations?.actives ?? 0 }}</div>
              <div class="sen-affectation-lbl">Affectations actives</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ TÂCHES & AUDIT ═══ -->
      <div class="sen-section">
        <div class="sen-duo-grid">
          <!-- Tâches -->
          <div class="sen-duo-block">
            <div class="sen-section-head">
              <div class="sen-section-icon" style="background:#ede9fe;color:#7c3aed;">
                <i class="fas fa-clipboard-list"></i>
              </div>
              <div>
                <h3 class="sen-section-title">Gestion des tâches</h3>
                <p class="sen-section-sub">{{ data.taches?.total ?? 0 }} tâches au total</p>
              </div>
            </div>
            <div class="sen-task-grid">
              <div class="sen-task-card">
                <div class="sen-task-card-icon" style="background:#dbeafe;color:#3b82f6;">
                  <i class="fas fa-plus-circle"></i>
                </div>
                <div class="sen-task-card-val">{{ data.taches?.nouvelle ?? 0 }}</div>
                <div class="sen-task-card-lbl">Nouvelles</div>
              </div>
              <div class="sen-task-card">
                <div class="sen-task-card-icon" style="background:#fef3c7;color:#d97706;">
                  <i class="fas fa-spinner"></i>
                </div>
                <div class="sen-task-card-val">{{ data.taches?.en_cours ?? 0 }}</div>
                <div class="sen-task-card-lbl">En cours</div>
              </div>
              <div class="sen-task-card">
                <div class="sen-task-card-icon" style="background:#dcfce7;color:#16a34a;">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="sen-task-card-val">{{ data.taches?.terminee ?? 0 }}</div>
                <div class="sen-task-card-lbl">Terminées</div>
              </div>
              <div class="sen-task-card" :class="{ 'sen-task-card-alert': (data.taches?.overdue ?? 0) > 0 }">
                <div class="sen-task-card-icon" style="background:#fee2e2;color:#dc2626;">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="sen-task-card-val">{{ data.taches?.overdue ?? 0 }}</div>
                <div class="sen-task-card-lbl">En retard</div>
              </div>
            </div>
            <div v-if="(data.taches?.total ?? 0) > 0" class="sen-task-progress">
              <div class="sen-task-progress-bar">
                <div class="sen-task-progress-fill" :style="{ width: taskCompletionPct + '%' }"></div>
              </div>
              <div class="sen-task-progress-lbl">{{ taskCompletionPct }}% terminées</div>
            </div>
          </div>

          <!-- Audit (résumé compact) -->
          <div class="sen-duo-block">
            <div class="sen-section-head">
              <div class="sen-section-icon" style="background:#fee2e2;color:#7f1d1d;">
                <i class="fas fa-shield-alt"></i>
              </div>
              <div>
                <h3 class="sen-section-title">Audit & Sécurité</h3>
                <p class="sen-section-sub">{{ data.audit?.last_24h ?? 0 }} actions dernières 24h</p>
              </div>
            </div>
            <div class="sen-audit-compact">
              <div class="sen-audit-compact-stats">
                <div class="sen-audit-compact-stat">
                  <div class="sen-audit-compact-icon" style="background:#fee2e2;color:#dc2626;">
                    <i class="fas fa-lock"></i>
                  </div>
                  <div class="sen-audit-compact-val">{{ data.audit?.comptes_geles ?? 0 }}</div>
                  <div class="sen-audit-compact-lbl">Comptes gelés</div>
                </div>
                <div class="sen-audit-compact-stat">
                  <div class="sen-audit-compact-icon" style="background:#fef3c7;color:#d97706;">
                    <i class="fas fa-user-times"></i>
                  </div>
                  <div class="sen-audit-compact-val">{{ data.audit?.connexions_echouees_24h ?? 0 }}</div>
                  <div class="sen-audit-compact-lbl">Échecs login 24h</div>
                </div>
              </div>
              <div class="sen-audit-compact-list">
                <div class="sen-audit-compact-list-head">
                  <i class="fas fa-history"></i> Actions sensibles récentes
                </div>
                <template v-if="(data.audit?.actions_sensibles ?? []).length">
                  <div v-for="a in (data.audit.actions_sensibles ?? []).slice(0, 4)" :key="a.id" class="sen-audit-compact-item">
                    <div class="sen-audit-dot"></div>
                    <div class="sen-audit-compact-item-info">
                      <div class="sen-audit-compact-item-action">{{ a.action }}</div>
                      <div class="sen-audit-compact-item-meta">
                        <span v-if="a.user">{{ a.user }}</span>
                        <span v-if="a.description"> · {{ a.description }}</span>
                        <span class="sen-audit-compact-item-time">{{ formatTime(a.created_at) }}</span>
                      </div>
                    </div>
                  </div>
                </template>
                <div v-else class="sen-audit-compact-empty">
                  <i class="fas fa-check-circle"></i> Aucune action sensible
                </div>
              </div>
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
                    <button v-if="drilldownLevel === 'province'" class="drill-back" @click="backToOrgane">
                      <i class="fas fa-arrow-left"></i>
                    </button>
                    <div>
                      <div class="drill-header-title" v-if="drilldownLevel === 'organe' && drilldownOrgane">
                        <i class="fas fa-sitemap"></i> {{ drilldownOrgane.nom }}
                      </div>
                      <div class="drill-header-title" v-else-if="drilldownLevel === 'province' && drilldownProvince">
                        <i class="fas fa-map-marker-alt"></i> {{ drilldownProvince.province.nom }}
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

                  <!-- ── LEVEL 1 : ORGANE → ITEMS (provinces / départements) ── -->
                  <template v-else-if="drilldownLevel === 'organe' && drilldownOrgane">
                    <!-- Summary bar -->
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
                          <div class="drill-summary-val" :style="{ color: drilldownColor }">{{ drilldownOrgane.items.reduce((s, i) => s + (i.pta?.total || 0), 0) }}</div>
                          <div class="drill-summary-lbl">Activités</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#059669;">{{ drilldownOrgane.items.reduce((s, i) => s + (i.pta?.terminee || 0), 0) }}</div>
                          <div class="drill-summary-lbl">Terminées</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#d97706;">{{ drilldownOrgane.items.reduce((s, i) => s + (i.pta?.total || 0), 0) - drilldownOrgane.items.reduce((s, i) => s + (i.pta?.terminee || 0), 0) }}</div>
                          <div class="drill-summary-lbl">En cours</div>
                        </div>
                        <div class="drill-summary-item">
                          <div class="drill-summary-val" style="color:#0077B5;">{{ drilldownOrgane.items.length > 0 ? Math.round(drilldownOrgane.items.reduce((s, i) => s + (i.pta?.avg || 0), 0) / drilldownOrgane.items.length) : 0 }}%</div>
                          <div class="drill-summary-lbl">Moy. avancement</div>
                        </div>
                      </template>
                    </div>

                    <!-- Items list -->
                    <div class="drill-items-grid">
                      <div
                        v-for="item in drilldownOrgane.items" :key="item.id"
                        class="drill-item-card"
                        :class="{ 'drill-item-clickable': drilldownOrgane.type_items === 'provinces' }"
                        @click="drilldownOrgane.type_items === 'provinces' && openProvinceDrilldown(item.id)"
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
                          <i v-if="drilldownOrgane.type_items === 'provinces'" class="fas fa-chevron-right drill-item-arrow"></i>
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
                  </template>

                  <!-- ── LEVEL 2 : PROVINCE DETAIL ── -->
                  <template v-else-if="drilldownLevel === 'province' && drilldownProvince">
                    <!-- Province info -->
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

                    <!-- Stats row - adapté à la section -->
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

                    <!-- ─── Contenu EFFECTIFS ─── -->
                    <template v-if="drilldownSection === 'effectifs'">
                      <!-- Répartition par organe -->
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

                      <!-- Départements -->
                      <div v-if="drilldownProvince.departments.length" class="drill-prov-section-title"><i class="fas fa-building"></i> Départements</div>
                      <div class="drill-prov-dept-grid">
                        <div v-for="d in drilldownProvince.departments" :key="d.id" class="drill-prov-dept">
                          <div class="drill-prov-dept-name">{{ d.nom }}</div>
                          <div class="drill-prov-dept-count">{{ d.actifs }} <small>actifs</small> / {{ d.total }}</div>
                        </div>
                      </div>

                      <!-- Agents -->
                      <div v-if="drilldownProvince.agents.length" class="drill-prov-section-title"><i class="fas fa-user"></i> Agents ({{ drilldownProvince.agents.length }})</div>
                      <div class="drill-prov-agents-table">
                        <div v-for="a in drilldownProvince.agents" :key="a.id" class="drill-prov-agent-row">
                          <div class="drill-prov-agent-avatar" :style="{ background: a.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: a.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
                            <i :class="a.sexe === 'F' ? 'fas fa-female' : 'fas fa-male'"></i>
                          </div>
                          <div class="drill-prov-agent-info">
                            <div class="drill-prov-agent-name">{{ a.nom }}</div>
                            <div class="drill-prov-agent-fn">{{ a.fonction }}</div>
                          </div>
                        </div>
                      </div>
                    </template>

                    <!-- ─── Contenu PRÉSENCE ─── -->
                    <template v-else-if="drilldownSection === 'presence'">
                      <div class="drill-prov-section-title"><i class="fas fa-user"></i> Agents actifs ({{ drilldownProvince.presence.total_active }})</div>
                      <div class="drill-prov-agents-table">
                        <div v-for="a in drilldownProvince.agents" :key="a.id" class="drill-prov-agent-row">
                          <div class="drill-prov-agent-avatar" :style="{ background: a.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: a.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
                            <i :class="a.sexe === 'F' ? 'fas fa-female' : 'fas fa-male'"></i>
                          </div>
                          <div class="drill-prov-agent-info">
                            <div class="drill-prov-agent-name">{{ a.nom }}</div>
                            <div class="drill-prov-agent-fn">{{ a.fonction }}</div>
                          </div>
                        </div>
                      </div>
                    </template>

                    <!-- ─── Contenu PTA ─── -->
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
                                <span v-if="act.statut" class="drill-activite-statut" :class="'statut-' + (act.statut || '').toLowerCase().replace(/\s+/g, '-')">{{ act.statut }}</span>
                              </div>
                            </div>
                          </div>
                          <div class="drill-activite-bar">
                            <div class="drill-activite-bar-fill" :style="{ width: act.pourcentage + '%', background: act.pourcentage >= 100 ? '#059669' : act.pourcentage > 0 ? '#d97706' : '#e2e8f0' }"></div>
                          </div>
                          <div v-if="act.date_debut || act.date_fin" class="drill-activite-dates">
                            <i class="fas fa-calendar-alt"></i>
                            {{ act.date_debut || '?' }} → {{ act.date_fin || '?' }}
                          </div>
                        </div>
                      </div>
                      <div v-else class="drill-empty">
                        <i class="fas fa-clipboard"></i>
                        <p>Aucune activité PTA pour cette province</p>
                      </div>
                    </template>
                  </template>
                </div>
              </div>
            </Transition>
          </div>
        </Transition>
      </Teleport>

      <!-- ═══ FOOTER ═══ -->
      <div class="sen-footer">
        <div class="sen-footer-inner">
          <div class="sen-footer-left">
            <i class="fas fa-sync-alt"></i>
            <span>Tableau de bord mis à jour le {{ today }} à {{ currentTime }}</span>
          </div>
          <div class="sen-footer-right">
            <span class="sen-footer-badge">E-PNMLS v2.0</span>
          </div>
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

// ─── DRILL-DOWN STATE ───
const drilldownOpen = ref(false)
const drilldownLoading = ref(false)
const drilldownOrgane = ref(null)   // { organe, nom, type_items, summary, items }
const drilldownProvince = ref(null) // { province, effectifs, by_organe, presence, pta, departments, agents }
const drilldownLevel = ref('organe') // 'organe' | 'province'
const drilldownSection = ref('effectifs') // 'effectifs' | 'presence' | 'pta'

async function openOrganeDrilldown(code, section = 'effectifs') {
  drilldownOpen.value = true
  drilldownLoading.value = true
  drilldownLevel.value = 'organe'
  drilldownSection.value = section
  drilldownProvince.value = null
  try {
    const { data: result } = await client.get(`/dashboard/executive/organe/${code}`)
    drilldownOrgane.value = result.data ?? result
  } catch (e) {
    drilldownOrgane.value = null
  } finally {
    drilldownLoading.value = false
  }
}

async function openProvinceDrilldown(id) {
  drilldownLoading.value = true
  drilldownLevel.value = 'province'
  try {
    const { data: result } = await client.get(`/dashboard/executive/province/${id}`)
    drilldownProvince.value = result.data ?? result
  } catch (e) {
    drilldownProvince.value = null
  } finally {
    drilldownLoading.value = false
  }
}

function closeDrilldown() {
  drilldownOpen.value = false
  drilldownOrgane.value = null
  drilldownProvince.value = null
  drilldownLevel.value = 'organe'
  drilldownSection.value = 'effectifs'
}

function backToOrgane() {
  drilldownLevel.value = 'organe'
  drilldownProvince.value = null
}

const drilldownColor = computed(() => {
  const code = drilldownOrgane.value?.organe
  if (code === 'SEN') return '#0077B5'
  if (code === 'SEP') return '#0ea5e9'
  if (code === 'SEL') return '#0d9488'
  return '#0077B5'
})

const quickActions = [
  { to: '/rh/communiques/create', label: 'Nouveau communiqué', desc: 'Publier un communiqué', icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe' },
  { to: '/rh/agents', label: 'Gestion agents', desc: 'Voir tous les agents', icon: 'fa-users', color: '#0077B5', bg: '#e0f2fe' },
  { to: '/signalements', label: 'Signalements', desc: 'Consulter les alertes', icon: 'fa-flag', color: '#dc2626', bg: '#fee2e2' },
  { to: '/plan-travail', label: 'PTA', desc: 'Suivi stratégique annuel', icon: 'fa-tasks', color: '#d97706', bg: '#fef3c7' },
  { to: '/rh/pointages/monthly', label: 'Pointages', desc: 'Rapport mensuel', icon: 'fa-clock', color: '#7c3aed', bg: '#ede9fe' },
  { to: '/requests', label: 'Demandes', desc: 'Gérer les demandes', icon: 'fa-paper-plane', color: '#059669', bg: '#d1fae5' },
]

const maxMetric = computed(() => {
  const vals = [
    data.value.agents?.total ?? 0,
    data.value.agents?.actifs ?? 0,
    data.value.requests?.en_attente ?? 0,
    data.value.requests?.approuve ?? 0,
    data.value.signalements?.ouvert ?? 0,
    data.value.taches?.en_cours ?? 0,
    data.value.communiques?.actifs ?? 0,
    data.value.documents?.total ?? 0,
  ]
  return Math.max(...vals, 1)
})

const metrics = computed(() => [
  { label: 'Agents total', value: data.value.agents?.total ?? 0, icon: 'fa-users', color: '#0077B5', bg: '#e0f2fe', pct: pct(data.value.agents?.total), alert: false, route: '/rh/agents' },
  { label: 'Agents actifs', value: data.value.agents?.actifs ?? 0, icon: 'fa-user-check', color: '#059669', bg: '#d1fae5', pct: pct(data.value.agents?.actifs), alert: false, route: '/rh/agents' },
  { label: 'Demandes en attente', value: data.value.requests?.en_attente ?? 0, icon: 'fa-hourglass-half', color: '#d97706', bg: '#fef3c7', pct: pct(data.value.requests?.en_attente), alert: (data.value.requests?.en_attente ?? 0) > 5, route: '/requests' },
  { label: 'Demandes approuvées', value: data.value.requests?.approuve ?? 0, icon: 'fa-check-double', color: '#16a34a', bg: '#dcfce7', pct: pct(data.value.requests?.approuve), alert: false, route: '/requests' },
  { label: 'Signalements ouverts', value: data.value.signalements?.ouvert ?? 0, icon: 'fa-exclamation-circle', color: '#dc2626', bg: '#fee2e2', pct: pct(data.value.signalements?.ouvert), alert: (data.value.signalements?.ouvert ?? 0) > 0, route: '/signalements' },
  { label: 'Tâches en cours', value: data.value.taches?.en_cours ?? 0, icon: 'fa-spinner', color: '#7c3aed', bg: '#ede9fe', pct: pct(data.value.taches?.en_cours), alert: false, route: '/taches' },
  { label: 'Communiqués actifs', value: data.value.communiques?.actifs ?? 0, icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe', pct: pct(data.value.communiques?.actifs), alert: false, route: '/rh/communiques' },
  { label: 'Documents', value: data.value.documents?.total ?? 0, icon: 'fa-folder-open', color: '#6366f1', bg: '#e0e7ff', pct: pct(data.value.documents?.total), alert: false, route: '/documents' },
])

function pct(val) {
  return Math.min(((val ?? 0) / maxMetric.value) * 100, 100)
}

// ─── ORGANES: agents breakdown ───
const organeCards = computed(() => {
  const bo = data.value.agents?.by_organe || {}
  return [
    { code: 'SEN', nom: 'Secrétariat Exécutif National', total: bo.sen?.total ?? 0, actifs: bo.sen?.actifs ?? 0, suspendus: bo.sen?.suspendus ?? 0, anciens: bo.sen?.anciens ?? 0, color: '#0077B5' },
    { code: 'SEP', nom: 'Secrétariat Exécutif Provincial', total: bo.sep?.total ?? 0, actifs: bo.sep?.actifs ?? 0, suspendus: bo.sep?.suspendus ?? 0, anciens: bo.sep?.anciens ?? 0, color: '#0ea5e9' },
    { code: 'SEL', nom: 'Secrétariat Exécutif Local', total: bo.sel?.total ?? 0, actifs: bo.sel?.actifs ?? 0, suspendus: bo.sel?.suspendus ?? 0, anciens: bo.sel?.anciens ?? 0, color: '#0d9488' },
  ]
})

function orgPct(count) {
  const total = data.value.agents?.actifs || 1
  return Math.round((count / total) * 100)
}

// ─── PRESENCE par organe ───
const presenceOrganes = computed(() => {
  const att = data.value.attendance?.by_organe || {}
  return [
    { code: 'SEN', label: 'National', icon: 'fa-flag', color: '#0077B5', today_present: att.sen?.today_present ?? 0, today_rate: att.sen?.today_rate ?? 0, monthly_rate: att.sen?.monthly_avg_rate ?? 0, total_active: att.sen?.total_active_agents ?? 0 },
    { code: 'SEP', label: 'Provincial', icon: 'fa-map-marked-alt', color: '#0ea5e9', today_present: att.sep?.today_present ?? 0, today_rate: att.sep?.today_rate ?? 0, monthly_rate: att.sep?.monthly_avg_rate ?? 0, total_active: att.sep?.total_active_agents ?? 0 },
    { code: 'SEL', label: 'Local', icon: 'fa-map-pin', color: '#0d9488', today_present: att.sel?.today_present ?? 0, today_rate: att.sel?.today_rate ?? 0, monthly_rate: att.sel?.monthly_avg_rate ?? 0, total_active: att.sel?.total_active_agents ?? 0 },
  ]
})

// ─── PLAN par organe ───
const planOrganes = computed(() => {
  const bo = data.value.plan_travail?.by_organe || {}
  return [
    { code: 'SEN', label: 'National (SEN)', color: '#0077B5', total: bo.sen?.total ?? 0, terminee: bo.sen?.terminee ?? 0, avg: bo.sen?.avg_completion ?? 0, trims: bo.sen?.by_trimestre || [] },
    { code: 'SEP', label: 'Provincial (SEP)', color: '#0ea5e9', total: bo.sep?.total ?? 0, terminee: bo.sep?.terminee ?? 0, avg: bo.sep?.avg_completion ?? 0, trims: bo.sep?.by_trimestre || [] },
    { code: 'SEL', label: 'Local (SEL)', color: '#0d9488', total: bo.sel?.total ?? 0, terminee: bo.sel?.terminee ?? 0, avg: bo.sel?.avg_completion ?? 0, trims: bo.sel?.by_trimestre || [] },
  ]
})

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

const loadError = ref(null)

onMounted(async () => {
  try {
    const { data: result } = await client.get('/dashboard/executive')
    data.value = result
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Impossible de charger le tableau de bord exécutif.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.sen-dashboard { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }

/* ═══════════ HERO ═══════════ */
.sen-hero {
  position: relative; border-radius: 20px; overflow: hidden; margin-bottom: 1.8rem;
  background: linear-gradient(135deg, #0a1628 0%, #0f2847 30%, #0c4a6e 60%, #0077B5 100%);
  box-shadow: 0 8px 32px rgba(0, 30, 60, .25);
}
.sen-hero-bg {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 400px 300px at 85% 20%, rgba(0,119,181,.25) 0%, transparent 70%),
    radial-gradient(ellipse 200px 200px at 10% 80%, rgba(56,189,248,.1) 0%, transparent 60%);
  pointer-events: none;
}
.sen-hero-inner {
  position: relative; z-index: 1; padding: 2rem 2.5rem;
  display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;
}
.sen-hero-left { display: flex; align-items: center; gap: 1.2rem; color: #fff; }
.sen-hero-badge {
  width: 60px; height: 60px; border-radius: 16px; flex-shrink: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.15), rgba(255,255,255,.05));
  border: 1px solid rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: #fbbf24; backdrop-filter: blur(8px);
}
.sen-hero-greeting { font-size: .82rem; opacity: .6; font-weight: 500; letter-spacing: .5px; text-transform: uppercase; }
.sen-hero-name { font-size: 1.5rem; font-weight: 800; margin: .1rem 0 .35rem; line-height: 1.15; color: #fff; }
.sen-hero-role { font-size: .78rem; font-weight: 600; opacity: .75; margin-bottom: .2rem; display: flex; align-items: center; }
.sen-hero-date { font-size: .72rem; opacity: .45; text-transform: capitalize; }

.sen-hero-kpis {
  display: flex; align-items: center; gap: 0;
  background: rgba(255,255,255,.08); border-radius: 16px; padding: .8rem 1.2rem;
  border: 1px solid rgba(255,255,255,.1); backdrop-filter: blur(8px);
}
.sen-kpi { display: flex; align-items: center; gap: .6rem; padding: 0 1rem; color: #fff; }
.sen-kpi-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.1); display: flex; align-items: center;
  justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.sen-kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.kpi-unit { font-size: .85rem; font-weight: 600; opacity: .7; }
.sen-kpi-lbl { font-size: .62rem; opacity: .5; text-transform: uppercase; letter-spacing: .4px; margin-top: .1rem; font-weight: 600; }
.kpi-divider { width: 1px; height: 36px; background: rgba(255,255,255,.12); margin: 0 .2rem; }

/* ═══════════ SECTIONS ═══════════ */
.sen-section { margin-bottom: 1.8rem; }
.sen-section-head { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.sen-section-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0;
}
.sen-section-title { font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; }
.sen-section-sub { font-size: .72rem; color: #94a3b8; margin: 0; font-weight: 500; }

/* ═══════════ QUICK ACTIONS ═══════════ */
.sen-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sen-action {
  position: relative; display: flex; align-items: center; gap: .8rem; padding: 1rem 1.1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
  text-decoration: none; color: #374151; transition: all .25s; overflow: hidden;
}
.sen-action-glow { position: absolute; top: 0; left: 0; width: 3px; height: 100%; opacity: 0; transition: opacity .25s; }
.sen-action:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-action:hover .sen-action-glow { opacity: 1; }
.sen-action-icon {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.sen-action-text { flex: 1; min-width: 0; }
.sen-action-label { font-size: .84rem; font-weight: 700; line-height: 1.2; }
.sen-action-desc { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
.sen-action-arrow { font-size: .65rem; color: #cbd5e1; transition: all .2s; }
.sen-action:hover .sen-action-arrow { color: #0077B5; transform: translateX(3px); }

/* ═══════════ METRICS ═══════════ */
.sen-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.sen-metric {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.1rem; transition: all .25s; position: relative;
}
.sen-metric-clickable { cursor: pointer; }
.sen-clickable { cursor: pointer; }
.sen-metric:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-metric-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .7rem; }
.sen-metric-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem;
}
.sen-metric-alert { color: #ef4444; font-size: .75rem; animation: pulse-alert 2s infinite; }
@keyframes pulse-alert { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
.sen-metric-val { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.sen-metric-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.sen-metric-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: .7rem; }
.sen-metric-bar-fill { height: 100%; border-radius: 4px; transition: width .8s ease; min-width: 3px; }

/* ═══════════ ORGANE CARDS (agents) ═══════════ */
.sen-organe-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sen-organe-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.3rem; transition: all .25s;
}
.sen-organe-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-organe-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.sen-organe-badge {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center;
  font-size: .72rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.sen-organe-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
.sen-organe-sub { font-size: .68rem; color: #94a3b8; }
.sen-organe-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; margin-bottom: 1rem; }
.sen-organe-stat { text-align: center; padding: .5rem .25rem; background: #f8fafc; border-radius: 10px; }
.sen-organe-stat-val { font-size: 1.3rem; font-weight: 800; line-height: 1; }
.sen-organe-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }
.sen-organe-bar-wrap {}
.sen-organe-bar-bg { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.sen-organe-bar-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }
.sen-organe-bar-label { font-size: .62rem; color: #94a3b8; margin-top: .25rem; }

/* ═══════════ PRESENCE PAR ORGANE ═══════════ */
.sen-presence-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.sen-presence-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s;
}
.sen-presence-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-presence-global { border-top: 4px solid #059669; }
.sen-presence-card-head {
  font-size: .88rem; font-weight: 700; color: #1e293b; margin-bottom: .8rem;
  display: flex; align-items: center; gap: .5rem;
}
.sen-presence-big { font-size: 2rem; font-weight: 800; text-align: center; line-height: 1; margin-bottom: .15rem; }
.sen-presence-sub { font-size: .7rem; color: #94a3b8; text-align: center; margin-bottom: .8rem; }
.sen-presence-item { display: flex; justify-content: space-between; font-size: .75rem; color: #475569; margin-bottom: .25rem; }
.sen-presence-bar { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; margin-bottom: .6rem; }
.sen-presence-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }

/* ═══════════ PLAN DE TRAVAIL ═══════════ */
.sen-plan-global-row {
  display: flex; align-items: flex-start; gap: 1.5rem; padding: 1.3rem;
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  margin-bottom: .75rem;
}
.sen-plan-ring-wrap { position: relative; flex-shrink: 0; }
.sen-ring-svg { width: 120px; height: 120px; }
.sen-ring-center {
  position: absolute; inset: 0; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.sen-ring-val { font-size: 1.5rem; font-weight: 800; color: #0077B5; line-height: 1; }
.sen-ring-lbl { font-size: .62rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; }
.sen-plan-trims { flex: 1; display: flex; flex-direction: column; gap: .75rem; }
.sen-trim {}
.sen-trim-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .3rem; }
.sen-trim-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sen-trim-pct { font-size: .78rem; font-weight: 700; color: #0077B5; }
.sen-trim-bar { height: 8px; background: #f1f5f9; border-radius: 8px; overflow: hidden; }
.sen-trim-fill {
  height: 100%; border-radius: 8px; transition: width .8s ease;
  background: linear-gradient(90deg, #0077B5, #38bdf8);
}
.sen-trim-detail { font-size: .65rem; color: #94a3b8; margin-top: .2rem; }

.sen-plan-organe-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sen-plan-organe-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s;
}
.sen-plan-organe-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-plan-organe-head { display: flex; align-items: center; gap: .6rem; margin-bottom: 1rem; }
.sen-plan-organe-badge {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center;
  font-size: .65rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.sen-plan-organe-info { flex: 1; min-width: 0; }
.sen-plan-organe-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sen-plan-organe-summary { font-size: .68rem; color: #94a3b8; }
.sen-plan-organe-pct { font-size: 1.4rem; font-weight: 800; flex-shrink: 0; }
.sen-plan-organe-trims { display: flex; flex-direction: column; gap: .5rem; }
.sen-plan-organe-trim {}
.sen-plan-organe-trim-head { display: flex; justify-content: space-between; font-size: .72rem; color: #475569; margin-bottom: .2rem; }
.sen-plan-organe-trim-bar { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.sen-plan-organe-trim-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }

/* ═══════════ RECENT ACTIVITY ═══════════ */
.sen-recent-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sen-recent-col {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; display: flex; flex-direction: column;
}
.sen-recent-head {
  display: flex; align-items: center; gap: .6rem; padding: .9rem 1rem;
  border-bottom: 2px solid; background: #fafbfc;
}
.sen-recent-head-icon {
  width: 34px; height: 34px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
}
.sen-recent-head-title { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sen-recent-head-count { font-size: .65rem; color: #94a3b8; }
.sen-alert-badge {
  margin-left: auto; font-size: .62rem; font-weight: 700; color: #fff;
  background: #ef4444; padding: .15rem .5rem; border-radius: 6px; white-space: nowrap;
}
.sen-recent-body { flex: 1; max-height: 250px; overflow-y: auto; }
.sen-recent-item {
  display: flex; align-items: flex-start; gap: .6rem; padding: .65rem 1rem;
  border-bottom: 1px solid #f8fafc; text-decoration: none; color: inherit;
  transition: background .15s;
}
.sen-recent-item:hover { background: #f0f9ff; }
.sen-recent-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: .35rem; }
.sen-recent-info { flex: 1; min-width: 0; }
.sen-recent-title { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.sen-recent-time { font-size: .65rem; color: #94a3b8; margin-top: .15rem; }
.sen-recent-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: .4rem; padding: 2rem 1rem; color: #cbd5e1; font-size: .82rem;
}
.sen-recent-empty i { font-size: 1.3rem; }

.sev-bg-basse { background: #22c55e; }
.sev-bg-moyenne { background: #f59e0b; }
.sev-bg-haute { background: #ef4444; }
.sen-sev-badge {
  display: inline-block; font-size: .58rem; font-weight: 700; padding: .1rem .4rem;
  border-radius: 4px; margin-left: .3rem; text-transform: uppercase; letter-spacing: .3px;
}
.sev-basse { background: #dcfce7; color: #16a34a; }
.sev-moyenne { background: #fef3c7; color: #d97706; }
.sev-haute { background: #fee2e2; color: #dc2626; }

/* ═══════════ TACHES (redesign card grid) ═══════════ */
.sen-duo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.sen-duo-block {}

.sen-task-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; margin-top: .25rem; }
.sen-task-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1rem; text-align: center; transition: all .25s;
}
.sen-task-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.06); }
.sen-task-card-alert { border-color: #fca5a5; background: #fef2f2; }
.sen-task-card-icon {
  width: 40px; height: 40px; border-radius: 12px; display: inline-flex;
  align-items: center; justify-content: center; font-size: .9rem; margin-bottom: .5rem;
}
.sen-task-card-val { font-size: 1.6rem; font-weight: 800; color: #1e293b; line-height: 1; }
.sen-task-card-lbl { font-size: .68rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .2rem; }

.sen-task-progress { margin-top: .75rem; }
.sen-task-progress-bar { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.sen-task-progress-fill {
  height: 100%; border-radius: 6px; min-width: 2px;
  background: linear-gradient(90deg, #7c3aed, #a78bfa); transition: width .8s ease;
}
.sen-task-progress-lbl { font-size: .65rem; color: #94a3b8; margin-top: .25rem; text-align: right; }

/* ═══════════ AUDIT COMPACT (in duo) ═══════════ */
.sen-audit-compact {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden; margin-top: .25rem;
}
.sen-audit-compact-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.sen-audit-compact-stat {
  text-align: center; padding: 1rem .5rem;
  border-bottom: 1px solid #f1f5f9;
}
.sen-audit-compact-stat:first-child { border-right: 1px solid #f1f5f9; }
.sen-audit-compact-icon {
  width: 36px; height: 36px; border-radius: 10px; display: inline-flex;
  align-items: center; justify-content: center; font-size: .85rem; margin-bottom: .35rem;
}
.sen-audit-compact-val { font-size: 1.4rem; font-weight: 800; color: #1e293b; line-height: 1; }
.sen-audit-compact-lbl { font-size: .62rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }

.sen-audit-compact-list { padding: 0; }
.sen-audit-compact-list-head {
  padding: .7rem 1rem; font-size: .78rem; font-weight: 700; color: #475569;
  background: #f8fafc; border-bottom: 1px solid #f1f5f9;
}
.sen-audit-compact-item {
  display: flex; align-items: flex-start; gap: .5rem; padding: .55rem 1rem;
  border-bottom: 1px solid #f8fafc; transition: background .15s;
}
.sen-audit-compact-item:hover { background: #fef2f2; }
.sen-audit-compact-item-action { font-size: .74rem; font-weight: 600; color: #1e293b; }
.sen-audit-compact-item-meta { font-size: .62rem; color: #94a3b8; margin-top: .1rem; display: flex; gap: .2rem; flex-wrap: wrap; }
.sen-audit-compact-item-time { margin-left: auto; white-space: nowrap; }
.sen-audit-compact-empty {
  padding: 1.2rem 1rem; color: #94a3b8; font-size: .78rem; text-align: center;
}

/* ═══════════ EMPTY STATES (enhanced) ═══════════ */
.sen-empty-icon-wrap {
  width: 48px; height: 48px; border-radius: 50%; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: .2rem;
}
.sen-empty-hint { font-size: .68rem; color: #cbd5e1; margin-top: .1rem; }

/* ═══════════ FOOTER ═══════════ */
.sen-footer {
  margin-top: .5rem; padding: 1rem 0; border-top: 1px solid #e5e7eb;
}
.sen-footer-inner {
  display: flex; align-items: center; justify-content: space-between;
  font-size: .72rem; color: #94a3b8;
}
.sen-footer-left { display: flex; align-items: center; gap: .4rem; }
.sen-footer-left i { font-size: .65rem; }
.sen-footer-badge {
  background: #f1f5f9; padding: .2rem .6rem; border-radius: 6px;
  font-weight: 600; font-size: .65rem; color: #64748b;
}

/* ═══════════ CONGÉS & DISPONIBILITÉS ═══════════ */
.sen-holidays-grid { display: grid; grid-template-columns: 300px 1fr; gap: .75rem; }
.sen-holiday-stats-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; display: flex; flex-direction: column; gap: .65rem;
}
.sen-holiday-stat-item {
  display: flex; align-items: center; gap: .7rem; padding: .6rem;
  background: #f8fafc; border-radius: 10px;
}
.sen-holiday-stat-icon {
  width: 40px; height: 40px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0;
}
.sen-holiday-stat-val { font-size: 1.5rem; font-weight: 800; line-height: 1; color: #1e293b; }
.sen-holiday-stat-lbl { font-size: .68rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }

.sen-holiday-list-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; overflow: hidden;
}
.sen-holiday-list-head {
  padding: .9rem 1.1rem; background: #f8fafc; border-bottom: 1px solid #e5e7eb;
  font-size: .82rem; font-weight: 700; color: #1e293b;
}
.sen-holiday-list { max-height: 260px; overflow-y: auto; }
.sen-holiday-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: .7rem 1.1rem; border-bottom: 1px solid #f8fafc;
}
.sen-holiday-item:hover { background: #f0f9ff; }
.sen-holiday-item-agent { font-size: .78rem; font-weight: 600; color: #1e293b; }
.sen-holiday-item-info { display: flex; align-items: center; gap: .5rem; font-size: .7rem; color: #64748b; }
.sen-holiday-badge {
  background: #fef3c7; color: #d97706; padding: .15rem .45rem;
  border-radius: 6px; font-weight: 600; font-size: .65rem;
}
.sen-holiday-dates { font-size: .68rem; }

/* ═══════════ AFFECTATIONS & POSTES VACANTS ═══════════ */
.sen-affectations-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.sen-affectation-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; display: flex; align-items: center; gap: .8rem;
  transition: all .25s;
}
.sen-affectation-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sen-affectation-alert { border-left: 3px solid #dc2626; }
.sen-affectation-warning { border-left: 3px solid #d97706; }
.sen-affectation-icon {
  width: 48px; height: 48px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0;
}
.sen-affectation-val { font-size: 1.75rem; font-weight: 800; line-height: 1; color: #1e293b; }
.sen-affectation-lbl { font-size: .68rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .2rem; }

/* ═══════════ AUDIT & SÉCURITÉ ═══════════ */
.sen-audit-row { display: grid; grid-template-columns: 320px 1fr; gap: .75rem; }
.sen-audit-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; display: flex; flex-direction: column; gap: .7rem;
}
.sen-audit-stat {
  display: flex; align-items: center; gap: .75rem; padding: .65rem;
  background: #f8fafc; border-radius: 10px;
}
.sen-audit-stat-icon {
  width: 42px; height: 42px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0;
}
.sen-audit-stat-val { font-size: 1.6rem; font-weight: 800; line-height: 1; color: #1e293b; }
.sen-audit-stat-lbl { font-size: .68rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }

.sen-audit-recent {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; overflow: hidden;
}
.sen-audit-recent-head {
  padding: .9rem 1.1rem; background: #f8fafc; border-bottom: 1px solid #e5e7eb;
  font-size: .82rem; font-weight: 700; color: #1e293b;
}
.sen-audit-list { max-height: 200px; overflow-y: auto; }
.sen-audit-list-item {
  display: flex; align-items: center; gap: .6rem; padding: .65rem 1.1rem;
  border-bottom: 1px solid #f8fafc;
}
.sen-audit-list-item:hover { background: #fef2f2; }
.sen-audit-dot { width: 8px; height: 8px; border-radius: 50%; background: #dc2626; flex-shrink: 0; }
.sen-audit-list-info { flex: 1; min-width: 0; }
.sen-audit-list-action { font-size: .76rem; font-weight: 600; color: #1e293b; }
.sen-audit-list-time { font-size: .65rem; color: #94a3b8; margin-top: .1rem; }

/* ═══════════ RESPONSIVE ═══════════ */
@media (max-width: 1100px) {
  .sen-hero-inner { flex-direction: column; align-items: flex-start; }
  .sen-hero-kpis { width: 100%; justify-content: space-between; }
}
@media (max-width: 991.98px) {
  .sen-metrics { grid-template-columns: repeat(2, 1fr); }
  .sen-actions { grid-template-columns: repeat(2, 1fr); }
  .sen-organe-grid { grid-template-columns: 1fr; }
  .sen-presence-grid { grid-template-columns: repeat(2, 1fr); }
  .sen-plan-organe-grid { grid-template-columns: 1fr; }
  .sen-recent-grid { grid-template-columns: 1fr; }
  .sen-holidays-grid { grid-template-columns: 1fr; }
  .sen-affectations-row { grid-template-columns: repeat(2, 1fr); }
  .sen-audit-row { grid-template-columns: 1fr; }
  .sen-duo-grid { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
  .sen-dashboard { padding: 0 .5rem 1.5rem; }
  .sen-hero { border-radius: 16px; margin-bottom: 1.2rem; }
  .sen-hero-inner { padding: 1.3rem 1.2rem; gap: 1rem; }
  .sen-hero-badge { width: 48px; height: 48px; font-size: 1.2rem; }
  .sen-hero-name { font-size: 1.2rem; }
  .sen-hero-kpis { padding: .6rem .8rem; flex-wrap: wrap; gap: .5rem; }
  .sen-kpi { padding: 0 .6rem; }
  .sen-kpi-val { font-size: 1.15rem; }
  .kpi-divider { display: none; }
  .sen-actions { grid-template-columns: 1fr 1fr; gap: .5rem; }
  .sen-action { padding: .75rem .85rem; }
  .sen-metrics { gap: .5rem; }
  .sen-metric { padding: .85rem; }
  .sen-metric-val { font-size: 1.4rem; }
  .sen-presence-grid { grid-template-columns: 1fr 1fr; }
  .sen-plan-global-row { flex-direction: column; align-items: center; }
}
@media (max-width: 575.98px) {
  .sen-hero-kpis { border-radius: 12px; }
  .sen-kpi { padding: .4rem .4rem; flex: 0 0 calc(50% - .5rem); }
  .sen-kpi-icon { display: none; }
  .sen-kpi-val { font-size: 1.1rem; }
  .sen-actions { grid-template-columns: 1fr; }
  .sen-action-arrow { display: none; }
  .sen-metrics { grid-template-columns: repeat(2, 1fr); }
  .sen-presence-grid { grid-template-columns: 1fr; }
  .sen-organe-stats { grid-template-columns: repeat(2, 1fr); }
  .sen-affectations-row { grid-template-columns: 1fr; }
  .sen-audit-row { grid-template-columns: 1fr; }
  .sen-task-grid { grid-template-columns: repeat(2, 1fr); }
}

/* ═══════════ DRILL-DOWN CLICKABLE ═══════════ */
.sen-organe-clickable { cursor: pointer; position: relative; }
.sen-organe-clickable:hover { border-color: #93c5fd; box-shadow: 0 8px 28px rgba(0,119,181,.12); }
.sen-drill-arrow {
  position: absolute; top: 1.2rem; right: 1.2rem;
  font-size: .65rem; color: #cbd5e1; transition: all .25s;
}
.sen-organe-clickable:hover .sen-drill-arrow { color: #0077B5; transform: translateX(3px); }

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

/* Header */
.drill-header {
  padding: 1.5rem 1.8rem; color: #fff; flex-shrink: 0;
  display: flex; align-items: center; justify-content: space-between;
}
.drill-header-left { display: flex; align-items: center; gap: .8rem; min-width: 0; }
.drill-header-title { font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: .5rem; }
.drill-header-sub { font-size: .72rem; opacity: .8; margin-top: .15rem; }
.drill-back {
  width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,.3);
  background: rgba(255,255,255,.1); color: #fff; display: flex; align-items: center;
  justify-content: center; cursor: pointer; font-size: .85rem; transition: all .2s; flex-shrink: 0;
}
.drill-back:hover { background: rgba(255,255,255,.2); }
.drill-close {
  width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,.2);
  background: rgba(255,255,255,.08); color: #fff; display: flex; align-items: center;
  justify-content: center; cursor: pointer; font-size: .85rem; transition: all .2s; flex-shrink: 0;
}
.drill-close:hover { background: rgba(255,255,255,.2); }

/* Body */
.drill-body { flex: 1; overflow-y: auto; padding: 1.4rem 1.6rem; }

/* Loading */
.drill-loading { text-align: center; padding: 3rem 1rem; }
.drill-loading p { margin-top: .8rem; color: #94a3b8; font-size: .82rem; }

/* Summary */
.drill-summary {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem;
  margin-bottom: 1.4rem;
}
.drill-summary-item {
  background: #fff; border-radius: 12px; padding: .8rem; text-align: center;
  border: 1px solid #e5e7eb;
}
.drill-summary-val { font-size: 1.5rem; font-weight: 800; color: #059669; }
.drill-summary-lbl { font-size: .62rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; }

/* Items grid */
.drill-items-grid { display: flex; flex-direction: column; gap: .6rem; }
.drill-item-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1rem 1.2rem; transition: all .25s;
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

/* ═══════════ PROVINCE DETAIL (Level 2) ═══════════ */
.drill-prov-info {
  display: flex; flex-wrap: wrap; gap: .5rem .8rem;
  margin-bottom: 1.2rem; padding: .8rem 1rem;
  background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
}
.drill-prov-info-item {
  display: flex; align-items: center; gap: .4rem; font-size: .78rem; color: #475569;
}
.drill-prov-info-item i { color: #94a3b8; font-size: .7rem; }

.drill-prov-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; margin-bottom: 1.2rem; }
.drill-prov-stat-card {
  background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
  padding: .8rem; text-align: center;
}
.drill-prov-stat-icon {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; margin: 0 auto .4rem; font-size: .85rem;
}
.drill-prov-stat-val { font-size: 1.4rem; font-weight: 800; line-height: 1; color: #1e293b; }
.drill-prov-stat-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: .15rem; }

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

/* ═══════════ ACTIVITES PTA ═══════════ */
.drill-prov-activites { display: flex; flex-direction: column; gap: .5rem; }
.drill-prov-activite { background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .6rem .75rem; }
.drill-activite-head { display: flex; gap: .6rem; align-items: flex-start; }
.drill-activite-pct { font-size: 1.1rem; font-weight: 700; min-width: 3rem; text-align: right; }
.drill-activite-info { flex: 1; min-width: 0; }
.drill-activite-titre { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.drill-activite-meta { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .25rem; }
.drill-activite-tag { font-size: .6rem; background: #f1f5f9; color: #64748b; padding: .1rem .4rem; border-radius: .25rem; }
.drill-activite-statut { font-size: .6rem; padding: .1rem .4rem; border-radius: .25rem; font-weight: 600; }
.statut-terminée, .statut-terminee { background: #d1fae5; color: #059669; }
.statut-en-cours { background: #fef3c7; color: #d97706; }
.statut-planifiée, .statut-planifiee { background: #e0f2fe; color: #0077B5; }
.statut-non-démarré, .statut-non-demarre { background: #f1f5f9; color: #94a3b8; }
.drill-activite-bar { height: 4px; background: #f1f5f9; border-radius: 2px; margin-top: .35rem; overflow: hidden; }
.drill-activite-bar-fill { height: 100%; border-radius: 2px; transition: width .4s ease; }
.drill-activite-dates { font-size: .6rem; color: #94a3b8; margin-top: .25rem; }
.drill-activite-dates i { margin-right: .2rem; }

/* ═══════════ TRANSITIONS ═══════════ */
.drill-fade-enter-active, .drill-fade-leave-active { transition: all .3s ease; }
.drill-fade-enter-from, .drill-fade-leave-to { opacity: 0; }

.drill-slide-enter-active { transition: all .35s cubic-bezier(.16,1,.3,1); }
.drill-slide-leave-active { transition: all .25s ease-in; }
.drill-slide-enter-from { transform: translateX(40px); opacity: 0; }
.drill-slide-leave-to { transform: translateX(-20px); opacity: 0; }

/* ═══════════ RESPONSIVE DRILL-DOWN ═══════════ */
@media (max-width: 640px) {
  .drill-panel { width: 100vw; }
  .drill-summary { grid-template-columns: repeat(2, 1fr); }
  .drill-prov-stats { grid-template-columns: repeat(2, 1fr); }
  .drill-prov-dept-grid { grid-template-columns: 1fr; }
  .drill-item-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
