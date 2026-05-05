const IGNORED_MESSAGES = [
    'a listener indicated an asynchronous response by returning true, but the message channel closed before a response was received',
    'could not establish connection. receiving end does not exist',
    'the page keeping the extension port is moved into back/forward cache, so the message channel is closed',
]

function shouldIgnoreMessage(message) {
    if (!message) return false

    const normalized = String(message).toLowerCase()
    return IGNORED_MESSAGES.some((pattern) => normalized.includes(pattern))
}

export function registerRuntimeNoiseFilter() {
    if (import.meta.env.DEV || typeof window === 'undefined') {
        return
    }

    window.addEventListener('unhandledrejection', (event) => {
        const reasonMessage =
            event.reason?.message ??
            event.reason?.toString?.() ??
            ''

        if (shouldIgnoreMessage(reasonMessage)) {
            event.preventDefault()
        }
    })

    window.addEventListener('error', (event) => {
        if (shouldIgnoreMessage(event.message)) {
            event.preventDefault()
        }
    })
}
