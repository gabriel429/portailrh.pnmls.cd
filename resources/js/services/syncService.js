/**
 * SERVICE DE SYNCHRONISATION - Portail RH PNMLS
 *
 * Gestion de la queue de synchronisation pour les pointages créés hors ligne
 * Synchronisation automatique, gestion des conflits et retry intelligent
 */

import offlineStorage from './offlineStorage'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import { debugLog, reportError } from '@/utils/logger'

function isAuthOrPermissionError(error) {
    return [401, 403, 419].includes(error?.response?.status)
}

class SyncService {
    constructor() {
        this.isOnline = navigator.onLine
        this.isSyncing = false
        this.syncInterval = null
        this.syncTimer = null
        this.ui = null
        this.retryDelays = [1000, 2000, 5000, 10000, 30000] // Délais progressifs
        this.maxRetries = 5

        this.initNetworkListener()
        if (this.isOnline) {
            this.startAutoSync()
            this.scheduleSync('startup', 1200)
        }

        // Purge old synced/failed queue entries and expired cached API
        // responses once per app load — cleanupQueue()/cleanup() existed
        // but were never actually invoked, so IndexedDB grew unbounded.
        this.cleanupQueue()
    }

    initNetworkListener() {
        window.addEventListener('online', () => {
            this.isOnline = true
            debugLog('🌐 Connexion rétablie - Début synchronisation')
            this.notifyUser('Synchronisation en cours...', 'info')
            this.startAutoSync()
            this.scheduleSync('online', 800)
        })

        window.addEventListener('offline', () => {
            this.isOnline = false
            this.stopAutoSync()
            debugLog('📱 Mode offline - Synchronisation suspendue')
        })

        window.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.isOnline) {
                this.scheduleSync('visible', 1200)
            }
        })

        window.addEventListener('focus', () => {
            if (this.isOnline) {
                this.scheduleSync('focus', 1200)
            }
        })
    }

    notifyUser(message, type = 'info', duration = 4000) {
        if (!this.ui) {
            this.ui = useUiStore()
        }
        this.ui?.addToast(message, type, duration)
    }

    /**
     * SYNCHRONISATION AUTOMATIQUE
     */
    startAutoSync() {
        if (this.syncInterval) return

        // Synchronisation toutes les 30 secondes en ligne
        this.syncInterval = setInterval(() => {
            if (this.isOnline && !this.isSyncing) {
                this.scheduleSync('interval')
            }
        }, 30000)

        debugLog('⏰ Synchronisation automatique démarrée')
    }

    stopAutoSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval)
            this.syncInterval = null
            debugLog('⏸️ Synchronisation automatique arrêtée')
        }

        if (this.syncTimer) {
            clearTimeout(this.syncTimer)
            this.syncTimer = null
        }
    }

    scheduleSync(reason = 'auto', delay = 0) {
        if (!this.isOnline || this.isSyncing) return false
        if (this.syncTimer) return true

        this.syncTimer = setTimeout(() => {
            this.syncTimer = null
            this.syncAll()
        }, delay)

        debugLog(`⏱️ Synchronisation planifiée (${reason})`)
        return true
    }

    /**
     * SYNCHRONISATION PRINCIPALE
     * Chaque opération est confirmée individuellement par le serveur avant
     * d'être retirée de la queue locale.
     */
    async syncAll() {
        if (!this.isOnline || this.isSyncing) {
            return false
        }

        this.isSyncing = true
        debugLog('🔄 Début synchronisation...')

        try {
            await offlineStorage.restoreStuckSyncing()
            const pending = await offlineStorage.getQueueItems({
                statuses: ['pending', 'retryable_error'],
            })

            if (pending.length === 0) {
                debugLog('✅ Aucune opération en attente')
                return true
            }

            debugLog(`📤 Synchronisation de ${pending.length} opération(s)`)

            let syncedCount = 0
            let errorCount = 0

            for (const operation of pending) {
                try {
                    await offlineStorage.updateQueueStatus(operation.id, 'syncing')
                    await this.syncQueuedOperation(operation)
                    await offlineStorage.updateQueueStatus(operation.id, 'synced')
                    await offlineStorage.removeQueueItem(operation.id)
                    syncedCount++
                } catch (error) {
                    if (isAuthOrPermissionError(error)) {
                        await offlineStorage.updateQueueStatus(operation.id, 'blocked_auth', 'Connexion requise pour synchroniser')
                        this.stopAutoSync()
                        this.notifyUser('Connexion requise pour synchroniser les données en attente', 'warning')
                        return false
                    }

                    const nextStatus = (operation.attempts || 0) + 1 >= this.maxRetries
                        ? 'error'
                        : 'retryable_error'
                    await offlineStorage.updateQueueStatus(operation.id, nextStatus, error.message || 'Erreur réseau ou serveur')
                    reportError(`❌ Échec sync ${operation.entity} ${operation.id}:`, error)
                    errorCount++
                }
            }

            if (syncedCount > 0 || errorCount > 0) {
                this.notifyUser(
                    `Synchronisation terminée : ${syncedCount} réussie(s), ${errorCount} en erreur`,
                    errorCount > 0 ? 'warning' : 'success'
                )
            }

            debugLog(`✅ Synchronisation terminée: ${syncedCount} succès, ${errorCount} erreurs`)
            return errorCount === 0

        } catch (error) {
            reportError('❌ Erreur synchronisation globale:', error)
            if (isAuthOrPermissionError(error)) {
                this.stopAutoSync()
                return false
            }
            this.notifyUser('Erreur de synchronisation', 'danger')
            return false

        } finally {
            this.isSyncing = false
        }
    }

    /**
     * Regroupe les pointages en attente par date pour l'envoi bulk.
     */
    groupPointagesByDate(pending) {
        const grouped = {}

        for (const pt of pending) {
            const date = pt.date_pointage
            if (!grouped[date]) {
                grouped[date] = []
            }
            grouped[date].push(pt)
        }

        return grouped
    }

    /**
     * Construit les payloads bulk compatibles avec POST /pointages.
     * Format attendu : { date_pointage, pointages: [ { agent_id, heure_entree, heure_sortie, observations } ] }
     */
    buildBulkPayload(grouped) {
        const payloads = []

        for (const [date, pointages] of Object.entries(grouped)) {
            const entries = pointages.map(pt => {
                const { tempId, status, created_at, offline_created, attempts, last_error, date_pointage, ...rest } = pt
                return {
                    agent_id: rest.agent_id,
                    heure_entree: rest.heure_entree || null,
                    heure_sortie: rest.heure_sortie || null,
                    observations: rest.observations || null,
                }
            })

            payloads.push({
                date_pointage: date,
                pointages: entries,
            })
        }

        return payloads
    }

    /**
     * Synchronisation d'un pointage individuel (fallback si bulk échoue).
     */
    async syncSinglePointage(pointage) {
        const { date_pointage, agent_id, client_operation_id } = pointage
        const response = await client.post('/pointages', {
            date_pointage: date_pointage,
            pointages: [{
                agent_id,
                heure_entree: pointage.heure_arrivee || pointage.heure_entree || null,
                heure_sortie: pointage.heure_depart || pointage.heure_sortie || null,
                observations: pointage.commentaire || pointage.observations || null,
                client_operation_id,
            }],
        }, { silent: true, skipForbiddenToast: true })

        debugLog(`✅ Pointage ${pointage.id} synchronisé avec succès`)
        return response.data
    }

    async syncQueuedOperation(operation) {
        if (operation.entity === 'pointage') {
            if (operation.operation === 'update') {
                return this.syncPointageUpdate(operation)
            }
            if (operation.operation === 'delete') {
                return this.syncPointageDelete(operation)
            }
            return this.syncSinglePointage(operation)
        }

        if (operation.operation !== 'create' || operation.request?.method !== 'post' || !operation.request.url) {
            throw new Error('Opération offline non prise en charge')
        }

        const response = await client.post(operation.request.url, {
            ...operation.payload,
            client_operation_id: operation.client_operation_id,
        }, { silent: true, skipForbiddenToast: true })

        debugLog(`✅ ${operation.entity} ${operation.id} synchronisé avec succès`)
        return response.data
    }

    /**
     * Synchronisation d'une correction de pointage faite hors ligne
     * (PUT /pointages/{id}) — distincte de syncSinglePointage, qui ne gère
     * que la création via l'endpoint bulk.
     */
    async syncPointageUpdate(operation) {
        const pointageId = operation.entity_id
        if (!pointageId) {
            throw new Error('Identifiant de pointage manquant pour synchroniser la modification')
        }

        const response = await client.put(`/pointages/${pointageId}`, operation.payload, {
            silent: true,
            skipForbiddenToast: true,
        })

        debugLog(`✅ Modification du pointage ${pointageId} synchronisée avec succès`)
        return response.data
    }

    /**
     * Synchronisation d'une suppression de pointage faite hors ligne
     * (DELETE /pointages/{id}).
     */
    async syncPointageDelete(operation) {
        const pointageId = operation.entity_id
        if (!pointageId) {
            throw new Error('Identifiant de pointage manquant pour synchroniser la suppression')
        }

        const response = await client.delete(`/pointages/${pointageId}`, {
            silent: true,
            skipForbiddenToast: true,
        })

        debugLog(`✅ Suppression du pointage ${pointageId} synchronisée avec succès`)
        return response.data
    }

    /**
     * GESTION DES RETRY
     */
    async scheduleRetry(pointage, error) {
        const { tempId, attempts = 0 } = pointage
        const nextDelay = this.retryDelays[Math.min(attempts, this.retryDelays.length - 1)]

        if (attempts >= this.maxRetries) {
            await offlineStorage.updatePointageStatus(
                tempId,
                'error',
                `Échec après ${this.maxRetries} tentatives: ${error.message}`
            )
            debugLog(`❌ Pointage ${tempId} abandonné après ${this.maxRetries} tentatives`)
            return
        }

        // Marquer comme en attente de retry
        await offlineStorage.updatePointageStatus(
            tempId,
            'pending',
            `Retry ${attempts + 1}/${this.maxRetries} dans ${nextDelay/1000}s`
        )

        debugLog(`⏳ Retry pointage ${tempId} dans ${nextDelay/1000}s (tentative ${attempts + 1}/${this.maxRetries})`)

        // Programmer le retry
        setTimeout(() => {
            if (this.isOnline && !this.isSyncing) {
                this.syncAll()
            }
        }, nextDelay)
    }

    /**
     * SYNCHRONISATION MANUELLE FORCÉE
     */
    async forceSyncAll() {
        if (!this.isOnline) {
            this.notifyUser('Connexion internet requise', 'warning')
            return false
        }

        if (this.isSyncing) {
            this.notifyUser('Synchronisation déjà en cours...', 'info')
            return false
        }

        this.notifyUser('Synchronisation forcée...', 'info')
        return await this.syncAll()
    }

    /**
     * ANNULATION D'UN POINTAGE
     */
    async cancelPointage(tempId) {
        try {
            await offlineStorage.removePointageFromQueue(tempId)
            debugLog(`🗑️ Pointage ${tempId} annulé`)
            this.notifyUser('Pointage supprimé de la queue', 'info')
            return true

        } catch (error) {
            reportError('❌ Erreur annulation pointage:', error)
            this.notifyUser('Impossible d\'annuler le pointage', 'danger')
            return false
        }
    }

    /**
     * RETRY MANUEL D'UN POINTAGE
     */
    async retryPointage(tempId) {
        if (!this.isOnline) {
            this.notifyUser('Connexion requise pour réessayer', 'warning')
            return false
        }

        try {
            const pointages = await offlineStorage.getQueueItems({ entity: 'pointage' })
            const pointage = pointages.find(p => p.id === tempId)

            if (!pointage) {
                this.notifyUser('Pointage introuvable', 'warning')
                return false
            }

            await offlineStorage.updatePointageStatus(pointage.id, 'pending')
            await this.syncAll()
            this.notifyUser('Pointage synchronisé avec succès', 'success')
            return true

        } catch (error) {
            reportError(`❌ Erreur retry pointage ${tempId}:`, error)
            this.notifyUser('Échec de la synchronisation', 'danger')
            return false
        }
    }

    /**
     * Liste détaillée de la file d'attente (tous types d'opérations), pour
     * un panneau permettant de voir/relancer/annuler chaque élément — pas
     * seulement le compteur agrégé affiché dans la barre de statut.
     */
    async getQueueItemsDetailed() {
        const items = await offlineStorage.getQueueItems()
        return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    }

    /**
     * RELANCE MANUELLE D'UNE OPÉRATION (tout type d'entité)
     */
    async retryQueueItem(id) {
        if (!this.isOnline) {
            this.notifyUser('Connexion requise pour réessayer', 'warning')
            return false
        }

        try {
            await offlineStorage.updateQueueStatus(id, 'pending')
            await this.syncAll()
            return true
        } catch (error) {
            reportError(`❌ Erreur retry opération ${id}:`, error)
            this.notifyUser('Échec de la synchronisation', 'danger')
            return false
        }
    }

    /**
     * ANNULATION MANUELLE D'UNE OPÉRATION (tout type d'entité)
     */
    async cancelQueueItem(id) {
        try {
            await offlineStorage.removeQueueItem(id)
            this.notifyUser('Opération supprimée de la file d\'attente', 'info')
            return true
        } catch (error) {
            reportError(`❌ Erreur annulation opération ${id}:`, error)
            this.notifyUser('Impossible de supprimer cette opération', 'danger')
            return false
        }
    }

    /**
     * STATISTIQUES DE SYNCHRONISATION
     */
    async getSyncStats() {
        const operations = await offlineStorage.getQueueItems()

        const stats = {
            total: operations.length,
            pending: operations.filter(operation => ['pending', 'retryable_error'].includes(operation.status)).length,
            syncing: operations.filter(operation => operation.status === 'syncing').length,
            errors: operations.filter(operation => ['error', 'blocked_auth'].includes(operation.status)).length,
            isOnline: this.isOnline,
            isSyncing: this.isSyncing,
            autoSyncActive: this.syncInterval !== null,
            syncScheduled: this.syncTimer !== null,
        }

        return stats
    }

    /**
     * NETTOYAGE DE LA QUEUE
     */
    async cleanupQueue() {
        try {
            await offlineStorage.cleanup()
            debugLog('🧹 Queue nettoyée')
            return true
        } catch (error) {
            reportError('❌ Erreur nettoyage queue:', error)
            return false
        }
    }

    /**
     * ARRÊT PROPRE DU SERVICE
     */
    destroy() {
        this.stopAutoSync()
        this.isSyncing = false
        debugLog('🛑 Service de synchronisation arrêté')
    }
}

// Instance singleton
const syncService = new SyncService()

// Nettoyage lors de la fermeture de la page
window.addEventListener('beforeunload', () => {
    syncService.destroy()
})

export default syncService
