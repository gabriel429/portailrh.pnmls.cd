<template>
  <div class="container-fluid" style="max-width:960px;padding-top:2rem;padding-bottom:3rem;">

    <!-- Hero -->
    <div class="communique-hero">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h2><i class="fas fa-bullhorn me-2"></i> {{ isEdit ? 'Modifier le Communiqué' : 'Nouveau Communiqué' }}</h2>
          <p>{{ isEdit ? 'Modifier ce communiqué officiel.' : 'Publier un communiqué à destination de tous les agents.' }}</p>
        </div>
        <router-link :to="{ name: 'rh.communiques.index' }" class="btn btn-light btn-sm" style="border-radius:8px;font-weight:600;">
          <i class="fas fa-arrow-left me-1"></i> Retour
        </router-link>
      </div>
    </div>

    <div v-if="loadingEdit" class="text-center py-5">
      <LoadingSpinner message="Chargement du communiqué..." />
    </div>

    <template v-else>
      <!-- Validation errors -->
      <div v-if="errors.length" class="alert alert-danger alert-dismissible fade show mb-3" style="border-radius:10px;">
        <strong><i class="fas fa-exclamation-triangle me-1"></i> Erreurs de validation :</strong>
        <ul class="mb-0 mt-2">
          <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
        </ul>
        <button type="button" class="btn-close" @click="errors = []"></button>
      </div>

      <form @submit.prevent="handleSubmit">

        <!-- Section 1: Contenu -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#0891b2,#0e7490)">
              <i class="fas fa-file-signature"></i>
            </div>
            <div>
              <h5>Contenu du communiqué</h5>
              <small>Titre et corps du message</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label for="titre" class="form-label fw-bold">Titre du communiqué <span class="text-danger">*</span></label>
              <input v-model="form.titre" type="text" class="form-control" id="titre" required
                     placeholder="Ex: Communiqué relatif aux congés annuels">
            </div>
            <div class="col-12">
              <label for="contenu" class="form-label fw-bold">Contenu <span class="text-danger">*</span></label>
              <textarea v-model="form.contenu" class="form-control" id="contenu" rows="8" required
                        placeholder="Rédigez le contenu du communiqué..."></textarea>
            </div>
          </div>
        </div>

        <!-- Section 2: Paramètres -->
        <div class="form-section">
          <div class="form-section-header">
            <div class="form-section-icon" style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
              <i class="fas fa-cog"></i>
            </div>
            <div>
              <h5>Paramètres de publication</h5>
              <small>Urgence, signataire et expiration</small>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="urgence" class="form-label fw-bold">Niveau d'urgence <span class="text-danger">*</span></label>
              <select v-model="form.urgence" class="form-select" id="urgence" required>
                <option value="normal">Normal</option>
                <option value="important">Important</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="signataire" class="form-label fw-bold">Signataire (au nom de)</label>
              <input v-model="form.signataire" type="text" class="form-control" id="signataire"
                     placeholder="Ex: Le Secrétaire Exécutif National">
            </div>
            <div class="col-md-4">
              <label for="date_expiration" class="form-label fw-bold">Date d'expiration</label>
              <input v-model="form.date_expiration" type="date" class="form-control" id="date_expiration">
              <small class="text-muted">Laisser vide pour un communiqué sans expiration.</small>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input v-model="form.actif" class="form-check-input" type="checkbox" id="actif">
                <label class="form-check-label" for="actif">
                  Publier immédiatement (actif)
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-3 justify-content-end">
          <router-link :to="{ name: 'rh.communiques.index' }" class="btn btn-cancel">
            <i class="fas fa-times me-1"></i> Annuler
          </router-link>
          <button type="submit" class="btn btn-submit" :disabled="submitting">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-paper-plane me-1"></i>
            {{ isEdit ? 'Mettre à jour' : 'Publier le communiqué' }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { create, update, get } from '@/api/communiques'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const isEdit = computed(() => !!route.query.edit)
const loadingEdit = ref(false)
const submitting = ref(false)
const errors = ref([])
const form = ref({
  titre: '',
  contenu: '',
  urgence: 'normal',
  signataire: '',
  date_expiration: '',
  actif: true,
})

async function loadCommunique() {
  if (!route.query.edit) return
  loadingEdit.value = true
  try {
    const { data } = await get(route.query.edit)
    const c = data.data
    form.value = {
      titre: c.titre || '',
      contenu: c.contenu || '',
      urgence: c.urgence || 'normal',
      signataire: c.signataire || '',
      date_expiration: c.date_expiration ? c.date_expiration.split('T')[0] : '',
      actif: !!c.actif,
    }
  } catch {
    ui.addToast('Communique introuvable.', 'danger')
    router.push({ name: 'rh.communiques.index' })
  } finally {
    loadingEdit.value = false
  }
}

async function handleSubmit() {
  errors.value = []
  submitting.value = true
  try {
    if (isEdit.value) {
      await update(route.query.edit, form.value)
      ui.addToast('Communique mis a jour avec succes.', 'success')
    } else {
      await create(form.value)
      ui.addToast('Communique publie avec succes.', 'success')
    }
    router.push({ name: 'rh.communiques.index' })
  } catch (err) {
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors || {}
      errors.value = Object.values(validationErrors).flat()
    } else {
      ui.addToast(err.response?.data?.message || 'Erreur lors de l\'operation.', 'danger')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => loadCommunique())
</script>

<style scoped>
.communique-hero {
    background: linear-gradient(135deg, #0077B5 0%, #005885 100%);
    color: #fff;
    border-radius: 16px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}
.communique-hero::after {
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
.communique-hero h2 { font-weight: 700; margin-bottom: .25rem; }
.communique-hero p { opacity: .85; margin-bottom: 0; }
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
.btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,119,181,.35);
    color: #fff;
}
.btn-submit:disabled { opacity: .7; cursor: not-allowed; }
.btn-cancel {
    background: #f2f4f7; color: #344054; font-weight: 600;
    border: 1.5px solid #d0d5dd; border-radius: 10px;
    padding: .7rem 2rem; text-decoration: none;
    transition: background .15s;
}
.btn-cancel:hover { background: #e4e7ec; color: #1a1a2e; }

@media (max-width: 767.98px) {
    .communique-hero {
        padding: 1.25rem 1rem;
        border-radius: 10px;
    }
    .communique-hero::after {
        width: 120px;
        height: 120px;
        right: -10px;
    }
    .communique-hero h2 { font-size: 1.2rem; }
    .form-section {
        padding: 1.25rem 1rem;
        border-radius: 10px;
    }
    .form-section-header h5 { font-size: 1rem; }
    .form-section-icon { width: 36px; height: 36px; font-size: 0.95rem; }
    .btn-submit, .btn-cancel { padding: 0.6rem 1.25rem; font-size: 0.9rem; }
    .d-flex.gap-3.justify-content-end {
        flex-direction: column;
    }
    .d-flex.gap-3.justify-content-end .btn {
        width: 100%;
    }
}
</style>
