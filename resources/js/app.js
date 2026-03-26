import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

// Initialize Offline Services for PWA functionality
import './services/offlineStorage'
import './services/cacheService'
import './services/syncService'

console.log('🗄️ Offline services initialized for PWA pointage system')

// Progressive Web App Setup - Service Worker Re-enabled
console.log('🚀 PWA: Service Worker re-enabled after clean deployment')

// Service Worker Registration with proper error handling
import { registerSW } from 'virtual:pwa-register'

const updateSW = registerSW({
    onNeedRefresh() {
        console.log('📱 PWA: New version available, updating...')
        updateSW(true)
    },
    onOfflineReady() {
        console.log('📱 PWA: App ready for offline use')
    },
    onRegistered(r) {
        console.log('✅ PWA: Service Worker registered successfully')
    },
    onRegisterError(error) {
        console.error('❌ PWA: Service Worker registration failed:', error)
    }
})

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
