<template>
  <div class="workflow-timeline" :class="{ compact }">
    <div class="wf-header">
      <h6 class="wf-title"><i class="fas fa-route me-2"></i>Circuit de validation</h6>
      <span v-if="currentStepLabel" class="wf-current-badge">
        <i class="fas fa-clock me-1"></i> En attente : {{ currentStepLabel }}
      </span>
    </div>

    <div class="wf-steps">
      <div
        v-for="(step, idx) in steps"
        :key="step.step"
        class="wf-step"
        :class="[`wf-${step.status}`, { 'wf-last': idx === steps.length - 1 }]"
      >
        <!-- Connector line -->
        <div v-if="idx > 0" class="wf-connector" :class="`wf-conn-${step.status}`"></div>

        <!-- Step circle -->
        <div class="wf-circle">
          <i v-if="step.status === 'validated'" class="fas fa-check"></i>
          <i v-else-if="step.status === 'rejected'" class="fas fa-times"></i>
          <i v-else-if="step.status === 'current'" class="fas fa-hourglass-half"></i>
          <span v-else class="wf-num">{{ idx + 1 }}</span>
        </div>

        <!-- Step info -->
        <div class="wf-info">
          <div class="wf-label">{{ step.label }}</div>
          <div v-if="step.status === 'validated' && step.validator" class="wf-detail">
            <i class="fas fa-user-check me-1"></i>
            {{ step.validator.nom_complet }}
            <span v-if="step.validated_at" class="wf-date">
              · {{ formatDate(step.validated_at) }}
            </span>
          </div>
          <div v-else-if="step.status === 'rejected'" class="wf-detail wf-rej">
            <i class="fas fa-ban me-1"></i> Rejeté à cette étape
          </div>
          <div v-else-if="step.status === 'current'" class="wf-detail wf-pending">
            <i class="fas fa-spinner fa-pulse me-1"></i> En attente de validation
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  steps: { type: Array, default: () => [] },
  compact: { type: Boolean, default: false },
})

const currentStepLabel = computed(() => {
  const current = props.steps.find(s => s.status === 'current')
  return current?.label || null
})

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
.workflow-timeline {
  background: #fff;
  border-radius: 12px;
  padding: 1.25rem;
  border: 1px solid #f1f5f9;
}
.wf-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
  gap: .5rem;
}
.wf-title {
  font-weight: 700;
  font-size: .9rem;
  color: #1e293b;
  margin: 0;
}
.wf-current-badge {
  font-size: .75rem;
  font-weight: 600;
  background: #fef3c7;
  color: #92400e;
  padding: 4px 12px;
  border-radius: 20px;
}

.wf-steps {
  display: flex;
  align-items: flex-start;
  gap: 0;
  position: relative;
}
.wf-step {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
  min-width: 0;
}

/* Connector line */
.wf-connector {
  position: absolute;
  top: 18px;
  right: 50%;
  left: calc(-50% + 18px);
  height: 3px;
  background: #e2e8f0;
  z-index: 0;
}
.wf-conn-validated { background: #22c55e; }
.wf-conn-current { background: linear-gradient(90deg, #22c55e, #f59e0b); }
.wf-conn-rejected { background: linear-gradient(90deg, #22c55e, #ef4444); }

/* Circle */
.wf-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .8rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #94a3b8;
  border: 3px solid #e2e8f0;
  z-index: 1;
  transition: all .3s;
}
.wf-validated .wf-circle {
  background: #dcfce7;
  color: #16a34a;
  border-color: #22c55e;
}
.wf-rejected .wf-circle {
  background: #fef2f2;
  color: #dc2626;
  border-color: #ef4444;
}
.wf-current .wf-circle {
  background: #fffbeb;
  color: #d97706;
  border-color: #f59e0b;
  box-shadow: 0 0 0 4px rgba(245, 158, 11, .15);
  animation: wfPulse 2s infinite;
}
@keyframes wfPulse {
  0%, 100% { box-shadow: 0 0 0 4px rgba(245, 158, 11, .15); }
  50% { box-shadow: 0 0 0 8px rgba(245, 158, 11, .08); }
}

.wf-num { font-size: .75rem; }

/* Info */
.wf-info {
  margin-top: .5rem;
  min-width: 0;
  width: 100%;
}
.wf-label {
  font-size: .78rem;
  font-weight: 700;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.wf-detail {
  font-size: .7rem;
  color: #64748b;
  margin-top: .15rem;
}
.wf-rej { color: #dc2626; }
.wf-pending { color: #d97706; }
.wf-date { color: #94a3b8; }

/* Compact mode */
.compact .wf-circle { width: 28px; height: 28px; font-size: .7rem; }
.compact .wf-connector { top: 14px; }
.compact .wf-label { font-size: .72rem; }
.compact .wf-detail { display: none; }

/* Responsive */
@media (max-width: 640px) {
  .wf-steps { flex-direction: column; align-items: flex-start; gap: 0; }
  .wf-step { flex-direction: row; align-items: center; gap: .75rem; width: 100%; padding: .35rem 0; }
  .wf-connector { display: none; }
  .wf-info { text-align: left; }
}
</style>
