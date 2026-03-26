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

class OfflineStorage {
    constructor() {
        this.dbName = 'PortailRH_OfflineDB'
        this.dbVersion = 1
        this.db = null
    }

    /**
     * Initialise la base de données IndexedDB
     */
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion)

            request.onerror = () => {
                console.error('❌ Erreur IndexedDB:', request.error)
                reject(request.error)
            }

            request.onsuccess = () => {
                this.db = request.result
                console.log('✅ IndexedDB initialisé:', this.dbName)
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

                console.log('🗄️ Tables IndexedDB créées')
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
        console.log(`✅ ${departments.length} départements mis en cache`)

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
        console.log(`✅ ${agents.length} agents du département ${departmentId} mis en cache`)

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
                console.log(`📂 ${departments.length} départements récupérés du cache`)
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
                console.log(`👥 ${agents.length} agents du département ${departmentId} récupérés du cache`)
                resolve(agents)
            }
            request.onerror = () => resolve([])
        })
    }

    /**
     * Ajoute un pointage à la queue offline
     */
    async addPointageToQueue(pointageData) {
        const transaction = this.db.transaction(['pointage_queue'], 'readwrite')
        const store = transaction.objectStore('pointage_queue')

        const queueItem = {
            ...pointageData,
            status: 'pending', // pending, syncing, synced, error
            created_at: new Date().toISOString(),
            attempts: 0,
            last_error: null,
            offline_created: true
        }

        return new Promise((resolve, reject) => {
            const request = store.add(queueItem)
            request.onsuccess = () => {
                console.log('📝 Pointage ajouté à la queue offline:', request.result)
                resolve(request.result)
            }
            request.onerror = () => reject(request.error)
        })
    }

    /**
     * Récupère tous les pointages en attente de synchronisation
     */
    async getPendingPointages() {
        const transaction = this.db.transaction(['pointage_queue'], 'readonly')
        const store = transaction.objectStore('pointage_queue')
        const index = store.index('status')

        return new Promise((resolve) => {
            const request = index.getAll('pending')
            request.onsuccess = () => {
                const pending = request.result || []
                console.log(`⏳ ${pending.length} pointages en attente de synchronisation`)
                resolve(pending)
            }
            request.onerror = () => resolve([])
        })
    }

    /**
     * Met à jour le statut d'un pointage dans la queue
     */
    async updatePointageStatus(tempId, status, error = null) {
        const transaction = this.db.transaction(['pointage_queue'], 'readwrite')
        const store = transaction.objectStore('pointage_queue')

        const getRequest = store.get(tempId)
        getRequest.onsuccess = () => {
            const item = getRequest.result
            if (item) {
                item.status = status
                item.last_error = error
                item.attempts = (item.attempts || 0) + (status === 'error' ? 1 : 0)
                item.updated_at = new Date().toISOString()

                store.put(item)
                console.log(`🔄 Pointage ${tempId} mis à jour:`, status)
            }
        }
    }

    /**
     * Supprime un pointage synchronisé de la queue
     */
    async removePointageFromQueue(tempId) {
        const transaction = this.db.transaction(['pointage_queue'], 'readwrite')
        const store = transaction.objectStore('pointage_queue')

        return new Promise((resolve) => {
            const request = store.delete(tempId)
            request.onsuccess = () => {
                console.log('🗑️ Pointage supprimé de la queue:', tempId)
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
        const cutoff = new Date(Date.now() - maxAge).toISOString()
        let cleaned = 0

        // Nettoyer les pointages synchronisés anciens
        const transaction = this.db.transaction(['pointage_queue'], 'readwrite')
        const store = transaction.objectStore('pointage_queue')
        const index = store.index('created_at')

        const range = IDBKeyRange.upperBound(cutoff)
        const request = index.openCursor(range)

        request.onsuccess = (event) => {
            const cursor = event.target.result
            if (cursor) {
                if (cursor.value.status === 'synced') {
                    cursor.delete()
                    cleaned++
                }
                cursor.continue()
            } else {
                console.log(`🧹 ${cleaned} anciens pointages nettoyés`)
            }
        }
    }
}

// Instance singleton
const offlineStorage = new OfflineStorage()

export default offlineStorage