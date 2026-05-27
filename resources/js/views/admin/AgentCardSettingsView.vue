<template>
  <div class="agent-card-settings">
    <div class="page-head">
      <div>
        <span class="eyebrow"><i class="fas fa-id-card me-2"></i>Cartes agents</span>
        <h1>Modele de carte d'identite</h1>
        <p>Parametrez les informations fixes imprimees sur les cartes professionnelles PNMLS.</p>
      </div>
      <div class="head-actions">
        <router-link class="soft-btn" :to="{ name: 'agent-cards.scan' }">
          <i class="fas fa-qrcode"></i>
          Scanner
        </router-link>
        <router-link class="primary-btn" :to="{ name: 'rh.agents.index' }">
          <i class="fas fa-users"></i>
          Choisir un agent
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner-border text-primary"></div>
      <span>Chargement du modele...</span>
    </div>

    <form v-else class="settings-grid" @submit.prevent="save">
      <section class="settings-panel">
        <div class="panel-title">
          <i class="fas fa-pen-ruler"></i>
          <span>Informations officielles</span>
        </div>

        <div class="form-grid">
          <label>
            <span>Pays</span>
            <input v-model.trim="form.country" type="text">
          </label>
          <label>
            <span>Ministere</span>
            <input v-model.trim="form.ministry" type="text">
          </label>
          <label class="wide">
            <span>Programme</span>
            <input v-model.trim="form.program_name" type="text">
          </label>
          <label>
            <span>Titre de la carte</span>
            <input v-model.trim="form.card_title" type="text">
          </label>
          <label>
            <span>Sous-titre</span>
            <input v-model.trim="form.subtitle" type="text">
          </label>
          <label>
            <span>Titre signataire</span>
            <input v-model.trim="form.authority_title" type="text">
          </label>
          <label>
            <span>Nom signataire</span>
            <input v-model.trim="form.signature_name" type="text">
          </label>
          <label class="wide">
            <span>Ligne contact</span>
            <input v-model.trim="form.contact_line" type="text">
          </label>
          <label class="wide">
            <span>Note verso</span>
            <textarea v-model.trim="form.footer_note" rows="3"></textarea>
          </label>
        </div>

        <div class="panel-title mt">
          <i class="fas fa-palette"></i>
          <span>Logo et couleurs</span>
        </div>

        <div class="form-grid compact">
          <label>
            <span>Couleur principale</span>
            <input v-model="form.primary_color" type="color">
          </label>
          <label>
            <span>Couleur accent</span>
            <input v-model="form.accent_color" type="color">
          </label>
          <label>
            <span>Logo principal</span>
            <input type="file" accept="image/*" @change="onFile($event, 'logo_primary')">
          </label>
          <label>
            <span>Logo secondaire</span>
            <input type="file" accept="image/*" @change="onFile($event, 'logo_secondary')">
          </label>
        </div>

        <button class="save-btn" type="submit" :disabled="saving">
          <span v-if="saving" class="spinner-border spinner-border-sm"></span>
          <i v-else class="fas fa-save"></i>
          Enregistrer le modele
        </button>
      </section>

      <section class="preview-panel">
        <div class="panel-title">
          <i class="fas fa-eye"></i>
          <span>Apercu recto / verso</span>
        </div>

        <div class="card-preview-stack">
          <article class="identity-card recto" :style="cardStyle">
            <img v-if="watermarkLogo" class="card-watermark" :src="watermarkLogo" alt="" aria-hidden="true">
            <header>
              <img v-if="primaryLogo" :src="primaryLogo" alt="Logo principal">
              <div>
                <strong>{{ form.country }}</strong>
                <span>{{ form.ministry }}</span>
                <span>{{ form.program_name }}</span>
              </div>
              <img v-if="secondaryLogo" :src="secondaryLogo" alt="Logo secondaire">
            </header>

            <div class="official-stripe" aria-hidden="true">
              <span></span><span></span><span></span>
            </div>

            <div class="card-title">
              <strong>{{ form.card_title }}</strong>
            </div>
            <div class="card-body">
              <div class="photo">AK</div>
              <div class="agent-lines">
                <h2>AMISI KASONGO</h2>
                <p>Chef de bureau</p>
                <dl>
                  <div><dt>Matricule</dt><dd>PNMLS-2026-001</dd></div>
                  <div><dt>Organe</dt><dd>SEN</dd></div>
                  <div><dt>Province</dt><dd>Kinshasa</dd></div>
                  <div class="address-line"><dt>Adresse</dt><dd>{{ professionalAddress }}</dd></div>
                  <div><dt>Validite</dt><dd>25/05/2026 - 25/05/2027</dd></div>
                </dl>
              </div>
            </div>
            <footer>PNMLS-CI-2026-0001-ABCDE</footer>
          </article>

          <article class="identity-card verso" :style="cardStyle">
            <header>
              <div>
                <strong>{{ form.subtitle }}</strong>
                <span>Verification numerique integree</span>
              </div>
            </header>
            <div class="official-stripe" aria-hidden="true">
              <span></span><span></span><span></span>
            </div>
            <div class="back-card-title">
              <strong>{{ form.card_title }}</strong>
            </div>
            <div class="verso-body">
              <div class="qr-placeholder"><i class="fas fa-qrcode"></i></div>
              <div>
                <h3>{{ form.authority_title }}</h3>
                <p>{{ form.signature_name || 'Nom du signataire' }}</p>
                <small>{{ form.contact_line }}</small>
              </div>
            </div>
            <footer>{{ form.footer_note }}</footer>
          </article>
        </div>
      </section>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { getAgentCardSettings, updateAgentCardSettings } from '@/api/agentCards'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
const loading = ref(true)
const saving = ref(false)
const files = reactive({
  logo_primary: null,
  logo_secondary: null,
})
const professionalAddress = 'croisement boulevard triomphal/avenue de la liberation commune de Kasa-Vubu'

const form = reactive({
  country: '',
  ministry: '',
  program_name: '',
  card_title: '',
  subtitle: '',
  authority_title: '',
  signature_name: '',
  contact_line: '',
  footer_note: '',
  primary_color: '#0077B5',
  accent_color: '#f6c343',
  logo_primary_url: '',
  logo_secondary_url: '',
})

const cardStyle = computed(() => ({
  '--primary': form.primary_color || '#0077B5',
  '--accent': form.accent_color || '#f6c343',
}))

const primaryLogo = computed(() => files.logo_primary ? URL.createObjectURL(files.logo_primary) : form.logo_primary_url)
const secondaryLogo = computed(() => files.logo_secondary ? URL.createObjectURL(files.logo_secondary) : form.logo_secondary_url)
const watermarkLogo = computed(() => secondaryLogo.value || primaryLogo.value || '/images/logo-pnmls.png')

function assignSettings(settings = {}) {
  Object.keys(form).forEach((key) => {
    if (settings[key] !== undefined && settings[key] !== null) form[key] = settings[key]
  })
}

function onFile(event, key) {
  files[key] = event.target.files?.[0] || null
}

async function load() {
  loading.value = true
  try {
    const { data } = await getAgentCardSettings()
    assignSettings(data.data || data)
  } catch (error) {
    ui.addToast(error.response?.data?.message || 'Impossible de charger le modele de carte.', 'danger')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  const payload = new FormData()
  Object.entries(form).forEach(([key, value]) => {
    if (!key.endsWith('_url')) payload.append(key, value || '')
  })
  Object.entries(files).forEach(([key, file]) => {
    if (file) payload.append(key, file)
  })

  try {
    const { data } = await updateAgentCardSettings(payload)
    assignSettings(data.data || data)
    files.logo_primary = null
    files.logo_secondary = null
    ui.addToast(data.message || 'Modele de carte enregistre.', 'success')
  } catch (error) {
    ui.addToast(error.response?.data?.message || 'Erreur lors de l enregistrement.', 'danger')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.agent-card-settings {
  color: #172033;
}

.page-head {
  align-items: center;
  background: linear-gradient(135deg, #0077b5 0%, #0f8aa9 100%);
  border-radius: 16px;
  box-shadow: 0 18px 45px rgba(0, 119, 181, .18);
  color: #fff;
  display: flex;
  gap: 1.5rem;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding: 1.5rem;
}

.eyebrow {
  color: rgba(255, 255, 255, .82);
  display: block;
  font-size: .78rem;
  font-weight: 800;
  letter-spacing: .08em;
  margin-bottom: .45rem;
  text-transform: uppercase;
}

.page-head h1 {
  font-size: clamp(1.5rem, 3vw, 2.2rem);
  font-weight: 900;
  margin: 0;
}

.page-head p {
  color: rgba(255, 255, 255, .78);
  margin: .35rem 0 0;
}

.head-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .65rem;
}

.primary-btn,
.soft-btn,
.save-btn {
  align-items: center;
  border: 0;
  border-radius: 12px;
  display: inline-flex;
  font-weight: 800;
  gap: .5rem;
  justify-content: center;
  min-height: 42px;
  padding: .7rem 1rem;
  text-decoration: none;
}

.primary-btn {
  background: #fff;
  color: #00679c;
}

.soft-btn {
  background: rgba(255, 255, 255, .15);
  color: #fff;
}

.loading-state {
  align-items: center;
  display: flex;
  gap: .75rem;
  justify-content: center;
  min-height: 260px;
}

.settings-grid {
  display: grid;
  gap: 1.25rem;
  grid-template-columns: minmax(0, 1.05fr) minmax(360px, .95fr);
}

.settings-panel,
.preview-panel {
  background: rgba(255, 255, 255, .92);
  border: 1px solid rgba(148, 163, 184, .22);
  border-radius: 16px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
  padding: 1.25rem;
}

.panel-title {
  align-items: center;
  color: #0f3552;
  display: flex;
  font-size: .95rem;
  font-weight: 900;
  gap: .55rem;
  margin-bottom: 1rem;
}

.panel-title.mt {
  margin-top: 1.35rem;
}

.form-grid {
  display: grid;
  gap: .85rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.form-grid.compact {
  align-items: end;
}

.form-grid label {
  display: grid;
  gap: .35rem;
}

.form-grid label.wide {
  grid-column: 1 / -1;
}

.form-grid span {
  color: #526176;
  font-size: .78rem;
  font-weight: 800;
}

.form-grid input,
.form-grid textarea {
  background: #f8fbfd;
  border: 1px solid #d8e3eb;
  border-radius: 10px;
  color: #172033;
  min-height: 42px;
  padding: .65rem .75rem;
  width: 100%;
}

.form-grid input[type="color"] {
  padding: .25rem;
}

.save-btn {
  background: #0077b5;
  color: #fff;
  margin-top: 1.25rem;
  width: 100%;
}

.save-btn:disabled {
  opacity: .7;
}

.card-preview-stack {
  display: grid;
  gap: 1rem;
  justify-items: center;
}

.identity-card {
  background:
    linear-gradient(116deg, transparent 0 69%, rgba(0, 119, 181, .08) 69.2%, rgba(0, 119, 181, .02) 100%),
    radial-gradient(circle at 78% 70%, rgba(0, 119, 181, .18), transparent 105px),
    radial-gradient(circle at 50% 56%, rgba(246, 195, 67, .1), transparent 118px),
    linear-gradient(180deg, #ffffff 0%, #f8fcff 100%);
  border: 1px solid rgba(15, 53, 82, .16);
  border-radius: 14px;
  box-shadow: 0 16px 34px rgba(15, 23, 42, .14);
  height: 255px;
  overflow: hidden;
  padding: 14px;
  position: relative;
  width: 404px;
}

.identity-card::before {
  background:
    linear-gradient(90deg, rgba(0, 119, 181, .08), rgba(255, 255, 255, 0) 55%),
    linear-gradient(180deg, rgba(255, 255, 255, .95), rgba(248, 252, 255, .82));
  content: "";
  inset: 0;
  position: absolute;
  z-index: 0;
}

.identity-card.verso {
  background: #f8fcff;
}

.identity-card.verso::before {
  background: url('/images/pnmls.jpeg') center / cover no-repeat;
  filter: blur(3px) saturate(.86) contrast(1.04);
  inset: -7px;
  opacity: .42;
  transform: scale(1.04);
}

.identity-card::after {
  background: linear-gradient(135deg, rgba(0, 119, 181, .06), rgba(0, 119, 181, .24));
  clip-path: polygon(62% 0, 100% 0, 100% 100%, 24% 100%);
  content: "";
  height: 100%;
  opacity: .72;
  position: absolute;
  right: 0;
  top: 0;
  width: 132px;
  z-index: 0;
}

.identity-card.verso::after {
  background:
    radial-gradient(circle at 76% 70%, rgba(0, 119, 181, .18), transparent 116px),
    linear-gradient(116deg, rgba(255, 255, 255, .9) 0 58%, rgba(235, 247, 255, .72) 58.2% 100%),
    linear-gradient(180deg, rgba(255, 255, 255, .8), rgba(248, 252, 255, .9));
  clip-path: none;
  left: 0;
  width: 100%;
}

.card-watermark {
  filter: blur(2px) saturate(1.05);
  height: 210px;
  left: 68%;
  mix-blend-mode: multiply;
  object-fit: contain;
  opacity: .18;
  pointer-events: none;
  position: absolute;
  top: 58%;
  transform: translate(-50%, -50%) rotate(-10deg);
  width: 210px;
  z-index: 0;
}

.identity-card > *:not(.card-watermark) {
  position: relative;
  z-index: 1;
}

.identity-card header {
  align-items: center;
  color: #172033;
  display: grid;
  gap: .5rem;
  grid-template-columns: 42px 1fr 42px;
  min-height: 40px;
  text-align: center;
}

.identity-card header img {
  background: rgba(255, 255, 255, .82);
  border: 1px solid rgba(148, 163, 184, .32);
  border-radius: 50%;
  filter: drop-shadow(0 2px 4px rgba(15, 23, 42, .12));
  height: 36px;
  mix-blend-mode: normal;
  object-fit: contain;
  padding: 3px;
  width: 42px;
}

.identity-card header strong {
  color: #82b8ee;
  display: block;
  font-size: .68rem;
  line-height: 1.05;
}

.identity-card header span {
  color: #172033;
  display: block;
  font-size: .54rem;
  font-weight: 800;
  line-height: 1.08;
}

.official-stripe {
  display: grid;
  gap: 2px;
  grid-template-columns: 1fr 1fr 1fr;
  height: 4px;
  margin: 6px 0 0;
}

.official-stripe span:nth-child(1) {
  background: #c8333a;
}

.official-stripe span:nth-child(2) {
  background: #f6c343;
}

.official-stripe span:nth-child(3) {
  background: var(--primary);
}

.card-title {
  align-items: center;
  color: #7db8f2;
  display: flex;
  justify-content: center;
  margin: 6px auto 5px;
  max-width: 270px;
  min-height: 28px;
  text-align: center;
}

.card-title strong,
.back-card-title strong {
  display: block;
  font-size: .95rem;
  font-weight: 950;
  letter-spacing: .04rem;
  line-height: 1.05;
  text-transform: uppercase;
}

.card-body {
  display: grid;
  gap: .75rem;
  grid-template-columns: 96px 1fr;
}

.photo {
  align-items: center;
  background: #e9f5fb;
  border: 3px solid #fff;
  border-radius: 14px;
  color: var(--primary);
  display: flex;
  font-size: 1.8rem;
  font-weight: 900;
  height: 104px;
  justify-content: center;
  width: 96px;
}

.agent-lines h2 {
  color: #0f3552;
  font-size: 1.25rem;
  font-weight: 900;
  margin: 0;
}

.agent-lines p {
  color: #526176;
  font-size: .85rem;
  font-weight: 800;
  margin: .1rem 0 .35rem;
}

.agent-lines dl {
  display: grid;
  gap: .18rem;
  margin: 0;
}

.agent-lines dl div {
  display: grid;
  grid-template-columns: 88px 1fr;
}

.agent-lines .address-line dd {
  font-size: .6rem;
  line-height: 1.15;
}

.agent-lines dt {
  color: #6b778c;
  font-size: .64rem;
  font-weight: 800;
}

.agent-lines dd {
  color: #172033;
  font-size: .68rem;
  font-weight: 900;
  margin: 0;
}

.identity-card footer {
  bottom: 10px;
  color: #6b778c;
  font-size: .54rem;
  font-weight: 800;
  left: 14px;
  position: absolute;
  right: 14px;
}

.verso header {
  grid-template-columns: 1fr;
  text-align: left;
}

.verso-body {
  align-items: center;
  display: grid;
  gap: 1rem;
  grid-template-columns: 96px 1fr;
  margin-top: 8px;
}

.back-card-title {
  color: #7db8f2;
  margin: 16px auto 6px;
  max-width: 310px;
  text-align: center;
}

.qr-placeholder {
  align-items: center;
  background: #f4f8fb;
  border: 1px dashed #b5c8d6;
  border-radius: 14px;
  color: var(--primary);
  display: flex;
  font-size: 3rem;
  height: 96px;
  justify-content: center;
  width: 96px;
}

.verso h3 {
  color: #0f3552;
  font-size: .88rem;
  font-weight: 900;
  margin: 0 0 .35rem;
}

.verso p {
  color: #172033;
  font-weight: 800;
  margin: 0 0 .35rem;
}

.verso small {
  color: #526176;
  font-weight: 700;
}

@media (max-width: 992px) {
  .page-head,
  .settings-grid {
    grid-template-columns: 1fr;
  }

  .settings-grid {
    display: grid;
  }
}

@media (max-width: 560px) {
  .page-head {
    align-items: flex-start;
    flex-direction: column;
    padding: 1.1rem;
  }

  .head-actions,
  .primary-btn,
  .soft-btn {
    width: 100%;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .identity-card {
    transform: scale(.82);
    transform-origin: top center;
    margin-bottom: -42px;
  }
}
</style>
