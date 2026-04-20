<template>
  <div class="container py-4">

    <!-- Hero -->
    <div class="fl-hero">
      <div class="fl-hero-body">
        <div class="fl-hero-text">
          <h2><i class="fas fa-graduation-cap me-2"></i>Formations — Renforcement des Capacités</h2>
          <p>Planification, suivi et gestion des formations du PNMLS.</p>
          <div class="fl-hero-stats">
            <div><div class="fl-hero-stat-val">{{ meta.total }}</div><div class="fl-hero-stat-lbl">Total</div></div>
            <div><div class="fl-hero-stat-val text-warning">{{ counts.en_cours }}</div><div class="fl-hero-stat-lbl">En cours</div></div>
            <div><div class="fl-hero-stat-val text-success">{{ counts.terminee }}</div><div class="fl-hero-stat-lbl">Terminées</div></div>
          </div>
        </div>
        <div class="fl-hero-actions">
          <router-link :to="{ name: 'renforcements.create' }" class="fl-btn-primary">
            <i class="fas fa-plus-circle me-1"></i> Planifier une formation
          </router-link>
        </div>
      </div>
    </div>

    <!-- Filtres statut -->
    <div class="fl-filter-grid">
      <button class="fl-filter-card fl-fc-all" :class="{ active: !filters.statut }" @click="setStatut('')">
        <div class="fl-fc-icon"><i class="fas fa-th-large"></i></div>
        <div><div class="fl-fc-name">Toutes</div><div class="fl-fc-count">{{ meta.total }}</div></div>
      </button>
      <button class="fl-filter-card fl-fc-blue" :class="{ active: filters.statut === 'planifiee' }" @click="setStatut('planifiee')">
        <div class="fl-fc-icon"><i class="fas fa-calendar-alt"></i></div>
        <div><div class="fl-fc-name">Planifiées</div><div class="fl-fc-count">{{ counts.planifiee }}</div></div>
      </button>
      <button class="fl-filter-card fl-fc-orange" :class="{ active: filters.statut === 'en_cours' }" @click="setStatut('en_cours')">
        <div class="fl-fc-icon"><i class="fas fa-spinner"></i></div>
        <div><div class="fl-fc-name">En cours</div><div class="fl-fc-count">{{ counts.en_cours }}</div></div>
      </button>
      <button class="fl-filter-card fl-fc-green" :class="{ active: filters.statut === 'terminee' }" @click="setStatut('terminee')">
        <div class="fl-fc-icon"><i class="fas fa-check-circle"></i></div>
        <div><div class="fl-fc-name">Terminées</div><div class="fl-fc-count">{{ counts.terminee }}</div></div>
      </button>
      <button class="fl-filter-card fl-fc-gray" :class="{ active: filters.statut === 'annulee' }" @click="setStatut('annulee')">
        <div class="fl-fc-icon"><i class="fas fa-ban"></i></div>
        <div><div class="fl-fc-name">Annulées</div><div class="fl-fc-count">{{ counts.annulee }}</div></div>
      </button>
    </div>

    <!-- Recherche -->
    <div class="fl-search-bar">
      <div class="fl-search-input-wrap">
        <i class="fas fa-search fl-search-icon"></i>
        <input v-model="filters.search" type="text" placeholder="Rechercher une formation..." class="fl-search-input" @input="onSearch">
        <button v-if="filters.search" class="fl-search-clear" @click="clearSearch"><i class="fas fa-times"></i></button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-success" role="status"><span class="visually-hidden">Chargement...</span></div>
      <p class="text-muted mt-2">Chargement des formations...</p>
    </div>

    <template v-else>
      <!-- Vide -->
      <div v-if="!formations.length" class="fl-empty">
        <div class="fl-empty-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <h5>Aucune formation trouvée</h5>
        <p class="text-muted">{{ filters.statut || filters.search ? 'Modifiez vos filtres.' : 'Planifiez la première formation.' }}</p>
        <router-link v-if="!filters.statut && !filters.search" :to="{ name: 'renforcements.create' }" class="fl-btn-primary">
          <i class="fas fa-plus-circle me-1"></i> Planifier une formation
        </router-link>
      </div>

      <!-- Liste -->
      <div v-else class="fl-list">
        <router-link v-for="f in formations" :key="f.id" :to="{ name: 'renforcements.show', params: { id: f.id } }" class="fl-card">
          <div class="fl-card-left">
            <div class="fl-card-statut-bar" :class="'sbar-' + f.statut"></div>
            <div class="fl-card-icon" :class="'ficon-' + f.statut">
              <i class="fas fa-chalkboard-teacher"></i>
            </div>
          </div>
          <div class="fl-card-body">
            <div class="fl-card-titre">{{ f.titre }}</div>
            <div class="fl-card-meta">
              <span class="fl-badge" :class="'badge-' + f.statut">{{ statutLabel(f.statut) }}</span>
              <span v-if="f.date_debut"><i class="fas fa-calendar me-1"></i>{{ formatDate(f.date_debut) }}</span>
              <span v-if="f.date_fin"><i class="fas fa-calendar-check me-1"></i>{{ formatDate(f.date_fin) }}</span>
              <span v-if="f.lieu"><i class="fas fa-map-marker-alt me-1"></i>{{ f.lieu }}</span>
              <span v-if="f.formateur"><i class="fas fa-user-tie me-1"></i>{{ f.formateur }}</span>
            </div>
            <div class="fl-card-footer">
              <span v-if="f.budget" class="fl-budget"><i class="fas fa-dollar-sign me-1"></i>{{ formatBudget(f.budget) }}</span>
              <span class="fl-benef"><i class="fas fa-users me-1"></i>{{ f.beneficiaires?.length ?? 0 }} bénéficiaire(s)</span>
              <span v-if="f.createur" class="fl-createur"><i class="fas fa-user me-1"></i>{{ f.createur.prenom }} {{ f.createur.nom }}</span>
            </div>
          </div>
          <div class="fl-card-right">
            <i class="fas fa-chevron-right fl-card-arrow"></i>
          </div>
        </router-link>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="fl-pagination">
        <button :disabled="meta.current_page <= 1" @click="loadFormations(meta.current_page - 1)" class="fl-pag-btn">
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="fl-pag-info">Page {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button :disabled="meta.current_page >= meta.last_page" @click="loadFormations(meta.current_page + 1)" class="fl-pag-btn">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import client from '@/api/client'

const route  = useRoute()
const router = useRouter()
const loading = ref(true)
const formations = ref([])
const meta = ref({ total: 0, current_page: 1, last_page: 1 })
const counts = ref({ planifiee: 0, en_cours: 0, terminee: 0, annulee: 0 })
const filters = ref({ statut: route.query.statut || '', search: '' })

let searchTimeout = null

function setStatut(s) {
  filters.value.statut = s
  loadFormations(1)
}

function onSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadFormations(1), 350)
}

function clearSearch() {
  filters.value.search = ''
  loadFormations(1)
}

async function loadFormations(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filters.value.statut) params.statut = filters.value.statut
    if (filters.value.search) params.search = filters.value.search
    const { data } = await client.get('/renforcements', { params })
    formations.value = data.data
    meta.value = { total: data.total, current_page: data.current_page, last_page: data.last_page }
  } finally {
    loading.value = false
  }
}

async function loadCounts() {
  const statuts = ['planifiee', 'en_cours', 'terminee', 'annulee']
  const results = await Promise.allSettled(
    statuts.map(s => client.get('/renforcements', { params: { statut: s, per_page: 1 } }))
  )
  statuts.forEach((s, i) => {
    if (results[i].status === 'fulfilled') {
      counts.value[s] = results[i].value.data.total ?? 0
    }
  })
}

function formatDate(v) {
  if (!v) return ''
  return new Date(v).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatBudget(v) {
  if (!v) return ''
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v)
}

function statutLabel(s) {
  return { planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' }[s] ?? s
}

onMounted(() => {
  loadFormations()
  loadCounts()
})

watch(() => route.query.statut, (v) => {
  filters.value.statut = v || ''
  loadFormations(1)
})
</script>

<style scoped>
/* Hero */
.fl-hero { border-radius: 16px; overflow: hidden; margin-bottom: 1.5rem;
  background: linear-gradient(135deg, #052e16 0%, #065f46 40%, #059669 100%);
  color: #fff; box-shadow: 0 6px 24px rgba(5,46,22,.25); }
.fl-hero-body { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; padding: 1.8rem 2rem; flex-wrap: wrap; }
.fl-hero-text h2 { font-size: 1.4rem; font-weight: 800; margin-bottom: .3rem; }
.fl-hero-text p { opacity: .7; margin-bottom: .8rem; font-size: .85rem; }
.fl-hero-stats { display: flex; gap: 1.5rem; }
.fl-hero-stat-val { font-size: 1.4rem; font-weight: 800; }
.fl-hero-stat-lbl { font-size: .65rem; opacity: .5; text-transform: uppercase; letter-spacing: .3px; font-weight: 600; }
.fl-btn-primary { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.2rem;
  background: #fff; color: #065f46; border-radius: 10px; font-weight: 700; font-size: .85rem;
  text-decoration: none; transition: all .2s; border: none; cursor: pointer; }
.fl-btn-primary:hover { background: #d1fae5; color: #052e16; }

/* Filtres */
.fl-filter-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: .65rem; margin-bottom: 1rem; }
.fl-filter-card { display: flex; align-items: center; gap: .7rem; padding: .75rem 1rem;
  border-radius: 12px; border: 1.5px solid #e5e7eb; background: #fff;
  cursor: pointer; transition: all .2s; font-size: .8rem; font-weight: 600; text-align: left; }
.fl-filter-card.active { border-width: 2px; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.fl-fc-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
.fl-fc-name { font-size: .78rem; font-weight: 700; }
.fl-fc-count { font-size: .72rem; color: #64748b; }
.fl-fc-all .fl-fc-icon  { background: #f1f5f9; color: #64748b; }
.fl-fc-blue .fl-fc-icon { background: #e0f2fe; color: #0ea5e9; }
.fl-fc-orange .fl-fc-icon { background: #fef3c7; color: #d97706; }
.fl-fc-green .fl-fc-icon  { background: #d1fae5; color: #059669; }
.fl-fc-gray .fl-fc-icon   { background: #f1f5f9; color: #94a3b8; }
.fl-fc-all.active  { border-color: #94a3b8; }
.fl-fc-blue.active { border-color: #0ea5e9; }
.fl-fc-orange.active { border-color: #d97706; }
.fl-fc-green.active  { border-color: #059669; }
.fl-fc-gray.active   { border-color: #94a3b8; }

/* Recherche */
.fl-search-bar { margin-bottom: 1.2rem; }
.fl-search-input-wrap { position: relative; }
.fl-search-icon { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .85rem; }
.fl-search-input { width: 100%; padding: .65rem .9rem .65rem 2.4rem; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: .85rem; transition: border-color .2s; }
.fl-search-input:focus { outline: none; border-color: #059669; }
.fl-search-clear { position: absolute; right: .8rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0 .3rem; }

/* Cards liste */
.fl-list { display: flex; flex-direction: column; gap: .5rem; }
.fl-card { display: flex; align-items: stretch; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px; text-decoration: none; color: #374151; overflow: hidden; transition: all .2s; }
.fl-card:hover { border-color: #a7f3d0; box-shadow: 0 6px 20px rgba(5,150,105,.1); transform: translateY(-1px); }
.fl-card-left { display: flex; align-items: center; gap: 0; }
.fl-card-statut-bar { width: 4px; height: 100%; flex-shrink: 0; }
.sbar-planifiee { background: #0ea5e9; }
.sbar-en_cours  { background: #d97706; }
.sbar-terminee  { background: #059669; }
.sbar-annulee   { background: #94a3b8; }
.fl-card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; margin: .9rem .8rem; flex-shrink: 0; }
.ficon-planifiee { background: #e0f2fe; color: #0ea5e9; }
.ficon-en_cours  { background: #fef3c7; color: #d97706; }
.ficon-terminee  { background: #d1fae5; color: #059669; }
.ficon-annulee   { background: #f1f5f9; color: #94a3b8; }
.fl-card-body { flex: 1; padding: .9rem .5rem .9rem 0; min-width: 0; }
.fl-card-titre { font-size: .95rem; font-weight: 800; color: #1e293b; margin-bottom: .3rem; }
.fl-card-meta { display: flex; flex-wrap: wrap; gap: .6rem; font-size: .72rem; color: #64748b; margin-bottom: .4rem; }
.fl-card-footer { display: flex; flex-wrap: wrap; gap: .7rem; font-size: .7rem; color: #94a3b8; }
.fl-budget { color: #059669; font-weight: 700; }
.fl-benef { color: #7c3aed; font-weight: 600; }
.fl-card-right { display: flex; align-items: center; padding: 0 1rem; }
.fl-card-arrow { font-size: .7rem; color: #cbd5e1; }
.fl-card:hover .fl-card-arrow { color: #059669; }

/* Badges */
.fl-badge { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 7px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
.badge-planifiee { background: #e0f2fe; color: #0369a1; }
.badge-en_cours  { background: #fef3c7; color: #92400e; }
.badge-terminee  { background: #d1fae5; color: #065f46; }
.badge-annulee   { background: #f1f5f9; color: #64748b; }

/* Empty */
.fl-empty { text-align: center; padding: 4rem 2rem; }
.fl-empty-icon { font-size: 3rem; color: #d1fae5; margin-bottom: 1rem; }
.fl-empty-icon i { color: #059669; }

/* Pagination */
.fl-pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
.fl-pag-btn { width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid #e5e7eb; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; }
.fl-pag-btn:hover:not(:disabled) { border-color: #059669; color: #059669; }
.fl-pag-btn:disabled { opacity: .4; cursor: not-allowed; }
.fl-pag-info { font-size: .82rem; color: #64748b; font-weight: 600; }

@media (max-width: 768px) {
  .fl-filter-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 480px) {
  .fl-filter-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
