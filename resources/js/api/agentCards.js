import client from '@/api/client'

export function getAgentCardSettings() {
    return client.get('/admin/agent-card-settings')
}

export function updateAgentCardSettings(formData) {
    return client.post('/admin/agent-card-settings', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
}

export function getAgentCard(agentId) {
    return client.get(`/admin/agents/${agentId}/id-card`)
}

export function issueAgentCard(agentId, renew = false) {
    return client.post(`/admin/agents/${agentId}/id-card`, { renew })
}

export function verifyAgentCard(token) {
    return client.get(`/agent-cards/verify/${token}`)
}
