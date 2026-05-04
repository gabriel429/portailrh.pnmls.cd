<template>
  <transition name="launch-fade">
    <div v-if="showLaunchScreen" class="launch-screen" aria-live="polite">
      <div class="launch-screen__backdrop" aria-hidden="true"></div>
      <div class="launch-screen__veil" aria-hidden="true"></div>
      <div class="launch-screen__halo" aria-hidden="true"></div>
      <div class="launch-screen__grain" aria-hidden="true"></div>

      <div class="launch-brand">
        <div class="launch-brand__logo-wrap" role="img" aria-label="Logo PNMLS">
          <img class="launch-brand__logo" src="/images/logo-pnmls.png" alt="Logo PNMLS" />
        </div>
        <div class="launch-brand__text">
          <h1 class="launch-brand__title">E-PNMLS</h1>
          <p class="launch-brand__subtitle">Programme National Multisectoriel de Lutte contre le Sida</p>
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
      <transition name="page-fade" mode="out-in">
        <component :is="Component" :key="route.fullPath" />
      </transition>
    </router-view>
  </component>

  <!-- Bouton flottant Webmail -->
  <a v-if="auth.isAuthenticated"
     href="https://camulus.o2switch.net:2096/"
     target="_blank"
     rel="noopener noreferrer"
     class="webmail-fab text-decoration-none">
    <i class="fas fa-at me-2"></i> Webmail professionnel
  </a>
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

const showLaunchScreen = ref(true)
let launchTimer = null

onMounted(() => {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  launchTimer = window.setTimeout(() => {
    showLaunchScreen.value = false
  }, prefersReducedMotion ? 1800 : 3600)
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
  color: #fff;
  overflow: hidden;
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
    radial-gradient(circle at 20% 20%, rgba(147, 229, 255, 0.29), transparent 36%),
    radial-gradient(circle at 80% 10%, rgba(56, 189, 248, 0.28), transparent 32%),
    linear-gradient(135deg, rgba(2, 49, 79, 0.9) 0%, rgba(3, 88, 132, 0.84) 45%, rgba(11, 120, 173, 0.8) 100%);
}

.launch-screen__grain {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255, 255, 255, 0.16) 0.55px, transparent 0.55px);
  background-size: 3px 3px;
  opacity: 0.16;
  mix-blend-mode: soft-light;
}

.launch-screen__halo {
  position: absolute;
  width: min(82vw, 660px);
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(186, 230, 253, 0.42) 0%, rgba(56, 189, 248, 0.12) 44%, rgba(255, 255, 255, 0) 72%);
  filter: blur(12px);
  animation: launchHaloPulse 2.2s ease-in-out infinite;
}

.launch-brand {
  position: relative;
  z-index: 1;
  width: min(92vw, 860px);
  display: grid;
  justify-items: center;
  gap: 1rem;
  padding: clamp(1.15rem, 2.8vw, 2rem);
  border-radius: 24px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0.1));
  border: 1px solid rgba(255, 255, 255, 0.48);
  backdrop-filter: blur(7px);
  box-shadow: 0 24px 64px rgba(2, 23, 37, 0.42);
  animation: launchMessageIn 0.8s ease forwards;
}

.launch-brand__logo-wrap {
  display: grid;
  place-items: center;
  position: relative;
  width: clamp(160px, 26vw, 270px);
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.98), rgba(240, 249, 255, 0.9));
  border: 2px solid rgba(255, 255, 255, 0.78);
  box-shadow: 0 18px 42px rgba(2, 23, 37, 0.4);
  animation: logoFloat 4.5s ease-in-out infinite;
}

.launch-brand__logo-wrap::after {
  content: '';
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  border: 2px solid rgba(186, 230, 253, 0.58);
  opacity: 0.8;
  animation: logoRingPulse 2.4s ease-in-out infinite;
}

.launch-brand__logo {
  width: 82%;
  height: 82%;
  object-fit: contain;
  filter: drop-shadow(0 8px 14px rgba(2, 23, 37, 0.25));
}

.launch-brand__text {
  display: grid;
  gap: .35rem;
  max-width: 760px;
}

.launch-brand__title {
  margin: 0;
  font-size: clamp(1.35rem, 1.2rem + 2vw, 2.5rem);
  letter-spacing: .08em;
  font-weight: 900;
  text-transform: uppercase;
  color: #ffffff;
  text-shadow: 0 12px 28px rgba(3, 19, 31, 0.56);
}

.launch-brand__subtitle {
  margin: 0;
  font-size: clamp(0.86rem, 0.82rem + .65vw, 1.18rem);
  font-weight: 800;
  line-height: 1.5;
  color: rgba(245, 251, 255, 0.99);
  text-wrap: balance;
  text-shadow: 0 8px 20px rgba(3, 19, 31, 0.5);
}

@keyframes launchMessageIn {
  0% {
    opacity: 0;
    transform: translateY(10px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes launchHaloPulse {
  0%,
  100% {
    transform: scale(0.96);
    opacity: 0.75;
  }
  50% {
    transform: scale(1.04);
    opacity: 1;
  }
}

@keyframes logoFloat {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-6px);
  }
}

@keyframes logoRingPulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.75;
  }
  50% {
    transform: scale(1.04);
    opacity: 1;
  }
}

@media (prefers-reduced-motion: reduce) {
  .launch-screen__halo,
  .launch-brand,
  .launch-brand__logo-wrap,
  .launch-brand__logo-wrap::after {
    animation: none !important;
  }
}

@media (max-width: 576px) {
  .launch-screen {
    padding-inline: 1rem;
  }

  .launch-brand {
    border-radius: 18px;
    padding: 1rem;
  }

  .launch-brand__logo-wrap {
    width: clamp(140px, 42vw, 190px);
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
