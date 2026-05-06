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
          placeholder="Ecrire un message au forum..."
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
              <p>
                <span v-if="post.auteur?.poste">{{ post.auteur.poste }}</span>
                <span v-if="post.auteur?.departement">{{ post.auteur.departement }}</span>
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
                      <span>{{ timeAgo(item.created_at) }}</span>
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
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { comment, create, list, remove, removeComment } from '@/api/forum'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const auth = useAuthStore()
const ui = useUiStore()

const posts = ref([])
const loading = ref(true)
const posting = ref(false)
const search = ref('')
const form = ref({ titre: '', contenu: '' })
const meta = ref({ current_page: 1, last_page: 1, total: 0 })

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
  } catch {
    ui.addToast('Impossible de charger le forum.', 'danger')
  } finally {
    loading.value = false
  }
}

async function submitPost() {
  if (posting.value || form.value.contenu.length < 2) return

  posting.value = true
  try {
    const { data } = await create(form.value)
    posts.value = [normalizePost(data.data), ...posts.value]
    meta.value.total = (meta.value.total || 0) + 1
    form.value = { titre: '', contenu: '' }
    ui.addToast(data.message || 'Sujet publie.', 'success')
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
    post.commentaires = [...post.commentaires, data.data]
    post.comments_count = (post.comments_count || 0) + 1
    post.commentForm = ''
    ui.addToast(data.message || 'Commentaire publie.', 'success')
  } catch (error) {
    const message = error.response?.data?.message || 'Impossible de publier le commentaire.'
    ui.addToast(message, 'danger')
  } finally {
    post.commenting = false
  }
}

async function deletePost(post) {
  if (!window.confirm('Supprimer ce sujet du forum ?')) return

  try {
    const { data } = await remove(post.id)
    posts.value = posts.value.filter(item => item.id !== post.id)
    meta.value.total = Math.max(0, (meta.value.total || 0) - 1)
    ui.addToast(data.message || 'Sujet supprime.', 'success')
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
    ui.addToast(data.message || 'Commentaire supprime.', 'success')
  } catch {
    ui.addToast('Impossible de supprimer ce commentaire.', 'danger')
  }
}

function normalizePost(post) {
  return {
    ...post,
    commentaires: post.commentaires || [],
    comments_count: post.comments_count || (post.commentaires || []).length,
    commentForm: '',
    commenting: false,
  }
}

function postInitials(post) {
  return initialsFromName(post.auteur?.name || 'Agent PNMLS')
}

function commentInitials(item) {
  return initialsFromName(item.auteur?.name || 'Agent PNMLS')
}

function initialsFromName(name) {
  const parts = (name || '').split(/\s+/).filter(Boolean)
  return ((parts[0]?.[0] || 'P') + (parts[1]?.[0] || 'N')).toUpperCase()
}

function commentLabel(count) {
  return count > 1 ? 'commentaires' : 'commentaire'
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
  max-width: 1120px;
  margin: 0 auto;
  padding: 2rem 1rem 3rem;
}

.forum-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(14, 116, 144, .16);
  border-radius: 8px;
  background:
    linear-gradient(135deg, rgba(236, 253, 245, .94), rgba(240, 249, 255, .94)),
    #fff;
  box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
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
  background: rgba(255, 255, 255, .94);
  box-shadow: 0 12px 32px rgba(15, 23, 42, .07);
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
}

.forum-input,
.forum-textarea,
.forum-search input,
.forum-comment-form textarea {
  width: 100%;
  border: 1px solid rgba(148, 163, 184, .35);
  border-radius: 8px;
  background: #fff;
  color: #0f172a;
  outline: none;
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
  background: #0f766e;
  color: #fff;
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
  background: #e0f2fe;
  color: #075985;
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
}

.forum-post-head {
  display: flex;
  justify-content: space-between;
  gap: .8rem;
}

.forum-post h2 {
  margin: 0;
  color: #0f172a;
  font-size: 1rem;
  font-weight: 900;
}

.forum-post-head p {
  display: flex;
  flex-wrap: wrap;
  gap: .45rem;
  margin: .15rem 0 0;
  color: #64748b;
  font-size: .83rem;
}

.forum-post-head p span:not(:last-child)::after {
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
  padding: .35rem .6rem;
  border-radius: 8px;
  background: #f0fdfa;
  color: #0f766e;
  font-size: .78rem;
  font-weight: 800;
}

.forum-post h3 {
  margin: .8rem 0 .35rem;
  color: #0f766e;
  font-size: 1.05rem;
  font-weight: 900;
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
  background: #f1f5f9;
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
  grid-template-columns: 36px 1fr;
  gap: .65rem;
}

.forum-comment-body {
  padding: .7rem .8rem;
  border: 1px solid rgba(148, 163, 184, .18);
  border-radius: 8px;
  background: #f8fafc;
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
}

.forum-comment-head span {
  color: #64748b;
  font-size: .75rem;
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

.forum-comment-form {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: .6rem;
}

.forum-comment-form button {
  min-height: 42px;
  padding: 0 .9rem;
  background: #0f766e;
  color: #fff;
}

.forum-comments-closed {
  padding: .7rem .8rem;
  border-radius: 8px;
  background: #f1f5f9;
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
  background: #fff;
  color: #334155;
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
    padding: 1rem .75rem 2rem;
  }

  .forum-hero,
  .forum-composer,
  .forum-post {
    grid-template-columns: 1fr;
  }

  .forum-hero {
    display: grid;
  }

  .forum-hero-badge {
    width: 72px;
    min-width: 72px;
  }

  .forum-avatar,
  .forum-post-avatar {
    width: 44px;
    height: 44px;
  }

  .forum-composer-footer,
  .forum-comment-form {
    align-items: stretch;
    grid-template-columns: 1fr;
  }

  .forum-composer-footer {
    flex-direction: column;
  }

  .forum-submit,
  .forum-comment-form button {
    width: 100%;
  }
}
</style>
