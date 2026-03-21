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
            const role = state.user?.role?.nom_role?.toLowerCase()
            return [
                'section nouvelle technologie',
                'chef section nouvelle technologie',
                'chef de section nouvelle technologie',
            ].includes(role)
        },
        hasAdminAccess(state) {
            return this.isRH || this.isAdminNT
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
        async login(email, password) {
            await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
            await client.post('/login', { email, password })
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
