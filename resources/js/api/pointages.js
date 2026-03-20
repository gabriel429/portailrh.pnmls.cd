import client from './client'

/**
 * List pointages (paginated) with optional filters.
 * @param {Object} params - { page, per_page, date, date_debut, date_fin, agent_id, department_id }
 */
export function list(params = {}) {
    return client.get('/pointages', { params })
}

/**
 * Get a single pointage by ID.
 * @param {number|string} id
 */
export function get(id) {
    return client.get(`/pointages/${id}`)
}

/**
 * Store bulk pointages.
 * @param {Object} data - { date_pointage, pointages: [{ agent_id, heure_entree, heure_sortie, observations }] }
 */
export function storeBulk(data) {
    return client.post('/pointages', data)
}

/**
 * Update a single pointage.
 * @param {number|string} id
 * @param {Object} data - { heure_entree, heure_sortie, heures_travaillees, observations }
 */
export function update(id, data) {
    return client.put(`/pointages/${id}`, data)
}

/**
 * Delete a pointage.
 * @param {number|string} id
 */
export function remove(id) {
    return client.delete(`/pointages/${id}`)
}

/**
 * Get daily report with attendance grouped by date.
 * @param {Object} params - { date_debut, date_fin, agent_id }
 */
export function daily(params = {}) {
    return client.get('/pointages/daily', { params })
}

/**
 * Get monthly report with per-agent summary.
 * @param {Object} params - { month } (format YYYY-MM)
 */
export function monthly(params = {}) {
    return client.get('/pointages/monthly', { params })
}

/**
 * Get agents for a specific department, with existing pointage data for a date.
 * @param {Object} params - { department_id, date }
 */
export function agentsByDepartment(params = {}) {
    return client.get('/pointages/agents-by-department', { params })
}

/**
 * Export daily report as Excel (returns blob).
 * @param {Object} params - { date_debut, date_fin, agent_id }
 */
export function exportDaily(params = {}) {
    return client.get('/pointages/daily/export', {
        params,
        responseType: 'blob',
    })
}

/**
 * Export monthly report as Excel (returns blob).
 * @param {Object} params - { month }
 */
export function exportMonthly(params = {}) {
    return client.get('/pointages/monthly/export', {
        params,
        responseType: 'blob',
    })
}
