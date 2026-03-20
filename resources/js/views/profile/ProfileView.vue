<template>
  <div class="container py-4">
    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="text-muted mt-3">Chargement du profil...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <!-- Profile Content -->
    <div v-else-if="agent" class="row">

      <!-- Left Sidebar -->
      <div class="col-lg-4 mb-4">

        <!-- Main Profile Card -->
        <div class="profile-main-card">
          <div class="profile-cover">
            <div class="cover-pattern"></div>
          </div>
          <div class="profile-avatar-wrap">
            <div class="profile-avatar">
              <img v-if="agent.photo" :src="'/' + agent.photo" :alt="agent.prenom">
              <span v-else class="avatar-initials">{{ initials }}</span>
            </div>
          </div>
          <div class="profile-identity">
            <h2>{{ agent.prenom }} {{ agent.postnom }} {{ agent.nom }}</h2>
            <div class="poste">{{ posteActuel }}</div>
            <div class="organe">{{ agent.organe || '' }}</div>

            <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
              <span v-if="agent.matricule_pnmls" class="matricule">{{ agent.matricule_pnmls }}</span>
              <span :class="['status-badge', agent.statut || 'actif']">
                <span class="status-dot"></span>
                {{ capitalize(agent.statut || 'actif') }}
              </span>
            </div>

            <div v-if="agent.role" class="mt-2">
              <span class="badge bg-light text-dark border" style="font-size:.8rem;">
                <i class="fas fa-shield-alt me-1 text-primary"></i>{{ agent.role.nom_role }}
              </span>
            </div>
          </div>

          <div class="profile-actions">
            <router-link :to="{ name: 'profile.edit' }" class="btn btn-edit-profile">
              <i class="fas fa-pen me-2"></i>Modifier mon profil
            </router-link>
          </div>
        </div>

        <!-- Contact Info Card -->
        <div class="sidebar-card">
          <div class="sidebar-card-header">
            <i class="fas fa-address-book me-2 text-primary"></i>Coordonnees
          </div>
          <div class="sidebar-card-body">
            <div class="info-item">
              <div class="info-item-icon"><i class="fas fa-envelope"></i></div>
              <div>
                <div class="info-item-label">Email professionnel</div>
                <div class="info-item-value">{{ agent.email_professionnel || 'N/A' }}</div>
              </div>
            </div>
            <div v-if="agent.email_prive" class="info-item">
              <div class="info-item-icon" style="background:#fef3e2;color:#e67e22;">
                <i class="fas fa-envelope-open"></i>
              </div>
              <div>
                <div class="info-item-label">Email prive</div>
                <div class="info-item-value">{{ agent.email_prive }}</div>
              </div>
            </div>
            <div class="info-item">
              <div class="info-item-icon" style="background:#e8f8e8;color:#28a745;">
                <i class="fas fa-phone"></i>
              </div>
              <div>
                <div class="info-item-label">Telephone</div>
                <div class="info-item-value">{{ agent.telephone || 'N/A' }}</div>
              </div>
            </div>
            <div class="info-item">
              <div class="info-item-icon" style="background:#f3e8ff;color:#7c3aed;">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div>
                <div class="info-item-label">Adresse</div>
                <div class="info-item-value">{{ agent.adresse || 'N/A' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Stats Card -->
        <div class="sidebar-card">
          <div class="sidebar-card-header">
            <i class="fas fa-chart-bar me-2 text-primary"></i>Apercu
          </div>
          <div class="sidebar-card-body">
            <div class="row g-2">
              <div class="col-6">
                <div class="stat-badge">
                  <div class="stat-number">{{ stats.documents }}</div>
                  <div class="stat-label">Documents</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-badge">
                  <div class="stat-number">{{ stats.affectations }}</div>
                  <div class="stat-label">Affectations</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-badge">
                  <div class="stat-number" style="color:#28a745;">{{ stats.pointages }}</div>
                  <div class="stat-label">Pointages</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-badge">
                  <div class="stat-number" style="color:#e67e22;">{{ stats.demandes }}</div>
                  <div class="stat-label">Demandes</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Content Column -->
      <div class="col-lg-8">

        <!-- Personal Info -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon" style="background:linear-gradient(135deg,#0077B5,#005885);">
              <i class="fas fa-user"></i>
            </div>
            <h5>Profil Personnel</h5>
          </div>
          <div class="info-card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon"><i class="fas fa-calendar"></i></div>
                  <div>
                    <div class="info-item-label">Date de naissance</div>
                    <div class="info-item-value">{{ formatDate(agent.date_naissance) || agent.annee_naissance || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon"><i class="fas fa-map-pin"></i></div>
                  <div>
                    <div class="info-item-label">Lieu de naissance</div>
                    <div class="info-item-value">{{ agent.lieu_naissance || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#fce4ec;color:#e91e63;">
                    <i class="fas fa-venus-mars"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Sexe</div>
                    <div class="info-item-value">{{ formatSexe(agent.sexe) }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#e8f5e9;color:#4caf50;">
                    <i class="fas fa-heart"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Situation familiale</div>
                    <div class="info-item-value">{{ capitalize(agent.situation_familiale) || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#fff3e0;color:#ff9800;">
                    <i class="fas fa-child"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Nombre d'enfants</div>
                    <div class="info-item-value">{{ agent.nombre_enfants ?? 0 }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Professional Info -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon" style="background:linear-gradient(135deg,#7c3aed,#5b21b6);">
              <i class="fas fa-briefcase"></i>
            </div>
            <h5>Informations Professionnelles</h5>
          </div>
          <div class="info-card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#ede9fe;color:#7c3aed;">
                    <i class="fas fa-id-badge"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Fonction / Poste</div>
                    <div class="info-item-value">{{ agent.fonction || agent.poste_actuel || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#ede9fe;color:#7c3aed;">
                    <i class="fas fa-building"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Organe</div>
                    <div class="info-item-value">{{ agent.organe || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#e3f2fd;color:#1976d2;">
                    <i class="fas fa-sitemap"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Departement</div>
                    <div class="info-item-value">{{ agent.departement?.nom || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#e3f2fd;color:#1976d2;">
                    <i class="fas fa-map"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Province</div>
                    <div class="info-item-value">{{ provinceName }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#e8f5e9;color:#388e3c;">
                    <i class="fas fa-calendar-check"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Date d'embauche</div>
                    <div class="info-item-value">{{ formatDate(agent.date_embauche) || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#fff8e1;color:#f9a825;">
                    <i class="fas fa-star"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Grade de l'Etat</div>
                    <div class="info-item-value">{{ agent.grade?.libelle || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div v-if="agent.matricule_etat" class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#fce4ec;color:#c62828;">
                    <i class="fas fa-hashtag"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Matricule Etat</div>
                    <div class="info-item-value">{{ agent.matricule_etat }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#e8eaf6;color:#3949ab;">
                    <i class="fas fa-university"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Provenance matricule</div>
                    <div class="info-item-value">{{ agent.institution?.nom || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div v-if="agent.annee_engagement_programme" class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#e0f2f1;color:#00897b;">
                    <i class="fas fa-handshake"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Annee engagement programme</div>
                    <div class="info-item-value">{{ agent.annee_engagement_programme }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Formation -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon" style="background:linear-gradient(135deg,#e67e22,#d35400);">
              <i class="fas fa-graduation-cap"></i>
            </div>
            <h5>Formation</h5>
          </div>
          <div class="info-card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#fff3e0;color:#e67e22;">
                    <i class="fas fa-graduation-cap"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Niveau d'etudes</div>
                    <div class="info-item-value">{{ agent.niveau_etudes || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <div class="info-item-icon" style="background:#fff3e0;color:#e67e22;">
                    <i class="fas fa-book"></i>
                  </div>
                  <div>
                    <div class="info-item-label">Domaine d'etudes</div>
                    <div class="info-item-value">{{ agent.domaine_etudes || 'N/A' }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Career Timeline -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon" style="background:linear-gradient(135deg,#00897b,#00695c);">
              <i class="fas fa-route"></i>
            </div>
            <h5>
              Parcours
              <span class="badge bg-light text-dark ms-2" style="font-size:.75rem;">{{ sortedAffectations.length }}</span>
            </h5>
          </div>
          <div class="info-card-body">
            <div v-if="sortedAffectations.length > 0" class="profile-timeline">
              <div
                v-for="aff in sortedAffectations"
                :key="aff.id"
                class="profile-tl-item"
              >
                <div :class="['profile-tl-dot', aff.actif ? 'active' : 'ended']"></div>
                <div :class="['profile-tl-card', aff.actif ? 'current' : '']">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1" style="font-size:.95rem;">
                        {{ aff.fonction?.nom || 'Fonction non definie' }}
                      </h6>
                      <div class="d-flex gap-2 flex-wrap mb-1">
                        <span class="badge bg-primary" style="font-size:.68rem;">{{ aff.niveau_administratif_label || '' }}</span>
                        <span v-if="aff.actif" class="badge bg-success" style="font-size:.68rem;">Poste actuel</span>
                        <span v-else class="badge bg-secondary" style="font-size:.68rem;">Termine</span>
                        <span v-if="affDuration(aff)" class="badge bg-warning text-dark" style="font-size:.68rem;">
                          <i class="fas fa-clock me-1"></i>{{ affDuration(aff) }}
                        </span>
                      </div>
                    </div>
                    <small class="text-muted text-end" style="white-space:nowrap;">
                      {{ formatMonthYear(aff.date_debut) || '?' }}
                      <br>
                      <template v-if="aff.date_fin">{{ formatMonthYear(aff.date_fin) }}</template>
                      <template v-else-if="aff.actif"><span class="text-success fw-semibold">Present</span></template>
                    </small>
                  </div>
                  <small v-if="aff.department" class="text-muted d-block mt-1">
                    <i class="fas fa-building me-1"></i>{{ aff.department.nom }}
                  </small>
                  <small v-if="aff.province" class="text-muted d-block">
                    <i class="fas fa-map-marker-alt me-1"></i>{{ aff.province.nom }}
                  </small>
                  <small v-if="aff.remarque" class="text-muted fst-italic d-block mt-1">
                    <i class="fas fa-comment me-1"></i>{{ aff.remarque }}
                  </small>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4">
              <i class="fas fa-route fa-2x text-muted mb-2 d-block opacity-50"></i>
              <p class="text-muted small mb-0">Aucun parcours enregistre</p>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon" style="background:linear-gradient(135deg,#28a745,#1e7e34);">
              <i class="fas fa-folder-open"></i>
            </div>
            <h5>
              Documents
              <span class="badge bg-light text-dark ms-2" style="font-size:.75rem;">{{ agent.documents?.length || 0 }}</span>
            </h5>
          </div>
          <div class="info-card-body">
            <template v-if="agent.documents && agent.documents.length > 0">
              <div class="table-responsive">
                <table class="table doc-table align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Document</th>
                      <th>Categorie</th>
                      <th>Date</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="doc in agent.documents.slice(0, 5)" :key="doc.id">
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <div style="width:32px;height:32px;border-radius:8px;background:#e6f3ff;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-file-alt text-primary" style="font-size:.85rem;"></i>
                          </div>
                          <span class="fw-600" style="font-size:.9rem;">{{ truncate(doc.nom_document, 30) }}</span>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-light text-dark border" style="font-size:.78rem;">
                          {{ doc.categorie }}
                        </span>
                      </td>
                      <td style="font-size:.85rem;color:#6c757d;">{{ formatDate(doc.created_at) }}</td>
                      <td class="text-end">
                        <a :href="'/documents/' + doc.id + '/download'" class="btn btn-dl btn-sm">
                          <i class="fas fa-download"></i>
                        </a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="agent.documents.length > 5" class="text-center mt-3">
                <router-link :to="{ name: 'documents.index' }" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                  <i class="fas fa-arrow-right me-1"></i>Voir tous les documents
                </router-link>
              </div>
            </template>

            <div v-else class="text-center py-4">
              <div style="width:64px;height:64px;border-radius:50%;background:#f0f7ff;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
                <i class="fas fa-folder-open text-primary" style="font-size:1.5rem;"></i>
              </div>
              <p class="text-muted mb-0">Aucun document pour le moment</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getProfile } from '@/api/profile'

const loading = ref(true)
const error = ref(null)
const agent = ref(null)
const stats = ref({ documents: 0, affectations: 0, pointages: 0, demandes: 0 })

const initials = computed(() => {
  if (!agent.value) return ''
  const p = (agent.value.prenom || '').charAt(0).toUpperCase()
  const n = (agent.value.nom || '').charAt(0).toUpperCase()
  return p + n
})

const posteActuel = computed(() => {
  if (!agent.value) return ''
  const activeAff = (agent.value.affectations || []).find(a => a.actif)
  return activeAff?.fonction?.nom || agent.value.fonction || agent.value.poste_actuel || 'Poste non defini'
})

const provinceName = computed(() => {
  if (!agent.value) return 'N/A'
  if (agent.value.province?.nom) return agent.value.province.nom
  if ((agent.value.organe || '').includes('National')) return 'National'
  return 'N/A'
})

const sortedAffectations = computed(() => {
  if (!agent.value?.affectations) return []
  return [...agent.value.affectations].sort((a, b) => {
    const da = a.date_debut ? new Date(a.date_debut) : new Date(0)
    const db = b.date_debut ? new Date(b.date_debut) : new Date(0)
    return db - da
  })
})

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function formatDate(dateStr) {
  if (!dateStr) return null
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return null
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatMonthYear(dateStr) {
  if (!dateStr) return null
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return null
  return d.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' })
}

function formatSexe(val) {
  if (val === 'M') return 'Masculin'
  if (val === 'F') return 'Feminin'
  return val || 'N/A'
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

function affDuration(aff) {
  if (!aff.date_debut) return null
  const start = new Date(aff.date_debut)
  const end = aff.date_fin ? new Date(aff.date_fin) : new Date()
  let months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth())
  const years = Math.floor(months / 12)
  months = months % 12
  let result = ''
  if (years > 0) result += years + ' an' + (years > 1 ? 's' : '')
  if (months > 0) result += (result ? ' ' : '') + months + ' mois'
  if (!result) {
    const days = Math.max(1, Math.floor((end - start) / (1000 * 60 * 60 * 24)))
    result = days + ' jour' + (days > 1 ? 's' : '')
  }
  return result
}

async function fetchProfile() {
  loading.value = true
  error.value = null
  try {
    const { data } = await getProfile()
    agent.value = data.agent
    stats.value = data.stats
  } catch (err) {
    error.value = err.response?.data?.message || 'Impossible de charger le profil.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchProfile)
</script>

<style scoped>
/* Cover & Avatar */
.profile-cover {
  background: linear-gradient(135deg, #0077B5 0%, #005885 50%, #00394f 100%);
  height: 200px;
  border-radius: 16px 16px 0 0;
  position: relative;
  overflow: hidden;
}
.profile-cover::after {
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
.profile-cover .cover-pattern {
  position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  border-radius: 16px 16px 0 0;
}
.profile-main-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,.08);
  overflow: hidden;
  margin-bottom: 1.5rem;
}
.profile-avatar-wrap {
  display: flex;
  align-items: flex-end;
  padding: 0 2rem;
  margin-top: -75px;
  position: relative;
  z-index: 2;
}
.profile-avatar {
  width: 150px; height: 150px;
  border-radius: 50%;
  border: 5px solid #fff;
  box-shadow: 0 4px 14px rgba(0,0,0,.12);
  background: #e9ecef;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
.avatar-initials {
  font-size: 3rem;
  font-weight: 700;
  color: #0077B5;
  user-select: none;
}
.profile-identity {
  padding: 1.5rem 2rem 1.25rem;
}
.profile-identity h2 { font-weight: 700; color: #1a1a2e; margin-bottom: .15rem; }
.profile-identity .poste { color: #0077B5; font-weight: 600; font-size: 1.05rem; }
.profile-identity .organe { color: #6c757d; font-size: .95rem; }
.profile-identity .matricule {
  display: inline-block;
  background: linear-gradient(135deg, #0077B5, #005885);
  color: #fff;
  font-weight: 700;
  font-size: .8rem;
  padding: .25rem .75rem;
  border-radius: 20px;
  margin-top: .5rem;
  letter-spacing: .5px;
}
.profile-actions { padding: 0 2rem 1.5rem; }
.btn-edit-profile {
  background: linear-gradient(135deg, #0077B5, #005885);
  border: none; color: #fff; font-weight: 600;
  padding: .5rem 1.5rem; border-radius: 10px;
  transition: transform .15s, box-shadow .15s;
  text-decoration: none;
  display: inline-block;
}
.btn-edit-profile:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(0,119,181,.35);
  color: #fff;
}

/* Info Cards */
.info-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #e9ecef;
  margin-bottom: 1.5rem;
  overflow: hidden;
}
.info-card-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 2px solid #f0f2f5;
}
.info-card-icon {
  width: 40px; height: 40px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; color: #fff; flex-shrink: 0;
}
.info-card-header h5 { font-weight: 700; margin-bottom: 0; color: #1a1a2e; font-size: 1.05rem; }
.info-card-body { padding: 1.5rem; }

/* Info Items */
.info-item {
  display: flex;
  align-items: flex-start;
  gap: .75rem;
  padding: .65rem 0;
}
.info-item + .info-item { border-top: 1px solid #f5f5f5; }
.info-item-icon {
  width: 32px; height: 32px;
  border-radius: 8px;
  background: #f0f7ff;
  display: flex; align-items: center; justify-content: center;
  color: #0077B5;
  font-size: .85rem;
  flex-shrink: 0;
  margin-top: 2px;
}
.info-item-label { color: #6c757d; font-size: .8rem; font-weight: 500; }
.info-item-value { color: #1a1a2e; font-weight: 600; font-size: .95rem; }

/* Stat Badges */
.stat-badge {
  text-align: center;
  padding: 1rem;
  border-radius: 12px;
  background: #f8f9fc;
  border: 1px solid #e9ecef;
}
.stat-badge .stat-number { font-size: 1.75rem; font-weight: 800; color: #0077B5; }
.stat-badge .stat-label { font-size: .8rem; color: #6c757d; font-weight: 500; }

/* Status Badge */
.status-badge {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .3rem .85rem; border-radius: 20px;
  font-weight: 600; font-size: .85rem;
}
.status-badge.actif { background: #e6f7ef; color: #0d6832; }
.status-badge.suspendu { background: #fff3e0; color: #e65100; }
.status-badge.ancien { background: #f5f5f5; color: #666; }
.status-badge .status-dot {
  width: 8px; height: 8px; border-radius: 50%;
}
.status-badge.actif .status-dot { background: #28a745; }
.status-badge.suspendu .status-dot { background: #ff9800; }
.status-badge.ancien .status-dot { background: #999; }

/* Documents Table */
.doc-table thead th {
  background: #f8f9fc;
  font-weight: 600;
  font-size: .85rem;
  color: #344054;
  border-bottom: 2px solid #e9ecef;
}
.doc-table tbody tr { transition: background .15s; }
.doc-table tbody tr:hover { background: #f0f7ff; }
.doc-table .btn-dl {
  background: #e6f3ff; color: #0077B5;
  border: none; border-radius: 8px;
  padding: .35rem .65rem;
  transition: background .15s;
}
.doc-table .btn-dl:hover { background: #cce5ff; }

/* Sidebar Card */
.sidebar-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1px solid #e9ecef;
  overflow: hidden;
  margin-bottom: 1.5rem;
}
.sidebar-card-header {
  background: linear-gradient(135deg, #f8f9fc, #eef1f6);
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e9ecef;
  font-weight: 700;
  color: #1a1a2e;
  font-size: .95rem;
}
.sidebar-card-body { padding: 1.25rem; }

/* Timeline */
.profile-timeline { position: relative; padding-left: 32px; }
.profile-timeline::before {
  content: '';
  position: absolute;
  left: 14px;
  top: 6px;
  bottom: 6px;
  width: 3px;
  background: linear-gradient(180deg, #00897b 0%, #e0e0e0 100%);
  border-radius: 3px;
}
.profile-tl-item {
  position: relative;
  padding-bottom: 24px;
}
.profile-tl-item:last-child { padding-bottom: 0; }
.profile-tl-dot {
  position: absolute;
  left: -25px;
  top: 4px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 3px solid #fff;
  box-shadow: 0 0 0 2px #ccc;
}
.profile-tl-dot.active {
  background: #28a745;
  box-shadow: 0 0 0 2px #28a745, 0 0 8px rgba(40,167,69,.35);
  animation: tl-pulse 2s infinite;
}
.profile-tl-dot.ended {
  background: #adb5bd;
  box-shadow: 0 0 0 2px #adb5bd;
}
@keyframes tl-pulse {
  0%, 100% { box-shadow: 0 0 0 2px #28a745, 0 0 4px rgba(40,167,69,.2); }
  50% { box-shadow: 0 0 0 2px #28a745, 0 0 12px rgba(40,167,69,.5); }
}
.profile-tl-card {
  background: #f8fafb;
  border-radius: 10px;
  padding: 14px 16px;
  border-left: 3px solid #e0e0e0;
  transition: all .2s;
}
.profile-tl-card:hover {
  background: #f0f7f5;
  border-left-color: #00897b;
}
.profile-tl-card.current {
  border-left-color: #28a745;
  background: #f0fff4;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-cover { height: 140px; }
  .profile-avatar-wrap { padding: 0 1rem; margin-top: -55px; }
  .profile-avatar { width: 110px; height: 110px; }
  .profile-identity { padding: 1rem; }
  .profile-actions { padding: 0 1rem 1rem; }
}
</style>
