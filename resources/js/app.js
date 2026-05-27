import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { debugLog, reportError } from '@/utils/logger'
import { recoverFromAssetLoadFailure, registerRuntimeNoiseFilter } from '@/utils/runtimeNoiseFilter'

import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

debugLog('PWA: Service Worker disabled to prevent stale builds')
registerRuntimeNoiseFilter()

const BUILD_CACHE_VERSION = '2026-05-27-task-create-button-v1'
const BUILD_CACHE_KEY = 'pnmls_build_cache_version'

async function clearBuildCachesOnVersionChange() {
    if (typeof window === 'undefined') return false

    let currentVersion = null
    try {
        currentVersion = window.localStorage.getItem(BUILD_CACHE_KEY)
    } catch (_) {
        currentVersion = null
    }

    if (currentVersion === BUILD_CACHE_VERSION) {
        return false
    }

    const jobs = []

    if ('caches' in window) {
        jobs.push(
            window.caches.keys()
                .then((keys) => Promise.all(
                    keys
                        .filter((key) => /workbox|precache|runtime|vite|pnmls|e-pnmls/i.test(key))
                        .map((key) => window.caches.delete(key))
                ))
        )
    }

    if ('serviceWorker' in navigator && navigator.serviceWorker.getRegistrations) {
        jobs.push(
            navigator.serviceWorker.getRegistrations()
                .then((registrations) => Promise.all(
                    registrations.map((registration) => registration.unregister().catch(() => false))
                ))
        )
    }

    try {
        await Promise.allSettled(jobs)
        window.localStorage.setItem(BUILD_CACHE_KEY, BUILD_CACHE_VERSION)
    } catch (error) {
        reportError('PWA: Build cache cleanup failed:', error)
    }

    try {
        const reloadKey = `${BUILD_CACHE_KEY}:reloaded:${BUILD_CACHE_VERSION}`
        if (!window.sessionStorage.getItem(reloadKey)) {
            window.sessionStorage.setItem(reloadKey, '1')
            window.location.reload()
            return true
        }
    } catch (_) {
        return false
    }

    return false
}

async function clearLegacyServiceWorkers() {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) return

    try {
        const registrations = await navigator.serviceWorker.getRegistrations()

        await Promise.all(registrations.map((registration) => registration.unregister()))

        if ('caches' in window) {
            const cacheKeys = await caches.keys()
            await Promise.all(cacheKeys
                .filter((key) => /workbox|precache|runtime|vite|pnmls|e-pnmls/i.test(key))
                .map((key) => caches.delete(key)))
        }
    } catch (error) {
        reportError('PWA: Service worker cleanup failed:', error)
    }
}

async function prepareRuntimeCaches() {
    const reloadScheduled = await clearBuildCachesOnVersionChange()
    if (reloadScheduled) return

    await clearLegacyServiceWorkers()
}

prepareRuntimeCaches()

const app = createApp(App)
app.config.errorHandler = (error) => {
    if (recoverFromAssetLoadFailure(error)) return

    reportError('Erreur runtime Vue:', error)
}
app.use(createPinia())
app.use(router)
app.mount('#app')
