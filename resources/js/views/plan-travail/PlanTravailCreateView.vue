<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title">
              <i class="fas fa-plus-circle me-2"></i>Nouvelle activite
            </h1>
            <p class="rh-sub">Plan de Travail Annuel {{ formData.annee || new Date().getFullYear() }}</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'plan-travail.index' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div v-if="loadingForm" class="text-center py-5">
        <LoadingSpinner message="Chargement..." />
      </div>

      <div v-else class="dash-panel mt-3">
        <div class="p-4">
          <div v-if="errors.length" class="alert alert-danger">
            <ul class="mb-0">
              <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
            </ul>
          </div>

          <form @submit.prevent="handleSubmit">
            <!-- Titre -->
            <div class="mb-3">
              <label for="titre" class="form-label fw-bold">Titre de l'activite <span class="text-danger">*</span></label>
              <input v-model="form.titre" type="text" class="form-control" id="titre" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label for="categorie" class="form-label fw-bold">Rubrique / categorie</label>
                <input v-model="form.categorie" list="pta-categories" type="text" class="form-control" id="categorie" placeholder="Ex. Leadership">
                <datalist id="pta-categories">
                  <option v-for="item in formData.categories" :key="item" :value="item"></option>
                </datalist>
              </div>

              <div class="col-md-4">
                <label for="responsable_code" class="form-label fw-bold">Responsable</label>
                <input v-model="form.responsable_code" list="pta-responsables" type="text" class="form-control" id="responsable_code" placeholder="Ex. DPSE">
                <datalist id="pta-responsables">
                  <option v-for="item in formData.responsables" :key="item" :value="item"></option>
                </datalist>
              </div>

              <div class="col-md-4">
                <label for="cout_cdf" class="form-label fw-bold">Cout en CDF</label>
                <input v-model.number="form.cout_cdf" type="number" step="0.01" min="0" class="form-control" id="cout_cdf" placeholder="0">
              </div>
            </div>

            <div class="mb-3">
              <label for="objectif" class="form-label fw-bold">Objectif strategique</label>
              <textarea v-model="form.objectif" class="form-control" id="objectif" rows="2"></textarea>
            </div>

            <div class="mb-3">
              <label for="resultat_attendu" class="form-label fw-bold">Resultat attendu</label>
              <textarea v-model="form.resultat_attendu" class="form-control" id="resultat_attendu" rows="2"></textarea>
            </div>

            <div class="row g-3">
              <!-- Niveau administratif -->
              <div class="col-md-4">
                <label for="niveau_administratif" class="form-label fw-bold">Niveau <span class="text-danger">*</span></label>
                <select v-model="form.niveau_administratif" class="form-select" id="niveau_administratif" required @change="onNiveauChange">
                  <option value="">-- Choisir --</option>
                  <option value="SEN">SEN (National)</option>
                  <option value="SEP">SEP (Provincial)</option>
                  <option value="SEL">SEL (Local)</option>
                </select>
              </div>

              <div class="col-md-4">
                <label for="validation_niveau" class="form-label fw-bold">Validation</label>
                <select v-model="form.validation_niveau" class="form-select" id="validation_niveau">
                  <option value="">-- Choisir --</option>
                  <option value="direction">Direction</option>
                  <option value="coordination_nationale">Coordination nationale</option>
                  <option value="coordination_provinciale">Coordination provinciale</option>
                </select>
              </div>

              <!-- Departement (SEN) -->
              <div v-if="form.niveau_administratif === 'SEN'" class="col-md-4">
                <label for="departement_id" class="form-label fw-bold">Departement</label>
                <select v-model="form.departement_id" class="form-select" id="departement_id">
                  <option value="">-- Direction / Tous --</option>
                  <option v-for="d in formData.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                </select>
              </div>

              <!-- Province (SEP/SEL) -->
              <div v-if="form.niveau_administratif === 'SEL'" class="col-md-4">
                <label for="province_id" class="form-label fw-bold">Province</label>
                <select v-model="form.province_id" class="form-select" id="province_id">
                  <option value="">-- Choisir --</option>
                  <option v-for="p in formData.provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
                </select>
              </div>

              <div v-if="form.niveau_administratif === 'SEP'" class="col-md-8">
                <label for="province_ids" class="form-label fw-bold">Provinces concernees</label>
                <select v-model="form.province_ids" class="form-select" id="province_ids" multiple size="6">
                  <option v-for="p in formData.provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
                </select>
                <div class="form-text">Maintenez Ctrl pour selectionner plusieurs provinces.</div>
              </div>

              <!-- Localite (SEL) -->
              <div v-if="form.niveau_administratif === 'SEL'" class="col-md-4">
                <label for="localite_id" class="form-label fw-bold">Localite</label>
                <select v-model="form.localite_id" class="form-select" id="localite_id">
                  <option value="">-- Choisir --</option>
                  <option v-for="l in formData.localites" :key="l.id" :value="l.id">{{ l.nom }}</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mt-1">
              <!-- Annee -->
              <div class="col-md-3">
                <label for="annee" class="form-label fw-bold">Annee <span class="text-danger">*</span></label>
                <input v-model.number="form.annee" type="number" class="form-control" id="annee" min="2020" max="2040" required>
              </div>

              <!-- Trimestre -->
              <div class="col-md-3">
                <label for="trimestre" class="form-label fw-bold">Trimestre</label>
                <select v-model="form.trimestre" class="form-select" id="trimestre">
                  <option value="">Annuel</option>
                  <option value="T1">T1 (Jan-Mar)</option>
                  <option value="T2">T2 (Avr-Jun)</option>
                  <option value="T3">T3 (Jul-Sep)</option>
                  <option value="T4">T4 (Oct-Dec)</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold">Chronogramme</label>
                <div class="d-flex flex-wrap gap-3 pt-2">
                  <label class="form-check-label"><input v-model="form.trimestre_1" class="form-check-input me-1" type="checkbox"> T1</label>
                  <label class="form-check-label"><input v-model="form.trimestre_2" class="form-check-input me-1" type="checkbox"> T2</label>
                  <label class="form-check-label"><input v-model="form.trimestre_3" class="form-check-input me-1" type="checkbox"> T3</label>
                  <label class="form-check-label"><input v-model="form.trimestre_4" class="form-check-input me-1" type="checkbox"> T4</label>
                </div>
              </div>

              <!-- Statut -->
              <div class="col-md-3">
                <label for="statut" class="form-label fw-bold">Statut <span class="text-danger">*</span></label>
                <select v-model="form.statut" class="form-select" id="statut" required>
                  <option value="planifiee">Planifiee</option>
                  <option value="en_cours">En cours</option>
                  <option value="terminee">Terminee</option>
                </select>
              </div>

              <!-- Pourcentage -->
              <div class="col-md-3">
                <label for="pourcentage" class="form-label fw-bold">Progression (%)</label>
                <input v-model.number="form.pourcentage" type="number" class="form-control" id="pourcentage" min="0" max="100">
              </div>
            </div>

            <div class="row g-3 mt-1">
              <!-- Date debut -->
              <div class="col-md-6">
                <label for="date_debut" class="form-label fw-bold">Date de debut</label>
                <input v-model="form.date_debut" type="date" class="form-control" id="date_debut">
              </div>

              <!-- Date fin -->
              <div class="col-md-6">
                <label for="date_fin" class="form-label fw-bold">Date de fin</label>
                <input v-model="form.date_fin" type="date" class="form-control" id="date_fin">
              </div>
            </div>

            <!-- Description -->
            <div class="mb-3 mt-3">
              <label for="description" class="form-label fw-bold">Description</label>
              <textarea v-model="form.description" class="form-control" id="description" rows="4"></textarea>
            </div>

            <!-- Observations -->
            <div class="mb-3">
              <label for="observations" class="form-label fw-bold">Observations</label>
              <textarea v-model="form.observations" class="form-control" id="observations" rows="3"></textarea>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-save me-1"></i> Creer l'activite
              </button>
              <router-link :to="{ name: 'plan-travail.index' }" class="btn btn-outline-secondary">Annuler</router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { create, getCreateData } from '@/api/planTravail'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const router = useRouter()
const ui = useUiStore()

const loadingForm = ref(true)
const submitting = ref(false)
const errors = ref([])
const formData = ref({ departments: [], provinces: [], localites: [], categories: [], responsables: [], annee: new Date().getFullYear() })
const form = ref({
  titre: '',
  categorie: '',
  objectif: '',
  responsable_code: '',
  cout_cdf: '',
  niveau_administratif: '',
  validation_niveau: '',
  departement_id: '',
  province_id: '',
  province_ids: [],
  localite_id: '',
  annee: new Date().getFullYear(),
  trimestre: '',
  trimestre_1: false,
  trimestre_2: false,
  trimestre_3: false,
  trimestre_4: false,
  statut: 'planifiee',
  pourcentage: 0,
  date_debut: '',
  date_fin: '',
  description: '',
  resultat_attendu: '',
  observations: '',
})

async function loadFormData() {
  try {
    const { data } = await getCreateData()
    formData.value = data
    form.value.annee = data.annee
  } catch (err) {
    if (err.response?.status === 403) {
      ui.addToast('Acces refuse.', 'danger')
      router.push({ name: 'plan-travail.index' })
    }
  } finally {
    loadingForm.value = false
  }
}

function onNiveauChange() {
  if (form.value.niveau_administratif !== 'SEN') form.value.departement_id = ''
  if (form.value.niveau_administratif !== 'SEL') form.value.province_id = ''
  if (form.value.niveau_administratif !== 'SEP') form.value.province_ids = []
  if (form.value.niveau_administratif !== 'SEL') form.value.localite_id = ''
}

function buildPayload() {
  const payload = { ...form.value }

  if (!payload.departement_id) delete payload.departement_id
  if (!payload.province_id) delete payload.province_id
  if (!payload.localite_id) delete payload.localite_id
  if (!payload.trimestre) delete payload.trimestre
  if (!payload.date_debut) delete payload.date_debut
  if (!payload.date_fin) delete payload.date_fin
  if (!payload.description) delete payload.description
  if (!payload.objectif) delete payload.objectif
  if (!payload.resultat_attendu) delete payload.resultat_attendu
  if (!payload.observations) delete payload.observations
  if (!payload.categorie) delete payload.categorie
  if (!payload.responsable_code) delete payload.responsable_code
  if (payload.cout_cdf === '' || payload.cout_cdf === null) delete payload.cout_cdf
  if (!payload.province_ids?.length) delete payload.province_ids

  return payload
}

async function handleSubmit() {
  errors.value = []
  submitting.value = true
  try {
    await create(buildPayload())
    ui.addToast('Activite creee avec succes.', 'success')
    router.push({ name: 'plan-travail.index' })
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = Object.values(err.response.data.errors || {}).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => loadFormData())
</script>

<style scoped>
@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; padding: 1rem; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    .form-label { font-size: .82rem; }
    .btn { font-size: .85rem; }
}
</style>
