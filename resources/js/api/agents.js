import client from '@/api/client'

/**
 * List agents with optional filters.
 * @param {Object} params - { search, organe, province_id, department_id, statut }
 */
export function list(params = {}) {
    return client.get('/agents', { params })
}

/**
 * Get a single agent by ID with full relations.
 * @param {number|string} id
 */
export function get(id) {
    return client.get(`/agents/${id}`)
}

/**
 * Create a new agent.
 * @param {FormData} formData
 */
export function create(formData) {
    return client.post('/agents', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
}

/**
 * Update an existing agent.
 * Uses POST with _method=PUT for file upload support.
 * @param {number|string} id
 * @param {FormData} formData
 */
export function update(id, formData) {
    formData.append('_method', 'PUT')
    return client.post(`/agents/${id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
}

/**
 * Delete an agent.
 * @param {number|string} id
 */
export function remove(id) {
    return client.delete(`/agents/${id}`)
}

/**
 * Export agents as CSV (returns a download).
 * @param {Object} params - { organe, province_id, departement_id }
 */
export function exportCsv(params = {}) {
    return client.get('/agents/export', {
        params,
        responseType: 'blob',
    })
}

/**
 * Get form options for create/edit (organes, departments, provinces, grades, etc.)
 */
export function getFormOptions() {
    return client.get('/agents/form-options')
}
