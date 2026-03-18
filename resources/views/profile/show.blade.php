@extends('layouts.app')

@section('title', ($agent->prenom ?? '') . ' ' . ($agent->nom ?? '') . ' – Profil')

@section('css')
<style>
    /* ── Cover & Avatar ── */
    .profile-cover {
        background: linear-gradient(135deg, #0077B5 0%, #005885 50%, #00394f 100%);
        height: 200px;
        border-radius: 16px 16px 0 0;
        position: relative;
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
    .profile-avatar .avatar-icon { font-size: 4rem; color: #adb5bd; }
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
    }
    .btn-edit-profile:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(0,119,181,.35);
        color: #fff;
    }

    /* ── Info Cards ── */
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

    /* ── Info Items ── */
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

    /* ── Stat Badges ── */
    .stat-badge {
        text-align: center;
        padding: 1rem;
        border-radius: 12px;
        background: #f8f9fc;
        border: 1px solid #e9ecef;
    }
    .stat-badge .stat-number { font-size: 1.75rem; font-weight: 800; color: #0077B5; }
    .stat-badge .stat-label { font-size: .8rem; color: #6c757d; font-weight: 500; }

    /* ── Status Badge ── */
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

    /* ── Documents Table ── */
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

    /* ── Sidebar Card ── */
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

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .profile-cover { height: 140px; }
        .profile-avatar-wrap { padding: 0 1rem; margin-top: -55px; }
        .profile-avatar { width: 110px; height: 110px; }
        .profile-identity { padding: 1rem; }
        .profile-actions { padding: 0 1rem 1rem; }
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="row">

        {{-- ═══ Colonne gauche (Sidebar) ═══ --}}
        <div class="col-lg-4 mb-4">

            {{-- Carte principale profil --}}
            <div class="profile-main-card">
                <div class="profile-cover">
                    <div class="cover-pattern"></div>
                </div>
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar">
                        @if($agent->photo)
                            <img src="{{ asset($agent->photo) }}" alt="{{ $agent->prenom }}">
                        @else
                            <i class="fas fa-user avatar-icon"></i>
                        @endif
                    </div>
                </div>
                <div class="profile-identity">
                    <h2>{{ $agent->prenom }} {{ $agent->postnom }} {{ $agent->nom }}</h2>
                    <div class="poste">{{ $agent->poste_actuel ?? 'Poste non défini' }}</div>
                    <div class="organe">{{ $agent->organe ?? '' }}</div>

                    <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                        @if($agent->matricule_pnmls)
                            <span class="matricule">{{ $agent->matricule_pnmls }}</span>
                        @endif
                        <span class="status-badge {{ $agent->statut ?? 'actif' }}">
                            <span class="status-dot"></span>
                            {{ ucfirst($agent->statut ?? 'actif') }}
                        </span>
                    </div>

                    @if($agent->role)
                        <div class="mt-2">
                            <span class="badge bg-light text-dark border" style="font-size:.8rem;">
                                <i class="fas fa-shield-alt me-1 text-primary"></i>{{ $agent->role->nom_role }}
                            </span>
                        </div>
                    @endif
                </div>

                @if(auth()->id() === $agent->id)
                <div class="profile-actions">
                    <a href="{{ route('profile.edit', $agent) }}" class="btn btn-edit-profile">
                        <i class="fas fa-pen me-2"></i>Modifier mon profil
                    </a>
                </div>
                @endif
            </div>

            {{-- Coordonnées --}}
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <i class="fas fa-address-book me-2 text-primary"></i>Coordonnées
                </div>
                <div class="sidebar-card-body">
                    <div class="info-item">
                        <div class="info-item-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="info-item-label">Email professionnel</div>
                            <div class="info-item-value">{{ $agent->email_professionnel ?? 'N/A' }}</div>
                        </div>
                    </div>
                    @if($agent->email_prive)
                    <div class="info-item">
                        <div class="info-item-icon" style="background:#fef3e2;color:#e67e22;">
                            <i class="fas fa-envelope-open"></i>
                        </div>
                        <div>
                            <div class="info-item-label">Email privé</div>
                            <div class="info-item-value">{{ $agent->email_prive }}</div>
                        </div>
                    </div>
                    @endif
                    <div class="info-item">
                        <div class="info-item-icon" style="background:#e8f8e8;color:#28a745;">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <div class="info-item-label">Téléphone</div>
                            <div class="info-item-value">{{ $agent->telephone ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-icon" style="background:#f3e8ff;color:#7c3aed;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <div class="info-item-label">Adresse</div>
                            <div class="info-item-value">{{ $agent->adresse ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statistiques rapides --}}
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>Aperçu
                </div>
                <div class="sidebar-card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-badge">
                                <div class="stat-number">{{ $agent->documents->count() }}</div>
                                <div class="stat-label">Documents</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-badge">
                                <div class="stat-number">{{ $agent->affectations->count() }}</div>
                                <div class="stat-label">Affectations</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-badge">
                                <div class="stat-number" style="color:#28a745;">{{ $agent->pointages->count() }}</div>
                                <div class="stat-label">Pointages</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-badge">
                                <div class="stat-number" style="color:#e67e22;">{{ $agent->requests->count() }}</div>
                                <div class="stat-label">Demandes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Colonne droite (Contenu) ═══ --}}
        <div class="col-lg-8">

            {{-- Profil Personnel --}}
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
                                    <div class="info-item-value">
                                        {{ $agent->date_naissance ? $agent->date_naissance->format('d/m/Y') : ($agent->annee_naissance ?? 'N/A') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-item-icon"><i class="fas fa-map-pin"></i></div>
                                <div>
                                    <div class="info-item-label">Lieu de naissance</div>
                                    <div class="info-item-value">{{ $agent->lieu_naissance ?? 'N/A' }}</div>
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
                                    <div class="info-item-value">{{ $agent->sexe == 'M' ? 'Masculin' : ($agent->sexe == 'F' ? 'Féminin' : ($agent->sexe ?? 'N/A')) }}</div>
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
                                    <div class="info-item-value">{{ ucfirst($agent->situation_familiale ?? 'N/A') }}</div>
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
                                    <div class="info-item-value">{{ $agent->nombre_enfants ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informations Professionnelles --}}
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
                                    <div class="info-item-value">{{ $agent->fonction ?? ($agent->poste_actuel ?? 'N/A') }}</div>
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
                                    <div class="info-item-value">{{ $agent->organe ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-item-icon" style="background:#e3f2fd;color:#1976d2;">
                                    <i class="fas fa-sitemap"></i>
                                </div>
                                <div>
                                    <div class="info-item-label">Département</div>
                                    <div class="info-item-value">{{ $agent->departement?->nom_dept ?? 'N/A' }}</div>
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
                                    <div class="info-item-value">{{ $agent->province?->nom ?? 'N/A' }}</div>
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
                                    <div class="info-item-value">{{ $agent->date_embauche ? $agent->date_embauche->format('d/m/Y') : 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-item-icon" style="background:#fff8e1;color:#f9a825;">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <div class="info-item-label">Grade</div>
                                    <div class="info-item-value">{{ $agent->grade?->nom ?? ($agent->grade_etat ?? 'N/A') }}</div>
                                </div>
                            </div>
                        </div>
                        @if($agent->matricule_etat)
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-item-icon" style="background:#fce4ec;color:#c62828;">
                                    <i class="fas fa-hashtag"></i>
                                </div>
                                <div>
                                    <div class="info-item-label">Matricule État</div>
                                    <div class="info-item-value">{{ $agent->matricule_etat }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($agent->annee_engagement_programme)
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-item-icon" style="background:#e0f2f1;color:#00897b;">
                                    <i class="fas fa-handshake"></i>
                                </div>
                                <div>
                                    <div class="info-item-label">Année engagement programme</div>
                                    <div class="info-item-value">{{ $agent->annee_engagement_programme }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Formation --}}
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
                                    <div class="info-item-label">Niveau d'études</div>
                                    <div class="info-item-value">{{ $agent->niveau_etudes ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-item-icon" style="background:#fff3e0;color:#e67e22;">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <div class="info-item-label">Domaine d'études</div>
                                    <div class="info-item-value">{{ $agent->domaine_etudes ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                        @if($agent->institution)
                        <div class="col-md-12">
                            <div class="info-item">
                                <div class="info-item-icon" style="background:#fff3e0;color:#e67e22;">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div>
                                    <div class="info-item-label">Institution</div>
                                    <div class="info-item-value">{{ $agent->institution->nom }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Parcours / Carrière --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon" style="background:linear-gradient(135deg,#00897b,#00695c);">
                        <i class="fas fa-route"></i>
                    </div>
                    <h5>Parcours <span class="badge bg-light text-dark ms-2" style="font-size:.75rem;">{{ $agent->affectations->count() }}</span></h5>
                </div>
                <div class="info-card-body">
                    @if($agent->affectations->count() > 0)
                        @foreach($agent->affectations->sortByDesc('date_debut') as $affectation)
                            <div class="d-flex align-items-start mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="me-3">
                                    @if($affectation->actif)
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success" style="width:36px;height:36px;">
                                            <i class="fas fa-briefcase text-white" style="font-size:.85rem;"></i>
                                        </span>
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary" style="width:36px;height:36px;">
                                            <i class="fas fa-history text-white" style="font-size:.85rem;"></i>
                                        </span>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $affectation->fonction?->nom ?? 'Fonction non définie' }}</h6>
                                    <div class="d-flex gap-2 flex-wrap mb-1">
                                        <span class="badge bg-primary" style="font-size:.7rem;">{{ $affectation->niveau_administratif_label }}</span>
                                        @if($affectation->actif)
                                            <span class="badge bg-success" style="font-size:.7rem;">En cours</span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size:.7rem;">Terminé</span>
                                        @endif
                                    </div>
                                    @if($affectation->department)
                                        <small class="text-muted d-block"><i class="fas fa-building me-1"></i>{{ $affectation->department->nom }}</small>
                                    @endif
                                    @if($affectation->province)
                                        <small class="text-muted d-block"><i class="fas fa-map-marker-alt me-1"></i>{{ $affectation->province->nom }}</small>
                                    @endif
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $affectation->date_debut?->format('d/m/Y') ?? '?' }}
                                        @if($affectation->date_fin)
                                            - {{ $affectation->date_fin->format('d/m/Y') }}
                                        @elseif($affectation->actif)
                                            - Aujourd'hui
                                        @endif
                                    </small>
                                    @if($affectation->remarque)
                                        <small class="text-muted fst-italic d-block mt-1">{{ $affectation->remarque }}</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-route fa-2x text-muted mb-2 d-block opacity-50"></i>
                            <p class="text-muted small mb-0">Aucun parcours enregistré</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Documents --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon" style="background:linear-gradient(135deg,#28a745,#1e7e34);">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h5>Documents <span class="badge bg-light text-dark ms-2" style="font-size:.75rem;">{{ $agent->documents->count() }}</span></h5>
                </div>
                <div class="info-card-body">
                    @if($agent->documents->count() > 0)
                        <div class="table-responsive">
                            <table class="table doc-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Document</th>
                                        <th>Catégorie</th>
                                        <th>Date</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($agent->documents->take(5) as $doc)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:32px;height:32px;border-radius:8px;background:#e6f3ff;display:flex;align-items:center;justify-content:center;">
                                                    <i class="fas fa-file-alt text-primary" style="font-size:.85rem;"></i>
                                                </div>
                                                <span class="fw-600" style="font-size:.9rem;">{{ Str::limit($doc->nom_document, 30) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border" style="font-size:.78rem;">
                                                {{ $doc->categorie }}
                                            </span>
                                        </td>
                                        <td style="font-size:.85rem;color:#6c757d;">{{ $doc->created_at->format('d/m/Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-dl btn-sm">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($agent->documents->count() > 5)
                        <div class="text-center mt-3">
                            <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                                <i class="fas fa-arrow-right me-1"></i>Voir tous les documents
                            </a>
                        </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <div style="width:64px;height:64px;border-radius:50%;background:#f0f7ff;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
                                <i class="fas fa-folder-open text-primary" style="font-size:1.5rem;"></i>
                            </div>
                            <p class="text-muted mb-0">Aucun document pour le moment</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
