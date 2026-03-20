import client from '@/api/client'

export function list(params = {}) {
    return client.get('/plan-travail', { params })
}

export function get(id) {
    return client.get(`/plan-travail/${id}`)
}

export function getCreateData() {
    return client.get('/plan-travail/create')
}

export function create(data) {
    return client.post('/plan-travail', data)
}

export function update(id, data) {
    return client.put(`/plan-travail/${id}`, data)
}

export function remove(id) {
    return client.delete(`/plan-travail/${id}`)
}

export function updateStatut(id, data) {
    return client.put(`/plan-travail/${id}/statut`, data)
}
