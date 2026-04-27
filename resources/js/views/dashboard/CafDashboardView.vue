<template>
  <div class="caf-dashboard">
    <!-- ═══ HERO ═══ -->
    <div class="caf-hero">
      <div class="caf-hero-bg"></div>
      <div class="caf-hero-inner">
        <div class="caf-hero-left">
          <div class="caf-hero-avatar">
            <img v-if="cafPhotoUrl" :src="cafPhotoUrl" :alt="cafFullName"
              class="caf-hero-avatar-photo" @error="handlePhotoError">
            <span v-else class="caf-hero-avatar-initials">{{ cafInitials }}</span>
          </div>
          <div>
            <div class="caf-hero-greeting">{{ cafGreeting }},</div>
            <h1 class="caf-hero-name">{{ cafCivility }} {{ cafFullName }}</h1>
            <div class="caf-hero-fonction" v-if="cafFonction">
              <i class="fas fa-id-badge me-1"></i>{{ cafFonction }}
            </div>
            <div class="caf-hero-role">
              <i class="fas fa-landmark me-1"></i>
              Cellule Administrative et Financière
              <span v-if="data.province?.nom" class="caf-hero-province-badge">
                {{ data.province.nom }}
              </span>
            </div>
            <div class="caf-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>
        <div class="caf-hero-kpis">
          <div class="caf-kpi caf-kpi-clickable" @click="openProvDrilldown('effectifs')">
            <div class="caf-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="caf-kpi-val">{{ data.agents?.actifs ?? '-' }}</div>
              <div class="caf-kpi-lbl">Agents actifs</div>
            </div>
            <i class="fas fa-search-plus caf-kpi-drill-icon"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="caf-kpi caf-kpi-clickable" @click="openProvDrilldown('presence')">
            <div class="caf-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="caf-kpi-val">{{ data.attendance?.today_rate ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="caf-kpi-lbl">Présence</div>
            </div>
            <i class="fas fa-search-plus caf-kpi-drill-icon"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="caf-kpi caf-kpi-clickable" @click="router.push('/requests')">
            <div class="caf-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="caf-kpi-val">{{ data.requests?.en_attente ?? 0 }}</div>
              <div class="caf-kpi-lbl">En attente</div>
            </div>
            <i class="fas fa-arrow-right caf-kpi-drill-icon"></i>
          </div>
          <div class="kpi-divider"></div>
          <div class="caf-kpi caf-kpi-clickable" @click="openProvDrilldown('pta')">
            <div class="caf-kpi-icon"><i class="fas fa-bullseye"></i></div>
            <div>
              <div class="caf-kpi-val">{{ data.plan_travail?.avg_completion ?? 0 }}<span class="kpi-unit">%</span></div>
              <div class="caf-kpi-lbl">Plan annuel</div>
            </div>
            <i class="fas fa-search-plus caf-kpi-drill-icon"></i>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" message="Chargement du tableau de bord CAF..." />

    <div v-else-if="loadError" class="alert alert-warning mx-3">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <template v-else>
      <!-- ═══ PROVINCE INFO ═══ -->
      <div class="caf-section" v-if="data.province">
        <div class="caf-province-card">
          <div class="caf-province-icon"><i class="fas fa-landmark"></i></div>
          <div class="caf-province-info">
            <div class="caf-province-name">{{ data.province.nom }}</div>
            <div class="caf-province-details">
              <span v-if="data.province.nom_gouverneur">
                <i class="fas fa-user-tie me-1"></i>Gouverneur : {{ data.province.nom_gouverneur }}
              </span>
              <span v-if="data.province.nom_secretariat_executif" class="caf-province-sep">
                <i class="fas fa-id-badge me-1"></i>CAF : {{ data.province.nom_secretariat_executif }}
              </span>
              <span v-if="data.province.ville_secretariat">
                <i class="fas fa-map-pin me-1"></i>{{ data.province.ville_secretariat }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ QUICK ACTIONS ═══ -->
      <div class="caf-section">
        <div class="caf-section-head">
          <div class="caf-section-icon" style="background:#d1fae5;color:#0d9488;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="caf-section-title">Actions rapides</h3>
            <p class="caf-section-sub">Accès direct aux modules administratifs et financiers</p>
          </div>
        </div>
        <div class="caf-actions">
          <router-link v-for="a in quickActions" :key="a.to" :to="a.to" class="caf-action">
            <div class="caf-action-glow" :style="{ background: a.color }"></div>
            <div class="caf-action-icon" :style="{ background: a.bg, color: a.color }">
              <i class="fas" :class="a.icon"></i>
            </div>
            <div class="caf-action-text">
              <div class="caf-action-label">{{ a.label }}</div>
              <div class="caf-action-desc">{{ a.desc }}</div>
            </div>
            <i class="fas fa-chevron-right caf-action-arrow"></i>
          </router-link>
        </div>
      </div>

      <!-- ═══ INDICATEURS CLÉS ═══ -->
      <div class="caf-section">
        <div class="caf-section-head">
          <div class="caf-section-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fas fa-chart-bar"></i>
          </div>
          <div>
            <h3 class="caf-section-title">Indicateurs clés</h3>
            <p class="caf-section-sub">Vue d'ensemble — {{ data.province?.nom }}</p>
          </div>
        </div>
        <div class="caf-metrics">
          <div v-for="m in metrics" :key="m.label" class="caf-metric caf-clickable"
            @click="m.drillSection ? openProvDrilldown(m.drillSection) : router.push(m.route)">
            <div class="caf-metric-header">
              <div class="caf-metric-icon" :style="{ background: m.bg, color: m.color }">
                <i class="fas" :class="m.icon"></i>
              </div>
              <span v-if="m.alert" class="caf-metric-alert"><i class="fas fa-exclamation-circle"></i></span>
              <span v-if="m.drillSection" class="caf-metric-drill-badge"><i class="fas fa-search-plus"></i></span>
            </div>
            <div class="caf-metric-val" :style="{ color: m.color }">{{ m.value }}</div>
            <div class="caf-metric-lbl">{{ m.label }}</div>
            <div class="caf-metric-bar">
              <div class="caf-metric-bar-fill" :style="{ background: m.color, width: m.pct + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ DÉPARTEMENTS ═══ -->
      <div class="caf-section">
        <div class="caf-section-head">
          <div class="caf-section-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fas fa-building"></i>
          </div>
          <div>
            <h3 class="caf-section-title">Départements</h3>
            <p class="caf-section-sub">{{ (data.departments || []).length }} département(s) — {{ data.agents?.actifs ?? 0 }} agents actifs</p>
          </div>
        </div>
        <div class="caf-dept-grid">
          <div v-for="dept in (data.departments || [])" :key="dept.id"
            class="caf-dept-card caf-clickable" @click="openDeptDrilldown(dept.id)">
            <div class="caf-dept-header">
              <div class="caf-dept-badge"><i class="fas fa-building"></i></div>
              <div class="caf-dept-info">
                <div class="caf-dept-name">{{ dept.nom }}</div>
                <div class="caf-dept-code">{{ dept.code }}</div>
              </div>
              <i class="fas fa-chevron-right caf-drill-arrow"></i>
            </div>
            <div class="caf-dept-stats">
              <div class="caf-dept-stat">
                <div class="caf-dept-stat-val">{{ dept.total ?? 0 }}</div>
                <div class="caf-dept-stat-lbl">Total</div>
              </div>
              <div class="caf-dept-stat">
                <div class="caf-dept-stat-val" style="color:#059669;">{{ dept.actifs ?? 0 }}</div>
                <div class="caf-dept-stat-lbl">Actifs</div>
              </div>
            </div>
            <div class="caf-dept-bar-wrap">
              <div class="caf-dept-bar-bg">
                <div class="caf-dept-bar-fill"
                  :style="{ width: deptPct(dept.actifs) + '%' }"></div>
              </div>
              <span class="caf-dept-bar-pct">{{ deptPct(dept.actifs) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PRÉSENCE PAR ORGANE ═══ -->
      <div class="caf-section">
        <div class="caf-section-head">
          <div class="caf-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-user-check"></i>
          </div>
          <div>
            <h3 class="caf-section-title">Présence par organe</h3>
            <p class="caf-section-sub">Taux de présence aujourd'hui et ce mois</p>
          </div>
        </div>
        <div class="caf-presence-cards">
          <div v-for="o in presenceOrganes" :key="o.code" class="caf-presence-card">
            <div class="caf-presence-icon" :style="{ color: o.color }">
              <i class="fas" :class="o.icon"></i>
            </div>
            <div class="caf-presence-label">{{ o.label }}</div>
            <div class="caf-presence-rate-big" :style="{ color: o.color }">{{ o.today_rate }}%</div>
            <div class="caf-presence-sub">{{ o.today_present }} / {{ o.total_active }} présents</div>
            <div class="caf-presence-bar-bg">
              <div class="caf-presence-bar-fill" :style="{ width: o.today_rate + '%', background: o.color }"></div>
            </div>
            <div class="caf-presence-monthly">Mois : {{ o.monthly_rate }}%</div>
          </div>
        </div>
      </div>

      <!-- ═══ PLAN DE TRAVAIL CAF ═══ -->
      <div class="caf-section">
        <div class="caf-section-head">
          <div class="caf-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-bullseye"></i>
          </div>
          <div>
            <h3 class="caf-section-title">Plan de Travail CAF {{ currentYear }}</h3>
            <p class="caf-section-sub">{{ data.plan_travail?.total ?? 0 }} activités planifiées</p>
          </div>
          <router-link to="/plan-travail" class="caf-section-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
        </div>
        <div class="caf-plan-layout">
          <div class="caf-plan-ring-wrap">
            <svg width="130" height="130" viewBox="0 0 130 130">
              <circle cx="65" cy="65" r="52" fill="none" stroke="#e5e7eb" stroke-width="12"/>
              <circle cx="65" cy="65" r="52" fill="none" stroke="#0d9488" stroke-width="12"
                stroke-linecap="round"
                :stroke-dasharray="ringDash"
                :stroke-dashoffset="ringOffset"
                transform="rotate(-90 65 65)"
                style="transition:stroke-dashoffset .6s ease"/>
            </svg>
            <div class="caf-plan-ring-label">
              <div class="caf-plan-ring-val">{{ data.plan_travail?.avg_completion ?? 0 }}<span style="font-size:.7em">%</span></div>
              <div class="caf-plan-ring-sub">Progression</div>
            </div>
          </div>
          <div class="caf-plan-trimesters">
            <div v-for="t in (data.plan_travail?.by_trimestre || [])" :key="t.trimestre" class="caf-plan-trimester">
              <div class="caf-plan-trimester-label">{{ t.trimestre }}</div>
              <div class="caf-plan-trimester-bar-bg">
                <div class="caf-plan-trimester-bar-fill" :style="{ width: t.avg_pourcentage + '%' }"></div>
              </div>
              <div class="caf-plan-trimester-pct">{{ t.avg_pourcentage }}%</div>
              <div class="caf-plan-trimester-count">{{ t.terminee }}/{{ t.total }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ ACTIVITÉS RÉCENTES ═══ -->
      <div class="caf-section">
        <div class="caf-duo">

          <!-- Demandes en attente -->
          <div class="caf-duo-block">
            <div class="caf-section-head">
              <div class="caf-section-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fas fa-paper-plane"></i>
              </div>
              <div>
                <h3 class="caf-section-title">Demandes récentes</h3>
                <p class="caf-section-sub">{{ data.requests?.en_attente ?? 0 }} en attente</p>
              </div>
              <router-link to="/requests" class="caf-section-link">Tout voir <i class="fas fa-arrow-right"></i></router-link>
            </div>
            <div v-if="data.requests?.recent_pending?.length" class="caf-req-list">
              <div v-for="r in data.requests.recent_pending" :key="r.id" class="caf-req-row">
                <div class="caf-req-icon" :style="{ background: '#fef3c7', color: '#d97706' }">
                  <i class="fas fa-file-alt"></i>
                </div>
                <div class="caf-req-info">
                  <div class="caf-req-type">{{ r.type }}</div>
                  <div class="caf-req-agent">{{ r.agent?.prenom }} {{ r.agent?.nom }}</div>
                </div>
                <div class="caf-req-date">{{ formatDate(r.created_at) }}</div>
              </div>
            </div>
            <div v-else class="caf-empty-msg"><i class="fas fa-inbox me-2"></i>Aucune demande en attente</div>
          </div>

          <!-- Congés actifs -->
          <div class="caf-duo-block">
            <div class="caf-section-head">
              <div class="caf-section-icon" style="background:#cffafe;color:#0891b2;">
                <i class="fas fa-umbrella-beach"></i>
              </div>
              <div>
                <h3 class="caf-section-title">Congés actifs</h3>
                <p class="caf-section-sub">{{ data.holidays?.active_today ?? 0 }} agent(s) en congé</p>
              </div>
            </div>
            <div v-if="data.holidays?.agents_en_conge_today?.length" class="caf-req-list">
              <div v-for="c in data.holidays.agents_en_conge_today" :key="c.id" class="caf-req-row">
                <div class="caf-req-icon" style="background:#cffafe;color:#0891b2;">
                  <i class="fas fa-umbrella-beach"></i>
                </div>
                <div class="caf-req-info">
                  <div class="caf-req-type">{{ c.agent }}</div>
                  <div class="caf-req-agent">{{ c.type }}</div>
                </div>
                <div class="caf-req-date">jusqu'au {{ c.date_fin }}</div>
              </div>
            </div>
            <div v-else class="caf-empty-msg"><i class="fas fa-check-circle me-2"></i>Aucun agent en congé</div>
          </div>

        </div>
      </div>

    </template>

    <!-- ═══ DRILL-DOWN DÉPARTEMENT ═══ -->
    <Teleport to="body">
      <div v-if="deptDrillOpen" class="drill-overlay" @click.self="closeDeptDrilldown">
        <div class="drill-panel">
          <div class="drill-header" style="background:#0d9488;">
            <div class="drill-header-left">
              <div>
                <div class="drill-header-title" v-if="deptDrillData">
                  <i class="fas fa-building me-2"></i>{{ deptDrillData.department?.nom }}
                </div>
                <div class="drill-header-title" v-else><i class="fas fa-spinner fa-spin"></i> Chargement...</div>
                <div class="drill-header-sub" v-if="deptDrillData">
                  {{ deptDrillData.effectifs?.actifs ?? 0 }} agents actifs · {{ deptDrillData.department?.code ?? '' }}
                </div>
              </div>
            </div>
            <button class="drill-close" @click="closeDeptDrilldown"><i class="fas fa-times"></i></button>
          </div>
          <div class="drill-body">
            <div v-if="deptDrillLoading" class="drill-loading">
              <i class="fas fa-circle-notch fa-spin fa-2x" style="color:#0d9488;"></i>
              <p>Chargement…</p>
            </div>
            <template v-else-if="deptDrillData">
              <div class="drill-section-tabs">
                <button class="drill-section-tab caf-tab" :class="{ active: deptDrillSection === 'effectifs' }" @click="deptDrillSection = 'effectifs'">
                  <i class="fas fa-users"></i> Effectifs
                </button>
                <button class="drill-section-tab caf-tab" :class="{ active: deptDrillSection === 'presence' }" @click="deptDrillSection = 'presence'">
                  <i class="fas fa-user-check"></i> Présence
                </button>
                <button class="drill-section-tab caf-tab" :class="{ active: deptDrillSection === 'pta' }" @click="deptDrillSection = 'pta'">
                  <i class="fas fa-tasks"></i> PTA
                </button>
              </div>

              <!-- Effectifs -->
              <template v-if="deptDrillSection === 'effectifs'">
                <div class="drill-dept-grid">
                  <div class="drill-prov-stat-card" style="border-color:#0d9488;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.effectifs?.total ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Total</div>
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
                <div v-if="deptDrillData.agents?.length" class="drill-prov-section-title">
                  <i class="fas fa-user me-1"></i>Agents actifs ({{ deptDrillData.agents.length }})
                </div>
                <div class="drill-prov-agents-table">
                  <div v-for="a in deptDrillData.agents" :key="a.id" class="drill-prov-agent-row">
                    <div class="drill-prov-agent-avatar"
                      :style="{ background: (a.sexe||'').toLowerCase() === 'f' ? '#fce7f3' : '#dbeafe', color: (a.sexe||'').toLowerCase() === 'f' ? '#be185d' : '#1d4ed8' }">
                      <i :class="(a.sexe||'').toLowerCase() === 'f' ? 'fas fa-female' : 'fas fa-male'"></i>
                    </div>
                    <div class="drill-prov-agent-info">
                      <div class="drill-prov-agent-name">{{ a.nom }}</div>
                      <div class="drill-prov-agent-fn">{{ a.fonction }}</div>
                    </div>
                  </div>
                </div>
                <div v-if="!deptDrillData.agents?.length" class="drill-empty">
                  <i class="fas fa-inbox"></i><p>Aucun agent</p>
                </div>
              </template>

              <!-- Présence -->
              <template v-else-if="deptDrillSection === 'presence'">
                <div class="drill-dept-grid">
                  <div class="drill-prov-stat-card" style="border-color:#059669;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.presence?.today_rate ?? 0 }}%</div>
                    <div class="drill-prov-stat-lbl">Aujourd'hui</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#0d9488;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.presence?.monthly_rate ?? 0 }}%</div>
                    <div class="drill-prov-stat-lbl">Ce mois</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.presence?.today_present ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Présents</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#7c3aed;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.effectifs?.actifs ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Attendus</div>
                  </div>
                </div>
              </template>

              <!-- PTA -->
              <template v-else>
                <div class="drill-dept-grid">
                  <div class="drill-prov-stat-card" style="border-color:#d97706;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.pta?.total ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Total activités</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#059669;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.pta?.terminee ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Terminées</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#0d9488;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.pta?.avg_completion ?? 0 }}%</div>
                    <div class="drill-prov-stat-lbl">Progression</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
                    <div class="drill-prov-stat-val">{{ deptDrillData.pta?.en_cours ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">En cours</div>
                  </div>
                </div>
              </template>
            </template>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ DRILL-DOWN PROVINCE (KPI overview) ═══ -->
    <Teleport to="body">
      <div v-if="provDrillOpen" class="drill-overlay" @click.self="closeProvDrilldown">
        <div class="drill-panel">
          <div class="drill-header" style="background:#0d9488;">
            <div class="drill-header-left">
              <div>
                <div class="drill-header-title" v-if="provDrillData">
                  <i class="fas fa-landmark me-2"></i>{{ provDrillData.province?.nom ?? data.province?.nom }}
                </div>
                <div class="drill-header-title" v-else><i class="fas fa-spinner fa-spin"></i> Chargement...</div>
                <div class="drill-header-sub" v-if="provDrillData">
                  {{ provDrillData.effectifs?.actifs ?? 0 }} agents actifs · {{ provDrillData.province?.code ?? '' }}
                </div>
              </div>
            </div>
            <button class="drill-close" @click="closeProvDrilldown"><i class="fas fa-times"></i></button>
          </div>
          <div class="drill-body">
            <div v-if="provDrillLoading" class="drill-loading">
              <i class="fas fa-circle-notch fa-spin fa-2x" style="color:#0d9488;"></i>
              <p>Chargement…</p>
            </div>
            <template v-else-if="provDrillData">
              <div class="drill-section-tabs">
                <button class="drill-section-tab caf-tab" :class="{ active: provDrillSection === 'effectifs' }" @click="provDrillSection = 'effectifs'">
                  <i class="fas fa-users"></i> Effectifs
                </button>
                <button class="drill-section-tab caf-tab" :class="{ active: provDrillSection === 'presence' }" @click="provDrillSection = 'presence'">
                  <i class="fas fa-user-check"></i> Présence
                </button>
                <button class="drill-section-tab caf-tab" :class="{ active: provDrillSection === 'pta' }" @click="provDrillSection = 'pta'">
                  <i class="fas fa-tasks"></i> PTA
                </button>
              </div>

              <!-- Effectifs province -->
              <template v-if="provDrillSection === 'effectifs'">
                <div class="drill-dept-grid">
                  <div class="drill-prov-stat-card" style="border-color:#0d9488;">
                    <div class="drill-prov-stat-val">{{ provDrillData.effectifs?.total ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Total</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#059669;">
                    <div class="drill-prov-stat-val">{{ provDrillData.effectifs?.actifs ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Actifs</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#d97706;">
                    <div class="drill-prov-stat-val">{{ provDrillData.effectifs?.suspendus ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Suspendus</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#64748b;">
                    <div class="drill-prov-stat-val">{{ provDrillData.effectifs?.anciens ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Anciens</div>
                  </div>
                </div>
                <div v-if="provDrillData.departments?.length" class="drill-prov-section-title">
                  <i class="fas fa-building me-1"></i>Départements ({{ provDrillData.departments.length }})
                </div>
                <div v-if="provDrillData.departments?.length" class="caf-dept-chips">
                  <button v-for="dept in provDrillData.departments" :key="dept.id" class="caf-dept-chip"
                    @click="openDeptDrilldown(dept.id, 'effectifs')">
                    {{ dept.code || dept.nom }}
                    <span style="font-size:.65em;opacity:.6;margin-left:4px;">({{ dept.actifs }})</span>
                    <i class="fas fa-chevron-right" style="font-size:.6em;margin-left:4px;"></i>
                  </button>
                </div>
              </template>

              <!-- Présence province -->
              <template v-else-if="provDrillSection === 'presence'">
                <div class="drill-dept-grid">
                  <div class="drill-prov-stat-card" style="border-color:#059669;">
                    <div class="drill-prov-stat-val">{{ provDrillData.presence?.today_rate ?? 0 }}%</div>
                    <div class="drill-prov-stat-lbl">Aujourd'hui</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#0d9488;">
                    <div class="drill-prov-stat-val">{{ provDrillData.presence?.monthly_avg_rate ?? 0 }}%</div>
                    <div class="drill-prov-stat-lbl">Ce mois</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
                    <div class="drill-prov-stat-val">{{ provDrillData.presence?.today_present ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Présents</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#7c3aed;">
                    <div class="drill-prov-stat-val">{{ provDrillData.effectifs?.actifs ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Attendus</div>
                  </div>
                </div>
              </template>

              <!-- PTA province -->
              <template v-else>
                <div class="drill-dept-grid">
                  <div class="drill-prov-stat-card" style="border-color:#d97706;">
                    <div class="drill-prov-stat-val">{{ provDrillData.pta?.total ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Total</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#059669;">
                    <div class="drill-prov-stat-val">{{ provDrillData.pta?.terminee ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">Terminées</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#0d9488;">
                    <div class="drill-prov-stat-val">{{ provDrillData.pta?.avg_completion ?? 0 }}%</div>
                    <div class="drill-prov-stat-lbl">Progression</div>
                  </div>
                  <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
                    <div class="drill-prov-stat-val">{{ provDrillData.pta?.en_cours ?? 0 }}</div>
                    <div class="drill-prov-stat-lbl">En cours</div>
                  </div>
                </div>
              </template>
            </template>
          </div>
        </div>
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

const router = useRouter()
const auth = useAuthStore()
const loading = ref(true)
const loadError = ref(null)
const data = ref({})
const currentYear = new Date().getFullYear()

// ─── IDENTITÉ ────────────────────────────────────────────────────────────────
const photoIndex = ref(0)

const cafPhotoUrl = computed(() => {
  const photo = auth.agent?.photo
  if (!photo) return null
  const p = photo.trim()
  if (/^https?:\/\//i.test(p)) return p
  const n = p.replace(/^\/+/, '')
  const candidates = [`/${n}`, `/storage/${n}`, `/uploads/profiles/${n}`]
  return [...new Set(candidates)][photoIndex.value] ?? null
})
function handlePhotoError() {
  const max = 3
  if (photoIndex.value < max - 1) photoIndex.value++
  else photoIndex.value = max
}
const isFemme = computed(() => {
  const s = (auth.agent?.sexe ?? '').toLowerCase()
  return s === 'f' || s === 'femme' || s === 'féminin'
})
const cafCivility = computed(() => isFemme.value ? 'Mme' : 'M.')
const cafGreeting = computed(() => {
  const h = new Date().getHours()
  return h < 12 ? 'Bonjour' : h < 18 ? 'Bon après-midi' : 'Bonsoir'
})
const cafFullName = computed(() => {
  const a = auth.agent
  if (!a) return auth.user?.name || 'CAF'
  return `${a.prenom ?? ''} ${a.nom ?? ''}`.trim()
})
const cafInitials = computed(() => {
  const a = auth.agent
  if (!a) return 'C'
  return ((a.prenom?.[0] ?? '') + (a.nom?.[0] ?? '')).toUpperCase() || 'C'
})
const cafFonction = computed(() => auth.agent?.fonction || auth.agent?.poste_actuel || null)

const today = computed(() => new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
}))

// ─── DRILL-DOWN DÉPARTEMENT ───────────────────────────────────────────────────
const deptDrillOpen = ref(false)
const deptDrillLoading = ref(false)
const deptDrillData = ref(null)
const deptDrillSection = ref('effectifs')

async function openDeptDrilldown(id, section = 'effectifs') {
  if (!id) return
  provDrillOpen.value = false
  deptDrillOpen.value = true
  deptDrillLoading.value = true
  deptDrillData.value = null
  deptDrillSection.value = section
  try {
    const { data: result } = await client.get(`/dashboard/executive/department/${id}`)
    deptDrillData.value = result.data ?? result
  } catch {
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

// ─── DRILL-DOWN PROVINCE ──────────────────────────────────────────────────────
const provDrillOpen = ref(false)
const provDrillLoading = ref(false)
const provDrillData = ref(null)
const provDrillSection = ref('effectifs')

async function openProvDrilldown(section = 'effectifs') {
  const provinceId = data.value.province?.id
  if (!provinceId) return
  provDrillOpen.value = true
  provDrillLoading.value = true
  provDrillData.value = null
  provDrillSection.value = section
  try {
    const { data: result } = await client.get(`/dashboard/executive/province/${provinceId}`)
    provDrillData.value = result.data ?? result
  } catch {
    provDrillData.value = null
  } finally {
    provDrillLoading.value = false
  }
}
function closeProvDrilldown() {
  provDrillOpen.value = false
  provDrillData.value = null
  provDrillSection.value = 'effectifs'
}

// ─── QUICK ACTIONS ────────────────────────────────────────────────────────────
const quickActions = [
  { to: '/rh/agents',             label: 'Agents',          desc: 'Agents de la province',   icon: 'fa-users',           color: '#0d9488', bg: '#d1fae5' },
  { to: '/plan-travail',          label: 'PTA CAF',         desc: 'Plan de travail annuel',  icon: 'fa-tasks',           color: '#d97706', bg: '#fef3c7' },
  { to: '/requests',              label: 'Demandes',        desc: 'Validation administrative', icon: 'fa-paper-plane',   color: '#059669', bg: '#d1fae5' },
  { to: '/signalements',          label: 'Signalements',    desc: 'Alertes province',         icon: 'fa-flag',            color: '#dc2626', bg: '#fee2e2' },
  { to: '/rh/pointages/monthly',  label: 'Pointages',       desc: 'Présence province',        icon: 'fa-clock',           color: '#7c3aed', bg: '#ede9fe' },
  { to: '/rh/communiques',        label: 'Communiqués',     desc: 'Informations officielles', icon: 'fa-bullhorn',        color: '#0891b2', bg: '#cffafe' },
]

// ─── METRICS ──────────────────────────────────────────────────────────────────
const maxMetric = computed(() => Math.max(
  data.value.agents?.total ?? 0,
  data.value.agents?.actifs ?? 0,
  data.value.requests?.en_attente ?? 0,
  data.value.signalements?.ouvert ?? 0,
  data.value.taches?.en_cours ?? 0,
  1
))
function pct(val) { return Math.min(((val ?? 0) / maxMetric.value) * 100, 100) }

const metrics = computed(() => [
  { label: 'Agents total',         value: data.value.agents?.total ?? 0,           icon: 'fa-users',              color: '#0d9488', bg: '#d1fae5', pct: pct(data.value.agents?.total),           alert: false,                                    route: '/rh/agents',   drillSection: 'effectifs' },
  { label: 'Agents actifs',        value: data.value.agents?.actifs ?? 0,          icon: 'fa-user-check',         color: '#059669', bg: '#d1fae5', pct: pct(data.value.agents?.actifs),          alert: false,                                    route: '/rh/agents',   drillSection: 'effectifs' },
  { label: 'Demandes en attente',  value: data.value.requests?.en_attente ?? 0,    icon: 'fa-hourglass-half',     color: '#d97706', bg: '#fef3c7', pct: pct(data.value.requests?.en_attente),    alert: (data.value.requests?.en_attente ?? 0) > 5, route: '/requests',  drillSection: null },
  { label: 'Signalements ouverts', value: data.value.signalements?.ouvert ?? 0,    icon: 'fa-exclamation-circle', color: '#dc2626', bg: '#fee2e2', pct: pct(data.value.signalements?.ouvert),    alert: (data.value.signalements?.ouvert ?? 0) > 0, route: '/signalements', drillSection: null },
  { label: 'Tâches en cours',      value: data.value.taches?.en_cours ?? 0,        icon: 'fa-spinner',            color: '#7c3aed', bg: '#ede9fe', pct: pct(data.value.taches?.en_cours),        alert: false,                                    route: '/taches',      drillSection: null },
  { label: 'Congés actifs',        value: data.value.holidays?.active_today ?? 0,  icon: 'fa-umbrella-beach',     color: '#f59e0b', bg: '#fef3c7', pct: pct(data.value.holidays?.active_today),  alert: false,                                    route: '/rh/conges',   drillSection: null },
])

// ─── PRÉSENCE PAR ORGANE ──────────────────────────────────────────────────────
const presenceOrganes = computed(() => {
  const att = data.value.attendance?.by_organe || {}
  return [
    { code: 'SEP', label: 'SEP (Provincial)', icon: 'fa-map-marked-alt', color: '#0ea5e9', today_present: att.sep?.today_present ?? 0, today_rate: att.sep?.today_rate ?? 0, monthly_rate: att.sep?.monthly_avg_rate ?? 0, total_active: att.sep?.total_active_agents ?? 0 },
    { code: 'SEL', label: 'SEL (Local)',       icon: 'fa-map-pin',        color: '#0d9488', today_present: att.sel?.today_present ?? 0, today_rate: att.sel?.today_rate ?? 0, monthly_rate: att.sel?.monthly_avg_rate ?? 0, total_active: att.sel?.total_active_agents ?? 0 },
  ]
})

function deptPct(actifs) {
  const total = data.value.agents?.actifs || 1
  return Math.min(Math.round(((actifs ?? 0) / total) * 100), 100)
}

// ─── PTA ring ─────────────────────────────────────────────────────────────────
const ringCirc = 2 * Math.PI * 52
const ringDash = ringCirc.toFixed(1)
const ringOffset = computed(() => {
  const pctVal = data.value.plan_travail?.avg_completion ?? 0
  return (ringCirc - (ringCirc * pctVal / 100)).toFixed(1)
})

// ─── HELPERS ──────────────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

// ─── DATA LOADING ─────────────────────────────────────────────────────────────
async function loadData() {
  loading.value = true
  loadError.value = null
  try {
    const res = await client.get('/dashboard/caf')
    data.value = res.data?.data ?? res.data ?? {}
  } catch (err) {
    loadError.value = err?.response?.data?.message ?? 'Impossible de charger le tableau de bord CAF.'
  } finally {
    loading.value = false
  }
}
onMounted(loadData)
</script>

<style scoped>
/* ─── LAYOUT ─── */
.caf-dashboard { min-height: 100vh; background: #f0f9f8; }

/* ─── HERO ─── */
.caf-hero { position: relative; background: linear-gradient(135deg, #0d9488 0%, #0f766e 40%, #065f46 100%); color: #fff; padding: 1.5rem 1.25rem 2rem; overflow: hidden; }
.caf-hero-bg { position: absolute; inset: 0; background: radial-gradient(ellipse at 80% 20%, rgba(255,255,255,.08) 0%, transparent 60%); pointer-events: none; }
.caf-hero-inner { position: relative; max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap; }
.caf-hero-left { display: flex; align-items: center; gap: 1rem; }
.caf-hero-avatar { width: 64px; height: 64px; border-radius: 50%; border: 3px solid rgba(255,255,255,.35); overflow: hidden; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.caf-hero-avatar-photo { width: 100%; height: 100%; object-fit: cover; }
.caf-hero-avatar-initials { font-size: 1.4rem; font-weight: 700; color: #fff; }
.caf-hero-greeting { font-size: .78rem; opacity: .75; }
.caf-hero-name { font-size: 1.3rem; font-weight: 800; margin: .1rem 0; }
.caf-hero-fonction { font-size: .78rem; opacity: .8; margin-top: .15rem; }
.caf-hero-role { display: inline-flex; align-items: center; gap: .3rem; font-size: .78rem; background: rgba(255,255,255,.15); border-radius: 20px; padding: .2rem .75rem; margin-top: .3rem; }
.caf-hero-province-badge { background: rgba(255,255,255,.25); border-radius: 12px; padding: .1rem .5rem; font-size: .72rem; margin-left: .3rem; }
.caf-hero-date { font-size: .72rem; opacity: .65; margin-top: .4rem; }
.caf-hero-kpis { display: flex; align-items: center; gap: .75rem; background: rgba(255,255,255,.1); border-radius: 16px; padding: .75rem 1rem; flex-wrap: wrap; }
.caf-kpi { display: flex; align-items: center; gap: .6rem; cursor: default; }
.caf-kpi-clickable { cursor: pointer; transition: opacity .2s; }
.caf-kpi-clickable:hover { opacity: .8; }
.caf-kpi-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: .95rem; }
.caf-kpi-val { font-size: 1.3rem; font-weight: 800; line-height: 1; }
.caf-kpi-lbl { font-size: .65rem; opacity: .75; }
.caf-kpi-drill-icon { font-size: .7rem; opacity: .6; margin-left: .2rem; }
.kpi-divider { width: 1px; height: 32px; background: rgba(255,255,255,.25); }
.kpi-unit { font-size: .7em; margin-left: 1px; }

/* ─── SECTIONS ─── */
.caf-section { max-width: 1200px; margin: 0 auto; padding: 1rem 1.25rem; }
.caf-section-head { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.caf-section-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.caf-section-title { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; }
.caf-section-sub { font-size: .72rem; color: #94a3b8; margin: .1rem 0 0; }
.caf-section-link { margin-left: auto; font-size: .78rem; color: #0d9488; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: .3rem; }
.caf-clickable { cursor: pointer; }

/* ─── PROVINCE CARD ─── */
.caf-province-card { display: flex; align-items: center; gap: 1rem; background: #fff; border-radius: 14px; padding: 1rem 1.25rem; border-left: 4px solid #0d9488; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.caf-province-icon { width: 46px; height: 46px; border-radius: 12px; background: #d1fae5; color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.caf-province-name { font-size: 1.05rem; font-weight: 700; color: #1e293b; }
.caf-province-details { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: .3rem; font-size: .78rem; color: #64748b; }
.caf-province-sep { color: #0d9488; }

/* ─── ACTIONS ─── */
.caf-actions { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .6rem; }
.caf-action { display: flex; align-items: center; gap: .75rem; background: #fff; border-radius: 14px; padding: .85rem 1rem; text-decoration: none; color: inherit; border: 1px solid #f1f5f9; transition: all .2s; position: relative; overflow: hidden; }
.caf-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.caf-action-glow { position: absolute; top: 0; left: 0; width: 3px; height: 100%; border-radius: 14px 0 0 14px; }
.caf-action-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
.caf-action-label { font-size: .82rem; font-weight: 700; color: #1e293b; }
.caf-action-desc { font-size: .68rem; color: #94a3b8; }
.caf-action-arrow { margin-left: auto; color: #cbd5e1; font-size: .75rem; }

/* ─── METRICS ─── */
.caf-metrics { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .6rem; }
.caf-metric { background: #fff; border-radius: 14px; padding: .9rem 1rem; border: 1px solid #f1f5f9; transition: all .2s; }
.caf-metric:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.caf-metric-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .5rem; }
.caf-metric-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .88rem; }
.caf-metric-alert { color: #dc2626; font-size: .85rem; }
.caf-metric-drill-badge { color: #0d9488; font-size: .75rem; }
.caf-metric-val { font-size: 1.4rem; font-weight: 800; line-height: 1.1; }
.caf-metric-lbl { font-size: .68rem; color: #94a3b8; margin-top: .2rem; }
.caf-metric-bar { height: 4px; background: #f1f5f9; border-radius: 4px; margin-top: .5rem; overflow: hidden; }
.caf-metric-bar-fill { height: 100%; border-radius: 4px; transition: width .4s; }

/* ─── DEPARTMENTS ─── */
.caf-dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .75rem; }
.caf-dept-card { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 1rem; transition: all .2s; cursor: pointer; }
.caf-dept-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); border-color: #0d9488; }
.caf-dept-header { display: flex; align-items: center; gap: .6rem; margin-bottom: .6rem; }
.caf-dept-badge { width: 32px; height: 32px; border-radius: 8px; background: #d1fae5; color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
.caf-dept-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.caf-dept-code { font-size: .68rem; color: #94a3b8; }
.caf-drill-arrow { margin-left: auto; color: #0d9488; font-size: .75rem; }
.caf-dept-stats { display: flex; gap: 1rem; margin-bottom: .5rem; }
.caf-dept-stat-val { font-size: 1.1rem; font-weight: 700; color: #1e293b; }
.caf-dept-stat-lbl { font-size: .65rem; color: #94a3b8; }
.caf-dept-bar-wrap { display: flex; align-items: center; gap: .5rem; }
.caf-dept-bar-bg { flex: 1; height: 5px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.caf-dept-bar-fill { height: 100%; background: #0d9488; border-radius: 4px; transition: width .4s; }
.caf-dept-bar-pct { font-size: .68rem; color: #94a3b8; min-width: 28px; text-align: right; }

/* ─── PRÉSENCE ─── */
.caf-presence-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .75rem; }
.caf-presence-card { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 1rem; text-align: center; }
.caf-presence-icon { font-size: 1.3rem; margin-bottom: .3rem; }
.caf-presence-label { font-size: .72rem; font-weight: 600; color: #64748b; margin-bottom: .4rem; }
.caf-presence-rate-big { font-size: 2rem; font-weight: 800; line-height: 1; }
.caf-presence-sub { font-size: .68rem; color: #94a3b8; margin: .2rem 0; }
.caf-presence-bar-bg { height: 6px; background: #e5e7eb; border-radius: 4px; overflow: hidden; margin: .4rem 0; }
.caf-presence-bar-fill { height: 100%; border-radius: 4px; transition: width .4s; }
.caf-presence-monthly { font-size: .68rem; color: #94a3b8; }

/* ─── PTA ─── */
.caf-plan-layout { display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; }
.caf-plan-ring-wrap { position: relative; flex-shrink: 0; }
.caf-plan-ring-label { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; }
.caf-plan-ring-val { font-size: 1.4rem; font-weight: 800; color: #1e293b; }
.caf-plan-ring-sub { font-size: .62rem; color: #94a3b8; }
.caf-plan-trimesters { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: .5rem; }
.caf-plan-trimester { display: grid; grid-template-columns: 28px 1fr 38px 38px; align-items: center; gap: .5rem; }
.caf-plan-trimester-label { font-size: .78rem; font-weight: 700; color: #64748b; }
.caf-plan-trimester-bar-bg { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.caf-plan-trimester-bar-fill { height: 100%; background: #0d9488; border-radius: 4px; transition: width .4s; }
.caf-plan-trimester-pct { font-size: .72rem; font-weight: 600; color: #0d9488; text-align: right; }
.caf-plan-trimester-count { font-size: .68rem; color: #94a3b8; text-align: right; }

/* ─── DUO ─── */
.caf-duo { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.caf-duo-block { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 1rem; }
.caf-req-list { display: flex; flex-direction: column; gap: .4rem; }
.caf-req-row { display: flex; align-items: center; gap: .6rem; padding: .45rem .5rem; border-radius: 8px; transition: background .15s; }
.caf-req-row:hover { background: #f8f9fa; }
.caf-req-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
.caf-req-info { flex: 1; min-width: 0; }
.caf-req-type { font-size: .78rem; font-weight: 600; color: #1e293b; text-transform: capitalize; }
.caf-req-agent { font-size: .68rem; color: #94a3b8; }
.caf-req-date { font-size: .68rem; color: #94a3b8; flex-shrink: 0; }
.caf-empty-msg { display: flex; align-items: center; justify-content: center; padding: 1.5rem; color: #94a3b8; font-size: .82rem; }

/* ─── DRILL-DOWN (harmonisé avec SEN/RH) ─── */
.drill-overlay { position: fixed; inset: 0; z-index: 9999; background: rgba(15,23,42,.55); backdrop-filter: blur(6px); display: flex; align-items: stretch; justify-content: flex-end; }
.drill-panel { width: 580px; max-width: 95vw; background: #f8fafc; display: flex; flex-direction: column; box-shadow: -12px 0 48px rgba(0,0,0,.18); overflow: hidden; }
.drill-header { padding: 1.5rem 1.8rem; color: #fff; flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; }
.drill-header-left { display: flex; align-items: center; gap: .8rem; min-width: 0; }
.drill-header-title { font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: .5rem; }
.drill-header-sub { font-size: .72rem; opacity: .8; margin-top: .15rem; }
.drill-close { width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,.2); background: rgba(255,255,255,.08); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .85rem; flex-shrink: 0; transition: all .2s; }
.drill-close:hover { background: rgba(255,255,255,.2); }
.drill-body { flex: 1; overflow-y: auto; padding: 1.4rem 1.6rem; }
.drill-loading { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 3rem; color: #94a3b8; }
.drill-empty { display: flex; flex-direction: column; align-items: center; gap: .6rem; padding: 2rem; color: #94a3b8; font-size: .85rem; }
.drill-empty i { font-size: 1.5rem; }
.drill-section-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: .5rem; }
.drill-section-tab { padding: .4rem .9rem; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; font-size: .78rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all .2s; }
.drill-section-tab:hover { border-color: #0d9488; color: #0d9488; }
.drill-section-tab.active { background: #0d9488; color: #fff; border-color: #0d9488; }
.drill-dept-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; margin-bottom: 1rem; }
.drill-prov-stat-card { background: #fff; border-radius: 12px; border-left: 3px solid; padding: .8rem 1rem; text-align: center; }
.drill-prov-stat-val { font-size: 1.5rem; font-weight: 800; color: #1e293b; line-height: 1; }
.drill-prov-stat-lbl { font-size: .65rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: .2rem; }
.drill-prov-section-title { display: flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .5rem; }
.drill-prov-agents-table { display: flex; flex-direction: column; gap: .4rem; }
.drill-prov-agent-row { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border-radius: 8px; background: #fff; border: 1px solid #f1f5f9; }
.drill-prov-agent-avatar { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
.drill-prov-agent-info { flex: 1; min-width: 0; }
.drill-prov-agent-name { font-size: .82rem; font-weight: 600; color: #1e293b; }
.drill-prov-agent-fn { font-size: .68rem; color: #94a3b8; }
.caf-dept-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem; }
.caf-dept-chip { padding: .3rem .7rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 20px; font-size: .75rem; font-weight: 600; color: #475569; cursor: pointer; transition: all .2s; }
.caf-dept-chip:hover { background: #d1fae5; border-color: #0d9488; color: #0d9488; }

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
  .caf-hero-inner { flex-direction: column; gap: 1rem; }
  .caf-hero-kpis { width: 100%; display: grid; grid-template-columns: repeat(2, 1fr); gap: .4rem; padding: .6rem; }
  .kpi-divider { display: none; }
  .caf-kpi { gap: .5rem; }
  .caf-section { padding: 1rem .85rem; }
  .caf-duo { grid-template-columns: 1fr; }
  .caf-dept-grid, .caf-metrics { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
  .caf-hero-avatar { width: 48px; height: 48px; }
  .caf-hero-name { font-size: 1.1rem; }
  .caf-kpi-val { font-size: 1.1rem; }
  .caf-kpi-icon { width: 30px; height: 30px; font-size: .82rem; }
  .caf-dept-grid, .caf-metrics { grid-template-columns: 1fr; }
  .caf-actions { grid-template-columns: 1fr; }
  .caf-section { padding: .75rem; }
  .drill-panel { width: 100vw !important; max-width: 100vw !important; border-radius: 16px 16px 0 0; }
  .drill-overlay { align-items: flex-end; }
}
</style>
