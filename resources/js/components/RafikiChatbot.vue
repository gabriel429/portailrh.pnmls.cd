<template>
  <teleport to="body">
    <div v-if="showRafiki" class="rafiki no-print" :class="{ 'rafiki-open': isOpen }">
      <transition name="rafiki-panel">
        <section v-if="isOpen" class="rafiki-panel" aria-label="Aide Rafiki">
          <header class="rafiki-header">
            <div class="rafiki-header-main">
              <RafikiMascot class="rafiki-avatar rafiki-avatar-panel" />
              <div>
                <p class="rafiki-kicker">Assistant d'aide</p>
                <h2>Rafiki</h2>
              </div>
            </div>
            <button class="rafiki-icon-btn" type="button" aria-label="Fermer Rafiki" @click="closeChat">
              <i class="fas fa-times"></i>
            </button>
          </header>

          <div class="rafiki-body">
            <div class="rafiki-message">
              <strong>Bonjour, je suis Rafiki.</strong>
              <span>Choisissez une question pour obtenir une réponse rapide sur E-PNMLS.</span>
            </div>

            <div class="rafiki-questions" role="list">
              <button
                v-for="(item, index) in questions"
                :key="item.question"
                class="rafiki-question"
                :class="{ active: activeIndex === index }"
                type="button"
                @click="selectQuestion(index)"
              >
                <span>{{ index + 1 }}</span>
                {{ item.question }}
              </button>
            </div>

            <article class="rafiki-answer" aria-live="polite">
              <p class="rafiki-answer-label">Réponse</p>
              <h3>{{ activeQuestion.question }}</h3>
              <p>{{ activeQuestion.answer }}</p>
              <button
                v-if="activeQuestion.route"
                class="rafiki-route"
                type="button"
                @click="goTo(activeQuestion.route)"
              >
                <i :class="['fas', activeQuestion.icon || 'fa-arrow-right']"></i>
                Ouvrir le module
              </button>
            </article>
          </div>
        </section>
      </transition>

      <button
        class="rafiki-launcher"
        :class="{ 'rafiki-tap': tapPulse }"
        type="button"
        aria-label="Ouvrir Rafiki"
        @click="toggleChat"
      >
        <span class="rafiki-status" aria-hidden="true"></span>
        <RafikiMascot class="rafiki-avatar" />
        <span class="rafiki-launcher-text">
          <strong>Rafiki</strong>
          <small>Aide</small>
        </span>
      </button>
    </div>
  </teleport>
</template>

<script setup>
import { computed, defineComponent, h, ref } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  show: {
    type: Boolean,
    default: true,
  },
})

const router = useRouter()
const isOpen = ref(false)
const activeIndex = ref(0)
const tapPulse = ref(false)
let pulseTimer = null

const questions = [
  {
    question: 'Comment consulter mon tableau de bord ?',
    answer: 'Après connexion, le tableau de bord affiche vos tâches, demandes, notifications et indicateurs selon votre rôle. Les responsables voient aussi les éléments de leur structure.',
    route: { name: 'dashboard' },
    icon: 'fa-gauge-high',
  },
  {
    question: 'Comment créer une demande ?',
    answer: 'Ouvrez le module Demandes, cliquez sur créer, renseignez le motif et les dates, puis envoyez. Le circuit de validation est orienté automatiquement selon votre niveau.',
    route: { name: 'requests.index' },
    icon: 'fa-paper-plane',
  },
  {
    question: 'Où suivre mes validations ?',
    answer: 'Les demandes et tâches en attente apparaissent dans vos notifications, votre tableau de bord et les listes dédiées. Ouvrez le détail pour voir l’étape actuelle.',
    route: { name: 'requests.index' },
    icon: 'fa-list-check',
  },
  {
    question: 'Comment fonctionne le pointage ?',
    answer: 'Le pointage se fait en deux temps : l’heure d’arrivée est enregistrée puis verrouillée, ensuite l’heure de départ est saisie et verrouillée. Une correction exige un profil habilité et un motif.',
    route: { name: 'rh.pointages.index' },
    icon: 'fa-clock',
  },
  {
    question: 'Pourquoi un agent ne peut pas être pointé ?',
    answer: 'Un agent en absence justifiée ou hors de votre périmètre ne peut pas être pointé. Vérifiez la date, la structure sélectionnée et les droits de votre profil.',
    route: { name: 'rh.pointages.index' },
    icon: 'fa-user-check',
  },
  {
    question: 'Comment voir mes notifications ?',
    answer: 'La cloche des notifications signale les nouveautés : demandes, affectations, documents, communiqués, tâches et actions RH. Ouvrez-les régulièrement pour ne rien manquer.',
    route: { name: 'notifications.index' },
    icon: 'fa-bell',
  },
  {
    question: 'Où trouver les documents officiels ?',
    answer: 'Les documents et communiqués sont centralisés dans les modules Documents et Communiqués. Les fichiers disponibles peuvent être consultés ou téléchargés selon vos droits.',
    route: { name: 'documents.index' },
    icon: 'fa-folder-open',
  },
  {
    question: 'Comment gérer mes tâches ?',
    answer: 'Dans Mes tâches, ouvrez une tâche pour consulter l’échéance, mettre à jour l’avancement, ajouter un commentaire ou transmettre les informations attendues.',
    route: { name: 'taches.index' },
    icon: 'fa-list-check',
  },
  {
    question: 'Comment utiliser la messagerie ?',
    answer: 'La messagerie permet de consulter et envoyer des mails liés au travail. Vérifiez aussi l’historique mail si vous devez retrouver un échange.',
    route: { name: 'mailbox.index' },
    icon: 'fa-at',
  },
  {
    question: 'Que faire si mon accès est incorrect ?',
    answer: 'Vérifiez d’abord votre profil agent et votre rôle. Si un module manque ou si une donnée semble incorrecte, contactez les RH ou l’administrateur avec la page concernée.',
    route: { name: 'profile.show' },
    icon: 'fa-user-gear',
  },
]

const showRafiki = computed(() => props.show)
const activeQuestion = computed(() => questions[activeIndex.value] || questions[0])

function toggleChat() {
  isOpen.value = !isOpen.value
  animateTap()
}

function closeChat() {
  isOpen.value = false
}

function selectQuestion(index) {
  activeIndex.value = index
  animateTap()
}

function animateTap() {
  tapPulse.value = false
  window.clearTimeout(pulseTimer)
  window.requestAnimationFrame(() => {
    tapPulse.value = true
    pulseTimer = window.setTimeout(() => {
      tapPulse.value = false
    }, 560)
  })
}

function goTo(route) {
  closeChat()
  router.push(route)
}

const RafikiMascot = defineComponent({
  name: 'RafikiMascot',
  setup() {
    return () => h('svg', {
      viewBox: '0 0 96 96',
      role: 'img',
      'aria-label': 'Rafiki, assistant E-PNMLS',
    }, [
      h('defs', [
        h('linearGradient', { id: 'rafikiHeadset', x1: '18', y1: '12', x2: '82', y2: '84', gradientUnits: 'userSpaceOnUse' }, [
          h('stop', { offset: '0', 'stop-color': '#0077B5' }),
          h('stop', { offset: '0.55', 'stop-color': '#0f766e' }),
          h('stop', { offset: '1', 'stop-color': '#d51920' }),
        ]),
      ]),
      h('circle', { cx: '48', cy: '49', r: '36', fill: '#fff7ed' }),
      h('circle', { cx: '23', cy: '45', r: '13', fill: '#8b5a2b' }),
      h('circle', { cx: '73', cy: '45', r: '13', fill: '#8b5a2b' }),
      h('circle', { cx: '23', cy: '45', r: '7', fill: '#f7c98f' }),
      h('circle', { cx: '73', cy: '45', r: '7', fill: '#f7c98f' }),
      h('path', { d: 'M21 50c0-21 13-35 27-35s27 14 27 35c0 18-11 31-27 31S21 68 21 50Z', fill: '#9b6635' }),
      h('path', { d: 'M31 60c0-13 8-22 17-22s17 9 17 22c0 11-7 18-17 18s-17-7-17-18Z', fill: '#f7c98f' }),
      h('path', { d: 'M34 37c4-11 24-11 28 0-5-3-23-3-28 0Z', fill: '#7a4a25' }),
      h('circle', { cx: '39', cy: '52', r: '3.4', fill: '#172033' }),
      h('circle', { cx: '57', cy: '52', r: '3.4', fill: '#172033' }),
      h('ellipse', { cx: '48', cy: '61', rx: '5.3', ry: '4.2', fill: '#7a4a25' }),
      h('path', { d: 'M40 69c4 4 12 4 16 0', fill: 'none', stroke: '#7a4a25', 'stroke-width': '3', 'stroke-linecap': 'round' }),
      h('path', { d: 'M18 48c0-18 12-32 30-32s30 14 30 32', fill: 'none', stroke: 'url(#rafikiHeadset)', 'stroke-width': '7', 'stroke-linecap': 'round' }),
      h('rect', { x: '14', y: '43', width: '10', height: '18', rx: '5', fill: '#0077B5' }),
      h('rect', { x: '72', y: '43', width: '10', height: '18', rx: '5', fill: '#d51920' }),
      h('path', { d: 'M76 58c0 10-8 16-19 16', fill: 'none', stroke: '#172033', 'stroke-width': '4', 'stroke-linecap': 'round' }),
      h('circle', { cx: '55', cy: '74', r: '3.2', fill: '#172033' }),
      h('path', { d: 'M47 24c-5-3-11-2-15 3 6-1 11-1 15-3Z', fill: '#ffef00' }),
      h('path', { d: 'M64 25c4-3 9-3 13 1-5 0-9 1-13-1Z', fill: '#00a1de' }),
      h('path', { d: 'M48 16c6 3 10 8 8 15-6-3-10-8-8-15Z', fill: '#d51920' }),
      h('path', { d: 'M37 28c6-8 16-9 23 0-4-2-18-2-23 0Z', fill: '#fff', opacity: '.34' }),
    ])
  },
})
</script>

<style scoped>
.rafiki {
  position: fixed;
  left: 24px;
  bottom: 24px;
  z-index: 1150;
  display: grid;
  justify-items: start;
  gap: .8rem;
  pointer-events: none;
}

.rafiki > * {
  pointer-events: auto;
}

.rafiki-launcher {
  position: relative;
  display: inline-grid;
  grid-template-columns: 48px auto;
  align-items: center;
  gap: .65rem;
  min-height: 62px;
  padding: .42rem .85rem .42rem .45rem;
  border: 1px solid rgba(255, 255, 255, .72);
  border-radius: 999px;
  color: #fff;
  background: linear-gradient(135deg, #0077B5 0%, #0f766e 54%, #d51920 100%);
  box-shadow: 0 18px 42px rgba(15, 23, 42, .24), 0 0 0 8px rgba(0, 119, 181, .08);
  cursor: pointer;
  overflow: visible;
  transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
}

.rafiki-launcher:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 52px rgba(15, 23, 42, .28), 0 0 0 8px rgba(213, 25, 32, .1);
}

.rafiki-launcher-text {
  display: grid;
  gap: .02rem;
  text-align: left;
  line-height: 1.05;
}

.rafiki-launcher-text strong {
  font-size: .9rem;
  font-weight: 900;
}

.rafiki-launcher-text small {
  color: rgba(255, 255, 255, .78);
  font-size: .68rem;
  font-weight: 800;
}

.rafiki-status {
  position: absolute;
  top: 4px;
  right: 10px;
  width: 11px;
  height: 11px;
  border-radius: 999px;
  background: #ffef00;
  border: 2px solid #fff;
  box-shadow: 0 0 0 0 rgba(255, 239, 0, .45);
  animation: rafikiStatus 2.2s ease-out infinite;
}

.rafiki-avatar {
  width: 48px;
  height: 48px;
  filter: drop-shadow(0 8px 14px rgba(15, 23, 42, .28));
  transform-origin: 50% 72%;
}

.rafiki-avatar-panel {
  width: 54px;
  height: 54px;
}

.rafiki-tap .rafiki-avatar,
.rafiki-open .rafiki-launcher .rafiki-avatar {
  animation: rafikiHello .55s cubic-bezier(.2, .9, .2, 1);
}

.rafiki-panel {
  width: min(380px, calc(100vw - 32px));
  max-height: min(620px, calc(100vh - 112px));
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, .68);
  border-radius: 22px;
  background: rgba(255, 255, 255, .94);
  color: #0f172a;
  box-shadow: 0 26px 78px rgba(15, 23, 42, .26);
  backdrop-filter: blur(18px) saturate(145%);
}

.rafiki-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: .9rem .95rem;
  color: #fff;
  background:
    linear-gradient(135deg, rgba(0, 119, 181, .98), rgba(15, 118, 110, .96) 58%, rgba(213, 25, 32, .96));
}

.rafiki-header-main {
  display: flex;
  align-items: center;
  min-width: 0;
  gap: .75rem;
}

.rafiki-kicker {
  margin: 0 0 .12rem;
  color: rgba(255, 255, 255, .78);
  font-size: .67rem;
  font-weight: 900;
  text-transform: uppercase;
}

.rafiki-header h2 {
  margin: 0;
  color: #fff;
  font-size: 1.2rem;
  font-weight: 950;
  line-height: 1;
}

.rafiki-icon-btn {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 12px;
  color: #fff;
  background: rgba(255, 255, 255, .18);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.rafiki-icon-btn:hover {
  background: rgba(255, 255, 255, .28);
}

.rafiki-body {
  display: grid;
  gap: .75rem;
  padding: .85rem;
  max-height: calc(min(620px, calc(100vh - 112px)) - 82px);
  overflow: auto;
}

.rafiki-message {
  display: grid;
  gap: .15rem;
  padding: .75rem .85rem;
  border: 1px solid rgba(0, 119, 181, .14);
  border-radius: 16px;
  background: #f8fbff;
  color: #334155;
  font-size: .82rem;
  line-height: 1.35;
}

.rafiki-message strong {
  color: #0f172a;
  font-size: .9rem;
}

.rafiki-questions {
  display: grid;
  gap: .4rem;
}

.rafiki-question {
  display: grid;
  grid-template-columns: 1.6rem 1fr;
  align-items: center;
  gap: .55rem;
  width: 100%;
  min-height: 42px;
  border: 1px solid rgba(148, 163, 184, .2);
  border-radius: 13px;
  padding: .52rem .62rem;
  color: #1f2937;
  background: #fff;
  text-align: left;
  font-size: .78rem;
  font-weight: 800;
  line-height: 1.22;
  transition: border-color .18s ease, background .18s ease, transform .18s ease;
}

.rafiki-question span {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.6rem;
  height: 1.6rem;
  border-radius: 999px;
  color: #0f766e;
  background: rgba(15, 118, 110, .1);
  font-size: .72rem;
}

.rafiki-question:hover,
.rafiki-question.active {
  border-color: rgba(0, 119, 181, .35);
  background: #eef8ff;
  transform: translateY(-1px);
}

.rafiki-answer {
  display: grid;
  gap: .42rem;
  padding: .85rem;
  border-radius: 17px;
  background: linear-gradient(180deg, #fff, #f8fafc);
  border: 1px solid rgba(15, 23, 42, .08);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, .85);
}

.rafiki-answer-label {
  margin: 0;
  color: #d51920;
  font-size: .67rem;
  font-weight: 950;
  text-transform: uppercase;
}

.rafiki-answer h3 {
  margin: 0;
  color: #0f172a;
  font-size: .94rem;
  font-weight: 900;
  line-height: 1.25;
}

.rafiki-answer p {
  margin: 0;
  color: #475569;
  font-size: .83rem;
  font-weight: 650;
  line-height: 1.48;
}

.rafiki-route {
  justify-self: start;
  display: inline-flex;
  align-items: center;
  gap: .42rem;
  margin-top: .15rem;
  min-height: 36px;
  border: 0;
  border-radius: 12px;
  padding: .48rem .7rem;
  color: #fff;
  background: linear-gradient(135deg, #0077B5, #0f766e);
  font-size: .76rem;
  font-weight: 900;
}

.rafiki-panel-enter-active,
.rafiki-panel-leave-active {
  transition: opacity .18s ease, transform .2s cubic-bezier(.2, .9, .2, 1);
}

.rafiki-panel-enter-from,
.rafiki-panel-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(.97);
}

@keyframes rafikiHello {
  0% { transform: rotate(0) translateY(0) scale(1); }
  35% { transform: rotate(-7deg) translateY(-3px) scale(1.04); }
  68% { transform: rotate(5deg) translateY(0) scale(1); }
  100% { transform: rotate(0) translateY(0) scale(1); }
}

@keyframes rafikiStatus {
  0% { box-shadow: 0 0 0 0 rgba(255, 239, 0, .48); }
  80%, 100% { box-shadow: 0 0 0 10px rgba(255, 239, 0, 0); }
}

@media (max-width: 576px) {
  .rafiki {
    left: 14px;
    bottom: 14px;
  }

  .rafiki-launcher {
    grid-template-columns: 44px;
    padding: .42rem;
    min-height: 54px;
  }

  .rafiki-launcher-text {
    display: none;
  }

  .rafiki-avatar {
    width: 44px;
    height: 44px;
  }

  .rafiki-panel {
    width: calc(100vw - 28px);
    max-height: calc(100vh - 92px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .rafiki-launcher,
  .rafiki-question,
  .rafiki-panel-enter-active,
  .rafiki-panel-leave-active,
  .rafiki-status,
  .rafiki-tap .rafiki-avatar,
  .rafiki-open .rafiki-launcher .rafiki-avatar {
    animation: none !important;
    transition: none !important;
  }
}
</style>
