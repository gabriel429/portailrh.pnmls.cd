/**
 * Sync Files — Upload/Download file attachments
 * Handles synchronization of document files, photos, etc.
 */
import fs from 'fs'
import path from 'path'
import http from 'http'
import https from 'https'

/**
 * Sync files between local and server.
 * Currently a stub — full implementation requires tracking which files are new.
 * @param {object} config - { serverUrl, localApiUrl, authToken, projectRoot }
 */
export async function syncFiles(config) {
    // TODO: Implement file sync
    // Strategy:
    // 1. Compare local storage/app/public/* with server files
    // 2. Upload new local files
    // 3. Download new server files
    // For now, file sync is handled by the record sync (file paths are synced,
    // actual file content needs manual transfer or future implementation)
    return { uploaded: 0, downloaded: 0 }
}

/**
 * Upload a single file to the server.
 */
export function uploadFile(serverUrl, filePath, uuid, table, field, authToken) {
    return new Promise((resolve, reject) => {
        if (!fs.existsSync(filePath)) {
            reject(new Error(`File not found: ${filePath}`))
            return
        }

        const url = new URL('/api/sync/files/upload', serverUrl)
        const client = url.protocol === 'https:' ? https : http

        const boundary = '----SyncBoundary' + Date.now()
        const fileName = path.basename(filePath)
        const fileContent = fs.readFileSync(filePath)

        const bodyParts = [
            `--${boundary}\r\nContent-Disposition: form-data; name="uuid"\r\n\r\n${uuid}\r\n`,
            `--${boundary}\r\nContent-Disposition: form-data; name="table"\r\n\r\n${table}\r\n`,
            `--${boundary}\r\nContent-Disposition: form-data; name="field"\r\n\r\n${field}\r\n`,
            `--${boundary}\r\nContent-Disposition: form-data; name="file"; filename="${fileName}"\r\nContent-Type: application/octet-stream\r\n\r\n`,
        ]

        const headerBuffer = Buffer.from(bodyParts.join(''))
        const footerBuffer = Buffer.from(`\r\n--${boundary}--\r\n`)
        const body = Buffer.concat([headerBuffer, fileContent, footerBuffer])

        const options = {
            hostname: url.hostname,
            port: url.port,
            path: url.pathname,
            method: 'POST',
            timeout: 60000,
            headers: {
                'Content-Type': `multipart/form-data; boundary=${boundary}`,
                'Content-Length': body.length,
                'Accept': 'application/json',
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
                catch { reject(new Error('Invalid upload response')) }
            })
        })

        req.on('error', reject)
        req.on('timeout', () => { req.destroy(); reject(new Error('Upload timeout')) })
        req.write(body)
        req.end()
    })
}

/**
 * Download a file from the server.
 */
export function downloadFile(serverUrl, uuid, table, field, destPath, authToken) {
    return new Promise((resolve, reject) => {
        const url = new URL(`/api/sync/files/download/${uuid}?table=${table}&field=${field}`, serverUrl)
        const client = url.protocol === 'https:' ? https : http

        const options = {
            hostname: url.hostname,
            port: url.port,
            path: url.pathname + url.search,
            method: 'GET',
            timeout: 60000,
            headers: { 'Accept': '*/*' },
        }

        if (authToken) {
            options.headers['Authorization'] = `Bearer ${authToken}`
        }

        const req = client.request(options, (res) => {
            if (res.statusCode !== 200) {
                reject(new Error(`Download failed: HTTP ${res.statusCode}`))
                return
            }

            const dir = path.dirname(destPath)
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true })
            }

            const writeStream = fs.createWriteStream(destPath)
            res.pipe(writeStream)
            writeStream.on('finish', () => resolve(destPath))
            writeStream.on('error', reject)
        })

        req.on('error', reject)
        req.on('timeout', () => { req.destroy(); reject(new Error('Download timeout')) })
        req.end()
    })
}
