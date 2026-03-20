<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-6">

        <!-- Header -->
        <div class="d-flex align-items-center mb-4">
          <router-link :to="{ name: 'profile.show' }" class="btn btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
          </router-link>
          <h3 class="mb-0">Changer le mot de passe</h3>
        </div>

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

        <!-- Form -->
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <div class="text-center mb-4">
              <div class="password-icon-wrap mx-auto mb-3">
                <i class="fas fa-lock"></i>
              </div>
              <p class="text-muted">
                Pour des raisons de securite, veuillez entrer votre mot de passe actuel
                avant de definir un nouveau mot de passe.
              </p>
            </div>

            <form @submit.prevent="submitForm">

              <!-- Current password -->
              <div class="mb-3">
                <label for="current_password" class="form-label fw-bold">Mot de passe actuel</label>
                <div class="input-group">
                  <input
                    :type="showCurrent ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': errors.current_password }"
                    id="current_password"
                    v-model="form.current_password"
                    placeholder="Votre mot de passe actuel"
                    required
                  >
                  <button class="btn btn-outline-secondary" type="button" @click="showCurrent = !showCurrent">
                    <i :class="showCurrent ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                  <div v-if="errors.current_password" class="invalid-feedback">{{ errors.current_password[0] }}</div>
                </div>
              </div>

              <hr class="my-4">

              <!-- New password -->
              <div class="mb-3">
                <label for="password" class="form-label fw-bold">Nouveau mot de passe</label>
                <div class="input-group">
                  <input
                    :type="showNew ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                    id="password"
                    v-model="form.password"
                    placeholder="Minimum 8 caracteres"
                    required
                    minlength="8"
                  >
                  <button class="btn btn-outline-secondary" type="button" @click="showNew = !showNew">
                    <i :class="showNew ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                  <div v-if="errors.password" class="invalid-feedback">{{ errors.password[0] }}</div>
                </div>

                <!-- Password strength indicator -->
                <div class="mt-2">
                  <div class="progress" style="height: 4px;">
                    <div
                      class="progress-bar"
                      :class="strengthClass"
                      :style="{ width: strengthPercent + '%' }"
                    ></div>
                  </div>
                  <small :class="strengthTextClass" class="mt-1 d-block">{{ strengthLabel }}</small>
                </div>
              </div>

              <!-- Confirm password -->
              <div class="mb-4">
                <label for="password_confirmation" class="form-label fw-bold">Confirmer le nouveau mot de passe</label>
                <div class="input-group">
                  <input
                    :type="showConfirm ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': confirmMismatch }"
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    placeholder="Repetez le nouveau mot de passe"
                    required
                  >
                  <button class="btn btn-outline-secondary" type="button" @click="showConfirm = !showConfirm">
                    <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                  <div v-if="confirmMismatch" class="invalid-feedback">Les mots de passe ne correspondent pas.</div>
                </div>
              </div>

              <!-- Submit -->
              <div class="d-grid gap-2">
                <button
                  type="submit"
                  class="btn btn-primary btn-lg"
                  :disabled="saving || !formValid"
                >
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
                  <i v-else class="fas fa-key me-2"></i>
                  Modifier le mot de passe
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Security tips -->
        <div class="card border-0 shadow-sm mt-4">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="fas fa-shield-alt me-2 text-primary"></i>Conseils de securite</h6>
            <ul class="list-unstyled mb-0 small text-muted">
              <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Utilisez au moins 8 caracteres</li>
              <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Melangez lettres majuscules, minuscules et chiffres</li>
              <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Ajoutez des caracteres speciaux (!@#$%)</li>
              <li><i class="fas fa-check-circle me-2 text-success"></i>Ne reutilisez pas un ancien mot de passe</li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { updatePassword } from '@/api/profile'

const saving = ref(false)
const successMsg = ref(null)
const errors = ref({})

const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

// Password strength computation
const strengthPercent = computed(() => {
  const pwd = form.password
  if (!pwd) return 0
  let score = 0
  if (pwd.length >= 8) score += 25
  if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score += 25
  if (/\d/.test(pwd)) score += 25
  if (/[^a-zA-Z0-9]/.test(pwd)) score += 25
  return score
})

const strengthClass = computed(() => {
  if (strengthPercent.value <= 25) return 'bg-danger'
  if (strengthPercent.value <= 50) return 'bg-warning'
  if (strengthPercent.value <= 75) return 'bg-info'
  return 'bg-success'
})

const strengthTextClass = computed(() => {
  if (strengthPercent.value <= 25) return 'text-danger'
  if (strengthPercent.value <= 50) return 'text-warning'
  if (strengthPercent.value <= 75) return 'text-info'
  return 'text-success'
})

const strengthLabel = computed(() => {
  if (!form.password) return ''
  if (strengthPercent.value <= 25) return 'Faible'
  if (strengthPercent.value <= 50) return 'Moyen'
  if (strengthPercent.value <= 75) return 'Bon'
  return 'Excellent'
})

const confirmMismatch = computed(() => {
  return form.password_confirmation.length > 0 && form.password !== form.password_confirmation
})

const formValid = computed(() => {
  return (
    form.current_password.length > 0 &&
    form.password.length >= 8 &&
    form.password === form.password_confirmation
  )
})

async function submitForm() {
  saving.value = true
  errors.value = {}
  successMsg.value = null

  try {
    const { data } = await updatePassword({
      current_password: form.current_password,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    successMsg.value = data.message || 'Mot de passe modifie avec succes.'

    // Reset form
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''

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
</script>

<style scoped>
.card {
  border-radius: 14px;
}
.password-icon-wrap {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0077B5, #005885);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.5rem;
}
.btn-primary {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none;
  border-radius: 10px;
  font-weight: 600;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #005885, #00394f);
}
.btn-primary:disabled {
  opacity: 0.65;
}
.btn-outline-secondary {
  border-radius: 10px;
}
.progress {
  border-radius: 4px;
  background: #e9ecef;
}
.input-group .btn {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
</style>
