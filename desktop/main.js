/**
 * Portail RH PNMLS — Desktop Application
 * Main Electron process: orchestrates MariaDB, PHP, and the BrowserWindow.
 */
import { app, BrowserWindow, ipcMain, dialog } from 'electron'
import path from 'path'
import { fileURLToPath } from 'url'
import { execSync } from 'child_process'

import { getPhpPath, startPhpServer, stopPhpServer } from './php-server.js'
import { getMariaDbPaths, startMariaDb, createDatabase, stopMariaDb } from './mysql-server.js'
import { setupDesktopEnv } from './env-setup.js'
import { initSyncEngine, stopSyncEngine } from './sync/sync-engine.js'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

// ─── Config ─────────────────────────────────────────────
const isDev = !app.isPackaged
const PROJECT_ROOT = isDev
    ? path.resolve(__dirname, '..')
    : path.resolve(process.resourcesPath, '..', 'app')
const DB_PORT = 3307
const APP_PORT = 8100
const DB_NAME = 'portailrh_pnmls'

let mainWindow = null
let splashWindow = null
let mariaDbPaths = null

// ─── Splash Screen ─────────────────────────────────────
function createSplashWindow() {
    splashWindow = new BrowserWindow({
        width: 420,
        height: 300,
        frame: false,
        transparent: true,
        resizable: false,
        alwaysOnTop: true,
        webPreferences: { nodeIntegration: false, contextIsolation: true },
    })

    const html = `
    <html>
    <body style="margin:0;display:flex;align-items:center;justify-content:center;
                 height:100vh;background:linear-gradient(135deg,#0b2948,#0077B5);
                 font-family:Segoe UI,sans-serif;color:#fff;border-radius:16px;
                 -webkit-app-region:drag;user-select:none;">
      <div style="text-align:center;">
        <div style="font-size:42px;margin-bottom:12px;">🏥</div>
        <h2 style="margin:0 0 6px;font-size:18px;font-weight:700;">Portail RH PNMLS</h2>
        <p style="margin:0 0 20px;font-size:12px;opacity:.7;">Demarrage de l'application...</p>
        <div style="width:180px;height:4px;background:rgba(255,255,255,.2);border-radius:4px;margin:0 auto;overflow:hidden;">
          <div id="bar" style="width:0%;height:100%;background:#fff;border-radius:4px;transition:width .5s;"></div>
        </div>
        <p id="status" style="margin-top:12px;font-size:11px;opacity:.6;">Initialisation...</p>
      </div>
    </body>
    <script>
      let pct = 0;
      function progress(p, msg) { pct = p; document.getElementById('bar').style.width = p+'%'; document.getElementById('status').textContent = msg; }
      window.progress = progress;
    </script>
    </html>`

    splashWindow.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent(html))
    return splashWindow
}

function updateSplash(percent, message) {
    if (splashWindow && !splashWindow.isDestroyed()) {
        splashWindow.webContents.executeJavaScript(`progress(${percent}, "${message}")`)
    }
}

// ─── Main Window ────────────────────────────────────────
function createMainWindow() {
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 1000,
        minHeight: 700,
        show: false,
        title: 'Portail RH PNMLS',
        icon: path.join(PROJECT_ROOT, 'public', 'images', 'pnmls.jpeg'),
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true,
        },
    })

    mainWindow.setMenuBarVisibility(false)

    mainWindow.on('closed', () => {
        mainWindow = null
    })

    return mainWindow
}

// ─── Run Artisan Commands ───────────────────────────────
function runArtisan(command, phpPath) {
    const artisanPath = path.join(PROJECT_ROOT, 'artisan')
    console.log(`[Artisan] Running: php artisan ${command}`)
    try {
        const output = execSync(`"${phpPath}" "${artisanPath}" ${command}`, {
            cwd: PROJECT_ROOT,
            stdio: 'pipe',
            windowsHide: true,
            timeout: 60000,
        })
        console.log(`[Artisan] ${output.toString().trim()}`)
        return true
    } catch (err) {
        console.error(`[Artisan] Failed: ${err.message}`)
        return false
    }
}

// ─── Boot Sequence ──────────────────────────────────────
async function boot() {
    createSplashWindow()

    try {
        // Step 1: Setup environment
        updateSplash(10, 'Configuration de l\'environnement...')
        setupDesktopEnv({
            projectRoot: PROJECT_ROOT,
            dbPort: DB_PORT,
            appPort: APP_PORT,
            dbName: DB_NAME,
        })

        // Step 2: Start MariaDB
        updateSplash(20, 'Demarrage de MariaDB...')
        mariaDbPaths = getMariaDbPaths(isDev)
        await startMariaDb(mariaDbPaths, DB_PORT)
        updateSplash(40, 'MariaDB pret.')

        // Step 3: Create database
        updateSplash(45, 'Creation de la base de donnees...')
        createDatabase(mariaDbPaths, DB_PORT, DB_NAME)

        // Step 4: Get PHP path
        const phpPath = getPhpPath(isDev)
        console.log(`[Boot] PHP path: ${phpPath}`)

        // Step 5: Run migrations
        updateSplash(50, 'Mise a jour de la base de donnees...')
        runArtisan('migrate --force', phpPath)

        // Step 6: Seed data (first launch only)
        // Check if roles table has data
        updateSplash(60, 'Verification des donnees initiales...')
        runArtisan('db:seed --force', phpPath)

        // Step 7: Clear caches
        updateSplash(70, 'Optimisation...')
        runArtisan('config:clear', phpPath)
        runArtisan('route:clear', phpPath)
        runArtisan('view:clear', phpPath)

        // Step 8: Start PHP server
        updateSplash(80, 'Demarrage du serveur PHP...')
        await startPhpServer({
            phpPath,
            projectRoot: PROJECT_ROOT,
            host: '127.0.0.1',
            port: APP_PORT,
        })
        updateSplash(90, 'Serveur PHP pret.')

        // Step 9: Open main window
        updateSplash(95, 'Chargement de l\'interface...')
        const win = createMainWindow()
        await win.loadURL(`http://127.0.0.1:${APP_PORT}`)

        // Show main window and close splash
        win.show()
        if (splashWindow && !splashWindow.isDestroyed()) {
            splashWindow.close()
            splashWindow = null
        }

        console.log('[Boot] Application ready!')

        // Step 10: Start sync engine
        initSyncEngine({
            serverUrl: 'https://deeppink-rhinoceros-934330.hostingersite.com',
            localApiUrl: `http://127.0.0.1:${APP_PORT}`,
            projectRoot: PROJECT_ROOT,
            intervalMs: 300000, // 5 minutes
            authToken: null, // Set after user login
        })

    } catch (err) {
        console.error('[Boot] Fatal error:', err)
        dialog.showErrorBox(
            'Erreur de demarrage',
            `L'application n'a pas pu demarrer.\n\n${err.message}\n\nVerifiez que les fichiers PHP et MariaDB sont presents.`
        )
        app.quit()
    }
}

// ─── IPC Handlers ───────────────────────────────────────
ipcMain.handle('app:version', () => app.getVersion())
ipcMain.on('window:minimize', () => mainWindow?.minimize())
ipcMain.on('window:maximize', () => {
    if (mainWindow?.isMaximized()) mainWindow.unmaximize()
    else mainWindow?.maximize()
})
ipcMain.on('window:close', () => mainWindow?.close())
ipcMain.on('sync:request', () => {
    console.log('[Sync] Manual sync requested')
    // TODO: Trigger sync engine
})

// ─── App Lifecycle ──────────────────────────────────────
app.whenReady().then(boot)

app.on('window-all-closed', () => {
    shutdown()
    app.quit()
})

app.on('before-quit', () => {
    shutdown()
})

function shutdown() {
    console.log('[App] Shutting down...')
    stopSyncEngine()
    stopPhpServer()
    if (mariaDbPaths) {
        stopMariaDb(mariaDbPaths, DB_PORT)
    }
}
