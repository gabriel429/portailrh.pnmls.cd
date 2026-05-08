import client from '@/api/client'

export function bootstrapExperience() {
  return client.get('/user-experience/bootstrap')
}

export function saveTour(action) {
  return client.post('/user-experience/tour', { action })
}

export function markCommuniqueRead(id) {
  return client.post(`/communiques/${id}/read`)
}

export function getCommuniqueReaders(id) {
  return client.get(`/communiques/${id}/reads`)
}

export function markForumRead(id) {
  return client.post(`/forum/${id}/read`)
}
