<template>
  <div class="card-print-page">
    <div class="toolbar no-print">
      <div>
        <button class="back-btn" type="button" @click="router.back()">
          <i class="fas fa-arrow-left"></i>
        </button>
        <div>
          <span class="eyebrow">Carte agent</span>
          <h1>{{ agentName || 'Carte d identite professionnelle' }}</h1>
        </div>
      </div>
      <div class="toolbar-actions">
        <router-link class="soft-btn" :to="{ name: 'admin.agent-cards.settings' }">
          <i class="fas fa-pen-ruler"></i>
          Modele
        </router-link>
        <router-link class="soft-btn" :to="{ name: 'agent-cards.scan' }">
          <i class="fas fa-qrcode"></i>
          Scanner
        </router-link>
        <button class="soft-btn" type="button" :disabled="busy || !agent" @click="generate(false)">
          <i class="fas fa-id-card"></i>
          {{ card ? 'Voir carte' : 'Generer' }}
        </button>
        <button class="renew-btn" type="button" :disabled="busy || !agent" @click="generate(true)">
          <i class="fas fa-rotate"></i>
          Renouveler
        </button>
        <button class="print-btn" type="button" :disabled="!card" @click="print">
          <i class="fas fa-print"></i>
          Imprimer
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner-border text-primary"></div>
      <span>Preparation de la carte...</span>
    </div>

    <template v-else>
      <div v-if="!card" class="empty-state no-print">
        <i class="fas fa-id-card"></i>
        <h2>Aucune carte active</h2>
        <p>Generez une carte avec une validite d'un an pour imprimer le recto et le verso.</p>
        <button class="print-btn" type="button" :disabled="busy" @click="generate(false)">
          <span v-if="busy" class="spinner-border spinner-border-sm"></span>
          <i v-else class="fas fa-plus"></i>
          Generer la carte
        </button>
      </div>

      <section v-else class="print-stage">
        <div class="status-strip no-print" :class="`status-${card.status}`">
          <i :class="statusIcon"></i>
          <span>{{ statusLabel }}</span>
          <strong>{{ card.serial }}</strong>
          <small>Validite: {{ formatDate(card.issued_at) }} au {{ formatDate(card.expires_at) }}</small>
        </div>

        <div class="cards-sheet">
          <article class="identity-card recto" :style="cardStyle">
            <div class="card-bg"></div>
            <img v-if="settings.logo_primary_url" class="card-watermark" :src="settings.logo_primary_url" alt="" aria-hidden="true">
            <header class="id-header">
              <img v-if="settings.logo_primary_url" :src="settings.logo_primary_url" alt="Logo PNMLS">
              <div>
                <strong>{{ settings.country }}</strong>
                <span>{{ settings.ministry }}</span>
                <span>{{ settings.program_name }}</span>
              </div>
              <img v-if="settings.logo_secondary_url" :src="settings.logo_secondary_url" alt="Logo secondaire">
            </header>

            <div class="id-title">{{ settings.card_title }}</div>

            <div class="front-body">
              <div class="agent-photo">
                <img v-if="agentPhoto" :src="agentPhoto" :alt="agentName">
                <span v-else>{{ initials }}</span>
              </div>

              <div class="identity-data">
                <h2>{{ agentName }}</h2>
                <p>{{ agentFunction }}</p>
                <dl>
                  <div><dt>Matricule</dt><dd>{{ agent.matricule_etat || agent.matricule || 'N/A' }}</dd></div>
                  <div><dt>Organe</dt><dd>{{ agent.organe || 'N/A' }}</dd></div>
                  <div><dt>Structure</dt><dd>{{ structureLabel }}</dd></div>
                  <div><dt>Province</dt><dd>{{ provinceLabel }}</dd></div>
                  <div><dt>Emission</dt><dd>{{ formatDate(card.issued_at) }}</dd></div>
                  <div><dt>Expiration</dt><dd>{{ formatDate(card.expires_at) }}</dd></div>
                </dl>
              </div>
            </div>

            <footer>
              <span>{{ card.serial }}</span>
              <span>{{ settings.subtitle }}</span>
            </footer>
          </article>

          <article class="identity-card verso" :style="cardStyle">
            <div class="card-bg"></div>
            <img v-if="settings.logo_primary_url" class="card-watermark" :src="settings.logo_primary_url" alt="" aria-hidden="true">
            <header class="id-header single">
              <div>
                <strong>{{ settings.subtitle }}</strong>
                <span>Verification securisee par QR code</span>
              </div>
            </header>

            <div class="back-body">
              <div class="qr-box">
                <img :src="qrImage" alt="QR verification">
              </div>
              <div class="back-copy">
                <h3>{{ settings.authority_title }}</h3>
                <div class="signature-line"></div>
                <p>{{ settings.signature_name || 'Signature autorisee' }}</p>
                <small>{{ settings.contact_line }}</small>
              </div>
            </div>

            <div class="verification-link">{{ card.verification_url }}</div>
            <footer>{{ settings.footer_note }}</footer>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getAgentCard, issueAgentCard } from '@/api/agentCards'
import { qrToSvgDataUri } from '@/utils/qrCode'
import { useUiStore } from '@/stores/ui'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const busy = ref(false)
const agent = ref(null)
const card = ref(null)
const settings = reactive({
  country: '',
  ministry: '',
  program_name: '',
  card_title: '',
  subtitle: '',
  authority_title: '',
  signature_name: '',
  contact_line: '',
  footer_note: '',
  primary_color: '#0077B5',
  accent_color: '#f6c343',
  logo_primary_url: '',
  logo_secondary_url: '',
})

const cardStyle = computed(() => ({
  '--primary': settings.primary_color || '#0077B5',
  '--accent': settings.accent_color || '#f6c343',
}))

const agentName = computed(() => {
  const a = agent.value || {}
  return a.nom_complet || [a.prenom, a.postnom, a.nom].filter(Boolean).join(' ')
})

const initials = computed(() => {
  const parts = agentName.value.split(/\s+/).filter(Boolean)
  return (parts[0]?.[0] || 'A') + (parts[1]?.[0] || '')
})

const agentPhoto = computed(() => {
  const photo = agent.value?.photo
  if (!photo) return ''
  return photo.startsWith('http') || photo.startsWith('/') ? photo : `/${photo}`
})

const agentFunction = computed(() => agent.value?.fonction || agent.value?.poste_actuel || agent.value?.role?.nom_role || 'Agent PNMLS')
const structureLabel = computed(() => agent.value?.departement?.nom || agent.value?.department?.nom || agent.value?.section?.nom || 'PNMLS')
const provinceLabel = computed(() => agent.value?.province?.nom_province || agent.value?.province?.nom || 'N/A')
const qrImage = computed(() => card.value?.verification_url ? qrToSvgDataUri(card.value.verification_url) : '')

const statusLabel = computed(() => ({
  valid: 'Carte valide',
  expired: 'Carte expiree',
  revoked: 'Carte revoquee',
}[card.value?.status] || 'Statut inconnu'))

const statusIcon = computed(() => ({
  valid: 'fas fa-circle-check',
  expired: 'fas fa-clock',
  revoked: 'fas fa-ban',
}[card.value?.status] || 'fas fa-circle-info'))

function assignPayload(payload = {}) {
  agent.value = payload.agent || null
  card.value = payload.card || null
  Object.assign(settings, payload.settings || {})
}

async function load() {
  loading.value = true
  try {
    const { data } = await getAgentCard(route.params.id)
    assignPayload(data.data || data)
  } catch (error) {
    ui.addToast(error.response?.data?.message || 'Impossible de charger la carte agent.', 'danger')
    router.push({ name: 'rh.agents.index' })
  } finally {
    loading.value = false
  }
}

async function generate(renew) {
  if (!agent.value && !route.params.id) return
  busy.value = true
  try {
    const { data } = await issueAgentCard(route.params.id, renew)
    assignPayload(data.data || data)
    ui.addToast(data.message || 'Carte agent generee.', 'success')
  } catch (error) {
    ui.addToast(error.response?.data?.message || 'Generation de la carte impossible.', 'danger')
  } finally {
    busy.value = false
  }
}

function print() {
  window.print()
}

function formatDate(value) {
  if (!value) return 'N/A'
  return new Date(value).toLocaleDateString('fr-FR')
}

onMounted(load)
</script>

<style scoped>
.card-print-page {
  color: #172033;
  padding-bottom: 2rem;
}

.toolbar {
  align-items: center;
  background: linear-gradient(135deg, #0077b5 0%, #0f8aa9 100%);
  border-radius: 16px;
  box-shadow: 0 18px 45px rgba(0, 119, 181, .16);
  color: #fff;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  padding: 1.1rem;
}

.toolbar > div:first-child {
  align-items: center;
  display: flex;
  gap: .85rem;
  min-width: 0;
}

.back-btn {
  align-items: center;
  background: rgba(255, 255, 255, .14);
  border: 1px solid rgba(255, 255, 255, .22);
  border-radius: 12px;
  color: #fff;
  display: inline-flex;
  height: 42px;
  justify-content: center;
  width: 42px;
}

.eyebrow {
  color: rgba(255, 255, 255, .72);
  display: block;
  font-size: .75rem;
  font-weight: 900;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.toolbar h1 {
  font-size: clamp(1.1rem, 2.3vw, 1.65rem);
  font-weight: 900;
  margin: 0;
}

.toolbar-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .55rem;
  justify-content: flex-end;
}

.soft-btn,
.renew-btn,
.print-btn {
  align-items: center;
  border: 0;
  border-radius: 12px;
  display: inline-flex;
  font-weight: 900;
  gap: .45rem;
  justify-content: center;
  min-height: 40px;
  padding: .65rem .9rem;
  text-decoration: none;
}

.soft-btn {
  background: rgba(255, 255, 255, .16);
  color: #fff;
}

.renew-btn {
  background: #f6c343;
  color: #172033;
}

.print-btn {
  background: #fff;
  color: #00679c;
}

.soft-btn:disabled,
.renew-btn:disabled,
.print-btn:disabled {
  opacity: .65;
}

.loading-state,
.empty-state {
  align-items: center;
  background: #fff;
  border: 1px solid rgba(148, 163, 184, .22);
  border-radius: 16px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
  display: flex;
  flex-direction: column;
  gap: .75rem;
  justify-content: center;
  min-height: 300px;
  text-align: center;
}

.empty-state i {
  color: #0077b5;
  font-size: 3rem;
}

.empty-state h2 {
  font-size: 1.3rem;
  font-weight: 900;
  margin: 0;
}

.empty-state p {
  color: #64748b;
  margin: 0;
}

.print-stage {
  display: grid;
  gap: 1rem;
}

.status-strip {
  align-items: center;
  background: #fff;
  border: 1px solid #d8e3eb;
  border-radius: 14px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
  display: flex;
  flex-wrap: wrap;
  gap: .75rem;
  padding: .85rem 1rem;
}

.status-strip i {
  color: #059669;
}

.status-expired i {
  color: #d97706;
}

.status-revoked i {
  color: #dc2626;
}

.status-strip span,
.status-strip strong {
  font-weight: 900;
}

.status-strip small {
  color: #64748b;
  font-weight: 700;
}

.cards-sheet {
  align-items: start;
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(2, max-content);
  justify-content: center;
  padding: 1rem 0;
}

.identity-card {
  background: #fff;
  border-radius: 4mm;
  box-shadow: 0 18px 44px rgba(15, 23, 42, .18);
  box-sizing: border-box;
  color: #172033;
  height: 54mm;
  overflow: hidden;
  padding: 3.2mm;
  position: relative;
  width: 85.6mm;
}

.card-bg {
  background: linear-gradient(135deg, var(--primary), #0f8aa9);
  height: 16mm;
  inset: 0 0 auto;
  position: absolute;
  z-index: 0;
}

.card-bg::after {
  background: var(--accent);
  border-radius: 999px;
  content: "";
  height: 24mm;
  opacity: .16;
  position: absolute;
  right: -7mm;
  top: 7mm;
  width: 40mm;
}

.card-watermark {
  filter: blur(1.8mm);
  height: 45mm;
  left: 50%;
  object-fit: contain;
  opacity: .075;
  pointer-events: none;
  position: absolute;
  top: 58%;
  transform: translate(-50%, -50%) rotate(-8deg);
  width: 45mm;
  z-index: 0;
}

.id-header {
  align-items: center;
  color: #fff;
  display: grid;
  gap: 1.8mm;
  grid-template-columns: 9.6mm 1fr 9.6mm;
  min-height: 10.8mm;
  position: relative;
  text-align: center;
  z-index: 2;
}

.id-header.single {
  grid-template-columns: 1fr;
  text-align: left;
}

.id-header img {
  background: #fff;
  border-radius: 2mm;
  height: 9.6mm;
  object-fit: contain;
  padding: 1mm;
  width: 9.6mm;
}

.id-header strong {
  display: block;
  font-size: 1.55mm;
  font-weight: 900;
  line-height: 1.1;
  text-transform: uppercase;
}

.id-header span {
  display: block;
  font-size: 1.16mm;
  font-weight: 700;
  line-height: 1.15;
}

.id-title {
  background: var(--accent);
  border-radius: 999px;
  color: #172033;
  display: inline-flex;
  font-size: 1.48mm;
  font-weight: 900;
  margin: 2mm 0 1.4mm;
  padding: .85mm 2.6mm;
  position: relative;
  z-index: 2;
}

.front-body {
  display: grid;
  gap: 2.6mm;
  grid-template-columns: 21mm 1fr;
  position: relative;
  z-index: 2;
}

.agent-photo {
  align-items: center;
  background: #e9f5fb;
  border: .8mm solid #fff;
  border-radius: 3mm;
  color: var(--primary);
  display: flex;
  font-size: 6.4mm;
  font-weight: 900;
  height: 25mm;
  justify-content: center;
  overflow: hidden;
}

.agent-photo img {
  height: 100%;
  object-fit: cover;
  width: 100%;
}

.identity-data h2 {
  color: #0f3552;
  font-size: 3.45mm;
  font-weight: 900;
  line-height: 1;
  margin: 0 0 .75mm;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  text-transform: uppercase;
  white-space: nowrap;
}

.identity-data p {
  color: #526176;
  font-size: 1.75mm;
  font-weight: 900;
  line-height: 1.15;
  margin: 0 0 1mm;
  max-height: 4.2mm;
  overflow: hidden;
}

.identity-data dl {
  display: grid;
  gap: .42mm;
  margin: 0;
}

.identity-data dl div {
  display: grid;
  grid-template-columns: 16.4mm 1fr;
  min-width: 0;
}

.identity-data dt {
  color: #64748b;
  font-size: 1.36mm;
  font-weight: 900;
  line-height: 1.1;
}

.identity-data dd {
  color: #172033;
  font-size: 1.45mm;
  font-weight: 800;
  line-height: 1.1;
  margin: 0;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.identity-card footer {
  align-items: center;
  bottom: 2.2mm;
  color: #64748b;
  display: flex;
  font-size: 1.35mm;
  font-weight: 900;
  justify-content: space-between;
  left: 3.2mm;
  position: absolute;
  right: 3.2mm;
  z-index: 2;
}

.back-body {
  align-items: center;
  display: grid;
  gap: 3.6mm;
  grid-template-columns: 27mm 1fr;
  margin-top: 5.8mm;
  position: relative;
  z-index: 2;
}

.qr-box {
  background: #fff;
  border: .35mm solid #d8e3eb;
  border-radius: 3mm;
  box-shadow: 0 5px 14px rgba(15, 23, 42, .12);
  height: 27mm;
  padding: 1.8mm;
  width: 27mm;
}

.qr-box img {
  height: 100%;
  width: 100%;
}

.back-copy h3 {
  color: #0f3552;
  font-size: 2.45mm;
  font-weight: 900;
  line-height: 1.1;
  margin: 0 0 5mm;
}

.signature-line {
  border-top: .35mm solid #172033;
  margin-bottom: 1.3mm;
  width: 32mm;
}

.back-copy p {
  font-size: 2.2mm;
  font-weight: 900;
  margin: 0 0 1mm;
}

.back-copy small {
  color: #526176;
  display: block;
  font-size: 1.75mm;
  font-weight: 700;
  line-height: 1.2;
}

.verification-link {
  bottom: 8mm;
  color: #64748b;
  font-size: 1.25mm;
  font-weight: 800;
  left: 3.2mm;
  overflow: hidden;
  position: absolute;
  right: 3.2mm;
  text-overflow: ellipsis;
  white-space: nowrap;
  z-index: 2;
}

@media (max-width: 940px) {
  .toolbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .toolbar-actions {
    justify-content: flex-start;
    width: 100%;
  }

  .cards-sheet {
    grid-template-columns: 1fr;
    justify-items: center;
  }
}

@media (max-width: 430px) {
  .toolbar-actions > * {
    flex: 1 1 calc(50% - .4rem);
  }

  .cards-sheet {
    transform: scale(.92);
    transform-origin: top center;
  }
}

@media print {
  @page {
    margin: 8mm;
    size: A4 landscape;
  }

  .no-print {
    display: none !important;
  }

  .card-print-page {
    padding: 0;
  }

  .cards-sheet {
    box-shadow: none;
    gap: 8mm;
    grid-template-columns: repeat(2, max-content);
    padding: 0;
  }

  .identity-card {
    box-shadow: none;
    break-inside: avoid;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>
