<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <div v-if="loading" class="text-center py-5">
        <LoadingSpinner message="Chargement de l'activite..." />
      </div>

      <template v-else>
        <section class="rh-hero">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <h1 class="rh-title"><i class="fas fa-edit me-2"></i>Modifier l'activite</h1>
              <p class="rh-sub">Plan de Travail Annuel {{ form.annee }}</p>
            </div>
            <div class="col-lg-4">
              <div class="hero-tools">
                <router-link :to="{ name: 'plan-travail.show', params: { id: route.params.id } }" class="btn-rh alt">
                  <i class="fas fa-arrow-left me-1"></i> Retour
                </router-link>
              </div>
            </div>
          </div>
        </section>

        <div class="dash-panel mt-3">
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

                <div v-if="form.niveau_administratif === 'SEN'" class="col-md-4">
                  <label for="departement_id" class="form-label fw-bold">Departement</label>
                  <select v-model="form.departement_id" class="form-select" id="departement_id">
                    <option value="">-- Direction / Tous --</option>
                    <option v-for="d in formData.departments" :key="d.id" :value="d.id">{{ d.nom }}</option>
                  </select>
                </div>

                <div v-if="form.niveau_administratif === 'SEP' || form.niveau_administratif === 'SEL'" class="col-md-4">
                  <label for="province_id" class="form-label fw-bold">Province</label>
                  <select v-model="form.province_id" class="form-select" id="province_id">
                    <option value="">-- Choisir --</option>
                    <option v-for="p in formData.provinces" :key="p.id" :value="p.id">{{ p.nom }}</option>
                  </select>
                </div>

                <div v-if="form.niveau_administratif === 'SEL'" class="col-md-4">
                  <label for="localite_id" class="form-label fw-bold">Localite</label>
                  <select v-model="form.localite_id" class="form-select" id="localite_id">
                    <option value="">-- Choisir --</option>
                    <option v-for="l in formData.localites" :key="l.id" :value="l.id">{{ l.nom }}</option>
                  </select>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-3">
                  <label for="annee" class="form-label fw-bold">Annee <span class="text-danger">*</span></label>
                  <input v-model.number="form.annee" type="number" class="form-control" id="annee" min="2020" max="2040" required>
                </div>

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

                <div class="col-md-3">
                  <label for="statut" class="form-label fw-bold">Statut <span class="text-danger">*</span></label>
                  <select v-model="form.statut" class="form-select" id="statut" required>
                    <option value="planifiee">Planifiee</option>
                    <option value="en_cours">En cours</option>
                    <option value="terminee">Terminee</option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label for="pourcentage" class="form-label fw-bold">Progression (%)</label>
                  <input v-model.number="form.pourcentage" type="number" class="form-control" id="pourcentage" min="0" max="100">
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <label for="date_debut" class="form-label fw-bold">Date de debut</label>
                  <input v-model="form.date_debut" type="date" class="form-control" id="date_debut">
                </div>

                <div class="col-md-6">
                  <label for="date_fin" class="form-label fw-bold">Date de fin</label>
                  <input v-model="form.date_fin" type="date" class="form-control" id="date_fin">
                </div>
              </div>

              <div class="mb-3 mt-3">
                <label for="description" class="form-label fw-bold">Description</label>
                <textarea v-model="form.description" class="form-control" id="description" rows="4"></textarea>
              </div>

              <div class="mb-3">
                <label for="observations" class="form-label fw-bold">Observations</label>
                <textarea v-model="form.observations" class="form-control" id="observations" rows="3"></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="fas fa-save me-1"></i> Mettre a jour
                </button>
                <router-link :to="{ name: 'plan-travail.show', params: { id: route.params.id } }" class="btn btn-outline-secondary">Annuler</router-link>
              </div>
            </form>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { get, update, getCreateData } from '@/api/planTravail'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const loading = ref(true)
const submitting = ref(false)
const errors = ref([])
const formData = ref({ departments: [], provinces: [], localites: [] })
const form = ref({
  titre: '',
  niveau_administratif: '',
  departement_id: '',
  province_id: '',
  localite_id: '',
  annee: new Date().getFullYear(),
  trimestre: '',
  statut: 'planifiee',
  pourcentage: 0,
  date_debut: '',
  date_fin: '',
  description: '',
  observations: '',
})

async function loadActivite() {
  try {
    const [activiteResp, createResp] = await Promise.all([
      get(route.params.id),
      getCreateData(),
    ])
    const a = activiteResp.data.data
    formData.value = createResp.data
    form.value = {
      titre: a.titre || '',
      niveau_administratif: a.niveau_administratif || '',
      departement_id: a.departement_id || '',
      province_id: a.province_id || '',
      localite_id: a.localite_id || '',
      annee: a.annee || new Date().getFullYear(),
      trimestre: a.trimestre || '',
      statut: a.statut || 'planifiee',
      pourcentage: a.pourcentage ?? 0,
      date_debut: a.date_debut ? a.date_debut.split('T')[0] : '',
      date_fin: a.date_fin ? a.date_fin.split('T')[0] : '',
      description: a.description || '',
      observations: a.observations || '',
    }
  } catch {
    ui.addToast('Activite introuvable.', 'danger')
    router.push({ name: 'plan-travail.index' })
  } finally {
    loading.value = false
  }
}

function onNiveauChange() {
  if (form.value.niveau_administratif !== 'SEN') form.value.departement_id = ''
  if (form.value.niveau_administratif !== 'SEP' && form.value.niveau_administratif !== 'SEL') form.value.province_id = ''
  if (form.value.niveau_administratif !== 'SEL') form.value.localite_id = ''
}

async function handleSubmit() {
  errors.value = []
  submitting.value = true
  try {
    await update(route.params.id, form.value)
    ui.addToast('Activite mise a jour.', 'success')
    router.push({ name: 'plan-travail.show', params: { id: route.params.id } })
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

onMounted(() => loadActivite())
</script>
