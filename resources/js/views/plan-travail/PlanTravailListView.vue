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
          <router-link v-if="canEdit" :to="{ name: 'plan-travail.create' }" class="pt-hero-btn">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle activite
          </router-link>
          <div class="pt-hero-filters">
            <select v-model="filters.annee" class="pt-filter-select" @change="loadPlan">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner message="Chargement du plan de travail..." />
    </div>

    <template v-else>
      <!-- Status filter cards -->
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

      <!-- Trimester filter -->
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

      <!-- Activity cards -->
      <div v-if="flatActivites.length" class="pt-grid">
        <div v-for="a in flatActivites" :key="a.id" class="pt-card">
          <div class="pt-card-top">
            <div class="pt-card-status-icon" :class="statutIconClass(a.statut)">
              <i :class="statutIconName(a.statut)"></i>
            </div>
            <div class="pt-card-info">
              <router-link :to="{ name: 'plan-travail.show', params: { id: a.id } }" class="pt-card-title">
                {{ a.titre }}
              </router-link>
              <div v-if="a.description" class="pt-card-desc">{{ truncate(a.description, 100) }}</div>
              <div class="pt-card-tags">
                <span :class="statutBadgeClass(a.statut)">{{ statutLabel(a.statut) }}</span>
                <span v-if="a.trimestre" class="pt-meta-badge">{{ a.trimestre }}</span>
              </div>
            </div>
          </div>
          <div class="pt-card-meta">
            <span class="pt-meta-item">
              <i class="fas fa-building me-1"></i>{{ a.niveau_administratif }}
              <template v-if="a.departement"> - {{ a.departement.nom }}</template>
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
              <router-link :to="{ name: 'plan-travail.show', params: { id: a.id } }" class="pt-act-btn pt-act-view">
                <i class="fas fa-eye"></i> Voir
              </router-link>
              <router-link v-if="canEdit" :to="{ name: 'plan-travail.edit', params: { id: a.id } }" class="pt-act-btn pt-act-edit">
                <i class="fas fa-edit"></i> Modifier
              </router-link>
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
          <router-link v-if="canEdit" :to="{ name: 'plan-travail.create' }" class="pt-hero-btn mt-3" style="display:inline-flex;">
            <i class="fas fa-plus-circle me-1"></i> Creer la premiere activite
          </router-link>
        </template>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { list } from '@/api/planTravail'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const ui = useUiStore()
const loading = ref(true)
const groupees = ref({})
const stats = ref({ total: 0, planifiee: 0, en_cours: 0, terminee: 0, avg_pourcentage: 0 })
const canEdit = ref(false)
const filters = ref({ annee: new Date().getFullYear(), trimestre: '', statut: '' })

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
  loading.value = true
  try {
    const params = { annee: filters.value.annee }
    if (filters.value.trimestre) params.trimestre = filters.value.trimestre
    if (filters.value.statut) params.statut = filters.value.statut
    const { data } = await list(params)
    groupees.value = data.groupees
    stats.value = data.stats
    canEdit.value = data.canEdit
  } catch {
    ui.addToast('Erreur lors du chargement du plan de travail.', 'danger')
  } finally {
    loading.value = false
  }
}

function setFilter(statut, trimestre) {
  filters.value.statut = statut
  filters.value.trimestre = trimestre
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
</style>
