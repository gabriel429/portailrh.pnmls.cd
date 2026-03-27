import client from './client'

export default {
  // Holiday Planning
  getHolidayPlannings(params = {}) {
    return client.get('/holiday-plannings', { params })
  },

  createHolidayPlanning(data) {
    return client.post('/holiday-plannings', data)
  },

  getHolidayPlanning(id) {
    return client.get(`/holiday-plannings/${id}`)
  },

  updateHolidayPlanning(id, data) {
    return client.put(`/holiday-plannings/${id}`, data)
  },

  deleteHolidayPlanning(id) {
    return client.delete(`/holiday-plannings/${id}`)
  },

  validateHolidayPlanning(id) {
    return client.post(`/holiday-plannings/${id}/validate`)
  },

  getHolidayCalendar(params = {}) {
    return client.get('/holiday-plannings/calendar', { params })
  },

  getHolidayStatistics(params = {}) {
    return client.get('/holiday-plannings/statistiques', { params })
  },

  exportHolidayPlannings(params = {}) {
    return client.get('/holiday-plannings/export', {
      params,
      responseType: 'blob'
    })
  },

  // Individual Holidays
  getHolidays(params = {}) {
    return client.get('/holidays', { params })
  },

  getPendingHolidays(params = {}) {
    return client.get('/holidays/pending', { params })
  },

  getActiveHolidays(params = {}) {
    return client.get('/holidays/active', { params })
  },

  createHoliday(data) {
    return client.post('/holidays', data)
  },

  getHoliday(id) {
    return client.get(`/holidays/${id}`)
  },

  updateHoliday(id, data) {
    return client.put(`/holidays/${id}`, data)
  },

  approveHoliday(id, data = {}) {
    return client.post(`/holidays/${id}/approve`, data)
  },

  refuseHoliday(id, data = {}) {
    return client.post(`/holidays/${id}/refuse`, data)
  },

  cancelHoliday(id, data = {}) {
    return client.post(`/holidays/${id}/cancel`, data)
  },

  markHolidayReturned(id, data = {}) {
    return client.post(`/holidays/${id}/mark-returned`, data)
  },

  // Agent Status
  getAgentStatuses(params = {}) {
    return client.get('/agent-statuses', { params })
  },

  getCurrentAgentStatuses(params = {}) {
    return client.get('/agent-statuses/current', { params })
  },

  getAgentStatusStatistics(params = {}) {
    return client.get('/agent-statuses/statistics', { params })
  },

  getAvailableAgents(params = {}) {
    return client.get('/agent-statuses/available', { params })
  },

  getAbsenceReport(params = {}) {
    return client.get('/agent-statuses/absence-report', { params })
  },

  createAgentStatus(data) {
    return client.post('/agent-statuses', data)
  },

  getAgentStatus(id) {
    return client.get(`/agent-statuses/${id}`)
  },

  approveAgentStatus(id, data = {}) {
    return client.post(`/agent-statuses/${id}/approve`, data)
  },

  extendAgentStatus(id, data) {
    return client.put(`/agent-statuses/${id}/extend`, data)
  },

  // Utility functions
  getAgentHolidayStats(agentId, params = {}) {
    return client.get(`/agents/${agentId}/holidays/stats`, { params })
  },

  getAgentStatusHistory(agentId, params = {}) {
    return client.get(`/agents/${agentId}/statuses/history`, { params })
  },

  checkAgentAvailability(agentId, params = {}) {
    return client.get(`/agents/${agentId}/availability`, { params })
  }
}