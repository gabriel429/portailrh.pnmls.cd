import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { registerSW } from 'virtual:pwa-register'
import router from './router'
import App from './App.vue'
import { debugLog, reportError } from '@/utils/logger'
import { recoverFromAssetLoadFailure, registerRuntimeNoiseFilter } from '@/utils/runtimeNoiseFilter'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

debugLog('PWA: Service Worker re-enabled after clean deployment')
registerRuntimeNoiseFilter()

async function clearLegacyRootServiceWorkers() {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) return

    try {
        const registrations = await navigator.serviceWorker.getRegistrations()
        let removedLegacyRegistration = false

        await Promise.all(registrations.map(async (registration) => {
            const scopePath = new URL(registration.scope).pathname
            const scriptPath = registration.active?.scriptURL
                ? new URL(registration.active.scriptURL).pathname
                : ''

            if (scopePath === '/' || scriptPath === '/sw.js') {
                removedLegacyRegistration = true
                await registration.unregister()
                return
            }

            await registration.update()
        }))

        if (removedLegacyRegistration && 'caches' in window) {
            const cacheKeys = await caches.keys()
            await Promise.all(cacheKeys
                .filter((key) => /workbox|precache|runtime/i.test(key))
                .map((key) => caches.delete(key)))
        }
    } catch (error) {
        reportError('PWA: Legacy service worker cleanup failed:', error)
    }
}

clearLegacyRootServiceWorkers()

registerSW({
    onNeedRefresh() {
        debugLog('PWA: New version available; update will apply on next navigation')
    },
    onOfflineReady() {
        debugLog('PWA: App ready for offline use')
    },
    onRegistered(registration) {
        registration?.update()
        debugLog('PWA: Service Worker registered successfully')
    },
    onRegisterError(error) {
        reportError('PWA: Service Worker registration failed:', error)
    },
})

const app = createApp(App)
app.config.errorHandler = (error) => {
    if (recoverFromAssetLoadFailure(error)) return

    reportError('Erreur runtime Vue:', error)
}
app.use(createPinia())
app.use(router)
app.mount('#app')
