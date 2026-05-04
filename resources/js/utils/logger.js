const isDev = import.meta.env.DEV

function getConsoleMethod(method) {
    if (typeof console === 'undefined') {
        return () => {}
    }

    return console[method]?.bind(console) ?? console.log.bind(console)
}

export function debugLog(...args) {
    if (!isDev) return
    getConsoleMethod('log')(...args)
}

export function reportError(...args) {
    getConsoleMethod('error')(...args)
}
