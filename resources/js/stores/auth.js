import { defineStore } from 'pinia'
import axios from 'axios'
import client from '@/api/client'

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
            const role = state.user?.role?.nom_role?.toLowerCase()
            return [
                'section ressources humaines',
                'chef section rh',
                'rh national',
                'rh provincial',
            ].includes(role)
        },
        isAdminNT(state) {
            if (state.user?.is_super_admin) return true
            const role = state.user?.role?.nom_role?.toLowerCase()
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
            const role = state.user?.role?.nom_role?.toLowerCase()
            return role === 'sen'
        },
        isSEP(state) {
            const role = state.user?.role?.nom_role?.toLowerCase()
            return role === 'sep'
        },
        isDirecteur(state) {
            const role = state.user?.role?.nom_role?.toLowerCase()
            return ['directeur', 'directeur de département'].includes(role)
        },
        isAssistant(state) {
            const role = state.user?.role?.nom_role?.toLowerCase()
            return ['assistant', 'assistant de département'].includes(role)
        },
        isDepartement(state) {
            const role = state.user?.role?.nom_role?.toLowerCase() ?? ''
            return role === 'directeur' || role.startsWith('assistant')
        },
        isChefSectionRenforcement(state) {
            const role = state.user?.role?.nom_role?.toLowerCase() ?? ''
            return role === 'chef section renforcement'
        },
        isCAF(state) {
            const role = state.user?.role?.nom_role?.toLowerCase() ?? ''
            return [
                'cellule administrative et financière',
                'cellule administrative et financiere',
                'caf',
                'chef caf',
                'responsable caf',
            ].includes(role)
        },
        canManageDocsTravail(state) {
            if (state.user?.is_super_admin) return true
            return this.isSEN || this.isRH
        },
        hasAdminAccess(state) {
            if (state.user?.is_super_admin) return true
            return this.isRH || this.isAdminNT || this.isSEN
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
    },
    actions: {
        async fetchUser() {
            try {
                const { data } = await client.get('/user')
                this.user = data
            } catch {
                this.user = null
            } finally {
                this.loading = false
            }
        },
        async login(email, password, remember = false) {
            await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
            await client.post('/login', { email, password, remember })
            await this.fetchUser()
        },
        async logout() {
            await client.post('/logout')
            this.user = null
            // Clear cached API data so next user doesn't see stale data
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_API_CACHE' })
            }
        },
        clearUser() {
            this.user = null
        },
    },
})
