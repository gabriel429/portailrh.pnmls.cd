<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Chargement...</span>
        </div>
      </div>

      <template v-else-if="demande">
        <!-- Hero -->
        <section class="rh-hero">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <router-link :to="{ name: 'requests.show', params: { id: demande.id } }" class="back-link">
                <i class="fas fa-arrow-left me-1"></i> Retour aux details
              </router-link>
              <h1 class="rh-title mt-2"><i class="fas fa-edit me-2"></i>Modifier la Demande #{{ demande.id }}</h1>
              <p class="rh-sub">Modifier le statut et ajouter des remarques sur cette demande.</p>
            </div>
          </div>
        </section>

        <!-- Not authorized -->
        <div v-if="!isRH" class="dash-panel mt-3">
          <div class="p-4 text-center">
            <i class="fas fa-lock fa-3x text-danger mb-3 d-block"></i>
            <h5>Acces refuse</h5>
            <p class="text-muted">Seuls les agents RH peuvent modifier le statut d'une demande.</p>
            <router-link :to="{ name: 'requests.index' }" class="btn-rh main">
              <i class="fas fa-arrow-left me-1"></i> Retour
            </router-link>
          </div>
        </div>

        <div v-else class="dash-panel mt-3">
          <div class="p-4">
            <!-- Read-only request info -->
            <div class="info-grid mb-4">
              <div class="info-card">
                <div class="info-label"><i class="fas fa-user me-1"></i> Agent</div>
                <div class="info-value">{{ demande.agent?.prenom }} {{ demande.agent?.nom }}</div>
                <div class="info-sub">{{ demande.agent?.id_agent }}</div>
              </div>
              <div class="info-card">
                <div class="info-label"><i class="fas fa-tag me-1"></i> Type</div>
                <div class="info-value">
                  <span class="req-badge type">{{ formatType(demande.type) }}</span>
                </div>
              </div>
              <div class="info-card">
                <div class="info-label"><i class="fas fa-calendar me-1"></i> Periode</div>
                <div class="info-value">
                  {{ formatDate(demande.date_debut) }}
                  <template v-if="demande.date_fin"> - {{ formatDate(demande.date_fin) }}</template>
                </div>
              </div>
              <div class="info-card">
                <div class="info-label"><i class="fas fa-align-left me-1"></i> Description</div>
                <div class="info-value info-desc">{{ demande.description }}</div>
              </div>
            </div>

            <hr class="my-4">

            <!-- Edit form -->
            <h6 class="section-title"><i class="fas fa-gavel me-2"></i> Decision</h6>

            <form @submit.prevent="handleSubmit">
              <!-- Statut cards -->
              <div class="statut-grid mb-4">
                <div
                  v-for="s in statutOptions" :key="s.value"
                  class="statut-card"
                  :class="{ active: form.statut === s.value, [s.color]: true }"
                  @click="form.statut = s.value"
                >
                  <i :class="s.icon" class="statut-icon"></i>
                  <span class="statut-label">{{ s.label }}</span>
                </div>
              </div>
              <div v-if="errors.statut" class="text-danger small mb-3"><i class="fas fa-exclamation-circle me-1"></i>{{ errors.statut[0] }}</div>

              <!-- Remarques -->
              <div class="mb-4">
                <label class="form-label fw-semibold">
                  <i class="fas fa-comment-alt me-1 text-muted"></i> Remarques
                  <span class="text-muted fw-normal ms-1">(optionnel)</span>
                </label>
                <textarea
                  v-model="form.remarques" rows="4"
                  class="form-control" :class="{ 'is-invalid': errors.remarques }"
                  placeholder="Expliquez votre decision ou ajoutez un commentaire..."
                ></textarea>
                <div v-if="errors.remarques" class="invalid-feedback d-block">{{ errors.remarques[0] }}</div>
              </div>

              <!-- Buttons -->
              <div class="d-flex gap-2 justify-content-end flex-wrap">
                <router-link
                  :to="{ name: 'requests.show', params: { id: demande.id } }"
                  class="btn-rh outline"
                >
                  <i class="fas fa-times me-1"></i> Annuler
                </router-link>
                <button type="submit" class="btn-rh main" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-save me-1"></i>
                  Enregistrer
                </button>
              </div>
            </form>
          </div>
        </div>
      </template>

      <!-- Not found -->
      <div v-else class="dash-panel mt-3">
        <div class="p-5 text-center">
          <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
          <h5>Demande introuvable</h5>
          <router-link :to="{ name: 'requests.index' }" class="btn-rh main mt-3">
            <i class="fas fa-arrow-left me-1"></i> Retour aux demandes
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { get, update } from '@/api/requests'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()

const loading = ref(true)
const demande = ref(null)
const isRH = ref(false)
const errors = ref({})
const submitting = ref(false)

const form = ref({
  statut: 'en_attente',
  remarques: '',
})

const statutOptions = [
  { value: 'en_attente', label: 'En attente', icon: 'fas fa-hourglass-half', color: 'c-warning' },
  { value: 'approuvé', label: 'Approuve', icon: 'fas fa-check-circle', color: 'c-success' },
  { value: 'rejeté', label: 'Rejete', icon: 'fas fa-times-circle', color: 'c-danger' },
  { value: 'annulé', label: 'Annule', icon: 'fas fa-ban', color: 'c-secondary' },
]

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatType(type) {
  if (!type) return ''
  return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
}

async function handleSubmit() {
  errors.value = {}
  submitting.value = true

  try {
    await update(demande.value.id, {
      statut: form.value.statut,
      remarques: form.value.remarques,
    })
    ui.addToast('Demande modifiee avec succes.', 'success')
    router.push({ name: 'requests.show', params: { id: demande.value.id } })
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else if (err.response?.status === 403) {
      ui.addToast(err.response.data.message || 'Action non autorisee.', 'danger')
    } else {
      ui.addToast('Erreur lors de la modification.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await get(route.params.id)
    demande.value = data.data
    isRH.value = data.isRH

    form.value.statut = demande.value.statut || 'en_attente'
    form.value.remarques = demande.value.remarques || ''
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast('Vous n\'avez pas acces a cette demande.', 'danger')
      router.push({ name: 'requests.index' })
    } else {
      ui.addToast('Erreur lors du chargement de la demande.', 'danger')
    }
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.back-link { color: rgba(255,255,255,.7); text-decoration: none; font-size: .85rem; transition: color .2s; }
.back-link:hover { color: #fff; }

.section-title { font-weight: 700; font-size: .95rem; color: #1e293b; margin-bottom: 1rem; }

/* Info grid */
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.info-card { background: #f8fafc; border-radius: 12px; padding: 1rem; border: 1px solid #f1f5f9; }
.info-label { font-size: .75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: .35rem; }
.info-value { font-size: .9rem; font-weight: 600; color: #1e293b; }
.info-sub { font-size: .78rem; color: #94a3b8; margin-top: .15rem; }
.info-desc { font-weight: 400; font-size: .85rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

.req-badge.type { background: #e0f2fe; color: #075985; padding: 3px 10px; border-radius: 6px; font-size: .8rem; font-weight: 600; }

/* Statut selection grid */
.statut-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.statut-card { display: flex; flex-direction: column; align-items: center; gap: .5rem; padding: 1rem .75rem; border-radius: 12px; border: 2px solid #e2e8f0; cursor: pointer; transition: all .2s; background: #fff; }
.statut-card:hover { border-color: #94a3b8; }
.statut-card .statut-icon { font-size: 1.4rem; color: #94a3b8; transition: color .2s; }
.statut-card .statut-label { font-size: .78rem; font-weight: 600; color: #64748b; }

.statut-card.active.c-warning { border-color: #f59e0b; background: #fffbeb; }
.statut-card.active.c-warning .statut-icon { color: #f59e0b; }
.statut-card.active.c-warning .statut-label { color: #92400e; }

.statut-card.active.c-success { border-color: #22c55e; background: #f0fdf4; }
.statut-card.active.c-success .statut-icon { color: #22c55e; }
.statut-card.active.c-success .statut-label { color: #166534; }

.statut-card.active.c-danger { border-color: #ef4444; background: #fef2f2; }
.statut-card.active.c-danger .statut-icon { color: #ef4444; }
.statut-card.active.c-danger .statut-label { color: #991b1b; }

.statut-card.active.c-secondary { border-color: #64748b; background: #f8fafc; }
.statut-card.active.c-secondary .statut-icon { color: #64748b; }
.statut-card.active.c-secondary .statut-label { color: #334155; }

/* Form */
.form-control { border-radius: 10px; border: 1px solid #e2e8f0; font-size: .88rem; }
.form-control:focus { border-color: #0077B5; box-shadow: 0 0 0 3px rgba(0,119,181,.1); }
.form-label { font-size: .85rem; color: #475569; }

/* Buttons */
.btn-rh { display: inline-flex; align-items: center; gap: .35rem; padding: .55rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: .85rem; border: none; cursor: pointer; transition: all .2s; text-decoration: none; }
.btn-rh.main { background: linear-gradient(135deg, #0077B5, #005a87); color: #fff; }
.btn-rh.main:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,119,181,.3); }
.btn-rh.main:disabled { opacity: .6; transform: none; }
.btn-rh.outline { background: #fff; color: #64748b; border: 1px solid #e2e8f0; }
.btn-rh.outline:hover { background: #f8fafc; color: #334155; }

@media (max-width: 767.98px) {
  .info-grid { grid-template-columns: 1fr; }
  .statut-grid { grid-template-columns: repeat(2, 1fr); }
  .statut-card { padding: .75rem .5rem; }
  .statut-card .statut-icon { font-size: 1.1rem; }
  .statut-card .statut-label { font-size: .72rem; }
}
</style>
