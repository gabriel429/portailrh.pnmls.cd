import { defineStore } from 'pinia'
import client from '@/api/client'

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        count: 0,
        recent: [],
        polling: null,
    }),
    actions: {
        async fetch() {
            if (typeof navigator !== 'undefined' && !navigator.onLine) {
                return
            }

            try {
                const { data } = await client.get('/notifications/unread-count')
                this.count = data.count
                this.recent = data.recent || []
            } catch (error) {
                if ([401, 419].includes(error.response?.status)) {
                    this.stopPolling()
                    this.count = 0
                    this.recent = []
                }
            }
        },
        startPolling() {
            if (this.polling) return
            if (typeof navigator === 'undefined' || navigator.onLine) {
                this.fetch()
            }
            this.polling = setInterval(() => {
                if (navigator.onLine) this.fetch()
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
