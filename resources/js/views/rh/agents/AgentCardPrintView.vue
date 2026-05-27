<template>
  <div
    class="card-print-page"
    :class="{ 'pvc-mode': printMode === 'pvc' }"
    :style="calibrationStyle"
  >
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
          {{ printMode === 'pvc' ? 'Imprimer PVC' : 'Imprimer' }}
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

        <div class="print-options no-print">
          <div class="option-block">
            <span>Mode impression</span>
            <div class="segmented-control">
              <button type="button" :class="{ active: printMode === 'a4' }" @click="setPrintMode('a4')">
                <i class="fas fa-file-lines"></i>
                A4
              </button>
              <button type="button" :class="{ active: printMode === 'pvc' }" @click="setPrintMode('pvc')">
                <i class="fas fa-id-card"></i>
                PVC / Sigma
              </button>
            </div>
          </div>

          <div v-if="printMode === 'pvc'" class="pvc-controls">
            <label>
              <span>Faces</span>
              <select v-model="pvcSide">
                <option value="both">Recto + verso</option>
                <option value="front">Recto seulement</option>
                <option value="back">Verso seulement</option>
              </select>
            </label>
            <label>
              <span>Decalage X</span>
              <input v-model.number="pvcOffsetX" type="number" step="0.1">
            </label>
            <label>
              <span>Decalage Y</span>
              <input v-model.number="pvcOffsetY" type="number" step="0.1">
            </label>
            <button class="mini-btn" type="button" @click="savePrintPrefs">
              <i class="fas fa-save"></i>
              Sauver
            </button>
            <button class="mini-btn ghost" type="button" @click="resetCalibration">
              <i class="fas fa-rotate-left"></i>
              Reset
            </button>
          </div>
        </div>

        <div class="cards-sheet">
          <article class="identity-card recto" :class="printSideClass('front')" :style="cardStyle">
            <div class="card-bg"></div>
            <img v-if="watermarkLogo" class="card-watermark" :src="watermarkLogo" alt="" aria-hidden="true">
            <header class="id-header">
              <img v-if="settings.logo_primary_url" :src="settings.logo_primary_url" alt="Logo PNMLS">
              <div>
                <strong>{{ settings.country }}</strong>
                <span>{{ settings.ministry }}</span>
                <span>{{ settings.program_name }}</span>
              </div>
              <img v-if="settings.logo_secondary_url" :src="settings.logo_secondary_url" alt="Logo secondaire">
            </header>

            <div class="official-stripe" aria-hidden="true">
              <span></span><span></span><span></span>
            </div>

            <div class="id-title">
              <strong>{{ settings.card_title }}</strong>
            </div>

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
                  <div class="address-row"><dt>Adresse</dt><dd>{{ professionalAddress }}</dd></div>
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

          <article class="identity-card verso" :class="printSideClass('back')" :style="cardStyle">
            <div class="card-bg"></div>
            <header class="id-header single">
              <div>
                <strong>{{ settings.subtitle }}</strong>
                <span>Verification securisee par QR code</span>
              </div>
            </header>

            <div class="official-stripe" aria-hidden="true">
              <span></span><span></span><span></span>
            </div>

            <div class="back-card-title">
              <strong>{{ settings.card_title }}</strong>
            </div>

            <div class="back-body">
              <div class="qr-area">
                <div class="qr-box">
                  <img :src="qrImage" alt="QR verification">
                </div>
                <span>Scan E-PNMLS</span>
              </div>
              <div class="back-copy">
                <h3>{{ settings.authority_title }}</h3>
                <div class="signature-line"></div>
                <p>{{ settings.signature_name || 'Signature autorisee' }}</p>
                <small>{{ settings.contact_line }}</small>
              </div>
            </div>

            <footer>{{ settings.footer_note }}</footer>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getAgentCard, issueAgentCard } from '@/api/agentCards'
import { useUiStore } from '@/stores/ui'
import QRCode from 'qrcode'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const busy = ref(false)
const agent = ref(null)
const card = ref(null)
const qrImage = ref('')
const printMode = ref('a4')
const pvcSide = ref('both')
const pvcOffsetX = ref(0)
const pvcOffsetY = ref(0)
const professionalAddress = 'croisement boulevard triomphal/avenue de la liberation commune de Kasa-Vubu'
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

const calibrationStyle = computed(() => ({
  '--pvc-offset-x': `${Number(pvcOffsetX.value) || 0}mm`,
  '--pvc-offset-y': `${Number(pvcOffsetY.value) || 0}mm`,
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
const provinceLabel = computed(() => normalizeProvinceLabel(agent.value?.province?.nom_province || agent.value?.province?.nom))
const qrPayload = computed(() => {
  if (card.value?.token) return `PNMLS-CARD:${card.value.token}`
  return ''
})
const watermarkLogo = computed(() => settings.logo_secondary_url || settings.logo_primary_url || '/images/logo-pnmls.png')

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

const PRINT_PREFS_KEY = 'pnmls-agent-card-print-prefs'
const PRINT_STYLE_ID = 'pnmls-pvc-print-style'

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

function setPrintMode(mode) {
  printMode.value = mode === 'pvc' ? 'pvc' : 'a4'
  savePrintPrefs(false)
}

function printSideClass(side) {
  const hidden = printMode.value === 'pvc'
    && ((pvcSide.value === 'front' && side !== 'front') || (pvcSide.value === 'back' && side !== 'back'))

  return {
    'print-side-hidden': hidden,
    'single-page-card': printMode.value === 'pvc' && pvcSide.value !== 'both' && !hidden,
  }
}

function print() {
  preparePrintMode()
  window.setTimeout(() => window.print(), 60)
}

function formatDate(value) {
  if (!value) return 'N/A'
  return new Date(value).toLocaleDateString('fr-FR')
}

function normalizeProvinceLabel(value) {
  const label = String(value || '').trim()
  return !label || ['n/a', 'na', 'non applicable'].includes(label.toLowerCase()) ? 'NATIONAL' : label
}

function loadPrintPrefs() {
  try {
    const prefs = JSON.parse(localStorage.getItem(PRINT_PREFS_KEY) || '{}')
    if (prefs.printMode === 'pvc') printMode.value = 'pvc'
    if (['both', 'front', 'back'].includes(prefs.pvcSide)) pvcSide.value = prefs.pvcSide
    pvcOffsetX.value = Number.isFinite(Number(prefs.pvcOffsetX)) ? Number(prefs.pvcOffsetX) : 0
    pvcOffsetY.value = Number.isFinite(Number(prefs.pvcOffsetY)) ? Number(prefs.pvcOffsetY) : 0
  } catch (_) {
    pvcOffsetX.value = 0
    pvcOffsetY.value = 0
  }
}

function savePrintPrefs(showToast = true) {
  localStorage.setItem(PRINT_PREFS_KEY, JSON.stringify({
    printMode: printMode.value,
    pvcSide: pvcSide.value,
    pvcOffsetX: Number(pvcOffsetX.value) || 0,
    pvcOffsetY: Number(pvcOffsetY.value) || 0,
  }))

  if (showToast) ui.addToast('Reglages d impression enregistres.', 'success')
}

function resetCalibration() {
  pvcOffsetX.value = 0
  pvcOffsetY.value = 0
  savePrintPrefs(false)
}

function preparePrintMode() {
  document.body.classList.toggle('sigma-pvc-print', printMode.value === 'pvc')

  let style = document.getElementById(PRINT_STYLE_ID)
  if (printMode.value !== 'pvc') {
    style?.remove()
    return
  }

  if (!style) {
    style = document.createElement('style')
    style.id = PRINT_STYLE_ID
    document.head.appendChild(style)
  }

  style.textContent = `
@media print {
  @page {
    size: 85.6mm 54mm;
    margin: 0;
  }

  html,
  body.sigma-pvc-print {
    background: #fff !important;
    margin: 0 !important;
    min-height: 54mm !important;
    padding: 0 !important;
    width: 85.6mm !important;
  }

  body.sigma-pvc-print .no-print,
  body.sigma-pvc-print .status-strip {
    display: none !important;
  }

  body.sigma-pvc-print .card-print-page {
    margin: 0 !important;
    padding: 0 !important;
    width: 85.6mm !important;
  }

  body.sigma-pvc-print .print-stage,
  body.sigma-pvc-print .cards-sheet {
    display: block !important;
    gap: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    transform: none !important;
    width: 85.6mm !important;
  }

  body.sigma-pvc-print .identity-card {
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    break-after: page !important;
    box-sizing: border-box !important;
    display: block !important;
    height: 54mm !important;
    margin: 0 !important;
    page-break-after: always !important;
    position: relative !important;
    transform: translate(var(--pvc-offset-x, 0mm), var(--pvc-offset-y, 0mm)) !important;
    transform-origin: left top !important;
    width: 85.6mm !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  body.sigma-pvc-print .identity-card.print-side-hidden {
    display: none !important;
  }

  body.sigma-pvc-print .identity-card.single-page-card,
  body.sigma-pvc-print .cards-sheet > .identity-card:last-child {
    break-after: auto !important;
    page-break-after: auto !important;
  }
}
`
}

function cleanupPrintMode() {
  document.body.classList.remove('sigma-pvc-print')
  document.getElementById(PRINT_STYLE_ID)?.remove()
}

watch(qrPayload, async (value) => {
  if (!value) {
    qrImage.value = ''
    return
  }

  try {
    qrImage.value = await QRCode.toDataURL(value, {
      errorCorrectionLevel: 'H',
      margin: 5,
      width: 720,
      color: {
        dark: '#071827',
        light: '#ffffff',
      },
    })
  } catch (_) {
    qrImage.value = ''
  }
}, { immediate: true })

watch([pvcSide, pvcOffsetX, pvcOffsetY], () => savePrintPrefs(false))

onMounted(() => {
  loadPrintPrefs()
  window.addEventListener('beforeprint', preparePrintMode)
  window.addEventListener('afterprint', cleanupPrintMode)
  load()
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeprint', preparePrintMode)
  window.removeEventListener('afterprint', cleanupPrintMode)
  cleanupPrintMode()
})
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

.print-options {
  align-items: center;
  background: rgba(255, 255, 255, .94);
  border: 1px solid rgba(148, 163, 184, .24);
  border-radius: 14px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
  display: flex;
  flex-wrap: wrap;
  gap: .85rem;
  padding: .85rem 1rem;
}

.option-block,
.pvc-controls,
.pvc-controls label {
  align-items: center;
  display: flex;
  gap: .6rem;
}

.option-block > span,
.pvc-controls label span {
  color: #526176;
  font-size: .78rem;
  font-weight: 900;
}

.segmented-control {
  background: #eaf5fb;
  border: 1px solid #cfe2ee;
  border-radius: 12px;
  display: inline-flex;
  gap: .25rem;
  padding: .25rem;
}

.segmented-control button,
.mini-btn {
  align-items: center;
  border: 0;
  border-radius: 10px;
  display: inline-flex;
  font-weight: 900;
  gap: .4rem;
  justify-content: center;
  min-height: 36px;
  padding: .55rem .75rem;
}

.segmented-control button {
  background: transparent;
  color: #526176;
}

.segmented-control button.active {
  background: #0077b5;
  color: #fff;
  box-shadow: 0 6px 14px rgba(0, 119, 181, .2);
}

.pvc-controls {
  flex-wrap: wrap;
}

.pvc-controls select,
.pvc-controls input {
  background: #f8fbfd;
  border: 1px solid #d8e3eb;
  border-radius: 10px;
  color: #172033;
  font-weight: 800;
  min-height: 36px;
  padding: .45rem .6rem;
}

.pvc-controls input {
  width: 76px;
}

.mini-btn {
  background: #0f8aa9;
  color: #fff;
}

.mini-btn.ghost {
  background: #eef5f9;
  color: #0f3552;
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
  background:
    linear-gradient(116deg, transparent 0 69%, rgba(0, 119, 181, .08) 69.2%, rgba(0, 119, 181, .02) 100%),
    radial-gradient(circle at 78% 70%, rgba(0, 119, 181, .18), transparent 22mm),
    radial-gradient(circle at 50% 56%, rgba(246, 195, 67, .1), transparent 24mm),
    linear-gradient(180deg, #ffffff 0%, #f8fcff 100%);
  border: .25mm solid rgba(15, 53, 82, .16);
  border-radius: 4mm;
  box-shadow: 0 18px 44px rgba(15, 23, 42, .18);
  box-sizing: border-box;
  color: #172033;
  height: 54mm;
  overflow: hidden;
  padding: 3mm 3.3mm;
  position: relative;
  width: 85.6mm;
}

.identity-card.verso {
  background: #f8fcff;
}

.identity-card.verso::before {
  background: url('/images/pnmls.jpeg') center / cover no-repeat;
  content: "";
  filter: blur(.9mm) saturate(.86) contrast(1.04);
  inset: -1.8mm;
  opacity: .42;
  position: absolute;
  transform: scale(1.04);
  z-index: 0;
}

.card-bg {
  background:
    linear-gradient(90deg, rgba(0, 119, 181, .08), rgba(255, 255, 255, 0) 55%),
    linear-gradient(180deg, rgba(255, 255, 255, .95), rgba(248, 252, 255, .82));
  inset: 0;
  position: absolute;
  z-index: 0;
}

.identity-card.verso .card-bg {
  background:
    radial-gradient(circle at 76% 70%, rgba(0, 119, 181, .18), transparent 24mm),
    linear-gradient(116deg, rgba(255, 255, 255, .9) 0 58%, rgba(235, 247, 255, .72) 58.2% 100%),
    linear-gradient(180deg, rgba(255, 255, 255, .8), rgba(248, 252, 255, .9));
}

.card-bg::after {
  background: linear-gradient(135deg, rgba(0, 119, 181, .06), rgba(0, 119, 181, .24));
  clip-path: polygon(62% 0, 100% 0, 100% 100%, 24% 100%);
  content: "";
  height: 54mm;
  opacity: .72;
  position: absolute;
  right: 0;
  top: 0;
  width: 28mm;
}

.card-watermark {
  filter: blur(.55mm) saturate(1.05);
  height: 50mm;
  left: 68%;
  mix-blend-mode: multiply;
  object-fit: contain;
  opacity: .18;
  pointer-events: none;
  position: absolute;
  top: 58%;
  transform: translate(-50%, -50%) rotate(-10deg);
  width: 50mm;
  z-index: 1;
}

.id-header {
  align-items: center;
  color: #172033;
  display: grid;
  gap: 1.8mm;
  grid-template-columns: 9.6mm 1fr 9.6mm;
  min-height: 8.6mm;
  position: relative;
  text-align: center;
  z-index: 2;
}

.id-header.single {
  grid-template-columns: 1fr;
  text-align: left;
}

.id-header img {
  background: rgba(255, 255, 255, .82);
  border: .2mm solid rgba(148, 163, 184, .32);
  border-radius: 50%;
  filter: drop-shadow(0 .45mm 1mm rgba(15, 23, 42, .12));
  height: 8.6mm;
  mix-blend-mode: normal;
  object-fit: contain;
  padding: .7mm;
  width: 9.6mm;
}

.id-header strong {
  color: #82b8ee;
  display: block;
  font-size: 1.92mm;
  font-weight: 900;
  line-height: 1.04;
  text-transform: uppercase;
}

.id-header span {
  color: #172033;
  display: block;
  font-size: 1.28mm;
  font-weight: 800;
  line-height: 1.1;
}

.official-stripe {
  display: grid;
  gap: .4mm;
  grid-template-columns: 1fr 1fr 1fr;
  height: .75mm;
  margin: 1.2mm 0 0;
  position: relative;
  z-index: 2;
}

.official-stripe span:nth-child(1) {
  background: #c8333a;
}

.official-stripe span:nth-child(2) {
  background: #f6c343;
}

.official-stripe span:nth-child(3) {
  background: var(--primary);
}

.id-title {
  align-items: center;
  color: #7db8f2;
  display: flex;
  justify-content: center;
  margin: 1.2mm auto 1mm;
  max-width: 56mm;
  min-height: 5.8mm;
  position: relative;
  text-align: center;
  z-index: 2;
}

.id-title strong,
.back-card-title strong {
  display: block;
  font-size: 2.55mm;
  font-weight: 950;
  letter-spacing: .12mm;
  line-height: 1.05;
  text-transform: uppercase;
}

.front-body {
  display: grid;
  gap: 2.8mm;
  grid-template-columns: 24.5mm 1fr;
  position: relative;
  z-index: 2;
}

.agent-photo {
  align-items: center;
  background: #e9f5fb;
  border: .55mm solid #fff;
  border-radius: 2.4mm;
  color: var(--primary);
  display: flex;
  font-size: 6.8mm;
  font-weight: 900;
  height: 25.5mm;
  justify-content: center;
  overflow: hidden;
  width: 24.5mm;
}

.agent-photo img {
  height: 100%;
  object-fit: cover;
  width: 100%;
}

.identity-data h2 {
  color: #0f3552;
  font-size: 3.95mm;
  font-weight: 900;
  line-height: 1.04;
  margin: 0 0 .85mm;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  text-transform: uppercase;
  white-space: nowrap;
}

.identity-data p {
  color: #526176;
  font-size: 1.92mm;
  font-weight: 900;
  line-height: 1.15;
  margin: 0 0 1mm;
  max-height: 4.7mm;
  overflow: hidden;
}

.identity-data dl {
  display: grid;
  gap: .48mm;
  margin: 0;
}

.identity-data dl div {
  display: grid;
  grid-template-columns: 16.5mm 1fr;
  min-width: 0;
}

.identity-data dt {
  color: #64748b;
  font-size: 1.5mm;
  font-weight: 900;
  line-height: 1.1;
}

.identity-data dd {
  color: #172033;
  font-size: 1.56mm;
  font-weight: 900;
  line-height: 1.1;
  margin: 0;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.identity-data .address-row dd {
  display: -webkit-box;
  font-size: 1.34mm;
  line-height: 1.08;
  max-height: 3mm;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: normal;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.identity-card footer {
  align-items: center;
  bottom: 2mm;
  color: #64748b;
  display: flex;
  font-size: 1.28mm;
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
  gap: 4mm;
  grid-template-columns: 26mm 1fr;
  margin-top: .8mm;
  position: relative;
  z-index: 2;
}

.back-card-title {
  color: #7db8f2;
  margin: 2.4mm auto 1.2mm;
  max-width: 62mm;
  position: relative;
  text-align: center;
  z-index: 2;
}

.qr-area {
  align-items: center;
  display: grid;
  gap: 1mm;
  justify-items: center;
  min-width: 0;
}

.qr-area span {
  color: #64748b;
  font-size: 1.2mm;
  font-weight: 900;
  line-height: 1;
  text-transform: uppercase;
}

.qr-box {
  background: #fff;
  border: .35mm solid #cbdce8;
  border-radius: 3mm;
  box-shadow: 0 5px 14px rgba(15, 23, 42, .12);
  height: 25mm;
  padding: 1.1mm;
  width: 25mm;
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
  margin: 0 0 4.3mm;
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

@media (max-width: 940px) {
  .toolbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .toolbar-actions {
    justify-content: flex-start;
    width: 100%;
  }

  .print-options,
  .option-block,
  .pvc-controls {
    align-items: stretch;
    flex-direction: column;
  }

  .segmented-control,
  .pvc-controls label,
  .pvc-controls select,
  .mini-btn {
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
    border-color: rgba(15, 53, 82, .28);
    box-shadow: none;
    break-inside: avoid;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>
