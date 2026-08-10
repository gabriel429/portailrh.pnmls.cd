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
</style>