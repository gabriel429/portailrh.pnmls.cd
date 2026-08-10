<template>
  <div class="support-page">
    <header class="support-header">
      <div>
        <span class="support-kicker">{{ isTechnician ? 'Section Nouvelle Technologie' : 'Assistance technique' }}</span>
        <h1>{{ isTechnician ? 'Support technique' : 'Mes demandes techniques' }}</h1>
        <p>{{ isTechnician ? 'Traitez les incidents et échangez avec les agents.' : 'Signalez un problème et suivez la réponse des techniciens.' }}</p>
      </div>
      <button class="primary-action" type="button" @click="openCreate">
        <i class="fas fa-plus"></i><span>Nouveau problème</span>
      </button>
    </header>

    <section v-if="isTechnician" class="support-stats" aria-label="Tableau de bord du support">
      <button v-for="stat in stats" :key="stat.key" type="button" :class="['stat-card', { active: filters.status === stat.filter }]" @click="setStatusFilter(stat.filter)">
        <span class="stat-icon" :style="{ color: stat.color, background: stat.bg }"><i class="fas" :class="stat.icon"></i></span>
        <span><strong>{{ stat.value }}</strong><small>{{ stat.label }}</small></span>
      </button>
    </section>

    <section class="support-workspace">
      <aside class="ticket-pane" :class="{ hiddenMobile: selectedTicket && mobileDetail }">
        <div class="ticket-tools">
          <div class="ticket-search"><i class="fas fa-search"></i><input v-model.trim="filters.search" type="search" placeholder="Rechercher" @keyup.enter="loadTickets"></div>
          <select v-model="filters.status" aria-label="Filtrer par statut" @change="loadTickets">
            <option value="">Tous les statuts</option><option value="nouveau">Nouveau</option><option value="en_cours">En cours</option><option value="resolu">Résolu</option><option value="ferme">Fermé</option>
          </select>
          <select v-model="filters.priority" aria-label="Filtrer par priorité" @change="loadTickets">
            <option value="">Toutes priorités</option><option value="urgent">Urgent</option><option value="normal">Normal</option><option value="faible">Faible</option>
          </select>
        </div>

        <div v-if="loadingList" class="pane-state"><span class="spinner-border spinner-border-sm"></span> Chargement…</div>
        <div v-else-if="!tickets.length" class="pane-state empty"><i class="fas fa-inbox"></i><strong>Aucune demande</strong><span>Créez un problème ou modifiez les filtres.</span></div>
        <div v-else class="ticket-list">
          <button v-for="ticket in tickets" :key="ticket.id" type="button" class="ticket-item" :class="{ active: selectedTicket?.id === ticket.id }" @click="openTicket(ticket.id)">
            <span class="ticket-item-top"><span class="status-dot" :class="ticket.status"></span><strong>#{{ ticket.id }} · {{ ticket.subject }}</strong><time>{{ formatShortDate(ticket.updated_at) }}</time></span>
            <span class="ticket-item-meta"><span>{{ ticket.module }}</span><span class="priority" :class="ticket.priority">{{ priorityLabel(ticket.priority) }}</span><span><i class="far fa-comment"></i> {{ ticket.messages_count }}</span></span>
            <small v-if="isTechnician">{{ ticket.requester?.name }}</small>
          </button>
        </div>
      </aside>

      <main class="conversation-pane" :class="{ visibleMobile: selectedTicket && mobileDetail }">
        <div v-if="loadingTicket" class="pane-state"><span class="spinner-border"></span> Ouverture de la demande…</div>
        <div v-else-if="!selectedTicket" class="conversation-empty">
          <span><i class="fas fa-headset"></i></span><h2>Sélectionnez une demande</h2><p>Le problème, les réponses et l’historique apparaîtront ici.</p>
        </div>
        <template v-else>
          <div class="conversation-head">
            <button class="mobile-back" type="button" aria-label="Retour à la liste" @click="closeMobileDetail"><i class="fas fa-arrow-left"></i></button>
            <div class="conversation-title"><span>#{{ selectedTicket.id }} · {{ selectedTicket.module }}</span><h2>{{ selectedTicket.subject }}</h2><small>Signalé par {{ selectedTicket.requester?.name }} · {{ formatDate(selectedTicket.created_at) }}</small></div>
            <div class="conversation-controls">
              <span class="status-pill" :class="selectedTicket.status">{{ statusLabel(selectedTicket.status) }}</span>
              <select v-if="isTechnician" v-model="selectedTicket.status" aria-label="Modifier le statut" :disabled="statusUpdating" @change="changeStatus">
                <option value="nouveau">Nouveau</option><option value="en_cours">En cours</option><option value="resolu">Résolu</option><option value="ferme">Fermé</option>
              </select>
            </div>
          </div>

          <div class="conversation-scroll">
            <article class="issue-card">
              <div class="issue-meta"><span class="priority" :class="selectedTicket.priority"><i class="fas fa-flag"></i> {{ priorityLabel(selectedTicket.priority) }}</span><span>{{ selectedTicket.module }}</span></div>
              <p>{{ selectedTicket.description }}</p>
              <a v-if="selectedTicket.attachment" class="attachment-link" :href="selectedTicket.attachment.url" target="_blank"><i class="fas fa-paperclip"></i><span>{{ selectedTicket.attachment.name }}</span><small>{{ fileSize(selectedTicket.attachment.size) }}</small></a>
            </article>

            <div class="thread-separator"><span>Historique des échanges</span></div>
            <div v-if="!selectedTicket.messages?.length" class="no-messages">Aucune réponse pour le moment.</div>
            <template v-for="message in selectedTicket.messages" :key="message.id">
              <div v-if="message.type === 'status_change'" class="status-event"><i class="fas fa-arrows-rotate"></i><span>{{ message.author.name }} a changé le statut de <strong>{{ statusLabel(message.status_from) }}</strong> à <strong>{{ statusLabel(message.status_to) }}</strong></span><time>{{ formatDate(message.created_at) }}</time></div>
              <article v-else class="message-row" :class="{ technician: message.author.is_technician }">
                <div class="message-avatar"><i class="fas" :class="message.author.is_technician ? 'fa-screwdriver-wrench' : 'fa-user'"></i></div>
                <div class="message-bubble"><header><strong>{{ message.author.name }}</strong><span v-if="message.author.is_technician">Technicien</span><time>{{ formatDate(message.created_at) }}</time></header><p v-if="message.body">{{ message.body }}</p><a v-if="message.attachment" class="attachment-link" :href="message.attachment.url" target="_blank"><i class="fas fa-paperclip"></i><span>{{ message.attachment.name }}</span><small>{{ fileSize(message.attachment.size) }}</small></a></div>
              </article>
            </template>
          </div>

          <form class="reply-box" @submit.prevent="sendReply">
            <textarea v-model.trim="reply.body" rows="2" placeholder="Écrivez votre réponse…" :disabled="replying"></textarea>
            <div class="reply-actions">
              <label class="file-button" title="Joindre un fichier"><i class="fas fa-paperclip"></i><input type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" @change="onReplyFile"></label>
              <span v-if="reply.file" class="selected-file">{{ reply.file.name }} <button type="button" @click="reply.file = null"><i class="fas fa-times"></i></button></span>
              <button class="send-button" type="submit" :disabled="replying || (!reply.body && !reply.file)"><i class="fas fa-paper-plane"></i><span>Envoyer</span></button>
            </div>
          </form>
        </template>
      </main>
    </section>

    <Teleport to="body">
      <div v-if="createOpen" class="support-modal" @click.self="closeCreate">
        <form class="support-modal-panel" @submit.prevent="submitTicket">
          <header><div><span class="support-kicker">Chat – Nouvelle Technologie</span><h2>Nouveau problème</h2></div><button type="button" aria-label="Fermer" @click="closeCreate"><i class="fas fa-times"></i></button></header>
          <div class="form-grid">
            <label class="full"><span>Objet du problème</span><input v-model.trim="form.subject" type="text" maxlength="180" required placeholder="Ex. Impossible d’enregistrer mon pointage"><small v-if="errors.subject">{{ errors.subject[0] }}</small></label>
            <label><span>Module concerné</span><select v-model="form.module" required><option value="" disabled>Sélectionner</option><option v-for="module in modules" :key="module" :value="module">{{ module }}</option></select><small v-if="errors.module">{{ errors.module[0] }}</small></label>
            <fieldset><legend>Niveau d’urgence</legend><div class="priority-options"><label v-for="priority in priorities" :key="priority.value" :class="[priority.value, { checked: form.priority === priority.value }]"><input v-model="form.priority" type="radio" :value="priority.value"><span>{{ priority.label }}</span></label></div></fieldset>
            <label class="full"><span>Description détaillée</span><textarea v-model.trim="form.description" rows="6" maxlength="10000" required placeholder="Décrivez les étapes, le message affiché et le résultat attendu."></textarea><small v-if="errors.description">{{ errors.description[0] }}</small></label>
            <label class="full upload-zone"><input type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" @change="onTicketFile"><i class="fas fa-cloud-arrow-up"></i><span><strong>{{ form.file ? form.file.name : 'Capture d’écran ou pièce jointe' }}</strong><small>{{ form.file ? fileSize(form.file.size) : 'Facultatif · 10 Mo maximum' }}</small></span></label>
          </div>
          <footer><button type="button" class="secondary-action" @click="closeCreate">Annuler</button><button type="submit" class="primary-action" :disabled="submitting"><span v-if="submitting" class="spinner-border spinner-border-sm"></span><i v-else class="fas fa-paper-plane"></i><span>Envoyer</span></button></footer>
        </form>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { createTechnicalTicket, getTechnicalSupportDashboard, getTechnicalTicket, getTechnicalTickets, replyToTechnicalTicket, updateTechnicalTicketStatus } from '@/api/technicalSupport'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()
const tickets = ref([])
const selectedTicket = ref(null)
const isTechnician = ref(false)
const dashboard = ref({})
const loadingList = ref(true)
const loadingTicket = ref(false)
const createOpen = ref(false)
const submitting = ref(false)
const replying = ref(false)
const statusUpdating = ref(false)
const mobileDetail = ref(false)
const errors = ref({})
const filters = reactive({ status: route.query.status || '', priority: route.query.priority || '', search: '' })
const form = reactive({ subject: '', description: '', module: '', priority: 'normal', file: null })
const reply = reactive({ body: '', file: null })
const modules = ['Pointage', 'Tâches', 'Congés', 'Profil', 'Demandes', 'Documents', 'Messagerie', 'PTA', 'Notifications', 'Autre']
const priorities = [{ value: 'faible', label: 'Faible' }, { value: 'normal', label: 'Normal' }, { value: 'urgent', label: 'Urgent' }]
const stats = computed(() => [
  { key: 'nouveau', filter: 'nouveau', label: 'Nouvelles demandes', value: dashboard.value.nouveau || 0, icon: 'fa-circle-exclamation', color: '#b91c1c', bg: '#fee2e2' },
  { key: 'en_cours', filter: 'en_cours', label: 'En cours', value: dashboard.value.en_cours || 0, icon: 'fa-clock', color: '#b45309', bg: '#fef3c7' },
  { key: 'resolu', filter: 'resolu', label: 'Résolues', value: dashboard.value.resolu || 0, icon: 'fa-circle-check', color: '#047857', bg: '#d1fae5' },
  { key: 'urgent', filter: '', label: 'Urgentes actives', value: dashboard.value.urgent || 0, icon: 'fa-bolt', color: '#9f1239', bg: '#ffe4e6' },
])

onMounted(async () => {
  await loadTickets()
  if (route.params.id) await openTicket(route.params.id)
  if (route.query.new === '1') openCreate()
})

watch(() => route.params.id, async id => {
  if (id && Number(id) !== selectedTicket.value?.id) await openTicket(id)
  if (!id) selectedTicket.value = null
})

watch(() => route.query.status, status => {
  filters.status = status || ''
  filters.priority = ''
  loadTickets()
})

async function loadTickets() {
  loadingList.value = true
  try {
    const response = await getTechnicalTickets({ status: filters.status || undefined, priority: filters.priority || undefined, search: filters.search || undefined })
    tickets.value = response.data.data?.data || []
    isTechnician.value = !!response.data.is_technician
    if (isTechnician.value) await loadDashboard()
  } catch (error) { ui.addToast(error.response?.data?.message || 'Impossible de charger les demandes.', 'danger') }
  finally { loadingList.value = false }
}

async function loadDashboard() { try { const response = await getTechnicalSupportDashboard(); dashboard.value = response.data.data || {} } catch (_) { dashboard.value = {} } }
async function openTicket(id) { loadingTicket.value = true; mobileDetail.value = true; try { const response = await getTechnicalTicket(id); selectedTicket.value = response.data.data; isTechnician.value = !!response.data.is_technician } catch (error) { ui.addToast(error.response?.data?.message || 'Demande inaccessible.', 'danger'); mobileDetail.value = false } finally { loadingTicket.value = false } }
function closeMobileDetail() { mobileDetail.value = false; selectedTicket.value = null; if (route.params.id) router.replace({ name: 'technical-support' }) }
function openCreate() { errors.value = {}; createOpen.value = true }
function closeCreate() { createOpen.value = false; router.replace({ query: { ...route.query, new: undefined } }) }
function resetForm() { Object.assign(form, { subject: '', description: '', module: '', priority: 'normal', file: null }); errors.value = {} }
function onTicketFile(event) { form.file = event.target.files?.[0] || null }
function onReplyFile(event) { reply.file = event.target.files?.[0] || null }

async function submitTicket() {
  submitting.value = true; errors.value = {}
  const payload = new FormData(); payload.append('subject', form.subject); payload.append('description', form.description); payload.append('module', form.module); payload.append('priority', form.priority); if (form.file) payload.append('attachment', form.file)
  try { const response = await createTechnicalTicket(payload); ui.addToast(response.data.message, 'success', 8000); closeCreate(); resetForm(); await loadTickets(); await openTicket(response.data.data.id) }
  catch (error) { errors.value = error.response?.data?.errors || {}; ui.addToast(error.response?.data?.message || 'Envoi impossible.', 'danger') }
  finally { submitting.value = false }
}

async function sendReply() {
  if (!reply.body && !reply.file) return
  replying.value = true; const payload = new FormData(); if (reply.body) payload.append('body', reply.body); if (reply.file) payload.append('attachment', reply.file)
  try { await replyToTechnicalTicket(selectedTicket.value.id, payload); reply.body = ''; reply.file = null; await openTicket(selectedTicket.value.id); await loadTickets() }
  catch (error) { ui.addToast(error.response?.data?.message || 'Réponse impossible.', 'danger') }
  finally { replying.value = false }
}

async function changeStatus(event) {
  const status = event.target.value; statusUpdating.value = true
  try { await updateTechnicalTicketStatus(selectedTicket.value.id, status); ui.addToast('Statut mis à jour.', 'success'); await openTicket(selectedTicket.value.id); await loadTickets() }
  catch (error) { ui.addToast(error.response?.data?.message || 'Mise à jour impossible.', 'danger'); await openTicket(selectedTicket.value.id) }
  finally { statusUpdating.value = false }
}

function setStatusFilter(status) { filters.status = status; filters.priority = ''; loadTickets() }
function statusLabel(value) { return { nouveau: 'Nouveau', en_cours: 'En cours', resolu: 'Résolu', ferme: 'Fermé' }[value] || value }
function priorityLabel(value) { return { faible: 'Faible', normal: 'Normal', urgent: 'Urgent' }[value] || value }
function formatShortDate(value) { return value ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short' }).format(new Date(value)) : '' }
function formatDate(value) { return value ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '' }
function fileSize(bytes) { if (!bytes) return ''; return bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} Mo` : `${Math.ceil(bytes / 1024)} Ko` }
</script>

<style scoped>
.support-page { width: min(1440px, 100%); margin: 0 auto; padding: 22px 16px 50px; color: #172033; }
.support-header { display: flex; justify-content: space-between; align-items: center; gap: 24px; padding: 24px 28px; color: #fff; background: #073f5e; border-left: 6px solid #12a594; border-radius: 6px; }
.support-kicker { display: block; color: #72ddd0; font-size: .72rem; font-weight: 900; text-transform: uppercase; }
.support-header h1 { margin: 3px 0 4px; font: 700 2rem Georgia, 'Times New Roman', serif; }
.support-header p { margin: 0; color: #cce3ec; }
.primary-action, .secondary-action { min-height: 42px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; gap: 9px; border: 0; border-radius: 6px; font-weight: 800; }
.primary-action { background: #0d9c8c; color: #fff; }
.primary-action:hover { background: #087d72; }
.primary-action:disabled { opacity: .55; }
.secondary-action { color: #334155; background: #e8eef1; }
.support-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 14px 0; }
.stat-card { min-width: 0; display: flex; align-items: center; gap: 12px; padding: 13px; text-align: left; color: inherit; background: #fff; border: 1px solid #dbe4e9; border-radius: 6px; }
.stat-card.active { border-color: #0d9c8c; box-shadow: 0 0 0 2px rgba(13, 156, 140, .12); }
.stat-icon { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; border-radius: 6px; }
.stat-card strong, .stat-card small { display: block; }.stat-card strong { font-size: 1.35rem; line-height: 1; }.stat-card small { margin-top: 5px; color: #607485; }
.support-workspace { height: min(720px, calc(100vh - 205px)); min-height: 560px; display: grid; grid-template-columns: minmax(300px, 390px) minmax(0, 1fr); margin-top: 14px; border: 1px solid #d5e0e6; border-radius: 6px; overflow: hidden; background: #fff; box-shadow: 0 10px 30px rgba(15, 50, 68, .07); }
.ticket-pane { min-width: 0; display: flex; flex-direction: column; border-right: 1px solid #dce5e9; background: #f7fafb; overflow: hidden; }
.ticket-tools { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 12px; border-bottom: 1px solid #dce5e9; }
.ticket-search { grid-column: 1 / -1; display: flex; align-items: center; gap: 8px; padding: 0 11px; background: #fff; border: 1px solid #cbd8df; border-radius: 5px; }
.ticket-search input { width: 100%; height: 38px; border: 0; outline: 0; background: transparent; }
.ticket-tools select, .conversation-controls select { height: 38px; min-width: 0; padding: 0 8px; border: 1px solid #cbd8df; border-radius: 5px; background: #fff; }
.ticket-list { overflow: auto; }.ticket-item { width: 100%; display: block; padding: 14px; text-align: left; color: inherit; background: transparent; border: 0; border-bottom: 1px solid #e1e8ec; }.ticket-item:hover { background: #eef7f6; }.ticket-item.active { background: #e1f4f1; box-shadow: inset 4px 0 #0d9c8c; }
.ticket-item-top { display: grid; grid-template-columns: 8px minmax(0, 1fr) auto; align-items: start; gap: 8px; }.ticket-item-top strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .88rem; }.ticket-item time, .ticket-item small { color: #718392; font-size: .72rem; }.ticket-item > small { display: block; margin: 7px 0 0 16px; }
.status-dot { width: 8px; height: 8px; margin-top: 5px; border-radius: 50%; }.status-dot.nouveau { background: #dc2626; }.status-dot.en_cours { background: #d97706; }.status-dot.resolu { background: #059669; }.status-dot.ferme { background: #64748b; }
.ticket-item-meta { display: flex; align-items: center; gap: 8px; margin: 8px 0 0 16px; color: #5e7180; font-size: .74rem; }.ticket-item-meta span:last-child { margin-left: auto; }
.priority { display: inline-flex; align-items: center; gap: 5px; padding: 3px 7px; border-radius: 4px; font-size: .7rem; font-weight: 900; text-transform: uppercase; }.priority.urgent { color: #a30f31; background: #ffe4e6; }.priority.normal { color: #9a5800; background: #fef3c7; }.priority.faible { color: #276472; background: #dff3f5; }
.conversation-pane { min-width: 0; display: flex; flex-direction: column; overflow: hidden; }.conversation-head { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border-bottom: 1px solid #dce5e9; }.conversation-title { min-width: 0; flex: 1; }.conversation-title > span { color: #087d72; font-size: .72rem; font-weight: 900; text-transform: uppercase; }.conversation-title h2 { margin: 2px 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 1.08rem; }.conversation-title small { color: #718392; }.conversation-controls { display: flex; align-items: center; gap: 8px; }.status-pill { padding: 5px 9px; border-radius: 999px; font-size: .72rem; font-weight: 900; }.status-pill.nouveau { color: #b91c1c; background: #fee2e2; }.status-pill.en_cours { color: #a85b00; background: #fef3c7; }.status-pill.resolu { color: #047857; background: #d1fae5; }.status-pill.ferme { color: #475569; background: #e2e8f0; }
.conversation-scroll { flex: 1; padding: 18px; overflow: auto; background: #f8fafb; }.issue-card { padding: 16px; background: #fff; border: 1px solid #d9e3e8; border-left: 4px solid #0d9c8c; border-radius: 5px; }.issue-card p, .message-bubble p { margin: 12px 0 0; white-space: pre-wrap; line-height: 1.6; }.issue-meta { display: flex; align-items: center; gap: 8px; color: #627484; font-size: .76rem; }.thread-separator { display: flex; align-items: center; margin: 22px 0 14px; color: #718392; font-size: .72rem; font-weight: 900; text-transform: uppercase; }.thread-separator::before, .thread-separator::after { content: ''; height: 1px; flex: 1; background: #dbe4e8; }.thread-separator span { padding: 0 10px; }
.message-row { display: flex; gap: 10px; margin: 13px 0; }.message-row.technician { flex-direction: row-reverse; }.message-avatar { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; color: #526979; background: #e6edf1; border-radius: 50%; }.message-row.technician .message-avatar { color: #087d72; background: #d6f2ed; }.message-bubble { max-width: min(78%, 700px); padding: 11px 13px; background: #fff; border: 1px solid #dae3e8; border-radius: 6px; }.message-row.technician .message-bubble { background: #e9f7f4; border-color: #b9e1da; }.message-bubble header { display: flex; align-items: center; gap: 7px; font-size: .77rem; }.message-bubble header span { padding: 2px 5px; color: #087d72; background: #ccece6; border-radius: 3px; font-size: .63rem; font-weight: 900; text-transform: uppercase; }.message-bubble time { margin-left: auto; color: #82919c; font-size: .68rem; }.status-event { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 14px auto; color: #687b89; font-size: .72rem; }.status-event time { color: #95a2ab; }.no-messages, .pane-state, .conversation-empty { color: #718392; text-align: center; }.pane-state { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 15px; }.pane-state.empty, .conversation-empty { height: 100%; flex-direction: column; }.pane-state.empty i, .conversation-empty span { font-size: 2rem; color: #98aaaF; }.pane-state.empty strong, .pane-state.empty span { display: block; margin-top: 5px; }.conversation-empty { display: flex; align-items: center; justify-content: center; }.conversation-empty h2 { margin: 14px 0 4px; font: 700 1.4rem Georgia, serif; }.conversation-empty p { margin: 0; }
.attachment-link { width: fit-content; max-width: 100%; display: flex; align-items: center; gap: 8px; margin-top: 12px; padding: 8px 10px; color: #075e78; background: #eef7fa; border: 1px solid #d2e8ef; border-radius: 5px; text-decoration: none; }.attachment-link span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }.attachment-link small { color: #718392; }.reply-box { padding: 12px; border-top: 1px solid #dce5e9; background: #fff; }.reply-box textarea { width: 100%; resize: none; padding: 10px; border: 1px solid #cbd8df; border-radius: 5px; outline-color: #0d9c8c; }.reply-actions { min-height: 38px; display: flex; align-items: center; gap: 9px; margin-top: 7px; }.file-button { width: 38px; height: 38px; display: grid; place-items: center; color: #376174; background: #e9f0f3; border-radius: 5px; cursor: pointer; }.file-button input { display: none; }.selected-file { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #607485; font-size: .75rem; }.selected-file button { border: 0; background: none; }.send-button { min-height: 38px; margin-left: auto; padding: 0 13px; display: inline-flex; align-items: center; gap: 7px; color: #fff; background: #087d72; border: 0; border-radius: 5px; font-weight: 800; }.send-button:disabled { opacity: .5; }.mobile-back { display: none; }
.support-modal { position: fixed; inset: 0; z-index: 1060; display: grid; place-items: center; padding: 18px; background: rgba(5, 28, 40, .68); }.support-modal-panel { width: min(720px, 100%); max-height: calc(100vh - 36px); overflow: auto; background: #fff; border-radius: 8px; box-shadow: 0 24px 70px rgba(0, 0, 0, .3); }.support-modal-panel > header { display: flex; justify-content: space-between; align-items: start; padding: 20px 24px; color: #fff; background: #073f5e; border-bottom: 4px solid #12a594; }.support-modal-panel h2 { margin: 2px 0 0; font: 700 1.5rem Georgia, serif; }.support-modal-panel header button { width: 36px; height: 36px; color: #fff; background: rgba(255, 255, 255, .14); border: 0; border-radius: 5px; }.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 22px 24px; }.form-grid .full { grid-column: 1 / -1; }.form-grid label > span, .form-grid legend { display: block; margin-bottom: 6px; color: #34495a; font-size: .78rem; font-weight: 900; }.form-grid input[type=text], .form-grid select, .form-grid textarea { width: 100%; padding: 10px 11px; border: 1px solid #cbd8df; border-radius: 5px; outline-color: #0d9c8c; }.form-grid textarea { resize: vertical; }.form-grid label > small { display: block; margin-top: 5px; color: #b91c1c; }.form-grid fieldset { min-width: 0; margin: 0; padding: 0; border: 0; }.priority-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; }.priority-options label { min-width: 0; padding: 10px 5px; text-align: center; border: 1px solid #d1dce2; border-radius: 5px; cursor: pointer; }.priority-options input { position: absolute; opacity: 0; }.priority-options label.checked.faible { color: #276472; background: #dff3f5; border-color: #7cc6cf; }.priority-options label.checked.normal { color: #915300; background: #fef3c7; border-color: #eabb62; }.priority-options label.checked.urgent { color: #a30f31; background: #ffe4e6; border-color: #ec8ca0; }.priority-options label span { margin: 0; }.upload-zone { min-height: 78px; display: flex; align-items: center; gap: 14px; padding: 14px; border: 1px dashed #8ca9b7; border-radius: 5px; background: #f6fafb; cursor: pointer; }.upload-zone input { display: none; }.upload-zone > i { font-size: 1.5rem; color: #0d8c80; }.upload-zone strong, .upload-zone small { display: block; }.upload-zone small { margin-top: 3px; color: #748593; }.support-modal-panel > footer { display: flex; justify-content: flex-end; gap: 9px; padding: 15px 24px; border-top: 1px solid #e0e7eb; }
@media (max-width: 820px) { .support-page { padding: 10px 8px 30px; }.support-header { align-items: flex-start; padding: 20px; }.support-header p { display: none; }.support-header h1 { font-size: 1.45rem; }.support-stats { grid-template-columns: 1fr 1fr; }.support-workspace { height: calc(100vh - 190px); min-height: 520px; grid-template-columns: 1fr; }.ticket-pane { border-right: 0; }.ticket-pane.hiddenMobile { display: none; }.conversation-pane { display: none; }.conversation-pane.visibleMobile { display: flex; }.mobile-back { width: 36px; height: 36px; flex: 0 0 36px; display: grid; place-items: center; color: #315568; background: #e7eff2; border: 0; border-radius: 5px; }.conversation-controls .status-pill { display: none; }.message-bubble { max-width: 88%; }.form-grid { grid-template-columns: 1fr; }.form-grid .full { grid-column: auto; } }
@media (max-width: 520px) { .support-header { flex-direction: column; }.support-header .primary-action { width: 100%; }.support-stats { gap: 6px; }.stat-card { padding: 9px; }.stat-icon { width: 34px; height: 34px; flex-basis: 34px; }.stat-card small { font-size: .65rem; }.support-workspace { height: calc(100vh - 225px); }.conversation-head { padding: 10px; }.conversation-title small { display: none; }.conversation-controls select { width: 105px; }.conversation-scroll { padding: 10px; }.reply-box { padding: 8px; }.send-button span { display: none; }.support-modal { padding: 0; }.support-modal-panel { width: 100%; max-height: 100vh; min-height: 100vh; border-radius: 0; }.form-grid { padding: 18px 14px; }.priority-options { grid-template-columns: 1fr; }.support-modal-panel > footer { position: sticky; bottom: 0; background: #fff; } }

/* Modern application finish */
.support-page {
  --support-accent: #0b8f82;
  --support-accent-strong: #067267;
  --support-ink: #142332;
  --support-muted: #687b8c;
  --support-line: #dce6eb;
  --support-surface: #ffffff;
  --support-soft: #f5f8fa;
  --support-shadow: 0 18px 45px rgba(19, 55, 72, .09);
  padding-top: 28px;
  color: var(--support-ink);
}
.support-header {
  min-height: 152px;
  padding: 30px 34px;
  background:
    linear-gradient(112deg, rgba(255, 255, 255, .04) 0 42%, transparent 42%),
    linear-gradient(135deg, #073b59 0%, #07506a 58%, #08796f 100%);
  border: 1px solid rgba(255, 255, 255, .12);
  border-left: 0;
  border-radius: 8px;
  box-shadow: 0 18px 42px rgba(4, 48, 69, .2);
  overflow: hidden;
}
.support-kicker { margin-bottom: 5px; color: #9aeee4; font-size: .7rem; letter-spacing: 0; }
.support-header h1 { margin: 0 0 7px; font-family: inherit; font-size: 2rem; font-weight: 850; }
.support-header p { color: rgba(235, 249, 252, .82); font-size: .92rem; }
.primary-action, .secondary-action, .send-button, .file-button, .mobile-back {
  transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease;
}
.primary-action {
  min-height: 46px;
  padding: 0 19px;
  background: #19a997;
  border: 1px solid rgba(255, 255, 255, .2);
  border-radius: 7px;
  box-shadow: 0 9px 20px rgba(0, 39, 42, .2);
}
.primary-action:hover:not(:disabled) { background: #119584; transform: translateY(-2px); box-shadow: 0 12px 24px rgba(0, 39, 42, .28); }
.secondary-action { border: 1px solid #d9e3e8; background: #f3f6f8; }
.support-stats { gap: 12px; margin: 16px 0; }
.stat-card {
  min-height: 76px;
  padding: 15px 16px;
  border-color: var(--support-line);
  border-radius: 8px;
  box-shadow: 0 6px 18px rgba(23, 56, 71, .045);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.stat-card:hover { transform: translateY(-2px); border-color: #b9d5d3; box-shadow: 0 12px 26px rgba(23, 56, 71, .09); }
.stat-card.active { border-color: var(--support-accent); box-shadow: 0 0 0 3px rgba(11, 143, 130, .12), 0 12px 24px rgba(23, 56, 71, .08); }
.stat-icon { width: 46px; height: 46px; flex-basis: 46px; border-radius: 8px; font-size: 1.05rem; }
.stat-card strong { font-size: 1.55rem; font-weight: 850; }
.stat-card small { color: var(--support-muted); font-weight: 650; }
.support-workspace {
  height: min(740px, calc(100dvh - 220px));
  border-color: var(--support-line);
  border-radius: 8px;
  box-shadow: var(--support-shadow);
}
.ticket-pane { background: #f8fafb; border-color: var(--support-line); }
.ticket-tools { gap: 9px; padding: 14px; background: rgba(255, 255, 255, .74); border-color: var(--support-line); }
.ticket-search, .ticket-tools select, .conversation-controls select {
  border-color: #d4e0e6;
  border-radius: 7px;
  box-shadow: 0 1px 2px rgba(21, 50, 63, .025);
  transition: border-color .18s ease, box-shadow .18s ease;
}
.ticket-search:focus-within, .ticket-tools select:focus, .conversation-controls select:focus {
  border-color: var(--support-accent);
  box-shadow: 0 0 0 3px rgba(11, 143, 130, .12);
  outline: none;
}
.ticket-item {
  margin: 7px 8px 0;
  width: calc(100% - 16px);
  padding: 15px 14px;
  background: rgba(255, 255, 255, .82);
  border: 1px solid transparent;
  border-radius: 7px;
  transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.ticket-item:last-child { margin-bottom: 8px; }
.ticket-item:hover { background: #fff; border-color: #d6e6e7; transform: translateX(2px); box-shadow: 0 7px 16px rgba(20, 61, 75, .06); }
.ticket-item.active { background: #e8f6f3; border-color: #a8d9d2; box-shadow: inset 3px 0 var(--support-accent), 0 7px 18px rgba(11, 111, 101, .08); }
.ticket-item-top strong { font-size: .9rem; font-weight: 780; }
.priority { border-radius: 999px; }
.conversation-head { min-height: 78px; padding: 15px 20px; background: rgba(255, 255, 255, .96); border-color: var(--support-line); }
.conversation-title h2 { margin: 3px 0; font-size: 1.12rem; font-weight: 800; }
.conversation-scroll { padding: 22px; background: #f6f8fa; }
.issue-card { padding: 19px; border: 1px solid #dce6eb; border-left: 0; border-radius: 8px; box-shadow: 0 7px 20px rgba(25, 54, 68, .045); }
.issue-card::before { content: ''; display: block; width: 42px; height: 4px; margin-bottom: 14px; background: var(--support-accent); border-radius: 999px; }
.message-row { gap: 11px; margin: 16px 0; }
.message-avatar { width: 38px; height: 38px; flex-basis: 38px; border: 2px solid #fff; box-shadow: 0 3px 9px rgba(23, 54, 67, .1); }
.message-bubble { padding: 13px 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(25, 54, 68, .04); }
.message-row.technician .message-bubble { background: #e6f5f2; border-color: #b6ded8; }
.attachment-link { padding: 10px 12px; border-radius: 7px; transition: border-color .18s ease, background-color .18s ease; }
.attachment-link:hover { border-color: #8dc8d4; background: #e3f3f6; }
.reply-box { padding: 14px 16px; border-color: var(--support-line); box-shadow: 0 -8px 22px rgba(24, 54, 68, .035); }
.reply-box textarea { padding: 12px 13px; border-radius: 7px; border-color: #d4e0e6; }
.reply-box textarea:focus { border-color: var(--support-accent); box-shadow: 0 0 0 3px rgba(11, 143, 130, .11); outline: none; }
.send-button { min-height: 40px; padding: 0 16px; border-radius: 7px; box-shadow: 0 7px 15px rgba(6, 114, 103, .16); }
.send-button:hover:not(:disabled) { background: #056c62; transform: translateY(-1px); }
.support-modal { backdrop-filter: blur(5px); background: rgba(5, 28, 40, .62); }
.support-modal-panel { width: min(760px, 100%); border: 1px solid rgba(255, 255, 255, .22); border-radius: 8px; box-shadow: 0 28px 80px rgba(0, 22, 34, .38); }
.support-modal-panel > header { padding: 24px 28px; background: linear-gradient(135deg, #073b59, #08776d); border-bottom: 0; }
.support-modal-panel h2 { font-family: inherit; font-size: 1.55rem; font-weight: 850; }
.support-modal-panel header button { border: 1px solid rgba(255, 255, 255, .18); border-radius: 7px; }
.support-modal-panel header button:hover { background: rgba(255, 255, 255, .22); }
.form-grid { gap: 18px; padding: 26px 28px; }
.form-grid label > span, .form-grid legend { margin-bottom: 8px; color: #2f4353; font-size: .79rem; }
.form-grid input[type=text], .form-grid select, .form-grid textarea { min-height: 45px; padding: 11px 13px; border-color: #d4e0e6; border-radius: 7px; background: #fbfcfd; transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease; }
.form-grid textarea { min-height: 138px; }
.form-grid input[type=text]:focus, .form-grid select:focus, .form-grid textarea:focus { background: #fff; border-color: var(--support-accent); box-shadow: 0 0 0 3px rgba(11, 143, 130, .12); outline: none; }
.priority-options { gap: 7px; }
.priority-options label { min-height: 45px; display: grid; place-items: center; border-radius: 7px; transition: transform .16s ease, border-color .16s ease, background-color .16s ease; }
.priority-options label:hover { transform: translateY(-1px); border-color: #9db5c1; }
.upload-zone { min-height: 88px; border-radius: 7px; background: #f6fafb; transition: border-color .18s ease, background-color .18s ease; }
.upload-zone:hover { border-color: var(--support-accent); background: #edf8f6; }
.support-modal-panel > footer { padding: 17px 28px; background: #f8fafb; }
.support-page :is(button, a, input, select, textarea, summary):focus-visible { outline: 3px solid rgba(14, 165, 233, .28); outline-offset: 2px; }

:global(html.dark .support-page) { --support-ink: #e6eef3; --support-muted: #9fb0bc; --support-line: rgba(255, 255, 255, .1); --support-surface: #172231; --support-soft: #111a27; }
:global(html.dark .stat-card), :global(html.dark .support-workspace), :global(html.dark .conversation-head), :global(html.dark .reply-box), :global(html.dark .issue-card), :global(html.dark .support-modal-panel) { color: var(--support-ink); background: var(--support-surface); border-color: var(--support-line); }
:global(html.dark .ticket-pane), :global(html.dark .conversation-scroll), :global(html.dark .support-modal-panel > footer) { background: var(--support-soft); border-color: var(--support-line); }
:global(html.dark .ticket-tools) { background: rgba(23, 34, 49, .9); border-color: var(--support-line); }
:global(html.dark .ticket-search), :global(html.dark .ticket-tools select), :global(html.dark .conversation-controls select), :global(html.dark .reply-box textarea), :global(html.dark .form-grid input[type=text]), :global(html.dark .form-grid select), :global(html.dark .form-grid textarea) { color: #e8f0f4; background: #1c2938; border-color: rgba(255, 255, 255, .13); }
:global(html.dark .ticket-item) { color: #dce7ed; background: rgba(31, 45, 60, .72); }
:global(html.dark .ticket-item:hover) { background: #263748; border-color: rgba(122, 214, 203, .28); }
:global(html.dark .ticket-item.active) { background: #173e3d; border-color: #397c75; }
:global(html.dark .message-bubble) { color: #dfe9ee; background: #202e3d; border-color: rgba(255, 255, 255, .1); }
:global(html.dark .message-row.technician .message-bubble) { background: #163d3a; border-color: #34776f; }
:global(html.dark .form-grid label > span), :global(html.dark .form-grid legend) { color: #dbe6ec; }
:global(html.dark .upload-zone) { background: #192837; border-color: #526978; }

@media (max-width: 820px) {
  .support-page { padding: 12px 10px 32px; }
  .support-header { min-height: 134px; padding: 22px; }
  .support-workspace { height: calc(100dvh - 205px); min-height: 500px; }
  .conversation-scroll { padding: 14px; }
}
@media (max-width: 520px) {
  .support-header { gap: 16px; }
  .support-stats { gap: 8px; }
  .stat-card { min-height: 68px; padding: 11px; }
  .support-workspace { height: calc(100dvh - 245px); }
  .support-modal-panel { border: 0; }
  .support-modal-panel > header { padding: 20px 16px; }
  .form-grid { gap: 16px; padding: 20px 16px; }
  .support-modal-panel > footer { padding: 14px 16px; }
}
@media (prefers-reduced-motion: reduce) {
  .support-page *, .support-page *::before, .support-page *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
}
</style>