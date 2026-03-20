import client from '@/api/client'

export function list(params = {}) {
    return client.get('/taches', { params })
}

export function get(id) {
    return client.get(`/taches/${id}`)
}

export function getCreateData() {
    return client.get('/taches/create')
}

export function create(data) {
    return client.post('/taches', data)
}

export function updateStatut(id, data) {
    return client.put(`/taches/${id}/statut`, data)
}

export function addCommentaire(id, data) {
    return client.post(`/taches/${id}/commentaire`, data)
}
