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
              <strong>{{ cameraReady ? 'Camera active' : 'Lecture manuelle disponible' }}</strong>
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

const router = useRouter()
const ui = useUiStore()
const videoRef = ref(null)
const stream = ref(null)
const detector = ref(null)
const cameraReady = ref(false)
const cameraMessage = ref('Activez la camera pour scanner le QR code imprime au verso.')
const manualValue = ref('')
let scanTimer = null
let scanning = false

const supportMessage = computed(() => {
  if ('BarcodeDetector' in window) return 'Placez le QR code dans le cadre.'
  return 'Votre navigateur ne lit pas directement le QR code; collez le lien scanne.'
})

function extractToken(value) {
  const input = String(value || '').trim()
  if (!input) return ''
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
      video: { facingMode: 'environment' },
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
  if (!detector.value || !videoRef.value) return
  stopLoop()
  scanTimer = window.setInterval(async () => {
    if (scanning || !videoRef.value || videoRef.value.readyState < 2) return
    scanning = true
    try {
      const codes = await detector.value.detect(videoRef.value)
      const value = codes?.[0]?.rawValue
      if (value) navigateToToken(value)
    } catch (_) {
      stopLoop()
    } finally {
      scanning = false
    }
  }, 450)
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
  if ('BarcodeDetector' in window) startCamera()
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
