const IGNORED_MESSAGES = [
    'a listener indicated an asynchronous response by returning true, but the message channel closed before a response was received',
    'could not establish connection. receiving end does not exist',
    'the page keeping the extension port is moved into back/forward cache, so the message channel is closed',
]

const ASSET_LOAD_MESSAGES = [
    'failed to fetch dynamically imported module',
    'error loading dynamically imported module',
    'dynamically imported module',
    'importing a module script failed',
    'failed to load module script',
    'loading chunk',
    'loading css chunk',
    'chunkloaderror',
    'unable to preload css',
    'ns_error_corrupted_content',
    'disallowed mime type',
    'type mime interdit',
    'expected a javascript module script',
    'strict mime type checking',
]

const BUILD_ASSET_PATTERN = /\/build\/assets\/[^"'()\s]+\.(js|css)\b/i

function shouldIgnoreMessage(message) {
    if (!message) return false

    const normalized = String(message).toLowerCase()
    return IGNORED_MESSAGES.some((pattern) => normalized.includes(pattern))
}

function shouldRecoverFromAssetLoadMessage(message) {
    if (!message) return false

    const normalized = String(message).toLowerCase()
    return ASSET_LOAD_MESSAGES.some((pattern) => normalized.includes(pattern)) ||
        (BUILD_ASSET_PATTERN.test(normalized) && /\b(failed|error|blocked|mime|module|corrupted)\b/.test(normalized))
}

function messageFromError(error) {
    return error?.message ?? error?.reason?.message ?? error?.toString?.() ?? ''
}

async function resetRuntimeAssetCaches() {
    const jobs = []

    if ('caches' in window) {
        jobs.push(
            window.caches.keys()
                .then((keys) => Promise.all(
                    keys
                        .filter((key) => /workbox|precache|vite|pnmls|e-pnmls/i.test(key))
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

    await Promise.allSettled(jobs)
}

function reloadWithFreshAssets(path) {
    let done = false
    const currentPath = `${window.location.pathname}${window.location.search}${window.location.hash}`
    const reload = () => {
        if (done) return
        done = true

        if (path === currentPath) {
            window.location.reload()
            return
        }

        window.location.replace(path)
    }

    window.setTimeout(reload, 800)
    resetRuntimeAssetCaches().finally(reload)
}

export function recoverFromAssetLoadFailure(error, targetPath = null) {
    if (typeof window === 'undefined') {
        return false
    }

    if (!shouldRecoverFromAssetLoadMessage(messageFromError(error))) {
        return false
    }

    const path = targetPath || `${window.location.pathname}${window.location.search}${window.location.hash}`
    const reloadKey = `asset_reload:${path}`
    const lastReload = Number(window.sessionStorage.getItem(reloadKey) || 0)
    const now = Date.now()

    if (now - lastReload < 10000) {
        return false
    }

    window.sessionStorage.setItem(reloadKey, String(now))
    reloadWithFreshAssets(path)
    return true
}

export function registerRuntimeNoiseFilter() {
    if (typeof window === 'undefined') {
        return
    }

    window.addEventListener('vite:preloadError', (event) => {
        if (recoverFromAssetLoadFailure(event.payload)) {
            event.preventDefault()
        }
    }, true)

    window.addEventListener('unhandledrejection', (event) => {
        const reasonMessage =
            event.reason?.message ??
            event.reason?.toString?.() ??
            ''

        if (recoverFromAssetLoadFailure(event.reason)) {
            event.preventDefault()
            return
        }

        if (shouldIgnoreMessage(reasonMessage)) {
            event.preventDefault()
        }
    }, true)

    window.addEventListener('error', (event) => {
        if (recoverFromAssetLoadFailure(event.error ?? event.message)) {
            event.preventDefault()
            return
        }

        if (shouldIgnoreMessage(event.message)) {
            event.preventDefault()
        }
    }, true)
}
