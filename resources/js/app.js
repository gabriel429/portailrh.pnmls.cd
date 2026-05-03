import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { registerSW } from 'virtual:pwa-register'
import router from './router'
import App from './App.vue'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

console.log('PWA: Service Worker re-enabled after clean deployment')

registerSW({
    onNeedRefresh() {
        console.log('PWA: New version available; update will apply on next navigation')
    },
    onOfflineReady() {
        console.log('PWA: App ready for offline use')
    },
    onRegistered() {
        console.log('PWA: Service Worker registered successfully')
    },
    onRegisterError(error) {
        console.error('PWA: Service Worker registration failed:', error)
    },
})

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
