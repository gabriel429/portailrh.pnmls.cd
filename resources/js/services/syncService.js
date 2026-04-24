/**
 * SERVICE DE SYNCHRONISATION - Portail RH PNMLS
 *
 * Gestion de la queue de synchronisation pour les pointages créés hors ligne
 * Synchronisation automatique, gestion des conflits et retry intelligent
 */

import offlineStorage from './offlineStorage'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

class SyncService {
    constructor() {
        this.isOnline = navigator.onLine
        this.isSyncing = false
        this.syncInterval = null
        this.ui = null
        this.retryDelays = [1000, 2000, 5000, 10000, 30000] // Délais progressifs
        this.maxRetries = 5

        this.initNetworkListener()
        this.startAutoSync()
    }

    initNetworkListener() {
        window.addEventListener('online', () => {
            this.isOnline = true
            console.log('🌐 Connexion rétablie - Début synchronisation')
            this.notifyUser('Synchronisation en cours...', 'info')

            // Synchronisation immédiate quand on revient en ligne
            setTimeout(() => this.syncAll(), 1000)
        })

        window.addEventListener('offline', () => {
            this.isOnline = false
            this.stopAutoSync()
            console.log('📱 Mode offline - Synchronisation suspendue')
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
                this.syncAll()
            }
        }, 30000)

        console.log('⏰ Synchronisation automatique démarrée')
    }

    stopAutoSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval)
            this.syncInterval = null
            console.log('⏸️ Synchronisation automatique arrêtée')
        }
    }

    /**
     * SYNCHRONISATION PRINCIPALE
     * Envoie tous les pointages en attente en un seul appel bulk.
     */
    async syncAll() {
        if (!this.isOnline || this.isSyncing) {
            return false
        }

        this.isSyncing = true
        console.log('🔄 Début synchronisation...')

        try {
            const pending = await offlineStorage.getPendingPointages()

            if (pending.length === 0) {
                console.log('✅ Aucun pointage en attente')
                return true
            }

            console.log(`📤 Synchronisation de ${pending.length} pointage(s) en lot`)

            try {
                // Regrouper tous les pointages par date et les envoyer en un seul appel bulk
                const grouped = this.groupPointagesByDate(pending)
                const bulkPayload = this.buildBulkPayload(grouped)

                let syncedCount = 0
                let errorCount = 0

                for (const payload of bulkPayload) {
                    try {
                        const response = await client.post('/pointages', payload)
                        syncedCount += payload.pointages.length
                    } catch (err) {
                        // Fallback : tenter un par un si le lot échoue
                        for (const pt of payload.pointages) {
                            try {
                                await this.syncSinglePointage(pt)
                                syncedCount++
                            } catch (singleErr) {
                                console.error(`❌ Échec sync pointage ${pt.tempId}:`, singleErr)
                                errorCount++
                            }
                        }
                    }
                }

                // Marquer les succès comme synchronisés
                if (syncedCount > 0) {
                    const allTempIds = pending.map(p => p.tempId)
                    for (const tempId of allTempIds) {
                        await offlineStorage.updatePointageStatus(tempId, 'synced')
                    }
                    // Supprimer de la queue après un délai
                    setTimeout(async () => {
                        for (const tempId of allTempIds) {
                            await offlineStorage.removePointageFromQueue(tempId)
                        }
                    }, 5000)
                }

                // Notification du résultat
                if (syncedCount > 0) {
                    this.notifyUser(
                        `✅ ${syncedCount} pointage(s) synchronisé(s)${errorCount > 0 ? `, ${errorCount} erreur(s)` : ''}`,
                        errorCount > 0 ? 'warning' : 'success'
                    )
                }

                if (errorCount > 0 && syncedCount === 0) {
                    this.notifyUser(`❌ Impossible de synchroniser ${errorCount} pointage(s)`, 'danger')
                }

                console.log(`✅ Synchronisation terminée: ${syncedCount} succès, ${errorCount} erreurs`)
                return errorCount === 0

            } catch (bulkError) {
                console.error('❌ Erreur synchronisation bulk:', bulkError)
                this.notifyUser('Erreur de synchronisation', 'danger')
                return false
            }

        } catch (error) {
            console.error('❌ Erreur synchronisation globale:', error)
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
        const { tempId, date_pointage, agent_id, heure_entree, heure_sortie, observations } = pointage

        await offlineStorage.updatePointageStatus(tempId, 'syncing')

        const response = await client.post('/pointages', {
            date_pointage: date_pointage,
            pointages: [{
                agent_id,
                heure_entree: heure_entree || null,
                heure_sortie: heure_sortie || null,
                observations: observations || null,
            }],
        })

        await offlineStorage.updatePointageStatus(tempId, 'synced')

        console.log(`✅ Pointage ${tempId} synchronisé avec succès`)
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
            console.log(`❌ Pointage ${tempId} abandonné après ${this.maxRetries} tentatives`)
            return
        }

        // Marquer comme en attente de retry
        await offlineStorage.updatePointageStatus(
            tempId,
            'pending',
            `Retry ${attempts + 1}/${this.maxRetries} dans ${nextDelay/1000}s`
        )

        console.log(`⏳ Retry pointage ${tempId} dans ${nextDelay/1000}s (tentative ${attempts + 1}/${this.maxRetries})`)

        // Programmer le retry
        setTimeout(() => {
            if (this.isOnline && !this.isSyncing) {
                this.syncPointage(pointage)
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
            console.log(`🗑️ Pointage ${tempId} annulé`)
            this.notifyUser('Pointage supprimé de la queue', 'info')
            return true

        } catch (error) {
            console.error('❌ Erreur annulation pointage:', error)
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
            const pending = await offlineStorage.getPendingPointages()
            const pointage = pending.find(p => p.tempId === tempId)

            if (!pointage) {
                this.notifyUser('Pointage introuvable', 'warning')
                return false
            }

            await this.syncPointage(pointage)
            this.notifyUser('Pointage synchronisé avec succès', 'success')
            return true

        } catch (error) {
            console.error(`❌ Erreur retry pointage ${tempId}:`, error)
            this.notifyUser('Échec de la synchronisation', 'danger')
            return false
        }
    }

    /**
     * STATISTIQUES DE SYNCHRONISATION
     */
    async getSyncStats() {
        const pending = await offlineStorage.getPendingPointages()

        const stats = {
            total: pending.length,
            pending: pending.filter(p => p.status === 'pending').length,
            syncing: pending.filter(p => p.status === 'syncing').length,
            errors: pending.filter(p => p.status === 'error').length,
            isOnline: this.isOnline,
            isSyncing: this.isSyncing,
            autoSyncActive: this.syncInterval !== null
        }

        return stats
    }

    /**
     * NETTOYAGE DE LA QUEUE
     */
    async cleanupQueue() {
        try {
            await offlineStorage.cleanup()
            console.log('🧹 Queue nettoyée')
            return true
        } catch (error) {
            console.error('❌ Erreur nettoyage queue:', error)
            return false
        }
    }

    /**
     * ARRÊT PROPRE DU SERVICE
     */
    destroy() {
        this.stopAutoSync()
        this.isSyncing = false
        console.log('🛑 Service de synchronisation arrêté')
    }
}

// Instance singleton
const syncService = new SyncService()

// Nettoyage lors de la fermeture de la page
window.addEventListener('beforeunload', () => {
    syncService.destroy()
})

export default syncService