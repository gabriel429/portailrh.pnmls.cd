<template>
  <div
    v-if="show"
    class="modal fade show"
    style="display: block; background: rgba(0,0,0,0.5); z-index: 2000 !important;"
    @click="handleBackdropClick"
  >
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="z-index: 2001 !important;" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-user-plus me-2"></i>
            Ajouter un Agent
          </h5>
          <button
            type="button"
            class="btn-close"
            @click="$emit('close')"
            :disabled="submitting"
          ></button>
        </div>

        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Chargement des options...</p>
          </div>

          <div v-else class="p-3">
            <div v-if="Object.keys(errors).length > 0" class="alert alert-danger alert-dismissible fade show mb-3">
              <strong><i class="fas fa-exclamation-triangle me-1"></i> Erreurs de validation :</strong>
              <ul class="mb-0 mt-2">
                <li v-for="(msgs, field) in errors" :key="field">{{ Array.isArray(msgs) ? msgs[0] : msgs }}</li>
              </ul>
              <button type="button" class="btn-close" @click="errors = {}"></button>
            </div>

            <form @submit.prevent="submitForm">
              <!-- Photo -->
              <div class="form-section mb-3">
                <div class="form-section-header">
                  <h6><i class="fas fa-camera me-2"></i>Photo de profil</h6>
                </div>
                <input type="file" class="form-control form-control-sm" :class="{ 'is-invalid': errors.photo }" accept="image/*" @change="handlePhotoChange">
                <div v-if="errors.photo" class="invalid-feedback">{{ errors.photo[0] }}</div>
                <small class="text-muted">Max 2 Mo. JPG, PNG, GIF</small>
              </div>

              <!-- Identite -->
              <div class="form-section mb-3">
                <div class="form-section-header">
                  <h6><i class="fas fa-id-card me-2"></i>Identite</h6>
                </div>
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label fw-medium">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': errors.nom }" v-model="form.nom" required>
                    <div v-if="errors.nom" class="invalid-feedback">{{ errors.nom[0] }}</div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-medium">Post-nom</label>
                    <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': errors.postnom }" v-model="form.postnom">
                    <div v-if="errors.postnom" class="invalid-feedback">{{ errors.postnom[0] }}</div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-medium">Prenom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': errors.prenom }" v-model="form.prenom" required>
                    <div v-if="errors.prenom" class="invalid-feedback">{{ errors.prenom[0] }}</div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-medium">Sexe <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" :class="{ 'is-invalid': errors.sexe }" v-model="form.sexe" required>
                      <option value="">--</option>
                      <option value="M">Masculin</option>
                      <option value="F">Feminin</option>
                    </select>
                    <div v-if="errors.sexe" class="invalid-feedback">{{ errors.sexe[0] }}</div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-medium">Annee naissance <span class="text-danger">*</span></label>
                    <input type="number" min="1945" max="2100" class="form-control form-control-sm" :class="{ 'is-invalid': errors.annee_naissance }" v-model.number="form.annee_naissance" required>
                    <div v-if="errors.annee_naissance" class="invalid-feedback">{{ errors.annee_naissance[0] }}</div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-medium">Date naissance</label>
                    <input type="date" class="form-control form-control-sm" :class="{ 'is-invalid': errors.date_naissance }" v-model="form.date_naissance">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-medium">Lieu naissance <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': errors.lieu_naissance }" v-model="form.lieu_naissance" required>
                    <div v-if="errors.lieu_naissance" class="invalid-feedback">{{ errors.lieu_naissance[0] }}</div>
                  </div>
                </div>
              </div>

              <!-- Coordonnees -->
              <div class="form-section mb-3">
                <div class="form-section-header">
                  <h6><i class="fas fa-address-book me-2"></i>Coordonnees</h6>
                </div>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Email institutionnel</label>
                    <input type="email" class="form-control form-control-sm" :class="{ 'is-invalid': errors.email_professionnel }" v-model="form.email_professionnel">
                    <div v-if="errors.email_professionnel" class="invalid-feedback">{{ errors.email_professionnel[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Telephone</label>
                    <input type="tel" class="form-control form-control-sm" :class="{ 'is-invalid': errors.telephone }" v-model="form.telephone">
                    <div v-if="errors.telephone" class="invalid-feedback">{{ errors.telephone[0] }}</div>
                  </div>
                </div>
              </div>

              <!-- Affectation -->
              <div class="form-section mb-3">
                <div class="form-section-header">
                  <h6><i class="fas fa-sitemap me-2"></i>Affectation & Fonction</h6>
                </div>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Organe <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" :class="{ 'is-invalid': errors.organe }" v-model="form.organe" required @change="syncPanels">
                      <option value="">-- Selectionner --</option>
                      <option v-for="o in options.organeOptions" :key="o" :value="o">{{ o }}</option>
                    </select>
                    <div v-if="errors.organe" class="invalid-feedback">{{ errors.organe[0] }}</div>
                  </div>

                  <div v-if="currentNiveau === 'SEN' && typeRattachement === 'departement'" class="col-md-6">
                    <label class="form-label fw-medium">Departement</label>
                    <select class="form-select form-select-sm" v-model="form.departement_id">
                      <option value="">-- Selectionner --</option>
                      <option v-for="d in options.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                    </select>
                  </div>

                  <div v-if="currentNiveau === 'SEP' || currentNiveau === 'SEL'" class="col-md-6">
                    <label class="form-label fw-medium">Province</label>
                    <select class="form-select form-select-sm" v-model="form.province_id">
                      <option value="">-- Selectionner --</option>
                      <option v-for="p in options.provinces" :key="p.id" :value="p.id">{{ p.nom_province || p.nom }}</option>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-medium">Fonction <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" :class="{ 'is-invalid': errors.fonction }" v-model="form.fonction" required>
                      <option value="">-- Selectionner --</option>
                      <option v-for="f in visibleFonctions" :key="f.id" :value="f.nom">{{ f.nom }}</option>
                    </select>
                    <div v-if="errors.fonction" class="invalid-feedback">{{ errors.fonction[0] }}</div>
                  </div>
                </div>

                <div v-if="currentNiveau === 'SEN'" class="mt-2">
                  <label class="form-label fw-medium">Rattachement</label>
                  <div class="d-flex gap-2">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" value="departement" v-model="typeRattachement" @change="syncPanels" id="ratt_dept">
                      <label class="form-check-label" for="ratt_dept">Departement</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" value="service_rattache" v-model="typeRattachement" @change="syncPanels" id="ratt_service">
                      <label class="form-check-label" for="ratt_service">Service rattache</label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Formation -->
              <div class="form-section mb-3">
                <div class="form-section-header">
                  <h6><i class="fas fa-graduation-cap me-2"></i>Formation & Engagement</h6>
                </div>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Niveau etudes <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" :class="{ 'is-invalid': errors.niveau_etudes }" v-model="form.niveau_etudes" required>
                      <option value="">-- Selectionner --</option>
                      <option v-for="n in options.niveauxEtudes" :key="n" :value="n">{{ n }}</option>
                    </select>
                    <div v-if="errors.niveau_etudes" class="invalid-feedback">{{ errors.niveau_etudes[0] }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Annee engagement <span class="text-danger">*</span></label>
                    <input type="number" min="1950" max="2100" class="form-control form-control-sm" :class="{ 'is-invalid': errors.annee_engagement_programme }" v-model.number="form.annee_engagement_programme" required>
                    <div v-if="errors.annee_engagement_programme" class="invalid-feedback">{{ errors.annee_engagement_programme[0] }}</div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')" :disabled="submitting">
            Annuler
          </button>
          <button type="button" class="btn btn-primary" @click="submitForm" :disabled="submitting || loading">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="fas fa-save me-2"></i>
            {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useUiStore } from '@/stores/ui'
import { create, getFormOptions } from '@/api/agents'

const props = defineProps({
  show: { type: Boolean, required: true }
})

const emit = defineEmits(['close', 'created'])

const ui = useUiStore()
const loading = ref(false)
const submitting = ref(false)
const errors = ref({})
const photoFile = ref(null)
const typeRattachement = ref('')

const form = reactive({
  nom: '', prenom: '', postnom: '', sexe: '', annee_naissance: null,
  date_naissance: '', lieu_naissance: '', email_professionnel: '',
  telephone: '', organe: '', departement_id: '', province_id: '',
  fonction: '', niveau_etudes: '', annee_engagement_programme: null,
})

const options = reactive({
  organeOptions: [], departments: [], provinces: [], fonctions: [], niveauxEtudes: [],
})

const organeToNiveau = {
  'secrétariat exécutif national': 'SEN', 'secretariat executif national': 'SEN',
  'secrétariat exécutif provincial': 'SEP', 'secretariat executif provincial': 'SEP',
  'secrétariat exécutif local': 'SEL', 'secretariat executif local': 'SEL',
}

const currentNiveau = computed(() => {
  if (!form.organe) return ''
  const key = form.organe.trim().toLowerCase()
  return organeToNiveau[key] || ''
})

const visibleFonctions = computed(() => {
  const niveau = currentNiveau.value
  if (!niveau) return options.fonctions

  let allowedTypes = []
  if (niveau === 'SEN') {
    if (typeRattachement.value === 'service_rattache') {
      allowedTypes = ['service_rattache', 'direction']
    } else if (typeRattachement.value === 'departement') {
      allowedTypes = ['département', 'departement', 'direction']
    } else {
      allowedTypes = ['direction']
    }
  } else if (niveau === 'SEP') {
    allowedTypes = ['province']
  } else if (niveau === 'SEL') {
    allowedTypes = ['local']
  }

  return options.fonctions.filter(f => {
    const matchNiveau = f.niveau_administratif === niveau || f.niveau_administratif === 'TOUS'
    if (!matchNiveau) return false
    if (allowedTypes.length) {
      return allowedTypes.includes(f.type_poste) || f.type_poste === 'appui'
    }
    return true
  })
})

function handlePhotoChange(event) {
  photoFile.value = event.target.files[0] || null
}

function syncPanels() {
  const niveau = currentNiveau.value
  if (niveau !== 'SEN') {
    typeRattachement.value = ''
    form.departement_id = ''
  }
  if (niveau !== 'SEP' && niveau !== 'SEL') {
    form.province_id = ''
  }
  if (form.fonction && !visibleFonctions.value.find(f => f.nom === form.fonction)) {
    form.fonction = ''
  }
}

async function fetchOptions() {
  loading.value = true
  try {
    const res = await getFormOptions()
    options.organeOptions = res.data.organeOptions || []
    options.departments = res.data.departments || []
    options.provinces = res.data.provinces || []
    options.fonctions = res.data.fonctions || []
    options.niveauxEtudes = res.data.niveauxEtudes || []
  } catch (err) {
    console.error('Error fetching options:', err)
    ui.addToast('Erreur lors du chargement des options', 'danger')
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
    await create(formData)
    ui.addToast('Agent cree avec succes', 'success')
    emit('created')
    emit('close')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      console.error('Error creating agent:', err)
      ui.addToast('Erreur lors de la creation de l\'agent', 'danger')
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

watch(() => props.show, (newValue) => {
  if (newValue) {
    fetchOptions()
  } else {
    errors.value = {}
    photoFile.value = null
    Object.keys(form).forEach(key => {
      if (typeof form[key] === 'number') form[key] = null
      else form[key] = ''
    })
    typeRattachement.value = ''
  }
})
</script>

<style scoped>
.form-section {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1rem;
}
.form-section-header h6 {
  font-weight: 600;
  margin-bottom: 0.75rem;
  color: #333;
}
.form-label {
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}
</style>
