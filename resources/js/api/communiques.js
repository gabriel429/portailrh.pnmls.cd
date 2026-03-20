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
    return client.put(`/communiques/${id}`, data)
}

export function remove(id) {
    return client.delete(`/communiques/${id}`)
}
