<template>
  <div class="forum-page">
    <section class="forum-hero">
      <div>
        <div class="forum-eyebrow">Communication interne</div>
        <h1>Forum PNMLS</h1>
        <p>Un espace commun pour partager une information, poser une question ou laisser une note utile aux agents.</p>
      </div>
      <div class="forum-hero-badge">
        <i class="fas fa-comments"></i>
        <span>{{ meta.total || 0 }}</span>
      </div>
    </section>

    <section class="forum-composer">
      <div class="forum-avatar">{{ initials }}</div>
      <form class="forum-form" @submit.prevent="submitPost">
        <input
          v-model.trim="form.titre"
          class="forum-input"
          type="text"
          maxlength="160"
          placeholder="Sujet facultatif"
        >
        <textarea
          v-model.trim="form.contenu"
          class="forum-textarea"
          rows="4"
          maxlength="3000"
          placeholder="Écrire un message au forum..."
          required
        ></textarea>
        <div class="forum-composer-footer">
          <span>{{ form.contenu.length }}/3000</span>
          <button class="forum-submit" type="submit" :disabled="posting || form.contenu.length < 2">
            <i class="fas fa-paper-plane"></i>
            <span>{{ posting ? 'Publication...' : 'Publier' }}</span>
          </button>
        </div>
      </form>
    </section>

    <section class="forum-toolbar">
      <div class="forum-search">
        <i class="fas fa-search"></i>
        <input v-model.trim="search" type="search" placeholder="Rechercher dans le forum" @keyup.enter="loadPosts(1)">
      </div>
      <button class="forum-refresh" type="button" title="Actualiser" @click="loadPosts(1)">
        <i class="fas fa-rotate-right"></i>
      </button>
    </section>

    <div v-if="loading" class="forum-loading">
      <LoadingSpinner message="Chargement du forum..." />
    </div>

    <section v-else-if="posts.length" class="forum-feed">
      <article v-for="post in posts" :key="post.id" class="forum-post">
        <div class="forum-post-avatar">{{ postInitials(post) }}</div>
        <div class="forum-post-body">
          <div class="forum-post-head">
            <div>
              <h2>{{ post.auteur?.name || 'Agent PNMLS' }}</h2>
              <p class="forum-author-meta">
                <span v-for="part in authorMetaParts(post.auteur)" :key="part">{{ part }}</span>
                <span>{{ timeAgo(post.created_at) }}</span>
              </p>
            </div>
            <button
              v-if="post.can_delete"
              class="forum-icon-btn danger"
              type="button"
              title="Supprimer le sujet"
              @click="deletePost(post)"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>

          <div class="forum-post-status">
            <span><i class="fas fa-hourglass-half"></i>{{ lifetimeLabel(post) }}</span>
            <span><i class="fas fa-message"></i>{{ post.comments_count || 0 }} {{ commentLabel(post.comments_count || 0) }}</span>
            <button
              v-if="canViewReaders(post)"
              class="forum-status-button"
              type="button"
              title="Voir qui a vu"
              @click="openReadersModal(post)"
            >
              <i class="fas fa-users"></i>{{ post.read_count || 0 }} vue(s)
            </button>
            <span v-else><i class="fas fa-users"></i>{{ post.read_count || 0 }} vue(s)</span>
          </div>

          <h3 v-if="post.titre">{{ post.titre }}</h3>
          <p class="forum-post-content">{{ post.contenu }}</p>

          <section class="forum-comments">
            <div class="forum-comments-head">
              <h4>Commentaires</h4>
              <span>{{ post.commentaires.length }}</span>
            </div>

            <div v-if="post.commentaires.length" class="forum-comment-list">
              <article v-for="item in post.commentaires" :key="item.id" class="forum-comment">
                <div class="forum-comment-avatar">{{ commentInitials(item) }}</div>
                <div class="forum-comment-body">
                  <div class="forum-comment-head">
                    <div>
                      <strong>{{ item.auteur?.name || 'Agent PNMLS' }}</strong>
                      <span v-if="authorMetaText(item.auteur)" class="forum-comment-meta">{{ authorMetaText(item.auteur) }}</span>
                      <span class="forum-comment-time">{{ timeAgo(item.created_at) }}</span>
                    </div>
                    <button
                      v-if="item.can_delete"
                      class="forum-comment-delete"
                      type="button"
                      title="Supprimer le commentaire"
                      @click="deleteComment(post, item)"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <p>{{ item.contenu }}</p>
                  <div class="forum-comment-reactions" aria-label="Réactions au commentaire">
                    <button
                      type="button"
                      class="forum-reaction-btn"
                      :class="{ active: item.user_reaction === 'like' }"
                      :disabled="item.reacting"
                      title="J'aime"
                      @click="reactToCommentAction(post, item, 'like')"
                    >
                      <i class="fas fa-thumbs-up"></i>
                      <span>{{ item.likes_count || 0 }}</span>
                    </button>
                    <button
                      type="button"
                      class="forum-reaction-btn dislike"
                      :class="{ active: item.user_reaction === 'dislike' }"
                      :disabled="item.reacting"
                      title="J'aime pas"
                      @click="reactToCommentAction(post, item, 'dislike')"
                    >
                      <i class="fas fa-thumbs-down"></i>
                      <span>{{ item.dislikes_count || 0 }}</span>
                    </button>
                  </div>
                </div>
              </article>
            </div>

            <form v-if="post.can_comment" class="forum-comment-form" @submit.prevent="submitComment(post)">
              <textarea
                v-model.trim="post.commentForm"
                rows="2"
                maxlength="2000"
                placeholder="Ajouter un commentaire..."
              ></textarea>
              <button type="submit" :disabled="post.commenting || !post.commentForm">
                <i class="fas fa-reply"></i>
                <span>{{ post.commenting ? 'Envoi...' : 'Commenter' }}</span>
              </button>
            </form>
            <div v-else class="forum-comments-closed">
              Ce sujet est ferme.
            </div>
          </section>
        </div>
      </article>
    </section>

    <section v-else class="forum-empty">
      <i class="fas fa-comments"></i>
      <h2>Aucun sujet actif</h2>
      <p>Les sujets restent visibles pendant deux semaines.</p>
    </section>

    <nav v-if="meta.last_page > 1" class="forum-pagination" aria-label="Pagination forum">
      <button :disabled="meta.current_page <= 1" @click="loadPosts(meta.current_page - 1)">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button
        v-for="page in paginationPages"
        :key="page"
        :class="{ active: page === meta.current_page }"
        @click="loadPosts(page)"
      >
        {{ page }}
      </button>
      <button :disabled="meta.current_page >= meta.last_page" @click="loadPosts(meta.current_page + 1)">
        <i class="fas fa-chevron-right"></i>
      </button>
    </nav>

    <teleport to="body">
      <div v-if="showReadersModal" class="forum-readers-overlay" @click.self="closeReadersModal">
        <div class="forum-readers-dialog">
          <div class="forum-readers-header">
            <div class="forum-readers-icon"><i class="fas fa-users"></i></div>
            <div>
              <h5>Agents ayant vu</h5>
              <p>{{ readersTarget?.titre || truncate(readersTarget?.contenu, 80) }}</p>
            </div>
            <button class="forum-readers-close" type="button" @click="closeReadersModal">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div v-if="readersLoading" class="forum-readers-loading">
            <div class="spinner-border text-primary"></div>
            <span>Chargement des vues...</span>
          </div>

          <div v-else-if="readersList.length" class="forum-readers-list">
            <article v-for="reader in readersList" :key="reader.id" class="forum-reader-row">
              <div class="forum-reader-avatar">{{ initialsFromName(reader.user?.name || 'Agent PNMLS') }}</div>
              <div class="forum-reader-info">
                <strong>{{ reader.user?.name || 'Agent' }}</strong>
                <span>{{ reader.user?.role || 'Role non precise' }}</span>
                <small>{{ [reader.user?.departement, reader.user?.province].filter(Boolean).join(' - ') || reader.user?.email || 'Structure non precisee' }}</small>
              </div>
              <time>{{ formatDateTime(reader.seen_at) }}</time>
            </article>
          </div>

          <div v-else class="forum-readers-empty">
            <i class="fas fa-eye-slash"></i>
            <strong>Aucune vue enregistrée</strong>
            <span>Les agents apparaîtront ici après avoir ouvert le forum.</span>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { comment, create, list, markRead, reactToComment, readers, remove, removeComment } from '@/api/forum'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const auth = useAuthStore()
const ui = useUiStore()

const posts = ref([])
const loading = ref(true)
const posting = ref(false)
const search = ref('')
const form = ref({ titre: '', contenu: '' })
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const showReadersModal = ref(false)
const readersTarget = ref(null)
const readersLoading = ref(false)
const readersList = ref([])

const initials = computed(() => {
  const agent = auth.agent
  const name = `${agent?.prenom || ''} ${agent?.nom || auth.user?.name || ''}`.trim()
  return initialsFromName(name)
})

const paginationPages = computed(() => {
  const pages = []
  const current = meta.value.current_page || 1
  const last = meta.value.last_page || 1
  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)
  for (let page = start; page <= end; page += 1) pages.push(page)
  return pages
})

async function loadPosts(page = 1) {
  loading.value = true
  try {
    const { data } = await list({
      page,
      search: search.value || undefined,
      per_page: 12,
    })
    posts.value = (data.data || []).map(normalizePost)
    meta.value = data.meta || { current_page: 1, last_page: 1, total: 0 }
    markVisiblePostsRead()
  } catch {
    ui.addToast('Impossible de charger le forum.', 'danger')
  } finally {
    loading.value = false
  }
}

async function markVisiblePostsRead() {
  const unreadPosts = posts.value.filter(post => !post.has_seen)

  await Promise.all(unreadPosts.map(async (post) => {
    try {
      await markRead(post.id)
      post.has_seen = true
      post.read_count = Number(post.read_count || 0) + 1
    } catch {
      // Le suivi de vue ne doit pas bloquer la lecture du forum.
    }
  }))
}

async function submitPost() {
  if (posting.value || form.value.contenu.length < 2) return

  posting.value = true
  try {
    const { data } = await create(form.value)
    posts.value = [normalizePost(data.data), ...posts.value]
    meta.value.total = (meta.value.total || 0) + 1
    form.value = { titre: '', contenu: '' }
    ui.addToast(data.message || 'Sujet publié.', 'success')
  } catch (error) {
    const message = error.response?.data?.message || 'Impossible de publier le sujet.'
    ui.addToast(message, 'danger')
  } finally {
    posting.value = false
  }
}

async function submitComment(post) {
  const contenu = (post.commentForm || '').trim()
  if (post.commenting || !contenu) return

  post.commenting = true
  try {
    const { data } = await comment(post.id, { contenu })
    post.commentaires = [...post.commentaires, normalizeComment(data.data)]
    post.comments_count = (post.comments_count || 0) + 1
    post.commentForm = ''
    ui.addToast(data.message || 'Commentaire publié.', 'success')
  } catch (error) {
    const message = error.response?.data?.message || 'Impossible de publier le commentaire.'
    ui.addToast(message, 'danger')
  } finally {
    post.commenting = false
  }
}

async function reactToCommentAction(post, item, reaction) {
  if (item.reacting) return

  item.reacting = true
  try {
    const { data } = await reactToComment(item.id, { reaction })
    const updated = normalizeComment(data.data)
    Object.assign(item, updated, { reacting: false })
  } catch (error) {
      const message = error.response?.data?.message || 'Impossible d’enregistrer votre réaction.'
    ui.addToast(message, 'danger')
  } finally {
    item.reacting = false
  }
}

async function deletePost(post) {
  if (!window.confirm('Supprimer ce sujet du forum ?')) return

  try {
    const { data } = await remove(post.id)
    posts.value = posts.value.filter(item => item.id !== post.id)
    meta.value.total = Math.max(0, (meta.value.total || 0) - 1)
    ui.addToast(data.message || 'Sujet supprimé.', 'success')
  } catch {
    ui.addToast('Impossible de supprimer ce sujet.', 'danger')
  }
}

async function deleteComment(post, item) {
  if (!window.confirm('Supprimer ce commentaire ?')) return

  try {
    const { data } = await removeComment(item.id)
    post.commentaires = post.commentaires.filter(commentaire => commentaire.id !== item.id)
    post.comments_count = Math.max(0, (post.comments_count || 0) - 1)
    ui.addToast(data.message || 'Commentaire supprimé.', 'success')
  } catch {
    ui.addToast('Impossible de supprimer ce commentaire.', 'danger')
  }
}

function normalizePost(post) {
  return {
    ...post,
    commentaires: (post.commentaires || []).map(normalizeComment),
    comments_count: post.comments_count || (post.commentaires || []).length,
    read_count: Number(post.read_count || 0),
    has_seen: Boolean(post.has_seen),
    commentForm: '',
    commenting: false,
  }
}

function normalizeComment(item) {
  return {
    ...item,
    likes_count: Number(item?.likes_count || 0),
    dislikes_count: Number(item?.dislikes_count || 0),
    user_reaction: item?.user_reaction || null,
    reacting: false,
  }
}

function postInitials(post) {
  return initialsFromName(post.auteur?.name || 'Agent PNMLS')
}

function commentInitials(item) {
  return initialsFromName(item.auteur?.name || 'Agent PNMLS')
}

function authorMetaParts(author) {
  if (!author) return []

  return [
    author.fonction || author.poste,
    author.rattachement || author.departement || author.province,
  ].filter(Boolean)
}

function authorMetaText(author) {
  return authorMetaParts(author).join(' - ')
}

function initialsFromName(name) {
  const parts = (name || '').split(/\s+/).filter(Boolean)
  return ((parts[0]?.[0] || 'P') + (parts[1]?.[0] || 'N')).toUpperCase()
}

function commentLabel(count) {
  return count > 1 ? 'commentaires' : 'commentaire'
}

function canViewReaders(post) {
  return auth.isSEN || auth.isSuperAdmin || Number(post.auteur?.user_id) === Number(auth.user?.id)
}

async function openReadersModal(post) {
  readersTarget.value = post
  readersList.value = []
  showReadersModal.value = true
  readersLoading.value = true

  try {
    const { data } = await readers(post.id)
    readersList.value = data.data || []
  } catch (error) {
    ui.addToast(error.response?.data?.message || 'Impossible de charger les vues.', 'danger')
    showReadersModal.value = false
  } finally {
    readersLoading.value = false
  }
}

function closeReadersModal() {
  showReadersModal.value = false
  readersTarget.value = null
  readersList.value = []
}

function truncate(value, len) {
  const text = value || ''
  return text.length > len ? text.substring(0, len) + '...' : text
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function lifetimeLabel(post) {
  if (!post.expires_at) return 'Sujet actif 14 jours'

  const diffMs = new Date(post.expires_at).getTime() - Date.now()
  const diffDays = Math.ceil(diffMs / 86400000)

  if (diffDays <= 0) return 'Sujet ferme'
  if (diffDays === 1) return '1 jour restant'
  return `${diffDays} jours restants`
}

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const diffMs = Date.now() - new Date(dateStr).getTime()
  const diffMin = Math.floor(diffMs / 60000)
  const diffH = Math.floor(diffMs / 3600000)
  const diffD = Math.floor(diffMs / 86400000)

  if (diffMin < 1) return 'a l instant'
  if (diffMin < 60) return `${diffMin} min`
  if (diffH < 24) return `${diffH} h`
  if (diffD < 7) return `${diffD} j`

  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

onMounted(() => loadPosts())
</script>

<style scoped>
.forum-page {
  --forum-glass: rgba(255, 255, 255, .68);
  --forum-glass-strong: rgba(255, 255, 255, .84);
  --forum-glass-border: rgba(125, 211, 252, .32);
  --forum-glass-shadow: 0 18px 48px rgba(15, 35, 58, .13), inset 0 1px 0 rgba(255, 255, 255, .70);
  max-width: 1120px;
  margin: 0 auto;
  padding: 2rem 1rem 3rem;
  position: relative;
}

.forum-page::before {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background:
    radial-gradient(circle at 14% 10%, rgba(14, 165, 233, .16), transparent 30%),
    radial-gradient(circle at 88% 14%, rgba(15, 118, 110, .14), transparent 28%),
    linear-gradient(135deg, rgba(240, 249, 255, .70), rgba(236, 253, 245, .58));
}

.forum-page,
.forum-page * {
  box-sizing: border-box;
}

.forum-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.5rem;
  border: 1px solid var(--forum-glass-border);
  border-radius: 8px;
  background:
    linear-gradient(135deg, rgba(236, 253, 245, .70), rgba(240, 249, 255, .76)),
    var(--forum-glass);
  box-shadow: var(--forum-glass-shadow);
  backdrop-filter: blur(20px) saturate(155%);
  -webkit-backdrop-filter: blur(20px) saturate(155%);
}

.forum-eyebrow {
  color: #0f766e;
  font-size: .78rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.forum-hero h1 {
  margin: .25rem 0;
  color: #0f172a;
  font-size: clamp(1.8rem, 4vw, 3rem);
  font-weight: 900;
}

.forum-hero p {
  max-width: 680px;
  margin: 0;
  color: #475569;
  font-size: 1rem;
}

.forum-hero-badge {
  width: 96px;
  min-width: 96px;
  aspect-ratio: 1;
  display: grid;
  place-items: center;
  border-radius: 8px;
  background: #0f172a;
  color: #fff;
  box-shadow: 0 14px 28px rgba(15, 23, 42, .2);
}

.forum-hero-badge i { font-size: 1.45rem; }
.forum-hero-badge span { font-size: 1.7rem; font-weight: 900; }

.forum-composer,
.forum-toolbar,
.forum-post,
.forum-empty {
  border: 1px solid rgba(148, 163, 184, .22);
  border-radius: 8px;
  background: var(--forum-glass);
  box-shadow: var(--forum-glass-shadow);
  backdrop-filter: blur(18px) saturate(152%);
  -webkit-backdrop-filter: blur(18px) saturate(152%);
}

.forum-composer {
  display: grid;
  grid-template-columns: 52px 1fr;
  gap: 1rem;
  margin-top: 1rem;
  padding: 1rem;
}

.forum-avatar,
.forum-post-avatar,
.forum-comment-avatar {
  display: grid;
  place-items: center;
  border-radius: 8px;
  background: linear-gradient(135deg, #0f766e, #2563eb);
  color: #fff;
  font-weight: 900;
  text-transform: uppercase;
}

.forum-avatar,
.forum-post-avatar {
  width: 52px;
  height: 52px;
}

.forum-comment-avatar {
  width: 36px;
  height: 36px;
  font-size: .78rem;
}

.forum-form {
  display: grid;
  gap: .75rem;
  min-width: 0;
}

.forum-input,
.forum-textarea,
.forum-search input,
.forum-comment-form textarea {
  width: 100%;
  border: 1px solid rgba(148, 163, 184, .35);
  border-radius: 8px;
  background: rgba(255, 255, 255, .62);
  color: #0f172a;
  outline: none;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.72);
  backdrop-filter: blur(10px) saturate(145%);
  -webkit-backdrop-filter: blur(10px) saturate(145%);
}

.forum-input {
  min-height: 44px;
  padding: 0 .9rem;
}

.forum-textarea {
  resize: vertical;
  min-height: 118px;
  padding: .85rem .9rem;
}

.forum-comment-form textarea {
  min-height: 54px;
  padding: .7rem .8rem;
  resize: vertical;
}

.forum-input:focus,
.forum-textarea:focus,
.forum-search input:focus,
.forum-comment-form textarea:focus {
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
}

.forum-composer-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  color: #64748b;
  font-size: .86rem;
}

.forum-submit,
.forum-refresh,
.forum-pagination button,
.forum-comment-form button,
.forum-icon-btn,
.forum-comment-delete {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .45rem;
  border: 0;
  border-radius: 8px;
  font-weight: 800;
}

.forum-submit {
  min-height: 42px;
  padding: 0 1rem;
  background: linear-gradient(135deg, rgba(15, 118, 110, .96), rgba(37, 99, 235, .90));
  color: #fff;
  box-shadow: 0 10px 24px rgba(15, 118, 110, .22), inset 0 1px 0 rgba(255,255,255,.28);
}

.forum-submit:disabled,
.forum-comment-form button:disabled {
  opacity: .55;
  cursor: not-allowed;
}

.forum-toolbar {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: .75rem;
  margin: 1rem 0;
  padding: .8rem;
}

.forum-search {
  position: relative;
}

.forum-search i {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.forum-search input {
  min-height: 44px;
  padding: 0 .9rem 0 2.4rem;
}

.forum-refresh {
  width: 44px;
  height: 44px;
  background: rgba(224, 242, 254, .72);
  color: #075985;
  border: 1px solid rgba(255,255,255,.65);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.76), 0 8px 18px rgba(15,35,58,.08);
}

.forum-loading {
  padding: 4rem 0;
  text-align: center;
}

.forum-feed {
  display: grid;
  gap: .9rem;
}

.forum-post {
  display: grid;
  grid-template-columns: 52px 1fr;
  gap: 1rem;
  padding: 1rem;
  transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
}

.forum-post:hover {
  transform: translateY(-1px);
  border-color: rgba(14, 165, 233, .36);
  box-shadow: 0 24px 56px rgba(15, 35, 58, .16), inset 0 1px 0 rgba(255,255,255,.72);
}

.forum-post-body,
.forum-post-head > div,
.forum-comment-body,
.forum-comment-head > div,
.forum-item-content {
  min-width: 0;
}

.forum-post-head {
  display: flex;
  justify-content: space-between;
  gap: .8rem;
  min-width: 0;
}

.forum-post h2 {
  margin: 0;
  color: #0f172a;
  font-size: 1rem;
  font-weight: 900;
  overflow-wrap: anywhere;
}

.forum-post-head p,
.forum-author-meta {
  display: flex;
  flex-wrap: wrap;
  gap: .45rem;
  margin: .15rem 0 0;
  color: #64748b;
  font-size: .83rem;
  min-width: 0;
}

.forum-author-meta span {
  min-width: 0;
  max-width: 100%;
  overflow-wrap: anywhere;
}

.forum-author-meta span:not(:last-child)::after {
  content: "";
  display: inline-block;
  width: 4px;
  height: 4px;
  margin-left: .45rem;
  border-radius: 50%;
  background: #cbd5e1;
  vertical-align: middle;
}

.forum-post-status {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  margin-top: .8rem;
}

.forum-post-status span {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  max-width: 100%;
  padding: .35rem .6rem;
  border-radius: 8px;
  background: rgba(240, 253, 250, .72);
  color: #0f766e;
  font-size: .78rem;
  font-weight: 800;
  overflow-wrap: anywhere;
}

.forum-status-button {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  max-width: 100%;
  padding: .35rem .6rem;
  border: 0;
  border-radius: 8px;
  background: rgba(238, 242, 255, .76);
  color: #3730a3;
  font-size: .78rem;
  font-weight: 800;
}

.forum-status-button:hover {
  background: #e0e7ff;
}

.forum-post h3 {
  margin: .8rem 0 .35rem;
  color: #0f766e;
  font-size: 1.05rem;
  font-weight: 900;
  overflow-wrap: anywhere;
}

.forum-post-content {
  margin: .65rem 0 0;
  color: #334155;
  line-height: 1.65;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.forum-icon-btn {
  width: 38px;
  height: 38px;
  color: #334155;
  background: rgba(241, 245, 249, .68);
  border: 1px solid rgba(255,255,255,.62);
}

.forum-icon-btn.danger {
  background: #fee2e2;
  color: #b91c1c;
}

.forum-comments {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(148, 163, 184, .22);
}

.forum-comments-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: .75rem;
}

.forum-comments-head h4 {
  margin: 0;
  color: #0f172a;
  font-size: .95rem;
  font-weight: 900;
}

.forum-comments-head span {
  min-width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  background: #e0f2fe;
  color: #075985;
  font-size: .8rem;
  font-weight: 900;
}

.forum-comment-list {
  display: grid;
  gap: .65rem;
  margin-bottom: .85rem;
}

.forum-comment {
  display: grid;
  grid-template-columns: 36px minmax(0, 1fr);
  gap: .65rem;
}

.forum-comment-body {
  padding: .7rem .8rem;
  border: 1px solid rgba(148, 163, 184, .18);
  border-radius: 8px;
  background: rgba(248, 250, 252, .64);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.68);
  backdrop-filter: blur(10px) saturate(140%);
  -webkit-backdrop-filter: blur(10px) saturate(140%);
}

.forum-comment-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .6rem;
}

.forum-comment-head strong {
  display: block;
  color: #0f172a;
  font-size: .88rem;
  line-height: 1.25;
  overflow-wrap: anywhere;
}

.forum-comment-meta,
.forum-comment-time {
  display: block;
  color: #64748b;
  font-size: .75rem;
}

.forum-comment-meta {
  margin-top: .08rem;
  color: #475569;
  font-weight: 700;
}

.forum-comment-delete {
  width: 28px;
  height: 28px;
  background: transparent;
  color: #b91c1c;
}

.forum-comment-body p {
  margin: .35rem 0 0;
  color: #334155;
  line-height: 1.5;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.forum-comment-reactions {
  display: flex;
  flex-wrap: wrap;
  gap: .4rem;
  margin-top: .6rem;
}

.forum-reaction-btn {
  min-width: 58px;
  min-height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .35rem;
  border: 1px solid rgba(14, 165, 233, .18);
  border-radius: 8px;
  background: rgba(239, 246, 255, .76);
  color: #2563eb;
  font-size: .78rem;
  font-weight: 900;
}

.forum-reaction-btn.dislike {
  border-color: rgba(239, 68, 68, .18);
  background: rgba(255, 247, 237, .78);
  color: #c2410c;
}

.forum-reaction-btn.active {
  background: #2563eb;
  color: #fff;
  border-color: #2563eb;
}

.forum-reaction-btn.dislike.active {
  background: #c2410c;
  border-color: #c2410c;
}

.forum-reaction-btn:disabled {
  opacity: .65;
  cursor: wait;
}

.forum-comment-form {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: .6rem;
}

.forum-comment-form button {
  min-height: 42px;
  padding: 0 .9rem;
  background: linear-gradient(135deg, rgba(15, 118, 110, .96), rgba(37, 99, 235, .88));
  color: #fff;
}

.forum-comments-closed {
  padding: .7rem .8rem;
  border-radius: 8px;
  background: rgba(241, 245, 249, .68);
  color: #64748b;
  font-size: .9rem;
}

.forum-empty {
  padding: 3rem 1rem;
  text-align: center;
}

.forum-empty i {
  color: #0f766e;
  font-size: 2rem;
}

.forum-empty h2 {
  margin: .8rem 0 .25rem;
  color: #0f172a;
  font-size: 1.25rem;
  font-weight: 900;
}

.forum-empty p {
  margin: 0;
  color: #64748b;
}

.forum-readers-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, .42);
  backdrop-filter: blur(14px) saturate(145%);
  -webkit-backdrop-filter: blur(14px) saturate(145%);
}

.forum-readers-dialog {
  width: min(100%, 680px);
  max-height: 88vh;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, .55);
  border-radius: 8px;
  background: rgba(255, 255, 255, .78);
  box-shadow: 0 28px 80px rgba(15, 23, 42, .28);
  backdrop-filter: blur(22px) saturate(160%);
  -webkit-backdrop-filter: blur(22px) saturate(160%);
  display: flex;
  flex-direction: column;
}

.forum-readers-header {
  display: flex;
  align-items: center;
  gap: .8rem;
  padding: 1rem 1.15rem;
  border-bottom: 1px solid rgba(148, 163, 184, .22);
}

.forum-readers-icon {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  background: #0f766e;
  color: #fff;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.forum-readers-header h5 {
  margin: 0;
  font-size: 1rem;
  font-weight: 850;
  color: #0f172a;
}

.forum-readers-header p {
  margin: .1rem 0 0;
  color: #64748b;
  font-size: .78rem;
  overflow-wrap: anywhere;
}

.forum-readers-close {
  margin-left: auto;
  width: 36px;
  height: 36px;
  border: 0;
  border-radius: 8px;
  color: #64748b;
  background: rgba(248, 250, 252, .70);
}

.forum-readers-close:hover {
  color: #dc2626;
  background: #fee2e2;
}

.forum-readers-loading,
.forum-readers-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .7rem;
  padding: 3rem 1rem;
  color: #64748b;
  text-align: center;
}

.forum-readers-empty i {
  font-size: 2rem;
  color: #94a3b8;
}

.forum-readers-empty strong {
  color: #0f172a;
}

.forum-readers-list {
  overflow: auto;
  padding: .75rem;
}

.forum-reader-row {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr) auto;
  gap: .75rem;
  align-items: center;
  padding: .75rem;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid rgba(148, 163, 184, .18);
}

.forum-reader-row + .forum-reader-row {
  margin-top: .55rem;
}

.forum-reader-avatar {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  background: #e0f2fe;
  color: #0369a1;
  display: grid;
  place-items: center;
  font-weight: 850;
}

.forum-reader-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: .1rem;
}

.forum-reader-info strong {
  color: #0f172a;
  font-size: .9rem;
}

.forum-reader-info span,
.forum-reader-info small,
.forum-reader-row time {
  color: #64748b;
  font-size: .75rem;
}

.forum-reader-row time {
  white-space: nowrap;
  font-weight: 700;
}

.forum-pagination {
  display: flex;
  justify-content: center;
  gap: .4rem;
  margin-top: 1.25rem;
}

.forum-pagination button {
  min-width: 40px;
  height: 40px;
  border: 1px solid rgba(148, 163, 184, .35);
  background: rgba(255,255,255,.72);
  color: #334155;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
}

.forum-pagination button.active {
  background: #0f766e;
  color: #fff;
  border-color: #0f766e;
}

.forum-pagination button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .forum-page {
    max-width: 100%;
    padding: .75rem .65rem 1.8rem;
  }

  .forum-hero,
  .forum-composer,
  .forum-post {
    grid-template-columns: 1fr;
  }

  .forum-hero {
    display: grid;
    gap: .75rem;
    padding: 1rem;
  }

  .forum-eyebrow {
    font-size: .68rem;
    letter-spacing: .06em;
  }

  .forum-hero h1 {
    font-size: 1.48rem;
    line-height: 1.15;
  }

  .forum-hero p {
    font-size: .88rem;
    line-height: 1.45;
  }

  .forum-hero-badge {
    width: auto;
    min-width: 0;
    min-height: 40px;
    aspect-ratio: auto;
    display: inline-flex;
    justify-content: center;
    gap: .45rem;
    justify-self: start;
    padding: .45rem .7rem;
  }

  .forum-hero-badge i {
    font-size: 1rem;
  }

  .forum-hero-badge span {
    font-size: 1rem;
  }

  .forum-avatar,
  .forum-post-avatar {
    width: 40px;
    height: 40px;
    font-size: .86rem;
  }

  .forum-composer,
  .forum-toolbar,
  .forum-post,
  .forum-empty {
    border-radius: 8px;
  }

  .forum-composer {
    gap: .65rem;
    margin-top: .75rem;
    padding: .78rem;
  }

  .forum-avatar {
    display: none;
  }

  .forum-input {
    min-height: 42px;
    padding: 0 .75rem;
    font-size: .88rem;
  }

  .forum-textarea {
    min-height: 96px;
    padding: .72rem .75rem;
    font-size: .88rem;
  }

  .forum-toolbar {
    gap: .55rem;
    margin: .75rem 0;
    padding: .65rem;
  }

  .forum-search input {
    min-height: 42px;
    font-size: .86rem;
  }

  .forum-refresh {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
  }

  .forum-feed {
    gap: .72rem;
  }

  .forum-post {
    gap: .55rem;
    padding: .78rem;
  }

  .forum-post-head {
    align-items: flex-start;
    gap: .45rem;
  }

  .forum-post h2 {
    font-size: .94rem;
    line-height: 1.25;
  }

  .forum-post-head p,
  .forum-author-meta {
    gap: .28rem .4rem;
    font-size: .72rem;
    line-height: 1.35;
  }

  .forum-author-meta span:not(:last-child)::after {
    display: none;
  }

  .forum-icon-btn {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
  }

  .forum-post-status {
    gap: .35rem;
    margin-top: .6rem;
  }

  .forum-post-status span {
    padding: .28rem .45rem;
    font-size: .7rem;
    line-height: 1.25;
  }

  .forum-status-button {
    padding: .28rem .45rem;
    font-size: .7rem;
    line-height: 1.25;
  }

  .forum-post h3 {
    margin: .65rem 0 .25rem;
    font-size: .96rem;
    line-height: 1.3;
  }

  .forum-post-content {
    margin-top: .45rem;
    font-size: .88rem;
    line-height: 1.55;
  }

  .forum-comments {
    margin-top: .75rem;
    padding-top: .75rem;
  }

  .forum-comments-head {
    margin-bottom: .6rem;
  }

  .forum-comments-head h4 {
    font-size: .86rem;
  }

  .forum-comments-head span {
    min-width: 26px;
    height: 26px;
    font-size: .72rem;
  }

  .forum-comment-list {
    gap: .55rem;
  }

  .forum-comment {
    grid-template-columns: 30px minmax(0, 1fr);
    gap: .45rem;
  }

  .forum-comment-avatar {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    font-size: .66rem;
  }

  .forum-comment-body {
    padding: .58rem .62rem;
  }

  .forum-comment-head {
    align-items: flex-start;
    gap: .4rem;
  }

  .forum-comment-head strong {
    font-size: .8rem;
  }

  .forum-comment-meta,
  .forum-comment-time {
    font-size: .67rem;
    line-height: 1.35;
  }

  .forum-comment-delete {
    width: 28px;
    height: 28px;
    flex-shrink: 0;
  }

  .forum-comment-body p {
    font-size: .82rem;
    line-height: 1.45;
  }

  .forum-comment-reactions {
    gap: .35rem;
    margin-top: .5rem;
  }

  .forum-reaction-btn {
    min-width: 0;
    min-height: 30px;
    padding: 0 .55rem;
    font-size: .72rem;
  }

  .forum-comment-form textarea {
    min-height: 74px;
    font-size: .86rem;
  }

  .forum-pagination {
    flex-wrap: wrap;
    gap: .35rem;
  }

  .forum-pagination button {
    min-width: 36px;
    height: 36px;
    font-size: .82rem;
  }

  .forum-composer-footer,
  .forum-comment-form {
    align-items: stretch;
    grid-template-columns: 1fr;
  }

  .forum-reader-row {
    grid-template-columns: 40px minmax(0, 1fr);
  }

  .forum-reader-row time {
    grid-column: 2;
  }

  .forum-composer-footer {
    flex-direction: column;
  }

  .forum-submit,
  .forum-comment-form button {
    width: 100%;
  }
}

@media (max-width: 380px) {
  .forum-page {
    padding-left: .5rem;
    padding-right: .5rem;
  }

  .forum-toolbar {
    grid-template-columns: 1fr;
  }

  .forum-refresh {
    width: 100%;
  }

  .forum-post {
    padding: .65rem;
  }

  .forum-comment {
    grid-template-columns: 1fr;
  }

  .forum-comment-avatar {
    display: none;
  }
}
</style>
