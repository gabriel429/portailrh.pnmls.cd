/**
 * SYSTÈME DE STOCKAGE OFFLINE - Portail RH PNMLS
 *
 * Module IndexedDB pour gérer les données offline des pointages RH
 * Permet aux utilisateurs de travailler sans connexion internet
 *
 * Fonctionnalités:
 * - Cache intelligent départements/agents
 * - Queue des pointages en attente
 * - Synchronisation automatique
 * - Gestion des conflits
 */

import { debugLog, reportError } from '@/utils/logger'

const API_RESPONSE_MAX_AGE = 7 * 24 * 60 * 60 * 1000
const API_RESPONSE_RETENTION = 30 * 24 * 60 * 60 * 1000
const QUEUE_DEDUPE_WINDOW = 60 * 1000
const STUCK_SYNCING_MAX_AGE = 10 * 60 * 1000

function stableValue(value) {
    if (Array.isArray(value)) return value.map(stableValue)
    if (!value || typeof value !== 'object') return value

    return Object.keys(value).sort().reduce((result, key) => {
        if (key === 'client_operation_id') return result
        result[key] = stableValue(value[key])
        return result
    }, {})
}

function stableStringify(value) {
    try {
        return JSON.stringify(stableValue(value))
    } catch (_) {
        return String(value ?? '')
    }
}

function hashString(value) {
    let hash = 2166136261
    for (let index = 0; index < value.length; index++) {
        hash ^= value.charCodeAt(index)
        hash = Math.imul(hash, 16777619)
    }
    return (hash >>> 0).toString(36)
}

function localId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID()
    }

    return `local-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function operationDedupeKey({ userId, entity, operation, entityId, payload, request }) {
    return [
        userId || 'guest',
        entity || 'unknown',
        operation || 'unknown',
        entityId || '',
        request?.method || '',
        request?.url || '',
        hashString(stableStringify(payload)),
    ].join(':')
}

class OfflineStorage {
    constructor() {
        this.dbName = 'PortailRH_OfflineDB'
        this.dbVersion = 3
        this.db = null
    }

    /**
     * Initialise la base de données IndexedDB
     */
    async init() {
        if (this.db) return

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion)

            request.onerror = () => {
                reportError('❌ Erreur IndexedDB:', request.error)
                reject(request.error)
            }

            request.onsuccess = () => {
                this.db = request.result
                debugLog('✅ IndexedDB initialisé:', this.dbName)
                resolve()
            }

            request.onupgradeneeded = (event) => {
                const db = event.target.result

                // Store pour les départements
                if (!db.objectStoreNames.contains('departments')) {
                    const deptStore = db.createObjectStore('departments', { keyPath: 'id' })
                    deptStore.createIndex('nom', 'nom', { unique: false })
                    deptStore.createIndex('lastSync', 'lastSync', { unique: false })
                }

                // Store pour les agents
                if (!db.objectStoreNames.contains('agents')) {
                    const agentStore = db.createObjectStore('agents', { keyPath: 'id' })
                    agentStore.createIndex('department_id', 'department_id', { unique: false })
                    agentStore.createIndex('nom_complet', 'nom_complet', { unique: false })
                    agentStore.createIndex('lastSync', 'lastSync', { unique: false })
                }

                // Store pour la queue des pointages offline
                if (!db.objectStoreNames.contains('pointage_queue')) {
                    const queueStore = db.createObjectStore('pointage_queue', {
                        keyPath: 'tempId',
                        autoIncrement: true
                    })
                    queueStore.createIndex('date_pointage', 'date_pointage', { unique: false })
                    queueStore.createIndex('status', 'status', { unique: false })
                    queueStore.createIndex('created_at', 'created_at', { unique: false })
                }

                // Store pour les métadonnées de synchronisation
                if (!db.objectStoreNames.contains('sync_metadata')) {
                    db.createObjectStore('sync_metadata', { keyPath: 'key' })
                }

                // Données réservées à un compte local, sans mot de passe ni token.
                if (!db.objectStoreNames.contains('offline_sessions')) {
                    db.createObjectStore('offline_sessions', { keyPath: 'user_id' })
                }

                // Les agents affichés par le formulaire de pointage sont isolés par
                // utilisateur et périmètre afin de ne pas exposer le cache d'un autre compte.
                if (!db.objectStoreNames.contains('pointage_agents')) {
                    const pointageAgentStore = db.createObjectStore('pointage_agents', { keyPath: 'cache_key' })
                    pointageAgentStore.createIndex('user_id', 'user_id', { unique: false })
                    pointageAgentStore.createIndex('scope_key', 'scope_key', { unique: false })
                }

                // Queue générique pour les écritures offline. pointage_queue reste lu
                // pendant la migration afin de ne perdre aucune opération existante.
                if (!db.objectStoreNames.contains('sync_queue')) {
                    const syncQueueStore = db.createObjectStore('sync_queue', { keyPath: 'id' })
                    syncQueueStore.createIndex('user_id', 'user_id', { unique: false })
                    syncQueueStore.createIndex('status', 'status', { unique: false })
                    syncQueueStore.createIndex('entity', 'entity', { unique: false })
                    syncQueueStore.createIndex('created_at', 'created_at', { unique: false })
                    syncQueueStore.createIndex('client_operation_id', 'client_operation_id', { unique: true })
                }

                if (!db.objectStoreNames.contains('api_responses')) {
                    const apiResponseStore = db.createObjectStore('api_responses', { keyPath: 'cache_key' })
                    apiResponseStore.createIndex('user_id', 'user_id', { unique: false })
                    apiResponseStore.createIndex('cached_at', 'cached_at', { unique: false })
                }

                debugLog('🗄️ Tables IndexedDB créées')
            }
        })
    }

    /**
     * Cache les départements avec horodatage
     */
    async cacheDepartments(departments) {
        const transaction = this.db.transaction(['departments'], 'readwrite')
        const store = transaction.objectStore('departments')
        const timestamp = new Date().toISOString()

        const promises = departments.map(dept => {
            const enrichedDept = {
                ...dept,
                lastSync: timestamp,
                cached: true
            }
            return store.put(enrichedDept)
        })

        await Promise.all(promises)
        await transaction.complete
        debugLog(`✅ ${departments.length} départements mis en cache`)

        // Sauvegarder le timestamp de synchronisation
        await this.setSyncTimestamp('departments', timestamp)
    }

    /**
     * Cache les agents par département
     */
    async cacheAgents(departmentId, agents) {
        const transaction = this.db.transaction(['agents'], 'readwrite')
        const store = transaction.objectStore('agents')
        const timestamp = new Date().toISOString()

        const promises = agents.map(agent => {
            const enrichedAgent = {
                ...agent,
                department_id: departmentId,
                lastSync: timestamp,
                cached: true,
                // Ajouter des champs utiles pour le pointage offline
                nom_complet: `${agent.nom} ${agent.prenom}`.trim(),
                poste_display: agent.poste || 'Non défini'
            }
            return store.put(enrichedAgent)
        })

        await Promise.all(promises)
        await transaction.complete
        debugLog(`✅ ${agents.length} agents du département ${departmentId} mis en cache`)

        await this.setSyncTimestamp(`agents_${departmentId}`, timestamp)
    }

    /**
     * Récupère les départements du cache
     */
    async getCachedDepartments() {
        const transaction = this.db.transaction(['departments'], 'readonly')
        const store = transaction.objectStore('departments')

        return new Promise((resolve) => {
            const request = store.getAll()
            request.onsuccess = () => {
                const departments = request.result || []
                debugLog(`📂 ${departments.length} départements récupérés du cache`)
                resolve(departments)
            }
            request.onerror = () => resolve([])
        })
    }

    /**
     * Récupère les agents d'un département du cache
     */
    async getCachedAgents(departmentId) {
        const transaction = this.db.transaction(['agents'], 'readonly')
        const store = transaction.objectStore('agents')
        const index = store.index('department_id')

        return new Promise((resolve) => {
            const request = index.getAll(departmentId)
            request.onsuccess = () => {
                const agents = request.result || []
                debugLog(`👥 ${agents.length} agents du département ${departmentId} récupérés du cache`)
                resolve(agents)
            }
            request.onerror = () => resolve([])
        })
    }

    async cacheSession(user) {
        if (!user?.id) return
        await this.init()

        const transaction = this.db.transaction(['offline_sessions'], 'readwrite')
        transaction.objectStore('offline_sessions').put({
            user_id: user.id,
            user,
            cached_at: new Date().toISOString(),
            server_session_status: 'verified',
        })
    }

    async getCachedSession(userId) {
        if (!userId) return null
        await this.init()

        return new Promise((resolve) => {
            const request = this.db.transaction(['offline_sessions'], 'readonly')
                .objectStore('offline_sessions')
                .get(userId)
            request.onsuccess = () => resolve(request.result || null)
            request.onerror = () => resolve(null)
        })
    }

    async clearCachedSession(userId) {
        if (!userId) return
        await this.init()
        this.db.transaction(['offline_sessions'], 'readwrite')
            .objectStore('offline_sessions')
            .delete(userId)
    }

    async cacheApiResponse(userId, cacheKey, data, { maxAge = API_RESPONSE_MAX_AGE } = {}) {
        if (!userId || !cacheKey) return
        await this.init()

        const cachedAt = new Date()
        return new Promise((resolve, reject) => {
            const request = this.db.transaction(['api_responses'], 'readwrite')
                .objectStore('api_responses')
                .put({
                    cache_key: `${userId}:${cacheKey}`,
                    user_id: userId,
                    request_key: cacheKey,
                    data,
                    cached_at: cachedAt.toISOString(),
                    expires_at: new Date(cachedAt.getTime() + maxAge).toISOString(),
                })
            request.onsuccess = () => resolve()
            request.onerror = () => reject(request.error)
        })
    }

    async getCachedApiResponse(userId, cacheKey, { maxAge = API_RESPONSE_MAX_AGE, allowStale = true } = {}) {
        if (!userId || !cacheKey) return null
        await this.init()

        return new Promise((resolve) => {
            const request = this.db.transaction(['api_responses'], 'readonly')
                .objectStore('api_responses')
                .get(`${userId}:${cacheKey}`)
            request.onsuccess = () => {
                const cached = request.result || null
                if (!cached) {
                    resolve(null)
                    return
                }

                const cachedAt = new Date(cached.cached_at).getTime()
                const isStale = Number.isFinite(cachedAt) && maxAge ? Date.now() - cachedAt > maxAge : false
                if (isStale && !allowStale) {
                    resolve(null)
                    return
                }

                resolve({ ...cached, is_stale: isStale })
            }
            request.onerror = () => resolve(null)
        })
    }

    async clearApiResponses(userId) {
        if (!userId) return
        await this.init()

        return new Promise((resolve) => {
            const transaction = this.db.transaction(['api_responses'], 'readwrite')
            const index = transaction.objectStore('api_responses').index('user_id')
            const request = index.openKeyCursor(IDBKeyRange.only(userId))
            request.onsuccess = () => {
                const cursor = request.result
                if (!cursor) return
                transaction.objectStore('api_responses').delete(cursor.primaryKey)
                cursor.continue()
            }
            transaction.oncomplete = () => resolve()
            transaction.onerror = () => resolve()
        })
    }

    async clearPointageAgents(userId) {
        if (!userId) return
        await this.init()

        return new Promise((resolve) => {
            const transaction = this.db.transaction(['pointage_agents'], 'readwrite')
            const index = transaction.objectStore('pointage_agents').index('user_id')
            const request = index.openKeyCursor(IDBKeyRange.only(userId))
            request.onsuccess = () => {
                const cursor = request.result
                if (!cursor) return
                transaction.objectStore('pointage_agents').delete(cursor.primaryKey)
                cursor.continue()
            }
            transaction.oncomplete = () => resolve()
            transaction.onerror = () => resolve()
        })
    }

    async cachePointageAgents(userId, scopeKey, agents) {
        if (!userId || !scopeKey || !Array.isArray(agents)) return
        await this.init()

        const transaction = this.db.transaction(['pointage_agents'], 'readwrite')
        transaction.objectStore('pointage_agents').put({
            cache_key: `${userId}:${scopeKey}`,
            user_id: userId,
            scope_key: scopeKey,
            agents,
            cached_at: new Date().toISOString(),
        })
    }

    async getCachedPointageAgents(userId, scopeKey) {
        if (!userId || !scopeKey) return []
        await this.init()

        return new Promise((resolve) => {
            const request = this.db.transaction(['pointage_agents'], 'readonly')
                .objectStore('pointage_agents')
                .get(`${userId}:${scopeKey}`)
            request.onsuccess = () => resolve(request.result?.agents || [])
            request.onerror = () => resolve([])
        })
    }

    /**
     * Ajoute un pointage à la queue offline
     */
    async addPointageToQueue(pointageData) {
        const queueItem = await this.enqueueOperation({
            userId: pointageData.created_by,
            entity: 'pointage',
            operation: 'create',
            entityId: pointageData.agent_id ? `pointage:${pointageData.date_pointage}:${pointageData.agent_id}` : null,
            payload: pointageData,
        })

        return queueItem.id
    }

    async findRecentDuplicateOperation(dedupeKey) {
        if (!dedupeKey) return null

        const cutoff = Date.now() - QUEUE_DEDUPE_WINDOW
        const items = await this.getQueueItems({ statuses: ['pending', 'retryable_error', 'syncing'] })

        return items.find((item) => {
            if (item.dedupe_key !== dedupeKey) return false
            const createdAt = new Date(item.created_at).getTime()
            return Number.isFinite(createdAt) && createdAt >= cutoff
        }) || null
    }

    async enqueueOperation({ userId, entity, operation, entityId = null, payload, request = null }) {
        await this.init()

        const dedupeKey = operationDedupeKey({ userId, entity, operation, entityId, payload, request })
        const duplicate = await this.findRecentDuplicateOperation(dedupeKey)
        if (duplicate) {
            debugLog('♻️ Opération offline déjà en attente, réutilisation:', duplicate.id)
            return duplicate
        }

        const id = localId()
        const queueItem = {
            id,
            tempId: id,
            client_operation_id: payload?.client_operation_id || localId(),
            dedupe_key: dedupeKey,
            user_id: userId || null,
            entity,
            operation,
            entity_id: entityId,
            payload,
            request,
            ...payload,
            status: 'pending',
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            attempts: 0,
            last_error: null,
            offline_created: true,
        }

        return new Promise((resolve, reject) => {
            const request = this.db.transaction(['sync_queue'], 'readwrite')
                .objectStore('sync_queue')
                .add(queueItem)
            request.onsuccess = () => {
                debugLog('📝 Opération ajoutée à la queue offline:', id)
                resolve(queueItem)
            }
            request.onerror = () => reject(request.error)
        })
    }

    /**
     * Récupère tous les pointages en attente de synchronisation
     */
    async getPendingPointages() {
        const pending = await this.getQueueItems({ entity: 'pointage', statuses: ['pending', 'retryable_error'] })
        debugLog(`⏳ ${pending.length} pointages en attente de synchronisation`)
        return pending
    }

    async getQueueItems({ userId = null, entity = null, statuses = null } = {}) {
        await this.init()

        return new Promise((resolve) => {
            const request = this.db.transaction(['sync_queue'], 'readonly')
                .objectStore('sync_queue')
                .getAll()
            request.onsuccess = () => {
                const items = (request.result || []).filter((item) => (
                    (!userId || item.user_id === userId)
                    && (!entity || item.entity === entity)
                    && (!statuses || statuses.includes(item.status))
                ))
                resolve(items.sort((left, right) => left.created_at.localeCompare(right.created_at)))
            }
            request.onerror = () => resolve([])
        })
    }

    async restoreStuckSyncing(maxAge = STUCK_SYNCING_MAX_AGE) {
        await this.init()

        const cutoff = Date.now() - maxAge
        let restored = 0

        await new Promise((resolve) => {
            const transaction = this.db.transaction(['sync_queue'], 'readwrite')
            const store = transaction.objectStore('sync_queue')
            const request = store.getAll()

            request.onsuccess = () => {
                ;(request.result || []).forEach((item) => {
                    if (item.status !== 'syncing') return

                    const updatedAt = new Date(item.updated_at || item.created_at).getTime()
                    if (Number.isFinite(updatedAt) && updatedAt > cutoff) return

                    store.put({
                        ...item,
                        status: 'retryable_error',
                        last_error: 'Synchronisation interrompue, reprise automatique.',
                        updated_at: new Date().toISOString(),
                    })
                    restored++
                })
            }

            transaction.oncomplete = () => resolve(restored)
            transaction.onerror = () => resolve(restored)
        })

        if (restored > 0) {
            debugLog(`♻️ ${restored} opération(s) offline reprises après interruption`)
        }

        return restored
    }

    /**
     * Met à jour le statut d'un pointage dans la queue
     */
    async updatePointageStatus(tempId, status, error = null) {
        return this.updateQueueStatus(tempId, status, error)
    }

    async updateQueueStatus(id, status, error = null) {
        await this.init()

        return new Promise((resolve) => {
            const store = this.db.transaction(['sync_queue'], 'readwrite').objectStore('sync_queue')
            const getRequest = store.get(id)
            getRequest.onsuccess = () => {
                const item = getRequest.result
                if (item) {
                    item.status = status
                    item.last_error = error
                    item.attempts = (item.attempts || 0) + (['retryable_error', 'error'].includes(status) ? 1 : 0)
                    item.updated_at = new Date().toISOString()
                    store.put(item)
                }
                resolve(item || null)
            }
            getRequest.onerror = () => resolve(null)
        })
    }

    /**
     * Supprime un pointage synchronisé de la queue
     */
    async removePointageFromQueue(tempId) {
        return this.removeQueueItem(tempId)
    }

    async removeQueueItem(id) {
        await this.init()
        return new Promise((resolve) => {
            const request = this.db.transaction(['sync_queue'], 'readwrite')
                .objectStore('sync_queue')
                .delete(id)
            request.onsuccess = () => {
                debugLog('🗑️ Opération supprimée de la queue:', id)
                resolve()
            }
            request.onerror = () => resolve() // Continue même en cas d'erreur
        })
    }

    /**
     * Métadonnées de synchronisation
     */
    async setSyncTimestamp(key, timestamp) {
        const transaction = this.db.transaction(['sync_metadata'], 'readwrite')
        const store = transaction.objectStore('sync_metadata')

        store.put({ key, timestamp, updated_at: new Date().toISOString() })
    }

    async getSyncTimestamp(key) {
        const transaction = this.db.transaction(['sync_metadata'], 'readonly')
        const store = transaction.objectStore('sync_metadata')

        return new Promise((resolve) => {
            const request = store.get(key)
            request.onsuccess = () => {
                const result = request.result
                resolve(result ? result.timestamp : null)
            }
            request.onerror = () => resolve(null)
        })
    }

    /**
     * Statistiques du cache
     */
    async getStats() {
        const [departments, totalAgents, pending] = await Promise.all([
            this.getCachedDepartments(),
            this.getTotalCachedAgents(),
            this.getPendingPointages()
        ])

        const lastSync = {
            departments: await this.getSyncTimestamp('departments'),
            agents: await this.getSyncTimestamp('agents_last')
        }

        return {
            departments: departments.length,
            agents: totalAgents,
            pendingPointages: pending.length,
            lastSync,
            dbSize: await this.estimateDbSize()
        }
    }

    async getTotalCachedAgents() {
        const transaction = this.db.transaction(['agents'], 'readonly')
        const store = transaction.objectStore('agents')

        return new Promise((resolve) => {
            const request = store.count()
            request.onsuccess = () => resolve(request.result)
            request.onerror = () => resolve(0)
        })
    }

    async estimateDbSize() {
        if ('storage' in navigator && 'estimate' in navigator.storage) {
            const estimate = await navigator.storage.estimate()
            return {
                used: Math.round(estimate.usage / 1024 / 1024 * 100) / 100, // MB
                quota: Math.round(estimate.quota / 1024 / 1024)  // MB
            }
        }
        return { used: 0, quota: 0 }
    }

    /**
     * Nettoyage des anciennes données
     */
    async cleanup(maxAge = 7 * 24 * 60 * 60 * 1000) { // 7 jours par défaut
        await this.init()
        const cutoff = new Date(Date.now() - maxAge).toISOString()
        let cleaned = 0

        // La queue historique pointage_queue est conservée pour migration, mais
        // les opérations créées aujourd'hui vivent dans sync_queue.
        const transaction = this.db.transaction(['sync_queue'], 'readwrite')
        const store = transaction.objectStore('sync_queue')
        const index = store.index('created_at')

        const range = IDBKeyRange.upperBound(cutoff)
        await new Promise((resolve, reject) => {
            const request = index.openCursor(range)

            request.onsuccess = (event) => {
                const cursor = event.target.result
                if (cursor) {
                    // 'error' / 'blocked_auth' are permanent failures (max
                    // retries exhausted, or a stale auth/permission block) —
                    // without this they never left the local queue.
                    if (['synced', 'error', 'blocked_auth'].includes(cursor.value.status)) {
                        cursor.delete()
                        cleaned++
                    }
                    cursor.continue()
                } else {
                    resolve()
                }
            }
            request.onerror = () => reject(request.error)
        })

        let apiCleaned = 0
        const apiCutoff = new Date(Date.now() - API_RESPONSE_RETENTION).toISOString()
        const apiTransaction = this.db.transaction(['api_responses'], 'readwrite')
        const apiStore = apiTransaction.objectStore('api_responses')
        const apiIndex = apiStore.index('cached_at')

        await new Promise((resolve) => {
            const request = apiIndex.openCursor(IDBKeyRange.upperBound(apiCutoff))

            request.onsuccess = (event) => {
                const cursor = event.target.result
                if (cursor) {
                    cursor.delete()
                    apiCleaned++
                    cursor.continue()
                } else {
                    resolve()
                }
            }
            request.onerror = () => resolve()
        })

        debugLog(`🧹 ${cleaned} anciennes opérations synchronisées nettoyées, ${apiCleaned} caches API expirés`)
        return cleaned
    }
}

// Instance singleton
const offlineStorage = new OfflineStorage()

export default offlineStorage
