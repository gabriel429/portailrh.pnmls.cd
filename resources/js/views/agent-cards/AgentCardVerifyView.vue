<template>
  <div class="verify-page">
    <div class="verify-shell">
      <div class="verify-head" :class="statusClass">
        <router-link class="back-link" :to="{ name: 'agent-cards.scan' }">
          <i class="fas fa-arrow-left"></i>
        </router-link>
        <div>
          <span>Verification carte agent</span>
          <h1>{{ title }}</h1>
        </div>
      </div>

      <div v-if="loading" class="verify-loading">
        <div class="spinner-border text-primary"></div>
        <span>Verification en cours...</span>
      </div>

      <div v-else-if="error" class="verify-error">
        <i class="fas fa-triangle-exclamation"></i>
        <h2>Carte introuvable</h2>
        <p>{{ error }}</p>
        <router-link class="primary-btn" :to="{ name: 'agent-cards.scan' }">Scanner une autre carte</router-link>
      </div>

      <template v-else>
        <section class="agent-card">
          <div class="photo">
            <img v-if="photoUrl" :src="photoUrl" :alt="agentName">
            <span v-else>{{ initials }}</span>
          </div>
          <div>
            <span class="status-pill" :class="statusClass">
              <i :class="statusIcon"></i>
              {{ statusLabel }}
            </span>
            <h2>{{ agentName }}</h2>
            <p>{{ agentFunction }}</p>
          </div>
        </section>

        <section class="details-grid">
          <div><span>Matricule</span><strong>{{ agent.matricule_etat || agent.matricule || 'N/A' }}</strong></div>
          <div><span>Organe</span><strong>{{ agent.organe || 'N/A' }}</strong></div>
          <div><span>Structure</span><strong>{{ structureLabel }}</strong></div>
          <div><span>Province</span><strong>{{ provinceLabel }}</strong></div>
          <div><span>Numero carte</span><strong>{{ card.serial }}</strong></div>
          <div><span>Validite</span><strong>{{ formatDate(card.issued_at) }} - {{ formatDate(card.expires_at) }}</strong></div>
        </section>

        <div class="action-row">
          <router-link class="soft-btn" :to="{ name: 'agent-cards.scan' }">
            <i class="fas fa-qrcode"></i>
            Scanner
          </router-link>
          <router-link v-if="agent?.id" class="primary-btn" :to="{ name: 'rh.agents.show', params: { id: agent.id } }">
            <i class="fas fa-user"></i>
            Fiche agent
          </router-link>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { verifyAgentCard } from '@/api/agentCards'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const agent = ref(null)
const card = ref(null)

const agentName = computed(() => {
  const a = agent.value || {}
  return a.nom_complet || [a.prenom, a.postnom, a.nom].filter(Boolean).join(' ') || 'Agent PNMLS'
})

const initials = computed(() => {
  const parts = agentName.value.split(/\s+/).filter(Boolean)
  return (parts[0]?.[0] || 'A') + (parts[1]?.[0] || '')
})

const photoUrl = computed(() => {
  const photo = agent.value?.photo
  if (!photo) return ''
  return photo.startsWith('http') || photo.startsWith('/') ? photo : `/${photo}`
})

const agentFunction = computed(() => agent.value?.fonction || agent.value?.poste_actuel || agent.value?.role?.nom_role || 'Agent PNMLS')
const structureLabel = computed(() => agent.value?.departement?.nom || agent.value?.department?.nom || agent.value?.section?.nom || 'PNMLS')
const provinceLabel = computed(() => agent.value?.province?.nom_province || agent.value?.province?.nom || 'N/A')

const statusClass = computed(() => `status-${card.value?.status || 'unknown'}`)
const statusLabel = computed(() => ({
  valid: 'Carte valide',
  expired: 'Carte expiree',
  revoked: 'Carte revoquee',
}[card.value?.status] || 'Verification impossible'))
const statusIcon = computed(() => ({
  valid: 'fas fa-circle-check',
  expired: 'fas fa-clock',
  revoked: 'fas fa-ban',
}[card.value?.status] || 'fas fa-circle-info'))
const title = computed(() => card.value ? statusLabel.value : 'Lecture du QR code')

async function load() {
  loading.value = true
  error.value = ''
  agent.value = null
  card.value = null

  try {
    const { data } = await verifyAgentCard(route.params.token)
    const payload = data.data || data
    agent.value = payload.agent
    card.value = payload.card
  } catch (err) {
    error.value = err.response?.data?.message || 'Le code scanne ne correspond a aucune carte agent.'
  } finally {
    loading.value = false
  }
}

function formatDate(value) {
  if (!value) return 'N/A'
  return new Date(value).toLocaleDateString('fr-FR')
}

watch(() => route.params.token, load)
onMounted(load)
</script>

<style scoped>
.verify-page {
  display: grid;
  min-height: calc(100vh - 150px);
  place-items: start center;
  padding: 1rem;
}

.verify-shell {
  background: #fff;
  border: 1px solid rgba(148, 163, 184, .24);
  border-radius: 20px;
  box-shadow: 0 20px 50px rgba(15, 23, 42, .12);
  max-width: 760px;
  overflow: hidden;
  width: 100%;
}

.verify-head {
  align-items: center;
  background: linear-gradient(135deg, #0077b5, #0f8aa9);
  color: #fff;
  display: flex;
  gap: .85rem;
  padding: 1.25rem;
}

.verify-head.status-expired {
  background: linear-gradient(135deg, #b45309, #d97706);
}

.verify-head.status-revoked,
.verify-head.status-unknown {
  background: linear-gradient(135deg, #991b1b, #dc2626);
}

.back-link {
  align-items: center;
  background: rgba(255, 255, 255, .15);
  border-radius: 12px;
  color: #fff;
  display: inline-flex;
  height: 42px;
  justify-content: center;
  text-decoration: none;
  width: 42px;
}

.verify-head span {
  color: rgba(255, 255, 255, .75);
  display: block;
  font-size: .75rem;
  font-weight: 900;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.verify-head h1 {
  font-size: clamp(1.25rem, 4vw, 2rem);
  font-weight: 900;
  margin: 0;
}

.verify-loading,
.verify-error {
  align-items: center;
  display: flex;
  flex-direction: column;
  gap: .75rem;
  min-height: 280px;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

.verify-error i {
  color: #dc2626;
  font-size: 3rem;
}

.verify-error h2 {
  font-weight: 900;
  margin: 0;
}

.verify-error p {
  color: #64748b;
  margin: 0;
}

.agent-card {
  align-items: center;
  display: grid;
  gap: 1rem;
  grid-template-columns: 96px 1fr;
  padding: 1.25rem;
}

.photo {
  align-items: center;
  background: #e9f5fb;
  border-radius: 18px;
  color: #0077b5;
  display: flex;
  font-size: 2rem;
  font-weight: 900;
  height: 96px;
  justify-content: center;
  overflow: hidden;
  width: 96px;
}

.photo img {
  height: 100%;
  object-fit: cover;
  width: 100%;
}

.status-pill {
  align-items: center;
  background: #e9f8f1;
  border-radius: 999px;
  color: #047857;
  display: inline-flex;
  font-size: .78rem;
  font-weight: 900;
  gap: .4rem;
  margin-bottom: .55rem;
  padding: .35rem .65rem;
}

.status-pill.status-expired {
  background: #fff7ed;
  color: #b45309;
}

.status-pill.status-revoked,
.status-pill.status-unknown {
  background: #fef2f2;
  color: #dc2626;
}

.agent-card h2 {
  color: #0f3552;
  font-size: clamp(1.3rem, 4vw, 2rem);
  font-weight: 900;
  margin: 0;
}

.agent-card p {
  color: #64748b;
  font-weight: 800;
  margin: .2rem 0 0;
}

.details-grid {
  border-top: 1px solid #e5edf3;
  display: grid;
  gap: 0;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.details-grid div {
  border-bottom: 1px solid #e5edf3;
  padding: 1rem 1.25rem;
}

.details-grid div:nth-child(odd) {
  border-right: 1px solid #e5edf3;
}

.details-grid span {
  color: #64748b;
  display: block;
  font-size: .78rem;
  font-weight: 900;
  margin-bottom: .25rem;
}

.details-grid strong {
  color: #172033;
  font-weight: 900;
}

.action-row {
  display: flex;
  flex-wrap: wrap;
  gap: .75rem;
  justify-content: flex-end;
  padding: 1.25rem;
}

.soft-btn,
.primary-btn {
  align-items: center;
  border: 0;
  border-radius: 12px;
  display: inline-flex;
  font-weight: 900;
  gap: .45rem;
  justify-content: center;
  min-height: 42px;
  padding: .7rem 1rem;
  text-decoration: none;
}

.soft-btn {
  background: #edf6fb;
  color: #0077b5;
}

.primary-btn {
  background: #0077b5;
  color: #fff;
}

@media (max-width: 560px) {
  .verify-page {
    padding: 0;
  }

  .verify-shell {
    border-radius: 0;
    min-height: calc(100vh - 64px);
  }

  .agent-card,
  .details-grid {
    grid-template-columns: 1fr;
  }

  .details-grid div:nth-child(odd) {
    border-right: 0;
  }

  .action-row > * {
    width: 100%;
  }
}
</style>
