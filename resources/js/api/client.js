import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import router from '@/router'

// Services offline
import offlineStorage from '@/services/offlineStorage'
import cacheService from '@/services/cacheService'

const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

// Initialisation du stockage offline
let offlineInitialized = false
async function initializeOffline() {
    if (!offlineInitialized) {
        try {
            await offlineStorage.init()
            offlineInitialized = true
            console.log('🗄️ Stockage offline initialisé')
        } catch (error) {
            console.error('❌ Erreur initialisation offline:', error)
        }
    }
}

// ── Request interceptor: gestion offline intelligente ──
client.interceptors.request.use(
    async config => {
        await initializeOffline()

        const isWriteMethod = ['post', 'put', 'patch', 'delete'].includes(config.method)
        const isPointageOperation = config.url.includes('/pointages')
        const isDataFetchOperation = config.url.includes('/agents/form-options') ||
                                   config.url.includes('/departments')

        // GESTION OFFLINE POUR LES POINTAGES
        if (isPointageOperation && isWriteMethod && !navigator.onLine) {
            const ui = useUiStore()

            try {
                // Sauvegarder le pointage localement
                const tempId = await offlineStorage.addPointageToQueue(config.data)

                ui.addToast('📱 Pointage sauvegardé localement (sera synchronisé)', 'success', 4000)

                // Simuler une réponse réussie
                return Promise.resolve({
                    data: {
                        id: tempId,
                        offline: true,
                        message: 'Pointage sauvegardé localement',
                        tempId
                    },
                    status: 201,
                    statusText: 'Created (Offline)',
                    config,
                    headers: {}
                })

            } catch (error) {
                ui.addToast('❌ Erreur sauvegarde locale du pointage', 'danger')
                return Promise.reject(new Error('Échec sauvegarde offline'))
            }
        }

        // GESTION OFFLINE POUR LES DONNÉES (lecture)
        if (isDataFetchOperation && !navigator.onLine) {
            const ui = useUiStore()

            try {
                let cachedData = null

                if (config.url.includes('/agents/form-options')) {
                    const departments = await cacheService.getDepartments()
                    cachedData = { departments }
                } else if (config.url.includes('/departments')) {
                    cachedData = await cacheService.getDepartments()
                }

                if (cachedData) {
                    ui.addToast('📱 Données depuis le cache local', 'info', 3000)

                    return Promise.resolve({
                        data: cachedData,
                        status: 200,
                        statusText: 'OK (Cache)',
                        config,
                        headers: {},
                        fromCache: true
                    })
                }

            } catch (error) {
                console.error('❌ Erreur cache offline:', error)
            }
        }

        // BLOCAGE GÉNÉRAL POUR AUTRES ÉCRITURES HORS LIGNE
        if (isWriteMethod && !navigator.onLine && !isPointageOperation) {
            const ui = useUiStore()
            ui.addToast('📡 Cette action nécessite une connexion internet', 'warning', 5000)
            return Promise.reject(new axios.Cancel('Hors ligne - opération bloquée'))
        }

        return config
    },
    error => Promise.reject(error)
)

// ── Response interceptor: cache automatique et gestion auth ──
client.interceptors.response.use(
    async response => {
        // MISE EN CACHE AUTOMATIQUE DES DONNÉES
        if (navigator.onLine && !response.fromCache) {
            try {
                // Cache des départements
                if (response.config.url.includes('/agents/form-options') && response.data.departments) {
                    await cacheService.cacheDepartments?.(response.data.departments)
                }

                if (response.config.url.includes('/departments') && Array.isArray(response.data)) {
                    await cacheService.cacheDepartments?.(response.data)
                }

                // Cache des agents par département
                const deptAgentsMatch = response.config.url.match(/\/departments\/(\d+)\/agents/)
                if (deptAgentsMatch && Array.isArray(response.data)) {
                    const departmentId = parseInt(deptAgentsMatch[1])
                    await cacheService.cacheAgents?.(departmentId, response.data)
                }

            } catch (error) {
                console.error('⚠️ Erreur mise en cache:', error)
            }
        }

        return response
    },
    error => {
        if (axios.isCancel(error)) return Promise.reject(error)

        // Gestion des erreurs d'authentification
        // Avoid double-redirect: the router guard already redirects unauthenticated users
        const isLoginRoute = router.currentRoute.value?.name === 'login'

        if (error.response?.status === 401 && !isLoginRoute) {
            const auth = useAuthStore()
            auth.clearUser()
            router.replace({ name: 'login' })
        }

        if (error.response?.status === 403) {
            const ui = useUiStore()
            ui.addToast('❌ Accès refusé - permissions insuffisantes', 'danger', 5000)
        }

        if (error.response?.status === 419 && !isLoginRoute) {
            const auth = useAuthStore()
            auth.clearUser()
            router.replace({ name: 'login' })
        }

        // FALLBACK CACHE EN CAS D'ERREUR RÉSEAU
        if (!navigator.onLine || error.code === 'NETWORK_ERROR') {
            const isDataFetch = error.config.url.includes('/agents/form-options') ||
                               error.config.url.includes('/departments')

            if (isDataFetch) {
                console.log('🔄 Tentative fallback cache après erreur réseau...')

                // Essayer de récupérer depuis le cache
                return initializeOffline().then(async () => {
                    try {
                        let cachedData = null

                        if (error.config.url.includes('/form-options')) {
                            const departments = await cacheService.getDepartments()
                            cachedData = { departments }
                        } else if (error.config.url.includes('/departments')) {
                            cachedData = await cacheService.getDepartments()
                        }

                        if (cachedData) {
                            const ui = useUiStore()
                            ui.addToast('📱 Fallback: données depuis le cache', 'warning', 4000)

                            return {
                                data: cachedData,
                                status: 200,
                                statusText: 'OK (Cache Fallback)',
                                config: error.config,
                                headers: {},
                                fromCache: true,
                                fallback: true
                            }
                        }
                    } catch (cacheError) {
                        console.error('❌ Échec fallback cache:', cacheError)
                    }

                    // Si pas de cache, propager l'erreur originale
                    return Promise.reject(error)
                })
            }
        }

        return Promise.reject(error)
    }
)

// ── Extensions pour les opérations offline ──

/**
 * Récupère les départements avec cache intelligent
 */
client.getDepartments = async (forceRefresh = false) => {
    await initializeOffline()
    return await cacheService.getDepartments(forceRefresh)
}

/**
 * Récupère les agents d'un département avec cache
 */
client.getAgentsByDepartment = async (departmentId, forceRefresh = false) => {
    await initializeOffline()
    return await cacheService.getAgentsByDepartment(departmentId, forceRefresh)
}

/**
 * Créer un pointage avec support offline
 */
client.createPointage = async (pointageData) => {
    if (!navigator.onLine) {
        await initializeOffline()
        const tempId = await offlineStorage.addPointageToQueue(pointageData)

        return {
            data: {
                id: tempId,
                offline: true,
                message: 'Pointage sauvegardé localement',
                tempId
            },
            offline: true
        }
    }

    return await client.post('/pointages', pointageData)
}

/**
 * Statistiques du système offline
 */
client.getOfflineStats = async () => {
    await initializeOffline()
    return await cacheService.getCacheStats()
}

/**
 * Forcer la synchronisation
 */
client.forceSync = async () => {
    const { default: syncService } = await import('@/services/syncService')
    return await syncService.forceSyncAll()
}

/**
 * Précharger les données pour usage offline
 */
client.preloadOfflineData = async () => {
    await initializeOffline()
    return await cacheService.preloadFrequentData()
}

export default client
