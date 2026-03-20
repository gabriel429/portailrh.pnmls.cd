import client from '@/api/client'

/**
 * List requests with optional query params (statut, type, page).
 */
export function list(params = {}) {
    return client.get('/requests', { params })
}

/**
 * Get a single request by ID.
 */
export function get(id) {
    return client.get(`/requests/${id}`)
}

/**
 * Create a new request. Accepts FormData for file uploads.
 */
export function create(formData) {
    return client.post('/requests', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
}

/**
 * Update a request (status change by RH).
 */
export function update(id, data) {
    return client.put(`/requests/${id}`, data)
}

/**
 * Delete a request.
 */
export function remove(id) {
    return client.delete(`/requests/${id}`)
}
