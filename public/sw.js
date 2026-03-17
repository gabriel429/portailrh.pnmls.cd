const CACHE_NAME = 'pnmls-rh-v1';
const OFFLINE_URL = '/offline.html';

// Resources to pre-cache
const PRE_CACHE = [
    OFFLINE_URL,
    '/images/logo-pnmls.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Install: pre-cache essential resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRE_CACHE))
            .then(() => self.skipWaiting())
    );
});

// Activate: clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch: network-first strategy for pages, cache-first for assets
self.addEventListener('fetch', event => {
    const { request } = event;

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Skip API and auth-related requests (never cache these)
    const url = new URL(request.url);
    if (url.pathname.startsWith('/api/') ||
        url.pathname.includes('deploy_') ||
        url.pathname.includes('csrf') ||
        url.pathname.includes('logout')) {
        return;
    }

    // For navigation requests: network first, fallback to offline page
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache successful page responses
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                    return response;
                })
                .catch(() => caches.match(request).then(cached => cached || caches.match(OFFLINE_URL)))
        );
        return;
    }

    // For static assets (images, CSS, JS): cache first, then network
    if (request.destination === 'image' ||
        request.destination === 'style' ||
        request.destination === 'font' ||
        url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff2?|ttf|eot)$/)) {
        event.respondWith(
            caches.match(request).then(cached => {
                if (cached) return cached;
                return fetch(request).then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                    return response;
                });
            })
        );
        return;
    }

    // Default: network first
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
