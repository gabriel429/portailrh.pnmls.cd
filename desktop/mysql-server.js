/**
 * MariaDB Portable Server Manager
 * Starts/stops a portable MariaDB instance for local data storage.
 */
import { spawn, execSync } from 'child_process'
import path from 'path'
import fs from 'fs'
import net from 'net'

let mysqldProcess = null

/**
 * Resolve paths for MariaDB binaries and data.
 */
export function getMariaDbPaths(isDev) {
    if (isDev) {
        // In development, use local desktop/bin/mariadb
        const base = path.join(process.cwd(), 'desktop', 'bin', 'mariadb')
        return {
            basedir: base,
            mysqld: path.join(base, 'bin', 'mysqld.exe'),
            mysql: path.join(base, 'bin', 'mysql.exe'),
            mysqladmin: path.join(base, 'bin', 'mysqladmin.exe'),
            mysqlInstallDb: path.join(base, 'bin', 'mysql_install_db.exe'),
            datadir: path.join(base, 'data'),
        }
    }
    // In production (packaged), MariaDB is in resources/mariadb
    const base = path.join(process.resourcesPath, 'mariadb')
    return {
        basedir: base,
        mysqld: path.join(base, 'bin', 'mysqld.exe'),
        mysql: path.join(base, 'bin', 'mysql.exe'),
        mysqladmin: path.join(base, 'bin', 'mysqladmin.exe'),
        mysqlInstallDb: path.join(base, 'bin', 'mysql_install_db.exe'),
        datadir: path.join(base, 'data'),
    }
}

/**
 * Check if this is the first launch (no data directory).
 */
export function isFirstLaunch(paths) {
    return !fs.existsSync(paths.datadir) || !fs.existsSync(path.join(paths.datadir, 'mysql'))
}

/**
 * Initialize MariaDB data directory (first launch only).
 */
export function initializeDatabase(paths) {
    console.log('[MariaDB] Initializing data directory...')

    // Create data dir if needed
    if (!fs.existsSync(paths.datadir)) {
        fs.mkdirSync(paths.datadir, { recursive: true })
    }

    // Run mysql_install_db to create system tables
    try {
        execSync(`"${paths.mysqlInstallDb}" --datadir="${paths.datadir}" --basedir="${paths.basedir}"`, {
            stdio: 'pipe',
            windowsHide: true,
        })
        console.log('[MariaDB] Data directory initialized successfully.')
    } catch (err) {
        console.error('[MariaDB] Failed to initialize:', err.message)
        throw err
    }
}

/**
 * Wait for MariaDB to accept connections.
 */
function waitForMariaDb(port, timeout = 20000) {
    return new Promise((resolve, reject) => {
        const start = Date.now()
        const tryConnect = () => {
            const socket = new net.Socket()
            socket.setTimeout(1000)
            socket.once('connect', () => {
                socket.destroy()
                resolve()
            })
            socket.once('error', () => {
                socket.destroy()
                if (Date.now() - start > timeout) {
                    reject(new Error(`MariaDB did not start within ${timeout}ms`))
                } else {
                    setTimeout(tryConnect, 500)
                }
            })
            socket.once('timeout', () => {
                socket.destroy()
                setTimeout(tryConnect, 500)
            })
            socket.connect(port, '127.0.0.1')
        }
        tryConnect()
    })
}

/**
 * Start MariaDB server.
 * @param {object} paths - from getMariaDbPaths()
 * @param {number} port - TCP port (default 3307)
 * @returns {Promise<ChildProcess>}
 */
export async function startMariaDb(paths, port = 3307) {
    if (isFirstLaunch(paths)) {
        initializeDatabase(paths)
    }

    console.log(`[MariaDB] Starting on port ${port}...`)

    mysqldProcess = spawn(paths.mysqld, [
        `--basedir=${paths.basedir}`,
        `--datadir=${paths.datadir}`,
        `--port=${port}`,
        '--skip-grant-tables',
        '--skip-networking=0',
        `--bind-address=127.0.0.1`,
        '--innodb-flush-method=normal',
        '--innodb-buffer-pool-size=64M',
        '--max-connections=10',
        '--console',
    ], {
        cwd: paths.basedir,
        stdio: ['ignore', 'pipe', 'pipe'],
        windowsHide: true,
    })

    mysqldProcess.stdout.on('data', (data) => {
        if (process.env.NODE_ENV === 'development') {
            console.log(`[MariaDB] ${data.toString().trim()}`)
        }
    })

    mysqldProcess.stderr.on('data', (data) => {
        const msg = data.toString().trim()
        if (msg.includes('ready for connections') || msg.includes('Version:')) {
            console.log(`[MariaDB] ${msg}`)
        } else if (msg.includes('ERROR') || msg.includes('Fatal')) {
            console.error(`[MariaDB ERROR] ${msg}`)
        }
    })

    mysqldProcess.on('close', (code) => {
        console.log(`[MariaDB] Exited with code ${code}`)
        mysqldProcess = null
    })

    await waitForMariaDb(port)
    console.log(`[MariaDB] Ready on port ${port}`)

    return mysqldProcess
}

/**
 * Create the application database if it doesn't exist.
 */
export function createDatabase(paths, port = 3307, dbName = 'portailrh_pnmls') {
    console.log(`[MariaDB] Creating database "${dbName}"...`)
    try {
        execSync(
            `"${paths.mysql}" --host=127.0.0.1 --port=${port} --user=root -e "CREATE DATABASE IF NOT EXISTS \`${dbName}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`,
            { stdio: 'pipe', windowsHide: true }
        )
        console.log(`[MariaDB] Database "${dbName}" ready.`)
    } catch (err) {
        console.error('[MariaDB] Failed to create database:', err.message)
        throw err
    }
}

/**
 * Stop MariaDB gracefully.
 */
export function stopMariaDb(paths, port = 3307) {
    if (mysqldProcess) {
        console.log('[MariaDB] Shutting down...')
        try {
            execSync(
                `"${paths.mysqladmin}" --host=127.0.0.1 --port=${port} --user=root shutdown`,
                { stdio: 'pipe', windowsHide: true, timeout: 10000 }
            )
        } catch {
            // If mysqladmin fails, force kill
            if (mysqldProcess && !mysqldProcess.killed) {
                mysqldProcess.kill('SIGKILL')
            }
        }
        mysqldProcess = null
    }
}

/**
 * Check if MariaDB is running.
 */
export function isMariaDbRunning() {
    return mysqldProcess !== null && !mysqldProcess.killed
}
