import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

client.interceptors.response.use(
    response => response,
    error => {
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
