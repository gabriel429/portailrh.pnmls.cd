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

const BUILD_CACHE_VERSION = '2026-05-06-forum-comment-notifications-v1'
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

function registerCurrentServiceWorker() {
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
}

async function prepareRuntimeCaches() {
    const reloadScheduled = await clearBuildCachesOnVersionChange()
    if (reloadScheduled) return

    await clearLegacyRootServiceWorkers()
    registerCurrentServiceWorker()
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
