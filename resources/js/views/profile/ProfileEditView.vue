<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <!-- Header -->
        <div class="d-flex align-items-center mb-4">
          <router-link :to="{ name: 'profile.show' }" class="btn btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
          </router-link>
          <h3 class="mb-0">Modifier le profil</h3>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="fetchError" class="alert alert-danger">
          <i class="fas fa-exclamation-triangle me-2"></i>{{ fetchError }}
        </div>

        <!-- Form -->
        <form v-else @submit.prevent="submitForm" enctype="multipart/form-data">

          <!-- Success message -->
          <div v-if="successMsg" class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ successMsg }}
            <button type="button" class="btn-close" @click="successMsg = null"></button>
          </div>

          <!-- Validation errors -->
          <div v-if="Object.keys(errors).length > 0" class="alert alert-danger">
            <ul class="mb-0">
              <li v-for="(msgs, field) in errors" :key="field">
                <span v-for="msg in msgs" :key="msg">{{ msg }}</span>
              </li>
            </ul>
          </div>

          <!-- Photo Section -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center">
              <h5 class="card-title mb-3"><i class="fas fa-camera me-2"></i> Photo de profil</h5>

              <!-- Photo Preview -->
              <div v-if="photoPreview" class="mb-3">
                <img :src="photoPreview" alt="Preview" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
              </div>
              <div v-else-if="agent.photo" class="mb-3">
                <img :src="'/' + agent.photo" :alt="agent.prenom" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
              </div>
              <div v-else class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px;">
                <span style="font-size: 2.5rem; font-weight: 700; color: #0077B5;">{{ initials }}</span>
              </div>

              <div class="mb-3">
                <input
                  type="file"
                  class="form-control"
                  :class="{ 'is-invalid': errors.photo }"
                  accept="image/*"
                  @change="onPhotoChange"
                >
                <div v-if="errors.photo" class="invalid-feedback">{{ errors.photo[0] }}</div>
                <small class="form-text text-muted">Max 2 Mo. Formats: JPG, PNG, GIF</small>
              </div>
            </div>
          </div>

          <!-- Editable Personal Information -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-user me-2"></i> Informations modifiables</h5>

              <div class="row mt-3">
                <div class="col-md-6 mb-3">
                  <label for="telephone" class="form-label fw-bold">Telephone</label>
                  <input
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.telephone }"
                    id="telephone"
                    v-model="form.telephone"
                    placeholder="+243 ..."
                  >
                  <div v-if="errors.telephone" class="invalid-feedback">{{ errors.telephone[0] }}</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label for="email_prive" class="form-label fw-bold">Email prive</label>
                  <input
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.email_prive }"
                    id="email_prive"
                    v-model="form.email_prive"
                    placeholder="email@exemple.com"
                  >
                  <div v-if="errors.email_prive" class="invalid-feedback">{{ errors.email_prive[0] }}</div>
                </div>

                <div class="col-md-12 mb-3">
                  <label for="adresse" class="form-label fw-bold">Adresse</label>
                  <textarea
                    class="form-control"
                    :class="{ 'is-invalid': errors.adresse }"
                    id="adresse"
                    v-model="form.adresse"
                    rows="2"
                    placeholder="Adresse complete"
                  ></textarea>
                  <div v-if="errors.adresse" class="invalid-feedback">{{ errors.adresse[0] }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Read-only Professional Information -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-info-circle me-2"></i> Informations professionnelles (lecture seule)</h5>

              <div class="row mt-3">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Nom</label>
                  <input type="text" class="form-control" :value="agent.nom" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Prenom</label>
                  <input type="text" class="form-control" :value="agent.prenom" disabled>
                </div>
                <div v-if="agent.postnom" class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Postnom</label>
                  <input type="text" class="form-control" :value="agent.postnom" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Matricule PNMLS</label>
                  <input type="text" class="form-control" :value="agent.matricule_pnmls || 'N/A'" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Date de naissance</label>
                  <input type="text" class="form-control" :value="formatDate(agent.date_naissance) || 'N/A'" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Sexe</label>
                  <input type="text" class="form-control" :value="formatSexe(agent.sexe)" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Fonction / Poste</label>
                  <input type="text" class="form-control" :value="agent.fonction || agent.poste_actuel || 'N/A'" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Role</label>
                  <input type="text" class="form-control" :value="agent.role?.nom_role || 'N/A'" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Departement</label>
                  <input type="text" class="form-control" :value="agent.departement?.nom || 'N/A'" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Province</label>
                  <input type="text" class="form-control" :value="agent.province?.nom || 'N/A'" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Grade de l'Etat</label>
                  <input type="text" class="form-control" :value="agent.grade?.libelle || 'N/A'" disabled>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Organe</label>
                  <input type="text" class="form-control" :value="agent.organe || 'N/A'" disabled>
                </div>
              </div>

              <small class="text-muted"><i class="fas fa-lock me-1"></i> Ces informations ne peuvent etre modifiees que par l'administration RH.</small>
            </div>
          </div>

          <!-- Buttons -->
          <div class="d-flex justify-content-between">
            <router-link :to="{ name: 'profile.show' }" class="btn btn-outline-secondary">
              <i class="fas fa-times me-2"></i> Annuler
            </router-link>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
              <i v-else class="fas fa-save me-2"></i>
              Enregistrer les modifications
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getProfile, updateProfile } from '@/api/profile'

const router = useRouter()

const loading = ref(true)
const saving = ref(false)
const fetchError = ref(null)
const successMsg = ref(null)
const errors = ref({})
const agent = ref({})
const photoFile = ref(null)
const photoPreview = ref(null)

const form = reactive({
  telephone: '',
  adresse: '',
  email_prive: '',
})

const initials = computed(() => {
  const p = (agent.value.prenom || '').charAt(0).toUpperCase()
  const n = (agent.value.nom || '').charAt(0).toUpperCase()
  return p + n
})

function formatDate(dateStr) {
  if (!dateStr) return null
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return null
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatSexe(val) {
  if (val === 'M') return 'Masculin'
  if (val === 'F') return 'Feminin'
  return val || 'N/A'
}

function onPhotoChange(event) {
  const file = event.target.files[0]
  if (file) {
    photoFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      photoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

async function fetchProfileData() {
  loading.value = true
  fetchError.value = null
  try {
    const { data } = await getProfile()
    agent.value = data.agent
    form.telephone = data.agent.telephone || ''
    form.adresse = data.agent.adresse || ''
    form.email_prive = data.agent.email_prive || ''
  } catch (err) {
    fetchError.value = err.response?.data?.message || 'Impossible de charger le profil.'
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  saving.value = true
  errors.value = {}
  successMsg.value = null

  try {
    const formData = new FormData()
    formData.append('telephone', form.telephone || '')
    formData.append('adresse', form.adresse || '')
    formData.append('email_prive', form.email_prive || '')
    if (photoFile.value) {
      formData.append('photo', photoFile.value)
    }

    const { data } = await updateProfile(formData)
    agent.value = data.agent
    successMsg.value = data.message || 'Profil mis a jour avec succes.'

    // Scroll to top to show success message
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      errors.value = { general: [err.response?.data?.message || 'Une erreur est survenue.'] }
    }
  } finally {
    saving.value = false
  }
}

onMounted(fetchProfileData)
</script>

<style scoped>
.card {
  border-radius: 14px;
}
.card-title {
  color: #1a1a2e;
  font-weight: 700;
}
.btn-primary {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none;
  border-radius: 10px;
  font-weight: 600;
  padding: .5rem 1.5rem;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #005885, #00394f);
}
.btn-outline-secondary {
  border-radius: 10px;
}
.form-control:disabled {
  background-color: #f8f9fc;
  color: #6c757d;
}
</style>
