/**
 * SERVICE DE CACHE INTELLIGENT - Portail RH PNMLS
 *
 * Gestion intelligente du cache pour les départements et agents
 * avec stratégies de fraîcheur et fallback offline automatique
 */

import offlineStorage from './offlineStorage'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import { debugLog, reportError } from '@/utils/logger'

class CacheService {
    constructor() {
        this.isOnline = navigator.onLine
        this.ui = null
        this.initNetworkListener()
    }

    initNetworkListener() {
        window.addEventListener('online', () => {
            this.isOnline = true
            debugLog('🌐 Connexion rétablie - Mode online')
            this.notifyUser('Connexion rétablie! Synchronisation...', 'success')
        })

        window.addEventListener('offline', () => {
            this.isOnline = false
            debugLog('📱 Mode offline activé')
            this.notifyUser('Mode offline - Les données sont sauvegardées localement', 'warning')
        })
    }

    notifyUser(message, type = 'info') {
        if (!this.ui) {
            // Import paresseux pour éviter les dépendances circulaires
            this.ui = useUiStore()
        }
        this.ui?.addToast(message, type, 4000)
    }

    /**
     * DÉPARTEMENTS : Récupération avec cache intelligent
     */
    async getDepartments(forceRefresh = false) {
        debugLog('📂 Récupération départements...')

        try {
            if (!forceRefresh) {
                // 1. Vérifier le cache local d'abord
                const cached = await offlineStorage.getCachedDepartments()
                const cacheAge = await this.getCacheAge('departments')

                // Cache valide pendant 24h en ligne, toujours valide hors ligne
                const maxAge = this.isOnline ? 24 * 60 * 60 * 1000 : Infinity

                if (cached.length > 0 && cacheAge < maxAge) {
                    debugLog(`✅ Départements depuis cache (${cacheAge}h)`)

                    // Mise à jour en arrière-plan si en ligne
                    if (this.isOnline && cacheAge > 60 * 60 * 1000) { // 1h
                        this.refreshDepartmentsBackground()
                    }

                    return cached
                }
            }

            // 2. Si pas de cache ou forcé, aller chercher en ligne
            if (!this.isOnline) {
                const cached = await offlineStorage.getCachedDepartments()
                if (cached.length > 0) {
                    debugLog('📱 Départements depuis cache (hors ligne)')
                    return cached
                }

                this.notifyUser('Aucune donnée en cache - Connexion requise', 'warning')
                return []
            }

            // 3. Récupération depuis l'API
            const response = await this.fetchDepartmentsFromAPI()

            // 4. Mise en cache si succès
            if (response.length > 0) {
                await offlineStorage.cacheDepartments(response)
                debugLog('✅ Départements mis en cache')
            }

            return response

        } catch (error) {
            reportError('❌ Erreur récupération départements:', error)

            // Fallback sur le cache en cas d'erreur
            const cached = await offlineStorage.getCachedDepartments()
            if (cached.length > 0) {
                debugLog('📱 Fallback: départements depuis cache')
                this.notifyUser('Données depuis le cache local', 'info')
                return cached
            }

            this.notifyUser('Impossible de récupérer les départements', 'danger')
            return []
        }
    }

    async fetchDepartmentsFromAPI() {
        try {
            const { data } = await client.get('/agents/form-options')
            return data.departments || []
        } catch (error) {
            // Essayer aussi une route alternative
            const { data } = await client.get('/departments')
            return data || []
        }
    }

    async refreshDepartmentsBackground() {
        try {
            debugLog('🔄 Mise à jour départements en arrière-plan')
            const fresh = await this.fetchDepartmentsFromAPI()

            if (fresh.length > 0) {
                await offlineStorage.cacheDepartments(fresh)
                this.notifyUser('Départements mis à jour', 'success', 2000)
            }
        } catch (error) {
            debugLog('⚠️ Échec mise à jour arrière-plan départements:', error.message)
        }
    }

    /**
     * AGENTS : Récupération par département avec cache intelligent
     */
    async getAgentsByDepartment(departmentId, forceRefresh = false) {
        debugLog(`👥 Récupération agents département ${departmentId}`)

        if (!departmentId) return []

        try {
            if (!forceRefresh) {
                // 1. Vérifier cache local
                const cached = await offlineStorage.getCachedAgents(departmentId)
                const cacheKey = `agents_${departmentId}`
                const cacheAge = await this.getCacheAge(cacheKey)

                // Cache valide 6h en ligne, toujours valide hors ligne
                const maxAge = this.isOnline ? 6 * 60 * 60 * 1000 : Infinity

                if (cached.length > 0 && cacheAge < maxAge) {
                    debugLog(`✅ Agents depuis cache (${Math.round(cacheAge / 1000 / 60)}min)`)

                    // Mise à jour arrière-plan si nécessaire
                    if (this.isOnline && cacheAge > 30 * 60 * 1000) { // 30min
                        this.refreshAgentsBackground(departmentId)
                    }

                    return cached
                }
            }

            // 2. Récupération hors ligne
            if (!this.isOnline) {
                const cached = await offlineStorage.getCachedAgents(departmentId)
                if (cached.length > 0) {
                    debugLog('📱 Agents depuis cache (hors ligne)')
                    return cached
                }

                this.notifyUser(`Aucun agent en cache pour ce département`, 'warning')
                return []
            }

            // 3. Récupération API
            const response = await this.fetchAgentsFromAPI(departmentId)

            // 4. Mise en cache
            if (response.length > 0) {
                await offlineStorage.cacheAgents(departmentId, response)
                debugLog(`✅ ${response.length} agents mis en cache`)
            }

            return response

        } catch (error) {
            reportError(`❌ Erreur agents département ${departmentId}:`, error)

            // Fallback cache
            const cached = await offlineStorage.getCachedAgents(departmentId)
            if (cached.length > 0) {
                debugLog('📱 Fallback: agents depuis cache')
                this.notifyUser('Agents depuis le cache local', 'info')
                return cached
            }

            this.notifyUser('Impossible de récupérer les agents', 'danger')
            return []
        }
    }

    async fetchAgentsFromAPI(departmentId) {
        const { data } = await client.get(`/departments/${departmentId}/agents`)
        return data || []
    }

    async refreshAgentsBackground(departmentId) {
        try {
            debugLog(`🔄 Mise à jour agents département ${departmentId} en arrière-plan`)
            const fresh = await this.fetchAgentsFromAPI(departmentId)

            if (fresh.length > 0) {
                await offlineStorage.cacheAgents(departmentId, fresh)
                debugLog('✅ Agents mis à jour en arrière-plan')
            }
        } catch (error) {
            debugLog('⚠️ Échec mise à jour arrière-plan agents:', error.message)
        }
    }

    /**
     * Calcule l'âge du cache en millisecondes
     */
    async getCacheAge(key) {
        const timestamp = await offlineStorage.getSyncTimestamp(key)
        if (!timestamp) return Infinity

        return Date.now() - new Date(timestamp).getTime()
    }

    /**
     * PRÉCHARGEMENT INTELLIGENT
     * Précharge les données fréquemment utilisées
     */
    async preloadFrequentData() {
        if (!this.isOnline) return

        debugLog('🚀 Préchargement des données fréquentes...')

        try {
            // 1. Précharger les départements
            const departments = await this.getDepartments()

            // 2. Précharger les agents des 3 départements les plus actifs
            const activeDepts = await this.getMostActiveDepartments(3)

            const preloadPromises = activeDepts.map(dept =>
                this.getAgentsByDepartment(dept.id).catch(() => debugLog(`⚠️ Échec préchargement ${dept.nom}`))
            )

            await Promise.allSettled(preloadPromises)

            debugLog('✅ Préchargement terminé')
            this.notifyUser('Données préchargées pour usage offline', 'success', 3000)

        } catch (error) {
            debugLog('⚠️ Échec préchargement:', error.message)
        }
    }

    async getMostActiveDepartments(limit = 3) {
        // Logique : départements avec le plus de pointages récents
        // Pour l'instant, retourne les premiers départements
        const departments = await offlineStorage.getCachedDepartments()
        return departments.slice(0, limit)
    }

    /**
     * INDICATEUR DE FRAÎCHEUR DES DONNÉES
     */
    async getDataFreshness() {
        const departmentAge = await this.getCacheAge('departments')
        const generalAgentsAge = await this.getCacheAge('agents_last')

        return {
            departments: {
                cached: departmentAge !== Infinity,
                age: departmentAge,
                fresh: departmentAge < 24 * 60 * 60 * 1000, // 24h
                label: this.formatAge(departmentAge)
            },
            agents: {
                cached: generalAgentsAge !== Infinity,
                age: generalAgentsAge,
                fresh: generalAgentsAge < 6 * 60 * 60 * 1000, // 6h
                label: this.formatAge(generalAgentsAge)
            }
        }
    }

    formatAge(ageMs) {
        if (ageMs === Infinity) return 'Aucune'

        const minutes = Math.floor(ageMs / 1000 / 60)
        const hours = Math.floor(minutes / 60)
        const days = Math.floor(hours / 24)

        if (days > 0) return `${days}j${hours % 24}h`
        if (hours > 0) return `${hours}h${minutes % 60}m`
        return `${minutes}min`
    }

    /**
     * Force la mise à jour de toutes les données
     */
    async forceRefreshAll() {
        if (!this.isOnline) {
            this.notifyUser('Connexion requise pour actualiser', 'warning')
            return false
        }

        debugLog('🔄 Actualisation forcée de toutes les données')

        try {
            // Actualiser départements
            const departments = await this.getDepartments(true)

            // Actualiser agents des départements en cache
            const cachedDepts = await offlineStorage.getCachedDepartments()
            const refreshPromises = cachedDepts.map(dept =>
                this.getAgentsByDepartment(dept.id, true)
            )

            await Promise.allSettled(refreshPromises)

            this.notifyUser('Toutes les données actualisées', 'success')
            return true

        } catch (error) {
            reportError('❌ Erreur actualisation forcée:', error)
            this.notifyUser('Erreur lors de l\'actualisation', 'danger')
            return false
        }
    }

    /**
     * Statistiques du cache
     */
    async getCacheStats() {
        const stats = await offlineStorage.getStats()
        const freshness = await this.getDataFreshness()

        return {
            ...stats,
            freshness,
            networkStatus: this.isOnline ? 'online' : 'offline'
        }
    }
}

// Instance singleton
const cacheService = new CacheService()

export default cacheService