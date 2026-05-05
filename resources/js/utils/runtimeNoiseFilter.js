const IGNORED_MESSAGES = [
    'a listener indicated an asynchronous response by returning true, but the message channel closed before a response was received',
    'could not establish connection. receiving end does not exist',
    'the page keeping the extension port is moved into back/forward cache, so the message channel is closed',
]

const ASSET_LOAD_MESSAGES = [
    'failed to fetch dynamically imported module',
    'error loading dynamically imported module',
    'importing a module script failed',
    'failed to load module script',
    'loading chunk',
    'loading css chunk',
    'chunkloaderror',
    'unable to preload css',
]

function shouldIgnoreMessage(message) {
    if (!message) return false

    const normalized = String(message).toLowerCase()
    return IGNORED_MESSAGES.some((pattern) => normalized.includes(pattern))
}

function shouldRecoverFromAssetLoadMessage(message) {
    if (!message) return false

    const normalized = String(message).toLowerCase()
    return ASSET_LOAD_MESSAGES.some((pattern) => normalized.includes(pattern))
}

function messageFromError(error) {
    return error?.message ?? error?.reason?.message ?? error?.toString?.() ?? ''
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
    window.location.assign(path)
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
