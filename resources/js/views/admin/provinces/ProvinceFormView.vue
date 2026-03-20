<template>
  <div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">
          <i class="fas fa-map me-2"></i>{{ isEdit ? 'Modifier la province' : 'Nouvelle province' }}
        </h4>
        <p class="text-muted mb-0">
          {{ isEdit ? 'Modifier les informations de la province' : 'Ajouter une nouvelle province au systeme' }}
        </p>
      </div>
      <router-link to="/admin/provinces" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Retour
      </router-link>
    </div>

    <!-- Loading -->
    <div v-if="loadingData" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement...</p>
    </div>

    <!-- Form -->
    <div v-else class="card border-0 shadow-sm">
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="row g-3">
            <!-- Code -->
            <div class="col-md-4">
              <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
              <input
                id="code"
                v-model="form.code"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.code }"
                placeholder="Ex: KIN"
                required
              />
              <div v-if="errors.code" class="invalid-feedback">{{ errors.code[0] }}</div>
            </div>

            <!-- Nom -->
            <div class="col-md-8">
              <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
              <input
                id="nom"
                v-model="form.nom"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom }"
                placeholder="Nom de la province"
                required
              />
              <div v-if="errors.nom" class="invalid-feedback">{{ errors.nom[0] }}</div>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label for="description" class="form-label">Description</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
                rows="3"
                placeholder="Description de la province (optionnel)"
              ></textarea>
              <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
            </div>

            <!-- Ville secretariat -->
            <div class="col-md-6">
              <label for="ville_secretariat" class="form-label">Ville Secretariat</label>
              <input
                id="ville_secretariat"
                v-model="form.ville_secretariat"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.ville_secretariat }"
                placeholder="Ville du secretariat"
              />
              <div v-if="errors.ville_secretariat" class="invalid-feedback">{{ errors.ville_secretariat[0] }}</div>
            </div>

            <!-- Adresse -->
            <div class="col-md-6">
              <label for="adresse" class="form-label">Adresse</label>
              <input
                id="adresse"
                v-model="form.adresse"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.adresse }"
                placeholder="Adresse physique"
              />
              <div v-if="errors.adresse" class="invalid-feedback">{{ errors.adresse[0] }}</div>
            </div>

            <!-- Nom gouverneur -->
            <div class="col-md-6">
              <label for="nom_gouverneur" class="form-label">Nom du Gouverneur</label>
              <input
                id="nom_gouverneur"
                v-model="form.nom_gouverneur"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom_gouverneur }"
                placeholder="Nom du gouverneur"
              />
              <div v-if="errors.nom_gouverneur" class="invalid-feedback">{{ errors.nom_gouverneur[0] }}</div>
            </div>

            <!-- Nom secretariat executif -->
            <div class="col-md-6">
              <label for="nom_secretariat_executif" class="form-label">Nom du Secretariat Executif</label>
              <input
                id="nom_secretariat_executif"
                v-model="form.nom_secretariat_executif"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom_secretariat_executif }"
                placeholder="Nom du secretariat executif"
              />
              <div v-if="errors.nom_secretariat_executif" class="invalid-feedback">{{ errors.nom_secretariat_executif[0] }}</div>
            </div>

            <!-- Email officiel -->
            <div class="col-md-6">
              <label for="email_officiel" class="form-label">Email Officiel</label>
              <input
                id="email_officiel"
                v-model="form.email_officiel"
                type="email"
                class="form-control"
                :class="{ 'is-invalid': errors.email_officiel }"
                placeholder="email@province.cd"
              />
              <div v-if="errors.email_officiel" class="invalid-feedback">{{ errors.email_officiel[0] }}</div>
            </div>

            <!-- Telephone officiel -->
            <div class="col-md-6">
              <label for="telephone_officiel" class="form-label">Telephone Officiel</label>
              <input
                id="telephone_officiel"
                v-model="form.telephone_officiel"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.telephone_officiel }"
                placeholder="+243 ..."
              />
              <div v-if="errors.telephone_officiel" class="invalid-feedback">{{ errors.telephone_officiel[0] }}</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex justify-content-end gap-2 mt-4">
            <router-link to="/admin/provinces" class="btn btn-outline-secondary">Annuler</router-link>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              {{ isEdit ? 'Mettre a jour' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import client from '@/api/client'

const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const loadingData = ref(false)
const saving = ref(false)
const errors = ref({})

const form = ref({
  code: '',
  nom: '',
  description: '',
  ville_secretariat: '',
  adresse: '',
  nom_gouverneur: '',
  nom_secretariat_executif: '',
  email_officiel: '',
  telephone_officiel: '',
})

async function loadProvince() {
  loadingData.value = true
  try {
    const { data } = await client.get(`/admin/provinces/${route.params.id}`)
    const p = data.data || data
    form.value = {
      code: p.code || '',
      nom: p.nom || '',
      description: p.description || '',
      ville_secretariat: p.ville_secretariat || '',
      adresse: p.adresse || '',
      nom_gouverneur: p.nom_gouverneur || '',
      nom_secretariat_executif: p.nom_secretariat_executif || '',
      email_officiel: p.email_officiel || '',
      telephone_officiel: p.telephone_officiel || '',
    }
  } catch (e) {
    console.error('Erreur chargement province:', e)
    alert('Erreur lors du chargement de la province.')
  } finally {
    loadingData.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await client.put(`/admin/provinces/${route.params.id}`, form.value)
    } else {
      await client.post('/admin/provinces', form.value)
    }
    router.push('/admin/provinces')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      console.error('Erreur sauvegarde province:', e)
      alert('Erreur lors de la sauvegarde de la province.')
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (isEdit.value) {
    loadProvince()
  }
})
</script>
