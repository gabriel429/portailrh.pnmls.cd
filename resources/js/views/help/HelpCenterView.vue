<template>
  <div class="help-center">
    <header class="help-header">
      <div>
        <span class="help-eyebrow">Centre d'aide</span>
        <h1>Comment pouvons-nous vous aider ?</h1>
        <p>Consultez les réponses courantes ou contactez directement la section Nouvelle Technologie.</p>
      </div>
      <i class="fas fa-life-ring" aria-hidden="true"></i>
    </header>

    <section class="help-shortcuts" aria-label="Raccourcis d'aide">
      <button type="button" class="help-shortcut" @click="openGuide">
        <span class="help-shortcut-icon blue"><i class="fas fa-book-open"></i></span>
        <span><strong>Guide utilisateur</strong><small>Découvrir les fonctions du portail</small></span>
        <i class="fas fa-chevron-right"></i>
      </button>
      <router-link class="help-shortcut" :to="{ name: 'technical-support', query: { new: '1' } }">
        <span class="help-shortcut-icon green"><i class="fas fa-comments"></i></span>
        <span><strong>Chat – Nouvelle Technologie</strong><small>Signaler un nouveau problème</small></span>
        <i class="fas fa-chevron-right"></i>
      </router-link>
      <router-link class="help-shortcut" :to="{ name: 'technical-support' }">
        <span class="help-shortcut-icon orange"><i class="fas fa-clipboard-list"></i></span>
        <span><strong>Mes demandes techniques</strong><small>Suivre les réponses et les statuts</small></span>
        <i class="fas fa-chevron-right"></i>
      </router-link>
    </section>

    <section class="faq-section">
      <div class="faq-heading">
        <div>
          <span class="help-eyebrow">Questions fréquentes</span>
          <h2>FAQ</h2>
        </div>
        <div class="faq-search">
          <i class="fas fa-search"></i>
          <input v-model.trim="search" type="search" placeholder="Rechercher dans la FAQ" aria-label="Rechercher dans la FAQ">
        </div>
      </div>

      <div v-if="filteredFaq.length" class="faq-list">
        <details v-for="item in filteredFaq" :key="item.question">
          <summary><span>{{ item.question }}</span><i class="fas fa-plus"></i></summary>
          <p>{{ item.answer }}</p>
        </details>
      </div>
      <div v-else class="faq-empty">Aucune réponse ne correspond à votre recherche.</div>
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const search = ref('')
const faq = [
  { question: 'Comment modifier mes informations de profil ?', answer: 'Ouvrez votre profil depuis le menu utilisateur, puis choisissez Modifier le profil. Les informations administratives protégées doivent être corrigées par la Section RH.' },
  { question: 'Pourquoi mon pointage ne s’enregistre-t-il pas ?', answer: 'Vérifiez votre connexion et l’autorisation de localisation du navigateur. Si le problème persiste, créez une demande technique en choisissant le module Pointage.' },
  { question: 'Comment demander un congé ?', answer: 'Dans Congés, consultez d’abord votre planning individuel puis utilisez l’action de demande. Les dates proposées doivent respecter le planning validé.' },
  { question: 'Où suivre mes tâches ?', answer: 'Le menu Mes tâches regroupe les nouvelles tâches, celles en cours et leur historique. Ouvrez une tâche pour ajouter une progression, un commentaire ou un rapport.' },
  { question: 'Puis-je joindre une capture à une demande technique ?', answer: 'Oui. Les images, PDF, documents bureautiques, fichiers texte et archives ZIP sont acceptés jusqu’à 10 Mo.' },
  { question: 'Comment savoir si un technicien a répondu ?', answer: 'Une notification apparaît dans le portail. Vous pouvez aussi ouvrir Mes demandes techniques pour consulter le fil complet et le statut.' },
]

const filteredFaq = computed(() => {
  const term = search.value.toLocaleLowerCase('fr')
  if (!term) return faq
  return faq.filter(item => `${item.question} ${item.answer}`.toLocaleLowerCase('fr').includes(term))
})

function openGuide() {
  window.dispatchEvent(new CustomEvent('epnmls:open-user-guide'))
}
</script>

<style scoped>
.help-center { max-width: 1180px; margin: 0 auto; padding: 28px 18px 60px; color: #172033; }
.help-header { min-height: 210px; padding: 42px 48px; display: flex; align-items: center; justify-content: space-between; gap: 24px; background: #083f5f; color: #fff; border-radius: 8px; position: relative; overflow: hidden; }
.help-header::after { content: ''; position: absolute; inset: 0; background: linear-gradient(110deg, transparent 55%, rgba(21, 150, 137, .38)); pointer-events: none; }
.help-header > * { position: relative; z-index: 1; }
.help-header > i { font-size: 5rem; color: #7dd3c7; }
.help-eyebrow { display: block; color: #0f8b7f; font-size: .75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0; margin-bottom: 6px; }
.help-header .help-eyebrow { color: #8ee4d8; }
.help-header h1 { margin: 0 0 10px; font-family: Georgia, 'Times New Roman', serif; font-size: clamp(1.8rem, 4vw, 3rem); }
.help-header p { margin: 0; max-width: 680px; color: #d8edf6; }
.help-shortcuts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin: 20px 0 34px; }
.help-shortcut { min-width: 0; min-height: 92px; display: grid; grid-template-columns: 48px minmax(0, 1fr) 18px; align-items: center; gap: 14px; padding: 16px; border: 1px solid #dbe4ea; border-radius: 8px; background: #fff; color: inherit; text-align: left; text-decoration: none; box-shadow: 0 8px 24px rgba(15, 49, 68, .06); }
.help-shortcut:hover { border-color: #56a99f; transform: translateY(-1px); }
.help-shortcut-icon { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 8px; }
.help-shortcut-icon.blue { color: #0369a1; background: #e0f2fe; }
.help-shortcut-icon.green { color: #087f72; background: #d9f5ef; }
.help-shortcut-icon.orange { color: #b45309; background: #fff1d6; }
.help-shortcut strong, .help-shortcut small { display: block; }
.help-shortcut strong { font-size: .94rem; }
.help-shortcut small { margin-top: 4px; color: #66788a; }
.help-shortcut > i { color: #9aa9b5; }
.faq-section { padding: 30px; background: #f7fafb; border: 1px solid #e2e9ed; border-radius: 8px; }
.faq-heading { display: flex; align-items: end; justify-content: space-between; gap: 18px; margin-bottom: 20px; }
.faq-heading h2 { margin: 0; font-family: Georgia, 'Times New Roman', serif; }
.faq-search { width: min(360px, 100%); display: flex; align-items: center; gap: 9px; padding: 0 13px; background: #fff; border: 1px solid #cedae1; border-radius: 6px; }
.faq-search input { width: 100%; height: 42px; border: 0; outline: 0; background: transparent; }
.faq-list { display: grid; gap: 9px; }
.faq-list details { background: #fff; border: 1px solid #dae3e8; border-radius: 6px; }
.faq-list summary { min-height: 54px; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 13px 16px; cursor: pointer; font-weight: 800; list-style: none; }
.faq-list summary::-webkit-details-marker { display: none; }
.faq-list details[open] summary i { transform: rotate(45deg); }
.faq-list p { margin: 0; padding: 0 16px 16px; color: #526779; line-height: 1.65; }
.faq-empty { padding: 30px; text-align: center; color: #6b7c8a; }
@media (max-width: 820px) { .help-shortcuts { grid-template-columns: 1fr; } .faq-heading { align-items: stretch; flex-direction: column; } .faq-search { width: 100%; } }
@media (max-width: 560px) { .help-center { padding: 14px 10px 40px; } .help-header { min-height: 180px; padding: 26px 22px; } .help-header > i { display: none; } .faq-section { padding: 18px 12px; } }

/* Modern help-center finish */
.help-center {
  --help-accent: #0b8f82;
  --help-ink: #142332;
  --help-muted: #667b8b;
  --help-line: #dce6eb;
  --help-surface: #fff;
  --help-soft: #f6f9fa;
  padding-top: 32px;
  color: var(--help-ink);
}
.help-header {
  min-height: 230px;
  padding: 44px 50px;
  background:
    linear-gradient(112deg, rgba(255, 255, 255, .04) 0 42%, transparent 42%),
    linear-gradient(135deg, #073b59 0%, #07506a 60%, #08796f 100%);
  border: 1px solid rgba(255, 255, 255, .12);
  border-radius: 8px;
  box-shadow: 0 20px 48px rgba(4, 48, 69, .2);
}
.help-header::after { background: linear-gradient(112deg, transparent 58%, rgba(79, 220, 200, .14)); }
.help-header > i { font-size: 4.7rem; color: #9aeee4; filter: drop-shadow(0 10px 20px rgba(0, 25, 36, .22)); }
.help-header h1 { font-family: inherit; font-size: 2.65rem; font-weight: 850; letter-spacing: 0; }
.help-header p { color: rgba(235, 249, 252, .82); font-size: .98rem; line-height: 1.6; }
.help-eyebrow { color: var(--help-accent); font-size: .7rem; letter-spacing: 0; }
.help-shortcuts { gap: 16px; margin: 22px 0 38px; }
.help-shortcut {
  min-height: 104px;
  padding: 18px;
  border-color: var(--help-line);
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(18, 53, 69, .055);
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.help-shortcut:hover { border-color: #9ccfca; transform: translateY(-3px); box-shadow: 0 16px 34px rgba(18, 53, 69, .11); }
.help-shortcut-icon { width: 50px; height: 50px; border-radius: 8px; font-size: 1.05rem; }
.help-shortcut strong { font-size: .97rem; font-weight: 800; }
.help-shortcut small { margin-top: 5px; color: var(--help-muted); line-height: 1.4; }
.help-shortcut > i { transition: transform .2s ease, color .2s ease; }
.help-shortcut:hover > i { color: var(--help-accent); transform: translateX(3px); }
.faq-section { padding: 32px; background: var(--help-soft); border-color: var(--help-line); border-radius: 8px; }
.faq-heading { margin-bottom: 22px; }
.faq-heading h2 { font-family: inherit; font-size: 1.65rem; font-weight: 850; }
.faq-search { min-height: 46px; border-color: #d4e0e6; border-radius: 7px; box-shadow: 0 3px 12px rgba(18, 53, 69, .035); transition: border-color .18s ease, box-shadow .18s ease; }
.faq-search:focus-within { border-color: var(--help-accent); box-shadow: 0 0 0 3px rgba(11, 143, 130, .12); }
.faq-search input { height: 44px; color: var(--help-ink); }
.faq-list { gap: 10px; }
.faq-list details { border-color: var(--help-line); border-radius: 8px; box-shadow: 0 4px 14px rgba(18, 53, 69, .035); overflow: hidden; transition: border-color .18s ease, box-shadow .18s ease; }
.faq-list details:hover, .faq-list details[open] { border-color: #b4d7d3; box-shadow: 0 8px 20px rgba(18, 53, 69, .07); }
.faq-list summary { min-height: 62px; padding: 15px 18px; font-size: .9rem; }
.faq-list summary i { width: 28px; height: 28px; flex: 0 0 28px; display: grid; place-items: center; color: var(--help-accent); background: #e5f5f2; border-radius: 50%; transition: transform .2s ease, background-color .2s ease; }
.faq-list details[open] summary i { background: #ccebe6; }
.faq-list p { padding: 0 18px 19px; color: #536a7a; }
.help-center :is(button, a, input, summary):focus-visible { outline: 3px solid rgba(14, 165, 233, .28); outline-offset: 2px; }

:global(html.dark .help-center) { --help-ink: #e6eef3; --help-muted: #9fb0bc; --help-line: rgba(255, 255, 255, .1); --help-surface: #172231; --help-soft: #111a27; }
:global(html.dark .help-shortcut), :global(html.dark .faq-list details), :global(html.dark .faq-search) { color: var(--help-ink); background: var(--help-surface); border-color: var(--help-line); }
:global(html.dark .faq-section) { background: var(--help-soft); border-color: var(--help-line); }
:global(html.dark .faq-search input) { color: var(--help-ink); }
:global(html.dark .faq-list p) { color: #b0c0ca; }
:global(html.dark .faq-list summary i) { color: #6ed9cd; background: #173b3b; }

@media (max-width: 820px) {
  .help-header { min-height: 205px; padding: 34px; }
  .help-header h1 { font-size: 2.1rem; }
}
@media (max-width: 560px) {
  .help-center { padding: 16px 10px 42px; }
  .help-header { min-height: 188px; padding: 26px 22px; }
  .help-header h1 { font-size: 1.75rem; }
  .help-shortcut { min-height: 92px; padding: 15px; }
  .faq-section { padding: 20px 14px; }
}
@media (prefers-reduced-motion: reduce) {
  .help-center *, .help-center *::before, .help-center *::after { transition-duration: .01ms !important; }
}
</style>