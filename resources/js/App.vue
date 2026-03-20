<template>
  <component :is="layout">
    <router-view />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from './layouts/AppLayout.vue'
import AdminLayout from './layouts/AdminLayout.vue'
import GuestLayout from './layouts/GuestLayout.vue'

const route = useRoute()

const layouts = {
  app: AppLayout,
  admin: AdminLayout,
  guest: GuestLayout,
}

const layout = computed(() => layouts[route.meta.layout || 'app'] || AppLayout)
</script>
