export const DOCUMENT_CATEGORY_OPTIONS = [
  {
    value: 'identite',
    label: 'Identité',
    fullLabel: 'Identité',
    description: "Carte d'identité, photo passeport, acte de naissance, fiche d'identification, carte professionnelle.",
    icon: 'fas fa-id-card',
    color: 'text-danger',
    style: 'background:#e8f4fd;color:#0077B5;',
  },
  {
    value: 'parcours',
    label: 'Parcours académique',
    fullLabel: 'Parcours académique et formation',
    description: 'Diplôme, brevet, certification, attestation de formation.',
    icon: 'fas fa-graduation-cap',
    color: 'text-info',
    style: 'background:#fff3e0;color:#e67e22;',
  },
  {
    value: 'carriere',
    label: 'Carrière administrative',
    fullLabel: 'Carrière administrative',
    description: "Affectation, mise en service, acte d'engagement, décision d'engagement, nomination, promotion, mutation, suspension, retraite.",
    icon: 'fas fa-briefcase',
    color: 'text-warning',
    style: 'background:#ede9fe;color:#7c3aed;',
  },
  {
    value: 'gestion_rh',
    label: 'Gestion RH',
    fullLabel: 'Gestion RH',
    description: "Contrat de travail, fiche d'évaluation, fiche de présence, demande de congé, autorisation de congé.",
    icon: 'fas fa-users-cog',
    color: 'text-success',
    style: 'background:#e6f7ef;color:#28a745;',
  },
  {
    value: 'documents_legaux',
    label: 'Documents légaux',
    fullLabel: 'Documents légaux',
    description: 'Casier judiciaire, certificat médical, attestation de bonne conduite, vie et moeurs.',
    icon: 'fas fa-balance-scale',
    color: 'text-primary',
    style: 'background:#e0f2fe;color:#0284c7;',
  },
  {
    value: 'autres',
    label: 'Autres',
    fullLabel: 'Autres',
    description: 'Ordre de mission, note de service, notification, documents bancaires, documents familiaux, autres.',
    icon: 'fas fa-folder-open',
    color: 'text-secondary',
    style: 'background:#f1f5f9;color:#64748b;',
  },
]

const LEGACY_CATEGORY_MAP = {
  mission: 'autres',
}

export function normalizeDocumentCategory(type) {
  if (!type) return ''
  return LEGACY_CATEGORY_MAP[type] || type
}

export function getDocumentCategory(type) {
  const normalized = normalizeDocumentCategory(type)
  return DOCUMENT_CATEGORY_OPTIONS.find((category) => category.value === normalized)
}

export function getDocumentCategoryLabel(type) {
  return getDocumentCategory(type)?.label || type || '-'
}

export function getDocumentCategoryFullLabel(type) {
  return getDocumentCategory(type)?.fullLabel || getDocumentCategoryLabel(type)
}

export function getDocumentCategoryIcon(type) {
  return getDocumentCategory(type)?.icon || 'fas fa-file'
}
