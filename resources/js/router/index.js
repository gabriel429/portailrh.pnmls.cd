import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    // Guest
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { guest: true, layout: 'guest' },
    },

    // Authenticated - App layout
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
        meta: { auth: true },
    },

    // Profile
    {
        path: '/profile',
        name: 'profile.show',
        component: () => import('@/views/profile/ProfileView.vue'),
        meta: { auth: true },
    },
    {
        path: '/profile/edit',
        name: 'profile.edit',
        component: () => import('@/views/profile/ProfileEditView.vue'),
        meta: { auth: true },
    },
    {
        path: '/profile/password',
        name: 'profile.password',
        component: () => import('@/views/profile/ChangePasswordView.vue'),
        meta: { auth: true },
    },

    // Notifications
    {
        path: '/notifications',
        name: 'notifications.index',
        component: () => import('@/views/notifications/NotificationListView.vue'),
        meta: { auth: true },
    },

    // Documents GED
    {
        path: '/documents',
        name: 'documents.index',
        component: () => import('@/views/documents/DocumentListView.vue'),
        meta: { auth: true },
    },
    {
        path: '/documents/create',
        name: 'documents.create',
        component: () => import('@/views/documents/DocumentCreateView.vue'),
        meta: { auth: true },
    },
    {
        path: '/documents/:id',
        name: 'documents.show',
        component: () => import('@/views/documents/DocumentShowView.vue'),
        meta: { auth: true },
    },

    // Requests (Demandes)
    {
        path: '/requests',
        name: 'requests.index',
        component: () => import('@/views/rh/requests/RequestListView.vue'),
        meta: { auth: true },
    },
    {
        path: '/requests/create',
        name: 'requests.create',
        component: () => import('@/views/rh/requests/RequestCreateView.vue'),
        meta: { auth: true },
    },
    {
        path: '/requests/:id',
        name: 'requests.show',
        component: () => import('@/views/rh/requests/RequestShowView.vue'),
        meta: { auth: true },
    },
    {
        path: '/requests/:id/edit',
        name: 'requests.edit',
        component: () => import('@/views/rh/requests/RequestEditView.vue'),
        meta: { auth: true },
    },

    // Signalements
    {
        path: '/signalements',
        name: 'signalements.index',
        component: () => import('@/views/rh/signalements/SignalementListView.vue'),
        meta: { auth: true },
    },
    {
        path: '/signalements/create',
        name: 'signalements.create',
        component: () => import('@/views/rh/signalements/SignalementCreateView.vue'),
        meta: { auth: true },
    },
    {
        path: '/signalements/:id',
        name: 'signalements.show',
        component: () => import('@/views/rh/signalements/SignalementShowView.vue'),
        meta: { auth: true },
    },
    {
        path: '/signalements/:id/edit',
        name: 'signalements.edit',
        component: () => import('@/views/rh/signalements/SignalementEditView.vue'),
        meta: { auth: true },
    },

    // Taches
    {
        path: '/taches',
        name: 'taches.index',
        component: () => import('@/views/taches/TacheListView.vue'),
        meta: { auth: true },
    },
    {
        path: '/taches/create',
        name: 'taches.create',
        component: () => import('@/views/taches/TacheCreateView.vue'),
        meta: { auth: true },
    },
    {
        path: '/taches/:id',
        name: 'taches.show',
        component: () => import('@/views/taches/TacheShowView.vue'),
        meta: { auth: true },
    },

    // Plan de Travail
    {
        path: '/plan-travail',
        name: 'plan-travail.index',
        component: () => import('@/views/plan-travail/PlanTravailListView.vue'),
        meta: { auth: true },
    },
    {
        path: '/plan-travail/create',
        name: 'plan-travail.create',
        component: () => import('@/views/plan-travail/PlanTravailCreateView.vue'),
        meta: { auth: true },
    },
    {
        path: '/plan-travail/:id',
        name: 'plan-travail.show',
        component: () => import('@/views/plan-travail/PlanTravailShowView.vue'),
        meta: { auth: true },
    },
    {
        path: '/plan-travail/:id/edit',
        name: 'plan-travail.edit',
        component: () => import('@/views/plan-travail/PlanTravailEditView.vue'),
        meta: { auth: true },
    },

    // Documents Travail
    {
        path: '/documents-travail',
        name: 'documents-travail.index',
        component: () => import('@/views/documents-travail/DocumentTravailListView.vue'),
        meta: { auth: true },
    },

    // Absences
    {
        path: '/mes-absences',
        name: 'mes-absences',
        component: () => import('@/views/absences/AbsenceListView.vue'),
        meta: { auth: true },
    },

    // Messages
    {
        path: '/messages/:id',
        name: 'messages.show',
        component: () => import('@/views/messages/MessageShowView.vue'),
        meta: { auth: true },
    },

    // Communiques public
    {
        path: '/communiques/:id',
        name: 'communiques.show-public',
        component: () => import('@/views/rh/communiques/CommuniqueShowView.vue'),
        meta: { auth: true },
    },

    // ── RH Routes (role-restricted in guards) ──
    {
        path: '/rh/dashboard',
        name: 'rh.dashboard',
        component: () => import('@/views/dashboard/RhDashboardView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },
    {
        path: '/rh/agents',
        name: 'rh.agents.index',
        component: () => import('@/views/rh/agents/AgentListView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },
    {
        path: '/rh/agents/create',
        name: 'rh.agents.create',
        component: () => import('@/views/rh/agents/AgentCreateView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },
    {
        path: '/rh/agents/:id',
        name: 'rh.agents.show',
        component: () => import('@/views/rh/agents/AgentShowView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },
    {
        path: '/rh/agents/:id/edit',
        name: 'rh.agents.edit',
        component: () => import('@/views/rh/agents/AgentEditView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },

    // RH Pointages
    {
        path: '/rh/pointages',
        name: 'rh.pointages.index',
        component: () => import('@/views/rh/pointages/PointageListView.vue'),
        meta: { auth: true },
    },
    {
        path: '/rh/pointages/create',
        name: 'rh.pointages.create',
        component: () => import('@/views/rh/pointages/PointageCreateView.vue'),
        meta: { auth: true },
    },
    {
        path: '/rh/pointages/daily',
        name: 'rh.pointages.daily',
        component: () => import('@/views/rh/pointages/PointageDailyView.vue'),
        meta: { auth: true },
    },
    {
        path: '/rh/pointages/monthly',
        name: 'rh.pointages.monthly',
        component: () => import('@/views/rh/pointages/PointageMonthlyView.vue'),
        meta: { auth: true },
    },
    {
        path: '/rh/pointages/:id',
        name: 'rh.pointages.show',
        component: () => import('@/views/rh/pointages/PointageShowView.vue'),
        meta: { auth: true },
    },
    {
        path: '/rh/pointages/:id/edit',
        name: 'rh.pointages.edit',
        component: () => import('@/views/rh/pointages/PointageEditView.vue'),
        meta: { auth: true },
    },

    // RH Communiques
    {
        path: '/rh/communiques',
        name: 'rh.communiques.index',
        component: () => import('@/views/rh/communiques/CommuniqueListView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },
    {
        path: '/rh/communiques/create',
        name: 'rh.communiques.create',
        component: () => import('@/views/rh/communiques/CommuniqueCreateView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },

    // RH Affectations
    {
        path: '/rh/affectations',
        name: 'rh.affectations.index',
        component: () => import('@/views/rh/affectations/AffectationListView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },
    {
        path: '/rh/affectations/create',
        name: 'rh.affectations.create',
        component: () => import('@/views/rh/affectations/AffectationCreateView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },
    {
        path: '/rh/affectations/:id/edit',
        name: 'rh.affectations.edit',
        component: () => import('@/views/rh/affectations/AffectationEditView.vue'),
        meta: { auth: true, roles: ['Section ressources humaines', 'RH National', 'RH Provincial'] },
    },

    // ── Admin Routes ──
    {
        path: '/admin',
        meta: { auth: true, adminNT: true, layout: 'admin' },
        children: [
            {
                path: '',
                redirect: '/admin/parametres',
            },
            {
                path: 'parametres',
                name: 'admin.dashboard',
                component: () => import('@/views/admin/AdminDashboardView.vue'),
            },
            {
                path: 'deployment',
                name: 'admin.deployment.index',
                component: () => import('@/views/admin/deployment/DeploymentView.vue'),
            },
            // CRUD entities
            { path: 'provinces', name: 'admin.provinces.index', component: () => import('@/views/admin/provinces/ProvinceListView.vue') },
            { path: 'provinces/create', name: 'admin.provinces.create', component: () => import('@/views/admin/provinces/ProvinceFormView.vue') },
            { path: 'provinces/:id/edit', name: 'admin.provinces.edit', component: () => import('@/views/admin/provinces/ProvinceFormView.vue') },
            { path: 'grades', name: 'admin.grades.index', component: () => import('@/views/admin/grades/GradeListView.vue') },
            { path: 'grades/create', name: 'admin.grades.create', component: () => import('@/views/admin/grades/GradeFormView.vue') },
            { path: 'grades/:id/edit', name: 'admin.grades.edit', component: () => import('@/views/admin/grades/GradeFormView.vue') },
            { path: 'roles', name: 'admin.roles.index', component: () => import('@/views/admin/roles/RoleListView.vue') },
            { path: 'roles/create', name: 'admin.roles.create', component: () => import('@/views/admin/roles/RoleFormView.vue') },
            { path: 'roles/:id/edit', name: 'admin.roles.edit', component: () => import('@/views/admin/roles/RoleFormView.vue') },
            { path: 'departments', name: 'admin.departments.index', component: () => import('@/views/admin/departments/DepartmentListView.vue') },
            { path: 'departments/create', name: 'admin.departments.create', component: () => import('@/views/admin/departments/DepartmentFormView.vue') },
            { path: 'departments/:id/edit', name: 'admin.departments.edit', component: () => import('@/views/admin/departments/DepartmentFormView.vue') },
            { path: 'fonctions', name: 'admin.fonctions.index', component: () => import('@/views/admin/fonctions/FonctionListView.vue') },
            { path: 'fonctions/create', name: 'admin.fonctions.create', component: () => import('@/views/admin/fonctions/FonctionFormView.vue') },
            { path: 'fonctions/:id/edit', name: 'admin.fonctions.edit', component: () => import('@/views/admin/fonctions/FonctionFormView.vue') },
            { path: 'sections', name: 'admin.sections.index', component: () => import('@/views/admin/sections/SectionListView.vue') },
            { path: 'sections/create', name: 'admin.sections.create', component: () => import('@/views/admin/sections/SectionFormView.vue') },
            { path: 'sections/:id/edit', name: 'admin.sections.edit', component: () => import('@/views/admin/sections/SectionFormView.vue') },
            { path: 'cellules', name: 'admin.cellules.index', component: () => import('@/views/admin/cellules/CelluleListView.vue') },
            { path: 'cellules/create', name: 'admin.cellules.create', component: () => import('@/views/admin/cellules/CelluleFormView.vue') },
            { path: 'cellules/:id/edit', name: 'admin.cellules.edit', component: () => import('@/views/admin/cellules/CelluleFormView.vue') },
            { path: 'localites', name: 'admin.localites.index', component: () => import('@/views/admin/localites/LocaliteListView.vue') },
            { path: 'localites/create', name: 'admin.localites.create', component: () => import('@/views/admin/localites/LocaliteFormView.vue') },
            { path: 'localites/:id/edit', name: 'admin.localites.edit', component: () => import('@/views/admin/localites/LocaliteFormView.vue') },
            { path: 'organes', name: 'admin.organes.index', component: () => import('@/views/admin/organes/OrganeListView.vue') },
            { path: 'organes/create', name: 'admin.organes.create', component: () => import('@/views/admin/organes/OrganeFormView.vue') },
            { path: 'organes/:id/edit', name: 'admin.organes.edit', component: () => import('@/views/admin/organes/OrganeFormView.vue') },
            { path: 'utilisateurs', name: 'admin.utilisateurs.index', component: () => import('@/views/admin/utilisateurs/UtilisateurListView.vue') },
            { path: 'utilisateurs/create', name: 'admin.utilisateurs.create', component: () => import('@/views/admin/utilisateurs/UtilisateurFormView.vue') },
            { path: 'utilisateurs/:id/edit', name: 'admin.utilisateurs.edit', component: () => import('@/views/admin/utilisateurs/UtilisateurFormView.vue') },
            { path: 'documents-travail', name: 'admin.documents-travail.index', component: () => import('@/views/admin/documents-travail/DocTravailListView.vue') },
            { path: 'documents-travail/create', name: 'admin.documents-travail.create', component: () => import('@/views/admin/documents-travail/DocTravailFormView.vue') },
            { path: 'documents-travail/:id/edit', name: 'admin.documents-travail.edit', component: () => import('@/views/admin/documents-travail/DocTravailFormView.vue') },
            { path: 'categories-documents', name: 'admin.categories-documents.index', component: () => import('@/views/admin/categories-documents/CategorieDocListView.vue') },
            { path: 'categories-documents/create', name: 'admin.categories-documents.create', component: () => import('@/views/admin/categories-documents/CategorieDocFormView.vue') },
            { path: 'categories-documents/:id/edit', name: 'admin.categories-documents.edit', component: () => import('@/views/admin/categories-documents/CategorieDocFormView.vue') },
            { path: 'logs', name: 'admin.logs', component: () => import('@/views/admin/AdminLogsView.vue') },
        ],
    },

    // 404
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/errors/NotFoundView.vue'),
        meta: { layout: 'guest' },
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 }
    },
})

router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (auth.loading) {
        await auth.fetchUser()
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' }
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } }
    }

    if (to.meta.roles) {
        const userRole = auth.role?.toLowerCase()
        const allowed = to.meta.roles.map(r => r.toLowerCase())
        if (!allowed.includes(userRole)) {
            return { name: 'dashboard' }
        }
    }

    if (to.meta.adminNT && !auth.isAdminNT) {
        return { name: 'dashboard' }
    }
})

export default router
