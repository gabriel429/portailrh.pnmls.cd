@extends('layouts.app')

@use('Illuminate\Support\Str')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            @if($agent->photo)
                <img src="{{ asset($agent->photo) }}" alt="{{ $agent->prenom }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
            @else
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="fas fa-user fa-2x text-muted"></i>
                </div>
            @endif
            <div>
                <h2 class="mb-0">{{ $agent->prenom }} {{ $agent->nom }}</h2>
                <p class="text-muted mb-0">{{ $agent->matricule_pnmls }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rh.agents.edit', $agent) }}" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i> Modifier
            </a>
            <a href="{{ route('rh.agents.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Informations personnelles -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-user me-2 text-primary"></i>Informations Personnelles</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Prénom</label>
                            <p class="mb-0">{{ $agent->prenom }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Nom</label>
                            <p class="mb-0">{{ $agent->nom }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Post nom</label>
                            <p class="mb-0">{{ $agent->postnom ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Email</label>
                            <p class="mb-0"><a href="mailto:{{ $agent->email }}">{{ $agent->email }}</a></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Téléphone</label>
                            <p class="mb-0">{{ $agent->telephone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Date de Naissance</label>
                            <p class="mb-0">{{ $agent->date_naissance?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Année de naissance</label>
                            <p class="mb-0">{{ $agent->annee_naissance ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Lieu de Naissance</label>
                            <p class="mb-0">{{ $agent->lieu_naissance ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted small">Adresse</label>
                            <p class="mb-0">{{ $agent->adresse ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Sexe</label>
                            <p class="mb-0">{{ $agent->sexe ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">E-mail privé</label>
                            <p class="mb-0">{{ $agent->email_prive ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">E-mail professionnel</label>
                            <p class="mb-0">{{ $agent->email_professionnel ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations professionnelles -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2 text-primary"></i>Informations Professionnelles</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Date d'Embauche</label>
                            <p class="mb-0">{{ $agent->date_embauche?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Poste Actuel</label>
                            <p class="mb-0">{{ $agent->poste_actuel ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Organe</label>
                            <p class="mb-0">{{ $agent->organe ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Fonction</label>
                            <p class="mb-0">{{ $agent->fonction ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Grade de l'État</label>
                            <p class="mb-0">{{ $agent->grade_etat ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Matricule de l'État</label>
                            <p class="mb-0">{{ $agent->matricule_etat ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Provenance matricule</label>
                            <p class="mb-0">{{ $agent->provenance_matricule ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Niveau d'études</label>
                            <p class="mb-0">{{ $agent->niveau_etudes ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Année d'engagement au programme</label>
                            <p class="mb-0">{{ $agent->annee_engagement_programme ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Province</label>
                            <p class="mb-0">{{ $agent->province?->nom_province ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Département</label>
                            <p class="mb-0">{{ $agent->departement?->nom_dept ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Rôle</label>
                            <p class="mb-0">
                                @if($agent->role)
                                    <span class="badge bg-info">{{ $agent->role->nom_role }}</span>
                                @else
                                    <span class="badge bg-secondary">Non assigné</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Statut</label>
                            <p class="mb-0">
                                @if($agent->statut === 'actif')
                                    <span class="badge bg-success">Actif</span>
                                @elseif($agent->statut === 'suspendu')
                                    <span class="badge bg-warning">Suspendu</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($agent->statut) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-file me-2 text-primary"></i>Documents ({{ $agent->documents->count() }})</h5>
                        <a href="{{ route('documents.create', ['agent_id' => $agent->id]) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Ajouter
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($agent->documents->count() > 0)
                        <!-- Section Identité -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-id-card me-2 text-danger"></i>
                                <strong>Identité</strong>
                                <span class="badge bg-secondary">{{ $agent->documents->where('type', 'identite')->count() }}</span>
                            </h6>
                            @forelse($agent->documents->where('type', 'identite') as $document)
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <div>
                                        <p class="mb-1"><strong>{{ $document->description }}</strong></p>
                                        <small class="text-muted">{{ $document->created_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('documents.show', $document) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form method="POST" action="{{ route('documents.destroy', $document) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small ms-3">Aucun document d'identité</p>
                            @endforelse
                        </div>

                        <!-- Section Parcours -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-graduation-cap me-2 text-info"></i>
                                <strong>Parcours</strong>
                                <span class="badge bg-secondary">{{ $agent->documents->where('type', 'parcours')->count() }}</span>
                            </h6>
                            @forelse($agent->documents->where('type', 'parcours') as $document)
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <div>
                                        <p class="mb-1"><strong>{{ $document->description }}</strong></p>
                                        <small class="text-muted">{{ $document->created_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('documents.show', $document) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form method="POST" action="{{ route('documents.destroy', $document) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small ms-3">Aucun document de parcours</p>
                            @endforelse
                        </div>

                        <!-- Section Carrière -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-briefcase me-2 text-warning"></i>
                                <strong>Carrière</strong>
                                <span class="badge bg-secondary">{{ $agent->documents->where('type', 'carriere')->count() }}</span>
                            </h6>
                            @forelse($agent->documents->where('type', 'carriere') as $document)
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <div>
                                        <p class="mb-1"><strong>{{ $document->description }}</strong></p>
                                        <small class="text-muted">{{ $document->created_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('documents.show', $document) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form method="POST" action="{{ route('documents.destroy', $document) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small ms-3">Aucun document de carrière</p>
                            @endforelse
                        </div>

                        <!-- Section Missions -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-plane me-2 text-success"></i>
                                <strong>Missions</strong>
                                <span class="badge bg-secondary">{{ $agent->documents->where('type', 'mission')->count() }}</span>
                            </h6>
                            @forelse($agent->documents->where('type', 'mission') as $document)
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <div>
                                        <p class="mb-1"><strong>{{ $document->description }}</strong></p>
                                        <small class="text-muted">{{ $document->created_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('documents.show', $document) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form method="POST" action="{{ route('documents.destroy', $document) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small ms-3">Aucun document de mission</p>
                            @endforelse
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file fa-4x text-muted mb-3 d-block opacity-50"></i>
                            <h6 class="text-muted">Aucun document</h6>
                            <p class="text-muted small mb-3">Cet agent n'a pas encore de documents dans le système</p>
                            <a href="{{ route('documents.create', ['agent_id' => $agent->id]) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Ajouter un document
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Résumé rapide -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Résumé</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Matricule</small>
                        <p class="mb-0 fw-bold">{{ $agent->matricule_pnmls }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">ID</small>
                        <p class="mb-0 fw-bold">#{{ $agent->id }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Membre depuis</small>
                        <p class="mb-0">{{ $agent->created_at?->format('d/m/Y') }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Dernière modification</small>
                        <p class="mb-0">{{ $agent->updated_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Statistiques demandes -->
            @if($agent->requests->count() > 0)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Demandes</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Total:</strong> {{ $agent->requests->count() }} demandes
                        </p>
                        <small class="text-muted">Consultez le profil pour voir plus de détails sur les demandes</small>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Actions</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('rh.agents.destroy', $agent) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-block btn-danger btn-sm w-100"
                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet agent?');">
                            <i class="fas fa-trash me-2"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
