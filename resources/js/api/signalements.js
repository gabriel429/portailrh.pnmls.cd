import client from '@/api/client'

export function list(params = {}) {
    return client.get('/signalements', { params })
}

export function get(id) {
    return client.get(`/signalements/${id}`)
}

export function create(data) {
    return client.post('/signalements', data)
}

export function update(id, data) {
    return client.put(`/signalements/${id}`, data)
}

export function remove(id) {
    return client.delete(`/signalements/${id}`)
}

export function getAgents() {
    return client.get('/signalements/agents')
}
