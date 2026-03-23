const CACHE_VERSION = 9;
const CACHE_NAME = 'pnmls-rh-v' + CACHE_VERSION;
const API_CACHE_NAME = 'pnmls-api-v1';
const OFFLINE_URL = '/offline.html';

// App shell: everything needed to run offline
const APP_SHELL = [
    OFFLINE_URL,
    '/',
    '/dashboard',
    '/manifest.json',
    '/images/logo-pnmls.png',
    '/images/pnmls.jpeg',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// URLs to NEVER cache
const NO_CACHE = ['deploy_', 'csrf', '/api/login', '/api/logout', '/sanctum', '/logout'];

function shouldCache(url) {
    const path = new URL(url).pathname;
    return !NO_CACHE.some(skip => path.includes(skip));
}

function isApiGetRequest(request) {
    if (request.method !== 'GET') return false;
    const url = new URL(request.url);
    return url.pathname.startsWith('/api/');
}

// ── Install: pre-cache app shell ──
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return Promise.allSettled(
                    APP_SHELL.map(url =>
                        cache.add(url).catch(() => console.log('Skip cache:', url))
                    )
                );
            })
            .then(() => self.skipWaiting())
    );
});

// ── Activate: clean old caches ──
self.addEventListener('activate', event => {
    const keepCaches = [CACHE_NAME, API_CACHE_NAME];
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(key => !keepCaches.includes(key)).map(key => caches.delete(key))
            )
        )
        .then(() => self.clients.claim())
    );
});

// ── Fetch strategies ──
self.addEventListener('fetch', event => {
    const { request } = event;

    // Skip non-GET requests (POST login, CSRF, etc.)
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // Skip requests that should never be cached
    if (!shouldCache(request.url)) return;

    // ── API GET requests: Network first → Cache fallback (for offline) ──
    if (isApiGetRequest(request)) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(API_CACHE_NAME).then(cache => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(request).then(cached => {
                        if (cached) return cached;
                        // No cache available: return a JSON 503 error
                        return new Response(
                            JSON.stringify({ message: 'Vous etes hors ligne et ces donnees ne sont pas disponibles en cache.' }),
                            { status: 503, headers: { 'Content-Type': 'application/json' } }
                        );
                    });
                })
        );
        return;
    }

    // ── Navigation (pages): Network first → Cache → Offline page ──
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(request)
                        .then(cached => cached || caches.match(OFFLINE_URL));
                })
        );
        return;
    }

    // ── Static assets (images, CSS, JS, fonts): Cache first → Network ──
    if (request.destination === 'image' ||
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'font' ||
        url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/)) {
        event.respondWith(
            caches.match(request).then(cached => {
                const fetchPromise = fetch(request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                    }
                    return response;
                }).catch(() => null);

                return cached || fetchPromise;
            })
        );
        return;
    }

    // ── Default: Network first → Cache fallback ──
    event.respondWith(
        fetch(request)
            .then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request))
    );
});

// ── Listen for messages from client ──
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data && event.data.type === 'CLEAR_API_CACHE') {
        caches.delete(API_CACHE_NAME);
    }
});
