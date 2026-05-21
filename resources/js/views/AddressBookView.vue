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
          <div v-for="agent in group.agents" :key="agent.id" class="ab-row">
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
              <a v-if="agent.telephone_professionnel" :href="`tel:${agent.telephone_professionnel}`">
                <i class="fas fa-phone"></i><b>Pro</b>{{ agent.telephone_professionnel }}
              </a>
              <span v-else><i class="fas fa-phone-slash"></i><b>Pro</b>N/A</span>
              <a v-if="agent.telephone_prive" :href="`tel:${agent.telephone_prive}`">
                <i class="fas fa-mobile-alt"></i><b>Privé</b>{{ agent.telephone_prive }}
              </a>
              <span v-else><i class="fas fa-mobile-alt"></i><b>Privé</b>N/A</span>
              <a v-if="agent.email_professionnel" :href="`mailto:${agent.email_professionnel}`">
                <i class="fas fa-envelope"></i><b>Pro</b>{{ agent.email_professionnel }}
              </a>
              <span v-else><i class="fas fa-envelope"></i><b>Pro</b>N/A</span>
              <a v-if="agent.email_prive" :href="`mailto:${agent.email_prive}`">
                <i class="fas fa-at"></i><b>Privé</b>{{ agent.email_prive }}
              </a>
              <span v-else><i class="fas fa-at"></i><b>Privé</b>N/A</span>
            </div>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import client from '@/api/client'

const loading = ref(true)
const error = ref('')
const search = ref('')
const payload = ref({ groups: [], total: 0 })
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
}
.ab-row:hover { background: rgba(240, 249, 255, .52); }
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
@media (max-width: 720px) {
  .ab-head { align-items: flex-start; flex-direction: column; }
  .ab-toolbar { align-items: stretch; }
  .ab-row { grid-template-columns: 38px minmax(0, 1fr); align-items: flex-start; }
  .ab-contact { grid-column: 2; }
}
</style>
