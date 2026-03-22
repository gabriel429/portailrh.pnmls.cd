/**
 * Sync Engine — Orchestrator
 * Manages automatic and manual synchronization between desktop and server.
 */
import { ipcMain, BrowserWindow } from 'electron'
import { pullFromServer } from './sync-pull.js'
import { pushToServer } from './sync-push.js'
import { syncFiles } from './sync-files.js'
import https from 'https'
import http from 'http'
import fs from 'fs'
import path from 'path'

let syncInterval = null
let isOnline = false
let isSyncing = false
let lastSyncAt = null
let syncConfig = null

/**
 * Initialize the sync engine.
 * @param {object} config
 * @param {string} config.serverUrl - Online server URL
 * @param {string} config.localApiUrl - Local PHP server URL
 * @param {string} config.projectRoot - Laravel project root
 * @param {number} config.intervalMs - Sync interval in milliseconds
 */
export function initSyncEngine(config) {
    syncConfig = config

    // Load last sync timestamp from file
    const metaFile = path.join(config.projectRoot, 'storage', 'sync_meta.json')
    if (fs.existsSync(metaFile)) {
        try {
            const meta = JSON.parse(fs.readFileSync(metaFile, 'utf-8'))
            lastSyncAt = meta.last_sync_at || null
        } catch { /* ignore */ }
    }

    // Setup IPC handlers
    ipcMain.on('sync:request', () => {
        console.log('[Sync] Manual sync requested via IPC')
        runSync()
    })

    // Start online detection + periodic sync
    startPeriodicSync(config.intervalMs || 300000) // default 5 min

    console.log(`[Sync] Engine initialized. Server: ${config.serverUrl}`)
    console.log(`[Sync] Last sync: ${lastSyncAt || 'never'}`)
}

/**
 * Check if the online server is reachable.
 */
export function checkOnlineStatus() {
    return new Promise((resolve) => {
        const url = new URL('/api/sync/status', syncConfig.serverUrl)
        const client = url.protocol === 'https:' ? https : http

        const req = client.get(url.toString(), { timeout: 5000 }, (res) => {
            let data = ''
            res.on('data', (chunk) => data += chunk)
            res.on('end', () => {
                try {
                    const json = JSON.parse(data)
                    resolve(json.status === 'online')
                } catch {
                    resolve(false)
                }
            })
        })

        req.on('error', () => resolve(false))
        req.on('timeout', () => { req.destroy(); resolve(false) })
    })
}

/**
 * Start periodic sync timer.
 */
function startPeriodicSync(intervalMs) {
    // Check online status every 30 seconds
    setInterval(async () => {
        const wasOnline = isOnline
        isOnline = await checkOnlineStatus()

        if (!wasOnline && isOnline) {
            console.log('[Sync] Connection restored! Triggering sync...')
            broadcastStatus({ status: 'online', message: 'Connexion retablie' })
            runSync()
        } else if (wasOnline && !isOnline) {
            console.log('[Sync] Connection lost.')
            broadcastStatus({ status: 'offline', message: 'Mode hors ligne' })
        }
    }, 30000)

    // Full sync on interval
    syncInterval = setInterval(() => {
        if (isOnline && !isSyncing) {
            runSync()
        }
    }, intervalMs)
}

/**
 * Run a full sync cycle: push then pull.
 */
export async function runSync() {
    if (isSyncing) {
        console.log('[Sync] Already syncing, skipping...')
        return
    }
    if (!isOnline) {
        console.log('[Sync] Offline, skipping sync.')
        return
    }

    isSyncing = true
    broadcastStatus({ status: 'syncing', message: 'Synchronisation en cours...' })

    try {
        // Step 1: Push local changes to server
        console.log('[Sync] Pushing local changes...')
        const pushResult = await pushToServer(syncConfig)
        console.log(`[Sync] Push: ${pushResult.accepted} accepted, ${pushResult.conflicts} conflicts`)

        // Step 2: Pull server changes to local
        console.log('[Sync] Pulling server changes...')
        const pullResult = await pullFromServer(syncConfig, lastSyncAt)
        console.log(`[Sync] Pull: ${pullResult.recordCount} records updated`)

        // Step 3: Sync files
        console.log('[Sync] Syncing files...')
        await syncFiles(syncConfig)

        // Update last sync timestamp
        lastSyncAt = pullResult.serverTime || new Date().toISOString()
        saveLastSync()

        broadcastStatus({
            status: 'synced',
            message: `Synchronise a ${new Date().toLocaleTimeString('fr-FR')}`,
            lastSync: lastSyncAt,
        })

        console.log('[Sync] Sync cycle complete.')
    } catch (err) {
        console.error('[Sync] Sync failed:', err.message)
        broadcastStatus({
            status: 'error',
            message: `Erreur de sync: ${err.message}`,
        })
    } finally {
        isSyncing = false
    }
}

/**
 * Save last sync timestamp to file.
 */
function saveLastSync() {
    const metaFile = path.join(syncConfig.projectRoot, 'storage', 'sync_meta.json')
    fs.writeFileSync(metaFile, JSON.stringify({ last_sync_at: lastSyncAt }, null, 2), 'utf-8')
}

/**
 * Broadcast sync status to all renderer windows.
 */
function broadcastStatus(data) {
    BrowserWindow.getAllWindows().forEach((win) => {
        if (!win.isDestroyed()) {
            win.webContents.send('sync:status', data)
        }
    })
}

/**
 * Stop the sync engine.
 */
export function stopSyncEngine() {
    if (syncInterval) {
        clearInterval(syncInterval)
        syncInterval = null
    }
    console.log('[Sync] Engine stopped.')
}
