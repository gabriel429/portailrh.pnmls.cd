<template>
  <div class="scanner-page">
    <section class="scanner-shell">
      <header>
        <router-link class="back-link" :to="{ name: 'dashboard' }">
          <i class="fas fa-arrow-left"></i>
        </router-link>
        <div>
          <span>Verification</span>
          <h1>Scanner une carte agent</h1>
        </div>
      </header>

      <div class="scanner-body">
        <div class="camera-frame">
          <video ref="videoRef" autoplay muted playsinline></video>
          <canvas ref="canvasRef" class="scan-canvas" aria-hidden="true"></canvas>
          <div class="scan-window"></div>
          <div v-if="!cameraReady" class="camera-placeholder">
            <i class="fas fa-qrcode"></i>
            <span>{{ cameraMessage }}</span>
          </div>
        </div>

        <aside class="scan-panel">
          <div class="status-card" :class="{ active: cameraReady }">
            <i class="fas" :class="cameraReady ? 'fa-video' : 'fa-mobile-screen'"></i>
            <div>
              <strong>{{ cameraReady ? 'Lecture QR active' : 'Camera en attente' }}</strong>
              <span>{{ supportMessage }}</span>
            </div>
          </div>

          <form class="manual-form" @submit.prevent="openManualToken">
            <label for="manual-token">Code ou lien QR</label>
            <div>
              <input id="manual-token" v-model.trim="manualValue" type="text" placeholder="Coller le lien ou le token">
              <button type="submit">
                <i class="fas fa-arrow-right"></i>
              </button>
            </div>
          </form>

          <button class="primary-btn" type="button" @click="startCamera">
            <i class="fas fa-camera"></i>
            Activer la camera
          </button>
        </aside>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import jsQR from 'jsqr'

const router = useRouter()
const ui = useUiStore()
const videoRef = ref(null)
const canvasRef = ref(null)
const stream = ref(null)
const detector = ref(null)
const cameraReady = ref(false)
const cameraMessage = ref('Activez la camera pour scanner le QR code imprime au verso.')
const manualValue = ref('')
let scanTimer = null
let scanning = false

const supportMessage = computed(() => {
  if (!cameraReady.value) return 'Activez la camera pour scanner la carte.'
  if (detector.value) return 'Lecture native active: placez le QR code dans le cadre.'
  return 'Mode compatible actif: l application analyse l image de la camera.'
})

function extractToken(value) {
  const input = String(value || '').trim()
  if (!input) return ''
  const compact = input.match(/^PNMLS-CARD:([A-Za-z0-9]+)/i)
  if (compact?.[1]) return compact[1]
  const match = input.match(/agent-cards\/verify\/([^/?#]+)/)
  return decodeURIComponent(match?.[1] || input)
}

function navigateToToken(value) {
  const token = extractToken(value)
  if (!token) return
  stopCamera()
  router.push({ name: 'agent-cards.verify', params: { token } })
}

function openManualToken() {
  const token = extractToken(manualValue.value)
  if (!token) {
    ui.addToast('Veuillez coller un code QR ou un lien valide.', 'warning')
    return
  }
  navigateToToken(token)
}

async function startCamera() {
  if (!navigator.mediaDevices?.getUserMedia) {
    cameraMessage.value = 'Camera non disponible dans ce navigateur.'
    return
  }

  try {
    if ('BarcodeDetector' in window && !detector.value) {
      detector.value = new window.BarcodeDetector({ formats: ['qr_code'] })
    }

    stream.value = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' },
        width: { ideal: 1920 },
        height: { ideal: 1080 },
      },
      audio: false,
    })

    if (videoRef.value) {
      videoRef.value.srcObject = stream.value
      await videoRef.value.play()
    }

    cameraReady.value = true
    cameraMessage.value = 'Camera active.'
    startLoop()
  } catch (error) {
    cameraReady.value = false
    cameraMessage.value = 'Autorisez la camera ou utilisez la saisie manuelle.'
    ui.addToast('Impossible d activer la camera.', 'warning')
  }
}

function startLoop() {
  if (!videoRef.value) return
  stopLoop()
  scanTimer = window.setInterval(async () => {
    if (scanning || !videoRef.value || videoRef.value.readyState < 2) return
    scanning = true
    try {
      let value = ''

      if (detector.value) {
        try {
          value = await detectWithNativeApi()
        } catch (_) {
          detector.value = null
        }
      }

      if (!value) {
        value = detectWithCanvas()
      }

      if (value) navigateToToken(value)
    } catch (_) {
      detector.value = null
    } finally {
      scanning = false
    }
  }, 260)
}

async function detectWithNativeApi() {
  const codes = await detector.value.detect(videoRef.value)
  return codes?.[0]?.rawValue || ''
}

function detectWithCanvas() {
  const video = videoRef.value
  const canvas = canvasRef.value
  if (!video || !canvas || !video.videoWidth || !video.videoHeight) return ''

  const scale = Math.min(1, 960 / video.videoWidth)
  const width = Math.max(1, Math.floor(video.videoWidth * scale))
  const height = Math.max(1, Math.floor(video.videoHeight * scale))
  const ctx = canvas.getContext('2d', { willReadFrequently: true })
  if (!ctx) return ''

  canvas.width = width
  canvas.height = height
  ctx.drawImage(video, 0, 0, width, height)

  const imageData = ctx.getImageData(0, 0, width, height)
  const fullCode = jsQR(imageData.data, width, height, { inversionAttempts: 'attemptBoth' })
  if (fullCode?.data) return fullCode.data

  const side = Math.floor(Math.min(width, height) * .72)
  const sx = Math.floor((width - side) / 2)
  const sy = Math.floor((height - side) / 2)
  const cropData = ctx.getImageData(sx, sy, side, side)
  const cropCode = jsQR(cropData.data, side, side, { inversionAttempts: 'attemptBoth' })
  return cropCode?.data || ''
}

function stopLoop() {
  if (scanTimer) {
    window.clearInterval(scanTimer)
    scanTimer = null
  }
}

function stopCamera() {
  stopLoop()
  stream.value?.getTracks?.().forEach(track => track.stop())
  stream.value = null
  cameraReady.value = false
}

onMounted(() => {
  startCamera()
})

onBeforeUnmount(stopCamera)
</script>

<style scoped>
.scanner-page {
  display: grid;
  min-height: calc(100vh - 150px);
  padding: 1rem;
  place-items: start center;
}

.scanner-shell {
  background: #fff;
  border: 1px solid rgba(148, 163, 184, .24);
  border-radius: 20px;
  box-shadow: 0 20px 50px rgba(15, 23, 42, .12);
  max-width: 980px;
  overflow: hidden;
  width: 100%;
}

.scanner-shell header {
  align-items: center;
  background: linear-gradient(135deg, #0077b5, #0f8aa9);
  color: #fff;
  display: flex;
  gap: .85rem;
  padding: 1.2rem;
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

header span {
  color: rgba(255, 255, 255, .75);
  display: block;
  font-size: .75rem;
  font-weight: 900;
  letter-spacing: .08em;
  text-transform: uppercase;
}

header h1 {
  font-size: clamp(1.3rem, 4vw, 2rem);
  font-weight: 900;
  margin: 0;
}

.scanner-body {
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(0, 1.25fr) 320px;
  padding: 1rem;
}

.camera-frame {
  aspect-ratio: 4 / 3;
  background: #0f172a;
  border-radius: 18px;
  overflow: hidden;
  position: relative;
}

.camera-frame video {
  height: 100%;
  object-fit: cover;
  width: 100%;
}

.scan-canvas {
  display: none;
}

.scan-window {
  border: 3px solid #f6c343;
  border-radius: 22px;
  box-shadow: 0 0 0 999px rgba(15, 23, 42, .35);
  height: min(62%, 330px);
  left: 50%;
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  width: min(62%, 330px);
}

.camera-placeholder {
  align-items: center;
  color: #fff;
  display: flex;
  flex-direction: column;
  gap: .75rem;
  inset: 0;
  justify-content: center;
  padding: 1.5rem;
  position: absolute;
  text-align: center;
}

.camera-placeholder i {
  font-size: 4rem;
}

.camera-placeholder span {
  max-width: 340px;
}

.scan-panel {
  display: grid;
  gap: 1rem;
}

.status-card {
  align-items: center;
  background: #f8fbfd;
  border: 1px solid #e2edf4;
  border-radius: 16px;
  display: flex;
  gap: .85rem;
  padding: 1rem;
}

.status-card.active {
  background: #ecfdf5;
  border-color: #bbf7d0;
}

.status-card i {
  align-items: center;
  background: #e4f3fb;
  border-radius: 14px;
  color: #0077b5;
  display: inline-flex;
  height: 44px;
  justify-content: center;
  width: 44px;
}

.status-card strong,
.status-card span {
  display: block;
}

.status-card strong {
  color: #172033;
  font-weight: 900;
}

.status-card span {
  color: #64748b;
  font-size: .88rem;
  font-weight: 700;
}

.manual-form {
  display: grid;
  gap: .45rem;
}

.manual-form label {
  color: #526176;
  font-size: .82rem;
  font-weight: 900;
}

.manual-form div {
  display: grid;
  gap: .5rem;
  grid-template-columns: 1fr 46px;
}

.manual-form input {
  border: 1px solid #d8e3eb;
  border-radius: 12px;
  min-height: 46px;
  padding: .75rem;
}

.manual-form button,
.primary-btn {
  align-items: center;
  background: #0077b5;
  border: 0;
  border-radius: 12px;
  color: #fff;
  display: inline-flex;
  font-weight: 900;
  gap: .45rem;
  justify-content: center;
  min-height: 46px;
  padding: .7rem 1rem;
}

@media (max-width: 820px) {
  .scanner-body {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .scanner-page {
    padding: 0;
  }

  .scanner-shell {
    border-radius: 0;
    min-height: calc(100vh - 64px);
  }

  .camera-frame {
    border-radius: 14px;
  }
}
</style>
