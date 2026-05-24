<template>
  <div class="mailbox-page">
    <section class="mailbox-hero">
      <div>
        <span class="mailbox-kicker">Messagerie professionnelle</span>
        <h1>Boite mail</h1>
        <p>{{ settings?.account_email || 'Compte professionnel' }}</p>
      </div>
      <div class="mailbox-hero-actions">
        <router-link :to="{ name: 'mail.history' }" class="mailbox-ghost-btn">
          <i class="fas fa-paper-plane"></i>
          Envoyes
        </router-link>
        <button v-if="settings?.has_credentials" type="button" class="mailbox-ghost-btn" @click="composerOpen = !composerOpen">
          <i class="fas fa-pen"></i>
          Nouveau mail
        </button>
        <button type="button" class="mailbox-primary-btn" @click="settingsOpen = !settingsOpen">
          <i class="fas fa-cog"></i>
          Reglages
        </button>
      </div>
    </section>

    <div v-if="loadingSettings" class="mailbox-loading">
      <div class="spinner-border text-primary" role="status"></div>
      <span>Preparation de la messagerie...</span>
    </div>

    <section v-else-if="showSettingsPanel" class="mailbox-settings">
      <div class="mailbox-settings-head">
        <div>
          <h2>Connexion de la boite professionnelle</h2>
          <p>Les identifiants IMAP sont chiffres dans l'application.</p>
        </div>
        <button
          v-if="settings?.has_credentials"
          type="button"
          class="mailbox-icon-btn"
          title="Fermer"
          @click="settingsOpen = false"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form class="mailbox-settings-form" @submit.prevent="saveMailboxSettings">
        <label>
          <span>Email professionnel</span>
          <input v-model.trim="settingsForm.email" type="email" autocomplete="email" required>
        </label>
        <label>
          <span>Utilisateur IMAP</span>
          <input v-model.trim="settingsForm.username" type="text" autocomplete="username" required>
        </label>
        <label>
          <span>Mot de passe</span>
          <input
            v-model="settingsForm.password"
            type="password"
            autocomplete="current-password"
            :placeholder="settings?.has_credentials ? 'Laisser vide pour conserver' : ''"
            :required="!settings?.has_credentials"
          >
        </label>
        <label>
          <span>Serveur IMAP</span>
          <input v-model.trim="settingsForm.imap_host" type="text" required>
        </label>
        <label>
          <span>Port</span>
          <input v-model.number="settingsForm.imap_port" type="number" min="1" max="65535" required>
        </label>
        <label>
          <span>Securite</span>
          <select v-model="settingsForm.imap_encryption" required>
            <option value="ssl">SSL</option>
            <option value="tls">TLS</option>
            <option value="none">Aucune</option>
          </select>
        </label>

        <div v-if="settingsError" class="mailbox-alert mailbox-alert-danger">
          <i class="fas fa-triangle-exclamation"></i>
          {{ settingsError }}
        </div>

        <div class="mailbox-settings-actions">
          <button type="submit" class="mailbox-primary-btn" :disabled="savingSettings">
            <span v-if="savingSettings" class="spinner-border spinner-border-sm"></span>
            <i v-else class="fas fa-plug"></i>
            Connecter
          </button>
        </div>
      </form>
    </section>

    <section v-if="settings?.has_credentials && composerOpen && !showSettingsPanel" class="mailbox-compose">
      <div class="mailbox-settings-head">
        <div>
          <h2>Nouveau mail</h2>
          <p>{{ settings.account_email }}</p>
        </div>
        <button type="button" class="mailbox-icon-btn" title="Fermer" @click="composerOpen = false">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form class="mailbox-compose-form" @submit.prevent="sendMail">
        <label>
          <span>Destinataires</span>
          <input v-model.trim="composeForm.to" type="text" placeholder="email@domaine.cd, autre@domaine.cd" required>
        </label>
        <label>
          <span>Objet</span>
          <input v-model.trim="composeForm.subject" type="text" required maxlength="180">
        </label>
        <label class="mailbox-compose-body">
          <span>Message</span>
          <textarea v-model.trim="composeForm.body" rows="8" required></textarea>
        </label>

        <div v-if="composeError" class="mailbox-alert mailbox-alert-danger">
          <i class="fas fa-triangle-exclamation"></i>
          {{ composeError }}
        </div>

        <div class="mailbox-settings-actions">
          <button type="button" class="mailbox-ghost-light-btn" @click="composerOpen = false">Annuler</button>
          <button type="submit" class="mailbox-primary-btn" :disabled="sendingMail">
            <span v-if="sendingMail" class="spinner-border spinner-border-sm"></span>
            <i v-else class="fas fa-paper-plane"></i>
            Envoyer
          </button>
        </div>
      </form>
    </section>

    <section v-if="settings?.has_credentials && !showSettingsPanel" class="mailbox-workspace">
      <header class="mailbox-toolbar">
        <div class="mailbox-search">
          <i class="fas fa-search"></i>
          <input
            v-model.trim="search"
            type="search"
            placeholder="Rechercher dans les mails..."
            @input="onSearch"
          >
        </div>
        <div class="mailbox-toolbar-actions">
          <span class="mailbox-unread">{{ meta.unread_count || 0 }} non lu(s)</span>
          <button type="button" class="mailbox-icon-btn" title="Actualiser" @click="loadMessages(meta.current_page || 1)">
            <i class="fas fa-rotate-right"></i>
          </button>
        </div>
      </header>

      <div v-if="messagesError" class="mailbox-alert mailbox-alert-danger mailbox-alert-inline">
        <i class="fas fa-triangle-exclamation"></i>
        {{ messagesError }}
      </div>

      <div v-if="loadingMessages" class="mailbox-loading mailbox-loading-panel">
        <div class="spinner-border text-primary" role="status"></div>
        <span>Chargement des mails...</span>
      </div>

      <div v-else-if="!messages.length" class="mailbox-empty">
        <i class="fas fa-inbox"></i>
        <h3>Aucun mail trouve</h3>
        <p>{{ search ? 'Essayez une autre recherche.' : 'La boite de reception est vide.' }}</p>
      </div>

      <div v-else class="mailbox-grid">
        <aside class="mailbox-list" aria-label="Liste des mails">
          <button
            v-for="message in messages"
            :key="message.uid"
            type="button"
            class="mailbox-list-item"
            :class="{ active: selectedUid === message.uid, unread: message.unread }"
            @click="selectMessage(message)"
          >
            <span class="mailbox-avatar">{{ senderInitial(message.from) }}</span>
            <span class="mailbox-list-content">
              <span class="mailbox-list-top">
                <strong>{{ cleanSender(message.from) || 'Expediteur' }}</strong>
                <time>{{ formatDate(message.date, true) }}</time>
              </span>
              <span class="mailbox-subject">{{ message.subject }}</span>
              <span class="mailbox-list-meta">
                <i v-if="message.answered" class="fas fa-reply"></i>
                <i v-if="message.flagged" class="fas fa-star"></i>
                {{ formatSize(message.size) }}
              </span>
            </span>
          </button>

          <nav v-if="meta.last_page > 1" class="mailbox-pagination" aria-label="Pagination mails">
            <button type="button" :disabled="meta.current_page <= 1" @click="loadMessages(meta.current_page - 1)">
              <i class="fas fa-chevron-left"></i>
            </button>
            <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
            <button type="button" :disabled="meta.current_page >= meta.last_page" @click="loadMessages(meta.current_page + 1)">
              <i class="fas fa-chevron-right"></i>
            </button>
          </nav>
        </aside>

        <article class="mailbox-reader">
          <div v-if="selectedLoading" class="mailbox-loading mailbox-loading-panel">
            <div class="spinner-border text-primary" role="status"></div>
            <span>Ouverture du mail...</span>
          </div>

          <template v-else-if="selectedMessage">
            <header class="mailbox-reader-head">
              <div>
                <h2>{{ selectedMessage.subject }}</h2>
                <div class="mailbox-reader-meta">
                  <span>{{ selectedMessage.from }}</span>
                  <time>{{ formatDate(selectedMessage.date) }}</time>
                </div>
              </div>
              <div class="mailbox-reader-actions">
                <button type="button" class="mailbox-icon-btn" title="Lu / non lu" @click="toggleRead(selectedMessage)">
                  <i class="fas" :class="selectedMessage.unread ? 'fa-envelope-open' : 'fa-envelope'"></i>
                </button>
                <button type="button" class="mailbox-icon-btn mailbox-danger-btn" title="Supprimer" @click="deleteMessage(selectedMessage.uid)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </header>
            <div class="mailbox-reader-body">{{ selectedMessage.body || 'Aucun contenu lisible.' }}</div>
          </template>

          <div v-else class="mailbox-reader-empty">
            <i class="fas fa-envelope-open-text"></i>
            <p>Selectionnez un mail pour l'afficher.</p>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import client from '@/api/client'

const settings = ref(null)
const loadingSettings = ref(true)
const settingsOpen = ref(false)
const savingSettings = ref(false)
const settingsError = ref('')
const composerOpen = ref(false)
const sendingMail = ref(false)
const composeError = ref('')

const messages = ref([])
const meta = ref({ total: 0, current_page: 1, last_page: 1, per_page: 15, unread_count: 0 })
const loadingMessages = ref(false)
const messagesError = ref('')
const search = ref('')
const selectedUid = ref(null)
const selectedMessage = ref(null)
const selectedLoading = ref(false)
let searchTimer = null

const settingsForm = reactive({
  email: '',
  username: '',
  password: '',
  imap_host: 'camulus.o2switch.net',
  imap_port: 993,
  imap_encryption: 'ssl',
})

const composeForm = reactive({
  to: '',
  subject: '',
  body: '',
})

const showSettingsPanel = computed(() => !settings.value?.has_credentials || settingsOpen.value)

function hydrateSettingsForm(payload) {
  settingsForm.email = payload?.account_email || ''
  settingsForm.username = payload?.username || payload?.account_email || ''
  settingsForm.password = ''
  settingsForm.imap_host = payload?.imap?.host || 'camulus.o2switch.net'
  settingsForm.imap_port = payload?.imap?.port || 993
  settingsForm.imap_encryption = payload?.imap?.encryption || 'ssl'
}

async function loadSettings() {
  loadingSettings.value = true
  settingsError.value = ''
  try {
    const { data } = await client.get('/mailbox/settings')
    settings.value = data.data
    hydrateSettingsForm(data.data)
    settingsOpen.value = !data.data?.has_credentials
    if (data.data?.has_credentials) {
      await loadMessages(1)
    }
  } catch (error) {
    settingsError.value = firstError(error) || 'Impossible de charger les reglages mail.'
  } finally {
    loadingSettings.value = false
  }
}

async function saveMailboxSettings() {
  savingSettings.value = true
  settingsError.value = ''

  try {
    const payload = {
      email: settingsForm.email,
      username: settingsForm.username,
      imap_host: settingsForm.imap_host,
      imap_port: settingsForm.imap_port,
      imap_encryption: settingsForm.imap_encryption,
    }

    if (settingsForm.password) {
      payload.password = settingsForm.password
    }

    const { data } = await client.post('/mailbox/settings', payload)
    settings.value = data.data
    hydrateSettingsForm(data.data)
    settingsOpen.value = false
    await loadMessages(1)
  } catch (error) {
    settingsError.value = firstError(error) || 'Connexion a la boite mail impossible.'
  } finally {
    savingSettings.value = false
  }
}

async function sendMail() {
  const recipients = composeForm.to
    .split(/[;,\n]+/)
    .map(email => email.trim())
    .filter(Boolean)

  if (!recipients.length) {
    composeError.value = 'Ajoutez au moins un destinataire.'
    return
  }

  sendingMail.value = true
  composeError.value = ''

  try {
    await client.post('/mailbox/send', {
      to: recipients,
      subject: composeForm.subject,
      body: composeForm.body,
    })

    composeForm.to = ''
    composeForm.subject = ''
    composeForm.body = ''
    composerOpen.value = false
  } catch (error) {
    composeError.value = firstError(error) || 'Envoi impossible.'
  } finally {
    sendingMail.value = false
  }
}

async function loadMessages(page = 1) {
  if (!settings.value?.has_credentials) return

  loadingMessages.value = true
  messagesError.value = ''

  try {
    const { data } = await client.get('/mailbox/messages', {
      params: {
        page,
        per_page: meta.value.per_page || 15,
        search: search.value || undefined,
      },
    })

    messages.value = data.data || []
    meta.value = { ...meta.value, ...(data.meta || {}) }

    if (messages.value.length) {
      const currentStillVisible = messages.value.some(message => message.uid === selectedUid.value)
      if (!currentStillVisible) {
        await selectMessage(messages.value[0])
      }
    } else {
      selectedUid.value = null
      selectedMessage.value = null
    }
  } catch (error) {
    messages.value = []
    selectedUid.value = null
    selectedMessage.value = null
    messagesError.value = firstError(error) || 'Impossible de lire la boite mail.'
    if (error.response?.status === 422) {
      settingsOpen.value = true
    }
  } finally {
    loadingMessages.value = false
  }
}

async function selectMessage(message) {
  selectedUid.value = message.uid
  selectedLoading.value = true

  try {
    const { data } = await client.get(`/mailbox/messages/${message.uid}`)
    selectedMessage.value = data.data
    patchMessage(message.uid, { seen: true, unread: false })
    meta.value.unread_count = Math.max((meta.value.unread_count || 0) - (message.unread ? 1 : 0), 0)
  } catch (error) {
    messagesError.value = firstError(error) || 'Impossible d ouvrir ce mail.'
  } finally {
    selectedLoading.value = false
  }
}

async function toggleRead(message) {
  const nextSeen = !!message.unread
  try {
    const { data } = await client.post(`/mailbox/messages/${message.uid}/read`, { seen: nextSeen })
    patchMessage(message.uid, data.data)
    if (selectedMessage.value?.uid === message.uid) {
      selectedMessage.value = { ...selectedMessage.value, ...data.data }
    }
    meta.value.unread_count = Math.max((meta.value.unread_count || 0) + (nextSeen ? -1 : 1), 0)
  } catch (error) {
    messagesError.value = firstError(error) || 'Action impossible.'
  }
}

async function deleteMessage(uid) {
  if (!window.confirm('Supprimer ce mail ?')) return

  try {
    await client.delete(`/mailbox/messages/${uid}`)
    messages.value = messages.value.filter(message => message.uid !== uid)
    if (selectedUid.value === uid) {
      selectedUid.value = null
      selectedMessage.value = null
    }
    await loadMessages(messages.value.length ? meta.value.current_page : Math.max(meta.value.current_page - 1, 1))
  } catch (error) {
    messagesError.value = firstError(error) || 'Suppression impossible.'
  }
}

function patchMessage(uid, patch) {
  messages.value = messages.value.map(message => (
    message.uid === uid ? { ...message, ...patch } : message
  ))
}

function onSearch() {
  window.clearTimeout(searchTimer)
  searchTimer = window.setTimeout(() => loadMessages(1), 400)
}

function firstError(error) {
  const errors = error.response?.data?.errors
  if (errors) {
    const first = Object.values(errors).flat()[0]
    if (first) return first
  }

  return error.response?.data?.message || error.message
}

function cleanSender(value) {
  if (!value) return ''
  return value.replace(/<[^>]+>/g, '').replace(/"/g, '').trim()
}

function senderInitial(value) {
  const cleaned = cleanSender(value)
  return (cleaned.charAt(0) || '@').toUpperCase()
}

function formatDate(value, compact = false) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleDateString('fr-FR', compact
    ? { day: '2-digit', month: '2-digit' }
    : { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatSize(size) {
  const value = Number(size || 0)
  if (value < 1024) return `${value} o`
  if (value < 1024 * 1024) return `${Math.round(value / 1024)} Ko`
  return `${(value / 1024 / 1024).toFixed(1)} Mo`
}

onMounted(loadSettings)
</script>

<style scoped>
.mailbox-page {
  max-width: 1180px;
  margin: 0 auto;
  padding: 20px;
}

.mailbox-hero,
.mailbox-settings,
.mailbox-compose,
.mailbox-workspace {
  border: 1px solid rgba(14, 116, 144, 0.18);
  border-radius: 8px;
  box-shadow: 0 12px 34px rgba(15, 118, 110, 0.12);
}

.mailbox-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 18px;
  padding: 18px 20px;
  color: #fff;
  background: linear-gradient(135deg, #0877a8 0%, #168b7f 100%);
}

.mailbox-kicker {
  display: block;
  margin-bottom: 4px;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
  opacity: 0.82;
}

.mailbox-hero h1 {
  margin: 0;
  font-size: 1.55rem;
  font-weight: 800;
}

.mailbox-hero p {
  margin: 4px 0 0;
  opacity: 0.9;
}

.mailbox-hero-actions,
.mailbox-toolbar-actions,
.mailbox-reader-actions,
.mailbox-settings-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.mailbox-primary-btn,
.mailbox-ghost-btn,
.mailbox-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 40px;
  border: 0;
  border-radius: 8px;
  font-weight: 800;
  line-height: 1;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.mailbox-primary-btn {
  padding: 0 16px;
  color: #fff;
  background: #03779c;
  box-shadow: 0 8px 18px rgba(3, 119, 156, 0.2);
}

.mailbox-ghost-btn {
  padding: 0 14px;
  color: #fff;
  text-decoration: none;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.28);
}

.mailbox-icon-btn {
  width: 40px;
  color: #0f5f76;
  background: #e8f5f8;
}

.mailbox-danger-btn {
  color: #b42318;
  background: #fee4e2;
}

.mailbox-primary-btn:hover,
.mailbox-ghost-btn:hover,
.mailbox-icon-btn:hover {
  transform: translateY(-1px);
}

.mailbox-primary-btn:disabled,
.mailbox-icon-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}

.mailbox-compose,
.mailbox-settings {
  padding: 20px;
  background: #fff;
}

.mailbox-compose {
  margin-bottom: 18px;
}

.mailbox-settings-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 18px;
}

.mailbox-settings h2 {
  margin: 0 0 4px;
  color: #102a43;
  font-size: 1.12rem;
  font-weight: 800;
}

.mailbox-settings p {
  margin: 0;
  color: #64748b;
}

.mailbox-settings-form {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.mailbox-settings-form label {
  display: grid;
  gap: 6px;
  color: #334155;
  font-size: 0.86rem;
  font-weight: 800;
}

.mailbox-settings-form input,
.mailbox-settings-form select,
.mailbox-compose-form input,
.mailbox-compose-form textarea,
.mailbox-search input {
  width: 100%;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  background: #fff;
  color: #102a43;
  outline: none;
}

.mailbox-settings-form input,
.mailbox-settings-form select,
.mailbox-compose-form input {
  min-height: 42px;
  padding: 0 12px;
}

.mailbox-settings-form input:focus,
.mailbox-settings-form select:focus,
.mailbox-compose-form input:focus,
.mailbox-compose-form textarea:focus,
.mailbox-search input:focus {
  border-color: #0786b1;
  box-shadow: 0 0 0 3px rgba(7, 134, 177, 0.12);
}

.mailbox-settings-actions,
.mailbox-alert {
  grid-column: 1 / -1;
}

.mailbox-compose-form {
  display: grid;
  gap: 14px;
}

.mailbox-compose-form label {
  display: grid;
  gap: 6px;
  color: #334155;
  font-size: 0.86rem;
  font-weight: 800;
}

.mailbox-compose-form textarea {
  min-height: 190px;
  padding: 12px;
  resize: vertical;
}

.mailbox-ghost-light-btn {
  min-height: 40px;
  padding: 0 16px;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
  font-weight: 800;
}

.mailbox-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 12px;
  border-radius: 8px;
  font-weight: 700;
}

.mailbox-alert-danger {
  color: #9f1239;
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

.mailbox-alert-inline {
  margin: 14px;
}

.mailbox-workspace {
  overflow: hidden;
  background: #fff;
}

.mailbox-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px;
  background: #f4fbfd;
  border-bottom: 1px solid #d9ecf2;
}

.mailbox-search {
  position: relative;
  flex: 1;
  max-width: 520px;
}

.mailbox-search i {
  position: absolute;
  top: 50%;
  left: 13px;
  color: #5f7f8c;
  transform: translateY(-50%);
}

.mailbox-search input {
  min-height: 42px;
  padding: 0 14px 0 40px;
}

.mailbox-unread {
  padding: 9px 12px;
  border-radius: 999px;
  color: #075985;
  background: #e0f2fe;
  font-size: 0.84rem;
  font-weight: 800;
  white-space: nowrap;
}

.mailbox-grid {
  display: grid;
  grid-template-columns: minmax(310px, 390px) minmax(0, 1fr);
  min-height: 590px;
}

.mailbox-list {
  display: flex;
  flex-direction: column;
  max-height: 72vh;
  overflow: auto;
  border-right: 1px solid #e2edf2;
  background: #f8fbfc;
}

.mailbox-list-item {
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
  gap: 10px;
  width: 100%;
  padding: 13px 12px;
  border: 0;
  border-bottom: 1px solid #e2edf2;
  background: transparent;
  color: #243b53;
  text-align: left;
  cursor: pointer;
}

.mailbox-list-item:hover,
.mailbox-list-item.active {
  background: #e9f7fb;
}

.mailbox-list-item.unread .mailbox-subject,
.mailbox-list-item.unread strong {
  color: #062f4f;
  font-weight: 900;
}

.mailbox-avatar {
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  border-radius: 8px;
  color: #06708f;
  background: #d9f3f6;
  font-weight: 900;
}

.mailbox-list-content,
.mailbox-list-top {
  min-width: 0;
}

.mailbox-list-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.mailbox-list-top strong,
.mailbox-subject {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-list-top strong {
  display: block;
  color: #334155;
  font-size: 0.9rem;
}

.mailbox-list-top time,
.mailbox-list-meta {
  color: #64748b;
  font-size: 0.78rem;
}

.mailbox-subject {
  display: block;
  margin-top: 3px;
  color: #475569;
  font-size: 0.88rem;
}

.mailbox-list-meta {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-top: 6px;
}

.mailbox-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px;
  margin-top: auto;
  background: #fff;
}

.mailbox-pagination button {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
}

.mailbox-reader {
  min-width: 0;
  background: #fff;
}

.mailbox-reader-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 18px;
  border-bottom: 1px solid #e2edf2;
}

.mailbox-reader-head h2 {
  margin: 0 0 8px;
  color: #102a43;
  font-size: 1.18rem;
  font-weight: 900;
}

.mailbox-reader-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 14px;
  color: #64748b;
  font-size: 0.86rem;
}

.mailbox-reader-body {
  min-height: 420px;
  padding: 18px;
  color: #1f2937;
  font-size: 0.94rem;
  line-height: 1.65;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.mailbox-loading,
.mailbox-empty,
.mailbox-reader-empty {
  display: grid;
  place-items: center;
  gap: 12px;
  min-height: 220px;
  color: #64748b;
  text-align: center;
}

.mailbox-loading-panel {
  min-height: 420px;
}

.mailbox-empty i,
.mailbox-reader-empty i {
  color: #9ccbd8;
  font-size: 2.4rem;
}

.mailbox-empty h3 {
  margin: 0;
  color: #334155;
  font-size: 1rem;
  font-weight: 900;
}

.mailbox-empty p,
.mailbox-reader-empty p {
  margin: 0;
}

@media (max-width: 860px) {
  .mailbox-page {
    padding: 14px;
  }

  .mailbox-hero,
  .mailbox-toolbar,
  .mailbox-reader-head,
  .mailbox-settings-head {
    align-items: stretch;
    flex-direction: column;
  }

  .mailbox-hero-actions,
  .mailbox-toolbar-actions {
    justify-content: space-between;
  }

  .mailbox-settings-form {
    grid-template-columns: 1fr;
  }

  .mailbox-grid {
    grid-template-columns: 1fr;
  }

  .mailbox-list {
    max-height: 420px;
    border-right: 0;
    border-bottom: 1px solid #e2edf2;
  }

  .mailbox-reader-body {
    min-height: 300px;
  }
}
</style>
