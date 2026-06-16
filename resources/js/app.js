import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { debugLog, reportError } from '@/utils/logger'
import { recoverFromAssetLoadFailure, registerRuntimeNoiseFilter } from '@/utils/runtimeNoiseFilter'

import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

debugLog('PWA: Service Worker enabled')
registerRuntimeNoiseFilter()

const BUILD_CACHE_VERSION = '2026-06-16-my-holiday-closures-v2'
const BUILD_CACHE_KEY = 'pnmls_build_cache_version'
const APP_SW_PATH = '/build/sw.js'

function emitPwaRuntimeEvent(name, detail = {}) {
    if (typeof window === 'undefined') return

    window.dispatchEvent(new CustomEvent(`epnmls:${name}`, { detail }))
}

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
                    registrations
                        .filter((registration) => !isCurrentAppServiceWorker(registration))
                        .map((registration) => registration.unregister().catch(() => false))
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

        await Promise.all(
            registrations
                .filter((registration) => !isCurrentAppServiceWorker(registration))
                .map((registration) => registration.unregister())
        )
    } catch (error) {
        reportError('PWA: Legacy service worker cleanup failed:', error)
    }
}

function isCurrentAppServiceWorker(registration) {
    const workers = [
        registration.active,
        registration.waiting,
        registration.installing,
    ].filter(Boolean)

    return workers.some((worker) => {
        try {
            return new URL(worker.scriptURL).pathname === APP_SW_PATH
        } catch (_) {
            return false
        }
    })
}

async function clearLegacyBuildCaches() {
    if (typeof window === 'undefined' || !('caches' in window)) return

    try {
        const cacheKeys = await caches.keys()
        await Promise.all(cacheKeys
            .filter((key) => /vite|e-pnmls/i.test(key))
            .map((key) => caches.delete(key)))
    } catch (error) {
        reportError('PWA: Legacy cache cleanup failed:', error)
    }
}

async function registerAppServiceWorker() {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) return

    try {
        const registration = await navigator.serviceWorker.register(APP_SW_PATH, { scope: '/' })
        watchServiceWorkerUpdate(registration)
        emitPwaRuntimeEvent('pwa-registration', { registration })
        registration.update?.().catch(() => {})
        debugLog('PWA: Service worker registered')
    } catch (error) {
        reportError('PWA: Service worker registration failed:', error)
    }
}

function watchServiceWorkerUpdate(registration) {
    if (!registration) return

    if (registration.waiting && navigator.serviceWorker.controller) {
        emitPwaRuntimeEvent('pwa-update-ready', { registration })
    }

    registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing
        if (!newWorker) return

        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                emitPwaRuntimeEvent('pwa-update-ready', { registration })
            }
        })
    })
}

async function prepareRuntimeCaches() {
    const reloadScheduled = await clearBuildCachesOnVersionChange()
    if (reloadScheduled) return

    await clearLegacyServiceWorkers()
    await clearLegacyBuildCaches()
    await registerAppServiceWorker()
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
