<template>
  <div class="admin-wrapper">
    <button
      v-if="isMobile && mobileSidebarOpen"
      class="admin-mobile-backdrop"
      type="button"
      aria-label="Fermer le panneau"
      @click="closeMobileSidebar"
    ></button>

    <!-- Sidebar -->
    <aside class="admin-sidebar" :class="sidebarClasses">
      <div class="sidebar-header">
        <router-link :to="{ name: 'admin.dashboard' }" class="sidebar-brand">
          <img src="/images/logo-pnmls.png" alt="PNMLS">
          <span v-if="showSidebarText" class="sidebar-brand-text">Admin PNMLS</span>
        </router-link>
        <button v-if="isMobile" class="sidebar-close" type="button" @click="closeMobileSidebar">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <nav class="sidebar-nav">
        <router-link
          v-for="item in navItems"
          :key="item.route"
          :to="{ name: item.route }"
          class="sidebar-link"
          active-class="active"
          @click="closeMobileSidebar"
        >
          <i :class="item.icon"></i>
          <span v-if="showSidebarText">{{ item.label }}</span>
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <router-link :to="{ name: 'dashboard' }" class="sidebar-link" @click="closeMobileSidebar">
          <i class="fas fa-arrow-left"></i>
          <span v-if="showSidebarText">Retour au portail</span>
        </router-link>
      </div>
    </aside>

    <!-- Main content -->
    <div class="admin-main" :class="{ expanded: !ui.sidebarOpen && !isMobile, 'mobile-sheet-open': mobileSidebarOpen }">
      <!-- Topbar -->
      <header class="admin-topbar">
        <button class="btn btn-sm btn-outline-secondary" @click="toggleSidebarPanel()">
          <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-right">
          <button type="button" class="admin-topbar-icon" aria-label="Notifications">
            <i class="fas fa-bell"></i>
          </button>

          <div class="dropdown">
            <a
              href="#"
              class="admin-user-chip dropdown-toggle"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <span class="admin-user-avatar">{{ adminInitials }}</span>
              <span class="admin-user-name">{{ adminDisplayName }}</span>
            </a>

            <ul class="dropdown-menu dropdown-menu-end admin-user-menu">
              <li>
                <div class="admin-user-menu-header">
                  <div class="admin-user-menu-title">{{ adminFullName }}</div>
                  <div class="admin-user-menu-subtitle">{{ auth.user?.email }}</div>
                </div>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a href="#" class="dropdown-item text-danger" @click.prevent="handleLogout">
                  <i class="fas fa-sign-out-alt me-2"></i>
                  Deconnexion
                </a>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <div class="admin-content">
        <AppToast />
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useNotificationStore } from '@/stores/notification'
import AppToast from '@/components/common/AppToast.vue'

const auth = useAuthStore()
const ui = useUiStore()
const notifStore = useNotificationStore()
const router = useRouter()
const route = useRoute()
const isMobile = ref(false)
const mobileSidebarOpen = ref(false)

const baseNavItems = [
  { route: 'admin.dashboard', icon: 'fas fa-tachometer-alt', label: 'Dashboard' },
  { route: 'plan-travail.index', icon: 'fas fa-calendar-check', label: 'PTA' },
  { route: 'admin.organes.index', icon: 'fas fa-sitemap', label: 'Organes' },
  { route: 'admin.departments.index', icon: 'fas fa-building', label: 'Departements' },
  { route: 'admin.sections.index', icon: 'fas fa-project-diagram', label: 'Sections' },
  { route: 'admin.cellules.index', icon: 'fas fa-cube', label: 'Cellules' },
  { route: 'admin.provinces.index', icon: 'fas fa-map-marked-alt', label: 'Provinces' },
  { route: 'admin.localites.index', icon: 'fas fa-map-pin', label: 'Localites' },
  { route: 'admin.fonctions.index', icon: 'fas fa-briefcase', label: 'Fonctions' },
  { route: 'admin.grades.index', icon: 'fas fa-layer-group', label: 'Grades' },
  { route: 'admin.roles.index', icon: 'fas fa-user-shield', label: 'Roles' },
  { route: 'admin.categories-documents.index', icon: 'fas fa-tags', label: 'Categories Documents' },
  { route: 'admin.utilisateurs.index', icon: 'fas fa-users-cog', label: 'Utilisateurs' },
  { route: 'admin.logs', icon: 'fas fa-scroll', label: 'Journaux' },
]

const navItems = computed(() => {
    const items = [...baseNavItems]
    if (auth.isSuperAdmin) {
        items.push({ route: 'admin.deployment.index', icon: 'fas fa-rocket', label: 'Deploiement' })
        items.push({ route: 'admin.audit-logs', icon: 'fas fa-shield-alt', label: 'Audit & Modifications' })
    }
    return items
})

const adminDisplayName = computed(() => auth.agent?.prenom || auth.user?.name || 'Compte')

const adminFullName = computed(() => {
  const fullName = [auth.agent?.prenom, auth.agent?.nom].filter(Boolean).join(' ').trim()
  return fullName || auth.user?.name || 'Compte administrateur'
})

const adminInitials = computed(() => {
  const source = adminFullName.value
  if (!source) {
    return 'AD'
  }

  return source
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
})

  const showSidebarText = computed(() => (isMobile.value ? true : ui.sidebarOpen))

  const sidebarClasses = computed(() => ({
    collapsed: !ui.sidebarOpen && !isMobile.value,
    'mobile-open': isMobile.value && mobileSidebarOpen.value,
    'mobile-mode': isMobile.value,
  }))

  function syncViewportState() {
    isMobile.value = window.innerWidth <= 768
    if (isMobile.value) {
      mobileSidebarOpen.value = false
    }
  }

  function closeMobileSidebar() {
    if (isMobile.value) {
      mobileSidebarOpen.value = false
    }
  }

  function toggleSidebarPanel() {
    if (isMobile.value) {
      mobileSidebarOpen.value = !mobileSidebarOpen.value
      return
    }

    ui.toggleSidebar()
  }

async function handleLogout() {
    notifStore.stopPolling()
    await auth.logout()
    router.push({ name: 'login' })
}

  watch(() => route.fullPath, () => {
    closeMobileSidebar()
  })

  onMounted(() => {
    syncViewportState()
    window.addEventListener('resize', syncViewportState)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', syncViewportState)
  })
</script>

<style scoped>
.admin-wrapper { display: flex; min-height: 100vh; }
.admin-sidebar {
    width: 260px; background: #1e293b; color: #cbd5e1;
    display: flex; flex-direction: column; transition: width .2s;
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 1040;
    overflow-y: auto;
}
.admin-sidebar.collapsed { width: 60px; }
  .sidebar-header { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,.1); display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
.sidebar-brand {
    display: flex; align-items: center; gap: .6rem;
    color: #fff; text-decoration: none; font-weight: 700; font-size: .95rem;
}
.sidebar-brand img { height: 32px; width: 32px; border-radius: 6px; }
  .sidebar-close {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.08); color: #fff;
  }
.sidebar-nav { flex: 1; padding: .5rem 0; }
.sidebar-link {
    display: flex; align-items: center; gap: .6rem;
    padding: .55rem 1rem; color: #94a3b8; text-decoration: none;
    font-size: .84rem; transition: all .15s; border-left: 3px solid transparent;
}
.sidebar-link:hover { color: #e2e8f0; background: rgba(255,255,255,.05); }
.sidebar-link.active { color: #fff; background: rgba(255,255,255,.1); border-left-color: #0077B5; }
.sidebar-link i { width: 20px; text-align: center; font-size: .85rem; }
.sidebar-footer { border-top: 1px solid rgba(255,255,255,.1); padding: .5rem 0; }
.admin-main { flex: 1; margin-left: 260px; transition: margin-left .2s; min-height: 100vh; background: #f8fafc; }
.admin-main.expanded { margin-left: 60px; }
.admin-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: .75rem 1.5rem; background: #fff;
    border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 1030;
}
.topbar-right { display: flex; align-items: center; gap: .75rem; }
.admin-content { padding: 1.5rem; }

.admin-topbar-icon {
  width: 42px;
  height: 42px;
  border: 0;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(180deg, #1f6b91 0%, #165a7a 100%);
  color: #fff;
  box-shadow: 0 10px 24px rgba(15, 23, 42, .14);
}

.admin-user-chip {
  min-height: 56px;
  padding: .5rem .95rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  gap: .72rem;
  background: linear-gradient(180deg, #1f6b91 0%, #165a7a 100%);
  color: #fff;
  text-decoration: none;
  box-shadow: 0 14px 30px rgba(15, 23, 42, .18);
  border: 1px solid rgba(255, 255, 255, .18);
}

.admin-user-chip::after {
  margin-left: .1rem;
}

.admin-user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,.16);
  color: #fff;
  font-size: .85rem;
  font-weight: 800;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,.12);
  flex-shrink: 0;
}

.admin-user-name {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: .95rem;
  font-weight: 700;
  text-transform: uppercase;
}

.admin-user-menu {
  min-width: 250px;
  margin-top: .6rem;
  border: 1px solid rgba(15, 23, 42, .08);
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 18px 38px rgba(15, 23, 42, .18);
}

.admin-user-menu-header {
  padding: .95rem 1rem;
  background: linear-gradient(180deg, #f8fbfd 0%, #eef6fa 100%);
}

.admin-user-menu-title {
  font-size: .95rem;
  font-weight: 700;
  color: #0f172a;
}

.admin-user-menu-subtitle {
  margin-top: .15rem;
  font-size: .8rem;
  color: #64748b;
  word-break: break-word;
}

.admin-mobile-backdrop {
  position: fixed; inset: 0; z-index: 1035; border: 0;
  background: rgba(15, 23, 42, 0.42);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

@media (max-width: 768px) {
  .admin-sidebar {
    width: min(82vw, 320px);
    max-width: 320px;
    transform: translateX(-110%);
    transition: transform .28s ease, box-shadow .28s ease;
    border-top-right-radius: 22px;
    border-bottom-right-radius: 22px;
    box-shadow: none;
    background: linear-gradient(180deg, #122033 0%, #1e293b 100%);
  }

  .admin-sidebar.mobile-open {
    transform: translateX(0);
    box-shadow: 0 22px 44px rgba(15, 23, 42, .28);
  }

  .admin-main,
  .admin-main.expanded {
    margin-left: 0;
  }

  .admin-topbar {
    padding: .8rem 1rem;
    border-bottom: 1px solid rgba(226, 232, 240, .9);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    background: rgba(255, 255, 255, .9);
  }

  .topbar-right {
    gap: .45rem;
  }

  .admin-topbar-icon {
    width: 38px;
    height: 38px;
  }

  .admin-user-chip {
    min-height: 48px;
    padding: .35rem .72rem .35rem .38rem;
    gap: .55rem;
  }

  .admin-user-avatar {
    width: 34px;
    height: 34px;
    font-size: .76rem;
  }

  .admin-user-name {
    max-width: 108px;
    font-size: .78rem;
  }

  .sidebar-link {
    padding: .72rem 1rem;
    margin: 0 .6rem;
    border-left-width: 0;
    border-radius: 14px;
    font-size: .83rem;
  }

  .sidebar-link.active {
    border-left-color: transparent;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
  }

  .admin-content {
    padding: 1rem;
  }
}
</style>
