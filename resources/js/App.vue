<template>
  <transition name="launch-fade">
    <div v-if="showLaunchScreen" class="launch-screen" aria-live="polite">
      <div class="launch-screen__backdrop" aria-hidden="true"></div>
      <div class="launch-screen__veil" aria-hidden="true"></div>
      <div class="launch-screen__grain" aria-hidden="true"></div>

      <div class="launch-brand">
        <div class="launch-brand__logo-stage" role="img" aria-label="Logo PNMLS">
          <span class="launch-brand__pulse launch-brand__pulse--outer" aria-hidden="true"></span>
          <span class="launch-brand__pulse launch-brand__pulse--inner" aria-hidden="true"></span>
          <span class="launch-brand__shine" aria-hidden="true"></span>
          <img class="launch-brand__logo" src="/images/logo-pnmls.png" alt="Logo PNMLS" />
        </div>
        <div class="launch-brand__text">
          <h1 class="launch-brand__title">E-PNMLS</h1>
          <p class="launch-brand__subtitle">Programme National Multisectoriel de Lutte contre le Sida</p>
        </div>
        <div class="launch-brand__progress" aria-hidden="true">
          <span></span>
        </div>
      </div>
    </div>
  </transition>

  <!-- Offline banner -->
  <div v-if="!isOnline" class="offline-banner">
    <i class="fas fa-exclamation-triangle"></i>
    Mode hors ligne — donnees en cache
  </div>

  <!-- Reconnected banner -->
  <div v-if="showReconnected" class="reconnected-banner">
    <i class="fas fa-wifi"></i>
    Connexion retablie
  </div>

  <!-- Global loading during initial auth check -->
  <div v-if="auth.loading" class="app-loading">
    <div class="spinner-border text-primary" role="status"></div>
  </div>

  <component v-else :is="layout">
    <router-view v-slot="{ Component }">
      <transition name="page-fade">
        <component :is="Component" :key="route.fullPath" />
      </transition>
    </router-view>
  </component>

  <!-- Bouton flottant Mail -->
  <router-link v-if="showWebmailFab"
     :to="{ name: 'mailbox.index' }"
     class="webmail-fab text-decoration-none no-print">
    <i class="fas fa-at me-2"></i> Mail
  </router-link>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useOnlineStatus } from '@/composables/useOnlineStatus'
import { useAuthStore } from '@/stores/auth'
import AppLayout from './layouts/AppLayout.vue'
import AdminLayout from './layouts/AdminLayout.vue'
import GuestLayout from './layouts/GuestLayout.vue'

const route = useRoute()
const auth = useAuthStore()
const { isOnline } = useOnlineStatus()

const layouts = {
  app: AppLayout,
  admin: AdminLayout,
  guest: GuestLayout,
}

const layout = computed(() => layouts[route.meta.layout || 'app'] || AppLayout)
const showWebmailFab = computed(() => (
  auth.isAuthenticated && !['mailbox.index', 'mail.history'].includes(String(route.name || ''))
))

const showLaunchScreen = ref(true)
let launchTimer = null

onMounted(() => {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  launchTimer = window.setTimeout(() => {
    showLaunchScreen.value = false
  }, prefersReducedMotion ? 900 : 2600)
})

onBeforeUnmount(() => {
  if (launchTimer) {
    window.clearTimeout(launchTimer)
  }
})

// Show a brief "reconnected" message when coming back online
const showReconnected = ref(false)
watch(isOnline, (newVal, oldVal) => {
    if (newVal && !oldVal) {
        showReconnected.value = true
        setTimeout(() => { showReconnected.value = false }, 3000)
    }
})

// Toggle body class for navbar offset
watch(isOnline, (val) => {
    document.body.classList.toggle('is-offline', !val)
}, { immediate: true })
</script>

<style>
.launch-fade-enter-active,
.launch-fade-leave-active {
  transition: opacity 0.45s ease;
}

.launch-fade-enter-from,
.launch-fade-leave-to {
  opacity: 0;
}

.launch-screen {
  position: fixed;
  inset: 0;
  z-index: 5000;
  display: grid;
  place-items: center;
  padding: 1.5rem;
  text-align: center;
  color: #f8fbff;
  overflow: hidden;
  background: #002f4f;
}

.launch-screen__backdrop {
  position: absolute;
  inset: -16px;
  background: url('/images/pnmls.jpeg') center / cover no-repeat;
  filter: blur(8px) saturate(1.05);
  transform: scale(1.06);
}

.launch-screen__veil {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 50% 42%, rgba(255, 255, 255, 0.2), transparent 30%),
    radial-gradient(circle at 22% 18%, rgba(213, 25, 32, 0.32), transparent 28%),
    radial-gradient(circle at 78% 80%, rgba(0, 161, 222, 0.34), transparent 30%),
    linear-gradient(145deg, rgba(0, 36, 62, 0.96) 0%, rgba(0, 76, 115, 0.94) 45%, rgba(0, 119, 181, 0.9) 100%);
}

.launch-screen__grain {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255, 255, 255, 0.16) 0.55px, transparent 0.55px);
  background-size: 3px 3px;
  opacity: 0.16;
  mix-blend-mode: soft-light;
}

.launch-brand {
  position: relative;
  z-index: 1;
  width: min(92vw, 780px);
  display: grid;
  justify-items: center;
  gap: 1.15rem;
  padding: clamp(1rem, 3vw, 2rem);
  animation: launchMessageIn 0.72s cubic-bezier(.2, .9, .2, 1) forwards;
}

.launch-brand__logo-stage {
  display: grid;
  place-items: center;
  position: relative;
  width: clamp(156px, 24vw, 246px);
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background:
    radial-gradient(circle at 50% 45%, rgba(255, 255, 255, 0.98), rgba(240, 249, 255, 0.92) 62%, rgba(219, 234, 254, 0.88));
  border: 1px solid rgba(255, 255, 255, 0.9);
  box-shadow:
    0 30px 80px rgba(0, 13, 27, 0.38),
    0 0 0 12px rgba(255, 255, 255, 0.08),
    0 0 90px rgba(213, 25, 32, 0.22);
  overflow: hidden;
  animation: launchLogoPop 1.1s cubic-bezier(.16, 1, .3, 1) both, launchLogoFloat 4.2s ease-in-out 1.2s infinite;
}

.launch-brand__pulse {
  content: '';
  position: absolute;
  inset: -12px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.28);
  pointer-events: none;
}

.launch-brand__pulse--outer {
  animation: launchPulseOuter 2.1s ease-out infinite;
}

.launch-brand__pulse--inner {
  inset: 7px;
  border-color: rgba(213, 25, 32, 0.34);
  animation: launchPulseInner 1.8s ease-in-out infinite;
}

.launch-brand__shine {
  position: absolute;
  inset: -20%;
  background: linear-gradient(115deg, transparent 32%, rgba(255, 255, 255, 0.78) 48%, transparent 64%);
  transform: translateX(-120%) rotate(8deg);
  animation: launchLogoShine 2.8s ease-in-out 0.4s infinite;
  pointer-events: none;
}

.launch-brand__logo {
  position: relative;
  width: 84%;
  height: 84%;
  object-fit: contain;
  filter: drop-shadow(0 10px 16px rgba(2, 23, 37, 0.22));
  z-index: 1;
}

.launch-brand__text {
  display: grid;
  gap: .4rem;
  max-width: 760px;
}

.launch-brand__title {
  margin: 0;
  font-size: clamp(1.35rem, 1.2rem + 2vw, 2.5rem);
  letter-spacing: .14em;
  font-weight: 900;
  text-transform: uppercase;
  color: #ffffff;
  text-shadow: 0 12px 28px rgba(3, 19, 31, 0.56);
  animation: launchTextIn 0.8s ease 0.18s both;
}

.launch-brand__subtitle {
  margin: 0;
  font-size: clamp(0.86rem, 0.82rem + .65vw, 1.18rem);
  font-weight: 800;
  line-height: 1.5;
  color: rgba(245, 251, 255, 0.99);
  text-wrap: balance;
  text-shadow: 0 8px 20px rgba(3, 19, 31, 0.5);
  animation: launchTextIn 0.8s ease 0.28s both;
}

.launch-brand__progress {
  position: relative;
  width: min(220px, 48vw);
  height: 4px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.26);
  overflow: hidden;
  box-shadow: 0 0 24px rgba(255, 255, 255, 0.14);
}

.launch-brand__progress span {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(90deg, #d51920, #ffef00, #00a1de);
  transform-origin: left center;
  animation: launchProgress 1.38s cubic-bezier(.66, 0, .34, 1) infinite;
}

@keyframes launchMessageIn {
  0% {
    opacity: 0;
    transform: translateY(14px) scale(0.98);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes launchLogoPop {
  0% {
    opacity: 0;
    transform: scale(0.72);
  }
  58% {
    opacity: 1;
    transform: scale(1.06);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes launchLogoFloat {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-6px);
  }
}

@keyframes launchLogoShine {
  0%,
  38% {
    transform: translateX(-125%) rotate(8deg);
    opacity: 0;
  }
  54% {
    opacity: 0.65;
  }
  72%,
  100% {
    transform: translateX(125%) rotate(8deg);
    opacity: 0;
  }
}

@keyframes launchPulseOuter {
  0% {
    opacity: 0.72;
    transform: scale(0.92);
  }
  100% {
    opacity: 0;
    transform: scale(1.2);
  }
}

@keyframes launchPulseInner {
  0%,
  100% {
    opacity: 0.46;
    transform: scale(0.98);
  }
  50% {
    opacity: 0.9;
    transform: scale(1.03);
  }
}

@keyframes launchProgress {
  0% {
    transform: translateX(-100%) scaleX(0.35);
  }
  42% {
    transform: translateX(0) scaleX(0.9);
  }
  100% {
    transform: translateX(112%) scaleX(0.42);
  }
}

@keyframes launchTextIn {
  0% {
    opacity: 0;
    transform: translateY(8px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .launch-brand,
  .launch-brand__logo-stage,
  .launch-brand__pulse,
  .launch-brand__shine,
  .launch-brand__progress span,
  .launch-brand__title,
  .launch-brand__subtitle {
    animation: none !important;
  }
}

@media (max-width: 576px) {
  .launch-screen {
    padding-inline: 1rem;
  }

  .launch-brand__logo-stage {
    width: clamp(136px, 42vw, 188px);
  }

  .launch-brand__title {
    letter-spacing: .1em;
  }
}

.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity .2s ease;
}
.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}
.app-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}
.webmail-fab {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 1050;
  background: linear-gradient(135deg, #0077B5, #005a87);
  color: #fff;
  border-radius: 50px;
  padding: 14px 24px;
  box-shadow: 0 4px 15px rgba(0,119,181,0.4);
  font-weight: 600;
  font-size: 0.95rem;
  transition: transform 0.2s, box-shadow 0.2s;
}
.webmail-fab:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(0,119,181,0.5);
  color: #fff;
}
</style>
