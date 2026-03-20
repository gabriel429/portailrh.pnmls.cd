<template>
  <div>
    <nav v-if="auth.isAuthenticated" class="navbar navbar-expand-lg navbar-main">
      <div class="container-fluid px-3">
        <router-link class="navbar-brand" :to="{ name: 'dashboard' }">
          <img src="/images/logo-pnmls.png" alt="PNMLS">
          <div class="brand-text">
            <div class="brand-title">Portail RH</div>
            <div class="brand-sub">PNMLS</div>
          </div>
        </router-link>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
          <ul class="navbar-nav mx-auto">
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'dashboard' }">
                <i class="fas fa-th-large nav-icon"></i> Accueil
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'profile.show' }">
                <i class="fas fa-id-badge nav-icon"></i> Mon Profil
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'requests.index' }">
                <i class="fas fa-paper-plane nav-icon"></i> Demandes
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'documents.index' }">
                <i class="fas fa-folder-open nav-icon"></i> Documents
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'plan-travail.index' }">
                <i class="fas fa-calendar-check nav-icon"></i> Plan de Travail
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" active-class="active" :to="{ name: 'documents-travail.index' }">
                <i class="fas fa-file-invoice nav-icon"></i> Docs Travail
              </router-link>
            </li>
          </ul>

          <ul class="navbar-nav align-items-lg-center">
            <!-- Admin dropdown -->
            <li v-if="auth.hasAdminAccess" class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-shield-halved nav-icon"></i> Admin
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <template v-if="auth.isRH">
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'rh.agents.index' }">
                      <span class="dd-icon dd-icon-blue"><i class="fas fa-users"></i></span> Gestion Agents
                    </router-link>
                  </li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'rh.communiques.index' }">
                      <span class="dd-icon dd-icon-green"><i class="fas fa-bullhorn"></i></span> Communiques
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
                </template>
                <template v-if="auth.isAdminNT">
                  <li v-if="auth.isRH"><hr class="dropdown-divider"></li>
                  <li>
                    <router-link class="dropdown-item" :to="{ name: 'admin.dashboard' }">
                      <span class="dd-icon dd-icon-slate"><i class="fas fa-sliders-h"></i></span> Parametres systeme
                    </router-link>
                  </li>
                </template>
              </ul>
            </li>

            <div class="nav-divider d-none d-lg-block"></div>

            <!-- Notification bell -->
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
                  <li v-for="n in notifStore.recent" :key="n.id">
                    <router-link
                      class="dropdown-item notif-item"
                      :class="{ 'notif-unread': !n.lu }"
                      :to="n.lien || '/notifications'"
                    >
                      <span class="notif-item-icon" :style="{ background: (n.couleur || '#0077B5') + '20', color: n.couleur || '#0077B5' }">
                        <i :class="'fas ' + (n.icone || 'fa-bell')"></i>
                      </span>
                      <span class="notif-item-content">
                        <span class="notif-item-title">{{ n.titre }}</span>
                        <span class="notif-item-time">{{ n.temps }}</span>
                      </span>
                      <span v-if="!n.lu" class="notif-dot"></span>
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

            <!-- User dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link nav-user-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <span class="nav-user-avatar">{{ initials }}</span>
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

    <!-- Content -->
    <div class="container-fluid">
      <AppToast />
      <slot />
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
      <p class="mb-0">&copy; 2026 Portail RH PNMLS — Programme National Multisectoriel de Lutte contre le Sida</p>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'
import AppToast from '@/components/common/AppToast.vue'

const auth = useAuthStore()
const notifStore = useNotificationStore()
const router = useRouter()
const route = useRoute()

// Close mobile navbar and dropdowns on route change
watch(() => route.fullPath, () => {
    const navbarEl = document.getElementById('navbarMain')
    if (navbarEl && navbarEl.classList.contains('show')) {
        const bsCollapse = window.bootstrap?.Collapse?.getInstance(navbarEl)
        if (bsCollapse) {
            bsCollapse.hide()
        } else {
            navbarEl.classList.remove('show')
        }
    }
    // Close any open dropdowns
    document.querySelectorAll('.navbar-main .dropdown-menu.show').forEach(menu => {
        menu.classList.remove('show')
        menu.closest('.dropdown')?.querySelector('.dropdown-toggle')?.classList.remove('show')
        menu.closest('.dropdown')?.querySelector('.dropdown-toggle')?.setAttribute('aria-expanded', 'false')
    })
})

const initials = computed(() => {
    const agent = auth.agent
    if (agent) {
        return ((agent.prenom?.[0] || '') + (agent.nom?.[0] || '')).toUpperCase()
    }
    return (auth.user?.name?.[0] || 'U').toUpperCase()
})

async function handleLogout() {
    await auth.logout()
    notifStore.stopPolling()
    router.push({ name: 'login' })
}

onMounted(() => {
    if (auth.isAuthenticated) {
        notifStore.startPolling()
    }
})

onUnmounted(() => {
    notifStore.stopPolling()
})
</script>
