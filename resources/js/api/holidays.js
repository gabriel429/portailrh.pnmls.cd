import client from './client'

export default {
  // Holiday Planning
  getHolidayPlannings(params = {}) {
    return client.get('/api/holiday-plannings', { params })
  },

  createHolidayPlanning(data) {
    return client.post('/api/holiday-plannings', data)
  },

  getHolidayPlanning(id) {
    return client.get(`/api/holiday-plannings/${id}`)
  },

  updateHolidayPlanning(id, data) {
    return client.put(`/api/holiday-plannings/${id}`, data)
  },

  deleteHolidayPlanning(id) {
    return client.delete(`/api/holiday-plannings/${id}`)
  },

  validateHolidayPlanning(id) {
    return client.post(`/api/holiday-plannings/${id}/validate`)
  },

  getHolidayCalendar(params = {}) {
    return client.get('/api/holiday-plannings/calendar', { params })
  },

  getHolidayStatistics(params = {}) {
    return client.get('/api/holiday-plannings/statistiques', { params })
  },

  exportHolidayPlannings(params = {}) {
    return client.get('/api/holiday-plannings/export', {
      params,
      responseType: 'blob'
    })
  },

  // Individual Holidays
  getHolidays(params = {}) {
    return client.get('/api/holidays', { params })
  },

  getPendingHolidays(params = {}) {
    return client.get('/api/holidays/pending', { params })
  },

  getActiveHolidays(params = {}) {
    return client.get('/api/holidays/active', { params })
  },

  createHoliday(data) {
    return client.post('/api/holidays', data)
  },

  getHoliday(id) {
    return client.get(`/api/holidays/${id}`)
  },

  updateHoliday(id, data) {
    return client.put(`/api/holidays/${id}`, data)
  },

  approveHoliday(id, data = {}) {
    return client.post(`/api/holidays/${id}/approve`, data)
  },

  refuseHoliday(id, data = {}) {
    return client.post(`/api/holidays/${id}/refuse`, data)
  },

  cancelHoliday(id, data = {}) {
    return client.post(`/api/holidays/${id}/cancel`, data)
  },

  markHolidayReturned(id, data = {}) {
    return client.post(`/api/holidays/${id}/mark-returned`, data)
  },

  // Agent Status
  getAgentStatuses(params = {}) {
    return client.get('/api/agent-statuses', { params })
  },

  getCurrentAgentStatuses(params = {}) {
    return client.get('/api/agent-statuses/current', { params })
  },

  getAgentStatusStatistics(params = {}) {
    return client.get('/api/agent-statuses/statistics', { params })
  },

  getAvailableAgents(params = {}) {
    return client.get('/api/agent-statuses/available', { params })
  },

  getAbsenceReport(params = {}) {
    return client.get('/api/agent-statuses/absence-report', { params })
  },

  createAgentStatus(data) {
    return client.post('/api/agent-statuses', data)
  },

  getAgentStatus(id) {
    return client.get(`/api/agent-statuses/${id}`)
  },

  approveAgentStatus(id, data = {}) {
    return client.post(`/api/agent-statuses/${id}/approve`, data)
  },

  extendAgentStatus(id, data) {
    return client.put(`/api/agent-statuses/${id}/extend`, data)
  },

  // Utility functions
  getAgentHolidayStats(agentId, params = {}) {
    return client.get(`/api/agents/${agentId}/holidays/stats`, { params })
  },

  getAgentStatusHistory(agentId, params = {}) {
    return client.get(`/api/agents/${agentId}/statuses/history`, { params })
  },

  checkAgentAvailability(agentId, params = {}) {
    return client.get(`/api/agents/${agentId}/availability`, { params })
  }
}