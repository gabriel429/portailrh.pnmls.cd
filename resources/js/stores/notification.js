import { defineStore } from 'pinia'
import client from '@/api/client'

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        count: 0,
        recent: [],
        polling: null,
        fetching: false,
        paused: false,
    }),
    actions: {
        async fetch() {
            if (this.fetching || this.paused) {
                return
            }

            if (typeof navigator !== 'undefined' && !navigator.onLine) {
                return
            }

            this.fetching = true

            try {
                const { data } = await client.get('/notifications/unread-count', {
                    background: true,
                    skipForbiddenToast: true,
                    timeout: 12000,
                })
                this.count = data.count
                this.recent = data.recent || []
            } catch (error) {
                if ([401, 403, 419].includes(error.response?.status)) {
                    this.stopPolling()
                    this.count = 0
                    this.recent = []
                }
            } finally {
                this.fetching = false
            }
        },
        startPolling({ paused = false } = {}) {
            this.paused = paused
            if (paused) {
                this.stopPolling()
                return
            }

            if (this.polling) return
            if (typeof navigator === 'undefined' || navigator.onLine) {
                this.fetch()
            }
            this.polling = setInterval(() => {
                if (!this.paused && navigator.onLine) this.fetch()
            }, 30000)
        },
        stopPolling() {
            if (this.polling) {
                clearInterval(this.polling)
                this.polling = null
            }
        },
        async markAllRead() {
            await client.post('/notifications/mark-all-read')
            this.count = 0
            this.recent = this.recent.map(n => ({ ...n, lu: true }))
        },
    },
})
