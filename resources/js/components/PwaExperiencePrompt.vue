<template>
  <Teleport to="body">
    <transition name="pwa-panel">
      <section
        v-if="visible"
        class="pwa-experience-panel no-print"
        :class="{ 'is-update': updateReady }"
        role="status"
        aria-live="polite"
      >
        <button class="pwa-panel__dismiss" type="button" aria-label="Masquer" @click="dismissPanel">
          <i class="fas fa-times"></i>
        </button>

        <div class="pwa-panel__icon" aria-hidden="true">
          <i class="fas" :class="updateReady ? 'fa-sync-alt' : 'fa-mobile-alt'"></i>
        </div>

        <div class="pwa-panel__content">
          <p class="pwa-panel__eyebrow">{{ updateReady ? 'Mise a jour PWA' : 'Application PWA' }}</p>
          <h2>{{ updateReady ? 'Nouvelle version prête' : 'Installer E-PNMLS' }}</h2>
          <p>
            {{ updateReady
              ? 'Rechargez pour appliquer les dernières corrections sans perdre votre session.'
              : 'Accès plus rapide, plein écran et meilleure continuité quand le réseau devient instable.' }}
          </p>

          <div class="pwa-panel__actions">
            <button class="pwa-panel__primary" type="button" @click="primaryAction">
              <i class="fas" :class="updateReady ? 'fa-redo-alt' : 'fa-download'"></i>
              <span>{{ updateReady ? 'Actualiser' : 'Installer' }}</span>
            </button>
            <button v-if="!updateReady" class="pwa-panel__ghost" type="button" @click="dismissPanel">
              Plus tard
            </button>
          </div>
        </div>
      </section>
    </transition>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const APP_SW_PATH = '/build/sw.js'
const DISMISS_KEY = 'epnmls:pwa-prompt-dismissed-until'
const DISMISS_DURATION = 12 * 60 * 60 * 1000

const deferredPrompt = ref(null)
const dismissed = ref(false)
const installed = ref(false)
const updateReady = ref(false)

let serviceWorkerRegistration = null
let controllerRefreshStarted = false
let controllerReloaded = false
const watchedRegistrations = new WeakSet()

const canInstall = computed(() => Boolean(deferredPrompt.value) && !installed.value)
const visible = computed(() => updateReady.value || (canInstall.value && !dismissed.value))

function isStandaloneMode() {
  return window.matchMedia?.('(display-mode: standalone)')?.matches || window.navigator.standalone === true
}

function readDismissState() {
  try {
    dismissed.value = Number(window.localStorage.getItem(DISMISS_KEY) || 0) > Date.now()
  } catch (_) {
    dismissed.value = false
  }
}

function storeDismissState() {
  dismissed.value = true

  try {
    window.localStorage.setItem(DISMISS_KEY, String(Date.now() + DISMISS_DURATION))
  } catch (_) {
    // localStorage can be disabled in strict browser modes.
  }
}

function dismissPanel() {
  if (updateReady.value) {
    updateReady.value = false
    return
  }

  storeDismissState()
}

async function primaryAction() {
  if (updateReady.value) {
    refreshForUpdate()
    return
  }

  if (!deferredPrompt.value) return

  const promptEvent = deferredPrompt.value
  deferredPrompt.value = null

  try {
    await promptEvent.prompt()
    const choice = await promptEvent.userChoice

    if (choice?.outcome !== 'accepted') {
      storeDismissState()
    }
  } catch (_) {
    storeDismissState()
  }
}

function refreshForUpdate() {
  const waitingWorker = serviceWorkerRegistration?.waiting

  if (waitingWorker) {
    controllerRefreshStarted = true
    waitingWorker.postMessage({ type: 'SKIP_WAITING' })
    window.setTimeout(() => window.location.reload(), 500)
    return
  }

  window.location.reload()
}

function handleBeforeInstallPrompt(event) {
  if (installed.value) return

  event.preventDefault()
  deferredPrompt.value = event
  readDismissState()
}

function handleAppInstalled() {
  installed.value = true
  deferredPrompt.value = null
  dismissed.value = true
}

function notifyUpdateReady(registration) {
  serviceWorkerRegistration = registration || serviceWorkerRegistration
  updateReady.value = true
  dismissed.value = false
}

function isAppServiceWorker(worker) {
  if (!worker?.scriptURL) return false

  try {
    return new URL(worker.scriptURL).pathname === APP_SW_PATH
  } catch (_) {
    return false
  }
}

function watchRegistration(registration) {
  if (!registration || watchedRegistrations.has(registration)) return

  serviceWorkerRegistration = registration
  watchedRegistrations.add(registration)

  if (registration.waiting && navigator.serviceWorker.controller) {
    notifyUpdateReady(registration)
  }

  registration.addEventListener('updatefound', () => {
    const newWorker = registration.installing
    if (!newWorker) return

    newWorker.addEventListener('statechange', () => {
      if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
        notifyUpdateReady(registration)
      }
    })
  })
}

async function findAppRegistration() {
  if (!('serviceWorker' in navigator) || !navigator.serviceWorker.getRegistrations) return null

  const registrations = await navigator.serviceWorker.getRegistrations()

  return registrations.find((registration) => [
    registration.active,
    registration.waiting,
    registration.installing,
  ].some(isAppServiceWorker)) || registrations[0] || null
}

async function watchForUpdates() {
  try {
    const registration = await findAppRegistration()
    if (!registration) return

    watchRegistration(registration)
    window.setTimeout(() => registration.update?.().catch(() => {}), 1500)
  } catch (_) {
    // Service worker checks should never block the UI.
  }
}

function handlePwaRegistration(event) {
  watchRegistration(event.detail?.registration)
}

function handlePwaUpdateReady(event) {
  notifyUpdateReady(event.detail?.registration)
}

function handleControllerChange() {
  if (controllerReloaded) return

  if (controllerRefreshStarted) {
    controllerReloaded = true
    window.location.reload()
    return
  }

  updateReady.value = true
}

onMounted(() => {
  installed.value = isStandaloneMode()
  readDismissState()

  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
  window.addEventListener('appinstalled', handleAppInstalled)
  window.addEventListener('epnmls:pwa-registration', handlePwaRegistration)
  window.addEventListener('epnmls:pwa-update-ready', handlePwaUpdateReady)
  navigator.serviceWorker?.addEventListener('controllerchange', handleControllerChange)

  watchForUpdates()
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
  window.removeEventListener('appinstalled', handleAppInstalled)
  window.removeEventListener('epnmls:pwa-registration', handlePwaRegistration)
  window.removeEventListener('epnmls:pwa-update-ready', handlePwaUpdateReady)
  navigator.serviceWorker?.removeEventListener('controllerchange', handleControllerChange)
})
</script>

<style scoped>
.pwa-experience-panel {
  position: fixed;
  left: max(1rem, env(safe-area-inset-left));
  bottom: max(1rem, env(safe-area-inset-bottom));
  z-index: 1800;
  width: min(420px, calc(100vw - 2rem));
  display: grid;
  grid-template-columns: auto 1fr;
  gap: .9rem;
  padding: .95rem 1rem;
  color: #0f2338;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, .96), rgba(241, 250, 255, .9)),
    radial-gradient(circle at 0 0, rgba(0, 119, 181, .16), transparent 42%);
  border: 1px solid rgba(148, 203, 232, .58);
  border-radius: 18px;
  box-shadow:
    0 20px 55px rgba(2, 31, 52, .18),
    0 1px 0 rgba(255, 255, 255, .72) inset;
  backdrop-filter: blur(18px) saturate(160%);
  -webkit-backdrop-filter: blur(18px) saturate(160%);
}

.pwa-experience-panel.is-update {
  border-color: rgba(34, 197, 94, .34);
  background:
    linear-gradient(135deg, rgba(255, 255, 255, .97), rgba(239, 253, 245, .9)),
    radial-gradient(circle at 0 0, rgba(34, 197, 94, .14), transparent 42%);
}

.pwa-panel__dismiss {
  position: absolute;
  top: .55rem;
  right: .55rem;
  width: 28px;
  height: 28px;
  display: inline-grid;
  place-items: center;
  border: 0;
  border-radius: 999px;
  color: #64748b;
  background: rgba(15, 35, 56, .06);
  transition: background .18s ease, color .18s ease, transform .18s ease;
}

.pwa-panel__dismiss:hover {
  color: #0f2338;
  background: rgba(15, 35, 56, .11);
  transform: translateY(-1px);
}

.pwa-panel__icon {
  width: 46px;
  height: 46px;
  display: inline-grid;
  place-items: center;
  border-radius: 16px;
  color: #fff;
  background: linear-gradient(135deg, #0077B5, #00A1DE);
  box-shadow: 0 12px 26px rgba(0, 119, 181, .26);
}

.is-update .pwa-panel__icon {
  background: linear-gradient(135deg, #059669, #22c55e);
  box-shadow: 0 12px 26px rgba(5, 150, 105, .24);
}

.pwa-panel__content {
  min-width: 0;
  padding-right: 1.2rem;
}

.pwa-panel__eyebrow {
  margin: 0 0 .15rem;
  color: #5d738b;
  font-size: .68rem;
  font-weight: 900;
  line-height: 1.1;
  text-transform: uppercase;
  letter-spacing: 0;
}

.pwa-panel__content h2 {
  margin: 0;
  color: #102033;
  font-size: .98rem;
  font-weight: 900;
  line-height: 1.2;
}

.pwa-panel__content p:not(.pwa-panel__eyebrow) {
  margin: .32rem 0 .8rem;
  color: #52667d;
  font-size: .79rem;
  font-weight: 650;
  line-height: 1.45;
}

.pwa-panel__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .5rem;
}

.pwa-panel__primary,
.pwa-panel__ghost {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .42rem;
  border-radius: 999px;
  font-size: .78rem;
  font-weight: 900;
  line-height: 1;
  transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.pwa-panel__primary {
  border: 0;
  padding: .55rem .86rem;
  color: #fff;
  background: linear-gradient(135deg, #0066A0, #008fd3);
  box-shadow: 0 10px 22px rgba(0, 102, 160, .22);
}

.pwa-panel__primary:hover,
.pwa-panel__ghost:hover {
  transform: translateY(-1px);
}

.pwa-panel__ghost {
  border: 1px solid rgba(99, 120, 146, .22);
  padding: .5rem .76rem;
  color: #52667d;
  background: rgba(255, 255, 255, .62);
}

.pwa-panel-enter-active,
.pwa-panel-leave-active {
  transition: opacity .22s ease, transform .22s ease;
}

.pwa-panel-enter-from,
.pwa-panel-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

:global(html.dark) .pwa-experience-panel {
  color: #eef6ff;
  background:
    linear-gradient(135deg, rgba(23, 32, 49, .96), rgba(13, 28, 48, .92)),
    radial-gradient(circle at 0 0, rgba(56, 189, 248, .2), transparent 42%);
  border-color: rgba(148, 203, 232, .2);
  box-shadow: 0 22px 60px rgba(0, 0, 0, .34);
}

:global(html.dark) .pwa-panel__eyebrow,
:global(html.dark) .pwa-panel__content p:not(.pwa-panel__eyebrow) {
  color: rgba(226, 232, 240, .76);
}

:global(html.dark) .pwa-panel__content h2 {
  color: #f8fbff;
}

:global(html.dark) .pwa-panel__ghost {
  color: #dbeafe;
  border-color: rgba(255, 255, 255, .12);
  background: rgba(255, 255, 255, .08);
}

:global(html.dark) .pwa-panel__dismiss {
  color: #dbeafe;
  background: rgba(255, 255, 255, .08);
}

@media (max-width: 576px) {
  .pwa-experience-panel {
    left: 1rem;
    right: 1rem;
    bottom: calc(1rem + env(safe-area-inset-bottom));
    width: auto;
    grid-template-columns: 1fr;
    padding: .9rem;
  }

  .pwa-panel__icon {
    width: 42px;
    height: 42px;
  }

  .pwa-panel__content {
    padding-right: 0;
  }
}
</style>
