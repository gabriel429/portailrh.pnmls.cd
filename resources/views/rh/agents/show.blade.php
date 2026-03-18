@extends('layouts.app')

@section('title', $agent->prenom . ' ' . $agent->nom . ' - Portail RH PNMLS')

@section('css')
<style>
    .agent-header {
        background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .agent-header .badge { font-size: 0.85rem; }
    .nav-tabs .nav-link { font-weight: 500; color: #666; }
    .nav-tabs .nav-link.active { color: #0077B5; border-bottom: 3px solid #0077B5; }
    .tab-badge { font-size: 0.7rem; vertical-align: middle; }
    .timeline-item { position: relative; padding-left: 30px; padding-bottom: 20px; border-left: 2px solid #e5e5e5; }
    .timeline-item:last-child { border-left: 2px solid transparent; }
    .timeline-dot { position: absolute; left: -8px; top: 4px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; }
    .timeline-dot.active { background-color: #28a745; }
    .timeline-dot.ended { background-color: #6c757d; }
    .message-card { border-left: 4px solid #0077B5; background: #f8f9fa; border-radius: 4px; padding: 15px; margin-bottom: 15px; }
    .message-card.unread { border-left-color: #ffc107; background: #fffef5; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    {{-- En-tete agent --}}
    <div class="agent-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                @if($agent->photo)
                    <img src="{{ asset($agent->photo) }}" alt="{{ $agent->prenom }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                @else
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: rgba(255,255,255,0.15);">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                @endif
                <div>
                    <h2 class="mb-1">{{ $agent->prenom }} {{ $agent->nom }}</h2>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="badge bg-light text-dark">{{ $agent->id_agent }}</span>
                        @if($agent->organe)
                            <span class="badge bg-info">{{ $agent->organe }}</span>
                        @endif
                        @if($agent->statut === 'actif')
                            <span class="badge bg-success">Actif</span>
                        @elseif($agent->statut === 'suspendu')
                            <span class="badge bg-warning text-dark">Suspendu</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($agent->statut) }}</span>
                        @endif
                    </div>
                    @if($agent->fonction)
                        <small class="opacity-75 mt-1 d-block">{{ $agent->fonction }}</small>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('rh.agents.print', $agent) }}" class="btn btn-light btn-sm" target="_blank">
                    <i class="fas fa-print me-1"></i> Imprimer
                </a>
                <a href="{{ route('rh.agents.edit', $agent) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit me-1"></i> Modifier
                </a>
                <a href="{{ route('rh.agents.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Colonne principale avec onglets --}}
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3" id="agentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tab === 'informations' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-informations" type="button">
                        <i class="fas fa-user me-1"></i> Informations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tab === 'demandes' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-demandes" type="button">
                        <i class="fas fa-file-signature me-1"></i> Demandes
                        @if($agent->requests->where('statut', 'en_attente')->count() > 0)
                            <span class="badge bg-warning text-dark tab-badge">{{ $agent->requests->where('statut', 'en_attente')->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tab === 'parcours' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-parcours" type="button">
                        <i class="fas fa-route me-1"></i> Parcours
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tab === 'documents' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">
                        <i class="fas fa-folder me-1"></i> Documents
                        <span class="badge bg-secondary tab-badge">{{ $agent->documents->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tab === 'messages' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-messages" type="button">
                        <i class="fas fa-envelope me-1"></i> Messages
                        @if($agent->messages->where('lu', false)->count() > 0)
                            <span class="badge bg-danger tab-badge">{{ $agent->messages->where('lu', false)->count() }}</span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="agentTabContent">

                {{-- ONGLET 1 : INFORMATIONS --}}
                <div class="tab-pane fade {{ $tab === 'informations' ? 'show active' : '' }}" id="tab-informations" role="tabpanel">
                    {{-- Informations personnelles --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0"><i class="fas fa-user me-2 text-primary"></i>Informations Personnelles</h5>
                        </div>
                        <div class="card-body">
                            {{-- Identité --}}
                            <div class="row mb-2">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Prénom</label>
                                    <p class="mb-0 fw-semibold">{{ $agent->prenom }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Post-nom</label>
                                    <p class="mb-0 fw-semibold">{{ $agent->postnom ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Nom</label>
                                    <p class="mb-0 fw-semibold">{{ $agent->nom }}</p>
                                </div>
                            </div>

                            <hr class="my-2">

                            {{-- Naissance --}}
                            <div class="row mb-2">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Date de naissance</label>
                                    <p class="mb-0">{{ $agent->date_naissance?->format('d/m/Y') ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Lieu de naissance</label>
                                    <p class="mb-0">{{ $agent->lieu_naissance ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Sexe</label>
                                    <p class="mb-0">{{ $agent->sexe === 'M' ? 'Masculin' : ($agent->sexe === 'F' ? 'Féminin' : ($agent->sexe ?? 'N/A')) }}</p>
                                </div>
                            </div>

                            <hr class="my-2">

                            {{-- Famille --}}
                            <div class="row mb-2">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Situation familiale</label>
                                    <p class="mb-0">{{ $agent->situation_familiale ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Nombre d'enfants</label>
                                    <p class="mb-0">{{ $agent->nombre_enfants ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Adresse</label>
                                    <p class="mb-0">{{ $agent->adresse ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <hr class="my-2">

                            {{-- Contact --}}
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Téléphone</label>
                                    <p class="mb-0">{{ $agent->telephone ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">E-mail privé</label>
                                    <p class="mb-0">{{ $agent->email_prive ? $agent->email_prive : 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">E-mail institutionnel</label>
                                    <p class="mb-0">{{ $agent->email_professionnel ? $agent->email_professionnel : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Informations professionnelles --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0"><i class="fas fa-briefcase me-2 text-primary"></i>Informations Professionnelles</h5>
                        </div>
                        <div class="card-body">
                            {{-- Poste --}}
                            <div class="row mb-2">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Organe</label>
                                    <p class="mb-0 fw-semibold">{{ $agent->organe ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Fonction</label>
                                    <p class="mb-0 fw-semibold">{{ $agent->fonction ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Date d'embauche</label>
                                    <p class="mb-0">{{ $agent->date_embauche?->format('d/m/Y') ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <hr class="my-2">

                            {{-- Localisation --}}
                            <div class="row mb-2">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Province</label>
                                    <p class="mb-0">{{ $agent->province?->nom ?? (str_contains($agent->organe ?? '', 'National') ? 'National' : 'N/A') }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Département</label>
                                    <p class="mb-0">{{ $agent->departement?->nom ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Année d'engagement</label>
                                    <p class="mb-0">{{ $agent->annee_engagement_programme ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Ancienneté</label>
                                    <p class="mb-0 fw-semibold">
                                        @if($agent->annee_engagement_programme)
                                            @php $anciennete = now()->year - $agent->annee_engagement_programme; @endphp
                                            {{ $anciennete }} an{{ $anciennete > 1 ? 's' : '' }}
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <hr class="my-2">

                            {{-- Matricule et grade --}}
                            <div class="row mb-2">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Matricule de l'État</label>
                                    <p class="mb-0">{{ $agent->matricule_etat ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Provenance matricule</label>
                                    <p class="mb-0">{{ $agent->institution?->nom ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Grade de l'État</label>
                                    <p class="mb-0">{{ $agent->grade?->libelle ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <hr class="my-2">

                            {{-- Études --}}
                            <div class="row mb-2">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Niveau d'études</label>
                                    <p class="mb-0">{{ $agent->niveau_etudes ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small">Domaine d'études</label>
                                    <p class="mb-0">{{ $agent->domaine_etudes ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ONGLET 2 : DEMANDES --}}
                <div class="tab-pane fade {{ $tab === 'demandes' ? 'show active' : '' }}" id="tab-demandes" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-file-signature me-2 text-primary"></i>Demandes ({{ $agent->requests->count() }})</h5>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if($agent->requests->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th>Date debut</th>
                                                <th>Date fin</th>
                                                <th>Statut</th>
                                                <th>Remarques</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($agent->requests->sortByDesc('created_at') as $request)
                                                <tr>
                                                    <td><strong>{{ ucfirst($request->type) }}</strong></td>
                                                    <td>{{ Str::limit($request->description, 50) }}</td>
                                                    <td>{{ $request->date_debut?->format('d/m/Y') ?? 'N/A' }}</td>
                                                    <td>{{ $request->date_fin?->format('d/m/Y') ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($request->statut === 'en_attente')
                                                            <span class="badge bg-warning text-dark">En attente</span>
                                                        @elseif($request->statut === 'approuve')
                                                            <span class="badge bg-success">Approuve</span>
                                                        @elseif($request->statut === 'rejete')
                                                            <span class="badge bg-danger">Rejete</span>
                                                        @else
                                                            <span class="badge bg-secondary">Annule</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ Str::limit($request->remarques, 30) ?? '-' }}</td>
                                                    <td><small class="text-muted">{{ $request->created_at?->format('d/m/Y') }}</small></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-file-signature fa-3x text-muted mb-3 d-block opacity-50"></i>
                                    <h6 class="text-muted">Aucune demande</h6>
                                    <p class="text-muted small">Cet agent n'a soumis aucune demande</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ONGLET 3 : PARCOURS / CARRIERE --}}
                <div class="tab-pane fade {{ $tab === 'parcours' ? 'show active' : '' }}" id="tab-parcours" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-route me-2 text-primary"></i>Parcours et Carriere</h5>
                            <a href="{{ route('rh.affectations.create', ['agent_id' => $agent->id]) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Ajouter un poste
                            </a>
                        </div>
                        <div class="card-body">
                            @if($agent->affectations->count() > 0)
                                <div class="ps-2">
                                    @foreach($agent->affectations->sortByDesc('date_debut') as $affectation)
                                        <div class="timeline-item">
                                            <div class="timeline-dot {{ $affectation->actif ? 'active' : 'ended' }}"></div>
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <h6 class="mb-1">
                                                        {{ $affectation->fonction?->nom ?? 'Fonction non definie' }}
                                                    </h6>
                                                    <div class="d-flex gap-2 flex-wrap mb-1">
                                                        <span class="badge bg-primary">{{ $affectation->niveau_administratif_label }}</span>
                                                        <span class="badge bg-outline-secondary border">{{ ucfirst($affectation->niveau) }}</span>
                                                    </div>
                                                    @if($affectation->department)
                                                        <small class="text-muted d-block"><i class="fas fa-building me-1"></i>{{ $affectation->department->nom }}</small>
                                                    @endif
                                                    @if($affectation->province)
                                                        <small class="text-muted d-block"><i class="fas fa-map-marker-alt me-1"></i>{{ $affectation->province->nom }}</small>
                                                    @endif
                                                    @if($affectation->remarque)
                                                        <small class="text-muted fst-italic d-block mt-1">{{ $affectation->remarque }}</small>
                                                    @endif
                                                </div>
                                                <div class="text-end">
                                                    @if($affectation->actif)
                                                        <span class="badge bg-success">En cours</span>
                                                    @else
                                                        <span class="badge bg-secondary">Termine</span>
                                                    @endif
                                                    <small class="text-muted d-block mt-1">
                                                        {{ $affectation->date_debut?->format('d/m/Y') ?? '?' }}
                                                        @if($affectation->date_fin)
                                                            - {{ $affectation->date_fin->format('d/m/Y') }}
                                                        @elseif($affectation->actif)
                                                            - Aujourd'hui
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-route fa-3x text-muted mb-3 d-block opacity-50"></i>
                                    <h6 class="text-muted">Aucune affectation</h6>
                                    <p class="text-muted small">Le parcours de cet agent n'a pas encore ete renseigne</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ONGLET 4 : DOCUMENTS --}}
                <div class="tab-pane fade {{ $tab === 'documents' ? 'show active' : '' }}" id="tab-documents" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-folder me-2 text-primary"></i>Documents ({{ $agent->documents->count() }})</h5>
                                <a href="{{ route('documents.create', ['agent_id' => $agent->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Ajouter
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($agent->documents->count() > 0)
                                @php
                                    $docTypes = [
                                        'identite' => ['label' => 'Identite', 'icon' => 'fa-id-card', 'color' => 'text-danger'],
                                        'parcours' => ['label' => 'Parcours', 'icon' => 'fa-graduation-cap', 'color' => 'text-info'],
                                        'carriere' => ['label' => 'Carriere', 'icon' => 'fa-briefcase', 'color' => 'text-warning'],
                                        'mission' => ['label' => 'Missions', 'icon' => 'fa-plane', 'color' => 'text-success'],
                                    ];
                                @endphp

                                @foreach($docTypes as $type => $config)
                                    <div class="mb-4">
                                        <h6 class="mb-3">
                                            <i class="fas {{ $config['icon'] }} me-2 {{ $config['color'] }}"></i>
                                            <strong>{{ $config['label'] }}</strong>
                                            <span class="badge bg-secondary">{{ $agent->documents->where('type', $type)->count() }}</span>
                                        </h6>
                                        @forelse($agent->documents->where('type', $type) as $document)
                                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                                <div>
                                                    <p class="mb-1"><strong>{{ $document->description }}</strong></p>
                                                    <small class="text-muted">{{ $document->created_at?->format('d/m/Y H:i') }}</small>
                                                </div>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('documents.show', $document) }}" class="btn btn-outline-primary" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success" title="Telecharger">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" style="display:inline;" onsubmit="return confirm('Supprimer ce document ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-muted small ms-3">Aucun document de type {{ $config['label'] }}</p>
                                        @endforelse
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-folder fa-3x text-muted mb-3 d-block opacity-50"></i>
                                    <h6 class="text-muted">Aucun document</h6>
                                    <p class="text-muted small mb-3">Cet agent n'a pas encore de documents</p>
                                    <a href="{{ route('documents.create', ['agent_id' => $agent->id]) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Ajouter un document
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ONGLET 5 : MESSAGES --}}
                <div class="tab-pane fade {{ $tab === 'messages' ? 'show active' : '' }}" id="tab-messages" role="tabpanel">
                    {{-- Formulaire d'envoi --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0"><i class="fas fa-paper-plane me-2 text-primary"></i>Envoyer un message</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('rh.agents.messages.store', $agent) }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="sujet" class="form-label">Sujet</label>
                                    <input type="text" class="form-control @error('sujet') is-invalid @enderror" id="sujet" name="sujet" value="{{ old('sujet') }}" placeholder="Sujet du message" required>
                                    @error('sujet')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="contenu" class="form-label">Message</label>
                                    <textarea class="form-control @error('contenu') is-invalid @enderror" id="contenu" name="contenu" rows="4" placeholder="Contenu du message..." required>{{ old('contenu') }}</textarea>
                                    @error('contenu')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Envoyer
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Historique des messages --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Historique des messages ({{ $agent->messages->count() }})</h5>
                        </div>
                        <div class="card-body">
                            @if($agent->messages->count() > 0)
                                @foreach($agent->messages->sortByDesc('created_at') as $msg)
                                    <div class="message-card {{ !$msg->lu ? 'unread' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-0">{{ $msg->sujet }}</h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i>{{ $msg->sender?->name ?? 'Systeme' }}
                                                    &bull; {{ $msg->created_at?->format('d/m/Y H:i') }}
                                                </small>
                                            </div>
                                            @if(!$msg->lu)
                                                <span class="badge bg-warning text-dark">Non lu</span>
                                            @else
                                                <span class="badge bg-light text-muted">Lu</span>
                                            @endif
                                        </div>
                                        <p class="mb-0">{{ $msg->contenu }}</p>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-envelope fa-3x text-muted mb-3 d-block opacity-50"></i>
                                    <h6 class="text-muted">Aucun message</h6>
                                    <p class="text-muted small">Aucun message n'a ete envoye a cet agent</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Colonne laterale --}}
        <div class="col-lg-4">
            {{-- Resume rapide --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Resume</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">ID Agent</small>
                        <p class="mb-0 fw-bold">{{ $agent->id_agent }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Membre depuis</small>
                        <p class="mb-0">{{ $agent->created_at?->format('d/m/Y') }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Derniere modification</small>
                        <p class="mb-0">{{ $agent->updated_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- Statistiques --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Statistiques</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Documents</span>
                        <strong>{{ $agent->documents->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Demandes totales</span>
                        <strong>{{ $agent->requests->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Demandes en attente</span>
                        <strong class="text-warning">{{ $agent->requests->where('statut', 'en_attente')->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Affectations</span>
                        <strong>{{ $agent->affectations->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Messages</span>
                        <strong>{{ $agent->messages->count() }}</strong>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('rh.agents.edit', $agent) }}" class="btn btn-warning btn-sm w-100 mb-2">
                        <i class="fas fa-edit me-2"></i> Modifier
                    </a>
                    <form method="POST" action="{{ route('rh.agents.destroy', $agent) }}" style="display:inline;" class="w-100">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm w-100"
                            onclick="return confirm('Supprimer cet agent ?');">
                            <i class="fas fa-trash me-2"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
