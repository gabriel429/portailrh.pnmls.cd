<template>
  <div class="agent-import-page">
    <section class="import-hero">
      <div>
        <p class="hero-kicker">Administration</p>
        <h1>Importer des agents depuis Excel</h1>
        <p class="hero-copy">
          Charge un fichier .xlsx ou .csv, controle les colonnes attendues et remonte les erreurs ligne par ligne.
        </p>
      </div>

      <div class="hero-actions">
        <button class="btn btn-light" type="button" :disabled="downloadingTemplate" @click="downloadTemplate">
          <span v-if="downloadingTemplate" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="fas fa-download me-2"></i>
          Telecharger le modele
        </button>
        <router-link :to="{ name: 'admin.dashboard' }" class="btn btn-outline-light">
          <i class="fas fa-arrow-left me-2"></i>
          Retour admin
        </router-link>
      </div>
    </section>

    <div class="row g-4 mt-1">
      <div class="col-lg-7">
        <div class="import-card">
          <div class="import-card-head">
            <div>
              <h2>Fichier a importer</h2>
              <p>Le systeme accepte les formats .xlsx et .csv. Les fichiers .xls ne sont pas supportes.</p>
            </div>
            <span class="format-pill">.xlsx / .csv</span>
          </div>

          <label class="dropzone" :class="{ 'has-file': selectedFile }" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop">
            <input ref="fileInput" type="file" class="d-none" accept=".xlsx,.csv,text/csv" @change="handleFileChange">
            <div class="dropzone-inner" @click="openFilePicker">
              <i class="fas" :class="selectedFile ? 'fa-file-excel' : 'fa-cloud-upload-alt'"></i>
              <strong>{{ selectedFile ? selectedFile.name : 'Cliquer ou deposer le fichier ici' }}</strong>
              <span>
                {{ selectedFile ? fileDescription : 'Premiere ligne: en-tetes. Lignes suivantes: donnees agents.' }}
              </span>
            </div>
          </label>

          <div class="alert alert-info mt-3 mb-0">
            <i class="fas fa-circle-info me-2"></i>
            Les champs obligatoires sont: nom, prenom, sexe, lieu_naissance, organe, fonction, niveau_etudes et annee_engagement_programme.
          </div>

          <div class="import-actions">
            <button class="btn btn-primary" type="button" :disabled="!selectedFile || importing" @click="submitImport">
              <span v-if="importing" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-file-import me-2"></i>
              Lancer l'import
            </button>
            <button v-if="selectedFile" class="btn btn-outline-secondary" type="button" :disabled="importing" @click="resetFile">
              Reinitialiser
            </button>
          </div>
        </div>

        <div v-if="result" class="import-card mt-4">
          <div class="import-card-head">
            <div>
              <h2>Resultat de l'import</h2>
              <p>{{ result.message }}</p>
            </div>
          </div>

          <div class="summary-grid">
            <div class="summary-item summary-total">
              <span>{{ result.summary?.total_rows ?? 0 }}</span>
              <small>Lignes lues</small>
            </div>
            <div class="summary-item summary-success">
              <span>{{ result.summary?.imported ?? 0 }}</span>
              <small>Agents importes</small>
            </div>
            <div class="summary-item summary-warning">
              <span>{{ result.summary?.skipped ?? 0 }}</span>
              <small>Lignes ignorees</small>
            </div>
          </div>

          <div v-if="result.errors?.length" class="error-table-wrap mt-4">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Ligne</th>
                  <th>Champ</th>
                  <th>Message</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(error, index) in result.errors" :key="`${error.row}-${error.field}-${index}`">
                  <td>{{ error.row }}</td>
                  <td><span class="error-field">{{ error.field }}</span></td>
                  <td>{{ error.message }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="alert alert-success mt-4 mb-0">
            <i class="fas fa-check-circle me-2"></i>
            Aucun rejet detecte sur ce fichier.
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="import-card guide-card">
          <div class="import-card-head">
            <div>
              <h2>Colonnes attendues</h2>
              <p>Tu peux garder uniquement les colonnes utiles, tant que les obligatoires sont presentes.</p>
            </div>
          </div>

          <div class="column-groups">
            <div>
              <h3>Obligatoires</h3>
              <ul>
                <li v-for="column in requiredColumns" :key="column">{{ column }}</li>
              </ul>
            </div>
            <div>
              <h3>Optionnelles</h3>
              <ul>
                <li v-for="column in optionalColumns" :key="column">{{ column }}</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="import-card mt-4 guide-card">
          <div class="import-card-head">
            <div>
              <h2>Regles de correspondance</h2>
            </div>
          </div>

          <div class="rules-list">
            <div class="rule-item">
              <strong>Organe</strong>
              <span>Accepte SEN, SEP, SEL ou les libelles complets.</span>
            </div>
            <div class="rule-item">
              <strong>Fonction</strong>
              <span>Doit correspondre exactement a une fonction existante dans la base.</span>
            </div>
            <div class="rule-item">
              <strong>Niveau d'etudes</strong>
              <span>Doit correspondre a une valeur deja definie dans l'application.</span>
            </div>
            <div class="rule-item">
              <strong>Province / departement / grade</strong>
              <span>Recherche par nom. Si la valeur n'existe pas, la ligne est rejetee.</span>
            </div>
            <div class="rule-item">
              <strong>Dates</strong>
              <span>Formats acceptes: AAAA-MM-JJ ou JJ/MM/AAAA.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()

const fileInput = ref(null)
const selectedFile = ref(null)
const importing = ref(false)
const downloadingTemplate = ref(false)
const result = ref(null)
const isDragging = ref(false)

const requiredColumns = [
  'nom',
  'prenom',
  'sexe',
  'lieu_naissance',
  'organe',
  'fonction',
  'niveau_etudes',
  'annee_engagement_programme',
  'annee_naissance ou date_naissance',
]

const optionalColumns = [
  'matricule_etat',
  'postnom',
  'situation_familiale',
  'nombre_enfants',
  'telephone',
  'adresse',
  'email_prive',
  'email_professionnel',
  'province',
  'departement',
  'grade',
  'institution',
  'domaine_etudes',
  'statut',
]

const fileDescription = computed(() => {
  if (!selectedFile.value) return ''
  const sizeInMb = (selectedFile.value.size / (1024 * 1024)).toFixed(2)
  return `${sizeInMb} Mo`
})

function openFilePicker() {
  fileInput.value?.click()
}

function handleFileChange(event) {
  const [file] = event.target.files || []
  assignFile(file)
}

function handleDrop(event) {
  isDragging.value = false
  const [file] = event.dataTransfer?.files || []
  assignFile(file)
}

function assignFile(file) {
  if (!file) return

  const validExtensions = ['xlsx', 'csv']
  const extension = file.name.split('.').pop()?.toLowerCase()
  if (!validExtensions.includes(extension)) {
    ui.addToast('Format non supporte. Utilise un fichier .xlsx ou .csv.', 'danger')
    return
  }

  selectedFile.value = file
  result.value = null
}

function resetFile() {
  selectedFile.value = null
  result.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

async function downloadTemplate() {
  downloadingTemplate.value = true
  try {
    const response = await client.get('/admin/agents/import-template', {
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'modele_import_agents.csv')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    ui.addToast('Impossible de telecharger le modele.', 'danger')
  } finally {
    downloadingTemplate.value = false
  }
}

async function submitImport() {
  if (!selectedFile.value) {
    ui.addToast('Selectionne un fichier avant de lancer l\'import.', 'warning')
    return
  }

  importing.value = true
  result.value = null

  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    const { data } = await client.post('/admin/agents/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    result.value = data

    if ((data.summary?.imported ?? 0) > 0) {
      ui.addToast(`${data.summary.imported} agent(s) importe(s) avec succes.`, 'success')
    }

    if ((data.summary?.skipped ?? 0) > 0) {
      ui.addToast(`${data.summary.skipped} ligne(s) ont ete rejetee(s).`, 'warning')
    }
  } catch (error) {
    const message = error.response?.data?.message || error.response?.data?.errors?.file?.[0] || 'Erreur lors de l\'import.'
    ui.addToast(message, 'danger')
  } finally {
    importing.value = false
  }
}
</script>

<style scoped>
.agent-import-page {
  color: #0f172a;
}

.import-hero {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
  padding: 1.75rem;
  border-radius: 20px;
  background: linear-gradient(135deg, #0f4c81 0%, #2563eb 58%, #60a5fa 100%);
  color: #fff;
  box-shadow: 0 20px 45px rgba(37, 99, 235, 0.18);
}

.hero-kicker {
  margin-bottom: 0.45rem;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.75);
}

.import-hero h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 800;
}

.hero-copy {
  max-width: 640px;
  margin: 0.75rem 0 0;
  color: rgba(255, 255, 255, 0.86);
}

.hero-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.import-card {
  padding: 1.4rem;
  border-radius: 18px;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}

.import-card-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.import-card-head h2 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
}

.import-card-head p {
  margin: 0.35rem 0 0;
  color: #64748b;
}

.format-pill {
  padding: 0.4rem 0.7rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #1d4ed8;
  background: #dbeafe;
}

.dropzone {
  display: block;
  border: 1.5px dashed #93c5fd;
  border-radius: 18px;
  background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%);
  cursor: pointer;
}

.dropzone.has-file {
  border-color: #10b981;
  background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%);
}

.dropzone-inner {
  padding: 2rem 1.25rem;
  text-align: center;
}

.dropzone-inner i {
  display: block;
  margin-bottom: 0.85rem;
  font-size: 2rem;
  color: #2563eb;
}

.dropzone.has-file .dropzone-inner i {
  color: #059669;
}

.dropzone-inner strong {
  display: block;
  font-size: 1rem;
}

.dropzone-inner span {
  display: block;
  margin-top: 0.4rem;
  color: #64748b;
}

.import-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 1rem;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.9rem;
}

.summary-item {
  padding: 1rem;
  border-radius: 16px;
  text-align: center;
}

.summary-item span {
  display: block;
  font-size: 1.8rem;
  font-weight: 800;
}

.summary-item small {
  color: #475569;
  font-weight: 600;
}

.summary-total {
  background: #eff6ff;
  color: #1d4ed8;
}

.summary-success {
  background: #ecfdf5;
  color: #047857;
}

.summary-warning {
  background: #fff7ed;
  color: #c2410c;
}

.error-table-wrap {
  max-height: 420px;
  overflow: auto;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
}

.error-table-wrap thead th {
  position: sticky;
  top: 0;
  background: #f8fafc;
}

.error-field {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  background: #fee2e2;
  color: #b91c1c;
  font-weight: 700;
  font-size: 0.78rem;
}

.guide-card h3 {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
  font-weight: 800;
}

.column-groups {
  display: grid;
  gap: 1.2rem;
}

.column-groups ul {
  padding-left: 1rem;
  margin: 0;
  color: #334155;
}

.column-groups li + li {
  margin-top: 0.45rem;
}

.rules-list {
  display: grid;
  gap: 0.9rem;
}

.rule-item {
  padding: 0.95rem 1rem;
  border-radius: 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.rule-item strong {
  display: block;
  margin-bottom: 0.2rem;
}

.rule-item span {
  color: #64748b;
}

@media (max-width: 991.98px) {
  .import-hero {
    flex-direction: column;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>