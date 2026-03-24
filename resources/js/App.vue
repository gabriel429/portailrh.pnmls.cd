<template>
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
import { computed, ref, watch } from 'vue'
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
