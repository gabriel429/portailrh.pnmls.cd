/**
 * Preload Script
 * Exposes a safe API to the renderer process via contextBridge.
 */
const { contextBridge, ipcRenderer } = require('electron')

contextBridge.exposeInMainWorld('desktopAPI', {
    // Sync status
    onSyncStatus: (callback) => ipcRenderer.on('sync:status', (_event, data) => callback(data)),
    requestSync: () => ipcRenderer.send('sync:request'),

    // App info
    getAppVersion: () => ipcRenderer.invoke('app:version'),
    isDesktop: () => true,

    // Window controls
    minimize: () => ipcRenderer.send('window:minimize'),
    maximize: () => ipcRenderer.send('window:maximize'),
    close: () => ipcRenderer.send('window:close'),
})
