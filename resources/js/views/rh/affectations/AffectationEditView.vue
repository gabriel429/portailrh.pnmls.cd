<template>
  <div class="container-fluid" style="max-width: 960px; padding-top: 2rem; padding-bottom: 3rem;">

    <!-- Loading -->
    <LoadingSpinner v-if="loading" message="Chargement de l'affectation..." />

    <template v-else-if="affectation">
      <!-- Hero -->
      <div class="affectation-hero">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h2><i class="fas fa-edit me-2"></i> Modifier l'Affectation</h2>
            <p v-if="affectation.agent">
              {{ affectation.agent.prenom }} {{ affectation.agent.nom }}
              <span v-if="affectation.fonction"> - {{ affectation.fonction.nom }}</span>
            </p>
          </div>
          <router-link :to="{ name: 'rh.affectations.index' }" class="btn btn-light btn-sm" style="border-radius: 8px; font-weight: 600;">
            <i class="fas fa-arrow-left me-1"></i> Retour
          </router-link>
        </div>
      </div>

      <!-- Validation errors -->
      <div v-if="Object.keys(errors).length > 0" class="alert alert-danger alert-dismissible fade show mb-3" style="border-radius: 10px;">
        <strong><i class="fas fa-exclamation-triangle me-1"></i> Erreurs de validation :</strong>
        <ul class="mb-0 mt-2">
          <li v-for="(msgs, field) in errors" :key="field">{{ Array.isArray(msgs) ? msgs[0] : msgs }}</li>
        </ul>
        <button type="button" class="btn-close" @click="errors = {}"></button>
      </div>

      <form @submit.prevent="submitForm">

        <!-- Section 1: Agent -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background: linear-gradient(135deg, #0077B5, #00a0dc)">
              <i class="fas fa-user"></i>
            </div>
            <div>
              <h5>Agent</h5>
              <small>Agent affecte</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-12">
              <label for="agent_id" class="form-label">Agent <span class="text-danger">*</span></label>
              <select class="form-select" :class="{ 'is-invalid': errors.agent_id }" id="agent_id" v-model="form.agent_id" required>
                <option value="">-- Selectionner un agent --</option>
                <option v-for="a in options.agents" :key="a.id" :value="a.id">
                  {{ a.prenom }} {{ a.nom }} {{ a.matricule ? '(' + a.matricule + ')' : '' }}
                </option>
              </select>
              <div v-if="errors.agent_id" class="invalid-feedback">{{ errors.agent_id[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Section 2: Fonction & Niveau -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9)">
              <i class="fas fa-briefcase"></i>
            </div>
            <div>
              <h5>Fonction & Niveau Administratif</h5>
              <small>Fonction, niveau administratif et type de poste</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="fonction_id" class="form-label">Fonction <span class="text-danger">*</span></label>
              <select class="form-select" :class="{ 'is-invalid': errors.fonction_id }" id="fonction_id" v-model="form.fonction_id" required>
                <option value="">-- Selectionner une fonction --</option>
                <option v-for="f in filteredFonctions" :key="f.id" :value="f.id">
                  {{ f.nom }}
                </option>
              </select>
              <div v-if="errors.fonction_id" class="invalid-feedback">{{ errors.fonction_id[0] }}</div>
            </div>
            <div class="col-md-3">
              <label for="niveau_administratif" class="form-label">Niveau Admin. <span class="text-danger">*</span></label>
              <select class="form-select" :class="{ 'is-invalid': errors.niveau_administratif }" id="niveau_administratif" v-model="form.niveau_administratif" required @change="onNiveauChange">
                <option value="">-- Selectionner --</option>
                <option value="SEN">SEN</option>
                <option value="SEP">SEP</option>
                <option value="SEL">SEL</option>
              </select>
              <div v-if="errors.niveau_administratif" class="invalid-feedback">{{ errors.niveau_administratif[0] }}</div>
            </div>
            <div class="col-md-3">
              <label for="niveau" class="form-label">Type de poste <span class="text-danger">*</span></label>
              <select class="form-select" :class="{ 'is-invalid': errors.niveau }" id="niveau" v-model="form.niveau" required @change="form.fonction_id = ''">
                <option value="">-- Selectionner --</option>
                <option value="direction">Direction</option>
                <option value="service_rattache">Service rattache</option>
                <option value="departement">Departement</option>
                <option value="section">Section</option>
                <option value="cellule">Cellule</option>
                <option value="province">Province</option>
                <option value="local">Local</option>
              </select>
              <div v-if="errors.niveau" class="invalid-feedback">{{ errors.niveau[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Section 3: Structure -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background: linear-gradient(135deg, #10b981, #059669)">
              <i class="fas fa-sitemap"></i>
            </div>
            <div>
              <h5>Structure</h5>
              <small>Departement, section, cellule, province, localite (selon le niveau)</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="department_id" class="form-label">Departement</label>
              <select class="form-select" :class="{ 'is-invalid': errors.department_id }" id="department_id" v-model="form.department_id" @change="onDepartmentChange">
                <option value="">-- Aucun --</option>
                <option v-for="d in options.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
              </select>
              <div v-if="errors.department_id" class="invalid-feedback">{{ errors.department_id[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="section_id" class="form-label">Section</label>
              <select class="form-select" :class="{ 'is-invalid': errors.section_id }" id="section_id" v-model="form.section_id" @change="onSectionChange">
                <option value="">-- Aucune --</option>
                <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.nom }}</option>
              </select>
              <div v-if="errors.section_id" class="invalid-feedback">{{ errors.section_id[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="cellule_id" class="form-label">Cellule</label>
              <select class="form-select" :class="{ 'is-invalid': errors.cellule_id }" id="cellule_id" v-model="form.cellule_id">
                <option value="">-- Aucune --</option>
                <option v-for="c in filteredCellules" :key="c.id" :value="c.id">{{ c.nom }}</option>
              </select>
              <div v-if="errors.cellule_id" class="invalid-feedback">{{ errors.cellule_id[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="province_id" class="form-label">Province</label>
              <select class="form-select" :class="{ 'is-invalid': errors.province_id }" id="province_id" v-model="form.province_id" @change="onProvinceChange">
                <option value="">-- Aucune --</option>
                <option v-for="p in options.provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
              </select>
              <div v-if="errors.province_id" class="invalid-feedback">{{ errors.province_id[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="localite_id" class="form-label">Localite</label>
              <select class="form-select" :class="{ 'is-invalid': errors.localite_id }" id="localite_id" v-model="form.localite_id">
                <option value="">-- Aucune --</option>
                <option v-for="l in filteredLocalites" :key="l.id" :value="l.id">{{ l.nom }}</option>
              </select>
              <div v-if="errors.localite_id" class="invalid-feedback">{{ errors.localite_id[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Section 4: Dates & Remarque -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706)">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
              <h5>Dates & Remarque</h5>
              <small>Periode de l'affectation et observations</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="date_debut" class="form-label">Date de debut</label>
              <input type="date" class="form-control" :class="{ 'is-invalid': errors.date_debut }" id="date_debut" v-model="form.date_debut">
              <div v-if="errors.date_debut" class="invalid-feedback">{{ errors.date_debut[0] }}</div>
            </div>
            <div class="col-md-4">
              <label for="date_fin" class="form-label">Date de fin</label>
              <input type="date" class="form-control" :class="{ 'is-invalid': errors.date_fin }" id="date_fin" v-model="form.date_fin">
              <div v-if="errors.date_fin" class="invalid-feedback">{{ errors.date_fin[0] }}</div>
            </div>
            <div class="col-md-4">
              <label for="actif" class="form-label">Statut</label>
              <select class="form-select" id="actif" v-model="form.actif">
                <option :value="true">Actif</option>
                <option :value="false">Inactif</option>
              </select>
            </div>
            <div class="col-md-12">
              <label for="remarque" class="form-label">Remarque</label>
              <textarea class="form-control" :class="{ 'is-invalid': errors.remarque }" id="remarque" v-model="form.remarque" rows="3" placeholder="Observations ou notes..."></textarea>
              <div v-if="errors.remarque" class="invalid-feedback">{{ errors.remarque[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-3 justify-content-end">
          <router-link :to="{ name: 'rh.affectations.index' }" class="btn btn-cancel">
            <i class="fas fa-times me-1"></i> Annuler
          </router-link>
          <button type="submit" class="btn btn-submit" :disabled="submitting">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-save me-1"></i> Enregistrer les modifications
          </button>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const route = useRoute()
const ui = useUiStore()

// State
const loading = ref(true)
const submitting = ref(false)
const errors = ref({})
const affectation = ref(null)

const form = reactive({
  agent_id: '',
  fonction_id: '',
  niveau_administratif: '',
  niveau: '',
  department_id: '',
  section_id: '',
  cellule_id: '',
  province_id: '',
  localite_id: '',
  date_debut: '',
  date_fin: '',
  actif: true,
  remarque: '',
})

const options = reactive({
  agents: [],
  fonctions: [],
  departments: [],
  sections: [],
  cellules: [],
  provinces: [],
  localites: [],
})

// Filtered fonctions based on niveau_administratif and type de poste (niveau)
const filteredFonctions = computed(() => {
  return options.fonctions.filter(f => {
    if (form.niveau_administratif) {
      if (f.niveau_administratif !== form.niveau_administratif && f.niveau_administratif !== 'TOUS') return false
    }
    if (form.niveau && f.type_poste) {
      const niveauToType = {
        'direction': 'direction',
        'service_rattache': 'service_rattache',
        'departement': 'département',
        'section': 'section',
        'cellule': 'cellule',
        'province': 'province',
        'local': 'local',
      }
      const typePoste = niveauToType[form.niveau] || form.niveau
      if (f.type_poste !== typePoste && f.type_poste !== 'appui') return false
    }
    return true
  })
})

// Filtered dropdowns based on parent selection
const filteredSections = computed(() => {
  if (!form.department_id) return []
  return options.sections.filter(s => String(s.department_id) === String(form.department_id))
})

const filteredCellules = computed(() => {
  if (!form.section_id) return []
  return options.cellules.filter(c => String(c.section_id) === String(form.section_id))
})

const filteredLocalites = computed(() => {
  if (!form.province_id) return []
  return options.localites.filter(l => String(l.province_id) === String(form.province_id))
})

// Cascading resets
function onNiveauChange() {
  form.fonction_id = ''
  if (form.niveau_administratif === 'SEN') {
    form.province_id = ''
    form.localite_id = ''
  } else {
    form.department_id = ''
    form.section_id = ''
    form.cellule_id = ''
  }
}

function onDepartmentChange() {
  form.section_id = ''
  form.cellule_id = ''
}

function onSectionChange() {
  form.cellule_id = ''
}

function onProvinceChange() {
  form.localite_id = ''
}

// Populate form from affectation data
function populateForm(data) {
  form.agent_id = data.agent_id || ''
  form.fonction_id = data.fonction_id || ''
  form.niveau_administratif = data.niveau_administratif || ''
  form.niveau = data.niveau || ''
  form.department_id = data.department_id || ''
  form.section_id = data.section_id || ''
  form.cellule_id = data.cellule_id || ''
  form.province_id = data.province_id || ''
  form.localite_id = data.localite_id || ''
  form.date_debut = data.date_debut ? data.date_debut.substring(0, 10) : ''
  form.date_fin = data.date_fin ? data.date_fin.substring(0, 10) : ''
  form.actif = data.actif !== undefined ? Boolean(data.actif) : true
  form.remarque = data.remarque || ''
}

// Fetch affectation and form options
async function fetchData() {
  loading.value = true
  try {
    const [affRes, optRes] = await Promise.all([
      client.get(`/admin/affectations`, { params: { page: 1, per_page: 9999 } }),
      client.get('/admin/affectations/form-data'),
    ])

    // Find the affectation by ID from paginated data
    const allAffectations = affRes.data.data || []
    const found = allAffectations.find(a => String(a.id) === String(route.params.id))

    if (!found) {
      ui.addToast('Affectation introuvable', 'danger')
      router.push({ name: 'rh.affectations.index' })
      return
    }

    affectation.value = found
    options.agents = optRes.data.agents || []
    options.fonctions = optRes.data.fonctions || []
    options.departments = optRes.data.departments || []
    options.sections = optRes.data.sections || []
    options.cellules = optRes.data.cellules || []
    options.provinces = optRes.data.provinces || []
    options.localites = optRes.data.localites || []

    populateForm(found)
  } catch (err) {
    console.error('Error fetching affectation:', err)
    ui.addToast('Erreur lors du chargement de l\'affectation', 'danger')
    router.push({ name: 'rh.affectations.index' })
  } finally {
    loading.value = false
  }
}

// Submit
async function submitForm() {
  submitting.value = true
  errors.value = {}

  const payload = {}
  for (const [key, value] of Object.entries(form)) {
    if (value !== '' && value !== null && value !== undefined) {
      payload[key] = value
    }
  }

  try {
    const { data } = await client.put(`/admin/affectations/${route.params.id}`, payload)
    affectation.value = data
    ui.addToast('Affectation modifiee avec succes', 'success')
    router.push({ name: 'rh.affectations.index' })
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      console.error('Error updating affectation:', err)
      ui.addToast('Erreur lors de la modification de l\'affectation', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.affectation-hero {
  background: linear-gradient(135deg, #0077B5 0%, #005885 100%);
  color: #fff;
  border-radius: 16px;
  padding: 2rem 2.5rem;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
}
.affectation-hero::after {
    content: '';
    position: absolute;
    right: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 200px;
    height: 200px;
    background: url('/images/pnmls.jpeg') center/contain no-repeat;
    opacity: 0.10;
    pointer-events: none;
}
.affectation-hero h2 { font-weight: 700; margin-bottom: .25rem; }
.affectation-hero p { opacity: .85; margin-bottom: 0; }
.form-section {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  padding: 2rem;
  margin-bottom: 1.5rem;
  border: 1px solid #e9ecef;
}
.form-section-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  margin-bottom: 1.5rem;
  padding-bottom: .75rem;
  border-bottom: 2px solid #e9ecef;
}
.form-section-icon {
  width: 42px; height: 42px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: #fff; flex-shrink: 0;
}
.form-section-header h5 { font-weight: 700; margin-bottom: 0; color: #1a1a2e; }
.form-section-header small { color: #6c757d; font-weight: 400; }
.btn-submit {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none; color: #fff; font-weight: 600;
  padding: .7rem 2rem; border-radius: 10px;
  transition: transform .15s, box-shadow .15s;
}
.btn-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(0,119,181,.35);
  color: #fff;
}
.btn-cancel {
  background: #f2f4f7; color: #344054; font-weight: 600;
  border: 1.5px solid #d0d5dd; border-radius: 10px;
  padding: .7rem 2rem;
}
.btn-cancel:hover { background: #e4e7ec; color: #1a1a2e; }

/* Mobile responsive */
@media (max-width: 767.98px) {
    .affectation-hero {
        padding: 1.25rem 1rem;
        border-radius: 10px;
    }
    .affectation-hero::after {
        width: 120px;
        height: 120px;
        right: -10px;
    }
    .affectation-hero h2 {
        font-size: 1.2rem;
    }
    .form-section {
        padding: 1.25rem 1rem;
        border-radius: 10px;
    }
    .form-section-header h5 {
        font-size: 1rem;
    }
    .form-section-icon {
        width: 36px;
        height: 36px;
        font-size: 0.95rem;
    }
    .btn-submit, .btn-cancel {
        padding: 0.6rem 1.25rem;
        font-size: 0.9rem;
    }
    .d-flex.gap-3.justify-content-end {
        flex-direction: column;
    }
    .d-flex.gap-3.justify-content-end .btn {
        width: 100%;
    }
}
</style>
