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
      <button class="forum-refresh" type="button" @click="loadPosts(1)">
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
              class="forum-post-delete"
              type="button"
              title="Supprimer"
              @click="deletePost(post)"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>
          <h3 v-if="post.titre">{{ post.titre }}</h3>
          <p class="forum-post-content">{{ post.contenu }}</p>
        </div>
      </article>
    </section>

    <section v-else class="forum-empty">
      <i class="fas fa-comments"></i>
      <h2>Aucun message pour le moment</h2>
      <p>Le premier message publie apparaitra ici.</p>
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
import { create, list, remove } from '@/api/forum'
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
    posts.value = data.data || []
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
    posts.value = [data.data, ...posts.value]
    meta.value.total = (meta.value.total || 0) + 1
    form.value = { titre: '', contenu: '' }
    ui.addToast(data.message || 'Message publie.', 'success')
  } catch (error) {
    const message = error.response?.data?.message || 'Impossible de publier le message.'
    ui.addToast(message, 'danger')
  } finally {
    posting.value = false
  }
}

async function deletePost(post) {
  if (!window.confirm('Supprimer ce message du forum ?')) return

  try {
    const { data } = await remove(post.id)
    posts.value = posts.value.filter(item => item.id !== post.id)
    meta.value.total = Math.max(0, (meta.value.total || 0) - 1)
    ui.addToast(data.message || 'Message supprime.', 'success')
  } catch {
    ui.addToast('Impossible de supprimer ce message.', 'danger')
  }
}

function postInitials(post) {
  return initialsFromName(post.auteur?.name || 'Agent PNMLS')
}

function initialsFromName(name) {
  const parts = (name || '').split(/\s+/).filter(Boolean)
  return (parts[0]?.[0] || 'P') + (parts[1]?.[0] || 'N')
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
.forum-post-avatar {
  width: 52px;
  height: 52px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  background: linear-gradient(135deg, #0f766e, #2563eb);
  color: #fff;
  font-weight: 900;
  text-transform: uppercase;
}

.forum-form {
  display: grid;
  gap: .75rem;
}

.forum-input,
.forum-textarea,
.forum-search input {
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

.forum-input:focus,
.forum-textarea:focus,
.forum-search input:focus {
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
.forum-pagination button {
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

.forum-submit:disabled {
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

.forum-post-delete {
  width: 38px;
  height: 38px;
  border: 0;
  border-radius: 8px;
  background: #fee2e2;
  color: #b91c1c;
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

  .forum-composer-footer {
    align-items: stretch;
    flex-direction: column;
  }

  .forum-submit {
    width: 100%;
  }
}
</style>
