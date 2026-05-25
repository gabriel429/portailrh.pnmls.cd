import client from '@/api/client'

export function list(params = {}) {
    return client.get('/communiques', { params })
}

export function get(id) {
    return client.get(`/communiques/${id}`)
}

export function create(data) {
    return client.post('/communiques', data)
}

export function update(id, data) {
    if (typeof FormData !== 'undefined' && data instanceof FormData) {
        if (!data.has('_method')) {
            data.append('_method', 'PUT')
        }

        return client.post(`/communiques/${id}`, data)
    }

    return client.put(`/communiques/${id}`, data)
}

export function remove(id) {
    return client.delete(`/communiques/${id}`)
}

export function markRead(id) {
    return client.post(`/communiques/${id}/read`)
}

export function readers(id) {
    return client.get(`/communiques/${id}/reads`)
}
