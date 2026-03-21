import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import router from '@/router'

const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

// ── Request interceptor: block write operations when offline ──
client.interceptors.request.use(
    config => {
        const isWriteMethod = ['post', 'put', 'patch', 'delete'].includes(config.method)
        if (isWriteMethod && !navigator.onLine) {
            const ui = useUiStore()
            ui.addToast('Cette action necessite une connexion internet', 'danger', 5000)
            return Promise.reject(new axios.Cancel('Hors ligne'))
        }
        return config
    },
    error => Promise.reject(error)
)

// ── Response interceptor: handle auth errors ──
client.interceptors.response.use(
    response => response,
    error => {
        if (axios.isCancel(error)) return Promise.reject(error)

        if (error.response?.status === 401) {
            const auth = useAuthStore()
            auth.clearUser()
            router.push({ name: 'login' })
        }
        if (error.response?.status === 419) {
            window.location.reload()
        }
        return Promise.reject(error)
    }
)

export default client
