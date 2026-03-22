/**
 * Sync Push — Local → Server
 * Sends dirty (locally modified) records to the online server.
 */
import http from 'http'
import https from 'https'

/**
 * Push local dirty records to the online server.
 * @param {object} config - { serverUrl, localApiUrl, authToken }
 * @returns {{ accepted: number, conflicts: number, errors: number }}
 */
export async function pushToServer(config) {
    // 1. Get dirty records from local database
    const dirtyData = await getDirtyRecords(config.localApiUrl)

    if (!dirtyData || Object.keys(dirtyData).length === 0) {
        return { accepted: 0, conflicts: 0, errors: 0 }
    }

    // 2. Send to online server
    const result = await serverPush(config.serverUrl, dirtyData, config.authToken)

    // 3. Mark accepted records as synced locally
    if (result.accepted && result.accepted.length > 0) {
        await markSynced(config.localApiUrl, result.accepted)
    }

    // 4. Handle conflicts (server-wins: apply server version locally)
    if (result.conflicts && result.conflicts.length > 0) {
        await resolveConflicts(config.localApiUrl, result.conflicts)
    }

    return {
        accepted: result.accepted?.length || 0,
        conflicts: result.conflicts?.length || 0,
        errors: result.errors?.length || 0,
    }
}

/**
 * Get dirty records from the local database via PHP API.
 */
async function getDirtyRecords(localApiUrl) {
    // Use the local sync/pull with a special parameter to get dirty records
    const response = await localRequest(localApiUrl, '/api/sync/dirty', 'GET')
    return response.data || {}
}

/**
 * Send dirty records to the online server.
 */
async function serverPush(serverUrl, data, authToken) {
    return serverRequest(serverUrl, '/api/sync/push', 'POST', data, authToken)
}

/**
 * Mark records as synced in the local database.
 */
async function markSynced(localApiUrl, acceptedRecords) {
    await localRequest(localApiUrl, '/api/sync/mark-synced', 'POST', {
        records: acceptedRecords,
    })
}

/**
 * Resolve conflicts by applying server data locally (server-wins).
 */
async function resolveConflicts(localApiUrl, conflicts) {
    const updates = {}
    for (const conflict of conflicts) {
        if (!updates[conflict.table]) updates[conflict.table] = []
        updates[conflict.table].push({
            uuid: conflict.uuid,
            data: conflict.server_data,
            action: 'update',
            updated_at: conflict.server_updated_at,
        })
    }

    if (Object.keys(updates).length > 0) {
        await localRequest(localApiUrl, '/api/sync/push', 'POST', updates)
    }
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
                try { resolve(JSON.parse(data)) }
                catch { reject(new Error(`Invalid server response`)) }
            })
        })

        req.on('error', reject)
        req.on('timeout', () => { req.destroy(); reject(new Error('Server timeout')) })
        req.write(postData)
        req.end()
    })
}

/**
 * Make an HTTP request to the local PHP server.
 */
function localRequest(baseUrl, path, method, body = null) {
    return new Promise((resolve, reject) => {
        const url = new URL(path, baseUrl)
        const postData = body ? JSON.stringify(body) : ''

        const options = {
            hostname: url.hostname,
            port: url.port,
            path: url.pathname,
            method,
            timeout: 15000,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        }

        if (postData) {
            options.headers['Content-Length'] = Buffer.byteLength(postData)
        }

        const req = http.request(options, (res) => {
            let data = ''
            res.on('data', (chunk) => data += chunk)
            res.on('end', () => {
                try { resolve(JSON.parse(data)) }
                catch { reject(new Error(`Invalid local response`)) }
            })
        })

        req.on('error', reject)
        req.on('timeout', () => { req.destroy(); reject(new Error('Local timeout')) })
        if (postData) req.write(postData)
        req.end()
    })
}
