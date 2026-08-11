<template>
  <teleport to="body">
    <div class="command-center no-print">
      <button
        class="command-center__trigger"
        :class="{ 'is-open': isOpen }"
        type="button"
        aria-label="Actions rapides"
        :aria-expanded="isOpen ? 'true' : 'false'"
        @click="toggle"
      >
        <i class="fas fa-bolt"></i>
      </button>

      <transition name="command-center-backdrop">
        <button
          v-if="isOpen"
          class="command-center__backdrop"
          type="button"
          aria-label="Fermer les actions rapides"
          @click="close"
        ></button>
      </transition>

      <transition name="command-center-panel">
        <section v-if="isOpen" class="command-center__panel" role="dialog" aria-modal="false" aria-label="Actions rapides">
          <header class="command-center__header">
            <div>
              <span class="command-center__eyebrow">E-PNMLS</span>
              <h2>Actions</h2>
            </div>
            <button class="command-center__icon-btn" type="button" aria-label="Fermer" @click="close">
              <i class="fas fa-times"></i>
            </button>
          </header>

          <label class="command-center__search">
            <i class="fas fa-search"></i>
            <input
              ref="searchInput"
              v-model="query"
              type="search"
              autocomplete="off"
              placeholder="Rechercher une action"
            >
          </label>

          <div class="command-center__pinned">
            <button
              v-for="action in pinnedActions"
              :key="action.key"
              class="command-center__pin"
              type="button"
              :style="actionStyle(action)"
              @click="runAction(action)"
            >
              <span><i :class="['fas', action.icon]"></i></span>
              <strong>{{ action.label }}</strong>
            </button>
          </div>

          <div class="command-center__list" role="list">
            <button
              v-for="action in filteredActions"
              :key="action.key"
              class="command-center__row"
              :class="{ 'is-current': isCurrentRoute(action) }"
              type="button"
              role="listitem"
              @click="runAction(action)"
            >
              <span class="command-center__row-icon" :style="actionStyle(action)">
                <i :class="['fas', action.icon]"></i>
              </span>
              <span class="command-center__row-copy">
                <strong>{{ action.label }}</strong>
                <small>{{ action.description }}</small>
              </span>
              <i class="fas fa-chevron-right command-center__row-arrow"></i>
            </button>

            <div v-if="filteredActions.length === 0" class="command-center__empty">
              <i class="fas fa-search"></i>
              <span>Aucun résultat</span>
            </div>
          </div>
        </section>
      </transition>
    </div>
  </teleport>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'

const auth = useAuthStore()
const ui = useUiStore()
const router = useRouter()
const route = useRoute()

const isOpen = ref(false)
const query = ref('')
const searchInput = ref(null)

const pinnedKeys = ['dashboard', 'requests.create', 'mailbox', 'support']

const actions = computed(() => {
  const items = [
    {
      key: 'dashboard',
      label: 'Accueil',
      description: 'Tableau de bord principal',
      icon: 'fa-th-large',
      color: '#0077B5',
      to: { name: 'dashboard' },
      keywords: ['home', 'dashboard', 'tableau'],
    },
    {
      key: 'requests.create',
      label: 'Nouvelle demande',
      description: 'Créer une demande administrative',
      icon: 'fa-paper-plane',
      color: '#0f766e',
      to: { name: 'requests.create' },
      keywords: ['absence', 'conge', 'permission', 'attestation'],
    },
    {
      key: 'requests',
      label: 'Demandes',
      description: 'Suivre les demandes et validations',
      icon: 'fa-inbox',
      color: '#0891b2',
      to: { name: 'requests.index' },
      keywords: ['validation', 'suivi'],
    },
    {
      key: 'tasks',
      label: 'Mes tâches',
      description: 'Travail attribué et avancement',
      icon: 'fa-list-check',
      color: '#2563eb',
      to: { name: 'taches.index' },
      keywords: ['tache', 'activite', 'avancement'],
    },
    {
      key: 'pta',
      label: 'PTA',
      description: 'Plan de travail annuel',
      icon: 'fa-calendar-check',
      color: '#d97706',
      to: { name: 'plan-travail.index' },
      keywords: ['plan', 'travail', 'annuel'],
    },
    {
      key: 'holidays',
      label: 'Congés',
      description: 'Planning et demandes de congé',
      icon: 'fa-calendar-days',
      color: '#059669',
      to: { name: 'mon-planning-conges' },
      keywords: ['absence', 'planning'],
    },
    {
      key: 'documents',
      label: 'Documents',
      description: 'Bibliothèque documentaire',
      icon: 'fa-folder-open',
      color: '#0284c7',
      to: { name: 'documents.index' },
      keywords: ['ged', 'fichier'],
    },
    {
      key: 'work-documents',
      label: 'Docs RH',
      description: 'Documents de travail',
      icon: 'fa-file-invoice',
      color: '#7c3aed',
      to: { name: 'documents-travail.index' },
      keywords: ['rh', 'travail'],
    },
    {
      key: 'mailbox',
      label: 'Mail',
      description: 'Boîte de réception intégrée',
      icon: 'fa-envelope',
      color: '#0ea5e9',
      to: { name: 'mailbox.index' },
      keywords: ['email', 'courrier'],
    },
    {
      key: 'address-book',
      label: "Carnet d'adresse",
      description: 'Contacts des agents',
      icon: 'fa-address-book',
      color: '#16a34a',
      to: { name: 'address-book.index' },
      keywords: ['contact', 'agent'],
    },
    {
      key: 'forum',
      label: 'Forum',
      description: 'Discussions institutionnelles',
      icon: 'fa-comments',
      color: '#14b8a6',
      to: { name: 'forum.index' },
      keywords: ['discussion', 'communaute'],
    },
    {
      key: 'notifications',
      label: 'Notifications',
      description: 'Alertes et messages récents',
      icon: 'fa-bell',
      color: '#dc2626',
      to: { name: 'notifications.index' },
      keywords: ['alerte'],
    },
    {
      key: 'support',
      label: 'Support',
      description: 'Assistance technique',
      icon: 'fa-headset',
      color: '#475569',
      to: { name: 'technical-support' },
      keywords: ['aide', 'ticket', 'probleme'],
    },
    {
      key: 'guide',
      label: 'Guide utilisateur',
      description: 'Parcours guidé de l’application',
      icon: 'fa-book-open',
      color: '#9333ea',
      event: 'epnmls:open-user-guide',
      keywords: ['aide', 'tour'],
    },
    {
      key: 'theme',
      label: ui.isDark ? 'Mode jour' : 'Mode nuit',
      description: 'Basculer le thème de l’interface',
      icon: ui.isDark ? 'fa-sun' : 'fa-moon',
      color: ui.isDark ? '#f59e0b' : '#0f172a',
      run: () => ui.toggleDarkMode(),
      keywords: ['theme', 'dark', 'nuit', 'jour'],
    },
  ]

  if (auth.canAdminPta) {
    items.splice(5, 0, {
      key: 'adm-pta',
      label: 'Adm PTA',
      description: 'Suivi administratif du PTA',
      icon: 'fa-chart-pie',
      color: '#b45309',
      to: { name: 'adm-pta.index' },
      keywords: ['administration', 'planification'],
    })
  }

  if (auth.isRH || auth.isSEN || auth.isSEP || auth.isRhOperationalAssistant) {
    items.push({
      key: 'rh-dashboard',
      label: 'Tableau RH',
      description: 'Pilotage RH et validations',
      icon: 'fa-chart-line',
      color: '#0369a1',
      to: { name: 'rh.dashboard' },
      keywords: ['ressources humaines', 'pilotage'],
    })
  }

  if (auth.hasAdminAccess) {
    items.push({
      key: 'agents',
      label: 'Agents',
      description: 'Fiches et dossiers agents',
      icon: 'fa-users',
      color: '#0d9488',
      to: { name: 'rh.agents.index' },
      keywords: ['rh', 'personnel'],
    })
  }

  if (auth.isChefSectionRenforcement) {
    items.push({
      key: 'training',
      label: 'Formations',
      description: 'Renforcement des capacités',
      icon: 'fa-graduation-cap',
      color: '#15803d',
      to: { name: 'renforcements.index' },
      keywords: ['formation', 'renforcement'],
    })
  }

  if (auth.isAdminNT) {
    items.push({
      key: 'admin',
      label: 'Paramètres',
      description: 'Administration système',
      icon: 'fa-sliders-h',
      color: '#334155',
      to: { name: 'admin.dashboard' },
      keywords: ['admin', 'systeme', 'configuration'],
    })
  }

  return items
})

const pinnedActions = computed(() => {
  return pinnedKeys
    .map((key) => actions.value.find((action) => action.key === key))
    .filter(Boolean)
})

const filteredActions = computed(() => {
  const needle = normalize(query.value)
  if (!needle) return actions.value

  return actions.value.filter((action) => {
    const haystack = normalize([
      action.label,
      action.description,
      ...(action.keywords || []),
    ].join(' '))

    return haystack.includes(needle)
  })
})

function normalize(value) {
  return (value || '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

function actionStyle(action) {
  return {
    '--command-accent': action.color,
    '--command-accent-soft': `${action.color}1f`,
  }
}

function toggle() {
  isOpen.value = !isOpen.value
}

function close() {
  isOpen.value = false
}

async function runAction(action) {
  close()

  await nextTick()

  if (action.run) {
    action.run()
    return
  }

  if (action.event) {
    window.dispatchEvent(new CustomEvent(action.event))
    return
  }

  if (action.to) {
    router.push(action.to)
  }
}

function isCurrentRoute(action) {
  if (!action.to?.name) return false
  return route.name === action.to.name
}

function handleKeydown(event) {
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault()
    toggle()
    return
  }

  if (event.key === 'Escape' && isOpen.value) {
    close()
  }
}

watch(isOpen, async (value) => {
  if (!value) {
    query.value = ''
    return
  }

  await nextTick()
  searchInput.value?.focus()
})

watch(() => route.fullPath, () => {
  close()
})

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.command-center {
  --command-panel-width: 390px;
  position: relative;
  z-index: 11900;
}

.command-center__trigger {
  position: fixed;
  left: max(1rem, env(safe-area-inset-left));
  bottom: max(1rem, env(safe-area-inset-bottom));
  z-index: 11920;
  width: 52px;
  height: 52px;
  border: 1px solid rgba(255, 255, 255, .54);
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  background:
    linear-gradient(135deg, rgba(0, 119, 181, .98), rgba(15, 118, 110, .94));
  box-shadow: 0 18px 42px rgba(0, 64, 96, .28), inset 0 1px 0 rgba(255, 255, 255, .32);
  transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
}

.command-center__trigger:hover,
.command-center__trigger.is-open {
  transform: translateY(-2px);
  box-shadow: 0 22px 50px rgba(0, 64, 96, .34), inset 0 1px 0 rgba(255, 255, 255, .38);
}

.command-center__trigger i {
  font-size: 1.05rem;
}

.command-center__backdrop {
  position: fixed;
  inset: 0;
  z-index: 11900;
  border: 0;
  background: rgba(15, 23, 42, .16);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
}

.command-center__panel {
  position: fixed;
  left: max(1rem, env(safe-area-inset-left));
  bottom: calc(max(1rem, env(safe-area-inset-bottom)) + 64px);
  z-index: 11910;
  width: min(var(--command-panel-width), calc(100vw - 2rem));
  max-height: min(680px, calc(100dvh - 7rem));
  overflow: hidden;
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(255, 255, 255, .66);
  border-radius: 18px;
  color: #0f172a;
  background:
    linear-gradient(145deg, rgba(255, 255, 255, .94), rgba(240, 249, 255, .82));
  box-shadow: 0 28px 80px rgba(15, 35, 58, .28), inset 0 1px 0 rgba(255, 255, 255, .88);
  backdrop-filter: blur(24px) saturate(160%);
  -webkit-backdrop-filter: blur(24px) saturate(160%);
}

.command-center__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1rem .75rem;
  border-bottom: 1px solid rgba(125, 211, 252, .22);
}

.command-center__eyebrow {
  display: block;
  color: #0369a1;
  font-size: .68rem;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: .08em;
}

.command-center__header h2 {
  margin: .12rem 0 0;
  color: #0f172a;
  font-size: 1.05rem;
  font-weight: 850;
  line-height: 1.15;
}

.command-center__icon-btn {
  width: 36px;
  height: 36px;
  border: 1px solid rgba(148, 163, 184, .26);
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #475569;
  background: rgba(255, 255, 255, .66);
}

.command-center__icon-btn:hover {
  color: #dc2626;
  background: rgba(254, 226, 226, .86);
}

.command-center__search {
  display: flex;
  align-items: center;
  gap: .65rem;
  margin: .85rem 1rem .75rem;
  min-height: 44px;
  padding: 0 .85rem;
  border: 1px solid rgba(125, 211, 252, .36);
  border-radius: 12px;
  color: #64748b;
  background: rgba(255, 255, 255, .74);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, .8);
}

.command-center__search input {
  width: 100%;
  min-width: 0;
  border: 0;
  outline: 0;
  color: #0f172a;
  background: transparent;
  font: inherit;
  font-size: .9rem;
  font-weight: 700;
}

.command-center__search input::placeholder {
  color: #94a3b8;
  font-weight: 700;
}

.command-center__pinned {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: .55rem;
  padding: 0 1rem .85rem;
}

.command-center__pin {
  min-width: 0;
  min-height: 74px;
  border: 1px solid rgba(148, 163, 184, .22);
  border-radius: 12px;
  display: grid;
  place-items: center;
  gap: .38rem;
  padding: .55rem .35rem;
  color: #0f172a;
  background: rgba(255, 255, 255, .62);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, .72);
}

.command-center__pin:hover {
  border-color: var(--command-accent);
  background: var(--command-accent-soft);
  transform: translateY(-1px);
}

.command-center__pin span {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--command-accent);
  background: var(--command-accent-soft);
}

.command-center__pin strong {
  max-width: 100%;
  color: #334155;
  font-size: .68rem;
  font-weight: 850;
  line-height: 1.1;
  text-align: center;
  overflow-wrap: anywhere;
}

.command-center__list {
  overflow: auto;
  padding: .35rem .65rem .85rem;
}

.command-center__row {
  width: 100%;
  min-width: 0;
  min-height: 58px;
  border: 1px solid transparent;
  border-radius: 12px;
  display: grid;
  grid-template-columns: 40px minmax(0, 1fr) auto;
  align-items: center;
  gap: .72rem;
  padding: .55rem .62rem;
  color: #0f172a;
  background: transparent;
  text-align: left;
}

.command-center__row:hover,
.command-center__row.is-current {
  border-color: rgba(125, 211, 252, .34);
  background: rgba(255, 255, 255, .72);
}

.command-center__row-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--command-accent);
  background: var(--command-accent-soft);
}

.command-center__row-copy {
  min-width: 0;
  display: grid;
  gap: .12rem;
}

.command-center__row-copy strong {
  color: #0f172a;
  font-size: .86rem;
  font-weight: 850;
  line-height: 1.15;
}

.command-center__row-copy small {
  color: #64748b;
  font-size: .72rem;
  font-weight: 650;
  line-height: 1.2;
  overflow-wrap: anywhere;
}

.command-center__row-arrow {
  color: #94a3b8;
  font-size: .72rem;
}

.command-center__empty {
  display: grid;
  place-items: center;
  gap: .45rem;
  padding: 1.4rem 1rem;
  color: #64748b;
  font-size: .86rem;
  font-weight: 800;
}

.command-center-backdrop-enter-active,
.command-center-backdrop-leave-active,
.command-center-panel-enter-active,
.command-center-panel-leave-active {
  transition: opacity .18s ease, transform .18s ease;
}

.command-center-backdrop-enter-from,
.command-center-backdrop-leave-to {
  opacity: 0;
}

.command-center-panel-enter-from,
.command-center-panel-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(.98);
}

html.dark .command-center__panel {
  color: #e2e8f0;
  border-color: rgba(125, 211, 252, .18);
  background:
    linear-gradient(145deg, rgba(30, 41, 59, .94), rgba(15, 23, 42, .88));
  box-shadow: 0 28px 80px rgba(0, 0, 0, .48), inset 0 1px 0 rgba(255, 255, 255, .12);
}

html.dark .command-center__header {
  border-bottom-color: rgba(125, 211, 252, .16);
}

html.dark .command-center__header h2,
html.dark .command-center__row-copy strong,
html.dark .command-center__search input,
html.dark .command-center__pin strong {
  color: #f8fafc;
}

html.dark .command-center__search,
html.dark .command-center__icon-btn,
html.dark .command-center__pin,
html.dark .command-center__row:hover,
html.dark .command-center__row.is-current {
  background: rgba(15, 23, 42, .58);
  border-color: rgba(125, 211, 252, .16);
}

html.dark .command-center__row-copy small,
html.dark .command-center__empty {
  color: #a8b7cc;
}

@media (max-width: 576px) {
  .command-center__trigger {
    width: 48px;
    height: 48px;
    border-radius: 14px;
  }

  .command-center__panel {
    left: .65rem;
    right: .65rem;
    bottom: calc(max(.75rem, env(safe-area-inset-bottom)) + 58px);
    width: auto;
    max-height: min(620px, calc(100dvh - 5rem));
    border-radius: 16px;
  }

  .command-center__pinned {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .command-center__pin {
    min-height: 58px;
    grid-template-columns: 32px minmax(0, 1fr);
    place-items: center start;
    padding-inline: .65rem;
  }

  .command-center__pin strong {
    text-align: left;
  }
}

@media (prefers-reduced-motion: reduce) {
  .command-center__trigger,
  .command-center__pin,
  .command-center-backdrop-enter-active,
  .command-center-backdrop-leave-active,
  .command-center-panel-enter-active,
  .command-center-panel-leave-active {
    transition: none !important;
  }
}
</style>
