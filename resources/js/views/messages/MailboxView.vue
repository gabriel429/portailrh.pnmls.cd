<template>
  <div class="mailbox-page">
    <div v-if="loadingSettings" class="mailbox-loading mailbox-loading-page">
      <div class="spinner-border text-primary" role="status"></div>
      <span>Preparation de la messagerie...</span>
    </div>

    <section v-else-if="showSettingsPanel" class="mailbox-settings">
      <div class="mailbox-settings-head">
        <div>
          <span class="mailbox-kicker">Messagerie professionnelle</span>
          <h1>Connexion de la boite mail</h1>
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

    <section v-else class="outlook-shell">
      <aside class="mailbox-sidebar">
        <div class="mailbox-account">
          <div class="mailbox-account-avatar">{{ accountInitial }}</div>
          <div>
            <span>Compte</span>
            <strong>{{ settings?.account_email }}</strong>
          </div>
        </div>

        <button type="button" class="mailbox-compose-btn" @click="openComposer">
          <i class="fas fa-pen"></i>
          Nouveau mail
        </button>

        <nav class="mailbox-folder-nav" aria-label="Navigation messagerie">
          <div class="mailbox-nav-title">Favoris</div>
          <button type="button" class="mailbox-folder-btn" :class="{ active: isInboxActive }" @click="openInbox">
            <i class="fas fa-inbox"></i>
            <span>Boite de reception</span>
            <b v-if="inboxFolder?.unread">{{ inboxFolder.unread }}</b>
          </button>
          <button type="button" class="mailbox-folder-btn" :class="{ active: activeFilter === 'unread' }" @click="openFilter('unread')">
            <i class="fas fa-envelope"></i>
            <span>Non lus</span>
            <b v-if="inboxFolder?.unread">{{ inboxFolder.unread }}</b>
          </button>
          <button type="button" class="mailbox-folder-btn" :class="{ active: activeFilter === 'flagged' }" @click="openFilter('flagged')">
            <i class="fas fa-star"></i>
            <span>Avec etoile</span>
          </button>
          <router-link :to="{ name: 'mail.history' }" class="mailbox-folder-btn">
            <i class="fas fa-paper-plane"></i>
            <span>Historique envoyes</span>
          </router-link>

          <div class="mailbox-nav-title">Dossiers</div>
          <button
            v-for="folder in visibleFolders"
            :key="folder.name"
            type="button"
            class="mailbox-folder-btn"
            :class="{ active: activeFilter === '' && activeFolder === folder.name }"
            @click="openFolder(folder)"
          >
            <i class="fas" :class="folderIcon(folder.type)"></i>
            <span>{{ folder.label }}</span>
            <b v-if="folder.unread">{{ folder.unread }}</b>
          </button>
        </nav>

        <div class="mailbox-side-card">
          <span>Connecte via IMAP</span>
          <strong>{{ folders.length }} dossier(s)</strong>
        </div>
      </aside>

      <main class="mailbox-main">
        <header class="mailbox-topbar">
          <div>
            <span class="mailbox-kicker">{{ activeSubtitle }}</span>
            <h1>{{ activeTitle }}</h1>
          </div>
          <div class="mailbox-search">
            <i class="fas fa-search"></i>
            <input
              v-model.trim="search"
              type="search"
              placeholder="Rechercher dans les mails..."
              @input="onSearch"
            >
          </div>
        </header>

        <div class="mailbox-commandbar">
          <button type="button" class="mailbox-command primary" @click="openComposer">
            <i class="fas fa-plus"></i>
            Nouveau
          </button>
          <button type="button" class="mailbox-command" @click="loadMessages(meta.current_page || 1)">
            <i class="fas fa-rotate-right"></i>
            Actualiser
          </button>
          <button type="button" class="mailbox-command" :disabled="!selectedMessage" @click="toggleRead(selectedMessage)">
            <i class="fas" :class="selectedMessage?.unread ? 'fa-envelope-open' : 'fa-envelope'"></i>
            {{ selectedMessage?.unread ? 'Marquer lu' : 'Non lu' }}
          </button>
          <button type="button" class="mailbox-command" :disabled="!selectedMessage" @click="toggleFlag(selectedMessage)">
            <i class="fas fa-star"></i>
            {{ selectedMessage?.flagged ? 'Retirer etoile' : 'Etoile' }}
          </button>
          <button type="button" class="mailbox-command" :disabled="!selectedMessage || !archiveFolder" @click="archiveSelected">
            <i class="fas fa-box-archive"></i>
            Archiver
          </button>
          <button type="button" class="mailbox-command danger" :disabled="!selectedMessage" @click="deleteMessage(selectedMessage.uid)">
            <i class="fas fa-trash"></i>
            Supprimer
          </button>
          <div class="mailbox-move">
            <select v-model="targetFolder" :disabled="!selectedMessage || !moveTargets.length">
              <option value="">Deplacer vers...</option>
              <option v-for="folder in moveTargets" :key="folder.name" :value="folder.name">{{ folder.label }}</option>
            </select>
            <button type="button" class="mailbox-command" :disabled="!selectedMessage || !targetFolder" @click="moveSelectedTo(targetFolder)">
              <i class="fas fa-folder-tree"></i>
            </button>
          </div>
          <button type="button" class="mailbox-command settings" @click="settingsOpen = true">
            <i class="fas fa-cog"></i>
          </button>
        </div>

        <div v-if="messagesError" class="mailbox-alert mailbox-alert-danger mailbox-alert-inline">
          <i class="fas fa-triangle-exclamation"></i>
          {{ messagesError }}
        </div>

        <div class="mailbox-content">
          <section class="mailbox-list-pane">
            <div class="mailbox-list-head">
              <strong>{{ meta.total || 0 }} message(s)</strong>
              <span>{{ meta.unread_count || 0 }} non lu(s)</span>
            </div>

            <div v-if="loadingMessages" class="mailbox-loading mailbox-loading-panel">
              <div class="spinner-border text-primary" role="status"></div>
              <span>Chargement des mails...</span>
            </div>

            <div v-else-if="!messages.length" class="mailbox-empty">
              <i class="fas fa-inbox"></i>
              <h3>Aucun mail trouve</h3>
              <p>{{ search ? 'Essayez une autre recherche.' : 'Ce dossier est vide.' }}</p>
            </div>

            <div v-else class="mailbox-message-list">
              <button
                v-for="message in messages"
                :key="message.uid"
                type="button"
                class="mailbox-message-item"
                :class="{ active: selectedUid === message.uid, unread: message.unread }"
                @click="selectMessage(message)"
              >
                <span class="mailbox-avatar">{{ senderInitial(message.from) }}</span>
                <span class="mailbox-message-content">
                  <span class="mailbox-message-top">
                    <strong>{{ cleanSender(message.from) || 'Expediteur' }}</strong>
                    <time>{{ formatDate(message.date, true) }}</time>
                  </span>
                  <span class="mailbox-subject">{{ message.subject }}</span>
                  <span class="mailbox-message-meta">
                    <i v-if="message.flagged" class="fas fa-star"></i>
                    <i v-if="message.answered" class="fas fa-reply"></i>
                    {{ formatSize(message.size) }}
                  </span>
                </span>
              </button>
            </div>

            <nav v-if="meta.last_page > 1" class="mailbox-pagination" aria-label="Pagination mails">
              <button type="button" :disabled="meta.current_page <= 1" @click="loadMessages(meta.current_page - 1)">
                <i class="fas fa-chevron-left"></i>
              </button>
              <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
              <button type="button" :disabled="meta.current_page >= meta.last_page" @click="loadMessages(meta.current_page + 1)">
                <i class="fas fa-chevron-right"></i>
              </button>
            </nav>
          </section>

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
                  <button type="button" class="mailbox-icon-btn" title="Repondre" @click="replyToSelected">
                    <i class="fas fa-reply"></i>
                  </button>
                  <button type="button" class="mailbox-icon-btn" title="Transferer" @click="forwardSelected">
                    <i class="fas fa-share"></i>
                  </button>
                  <button type="button" class="mailbox-icon-btn" title="Lu / non lu" @click="toggleRead(selectedMessage)">
                    <i class="fas" :class="selectedMessage.unread ? 'fa-envelope-open' : 'fa-envelope'"></i>
                  </button>
                </div>
              </header>

              <div class="mailbox-reader-tools">
                <button type="button" @click="toggleFlag(selectedMessage)">
                  <i class="fas fa-star"></i>
                  {{ selectedMessage.flagged ? 'Retirer l etoile' : 'Marquer avec etoile' }}
                </button>
                <button type="button" :disabled="!archiveFolder" @click="archiveSelected">
                  <i class="fas fa-box-archive"></i>
                  Archiver
                </button>
                <button type="button" class="danger" @click="deleteMessage(selectedMessage.uid)">
                  <i class="fas fa-trash"></i>
                  Supprimer
                </button>
              </div>

              <div class="mailbox-reader-body">{{ selectedMessage.body || 'Aucun contenu lisible.' }}</div>
            </template>

            <div v-else class="mailbox-reader-empty">
              <i class="fas fa-envelope-open-text"></i>
              <h3>Selectionnez un message</h3>
              <p>{{ activeTitle }}</p>
            </div>
          </article>
        </div>
      </main>
    </section>

    <div v-if="composerOpen && !showSettingsPanel" class="mailbox-compose-backdrop" @click.self="composerOpen = false">
      <section class="mailbox-compose-drawer">
        <div class="mailbox-compose-head">
          <div>
            <span class="mailbox-kicker">Depuis {{ settings?.account_email }}</span>
            <h2>Nouveau mail</h2>
          </div>
          <button type="button" class="mailbox-icon-btn" title="Fermer" @click="composerOpen = false">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form class="mailbox-compose-form" @submit.prevent="sendMail">
          <div class="mailbox-compose-recipients">
            <div class="mailbox-recipient-row">
              <span class="mailbox-recipient-label">A</span>
              <div class="mailbox-recipient-box" @click="focusRecipient('to')">
                <span v-for="recipient in composeForm.to" :key="`to-${recipient.email}`" class="mailbox-recipient-chip">
                  {{ recipient.name || recipient.email }}
                  <button type="button" title="Retirer" @click.stop="removeRecipient('to', recipient.email)">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
                <input
                  ref="toInput"
                  v-model.trim="recipientDraft.to"
                  type="text"
                  placeholder="Nom, poste, groupe ou email"
                  @focus="activeRecipientField = 'to'"
                  @input="activeRecipientField = 'to'"
                  @keydown.enter.prevent="commitRecipient('to')"
                  @keydown.tab.prevent="commitRecipient('to')"
                  @keydown.backspace="removeLastRecipient('to', $event)"
                >
              </div>
            </div>

            <div class="mailbox-recipient-row">
              <span class="mailbox-recipient-label">CC</span>
              <div class="mailbox-recipient-box" @click="focusRecipient('cc')">
                <span v-for="recipient in composeForm.cc" :key="`cc-${recipient.email}`" class="mailbox-recipient-chip">
                  {{ recipient.name || recipient.email }}
                  <button type="button" title="Retirer" @click.stop="removeRecipient('cc', recipient.email)">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
                <input
                  ref="ccInput"
                  v-model.trim="recipientDraft.cc"
                  type="text"
                  placeholder="Copie"
                  @focus="activeRecipientField = 'cc'"
                  @input="activeRecipientField = 'cc'"
                  @keydown.enter.prevent="commitRecipient('cc')"
                  @keydown.tab.prevent="commitRecipient('cc')"
                  @keydown.backspace="removeLastRecipient('cc', $event)"
                >
              </div>
            </div>

            <div class="mailbox-recipient-row">
              <span class="mailbox-recipient-label">CCI</span>
              <div class="mailbox-recipient-box" @click="focusRecipient('bcc')">
                <span v-for="recipient in composeForm.bcc" :key="`bcc-${recipient.email}`" class="mailbox-recipient-chip">
                  {{ recipient.name || recipient.email }}
                  <button type="button" title="Retirer" @click.stop="removeRecipient('bcc', recipient.email)">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
                <input
                  ref="bccInput"
                  v-model.trim="recipientDraft.bcc"
                  type="text"
                  placeholder="Copie cachee"
                  @focus="activeRecipientField = 'bcc'"
                  @input="activeRecipientField = 'bcc'"
                  @keydown.enter.prevent="commitRecipient('bcc')"
                  @keydown.tab.prevent="commitRecipient('bcc')"
                  @keydown.backspace="removeLastRecipient('bcc', $event)"
                >
              </div>
            </div>
          </div>

          <div class="mailbox-smart-panel">
            <div class="mailbox-smart-head">
              <strong>Suggestions intelligentes</strong>
              <span>{{ loadingContacts ? 'Chargement...' : `${contactOptions.length} contact(s)` }}</span>
            </div>
            <div class="mailbox-smart-groups">
              <button
                v-for="group in smartGroups"
                :key="group.key"
                type="button"
                :disabled="!group.contacts.length"
                @click="addGroupRecipients(group)"
              >
                <i class="fas" :class="group.icon"></i>
                <span>{{ group.label }}</span>
                <b>{{ group.contacts.length }}</b>
              </button>
            </div>
            <div v-if="filteredContactSuggestions.length" class="mailbox-contact-suggestions">
              <button
                v-for="contact in filteredContactSuggestions"
                :key="`${activeRecipientField}-${contact.email}`"
                type="button"
                @click="addRecipient(activeRecipientField, contact)"
              >
                <span class="mailbox-contact-avatar">{{ contactInitial(contact) }}</span>
                <span>
                  <strong>{{ contact.name }}</strong>
                  <small>{{ contact.email }} - {{ contact.poste || contact.structure || 'Contact' }}</small>
                </span>
              </button>
            </div>
          </div>

          <label>
            <span>Objet</span>
            <input v-model.trim="composeForm.subject" type="text" required maxlength="180">
          </label>
          <label class="mailbox-compose-body">
            <span>Message</span>
            <textarea v-model.trim="composeForm.body" rows="12" required></textarea>
          </label>

          <div v-if="composeError" class="mailbox-alert mailbox-alert-danger">
            <i class="fas fa-triangle-exclamation"></i>
            {{ composeError }}
          </div>

          <div class="mailbox-compose-actions">
            <button type="button" class="mailbox-ghost-light-btn" @click="composerOpen = false">Annuler</button>
            <button type="submit" class="mailbox-primary-btn" :disabled="sendingMail">
              <span v-if="sendingMail" class="spinner-border spinner-border-sm"></span>
              <i v-else class="fas fa-paper-plane"></i>
              Envoyer
            </button>
          </div>
        </form>
      </section>
    </div>
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

const folders = ref([])
const activeFolder = ref('INBOX')
const activeFilter = ref('')
const targetFolder = ref('')

const composerOpen = ref(false)
const sendingMail = ref(false)
const composeError = ref('')
const loadingContacts = ref(false)
const addressBookGroups = ref([])
const activeRecipientField = ref('to')
const toInput = ref(null)
const ccInput = ref(null)
const bccInput = ref(null)

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
  to: [],
  cc: [],
  bcc: [],
  subject: '',
  body: '',
})

const recipientDraft = reactive({
  to: '',
  cc: '',
  bcc: '',
})

const showSettingsPanel = computed(() => !settings.value?.has_credentials || settingsOpen.value)
const accountInitial = computed(() => (settings.value?.account_email?.charAt(0) || '@').toUpperCase())
const inboxFolder = computed(() => folders.value.find(folder => folder.type === 'inbox') || folders.value[0])
const archiveFolder = computed(() => folders.value.find(folder => folder.type === 'archive'))
const trashFolder = computed(() => folders.value.find(folder => folder.type === 'trash'))
const activeFolderMeta = computed(() => folders.value.find(folder => folder.name === activeFolder.value))
const visibleFolders = computed(() => folders.value.length ? folders.value : [{ name: 'INBOX', label: 'Boite de reception', type: 'inbox', unread: 0 }])
const moveTargets = computed(() => folders.value.filter(folder => folder.name !== activeFolder.value))
const isInboxActive = computed(() => activeFilter.value === '' && activeFolder.value === inboxFolder.value?.name)
const activeTitle = computed(() => {
  if (activeFilter.value === 'unread') return 'Messages non lus'
  if (activeFilter.value === 'flagged') return 'Messages avec etoile'
  return activeFolderMeta.value?.label || 'Boite mail'
})
const activeSubtitle = computed(() => activeFilter.value ? 'Vue rapide' : 'Dossier')
const contactOptions = computed(() => {
  const contacts = []
  const seen = new Set()

  for (const group of addressBookGroups.value) {
    for (const agent of group.agents || []) {
      const email = normalizeEmail(agent.email_professionnel || agent.emails?.[0])
      if (!email || seen.has(email)) continue

      seen.add(email)
      contacts.push({
        id: agent.id,
        name: agent.nom_complet || email,
        email,
        poste: agent.poste || group.poste || '',
        structure: agent.structure || '',
        search: normalizeText([
          agent.nom_complet,
          email,
          agent.poste,
          group.poste,
          agent.structure,
        ].filter(Boolean).join(' ')),
      })
    }
  }

  return contacts
})
const smartGroups = computed(() => {
  const contacts = contactOptions.value
  const by = (key, label, icon, predicate) => ({
    key,
    label,
    icon,
    contacts: contacts.filter(predicate),
  })

  const priorityGroups = [
    by('directeurs', 'Directeurs', 'fa-user-tie', contact => contact.search.includes('directeur') || contact.search.includes('directrice')),
    by('sen', 'SEN', 'fa-building-columns', contact => contact.search.includes('sen') || contact.search.includes('national')),
    by('sep', 'SEP', 'fa-location-dot', contact => contact.search.includes('sep') || contact.search.includes('provincial')),
    by('rh', 'Ressources humaines', 'fa-people-group', contact => contact.search.includes('ressource humaine') || contact.search.includes('rh')),
    by('chefs', 'Chefs de section', 'fa-sitemap', contact => contact.search.includes('chef') && contact.search.includes('section')),
    by('assistants', 'Assistants', 'fa-user-check', contact => contact.search.includes('assistant')),
  ]

  const posteGroups = addressBookGroups.value
    .map(group => ({
      key: `poste-${normalizeText(group.poste)}`,
      label: group.poste,
      icon: 'fa-id-badge',
      contacts: contacts.filter(contact => normalizeText(contact.poste) === normalizeText(group.poste)),
    }))
    .filter(group => group.contacts.length > 1)

  const seen = new Set()

  return [...priorityGroups, ...posteGroups]
    .filter(group => group.contacts.length)
    .filter(group => {
      const key = normalizeText(group.label)
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
    .slice(0, 18)
})
const filteredContactSuggestions = computed(() => {
  const field = activeRecipientField.value
  const term = normalizeText(recipientDraft[field] || '')
  const pool = contactOptions.value.filter(contact => !isRecipientSelected(field, contact.email))

  if (!term) {
    return pool.slice(0, 8)
  }

  return pool
    .filter(contact => contact.search.includes(term))
    .slice(0, 10)
})

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
      await loadFolders()
      await loadContacts()
      activeFolder.value = inboxFolder.value?.name || 'INBOX'
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
    await loadFolders()
    await loadContacts()
    activeFolder.value = inboxFolder.value?.name || 'INBOX'
    await loadMessages(1)
  } catch (error) {
    settingsError.value = firstError(error) || 'Connexion a la boite mail impossible.'
  } finally {
    savingSettings.value = false
  }
}

async function loadFolders() {
  try {
    const { data } = await client.get('/mailbox/folders')
    folders.value = data.data || []
  } catch (_) {
    folders.value = [{ name: 'INBOX', label: 'Boite de reception', type: 'inbox', total: 0, unread: 0 }]
  }
}

async function loadContacts() {
  loadingContacts.value = true

  try {
    const { data } = await client.get('/address-book')
    addressBookGroups.value = data.data?.groups || []
  } catch (_) {
    addressBookGroups.value = []
  } finally {
    loadingContacts.value = false
  }
}

function openInbox() {
  activeFolder.value = inboxFolder.value?.name || 'INBOX'
  activeFilter.value = ''
  resetSelection()
  loadMessages(1)
}

function openFolder(folder) {
  activeFolder.value = folder.name
  activeFilter.value = ''
  resetSelection()
  loadMessages(1)
}

function openFilter(filter) {
  activeFolder.value = inboxFolder.value?.name || activeFolder.value || 'INBOX'
  activeFilter.value = filter
  resetSelection()
  loadMessages(1)
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
        folder: activeFolder.value,
        filter: activeFilter.value || undefined,
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
      resetSelection()
    }
  } catch (error) {
    messages.value = []
    resetSelection()
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
    const { data } = await client.get(`/mailbox/messages/${message.uid}`, {
      params: { folder: activeFolder.value },
    })
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
  if (!message) return
  const nextSeen = !!message.unread
  try {
    const { data } = await client.post(`/mailbox/messages/${message.uid}/read`, {
      seen: nextSeen,
      source_folder: activeFolder.value,
    })
    patchMessage(message.uid, data.data)
    if (selectedMessage.value?.uid === message.uid) {
      selectedMessage.value = { ...selectedMessage.value, ...data.data }
    }
    meta.value.unread_count = Math.max((meta.value.unread_count || 0) + (nextSeen ? -1 : 1), 0)
  } catch (error) {
    messagesError.value = firstError(error) || 'Action impossible.'
  }
}

async function toggleFlag(message) {
  if (!message) return
  const nextFlagged = !message.flagged
  try {
    const { data } = await client.post(`/mailbox/messages/${message.uid}/flag`, {
      flagged: nextFlagged,
      source_folder: activeFolder.value,
    })
    patchMessage(message.uid, data.data)
    if (selectedMessage.value?.uid === message.uid) {
      selectedMessage.value = { ...selectedMessage.value, ...data.data }
    }
  } catch (error) {
    messagesError.value = firstError(error) || 'Action impossible.'
  }
}

async function moveSelectedTo(folderName) {
  if (!selectedMessage.value || !folderName) return

  try {
    await client.post(`/mailbox/messages/${selectedMessage.value.uid}/move`, {
      folder: folderName,
      source_folder: activeFolder.value,
    })
    targetFolder.value = ''
    await loadFolders()
    await loadMessages(messages.value.length > 1 ? meta.value.current_page : Math.max(meta.value.current_page - 1, 1))
  } catch (error) {
    messagesError.value = firstError(error) || 'Deplacement impossible.'
  }
}

function archiveSelected() {
  if (archiveFolder.value) {
    moveSelectedTo(archiveFolder.value.name)
  }
}

async function deleteMessage(uid) {
  if (!uid || !window.confirm('Supprimer ce mail ?')) return

  if (trashFolder.value && activeFolder.value !== trashFolder.value.name) {
    await moveSelectedTo(trashFolder.value.name)
    return
  }

  try {
    await client.delete(`/mailbox/messages/${uid}`, {
      params: { folder: activeFolder.value },
    })
    await loadFolders()
    await loadMessages(messages.value.length > 1 ? meta.value.current_page : Math.max(meta.value.current_page - 1, 1))
  } catch (error) {
    messagesError.value = firstError(error) || 'Suppression impossible.'
  }
}

async function sendMail() {
  commitRecipient('to', false)
  commitRecipient('cc', false)
  commitRecipient('bcc', false)

  const recipients = recipientEmails('to')

  if (!recipients.length) {
    composeError.value = 'Ajoutez au moins un destinataire.'
    return
  }

  sendingMail.value = true
  composeError.value = ''

  try {
    await client.post('/mailbox/send', {
      to: recipients,
      cc: recipientEmails('cc'),
      bcc: recipientEmails('bcc'),
      subject: composeForm.subject,
      body: composeForm.body,
    })

    composeForm.to = []
    composeForm.cc = []
    composeForm.bcc = []
    composeForm.subject = ''
    composeForm.body = ''
    composerOpen.value = false
    await loadFolders()
  } catch (error) {
    composeError.value = firstError(error) || 'Envoi impossible.'
  } finally {
    sendingMail.value = false
  }
}

function openComposer() {
  composeError.value = ''
  composerOpen.value = true
  window.setTimeout(() => focusRecipient('to'), 80)
}

function replyToSelected() {
  if (!selectedMessage.value) return
  composeForm.to = []
  composeForm.cc = []
  composeForm.bcc = []
  addRecipient('to', {
    email: extractEmail(selectedMessage.value.from),
    name: cleanSender(selectedMessage.value.from),
  })
  composeForm.subject = selectedMessage.value.subject?.startsWith('Re:')
    ? selectedMessage.value.subject
    : `Re: ${selectedMessage.value.subject || ''}`.trim()
  composeForm.body = `\n\n--- Message original ---\n${selectedMessage.value.body || ''}`
  openComposer()
}

function forwardSelected() {
  if (!selectedMessage.value) return
  composeForm.to = []
  composeForm.cc = []
  composeForm.bcc = []
  composeForm.subject = selectedMessage.value.subject?.startsWith('Tr:')
    ? selectedMessage.value.subject
    : `Tr: ${selectedMessage.value.subject || ''}`.trim()
  composeForm.body = `\n\n--- Message transfere ---\nDe: ${selectedMessage.value.from}\nDate: ${formatDate(selectedMessage.value.date)}\n\n${selectedMessage.value.body || ''}`
  openComposer()
}

function focusRecipient(field) {
  activeRecipientField.value = field
  const refs = { to: toInput, cc: ccInput, bcc: bccInput }
  refs[field]?.value?.focus()
}

function commitRecipient(field, preferSuggestion = true) {
  const draft = recipientDraft[field]
  if (!draft) return

  const tokens = draft
    .split(/[;,\n]+/)
    .map(item => item.trim())
    .filter(Boolean)

  if (!tokens.length) return

  for (const token of tokens) {
    const normalizedToken = normalizeText(token)
    const matchedGroup = smartGroups.value.find(group => normalizeText(group.label) === normalizedToken)

    if (matchedGroup) {
      addGroupRecipients(matchedGroup, field)
      continue
    }

    const suggestion = preferSuggestion
      ? filteredContactSuggestions.value.find(contact => contact.search.includes(normalizedToken))
      : null

    if (suggestion && !isEmail(token)) {
      addRecipient(field, suggestion)
      continue
    }

    if (isEmail(token)) {
      addRecipient(field, { email: token, name: token })
    } else if (preferSuggestion) {
      composeError.value = 'Selectionnez un contact suggere ou saisissez une adresse email valide.'
    }
  }

  recipientDraft[field] = ''
}

function addGroupRecipients(group, field = activeRecipientField.value || 'to') {
  for (const contact of group.contacts) {
    addRecipient(field, contact)
  }
  recipientDraft[field] = ''
  activeRecipientField.value = field
}

function addRecipient(field, contact) {
  const email = normalizeEmail(contact.email)
  if (!email || !isEmail(email)) return

  if (isRecipientSelected(field, email)) {
    recipientDraft[field] = ''
    return
  }

  composeForm[field].push({
    email,
    name: contact.name && contact.name !== email ? contact.name : '',
  })
  recipientDraft[field] = ''
  composeError.value = ''
}

function removeRecipient(field, email) {
  composeForm[field] = composeForm[field].filter(recipient => recipient.email !== email)
}

function removeLastRecipient(field, event) {
  if (recipientDraft[field]) return
  if (event.key !== 'Backspace') return

  composeForm[field].pop()
}

function isRecipientSelected(field, email) {
  const normalizedEmail = normalizeEmail(email)
  return composeForm[field].some(recipient => recipient.email === normalizedEmail)
}

function recipientEmails(field) {
  return composeForm[field].map(recipient => recipient.email)
}

function contactInitial(contact) {
  return (contact.name?.charAt(0) || contact.email?.charAt(0) || '@').toUpperCase()
}

function resetSelection() {
  selectedUid.value = null
  selectedMessage.value = null
  targetFolder.value = ''
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

function normalizeEmail(value) {
  return (value || '').toString().trim().toLowerCase()
}

function isEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizeEmail(value))
}

function normalizeText(value) {
  return (value || '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

function extractEmail(value) {
  if (!value) return ''
  const match = value.match(/<([^>]+)>/)
  return (match?.[1] || value).replace(/"/g, '').trim()
}

function senderInitial(value) {
  const cleaned = cleanSender(value)
  return (cleaned.charAt(0) || '@').toUpperCase()
}

function folderIcon(type) {
  return {
    inbox: 'fa-inbox',
    sent: 'fa-paper-plane',
    drafts: 'fa-file-lines',
    archive: 'fa-box-archive',
    junk: 'fa-ban',
    trash: 'fa-trash',
    folder: 'fa-folder',
  }[type] || 'fa-folder'
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
  min-height: calc(100vh - 88px);
  padding: 14px;
  color: #172033;
  background: #eef6fa;
}

.outlook-shell,
.mailbox-settings {
  max-width: 1440px;
  margin: 0 auto;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 14px 42px rgba(15, 76, 92, 0.14);
}

.outlook-shell {
  display: grid;
  grid-template-columns: 250px minmax(0, 1fr);
  min-height: calc(100vh - 116px);
  overflow: hidden;
}

.mailbox-sidebar {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 14px;
  background: #f4f9fb;
  border-right: 1px solid #dbeaf0;
}

.mailbox-account {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  gap: 10px;
  align-items: center;
  padding: 10px;
  border: 1px solid #d8e9ef;
  border-radius: 8px;
  background: #fff;
}

.mailbox-account-avatar,
.mailbox-avatar {
  display: grid;
  place-items: center;
  border-radius: 8px;
  font-weight: 900;
}

.mailbox-account-avatar {
  width: 44px;
  height: 44px;
  color: #fff;
  background: #05769a;
}

.mailbox-account span,
.mailbox-side-card span,
.mailbox-kicker {
  display: block;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
}

.mailbox-account strong {
  display: block;
  overflow: hidden;
  color: #102a43;
  font-size: 0.86rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-compose-btn,
.mailbox-primary-btn,
.mailbox-command,
.mailbox-icon-btn,
.mailbox-ghost-light-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 38px;
  border-radius: 8px;
  font-weight: 800;
  line-height: 1;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}

.mailbox-compose-btn,
.mailbox-primary-btn,
.mailbox-command.primary {
  border: 0;
  color: #fff;
  background: #04769a;
}

.mailbox-compose-btn {
  width: 100%;
}

.mailbox-command,
.mailbox-ghost-light-btn {
  border: 1px solid #cfe1e8;
  color: #0f5f76;
  background: #fff;
}

.mailbox-icon-btn {
  width: 38px;
  border: 0;
  color: #0f5f76;
  background: #e7f3f7;
}

.mailbox-command:hover,
.mailbox-compose-btn:hover,
.mailbox-primary-btn:hover,
.mailbox-icon-btn:hover {
  transform: translateY(-1px);
}

.mailbox-command:disabled,
.mailbox-primary-btn:disabled,
.mailbox-icon-btn:disabled,
.mailbox-move select:disabled {
  cursor: not-allowed;
  opacity: 0.5;
  transform: none;
}

.mailbox-command.danger {
  color: #b42318;
  border-color: #fecaca;
  background: #fff7f7;
}

.mailbox-command.settings {
  width: 38px;
  padding: 0;
  margin-left: auto;
}

.mailbox-folder-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 0;
  overflow: auto;
}

.mailbox-nav-title {
  margin: 10px 8px 4px;
  color: #6b7b8d;
  font-size: 0.72rem;
  font-weight: 900;
  text-transform: uppercase;
}

.mailbox-folder-btn {
  display: grid;
  grid-template-columns: 22px minmax(0, 1fr) auto;
  gap: 8px;
  align-items: center;
  min-height: 36px;
  padding: 0 9px;
  border: 1px solid transparent;
  border-radius: 8px;
  color: #23364d;
  background: transparent;
  text-align: left;
  text-decoration: none;
}

.mailbox-folder-btn span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-folder-btn:hover,
.mailbox-folder-btn.active {
  color: #075985;
  background: #e7f5fa;
  border-color: #c3e3ee;
}

.mailbox-folder-btn b {
  min-width: 22px;
  padding: 2px 7px;
  border-radius: 999px;
  color: #fff;
  background: #0b83a5;
  font-size: 0.72rem;
  text-align: center;
}

.mailbox-side-card {
  margin-top: auto;
  padding: 12px;
  border: 1px solid #d8e9ef;
  border-radius: 8px;
  background: #fff;
}

.mailbox-side-card strong {
  display: block;
  margin-top: 2px;
  color: #102a43;
}

.mailbox-main {
  display: flex;
  min-width: 0;
  flex-direction: column;
  background: #fff;
}

.mailbox-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border-bottom: 1px solid #e0edf2;
  background: #fff;
}

.mailbox-topbar h1,
.mailbox-settings h1,
.mailbox-compose-head h2 {
  margin: 2px 0 0;
  color: #102a43;
  font-size: 1.22rem;
  font-weight: 900;
}

.mailbox-search {
  position: relative;
  flex: 1;
  max-width: 440px;
}

.mailbox-search i {
  position: absolute;
  top: 50%;
  left: 13px;
  color: #5f7f8c;
  transform: translateY(-50%);
}

.mailbox-search input,
.mailbox-settings-form input,
.mailbox-settings-form select,
.mailbox-compose-form input,
.mailbox-compose-form textarea,
.mailbox-move select {
  width: 100%;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  background: #fff;
  color: #102a43;
  outline: none;
}

.mailbox-search input {
  min-height: 40px;
  padding: 0 14px 0 40px;
}

.mailbox-commandbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  padding: 10px 12px;
  border-bottom: 1px solid #e0edf2;
  background: #f8fbfc;
}

.mailbox-command {
  min-height: 34px;
  padding: 0 11px;
  font-size: 0.84rem;
}

.mailbox-move {
  display: flex;
  gap: 6px;
  align-items: center;
}

.mailbox-move select {
  min-height: 34px;
  min-width: 164px;
  padding: 0 10px;
  font-size: 0.84rem;
  font-weight: 700;
}

.mailbox-content {
  display: grid;
  grid-template-columns: minmax(300px, 390px) minmax(0, 1fr);
  min-height: 0;
  flex: 1;
}

.mailbox-list-pane {
  display: flex;
  min-width: 0;
  flex-direction: column;
  border-right: 1px solid #e0edf2;
  background: #f8fbfc;
}

.mailbox-list-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border-bottom: 1px solid #e0edf2;
  color: #64748b;
  font-size: 0.82rem;
}

.mailbox-list-head strong {
  color: #102a43;
}

.mailbox-message-list {
  min-height: 0;
  overflow: auto;
}

.mailbox-message-item {
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
  gap: 10px;
  width: 100%;
  padding: 13px 12px;
  border: 0;
  border-bottom: 1px solid #e0edf2;
  background: transparent;
  color: #243b53;
  text-align: left;
  cursor: pointer;
}

.mailbox-message-item:hover,
.mailbox-message-item.active {
  background: #e9f7fb;
}

.mailbox-message-item.active {
  box-shadow: inset 3px 0 0 #087da0;
}

.mailbox-message-item.unread .mailbox-subject,
.mailbox-message-item.unread strong {
  color: #062f4f;
  font-weight: 900;
}

.mailbox-avatar {
  width: 42px;
  height: 42px;
  color: #06708f;
  background: #d9f3f6;
}

.mailbox-message-content,
.mailbox-message-top {
  min-width: 0;
}

.mailbox-message-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.mailbox-message-top strong,
.mailbox-subject {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-message-top strong {
  display: block;
  color: #334155;
  font-size: 0.9rem;
}

.mailbox-message-top time,
.mailbox-message-meta {
  color: #64748b;
  font-size: 0.78rem;
}

.mailbox-subject {
  display: block;
  margin-top: 3px;
  color: #475569;
  font-size: 0.88rem;
}

.mailbox-message-meta {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-top: 6px;
}

.mailbox-message-meta .fa-star {
  color: #c28a05;
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

.mailbox-reader-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.mailbox-reader-tools {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 10px 18px;
  border-bottom: 1px solid #e2edf2;
  background: #fbfdfe;
}

.mailbox-reader-tools button {
  display: inline-flex;
  gap: 7px;
  align-items: center;
  min-height: 32px;
  padding: 0 10px;
  border: 1px solid #d6e6ec;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
  font-weight: 800;
}

.mailbox-reader-tools button.danger {
  color: #b42318;
  border-color: #fecaca;
}

.mailbox-reader-body {
  min-height: 420px;
  padding: 20px;
  color: #1f2937;
  font-size: 0.94rem;
  line-height: 1.65;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.mailbox-settings {
  padding: 22px;
}

.mailbox-settings-head,
.mailbox-compose-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 18px;
}

.mailbox-settings p {
  margin: 4px 0 0;
  color: #64748b;
}

.mailbox-settings-form {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.mailbox-settings-form label,
.mailbox-compose-form label {
  display: grid;
  gap: 6px;
  color: #334155;
  font-size: 0.86rem;
  font-weight: 800;
}

.mailbox-settings-form input,
.mailbox-settings-form select,
.mailbox-compose-form input {
  min-height: 42px;
  padding: 0 12px;
}

.mailbox-compose-form textarea {
  min-height: 260px;
  padding: 12px;
  resize: vertical;
}

.mailbox-settings-form input:focus,
.mailbox-settings-form select:focus,
.mailbox-compose-form input:focus,
.mailbox-compose-form textarea:focus,
.mailbox-search input:focus,
.mailbox-move select:focus {
  border-color: #0786b1;
  box-shadow: 0 0 0 3px rgba(7, 134, 177, 0.12);
}

.mailbox-settings-actions,
.mailbox-alert {
  grid-column: 1 / -1;
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
  margin: 10px 12px 0;
}

.mailbox-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px;
  margin-top: auto;
  border-top: 1px solid #e0edf2;
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

.mailbox-loading,
.mailbox-empty,
.mailbox-reader-empty {
  display: grid;
  place-items: center;
  gap: 12px;
  min-height: 240px;
  color: #64748b;
  text-align: center;
}

.mailbox-loading-page {
  min-height: calc(100vh - 140px);
}

.mailbox-loading-panel {
  min-height: 420px;
}

.mailbox-empty i,
.mailbox-reader-empty i {
  color: #9ccbd8;
  font-size: 2.4rem;
}

.mailbox-empty h3,
.mailbox-reader-empty h3 {
  margin: 0;
  color: #334155;
  font-size: 1rem;
  font-weight: 900;
}

.mailbox-empty p,
.mailbox-reader-empty p {
  margin: 0;
}

.mailbox-compose-backdrop {
  position: fixed;
  inset: 0;
  z-index: 2100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  background: rgba(11, 31, 46, 0.42);
  backdrop-filter: blur(6px);
}

.mailbox-compose-drawer {
  display: flex;
  width: min(900px, 100%);
  max-height: calc(100vh - 36px);
  flex-direction: column;
  overflow: hidden;
  padding: 0;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 26px 80px rgba(11, 31, 46, 0.34);
}

.mailbox-compose-head {
  margin: 0;
  padding: 16px 18px;
  color: #fff;
  background: linear-gradient(135deg, #04769a 0%, #168b7f 100%);
}

.mailbox-compose-head .mailbox-kicker,
.mailbox-compose-head h2 {
  color: #fff;
}

.mailbox-compose-head .mailbox-icon-btn {
  color: #fff;
  background: rgba(255, 255, 255, 0.18);
}

.mailbox-compose-form {
  display: grid;
  gap: 14px;
  min-height: 0;
  overflow: auto;
  padding: 16px 18px 18px;
}

.mailbox-compose-recipients {
  display: grid;
  gap: 8px;
  padding: 10px;
  border: 1px solid #d7e8ee;
  border-radius: 8px;
  background: #f8fbfc;
}

.mailbox-recipient-row {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  gap: 8px;
  align-items: start;
}

.mailbox-recipient-label {
  padding-top: 9px;
  color: #0f5f76;
  font-size: 0.78rem;
  font-weight: 900;
  text-align: center;
}

.mailbox-recipient-box {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  min-height: 40px;
  padding: 5px 8px;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  background: #fff;
}

.mailbox-recipient-box:focus-within {
  border-color: #0786b1;
  box-shadow: 0 0 0 3px rgba(7, 134, 177, 0.12);
}

.mailbox-recipient-box input {
  flex: 1;
  min-width: 180px;
  min-height: 28px;
  padding: 0;
  border: 0;
  box-shadow: none;
}

.mailbox-recipient-box input:focus {
  box-shadow: none;
}

.mailbox-recipient-chip {
  display: inline-flex;
  align-items: center;
  max-width: 260px;
  gap: 6px;
  min-height: 28px;
  padding: 0 6px 0 10px;
  border-radius: 999px;
  color: #075985;
  background: #e0f2fe;
  font-size: 0.82rem;
  font-weight: 800;
}

.mailbox-recipient-chip button {
  display: grid;
  place-items: center;
  width: 20px;
  height: 20px;
  border: 0;
  border-radius: 999px;
  color: #075985;
  background: transparent;
}

.mailbox-recipient-chip button:hover {
  background: rgba(7, 89, 133, 0.12);
}

.mailbox-smart-panel {
  display: grid;
  gap: 10px;
  padding: 10px;
  border: 1px solid #d7e8ee;
  border-radius: 8px;
  background: #fbfdfe;
}

.mailbox-smart-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: #64748b;
  font-size: 0.82rem;
}

.mailbox-smart-head strong {
  color: #102a43;
}

.mailbox-smart-groups {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.mailbox-smart-groups button {
  display: inline-grid;
  grid-template-columns: 18px minmax(0, auto) auto;
  gap: 7px;
  align-items: center;
  min-height: 34px;
  padding: 0 10px;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
  font-weight: 800;
}

.mailbox-smart-groups button:disabled {
  opacity: 0.45;
}

.mailbox-smart-groups button b {
  min-width: 22px;
  padding: 2px 7px;
  border-radius: 999px;
  color: #fff;
  background: #0b83a5;
  font-size: 0.72rem;
}

.mailbox-contact-suggestions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.mailbox-contact-suggestions button {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 8px;
  align-items: center;
  min-height: 46px;
  padding: 7px;
  border: 1px solid #e0edf2;
  border-radius: 8px;
  background: #fff;
  color: #172033;
  text-align: left;
}

.mailbox-contact-suggestions button:hover {
  border-color: #8fd3e8;
  background: #f0fbff;
}

.mailbox-contact-avatar {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  color: #fff;
  background: #04769a;
  font-size: 0.82rem;
  font-weight: 900;
}

.mailbox-contact-suggestions strong,
.mailbox-contact-suggestions small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-contact-suggestions strong {
  font-size: 0.84rem;
}

.mailbox-contact-suggestions small {
  color: #64748b;
  font-size: 0.74rem;
}

.mailbox-compose-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.mailbox-ghost-light-btn {
  padding: 0 16px;
}

@media (max-width: 1040px) {
  .outlook-shell {
    grid-template-columns: 220px minmax(0, 1fr);
  }

  .mailbox-content {
    grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
  }
}

@media (max-width: 860px) {
  .mailbox-page {
    padding: 10px;
  }

  .outlook-shell {
    grid-template-columns: 1fr;
  }

  .mailbox-sidebar {
    border-right: 0;
    border-bottom: 1px solid #dbeaf0;
  }

  .mailbox-folder-nav {
    flex-direction: row;
    padding-bottom: 4px;
    overflow-x: auto;
  }

  .mailbox-nav-title,
  .mailbox-side-card {
    display: none;
  }

  .mailbox-folder-btn {
    min-width: 160px;
  }

  .mailbox-topbar,
  .mailbox-reader-head,
  .mailbox-settings-head,
  .mailbox-compose-head {
    align-items: stretch;
    flex-direction: column;
  }

  .mailbox-search {
    max-width: none;
  }

  .mailbox-content {
    grid-template-columns: 1fr;
  }

  .mailbox-list-pane {
    max-height: 480px;
    border-right: 0;
    border-bottom: 1px solid #e0edf2;
  }

  .mailbox-settings-form {
    grid-template-columns: 1fr;
  }

  .mailbox-compose-backdrop {
    padding: 10px;
  }

  .mailbox-compose-drawer {
    max-height: calc(100vh - 20px);
    overflow: auto;
  }

  .mailbox-contact-suggestions {
    grid-template-columns: 1fr;
  }

  .mailbox-recipient-row {
    grid-template-columns: 36px minmax(0, 1fr);
  }
}
</style>
