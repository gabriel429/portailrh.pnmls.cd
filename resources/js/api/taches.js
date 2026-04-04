import client from '@/api/client'

export function list(params = {}) {
    return client.get('/taches', { params })
}

export function getSummary() {
    return client.get('/taches', { params: { summary: 1 } })
}

export function listAssignedByMe(params = {}) {
    return client.get('/taches', { params: { ...params, scope: 'created' } })
}

export function get(id) {
    return client.get(`/taches/${id}`)
}

export function getCreateData() {
    return client.get('/taches/create')
}

export function create(data) {
    if (data instanceof FormData) {
        return client.post('/taches', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
    }

    return client.post('/taches', data)
}

export function updateStatut(id, data) {
    if (data instanceof FormData) {
        data.append('_method', 'PUT')
        return client.post(`/taches/${id}/statut`, data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
    }

    return client.put(`/taches/${id}/statut`, data)
}

export function addCommentaire(id, data) {
    return client.post(`/taches/${id}/commentaire`, data)
}
