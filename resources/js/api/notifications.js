import client from '@/api/client'

export function list(params = {}) {
    return client.get('/notifications', { params })
}

export function markRead(id) {
    return client.post(`/notifications/${id}/read`)
}

export function markAllRead() {
    return client.post('/notifications/mark-all-read')
}

export function remove(id) {
    return client.delete(`/notifications/${id}`)
}
