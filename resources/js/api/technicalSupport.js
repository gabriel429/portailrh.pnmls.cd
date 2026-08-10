import client from '@/api/client'

export const getTechnicalTickets = (params = {}) => client.get('/technical-support', { params })
export const getTechnicalTicket = id => client.get(`/technical-support/${id}`)
export const createTechnicalTicket = payload => client.post('/technical-support', payload)
export const replyToTechnicalTicket = (id, payload) => client.post(`/technical-support/${id}/messages`, payload)
export const updateTechnicalTicketStatus = (id, status) => client.put(`/technical-support/${id}/status`, { status })
export const getTechnicalSupportDashboard = () => client.get('/technical-support/dashboard')