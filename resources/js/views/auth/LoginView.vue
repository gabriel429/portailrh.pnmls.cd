<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-header">
        <img src="/images/logo-pnmls.png" alt="PNMLS" class="login-logo">
        <h2>Portail RH</h2>
        <p class="text-muted">Programme National Multisectoriel de Lutte contre le Sida</p>
      </div>

      <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
        {{ errorMessage }}
        <button type="button" class="btn-close" @click="errorMessage = ''"></button>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label for="email" class="form-label">Adresse email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="form-control"
              :class="{ 'is-invalid': errors.email }"
              placeholder="votre@email.cd"
              required
              autofocus
            >
          </div>
          <div v-if="errors.email" class="invalid-feedback d-block">{{ errors.email[0] }}</div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Mot de passe</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              class="form-control"
              :class="{ 'is-invalid': errors.password }"
              placeholder="Mot de passe"
              required
            >
            <button class="btn btn-outline-secondary" type="button" @click="showPassword = !showPassword">
              <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>
          <div v-if="errors.password" class="invalid-feedback d-block">{{ errors.password[0] }}</div>
        </div>

        <div class="mb-3 form-check">
          <input id="remember" v-model="form.remember" type="checkbox" class="form-check-input">
          <label for="remember" class="form-check-label">Se souvenir de moi</label>
        </div>

        <button type="submit" class="btn btn-primary w-100" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
          Connexion
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '', remember: false })
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const errors = ref({})

async function handleLogin() {
    loading.value = true
    errorMessage.value = ''
    errors.value = {}

    try {
        await auth.login(form.email, form.password)
        const redirect = route.query.redirect || '/dashboard'
        router.push(redirect)
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {}
            errorMessage.value = e.response.data.message || 'Erreur de validation.'
        } else if (e.response?.status === 401) {
            errorMessage.value = 'Identifiants incorrects.'
        } else {
            errorMessage.value = 'Erreur de connexion. Veuillez reessayer.'
        }
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #004060 100%);
    padding: 1rem;
}
.login-card {
    background: #fff;
    border-radius: 16px;
    padding: 2.5rem;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.login-header {
    text-align: center;
    margin-bottom: 2rem;
}
.login-logo {
    height: 64px;
    width: 64px;
    margin-bottom: 1rem;
}
.login-header h2 {
    color: #0077B5;
    font-weight: 700;
    margin-bottom: .25rem;
}
</style>
