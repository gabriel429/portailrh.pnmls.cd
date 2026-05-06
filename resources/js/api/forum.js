import client from '@/api/client'

export function list(params = {}) {
  return client.get('/forum', { params })
}

export function create(data) {
  return client.post('/forum', data)
}

export function remove(id) {
  return client.delete(`/forum/${id}`)
}
