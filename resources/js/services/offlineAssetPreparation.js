import { debugLog, reportError } from '@/utils/logger'

const INDEX_URL = '/build/offline-assets.json'
const CACHE_NAME = 'pnmls-build-assets'
const STATE_KEY = 'epnmls:offline-assets-state'
const EVENT_NAME = 'epnmls:offline-preparation'
const BATCH_SIZE = 6

class OfflineAssetPreparation {
    constructor() {
        this.running = null
        this.state = {
            status: 'idle',
            completed: 0,
            total: 0,
            percent: 0,
            version: null,
            persistent: false,
            error: null,
        }

        window.addEventListener('online', () => this.start())
    }

    getState() {
        return { ...this.state }
    }

    emit(patch = {}) {
        this.state = { ...this.state, ...patch }
        window.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: this.getState() }))
    }

    async start() {
        if (this.running || !navigator.onLine || !('caches' in window)) return this.running

        this.running = this.prepare().finally(() => {
            this.running = null
        })

        return this.running
    }

    async prepare() {
        try {
            const persistent = await this.requestPersistentStorage()
            const response = await fetch(`${INDEX_URL}?v=${Date.now()}`, {
                cache: 'no-store',
                credentials: 'same-origin',
            })

            if (!response.ok) throw new Error(`Index offline indisponible (${response.status})`)

            const index = await response.json()
            const assets = Array.isArray(index.assets) ? [...new Set(index.assets)] : []
            const cache = await caches.open(CACHE_NAME)
            const cachedRequests = await cache.keys()
            const cachedUrls = new Set(cachedRequests.map((request) => new URL(request.url).pathname))
            const pending = assets.filter((asset) => !cachedUrls.has(asset))
            let completed = assets.length - pending.length

            this.emit({
                status: pending.length ? 'preparing' : 'ready',
                completed,
                total: assets.length,
                percent: this.toPercent(completed, assets.length),
                version: index.version || null,
                persistent,
                error: null,
            })

            for (let offset = 0; offset < pending.length; offset += BATCH_SIZE) {
                if (!navigator.onLine) {
                    this.emit({ status: 'paused' })
                    return false
                }

                const batch = pending.slice(offset, offset + BATCH_SIZE)
                await Promise.all(batch.map((asset) => this.cacheAsset(cache, asset)))
                completed += batch.length
                this.emit({
                    status: 'preparing',
                    completed,
                    percent: this.toPercent(completed, assets.length),
                })
            }

            await this.removeOutdatedAssets(cache, new Set(assets))
            this.storeReadyState(index.version, assets.length, persistent)
            this.emit({ status: 'ready', completed: assets.length, percent: 100 })
            debugLog(`PWA: ${assets.length} ressources disponibles hors ligne`)
            return true
        } catch (error) {
            reportError('PWA: préparation hors ligne échouée:', error)
            this.emit({ status: 'error', error: error.message || 'Préparation impossible' })
            return false
        }
    }

    async cacheAsset(cache, asset) {
        const response = await fetch(asset, { cache: 'reload', credentials: 'same-origin' })
        if (!response.ok) throw new Error(`Ressource indisponible: ${asset}`)
        await cache.put(asset, response)
    }

    async removeOutdatedAssets(cache, expectedAssets) {
        const requests = await cache.keys()
        await Promise.all(requests.map((request) => {
            const pathname = new URL(request.url).pathname
            return expectedAssets.has(pathname) ? true : cache.delete(request)
        }))
    }

    async requestPersistentStorage() {
        if (!navigator.storage?.persist) return false

        try {
            if (await navigator.storage.persisted?.()) return true
            return await navigator.storage.persist()
        } catch (_) {
            return false
        }
    }

    storeReadyState(version, total, persistent) {
        try {
            localStorage.setItem(STATE_KEY, JSON.stringify({
                version,
                total,
                persistent,
                preparedAt: new Date().toISOString(),
            }))
        } catch (_) {
            // The cache remains usable when localStorage is unavailable.
        }
    }

    toPercent(completed, total) {
        return total > 0 ? Math.round((completed / total) * 100) : 100
    }
}

export default new OfflineAssetPreparation()