/**
 * Sync Pull — Server → Local
 * Fetches modified records from the online server and applies them locally.
 */
import http from 'http'
import https from 'https'

/**
 * Pull changes from the server and apply to local database via local PHP API.
 * @param {object} config - { serverUrl, localApiUrl, authToken }
 * @param {string|null} lastSyncAt - ISO timestamp of last sync
 * @returns {{ recordCount: number, serverTime: string }}
 */
export async function pullFromServer(config, lastSyncAt = null) {
    // 1. Request changes from the online server
    const pullData = await serverRequest(config.serverUrl, '/api/sync/pull', 'POST', {
        last_sync_at: lastSyncAt,
        tables: [], // empty = all
    }, config.authToken)

    const serverTime = pullData.server_time
    const data = pullData.data || {}

    let recordCount = 0

    // 2. Apply each table's records to local database via the local PHP API
    for (const [table, records] of Object.entries(data)) {
        if (!Array.isArray(records) || records.length === 0) continue

        // Push to local database via local sync/push endpoint
        const localResult = await localRequest(config.localApiUrl, '/api/sync/push', 'POST', {
            [table]: records.map(record => ({
                uuid: record.uuid,
                data: record,
                action: record.deleted_at ? 'delete' : 'update',
                updated_at: record.updated_at,
            })),
        })

        recordCount += records.length
    }

    return { recordCount, serverTime }
}

/**
 * Make an HTTP request to the online server.
 */
function serverRequest(baseUrl, path, method, body, authToken) {
    return new Promise((resolve, reject) => {
        const url = new URL(path, baseUrl)
        const client = url.protocol === 'https:' ? https : http

        const postData = JSON.stringify(body)

        const options = {
            hostname: url.hostname,
            port: url.port,
            path: url.pathname,
            method,
            timeout: 30000,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Content-Length': Buffer.byteLength(postData),
            },
        }

        if (authToken) {
            options.headers['Authorization'] = `Bearer ${authToken}`
        }

        const req = client.request(options, (res) => {
            let data = ''
            res.on('data', (chunk) => data += chunk)
            res.on('end', () => {
                try {
                    resolve(JSON.parse(data))
                } catch {
                    reject(new Error(`Invalid JSON response from server: ${data.substring(0, 200)}`))
                }
            })
        })

        req.on('error', reject)
        req.on('timeout', () => { req.destroy(); reject(new Error('Server request timed out')) })
        req.write(postData)
        req.end()
    })
}

/**
 * Make an HTTP request to the local PHP server.
 */
function localRequest(baseUrl, path, method, body) {
    return new Promise((resolve, reject) => {
        const url = new URL(path, baseUrl)
        const postData = JSON.stringify(body)

        const options = {
            hostname: url.hostname,
            port: url.port,
            path: url.pathname,
            method,
            timeout: 15000,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Content-Length': Buffer.byteLength(postData),
            },
        }

        const req = http.request(options, (res) => {
            let data = ''
            res.on('data', (chunk) => data += chunk)
            res.on('end', () => {
                try {
                    resolve(JSON.parse(data))
                } catch {
                    reject(new Error(`Invalid JSON from local server: ${data.substring(0, 200)}`))
                }
            })
        })

        req.on('error', reject)
        req.on('timeout', () => { req.destroy(); reject(new Error('Local request timed out')) })
        req.write(postData)
        req.end()
    })
}
