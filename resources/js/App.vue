<template>
  <transition name="launch-fade">
    <div v-if="showLaunchScreen" class="launch-screen" aria-live="polite">
      <div class="launch-screen__halo"></div>

      <div class="launch-logo-build" role="img" aria-label="Logo PNMLS en reconstitution">
        <div class="launch-logo-build__piece launch-logo-build__piece--tl"></div>
        <div class="launch-logo-build__piece launch-logo-build__piece--tr"></div>
        <div class="launch-logo-build__piece launch-logo-build__piece--bl"></div>
        <div class="launch-logo-build__piece launch-logo-build__piece--br"></div>
        <div class="launch-logo-build__core"></div>
      </div>

      <p class="launch-screen__message">
        Plateforme du Programme National Multisectoriel de Lutte contre le Sida
      </p>
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
        <component :is="Component" />
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
  gap: 1.35rem;
  padding: 1.5rem;
  text-align: center;
  background:
    radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.18), transparent 34%),
    radial-gradient(circle at 88% 14%, rgba(255, 255, 255, 0.14), transparent 30%),
    linear-gradient(135deg, #005a87 0%, #0077b5 42%, #0089cb 100%);
  color: #fff;
}

.launch-screen__halo {
  position: absolute;
  width: min(76vw, 520px);
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.34) 0%, rgba(255, 255, 255, 0) 68%);
  filter: blur(6px);
  animation: launchHaloPulse 2.2s ease-in-out infinite;
}

.launch-logo-build {
  position: relative;
  width: clamp(96px, 16vw, 160px);
  aspect-ratio: 1 / 1;
  filter: drop-shadow(0 12px 28px rgba(0, 0, 0, 0.25));
}

.launch-logo-build__piece,
.launch-logo-build__core {
  position: absolute;
  inset: 0;
  background: url('/images/logo-pnmls.png') center / cover no-repeat;
  border-radius: 18px;
}

.launch-logo-build__piece {
  opacity: 0;
  animation: pieceIn 0.95s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.launch-logo-build__piece--tl {
  clip-path: inset(0 50% 50% 0 round 18px 0 0 0);
  transform: translate(-55px, -45px) rotate(-10deg) scale(0.82);
  animation-delay: 0.08s;
}

.launch-logo-build__piece--tr {
  clip-path: inset(0 0 50% 50% round 0 18px 0 0);
  transform: translate(56px, -36px) rotate(9deg) scale(0.82);
  animation-delay: 0.21s;
}

.launch-logo-build__piece--bl {
  clip-path: inset(50% 50% 0 0 round 0 0 0 18px);
  transform: translate(-47px, 44px) rotate(8deg) scale(0.82);
  animation-delay: 0.34s;
}

.launch-logo-build__piece--br {
  clip-path: inset(50% 0 0 50% round 0 0 18px 0);
  transform: translate(52px, 50px) rotate(-8deg) scale(0.82);
  animation-delay: 0.47s;
}

.launch-logo-build__core {
  opacity: 0;
  transform: scale(0.92);
  animation: coreReveal 0.9s ease forwards;
  animation-delay: 0.62s;
}

.launch-screen__message {
  margin: 0;
  max-width: 680px;
  font-size: clamp(0.95rem, 1.1vw + 0.7rem, 1.25rem);
  font-weight: 700;
  letter-spacing: 0.01em;
  line-height: 1.45;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.18);
  animation: launchMessageIn 0.8s ease forwards;
  animation-delay: 0.78s;
  opacity: 0;
}

@keyframes pieceIn {
  to {
    opacity: 1;
    transform: translate(0, 0) rotate(0deg) scale(1);
  }
}

@keyframes coreReveal {
  0% {
    opacity: 0;
    transform: scale(0.92);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
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

@media (max-width: 576px) {
  .launch-screen {
    gap: 1rem;
    padding-inline: 1rem;
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
