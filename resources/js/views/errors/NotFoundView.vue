<template>
  <div class="text-center py-5">
    <div style="font-size: 5rem; color: #0077B5;">
      <i class="fas fa-exclamation-triangle"></i>
    </div>
    <h1 class="display-4 fw-bold mt-3">404</h1>
    <p class="lead text-muted">Page introuvable</p>
    <router-link :to="{ name: 'dashboard' }" class="btn btn-primary mt-3">
      <i class="fas fa-home me-1"></i> Retour a l'accueil
    </router-link>
  </div>
</template>
