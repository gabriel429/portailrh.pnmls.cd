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
            try {
                const { data } = await client.get('/notifications/unread-count')
                this.count = data.count
                this.recent = data.recent || []
            } catch {
                // silently fail
            }
        },
        startPolling() {
            this.fetch()
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
