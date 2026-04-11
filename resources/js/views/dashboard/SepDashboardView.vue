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
              <i class="fas fa-shield-alt me-1"></i>
              Secrétariat Exécutif Provincial — {{ data.province?.nom || 'Votre Province' }}
            </div>
            <div class="sep-hero-date">
              <i class="fas fa-calendar-alt me-1"></i>{{ today }}
            </div>
          </div>
        </div>
        <div class="sep-hero-kpis">
          <div class="sep-kpi sep-kpi-clickable" @click="openProvinceDrilldown('effectifs')">
            <div class="sep-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
              <div class="sep-kpi-val">{{ data.agents?.actifs ?? '-' }}</div>
              <div class="sep-kpi-lbl">Agents actifs</div>
            </div>
          </div>
          <div class="kpi-divider"></div>
          <div class="sep-kpi sep-kpi-clickable" @click="openProvinceDrilldown('presence')">
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
          <div class="sep-kpi sep-kpi-clickable" @click="openProvinceDrilldown('pta')">
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
      <!-- ═══ QUICK ACTIONS ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#e0f2fe;color:#0ea5e9;">
            <i class="fas fa-bolt"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Actions rapides</h3>
            <p class="sep-section-sub">Accès direct aux modules clés</p>
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
            <h3 class="sep-section-title">Indicateurs clés — {{ data.province?.nom }}</h3>
            <p class="sep-section-sub">Vue d'ensemble de votre province</p>
          </div>
        </div>
        <div class="sep-metrics">
          <div v-for="m in metrics" :key="m.label" class="sep-metric sep-metric-clickable" @click="m.drilldown ? openProvinceDrilldown(m.drilldown) : router.push(m.route)">
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

      <!-- ═══ EFFECTIFS PAR ORGANE ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fas fa-sitemap"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Effectifs par organe</h3>
            <p class="sep-section-sub">{{ data.agents?.total ?? 0 }} agents au total dans la province</p>
          </div>
        </div>
        <div class="sep-organe-row">
          <div v-for="o in organeCards" :key="o.code"
               class="sep-organe-chip sep-organe-clickable"
               :style="{ borderColor: o.color }"
               @click="openProvinceDrilldown('effectifs')">
            <div class="sep-organe-chip-badge" :style="{ background: o.color }">{{ o.code }}</div>
            <div class="sep-organe-chip-count">{{ o.count }}</div>
            <div class="sep-organe-chip-lbl">actifs</div>
            <i class="fas fa-chevron-right sep-drill-arrow"></i>
          </div>
        </div>
      </div>

      <!-- ═══ PRÉSENCE ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#d1fae5;color:#059669;">
            <i class="fas fa-user-check"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Présence aujourd'hui</h3>
            <p class="sep-section-sub">{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }} présents ({{ data.attendance?.today_rate ?? 0 }}%)</p>
          </div>
        </div>
        <div class="sep-presence-cards">
          <!-- Aujourd'hui -->
          <div class="sep-presence-card sep-organe-clickable" @click="openProvinceDrilldown('presence')">
            <div class="sep-presence-card-head" style="color:#059669;">
              <i class="fas fa-calendar-day"></i> Aujourd'hui
              <i class="fas fa-chevron-right sep-drill-arrow" style="margin-left:auto;"></i>
            </div>
            <div class="sep-presence-big" style="color:#059669;">{{ data.attendance?.today_rate ?? 0 }}%</div>
            <div class="sep-presence-sub">{{ data.attendance?.today_present ?? 0 }} / {{ data.attendance?.total_active_agents ?? 0 }}</div>
            <div class="sep-presence-bar">
              <div class="sep-presence-fill" :style="{ background: '#059669', width: (data.attendance?.today_rate ?? 0) + '%' }"></div>
            </div>
          </div>
          <!-- Mois -->
          <div class="sep-presence-card sep-organe-clickable" @click="openProvinceDrilldown('presence')">
            <div class="sep-presence-card-head" style="color:#0ea5e9;">
              <i class="fas fa-calendar-alt"></i> Mois courant
              <i class="fas fa-chevron-right sep-drill-arrow" style="margin-left:auto;"></i>
            </div>
            <div class="sep-presence-big" style="color:#0ea5e9;">{{ data.attendance?.monthly_rate ?? 0 }}%</div>
            <div class="sep-presence-sub">Taux moyen mensuel</div>
            <div class="sep-presence-bar">
              <div class="sep-presence-fill" :style="{ background: '#0ea5e9', width: (data.attendance?.monthly_rate ?? 0) + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PLAN DE TRAVAIL ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fas fa-tasks"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Plan de travail {{ currentYear }}</h3>
            <p class="sep-section-sub">{{ data.plan_travail?.terminee ?? 0 }} / {{ data.plan_travail?.total ?? 0 }} activités terminées ({{ data.plan_travail?.avg_completion ?? 0 }}%)</p>
          </div>
        </div>
        <div class="sep-plan-wrap">
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
            <div v-for="t in (data.plan_travail?.by_trimestre || [])" :key="t.trimestre" class="sep-trim sep-organe-clickable" @click="openProvinceDrilldown('pta')">
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
      </div>

      <!-- ═══ DÉPARTEMENTS ═══ -->
      <div class="sep-section">
        <div class="sep-section-head">
          <div class="sep-section-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fas fa-building"></i>
          </div>
          <div>
            <h3 class="sep-section-title">Départements de la province</h3>
            <p class="sep-section-sub">{{ (data.departments || []).length }} département(s) — cliquez pour explorer</p>
          </div>
        </div>
        <div class="sep-dept-grid">
          <div v-for="dept in (data.departments || [])" :key="dept.id"
               class="sep-dept-card sep-organe-clickable"
               @click="openDepartmentDrilldown(dept.id)">
            <div class="sep-dept-head">
              <div class="sep-dept-badge"><i class="fas fa-building"></i></div>
              <div class="sep-dept-info">
                <div class="sep-dept-name">{{ dept.nom }}</div>
                <div class="sep-dept-code">{{ dept.code }}</div>
              </div>
              <i class="fas fa-chevron-right sep-drill-arrow"></i>
            </div>
            <div class="sep-dept-stats">
              <div class="sep-dept-stat">
                <span class="sep-dept-stat-val">{{ dept.total }}</span>
                <span class="sep-dept-stat-lbl">Total</span>
              </div>
              <div class="sep-dept-stat">
                <span class="sep-dept-stat-val" style="color:#059669;">{{ dept.actifs }}</span>
                <span class="sep-dept-stat-lbl">Actifs</span>
              </div>
            </div>
            <div class="sep-dept-bar-bg">
              <div class="sep-dept-bar-fill" :style="{ width: dept.total > 0 ? Math.round(dept.actifs / dept.total * 100) + '%' : '0%' }"></div>
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
                <div class="drill-header" :style="{ background: '#0ea5e9' }">
                  <div class="drill-header-left">
                    <button v-if="drilldownLevel === 'department'" class="drill-back" @click="backToProvince">
                      <i class="fas fa-arrow-left"></i>
                    </button>
                    <div>
                      <div class="drill-header-title" v-if="drilldownLevel === 'province' && drilldownProvince">
                        <i class="fas fa-map-marker-alt"></i> {{ drilldownProvince.province?.nom || data.province?.nom }}
                      </div>
                      <div class="drill-header-title" v-else-if="drilldownLevel === 'department' && drilldownDepartment">
                        <i class="fas fa-building"></i> {{ drilldownDepartment.department?.nom }}
                      </div>
                      <div class="drill-header-title" v-else>
                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                      </div>
                      <div class="drill-header-sub" v-if="drilldownLevel === 'province' && drilldownProvince">
                        {{ drilldownProvince.effectifs?.total }} agents · {{ drilldownProvince.province?.ville_secretariat || '' }}
                      </div>
                      <div class="drill-header-sub" v-else-if="drilldownLevel === 'department' && drilldownDepartment">
                        <template v-if="drilldownSection === 'effectifs'">{{ drilldownDepartment.effectifs?.total }} agents · {{ drilldownDepartment.department?.code || '' }}</template>
                        <template v-else-if="drilldownSection === 'presence'">Présence · {{ drilldownDepartment.effectifs?.actifs }} agents actifs</template>
                        <template v-else>PTA {{ currentYear }} · {{ drilldownDepartment.pta?.total || 0 }} activités</template>
                      </div>
                    </div>
                  </div>
                  <button class="drill-close" @click="closeDrilldown"><i class="fas fa-times"></i></button>
                </div>

                <!-- Section tabs (province level) -->
                <div v-if="drilldownLevel === 'province'" class="drill-tabs">
                  <button v-for="tab in drillTabs" :key="tab.key"
                    :class="['drill-tab', { active: drilldownSection === tab.key }]"
                    @click="drilldownSection = tab.key">
                    <i class="fas" :class="tab.icon"></i> {{ tab.label }}
                  </button>
                </div>

                <!-- Body -->
                <div class="drill-body">
                  <div v-if="drilldownLoading" class="drill-loading">
                    <i class="fas fa-circle-notch fa-spin fa-2x" style="color:#0ea5e9;"></i>
                    <p>Chargement des données…</p>
                  </div>

                  <!-- ── LEVEL 1 : PROVINCE DETAIL ── -->
                  <template v-else-if="drilldownLevel === 'province' && drilldownProvince">
                    <!-- Province info -->
                    <div class="drill-prov-info">
                      <div class="drill-prov-info-item" v-if="drilldownProvince.province?.nom_secretariat_executif">
                        <i class="fas fa-user-tie"></i>
                        <span>SE : {{ drilldownProvince.province.nom_secretariat_executif }}</span>
                      </div>
                      <div class="drill-prov-info-item" v-if="drilldownProvince.province?.nom_gouverneur">
                        <i class="fas fa-landmark"></i>
                        <span>Gouverneur : {{ drilldownProvince.province.nom_gouverneur }}</span>
                      </div>
                      <div class="drill-prov-info-item" v-if="drilldownProvince.province?.telephone">
                        <i class="fas fa-phone"></i>
                        <span>{{ drilldownProvince.province.telephone }}</span>
                      </div>
                      <div class="drill-prov-info-item" v-if="drilldownProvince.province?.email">
                        <i class="fas fa-envelope"></i>
                        <span>{{ drilldownProvince.province.email }}</span>
                      </div>
                    </div>

                    <!-- Stats cards -->
                    <div class="drill-prov-stats">
                      <template v-if="drilldownSection === 'effectifs'">
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#e0f2fe;color:#0077B5;"><i class="fas fa-users"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.effectifs?.total }}</div>
                          <div class="drill-prov-stat-lbl">Agents</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-user-check"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.effectifs?.actifs }}</div>
                          <div class="drill-prov-stat-lbl">Actifs</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-user-slash"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.effectifs?.suspendus }}</div>
                          <div class="drill-prov-stat-lbl">Suspendus</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#f1f5f9;color:#64748b;"><i class="fas fa-user-clock"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.effectifs?.anciens }}</div>
                          <div class="drill-prov-stat-lbl">Anciens</div>
                        </div>
                      </template>
                      <template v-else-if="drilldownSection === 'presence'">
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-user-check"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.presence?.today_present }}</div>
                          <div class="drill-prov-stat-lbl">Présents</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-user-times"></i></div>
                          <div class="drill-prov-stat-val">{{ (drilldownProvince.presence?.total_active ?? 0) - (drilldownProvince.presence?.today_present ?? 0) }}</div>
                          <div class="drill-prov-stat-lbl">Absents</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-chart-line"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.presence?.today_rate }}%</div>
                          <div class="drill-prov-stat-lbl">Taux jour</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#e0f2fe;color:#0ea5e9;"><i class="fas fa-calendar-check"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.presence?.monthly_rate }}%</div>
                          <div class="drill-prov-stat-lbl">Moy. mois</div>
                        </div>
                      </template>
                      <template v-else>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#e0f2fe;color:#0ea5e9;"><i class="fas fa-tasks"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.pta?.total }}</div>
                          <div class="drill-prov-stat-lbl">Activités</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-check-circle"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.pta?.terminee }}</div>
                          <div class="drill-prov-stat-lbl">Terminées</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-spinner"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.pta?.en_cours }}</div>
                          <div class="drill-prov-stat-lbl">En cours</div>
                        </div>
                        <div class="drill-prov-stat-card">
                          <div class="drill-prov-stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-percentage"></i></div>
                          <div class="drill-prov-stat-val">{{ drilldownProvince.pta?.avg }}%</div>
                          <div class="drill-prov-stat-lbl">Avancement</div>
                        </div>
                      </template>
                    </div>

                    <!-- ─── Contenu EFFECTIFS ─── -->
                    <template v-if="drilldownSection === 'effectifs'">
                      <div class="drill-prov-section-title"><i class="fas fa-sitemap"></i> Répartition par organe</div>
                      <div class="drill-prov-organe-row">
                        <div class="drill-prov-organe-chip" style="border-color:#0077B5;">
                          <span class="drill-prov-organe-code" style="color:#0077B5;">SEN</span>
                          <span>{{ drilldownProvince.by_organe?.sen ?? 0 }}</span>
                        </div>
                        <div class="drill-prov-organe-chip" style="border-color:#0ea5e9;">
                          <span class="drill-prov-organe-code" style="color:#0ea5e9;">SEP</span>
                          <span>{{ drilldownProvince.by_organe?.sep ?? 0 }}</span>
                        </div>
                        <div class="drill-prov-organe-chip" style="border-color:#0d9488;">
                          <span class="drill-prov-organe-code" style="color:#0d9488;">SEL</span>
                          <span>{{ drilldownProvince.by_organe?.sel ?? 0 }}</span>
                        </div>
                      </div>

                      <div v-if="drilldownProvince.departments?.length" class="drill-prov-section-title"><i class="fas fa-building"></i> Départements</div>
                      <div class="drill-prov-dept-grid">
                        <div v-for="d in drilldownProvince.departments" :key="d.id" class="drill-prov-dept drill-item-clickable" @click="openDepartmentDrilldown(d.id)">
                          <div class="drill-prov-dept-name">{{ d.nom }} <i class="fas fa-chevron-right" style="font-size:0.7em;opacity:0.5;margin-left:4px;"></i></div>
                          <div class="drill-prov-dept-count">{{ d.actifs }} <small>actifs</small> / {{ d.total }}</div>
                        </div>
                      </div>

                      <div v-if="drilldownProvince.agents?.length" class="drill-prov-section-title"><i class="fas fa-user"></i> Agents ({{ drilldownProvince.agents.length }})</div>
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
                      <div class="drill-prov-section-title"><i class="fas fa-user"></i> Agents actifs ({{ drilldownProvince.presence?.total_active }})</div>
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
                      <div class="drill-prov-section-title"><i class="fas fa-clipboard-list"></i> Activités PTA {{ currentYear }} ({{ drilldownProvince.activites?.length || 0 }})</div>
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

                  <!-- ── LEVEL 2 : DÉPARTEMENT DETAIL ── -->
                  <template v-else-if="drilldownLevel === 'department' && drilldownDepartment">

                    <!-- ─── Contenu EFFECTIFS ─── -->
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
                        <div v-for="a in drilldownDepartment.agents" :key="a.id" class="drill-prov-agent-row">
                          <div class="drill-prov-agent-avatar" :style="{ background: a.sexe === 'F' ? '#fce7f3' : '#dbeafe', color: a.sexe === 'F' ? '#be185d' : '#1d4ed8' }">
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

                    <!-- ─── Contenu PRÉSENCE ─── -->
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
                        <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
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
                        <div v-for="a in drilldownDepartment.agents" :key="a.id" class="drill-prov-agent-row">
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
                      <div class="drill-dept-grid">
                        <div class="drill-prov-stat-card" style="border-color:#0ea5e9;">
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
                          <i class="fas fa-clipboard-list"></i> Activités PTA {{ currentYear }} ({{ drilldownDepartment.activites.length }})
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
      </Teleport>

      <!-- ═══ FOOTER ═══ -->
      <div class="sep-footer">
        <div class="sep-footer-inner">
          <div class="sep-footer-left">
            <i class="fas fa-sync-alt"></i>
            <span>Tableau de bord mis à jour le {{ today }} à {{ currentTime }}</span>
          </div>
          <div class="sep-footer-right">
            <span class="sep-footer-badge">E-PNMLS v2.0</span>
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
const loadError = ref(null)
const currentYear = new Date().getFullYear()

const today = computed(() => new Date().toLocaleDateString('fr-FR', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
}))

const currentTime = computed(() => new Date().toLocaleTimeString('fr-FR', {
  hour: '2-digit', minute: '2-digit',
}))

// ─── DRILL-DOWN STATE ───
const drilldownOpen = ref(false)
const drilldownLoading = ref(false)
const drilldownProvince = ref(null)
const drilldownDepartment = ref(null)
const drilldownLevel = ref('province')
const drilldownSection = ref('effectifs')

const drillTabs = [
  { key: 'effectifs', label: 'Effectifs', icon: 'fa-users' },
  { key: 'presence', label: 'Présence', icon: 'fa-user-check' },
  { key: 'pta', label: 'Plan de travail', icon: 'fa-tasks' },
]

async function openProvinceDrilldown(section = 'effectifs') {
  drilldownOpen.value = true
  drilldownLoading.value = true
  drilldownLevel.value = 'province'
  drilldownSection.value = section
  drilldownDepartment.value = null
  try {
    const { data: result } = await client.get(`/dashboard/executive/province/${data.value.province?.id}`)
    drilldownProvince.value = result.data ?? result
  } catch (e) {
    drilldownProvince.value = null
  } finally {
    drilldownLoading.value = false
  }
}

async function openDepartmentDrilldown(id) {
  drilldownLoading.value = true
  drilldownLevel.value = 'department'
  try {
    const { data: result } = await client.get(`/dashboard/executive/department/${id}`)
    drilldownDepartment.value = result.data ?? result
  } catch (e) {
    drilldownDepartment.value = null
  } finally {
    drilldownLoading.value = false
  }
}

function closeDrilldown() {
  drilldownOpen.value = false
  drilldownProvince.value = null
  drilldownDepartment.value = null
  drilldownLevel.value = 'province'
  drilldownSection.value = 'effectifs'
}

function backToProvince() {
  drilldownDepartment.value = null
  drilldownLevel.value = 'province'
}

// ─── COMPUTED DATA ───
const maxMetric = computed(() => {
  const vals = [
    data.value.agents?.total ?? 0,
    data.value.agents?.actifs ?? 0,
    data.value.requests?.en_attente ?? 0,
    data.value.signalements?.ouvert ?? 0,
    data.value.plan_travail?.total ?? 0,
    data.value.communiques?.actifs ?? 0,
  ]
  return Math.max(...vals, 1)
})

function pct(val) {
  return Math.min(((val ?? 0) / maxMetric.value) * 100, 100)
}

const metrics = computed(() => [
  { label: 'Agents total', value: data.value.agents?.total ?? 0, icon: 'fa-users', color: '#0ea5e9', bg: '#e0f2fe', pct: pct(data.value.agents?.total), alert: false, drilldown: 'effectifs' },
  { label: 'Agents actifs', value: data.value.agents?.actifs ?? 0, icon: 'fa-user-check', color: '#059669', bg: '#d1fae5', pct: pct(data.value.agents?.actifs), alert: false, drilldown: 'effectifs' },
  { label: 'Demandes en attente', value: data.value.requests?.en_attente ?? 0, icon: 'fa-hourglass-half', color: '#d97706', bg: '#fef3c7', pct: pct(data.value.requests?.en_attente), alert: (data.value.requests?.en_attente ?? 0) > 5, route: '/requests' },
  { label: 'Signalements ouverts', value: data.value.signalements?.ouvert ?? 0, icon: 'fa-exclamation-circle', color: '#dc2626', bg: '#fee2e2', pct: pct(data.value.signalements?.ouvert), alert: (data.value.signalements?.ouvert ?? 0) > 0, route: '/signalements' },
  { label: 'Plan annuel', value: (data.value.plan_travail?.avg_completion ?? 0) + '%', icon: 'fa-tasks', color: '#d97706', bg: '#fef3c7', pct: pct(data.value.plan_travail?.avg_completion), alert: false, drilldown: 'pta' },
  { label: 'Communiqués actifs', value: data.value.communiques?.actifs ?? 0, icon: 'fa-bullhorn', color: '#0891b2', bg: '#cffafe', pct: pct(data.value.communiques?.actifs), alert: false, route: '/communiques' },
])

const organeCards = computed(() => {
  const bo = data.value.agents?.by_organe || {}
  return [
    { code: 'SEN', count: bo.sen ?? 0, color: '#0077B5' },
    { code: 'SEP', count: bo.sep ?? 0, color: '#0ea5e9' },
    { code: 'SEL', count: bo.sel ?? 0, color: '#0d9488' },
  ]
})

const ringCirc = 2 * Math.PI * 52
const ringDash = ringCirc.toFixed(1)
const ringOffset = computed(() => {
  const pctVal = data.value.plan_travail?.avg_completion ?? 0
  return (ringCirc - (ringCirc * pctVal / 100)).toFixed(1)
})

const quickActions = [
  { to: '/signalements', label: 'Signalements', desc: 'Consulter les alertes', icon: 'fa-flag', color: '#dc2626', bg: '#fee2e2' },
  { to: '/plan-travail', label: 'PTA', desc: 'Suivi stratégique annuel', icon: 'fa-tasks', color: '#d97706', bg: '#fef3c7' },
  { to: '/requests', label: 'Demandes', desc: 'Gérer les demandes', icon: 'fa-paper-plane', color: '#059669', bg: '#d1fae5' },
  { to: '/documents', label: 'Documents', desc: 'Accéder aux documents', icon: 'fa-folder-open', color: '#6366f1', bg: '#e0e7ff' },
  { to: '/notifications', label: 'Notifications', desc: 'Voir les messages', icon: 'fa-bell', color: '#0891b2', bg: '#cffafe' },
  { to: '/profile', label: 'Mon profil', desc: 'Voir mes infos', icon: 'fa-user-circle', color: '#7c3aed', bg: '#ede9fe' },
]

onMounted(async () => {
  try {
    const { data: result } = await client.get('/dashboard/executive/sep')
    data.value = result.data ?? result
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
  background: linear-gradient(135deg, #0c2340 0%, #0c4a6e 40%, #0369a1 70%, #0ea5e9 100%);
  box-shadow: 0 8px 32px rgba(0, 30, 60, .25);
}
.sep-hero-bg {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 400px 300px at 85% 20%, rgba(14,165,233,.25) 0%, transparent 70%),
    radial-gradient(ellipse 200px 200px at 10% 80%, rgba(56,189,248,.1) 0%, transparent 60%);
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
.sep-hero-role { font-size: .78rem; font-weight: 600; opacity: .75; margin-bottom: .2rem; display: flex; align-items: center; }
.sep-hero-date { font-size: .72rem; opacity: .45; text-transform: capitalize; }

.sep-hero-kpis {
  display: flex; align-items: center; gap: 0;
  background: rgba(255,255,255,.08); border-radius: 16px; padding: .8rem 1.2rem;
  border: 1px solid rgba(255,255,255,.1); backdrop-filter: blur(8px);
}
.sep-kpi { display: flex; align-items: center; gap: .6rem; padding: 0 1rem; color: #fff; }
.sep-kpi-clickable { cursor: pointer; border-radius: 10px; transition: background .15s; }
.sep-kpi-clickable:hover { background: rgba(255,255,255,.15); }
.sep-kpi-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.1); display: flex; align-items: center;
  justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.sep-kpi-val { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.kpi-unit { font-size: .85rem; font-weight: 600; opacity: .7; }
.sep-kpi-lbl { font-size: .62rem; opacity: .5; text-transform: uppercase; letter-spacing: .4px; margin-top: .1rem; font-weight: 600; }
.kpi-divider { width: 1px; height: 36px; background: rgba(255,255,255,.12); margin: 0 .2rem; }

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
.sep-action-icon {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
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
.sep-metric-clickable { cursor: pointer; }
.sep-metric:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sep-metric-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .7rem; }
.sep-metric-icon {
  width: 40px; height: 40px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; font-size: .9rem;
}
.sep-metric-alert { color: #ef4444; font-size: .75rem; animation: pulse-alert 2s infinite; }
@keyframes pulse-alert { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
.sep-metric-val { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: .2rem; }
.sep-metric-lbl { font-size: .68rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.sep-metric-bar { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: .7rem; }
.sep-metric-bar-fill { height: 100%; border-radius: 4px; transition: width .8s ease; min-width: 3px; }

/* ═══════════ ORGANE CHIPS ═══════════ */
.sep-organe-row { display: flex; gap: .75rem; flex-wrap: wrap; }
.sep-organe-chip {
  display: flex; align-items: center; gap: .6rem; padding: .8rem 1.2rem;
  background: #fff; border: 2px solid; border-radius: 12px;
  cursor: pointer; transition: all .2s; flex: 1; min-width: 120px;
}
.sep-organe-chip:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
.sep-organe-chip-badge {
  width: 36px; height: 36px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center;
  font-size: .68rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.sep-organe-chip-count { font-size: 1.4rem; font-weight: 800; color: #1e293b; }
.sep-organe-chip-lbl { font-size: .65rem; color: #94a3b8; font-weight: 600; }
.sep-drill-arrow { font-size: .65rem; color: #cbd5e1; margin-left: auto; transition: all .2s; }
.sep-organe-chip:hover .sep-drill-arrow,
.sep-organe-clickable:hover .sep-drill-arrow { color: #0ea5e9; transform: translateX(3px); }

/* ═══════════ PRÉSENCE ═══════════ */
.sep-presence-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; }
.sep-presence-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s; cursor: pointer;
}
.sep-presence-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sep-presence-card-head {
  font-size: .88rem; font-weight: 700; margin-bottom: .8rem;
  display: flex; align-items: center; gap: .5rem;
}
.sep-presence-big { font-size: 2rem; font-weight: 800; text-align: center; line-height: 1; margin-bottom: .15rem; }
.sep-presence-sub { font-size: .7rem; color: #94a3b8; text-align: center; margin-bottom: .8rem; }
.sep-presence-bar { height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
.sep-presence-fill { height: 100%; border-radius: 6px; transition: width .8s ease; min-width: 2px; }

/* ═══════════ PLAN DE TRAVAIL ═══════════ */
.sep-plan-wrap {
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
.sep-trim { cursor: pointer; border-radius: 8px; padding: .4rem .6rem; transition: background .15s; }
.sep-trim:hover { background: #f8fafc; }
.sep-trim-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .3rem; }
.sep-trim-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.sep-trim-pct { font-size: .78rem; font-weight: 700; color: #0ea5e9; }
.sep-trim-bar { height: 8px; background: #f1f5f9; border-radius: 8px; overflow: hidden; }
.sep-trim-fill {
  height: 100%; border-radius: 8px; transition: width .8s ease;
  background: linear-gradient(90deg, #0ea5e9, #38bdf8);
}
.sep-trim-detail { font-size: .65rem; color: #94a3b8; margin-top: .2rem; }

/* ═══════════ DÉPARTEMENTS ═══════════ */
.sep-dept-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.sep-dept-card {
  background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
  padding: 1.2rem; transition: all .25s; cursor: pointer;
  border-top: 4px solid #0ea5e9;
}
.sep-dept-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.sep-dept-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .75rem; }
.sep-dept-badge {
  width: 36px; height: 36px; border-radius: 10px;
  background: #e0f2fe; color: #0ea5e9;
  display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0;
}
.sep-dept-info { flex: 1; min-width: 0; }
.sep-dept-name { font-size: .85rem; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sep-dept-code { font-size: .65rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.sep-dept-stats { display: flex; gap: .75rem; margin-bottom: .6rem; }
.sep-dept-stat { flex: 1; text-align: center; background: #f8fafc; border-radius: 8px; padding: .4rem; }
.sep-dept-stat-val { display: block; font-size: 1.2rem; font-weight: 800; line-height: 1; }
.sep-dept-stat-lbl { display: block; font-size: .6rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: .15rem; }
.sep-dept-bar-bg { height: 5px; background: #f1f5f9; border-radius: 5px; overflow: hidden; }
.sep-dept-bar-fill { height: 100%; background: #0ea5e9; border-radius: 5px; transition: width .8s ease; min-width: 2px; }

/* ═══════════ ORGANE CLICKABLE SHARED ═══════════ */
.sep-organe-clickable { cursor: pointer; }

/* ═══════════ FOOTER ═══════════ */
.sep-footer {
  margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 1rem;
}
.sep-footer-inner { display: flex; justify-content: space-between; align-items: center; }
.sep-footer-left { display: flex; align-items: center; gap: .5rem; font-size: .72rem; color: #94a3b8; }
.sep-footer-left i { color: #0ea5e9; }
.sep-footer-badge {
  background: linear-gradient(90deg, #0ea5e9, #38bdf8); color: #fff;
  padding: .2rem .6rem; border-radius: 20px; font-size: .68rem; font-weight: 700;
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
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  padding: 1.25rem 1.5rem; flex-shrink: 0;
}
.drill-header-left { display: flex; align-items: center; gap: .75rem; flex: 1; min-width: 0; }
.drill-header-title { font-size: 1rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: .4rem; }
.drill-header-sub { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
.drill-back {
  background: rgba(255,255,255,.15); border: none; color: #fff;
  width: 32px; height: 32px; border-radius: 8px; cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: .85rem;
  transition: background .15s; flex-shrink: 0;
}
.drill-back:hover { background: rgba(255,255,255,.25); }
.drill-close {
  background: rgba(255,255,255,.15); border: none; color: #fff;
  width: 32px; height: 32px; border-radius: 8px; cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: .85rem;
  transition: background .15s; flex-shrink: 0;
}
.drill-close:hover { background: rgba(255,255,255,.25); }

/* Tabs */
.drill-tabs {
  display: flex; background: #fff; border-bottom: 1px solid #e5e7eb;
  padding: 0 1rem; gap: .25rem; flex-shrink: 0;
}
.drill-tab {
  padding: .7rem .9rem; font-size: .78rem; font-weight: 600; color: #64748b;
  border: none; background: none; cursor: pointer; border-bottom: 2px solid transparent;
  transition: all .15s; display: flex; align-items: center; gap: .4rem;
}
.drill-tab.active { color: #0ea5e9; border-bottom-color: #0ea5e9; }
.drill-tab:hover:not(.active) { color: #334155; background: #f8fafc; }

/* Body */
.drill-body { flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem; }
.drill-loading { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 0; gap: 1rem; color: #94a3b8; }
.drill-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 0; gap: .75rem; color: #94a3b8; }
.drill-empty i { font-size: 1.5rem; opacity: .5; }

/* Province info */
.drill-prov-info { display: flex; flex-wrap: wrap; gap: .5rem .75rem; margin-bottom: 1.1rem; padding: .85rem; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
.drill-prov-info-item { display: flex; align-items: center; gap: .4rem; font-size: .78rem; color: #475569; }
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
.drill-prov-stat-lbl { font-size: .62rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; margin-top: .15rem; }

/* Section title */
.drill-prov-section-title {
  font-size: .82rem; font-weight: 700; color: #475569; margin: 1.2rem 0 .6rem;
  display: flex; align-items: center; gap: .4rem; padding-bottom: .4rem;
  border-bottom: 1px solid #f1f5f9;
}

/* Organe chips */
.drill-prov-organe-row { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
.drill-prov-organe-chip {
  display: flex; align-items: center; gap: .4rem; padding: .5rem .8rem;
  background: #fff; border: 1.5px solid; border-radius: 8px; font-size: .82rem; font-weight: 600;
}
.drill-prov-organe-code { font-weight: 800; }

/* Dept grid */
.drill-prov-dept-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; margin-bottom: 1rem; }
.drill-prov-dept {
  background: #fff; border-radius: 10px; border: 1px solid #e5e7eb;
  padding: .75rem; cursor: pointer; transition: all .15s;
}
.drill-prov-dept:hover { border-color: #0ea5e9; background: #f0f9ff; }
.drill-prov-dept-name { font-size: .8rem; font-weight: 700; color: #1e293b; margin-bottom: .2rem; }
.drill-prov-dept-count { font-size: .72rem; color: #64748b; }
.drill-item-clickable { cursor: pointer; }

/* Agents table */
.drill-prov-agents-table { display: flex; flex-direction: column; gap: .4rem; }
.drill-prov-agent-row { display: flex; align-items: center; gap: .6rem; padding: .55rem .75rem; background: #fff; border-radius: 10px; border: 1px solid #f1f5f9; }
.drill-prov-agent-avatar { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
.drill-prov-agent-info { flex: 1; min-width: 0; }
.drill-prov-agent-name { font-size: .8rem; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.drill-prov-agent-fn { font-size: .68rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Activités */
.drill-prov-activites { display: flex; flex-direction: column; gap: .6rem; }
.drill-prov-activite { background: #fff; border-radius: 10px; border: 1px solid #e5e7eb; padding: .85rem; }
.drill-activite-head { display: flex; align-items: flex-start; gap: .7rem; margin-bottom: .4rem; }
.drill-activite-pct { font-size: 1.1rem; font-weight: 800; min-width: 44px; flex-shrink: 0; line-height: 1.2; }
.drill-activite-info { flex: 1; min-width: 0; }
.drill-activite-titre { font-size: .82rem; font-weight: 700; color: #1e293b; margin-bottom: .25rem; }
.drill-activite-meta { display: flex; flex-wrap: wrap; gap: .3rem; }
.drill-activite-tag, .drill-activite-cat, .drill-activite-dept {
  background: #f1f5f9; color: #64748b; font-size: .65rem; font-weight: 600;
  padding: .15rem .4rem; border-radius: 4px;
}
.drill-activite-statut { font-size: .65rem; font-weight: 600; padding: .15rem .4rem; border-radius: 4px; background: #d1fae5; color: #059669; }
.drill-activite-bar { height: 5px; background: #f1f5f9; border-radius: 5px; overflow: hidden; }
.drill-activite-bar-fill { height: 100%; border-radius: 5px; transition: width .6s ease; }
.drill-activite-dates { font-size: .67rem; color: #94a3b8; margin-top: .4rem; display: flex; align-items: center; gap: .3rem; }

/* Department stats grid */
.drill-dept-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; margin-bottom: 1rem; }

/* Item bar */
.drill-item-bar { height: 5px; background: #f1f5f9; border-radius: 5px; overflow: hidden; }
.drill-item-bar-fill { height: 100%; border-radius: 5px; transition: width .6s ease; }

/* ═══════════ TRANSITIONS ═══════════ */
.drill-fade-enter-active, .drill-fade-leave-active { transition: opacity .25s; }
.drill-fade-enter-from, .drill-fade-leave-to { opacity: 0; }
.drill-slide-enter-active, .drill-slide-leave-active { transition: transform .25s ease, opacity .2s; }
.drill-slide-enter-from { transform: translateX(30px); opacity: 0; }
.drill-slide-leave-to { transform: translateX(-30px); opacity: 0; }

/* ═══════════ RESPONSIVE ═══════════ */
@media (max-width: 900px) {
  .sep-actions { grid-template-columns: repeat(2, 1fr); }
  .sep-metrics { grid-template-columns: repeat(2, 1fr); }
  .sep-dept-grid { grid-template-columns: repeat(2, 1fr); }
  .drill-prov-stats { grid-template-columns: repeat(2, 1fr); }
  .drill-dept-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .sep-hero-inner { flex-direction: column; }
  .sep-hero-kpis { flex-wrap: wrap; }
  .sep-actions, .sep-metrics, .sep-dept-grid { grid-template-columns: 1fr; }
  .sep-presence-cards { grid-template-columns: 1fr; }
  .drill-panel { width: 100vw; }
}
</style>
