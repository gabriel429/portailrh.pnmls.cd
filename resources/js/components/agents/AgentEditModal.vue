<template>
  <div
    v-if="show"
    class="modal fade show"
    style="display: block; background: rgba(0,0,0,0.5);"
    @click="handleBackdropClick"
  >
    <div class="modal-dialog modal-xl modal-dialog-scrollable" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-user-edit me-2"></i>
            Modifier l'Agent
            <span v-if="agent" class="text-muted ms-2">{{ agent.prenom }} {{ agent.nom }}</span>
          </h5>
          <button
            type="button"
            class="btn-close"
            @click="$emit('close')"
            :disabled="submitting"
          ></button>
        </div>

        <div class="modal-body p-0">
          <!-- Loading -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Chargement de l'agent...</p>
          </div>

          <div v-else-if="agent" class="p-4">
            <!-- Validation errors -->
            <div v-if="Object.keys(errors).length > 0" class="alert alert-danger alert-dismissible fade show mb-3">
              <strong><i class="fas fa-exclamation-triangle me-1"></i> Erreurs de validation :</strong>
              <ul class="mb-0 mt-2">
                <li v-for="(msgs, field) in errors" :key="field">{{ Array.isArray(msgs) ? msgs[0] : msgs }}</li>
              </ul>
              <button type="button" class="btn-close" @click="errors = {}"></button>
            </div>

            <form @submit.prevent="submitForm">
              <!-- Photo de profil -->
              <div class="form-section">
                <div class="form-section-header">
                  <div class="form-section-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
                    <i class="fas fa-camera"></i>
                  </div>
                  <div>
                    <h6>Photo de profil</h6>
                    <small>Modifier la photo de l'agent</small>
                  </div>
                </div>
                <div class="row g-3 align-items-center">
                  <div class="col-auto">
                    <div v-if="agent.photo" class="position-relative">
                      <img :src="'/' + agent.photo" :alt="agent.prenom" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;">
                    </div>
                    <div v-else class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:80px;height:80px;">
                      <i class="fas fa-user fa-2x text-muted"></i>
                    </div>
                  </div>
                  <div class="col">
                    <input type="file" class="form-control form-control-sm" :class="{ 'is-invalid': errors.photo }" accept="image/*" @change="handlePhotoChange">
                    <div v-if="errors.photo" class="invalid-feedback">{{ errors.photo[0] }}</div>
                    <small class="form-text text-muted">Max 2 Mo. Formats: JPG, PNG, GIF</small>
                  </div>
                </div>
              </div>

              <!-- Section 1: Identité -->
              <div class="form-section">
                <div class="form-section-header">
                  <div class="form-section-icon" style="background:linear-gradient(135deg,#0077B5,#00a0dc)">
                    <i class="fas fa-id-card"></i>
                  </div>
                  <div>
                    <h6>Identité de l'agent</h6>
                    <small>Nom, prénom, post-nom et état civil</small>
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-medium">ID Agent</label>
                    <p class="form-control-plaintext fw-bold text-primary">{{ agent.id_agent }}</p>
                  </div>
                  <div class="col-md-6">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" :class="{ 'is-invalid': errors.nom }" id="nom" v-model="form.nom" required>
                    <div v-if="errors.nom" class="invalid-feedback">{{ errors.nom[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label for="postnom" class="form-label">Post-nom</label>
                    <input type="text" class="form-control" :class="{ 'is-invalid': errors.postnom }" id="postnom" v-model="form.postnom">
                    <div v-if="errors.postnom" class="invalid-feedback">{{ errors.postnom[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" :class="{ 'is-invalid': errors.prenom }" id="prenom" v-model="form.prenom" required>
                    <div v-if="errors.prenom" class="invalid-feedback">{{ errors.prenom[0] }}</div>
                  </div>
                  <div class="col-md-4">
                    <label for="sexe" class="form-label">Sexe <span class="text-danger">*</span></label>
                    <select class="form-select" :class="{ 'is-invalid': errors.sexe }" id="sexe" v-model="form.sexe" required>
                      <option value="M">Masculin</option>
                      <option value="F">Féminin</option>
                    </select>
                    <div v-if="errors.sexe" class="invalid-feedback">{{ errors.sexe[0] }}</div>
                  </div>
                  <div class="col-md-4">
                    <label for="annee_naissance" class="form-label">Année de naissance <span class="text-danger">*</span></label>
                    <input type="number" min="1945" max="2100" class="form-control" :class="{ 'is-invalid': errors.annee_naissance }" id="annee_naissance" v-model.number="form.annee_naissance" required>
                    <div v-if="errors.annee_naissance" class="invalid-feedback">{{ errors.annee_naissance[0] }}</div>
                  </div>
                  <div class="col-md-4">
                    <label for="date_naissance" class="form-label">Date de naissance</label>
                    <input type="date" class="form-control" :class="{ 'is-invalid': errors.date_naissance }" id="date_naissance" v-model="form.date_naissance">
                    <div v-if="errors.date_naissance" class="invalid-feedback">{{ errors.date_naissance[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label for="lieu_naissance" class="form-label">Lieu de naissance <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" :class="{ 'is-invalid': errors.lieu_naissance }" id="lieu_naissance" v-model="form.lieu_naissance" required>
                    <div v-if="errors.lieu_naissance" class="invalid-feedback">{{ errors.lieu_naissance[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label for="situation_familiale" class="form-label">Situation familiale</label>
                    <select class="form-select" id="situation_familiale" v-model="form.situation_familiale">
                      <option value="">-- Sélectionner --</option>
                      <option value="celibataire">Célibataire</option>
                      <option value="marie">Marié(e)</option>
                      <option value="divorce">Divorcé(e)</option>
                      <option value="veuf">Veuf/Veuve</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Section 2: Coordonnées -->
              <div class="form-section">
                <div class="form-section-header">
                  <div class="form-section-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="fas fa-address-book"></i>
                  </div>
                  <div>
                    <h6>Coordonnées</h6>
                    <small>E-mails, téléphone et adresse</small>
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="email_professionnel" class="form-label">E-mail institutionnel</label>
                    <input type="email" class="form-control" :class="{ 'is-invalid': errors.email_professionnel }" id="email_professionnel" v-model="form.email_professionnel">
                    <div v-if="errors.email_professionnel" class="invalid-feedback">{{ errors.email_professionnel[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label for="email_prive" class="form-label">E-mail privé</label>
                    <input type="email" class="form-control" :class="{ 'is-invalid': errors.email_prive }" id="email_prive" v-model="form.email_prive">
                    <div v-if="errors.email_prive" class="invalid-feedback">{{ errors.email_prive[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" :class="{ 'is-invalid': errors.telephone }" id="telephone" v-model="form.telephone">
                    <div v-if="errors.telephone" class="invalid-feedback">{{ errors.telephone[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label for="adresse" class="form-label">Adresse</label>
                    <input type="text" class="form-control" :class="{ 'is-invalid': errors.adresse }" id="adresse" v-model="form.adresse">
                    <div v-if="errors.adresse" class="invalid-feedback">{{ errors.adresse[0] }}</div>
                  </div>
                </div>
              </div>

              <!-- Section 3: Fonction et Organe -->
              <div class="form-section">
                <div class="form-section-header">
                  <div class="form-section-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                    <i class="fas fa-sitemap"></i>
                  </div>
                  <div>
                    <h6>Affectation & Fonction</h6>
                    <small>Organe, département et fonction</small>
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="organe" class="form-label">Organe <span class="text-danger">*</span></label>
                    <select class="form-select" :class="{ 'is-invalid': errors.organe }" id="organe" v-model="form.organe" required @change="syncPanels">
                      <option value="">-- Sélectionner un organe --</option>
                      <option v-for="o in formOptions.organeOptions" :key="o" :value="o">{{ o }}</option>
                    </select>
                    <div v-if="errors.organe" class="invalid-feedback">{{ errors.organe[0] }}</div>
                  </div>

                  <!-- Département pour SEN -->
                  <div v-if="currentNiveau === 'SEN'" class="col-md-6">
                    <label for="departement_id" class="form-label">Département</label>
                    <select class="form-select" :class="{ 'is-invalid': errors.departement_id }" id="departement_id" v-model="form.departement_id" @change="syncSection">
                      <option value="">-- Sélectionner un département --</option>
                      <option v-for="d in formOptions.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                    </select>
                    <div v-if="errors.departement_id" class="invalid-feedback">{{ errors.departement_id[0] }}</div>
                  </div>

                  <!-- Province pour SEP/SEL -->
                  <div v-if="currentNiveau === 'SEP' || currentNiveau === 'SEL'" class="col-md-6">
                    <label for="province_id" class="form-label">Province</label>
                    <select class="form-select" :class="{ 'is-invalid': errors.province_id }" id="province_id" v-model="form.province_id">
                      <option value="">-- Sélectionner une province --</option>
                      <option v-for="p in formOptions.provinces" :key="p.id" :value="p.id">{{ p.nom_province || p.nom }}</option>
                    </select>
                    <div v-if="errors.province_id" class="invalid-feedback">{{ errors.province_id[0] }}</div>
                  </div>

                  <div class="col-md-6">
                    <label for="fonction" class="form-label">Fonction <span class="text-danger">*</span></label>
                    <select class="form-select" :class="{ 'is-invalid': errors.fonction }" id="fonction" v-model="form.fonction" required>
                      <option value="">-- Sélectionner une fonction --</option>
                      <option v-for="f in visibleFonctions" :key="f.id" :value="f.nom">{{ f.nom }}</option>
                    </select>
                    <div v-if="errors.fonction" class="invalid-feedback">{{ errors.fonction[0] }}</div>
                  </div>

                  <div class="col-md-6">
                    <label for="niveau_etudes" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                    <select class="form-select" :class="{ 'is-invalid': errors.niveau_etudes }" id="niveau_etudes" v-model="form.niveau_etudes" required>
                      <option value="">-- Sélectionner --</option>
                      <option v-for="n in formOptions.niveauxEtudes" :key="n" :value="n">{{ n }}</option>
                    </select>
                    <div v-if="errors.niveau_etudes" class="invalid-feedback">{{ errors.niveau_etudes[0] }}</div>
                  </div>
                </div>
              </div>

              <!-- Section 4: Statut -->
              <div class="form-section">
                <div class="form-section-header">
                  <div class="form-section-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
                    <i class="fas fa-toggle-on"></i>
                  </div>
                  <div>
                    <h6>Statut</h6>
                    <small>Statut actuel de l'agent</small>
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                    <select class="form-select" :class="{ 'is-invalid': errors.statut }" id="statut" v-model="form.statut" required>
                      <option value="actif">Actif</option>
                      <option value="suspendu">Suspendu</option>
                      <option value="ancien">Ancien</option>
                    </select>
                    <div v-if="errors.statut" class="invalid-feedback">{{ errors.statut[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label for="annee_engagement_programme" class="form-label">Année d'engagement <span class="text-danger">*</span></label>
                    <input type="number" min="1950" max="2100" class="form-control" :class="{ 'is-invalid': errors.annee_engagement_programme }" id="annee_engagement_programme" v-model.number="form.annee_engagement_programme" required>
                    <div v-if="errors.annee_engagement_programme" class="invalid-feedback">{{ errors.annee_engagement_programme[0] }}</div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            @click="$emit('close')"
            :disabled="submitting"
          >
            Annuler
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="submitForm"
            :disabled="submitting || loading"
          >
            <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="fas fa-save me-2"></i>
            {{ submitting ? 'Enregistrement...' : 'Enregistrer les modifications' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { get, update, getFormOptions } from '@/api/agents'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  agentId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const ui = useUiStore()

// État
const loading = ref(false)
const submitting = ref(false)
const errors = ref({})
const agent = ref(null)
const photoFile = ref(null)
const typeRattachement = ref('')

const form = reactive({
  nom: '',
  prenom: '',
  postnom: '',
  sexe: '',
  annee_naissance: null,
  date_naissance: '',
  lieu_naissance: '',
  situation_familiale: '',
  nombre_enfants: null,
  email_professionnel: '',
  email_prive: '',
  telephone: '',
  adresse: '',
  matricule_etat: '',
  institution_id: '',
  grade_id: '',
  organe: '',
  departement_id: '',
  section_id: '',
  province_id: '',
  fonction: '',
  niveau_etudes: '',
  domaine_etudes: '',
  annee_engagement_programme: null,
  date_embauche: '',
  statut: 'actif',
})

const formOptions = reactive({
  organeOptions: [],
  departments: [],
  provinces: [],
  grades: [],
  institutionCategories: [],
  sections: [],
  fonctions: [],
  niveauxEtudes: [],
})

// Organe to niveau mapping
const organeToNiveau = {
  'secrétariat exécutif national': 'SEN',
  'secretariat executif national': 'SEN',
  'secrétariat exécutif provincial': 'SEP',
  'secretariat executif provincial': 'SEP',
  'secrétariat exécutif local': 'SEL',
  'secretariat executif local': 'SEL',
}

function normalizeStr(str) {
  return str.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
}

const currentNiveau = computed(() => {
  if (!form.organe) return ''
  const key = form.organe.trim().toLowerCase()
  if (organeToNiveau[key]) return organeToNiveau[key]
  const normalized = normalizeStr(form.organe)
  return organeToNiveau[normalized] || ''
})

const visibleFonctions = computed(() => {
  const niveau = currentNiveau.value
  if (!niveau) return formOptions.fonctions

  return formOptions.fonctions.filter(f => {
    return f.niveau_administratif === niveau || f.niveau_administratif === 'TOUS'
  })
})

// Méthodes
function handlePhotoChange(event) {
  photoFile.value = event.target.files[0] || null
}

function syncPanels() {
  const niveau = currentNiveau.value
  if (niveau !== 'SEN') {
    typeRattachement.value = ''
    form.departement_id = ''
    form.section_id = ''
  }
  if (niveau !== 'SEP' && niveau !== 'SEL') {
    form.province_id = ''
  }
  if (form.fonction && !visibleFonctions.value.find(f => f.nom === form.fonction)) {
    form.fonction = ''
  }
}

function syncSection() {
  form.section_id = ''
}

// Populate form from agent data
function populateForm(agentData) {
  form.nom = agentData.nom || ''
  form.prenom = agentData.prenom || ''
  form.postnom = agentData.postnom || ''
  form.sexe = agentData.sexe || ''
  form.annee_naissance = agentData.annee_naissance || null
  form.date_naissance = agentData.date_naissance ? agentData.date_naissance.substring(0, 10) : ''
  form.lieu_naissance = agentData.lieu_naissance || ''
  form.situation_familiale = agentData.situation_familiale || ''
  form.nombre_enfants = agentData.nombre_enfants
  form.email_professionnel = agentData.email_professionnel || ''
  form.email_prive = agentData.email_prive || ''
  form.telephone = agentData.telephone || ''
  form.adresse = agentData.adresse || ''
  form.matricule_etat = agentData.matricule_etat || ''
  form.institution_id = agentData.institution_id || ''
  form.grade_id = agentData.grade_id || ''
  form.organe = agentData.organe || ''
  form.departement_id = agentData.departement_id || ''
  form.section_id = agentData.section_id || ''
  form.province_id = agentData.province_id || ''
  form.fonction = agentData.fonction || ''
  form.niveau_etudes = agentData.niveau_etudes || ''
  form.domaine_etudes = agentData.domaine_etudes || ''
  form.annee_engagement_programme = agentData.annee_engagement_programme || null
  form.date_embauche = agentData.date_embauche ? agentData.date_embauche.substring(0, 10) : ''
  form.statut = agentData.statut || 'actif'

  // Déterminer le type de rattachement pour SEN
  const orgKey = (form.organe || '').trim().toLowerCase()
  const niveau = organeToNiveau[orgKey] || organeToNiveau[normalizeStr(form.organe || '')] || ''
  if (niveau === 'SEN') {
    typeRattachement.value = form.departement_id ? 'departement' : 'service_rattache'
  }
}

async function fetchData() {
  if (!props.agentId) return

  loading.value = true
  try {
    const [agentRes, optionsRes] = await Promise.all([
      get(props.agentId),
      getFormOptions(),
    ])

    agent.value = agentRes.data.agent
    formOptions.organeOptions = optionsRes.data.organeOptions || []
    formOptions.departments = optionsRes.data.departments || []
    formOptions.provinces = optionsRes.data.provinces || []
    formOptions.grades = optionsRes.data.grades || []
    formOptions.institutionCategories = optionsRes.data.institutionCategories || []
    formOptions.sections = optionsRes.data.sections || []
    formOptions.fonctions = optionsRes.data.fonctions || []
    formOptions.niveauxEtudes = optionsRes.data.niveauxEtudes || []

    populateForm(agent.value)
  } catch (err) {
    console.error('Error fetching agent:', err)
    ui.addToast('Erreur lors du chargement de l\'agent', 'danger')
    emit('close')
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  submitting.value = true
  errors.value = {}

  const formData = new FormData()

  for (const [key, value] of Object.entries(form)) {
    if (value !== '' && value !== null && value !== undefined) {
      formData.append(key, value)
    }
  }

  if (photoFile.value) {
    formData.append('photo', photoFile.value)
  }

  try {
    await update(props.agentId, formData)
    ui.addToast('Agent modifié avec succès', 'success')
    emit('updated')
    emit('close')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      console.error('Error updating agent:', err)
      ui.addToast('Erreur lors de la modification de l\'agent', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

function handleBackdropClick() {
  if (!submitting.value) {
    emit('close')
  }
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue && props.agentId) {
    fetchData()
  } else if (!newValue) {
    errors.value = {}
    photoFile.value = null
  }
})
</script>

<style scoped>
.modal-dialog {
  max-width: 1000px;
}

.modal-body {
  max-height: 70vh;
  overflow-y: auto;
}

.form-section {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-section-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e9ecef;
}

.form-section-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  color: #fff;
  flex-shrink: 0;
}

.form-section-header h6 {
  font-weight: 600;
  margin-bottom: 0;
  color: #333;
}

.form-section-header small {
  color: #6c757d;
}

.form-control-plaintext {
  padding: 0.375rem 0;
  border: none;
  background: transparent;
}

.btn {
  border-radius: 8px;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }

  .form-section {
    padding: 1rem;
  }

  .form-section-icon {
    width: 36px;
    height: 36px;
    font-size: 0.9rem;
  }

  .form-section-header h6 {
    font-size: 0.95rem;
  }
}
</style>