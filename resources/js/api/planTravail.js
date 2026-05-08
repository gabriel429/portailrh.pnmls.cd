import client from '@/api/client'

export function list(params = {}) {
    return client.get('/plan-travail', { params })
}

export function get(id, params = {}) {
    return client.get(`/plan-travail/${id}`, { params })
}

export function getCreateData(params = {}) {
    return client.get('/plan-travail/create', { params })
}

export function create(data, params = {}) {
    return client.post('/plan-travail', data, { params })
}

export function update(id, data, params = {}) {
    return client.put(`/plan-travail/${id}`, data, { params })
}

export function remove(id, params = {}) {
    return client.delete(`/plan-travail/${id}`, { params })
}

export function updateStatut(id, data, params = {}) {
    return client.put(`/plan-travail/${id}/statut`, data, { params })
}

export function dashboard(params = {}) {
    return client.get('/plan-travail/dashboard', { params })
}
