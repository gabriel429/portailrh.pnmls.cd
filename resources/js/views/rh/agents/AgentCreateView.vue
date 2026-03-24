<template>
  <div class="container-fluid" style="max-width:960px;padding-top:2rem;padding-bottom:3rem;">

    <!-- Hero -->
    <div class="agent-form-hero">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h2><i class="fas fa-user-plus me-2"></i> Nouvel Agent</h2>
          <p>Enregistrer un nouvel agent au Portail RH PNMLS</p>
        </div>
        <router-link :to="{ name: 'rh.agents.index' }" class="btn btn-light btn-sm" style="border-radius:8px;font-weight:600;">
          <i class="fas fa-arrow-left me-1"></i> Retour
        </router-link>
      </div>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="loadingOptions" message="Chargement des options..." />

    <template v-else>
      <!-- Validation errors -->
      <div v-if="Object.keys(errors).length > 0" class="alert alert-danger alert-dismissible fade show mb-3" style="border-radius:10px;">
        <strong><i class="fas fa-exclamation-triangle me-1"></i> Erreurs de validation :</strong>
        <ul class="mb-0 mt-2">
          <li v-for="(msgs, field) in errors" :key="field">{{ Array.isArray(msgs) ? msgs[0] : msgs }}</li>
        </ul>
        <button type="button" class="btn-close" @click="errors = {}"></button>
      </div>

      <form @submit.prevent="submitForm">

        <!-- Section 1: Identite -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#0077B5,#00a0dc)">
              <i class="fas fa-id-card"></i>
            </div>
            <div>
              <h5>Identite de l'agent</h5>
              <small>Nom, prenom, post-nom et etat civil</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
              <input type="text" class="form-control" :class="{ 'is-invalid': errors.nom }" id="nom" v-model="form.nom" placeholder="KABONGO" required>
              <div v-if="errors.nom" class="invalid-feedback">{{ errors.nom[0] }}</div>
            </div>
            <div class="col-md-4">
              <label for="postnom" class="form-label">Post-nom</label>
              <input type="text" class="form-control" :class="{ 'is-invalid': errors.postnom }" id="postnom" v-model="form.postnom" placeholder="MWAMBA">
              <div v-if="errors.postnom" class="invalid-feedback">{{ errors.postnom[0] }}</div>
            </div>
            <div class="col-md-4">
              <label for="prenom" class="form-label">Prenom <span class="text-danger">*</span></label>
              <input type="text" class="form-control" :class="{ 'is-invalid': errors.prenom }" id="prenom" v-model="form.prenom" placeholder="Jean" required>
              <div v-if="errors.prenom" class="invalid-feedback">{{ errors.prenom[0] }}</div>
            </div>
            <div class="col-md-3">
              <label for="sexe" class="form-label">Sexe <span class="text-danger">*</span></label>
              <select class="form-select" :class="{ 'is-invalid': errors.sexe }" id="sexe" v-model="form.sexe" required>
                <option value="">--</option>
                <option value="M">Masculin</option>
                <option value="F">Feminin</option>
              </select>
              <div v-if="errors.sexe" class="invalid-feedback">{{ errors.sexe[0] }}</div>
            </div>
            <div class="col-md-3">
              <label for="annee_naissance" class="form-label">Annee de naissance <span class="text-danger">*</span></label>
              <input type="number" min="1945" max="2100" class="form-control" :class="{ 'is-invalid': errors.annee_naissance }" id="annee_naissance" v-model.number="form.annee_naissance" placeholder="1985" required>
              <div v-if="errors.annee_naissance" class="invalid-feedback">{{ errors.annee_naissance[0] }}</div>
            </div>
            <div class="col-md-3">
              <label for="date_naissance" class="form-label">Date de naissance</label>
              <input type="date" class="form-control" :class="{ 'is-invalid': errors.date_naissance }" id="date_naissance" v-model="form.date_naissance">
              <div v-if="errors.date_naissance" class="invalid-feedback">{{ errors.date_naissance[0] }}</div>
            </div>
            <div class="col-md-3">
              <label for="lieu_naissance" class="form-label">Lieu de naissance <span class="text-danger">*</span></label>
              <input type="text" class="form-control" :class="{ 'is-invalid': errors.lieu_naissance }" id="lieu_naissance" v-model="form.lieu_naissance" placeholder="Kinshasa" required>
              <div v-if="errors.lieu_naissance" class="invalid-feedback">{{ errors.lieu_naissance[0] }}</div>
            </div>
            <div class="col-md-4">
              <label for="situation_familiale" class="form-label">Situation familiale</label>
              <select class="form-select" id="situation_familiale" v-model="form.situation_familiale">
                <option value="">-- Selectionner --</option>
                <option value="celibataire">Celibataire</option>
                <option value="marie">Marie(e)</option>
                <option value="divorce">Divorce(e)</option>
                <option value="veuf">Veuf/Veuve</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="nombre_enfants" class="form-label">Nombre d'enfants</label>
              <input type="number" min="0" class="form-control" id="nombre_enfants" v-model.number="form.nombre_enfants">
            </div>
            <div class="col-md-4">
              <label for="photo" class="form-label">Photo</label>
              <input type="file" class="form-control" id="photo" accept="image/*" @change="handlePhotoChange">
              <small class="text-muted">Max 2 Mo. JPG, PNG</small>
            </div>
          </div>
        </div>

        <!-- Section 2: Coordonnees -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
              <i class="fas fa-address-book"></i>
            </div>
            <div>
              <h5>Coordonnees</h5>
              <small>E-mails, telephone et adresse</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="email_professionnel" class="form-label"><i class="fas fa-envelope text-primary me-1"></i> E-mail institutionnel</label>
              <input type="email" class="form-control" :class="{ 'is-invalid': errors.email_professionnel }" id="email_professionnel" v-model="form.email_professionnel" placeholder="nom@pnmls.cd">
              <div v-if="errors.email_professionnel" class="invalid-feedback">{{ errors.email_professionnel[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="email_prive" class="form-label"><i class="fas fa-envelope-open text-muted me-1"></i> E-mail prive</label>
              <input type="email" class="form-control" :class="{ 'is-invalid': errors.email_prive }" id="email_prive" v-model="form.email_prive" placeholder="nom@gmail.com">
              <div v-if="errors.email_prive" class="invalid-feedback">{{ errors.email_prive[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="telephone" class="form-label"><i class="fas fa-phone text-success me-1"></i> Telephone</label>
              <input type="tel" class="form-control" :class="{ 'is-invalid': errors.telephone }" id="telephone" v-model="form.telephone" placeholder="+243 ...">
              <div v-if="errors.telephone" class="invalid-feedback">{{ errors.telephone[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="adresse" class="form-label"><i class="fas fa-map-marker-alt text-danger me-1"></i> Adresse</label>
              <input type="text" class="form-control" :class="{ 'is-invalid': errors.adresse }" id="adresse" v-model="form.adresse" placeholder="Av. ..., Commune ...">
              <div v-if="errors.adresse" class="invalid-feedback">{{ errors.adresse[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Section 3: Matricule & Grade -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
              <i class="fas fa-id-badge"></i>
            </div>
            <div>
              <h5>Matricule & Grade</h5>
              <small>Matricule de l'Etat, provenance du matricule et grade</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="matricule_etat" class="form-label">Matricule de l'Etat</label>
              <input type="text" class="form-control" :class="{ 'is-invalid': errors.matricule_etat }" id="matricule_etat" v-model="form.matricule_etat" placeholder="N.U. si vide">
              <small class="text-muted">Laisser vide = "N.U."</small>
              <div v-if="errors.matricule_etat" class="invalid-feedback">{{ errors.matricule_etat[0] }}</div>
            </div>
            <div class="col-md-4">
              <label for="institution_id" class="form-label">Provenance matricule</label>
              <select class="form-select" :class="{ 'is-invalid': errors.institution_id }" id="institution_id" v-model="form.institution_id">
                <option value="">-- Selectionner --</option>
                <optgroup v-for="cat in options.institutionCategories" :key="cat.id" :label="cat.nom">
                  <option v-for="inst in cat.institutions" :key="inst.id" :value="inst.id">{{ inst.nom }}</option>
                </optgroup>
              </select>
              <div v-if="errors.institution_id" class="invalid-feedback">{{ errors.institution_id[0] }}</div>
            </div>
            <div class="col-md-4">
              <label for="grade_id" class="form-label">Grade de l'Etat</label>
              <select class="form-select" :class="{ 'is-invalid': errors.grade_id }" id="grade_id" v-model="form.grade_id">
                <option value="">-- Selectionner --</option>
                <option v-for="g in options.grades" :key="g.id" :value="g.id">{{ g.libelle }} ({{ g.categorie }})</option>
              </select>
              <div v-if="errors.grade_id" class="invalid-feedback">{{ errors.grade_id[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Section 4: Affectation -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
              <i class="fas fa-sitemap"></i>
            </div>
            <div>
              <h5>Affectation & Fonction</h5>
              <small>Organe, departement/service, section et fonction</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="organe" class="form-label"><span class="step-badge">1</span> Organe <span class="text-danger">*</span></label>
              <select class="form-select" :class="{ 'is-invalid': errors.organe }" id="organe" v-model="form.organe" required @change="syncPanels">
                <option value="">-- Selectionner un organe --</option>
                <option v-for="o in options.organeOptions" :key="o" :value="o">{{ o }}</option>
              </select>
              <div v-if="errors.organe" class="invalid-feedback">{{ errors.organe[0] }}</div>
            </div>

            <!-- SEN: Rattachement -->
            <div v-if="currentNiveau === 'SEN'" class="col-md-6">
              <label class="form-label"><span class="step-badge">2</span> Rattachement</label>
              <div class="d-flex gap-2">
                <div class="flex-fill">
                  <input type="radio" class="btn-check" id="ratt_dept" value="departement" v-model="typeRattachement" @change="syncPanels">
                  <label class="btn btn-outline-primary w-100" for="ratt_dept" style="border-radius:8px">
                    <i class="fas fa-building me-1"></i> Departement
                  </label>
                </div>
                <div class="flex-fill">
                  <input type="radio" class="btn-check" id="ratt_service" value="service_rattache" v-model="typeRattachement" @change="syncPanels">
                  <label class="btn btn-outline-warning w-100" for="ratt_service" style="border-radius:8px">
                    <i class="fas fa-link me-1"></i> Service rattache
                  </label>
                </div>
              </div>
            </div>

            <!-- Departement (SEN + departement) -->
            <div v-if="currentNiveau === 'SEN' && typeRattachement === 'departement'" class="col-md-6">
              <label for="departement_id" class="form-label"><span class="step-badge">3</span> Departement</label>
              <select class="form-select" :class="{ 'is-invalid': errors.departement_id }" id="departement_id" v-model="form.departement_id" @change="syncSection">
                <option value="">-- Selectionner un departement --</option>
                <option v-for="d in options.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
              </select>
              <div v-if="errors.departement_id" class="invalid-feedback">{{ errors.departement_id[0] }}</div>
            </div>

            <!-- Section (after departement) -->
            <div v-if="currentNiveau === 'SEN' && typeRattachement === 'departement' && form.departement_id" class="col-md-6">
              <label for="section_id" class="form-label"><span class="step-badge">4</span> Section</label>
              <select class="form-select" id="section_id" v-model="form.section_id" @change="filterFonctions">
                <option value="">-- Aucune (niveau departement) --</option>
                <option
                  v-for="s in filteredSections"
                  :key="s.id"
                  :value="s.id"
                >{{ s.nom }}</option>
              </select>
              <small class="text-muted">Laissez vide pour Directeur, Chef, Assistant ou Secretaire du departement.</small>
            </div>

            <!-- Province (SEP/SEL) -->
            <div v-if="currentNiveau === 'SEP' || currentNiveau === 'SEL'" class="col-md-6">
              <label for="province_id" class="form-label"><span class="step-badge">2</span> Province</label>
              <select class="form-select" :class="{ 'is-invalid': errors.province_id }" id="province_id" v-model="form.province_id">
                <option value="">-- Selectionner une province --</option>
                <option v-for="p in options.provinces" :key="p.id" :value="p.id">{{ p.nom_province || p.nom || ('Province ' + p.id) }}</option>
              </select>
              <div v-if="errors.province_id" class="invalid-feedback">{{ errors.province_id[0] }}</div>
            </div>

            <!-- Fonction -->
            <div class="col-md-6">
              <label for="fonction" class="form-label"><i class="fas fa-briefcase me-1" style="color:#8b5cf6;"></i> Fonction / Poste <span class="text-danger">*</span></label>
              <select class="form-select" :class="{ 'is-invalid': errors.fonction }" id="fonction" v-model="form.fonction" required>
                <option value="">-- Selectionner une fonction --</option>
                <option
                  v-for="f in visibleFonctions"
                  :key="f.id"
                  :value="f.nom"
                >{{ f.nom }}{{ f.est_chef ? ' *' : '' }}</option>
              </select>
              <div v-if="errors.fonction" class="invalid-feedback">{{ errors.fonction[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Section 5: Formation & Engagement -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#ec4899,#be185d)">
              <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
              <h5>Formation & Engagement</h5>
              <small>Niveau d'etudes, domaine et dates d'engagement</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="niveau_etudes" class="form-label">Niveau d'etudes <span class="text-danger">*</span></label>
              <select class="form-select" :class="{ 'is-invalid': errors.niveau_etudes }" id="niveau_etudes" v-model="form.niveau_etudes" required>
                <option value="">-- Selectionner --</option>
                <option v-for="n in options.niveauxEtudes" :key="n" :value="n">{{ n }}</option>
              </select>
              <div v-if="errors.niveau_etudes" class="invalid-feedback">{{ errors.niveau_etudes[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="domaine_etudes" class="form-label">Domaine d'etudes</label>
              <input type="text" class="form-control" :class="{ 'is-invalid': errors.domaine_etudes }" id="domaine_etudes" v-model="form.domaine_etudes" placeholder="Ex: Sciences informatiques, Droit...">
              <div v-if="errors.domaine_etudes" class="invalid-feedback">{{ errors.domaine_etudes[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="annee_engagement_programme" class="form-label">Annee d'engagement au programme <span class="text-danger">*</span></label>
              <input type="number" min="1950" max="2100" class="form-control" :class="{ 'is-invalid': errors.annee_engagement_programme }" id="annee_engagement_programme" v-model.number="form.annee_engagement_programme" placeholder="2020" required>
              <div v-if="errors.annee_engagement_programme" class="invalid-feedback">{{ errors.annee_engagement_programme[0] }}</div>
            </div>
            <div class="col-md-6">
              <label for="date_embauche" class="form-label">Date d'embauche <small class="text-muted">(optionnel)</small></label>
              <input type="date" class="form-control" :class="{ 'is-invalid': errors.date_embauche }" id="date_embauche" v-model="form.date_embauche">
              <div v-if="errors.date_embauche" class="invalid-feedback">{{ errors.date_embauche[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-3 justify-content-end">
          <router-link :to="{ name: 'rh.agents.index' }" class="btn btn-cancel">
            <i class="fas fa-times me-1"></i> Annuler
          </router-link>
          <button type="submit" class="btn btn-submit" :disabled="submitting">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-user-plus me-1"></i> Enregistrer l'agent
          </button>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { create, getFormOptions } from '@/api/agents'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const ui = useUiStore()

// State
const loadingOptions = ref(true)
const submitting = ref(false)
const errors = ref({})
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
})

const options = reactive({
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
    // Fallback: strip accents
    const normalized = normalizeStr(form.organe)
    return organeToNiveau[normalized] || ''
})

// Filtered sections based on selected department
const filteredSections = computed(() => {
    if (!form.departement_id) return []
    return options.sections.filter(
        s => s.type === 'section' && String(s.department_id) === String(form.departement_id)
    )
})

// Filtered fonctions based on organe, type de rattachement, and section
const visibleFonctions = computed(() => {
    const niveau = currentNiveau.value
    if (!niveau) return options.fonctions

    let allowedTypes = []
    if (niveau === 'SEN') {
        if (typeRattachement.value === 'service_rattache') {
            allowedTypes = ['service_rattache', 'direction']
        } else if (typeRattachement.value === 'departement') {
            if (form.section_id) {
                allowedTypes = ['section', 'cellule']
            } else {
                allowedTypes = ['département', 'departement', 'direction']
            }
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
        form.section_id = ''
    }
    if (niveau !== 'SEP' && niveau !== 'SEL') {
        form.province_id = ''
    }
    // Reset fonction if no longer visible
    if (form.fonction && !visibleFonctions.value.find(f => f.nom === form.fonction)) {
        form.fonction = ''
    }
}

function syncSection() {
    form.section_id = ''
}

function filterFonctions() {
    if (form.fonction && !visibleFonctions.value.find(f => f.nom === form.fonction)) {
        form.fonction = ''
    }
}

// Fetch form options
async function fetchOptions() {
    loadingOptions.value = true
    try {
        const { data } = await getFormOptions()
        options.organeOptions = data.organeOptions || []
        options.departments = data.departments || []
        options.provinces = data.provinces || []
        options.grades = data.grades || []
        options.institutionCategories = data.institutionCategories || []
        options.sections = data.sections || []
        options.fonctions = data.fonctions || []
        options.niveauxEtudes = data.niveauxEtudes || []
    } catch (err) {
        console.error('Error fetching form options:', err)
        ui.addToast('Erreur lors du chargement des options', 'danger')
    } finally {
        loadingOptions.value = false
    }
}

// Submit
async function submitForm() {
    submitting.value = true
    errors.value = {}

    const formData = new FormData()

    // Append all non-empty form fields
    for (const [key, value] of Object.entries(form)) {
        if (value !== '' && value !== null && value !== undefined) {
            formData.append(key, value)
        }
    }

    // Append photo
    if (photoFile.value) {
        formData.append('photo', photoFile.value)
    }

    try {
        await create(formData)
        ui.addToast('Agent cree avec succes', 'success')
        router.push({ name: 'rh.agents.index' })
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

onMounted(() => {
    fetchOptions()
})
</script>

<style scoped>
.agent-form-hero {
    background: linear-gradient(135deg, #0077B5 0%, #005885 100%);
    color: #fff;
    border-radius: 16px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}
.agent-form-hero::after {
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
.agent-form-hero h2 { font-weight: 700; margin-bottom: .25rem; }
.agent-form-hero p { opacity: .85; margin-bottom: 0; }
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
.step-badge {
    background: #0077B5; color: #fff; font-weight: 700;
    width: 24px; height: 24px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .75rem; margin-right: .35rem;
}

/* Mobile responsive */
@media (max-width: 767.98px) {
    .agent-form-hero {
        padding: 1.25rem 1rem;
        border-radius: 10px;
    }
    .agent-form-hero::after {
        width: 120px;
        height: 120px;
        right: -10px;
    }
    .agent-form-hero h2 {
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
