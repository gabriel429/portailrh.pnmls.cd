<template>
  <teleport to="body">
    <div v-if="activeModal" class="ux-overlay">
      <section v-if="activeModal === 'tour-prompt'" class="ux-card ux-card-compact">
        <button class="ux-close" type="button" aria-label="Ignorer" @click="skipTour">
          <i class="fas fa-times"></i>
        </button>
        <div class="ux-kicker">
          <span class="ux-kicker-icon"><i class="fas fa-route"></i></span>
          Guide utilisateur
        </div>
        <h2>Bienvenue sur E-PNMLS</h2>
        <p>
          Une courte visite peut vous aider à retrouver rapidement le tableau de bord,
          les modules, les notifications et les actions utiles selon votre rôle.
        </p>
        <div class="ux-actions">
          <button class="ux-btn ux-btn-primary" type="button" @click="startTour">
            <i class="fas fa-play"></i>
            Commencer la visite
          </button>
          <button class="ux-btn ux-btn-ghost" type="button" @click="skipTour">
            Ignorer
          </button>
        </div>
      </section>

      <section v-else-if="activeModal === 'tour'" class="ux-card ux-tour-card">
        <button class="ux-close" type="button" aria-label="Fermer" @click="closeTour">
          <i class="fas fa-times"></i>
        </button>
        <div class="ux-progress">
          <span
            v-for="(step, index) in tourSteps"
            :key="step.title"
            :class="['ux-progress-dot', { active: index === tourStep, done: index < tourStep }]"
          ></span>
        </div>
        <div class="ux-step-icon">
          <i :class="['fas', currentTourStep.icon]"></i>
        </div>
        <p class="ux-step-count">Étape {{ tourStep + 1 }} sur {{ tourSteps.length }}</p>
        <h2>{{ currentTourStep.title }}</h2>
        <p>{{ currentTourStep.text }}</p>
        <ul v-if="currentTourStep.details" class="ux-detail-list">
          <li v-for="detail in currentTourStep.details" :key="detail">
            <i class="fas fa-check"></i>
            <span>{{ detail }}</span>
          </li>
        </ul>
        <div v-if="currentTourStep.modules" class="ux-module-list">
          <span v-for="module in currentTourStep.modules" :key="module">{{ module }}</span>
        </div>
        <div class="ux-actions ux-actions-between">
          <button class="ux-btn ux-btn-ghost" type="button" :disabled="tourStep === 0" @click="previousTourStep">
            <i class="fas fa-arrow-left"></i>
            Retour
          </button>
          <button v-if="tourStep < tourSteps.length - 1" class="ux-btn ux-btn-primary" type="button" @click="nextTourStep">
            Suivant
            <i class="fas fa-arrow-right"></i>
          </button>
          <button v-else class="ux-btn ux-btn-primary" type="button" @click="finishTour">
            Terminer
            <i class="fas fa-check"></i>
          </button>
        </div>
      </section>

      <section v-else-if="activeModal === 'communique' && currentCommunique" class="ux-card ux-info-card">
        <div class="ux-kicker">
          <span class="ux-kicker-icon ux-orange"><i class="fas fa-bullhorn"></i></span>
          Communiqué officiel
        </div>
        <h2>{{ currentCommunique.titre }}</h2>
        <div class="ux-meta-grid">
          <span><i class="fas fa-calendar"></i>{{ formatDateTime(currentCommunique.created_at) }}</span>
          <span><i class="fas fa-user-tie"></i>{{ currentCommunique.signataire || currentCommunique.auteur?.name || 'SEN' }}</span>
        </div>
        <div class="ux-message">{{ currentCommunique.contenu }}</div>
        <a v-if="currentCommunique.piece_jointe_url" class="ux-attachment" :href="currentCommunique.piece_jointe_url" target="_blank" rel="noopener">
          <i class="fas fa-paperclip"></i>
          Pièce jointe
        </a>
        <div class="ux-actions">
          <button class="ux-btn ux-btn-ghost" type="button" @click="openCommunique">
            <i class="fas fa-eye"></i>
            Voir le détail
          </button>
          <button class="ux-btn ux-btn-primary" type="button" :disabled="saving" @click="acknowledgeCommunique">
            <span v-if="saving" class="spinner-border spinner-border-sm"></span>
            <i v-else class="fas fa-check"></i>
            J'ai lu
          </button>
        </div>
      </section>

      <section v-else-if="activeModal === 'forum' && currentForum" class="ux-card ux-info-card">
        <div class="ux-kicker">
          <span class="ux-kicker-icon ux-green"><i class="fas fa-comments"></i></span>
          Forum lancé
        </div>
        <h2>{{ currentForum.titre || 'Discussion institutionnelle' }}</h2>
        <div class="ux-meta-grid">
          <span><i class="fas fa-calendar-plus"></i>{{ formatDateTime(currentForum.created_at) }}</span>
          <span><i class="fas fa-hourglass-half"></i>{{ forumPeriod(currentForum) }}</span>
          <span><i class="fas fa-user"></i>{{ currentForum.auteur?.name || 'Service initiateur' }}</span>
        </div>
        <div class="ux-message">{{ currentForum.contenu }}</div>
        <div class="ux-actions">
          <button class="ux-btn ux-btn-ghost" type="button" :disabled="saving" @click="dismissForum">
            Plus tard
          </button>
          <button class="ux-btn ux-btn-primary" type="button" :disabled="saving" @click="participateForum">
            <span v-if="saving" class="spinner-border spinner-border-sm"></span>
            <i v-else class="fas fa-arrow-right"></i>
            Participer au forum
          </button>
        </div>
      </section>
    </div>
  </teleport>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  bootstrapExperience,
  markCommuniqueRead,
  markForumRead,
  saveTour,
} from '@/api/userExperience'

const auth = useAuthStore()
const router = useRouter()

const activeModal = ref(null)
const tourStep = ref(0)
const tourState = ref(null)
const communiques = ref([])
const forums = ref([])
const saving = ref(false)
const loadedUserId = ref(null)

const roleModules = computed(() => {
  const modules = ['Demandes', 'Documents', 'Mes tâches', 'PTA', 'Forum']

  if (auth.canAdminPta) {
    modules.push('Adm PTA')
  }

  if (auth.hasAdminAccess) {
    modules.push('Administration')
  }

  return modules
})

const tourSteps = computed(() => [
  {
    icon: 'fa-gauge-high',
    title: 'Tableau de bord',
    text: 'Retrouvez les indicateurs, les alertes et les activités importantes dès votre arrivée.',
    details: [
      'Consultez les demandes à traiter, les tâches récentes et les échéances importantes.',
      'Les responsables voient les éléments de leur niveau: département, province, local ou national.',
    ],
  },
  {
    icon: 'fa-bars-staggered',
    title: 'Menu de navigation',
    text: 'Le menu donne un accès rapide aux espaces de travail, aux documents et aux outils de suivi.',
    details: [
      'Utilisez Accueil pour revenir au tableau de bord et les modules pour passer d’un espace à l’autre.',
      'Sur téléphone, ouvrez le menu puis choisissez directement l’action souhaitée.',
    ],
  },
  {
    icon: 'fa-layer-group',
    title: 'Modules accessibles',
    text: 'Les modules visibles sont adaptés à votre rôle et à votre rattachement dans la structure.',
    modules: roleModules.value,
  },
  {
    icon: 'fa-paper-plane',
    title: 'Demandes et validations',
    text: 'Les demandes suivent un circuit adapté à votre organe et à votre niveau hiérarchique.',
    details: [
      'Un agent suit sa demande depuis le module Demandes.',
      'Le valideur concerné retrouve les demandes dans son tableau de bord et dans la liste des demandes.',
      'Après validation, la demande passe automatiquement à l’étape suivante.',
    ],
  },
  {
    icon: 'fa-calendar-days',
    title: 'Présences et congés',
    text: 'Les congés et absences approuvées sont pris en compte pour éviter les pointages incohérents.',
    details: [
      'Vérifiez vos congés, absences et permissions depuis les modules dédiés.',
      'Pendant une absence justifiée, l’agent ne doit pas être pointé comme présent.',
    ],
  },
  {
    icon: 'fa-folder-open',
    title: 'Documents et communiqués',
    text: 'Les documents RH, communiqués et pièces importantes restent centralisés dans l’application.',
    details: [
      'Ouvrez les documents partagés et téléchargez les pièces jointes nécessaires.',
      'Lisez les communiqués affichés à la connexion pour rester aligné avec les instructions officielles.',
    ],
  },
  {
    icon: 'fa-bell',
    title: 'Notifications',
    text: 'Les notifications signalent les communiqués, les tâches, les validations et les messages importants.',
    details: [
      'Le badge rouge indique les nouveautés non lues.',
      'Cliquez sur une notification pour accéder directement à l’élément concerné.',
    ],
  },
  {
    icon: 'fa-list-check',
    title: 'Tâches et activités',
    text: 'Les tâches attribuées, les activités PTA et les demandes restent accessibles depuis leurs modules dédiés.',
    details: [
      'Mettez à jour l’avancement des tâches pour garder le suivi à jour.',
      'Les responsables peuvent consulter les performances et les retards de leur équipe.',
    ],
  },
  {
    icon: 'fa-address-card',
    title: 'Profil et contacts',
    text: 'Le profil agent et le carnet d’adresses facilitent la mise à jour des informations utiles.',
    details: [
      'Vérifiez votre photo, vos contacts, vos mails et votre poste dans le profil.',
      'Le carnet d’adresses permet de retrouver rapidement un agent selon son poste ou son nom.',
    ],
  },
  {
    icon: 'fa-circle-question',
    title: 'Assistance',
    text: 'Le bouton Aide permet de revoir ces explications quand vous en avez besoin.',
    details: [
      'En cas de blocage, notez le module concerné, l’action effectuée et le message affiché.',
      'Contactez les ressources humaines ou l’administrateur avec ces informations pour un traitement rapide.',
    ],
  },
])

const currentTourStep = computed(() => tourSteps.value[tourStep.value] || tourSteps.value[0])
const currentCommunique = computed(() => communiques.value[0] || null)
const currentForum = computed(() => forums.value[0] || null)

watch(
  () => auth.user?.id,
  (userId) => {
    if (!userId) {
      resetExperience()
      return
    }

    loadExperience(userId)
  },
  { immediate: true }
)

async function loadExperience(userId) {
  if (loadedUserId.value === userId) return

  loadedUserId.value = userId

  try {
    const { data } = await bootstrapExperience()
    const payload = data.data || data
    tourState.value = payload.guided_tour || null
    communiques.value = Array.isArray(payload.communiques) ? payload.communiques : []
    forums.value = Array.isArray(payload.forums) ? payload.forums : []
    await nextTick()
    chooseNextModal()
  } catch {
    resetExperience(false)
  }
}

function resetExperience(clearUser = true) {
  activeModal.value = null
  tourStep.value = 0
  tourState.value = null
  communiques.value = []
  forums.value = []
  saving.value = false

  if (clearUser) {
    loadedUserId.value = null
  }
}

function chooseNextModal() {
  if (activeModal.value) return

  if (tourState.value?.should_prompt) {
    activeModal.value = 'tour-prompt'
    return
  }

  if (communiques.value.length > 0) {
    activeModal.value = 'communique'
    return
  }

  if (forums.value.length > 0) {
    activeModal.value = 'forum'
  }
}

function startTour() {
  tourStep.value = 0
  activeModal.value = 'tour'
}

async function skipTour() {
  if (saving.value) return

  saving.value = true
  try {
    await saveTour('skipped')
    tourState.value = { ...(tourState.value || {}), should_prompt: false, skipped_at: new Date().toISOString() }
  } finally {
    saving.value = false
    activeModal.value = null
    await nextTick()
    chooseNextModal()
  }
}

function previousTourStep() {
  tourStep.value = Math.max(0, tourStep.value - 1)
}

function nextTourStep() {
  tourStep.value = Math.min(tourSteps.value.length - 1, tourStep.value + 1)
}

async function finishTour() {
  if (saving.value) return

  saving.value = true
  try {
    await saveTour('completed')
    tourState.value = { ...(tourState.value || {}), should_prompt: false, completed_at: new Date().toISOString() }
  } finally {
    saving.value = false
    activeModal.value = null
    tourStep.value = 0
    await nextTick()
    chooseNextModal()
  }
}

async function closeTour() {
  if (tourState.value?.should_prompt) {
    await skipTour()
    return
  }

  activeModal.value = null
  tourStep.value = 0
  await nextTick()
  chooseNextModal()
}

async function acknowledgeCommunique() {
  if (!currentCommunique.value || saving.value) return

  saving.value = true
  try {
    await markCommuniqueRead(currentCommunique.value.id)
    communiques.value.shift()
  } finally {
    saving.value = false
    activeModal.value = null
    await nextTick()
    chooseNextModal()
  }
}

async function openCommunique() {
  if (!currentCommunique.value) return

  const id = currentCommunique.value.id
  await acknowledgeCommunique()
  router.push({ name: 'communiques.show-public', params: { id } })
}

async function dismissForum() {
  if (!currentForum.value || saving.value) return

  saving.value = true
  try {
    await markForumRead(currentForum.value.id)
    forums.value.shift()
  } finally {
    saving.value = false
    activeModal.value = null
    await nextTick()
    chooseNextModal()
  }
}

async function participateForum() {
  if (!currentForum.value) return

  await dismissForum()
  router.push({ name: 'forum.index' })
}

function openGuideFromHelp() {
  if (!auth.isAuthenticated || activeModal.value) return

  tourStep.value = 0
  activeModal.value = 'tour'
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'

  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function forumPeriod(forum) {
  if (!forum?.expires_at) return 'Participation ouverte'

  return `Jusqu'au ${formatDateTime(forum.expires_at)}`
}

onMounted(() => {
  window.addEventListener('epnmls:open-user-guide', openGuideFromHelp)
})

onUnmounted(() => {
  window.removeEventListener('epnmls:open-user-guide', openGuideFromHelp)
})
</script>

<style scoped>
.ux-overlay {
  position: fixed;
  inset: 0;
  z-index: 12000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background:
    radial-gradient(circle at 20% 10%, rgba(14, 165, 233, .2), transparent 32%),
    rgba(15, 23, 42, .46);
  backdrop-filter: blur(14px) saturate(145%);
}

.ux-card {
  position: relative;
  width: min(100%, 640px);
  max-height: min(720px, 92vh);
  overflow: auto;
  border: 1px solid rgba(255, 255, 255, .55);
  border-radius: 22px;
  background: linear-gradient(145deg, rgba(255, 255, 255, .88), rgba(240, 249, 255, .68));
  box-shadow: 0 30px 90px rgba(15, 23, 42, .28);
  backdrop-filter: blur(24px) saturate(160%);
  padding: 1.35rem;
  color: #0f172a;
}

.ux-card-compact {
  max-width: 520px;
}

.ux-tour-card {
  max-width: 600px;
  text-align: center;
}

.ux-info-card {
  max-width: 680px;
}

.ux-close {
  position: absolute;
  top: .85rem;
  right: .85rem;
  width: 36px;
  height: 36px;
  border: 0;
  border-radius: 12px;
  color: #64748b;
  background: rgba(255, 255, 255, .62);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.ux-close:hover {
  color: #dc2626;
  background: rgba(254, 226, 226, .9);
}

.ux-kicker {
  display: inline-flex;
  align-items: center;
  gap: .55rem;
  margin-bottom: .9rem;
  color: #0369a1;
  font-size: .78rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0;
}

.ux-kicker-icon,
.ux-step-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  background: linear-gradient(135deg, #0ea5e9, #0f766e);
}

.ux-kicker-icon {
  width: 34px;
  height: 34px;
  border-radius: 12px;
}

.ux-kicker-icon.ux-orange {
  background: linear-gradient(135deg, #f97316, #dc2626);
}

.ux-kicker-icon.ux-green {
  background: linear-gradient(135deg, #22c55e, #0f766e);
}

.ux-step-icon {
  width: 68px;
  height: 68px;
  border-radius: 20px;
  margin: .65rem auto .8rem;
  font-size: 1.55rem;
}

.ux-card h2 {
  margin: 0 2rem .6rem 0;
  color: #0f172a;
  font-size: 1.25rem;
  font-weight: 850;
  line-height: 1.25;
}

.ux-tour-card h2 {
  margin-right: 0;
}

.ux-card p,
.ux-message {
  margin: 0;
  color: #475569;
  font-size: .94rem;
  line-height: 1.6;
}

.ux-message {
  max-height: 260px;
  overflow: auto;
  padding: 1rem;
  margin-top: .9rem;
  border: 1px solid rgba(148, 163, 184, .22);
  border-radius: 16px;
  background: rgba(255, 255, 255, .5);
  white-space: pre-wrap;
}

.ux-actions {
  display: flex;
  justify-content: flex-end;
  gap: .65rem;
  flex-wrap: wrap;
  margin-top: 1.15rem;
}

.ux-actions-between {
  justify-content: space-between;
}

.ux-btn {
  border: 0;
  border-radius: 12px;
  padding: .68rem .95rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .48rem;
  font-size: .86rem;
  font-weight: 800;
  min-height: 42px;
}

.ux-btn-primary {
  color: #fff;
  background: linear-gradient(135deg, #0077B5, #0f766e);
  box-shadow: 0 12px 30px rgba(0, 119, 181, .25);
}

.ux-btn-primary:hover {
  filter: brightness(.98);
  transform: translateY(-1px);
}

.ux-btn-ghost {
  color: #0f172a;
  background: rgba(255, 255, 255, .66);
  border: 1px solid rgba(148, 163, 184, .28);
}

.ux-btn:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.ux-progress {
  display: flex;
  justify-content: center;
  gap: .38rem;
  margin-bottom: .8rem;
}

.ux-progress-dot {
  width: 9px;
  height: 9px;
  border-radius: 999px;
  background: rgba(148, 163, 184, .55);
}

.ux-progress-dot.active,
.ux-progress-dot.done {
  background: #0077B5;
}

.ux-step-count {
  color: #64748b !important;
  font-size: .78rem !important;
  font-weight: 800;
  text-transform: uppercase;
}

.ux-module-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: .45rem;
  margin-top: .9rem;
}

.ux-module-list span {
  border-radius: 999px;
  padding: .35rem .7rem;
  color: #0f172a;
  background: rgba(255, 255, 255, .74);
  border: 1px solid rgba(148, 163, 184, .28);
  font-size: .78rem;
  font-weight: 800;
}

.ux-detail-list {
  display: grid;
  gap: .55rem;
  margin: .95rem 0 0;
  padding: 0;
  list-style: none;
  text-align: left;
}

.ux-detail-list li {
  display: grid;
  grid-template-columns: 1.35rem 1fr;
  align-items: start;
  gap: .55rem;
  padding: .65rem .75rem;
  border-radius: 14px;
  color: #334155;
  background: rgba(255, 255, 255, .64);
  border: 1px solid rgba(148, 163, 184, .24);
  font-size: .84rem;
  font-weight: 700;
  line-height: 1.35;
}

.ux-detail-list i {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.35rem;
  height: 1.35rem;
  border-radius: 999px;
  color: #047857;
  background: rgba(16, 185, 129, .14);
  font-size: .68rem;
}

.ux-meta-grid {
  display: flex;
  flex-wrap: wrap;
  gap: .55rem;
}

.ux-meta-grid span,
.ux-attachment {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  border-radius: 999px;
  padding: .42rem .7rem;
  color: #334155;
  background: rgba(255, 255, 255, .62);
  border: 1px solid rgba(148, 163, 184, .22);
  font-size: .78rem;
  font-weight: 700;
}

.ux-attachment {
  margin-top: .8rem;
  text-decoration: none;
}

@media (max-width: 576px) {
  .ux-overlay {
    align-items: flex-end;
    padding: .75rem;
  }

  .ux-card {
    border-radius: 18px;
    padding: 1.1rem;
  }

  .ux-card h2 {
    font-size: 1.08rem;
    margin-right: 1.8rem;
  }

  .ux-actions,
  .ux-actions-between {
    justify-content: stretch;
  }

  .ux-btn {
    flex: 1 1 100%;
  }
}
</style>
