import client from '@/api/client'

/**
 * Fetch the authenticated user's full profile (agent + relations + stats).
 */
export function getProfile() {
    return client.get('/profile/full')
}

/**
 * Update editable profile fields.
 * Accepts a FormData object (for photo upload) or a plain object.
 */
export function updateProfile(data) {
    // If data is FormData, use POST with _method=PUT for file uploads
    if (data instanceof FormData) {
        data.append('_method', 'PUT')
        return client.post('/profile', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
    }
    return client.put('/profile', data)
}

/**
 * Change the authenticated user's password.
 */
export function updatePassword(data) {
    return client.put('/profile/password', data)
}
