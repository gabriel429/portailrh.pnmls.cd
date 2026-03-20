import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
    state: () => ({
        toasts: [],
        sidebarOpen: true,
    }),
    actions: {
        addToast(message, type = 'success', duration = 5000) {
            const id = Date.now()
            this.toasts.push({ id, message, type })
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id)
            }, duration)
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id)
        },
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen
        },
    },
})
