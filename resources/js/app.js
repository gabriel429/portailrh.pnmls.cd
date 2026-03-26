import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

// PWA Service Worker Registration with Error Handling
import { registerSW } from 'virtual:pwa-register'

const updateSW = registerSW({
    onNeedRefresh() {
        // Force immediate update to fix cache issues
        console.log('New content available - updating...')
        updateSW(true)
    },
    onOfflineReady() {
        console.log('App ready to work offline')
    },
    onRegistered(registration) {
        console.log('SW Registered: ', registration)

        // Check for updates every 60 seconds when online
        if (registration) {
            setInterval(() => {
                if (navigator.onLine) {
                    registration.update()
                }
            }, 60000)
        }
    },
    onRegisterError(error) {
        console.error('SW registration error:', error)

        // If registration fails, try to cleanup old cache
        if ('caches' in window) {
            caches.keys().then(cacheNames => {
                cacheNames.forEach(cacheName => {
                    if (cacheName.includes('workbox') || cacheName.includes('pwa')) {
                        caches.delete(cacheName)
                    }
                })
            })
        }
    }
})

// Handle service worker errors globally
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.ready.then(registration => {
            // Listen for SW errors and force reload if needed
            registration.addEventListener('message', event => {
                if (event.data && event.data.type === 'CACHE_ERROR') {
                    console.warn('Cache error detected, clearing cache...')
                    caches.keys().then(cacheNames => {
                        Promise.all(
                            cacheNames.map(cacheName => caches.delete(cacheName))
                        ).then(() => {
                            window.location.reload()
                        })
                    })
                }
            })
        }).catch(error => {
            console.error('Service Worker ready error:', error)
        })
    })
}

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
