<template>
  <div class="rh-modern">
    <div class="rh-shell">
      <section class="rh-hero">
        <div class="row g-3 align-items-center">
          <div class="col-lg-8">
            <h1 class="rh-title">
              <i class="fas fa-bullhorn me-2"></i>
              {{ isEdit ? 'Modifier le Communique' : 'Nouveau Communique' }}
            </h1>
            <p class="rh-sub">{{ isEdit ? 'Modifier ce communique officiel.' : 'Publier un communique a destination de tous les agents.' }}</p>
          </div>
          <div class="col-lg-4">
            <div class="hero-tools">
              <router-link :to="{ name: 'rh.communiques.index' }" class="btn-rh alt">
                <i class="fas fa-arrow-left me-1"></i> Retour
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div v-if="loadingEdit" class="text-center py-5">
        <LoadingSpinner message="Chargement du communique..." />
      </div>

      <div v-else class="dash-panel mt-3">
        <div class="p-4">
          <div v-if="errors.length" class="alert alert-danger">
            <ul class="mb-0">
              <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
            </ul>
          </div>

          <form @submit.prevent="handleSubmit" class="row g-3">
            <!-- Titre -->
            <div class="col-12">
              <label for="titre" class="form-label fw-bold">Titre du communique <span class="text-danger">*</span></label>
              <input v-model="form.titre" type="text" class="form-control" id="titre" required
                     placeholder="Ex: Communique relatif aux conges annuels">
            </div>

            <!-- Contenu -->
            <div class="col-12">
              <label for="contenu" class="form-label fw-bold">Contenu <span class="text-danger">*</span></label>
              <textarea v-model="form.contenu" class="form-control" id="contenu" rows="8" required
                        placeholder="Redigez le contenu du communique..."></textarea>
            </div>

            <!-- Urgence -->
            <div class="col-md-4">
              <label for="urgence" class="form-label fw-bold">Niveau d'urgence <span class="text-danger">*</span></label>
              <select v-model="form.urgence" class="form-select" id="urgence" required>
                <option value="normal">Normal</option>
                <option value="important">Important</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>

            <!-- Signataire -->
            <div class="col-md-4">
              <label for="signataire" class="form-label fw-bold">Signataire (au nom de)</label>
              <input v-model="form.signataire" type="text" class="form-control" id="signataire"
                     placeholder="Ex: Le Secretaire Executif National">
            </div>

            <!-- Date expiration -->
            <div class="col-md-4">
              <label for="date_expiration" class="form-label fw-bold">Date d'expiration</label>
              <input v-model="form.date_expiration" type="date" class="form-control" id="date_expiration">
              <small class="text-muted">Laisser vide pour un communique sans expiration.</small>
            </div>

            <!-- Actif -->
            <div class="col-12">
              <div class="form-check">
                <input v-model="form.actif" class="form-check-input" type="checkbox" id="actif">
                <label class="form-check-label" for="actif">
                  Publier immediatement (actif)
                </label>
              </div>
            </div>

            <!-- Boutons -->
            <div class="col-12 mt-3">
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-paper-plane me-1"></i>
                {{ isEdit ? 'Mettre a jour' : 'Publier le communique' }}
              </button>
              <router-link :to="{ name: 'rh.communiques.index' }" class="btn btn-outline-secondary ms-2">Annuler</router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
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
@media (max-width: 767.98px) {
    .rh-list-card, .dash-panel { border-radius: 12px; padding: 1rem; }
    .card { border-radius: 12px; }
    .card-body { padding: .85rem; }
    .form-label { font-size: .82rem; }
    .btn { font-size: .85rem; }
}
</style>
