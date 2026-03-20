<template>
  <div class="login-page">
    <!-- Left branding panel -->
    <div class="login-brand">
      <div class="brand-bg"></div>
      <div class="brand-content">
        <img src="/images/pnmls.jpeg" alt="PNMLS" class="brand-logo">
        <h1 class="brand-title">PNMLS</h1>
        <p class="brand-tagline">Programme National Multisectoriel<br>de Lutte contre le Sida</p>
        <div class="brand-separator"></div>
        <p class="brand-portal">Portail des Ressources Humaines</p>
      </div>
      <!-- Decorative watermark -->
      <img src="/images/pnmls.jpeg" alt="" class="brand-watermark">
    </div>

    <!-- Right form panel -->
    <div class="login-form-panel">
      <div class="login-card">
        <div class="login-header">
          <div class="login-icon">
            <i class="fas fa-sign-in-alt"></i>
          </div>
          <h2>Connexion</h2>
          <p>Accédez à votre espace personnel</p>
        </div>

        <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
          <i class="fas fa-exclamation-circle me-2"></i>{{ errorMessage }}
          <button type="button" class="btn-close" @click="errorMessage = ''"></button>
        </div>

        <form @submit.prevent="handleLogin">
          <div class="form-group">
            <label for="email" class="form-label">
              <i class="fas fa-envelope me-1"></i> Adresse email
            </label>
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
            <div v-if="errors.email" class="invalid-feedback d-block">{{ errors.email[0] }}</div>
          </div>

          <div class="form-group">
            <label for="password" class="form-label">
              <i class="fas fa-lock me-1"></i> Mot de passe
            </label>
            <div class="password-wrapper">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="form-control"
                :class="{ 'is-invalid': errors.password }"
                placeholder="Mot de passe"
                required
              >
              <button class="password-toggle" type="button" @click="showPassword = !showPassword" tabindex="-1">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
            <div v-if="errors.password" class="invalid-feedback d-block">{{ errors.password[0] }}</div>
          </div>

          <div class="form-options">
            <label class="remember-label">
              <input v-model="form.remember" type="checkbox" class="form-check-input">
              <span>Se souvenir de moi</span>
            </label>
          </div>

          <button type="submit" class="btn-login" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            <span v-else class="btn-login-icon"><i class="fas fa-arrow-right"></i></span>
            Connexion
          </button>
        </form>

        <div class="login-footer">
          <img src="/images/logo-pnmls.png" alt="PNMLS" class="footer-logo">
          <span>PNMLS &middot; Portail RH</span>
        </div>
      </div>
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
/* ═══════════════════════════════════════════
   Login Page — Split Layout
   ═══════════════════════════════════════════ */
.login-page {
    min-height: 100vh;
    display: flex;
    background: #f0f4f8;
}

/* ── Left Brand Panel ── */
.login-brand {
    flex: 0 0 45%;
    background: linear-gradient(160deg, #0077B5 0%, #005a87 40%, #003f5f 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.brand-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 80%, rgba(255,255,255,.06) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,.04) 0%, transparent 50%);
}

.brand-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 2rem;
}

.brand-logo {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(255,255,255,.25);
    box-shadow: 0 8px 40px rgba(0,0,0,.3), 0 0 0 8px rgba(255,255,255,.08);
    margin-bottom: 1.75rem;
    animation: logoFloat 6s ease-in-out infinite;
}

@keyframes logoFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.brand-title {
    font-size: 2.5rem;
    font-weight: 900;
    color: #fff;
    letter-spacing: 6px;
    margin-bottom: .5rem;
    text-shadow: 0 2px 10px rgba(0,0,0,.2);
}

.brand-tagline {
    font-size: .95rem;
    color: rgba(255,255,255,.75);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.brand-separator {
    width: 60px;
    height: 3px;
    background: rgba(255,255,255,.35);
    border-radius: 2px;
    margin: 0 auto 1.5rem;
}

.brand-portal {
    font-size: .85rem;
    font-weight: 600;
    color: rgba(255,255,255,.5);
    text-transform: uppercase;
    letter-spacing: 3px;
}

.brand-watermark {
    position: absolute;
    bottom: -60px;
    right: -60px;
    width: 320px;
    height: 320px;
    object-fit: contain;
    opacity: 0.06;
    pointer-events: none;
    z-index: 1;
}

/* ── Right Form Panel ── */
.login-form-panel {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.login-card {
    width: 100%;
    max-width: 400px;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #0077B5, #005a87);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: #fff;
    font-size: 1.3rem;
    box-shadow: 0 4px 15px rgba(0,119,181,.3);
}

.login-header h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: .25rem;
}

.login-header p {
    font-size: .88rem;
    color: #94a3b8;
    margin: 0;
}

/* ── Form fields ── */
.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    font-size: .82rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: .4rem;
    display: block;
}

.form-label i {
    color: #0077B5;
    font-size: .75rem;
}

.form-control {
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    padding: .7rem 1rem;
    font-size: .9rem;
    transition: all .2s ease;
    background: #fff;
}

.form-control:focus {
    border-color: #0077B5;
    box-shadow: 0 0 0 4px rgba(0,119,181,.1);
}

.form-control.is-invalid {
    border-color: #ef4444;
}

.password-wrapper {
    position: relative;
}

.password-wrapper .form-control {
    padding-right: 3rem;
}

.password-toggle {
    position: absolute;
    right: 1px;
    top: 1px;
    bottom: 1px;
    width: 2.8rem;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    border-radius: 0 12px 12px 0;
    transition: color .2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: #0077B5;
}

/* ── Options ── */
.form-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.remember-label {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .84rem;
    color: #64748b;
    cursor: pointer;
    margin: 0;
}

.remember-label .form-check-input {
    margin: 0;
    border-radius: 5px;
    width: 1.1em;
    height: 1.1em;
}

.remember-label .form-check-input:checked {
    background-color: #0077B5;
    border-color: #0077B5;
}

/* ── Login button ── */
.btn-login {
    width: 100%;
    padding: .75rem;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #0077B5, #005a87);
    color: #fff;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    box-shadow: 0 4px 15px rgba(0,119,181,.3);
}

.btn-login:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(0,119,181,.4);
}

.btn-login:active:not(:disabled) {
    transform: translateY(0);
}

.btn-login:disabled {
    opacity: .7;
    cursor: not-allowed;
}

.btn-login-icon {
    width: 24px;
    height: 24px;
    background: rgba(255,255,255,.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
}

/* ── Footer ── */
.login-footer {
    margin-top: 2rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    color: #94a3b8;
    font-size: .78rem;
}

.footer-logo {
    width: 20px;
    height: 20px;
    opacity: .5;
}

/* ── Alert ── */
.alert-danger {
    border-radius: 12px;
    font-size: .85rem;
    border: none;
    background: #fef2f2;
    color: #dc2626;
    margin-bottom: 1.25rem;
}

/* ═══════════════════════════════════════════
   Responsive
   ═══════════════════════════════════════════ */
@media (max-width: 768px) {
    .login-page {
        flex-direction: column;
    }

    .login-brand {
        flex: none;
        padding: 2.5rem 1.5rem;
    }

    .brand-logo {
        width: 90px;
        height: 90px;
    }

    .brand-title {
        font-size: 1.75rem;
        letter-spacing: 4px;
    }

    .brand-tagline {
        font-size: .82rem;
    }

    .brand-watermark {
        width: 180px;
        height: 180px;
        bottom: -40px;
        right: -40px;
    }

    .login-form-panel {
        padding: 1.5rem;
    }
}
</style>
