<template>
  <Teleport to="body">
    <Transition name="online-agents-fade">
      <div v-if="open" class="online-agents-overlay" @click.self="$emit('close')">
        <div class="online-agents-panel">
          <div class="online-agents-header">
            <div>
              <h3>{{ title }}</h3>
              <p>Activité détectée dans les 30 dernières minutes</p>
            </div>
            <button type="button" class="online-agents-close" @click="$emit('close')" aria-label="Fermer">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div v-if="agents.length" class="online-agents-list">
            <div v-for="agent in agents" :key="agent.id" class="online-agents-row">
              <div class="online-agents-avatar">
                <img v-if="agent.photo" :src="agent.photo" :alt="agentName(agent)">
                <span v-else>{{ initials(agent) }}</span>
              </div>
              <div class="online-agents-info">
                <div class="online-agents-name">{{ agentName(agent) }}</div>
                <div class="online-agents-role">{{ agent.fonction || agent.poste_actuel || 'Agent PNMLS' }}</div>
              </div>
              <span class="online-agents-status" :class="{ recent: !isNow(agent) }">
                <span class="online-agents-dot"></span>
                {{ agent.label || 'Actif récemment' }}
              </span>
            </div>
          </div>

          <div v-else class="online-agents-empty">
            <i class="fas fa-user-clock"></i>
            <span>Aucun agent actif dans les 30 dernières minutes</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Agents en ligne' },
  agents: { type: Array, default: () => [] },
})

defineEmits(['close'])

function agentName(agent) {
  return [agent.prenom, agent.nom].filter(Boolean).join(' ') || 'Agent'
}

function initials(agent) {
  return agentName(agent)
    .split(/\s+/)
    .map((part) => part.charAt(0))
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

function isNow(agent) {
  return (agent.connected_seconds ?? 9999) < 60
}
</script>

<style scoped>
.online-agents-overlay {
  position: fixed;
  inset: 0;
  z-index: 10050;
  display: flex;
  justify-content: flex-end;
  background: rgba(15, 23, 42, .5);
  backdrop-filter: blur(4px);
}
.online-agents-panel {
  width: 420px;
  max-width: 94vw;
  height: 100vh;
  background: #f8fafc;
  box-shadow: -10px 0 36px rgba(15, 23, 42, .2);
  display: flex;
  flex-direction: column;
}
.online-agents-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.1rem;
  background: #0077B5;
  color: #fff;
}
.online-agents-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 800;
}
.online-agents-header p {
  margin: .15rem 0 0;
  font-size: .72rem;
  opacity: .76;
}
.online-agents-close {
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 8px;
  background: rgba(255,255,255,.16);
  color: #fff;
  display: grid;
  place-items: center;
}
.online-agents-list {
  padding: .7rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: .55rem;
}
.online-agents-row {
  display: flex;
  align-items: center;
  gap: .75rem;
  min-width: 0;
  padding: .7rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
}
.online-agents-avatar {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
  background: #e0f2fe;
  color: #0077B5;
  display: grid;
  place-items: center;
  font-size: .78rem;
  font-weight: 800;
}
.online-agents-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.online-agents-info {
  flex: 1;
  min-width: 0;
}
.online-agents-name {
  font-size: .84rem;
  font-weight: 800;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.online-agents-role {
  margin-top: .1rem;
  font-size: .7rem;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.online-agents-status {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  flex-shrink: 0;
  padding: .25rem .45rem;
  border-radius: 999px;
  background: #dcfce7;
  color: #166534;
  font-size: .68rem;
  font-weight: 800;
}
.online-agents-status.recent {
  background: #e0f2fe;
  color: #0369a1;
}
.online-agents-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: currentColor;
}
.online-agents-empty {
  margin: 1rem;
  min-height: 150px;
  display: grid;
  place-items: center;
  gap: .45rem;
  color: #94a3b8;
  text-align: center;
  font-size: .82rem;
  font-weight: 700;
}
.online-agents-empty i {
  font-size: 1.5rem;
  color: #0077B5;
}
.online-agents-fade-enter-active,
.online-agents-fade-leave-active {
  transition: opacity .18s ease;
}
.online-agents-fade-enter-from,
.online-agents-fade-leave-to {
  opacity: 0;
}
@media (max-width: 575.98px) {
  .online-agents-overlay { align-items: flex-end; }
  .online-agents-panel {
    width: 100vw;
    max-width: 100vw;
    height: 82vh;
    border-radius: 14px 14px 0 0;
    overflow: hidden;
  }
}
</style>
