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

            console.log(`📤 Synchronisation de ${pending.length} pointage(s)`)

            let synced = 0
            let errors = 0

            // Synchroniser chaque pointage
            for (const pointage of pending) {
                try {
                    await this.syncPointage(pointage)
                    synced++
                } catch (error) {
                    console.error(`❌ Échec sync pointage ${pointage.tempId}:`, error)
                    errors++
                }

                // Pause légère entre les synchronisations
                await new Promise(resolve => setTimeout(resolve, 100))
            }

            // Notification du résultat
            if (synced > 0) {
                this.notifyUser(
                    `✅ ${synced} pointage(s) synchronisé(s)${errors > 0 ? `, ${errors} erreur(s)` : ''}`,
                    errors > 0 ? 'warning' : 'success'
                )
            }

            if (errors > 0 && synced === 0) {
                this.notifyUser(`❌ Impossible de synchroniser ${errors} pointage(s)`, 'danger')
            }

            console.log(`✅ Synchronisation terminée: ${synced} succès, ${errors} erreurs`)
            return errors === 0

        } catch (error) {
            console.error('❌ Erreur synchronisation globale:', error)
            this.notifyUser('Erreur de synchronisation', 'danger')
            return false

        } finally {
            this.isSyncing = false
        }
    }

    /**
     * SYNCHRONISATION D'UN POINTAGE INDIVIDUEL
     */
    async syncPointage(pointage) {
        const { tempId } = pointage

        try {
            // Marquer comme en cours de synchronisation
            await offlineStorage.updatePointageStatus(tempId, 'syncing')

            // Préparer les données pour l'API
            const apiData = this.preparePointageForAPI(pointage)

            // Envoyer à l'API
            const response = await this.sendPointageToAPI(apiData)

            // Marquer comme synchronisé
            await offlineStorage.updatePointageStatus(tempId, 'synced')

            // Supprimer de la queue après un délai
            setTimeout(() => {
                offlineStorage.removePointageFromQueue(tempId)
            }, 5000)

            console.log(`✅ Pointage ${tempId} synchronisé avec succès`)
            return response

        } catch (error) {
            console.error(`❌ Erreur sync pointage ${tempId}:`, error)

            // Gestion des erreurs spécifiques
            if (error.response?.status === 422) {
                // Erreur de validation - ne pas retenter
                await offlineStorage.updatePointageStatus(
                    tempId,
                    'error',
                    `Validation: ${error.response.data.message || 'Données invalides'}`
                )
                throw new Error('Validation failed - will not retry')

            } else if (error.response?.status === 409) {
                // Conflit - pointage déjà existe
                await offlineStorage.updatePointageStatus(
                    tempId,
                    'error',
                    'Pointage déjà existant'
                )
                console.log(`⚠️ Pointage ${tempId} déjà existant - marqué comme erreur`)
                return null

            } else {
                // Erreur temporaire - programmer un retry
                await this.scheduleRetry(pointage, error)
                throw error
            }
        }
    }

    /**
     * Prépare les données du pointage pour l'API
     */
    preparePointageForAPI(pointage) {
        const {
            tempId,
            status,
            created_at,
            offline_created,
            attempts,
            last_error,
            ...apiData
        } = pointage

        // Ajouter des métadonnées de synchronisation
        return {
            ...apiData,
            _sync_metadata: {
                offline_created: true,
                created_offline_at: created_at,
                sync_attempt: attempts + 1,
                client_temp_id: tempId
            }
        }
    }

    /**
     * Envoie le pointage à l'API
     */
    async sendPointageToAPI(pointageData) {
        try {
            // Essayer l'endpoint de création batch si disponible
            const response = await client.post('/pointages/batch', {
                pointages: [pointageData]
            })
            return response.data

        } catch (error) {
            // Fallback sur l'endpoint individuel
            if (error.response?.status === 404) {
                const response = await client.post('/pointages', pointageData)
                return response.data
            }
            throw error
        }
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