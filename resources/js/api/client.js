import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import router from '@/router'
import { debugLog, reportError } from '@/utils/logger'

// Services offline
import offlineStorage from '@/services/offlineStorage'
import cacheService from '@/services/cacheService'

const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    timeout: 30000,
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

let csrfRefreshPromise = null
let authRedirectInProgress = false
let lastForbiddenToastAt = 0
let lastForbiddenToastKey = ''

function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms))
}

function isTransientNetworkError(error) {
    return !error.response && (
        error.code === 'ERR_NETWORK' ||
        error.code === 'ECONNABORTED' ||
        error.message === 'Network Error' ||
        error.message?.includes('timeout')
    )
}

function resolveFromRequestInterceptor(config, response) {
    config.adapter = () => Promise.resolve({
        ...response,
        config,
        headers: response.headers || {},
    })

    return config
}

function requestMethod(config) {
    return (config?.method || 'get').toLowerCase()
}

function requestUrl(config) {
    return String(config?.url || '')
}

function isBackgroundRequest(config) {
    const url = requestUrl(config)

    return Boolean(config?.silent || config?.skipForbiddenToast || config?.background)
        || url.includes('/notifications/unread-count')
        || url.includes('/sync/')
        || url.includes('/user-experience/bootstrap')
}

function shouldShowForbiddenToast(error) {
    const config = error.config || {}
    if (isBackgroundRequest(config)) return false
    if (config.showForbiddenToast === false) return false
    if (config.showForbiddenToast === true) return true

    const method = requestMethod(config)
    if (['get', 'head', 'options'].includes(method)) return false

    const key = `${method}:${requestUrl(config)}`
    const now = Date.now()
    if (key === lastForbiddenToastKey && now - lastForbiddenToastAt < 15000) {
        return false
    }

    lastForbiddenToastKey = key
    lastForbiddenToastAt = now
    return true
}

function redirectToLoginOnce() {
    const isLoginRoute = router.currentRoute.value?.name === 'login'
    if (isLoginRoute || authRedirectInProgress) return

    authRedirectInProgress = true
    const auth = useAuthStore()
    auth.clearUser()
    router.replace({ name: 'login' }).finally(() => {
        window.setTimeout(() => {
            authRedirectInProgress = false
        }, 1000)
    })
}

async function refreshCsrfCookie() {
    if (!csrfRefreshPromise) {
        csrfRefreshPromise = axios.get('/sanctum/csrf-cookie', {
            withCredentials: true,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).finally(() => {
            csrfRefreshPromise = null
        })
    }

    return csrfRefreshPromise
}

// Initialisation du stockage offline
let offlineInitialized = false
async function initializeOffline() {
    if (!offlineInitialized) {
        try {
            await offlineStorage.init()
            offlineInitialized = true
            debugLog('🗄️ Stockage offline initialisé')
        } catch (error) {
            reportError('❌ Erreur initialisation offline:', error)
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
                return resolveFromRequestInterceptor(config, {
                    data: {
                        id: tempId,
                        offline: true,
                        message: 'Pointage sauvegardé localement',
                        tempId
                    },
                    status: 201,
                    statusText: 'Created (Offline)',
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

                    return resolveFromRequestInterceptor(config, {
                        data: cachedData,
                        status: 200,
                        statusText: 'OK (Cache)',
                        fromCache: true
                    })
                }

            } catch (error) {
                reportError('❌ Erreur cache offline:', error)
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
                reportError('⚠️ Erreur mise en cache:', error)
            }
        }

        return response
    },
    async error => {
        if (axios.isCancel(error)) return Promise.reject(error)

        const method = (error.config?.method || '').toLowerCase()
        if (method === 'get' && isTransientNetworkError(error)) {
            error.config.__retryCount = error.config.__retryCount || 0

            if (error.config.__retryCount < 2) {
                error.config.__retryCount += 1
                await wait(error.config.__retryCount * 1500)
                return client(error.config)
            }
        }

        const status = error.response?.status

        if (status === 419 && !error.config?.__csrfRetried) {
            try {
                await refreshCsrfCookie()
                error.config.__csrfRetried = true
                return client(error.config)
            } catch (csrfError) {
                reportError('❌ Impossible de renouveler le jeton CSRF:', csrfError)
            }
        }

        if (status === 401) {
            redirectToLoginOnce()
        }

        if (status === 403 && shouldShowForbiddenToast(error)) {
            const ui = useUiStore()
            ui.addToast(error.response?.data?.message || 'Accès refusé - permissions insuffisantes', 'danger', 5000)
        }

        if (status === 419) {
            redirectToLoginOnce()
        }

        // FALLBACK CACHE EN CAS D'ERREUR RÉSEAU
        if (!navigator.onLine || isTransientNetworkError(error)) {
            const isDataFetch = error.config.url.includes('/agents/form-options') ||
                               error.config.url.includes('/departments')

            if (isDataFetch) {
                debugLog('🔄 Tentative fallback cache après erreur réseau...')

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
                        reportError('❌ Échec fallback cache:', cacheError)
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

    return await client.post('/pointages', {
        date_pointage: pointageData.date_pointage,
        pointages: [{
            agent_id: pointageData.agent_id,
            heure_entree: pointageData.heure_arrivee || pointageData.heure_entree || null,
            heure_sortie: pointageData.heure_depart || pointageData.heure_sortie || null,
            observations: pointageData.commentaire || pointageData.observations || null,
        }],
    })
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
