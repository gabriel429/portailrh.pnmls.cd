/**
 * PHP Built-in Server Manager
 * Starts/stops the PHP development server for the Laravel app.
 */
import { spawn } from 'child_process'
import path from 'path'
import fs from 'fs'
import net from 'net'

let phpProcess = null

/**
 * Resolve the path to the PHP binary.
 * In production (packaged), it's in the extraResources folder.
 * In development, use the system PHP or local WAMP PHP.
 */
export function getPhpPath(isDev) {
    if (isDev) {
        // Prefer desktop/bin/php if downloaded, fallback to WAMP PHP
        const localPhp = path.join(process.cwd(), 'desktop', 'bin', 'php', 'php.exe')
        if (fs.existsSync(localPhp)) return localPhp
        return 'C:\\wamp64\\bin\\php\\php8.2.26\\php.exe'
    }
    // In production, PHP is bundled in resources/php
    const resourcesPath = process.resourcesPath
    return path.join(resourcesPath, 'php', 'php.exe')
}

/**
 * Check if a port is available.
 */
function isPortFree(port) {
    return new Promise((resolve) => {
        const server = net.createServer()
        server.once('error', () => resolve(false))
        server.once('listening', () => {
            server.close(() => resolve(true))
        })
        server.listen(port, '127.0.0.1')
    })
}

/**
 * Wait for PHP server to be ready by attempting a connection.
 */
function waitForServer(host, port, timeout = 15000) {
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
                    reject(new Error(`PHP server did not start within ${timeout}ms`))
                } else {
                    setTimeout(tryConnect, 300)
                }
            })
            socket.once('timeout', () => {
                socket.destroy()
                if (Date.now() - start > timeout) {
                    reject(new Error(`PHP server timed out`))
                } else {
                    setTimeout(tryConnect, 300)
                }
            })
            socket.connect(port, host)
        }
        tryConnect()
    })
}

/**
 * Start the PHP built-in server.
 * @param {object} opts - { phpPath, projectRoot, host, port }
 * @returns {Promise<ChildProcess>}
 */
export async function startPhpServer({ phpPath, projectRoot, host = '127.0.0.1', port = 8100 }) {
    // Check port availability
    const free = await isPortFree(port)
    if (!free) {
        console.log(`[PHP] Port ${port} is busy, attempting to use it anyway...`)
    }

    const publicDir = path.join(projectRoot, 'public')

    console.log(`[PHP] Starting server: ${phpPath} -S ${host}:${port} -t ${publicDir}`)

    phpProcess = spawn(phpPath, [
        '-S', `${host}:${port}`,
        '-t', publicDir,
    ], {
        cwd: projectRoot,
        env: {
            ...process.env,
            APP_ENV: 'local',
        },
        stdio: ['ignore', 'pipe', 'pipe'],
        windowsHide: true,
    })

    phpProcess.stdout.on('data', (data) => {
        // Suppress noisy PHP built-in server logs in production
        if (process.env.NODE_ENV === 'development') {
            console.log(`[PHP] ${data.toString().trim()}`)
        }
    })

    phpProcess.stderr.on('data', (data) => {
        const msg = data.toString().trim()
        // PHP built-in server logs requests to stderr; only show errors
        if (msg.includes('Fatal') || msg.includes('Error') || msg.includes('Warning')) {
            console.error(`[PHP ERROR] ${msg}`)
        }
    })

    phpProcess.on('close', (code) => {
        console.log(`[PHP] Server exited with code ${code}`)
        phpProcess = null
    })

    // Wait for the server to accept connections
    await waitForServer(host, port)
    console.log(`[PHP] Server ready at http://${host}:${port}`)

    return phpProcess
}

/**
 * Stop the PHP server gracefully.
 */
export function stopPhpServer() {
    if (phpProcess) {
        console.log('[PHP] Stopping server...')
        phpProcess.kill('SIGTERM')
        // On Windows, SIGTERM may not work, force kill after 2s
        setTimeout(() => {
            if (phpProcess && !phpProcess.killed) {
                phpProcess.kill('SIGKILL')
            }
        }, 2000)
        phpProcess = null
    }
}

/**
 * Check if the PHP server is running.
 */
export function isPhpRunning() {
    return phpProcess !== null && !phpProcess.killed
}
