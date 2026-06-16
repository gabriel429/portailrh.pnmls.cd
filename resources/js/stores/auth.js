import { defineStore } from 'pinia'
import axios from 'axios'
import client from '@/api/client'

const AUTH_USER_CACHE_KEY = 'epnmls_auth_user'

function normalizeText(value) {
    return (value ?? '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
}

function normalizedRole(state) {
    const role = state.user?.role?.nom_role
        ?? state.user?.nom_role
        ?? (typeof state.user?.role === 'string' ? state.user.role : '')

    return normalizeText(role)
}

function normalizedDepartmentCode(state) {
    return normalizeText(state.user?.agent?.departement?.code)
}

function normalizedDepartmentName(state) {
    return normalizeText(state.user?.agent?.departement?.nom)
}

function normalizedOrgane(state) {
    return normalizeText(state.user?.agent?.organe)
}

function normalizedAgentProfile(state) {
    const profile = [
        state.user?.agent?.fonction,
        state.user?.agent?.poste_actuel,
        state.user?.agent?.departement?.code,
        state.user?.agent?.departement?.nom,
    ]
        .filter(Boolean)
        .join(' ')

    return normalizeText(profile)
}

function isPlanningEvaluationDepartment(state) {
    const deptCode = normalizedDepartmentCode(state)
    const deptName = normalizedDepartmentName(state)
    const hasPlanification = deptName.includes('planification') || deptCode.includes('plan') || deptCode.includes('pse')
    const hasEvaluation = deptName.includes('evaluation') || deptName.includes('suivi') || deptCode.includes('pse')

    return hasPlanification && hasEvaluation
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loading: true,
    }),
    getters: {
        isAuthenticated: (state) => !!state.user,
        agent: (state) => state.user?.agent,
        role: (state) => state.user?.role?.nom_role,
        isRH(state) {
            const role = normalizedRole(state)
            return role.includes('ressource humaine') || role.includes('ressources humaines') || [
                'rh',
                'section ressources humaines',
                'chef section rh',
                'chef de section rh',
                'chef section ressources humaines',
                'ressources humaines',
                'rh national',
                'rh provincial',
            ].includes(role)
        },
        isAssistantRH(state) {
            if (state.user?.is_super_admin) return false

            const role = normalizedRole(state)
            const profile = `${role} ${normalizedAgentProfile(state)}`

            return [
                'assistant rh',
                'assistant ressources humaines',
                'assistant ressource humaine',
                'assistant section rh',
                'assistant de section rh',
            ].includes(role)
                || profile.includes('assistant rh')
                || profile.includes('assistant ressources humaines')
                || profile.includes('assistant ressource humaine')
                || (profile.includes('assistant') && (
                    profile.includes(' rh')
                    || profile.includes('ressource humaine')
                    || profile.includes('ressources humaines')
                ))
        },
        isFullRH() {
            return this.isRH && !this.isAssistantRH
        },
        isAdminNT(state) {
            if (state.user?.is_super_admin) return true
            const role = normalizedRole(state)
            return [
                'section nouvelle technologie',
                'chef section nouvelle technologie',
                'chef de section nouvelle technologie',
            ].includes(role)
        },
        isSuperAdmin(state) {
            return !!state.user?.is_super_admin
        },
        isSEN(state) {
            return normalizedRole(state) === 'sen'
        },
        isSENA(state) {
            return normalizedRole(state) === 'sena'
        },
        isPlanification(state) {
            const role = normalizedRole(state)
            return ['chef section planification', 'cellule planification'].includes(role)
        },
        isPlanificationDepartment(state) {
            return isPlanningEvaluationDepartment(state)
        },
        canAdminPta(state) {
            return !!state.user?.is_super_admin || this.isSEN || this.isPlanification || this.isPlanificationDepartment
        },
        isSEP(state) {
            const role = normalizedRole(state)
            if (role === 'sep') return true
            if (role !== 'secom') return false

            const deptCode = normalizedDepartmentCode(state)
            const deptName = normalizedDepartmentName(state)
            const organe = normalizedOrgane(state)
            const isCafScope = deptCode === 'caf' || deptName.includes('cellule administrative et financ')

            if (isCafScope) return false

            return organe.includes('provincial')
        },
        isSEL(state) {
            const role = normalizedRole(state)
            if (role === 'sel') return true

            const organe = normalizedOrgane(state)
            const profile = normalizedAgentProfile(state)

            return organe.includes('local')
                && (
                    profile.includes('secretaire executif local')
                    || profile.includes('(sel)')
                )
        },
        isRhLocal(state) {
            const role = normalizedRole(state)
            if ([
                'rh local',
                'aaf local',
                'assistant administratif et financier',
            ].includes(role)) {
                return true
            }

            const organe = normalizedOrgane(state)
            const profile = normalizedAgentProfile(state)

            return organe.includes('local')
                && (
                    profile.includes('rh local')
                    || profile.includes('assistant administratif et financier')
                    || profile.includes('aaf local')
                )
        },
        isProvincialOperationalAssistant() {
            return this.isSEP && normalizedRole(this.$state) === 'secom'
        },
        isRhOperationalAssistant() {
            return this.isAssistantRH || this.isProvincialOperationalAssistant
        },
        isDirecteur(state) {
            const role = normalizedRole(state)
            return [
                'directeur',
                'directrice',
                'directeur de département',
                'directeur de departement',
                'directrice de département',
                'directrice de departement',
            ].includes(role)
        },
        isAssistant(state) {
            if (this.isAssistantRH) return false

            const role = normalizedRole(state)
            return [
                'assistant',
                'assistant de département',
                'assistant de departement',
                'secrétaire de département',
                'secretaire de departement',
            ].includes(role)
        },
        isDepartement(state) {
            if (this.isAssistantRH) return false

            const role = normalizedRole(state)
            return [
                'directeur',
                'directrice',
                'directeur de département',
                'directeur de departement',
                'directrice de département',
                'directrice de departement',
                'secrétaire de département',
                'secretaire de departement',
            ].includes(role) || role.startsWith('assistant')
        },
        isChefSectionRenforcement(state) {
            return normalizedRole(state) === 'chef section renforcement'
        },
        isCAF(state) {
            const role = normalizedRole(state)
            if ([
                'cellule administrative et financière',
                'cellule administrative et financiere',
                'caf',
                'chef caf',
                'responsable caf',
            ].includes(role)) {
                return true
            }

            if (role !== 'secom') return false

            const deptCode = normalizedDepartmentCode(state)
            const deptName = normalizedDepartmentName(state)
            return deptCode === 'caf' || deptName.includes('cellule administrative et financ')
        },
        canManageDocsTravail(state) {
            if (state.user?.is_super_admin) return true

            const role = normalizedRole(state)
            const organe = normalizedOrgane(state)
            const profile = normalizedAgentProfile(state)
            if (role.includes('provincial') || role.includes('province') || organe.includes('provincial')) return false
            if (
                role.includes('assistant')
                || profile.includes('assistant rh')
                || profile.includes('assistant ressources humaines')
                || profile.includes('assistant ressource humaine')
            ) return false

            return [
                'section ressources humaines',
                'section ressource humaine',
                'chef section rh',
                'chef de section rh',
                'chef section ressources humaines',
                'rh national',
                'ressources humaines',
                'rh',
            ].includes(role)
        },
        canCreateAgents() {
            return this.isSuperAdmin || this.isSEN || this.isFullRH || (this.isRhOperationalAssistant && this.hasPermission('create_agent'))
        },
        canEditAgents() {
            return this.isSuperAdmin || this.isSEN || this.isFullRH || (this.isRhOperationalAssistant && this.hasPermission('edit_agent'))
        },
        canDeleteAgents() {
            return this.isSuperAdmin || this.isSEN || this.isFullRH || (this.isRhOperationalAssistant && this.hasPermission('delete_agent'))
        },
        canManageRhAdminModules() {
            return this.isSuperAdmin || this.isSEN || this.isFullRH
        },
        hasAdminAccess(state) {
            if (state.user?.is_super_admin) return true
            return this.isRH || this.isAdminNT || this.isSEN || this.isSEP || this.isSEL || this.isRhLocal || this.isRhOperationalAssistant
        },
        permissions(state) {
            return state.user?.permissions || []
        },
        hasPermission() {
            return (code) => {
                if (this.isSuperAdmin) return true
                return this.permissions.includes(code)
            }
        },
        hasAnyPermission() {
            return (codes) => {
                if (this.isSuperAdmin) return true
                return codes.some(c => this.permissions.includes(c))
            }
        },
        hasRole() {
            return (roles) => {
                if (this.isSuperAdmin) return true
                const rolesArray = Array.isArray(roles) ? roles : [roles]
                const userRole = normalizedRole(this.$state)
                return rolesArray.some(r => normalizeText(r) === userRole)
            }
        },
    },
    actions: {
        cachedUser() {
            if (typeof window === 'undefined') return null

            try {
                const raw = window.localStorage.getItem(AUTH_USER_CACHE_KEY)
                return raw ? JSON.parse(raw) : null
            } catch (_) {
                return null
            }
        },
        cacheUser(user) {
            if (typeof window === 'undefined') return

            try {
                if (user) {
                    window.localStorage.setItem(AUTH_USER_CACHE_KEY, JSON.stringify(user))
                } else {
                    window.localStorage.removeItem(AUTH_USER_CACHE_KEY)
                }
            } catch (_) {
                // Ignore storage quota/private-mode failures.
            }
        },
        hasSessionHint() {
            if (typeof window === 'undefined') return false

            const authHint = window.localStorage.getItem('epnmls_auth_hint') === '1'
            const hasCachedUser = !!this.cachedUser()
            const hasXsrfCookie = document.cookie.split(';').some((cookie) =>
                cookie.trim().startsWith('XSRF-TOKEN=')
            )

            return authHint || hasCachedUser || hasXsrfCookie
        },
        markSessionHint(enabled = true) {
            if (typeof window === 'undefined') return

            if (enabled) {
                window.localStorage.setItem('epnmls_auth_hint', '1')
            } else {
                window.localStorage.removeItem('epnmls_auth_hint')
            }
        },
        async fetchUser(options = {}) {
            const { force = false } = options

            if (!force && !this.hasSessionHint()) {
                this.user = null
                this.loading = false
                return null
            }

            try {
                const { data } = await client.get('/user')
                this.user = data
                this.cacheUser(data)
                this.markSessionHint(true)
                return data
            } catch (error) {
                const networkFailed = !error.response
                const cached = this.cachedUser()

                if (networkFailed && cached) {
                    this.user = cached
                    this.markSessionHint(true)
                    return cached
                }

                this.user = null
                this.cacheUser(null)
                this.markSessionHint(false)
                return null
            } finally {
                this.loading = false
            }
        },
        async login(email, password, remember = false) {
            await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
            await client.post('/login', { email, password, remember })
            this.markSessionHint(true)
            await this.fetchUser({ force: true })
        },
        async logout() {
            await client.post('/logout')
            this.user = null
            this.cacheUser(null)
            this.markSessionHint(false)
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_API_CACHE' })
            }
        },
        clearUser() {
            this.user = null
            this.cacheUser(null)
            this.markSessionHint(false)
        },
    },
})
