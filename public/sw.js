// Version 2026-05-06-forum-reactions-cache: retire les anciens service workers racine qui peuvent garder des builds obsoletes.
self.addEventListener('install', () => {
    self.skipWaiting()
})

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        if ('caches' in self) {
            const keys = await caches.keys()
            await Promise.all(keys.map((key) => caches.delete(key)))
        }

        await self.clients.claim()
        await self.registration.unregister()

        const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        for (const client of clients) {
            client.navigate(client.url)
        }
    })())
})

self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request))
})
