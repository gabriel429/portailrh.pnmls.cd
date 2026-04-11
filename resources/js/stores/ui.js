import { defineStore } from 'pinia'

const DARK_KEY = 'epnmls_dark_mode'
const DARK_HOUR_START = 18
const DARK_HOUR_END   = 6   // 06h00 le lendemain

function isNightTime() {
    const h = new Date().getHours()
    return h >= DARK_HOUR_START || h < DARK_HOUR_END
}

function applyDark(enabled) {
    document.documentElement.classList.toggle('dark', enabled)
}

export const useUiStore = defineStore('ui', {
    state: () => ({
        toasts: [],
        sidebarOpen: true,
        darkMode: false,
        // null = auto (time-based) | true/false = forced by user
        _darkOverride: null,
    }),

    getters: {
        isDark: (state) => state.darkMode,
    },

    actions: {
        /** Appeler au montage de l'app pour initialiser le mode. */
        initDarkMode() {
            const stored = localStorage.getItem(DARK_KEY)
            if (stored !== null) {
                this._darkOverride = stored === 'true'
                this.darkMode = this._darkOverride
            } else {
                this._darkOverride = null
                this.darkMode = isNightTime()
            }
            applyDark(this.darkMode)

            // Vérifier toutes les minutes si le mode automatique doit basculer
            this._darkTimer = setInterval(() => {
                if (this._darkOverride === null) {
                    const night = isNightTime()
                    if (night !== this.darkMode) {
                        this.darkMode = night
                        applyDark(night)
                    }
                }
            }, 60_000)
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode
            this._darkOverride = this.darkMode
            localStorage.setItem(DARK_KEY, String(this.darkMode))
            applyDark(this.darkMode)
        },

        /** Revenir au mode automatique (basé sur l'heure). */
        resetDarkMode() {
            this._darkOverride = null
            localStorage.removeItem(DARK_KEY)
            this.darkMode = isNightTime()
            applyDark(this.darkMode)
        },

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
