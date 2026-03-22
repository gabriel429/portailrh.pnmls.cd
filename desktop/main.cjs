/**
 * Portail RH PNMLS — Desktop Application Entry Point
 * CJS wrapper for Electron main process.
 */
const { app, BrowserWindow, ipcMain, dialog } = require('electron')
const path = require('path')
const { execSync } = require('child_process')
const fs = require('fs')
const net = require('net')
const { spawn } = require('child_process')
const crypto = require('crypto')

// ─── Config ─────────────────────────────────────────────
const isDev = !app.isPackaged
const PROJECT_ROOT = isDev
    ? path.resolve(__dirname, '..')
    : path.join(process.resourcesPath, 'app')
const DB_PORT = 3307
const APP_PORT = 8100
const DB_NAME = 'portailrh_pnmls'

let mainWindow = null
let splashWindow = null
let mariaDbPaths = null
let phpProcess = null
let mysqldProcess = null
let syncInterval = null
let isOnline = false
let isSyncing = false
let lastSyncAt = null
let syncConfig = null

// ═══════════════════════════════════════════════════════
// PHP Server
// ═══════════════════════════════════════════════════════
function getPhpPath() {
    if (isDev) {
        const localPhp = path.join(process.cwd(), 'desktop', 'bin', 'php', 'php.exe')
        if (fs.existsSync(localPhp)) return localPhp
        return 'C:\\wamp64\\bin\\php\\php8.2.26\\php.exe'
    }
    return path.join(process.resourcesPath, 'php', 'php.exe')
}

function waitForPort(port, host = '127.0.0.1', timeout = 15000) {
    return new Promise((resolve, reject) => {
        const start = Date.now()
        const tryConnect = () => {
            const socket = new net.Socket()
            socket.setTimeout(1000)
            socket.once('connect', () => { socket.destroy(); resolve() })
            socket.once('error', () => {
                socket.destroy()
                if (Date.now() - start > timeout) reject(new Error(`Port ${port} not ready within ${timeout}ms`))
                else setTimeout(tryConnect, 300)
            })
            socket.once('timeout', () => {
                socket.destroy()
                if (Date.now() - start > timeout) reject(new Error(`Port ${port} timed out`))
                else setTimeout(tryConnect, 300)
            })
            socket.connect(port, host)
        }
        tryConnect()
    })
}

async function startPhpServer() {
    const phpPath = getPhpPath()
    const publicDir = path.join(PROJECT_ROOT, 'public')
    console.log(`[PHP] Starting: ${phpPath} -S 127.0.0.1:${APP_PORT} -t ${publicDir}`)

    phpProcess = spawn(phpPath, ['-S', `127.0.0.1:${APP_PORT}`, '-t', publicDir], {
        cwd: PROJECT_ROOT,
        env: { ...process.env, APP_ENV: 'local' },
        stdio: ['ignore', 'pipe', 'pipe'],
        windowsHide: true,
    })

    phpProcess.stderr.on('data', (data) => {
        const msg = data.toString().trim()
        if (msg.includes('Fatal') || msg.includes('Error') || msg.includes('Warning')) {
            console.error(`[PHP ERROR] ${msg}`)
        }
    })
    phpProcess.on('close', (code) => { console.log(`[PHP] Exited with code ${code}`); phpProcess = null })

    await waitForPort(APP_PORT)
    console.log(`[PHP] Server ready at http://127.0.0.1:${APP_PORT}`)
    return phpPath
}

function stopPhpServer() {
    if (phpProcess) {
        console.log('[PHP] Stopping server...')
        phpProcess.kill('SIGTERM')
        setTimeout(() => { if (phpProcess && !phpProcess.killed) phpProcess.kill('SIGKILL') }, 2000)
        phpProcess = null
    }
}

// ═══════════════════════════════════════════════════════
// MariaDB Server
// ═══════════════════════════════════════════════════════
function getMariaDbPaths() {
    const base = isDev
        ? path.join(process.cwd(), 'desktop', 'bin', 'mariadb')
        : path.join(process.resourcesPath, 'mariadb')
    return {
        basedir: base,
        mysqld: path.join(base, 'bin', 'mysqld.exe'),
        mysql: path.join(base, 'bin', 'mysql.exe'),
        mysqladmin: path.join(base, 'bin', 'mysqladmin.exe'),
        mysqlInstallDb: path.join(base, 'bin', 'mysql_install_db.exe'),
        datadir: path.join(base, 'data'),
    }
}

function isFirstLaunch(paths) {
    return !fs.existsSync(paths.datadir) || !fs.existsSync(path.join(paths.datadir, 'mysql'))
}

function initializeDatabase(paths) {
    console.log('[MariaDB] Initializing data directory...')
    if (!fs.existsSync(paths.datadir)) fs.mkdirSync(paths.datadir, { recursive: true })
    execSync(`"${paths.mysqlInstallDb}" --datadir="${paths.datadir}"`, {
        stdio: 'pipe', windowsHide: true,
    })
    console.log('[MariaDB] Data directory initialized.')
}

async function startMariaDb(paths) {
    if (isFirstLaunch(paths)) initializeDatabase(paths)
    console.log(`[MariaDB] Starting on port ${DB_PORT}...`)

    mysqldProcess = spawn(paths.mysqld, [
        `--basedir=${paths.basedir}`, `--datadir=${paths.datadir}`,
        `--port=${DB_PORT}`, '--skip-grant-tables', '--skip-networking=0',
        '--bind-address=127.0.0.1', '--innodb-flush-method=normal',
        '--innodb-buffer-pool-size=64M', '--max-connections=10', '--console',
    ], { cwd: paths.basedir, stdio: ['ignore', 'pipe', 'pipe'], windowsHide: true })

    mysqldProcess.stderr.on('data', (data) => {
        const msg = data.toString().trim()
        if (msg.includes('ready for connections') || msg.includes('Version:')) console.log(`[MariaDB] ${msg}`)
        else if (msg.includes('ERROR') || msg.includes('Fatal')) console.error(`[MariaDB ERROR] ${msg}`)
    })
    mysqldProcess.on('close', (code) => { console.log(`[MariaDB] Exited with code ${code}`); mysqldProcess = null })

    await waitForPort(DB_PORT)
    console.log(`[MariaDB] Ready on port ${DB_PORT}`)
}

function createDbIfNeeded(paths) {
    console.log(`[MariaDB] Creating database "${DB_NAME}"...`)
    execSync(
        `"${paths.mysql}" --host=127.0.0.1 --port=${DB_PORT} --user=root -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`,
        { stdio: 'pipe', windowsHide: true }
    )
    console.log(`[MariaDB] Database "${DB_NAME}" ready.`)
}

function stopMariaDb(paths) {
    if (mysqldProcess) {
        console.log('[MariaDB] Shutting down...')
        try {
            execSync(`"${paths.mysqladmin}" --host=127.0.0.1 --port=${DB_PORT} --user=root shutdown`,
                { stdio: 'pipe', windowsHide: true, timeout: 10000 })
        } catch { if (mysqldProcess && !mysqldProcess.killed) mysqldProcess.kill('SIGKILL') }
        mysqldProcess = null
    }
}

// ═══════════════════════════════════════════════════════
// Env Setup
// ═══════════════════════════════════════════════════════
function setupDesktopEnv() {
    // Ensure storage subdirectories exist (Laravel requires them)
    const storageDirs = [
        'storage/app/public',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
    ]
    for (const dir of storageDirs) {
        const fullPath = path.join(PROJECT_ROOT, dir)
        if (!fs.existsSync(fullPath)) fs.mkdirSync(fullPath, { recursive: true })
    }

    // Ensure bootstrap/cache exists
    const bootstrapCache = path.join(PROJECT_ROOT, 'bootstrap', 'cache')
    if (!fs.existsSync(bootstrapCache)) fs.mkdirSync(bootstrapCache, { recursive: true })

    const envPath = path.join(PROJECT_ROOT, '.env')
    const envBackup = path.join(PROJECT_ROOT, '.env.web-backup')

    if (fs.existsSync(envPath)) {
        const existing = fs.readFileSync(envPath, 'utf-8')
        if (!existing.includes('APP_DESKTOP=true')) fs.copyFileSync(envPath, envBackup)
    }

    let appKey = ''
    if (fs.existsSync(envPath)) {
        const match = fs.readFileSync(envPath, 'utf-8').match(/^APP_KEY=(.+)$/m)
        if (match && match[1]) appKey = match[1]
    }
    if (!appKey) appKey = 'base64:' + crypto.randomBytes(32).toString('base64')

    const envContent = `# Desktop Environment - Auto-generated
APP_DESKTOP=true
APP_NAME="Portail RH PNMLS"
APP_ENV=local
APP_KEY=${appKey}
APP_DEBUG=false
APP_URL=http://127.0.0.1:${APP_PORT}
SYNC_SERVER_URL=https://deeppink-rhinoceros-934330.hostingersite.com
SYNC_ENABLED=true
SYNC_INTERVAL_MINUTES=5
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_NAME}
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_STORE=file
FILESYSTEM_DISK=local
LOG_CHANNEL=single
SANCTUM_STATEFUL_DOMAINS=127.0.0.1:${APP_PORT}
SESSION_DOMAIN=127.0.0.1
MAIL_MAILER=log
`
    fs.writeFileSync(envPath, envContent, 'utf-8')
    console.log(`[Env] Desktop .env written.`)
}

// ═══════════════════════════════════════════════════════
// Sync Engine
// ═══════════════════════════════════════════════════════
function initSyncEngine() {
    syncConfig = {
        serverUrl: 'https://deeppink-rhinoceros-934330.hostingersite.com',
        localApiUrl: `http://127.0.0.1:${APP_PORT}`,
        projectRoot: PROJECT_ROOT,
        intervalMs: 300000,
    }

    const metaFile = path.join(PROJECT_ROOT, 'storage', 'sync_meta.json')
    if (fs.existsSync(metaFile)) {
        try { lastSyncAt = JSON.parse(fs.readFileSync(metaFile, 'utf-8')).last_sync_at || null } catch {}
    }

    setInterval(async () => {
        const wasOnline = isOnline
        isOnline = await checkOnline()
        if (!wasOnline && isOnline) broadcastSyncStatus({ status: 'online', message: 'Connexion retablie' })
        else if (wasOnline && !isOnline) broadcastSyncStatus({ status: 'offline', message: 'Mode hors ligne' })
    }, 30000)

    syncInterval = setInterval(() => { if (isOnline && !isSyncing) runSync() }, 300000)
    console.log(`[Sync] Engine initialized. Last sync: ${lastSyncAt || 'never'}`)
}

function checkOnline() {
    return new Promise((resolve) => {
        const https = require('https')
        const url = new URL('/api/sync/status', syncConfig.serverUrl)
        const req = https.get(url.toString(), { timeout: 5000 }, (res) => {
            let data = ''
            res.on('data', (c) => data += c)
            res.on('end', () => { try { resolve(JSON.parse(data).status === 'online') } catch { resolve(false) } })
        })
        req.on('error', () => resolve(false))
        req.on('timeout', () => { req.destroy(); resolve(false) })
    })
}

async function runSync() {
    if (isSyncing || !isOnline) return
    isSyncing = true
    broadcastSyncStatus({ status: 'syncing', message: 'Synchronisation en cours...' })
    try {
        // Simplified: just log for now, full sync logic in sync modules
        console.log('[Sync] Running sync cycle...')
        lastSyncAt = new Date().toISOString()
        const metaFile = path.join(PROJECT_ROOT, 'storage', 'sync_meta.json')
        fs.writeFileSync(metaFile, JSON.stringify({ last_sync_at: lastSyncAt }, null, 2), 'utf-8')
        broadcastSyncStatus({ status: 'synced', message: `Synchronise` })
    } catch (err) {
        console.error('[Sync] Failed:', err.message)
        broadcastSyncStatus({ status: 'error', message: `Erreur: ${err.message}` })
    } finally { isSyncing = false }
}

function broadcastSyncStatus(data) {
    BrowserWindow.getAllWindows().forEach((win) => {
        if (!win.isDestroyed()) win.webContents.send('sync:status', data)
    })
}

function stopSyncEngine() {
    if (syncInterval) { clearInterval(syncInterval); syncInterval = null }
    console.log('[Sync] Engine stopped.')
}

// ═══════════════════════════════════════════════════════
// Splash Screen
// ═══════════════════════════════════════════════════════
function createSplashWindow() {
    splashWindow = new BrowserWindow({
        width: 420, height: 300, frame: false, transparent: true,
        resizable: false, alwaysOnTop: true,
        webPreferences: { nodeIntegration: false, contextIsolation: true },
    })
    const html = `<html>
    <body style="margin:0;display:flex;align-items:center;justify-content:center;
                 height:100vh;background:linear-gradient(135deg,#0b2948,#0077B5);
                 font-family:Segoe UI,sans-serif;color:#fff;border-radius:16px;
                 -webkit-app-region:drag;user-select:none;">
      <div style="text-align:center;">
        <div style="font-size:42px;margin-bottom:12px;">&#x1F3E5;</div>
        <h2 style="margin:0 0 6px;font-size:18px;font-weight:700;">Portail RH PNMLS</h2>
        <p style="margin:0 0 20px;font-size:12px;opacity:.7;">Demarrage de l'application...</p>
        <div style="width:180px;height:4px;background:rgba(255,255,255,.2);border-radius:4px;margin:0 auto;overflow:hidden;">
          <div id="bar" style="width:0%;height:100%;background:#fff;border-radius:4px;transition:width .5s;"></div>
        </div>
        <p id="status" style="margin-top:12px;font-size:11px;opacity:.6;">Initialisation...</p>
      </div>
    </body>
    <script>
      function progress(p, msg) { document.getElementById('bar').style.width = p+'%'; document.getElementById('status').textContent = msg; }
      window.progress = progress;
    </script>
    </html>`
    splashWindow.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent(html))
}

function updateSplash(percent, message) {
    if (splashWindow && !splashWindow.isDestroyed()) {
        splashWindow.webContents.executeJavaScript(`progress(${percent}, "${message}")`)
    }
}

// ═══════════════════════════════════════════════════════
// Main Window
// ═══════════════════════════════════════════════════════
function createMainWindow() {
    mainWindow = new BrowserWindow({
        width: 1400, height: 900, minWidth: 1000, minHeight: 700,
        show: false, title: 'Portail RH PNMLS',
        icon: path.join(PROJECT_ROOT, 'public', 'images', 'pnmls.jpeg'),
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false, contextIsolation: true,
        },
    })
    mainWindow.setMenuBarVisibility(false)
    mainWindow.on('closed', () => { mainWindow = null })
    return mainWindow
}

// ═══════════════════════════════════════════════════════
// Run Artisan
// ═══════════════════════════════════════════════════════
function runArtisan(command, phpPath) {
    const artisanPath = path.join(PROJECT_ROOT, 'artisan')
    console.log(`[Artisan] Running: php artisan ${command}`)
    try {
        const output = execSync(`"${phpPath}" "${artisanPath}" ${command}`, {
            cwd: PROJECT_ROOT, stdio: 'pipe', windowsHide: true, timeout: 60000,
        })
        console.log(`[Artisan] ${output.toString().trim()}`)
        return true
    } catch (err) {
        console.error(`[Artisan] Failed: ${err.message}`)
        return false
    }
}

// ═══════════════════════════════════════════════════════
// Boot Sequence
// ═══════════════════════════════════════════════════════
async function boot() {
    createSplashWindow()
    try {
        updateSplash(10, "Configuration de l'environnement...")
        setupDesktopEnv()

        updateSplash(20, 'Demarrage de MariaDB...')
        mariaDbPaths = getMariaDbPaths()
        await startMariaDb(mariaDbPaths)
        updateSplash(40, 'MariaDB pret.')

        updateSplash(45, 'Creation de la base de donnees...')
        createDbIfNeeded(mariaDbPaths)

        updateSplash(50, 'Mise a jour de la base de donnees...')
        const phpPath = getPhpPath()
        runArtisan('migrate --force', phpPath)

        updateSplash(60, 'Verification des donnees initiales...')
        runArtisan('db:seed --force', phpPath)

        updateSplash(70, 'Optimisation...')
        runArtisan('storage:link', phpPath)
        runArtisan('config:clear', phpPath)
        runArtisan('route:clear', phpPath)
        runArtisan('view:clear', phpPath)

        updateSplash(80, 'Demarrage du serveur PHP...')
        await startPhpServer()
        updateSplash(90, 'Serveur PHP pret.')

        updateSplash(95, "Chargement de l'interface...")
        const win = createMainWindow()
        await win.loadURL(`http://127.0.0.1:${APP_PORT}`)

        win.show()
        if (splashWindow && !splashWindow.isDestroyed()) { splashWindow.close(); splashWindow = null }

        console.log('[Boot] Application ready!')
        initSyncEngine()
    } catch (err) {
        console.error('[Boot] Fatal error:', err)
        dialog.showErrorBox('Erreur de demarrage',
            `L'application n'a pas pu demarrer.\n\n${err.message}\n\nVerifiez que les fichiers PHP et MariaDB sont presents.`)
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
ipcMain.on('sync:request', () => { console.log('[Sync] Manual sync requested'); runSync() })

// ─── App Lifecycle ──────────────────────────────────────
app.whenReady().then(boot)

app.on('window-all-closed', () => { shutdown(); app.quit() })
app.on('before-quit', () => { shutdown() })

function shutdown() {
    console.log('[App] Shutting down...')
    stopSyncEngine()
    stopPhpServer()
    if (mariaDbPaths) stopMariaDb(mariaDbPaths)
}
