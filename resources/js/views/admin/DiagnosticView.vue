<template>
  <div class="diagnostic-page">
    <section class="diagnostic-hero">
      <div>
        <span class="diagnostic-kicker">
          <i class="fas fa-stethoscope"></i>
          Super admin
        </span>
        <h1>Diagnostic donnees</h1>
        <p>Repere les liaisons cassees qui empechent les agents, localites, SEL et comptes utilisateurs de bien s afficher.</p>
      </div>
      <button class="diagnostic-refresh" type="button" :disabled="loading" @click="loadDiagnostics">
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
        Actualiser
      </button>
    </section>

    <div v-if="loading" class="diagnostic-loading">
      <div class="spinner-border text-primary"></div>
      <span>Analyse des donnees en cours...</span>
    </div>

    <template v-else>
      <section class="diagnostic-summary">
        <article class="summary-card score" :class="scoreClass">
          <span>Score qualite</span>
          <strong>{{ summary.score ?? 0 }}%</strong>
        </article>
        <article class="summary-card danger">
          <span>Bloquants</span>
          <strong>{{ summary.blocking ?? 0 }}</strong>
        </article>
        <article class="summary-card warning">
          <span>A surveiller</span>
          <strong>{{ summary.warnings ?? 0 }}</strong>
        </article>
        <article class="summary-card neutral">
          <span>Anomalies totales</span>
          <strong>{{ summary.issues ?? 0 }}</strong>
        </article>
        <article class="summary-card neutral">
          <span>Agents</span>
          <strong>{{ summary.agents ?? 0 }}</strong>
        </article>
        <article class="summary-card neutral">
          <span>Localites</span>
          <strong>{{ summary.localites ?? 0 }}</strong>
        </article>
      </section>

      <section class="diagnostic-toolbar">
        <div class="diagnostic-search">
          <i class="fas fa-search"></i>
          <input v-model.trim="search" type="search" placeholder="Rechercher un agent, email, localite, province...">
        </div>
        <div class="diagnostic-filters">
          <button
            v-for="filter in filters"
            :key="filter.value"
            type="button"
            :class="{ active: severityFilter === filter.value }"
            @click="severityFilter = filter.value"
          >
            {{ filter.label }}
          </button>
        </div>
      </section>

      <section class="diagnostic-grid">
        <article
          v-for="category in filteredCategories"
          :key="category.key"
          class="diagnostic-card"
          :class="`severity-${category.severity}`"
        >
          <header class="diagnostic-card-header">
            <div>
              <span class="severity-badge">{{ severityLabel(category.severity) }}</span>
              <h2>{{ category.title }}</h2>
              <p>{{ category.description }}</p>
            </div>
            <strong>{{ category.count }}</strong>
          </header>

          <div class="diagnostic-recommendation">
            <i class="fas fa-lightbulb"></i>
            {{ category.recommendation }}
          </div>

          <div v-if="category.items.length" class="diagnostic-items">
            <div v-for="item in category.items" :key="item.id" class="diagnostic-item">
              <div class="item-main">
                <div class="item-icon">
                  <i :class="entityIcon(item.entity_type)"></i>
                </div>
                <div>
                  <h3>{{ item.title }}</h3>
                  <p>{{ item.subtitle }}</p>
                  <span>{{ item.message }}</span>
                  <div v-if="item.meta?.length" class="item-meta">
                    <small v-for="meta in item.meta" :key="`${item.id}-${meta.label}`">
                      {{ meta.label }}: <b>{{ meta.value }}</b>
                    </small>
                  </div>
                </div>
              </div>
              <router-link v-if="item.action?.url" class="item-action" :to="item.action.url">
                {{ item.action.label }}
                <i class="fas fa-arrow-right"></i>
              </router-link>
            </div>
            <div v-if="category.overflow" class="diagnostic-overflow">
              +{{ category.overflow }} autres elements. Utilise la recherche pour cibler.
            </div>
          </div>

          <div v-else class="diagnostic-ok">
            <i class="fas fa-check-circle"></i>
            Aucun probleme detecte ici.
          </div>
        </article>
      </section>

      <div v-if="!filteredCategories.length" class="diagnostic-empty">
        <i class="fas fa-search"></i>
        <p>Aucun resultat pour cette recherche.</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import client from '@/api/client'

const loading = ref(true)
const summary = ref({})
const categories = ref([])
const search = ref('')
const severityFilter = ref('all')

const filters = [
  { value: 'all', label: 'Tous' },
  { value: 'danger', label: 'Bloquants' },
  { value: 'warning', label: 'A surveiller' },
  { value: 'info', label: 'Info' },
]

const scoreClass = computed(() => {
  const score = Number(summary.value.score ?? 0)
  if (score >= 85) return 'good'
  if (score >= 65) return 'medium'
  return 'low'
})

const filteredCategories = computed(() => {
  const term = search.value.toLowerCase()

  return categories.value
    .filter(category => severityFilter.value === 'all' || category.severity === severityFilter.value)
    .map(category => {
      if (!term) return category

      const searchableHeader = [
        category.title,
        category.description,
        category.recommendation,
      ].join(' ').toLowerCase()

      const items = category.items.filter(item => {
        const haystack = [
          item.title,
          item.subtitle,
          item.message,
          ...(item.meta || []).map(meta => `${meta.label} ${meta.value}`),
        ].join(' ').toLowerCase()

        return haystack.includes(term)
      })

      if (!searchableHeader.includes(term) && !items.length) {
        return null
      }

      return { ...category, items }
    })
    .filter(Boolean)
})

function severityLabel(severity) {
  return {
    danger: 'Bloquant',
    warning: 'A surveiller',
    info: 'Information',
  }[severity] || 'Info'
}

function entityIcon(type) {
  return {
    agent: 'fas fa-user',
    user: 'fas fa-user-shield',
    localite: 'fas fa-map-pin',
    province: 'fas fa-map',
    affectation: 'fas fa-random',
  }[type] || 'fas fa-database'
}

async function loadDiagnostics() {
  loading.value = true
  try {
    const { data } = await client.get('/superadmin/diagnostics')
    summary.value = data.summary || {}
    categories.value = data.categories || []
  } catch (error) {
    console.error('Erreur diagnostic donnees:', error)
  } finally {
    loading.value = false
  }
}

onMounted(loadDiagnostics)
</script>

<style scoped>
.diagnostic-page {
  display: grid;
  gap: 1rem;
}

.diagnostic-hero {
  background: linear-gradient(135deg, #063047 0%, #0077b5 100%);
  border-radius: 16px;
  color: #fff;
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.35rem 1.5rem;
  box-shadow: 0 18px 38px rgba(0, 72, 112, .22);
}

.diagnostic-kicker {
  align-items: center;
  color: rgba(255, 255, 255, .76);
  display: inline-flex;
  font-size: .75rem;
  font-weight: 800;
  gap: .45rem;
  letter-spacing: .04em;
  text-transform: uppercase;
}

.diagnostic-hero h1 {
  font-size: clamp(1.35rem, 2vw, 2rem);
  font-weight: 900;
  margin: .35rem 0 .2rem;
}

.diagnostic-hero p {
  color: rgba(255, 255, 255, .78);
  margin: 0;
  max-width: 760px;
}

.diagnostic-refresh {
  align-self: center;
  background: rgba(255, 255, 255, .14);
  border: 1px solid rgba(255, 255, 255, .25);
  border-radius: 12px;
  color: #fff;
  display: inline-flex;
  font-weight: 800;
  gap: .5rem;
  padding: .65rem 1rem;
}

.diagnostic-loading,
.diagnostic-empty {
  align-items: center;
  background: #fff;
  border: 1px solid #dbe8f0;
  border-radius: 14px;
  color: #64748b;
  display: flex;
  gap: .75rem;
  justify-content: center;
  min-height: 180px;
}

.diagnostic-summary {
  display: grid;
  gap: .8rem;
  grid-template-columns: repeat(6, minmax(0, 1fr));
}

.summary-card {
  background: #fff;
  border: 1px solid #dbe8f0;
  border-radius: 14px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  padding: 1rem;
}

.summary-card span {
  color: #64748b;
  display: block;
  font-size: .76rem;
  font-weight: 800;
  text-transform: uppercase;
}

.summary-card strong {
  color: #0f172a;
  display: block;
  font-size: 1.75rem;
  font-weight: 950;
  line-height: 1;
  margin-top: .45rem;
}

.summary-card.danger {
  border-left: 4px solid #dc2626;
}

.summary-card.warning {
  border-left: 4px solid #f59e0b;
}

.summary-card.score.good {
  border-left: 4px solid #059669;
}

.summary-card.score.medium {
  border-left: 4px solid #f59e0b;
}

.summary-card.score.low {
  border-left: 4px solid #dc2626;
}

.diagnostic-toolbar {
  align-items: center;
  display: flex;
  gap: .85rem;
  justify-content: space-between;
}

.diagnostic-search {
  align-items: center;
  background: #fff;
  border: 1px solid #c9ddea;
  border-radius: 12px;
  display: flex;
  flex: 1;
  gap: .65rem;
  max-width: 620px;
  padding: .65rem .85rem;
}

.diagnostic-search i {
  color: #0077b5;
}

.diagnostic-search input {
  border: 0;
  flex: 1;
  min-width: 0;
  outline: 0;
}

.diagnostic-filters {
  display: flex;
  flex-wrap: wrap;
  gap: .45rem;
}

.diagnostic-filters button {
  background: #fff;
  border: 1px solid #c9ddea;
  border-radius: 999px;
  color: #476176;
  font-weight: 800;
  padding: .55rem .85rem;
}

.diagnostic-filters button.active {
  background: #0077b5;
  border-color: #0077b5;
  color: #fff;
}

.diagnostic-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.diagnostic-card {
  background: #fff;
  border: 1px solid #dbe8f0;
  border-radius: 16px;
  box-shadow: 0 14px 32px rgba(15, 23, 42, .07);
  overflow: hidden;
}

.diagnostic-card-header {
  border-top: 4px solid #0ea5e9;
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
}

.severity-danger .diagnostic-card-header {
  border-top-color: #dc2626;
}

.severity-warning .diagnostic-card-header {
  border-top-color: #f59e0b;
}

.diagnostic-card-header h2 {
  color: #0f172a;
  font-size: 1rem;
  font-weight: 900;
  margin: .35rem 0 .25rem;
}

.diagnostic-card-header p {
  color: #64748b;
  font-size: .85rem;
  margin: 0;
}

.diagnostic-card-header strong {
  color: #0f172a;
  font-size: 1.8rem;
  font-weight: 950;
  line-height: 1;
}

.severity-badge {
  background: #e0f2fe;
  border-radius: 999px;
  color: #0369a1;
  display: inline-flex;
  font-size: .7rem;
  font-weight: 900;
  padding: .25rem .55rem;
  text-transform: uppercase;
}

.severity-danger .severity-badge {
  background: #fee2e2;
  color: #b91c1c;
}

.severity-warning .severity-badge {
  background: #fef3c7;
  color: #b45309;
}

.diagnostic-recommendation {
  align-items: center;
  background: #f8fafc;
  border-top: 1px solid #edf2f7;
  color: #334155;
  display: flex;
  font-size: .86rem;
  font-weight: 700;
  gap: .55rem;
  padding: .75rem 1rem;
}

.diagnostic-recommendation i {
  color: #f59e0b;
}

.diagnostic-items {
  display: grid;
  gap: .65rem;
  max-height: 430px;
  overflow: auto;
  padding: .85rem;
}

.diagnostic-item {
  align-items: flex-start;
  border: 1px solid #e2edf5;
  border-radius: 12px;
  display: flex;
  gap: .75rem;
  justify-content: space-between;
  padding: .75rem;
}

.item-main {
  display: flex;
  gap: .7rem;
  min-width: 0;
}

.item-icon {
  align-items: center;
  background: #e0f2fe;
  border-radius: 10px;
  color: #0077b5;
  display: inline-flex;
  flex: 0 0 34px;
  height: 34px;
  justify-content: center;
  width: 34px;
}

.diagnostic-item h3 {
  color: #0f172a;
  font-size: .9rem;
  font-weight: 900;
  margin: 0;
}

.diagnostic-item p {
  color: #64748b;
  font-size: .78rem;
  margin: .1rem 0 .25rem;
}

.diagnostic-item span {
  color: #334155;
  display: block;
  font-size: .83rem;
}

.item-meta {
  display: flex;
  flex-wrap: wrap;
  gap: .35rem;
  margin-top: .45rem;
}

.item-meta small {
  background: #f1f5f9;
  border-radius: 999px;
  color: #475569;
  font-weight: 700;
  padding: .22rem .5rem;
}

.item-action {
  align-items: center;
  background: #eff8fc;
  border-radius: 999px;
  color: #00669b;
  display: inline-flex;
  flex: 0 0 auto;
  font-size: .78rem;
  font-weight: 900;
  gap: .35rem;
  padding: .45rem .7rem;
  text-decoration: none;
}

.diagnostic-ok,
.diagnostic-overflow {
  color: #64748b;
  font-weight: 800;
  padding: 1rem;
  text-align: center;
}

.diagnostic-ok i {
  color: #059669;
  margin-right: .35rem;
}

@media (max-width: 1200px) {
  .diagnostic-summary {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .diagnostic-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .diagnostic-hero,
  .diagnostic-toolbar,
  .diagnostic-item {
    flex-direction: column;
  }

  .diagnostic-summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .diagnostic-search {
    max-width: none;
    width: 100%;
  }

  .item-action {
    justify-content: center;
    width: 100%;
  }
}
</style>
