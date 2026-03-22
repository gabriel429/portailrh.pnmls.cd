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
        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAgAElEQVR4nO2deXwURfr/P1V9zExPZjKZTA6SkIQQQsQkxCQCIqIiIiKCooCo4Mmisirqquu9fpV1Xb/rsbseqLjirSheCIiKyCUgmQQIECCEEEJIQo5JMldPd1f9/uBYkCMJmYB+f3m/XvPHTFdXPd31TNVTT9VTBXTTTTfddNNNN91000033XTTTTfddNNNN91000033XTTTTfddNNNN910838KcroF6Cq++eYbTJs2Lb6+vj7TMIx0SZIiRFGkwWCQWa1W6vV6ma7roJTCZrNRVVWZKIrUMAxmGEYQQIXVai2dOnVq5bPPPstO9/N0Ff9nFKB///5ieXl5rq7rAwghZs4545zXCYKwOTk5uSwxMdH//fffH1WRLWtWwT5w8KHvU6ZMQWlpqXnLli1pmqZlEkKSKaWUMRYCsKFXr15rN2/eHDyVz9aV/K4VIDU1Va6trR1GKc1ijOmEkOLExMTV27dvP1RB7sTYHK4GryKitJXabJ8YnqYJALlFcDrHGvvqLgBIcn5j6ysb+2UoobraN4gkPZu3t37Dhr69I/SW5rS8vfUbACArK0vcuXNnLud8EAAz57zC6XQu2rNnj/d0PX84+N0pwKJFizBx4sT0UCg0GgAkSVqSmZlZ8k6UjQW2bM7goVAWAIDzeAjCah4Mvg9BeBmcXQiQKhjGVgjCkwAWEUH4DIxl5e3zPF0Y4/gzIeQMzlhjfn3zPe445whuGLfk1zdP/LUMTz75JF544YXUYDA4ilKqEEKWDhw4cN2SJUtO7csIA78bBXjzzTdx1113DQYwlHNelpCQ8NWOHTtCAOCOcz7ADf06MC4Tk+kJgNfxkHY/EehmUGFLXk3DWyVnZZnVXbt2Ell+BJQ4uRq6ksiyB4y5icXyMvO2LhccUVcazZ45lozMgcHtW/8EgOXVef5+LHnWn5kh82Aw4vbYRE9xcfEwQkgB57ykX79+CwoLC383NgM93QK0xZw5c6AoSsFdd931KCFE7t+//99/6ZP6xVxv4xUbcs5QAIDI8hdCpGMiCIE1K+dTKTbeDc5zQehurusDt11zFdX27csFIXWgNAjdALVYJnE1WMA5k5nX+yQIKTWaGl8AY7HB8h2DOeP5oHTd8eQy6uvHGU2Nu16r25Ow9tyzv58/ccLfBEGo3LJly58tFsuo8ePHn7qX1Al+0y2AzWZL0nX9egDFjz/++KIbe8Sgx423Yn16Sqbu8XwG8HIhwjY5t2KPZ+cDM9A4+6311GqdeFbl3tLCaPsvNCJiKvP5HgRnQ0CIh4rSLcRkYiwQiM+ra5rv7uEaDErjYehJpn5n/PPMJStRlBBzAWfsIq5p1xCBfs85fhRdrsX9t5R5Dsq1acSFCLoLl4OQeCKKN3FdHwvOB0g9Ei/N2bjFb7VaCxhjIymlX/h8vpLT+Arb5DfZAuTk5FCLxXKjrusj4+Ling8EAotGzvp3RvUD9z8HABwQQekqajJ/aLS2fudOiI3t9fcXAULms0BgNABAFP/BDd2fX988SYyK7vPtqCvyR+i0elBtIy4IGiFJkq7I39uQcHZNA873aWV5K9cNcrlc6Xck916bV9PwmBgdPZCI0lwAyczncx4uX2jLllxwgHAs5Zo2hxA6Ahw6jYsPFsY6Zq1KiN2WkZHxV8ZYhsVimREfH6+c+rfYPn5zLUBERESqYRhTBEF4b+3ggorgho0TAO5Vzspb5Pvll6+pxfIgZ0zmwcCXAPEQShdwxgbLPXtequ/bZyeiSMfL1vrq6upcxpidMUYFQWChUIgZhlFNCKm3Wq11ra2th8o0mUwwDMNpGIYLQJLZZFIIIWCcU0JI0G63r5s9e3b96NH7dcsd6/gHJ/QXMAZCSQI3WDYRhK8BrnDDmBj/0KOX173wj2RCKbvIF/L7fL7bBEFY7PP5jtulnC5+UwqgKMoYznl8ZmbmW0VFRXrJuQOoWlr63f7+nFQTSVzCdSOTM+NJAjJdTkm5IbuoRC85J1+5sKxS8Xq9BYQQaJoWZIwVG4bhabvUEyMIghlAgSiKsZRSSJK0ecWZGWUUQNbP6/Ti1ESX0dq6XIyLO0evrSmkinJZwn33lVY9PfNLItDPiMm8uj69T9kVawqvYIw5Y2Ji3qqsrPzNGIm/CQVISEgQPR7PHwGs8/v9Kw6/5k6IyeWh0D+oSX6Mqeo9MNgYYjY/BN3YcHtaxpKSkpJBjDGHruv1lNJ1qqp22cuVJAmGYWRJktSPEBKMior6fqFZsLNgMAeG4eeG8VB+Q8tl7vjoEVzTnhLs9olGS8sPgt1+Xu7OqmqbzZahadoEm8324r59+37X/oOw4XA4Isxm8+MRERHJ28dfeUybpNAVOcsdF3U1ABSlJib9IyNdsVgsw81m8yhCiPNY93Q1lFKzJEljTCbTNVFRUc4N/fqY3a7IbwtdkS8XRtu3FiXFDSmMtv/ojnNee/Ce7TffjMjISLvFYnlSUZSE0yH3bwqr1eo0m81PxcXF2d3JCa5CV+Q3RSkJ8b9OV9wrKdYd5/zTxRdfDKvVOsRkMo2klP4mDCtBEERRFEeZzeYJ1/ZJj3AnxgwrSorLKoxxPFroipyzbexlh9K6Y6MecMc5p/RNTxctFsufLRZLxmkU/fRitVpjzWbzkzExMbI7wZVR6Ir8uTA+emhRUtzgjf37mX+d3mazpVosljGU0tPyj2+LAy3CtYqiDK178w24Y6OeKnRFfu1OSYg9mGZjbpZSGG3bXRjrePT+lJ7UYrHMMJvNWadV7tNRqNVqdRqGMT0lJWXmj317M3AkgHMHYWwQCwae0Zs8juKUhCwAGD9+PLVYLCNVVXUEAoGvGGONp0PmtmCMBTVN+yAYDFb3nP7Hm0cw4Tkiii/wgP+NinvvpnvnfYLQnqqZIHQV4bBN0gJXjBkz5kVK6XCTyZR5uuU/ZRzo859yuVxycUpCZqHLvt4d78pxxzmnFDptTUVpSWmFrsgv3XHO2xwOh8tsNo+jlEacbrk7gslkopIkXW+1WnO333wjBQB3bNSMwmj7T+v7pJprXn4R7jjnre7YqEf79u0Ls9n8gM1mSz7dcnc5CQkJotlsfjw+Pt4OAEU94/sVOm3NhdH2Le6EmCHu2KgnC522Wnd89BibzZYpy/Iwu91+usU+aURRHGKxWEZvHDyAFsY4/lGUmugCAHds1M2F0bZNxb16prmT4tIuGDpUNJvNj9tstlP+sKd0GKgoygxK6bxldkuQh0L3E1FczjVtJlWUx5jP9xQxmR4kglA6pMnr1HWdqarq7mqZVkTbRbsalEEIfKKkn9PkDYUzf0JIuslkGjx8+PD3Xr9oOKt55smbuWHcLdhslxpe7xxCCOOAexSRZ7a0tt7bp0+fpzds2HDK/ASnTAEURRkDoNHv969wJ7iuYIHgx1SWV3FNS4MgzKRm0wIQaj+vsdUVUtWakKZt60p51sU7CxDS7uHgoyjnDgBghPgJxyoQ8jks5k8K9uyrD0dZhJB4k8l0xbDknm8+5dn3ADWZN3Bd17kWepBaI+5kPu+/iKJMHNroVQzDGBUIBF4JR7ntku1UFHLAvTti0zUTXm9evHC43Lv3EnXbttFcC82kinW8JTtnW+aCxSwiImKoqqrVmqaVdZUsa1J6iFIgMJNr+r0AF/lx0hFCvRDoK4IgPptb09Bpw5MQ4pJlecK555772v9uWT+FG8xBKC3nunYlEaWFQmTkIh4M2s+pa8ohhHj9fv+yzpbZHrp8FJCbm0sNw5iSmZn5VvMPi3MNv++74OZN31CTyU1EaToLBD5Wd+5UrFZrQUgN1XRl5a9OThBFn+9dpmkP8BNUPgBwziK4rj9gaKFN7tio3M6WzTmvV1X101WrVt1oWCzzwPllYOxScMQSUdyg19d/Z3i9n/+cFLeUcz7I6XSeEnugyxVg69atUwRBeK+oqEg39UrbQASxmEhyseH1fgdC4uWUlPOHVNUkGbqhh7RQlzb7kq/1AW4Y13TkHs5YPNe07woTXElhEKEuFAotPb+mcYTgiLoKgvADEcWVzO/7mQjCEiIKLxsez0xFUV4JBAK3haG8NulSBbDb7UkAxDV5uRWFrshZgU0bPwTQwrVQAVWUizhnCeftqBRVVU0IqsHirpSlMN7pAOf3H+saOfDh5Hg9IncRTXupeNi5nZaDMVauqmr1OVU1WUSS1nFNu4c6os7nhjGSmMxLCKU7f4yODBJCihVFGdx2jp2jyxTg7bffhqZp18fFxb1j7KtRwHkykaQLiCh8Tqjw7lm7a6pnnnvhP4PB4FCr1dr1i+kM40Z+wNg7CBelEm62PBSKcp4nOaKizFHOqFBScmJTTsET1cl9D1niHADXjXF8y9ZBl32zS5wwf0snRTFW6bqeemFLIEgk6QnW2jqJyPI0cMYEZ9RbQlICzcnJWcw5H5abmyt2qrA26DIj0Gq1DmCMOQOBwCIA2DzqEhpYt/Y2GMadRJbvzttbv1ixWEYGVXUFYywsM2PKKzuSVYOMEymK+yaYl2648r/zLeucEWvA+YAjbrDZJhVU1nzU/93t9kq/OEw1cJHO+GCDkxzGmHhZ4ad45Ju/QmI6AKA4Mdvzh2kfyqJAS2WRX+j9Q2rLycpKKaWiKP7xnxm9/3127d7ZotN5n97Y8BgYuwIgjIjiI+f7tXW6rhcEAoGPTracNuXoikzfeOMNcM5H/OUvf1lU3DvZub5fH7tj+HDaZ/53rxCLZSwYS7fZbKm6YdSEq/Ktr1Wkqkz42eDkBdWgP5TuDc1JfLtCAYC1SXEOwpH363uMoPqQ7Z9bPyvxiHubVHzu1/HHECN5BofICcX8golYfOaIQ+kzarc5pFBACeksz69iYeQr2+0A4HxzV7rztfKEuDl7nKZXK+6yv14ptyUvY4zpuj5vRtnOcb3f/+Qmo7k5C4xnmXr17kMtyjlc12auTE+pApCcnp7eZn4nS5e0AIqiDCaEyGv7n7k6uLV0O4BiEJIKcCehQqkvOeXSEZu2jgwEAl+Fo7yY18toY0hYaTAMOvx3WSQf2QTjkS8fPzvBElKXH8vqf2LMX/BN/tXHzFfWVXz5/AjE+BoA7H9Zo+/8GntdvQAAIoWbEMzXGWYAJALgjZzDJQl0mSzwq3y3pbbpRxBF8ZqIiIglP0h8CBi/NL+hZWrtvA9RNfUP60Vn9Hnn7/PYVVUdFggE3unga2kXYW8BvvnmGwAYmpOTs+zMVWuDhNK3iSAsB1BMROnOtK+/umjklu0DVFVd0UZW7aZZE67+deUDQEjn1zSqdPtzlzz4r+MN+W5Z9gYk49jOv5HFXx6qfGC/LWDWAoe+6wx5moHHOYedc045hwsANIMNDej4Jf6dyjYneSilnwb8gXGi07kIBLmFsY5n4sZNguiKucRobS34MVIJAojPysrqElsg7Apw7bXXpnPOy37++WcGAOb0Pk9wwzgPnGdasnO+yLpqkqjruj1cs3q952yFzvDQ8a5zgO6K6pl7vKYu2bMHl7o//9WvBJaQD1OXvvarXwGfydYuuRjjqXUtbKX9jcphJ0oXCoV03dDLBlfVJYtRURcSUVwOAP23lteAswTm998qCMKCHTt2jGxXwR0k7AoQCoVGJyYmfrUx+wxzUc/4fpwKEGy2GwDowa2lroaGhgs0TVsarvJ2t0qDGMcJHTVVMb2gH3eIB9y67HXIunrYLxxTf/gnerTWHZGuVTLDY49Fe2EMTsZZm379CRMmfK9p2vD+23d586rrF6zP6mt2x0Zdy3X9TjA2bVVe9jZCSL/vv/++3WW3l7AqQGpqqgwAPz30QCi0d89cFvDPVreXvpo8661GIdJ+/46Y2BbOucwY84erTMbJ2LbSNNjjURl1fD9OQkstxqz75ND33J1rcO2aow3vrXEZCIlHrVU5PgTQIbTp3/jwww9hGMY2u92e7k6IGaTvrd7CGbucmsz3gJL39G3bRjHGqq6++upwOKOOIKwKUFtbO0ySpCW1TzyaA0IrzX0yL+IGG7fz2vEbmdd3wQ2lZXmqqq4OZ5kMGNVWGg7gp74XnjDNTcvfgjkUgMPXgP+Z9zBEbhyV5uc+5x3IrX0QzmGE1GuVl8tT25SR8++DweBwYjFvpvbIc/PrmyclzXxmldK333PmtPT5Lpdrvqqqo9tdeDsJqwJQSrMyMzNLoOtDwIzRwe1b/wNB+EpwOC5K//qbpxljDs552Fb0jPqpGZyxdq2m+easseAnGPTEeffh6tXv4qm59yOhpfao64xQ/HDYkLA9cBAYEB8hlB41BD0q//34RweZftbOqmp3Qkxm5Z/uXePfumVTYPvW2Qsc1lBXLIwJmwJkZ2eLhmHoa9euZY5J174i2OznE0I2wtCHsNbWx3qPHuvSdT0s06sAkPNRJX4qbXqUE9quMfLOuL5w98w5YZo7l/wb5+xce8xrxYlZ2B2T3iEZZWp84qLeM323p85rT3rG2KKGhoaRAMBVdTY1mx6zDhnSC5x59braO3Rdr4qOjg7rauKwKUBFRUUupbS4KCVBaXr33feJLCucsYuoYr2SRtqfOhC0EbbImNIG1s8f4k915J73Bt9wQseHwI9vr80dMKkjRUGgKB0S7Z1cd0dWu4NTGGN1jLHY1tVrAM6T5J4pyzI/n68TQfyJg/SOjIxc6vP5Tjiq6ChhUwBN0wYkJiauZn7fHwlBo+H1poEQxvz+l5Oe+UcdIQShUCiMK12IyNvfHQMAVmVehB3OlA6XVG2Pw9IzL0FH+n+RkFeXTOrf4dVFjDFvzqRrFAjCvOD2bXMKXfabua4/QSidO2PGjBpKafuHIe0gbApAKTUf2JmjD6fCjwA2Czbb/SAkdeijjyqapoV1W5WkSFZOCPSO3GNQAe8PntKhcgiA9wddD01svzeWENTFW9ST8twZhrGipqZmqCk9/X4iCN+C4wwiy1Ol2LjiS19+0Q6ATZ8+/WSyPiZhUYCvvvoKnO9vP4kofQxNe4JroaFGS8sjIOSTXbt35zLGwjrdG2WVguDo8D9sUe5Y7FPaH1rQYHHgq7MndKgMq0we23Vz35OKS5RlucIwjGTrwEGMc5YAglQeUueE9uzewULqFMMw6ufOnes6mbyPRVgU4Pbbb4/nnNcBgOXMM5dQs/k5AAOJKHxpOvPMRxhj9nAEah7O1ppQMgg6HB2kSmZ8MqB9a0IIgPfOmYyAbG13/rKA+Wcnym92VK6DqKoKAsBo9jAiio1EENYTKnxCFWs+OM4ym80lPp/vxNZsBwiLAjQ0NGQKgrC5qE+K3V9c9CMLqQ+C8ZGgtCZr6Uqdcx5+jyOjIzpqAxzk8wETERBNbaZrNNsxb+C1baY7CCVks81MbvhxdEKnbB2DMXbpijUiGP+Ua/pVnLGRTFWngDM5JiZmm6ZpHRuOnEjmcGRiGEZ6cnJyGfM030EIXWEfdG42sVgu4SHtpZpXXwOlNKzLnFmzMGLxJdfc+fnw2/D58Nvw4YX34IbMn0AI0CtyO+ZeNAUfDrsJTtM+AMDA2DX4ZNgU/PXshwBQ3HXeXPR4myD5dQ2Wgv2iRV5mIPl1Dcmva4i8igECR8KbDG+Nmo5+zg1HydA3shTvnT8NxWMvxqrLRuPB/s/DKnrXNdyS2mk/h2EYFU1NTcngfAqR5WeJLD9ECEBtkfeXlZX5RVEMW1xkWGaYJEmKSE1N9aN+bxQo3dFn/kIUZ/auNoJB5T+lW+TwWv8AJ/Sq82NW9zvo2CHgmJCyEHWBT7DXr+LqpOUAgD25L+FPa55GgqUO43sux5qG3gCAwTHF6J29f5ZP2yojuI4iapSO6BH7bUq9XEIzldBvYB36SbV4ecvNR5RvEX349pIpSLHsw1ZfHM6ILMc5rk04x1UccUV4HrHM5/NlCikJbxmtrQnQ9STOUc193ts3ZKa/cM7uveEpBWFqAURRpAsXLmRElv7Dde3+Qpf9OWNf3UJC6aznP/gg1jCM6nCUc9PKPYiYVXHrZ5VX3gwAcysvQtaXX6LY0xuUq7gg7juQw7qFOzI+QZp9+2E5HOkFMAIESn8DnAPWXAYteNw1gUd8y3O5kWLZhwp/NPp9uhIz1v0ZNZoDIxILw+LlFAShxjCMWMPjSefB4Cxu6DeBszMANHHDgCAI4Ru9hSOTYDDIACBvz75SqigXAmQrMZkfSZn9zl9bW1vjCSGd9gAOnrsZH64P3eULYZbBiAgArZodW5qyUOnbPzQWiH6Eu9dCNTyV99wR+UQEmpFavQUggG+jAGsWA41jMCVweDcIxxfgsNnERtUBDiBVacCqMZfBKddjxOI5q+M/3D2ts88JALquBxljEVJSUikIWQdB8EA34sHZ+UZrc+bB9x0OwqIAVqt1fwBknHMC8/m/hCAoAPy7bpk8Q5Zlp9VqrWsjizZZU2t5OKiTlw43KMf2XIwtVw/G5YmrARAUewbiYBPQqsuoD9kxMeUHnB2z5sAdHH+a/zTimvYAAAIbCEQnh2MUAyEcgQ3HbgEGbj1y/qq0KQtvlV8ODoKBUVvxdP/XsX70VWnN1+cctSjlZJFlWSeaJgN8KKHC/j8QoZWE0FSrtf2jkrYIiwK0trYyAOCMTRds9uuga1O5qr4BQlcEAgHl8A2ZTgbTi5tdjOOJXzfFDjmAJGszmoxYvFExA5+UnX/o/+81FDyz4Q8QwHBX5v6p3l61VRhdsuDQ/b4NFASA63odnBH4Nx37dUxe/T6yd/5y6DsHwdTlL2HkktfwXsUItBgWEK7Hgte+0dwYHk9tKBRSQnv3BmGwRq6qP0IU6wkhmzhnnX6fhxMWBdD1/cYToRScsySAhIgkPshDoTpd0zpvaIpKBufkKFfcZ7vHoefHvyD542X4w093I2Qc9g8mHK9suRll3h6wChoAQDYMkMOUKLiFgmmALd1AoIKAe47dAgjMwONfPAaz6gXAMS71C3x20c0YGPMLJv/0Oq78ftb+hJwlSUIwXO9UNOflh4gs1VBFKaaK5Q1u6Gu5qpYcfN/hICyjAEEQmGEY4LpWykLqdEJIHdf1+4hi+Zi2BDyGcfTcekfQDD0d5GhRNSahST3YHB7tFAgaVjxSdC8+Pu+Y8SDgKuDfShGRxeArPn699XpeQ0pgOzbZL8SqQDY+Lb8CVyYtxfAeq7GtuQ9yHNsPSECrVcMalv6ZUsrknkksWFxUwwL+e4ggNoPSFjC+VBAEdPadHionHJnYbDYKAMRkfoxK8kpqNj+RX99ySd6efW8pihKU5c6taqaCeMbhFdwR/8+nO6/E+r29jnunf/1+w8+/8fgGoLmHASXNQJqrHmlKDRbsGomVDVmwCUF8NORBPJz1JgDBC2qbGuX8rgPSHR+TyRT0Llxo5qHQcM75LlDKYLBEarXabbb2rUtsD2FpAVR1/3o6rmkvgaCMh0KzSs4pOCfr53XBUCjkYYw5AXR4JJD9/i406YiobuVHOONfLZ2AhXsGobz16Jm9itZUXLfyaQQMGSAcmZUboU9pwJZsBcaBJr5qthl7FwChBmDv2zIa3DJCKwAQYMu9VmjbABjAlgeUA0blf7sGLdQMc1YAwxd9hLEpC9DHXgGXyVd8Z795lwkRTWEZ7gL7u1UhNlZhu3dX5u/zvA4cCJ9obIXFYglbyFhYFEAUxf0tCeeKnNRzZqhqd4K+pzpj/RnpjcPqmuoaGxtdaEMBXO9U0aBG7N5bEg/NGWzz8FEhg8/lONLnv7wmD8trjr3IpikYgw/KrgMAKGor/mfeQ0CjAf/2/1aiuuq/6dl2QD/gKiAA/Ict3fB/fvDX/xKBZtxT+Vf8z1V/x8c7Dm4ITbbNGDAnbJVvMplkACEeDDII9JtfXz/0vsNAuAwWduuttwJAY2h35U4YxhWGz/u5Xl8/s096n2oAx1zMGPPGNnHSsgZEvr4ztqlFWxgM6j/2fGcHrK/uzBJfrvgsxPjXv6789kMwY8Ffkdq4u12pOQhmD7oe7YmVuXzDApy/adHJidUONE2LBVCfs3l7fV5N42u/vm4YRtj8AGFpARhjwY0bN5qF6Oi7BaeT9fjTw17XVeP2X9y3GhazRQkE/xtQkTi7DPtU8eWGIG78eH2LG0A840gHGGo8fK9GiAucd0q2YRu/wbji9gceLTxzBF659CH0atiFC7cvP2FaAo6H5z+NjclnodEWCwLWIbukLTjnaSaTqfxg19qVhKspqdi8eXNa7tbyluyf1/238g9A6JH/qsagKIZ0fgfjXGEcQ/ZX/n40CPGdrfw4zx48PP/pdqdvtDjw/GUPA+B45eJ7oJMTeAQPEO1vwp+/+gsAQATrwFrxtqGUpiclJR1zo4z+/fvLhnGcUKaTKSscmVit1lJN0467OpcxdqicGT/XUk7JE0ckOOBmFSmCkkAaBaZvoAc+MmGrJYHOlwSyiBK02fQJzMDj8x6GI9j+wN1/jLwfHms0AGBnXAa+7H95u+4btm0ZLiv8FAYRhjte3xW2FbuCIERs3rzZDwB2u90ly/IdI0aMoABQUVGRRgipCFdZYVGAW265pRLAifa5Cx7YdRsvnhPHWEj92ETJxEgznUY4Lo8kgYujTOiRFxWI1KanRhsz+vRnBz6hO3ufo01PuVybnnqpQMm/TywJwTUrZmPgrsJ2y76s92Aszh1zxG+zL7wDgXYGgNz37f8ipmmPEtDZ8HYXegIKCgqO+K6qarJhGNcVFxeLABAMBnNsNtvR89MnSdiig61W670+n+/5Y12Li4uLra+vzzAMo1MBodK/K8ZpjH92vOsZe0rw9uzJkA2tXfn5JAsmTP8ctUdFDRHcvvg53LLy7Xbls7LXQDx40xuvB+/s3enJIEpprCRJw1VV/eBY161W64z8/Px/Llu2LDwOp3BkAgCMMT07O1sEAKvVmiSK4stpaWkyAMyePbtOFMVOr2YVYSwiwDHDyswhP56c91C7Kx8A/j1s+jEqHwA43j1vKjTYNHcAABL+SURBVBotke3K59yda3D18tlXxL5VcdRG1x2Fcz7cZrMdd8cUzjkNV+UD4Y0MKi4vL88FAM65yDmPCIX22yqjR48GpRSd9QgG/tjbLwrHii0guHPRs+hTv7PdeRUlZmPeoOuPe92rOPDm0Kntzu8PS1+PtVaUbbe8Wv5o6pvr233fr5EkKb6+vr7GHRv1amFM5MNFPeMzKv54BwCgR48eDsbYSe9KcizCpgCpqalrGWODAMDv91cYhnFDVVXVIWtVkqTNuq53emdsp5WMFSiOGISft3kxJhS2K/gGAKAKEv469i8w6AkGG5zj8wGTsOsEQaWHo2gBPDLv0YiQajy1V7W9lPl2UbvlOYggCBEAvMVpPeO5YfQDCGV+/+cNH773izs+epjH4xlqsViWdjjjExA2BdiyZUuQEGJ+8sknj3l91qxZpZIk9etsObU3pngSI3GZSSS3ixQ1rpZaPPr1k0fM8p0IAuCtITdjZ1zbIYWaaMIrw+5st6F01p6NuH7ZG1ANeteugGNmO287BOd8jNVqnc8CgSEgZGn+Ps/T5vSMbFBSRigNEkIybrvttrDuoxjW1bqc84oXXngh9VjXLomOASHk0GigM1RO7sXUO1Jf6xet93jn42kl0f72rzjfFpOGd4ZOQ3unlH7MGYUNPc5oV1oOIL+y0C0J+BvhZGO7hcL+g6tEUUxqamqqhkCLwdi17tioG9XKiivAefrfolzFnPPQM88805Fs2ySsewQlJiZGNDQ0TAkGg0fsdVucluxkPu99l1HTzPr6+uGapoVlb6B1cVGjiaZ92d5l5zoRcOvN/0FJcpvBuodBkLdjJWa9M63NVoYIQg03K30Lqmo63E8TQgaYzWZlZZS1Hro+jJjM61ggMBmE2KnJ9Mw59c2psiyXt7S0bO5o3icirC3Anj17vJRSZfDgwYfyLck+QzaaPXNByPa9e/f6KaWKIAiddkH/ktFLJrrxakdiDj46ewJKUvI7WBJHUfq5WJbe9gQclaTHTqbyAUAUhGGTJk1aykPaQ+A4m/l8n4Fgr2Cz3XNWVW0JpbTfmDFjwlr5QBdsEUMIWVpUVDQMAEovugChvdUvgZAgN4z+AKAoymJCSKf3uzl7284QofTZ9jZhHktkzayL79ZxEtEkHGjbRSyK5QYnb3c4cwCEkDxBFIvv/H5hGgicxGR+FpSWAogxWlrmOByOZMZY1XvvvXcy2Z+QsCtAQUHBOkJIwd/+9jf4SzbcCkGIB8FLYMa1RUlx436MVGRRFBVKaadtATWp57+J1HYYFiHU76Ta+YbFdkxHVZtwjh3xZ+DrnONvRkIl+faCmvoOr9VSFAWiKI7KyMhYBMOIJ4I4k2uh8UQSZwIAEcWXVFUdFxcX98VJyd4GXbJPoMViGS2KYuVPNrPMQ6HJMIwQB3RqMvmobFpyORfcNTU1V2ia1ukdMNclRMskpH3HDWPo8dIQQXwsv775afPLO4cHDZz0kp04zx58+q8xsBy2oRQBwAVhRahnz/MGF23qcJ6U0jEWi6XK5/MdOhxjy7gxVC8rk7Wa6s+vFy3X7WzyXNNVZwh0yU6hZ5xxxgLDMEbnVe9bl1/ffHd+k/d+IcrxLDcMGAHf5K/0QLIkSdWU0k4fm1ZQ3RACpRMJFcp/fY0AIISWEEL/DgCS7i3rjM7XOhLx4TECSxvjez53kpVvF0Ux4/DKB4Az5n3FsjdsDkacO/SynU2e6+12+zHdwuGgSxTA7XYzznmx1WotAID1fVIV1tzyEwG3EEH8gYVCc37uGe+WJGmIyWTqtAz5dZ4aSOJVhJAjDTBCdEjiLXn7mkIAwGSb9+DwjxDSQinBoc8JtpE7nHeHTkXTYS7i4sQsTL71g4qTkZtSeofdbn+t5LxBx3SRDvhxmQtAqLa2NqyR1UfI0FUZX3755QsYYyP79+9PdY+nAEBZ6suzH1HOGzoPlJbojY2PRUREfMoYa3/47QnIr2ksFhTrDcBhU8aE/ju/tunQpj8RZho0y/TFKNnITo4Wo3tGS7ae0bItxSXbekeqMRYJ0yngJ+AgBMcM82q1ODB7yC37m34QvHXhtIqgLarDR8RTSsdIkrTsO0Wyq5s3r3fHOa8pObv/oetPP/00VFW90eFwdMkWsQfp0iNjrFZrFmMsc3VS/Pd6U8O3gmKdavh9zwEAVawPwjDoEI8Pqqoquq6HZetYd2zUw0wLzSSEVnCrJbtgd12HNqNOeWWdwjhxqIqzsd6LWsZx1Mkdsq7io39djh0xvfHoTbPuU29P6ZBxSQjJkGX5gqKz898MlG76kkjShzwUmgpCKgW7/c7c8t0tVqs1lzGWHK79lI9Hlx4Y4fP5SgAkDWtsoUQQZhle73dEEN8nFuVB5vW+yzVtuM/nc0uS5KCUhiXmXY+w/o3Kpo+IJE3raOUDwK47Cvy7p+dX193UK2iSyEQCbBYFoksChUBRapXYeGYyl/1r+Ay8fPE9oECPnu/ubvcsFyHEKYrihOTk5DfVih05hNKf8/bWfyCl9roYhGw3Wls/zsvNFRljY2644YYurXzgFBwalZCQYG5sbLz368ED/hZbucthtLRcwXX9EWo238e00HgAVLA77hm0p3aEqqqLOec1nS1z48XnAdYIZH+xsNPyD/qyBi1qiBIQmCllhVcnwjqrcnggxF7iQCrnXFFMdHVchHDpzuuSPMnvVaVVXp90lEF6ALMoig8lJiY++7nmK4BuZAk223v9d1Qesl02FuQqZ5eUTpEkaX5ra2tVpx+gDU7JqWEHjMHkn3snf6/trX5ViHQ8xoPBWBYMPkPMpvu4Gnrin+cNu/LjBQv+oKrqp5zzsO0n2JVIr+5VLCSY1xIiyynhdZSStYbBy/hdve75dVpKqUwpfczlcr2wgIcGc12/E4SUgbNBNMI29qxd1VUAYLVa8zjnaX6//9NT8Qyn5Oxgn8+3jjHmPHdnVXz+vubrDE/TDaBUBOcOIptqACTfW1KEhISE12VZngAgrFuhdRXa7T38RBDWipSvZRyxusFHExx5LM0BzJTSxxRF+VdNTU0j17SpVFEeSf9m4XQiSi+x1tYfinrGZ0RHRzsYYyPPP//8U1L5wCk8PDo2NvYtTdOucTgcdkKFHTwYvBiUrmKtraOp1Xpl/207WXl5ORs8ePBrZrN5NKU07VTJ1hmapyaHzoj0nyeL5DUBxmtmahzhaDrQ5z/kcrleaGlpqQMAIstzmd/3Rvm4K5PyahvfISbztCrOG30+312Kory4cGHnu67fJC6XK8JisTw5qX+2XBjr+HNhtG1ucc/4I7Y+LcnPlgtTEuIVi3K1IAhdfmpWV0IIyZAk6dGUlBSlOD0ltdAV+f767Ex5y7gr4I5zXlsYbdvuTojJmjZtGhRFmREREXHKD5A+pWcHA4CiKAmc8+vj4+P/d+fOnYfG7CX5ObJWUzMShERwxuxElj8YXNuYpet6qmEYHzHW9r77vyUOjPPjExMT3ywvL2clFw2l6vr1LwNwiNGuyRCFCKOhIY8Q2nhusy+Xc14SCATCtpVueznlCgAAFoslA8CosWPHvvhc7xTUv/2fMQAiYLEs4l7vIK6FnhKjY87vv63cGxkZGR8IBCbouj6PMdblVnFnoZTaKaV3SJK0bG1G2lq9sSH1rN01ZQCw6fzBYnDTpnfBeRAEGYI9ctK5tY2DDcOoCQaDS0+z6KcWs9mcpSjKjL59+2LDWVkyABQlxaUVOiMa3HFRf6yd9wncPaKHueOcV/Tv3x9ms3mcKIrXyLLcpefonSyKooBSOkaW5T9FR0dHAEBRUly/wmj79qLDurmiHq4BhdG2Xe54Vz+r1XqN2Wzu2B70Yea0tAAHMZlMmYSQMbm5uc+/HmyFtnv3ciKKH3Ndu45QYRVnbBRVlGksGLzOMnDQ1HNXrHH5/L5xhmGUjR8//vuPPuqy4/Q6BCEkTxTFUbIsLygcPLjYv37dFFDSmFfT+JU7LuoPXDcmmnr1ukRvbEo2WltuIbL8xpAm7wWMsYrT/c8/rQoAADabLVnTtBsj7fbnv7WIaWJ6+gajutqh19ZsJ7Lpbq7rYwmla6JGj3u+adFXIwSHY8XgXdXJmqYNNwxjG+f8+9NhH5hMJoRCoQGCIAwTRbE4IyNj0ZyGmiymqv8AIWVgbLhgt5/tGDu2peG9994H50kgpH6+03Xds1V7b+Ocrzgdff6vOe0KAACRkZF2VVVniKL4jtfrrXDHOR/mjMURgS7nmn6PkpV9YWDzpie4rv8ZonhT/j7POwBgt9vTg8HgcMZYkDG2gDHW6d3I2oJSagcwWhTFJEEQ1l577bVLZ8+eDQBwx0ePI5K0jWtaGg+FZkEQPqAWyzPM73ufUOGHKUR6s6y55Y+iKL7t9Xoru1rW9vCbUAAAyM7OpmVlZbcBqPg5MXYZlWSq1dUWUkW5igUCd4LzCBCSTC2WaWftrikBgKKEmALqcGwb1ezXGxoaRjLGYhljXsMwVsiyXBGO8OqCggK43e5YzvkwSZISAHitVuv8lWdmVqsV5Xng3Cm6XCuy128OAkBRz/hcFgjcL9jtTxjNzT8SqzKJaLo+pCUQYoyNVBTlxYaGhrAdmtVZfjMKcBBFUYZyzgdZLJZXfnRFRhgez/3gTDFn978nsL54uyUjs3e/n9eGAMAd77yR68ZUAJuJLP8r/v4HNpz7z1eUmpqaoYZhJAP7z+IxDKMCQJkgCDW6rh/33AKTySRrmhbLOU+jlKYfCNQAIaTeZrMtqa+vPzRP4Y51zOQGGwqCKnCkCjGxF+WWlvkBwJ0Ym8FV9QUQUrbDHvnUlD21V1NK6y+44IJPFyxYcLziTwu/OQUAAKfTaQ8EArcRQopXpqcu5d4WHbqeyX3+WfkNLecBwO7/eRL7/v3ibTTCtsho8bxAZNOX0LSbOGclRBAXij16LM4p3hRKT08XPR5Pss/nyzAMI54xpsiyrIdCIUXXdZFSykwmU1DTNAiCEAJQbzKZypOSkso2b97sr3r4YWiefWj67LNbuaHfAELKhUjHY4bH84ycknJTduGGUGFM5BuECt/m1TZ+CgDu+OiREGjdeR4/GGOjRVF861RM7JwMv0kFOIiiKIM558MEQfhoRWxUPQsEBuTtrV8MABvzcmioavcUMDYVnKUJcfG95bj4kFq2fTDz+b6W4nsk5mze5nXHOW/kjCUTQsqILG/mmlYtJfUMZheub9k66WrZNW5CqGHOHKruqYjNLtpUs753cqze2pqVX9e0pKhnfA5Xg5NA6Equaw8KEbZpRjB4AQx9fH59y4VVf3sGrSt+pP7Vaz4jlM7Kq2taBAAOhyNWVdUphJCyyZMnfzFr1qzT+yJPwG9aAQCgf//+4rZt264GkGwymT7weDxV5X+4BZ4v530MxjZxQhkYi8mvb74bANzx0YO5rj+RX998CQAUJcYlc0PL4oxdBMYyqWx6iqnqLDkh4ezQ3uqtUZOuP5P5fKz5y8935je09HAnxOSygP8XKsl306ioT4yGhs9A6SoAO/Lrmt6s/t9nsfeZp/dY+p/VN7Bh/UJwbgfBBlPvPjdcWF5p93q91wMIORyO9/bu3fub6euPx29eAQ7Sp08fuaqq6hoA8YIgLFh5RnqZUVk5huv6VGKx3JdXVbsBAApjHE8B2Je/z/NPAHAnxIzjIXUqOAcoLRNstoeM5pbviEDLuKaPy5g338rMMsouu6Qh5rbpca0//SgHS7esB7ifyKY5XNduIKL0CFfVR4gs384NYwg4H5tf33xxcd9ednBEXFjXJAZVdRyAkN1u/6Ar1/CFm9+NAhwkKytL3LFjx0hCSD/GWFVMTMz83bt3H1pQUeiK/EmwWqfm7qreBgBbx14m+9cXxzO//wlwvp4QxHDOHYTSb7nB/pNf3xxz4L5d1GbLTnp8Zkvl/Xf9LDgirzKaPAs5Z1lSbFxPo6lpONe0yaC0gtrtj4ynJk9VVdUISmk/xlhVXFzcvF27doX1YKxTwe9OAQ7y9ddfY/LkyUmqqo6mlEboul4dGRm55Lv0FE/sxEnBHnffi9p33sWeP921kHPugKHHE1GaDqAfZ+xscP4dOH9McDrzmdd7DVeDjxFJ/gKUfst1faLgcNzPdS3IfL57qCT/66zqfdU9evRweDyeCwgh6ZzzkCRJ31966aWbP/7449P9Ok6a360C/Jro6OgEn8837OC5eoZh1JnN5pKYmJht3428JNi88Kt4Fgh4LH37BgNbSocRShKMQFAXo6MXM59vJNe1Rmoye7iuV5h6pdXfrAOl27alBQKBHEmSEjjnlDHWYrFYlt11113bjhcG/3vj/4wCHM706dMxd+5cl8/ny9F1PV0QBIUQQgVBQDAYZFarlXq9XqbrOiilsNlsVFVVJooiDMMAIQS6rocIIRU2m21DZmZm9fLly39X09HddNNNN91000033XTTTTfddNNNN91000033XTTTTfddNNNN91000033XTTzf+P/D/GfG64fl67/wAAAABJRU5ErkJggg==" style="width:80px;height:80px;margin-bottom:12px;border-radius:50%;background:#fff;padding:4px;" />
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
        icon: path.join(PROJECT_ROOT, 'public', 'images', 'icons', 'pnmls-256.png'),
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
