import client from './client'

/**
 * List documents with optional filters (paginated).
 * @param {Object} params - { categorie, search, page }
 */
export function list(params = {}) {
    return client.get('/documents', { params })
}

/**
 * Get a single document by ID.
 * @param {number|string} id
 */
export function get(id) {
    return client.get(`/documents/${id}`)
}

/**
 * Create (upload) a new document.
 * @param {FormData} formData - multipart form data with fichier, nom_document, etc.
 */
export function create(formData) {
    return client.post('/documents', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
}

/**
 * Delete a document.
 * @param {number|string} id
 */
export function remove(id) {
    return client.delete(`/documents/${id}`)
}

/**
 * Download a document file (returns blob).
 * @param {number|string} id
 */
export function download(id) {
    return client.get(`/documents/${id}/download`, {
        responseType: 'blob',
    })
}
