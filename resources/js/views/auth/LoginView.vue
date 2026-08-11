<template>
  <div class="login-page">
    <!-- Left branding panel -->
    <div class="login-brand">
      <div class="brand-bg"></div>
      <div class="brand-content">
        <div class="brand-logo-shell" aria-hidden="true">
          <span class="brand-logo-pulse brand-logo-pulse--outer"></span>
          <span class="brand-logo-pulse brand-logo-pulse--inner"></span>
          <span class="brand-logo-shine"></span>
          <img src="/images/logo-pnmls.png" alt="PNMLS" class="brand-logo">
        </div>
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

          <button type="button" class="login-support-button" @click="openSupportModal">
            <span><i class="fas fa-headset"></i></span>
            Assistance informatique
          </button>
        </form>

        <div class="login-footer">
          <img src="/images/logo-pnmls.png" alt="PNMLS" class="footer-logo">
          <span>PNMLS &middot; E-PNMLS</span>
        </div>
      </div>
    </div>

    <div v-if="supportOpen" class="support-request-backdrop" @click.self="closeSupportModal">
      <form class="support-request-modal" @submit.prevent="handleSupportRequest">
        <header class="support-request-header">
          <div>
            <span>Assistance informatique</span>
            <h2>Signaler un probl&egrave;me de connexion</h2>
          </div>
          <button type="button" class="support-close-button" aria-label="Fermer" @click="closeSupportModal">
            <i class="fas fa-times"></i>
          </button>
        </header>

        <div v-if="supportSent" class="support-success">
          <span><i class="fas fa-check"></i></span>
          <strong>Demande envoy&eacute;e</strong>
          <p>{{ supportMessage }}</p>
        </div>

        <template v-else>
          <div v-if="supportErrorMessage" class="support-error">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ supportErrorMessage }}</span>
          </div>

          <div class="support-form-grid">
            <label>
              <span>Nom complet</span>
              <input
                v-model.trim="supportForm.requester_name"
                type="text"
                maxlength="160"
                autocomplete="name"
                required
                placeholder="Votre nom"
              >
              <small v-if="supportErrors.requester_name">{{ supportErrors.requester_name[0] }}</small>
            </label>

            <label>
              <span>Email de contact</span>
              <input
                v-model.trim="supportForm.requester_email"
                type="email"
                maxlength="190"
                autocomplete="email"
                required
                placeholder="vous@email.cd"
              >
              <small v-if="supportErrors.requester_email">{{ supportErrors.requester_email[0] }}</small>
            </label>

            <label>
              <span>T&eacute;l&eacute;phone</span>
              <input
                v-model.trim="supportForm.requester_phone"
                type="tel"
                maxlength="60"
                autocomplete="tel"
                placeholder="+243 ..."
              >
              <small v-if="supportErrors.requester_phone">{{ supportErrors.requester_phone[0] }}</small>
            </label>

            <label>
              <span>Type de probl&egrave;me</span>
              <select v-model="supportForm.subject" required>
                <option v-for="subject in supportSubjects" :key="subject" :value="subject">{{ subject }}</option>
              </select>
              <small v-if="supportErrors.subject">{{ supportErrors.subject[0] }}</small>
            </label>

            <label class="support-form-full">
              <span>Description</span>
              <textarea
                v-model.trim="supportForm.description"
                rows="5"
                maxlength="10000"
                required
                placeholder="Expliquez ce qui se passe, le message affich&eacute;, ou depuis quand le probl&egrave;me existe."
              ></textarea>
              <small v-if="supportErrors.description">{{ supportErrors.description[0] }}</small>
            </label>

            <label class="support-upload support-form-full">
              <input
                type="file"
                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                @change="handleSupportFile"
              >
              <span class="support-upload-icon"><i class="fas fa-paperclip"></i></span>
              <span>
                <strong>{{ supportAttachmentName || 'Joindre une capture ou un document' }}</strong>
                <small>Facultatif &middot; 10 Mo maximum</small>
              </span>
            </label>

            <label class="support-honeypot" aria-hidden="true">
              <span>Site web</span>
              <input v-model="supportForm.website" type="text" tabindex="-1" autocomplete="off">
            </label>
          </div>
        </template>

        <footer class="support-request-actions">
          <button type="button" class="support-secondary-button" :disabled="supportLoading" @click="closeSupportModal">
            {{ supportSent ? 'Fermer' : 'Annuler' }}
          </button>
          <button v-if="!supportSent" type="submit" class="support-primary-button" :disabled="supportLoading">
            <span v-if="supportLoading" class="spinner-border spinner-border-sm"></span>
            <i v-else class="fas fa-paper-plane"></i>
            Envoyer
          </button>
        </footer>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import client from '@/api/client'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '', remember: false })
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const errorType = ref('')
const errors = ref({})
const supportOpen = ref(false)
const supportLoading = ref(false)
const supportSent = ref(false)
const supportMessage = ref('')
const supportErrorMessage = ref('')
const supportErrors = ref({})
const supportAttachmentName = ref('')
const supportSubjects = [
    'Mot de passe oublié',
    'Compte bloqué',
    'Email non reconnu',
    'Problème de connexion',
    'Autre',
]
const supportForm = reactive({
    requester_name: '',
    requester_email: '',
    requester_phone: '',
    subject: supportSubjects[0],
    description: '',
    attachment: null,
    website: '',
})

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

function openSupportModal() {
    supportSent.value = false
    supportMessage.value = ''
    supportErrorMessage.value = ''
    supportErrors.value = {}

    if (form.email && !supportForm.requester_email) {
        supportForm.requester_email = form.email
    }

    supportOpen.value = true
}

function closeSupportModal() {
    if (supportLoading.value) return

    supportOpen.value = false

    if (supportSent.value) {
        resetSupportForm()
    }
}

function resetSupportForm() {
    Object.assign(supportForm, {
        requester_name: '',
        requester_email: '',
        requester_phone: '',
        subject: supportSubjects[0],
        description: '',
        attachment: null,
        website: '',
    })
    supportAttachmentName.value = ''
    supportErrorMessage.value = ''
    supportErrors.value = {}
}

function handleSupportFile(event) {
    const file = event.target.files?.[0] || null
    supportForm.attachment = file
    supportAttachmentName.value = file?.name || ''
}

async function handleSupportRequest() {
    supportLoading.value = true
    supportErrorMessage.value = ''
    supportErrors.value = {}

    const payload = new FormData()
    payload.append('requester_name', supportForm.requester_name)
    payload.append('requester_email', supportForm.requester_email)
    if (supportForm.requester_phone) payload.append('requester_phone', supportForm.requester_phone)
    payload.append('subject', supportForm.subject)
    payload.append('description', supportForm.description)
    payload.append('module', 'Connexion')
    payload.append('priority', 'normal')
    if (supportForm.attachment) payload.append('attachment', supportForm.attachment)
    if (supportForm.website) payload.append('website', supportForm.website)

    try {
        const response = await client.post('/technical-support/public', payload, { skipForbiddenToast: true })
        supportMessage.value = response.data?.message || 'Votre demande a été envoyée.'
        supportSent.value = true
    } catch (e) {
        supportErrors.value = e.response?.data?.errors || {}
        supportErrorMessage.value = e.response?.data?.message
            || 'Impossible d’envoyer la demande pour le moment.'
    } finally {
        supportLoading.value = false
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

.brand-logo-shell {
    position: relative;
    display: grid;
    place-items: center;
    width: clamp(104px, 12vw, 142px);
    height: clamp(104px, 12vw, 142px);
    border-radius: 50%;
    margin: 0 auto 1.75rem;
    background:
        radial-gradient(circle at 50% 38%, rgba(255,255,255,.98), rgba(240,249,255,.92) 66%, rgba(219,234,254,.86));
    border: 1px solid rgba(255,255,255,.82);
    box-shadow:
        0 16px 48px rgba(0,0,0,.28),
        0 0 0 7px rgba(255,255,255,.08),
        0 0 70px rgba(213,25,32,.22);
    overflow: hidden;
    animation: loginLogoEnter .82s cubic-bezier(.16, 1, .3, 1) both, loginLogoFloat 5.6s ease-in-out .9s infinite;
}

.brand-logo-shell::after {
    content: "";
    position: absolute;
    left: 18%;
    right: 18%;
    bottom: -1px;
    height: 4px;
    border-radius: 999px;
    background: linear-gradient(90deg, #d51920, #ffef00, #00a1de);
    box-shadow: 0 0 22px rgba(255,255,255,.36);
}

.brand-logo-pulse {
    position: absolute;
    inset: -10px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.22);
    pointer-events: none;
}

.brand-logo-pulse--outer {
    animation: loginLogoPulse 2.45s ease-out infinite;
}

.brand-logo-pulse--inner {
    inset: 9px;
    border-color: rgba(213,25,32,.28);
    animation: loginLogoBreath 2.1s ease-in-out infinite;
}

.brand-logo-shine {
    position: absolute;
    inset: -22%;
    background: linear-gradient(115deg, transparent 34%, rgba(255,255,255,.74) 49%, transparent 64%);
    transform: translateX(-125%) rotate(8deg);
    animation: loginLogoShine 4.6s ease-in-out 1.2s infinite;
    pointer-events: none;
}

.brand-logo {
    position: relative;
    z-index: 1;
    display: block;
    width: 84%;
    height: 84%;
    object-fit: contain;
    filter: drop-shadow(0 8px 14px rgba(2,23,37,.22));
}

@keyframes loginLogoEnter {
    0% {
        opacity: 0;
        transform: scale(.78);
    }
    62% {
        opacity: 1;
        transform: scale(1.06);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes loginLogoFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-7px); }
}

@keyframes loginLogoPulse {
    0% {
        opacity: .7;
        transform: scale(.92);
    }
    100% {
        opacity: 0;
        transform: scale(1.18);
    }
}

@keyframes loginLogoBreath {
    0%, 100% {
        opacity: .45;
        transform: scale(.98);
    }
    50% {
        opacity: .92;
        transform: scale(1.03);
    }
}

@keyframes loginLogoShine {
    0%, 42% {
        opacity: 0;
        transform: translateX(-125%) rotate(8deg);
    }
    55% {
        opacity: .55;
    }
    70%, 100% {
        opacity: 0;
        transform: translateX(125%) rotate(8deg);
    }
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

.login-support-button {
    width: 100%;
    min-height: 44px;
    margin-top: .85rem;
    padding: .68rem .8rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    color: #075e78;
    background: rgba(224,242,254,.78);
    border: 1px solid rgba(14,116,144,.18);
    border-radius: 8px;
    font-size: .88rem;
    font-weight: 800;
    transition: all .2s ease;
}

.login-support-button span {
    width: 26px;
    height: 26px;
    display: grid;
    place-items: center;
    color: #fff;
    background: #0d8fa3;
    border-radius: 50%;
    box-shadow: 0 6px 14px rgba(13,143,163,.22);
}

.login-support-button:hover {
    color: #064e63;
    background: #e0f7fb;
    border-color: rgba(13,143,163,.38);
    transform: translateY(-1px);
}

/* ── Public support request ── */
.support-request-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: grid;
    place-items: center;
    padding: 18px;
    background: rgba(6, 24, 38, .68);
    backdrop-filter: blur(7px);
    -webkit-backdrop-filter: blur(7px);
}

.support-request-modal {
    width: min(680px, 100%);
    max-height: calc(100dvh - 36px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #fff;
    border: 1px solid rgba(186,230,253,.42);
    border-radius: 8px;
    box-shadow: 0 28px 80px rgba(2, 23, 37, .34);
}

.support-request-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.35rem;
    color: #fff;
    background:
        linear-gradient(135deg, #073f5e, #0d8fa3),
        radial-gradient(120% 100% at 0% 0%, rgba(255,255,255,.26), transparent 45%);
    border-bottom: 4px solid #12a594;
}

.support-request-header span {
    display: block;
    margin-bottom: .28rem;
    color: #a7f3d0;
    font-size: .72rem;
    font-weight: 900;
    text-transform: uppercase;
}

.support-request-header h2 {
    margin: 0;
    color: #fff;
    font-size: 1.2rem;
    font-weight: 850;
    line-height: 1.25;
}

.support-close-button {
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    display: grid;
    place-items: center;
    color: #fff;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 8px;
}

.support-form-grid {
    flex: 1 1 auto;
    min-height: 0;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    padding: 1.35rem;
    overflow: auto;
}

.support-form-full {
    grid-column: 1 / -1;
}

.support-form-grid label > span,
.support-form-grid label > small,
.support-upload strong,
.support-upload small {
    display: block;
}

.support-form-grid label > span {
    margin-bottom: .4rem;
    color: #34495a;
    font-size: .78rem;
    font-weight: 850;
}

.support-form-grid input:not([type="file"]),
.support-form-grid select,
.support-form-grid textarea {
    width: 100%;
    min-height: 44px;
    padding: .68rem .78rem;
    color: #142332;
    background: #fff;
    border: 1px solid #cbd8df;
    border-radius: 8px;
    outline: none;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.support-form-grid textarea {
    resize: vertical;
    min-height: 118px;
}

.support-form-grid input:focus,
.support-form-grid select:focus,
.support-form-grid textarea:focus {
    border-color: #0d8fa3;
    box-shadow: 0 0 0 4px rgba(13,143,163,.12);
}

.support-form-grid label > small {
    margin-top: .3rem;
    color: #b91c1c;
    font-size: .72rem;
}

.support-upload {
    min-height: 78px;
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: .9rem;
    background: #f6fafb;
    border: 1px dashed #8ca9b7;
    border-radius: 8px;
    cursor: pointer;
}

.support-upload input {
    display: none;
}

.support-upload-icon {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    display: grid;
    place-items: center;
    color: #075e78;
    background: #e0f2fe;
    border-radius: 8px;
}

.support-upload strong {
    max-width: 100%;
    overflow: hidden;
    color: #26384b;
    font-size: .86rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.support-upload small {
    margin-top: .15rem;
    color: #718392;
    font-size: .74rem;
}

.support-honeypot {
    position: absolute;
    left: -10000px;
    width: 1px;
    height: 1px;
    overflow: hidden;
}

.support-error {
    margin: 1.1rem 1.35rem 0;
    padding: .75rem .85rem;
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    color: #991b1b;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    font-size: .84rem;
}

.support-success {
    padding: 2rem 1.35rem 1.45rem;
    text-align: center;
}

.support-success > span {
    width: 54px;
    height: 54px;
    display: grid;
    place-items: center;
    margin: 0 auto .85rem;
    color: #fff;
    background: #0d9c8c;
    border-radius: 50%;
    box-shadow: 0 14px 30px rgba(13,156,140,.22);
}

.support-success strong {
    display: block;
    color: #142332;
    font-size: 1.12rem;
}

.support-success p {
    width: min(460px, 100%);
    margin: .45rem auto 0;
    color: #66788a;
    line-height: 1.6;
}

.support-request-actions {
    display: flex;
    justify-content: flex-end;
    gap: .65rem;
    padding: 1rem 1.35rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.support-primary-button,
.support-secondary-button {
    min-height: 40px;
    padding: 0 .95rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .48rem;
    border-radius: 8px;
    font-weight: 850;
}

.support-primary-button {
    color: #fff;
    background: #0d8fa3;
    border: 1px solid #0d8fa3;
}

.support-secondary-button {
    color: #334155;
    background: #fff;
    border: 1px solid #cbd5e1;
}

.support-primary-button:disabled,
.support-secondary-button:disabled {
    opacity: .62;
    cursor: not-allowed;
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

    .brand-logo-shell {
        grid-row: span 3;
        width: 68px;
        height: 68px;
        margin: 0;
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

    .login-support-button {
        min-height: 42px;
        font-size: .84rem;
    }

    .support-request-backdrop {
        padding: 10px;
        align-items: end;
    }

    .support-request-modal {
        max-height: calc(100dvh - 20px);
        border-radius: 8px 8px 0 0;
    }

    .support-request-header {
        padding: 1rem;
    }

    .support-request-header h2 {
        font-size: 1.05rem;
    }

    .support-form-grid {
        grid-template-columns: 1fr;
        gap: .85rem;
        padding: 1rem;
    }

    .support-form-full {
        grid-column: auto;
    }

    .support-request-actions {
        padding: .85rem 1rem;
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

    .brand-logo-shell {
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

    .support-request-actions {
        flex-direction: column-reverse;
    }

    .support-primary-button,
    .support-secondary-button {
        width: 100%;
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

    .brand-logo-shell {
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
    .brand-logo-shell,
    .brand-logo-pulse,
    .brand-logo-shine {
        animation: none !important;
    }
}
</style>
