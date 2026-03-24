<template>
  <div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar" :class="{ collapsed: !ui.sidebarOpen }">
      <div class="sidebar-header">
        <router-link :to="{ name: 'admin.dashboard' }" class="sidebar-brand">
          <img src="/images/logo-pnmls.png" alt="PNMLS">
          <span v-if="ui.sidebarOpen" class="sidebar-brand-text">Admin PNMLS</span>
        </router-link>
      </div>

      <nav class="sidebar-nav">
        <router-link
          v-for="item in navItems"
          :key="item.route"
          :to="{ name: item.route }"
          class="sidebar-link"
          active-class="active"
        >
          <i :class="item.icon"></i>
          <span v-if="ui.sidebarOpen">{{ item.label }}</span>
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <router-link :to="{ name: 'dashboard' }" class="sidebar-link">
          <i class="fas fa-arrow-left"></i>
          <span v-if="ui.sidebarOpen">Retour au portail</span>
        </router-link>
      </div>
    </aside>

    <!-- Main content -->
    <div class="admin-main" :class="{ expanded: !ui.sidebarOpen }">
      <!-- Topbar -->
      <header class="admin-topbar">
        <button class="btn btn-sm btn-outline-secondary" @click="ui.toggleSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-right">
          <span class="text-muted me-3">{{ auth.user?.name }}</span>
          <a href="#" class="text-danger" @click.prevent="handleLogout">
            <i class="fas fa-sign-out-alt"></i>
          </a>
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
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useNotificationStore } from '@/stores/notification'
import AppToast from '@/components/common/AppToast.vue'

const auth = useAuthStore()
const ui = useUiStore()
const notifStore = useNotificationStore()
const router = useRouter()

const baseNavItems = [
    { route: 'admin.dashboard', icon: 'fas fa-tachometer-alt', label: 'Dashboard' },
    { route: 'admin.organes.index', icon: 'fas fa-sitemap', label: 'Organes' },
    { route: 'admin.departments.index', icon: 'fas fa-building', label: 'Departements' },
    { route: 'admin.sections.index', icon: 'fas fa-project-diagram', label: 'Sections' },
    { route: 'admin.cellules.index', icon: 'fas fa-cube', label: 'Cellules' },
    { route: 'admin.provinces.index', icon: 'fas fa-map-marked-alt', label: 'Provinces' },
    { route: 'admin.localites.index', icon: 'fas fa-map-pin', label: 'Localites' },
    { route: 'admin.fonctions.index', icon: 'fas fa-briefcase', label: 'Fonctions' },
    { route: 'admin.grades.index', icon: 'fas fa-layer-group', label: 'Grades' },
    { route: 'admin.roles.index', icon: 'fas fa-user-shield', label: 'Roles' },
    { route: 'admin.documents-travail.index', icon: 'fas fa-file-alt', label: 'Documents de travail' },
    { route: 'admin.categories-documents.index', icon: 'fas fa-tags', label: 'Categories Documents' },
    { route: 'admin.utilisateurs.index', icon: 'fas fa-users-cog', label: 'Utilisateurs' },
    { route: 'admin.deployment.index', icon: 'fas fa-rocket', label: 'Deploiement' },
    { route: 'admin.logs', icon: 'fas fa-scroll', label: 'Journaux' },
]

const navItems = computed(() => {
    const items = [...baseNavItems]
    if (auth.isSuperAdmin) {
        items.push({ route: 'admin.audit-logs', icon: 'fas fa-shield-alt', label: 'Audit & Modifications' })
    }
    return items
})

async function handleLogout() {
    notifStore.stopPolling()
    await auth.logout()
    router.push({ name: 'login' })
}
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
.sidebar-header { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,.1); }
.sidebar-brand {
    display: flex; align-items: center; gap: .6rem;
    color: #fff; text-decoration: none; font-weight: 700; font-size: .95rem;
}
.sidebar-brand img { height: 32px; width: 32px; border-radius: 6px; }
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
.topbar-right { display: flex; align-items: center; gap: .5rem; }
.admin-content { padding: 1.5rem; }

@media (max-width: 768px) {
    .admin-sidebar { width: 60px; }
    .admin-sidebar .sidebar-brand-text,
    .admin-sidebar .sidebar-link span { display: none; }
    .admin-main { margin-left: 60px; }
}
</style>
