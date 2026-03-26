import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

// PWA Service Worker Registration
import { registerSW } from 'virtual:pwa-register'

const updateSW = registerSW({
    onNeedRefresh() {
        // Show a prompt to user for update
        if (confirm('New content available. Reload?')) {
            updateSW(true)
        }
    },
    onOfflineReady() {
        console.log('App ready to work offline')
    },
    onRegistered(registration) {
        console.log('SW Registered: ', registration)
    },
    onRegisterError(error) {
        console.log('SW registration error', error)
    }
})

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
