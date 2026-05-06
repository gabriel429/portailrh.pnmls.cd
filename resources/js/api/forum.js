import client from '@/api/client'

export function list(params = {}) {
  return client.get('/forum', { params })
}

export function create(data) {
  return client.post('/forum', data)
}

export function comment(postId, data) {
  return client.post(`/forum/${postId}/comments`, data)
}

export function reactToComment(commentId, data) {
  return client.post(`/forum/comments/${commentId}/reaction`, data)
}

export function remove(id) {
  return client.delete(`/forum/${id}`)
}

export function removeComment(id) {
  return client.delete(`/forum/comments/${id}`)
}
