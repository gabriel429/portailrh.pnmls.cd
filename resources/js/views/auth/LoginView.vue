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
        <p class="brand-portal">Syst&egrave;me de Gestion des Ressources Humaines</p>
      </div>
    </div>

    <!-- Right form panel -->
    <div class="login-form-panel">
      <div class="login-card">
        <div class="login-header">
          <div class="login-icon">
            <i class="fas fa-sign-in-alt"></i>
          </div>
          <h2>Connexion</h2>
          <p>Acc&eacute;dez &agrave; votre espace personnel</p>
        </div>

        <div v-if="errorMessage" class="alert alert-dismissible fade show"
             :class="errorType === 'session' ? 'alert-warning' : 'alert-danger'">
          <i :class="errorType === 'session' ? 'fas fa-desktop me-2' : 'fas fa-exclamation-circle me-2'"></i>{{ errorMessage }}
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
              <button
                class="password-toggle"
                type="button"
                @click="showPassword = !showPassword"
                tabindex="-1"
                :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
              >
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
          <span>PNMLS &middot; E-PNMLS</span>
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
const errorType = ref('')
const errors = ref({})

async function handleLogin() {
    loading.value = true
    errorMessage.value = ''
    errorType.value = ''
    errors.value = {}

    try {
        await auth.login(form.email, form.password, form.remember)
        const redirect = route.query.redirect
        if (redirect && redirect.startsWith('/') && !redirect.startsWith('//')) {
            router.push(redirect)
        } else {
            router.push('/dashboard')
        }
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {}
            errorMessage.value = e.response.data.message || 'Erreur de validation.'
        } else if (e.response?.status === 401) {
            errorMessage.value = 'Identifiants incorrects.'
        } else if (e.response?.status === 409) {
            errorType.value = 'session'
            errorMessage.value = e.response.data.message
        } else {
            errorMessage.value = e.response?.data?.message
                || 'Erreur de connexion. Veuillez reessayer.'
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
    min-height: 100dvh;
    display: grid;
    grid-template-columns: minmax(360px, 45%) minmax(0, 1fr);
    background:
        linear-gradient(135deg, rgba(224,242,254,.92), rgba(255,255,255,.98) 48%, rgba(236,253,245,.9)),
        radial-gradient(70% 55% at 0% 0%, rgba(0,119,181,.16), transparent 62%),
        radial-gradient(62% 48% at 100% 100%, rgba(15,118,110,.13), transparent 64%);
    overflow-x: hidden;
    position: relative;
}

/* ── Left Brand Panel ── */
.login-brand {
    min-height: 100vh;
    min-height: 100dvh;
    background:
        linear-gradient(160deg, rgba(0,119,181,.96) 0%, rgba(0,90,135,.92) 42%, rgba(15,76,95,.94) 100%),
        radial-gradient(85% 70% at 20% 12%, rgba(255,255,255,.24), transparent 58%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: clamp(2rem, 5vw, 4rem);
    border-right: 1px solid rgba(255,255,255,.20);
    box-shadow: 24px 0 60px rgba(15,35,58,.12);
}

.brand-bg {
    position: absolute;
    inset: -20px;
    background: url('/images/pnmls.jpeg') center/cover no-repeat;
    filter: blur(8px) saturate(110%);
    opacity: 0.13;
}

.login-brand::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(125deg, rgba(255,255,255,.20), transparent 28%, rgba(255,255,255,.08) 62%, transparent),
        repeating-linear-gradient(90deg, rgba(255,255,255,.08) 0, rgba(255,255,255,.08) 1px, transparent 1px, transparent 82px);
    pointer-events: none;
}

.brand-content {
    position: relative;
    z-index: 2;
    text-align: center;
    width: min(100%, 420px);
    padding: 0;
}

.brand-logo {
    width: clamp(104px, 12vw, 142px);
    height: clamp(104px, 12vw, 142px);
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
    font-size: clamp(2rem, 4vw, 2.7rem);
    font-weight: 900;
    color: #fff;
    letter-spacing: .18em;
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
    letter-spacing: .16em;
    line-height: 1.7;
}

/* ── Right Form Panel ── */
.login-form-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
    padding: clamp(1.5rem, 4vw, 3rem);
    background: transparent !important;
}

.login-card {
    width: 100%;
    max-width: 430px;
    padding: clamp(1.35rem, 3vw, 2.1rem);
    border-radius: 26px;
    background:
        linear-gradient(145deg, rgba(255,255,255,.90), rgba(255,255,255,.62)),
        linear-gradient(315deg, rgba(14,165,233,.09), rgba(15,118,110,.06)) !important;
    border: 1px solid rgba(125,211,252,.32);
    box-shadow: 0 24px 68px rgba(15,35,58,.14), inset 0 1px 0 rgba(255,255,255,.74) !important;
    backdrop-filter: blur(20px) saturate(155%);
    -webkit-backdrop-filter: blur(20px) saturate(155%);
    overflow: hidden;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    background:
        linear-gradient(135deg, rgba(0,119,181,.98), rgba(15,118,110,.92)),
        radial-gradient(100% 90% at 20% 0%, rgba(255,255,255,.32), transparent 48%);
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
    min-height: 46px;
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
    width: 2.9rem;
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
    min-height: 48px;
    padding: .75rem;
    border: none;
    border-radius: 12px;
    background:
        linear-gradient(135deg, rgba(0,119,181,.98), rgba(15,118,110,.92)),
        radial-gradient(100% 90% at 20% 0%, rgba(255,255,255,.28), transparent 48%);
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

.alert-warning {
    border-radius: 12px;
    font-size: .85rem;
    border: none;
    background: #fffbeb;
    color: #b45309;
    margin-bottom: 1.25rem;
}

/* ═══════════════════════════════════════════
   Responsive
   ═══════════════════════════════════════════ */
@media (max-width: 768px) {
    .login-page {
        grid-template-columns: 1fr;
        min-height: 100dvh;
    }

    /* Brand panel becomes a compact header on mobile */
    .login-brand {
        min-height: 0;
        padding: 1.35rem 1rem 2.65rem;
        border-right: 0;
        box-shadow: 0 20px 44px rgba(15,35,58,.16);
    }

    .brand-content {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: .15rem .85rem;
        text-align: left;
    }

    .brand-logo {
        grid-row: span 3;
        width: 68px;
        height: 68px;
        margin: 0;
        border-width: 3px;
        box-shadow: 0 4px 20px rgba(0,0,0,.25), 0 0 0 5px rgba(255,255,255,.08);
    }

    .brand-title {
        font-size: 1.32rem;
        letter-spacing: .16em;
        margin: 0;
    }

    .brand-tagline {
        font-size: .74rem;
        margin: 0;
        line-height: 1.4;
    }

    .brand-separator {
        display: none;
    }

    .brand-portal {
        font-size: .64rem;
        letter-spacing: .08em;
        line-height: 1.35;
        margin: .15rem 0 0;
    }

    /* Form panel takes remaining space */
    .login-form-panel {
        padding: 0 1rem 1rem;
        align-items: flex-start;
        background: transparent !important;
        border-radius: 24px 24px 0 0;
        margin-top: -1.8rem;
        position: relative;
        z-index: 3;
        box-shadow: none;
    }

    .login-card {
        max-width: 100%;
        padding: 1.25rem;
        border-radius: 22px;
    }

    .login-header {
        margin-bottom: 1.5rem;
    }

    .login-icon {
        width: 48px;
        height: 48px;
        font-size: 1.1rem;
        border-radius: 14px;
        margin-bottom: .75rem;
    }

    .login-header h2 {
        font-size: 1.3rem;
    }

    .login-header p {
        font-size: .82rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-control {
        padding: .65rem .9rem;
        font-size: .88rem;
        border-radius: 10px;
    }

    .btn-login {
        padding: .7rem;
        font-size: .9rem;
        border-radius: 10px;
        margin-top: .25rem;
    }

    .form-options {
        margin-bottom: 1.25rem;
    }

    .login-footer {
        margin-top: 1.25rem;
    }

    .alert-danger {
        font-size: .8rem;
        border-radius: 10px;
    }
}

/* Extra small screens */
@media (max-width: 380px) {
    .login-brand {
        padding: 1rem .75rem 2.2rem;
    }

    .brand-logo {
        width: 58px;
        height: 58px;
    }

    .brand-title {
        font-size: 1.12rem;
        letter-spacing: .13em;
    }

    .brand-tagline {
        font-size: .68rem;
    }

    .login-form-panel {
        padding: 0 .75rem .85rem;
    }

    .login-header h2 {
        font-size: 1.15rem;
    }

    .brand-portal {
        display: none;
    }
}

@media (max-height: 700px) and (max-width: 768px) {
    .login-brand {
        padding-top: .9rem;
        padding-bottom: 2rem;
    }

    .brand-logo {
        width: 54px;
        height: 54px;
    }

    .login-header {
        margin-bottom: 1rem;
    }

    .login-icon {
        width: 42px;
        height: 42px;
        margin-bottom: .55rem;
    }

    .form-group {
        margin-bottom: .8rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .brand-logo {
        animation: none;
    }
}
</style>
