import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

// Progressive Web App Setup - Service Worker Disabled During Deployment
console.log('PWA: Service Worker temporarily disabled for clean deployment')

// Force cleanup of existing service workers and caches
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(registrations => {
        registrations.forEach(registration => {
            console.log('Unregistering old service worker...')
            registration.unregister()
        })
    })
}

// Clear all caches to prevent conflicts
if ('caches' in window) {
    caches.keys().then(cacheNames => {
        console.log('Clearing all caches:', cacheNames.length, 'caches')
        Promise.all(
            cacheNames.map(cacheName => {
                console.log('Deleting cache:', cacheName)
                return caches.delete(cacheName)
            })
        ).then(() => {
            console.log('All caches cleared successfully')
        })
    })
}

// TODO: Re-enable service worker after deployment is confirmed working:
// import { registerSW } from 'virtual:pwa-register'
// const updateSW = registerSW({ registerType: 'autoUpdate' })

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
