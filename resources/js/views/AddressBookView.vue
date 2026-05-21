<template>
  <div class="ab-page">
    <section class="ab-head">
      <div>
        <p class="ab-kicker">Carnet d'adresse</p>
        <h1>Contacts des agents</h1>
        <p>{{ total }} agent(s), regroupés par poste selon l'ordre institutionnel.</p>
      </div>
      <router-link to="/dashboard" class="ab-back">
        <i class="fas fa-arrow-left"></i>
        Tableau de bord
      </router-link>
    </section>

    <div class="ab-toolbar">
      <div class="ab-search">
        <i class="fas fa-search"></i>
        <input v-model.trim="search" type="search" placeholder="Rechercher un agent, poste ou contact">
      </div>
      <button type="button" class="ab-refresh" @click="load" :disabled="loading">
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
      </button>
    </div>

    <div v-if="loading" class="ab-state">
      <i class="fas fa-spinner fa-spin"></i>
      Chargement du carnet...
    </div>
    <div v-else-if="error" class="ab-state ab-error">
      <i class="fas fa-exclamation-circle"></i>
      {{ error }}
    </div>
    <div v-else-if="!groups.length" class="ab-state">
      <i class="fas fa-address-book"></i>
      Aucun contact trouvé.
    </div>

    <section v-else class="ab-groups">
      <article v-for="group in groups" :key="group.poste" class="ab-group">
        <header class="ab-group-head">
          <div>
            <h2>{{ group.poste }}</h2>
            <span>{{ group.count }} agent(s)</span>
          </div>
        </header>

        <div class="ab-list">
          <button v-for="agent in group.agents" :key="agent.id" type="button" class="ab-row" @click="openAgent(agent)">
            <div class="ab-avatar">
              <img
                v-if="agent.photo && !agent._photoError"
                :src="photoUrl(agent.photo)"
                :alt="agent.nom_complet"
                @error="agent._photoError = true"
              >
              <span v-else>{{ initials(agent) }}</span>
            </div>
            <div class="ab-main">
              <strong>{{ agent.nom_complet }}</strong>
              <span v-if="agent.structure">{{ agent.structure }}</span>
            </div>
            <div class="ab-contact">
              <a v-if="agent.telephone_professionnel" :href="`tel:${agent.telephone_professionnel}`" @click.stop>
                <i class="fas fa-phone"></i><b>Pro</b>{{ agent.telephone_professionnel }}
              </a>
              <span v-else><i class="fas fa-phone-slash"></i><b>Pro</b>N/A</span>
              <a v-if="agent.telephone_prive" :href="`tel:${agent.telephone_prive}`" @click.stop>
                <i class="fas fa-mobile-alt"></i><b>Privé</b>{{ agent.telephone_prive }}
              </a>
              <span v-else><i class="fas fa-mobile-alt"></i><b>Privé</b>N/A</span>
              <a v-if="agent.email_professionnel" :href="`mailto:${agent.email_professionnel}`" @click.stop>
                <i class="fas fa-envelope"></i><b>Pro</b>{{ agent.email_professionnel }}
              </a>
              <span v-else><i class="fas fa-envelope"></i><b>Pro</b>N/A</span>
              <a v-if="agent.email_prive" :href="`mailto:${agent.email_prive}`" @click.stop>
                <i class="fas fa-at"></i><b>Privé</b>{{ agent.email_prive }}
              </a>
              <span v-else><i class="fas fa-at"></i><b>Privé</b>N/A</span>
            </div>
          </button>
        </div>
      </article>
    </section>

    <teleport to="body">
      <div v-if="selectedAgent" class="ab-modal-overlay" @click.self="closeAgent">
        <section class="ab-modal" role="dialog" aria-modal="true" aria-label="Fiche contact agent">
          <button type="button" class="ab-modal-close" title="Fermer" @click="closeAgent">
            <i class="fas fa-times"></i>
          </button>

          <div class="ab-modal-hero">
            <div class="ab-modal-avatar">
              <img
                v-if="selectedAgent.photo && !selectedAgent._modalPhotoError"
                :src="photoUrl(selectedAgent.photo)"
                :alt="selectedAgent.nom_complet"
                @error="selectedAgent._modalPhotoError = true"
              >
              <span v-else>{{ initials(selectedAgent) }}</span>
            </div>
            <div>
              <p class="ab-modal-kicker">Contact PNMLS</p>
              <h2>{{ selectedAgent.nom_complet }}</h2>
              <span>{{ selectedAgent.poste }}</span>
            </div>
          </div>

          <div class="ab-modal-meta">
            <span v-if="selectedAgent.structure"><i class="fas fa-building"></i>{{ selectedAgent.structure }}</span>
            <span><i class="fas fa-id-badge"></i>{{ selectedAgent.poste || 'Poste non renseigné' }}</span>
          </div>

          <div class="ab-modal-grid">
            <article class="ab-modal-info">
              <i class="fas fa-phone"></i>
              <div>
                <small>Téléphone pro</small>
                <a v-if="selectedAgent.telephone_professionnel" :href="`tel:${selectedAgent.telephone_professionnel}`">{{ selectedAgent.telephone_professionnel }}</a>
                <strong v-else>N/A</strong>
              </div>
            </article>
            <article class="ab-modal-info">
              <i class="fas fa-mobile-alt"></i>
              <div>
                <small>Téléphone privé</small>
                <a v-if="selectedAgent.telephone_prive" :href="`tel:${selectedAgent.telephone_prive}`">{{ selectedAgent.telephone_prive }}</a>
                <strong v-else>N/A</strong>
              </div>
            </article>
            <article class="ab-modal-info">
              <i class="fas fa-envelope"></i>
              <div>
                <small>Email pro</small>
                <a v-if="selectedAgent.email_professionnel" :href="`mailto:${selectedAgent.email_professionnel}`">{{ selectedAgent.email_professionnel }}</a>
                <strong v-else>N/A</strong>
              </div>
            </article>
            <article class="ab-modal-info">
              <i class="fas fa-at"></i>
              <div>
                <small>Email privé</small>
                <a v-if="selectedAgent.email_prive" :href="`mailto:${selectedAgent.email_prive}`">{{ selectedAgent.email_prive }}</a>
                <strong v-else>N/A</strong>
              </div>
            </article>
          </div>

          <div class="ab-modal-actions">
            <a v-if="selectedAgent.telephone_professionnel || selectedAgent.telephone_prive" :href="`tel:${selectedAgent.telephone_professionnel || selectedAgent.telephone_prive}`">
              <i class="fas fa-phone"></i>Appeler
            </a>
            <a v-if="selectedAgent.email_professionnel || selectedAgent.email_prive" :href="`mailto:${selectedAgent.email_professionnel || selectedAgent.email_prive}`">
              <i class="fas fa-paper-plane"></i>Écrire
            </a>
          </div>
        </section>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import client from '@/api/client'

const loading = ref(true)
const error = ref('')
const search = ref('')
const payload = ref({ groups: [], total: 0 })
const selectedAgent = ref(null)
let searchTimer = null

const groups = computed(() => payload.value.groups || [])
const total = computed(() => payload.value.total || 0)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await client.get('/address-book', {
      params: search.value ? { search: search.value } : {},
    })
    payload.value = data.data || { groups: [], total: 0 }
  } catch (e) {
    error.value = e.response?.data?.message || 'Impossible de charger le carnet d’adresse.'
  } finally {
    loading.value = false
  }
}

function initials(agent) {
  return `${agent.prenom?.[0] || ''}${agent.nom?.[0] || ''}`.toUpperCase() || 'AG'
}

function photoUrl(photo) {
  if (!photo) return null
  const value = String(photo).trim()
  if (/^https?:\/\//i.test(value)) return value
  const normalized = value.replace(/^\/+/, '')
  if (!normalized.includes('/') && !normalized.startsWith('uploads/')) {
    return `/uploads/profiles/${normalized}`
  }
  return `/${normalized}`
}

function openAgent(agent) {
  selectedAgent.value = agent
}

function closeAgent() {
  selectedAgent.value = null
}

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(load, 300)
})

onMounted(load)
</script>

<style scoped>
.ab-page {
  --ab-glass: rgba(255, 255, 255, .68);
  --ab-glass-strong: rgba(255, 255, 255, .82);
  --ab-glass-border: rgba(125, 211, 252, .34);
  --ab-glass-shadow: 0 18px 48px rgba(15, 35, 58, .13), inset 0 1px 0 rgba(255, 255, 255, .72);
  max-width: 1120px; margin: 0 auto; padding: 0 1rem 2rem; position: relative;
}
.ab-page::before {
  content: ''; position: fixed; inset: 0; z-index: -1; pointer-events: none;
  background:
    radial-gradient(circle at 12% 8%, rgba(14, 165, 233, .14), transparent 30%),
    radial-gradient(circle at 90% 18%, rgba(15, 118, 110, .12), transparent 28%),
    linear-gradient(135deg, rgba(240, 249, 255, .72), rgba(236, 253, 245, .58));
}
.ab-head {
  display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem;
  padding: 1.1rem 1.15rem; border: 1px solid var(--ab-glass-border); margin: 1rem 0;
  border-radius: 8px; background: var(--ab-glass); box-shadow: var(--ab-glass-shadow);
  backdrop-filter: blur(18px) saturate(155%); -webkit-backdrop-filter: blur(18px) saturate(155%);
}
.ab-kicker { margin: 0 0 .25rem; color: #0ea5e9; font-size: .78rem; font-weight: 800; text-transform: uppercase; }
.ab-head h1 { margin: 0; color: #0f172a; font-size: 1.6rem; font-weight: 900; }
.ab-head p { margin: .25rem 0 0; color: #64748b; font-size: .88rem; }
.ab-back, .ab-refresh {
  border: 1px solid rgba(255,255,255,.72); background: rgba(255,255,255,.52); color: #0f172a; border-radius: 8px;
  padding: .55rem .75rem; text-decoration: none; display: inline-flex; align-items: center; gap: .45rem; font-weight: 700;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.75), 0 8px 18px rgba(15,35,58,.08);
  backdrop-filter: blur(12px) saturate(145%); -webkit-backdrop-filter: blur(12px) saturate(145%);
}
.ab-toolbar { display: flex; gap: .7rem; margin-bottom: 1rem; }
.ab-search {
  flex: 1; display: flex; align-items: center; gap: .6rem; background: var(--ab-glass-strong);
  border: 1px solid var(--ab-glass-border); border-radius: 8px; padding: .65rem .8rem;
  box-shadow: var(--ab-glass-shadow); backdrop-filter: blur(16px) saturate(150%);
  -webkit-backdrop-filter: blur(16px) saturate(150%);
}
.ab-search input { flex: 1; border: 0; outline: 0; min-width: 0; font-size: .92rem; background: transparent; }
.ab-state {
  min-height: 180px; display: flex; align-items: center; justify-content: center; gap: .6rem;
  color: #64748b; background: var(--ab-glass); border: 1px solid var(--ab-glass-border); border-radius: 8px; font-weight: 700;
  box-shadow: var(--ab-glass-shadow); backdrop-filter: blur(18px) saturate(155%);
  -webkit-backdrop-filter: blur(18px) saturate(155%);
}
.ab-error { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
.ab-groups { display: grid; gap: 1rem; }
.ab-group {
  background: var(--ab-glass); border: 1px solid var(--ab-glass-border); border-radius: 8px; overflow: hidden;
  box-shadow: var(--ab-glass-shadow); backdrop-filter: blur(18px) saturate(155%);
  -webkit-backdrop-filter: blur(18px) saturate(155%);
}
.ab-group-head {
  padding: .85rem 1rem; background: rgba(255, 255, 255, .42); border-bottom: 1px solid rgba(226, 232, 240, .72);
}
.ab-group-head h2 { margin: 0; font-size: 1rem; color: #0f172a; font-weight: 900; }
.ab-group-head span { color: #64748b; font-size: .76rem; font-weight: 700; }
.ab-list { display: grid; }
.ab-row {
  display: grid; grid-template-columns: 42px minmax(0, 1fr) minmax(280px, auto);
  align-items: center; gap: .75rem; padding: .8rem 1rem; border-bottom: 1px solid rgba(241, 245, 249, .78);
  background: linear-gradient(90deg, rgba(255,255,255,.28), rgba(255,255,255,.08));
  width: 100%; border-left: 0; border-right: 0; border-top: 0; text-align: left; cursor: pointer;
  font: inherit; color: inherit;
}
.ab-row:hover { background: rgba(240, 249, 255, .52); transform: translateY(-1px); }
.ab-row:focus-visible { outline: 3px solid rgba(14,165,233,.26); outline-offset: -3px; }
.ab-row:last-child { border-bottom: 0; }
.ab-avatar {
  width: 42px; height: 42px; border-radius: 50%; background: #dbeafe; color: #1d4ed8;
  display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: .8rem; overflow: hidden;
}
.ab-avatar img { width: 100%; height: 100%; object-fit: cover; }
.ab-main { min-width: 0; }
.ab-main strong { display: block; color: #0f172a; font-size: .9rem; }
.ab-main span { display: block; color: #64748b; font-size: .74rem; margin-top: .1rem; }
.ab-contact { display: grid; gap: .25rem; font-size: .78rem; }
.ab-contact a, .ab-contact span {
  color: #334155; text-decoration: none; display: grid; grid-template-columns: 14px 42px minmax(0, 1fr);
  align-items: center; gap: .4rem; min-width: 0;
}
.ab-contact b { color: #64748b; font-size: .68rem; text-transform: uppercase; }
.ab-contact a:hover { color: #0ea5e9; }
.ab-modal-overlay {
  position: fixed; inset: 0; z-index: 10000; display: flex; align-items: center; justify-content: center;
  padding: 1rem; background: rgba(15, 23, 42, .42); backdrop-filter: blur(18px) saturate(155%);
  -webkit-backdrop-filter: blur(18px) saturate(155%);
}
.ab-modal {
  width: min(620px, 100%); max-height: 90vh; overflow: auto; position: relative;
  border: 1px solid rgba(255,255,255,.58); border-radius: 8px; padding: 1.15rem;
  background:
    linear-gradient(135deg, rgba(255,255,255,.76), rgba(255,255,255,.46)),
    radial-gradient(circle at 12% 0%, rgba(14,165,233,.18), transparent 38%);
  box-shadow: 0 34px 90px rgba(15, 23, 42, .28), inset 0 1px 0 rgba(255,255,255,.80);
  backdrop-filter: blur(26px) saturate(170%); -webkit-backdrop-filter: blur(26px) saturate(170%);
}
.ab-modal-close {
  position: absolute; top: .75rem; right: .75rem; width: 38px; height: 38px; border: 1px solid rgba(255,255,255,.62);
  border-radius: 8px; background: rgba(255,255,255,.56); color: #64748b; display: inline-flex; align-items: center; justify-content: center;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.78); backdrop-filter: blur(12px) saturate(145%);
}
.ab-modal-close:hover { color: #dc2626; background: rgba(254,226,226,.72); }
.ab-modal-hero {
  display: grid; grid-template-columns: 86px minmax(0, 1fr); gap: 1rem; align-items: center; padding-right: 2.4rem;
}
.ab-modal-avatar {
  width: 86px; height: 86px; border-radius: 50%; overflow: hidden; background: #dbeafe; color: #1d4ed8;
  display: grid; place-items: center; font-size: 1.25rem; font-weight: 900; border: 4px solid rgba(255,255,255,.76);
  box-shadow: 0 18px 36px rgba(15,35,58,.18), inset 0 1px 0 rgba(255,255,255,.72);
}
.ab-modal-avatar img { width: 100%; height: 100%; object-fit: cover; }
.ab-modal-kicker { margin: 0 0 .2rem; color: #0ea5e9; font-size: .72rem; font-weight: 900; text-transform: uppercase; }
.ab-modal h2 { margin: 0; color: #0f172a; font-size: 1.35rem; font-weight: 900; overflow-wrap: anywhere; }
.ab-modal-hero span { display: block; margin-top: .25rem; color: #0f766e; font-size: .9rem; font-weight: 800; overflow-wrap: anywhere; }
.ab-modal-meta { display: flex; flex-wrap: wrap; gap: .5rem; margin: 1rem 0; }
.ab-modal-meta span {
  display: inline-flex; align-items: center; gap: .4rem; padding: .42rem .65rem; border-radius: 8px;
  color: #334155; background: rgba(255,255,255,.54); border: 1px solid rgba(255,255,255,.62);
  font-size: .78rem; font-weight: 800; box-shadow: inset 0 1px 0 rgba(255,255,255,.72);
}
.ab-modal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
.ab-modal-info {
  display: grid; grid-template-columns: 36px minmax(0, 1fr); gap: .65rem; align-items: flex-start;
  padding: .8rem; border-radius: 8px; background: rgba(255,255,255,.56); border: 1px solid rgba(255,255,255,.64);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.72); backdrop-filter: blur(12px) saturate(145%);
  -webkit-backdrop-filter: blur(12px) saturate(145%);
}
.ab-modal-info > i {
  width: 36px; height: 36px; border-radius: 8px; display: grid; place-items: center;
  color: #0369a1; background: rgba(224,242,254,.78);
}
.ab-modal-info small { display: block; color: #64748b; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
.ab-modal-info a, .ab-modal-info strong { color: #0f172a; font-size: .9rem; font-weight: 850; text-decoration: none; overflow-wrap: anywhere; }
.ab-modal-info a:hover { color: #0ea5e9; }
.ab-modal-actions { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1rem; }
.ab-modal-actions a {
  display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 42px; padding: 0 .95rem;
  border-radius: 8px; color: #fff; text-decoration: none; font-weight: 900;
  background: linear-gradient(135deg, rgba(0,119,181,.96), rgba(15,118,110,.92));
  box-shadow: 0 12px 26px rgba(0,119,181,.22), inset 0 1px 0 rgba(255,255,255,.28);
}
@media (max-width: 720px) {
  .ab-head { align-items: flex-start; flex-direction: column; }
  .ab-toolbar { align-items: stretch; }
  .ab-row { grid-template-columns: 38px minmax(0, 1fr); align-items: flex-start; }
  .ab-contact { grid-column: 2; }
  .ab-modal { padding: 1rem; }
  .ab-modal-hero { grid-template-columns: 72px minmax(0, 1fr); }
  .ab-modal-avatar { width: 72px; height: 72px; }
  .ab-modal-grid { grid-template-columns: 1fr; }
  .ab-modal-actions a { width: 100%; }
}
</style>
