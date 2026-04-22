<template>
  <div>
    <nav v-if="auth.isAuthenticated" class="navbar navbar-expand-lg navbar-main">
      <div class="container-fluid px-3">
        <router-link class="navbar-brand" :to="{ name: 'dashboard' }">
          <img src="/images/logo-pnmls.png" alt="PNMLS">
          <div class="brand-text">
            <div class="brand-title">E-PNMLS</div>
            <div class="brand-sub">PNMLS</div>
          </div>
        </router-link>

        <button
          class="navbar-toggler"
          type="button"
          :aria-expanded="isMobileNavOpen ? 'true' : 'false'"
          aria-controls="navbarMain"
          @click="toggleMobileNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" :class="{ show: isMobileNavOpen }" id="navbarMain">
          <div class="mobile-nav-sheet-head d-lg-none">
            <div>
              <div class="mobile-nav-eyebrow">Navigation</div>
              <div class="mobile-nav-title">E-PNMLS</div>
            </div>
            <button class="mobile-nav-close" type="button" aria-label="Fermer le menu" @click="closeMobileNav">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <ul class="navbar-nav mx-auto navbar-primary-nav">
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'dashboard' }" title="Accueil">
                <i class="fas fa-th-large nav-icon"></i>
                <span class="nav-link-label">Accueil</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'requests.index' }" title="Demandes">
                <i class="fas fa-paper-plane nav-icon"></i>
                <span class="nav-link-label">Demandes</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'documents.index' }" title="Documents">
                <i class="fas fa-folder-open nav-icon"></i>
                <span class="nav-link-label">Documents</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'taches.index' }" title="Mes tâches">
                <i class="fas fa-tasks nav-icon"></i>
                <span class="nav-link-label">Mes tâches</span>
                <span v-if="taskNewCount > 0 || taskInProgressCount > 0" class="nav-badge-stack">
                  <span v-if="taskNewCount > 0" class="nav-badge nav-badge-new">{{ taskNewBadgeLabel }}</span>
                  <span v-if="taskInProgressCount > 0" class="nav-badge nav-badge-progress">{{ taskInProgressBadgeLabel }}</span>
                </span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'plan-travail.index' }" title="PTA">
                <i class="fas fa-calendar-check nav-icon"></i>
                <span class="nav-link-label">PTA</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'documents-travail.index' }" title="Documents de travail">
                <i class="fas fa-file-invoice nav-icon"></i>
                <span class="nav-link-label">Docs RH</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'mon-planning-conges' }" title="Congés">
                <i class="fas fa-calendar-alt nav-icon"></i>
                <span class="nav-link-label">Congés</span>
              </router-link>
            </li>
            <li v-if="auth.isChefSectionRenforcement" class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'renforcements.index' }" title="Formations RC">
                <i class="fas fa-graduation-cap nav-icon"></i>
                <span class="nav-link-label">Formations</span>
              </router-link>
            </li>
          </ul>

          <div class="mobile-nav-divider d-lg-none"></div>

          <ul class="navbar-nav align-items-lg-center navbar-secondary-nav">
            <!-- ── Menu Renforcement des Capacités ── -->
            <li v-if="auth.isChefSectionRenforcement" class="nav-item dropdown">
              <a class="nav-link dropdown-toggle nav-link-rc" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-graduation-cap nav-icon"></i>
                <span class="nav-link-label">Renforcement</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'renforcements.index' }">
                    <span class="dd-icon dd-icon-green"><i class="fas fa-chalkboard-teacher"></i></span> Formations
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'renforcements.create' }">
                    <span class="dd-icon dd-icon-teal"><i class="fas fa-plus-circle"></i></span> Planifier une formation
                  </router-link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'renforcements.report.monthly' }">
                    <span class="dd-icon dd-icon-blue"><i class="fas fa-chart-bar"></i></span> Rapport mensuel
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'renforcements.report.annual' }">
                    <span class="dd-icon dd-icon-purple"><i class="fas fa-chart-line"></i></span> Rapport annuel
                  </router-link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'requests.index', query: { type: 'renforcement_capacites' } }">
                    <span class="dd-icon dd-icon-orange"><i class="fas fa-paper-plane"></i></span> Demandes RC
                  </router-link>
                </li>
              </ul>
            </li>

            <li v-if="auth.hasAdminAccess" class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-shield-halved nav-icon"></i>
                <span class="nav-link-label">Admin</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <template v-if="auth.isRH || auth.isSEN">
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'rh.agents.index' }">
                      <span class="dd-icon dd-icon-blue"><i class="fas fa-users"></i></span> Gestion Agents
                    </router-link>
                  </li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'rh.holidays.planning' }">
                      <span class="dd-icon dd-icon-teal"><i class="fas fa-calendar-alt"></i></span> Gestion des Congés
                    </router-link>
                  </li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'rh.communiques.index' }">
                      <span class="dd-icon dd-icon-green"><i class="fas fa-bullhorn"></i></span> Communiqués
                    </router-link>
                  </li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'rh.pointages.index' }">
                      <span class="dd-icon dd-icon-purple"><i class="fas fa-clock"></i></span> Pointages
                    </router-link>
                  </li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'rh.affectations.index' }">
                      <span class="dd-icon dd-icon-orange"><i class="fas fa-exchange-alt"></i></span> Affectations
                    </router-link>
                  </li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'signalements.index' }">
                      <span class="dd-icon dd-icon-red"><i class="fas fa-flag"></i></span> Signalements
                    </router-link>
                  </li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'rh.dashboard' }">
                      <span class="dd-icon dd-icon-teal"><i class="fas fa-chart-line"></i></span> Tableau de Bord
                    </router-link>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'mail.history' }">
                      <span class="dd-icon dd-icon-blue"><i class="fas fa-paper-plane"></i></span> Historique emails
                    </router-link>
                  </li>
                </template>
                <template v-if="auth.isAdminNT">
                  <li v-if="auth.isRH || auth.isSEN"><hr class="dropdown-divider"></li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'admin.dashboard' }">
                      <span class="dd-icon dd-icon-slate"><i class="fas fa-sliders-h"></i></span> Parametres systeme
                    </router-link>
                  </li>
                </template>
              </ul>
            </li>

            <div class="nav-divider d-none d-lg-block"></div>

            <li class="nav-item d-none d-lg-flex align-items-center">
              <button class="dark-toggle-btn" @click="ui.toggleDarkMode()" :title="ui.isDark ? 'Passer en mode jour' : 'Passer en mode nuit'">
                <i :class="ui.isDark ? 'fas fa-sun' : 'fas fa-moon'"></i>
                <span>{{ ui.isDark ? 'Jour' : 'Nuit' }}</span>
              </button>
            </li>

            <div class="nav-divider d-none d-lg-block"></div>

            <li class="nav-item dropdown">
              <a class="nav-link nav-notif-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span v-if="notifStore.count > 0" class="notif-badge">
                  {{ notifStore.count > 99 ? '99+' : notifStore.count }}
                </span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end notif-dropdown">
                <li>
                  <div class="notif-dd-header">
                    <span class="notif-dd-title">Notifications</span>
                    <a v-if="notifStore.count > 0" href="#" class="notif-dd-clear" @click.prevent="notifStore.markAllRead()">
                      Tout marquer lu
                    </a>
                  </div>
                </li>
                <template v-if="notifStore.recent.length > 0">
                  <li v-for="notification in notifStore.recent" :key="notification.id">
                    <router-link
                      class="dropdown-item notif-item"
                      :class="{ 'notif-unread': !notification.lu }"
                      :to="notification.lien || '/notifications'"
                    >
                      <span
                        class="notif-item-icon"
                        :style="{ background: (notification.couleur || '#0077B5') + '20', color: notification.couleur || '#0077B5' }"
                      >
                        <i :class="'fas ' + (notification.icone || 'fa-bell')"></i>
                      </span>
                      <span class="notif-item-content">
                        <span class="notif-item-title">{{ notification.titre }}</span>
                        <span class="notif-item-time">{{ notification.temps }}</span>
                      </span>
                      <span v-if="!notification.lu" class="notif-dot"></span>
                    </router-link>
                  </li>
                </template>
                <li v-else>
                  <div class="notif-empty">Aucune notification</div>
                </li>
                <li><hr class="dropdown-divider m-0"></li>
                <li>
                  <router-link class="dropdown-item text-center notif-see-all" :to="{ name: 'notifications.index' }">
                    Voir toutes les notifications
                  </router-link>
                </li>
              </ul>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link nav-user-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <img v-if="currentProfilePhotoUrl" :src="currentProfilePhotoUrl" alt="Photo" class="nav-user-photo" @error="handleProfilePhotoError">
                <span v-else class="nav-user-avatar">{{ initials }}</span>
                <span class="nav-user-name">{{ auth.agent?.prenom || auth.user?.name }}</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <div class="user-dd-header">
                    <div class="user-dd-name">{{ auth.agent?.prenom }} {{ auth.agent?.nom || auth.user?.name }}</div>
                    <div class="user-dd-email">{{ auth.user?.email }}</div>
                  </div>
                </li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'profile.show' }">
                    <span class="dd-icon dd-icon-blue"><i class="fas fa-user"></i></span> Mon Profil
                  </router-link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item text-danger" href="#" @click.prevent="handleLogout">
                    <span class="dd-icon dd-icon-red"><i class="fas fa-sign-out-alt"></i></span> Deconnexion
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <button
      v-if="auth.isAuthenticated && isMobileNavOpen"
      class="mobile-nav-backdrop d-lg-none"
      type="button"
      aria-label="Fermer le menu"
      @click="closeMobileNav"
    ></button>

    <div class="watermark-logo"></div>

    <div class="container-fluid main-content">
      <AppToast />
      <slot />
    </div>

    <footer class="footer mt-5">
      <p class="mb-0">&copy; 2026 E-PNMLS — Programme National Multisectoriel de Lutte contre le Sida</p>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'
import { useUiStore } from '@/stores/ui'
import { getSummary as getTaskSummary } from '@/api/taches'
import AppToast from '@/components/common/AppToast.vue'

const ui = useUiStore()

const auth = useAuthStore()
const notifStore = useNotificationStore()
const router = useRouter()
const route = useRoute()
const isMobileNavOpen = ref(false)
const profilePhotoIndex = ref(0)
const taskNewCount = ref(0)
const taskInProgressCount = ref(0)

function closeMobileNav() {
  isMobileNavOpen.value = false
}

function toggleMobileNav() {
  isMobileNavOpen.value = !isMobileNavOpen.value
}

function handleViewportChange() {
  if (window.innerWidth >= 992) {
    closeMobileNav()
  }
}

watch(() => route.fullPath, () => {
  closeMobileNav()

  if (auth.isAuthenticated) {
    loadTaskSummary()
  }

  document.querySelectorAll('.navbar-main .dropdown-menu.show').forEach((menu) => {
    menu.classList.remove('show')
    menu.closest('.dropdown')?.querySelector('.dropdown-toggle')?.classList.remove('show')
    menu.closest('.dropdown')?.querySelector('.dropdown-toggle')?.setAttribute('aria-expanded', 'false')
  })
})

const taskNewBadgeLabel = computed(() => {
  return taskNewCount.value > 99 ? '99+' : String(taskNewCount.value)
})

const taskInProgressBadgeLabel = computed(() => {
  return taskInProgressCount.value > 99 ? '99+' : String(taskInProgressCount.value)
})

const initials = computed(() => {
  const agent = auth.agent
  if (agent) {
    return ((agent.prenom?.[0] || '') + (agent.nom?.[0] || '')).toUpperCase()
  }

  return (auth.user?.name?.[0] || 'U').toUpperCase()
})

const profilePhotoCandidates = computed(() => {
  const photo = auth.agent?.photo

  if (!photo) {
    return []
  }

  const trimmedPhoto = photo.trim()
  const candidates = []

  if (/^https?:\/\//i.test(trimmedPhoto)) {
    candidates.push(trimmedPhoto)
  } else {
    const normalizedPhoto = trimmedPhoto.replace(/^\/+/, '')

    candidates.push(`/${normalizedPhoto}`)

    if (!normalizedPhoto.startsWith('storage/')) {
      candidates.push(`/storage/${normalizedPhoto}`)
    }

    if (!normalizedPhoto.startsWith('uploads/') && !normalizedPhoto.includes('/')) {
      candidates.push(`/uploads/profiles/${normalizedPhoto}`)
    }
  }

  return [...new Set(candidates)]
})

const currentProfilePhotoUrl = computed(() => {
  return profilePhotoCandidates.value[profilePhotoIndex.value] || null
})

function handleProfilePhotoError() {
  if (profilePhotoIndex.value < profilePhotoCandidates.value.length - 1) {
    profilePhotoIndex.value += 1
    return
  }

  profilePhotoIndex.value = profilePhotoCandidates.value.length
}

async function loadTaskSummary() {
  try {
    const { data } = await getTaskSummary()
    taskNewCount.value = Number(data.newAssignedCount ?? data.new_assigned_count ?? 0)
    taskInProgressCount.value = Number(data.inProgressAssignedCount ?? data.in_progress_assigned_count ?? 0)
  } catch {
    taskNewCount.value = 0
    taskInProgressCount.value = 0
  }
}

async function handleLogout() {
  await auth.logout()
  notifStore.stopPolling()
  router.push({ name: 'login' })
}

onMounted(() => {
  ui.initDarkMode()

  if (auth.isAuthenticated) {
    notifStore.startPolling()
    loadTaskSummary()
  }

  window.addEventListener('resize', handleViewportChange)
  document.body.classList.toggle('mobile-nav-open', isMobileNavOpen.value)
})

onUnmounted(() => {
  notifStore.stopPolling()
  window.removeEventListener('resize', handleViewportChange)
  document.body.classList.remove('mobile-nav-open')
})

watch(isMobileNavOpen, (value) => {
  document.body.classList.toggle('mobile-nav-open', value)
})

watch(profilePhotoCandidates, () => {
  profilePhotoIndex.value = 0
}, { immediate: true })

watch(() => auth.user?.id, (userId) => {
  if (userId) {
    loadTaskSummary()
    return
  }

  taskNewCount.value = 0
  taskInProgressCount.value = 0
}, { immediate: true })
</script>

<style scoped>
.watermark-logo {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 420px;
  height: 420px;
  background: url('/images/logo-pnmls.png') center / contain no-repeat;
  opacity: 0.04;
  filter: blur(2px) grayscale(30%);
  pointer-events: none;
  z-index: 0;
}

@media (max-width: 768px) {
  .watermark-logo {
    width: 240px;
    height: 240px;
  }
}

.main-content {
  position: relative;
  z-index: 1;
}
</style>
