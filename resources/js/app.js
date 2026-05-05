import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { registerSW } from 'virtual:pwa-register'
import router from './router'
import App from './App.vue'
import { debugLog, reportError } from '@/utils/logger'
import { registerRuntimeNoiseFilter } from '@/utils/runtimeNoiseFilter'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import '@fortawesome/fontawesome-free/css/all.min.css'
import '../css/app.css'

debugLog('PWA: Service Worker re-enabled after clean deployment')
registerRuntimeNoiseFilter()

registerSW({
    onNeedRefresh() {
        debugLog('PWA: New version available; update will apply on next navigation')
    },
    onOfflineReady() {
        debugLog('PWA: App ready for offline use')
    },
    onRegistered() {
        debugLog('PWA: Service Worker registered successfully')
    },
    onRegisterError(error) {
        reportError('PWA: Service Worker registration failed:', error)
    },
})

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
