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

export function validateTask(id, data = {}) {
    return client.post(`/taches/${id}/validate`, data)
}

export function rejectTask(id, data = {}) {
    return client.post(`/taches/${id}/reject`, data)
}

export function submitReport(id, data) {
    if (data instanceof FormData) {
        return client.post(`/taches/${id}/report`, data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
    }

    return client.post(`/taches/${id}/report`, data)
}

export function viewReports(id) {
    return client.get(`/taches/${id}/reports`)
}

export function update(id, data) {
    return client.put(`/taches/${id}`, data)
}

export function destroy(id) {
    return client.delete(`/taches/${id}`)
}
